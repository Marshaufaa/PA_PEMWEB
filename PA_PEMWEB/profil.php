<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php'; 

// 3. Cek status login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php?pesan=belum_login');
    exit;
}

// 4. Ambil data pengguna dari database
$id_user = $_SESSION['id_user']; 
$stmt = $koneksi->prepare("SELECT username, nama, peran, foto_profil, deskripsi FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// 5. Set variabel untuk ditampilkan
$nama = $user_data['nama'];
$username = $user_data['username'];
$peran = $user_data['peran'];

// Cek foto profil
$foto_profil = $user_data['foto_profil'];
if (empty($foto_profil) || !file_exists($foto_profil)) {
    $foto_profil_display = 'https://via.placeholder.com/150'; 
} else {
    $foto_profil_display = $foto_profil;
}

// Cek deskripsi
$deskripsi = $user_data['deskripsi'];
if (empty($deskripsi)) {
    $deskripsi = 'Tulis deskripsi singkat tentang Anda di sini...';
}

$isLoggedIn = true; // Sudah pasti true

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - CEPECIAL NEWS</title> 
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">
    
    <style>
        /* CSS Notifikasi */
        .notification {
            visibility: hidden; min-width: 250px; background-color: #dc3545; 
            color: #fff; text-align: center; border-radius: 5px; padding: 16px;
            position: fixed; z-index: 10000; right: 30px; top: 30px;
            font-size: 17px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .notification.success { background-color: #28a745; }
        .notification.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }
        @-webkit-keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @keyframes fadein { from {top: 0; opacity: 0;} to {top: 30px; opacity: 1;} }
        @-webkit-keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }
        @keyframes fadeout { from {top: 30px; opacity: 1;} to {top: 0; opacity: 0;} }

        /* CSS Tombol Modal Hapus Akun */
        .modal-buttons { display: flex; justify-content: space-between; gap: 15px; }
        .modal-btn {
            flex: 1; padding: 12px 20px; border: none;
            border-radius: 8px; cursor: pointer; font-size: 1em;
            font-weight: bold; text-align: center; text-decoration: none;
            transition: background-color 0.3s;
        }
        .modal-btn.cancel-btn { background-color: #f0f0f0; color: #333; }
        .modal-btn.cancel-btn:hover { background-color: #e0e0e0; }
        .modal-btn.delete-btn { background-color: #dc3545; color: white; }
        .modal-btn.delete-btn:hover { background-color: #c82333; }
    </style>
</head>
<body>

    <?php
    // --- BLOK PHP NOTIFIKASI ---
    $pesan_teks = "";
    $pesan_tipe = ""; 

    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sukses') {
            $pesan_teks = "Profil berhasil diperbarui!";
            $pesan_tipe = "success"; 
        } else if ($_GET['status'] == 'gagal_upload') {
            $pesan_teks = "Gagal mengupload file.";
        } else if ($_GET['status'] == 'gagal_ekstensi') {
            $pesan_teks = "Format file tidak diizinkan (hanya jpg, jpeg, png).";
        } else if ($_GET['status'] == 'gagal_db') {
            $pesan_teks = "Terjadi kesalahan. Gagal menyimpan ke database.";
        } else if ($_GET['status'] == 'gagal_hapus') {
            $pesan_teks = "Gagal menghapus akun.";
        }
    }

    if ($pesan_teks != "") {
        echo '<div id="notification" class="notification ' . $pesan_tipe . '">' . htmlspecialchars($pesan_teks) . '</div>';
    }
    ?>

    <!-- Sidebar dimulai 'expanded' (terbuka) -->
    <nav class="sidebar expanded"> 
        
        <div class="sidebar-top-icon">
            <?php if ($isLoggedIn && !empty($foto_profil) && file_exists($foto_profil)): ?>
                 <img src="<?php echo htmlspecialchars($foto_profil); ?>" alt="Foto Profil" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border: 2px solid white; margin: 0 auto;">
            <?php else: ?>
                <i class="fas fa-user-circle"></i> 
            <?php endif; ?>
        </div>

        <!-- Tampilkan profil login, tambahkan 'active-state' secara manual -->
        <div class="user-profile active-state"> 
            <img src="<?php echo htmlspecialchars($foto_profil_display); ?>" alt="Foto Profil">
            <div class="user-info">
                <h4><?php echo htmlspecialchars($nama); ?></h4>
                <p><?php echo htmlspecialchars($peran); ?></p>
            </div>
        </div>
        
        <!-- (FIXED) Menu sidebar lengkap dengan logika PERAN -->
        <ul class="sidebar-nav active-state"> 
            <li><a href="index.php" id="home-link-sidebar">Home</a></li>
            <li><a href="profil.php" class="active">Edit profil</a></li> 

            <?php
            // Tautan HANYA untuk Uploader dan Admin
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
                echo '<li><a href="admin_manage_berita.php">Kelola Berita</a></li>';
            }
            ?>

            <li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                <a href="#" id="hapusAkunBtn">Hapus Akun Saya</a>
            </li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
        <!-- (Blok loggedOut tidak diperlukan di halaman profil) -->
        
        <div class="hamburger-menu">
            <i class="fas fa-bars"></i>
        </div>
    </nav> 

    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <a href="index.php" class="home-button" id="home-link-header">Home</a>
            </div>
        </header>

        <div id="page-content">
            <div class="edit-profile-container">
                <h2>Edit Profil</h2>
                
                <form action="update_profil.php" method="post" enctype="multipart/form-data">
                    <div class="form-group profile-pic-group">
                        <label>Foto Profil</label>
                        <img src="<?php echo htmlspecialchars($foto_profil_display); ?>" alt="Preview Foto Profil" id="picPreview">
                        <input type="file" name="foto_profil" id="picUpload" accept="image/png, image/jpeg">
                        <label for="picUpload" class="pic-upload-btn">Ubah Gambar</label>
                    </div>
                    
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username (Tidak bisa diubah)</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="peran">Role (Tidak bisa diubah)</label>
                        <input type="text" id="peran" name="peran" value="<?php echo htmlspecialchars($peran); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>
                    
                    <button type="submit" class="save-btn">Simpan Perubahan</button>
                    
                </form>
            </div>
        </div>
    </main>

    <!-- (FIXED) Modal Hapus Akun -->
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
            <!-- (FIXED) Link 'hapus.php' diubah menjadi 'proses_hapus.php' -->
            <a href="proses_hapus.php" class="modal-btn delete-btn">Ya, Hapus Akun Saya</a>
          </div>
        </div>
      </div>
    </div>

    <!-- (FIXED) Memanggil 'profil.js' (File baru di bawah) -->
    <script src="profil.js"></script> 

    <!-- Skrip untuk preview gambar -->
    <script>
        const picUpload = document.getElementById('picUpload');
        const picPreview = document.getElementById('picPreview');
        picUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { picPreview.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        });
    </script>

    <!-- Skrip untuk notifikasi -->
    <script>
        var notifElement = document.getElementById("notification");
        if (notifElement) {
          notifElement.className += " show"; 
          setTimeout(function(){ 
              notifElement.className = notifElement.className.replace(" show", ""); 
          }, 3000); 
        }
    </script>

    <!-- Skrip untuk modal hapus akun -->
    <script>
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