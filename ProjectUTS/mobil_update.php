<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$mobilModel = new Mobil();
$id = (int) ($_GET['id'] ?? 0);
$mobil = $mobilModel->getById($id);

if (!$mobil) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mobilModel->update($id, [
            'nama_mobil' => $_POST['nama_mobil'] ?? '',
            'plat_nomor' => $_POST['plat_nomor'] ?? '',
            'harga_sewa_per_hari' => $_POST['harga_sewa_per_hari'] ?? 0,
            'status' => $_POST['status'] ?? 'tersedia',
            'gambar' => $_POST['gambar'] ?? null,
        ]);
        $message = 'Data mobil berhasil diperbarui.';
        $mobil = $mobilModel->getById($id);
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
    <title>Edit Mobil</title>
</head>
<body>
    <div>
        <h2>Edit Mobil</h2>
        <form method="POST">
            <input type="text" name="nama_mobil" value="<?= htmlspecialchars($mobil['nama_mobil']) ?>" required>
            <input type="text" name="plat_nomor" value="<?= htmlspecialchars($mobil['plat_nomor']) ?>" required>
            <input type="number" name="harga_sewa_per_hari" value="<?= htmlspecialchars($mobil['harga_sewa_per_hari']) ?>" min="0" step="1000" required>
            <select name="status">
                <option value="tersedia" <?= $mobil['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                <option value="disewa" <?= $mobil['status'] === 'disewa' ? 'selected' : '' ?>>Disewa</option>
                <option value="perbaikan" <?= $mobil['status'] === 'perbaikan' ? 'selected' : '' ?>>Perbaikan</option>
            </select>
            <input type="text" name="gambar" value="<?= htmlspecialchars($mobil['gambar'] ?? '') ?>" placeholder="Nama file gambar (opsional)">
            <button type="submit">Update</button>
        </form>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <a href="dashboard.php">Kembali</a>
    </div>
</body>
</html>
