<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobil = new Mobil();
    try {
        $mobil->tambahMobil([
            'nama_mobil' => $_POST['nama_mobil'] ?? '',
            'plat_nomor' => $_POST['plat_nomor'] ?? '',
            'harga_sewa_per_hari' => $_POST['harga_sewa_per_hari'] ?? 0,
            'status' => $_POST['status'] ?? 'tersedia',
            'gambar' => $_POST['gambar'] ?? null,
        ]);
        $message = 'Mobil berhasil ditambahkan.';
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
    <title>Tambah Mobil</title>
</head>
<body>
    <div>
        <h2>Tambah Mobil</h2>
        <form method="POST">
            <input type="text" name="nama_mobil" placeholder="Nama Mobil" required>
            <input type="text" name="plat_nomor" placeholder="Plat Nomor" required>
            <input type="number" name="harga_sewa_per_hari" placeholder="Harga sewa per hari" min="0" step="1000" required>
            <select name="status">
                <option value="tersedia">Tersedia</option>
                <option value="disewa">Disewa</option>
                <option value="perbaikan">Perbaikan</option>
            </select>
            <input type="text" name="gambar" placeholder="Nama file gambar (opsional)">
            <button type="submit">Simpan</button>
        </form>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <a href="dashboard.php">Kembali</a>
    </div>
</body>
</html>
