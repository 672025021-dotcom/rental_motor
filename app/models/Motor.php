<?php

require_once __DIR__ . '/../database.php';

class Motor
{
    private const TYPES = ['matic', 'manual', 'sport'];

    public static function getAllAvailableMotors(
        ?string $type_filter = null,
        ?string $search_keyword = null,
        ?int $limit = null
    ): array {
        $conditions = ["status = 'tersedia'"];
        $parameters = [];

        if ($type_filter !== null && $type_filter !== '') {
            if (!in_array($type_filter, self::TYPES, true)) {
                throw new InvalidArgumentException('Tipe motor tidak valid.');
            }

            $conditions[] = 'type = :type';
            $parameters['type'] = $type_filter;
        }

        $search_keyword = trim((string) $search_keyword);
        if ($search_keyword !== '') {
            $conditions[] = '(brand LIKE :search_brand OR model LIKE :search_model)';
            $searchTerm = '%' . $search_keyword . '%';
            $parameters['search_brand'] = $searchTerm;
            $parameters['search_model'] = $searchTerm;
        }

        $sql = 'SELECT id, brand, model, plate_number, type, engine_cc, year, color,
                       description, price_per_day, image, status, created_at, updated_at
                FROM motorcycles
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY created_at DESC, id DESC';
        if ($limit !== null) {
            if ($limit < 1) {
                throw new InvalidArgumentException('Batas jumlah motor harus lebih besar dari nol.');
            }
            $sql .= ' LIMIT :limit';
        }

        $statement = getDBConnection()->prepare($sql);
        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        if ($limit !== null) {
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function getMotorById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $statement = getDBConnection()->prepare(
            'SELECT id, brand, model, plate_number, type, engine_cc, year, color,
                    description, price_per_day, image, status, created_at, updated_at
             FROM motorcycles
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $motor = $statement->fetch();

        return $motor === false ? null : $motor;
    }

    public static function getRelatedMotors(string $type, int $current_id, int $limit = 3): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipe motor tidak valid.');
        }
        if ($current_id < 1 || $limit < 1) {
            throw new InvalidArgumentException('ID motor dan batas rekomendasi harus lebih besar dari nol.');
        }

        $statement = getDBConnection()->prepare(
            "SELECT id, brand, model, plate_number, type, engine_cc, year, color,
                    description, price_per_day, image, status, created_at, updated_at
             FROM motorcycles
             WHERE type = :type AND status = 'tersedia' AND id <> :current_id
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );
        $statement->bindValue(':type', $type, PDO::PARAM_STR);
        $statement->bindValue(':current_id', $current_id, PDO::PARAM_INT);
        $statement->bindValue(':limit', min($limit, 12), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}