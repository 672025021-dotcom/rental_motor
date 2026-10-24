Berikut alur aplikasi **Rental Motor** dari sisi pengguna sampai data tersimpan di database.

## Gambaran singkat

```text
Beranda → Katalog → Detail motor → Login/Daftar → Form booking
        → Validasi & unggah KTP/SIM → Simpan pesanan → Detail/Riwayat pesanan
```

### 1. Beranda dan katalog

- Beranda menampilkan hingga enam motor yang berstatus tersedia. Tombol katalog membawa pengguna ke daftar lengkap.
- Di katalog, pengguna bisa mencari berdasarkan merek/model atau memfilter berdasarkan tipe motor.
- Saat motor dipilih, halaman detail menampilkan informasi motor dan pilihan untuk mulai booking jika motor tersedia.

Halaman terkait: `index.php`, `katalog.php`, `detail_motor.php`. Data motor diambil melalui `Motor.php`.

### 2. Daftar dan login

Untuk membuat booking, pengguna harus login. Jika belum punya akun, alurnya:

```text
Daftar → Data pengguna divalidasi → Password di-hash → Data masuk ke tabel users
      → Login → Password diverifikasi → Sesi pengguna dibuat
```

Setelah login berhasil, pengguna diarahkan ke katalog. Status login disimpan di sesi dan dipakai untuk membatasi halaman booking, profil, serta pesanan.

Halaman dan logikanya ada di `register.php`, `login.php`, `auth.php`, dan `User.php`.

### 3. Membuat booking

Dari detail motor, pengguna masuk ke formulir booking. Form meminta tanggal sewa, metode pengambilan, dokumen KTP dan SIM, serta catatan. Alamat pengantaran diminta jika memilih opsi **Diantar**.

Saat formulir dikirim, `booking_simpan.php` memeriksa sesi/login, token formulir, tanggal, metode pengambilan, dan berkas. Berkas KTP/SIM disimpan di `storage/uploads/`. Sistem lalu memeriksa apakah motor masih tersedia untuk rentang tanggal tersebut.

Jika lolos, sistem menghitung total dan membuat pesanan berstatus `pending`. Proses penyimpanan booking dan pencatatan status awal dilakukan di `Booking.php`, lalu pengguna diarahkan ke detail pesanan.

```text
Validasi berhasil → cek bentrok tanggal → simpan berkas
                  → hitung harga → simpan booking + log status
                  → tampilkan detail pesanan
```

### 4. Melihat, mengedit, atau membatalkan pesanan

Pengguna yang sudah login bisa membuka riwayat pesanan. Dari sana mereka dapat melihat detail dan—selama status masih `pending`—mengedit atau membatalkan pesanan. Pembatalan mengubah status menjadi `dibatalkan`; datanya tidak dihapus dari database.

Halaman terkait: `pesanan.php`, `pesanan_detail.php`, `pesanan_edit.php`, dan `pesanan_batal.php`.

### 5. Perubahan status pesanan

Status pesanan dicatat di tabel `bookings`, sedangkan perubahan statusnya dicatat di `booking_status_logs`. Dalam implementasi sekarang, transisi berikut **disimulasikan otomatis saat riwayat atau detail pesanan dibuka**:

```text
pending → dikonfirmasi → berjalan → selesai
    └──────────────────────────────→ dibatalkan (oleh pengguna, selama pending)
```

Secara lebih rinci, kode mengubah `pending` menjadi `dikonfirmasi` setelah dua menit. Status `dikonfirmasi` bisa menjadi `berjalan` setelah lima menit dan ketika tanggal mulai sudah tiba. Status `berjalan` menjadi `selesai` setelah tanggal selesai terlewati. Ini simulasi di aplikasi, bukan alur persetujuan admin sebenarnya.

## Bagian utama aplikasi

- `public/` berisi halaman yang diakses browser.
- `app/models/` berisi operasi data pengguna, motor, dan booking.
- `app/auth.php` mengatur sesi serta akses login.
- `app/helpers.php` menyediakan fungsi pendukung seperti redirect, pesan sementara, dan format rupiah.
- `rental_motor.sql` mendefinisikan tabel `users`, `motorcycles`, `bookings`, dan `booking_status_logs`.
- Koneksi database dan pengaturan URL berada di `database.php` dan `config.php`.

**Catatan perhitungan:** durasi sewa dihitung sebagai selisih tanggal mulai dan tanggal selesai. Jadi bila tanggal mulai dan selesai sama, durasi yang tersimpan adalah `0` hari.