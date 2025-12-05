<?php
// 1. Mulai session di paling atas
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek Status Login & Peran
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';
$id_user_login = $isLoggedIn ? $_SESSION['id_user'] : 0;

// JIKA BUKAN UPLOADER ATAU ADMIN, TENDANG!
if ($peran != 'uploader' && $peran != 'admin') {
    die("Akses ditolak.");
}

// 3. Ambil ID Berita dari URL
if (!isset($_GET['id'])) {
    header('Location: daftar_berita_saya.php?status=hapus_gagal');
    exit;
}
$id_berita_terhapus = (int)$_GET['id'];

// 4. (PENTING) Ambil path gambar & ID Penulis SEBELUM dihapus dari DB
$gambar_path = "";
$id_penulis_berita = 0;
$stmt_get = $koneksi->prepare("SELECT gambar, id_penulis FROM berita WHERE id_berita = ?");
$stmt_get->bind_param("i", $id_berita_terhapus);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
if ($result_get->num_rows > 0) {
    $data_berita = $result_get->fetch_assoc();
    $gambar_path = $data_berita['gambar'];
    $id_penulis_berita = $data_berita['id_penulis'];
} else {
    // Berita tidak ada
    header('Location: daftar_berita_saya.php?status=hapus_gagal');
    exit;
}
$stmt_get->close();

// 5. KEAMANAN: Cek Kepemilikan
// Jika yang login adalah 'uploader', dia HANYA bisa hapus beritanya sendiri.
// Admin bisa hapus berita siapa saja (dia akan lolos cek ini).
if ($peran == 'uploader' && $id_penulis_berita != $id_user_login) {
    die("Akses ditolak. Anda bukan pemilik berita ini.");
}

// 6. HAPUS BERITA DARI DATABASE
$stmt_delete = $koneksi->prepare("DELETE FROM berita WHERE id_berita = ?");
$stmt_delete->bind_param("i", $id_berita_terhapus);

if ($stmt_delete->execute()) {
    // 7. Jika DB berhasil, HAPUS FILE GAMBAR DARI SERVER
    if (!empty($gambar_path) && file_exists($gambar_path)) {
        unlink($gambar_path); // Hapus file fisik
    }

    // 8. ALIHKAN KEMBALI KE HALAMAN KELOLA
    $koneksi->close();
    header('Location: daftar_berita_saya.php?status=hapus_sukses');
    exit;

} else {
    // Gagal
    $koneksi->close();
    header('Location: daftar_berita_saya.php?status=hapus_gagal');
    exit;
}
?>