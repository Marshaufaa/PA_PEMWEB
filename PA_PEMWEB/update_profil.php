<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// 3. Keamanan: Cek apakah pengguna sudah login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    die("Akses ditolak. Anda harus login.");
}

// 4. Ambil data dari session dan form
$id_user = $_SESSION['id_user'];
$nama_baru = $_POST['nama_lengkap'];
$deskripsi_baru = $_POST['deskripsi'];

// Siapkan variabel untuk query SQL
$kolom_sql = "";
$nilai_sql = [];
$tipe_sql = "";

// Inisialisasi array untuk menyimpan nama kolom dan nilai yang akan di-update
$update_kolom = [];
$update_nilai = [];
$update_tipe = "";

// 5. Selalu update nama dan deskripsi
$update_kolom[] = "nama = ?";
$update_nilai[] = $nama_baru;
$update_tipe .= "s"; // 's' untuk string

$update_kolom[] = "deskripsi = ?";
$update_nilai[] = $deskripsi_baru;
$update_tipe .= "s"; // 's' untuk string

// 6. Cek apakah ada FOTO PROFIL BARU yang di-upload
// Cek 'error' == 0 (UPLOAD_ERR_OK) dan 'size' > 0
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0 && $_FILES['foto_profil']['size'] > 0) {
    
    // --- Ambil data file ---
    $file_tmp = $_FILES['foto_profil']['tmp_name'];
    $file_nama = $_FILES['foto_profil']['name'];
    $file_tipe = $_FILES['foto_profil']['type'];
    
    // Dapatkan ekstensi file (png, jpg, dll)
    $file_ext = strtolower(pathinfo($file_nama, PATHINFO_EXTENSION));
    
    // Tentukan ekstensi yang diizinkan
    $ext_diizinkan = ['jpg', 'jpeg', 'png'];

    if (in_array($file_ext, $ext_diizinkan)) {
        
        // Buat nama file baru yang unik (untuk menghindari tabrakan nama)
        // Contoh: uploads/profil/1_1678886400.jpg (user ID 1_timestamp.ext)
        $nama_file_baru = $id_user . '_' . time() . '.' . $file_ext;
        
        // Tentukan folder tujuan
        $folder_tujuan = "uploads/profil/"; // PASTIKAN FOLDER INI ADA!
        
        // Cek jika folder tidak ada, buat folder itu
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0755, true); // Buat folder secara rekursif
        }

        $path_file_baru = $folder_tujuan . $nama_file_baru;

        // Pindahkan file yang di-upload ke folder tujuan
        if (move_uploaded_file($file_tmp, $path_file_baru)) {
            
            // TODO (Opsional): Hapus file foto lama dari server
            // (Kamu perlu mengambil 'foto_profil' lama dari DB sebelum update)

            // Tambahkan 'foto_profil' ke query update
            $update_kolom[] = "foto_profil = ?";
            $update_nilai[] = $path_file_baru;
            $update_tipe .= "s";

        } else {
            // Gagal upload file
            header('Location: profil.php?status=gagal_upload');
            exit;
        }
    } else {
        // Ekstensi file tidak diizinkan
        header('Location: profil.php?status=gagal_ekstensi');
        exit;
    }
}

// 7. Siapkan dan jalankan query UPDATE
if (!empty($update_kolom)) {
    // Tambahkan id_user ke akhir nilai untuk klausa WHERE
    $update_nilai[] = $id_user;
    $update_tipe .= "i"; // 'i' untuk integer

    // Gabungkan semua kolom untuk query
    $sql_set = implode(", ", $update_kolom);
    
    // Buat query UPDATE
    $stmt = $koneksi->prepare("UPDATE users SET $sql_set WHERE id_user = ?");
    
    // Bind semua parameter sekaligus
    $stmt->bind_param($update_tipe, ...$update_nilai);

    // Jalankan query
    if ($stmt->execute()) {
        // 8. SUKSES! Perbarui data di SESSION
        $_SESSION['nama'] = $nama_baru;
        if (isset($path_file_baru)) {
            // (Jika kamu ingin menyimpan foto di session, tapi tidak wajib)
            // $_SESSION['foto_profil'] = $path_file_baru; 
        }

        // 9. Kembalikan ke halaman edit profil dengan pesan sukses
        header('Location: profil.php?status=sukses');
        exit;

    } else {
        // Gagal query database
        header('Location: profil.php?status=gagal_db');
        exit;
    }

    $stmt->close();
} else {
    // Tidak ada yang di-update? (Seharusnya tidak mungkin, karena nama & deskripsi selalu ada)
    header('Location: profil.php');
    exit;
}

$koneksi->close();

?>