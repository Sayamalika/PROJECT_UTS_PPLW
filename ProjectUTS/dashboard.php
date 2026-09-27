<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requiredLogin();

$user = $auth->currUser();
$role = $user['role'];

if ($role === 'admin') {
    $mobilModel = new Mobil();
    $transaksiModel = new Transaksi();
    $mobilList = $mobilModel->getAll();
    $transaksiList = $transaksiModel->getAll();
} else {
    $mobilModel = new Mobil();
    $transaksiModel = new Transaksi();
    $availableCars = $mobilModel->getAvailable();
    $userTransactions = $transaksiModel->getByUser((int) $user['id_user']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <nav>
        <div>Dashboard <?= ucfirst($role) ?></div>
        <div>
            <?php if ($role === 'admin'): ?>
                <a href="mobil_create.php">Tambah Mobil</a>
            <?php else: ?>
                <a href="sewa.php">Sewa Mobil</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($role === 'admin'): ?>
            <div class="grid">
                <div class="card">
                    <h3>Daftar Mobil</h3>
                    <table border="1" cellpadding="8" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Plat</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mobilList as $mobil): ?>
                            <tr>
                                <td><?= htmlspecialchars($mobil['nama_mobil']) ?></td>
                                <td><?= htmlspecialchars($mobil['plat_nomor']) ?></td>
                                <td>Rp <?= number_format($mobil['harga_sewa_per_hari'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($mobil['status']) ?></td>
                                <td>
                                    <a class="btn" href="mobil_update.php?id=<?= $mobil['id_mobil'] ?>">Edit</a>
                                    <a class="btn danger" href="mobil_delete.php?id=<?= $mobil['id_mobil'] ?>" onclick="return confirm('Hapus mobil ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h3>Daftar Transaksi</h3>
                    <table border="1" cellpadding="8" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Mobil</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transaksiList as $transaksi): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaksi['nama']) ?></td>
                                <td><?= htmlspecialchars($transaksi['nama_mobil']) ?></td>
                                <td>Rp <?= number_format($transaksi['total_biaya'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($transaksi['status_transaksi']) ?></td>
                                <td> <?php if ($transaksi['status_transaksi'] === 'menunggu'): ?>
                                    <a href="approve.php?id=<?= $transaksi['id_transaksi'] ?>">Setujui</a>
                                    <a href="reject.php?id=<?= $transaksi['id_transaksi'] ?>">Tolak</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <h3>Mobil Tersedia</h3>
                <div class="cars">
                    <?php foreach ($availableCars as $car): ?>
                        <div class="car">
                            <h4><?= htmlspecialchars($car['nama_mobil']) ?></h4>
                            <p>Plat: <?= htmlspecialchars($car['plat_nomor']) ?></p>
                            <p>Harga: Rp <?= number_format($car['harga_sewa_per_hari'], 0, ',', '.') ?>/hari</p>
                            <a class="btn" href="sewa.php?id=<?= $car['id_mobil'] ?>">Sewa</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h3>Riwayat Penyewaan Saya</h3>
                <table border="1" cellpadding="8" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Mobil</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($userTransactions as $tx): ?>
                        <tr>
                            <td><?= htmlspecialchars($tx['nama_mobil']) ?></td>
                            <td><?= htmlspecialchars($tx['tanggal_sewa']) ?></td>
                            <td><?= htmlspecialchars($tx['tanggal_kembali']) ?></td>
                            <td>Rp <?= number_format($tx['total_biaya'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($tx['status_transaksi']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
