<?php
// 1. Mulai session di paling atas
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// --- 3. KEAMANAN: Cek Status Login & Peran ---
$isLoggedIn = isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true;
$peran = $isLoggedIn ? $_SESSION['peran'] : 'tamu';

// JIKA BUKAN UPLOADER ATAU ADMIN, TENDANG!
if ($peran != 'uploader' && $peran != 'admin') {
    header('Location: index.php?pesan=AksesDitolak');
    exit;
}

// 4. Ambil data pengguna untuk sidebar
$nama = $_SESSION['nama'];
$id_user = $_SESSION['id_user']; 

$foto_profil = ''; 
$stmt = $koneksi->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $foto_profil = $user_data['foto_profil']; 
}
$stmt->close();

$foto_profil_display = 'https://via.placeholder.com/150';
if (!empty($foto_profil) && file_exists($foto_profil)) {
    $foto_profil_display = $foto_profil;
}

// --- 5. Ambil Daftar Kategori dari DB ---
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($koneksi, $query_kategori);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Berita - CEPECIAL NEWS</title> 
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #fff; 
        }
        .form-group textarea {
            min-height: 250px; 
        }
        #gambarPreview {
            width: 250px; 
            height: 250px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            object-fit: cover; 
            margin-top: 10px;
            display: none; 
        }
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
            <div class="user-info">
                <h4><?php echo htmlspecialchars($nama); ?></h4>
                <p><?php echo htmlspecialchars($peran); ?></p>
            </div>
        </div>
        
        <!-- 
          ===============================================================
          ==  PERBAIKAN ADA DI DALAM 'loggedInNav' INI  ==
          ===============================================================
        -->
        <ul class="sidebar-nav" id="loggedInNav"> 
            <li><a href="index.php" id="home-link-sidebar">Home</a></li>
            <li><a href="profil.php">Edit profil</a></li> 

            <?php
            // Tautan HANYA untuk Uploader dan Admin
            if ($peran == 'uploader' || $peran == 'admin') {
                echo '<li><a href="upload_berita.php" class="active">Upload Berita</a></li>';
                echo '<li><a href="daftar_berita_saya.php">Edit Berita Saya</a></li>';
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
             <div class="user-info"><h4>Tamu</h4><p>Silakan login</p></div>
        </div>
        <ul class="sidebar-nav" id="loggedOutNav">
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php">Log in</a></li>
            <li><a href="register.php">Daftar</a></li>
        </ul>

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
                <h2>Upload Berita Baru</h2>
                
                <form action="proses_upload.php" method="post" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label for="judul">Judul Berita</label>
                        <input type="text" id="judul" name="judul" placeholder="Tulis judul di sini..." required>
                    </div>

                    <div class="form-group">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php
                            mysqli_data_seek($result_kategori, 0); 
                            while ($row = mysqli_fetch_assoc($result_kategori)) {
                                echo '<option value="' . $row['id_kategori'] . '">' . htmlspecialchars($row['nama_kategori']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tipe_konten">Tipe Konten</label>
                        <select id="tipe_konten" name="tipe_konten" required>
                            <option value="Berita" selected>Berita (Default)</option>
                            <option value="Data">Data</option>
                            <option value="Artikel">Artikel</option>
                            <option value="Tutorial">Tutorial</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gambar">Gambar Sampul (Cover)</label>
                        <img id="gambarPreview" src="#" alt="Preview Gambar Sampul">
                        <input type="file" id="gambar" name="gambar" accept="image/png, image/jpeg, image/jpg" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="isi_berita">Isi Berita</label>
                        <textarea id="isi_berita" name="isi_berita" rows="15" placeholder="Tulis isi berita di sini..." required></textarea>
                    </div>
                    
                    <input type="hidden" name="id_penulis" value="<?php echo htmlspecialchars($id_user); ?>">
                    
                    <button type="submit" class="save-btn">Upload Berita</button>
                    
                </form>
            </div>
        </div>
    </main>

    
    <script>
        const php_isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    
    <script src="script.js"></script> 
    
    <script>
        const inputGambar = document.getElementById('gambar');
        const previewGambar = document.getElementById('gambarPreview');

        inputGambar.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewGambar.src = e.target.result;
                    previewGambar.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewGambar.style.display = 'none';
                previewGambar.src = '#'; 
            }
        });
    </script>

</body>
</html>