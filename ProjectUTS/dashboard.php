<?php
session_start();
require_once 'classes.php'; 


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Akses ditolak. Anda harus login sebagai Admin.");
}


$db = Database::getInstance();
$userClass = new User();

$resultMobil = $db->query("SELECT COUNT(*) as total FROM mobil");
$totalMobil = $db->fetchOne($resultMobil)['total'];

$daftarUsers = $userClass->getAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin Simpel</title>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 8px;
        }
    </style>
</head>
<body>
    <h1>Dashboard Admin Persewaan Mobil</h1>
    <hr>
    <h2>Ringkasan Data</h2>
    <ul>
        <li><b>Total Mobil yang dimiliki:</b> <?= $totalMobil ?> unit</li>
        <li><b>Total Pengguna Terdaftar:</b> <?= count($daftarUsers) ?> orang</li>
    </ul>
    <br> 
    <h2>Daftar Pengguna</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nama Lengkap</th>
            <th>Email</th>
            <th>Role</th>
        </tr>
        <?php foreach ($daftarUsers as $u): ?>
        <tr>
            <td><?= $u['id_user'] ?></td>
            <td><?= $u['nama'] ?></td>
            <td><?= $u['email'] ?></td>
            <td><?= $u['role'] ?></td>
        </tr>
        <?php endforeach; ?>

    </table>

</body>
</html>
