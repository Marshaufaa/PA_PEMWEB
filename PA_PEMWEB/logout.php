<?php
// 1. Selalu mulai session untuk mengaksesnya
session_start();

// 2. Hapus semua variabel session
$_SESSION = array();

// 3. Hancurkan session
session_destroy();

// 4. Alihkan ke halaman login (bukan index) dengan pesan sukses
// File login.php kamu sudah siap menerima 'logout_sukses'
header("Location: login.php?pesan=logout_sukses");
exit;
?>