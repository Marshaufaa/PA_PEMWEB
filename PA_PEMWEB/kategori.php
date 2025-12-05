<?php
// 1. Mulai session di paling atas
session_start();
include 'koneksi.php';

// 2. Cek status login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php?pesan=belum_login');
    exit;
}

// 3. Ambil ID Kategori dari URL
if (!isset($_GET['id'])) {
    die("Kategori tidak ditemukan.");
}
$id_kategori = (int)$_GET['id']; 

// (BARU) 4. Ambil Tipe Filter dari URL
$filter_list = ['Berita', 'Data', 'Artikel', 'Tutorial'];
$tipe_filter = 'Semua'; // Default
if (isset($_GET['filter']) && in_array($_GET['filter'], $filter_list)) {
    $tipe_filter = $_GET['filter'];
}

// 5. Ambil data login untuk sidebar
$isLoggedIn = true;
$nama = $_SESSION['nama'];
$peran = $_SESSION['peran'];
$id_user = $_SESSION['id_user'];
$foto_profil = ''; 
if ($isLoggedIn) {
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

// 6. Ambil info kategori ini
$stmt_kat = $koneksi->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
$stmt_kat->bind_param("i", $id_kategori);
$stmt_kat->execute();
$result_kat = $stmt_kat->get_result();
if ($result_kat->num_rows == 0) { die("Kategori tidak valid."); }
$kategori = $result_kat->fetch_assoc();
$nama_kategori = $kategori['nama_kategori'];
$stmt_kat->close();

// (MODIFIKASI) 7. Siapkan query berita DENGAN FILTER
$sql_query = "SELECT b.id_berita, b.judul, b.gambar, LEFT(b.isi_berita, 150) as cuplikan, u.nama as nama_penulis 
              FROM berita b
              JOIN users u ON b.id_penulis = u.id_user
              WHERE b.id_kategori = ?";

$param_types = "i";
$param_values = [$id_kategori];

// Jika filternya BUKAN "Semua", tambahkan kondisi WHERE
if ($tipe_filter != 'Semua') {
    $sql_query .= " AND b.tipe_konten = ?";
    $param_types .= "s"; // Tambah 's' untuk string
    $param_values[] = $tipe_filter; // Tambah nilai filternya
}
$sql_query .= " ORDER BY b.tgl_upload DESC";

$stmt_berita = $koneksi->prepare($sql_query);
// Gunakan ... (spread operator) untuk memasukkan parameter secara dinamis
$stmt_berita->bind_param($param_types, ...$param_values); 
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nama_kategori); ?> - CEPECIAL NEWS</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        /* CSS untuk daftar berita (layout list) */
        .news-list-container { display: flex; flex-direction: column; gap: 25px; }
        .news-card { display: flex; gap: 20px; background-color: #fff; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.3s ease; }
        .news-card:hover { transform: translateY(-5px); }
        .news-card img { width: 250px; height: 180px; object-fit: cover; flex-shrink: 0; }
        .news-card-content { padding: 20px; flex-grow: 1; }
        .news-card-content h3 a { text-decoration: none; color: #333; }
        .news-card-content .snippet { font-size: 0.95em; color: #666; line-height: 1.5; margin-bottom: 15px; }
        .news-card-content .author { font-size: 0.9em; font-weight: bold; color: #8f2c24; }
        
        /* (BARU) CSS UNTUK FILTER */
        .header-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 15px; /* Jarak dari breadcrumb */
        }
        .filter-btn {
            padding: 8px 15px;
            text-decoration: none;
            color: #555;
            background-color: #f0f0f0;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background-color: #ddd;
        }
        .filter-btn.active {
            background-color: #8f2c24; /* Warna tema */
            color: white;
        }

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
    </style>
</head>
<body>

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
            <!-- Tautan untuk semua user yang login -->
      <li><a href="index.php" id="home-link-sidebar">Home</a></li>
      <li><a href="profil.php">Edit profil</a></li>

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
                    <span class="breadcrumb-tag"><?php echo htmlspecialchars($nama_kategori); ?></span>
                </div>
                
                <div class="header-filters">
                    <a href="kategori.php?id=<?php echo $id_kategori; ?>&filter=Semua" 
                       class="filter-btn <?php if($tipe_filter == 'Semua') echo 'active'; ?>">Semua</a>
                       
                    <a href="kategori.php?id=<?php echo $id_kategori; ?>&filter=Berita" 
                       class="filter-btn <?php if($tipe_filter == 'Berita') echo 'active'; ?>">Berita</a>
                       
                    <a href="kategori.php?id=<?php echo $id_kategori; ?>&filter=Data" 
                       class="filter-btn <?php if($tipe_filter == 'Data') echo 'active'; ?>">Data</a>
                       
                    <a href="kategori.php?id=<?php echo $id_kategori; ?>&filter=Artikel" 
                       class="filter-btn <?php if($tipe_filter == 'Artikel') echo 'active'; ?>">Artikel</a>
                       
                    <a href="kategori.php?id=<?php echo $id_kategori; ?>&filter=Tutorial" 
                       class="filter-btn <?php if($tipe_filter == 'Tutorial') echo 'active'; ?>">Tutorial</a>
                </div>
            </div>
        </header>

        <div id="page-content">
            
            <h2 style="margin-bottom: 20px;">
                Daftar Konten: <?php echo htmlspecialchars($nama_kategori); ?> 
                <span style="color: #8f2c24;">(<?php echo $tipe_filter; ?>)</span>
            </h2>
            
            <div class="news-list-container">
                <?php
                if ($result_berita->num_rows > 0):
                    while ($berita = $result_berita->fetch_assoc()):
                ?>
                    <div class="news-card"> 
                        <img src="<?php echo htmlspecialchars(file_exists($berita['gambar']) ? $berita['gambar'] : 'https://via.placeholder.com/300x200'); ?>" alt="Gambar Berita">
                        <div class="news-card-content">
                            <h3>
                                <a href="berita.php?id=<?php echo $berita['id_berita']; ?>">
                                    <?php echo htmlspecialchars($berita['judul']); ?>
                                </a>
                            </h3>
                            <p class="snippet"><?php echo htmlspecialchars($berita['cuplikan']); ?>...</p>
                            <p class="author">Oleh: <?php echo htmlspecialchars($berita['nama_penulis']); ?></p>
                        </div>
                    </div>
                
                <?php
                    endwhile;
                else:
                    // (MODIFIKASI) Pesan jika tidak ada berita sesuai filter
                    echo '<p>Tidak ada konten dengan tipe <b>' . htmlspecialchars($tipe_filter) . '</b> di kategori ini.</p>';
                endif;
                $stmt_berita->close();
                $koneksi->close();
                ?>
            </div>

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