<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// ------------------------------------------------------------------
// 3. KEAMANAN DASAR
// ------------------------------------------------------------------

// Cek apakah user login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    // Jika tidak login, hentikan
    die("Akses ditolak. Anda harus login untuk melakukan aksi ini.");
}

// ------------------------------------------------------------------
// 4. AMBIL ID PENGGUNA (HANYA DARI SESSION, BUKAN DARI GET/POST)
// ------------------------------------------------------------------
$id_user = $_SESSION['id_user'];

// ------------------------------------------------------------------
// 5. KUMPULKAN SEMUA FILE YANG AKAN DIHAPUS
// ------------------------------------------------------------------

$file_untuk_dihapus = [];

// A. Ambil FOTO PROFIL pengguna
$stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_user->bind_param("i", $id_user);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $data_user = $result_user->fetch_assoc();
    if (!empty($data_user['foto_profil'])) {
        $file_untuk_dihapus[] = $data_user['foto_profil'];
    }
}
$stmt_user->close();

// B. Ambil SEMUA GAMBAR BERITA milik pengguna ini
$stmt_berita = $koneksi->prepare("SELECT gambar FROM berita WHERE id_penulis = ?");
$stmt_berita->bind_param("i", $id_user);
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

// ------------------------------------------------------------------
// 6. HAPUS PENGGUNA DARI DATABASE
// ------------------------------------------------------------------
// (Query ini akan otomatis men-trigger ON DELETE CASCADE, 
//  yang akan menghapus semua baris di tabel 'berita' milik user ini)

$stmt_delete = $koneksi->prepare("DELETE FROM users WHERE id_user = ?");
$stmt_delete->bind_param("i", $id_user);

if ($stmt_delete->execute()) {
    // --- JIKA DELETE DB BERHASIL ---

    // 7. HAPUS SEMUA FILE DARI SERVER
    foreach ($file_untuk_dihapus as $file_path) {
        if (file_exists($file_path)) {
            unlink($file_path); // Perintah untuk menghapus file
        }
    }

    // 8. HANCURKAN SESSION (LOG OUT)
    session_unset();    // Hapus semua variabel session
    session_destroy();  // Hancurkan session

    // 9. ALIHKAN KE INDEX DENGAN PESAN SUKSES
    $koneksi->close();
    header('Location: index.php?pesan=hapus_sukses');
    exit;

} else {
    // --- JIKA DELETE DB GAGAL ---
    $koneksi->close();
    // Arahkan kembali ke profil dengan pesan error
    // (Kamu bisa tambahkan notifikasi untuk 'gagal_hapus' di profil.php)
    header('Location: profil.php?status=gagal_hapus');
    exit;
}
?>