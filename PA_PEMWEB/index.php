<?php
// 1. Mulai session di paling atas
session_start();
include 'koneksi.php';

// 3. Cek status login
$isLoggedIn = isset($_SESSION['username']);
$nama = $isLoggedIn ? $_SESSION['nama'] : 'Tamu';
$peran = $isLoggedIn ? $_SESSION['peran'] : 'Silakan login';

// --- (DEBUGGING SEMENTARA) ---
// Hapus baris var_dump ini jika sudah selesai
/*
echo "<pre style='background: #fff; color: #000; padding: 10px; border: 1px solid #000; position: fixed; top: 10px; left: 250px; z-index: 9999;'>";
echo "STATUS LOGIN SAAT INI:<br>";
var_dump($_SESSION);
echo "</pre>";
*/
// --- (AKHIR DEBUGGING) ---


// 4. Ambil foto profil
$foto_profil = ''; 
if ($isLoggedIn) {
    $id_user = $_SESSION['id_user']; 
    $stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
    $stmt_user->bind_param("i", $id_user);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
        $foto_profil = $user_data['foto_profil']; 
    }
    $stmt_user->close();
}
$foto_profil_display = 'https://via.placeholder.com/150'; 
if (!empty($foto_profil) && file_exists($foto_profil)) {
    $foto_profil_display = $foto_profil;
}

// 5. AMBIL KATEGORI DARI DB
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($koneksi, $query_kategori);

// 6. LOGIKA NOTIFIKASI TERPUSAT
$pesan_teks = "";
$pesan_tipe = ""; 

if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == "hapus_sukses") {
        $pesan_teks = "Akun Anda telah berhasil dihapus.";
        $pesan_tipe = "success"; 
    }
    if ($_GET['pesan'] == "AksesDitolak") {
        $pesan_teks = "Akses Ditolak. Anda tidak punya izin.";
    }
}
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'upload_sukses') {
        $pesan_teks = "Berita berhasil di-upload!";
        $pesan_tipe = "success";
    } else if ($_GET['status'] == 'error_ext') {
        $pesan_teks = "Gagal: Format file harus JPG, JPEG, atau PNG.";
    } else if ($_GET['status'] == 'error_size') {
        $pesan_teks = "Gagal: Ukuran file terlalu besar (Maks 50MB).";
    } else if ($_GET['status'] == 'error_db' || $_GET['status'] == 'error_move') {
        $pesan_teks = "Terjadi kesalahan saat upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEPECIAL NEWS - Dashboard</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">
    
    <style>
        /* (CSS Notifikasi & Modal kamu) */
        .notification { visibility: hidden; min-width: 250px; background-color: #dc3545; color: #fff; text-align: center; border-radius: 5px; padding: 16px; position: fixed; z-index: 10000; right: 30px; top: 30px; font-size: 17px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .notification.success { background-color: #28a745; }
        .notification.show { visibility: visible; -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @-webkit-keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @-webkit-keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        @keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        .modal-buttons { display: flex; justify-content: space-between; gap: 15px; }
        .modal-btn { flex: 1; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1em; font-weight: bold; text-align: center; text-decoration: none; transition: background-color 0.3s; }
        .modal-btn.cancel-btn { background-color: #f0f0f0; color: #333; }
        .modal-btn.cancel-btn:hover { background-color: #e0e0e0; }
        .modal-btn.delete-btn { background-color: #dc3545; color: white; }
        .modal-btn.delete-btn:hover { background-color: #c82333; }
        /* (CSS Hero Banner kamu) */
        .hero-banner { /* ... */ }
        @keyframes waveColor { /* ... */ }
        .hero-banner::before { /* ... */ }
        @keyframes waveLight { /* ... */ }
        .hero-banner h1, .hero-banner p { /* ... */ }
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
        <ul class="sidebar-nav" id="loggedInNav">
            <li><a href="index.php" class="active" id="home-link-sidebar">Home</a></li>
            <li><a href="profil.php">Edit profil</a></li>

            <?php
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php">Edit Berita Saya</a></li>';
            }

            // Tautan HANYA untuk Admin
            if ($peran == 'admin') {
                echo '<li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                        <a href="admin_statistics.php">Statistik Situs</a>
                      </li>';
                echo '<li><a href="admin_manage_users.php">Kelola Pengguna</a></li>';
                // --- (FIXED) LINK YANG HILANG DITAMBAHKAN DI SINI ---
                echo '<li><a href="admin_manage_berita.php">Kelola Berita</a></li>';
            }
            ?>

            <!-- Tautan untuk semua user yang login -->
            <li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                <a href="#" id="hapusAkunBtn">Hapus Akun Saya</a>
            </li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
        
        <div class="user-profile" id="loggedOutState">
            <div class="user-info"><h4><?php echo htmlspecialchars($nama); ?></h4><p><?php echo htmlspecialchars($peran); ?></p></div>
        </div>
        <ul class="sidebar-nav" id="loggedOutNav">
            <li><a href="index.php" class="active" id="home-link-sidebar-guest">Home</a></li>
            <li><a href="login.php" id="loginButton">Log in</a></li>
            <li><a href="register.php" id="registerButton">Daftar</a></li>
        </ul>

        <div class="hamburger-menu"><i class="fas fa-bars"></i></div>
    </nav> 
    
    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <a href="index.php" class="home-button" id="home-link-header">Home</a>
            </div>
            
            <div class="header-right">
                
                <div class="kategori-container">
                    <a href="#" class="kategori-button">Kategori</a>
                    <div class="kategori-dropdown">
                        <?php
                        mysqli_data_seek($result_kategori, 0); 
                        while ($row_kat = mysqli_fetch_assoc($result_kategori)) {
                            echo '<a href="kategori.php?id=' . $row_kat['id_kategori'] . '">' . htmlspecialchars($row_kat['nama_kategori']) . '</a>';
                        }
                        ?>
                    </div>
                </div>
                <div id="breadcrumb-container"></div>
                <form action="search.php" method="GET" class="search-bar">
                <input type="search" name="q" placeholder="Cari judul berita..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" required>
                <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </header>

        <div id="page-content">
            <section class="hero-banner">
                <h1>CEPECIAL NEWS</h1>
                <p class="welcome-message">SELAMAT DATANG DI CEPECIAL NEWS</p>
            </section>
        </div>
    </main> 
    
    <div id="hapusAkunModal" class="modal">
      <div class="modal-content" style="max-width: 500px;">
        <div class="modal-form"> 
          <h2 style="color: #8f2c24;">Konfirmasi Hapus Akun</h2>
          <p style="text-align: center; margin-bottom: 25px; font-size: 1.1em; line-height: 1.6;">
            Apakah Anda benar-benar yakin ingin menghapus akun Anda? <br>
            <strong>Semua data Anda akan hilang selamanya.</strong> Tindakan ini tidak dapat dibatalkan.
          </p>
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