<?php
// 1. Mulai session & Keamanan Admin
session_start();
include 'koneksi.php';

$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';
$admin_id = $_SESSION['id_user']; // Ambil ID admin yang sedang login

// HANYA ADMIN YANG BOLEH AKSES
if ($peran != 'admin') {
    header('Location: index.php?pesan=AksesDitolak');
    exit;
}

// 2. (FIXED) Ambil data Admin untuk sidebar
$nama = $_SESSION['nama'];
$foto_profil = ''; 
$stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_user->bind_param("i", $admin_id);
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

// (BARU) Logika Notifikasi
$pesan_teks = "";
$pesan_tipe = ""; 
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'hapus_sukses') {
        $pesan_teks = "Pengguna telah berhasil dihapus.";
        $pesan_tipe = "success";
    } else if ($_GET['status'] == 'hapus_gagal') {
        $pesan_teks = "Gagal menghapus pengguna.";
    }
}

// 3. Ambil SEMUA pengguna KECUALI admin sendiri
$stmt_users = $koneksi->prepare("SELECT id_user, nama, username, peran, email FROM users WHERE id_user != ?");
$stmt_users->bind_param("i", $admin_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Kelola Pengguna - CEPECIAL NEWS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">
    <style>
        /* (FIXED) CSS Notifikasi & Modal */
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
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .user-table th, .user-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        .user-table th {
            background-color: #f9f9f9;
            color: #555;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        .user-table td {
            color: #333;
        }
        .user-table .role-tag {
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
        }
        .role-admin { background-color: #6f42c1; } /* (Warna untuk admin jika muncul) */
        .role-uploader { background-color: #007bff; }
        .role-mahasiswa { background-color: #28a745; }
        .role-masyarakat { background-color: #ffc107; color: #333; }
        
        .delete-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .delete-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    
    <?php
    // (BARU) Tampilkan Notifikasi
    if ($pesan_teks != "") {
        echo '<div id="notification" class="notification ' . $pesan_tipe . '">' . htmlspecialchars($pesan_teks) . '</div>';
    }
    ?>

    <!-- (FIXED) Sidebar HTML Lengkap Disalin ke sini -->
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
                echo '<li><a href="admin_manage_users.php" class="active">Kelola Pengguna</a></li>'; // <-- Diberi .active
                echo '<li><a href="admin_manage_berita.php">Kelola Berita</a></li>';
            }
            ?>
            <li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                <a href="#" id="hapusAkunBtn">Hapus Akun Saya</a>
            </li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
        
        <div class="user-profile" id="loggedOutState">
            <div class="user-info"><h4>Tamu</h4><p>Silakan login</p></div>
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
                    <span class="breadcrumb-tag">Admin Panel: Kelola Pengguna</span>
                </div>
            </div>
        </header>

        <div id="page-content">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result_users->num_rows > 0):
                        while ($user = $result_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-tag role-<?php echo $user['peran']; ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['peran'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_proses_hapus.php?id=<?php echo $user['id_user']; ?>" 
                                   class="delete-link" 
                                   onclick="return confirm('ADMIN: Anda yakin ingin menghapus pengguna <?php echo htmlspecialchars(addslashes($user['username'])); ?>? Semua berita dan file terkait akan dihapus permanen.');">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Tidak ada pengguna lain selain Anda.</td>
                        </tr>
                    <?php endif;
                    $stmt_users->close();
                    $koneksi->close();
                    ?>
                </tbody>
            </table>
        </div>
    </main> 
    
    <!-- (FIXED) Modal Hapus Akun & Skripnya -->
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

    <!-- (BARU) Skrip Notifikasi & Modal -->
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