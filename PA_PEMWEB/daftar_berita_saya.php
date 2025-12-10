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
    header('Location: index.php?pesan=AksesDitolak');
    exit;
}

// 3. Ambil data pengguna untuk sidebar
$nama = $_SESSION['nama'];
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

// 4. Logika Notifikasi
$pesan_teks = "";
$pesan_tipe = ""; 
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'hapus_sukses') {
        $pesan_teks = "Berita telah berhasil dihapus.";
        $pesan_tipe = "success";
    } else if ($_GET['status'] == 'hapus_gagal') {
        $pesan_teks = "Gagal menghapus berita.";
    } else if ($_GET['status'] == 'edit_sukses') {
        $pesan_teks = "Berita telah berhasil diperbarui.";
        $pesan_tipe = "success";
    }
}

// 5. (INTI) Ambil SEMUA berita yang ditulis oleh PENGGUNA INI
$stmt_berita = $koneksi->prepare(
    "SELECT b.id_berita, b.judul, k.nama_kategori, b.tgl_upload, b.view_count 
     FROM berita b
     JOIN kategori k ON b.id_kategori = k.id_kategori
     WHERE b.id_penulis = ? 
     ORDER BY b.tgl_upload DESC"
);
$stmt_berita->bind_param("i", $id_user_login);
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita Saya - CEPECIAL NEWS</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        /* (Salin CSS Notifikasi, Modal, Tabel, dan Tombol) */
        .notification { visibility: hidden; min-width: 250px; background-color: #dc3545; color: #fff; text-align: center; border-radius: 5px; padding: 16px; position: fixed; z-index: 10000; right: 30px; top: 30px; font-size: 17px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .notification.success { background-color: #28a745; }
        .notification.show { visibility: visible; -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @-webkit-keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @-webkit-keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        @keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        
        .modal-content { max-width: 500px; }
        .modal-form h2 { color: #8f2c24; }
        .modal-buttons { display: flex; justify-content: space-between; gap: 15px; }
        .modal-btn { flex: 1; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: bold; text-align: center; text-decoration: none; transition: background-color 0.3s; }
        .modal-btn.cancel-btn { background-color: #f0f0f0; color: #333; }
        .modal-btn.cancel-btn:hover { background-color: #e0e0e0; }
        .modal-btn.delete-btn { background-color: #dc3545; color: white; }
        .modal-btn.delete-btn:hover { background-color: #c82333; }
        
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
            max-width: 300px;
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

    <nav class="sidebar"> 
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

        <!-- 
          ===============================================================
          ==  (FIXED) BLOK 'loggedInNav' LENGKAP DISALIN DARI INDEX.PHP  ==
          ===============================================================
        -->
        <ul class="sidebar-nav" id="loggedInNav">
            <li><a href="index.php" id="home-link-sidebar">Home</a></li> 
            <li><a href="profil.php">Edit profil</a></li>
            <?php
            // Tautan HANYA untuk Uploader dan Admin
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php" class="active">Edit Berita Saya</a></li>'; // <-- Diberi kelas 'active'
            }
            
            // (FIXED) Tautan HANYA untuk Admin DITAMBAHKAN
            if ($peran == 'admin') {
                echo '<li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                        <a href="admin_statistics.php">Statistik Situs</a>
                      </li>';
                echo '<li><a href="admin_manage_users.php">Kelola Pengguna</a></li>';
                echo '<li><a href="admin_manage_berita.php">Kelola Berita</a></li>';
            }
            ?>
            <li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                <a href="#" id="hapusAkunBtn">Hapus Akun Saya</a>
            </li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
        <!-- =============================================================== -->

        <div class="user-profile" id="loggedOutState">
            <div class="user-info"><h4><?php echo htmlspecialchars($nama); ?></h4><p><?php echo htmlspecialchars($peran); ?></p></div>
        </div>
        <ul class="sidebar-nav" id="loggedOutNav">
            <li><a href="index.php" id="home-link-sidebar-guest">Home</a></li> 
            <li><a href="login.php" id="loginButton">Log in</a></li>
            <li><a href="register.php" id="registerButton">Daftar</a></li>
        </ul>
        <div class="hamburger-menu"><i class="fas fa-bars"></i></div>
    </nav> 
    
    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <a href="index.php" class="home-button">Home</a>
            </div>
            <div class="header-right">
                <div id="breadcrumb-container">
                    <span class="breadcrumb-tag">Edit Berita Saya</span>
                </div>
            </div>
        </header>

        <div id="page-content">
            
            <h2>Kelola Berita Anda</h2>
            
            <table class="news-table">
                <thead>
                    <tr>
                        <th>Judul Berita</th>
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
                            <td><?php echo htmlspecialchars($berita['nama_kategori']); ?></td>
                            <td><?php echo date('d M Y', strtotime($berita['tgl_upload'])); ?></td>
                            <td><?php echo $berita['view_count']; ?></td>
                            <td>
                                <a href="edit_berita.php?id=<?php echo $berita['id_berita']; ?>" class="action-btn edit-btn">Edit</a>
                                
                                <a href="hapus_berita.php?id=<?php echo $berita['id_berita']; ?>" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirm('Anda yakin ingin menghapus berita &quot;<?php echo htmlspecialchars(addslashes($berita['judul'])); ?>&quot;?');">
                                   Hapus
                                </a>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Anda belum meng-upload berita apapun.</td>
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
    
    <!-- (Salin Modal Hapus Akun dari index.php) -->
    <div id="hapusAkunModal" class="modal">
      <div class="modal-content" style="max-width: 500px;">
        <div class="modal-form"> 
          <h2 style="color: #8f2c24;">Konfirmasi Hapus Akun</h2>
          <p style="text-align: center; margin-bottom: 25px;">... (Isi pesan konfirmasi)</p>
          <div class="modal-buttons">
            <button id="batalHapusBtn" class="modal-btn cancel-btn">Batal</button>
            <a href="proses_hapus.php" class="modal-btn delete-btn">Ya, Hapus Akun Saya</a>
          </div>
        </div>
      </div>
    </div>
    
    <script>
        const php_isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="script.js"></script>
    
    <!-- (Salin Skrip Notifikasi & Modal dari index.php) -->
    <script>
       // --- Skrip untuk Notifikasi ---
       var notifElement = document.getElementById("notification");
       if (notifElement) {
         notifElement.className += " show"; 
         setTimeout(function(){ 
             notifElement.className = notifElement.className.replace(" show", ""); 
         }, 3000); 
       }
       // --- Skrip untuk Modal Hapus Akun ---
       const hapusAkunModal = document.getElementById('hapusAkunModal');
       const hapusAkunBtn = document.getElementById('hapusAkunBtn'); 
       const batalHapusBtn = document.getElementById('batalHapusBtn');
       if (hapusAkunBtn) {
           hapusAkunBtn.addEventListener('click', function(e) {
               e.preventDefault(); 
               hapusAkunModal.style.display = 'block';
           });
       }
       if (batalHapusBtn) {
           batalHapusBtn.addEventListener('click', function() {
               hapusAkunModal.style.display = 'none';
           });
       }
       window.addEventListener('click', function(event) {
           if (event.target == hapusAkunModal) {
               hapusAkunModal.style.display = 'none';
           }
       });
    </script>
    
</body>
</html>