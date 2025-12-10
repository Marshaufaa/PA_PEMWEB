<?php
// Kosongkan bagian ini atau gunakan untuk logika lain jika perlu
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Halaman Pendaftaran</title>
    <link rel="stylesheet" href="login.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        .notification {
            visibility: hidden;
            min-width: 250px;
            background-color: #dc3545; /* Merah untuk peringatan */
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 10000;
            right: 30px;
            top: 30px;
            font-size: 17px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        /* Tambahkan style untuk notifikasi sukses (hijau) */
        .notification.success {
            background-color: #28a745; 
        }
        .notification.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }
        @-webkit-keyframes fadein {
            from {top: 0; opacity: 0;} 
            to {top: 30px; opacity: 1;}
        }
        @keyframes fadein {
            from {top: 0; opacity: 0;}
            to {top: 30px; opacity: 1;}
        }
        @-webkit-keyframes fadeout {
            from {top: 30px; opacity: 1;} 
            to {top: 0; opacity: 0;}
        }
        @keyframes fadeout {
            from {top: 30px; opacity: 1;}
            to {top: 0; opacity: 0;}
        }
    </style>
</head>
<body>

    <?php
    $pesan_teks = "";
    $pesan_tipe = ""; // "success" atau "" (default merah)

    // Cek jika ada pesan di URL
    if (isset($_GET['pesan'])) {
        if ($_GET['pesan'] == "wajib_diisi") {
            $pesan_teks = "Semua kolom wajib diisi.";
        } else if ($_GET['pesan'] == "user_email_taken") {
            $pesan_teks = "Username atau Email sudah terdaftar.";
        } else if ($_GET['pesan'] == "gagal_db") {
            $pesan_teks = "Terjadi kesalahan. Silakan coba lagi.";
        }
        // Kita juga tambahkan pesan 'registrasi_sukses' di login.php
    }

    // Jika $pesan_teks tidak kosong, tampilkan notifikasinya
    if ($pesan_teks != "") {
        echo '<div id="notification" class="notification ' . $pesan_tipe . '">' . htmlspecialchars($pesan_teks) . '</div>';
    }
    ?>
    
    <section>
    ```

    <script>
    var notifElement = document.getElementById("notification");
    
    if (notifElement) {
      notifElement.className += " show"; // Tambah class 'show'
      
      setTimeout(function(){ 
          notifElement.className = notifElement.className.replace(" show", ""); 
      }, 3000); // Sembunyikan setelah 3 detik
    }
    </script>

</body>
</html>

    <section>
        <div class="leaves">
            <div class="set">
                <div><img src="foto/leaf_01.png"></div>
                <div><img src="foto/leaf_02.png"></div>
                <div><img src="foto/leaf_03.png"></div>
                <div><img src="foto/leaf_04.png"></div>
                <div><img src="foto/leaf_01.png"></div>
                <div><img src="foto/leaf_02.png"></div>
                <div><img src="foto/leaf_03.png"></div>
                <div><img src="foto/leaf_04.png"></div>
            </div>
        </div>
        <img src="foto/bg.jpg" class="bg">
        <img src="foto/girl.png" class="girl">
        <img src="foto/trees.png" class="trees">

        <div class="login">
            <h2>Daftar Akun</h2>
            <form action="proses_register.php" method="post">
                
                <div class="inputBox">
                    <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required>
                </div>

                <div class="inputBox">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="inputBox">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                
                <div class="inputBox">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="inputBox">
                    <select name="peran" required>
                        <option value="" disabled selected>Daftar sebagai...</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="uploader">Uploader</option>
                        <option value="masyarakat">Masyarakat</option>
                    </select>
                </div>

                <div class="inputBox">
                    <input type="submit" value="Daftar" id="btn">
                </div>
            </form>
            
            <div class="group">
                <a href="login.php">Sudah punya akun?</a>
                <a href="index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </section>

</body>
</html>