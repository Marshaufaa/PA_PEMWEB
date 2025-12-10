<?php
// 1. Mulai session & Keamanan Admin
session_start();
include 'koneksi.php';

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';
$admin_id = $_SESSION['id_user'];

// HANYA ADMIN YANG BOLEH AKSES
if ($peran != 'admin') {
    die("Akses ditolak.");
}

// 2. Ambil ID user yang akan dihapus dari URL
if (!isset($_GET['id'])) {
    die("ID pengguna tidak ditemukan.");
}
$id_user_terhapus = (int)$_GET['id'];

// 3. Keamanan Tambahan: Admin tidak bisa menghapus dirinya sendiri
if ($id_user_terhapus == $admin_id) {
    die("Admin tidak dapat menghapus akunnya sendiri.");
}

// 4. Kumpulkan semua file milik user yang akan dihapus
$file_untuk_dihapus = [];

// A. Ambil FOTO PROFIL
$stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_user->bind_param("i", $id_user_terhapus);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $data_user = $result_user->fetch_assoc();
    if (!empty($data_user['foto_profil'])) {
        $file_untuk_dihapus[] = $data_user['foto_profil'];
    }
}
$stmt_user->close();

// B. Ambil SEMUA GAMBAR BERITA
$stmt_berita = $koneksi->prepare("SELECT gambar FROM berita WHERE id_penulis = ?");
$stmt_berita->bind_param("i", $id_user_terhapus);
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();
if ($result_berita->num_rows > 0) {
    while ($row = $result_berita->fetch_assoc()) {
        if (!empty($row['gambar'])) {
            $file_untuk_dihapus[] = $row['gambar'];
        }
    }
}
$stmt_berita->close();

// 5. HAPUS PENGGUNA DARI DATABASE (ON DELETE CASCADE akan menghapus berita)
$stmt_delete = $koneksi->prepare("DELETE FROM users WHERE id_user = ?");
$stmt_delete->bind_param("i", $id_user_terhapus);

if ($stmt_delete->execute()) {
    // 6. HAPUS SEMUA FILE DARI SERVER
    foreach ($file_untuk_dihapus as $file_path) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // 7. ALIHKAN KEMBALI KE HALAMAN KELOLA
    $koneksi->close();
    // (Kamu bisa tambahkan notifikasi sukses di 'admin_manage_users.php')
    header('Location: admin_manage_users.php?status=hapus_sukses');
    exit;

} else {
    // Gagal
    $koneksi->close();
    header('Location: admin_manage_users.php?status=hapus_gagal');
    exit;
}
?>