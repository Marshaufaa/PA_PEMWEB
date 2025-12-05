<?php
// Konfigurasi Database
$DB_HOST = 'localhost'; // Biasanya 'localhost'
$DB_USER = 'root';      // User default XAMPP
$DB_PASS = '';          // Password default XAMPP (kosong)
$DB_NAME = 'db_berita'; // Ganti dengan nama databasemu

// Membuat koneksi
$koneksi = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Cek koneksi
if (!$koneksi) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan error
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Set charset (opsional tapi bagus)
mysqli_set_charset($koneksi, "utf8mb4");
?>