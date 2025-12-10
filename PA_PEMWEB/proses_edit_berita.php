<?php
// 1. Mulai session di paling atas
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek Status Login & Peran
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';
$id_user_login = $isLoggedIn ? $_SESSION['id_user'] : 0;

// Cek jika diakses via POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header('Location: index.php');
    exit;
}

// Cek peran
if ($peran != 'uploader' && $peran != 'admin') {
    die("Akses ditolak. Anda tidak memiliki izin.");
}

// 3. Ambil semua data dari FORM
$id_berita = (int)$_POST['id_berita'];
$judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
$id_kategori = (int)$_POST['id_kategori'];
$tipe_konten = mysqli_real_escape_string($koneksi, $_POST['tipe_konten']);
$isi_berita = mysqli_real_escape_string($koneksi, $_POST['isi_berita']);
$gambar_lama = $_POST['gambar_lama']; // Path gambar lama

// 4. KEAMANAN TAMBAHAN: Cek Kepemilikan Berita
// Ambil ID penulis asli dari database
$stmt_check = $koneksi->prepare("SELECT id_penulis FROM berita WHERE id_berita = ?");
$stmt_check->bind_param("i", $id_berita);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows == 0) {
    die("Berita tidak ditemukan.");
}
$berita_data = $result_check->fetch_assoc();
$id_penulis_berita = $berita_data['id_penulis'];
$stmt_check->close();

// Jika yang login 'uploader' (bukan admin), dia hanya bisa edit beritanya sendiri
if ($peran == 'uploader' && $id_penulis_berita != $id_user_login) {
    die("Akses ditolak. Anda bukan pemilik berita ini.");
}
// Jika 'admin', dia bisa lanjut

// 5. PROSES GAMBAR BARU (JIKA ADA)
$path_gambar_sql = $gambar_lama; // Default, gunakan gambar lama

// Cek apakah ada file baru di-upload ('gambar_baru' adalah 'name' dari input)
if (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] == UPLOAD_ERR_OK && $_FILES['gambar_baru']['size'] > 0) {
    
    $gambar = $_FILES['gambar_baru'];
    
    // Tentukan folder tujuan
    $folder_tujuan = "uploads/berita/";
    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0755, true);
    }

    // Validasi ekstensi
    $file_info = pathinfo($gambar['name']);
    $file_ext = strtolower($file_info['extension']);
    $ext_diizinkan = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_ext, $ext_diizinkan)) {
        // Gagal: ekstensi salah
        header('Location: edit_berita.php?id=' . $id_berita . '&status=error_ext');
        exit;
    }

    // Validasi ukuran (50MB)
    $max_size = 50 * 1024 * 1024; 
    if ($gambar['size'] > $max_size) {
        // Gagal: ukuran file
        header('Location: edit_berita.php?id=' . $id_berita . '&status=error_size');
        exit;
    }

    // Buat nama file unik
    $nama_file_baru = $id_user_login . '_' . time() . '.' . $file_ext;
    $path_file_baru = $folder_tujuan . $nama_file_baru;

    // Pindahkan file baru
    if (move_uploaded_file($gambar['tmp_name'], $path_file_baru)) {
        // --- SUKSES UPLOAD GAMBAR BARU ---
        
        // 1. Set path SQL ke gambar baru
        $path_gambar_sql = $path_file_baru;
        
        // 2. Hapus gambar lama (jika ada & bukan placeholder)
        if (!empty($gambar_lama) && file_exists($gambar_lama) && strpos($gambar_lama, 'placeholder.com') === false) {
            unlink($gambar_lama);
        }

    } else {
        // Gagal memindahkan file
        header('Location: edit_berita.php?id=' . $id_berita . '&status=error_move');
        exit;
    }
}

// 6. UPDATE DATABASE
$stmt_update = $koneksi->prepare(
    "UPDATE berita SET 
        judul = ?, 
        id_kategori = ?, 
        tipe_konten = ?, 
        isi_berita = ?, 
        gambar = ? 
     WHERE id_berita = ?"
);
// Tipe data: s(judul), i(id_kategori), s(tipe_konten), s(isi_berita), s(gambar), i(id_berita)
$stmt_update->bind_param("sisssi", $judul, $id_kategori, $tipe_konten, $isi_berita, $path_gambar_sql, $id_berita);

if ($stmt_update->execute()) {
    // BERHASIL! Arahkan kembali ke daftar berita
    $stmt_update->close();
    $koneksi->close();
    header('Location: daftar_berita_saya.php?status=edit_sukses');
    exit;
} else {
    // GAGAL
    $stmt_update->close();
    $koneksi->close();
    header('Location: edit_berita.php?id=' . $id_berita . '&status=error_db');
    exit;
}
?>