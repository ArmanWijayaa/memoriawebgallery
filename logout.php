<?php
session_start();

// Hancurkan semua sesi
$_SESSION = array(); // Mengosongkan variabel sesi

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit;
?>
