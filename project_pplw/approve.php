<?php

require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

$transaksi = new Transaksi();

$transaksi->approve($id);

header('Location: dashboard.php');
exit;