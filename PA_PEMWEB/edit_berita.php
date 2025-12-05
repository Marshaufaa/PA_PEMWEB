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

// 3. (WAJIB) Ambil ID Berita dari URL
if (!isset($_GET['id'])) {
    die("Berita tidak ditemukan.");
}
$id_berita = (int)$_GET['id'];

// 4. Ambil data pengguna untuk sidebar
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

// 5. Ambil data BERITA LAMA untuk di-edit
$stmt_berita = $koneksi->prepare("SELECT * FROM berita WHERE id_berita = ?");
$stmt_berita->bind_param("i", $id_berita);
$stmt_berita->execute();
$result_berita = $stmt_berita->get_result();

if ($result_berita->num_rows == 0) {
    die("Berita tidak valid atau telah dihapus.");
}
$berita = $result_berita->fetch_assoc();
$stmt_berita->close();

// 6. (PENTING) KEAMANAN: Cek Kepemilikan
// Jika yang login adalah 'uploader', cek apakah dia pemilik berita ini.
if ($peran == 'uploader' && $berita['id_penulis'] != $id_user_login) {
    die("Akses ditolak. Anda bukan pemilik berita ini.");
}

// 7. Ambil Daftar Kategori dari DB (untuk dropdown)
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($koneksi, $query_kategori);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - CEPECIAL NEWS</title> 
    
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

        /* Preview gambar akan langsung tampil */
        #gambarPreview {
            width: 250px; 
            height: 250px;
            border: 2px solid #ddd; /* Ganti dari dashed ke solid */
            border-radius: 8px;
            background-color: #f9f9f9;
            object-fit: cover; 
            margin-top: 10px;
            display: block; /* Langsung tampil */
        }
    </style>
</head>
<body>

    <nav class="sidebar"> 
        <!-- (Salin HTML sidebar LENGKAP dari daftar_berita_saya.php) -->
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
                echo '<li><a href="daftar_berita_saya.php" class="active">Edit Berita Saya</a></li>';
            }
            // (Tambahkan menu Admin di sini jika perlu)
            if ($peran == 'admin') {
                 echo '<li style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px;"><a href="admin_statistics.php">Statistik Situs</a></li>';
                 echo '<li><a href="admin_manage_users.php">Kelola Pengguna</a></li>';
                 echo '<li><a href="admin_manage_berita.php">Kelola Berita</a></li>';
            }
            ?>
            <li><a href="#" id="hapusAkunBtn">Hapus Akun</a></li>
            <li><a href="logout.php" id="logoutButton">Log out</a></li>
        </ul>
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
            <div class="header-right">
                <div id="breadcrumb-container">
                    <span class="breadcrumb-tag">Edit Berita</span>
                </div>
            </div>
        </header>

        <div id="page-content">
            <div class="edit-profile-container">
                <h2>Edit Berita</h2>
                
                <!-- Form akan dikirim ke 'proses_edit_berita.php' -->
                <form action="proses_edit_berita.php" method="post" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label for="judul">Judul Berita</label>
                        <input type="text" id="judul" name="judul" required 
                               value="<?php echo htmlspecialchars($berita['judul']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" required>
                            <option value="" disabled>-- Pilih Kategori --</option>
                            <?php
                            mysqli_data_seek($result_kategori, 0); 
                            while ($row = mysqli_fetch_assoc($result_kategori)) {
                                // Tambahkan 'selected' jika ID-nya cocok
                                $selected = ($row['id_kategori'] == $berita['id_kategori']) ? 'selected' : '';
                                echo '<option value="' . $row['id_kategori'] . '" ' . $selected . '>' 
                                     . htmlspecialchars($row['nama_kategori']) 
                                     . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tipe_konten">Tipe Konten</label>
                        <select id="tipe_konten" name="tipe_konten" required>
                            <?php 
                            $tipe_list = ['Berita', 'Data', 'Artikel', 'Tutorial'];
                            foreach ($tipe_list as $tipe) {
                                $selected = ($tipe == $berita['tipe_konten']) ? 'selected' : '';
                                echo '<option value="' . $tipe . '" ' . $selected . '>' . $tipe . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="gambar">Gambar Sampul (Kosongkan jika tidak ingin ganti)</label>
                        
                        <!-- Tampilkan gambar yang ada saat ini -->
                        <img id="gambarPreview" src="<?php echo htmlspecialchars($berita['gambar']); ?>" alt="Preview Gambar Sampul">
                        
                        <!-- Input file TIDAK 'required' -->
                        <input type="file" id="gambar" name="gambar_baru" accept="image/png, image/jpeg, image/jpg">
                    </div>
                    
                    <div class="form-group">
                        <label for="isi_berita">Isi Berita</label>
                        <textarea id="isi_berita" name="isi_berita" rows="15" required><?php echo htmlspecialchars($berita['isi_berita']); ?></textarea>
                    </div>
                    
                    <!-- Input tersembunyi untuk ID berita dan path gambar lama -->
                    <input type="hidden" name="id_berita" value="<?php echo $id_berita; ?>">
                    <input type="hidden" name="gambar_lama" value="<?php echo htmlspecialchars($berita['gambar']); ?>">
                    
                    <button type="submit" class="save-btn">Simpan Perubahan</button>
                    
                </form>
            </div>
        </div>
    </main>

    
    <script>
        const php_isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    
    <script src="script.js"></script> 
    
    <!-- Skrip untuk preview gambar (sama seperti upload) -->
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
            }
        });
    </script>

    <!-- (Salin skrip Modal Hapus Akun dari index.php jika diperlukan) -->

</body>
</html>