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

// 2. (FIXED) Ambil data Admin untuk sidebar
$nama = $_SESSION['nama'];
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


// 3. Ambil Data Statistik dari DB
// A. Total Views per Kategori
$query_views_kategori = "SELECT k.nama_kategori, SUM(b.view_count) as total_views
                         FROM berita b
                         JOIN kategori k ON b.id_kategori = k.id_kategori
                         GROUP BY k.id_kategori
                         ORDER BY total_views DESC";
$result_views_kategori = mysqli_query($koneksi, $query_views_kategori);

$labels_kategori = [];
$data_kategori = [];
while ($row = mysqli_fetch_assoc($result_views_kategori)) {
    $labels_kategori[] = $row['nama_kategori'];
    $data_kategori[] = $row['total_views'];
}

// B. Berita Paling Populer (Top 5)
$query_top_berita = "SELECT judul, view_count 
                     FROM berita 
                     ORDER BY view_count DESC 
                     LIMIT 5";
$result_top_berita = mysqli_query($koneksi, $query_top_berita);

// C. Total Pengguna
$total_users = $koneksi->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_berita = $koneksi->query("SELECT COUNT(*) as total FROM berita")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Statistik - CEPECIAL NEWS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">
    <!-- (BARU) Import Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stat-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .stat-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .stat-card h2 {
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .stat-card .stat-overview {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-card .stat-overview div h3 {
            font-size: 2.5em;
            color: #8f2c24;
        }
        .stat-card .stat-overview div p {
            font-size: 1.1em;
            color: #555;
        }
        .stat-card ol { padding-left: 20px; }
        .stat-card ol li {
            font-size: 1.1em;
            padding: 5px;
            color: #333;
        }
        .stat-card ol li strong { color: #8f2c24; }
    </style>
</head>
<body>
    
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
            // Menu Uploader (Admin juga Uploader)
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php">Edit Berita Saya</a></li>';
            }
            // Menu Admin
            if ($peran == 'admin') {
                echo '<li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;">
                        <a href="admin_statistics.php" class="active">Statistik Situs</a> <!-- Diberi .active -->
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
                    <span class="breadcrumb-tag">Admin Panel: Statistik</span>
                </div>
            </div>
        </header>

        <div id="page-content">
            <div class="stat-container">
                
                <div class="stat-card" style="grid-column: 1 / -1;"> <!-- Kartu 1: Overview -->
                    <h2>Overview Situs</h2>
                    <div class="stat-overview">
                        <div>
                            <h3><?php echo $total_users; ?></h3>
                            <p>Total Pengguna</p>
                        </div>
                        <div>
                            <h3><?php echo $total_berita; ?></h3>
                            <p>Total Berita</p>
                        </div>
                    </div>
                </div>

                <div class="stat-card"> <!-- Kartu 2: Chart Kategori -->
                    <h2>Penayangan per Kategori</h2>
                    <canvas id="kategoriChart"></canvas>
                </div>

                <div class="stat-card"> <!-- Kartu 3: Top Berita -->
                    <h2>Top 5 Berita Populer</h2>
                    <ol>
                        <?php 
                        if ($result_top_berita->num_rows > 0) {
                            while ($row = mysqli_fetch_assoc($result_top_berita)): ?>
                                <li>
                                    <?php echo htmlspecialchars($row['judul']); ?>
                                    (<strong><?php echo $row['view_count']; ?></strong> views)
                                </li>
                            <?php endwhile;
                        } else {
                            echo "<li>Belum ada data penayangan.</li>";
                        }
                        ?>
                    </ol>
                </div>

            </div>
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
    
    <script>
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
    
    <!-- (BARU) Skrip untuk Chart.js -->
    <script>
        const ctx = document.getElementById('kategoriChart').getContext('2d');
        const kategoriChart = new Chart(ctx, {
            type: 'pie', // Tipe chart: pie (lingkaran)
            data: {
                labels: <?php echo json_encode($labels_kategori); ?>,
                datasets: [{
                    label: 'Total Penayangan',
                    data: <?php echo json_encode($data_kategori); ?>,
                    backgroundColor: [
                        '#8f2c24',
                        '#d64c42',
                        '#b93d34',
                        '#a9342c',
                        '#f5a623',
                        '#6c757d' // Warna tambahan jika ada >5 kategori
                    ],
                    hoverOffset: 4
                }]
            }
        });
    </script>
</body>
</html>