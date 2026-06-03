<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "lost_found_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>