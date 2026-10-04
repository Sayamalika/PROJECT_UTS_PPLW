<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);
$mobilModel = new Mobil();

try {
    if ($id <= 0 || !$mobilModel->getById($id)) {
        throw new RuntimeException('Mobil tidak ditemukan.');
    }
    if (!$mobilModel->delete($id)) {
        throw new RuntimeException('Gagal menghapus mobil.');
    }
    $_SESSION['flash'] = ['type' => 'success', 'text' => 'Mobil berhasil dihapus.'];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'text' => $e->getMessage()];
}

header('Location: dashboard.php');
exit;
