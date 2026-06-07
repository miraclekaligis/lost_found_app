<?php
require 'koneksi.php';
// Hapus semua data session dan destroy
$_SESSION = [];
session_destroy();
header("Location: /");
exit;
