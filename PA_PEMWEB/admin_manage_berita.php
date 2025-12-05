<?php
// 1. Mulai session & Keamanan Admin
session_start();
include 'koneksi.php';

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';

// HANYA ADMIN YANG BOLEH AKSES
if ($peran != 'admin') {
    header('Location: index.php?pesan=AksesDitolak');
    exit;
}

// 2. Ambil data Admin untuk sidebar
$nama = $_SESSION['nama'];
$id_user_login = $_SESSION['id_user'];
$foto_profil = ''; 
$stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_user->bind_param("i", $id_user_login);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $foto_profil = $user_data['foto_profil']; 
}
$stmt_user->close();
$foto_profil_display = 'https://via.placeholder.com/150'; 
if (!empty($foto_profil) && file_exists($foto_profil)) {
    $foto_profil_display = $foto_profil;
}

// 3. Logika Notifikasi
$pesan_teks = "";
$pesan_tipe = ""; 
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'hapus_sukses') {
        $pesan_teks = "Berita telah berhasil dihapus.";
        $pesan_tipe = "success";
    } else if ($_GET['status'] == 'hapus_gagal') {
        $pesan_teks = "Gagal menghapus berita.";
    }
}

// 4. (INTI) Ambil SEMUA berita dari SEMUA penulis
$stmt_berita = $koneksi->prepare(
    "SELECT b.id_berita, b.judul, k.nama_kategori, u.nama as nama_penulis, b.tgl_upload, b.view_count 
     FROM berita b
     JOIN kategori k ON b.id_kategori = k.id_kategori
     JOIN users u ON b.id_penulis = u.id_user
     ORDER BY b.tgl_upload DESC"
);
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Kelola Berita - CEPECIAL NEWS</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        /* (Salin CSS Notifikasi, Modal, Tabel, dan Tombol dari 'daftar_berita_saya.php') */
        .notification { visibility: hidden; min-width: 250px; background-color: #dc3545; color: #fff; text-align: center; border-radius: 5px; padding: 16px; position: fixed; z-index: 10000; right: 30px; top: 30px; font-size: 17px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .notification.success { background-color: #28a745; }
        .notification.show { visibility: visible; -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @-webkit-keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @-webkit-keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        @keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        .modal-buttons { display: flex; /* ... */ }
        .modal-btn { /* ... */ }
        
        .news-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-top: 20px;
        }
        .news-table th, .news-table td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .news-table th { background-color: #f9f9f9; color: #555; font-size: 0.9em; text-transform: uppercase; }
        .news-table td { color: #333; }
        .news-table .judul {
            font-weight: bold;
            color: #8f2c24;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-btn { padding: 5px 10px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; font-size: 0.9em; margin-right: 5px; }
        .edit-btn { background-color: #007bff; }
        .edit-btn:hover { background-color: #0056b3; }
        .delete-btn { background-color: #dc3545; }
        .delete-btn:hover { background-color: #c82333; }
    </style>
</head>
<body>

    <?php
    // Tampilkan Notifikasi
    if ($pesan_teks != "") {
        echo '<div id="notification" class="notification ' . $pesan_tipe . '">' . htmlspecialchars($pesan_teks) . '</div>';
    }
    ?>

    <nav class="sidebar expanded"> <!-- Mulai dengan sidebar terbuka -->
        <!-- (SALIN LENGKAP HTML Sidebar dari index.php) -->
        <div class="sidebar-top-icon">
            <?php if ($isLoggedIn && !empty($foto_profil) && file_exists($foto_profil)): ?>
                <img src="<?php echo htmlspecialchars($foto_profil); ?>" alt="Foto Profil" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border: 2px solid white; margin: 0 auto;">
            <?php else: ?>
                <i class="fas fa-user-circle"></i> 
            <?php endif; ?>
        </div>
        <div class="user-profile" id="loggedInState">
            <img src="<?php echo htmlspecialchars($foto_profil_display); ?>" alt="Foto Profil">
            <div class="user-info"><h4><?php echo htmlspecialchars($nama); ?></h4><p><?php echo htmlspecialchars($peran); ?></p></div>
        </div>
        <ul class="sidebar-nav" id="loggedInNav">
            <li><a href="index.php" id="home-link-sidebar">Home</a></li> 
            <li><a href="profil.php">Edit profil</a></li>
            <?php
            // Menu Uploader
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php">Edit Berita Saya</a></li>';
            }
            // Menu Admin
            if ($peran == 'admin') {
                echo '<li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                        <a href="admin_statistics.php">Statistik Situs</a>
                      </li>';
                echo '<li><a href="admin_manage_users.php">Kelola Pengguna</a></li>';
                echo '<li><a href="admin_manage_berita.php" class="active">Kelola Berita</a></li>'; // <-- INI DIA
            }
            ?>
            <li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                <a href="#" id="hapusAkunBtn">Hapus Akun Saya</a>
            </li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
        <!-- (Kita tidak perlu menu loggedOut di halaman admin) -->
        <div class="hamburger-menu"><i class="fas fa-bars"></i></div>
    </nav> 
    
    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <a href="index.php" class="home-button">Home</a>
            </div>
            <div class="header-right">
                <div id="breadcrumb-container">
                    <span class="breadcrumb-tag">Admin Panel: Kelola Semua Berita</span>
                </div>
            </div>
        </header>

        <div id="page-content">
            
            <h2>Kelola Semua Berita</h2>
            
            <table class="news-table">
                <thead>
                    <tr>
                        <th>Judul Berita</th>
                        <th>Penulis</th> <!-- (BARU) Kolom Penulis -->
                        <th>Kategori</th>
                        <th>Tgl Upload</th>
                        <th>Views</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_berita->num_rows > 0):
                        while ($berita = $result_berita->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="judul"><?php echo htmlspecialchars($berita['judul']); ?></td>
                            <td><?php echo htmlspecialchars($berita['nama_penulis']); ?></td> <!-- (BARU) Tampilkan Penulis -->
                            <td><?php echo htmlspecialchars($berita['nama_kategori']); ?></td>
                            <td><?php echo date('d M Y', strtotime($berita['tgl_upload'])); ?></td>
                            <td><?php echo $berita['view_count']; ?></td>
                            <td>
                                <!-- Admin bisa edit berita siapapun -->
                                <a href="edit_berita.php?id=<?php echo $berita['id_berita']; ?>" class="action-btn edit-btn">Edit</a>
                                
                                <a href="admin_proses_hapus_berita.php?id=<?php echo $berita['id_berita']; ?>" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirm('ADMIN: Yakin ingin menghapus berita &quot;<?php echo htmlspecialchars(addslashes($berita['judul'])); ?>&quot;?');">
                                   Hapus
                                </a>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Belum ada berita di situs ini.</td>
                        </tr>
                    <?php
                    endif;
                    $stmt_berita->close();
                    $koneksi->close();
                    ?>
                </tbody>
            </table>

        </div>
    </main> 
    
    <!-- (Salin Modal Hapus Akun & Skripnya dari index.php) -->
    
    <script>
        const php_isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="script.js"></script>
    
    <!-- (Skrip Notifikasi & Modal) -->
    <script>
       // --- Skrip untuk Notifikasi ---
       var notifElement = document.getElementById("notification");
       if (notifElement) {
         notifElement.className += " show"; 
         setTimeout(function(){ 
             notifElement.className = notifElement.className.replace(" show", ""); 
         }, 3000); 
       }
       // --- Skrip untuk Modal Hapus Akun (Pribadi) ---
       const hapusAkunModal = document.getElementById('hapusAkunModal');
       const hapusAkunBtn = document.getElementById('hapusAkunBtn'); 
       const batalHapusBtn = document.getElementById('batalHapusBtn');
       // ... (Salin sisa logika modal dari file lain) ...
    </script>
    
</body>
</html>