<?php
// 1. Hubungkan ke database
include 'koneksi.php';

// 2. Pastikan form disubmit dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Ambil data dari form dan amankan (basic sanitizing)
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password_plain = $_POST['password']; // Password asli, JANGAN di-escape
    $peran = mysqli_real_escape_string($koneksi, $_POST['peran']);

    // 4. Validasi input (pastikan tidak ada yang kosong)
    if (empty($nama_lengkap) || empty($email) || empty($username) || empty($password_plain) || empty($peran)) {
        // Jika ada yang kosong, kembalikan ke register dengan pesan error
        header('Location: register.php?pesan=wajib_diisi');
        exit;
    }

    // 5. Cek apakah USERNAME or EMAIL sudah ada di database
    // Kita pakai prepared statement untuk keamanan
    $stmt_check = $koneksi->prepare("SELECT id_user FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Jika username atau email sudah ada, kembalikan dengan pesan error
        $stmt_check->close();
        header('Location: register.php?pesan=user_email_taken');
        exit;
    }
    $stmt_check->close();


    // 6. Jika aman (user/email belum ada), HASH password!
    // Ini sangat penting untuk keamanan
    $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);


    // 7. Masukkan data ke database menggunakan prepared statement
    $stmt_insert = $koneksi->prepare("INSERT INTO users (nama, email, username, password, peran) VALUES (?, ?, ?, ?, ?)");
    // 's' berarti tipenya String
    $stmt_insert->bind_param("sssss", $nama_lengkap, $email, $username, $password_hashed, $peran);

    // 8. Eksekusi query
    if ($stmt_insert->execute()) {
        // Jika registrasi berhasil, arahkan ke halaman login dengan pesan sukses
        $stmt_insert->close();
        $koneksi->close();
        header('Location: login.php?pesan=registrasi_sukses');
        exit;
    } else {
        // Jika gagal insert ke DB
        $stmt_insert->close();
        $koneksi->close();
        header('Location: register.php?pesan=gagal_db');
        exit;
    }

} else {
    // Jika file diakses langsung tanpa POST, tendang ke halaman register
    header('Location: register.php');
    exit;
}
?>