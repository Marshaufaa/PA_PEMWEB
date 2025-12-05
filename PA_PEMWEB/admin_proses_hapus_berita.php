<?php
// 1. Mulai session & Keamanan Admin
session_start();
include 'koneksi.php';

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';

// HANYA ADMIN YANG BOLEH AKSES
if ($peran != 'admin') {
    die("Akses ditolak.");
}

// 2. Ambil ID berita yang akan dihapus dari URL
if (!isset($_GET['id'])) {
    header('Location: admin_manage_berita.php?status=hapus_gagal');
    exit;
}
$id_berita_terhapus = (int)$_GET['id'];

// 3. (PENTING) Ambil path gambar SEBELUM dihapus dari DB
$gambar_path = "";
$stmt_get = $koneksi->prepare("SELECT gambar FROM berita WHERE id_berita = ?");
$stmt_get->bind_param("i", $id_berita_terhapus);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
if ($result_get->num_rows > 0) {
    $data_berita = $result_get->fetch_assoc();
    $gambar_path = $data_berita['gambar'];
}
$stmt_get->close();

// 4. HAPUS BERITA DARI DATABASE
$stmt_delete = $koneksi->prepare("DELETE FROM berita WHERE id_berita = ?");
$stmt_delete->bind_param("i", $id_berita_terhapus);

if ($stmt_delete->execute()) {
    // 5. Jika DB berhasil, HAPUS FILE GAMBAR DARI SERVER
    if (!empty($gambar_path) && file_exists($gambar_path)) {
        unlink($gambar_path); // Hapus file
    }

    // 6. ALIHKAN KEMBALI KE HALAMAN KELOLA
    $koneksi->close();
    header('Location: admin_manage_berita.php?status=hapus_sukses');
    exit;

} else {
    // Gagal
    $koneksi->close();
    header('Location: admin_manage_berita.php?status=hapus_gagal');
    exit;
}
?>