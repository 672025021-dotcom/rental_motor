<?php

require_once __DIR__ . '/../database.php';

class BookingUnavailableException extends RuntimeException
{
}

class BookingStateException extends RuntimeException
{
}

class Booking
{
    public static function checkMotorAvailability(int $motor_id, string $start_date, string $end_date): bool
    {
        self::validateBookingDates($start_date, $end_date);
        if ($motor_id < 1) {
            return false;
        }

        return self::isMotorAvailable(getDBConnection(), $motor_id, $start_date, $end_date);
    }

    public static function createBooking(array $data): int
    {
        foreach ([
            'booking_code',
            'user_id',
            'motorcycle_id',
            'start_date',
            'end_date',
            'total_days',
            'price_per_day',
            'total_price',
            'pickup_method',
            'delivery_address',
            'notes',
            'ktp_file',
            'sim_file',
        ] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new InvalidArgumentException('Data booking tidak lengkap.');
            }
        }

        $motorId = (int) $data['motorcycle_id'];
        $userId = (int) $data['user_id'];
        $startDate = (string) $data['start_date'];
        $endDate = (string) $data['end_date'];
        self::validateBookingDates($startDate, $endDate);

        if ($motorId < 1 || $userId < 1) {
            throw new InvalidArgumentException('Pengguna dan motor harus valid.');
        }
        if (!in_array($data['pickup_method'], ['ambil_sendiri', 'diantar'], true)) {
            throw new InvalidArgumentException('Metode pengambilan tidak valid.');
        }

        $connection = getDBConnection();
        $connection->beginTransaction();

        try {
            $motorStatement = $connection->prepare(
                'SELECT status
                 FROM motorcycles
                 WHERE id = :motor_id
                 FOR UPDATE'
            );
            $motorStatement->execute(['motor_id' => $motorId]);
            $motorStatus = $motorStatement->fetchColumn();

            if ($motorStatus !== 'tersedia') {
                throw new BookingUnavailableException('Motor tidak tersedia untuk dipesan.');
            }
            if (!self::isMotorAvailable($connection, $motorId, $startDate, $endDate)) {
                throw new BookingUnavailableException('Motor sudah dipesan pada rentang tanggal tersebut.');
            }

            $insertBooking = $connection->prepare(
                "INSERT INTO bookings (
                    booking_code, user_id, motorcycle_id, start_date, end_date,
                    total_days, price_per_day, total_price, pickup_method,
                    delivery_address, notes, ktp_file, sim_file, status, payment_status
                ) VALUES (
                    :booking_code, :user_id, :motorcycle_id, :start_date, :end_date,
                    :total_days, :price_per_day, :total_price, :pickup_method,
                    :delivery_address, :notes, :ktp_file, :sim_file, 'pending', 'belum_bayar'
                )"
            );
            $insertBooking->execute([
                'booking_code' => (string) $data['booking_code'],
                'user_id' => $userId,
                'motorcycle_id' => $motorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => (int) $data['total_days'],
                'price_per_day' => (int) $data['price_per_day'],
                'total_price' => (int) $data['total_price'],
                'pickup_method' => $data['pickup_method'],
                'delivery_address' => $data['delivery_address'],
                'notes' => $data['notes'],
                'ktp_file' => (string) $data['ktp_file'],
                'sim_file' => (string) $data['sim_file'],
            ]);
            $bookingId = (int) $connection->lastInsertId();

            $insertLog = $connection->prepare(
                "INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by, note)
                 VALUES (:booking_id, NULL, 'pending', :changed_by, :note)"
            );
            $insertLog->execute([
                'booking_id' => $bookingId,
                'changed_by' => $userId,
                'note' => 'Booking dibuat oleh pelanggan.',
            ]);

            $connection->commit();

            return $bookingId;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function getBookingById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $statement = getDBConnection()->prepare(
            'SELECT b.*, m.brand, m.model, m.plate_number, m.type, m.engine_cc,
                    m.year, m.color, m.image
             FROM bookings AS b
             INNER JOIN motorcycles AS m ON m.id = b.motorcycle_id
             WHERE b.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $booking = $statement->fetch();

        return $booking === false ? null : $booking;
    }

    public static function getUserBookings(int $user_id): array
    {
        if ($user_id < 1) {
            return [];
        }

        $connection = getDBConnection();

        $statement = $connection->prepare(
            'SELECT b.id, b.booking_code, b.start_date, b.end_date, b.total_days,
                    b.total_price, b.status, b.payment_status, b.created_at,
                    m.brand, m.model, m.plate_number, m.image
             FROM bookings AS b
             INNER JOIN motorcycles AS m ON m.id = b.motorcycle_id
             WHERE b.user_id = :user_id
             ORDER BY b.created_at DESC, b.id DESC'
        );
        $statement->execute(['user_id' => $user_id]);

        return $statement->fetchAll();
    }

    public static function getUserBookingIds(int $user_id): array
    {
        if ($user_id < 1) {
            return [];
        }

        $statement = getDBConnection()->prepare(
            'SELECT id
             FROM bookings
             WHERE user_id = :user_id
             ORDER BY id ASC'
        );
        $statement->execute(['user_id' => $user_id]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function getBookingDetailForUser(int $booking_id, int $user_id): ?array
    {
        if ($booking_id < 1 || $user_id < 1) {
            return null;
        }

        $statement = getDBConnection()->prepare(
            'SELECT b.*, m.brand, m.model, m.plate_number, m.type, m.engine_cc,
                    m.year, m.color, m.description, m.image
             FROM bookings AS b
             INNER JOIN motorcycles AS m ON m.id = b.motorcycle_id
             WHERE b.id = :booking_id AND b.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'booking_id' => $booking_id,
            'user_id' => $user_id,
        ]);
        $booking = $statement->fetch();

        return $booking === false ? null : $booking;
    }

    public static function autoUpdateBookingStatus(int $booking_id): void
    {
        if ($booking_id < 1) {
            return;
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();

        try {
            $bookingStatement = $pdo->prepare(
                'SELECT id, status,
                        TIMESTAMPDIFF(SECOND, created_at, CURRENT_TIMESTAMP) AS age_seconds,
                        end_date < CURRENT_DATE() AS end_date_passed
                 FROM bookings
                 WHERE id = :booking_id
                 FOR UPDATE'
            );
            $bookingStatement->execute(['booking_id' => $booking_id]);
            $booking = $bookingStatement->fetch();

            if ($booking === false || $booking['status'] === 'dibatalkan') {
                $pdo->commit();
                return;
            }

            $statuses = ['pending', 'dikonfirmasi', 'berjalan', 'selesai'];
            $currentIndex = array_search($booking['status'], $statuses, true);
            if ($currentIndex === false || $booking['status'] === 'selesai') {
                $pdo->commit();
                return;
            }

            $ageSeconds = (int) $booking['age_seconds'];
            if ((bool) $booking['end_date_passed']) {
                $targetIndex = 3;
            } elseif ($ageSeconds < 120) {
                $targetIndex = 0;
            } elseif ($ageSeconds < 300) {
                $targetIndex = 1;
            } else {
                $targetIndex = 2;
            }

            $updateStatement = $pdo->prepare(
                'UPDATE bookings
                 SET status = :new_status
                 WHERE id = :booking_id AND status = :old_status'
            );
            $logStatement = $pdo->prepare(
                "INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by, note)
                 VALUES (:booking_id, :old_status, :new_status, NULL, 'Sistem Otomatis')"
            );

            for ($nextIndex = $currentIndex + 1; $nextIndex <= $targetIndex; $nextIndex++) {
                $oldStatus = $statuses[$nextIndex - 1];
                $newStatus = $statuses[$nextIndex];
                $updateStatement->execute([
                    'new_status' => $newStatus,
                    'booking_id' => $booking_id,
                    'old_status' => $oldStatus,
                ]);
                if ($updateStatement->rowCount() !== 1) {
                    throw new BookingStateException('Status pesanan berubah sebelum simulasi selesai.');
                }

                $logStatement->execute([
                    'booking_id' => $booking_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public static function deleteBookingFile(int $booking_id, int $user_id, string $file_type): bool
    {
        if ($booking_id < 1 || $user_id < 1 || !in_array($file_type, ['ktp', 'sim'], true)) {
            return false;
        }

        $column = $file_type . '_file';
        $directory = $file_type === 'ktp' ? UPLOAD_KTP_DIR : UPLOAD_SIM_DIR;
        $connection = getDBConnection();
        $connection->beginTransaction();

        try {
            $bookingStatement = $connection->prepare(
                "SELECT status, {$column} AS file_name
                 FROM bookings
                 WHERE id = :booking_id AND user_id = :user_id
                 FOR UPDATE"
            );
            $bookingStatement->execute([
                'booking_id' => $booking_id,
                'user_id' => $user_id,
            ]);
            $booking = $bookingStatement->fetch();

            if ($booking === false) {
                $connection->rollBack();
                return false;
            }
            if (!in_array($booking['status'], ['selesai', 'dibatalkan'], true)) {
                throw new BookingStateException('Berkas hanya dapat dihapus setelah pesanan selesai atau dibatalkan.');
            }

            $fileName = (string) $booking['file_name'];
            if ($fileName === '') {
                $connection->commit();
                return false;
            }

            $safeFileName = basename(str_replace('\\', '/', $fileName));
            $filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeFileName;
            if (is_file($filePath) && !@unlink($filePath) && is_file($filePath)) {
                throw new BookingStateException('Berkas gagal dihapus dari server.');
            }

            $updateStatement = $connection->prepare(
                "UPDATE bookings
                 SET {$column} = ''
                 WHERE id = :booking_id AND user_id = :user_id"
            );
            $updateStatement->execute([
                'booking_id' => $booking_id,
                'user_id' => $user_id,
            ]);

            if ($updateStatement->rowCount() !== 1) {
                throw new BookingStateException('Data berkas pesanan gagal diperbarui.');
            }

            $connection->commit();

            return true;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function getBookingStatusLogs(int $booking_id, int $user_id): array
    {
        if (self::getBookingDetailForUser($booking_id, $user_id) === null) {
            return [];
        }

        $statement = getDBConnection()->prepare(
            'SELECT old_status, new_status, changed_by, note, created_at
             FROM booking_status_logs
             WHERE booking_id = :booking_id
             ORDER BY created_at ASC, id ASC'
        );
        $statement->execute(['booking_id' => $booking_id]);

        return $statement->fetchAll();
    }

    public static function updateBookingCustomer(int $booking_id, int $user_id, array $data): bool
    {
        foreach ([
            'start_date',
            'end_date',
            'total_days',
            'total_price',
            'pickup_method',
            'delivery_address',
            'notes',
        ] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new InvalidArgumentException('Data perubahan booking tidak lengkap.');
            }
        }

        if ($booking_id < 1 || $user_id < 1
            || !in_array($data['pickup_method'], ['ambil_sendiri', 'diantar'], true)) {
            return false;
        }

        $startDate = (string) $data['start_date'];
        $endDate = (string) $data['end_date'];
        self::validateBookingDates($startDate, $endDate);

        $connection = getDBConnection();
        $connection->beginTransaction();

        try {
            $currentStatement = $connection->prepare(
                'SELECT motorcycle_id, price_per_day
                 FROM bookings
                 WHERE id = :booking_id AND user_id = :user_id AND status = :status
                 FOR UPDATE'
            );
            $currentStatement->execute([
                'booking_id' => $booking_id,
                'user_id' => $user_id,
                'status' => 'pending',
            ]);
            $currentBooking = $currentStatement->fetch();

            if ($currentBooking === false) {
                $connection->rollBack();
                return false;
            }

            $motorId = (int) $currentBooking['motorcycle_id'];
            $pricePerDay = (int) $currentBooking['price_per_day'];
            $motorLock = $connection->prepare('SELECT id FROM motorcycles WHERE id = :motor_id FOR UPDATE');
            $motorLock->execute(['motor_id' => $motorId]);
            if ($motorLock->fetchColumn() === false
                || !self::isMotorAvailable($connection, $motorId, $startDate, $endDate, $booking_id)) {
                throw new BookingUnavailableException('Motor tidak tersedia pada rentang tanggal yang dipilih.');
            }

            $expectedDays = (int) (new DateTimeImmutable($startDate))->diff(new DateTimeImmutable($endDate))->days;
            $expectedTotal = $expectedDays * $pricePerDay;
            if ((int) $data['total_days'] !== $expectedDays
                || (int) $data['total_price'] !== $expectedTotal
                || $expectedDays > 65535 || $expectedTotal > 4294967295) {
                throw new InvalidArgumentException('Durasi atau total harga booking tidak valid.');
            }

            $statement = $connection->prepare(
                "UPDATE bookings
                 SET start_date = :start_date,
                     end_date = :end_date,
                     total_days = :total_days,
                     total_price = :total_price,
                     pickup_method = :pickup_method,
                     delivery_address = :delivery_address,
                     notes = :notes
                 WHERE id = :booking_id AND user_id = :user_id AND status = 'pending'"
            );
            $statement->execute([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $expectedDays,
                'total_price' => $expectedTotal,
                'pickup_method' => $data['pickup_method'],
                'delivery_address' => $data['delivery_address'],
                'notes' => $data['notes'],
                'booking_id' => $booking_id,
                'user_id' => $user_id,
            ]);
            $connection->commit();

            return true;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function cancelBookingCustomer(int $booking_id, int $user_id, string $reason): bool
    {
        if ($booking_id < 1 || $user_id < 1 || trim($reason) === '') {
            return false;
        }

        $connection = getDBConnection();
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare(
                "UPDATE bookings
                 SET status = 'dibatalkan',
                     cancelled_at = NOW(),
                     cancel_reason = :reason
                 WHERE id = :booking_id
                   AND user_id = :user_id
                   AND status = 'pending'"
            );
            $statement->execute([
                'reason' => $reason,
                'booking_id' => $booking_id,
                'user_id' => $user_id,
            ]);

            if ($statement->rowCount() !== 1) {
                $connection->rollBack();
                return false;
            }

            $logStatement = $connection->prepare(
                "INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by, note)
                 VALUES (:booking_id, 'pending', 'dibatalkan', :changed_by, :note)"
            );
            $logStatement->execute([
                'booking_id' => $booking_id,
                'changed_by' => $user_id,
                'note' => $reason,
            ]);

            $connection->commit();

            return true;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private static function isMotorAvailable(
        PDO $connection,
        int $motorId,
        string $startDate,
        string $endDate,
        ?int $excludeBookingId = null
    ): bool {
        $sql = "SELECT 1
                FROM bookings
                WHERE motorcycle_id = :motor_id
                  AND status <> 'dibatalkan'
                  AND start_date <= :end_date
                  AND end_date >= :start_date";
        $parameters = [
            'motor_id' => $motorId,
            'end_date' => $endDate,
            'start_date' => $startDate,
        ];
        if ($excludeBookingId !== null) {
            $sql .= ' AND id <> :exclude_booking_id';
            $parameters['exclude_booking_id'] = $excludeBookingId;
        }
        $sql .= ' LIMIT 1';

        $statement = $connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() === false;
    }

    private static function validateBookingDates(string $startDate, string $endDate): void
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);

        if ($start === false || $start->format('Y-m-d') !== $startDate
            || $end === false || $end->format('Y-m-d') !== $endDate
            || $end < $start) {
            throw new InvalidArgumentException('Rentang tanggal booking tidak valid.');
        }
    }
}