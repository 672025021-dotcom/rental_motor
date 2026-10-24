CREATE DATABASE IF NOT EXISTS rental_motor CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE rental_motor;

CREATE TABLE
    users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        password_hash VARCHAR(255) NOT NULL, -- isi dengan password_hash() PHP
        phone_wa VARCHAR(20) NOT NULL, -- nomor WhatsApp
        address TEXT NULL,
        role ENUM ('customer', 'admin') NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_users_email (email)
    ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- 2. MOTORCYCLES  (Katalog & filter)
--    Satu baris = satu unit motor (punya plat nomor sendiri).
--    "status" hanya kondisi unit secara umum. Ketersediaan
--    pada tanggal tertentu dihitung dari tabel bookings.
-- ------------------------------------------------------------
CREATE TABLE
    motorcycles (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        brand VARCHAR(50) NOT NULL, -- Honda, Yamaha, ...
        model VARCHAR(80) NOT NULL, -- Vario 160, NMAX, ...
        plate_number VARCHAR(15) NOT NULL,
        type ENUM ('matic', 'manual', 'sport') NOT NULL,
        engine_cc SMALLINT UNSIGNED NULL,
        year SMALLINT UNSIGNED NULL,
        color VARCHAR(30) NULL,
        description VARCHAR(255) NULL, -- spesifikasi singkat
        price_per_day INT UNSIGNED NOT NULL, -- rupiah, tanpa desimal
        image VARCHAR(255) NULL, -- path/nama file foto
        status ENUM ('tersedia', 'maintenance', 'nonaktif') NOT NULL DEFAULT 'tersedia',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_motor_plate (plate_number),
        KEY idx_motor_type_status (type, status)
    ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- 3. BOOKINGS  (Create, Read, Update, Cancel)
--    - price_per_day disalin saat booking (snapshot), jadi kalau
--      harga motor berubah, pesanan lama tidak ikut berubah.
--    - "Hapus" pesanan = status 'dibatalkan' + cancelled_at
--      (soft delete), data tidak benar-benar dihapus.
-- ------------------------------------------------------------
CREATE TABLE
    bookings (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_code VARCHAR(20) NOT NULL, -- contoh: RM-20261022-0001
        user_id INT UNSIGNED NOT NULL,
        motorcycle_id INT UNSIGNED NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_days SMALLINT UNSIGNED NOT NULL,
        price_per_day INT UNSIGNED NOT NULL, -- snapshot harga
        total_price INT UNSIGNED NOT NULL,
        pickup_method ENUM ('ambil_sendiri', 'diantar') NOT NULL DEFAULT 'ambil_sendiri',
        delivery_address TEXT NULL, -- wajib diisi jika 'diantar'
        notes VARCHAR(255) NULL,
        ktp_file VARCHAR(255) NOT NULL, -- path file upload KTP
        sim_file VARCHAR(255) NOT NULL, -- path file upload SIM
        status ENUM (
            'pending',
            'dikonfirmasi',
            'berjalan',
            'selesai',
            'dibatalkan'
        ) NOT NULL DEFAULT 'pending',
        payment_status ENUM ('belum_bayar', 'lunas', 'dikembalikan') NOT NULL DEFAULT 'belum_bayar',
        cancelled_at DATETIME NULL,
        cancel_reason VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_booking_code (booking_code),
        KEY idx_booking_user (user_id, status),
        KEY idx_booking_motor_dates (motorcycle_id, start_date, end_date),
        CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT fk_booking_motor FOREIGN KEY (motorcycle_id) REFERENCES motorcycles (id) ON DELETE RESTRICT ON UPDATE CASCADE,
        CONSTRAINT chk_booking_dates CHECK (end_date >= start_date)
    ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- 4. BOOKING_STATUS_LOGS  (opsional, tapi berguna)
--    Mencatat setiap perubahan status. Berguna untuk detail
--    pesanan di sisi user dan audit di sisi admin nanti.
-- ------------------------------------------------------------
CREATE TABLE
    booking_status_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id INT UNSIGNED NOT NULL,
        old_status VARCHAR(20) NULL,
        new_status VARCHAR(20) NOT NULL,
        changed_by INT UNSIGNED NULL, -- users.id (customer/admin)
        note VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_log_booking (booking_id),
        CONSTRAINT fk_log_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
        CONSTRAINT fk_log_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- DATA CONTOH (untuk mencoba katalog)
-- ------------------------------------------------------------
INSERT INTO
    motorcycles (
        brand,
        model,
        plate_number,
        type,
        engine_cc,
        year,
        color,
        description,
        price_per_day,
        image
    )
VALUES
    (
        'Honda',
        'Beat',
        'H 1001 AA',
        'matic',
        110,
        2022,
        'Putih',
        'Irit, ringan, cocok dalam kota',
        70000,
        'beat.jpg'
    ),
    (
        'Honda',
        'Vario 160',
        'H 1002 AB',
        'matic',
        160,
        2023,
        'Hitam',
        'Nyaman untuk jarak menengah',
        100000,
        'vario160.jpg'
    ),
    (
        'Yamaha',
        'NMAX',
        'H 1003 AC',
        'matic',
        155,
        2023,
        'Abu',
        'Bagasi luas, cocok touring',
        150000,
        'nmax.jpg'
    ),
    (
        'Honda',
        'CRF150L',
        'H 1004 AD',
        'manual',
        150,
        2022,
        'Merah',
        'Trail untuk jalur menanjak',
        175000,
        'crf150l.jpg'
    ),
    (
        'Yamaha',
        'R15',
        'H 1005 AE',
        'sport',
        155,
        2023,
        'Biru',
        'Motor sport harian',
        200000,
        'r15.jpg'
    );