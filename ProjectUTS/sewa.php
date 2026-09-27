<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('customer');

$mobilModel = new Mobil();
$idMobil = (int) ($_GET['id'] ?? 0);
$mobil = $idMobil ? $mobilModel->getById($idMobil) : null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaksi = new Transaksi();
        $result = $transaksi->mulaiTransaksi(
            $_SESSION['user']['id_user'],
            (int) ($_POST['id_mobil'] ?? 0),
            $_POST['tanggal_sewa'] ?? '',
            $_POST['tanggal_kembali'] ?? ''
        );

        $message = 'Transaksi berhasil dibuat. Total biaya: Rp ' . number_format((float) $result['total_biaya'], 0, ',', '.');
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Mobil</title>
</head>
<body>
    <div>
        <h2>Form Penyewaan</h2>
        <?php if ($mobil): ?>
            <p><strong><?= htmlspecialchars($mobil['nama_mobil']) ?></strong></p>
            <p>Harga: Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?> / hari</p>
        <?php endif; ?>

        <form method="POST">
            <select name="id_mobil" required>
                <option value="">Pilih Mobil</option>
                <?php foreach ($mobilModel->getAvailable() as $car): ?>
                    <option value="<?= $car['id_mobil'] ?>" <?= ($idMobil == $car['id_mobil']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($car['nama_mobil']) ?> - Rp <?= number_format($car['harga_sewa_per_hari'], 0, ',', '.') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="tanggal_sewa" required>
            <input type="date" name="tanggal_kembali" required>
            <button type="submit">Buat Transaksi</button>
        </form>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <a href="dashboard.php">Kembali</a>
    </div>
</body>
</html>
