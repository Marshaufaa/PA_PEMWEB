<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// ------------------------------------------------------------------
// 3. KEAMANAN DASAR
// ------------------------------------------------------------------

// Cek apakah diakses via POST (dari form)
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Jika diakses langsung, tendang ke index
    header('Location: index.php');
    exit;
}

// Cek apakah user login dan punya peran
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';

// Jika BUKAN uploader atau admin, hentikan proses
if ($peran != 'uploader' && $peran != 'admin') {
    die("Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini.");
}

// ------------------------------------------------------------------
// 4. AMBIL DATA DARI FORM
// ------------------------------------------------------------------
// Ambil data teks (diamankan)
$judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
$isi_berita = mysqli_real_escape_string($koneksi, $_POST['isi_berita']);
$id_kategori = (int)$_POST['id_kategori']; // Ambil sebagai angka
$id_penulis = (int)$_POST['id_penulis'];   // Ambil sebagai angka
$tgl_upload = date('Y-m-d H:i:s'); // Waktu saat ini

// Ambil data file
$gambar = $_FILES['gambar'];

// ------------------------------------------------------------------
// 5. PROSES UPLOAD GAMBAR
// ------------------------------------------------------------------

// Cek apakah ada error saat upload
if ($gambar['error'] !== UPLOAD_ERR_OK) {
    // Kirim kembali ke form dengan pesan error
    header('Location: upload_berita.php?status=error_upload');
    exit;
}

// Tentukan folder tujuan
$folder_tujuan = "uploads/berita/";

// Cek & buat folder jika belum ada
if (!is_dir($folder_tujuan)) {
    mkdir($folder_tujuan, 0755, true); // 0755 = izin folder
}

// Validasi ekstensi file
$file_info = pathinfo($gambar['name']);
$file_ext = strtolower($file_info['extension']);
$ext_diizinkan = ['jpg', 'jpeg', 'png'];

if (!in_array($file_ext, $ext_diizinkan)) {
    // Ekstensi tidak valid
    header('Location: upload_berita.php?status=error_ext');
    exit;
}

// Validasi ukuran file (misal: maks 5MB)
$max_size = 50 * 1024 * 1024; // 50 Megabytes
if ($gambar['size'] > $max_size) {
    // File terlalu besar
    header('Location: upload_berita.php?status=error_size');
    exit;
}

// Buat nama file baru yang unik (untuk menghindari nama file yang sama)
// Format: [id_penulis]_[timestamp_sekarang].[ext]
// Contoh: 12_1678886400.jpg
$nama_file_baru = $id_penulis . '_' . time() . '.' . $file_ext;
$path_file_baru = $folder_tujuan . $nama_file_baru;

// Pindahkan file dari lokasi sementara (tmp_name) ke folder tujuan
if (move_uploaded_file($gambar['tmp_name'], $path_file_baru)) {
    
    // --- Jika upload file BERHASIL, lanjut simpan ke database ---

    // ------------------------------------------------------------------
    // 6. SIMPAN KE DATABASE
    // ------------------------------------------------------------------
    
    // Gunakan Prepared Statements untuk keamanan SQL Injection
    $stmt = $koneksi->prepare(
        "INSERT INTO berita (judul, id_kategori, isi_berita, id_penulis, tgl_upload, gambar) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    // 'sisiss' adalah tipe data: s=String, i=Integer
    $stmt->bind_param("sisiss", $judul, $id_kategori, $isi_berita, $id_penulis, $tgl_upload, $path_file_baru);

    // Eksekusi query
    if ($stmt->execute()) {
        // BERHASIL! Arahkan ke index dengan pesan sukses
        $stmt->close();
        $koneksi->close();
        header('Location: index.php?status=upload_sukses');
        exit;
    } else {
        // Gagal menyimpan ke DB
        $stmt->close();
        $koneksi->close();
        header('Location: upload_berita.php?status=error_db');
        exit;
    }

} else {
    // Gagal memindahkan file (mungkin masalah izin folder)
    header('Location: upload_berita.php?status=error_move');
    exit;
}

?>