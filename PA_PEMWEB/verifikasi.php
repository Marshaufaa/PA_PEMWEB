<?php
// 1. Mulai Session (HARUS di paling atas)
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// 3. Cek apakah data form (username & password) dikirim
if (isset($_POST['username']) && isset($_POST['password'])) {
    
    $username = $_POST['username'];
    $password_input = $_POST['password']; // Password yang diinput user

    // 4. Lindungi dari SQL Injection dengan Prepared Statements
    // Kita cari user berdasarkan username
    $stmt = $koneksi->prepare("SELECT id_user, nama, username, password, peran FROM users WHERE username = ?");
    
    // Bind parameter 'username' sebagai string ("s")
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 5. Cek apakah user ditemukan (hasil query harus 1 baris)
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 6. Verifikasi Password!
        // Ini bagian penting: membandingkan password input dengan hash di database
        if (password_verify($password_input, $user['password'])) {
            
            // 7. Jika password benar, buat SESSION
            $_SESSION['isLoggedIn'] = true; // Status login
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['peran'] = $user['peran'];

            // 8. Alihkan ke halaman utama (index.php)
            header('Location: index.php');
            exit; // Hentikan eksekusi skrip

        } else {
            // Jika Password salah
            // Kembalikan ke login.php dengan pesan 'gagal'
            header('Location: login.php?pesan=gagal');
            exit;
        }
    } else {
        // Jika User tidak ditemukan
        // Kembalikan ke login.php dengan pesan 'gagal'
        header('Location: login.php?pesan=gagal');
        exit;
    }
    
    $stmt->close();
    $koneksi->close();

} else {
    // Jika data tidak lengkap atau akses langsung ke verifikasi.php
    header('Location: login.php');
    exit;
}
?>