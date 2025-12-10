<?php
// 1. Mulai session di paling atas
session_start();

// --- (TAMBAHAN BLOK UNTUK MENGHITUNG VIEW) ---
include 'koneksi.php'; // Koneksi dipindah ke atas
if (isset($_GET['id'])) {
    $id_berita_view = (int)$_GET['id'];
    
    // Gunakan UPDATE query untuk menambah view count
    $stmt_view = $koneksi->prepare("UPDATE IGNORE berita SET view_count = view_count + 1 WHERE id_berita = ?");
    $stmt_view->bind_param("i", $id_berita_view);
    $stmt_view->execute();
    $stmt_view->close();
}
// --- (AKHIR BLOK VIEW COUNT) ---


// 2. (PENJAGA) Cek status login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php?pesan=belum_login');
    exit;
}

// 3. Hubungkan ke database (Baris ini dihapus, sudah pindah ke atas)
// include 'koneksi.php'; 

// 4. (WAJIB) Ambil ID Berita dari URL
if (!isset($_GET['id'])) {
    die("Berita tidak ditemukan.");
}
$id_berita = (int)$_GET['id']; 

// 5. Ambil data login untuk sidebar
$isLoggedIn = true; 
$nama = $_SESSION['nama'];
$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];

$foto_profil = ''; 
$stmt_user = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt_user->bind_param("i", $id_user);
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

// 6. (FIXED) Ambil data BERITA LENGKAP (termasuk tipe_konten)
$stmt_berita = $koneksi->prepare(
    "SELECT b.judul, b.isi_berita, b.gambar, b.tgl_upload, b.tipe_konten, u.nama as nama_penulis, k.nama_kategori, k.id_kategori
     FROM berita b
     JOIN users u ON b.id_penulis = u.id_user
     JOIN kategori k ON b.id_kategori = k.id_kategori
     WHERE b.id_berita = ?"
);
$stmt_berita->bind_param("i", $id_berita);
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();

if ($result_berita->num_rows == 0) {
    die("Berita tidak valid atau telah dihapus.");
}
$berita = $result_berita->fetch_assoc();
$id_kategori_berita = $berita['id_kategori']; 
$stmt_berita->close();
$koneksi->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($berita['judul']); ?></title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        /* ... (CSS artikel header, meta, dll.) ... */
        .article-header { margin-bottom: 20px; }
        .article-header h1 { font-size: 2.8em; color: #333; margin-bottom: 15px; }
        .article-meta { font-size: 0.9em; color: #666; margin-bottom: 20px; }
        .article-meta span { margin-right: 15px; vertical-align: middle; }
        .article-meta .kategori-tag {
            background-color: #8f2c24;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none; /* (BARU) untuk link */
            display: inline-block; /* (BARU) untuk link */
        }
        
        /* --- (BARU) CSS UNTUK TIPE KONTEN --- */
        .article-meta .tipe-tag {
            background-color: #8f2c24; /* Biru, atau ganti sesuai selera */
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-left: 5px; /* Jarak dari tag kategori */
        }
        /* --- (AKHIR BARU) --- */
        
        .article-inline-image {
            float: right;
            width: 350px; 
            max-width: 45%; 
            margin: 10px 0 15px 25px; 
            border-radius: 15px;
            border: 3px solid #eee; 
        }
        .article-content {
            font-size: 1.15em;
            line-height: 1.7;
            color: #333;
            overflow: auto; 
        }
        .article-content p {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 2px dotted #ccc;
            color: #555;
        }
        .article-content p:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* (CSS Notifikasi & Modal) */
        .notification { visibility: hidden; /* ... */ }
        .notification.success { background-color: #28a745; }
        .notification.show { visibility: visible; /* ... */ }
        @keyframes fadein { /* ... */ }
        @keyframes fadeout { /* ... */ }
        .modal-buttons { display: flex; /* ... */ }
        .modal-btn { /* ... */ }
        .modal-btn.cancel-btn { /* ... */ }
        .modal-btn.delete-btn { /* ... */ }
    </style>
</head>
<body>

    <!-- (Sidebar kamu sudah lengkap dan benar) -->
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
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php">Edit Berita Saya</a></li>';
            }
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
                    <a href="kategori.php?id=<?php echo $id_kategori_berita; ?>" class="breadcrumb-tag"><?php echo htmlspecialchars($berita['nama_kategori']); ?></a>
                </div>
            </div>
        </header>

        <div id="page-content">
            
            <article class="news-article-container">
                <div class="article-header">
                    <h1><?php echo htmlspecialchars($berita['judul']); ?></h1>
                    
                    <!-- (FIXED) Tampilkan Kategori dan Tipe Konten -->
                    <div class="article-meta">
                        <span>Oleh: <strong><?php echo htmlspecialchars($berita['nama_penulis']); ?></strong></span>
                        <span>Pada: <strong><?php echo date('d M Y', strtotime($berita['tgl_upload'])); ?></strong></span>
                        <a href="kategori.php?id=<?php echo $id_kategori_berita; ?>" class="kategori-tag"><?php echo htmlspecialchars($berita['nama_kategori']); ?></a>
                        <span class="tipe-tag"><?php echo htmlspecialchars($berita['tipe_konten']); ?></span>
                    </div>
                </div>
                
                <div class="article-content">
                    
                    <img src="<?php echo htmlspecialchars(file_exists($berita['gambar']) ? $berita['gambar'] : 'https://via.placeholder.com/350'); ?>" alt="Gambar Sampul" class="article-inline-image">

                    <?php
                    // Cetak isi berita paragraf per paragraf
                    $paragraf = explode("\n", htmlspecialchars($berita['isi_berita']));
                    foreach ($paragraf as $p) {
                        if (!empty(trim($p))) {
                            echo "<p>" . trim($p) . "</p>";
                        }
                    }
                    ?>
                </div>
            </article>

        </div>
    </main> 
    
    <!-- (Modal Hapus Akun) -->
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
    
    <!-- (Skrip Modal Hapus Akun) -->
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