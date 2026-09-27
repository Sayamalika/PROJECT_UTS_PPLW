<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $mobil = new Mobil();
    $mobil->delete($id);
}

header('Location: dashboard.php');
exit;
