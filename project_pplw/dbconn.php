<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "persewaan_mobil";


$conn = mysqli_connect($host, $user, $password, $dbname);

// Memeriksa koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
} else {
    echo "Koneksi ke database MySQL berhasil!";
}
?>