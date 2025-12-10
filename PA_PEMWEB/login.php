<?php
// (PERBAIKAN) Mulai session di paling atas
session_start();

// (PERBAIKAN) Siapkan KEDUA variabel
$pesan_teks = "";
$pesan_tipe = ""; // <-- INI YANG HILANG

// Cek jika ada parameter 'pesan' di URL
if (isset($_GET['pesan'])) {

    if ($_GET['pesan'] == "belum_login") {
        $pesan_teks = "Anda harus login terlebih dahulu.";
        // $pesan_tipe biarkan "", akan jadi merah (default)
    } else if ($_GET['pesan'] == "gagal") {
        $pesan_teks = "Username atau password salah.";
        // $pesan_tipe biarkan "", akan jadi merah (default)
    } else if ($_GET['pesan'] == "logout_sukses") { // (PERBAIKAN) Ganti nama 'logout' agar lebih jelas
        $pesan_teks = "Anda telah berhasil logout.";
        $pesan_tipe = "success"; // (PERBAIKAN) Set tipe agar jadi hijau
    } else if ($_GET['pesan'] == "registrasi_sukses") { // (PERBAIKAN) Tambahkan ini
        $pesan_teks = "Registrasi berhasil! Silakan login.";
        $pesan_tipe = "success";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Glassmorphism Login Page | @coding.pixel</title>
    <link rel="stylesheet" href="login.css">
    <link rel="icon" href="foto/Eventease-logo.png" type="image/x-icon">

    <style>
        .notification {
            visibility: hidden;
            min-width: 250px;
            background-color: #dc3545;
            /* Merah untuk peringatan */
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 10000;
            right: 30px;
            top: 30px;
            font-size: 17px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* --- (PERBAIKAN) TAMBAHKAN CSS UNTUK NOTIFIKASI HIJAU --- */
        .notification.success {
            background-color: #28a745;
        }

        .notification.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }
    </style>
</head>

<body>

    <?php
    // (PERBAIKAN) Pindahkan blok 'echo' notifikasi ke sini
    // Sekarang $pesan_tipe sudah pasti ada (kosong atau "success")
    if ($pesan_teks != "") {
        echo '<div id="notification" class="notification ' . $pesan_tipe . '">' . htmlspecialchars($pesan_teks) . '</div>';
    }
    ?>

    <section>
        <div class="login">
            <h2>Welcome!</h2>
            <form action="verifikasi.php" method="post">
                <div class="inputBox">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="inputBox">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="inputBox">
                    <input type="submit" value="Login" id="btn">
                </div>
            </form>
            <div class="group">
                <a href="index.php" class="btn-beranda">Kembali ke Beranda</a>
            </div>
        </div>
    </section>

    <script>
        // Skrip ini sudah benar
        var notifElement = document.getElementById("notification");

        if (notifElement) {
            notifElement.className += " show"; // <-- (Perbaikan kecil) Ganti '=' menjadi '+='

            setTimeout(function() {
                notifElement.className = notifElement.className.replace("show", "");
            }, 3000);
        }
    </script>
</body>

</html>