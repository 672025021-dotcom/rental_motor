<?php

require_once __DIR__ . '/../database.php';

class User
{
    public static function registerUser(
        string $full_name,
        string $email,
        string $password,
        string $phone_wa,
        ?string $address
    ): int {
        $connection = getDBConnection();
        $statement = $connection->prepare(
            'INSERT INTO users (full_name, email, password_hash, phone_wa, address)
             VALUES (:full_name, :email, :password_hash, :phone_wa, :address)'
        );
        $statement->execute([
            'full_name' => $full_name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'phone_wa' => $phone_wa,
            'address' => $address,
        ]);

        return (int) $connection->lastInsertId();
    }

    public static function getUserByEmail(string $email): ?array
    {
        $statement = getDBConnection()->prepare(
            'SELECT id, full_name, email, password_hash, phone_wa, address, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public static function getUserById(int $id): ?array
    {
        $statement = getDBConnection()->prepare(
            'SELECT id, full_name, email, phone_wa, address, role, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public static function updateUserProfile(int $user_id, string $full_name, string $phone_wa, ?string $address): bool
    {
        if ($user_id < 1) {
            return false;
        }

        $statement = getDBConnection()->prepare(
            'UPDATE users
             SET full_name = :full_name, phone_wa = :phone_wa, address = :address
             WHERE id = :user_id'
        );
        $statement->execute([
            'full_name' => $full_name,
            'phone_wa' => $phone_wa,
            'address' => $address,
            'user_id' => $user_id,
        ]);

        return $statement->rowCount() === 1 || self::getUserById($user_id) !== null;
    }

    public static function getUserPasswordHash(int $user_id): ?string
    {
        if ($user_id < 1) {
            return null;
        }

        $statement = getDBConnection()->prepare('SELECT password_hash FROM users WHERE id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $user_id]);
        $passwordHash = $statement->fetchColumn();

        return $passwordHash === false ? null : (string) $passwordHash;
    }

    public static function updateUserPassword(int $user_id, string $new_password_hash): bool
    {
        if ($user_id < 1 || $new_password_hash === '') {
            return false;
        }

        $statement = getDBConnection()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :user_id'
        );
        $statement->execute([
            'password_hash' => $new_password_hash,
            'user_id' => $user_id,
        ]);

        return $statement->rowCount() === 1;
    }

    public static function isEmailRegistered(string $email): bool
    {
        $statement = getDBConnection()->prepare(
            'SELECT 1
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }
}