<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// 3. Keamanan: Cek apakah pengguna sudah login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    die("Akses ditolak. Anda harus login.");
}

// 4. Ambil ID pengguna dari session
$id_user = $_SESSION['id_user'];

// --- PROSES PENTING: Hapus file lama (jika ada) ---
$foto_path = "";
$stmt_select = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_select->bind_param("i", $id_user);
$stmt_select->execute();
$result = $stmt_select->get_result();
if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $foto_path = $data['foto_profil'];
}
$stmt_select->close();

// 5. Siapkan dan jalankan query DELETE
$stmt_delete = $koneksi->prepare("DELETE FROM users WHERE id_user = ?");
$stmt_delete->bind_param("i", $id_user);

if ($stmt_delete->execute()) {
    // 6. Jika delete DB berhasil, hapus file foto profil dari server
    if (!empty($foto_path) && file_exists($foto_path)) {
        unlink($foto_path); // Hapus file
    }

    // 7. Hancurkan session (Log out)
    session_unset();
    session_destroy();

    // 8. Alihkan ke halaman index dengan pesan sukses
    header('Location: index.php?pesan=hapus_sukses');
    exit;

} else {
    // Jika gagal, kembalikan ke profil
    header('Location: profil.php?status=gagal_hapus');
    exit;
}

$stmt_delete->close();
$koneksi->close();
?>