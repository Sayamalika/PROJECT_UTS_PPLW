<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('customer');
$user = $auth->currUser();
$userName = $user['nama'] ?? 'Customer';

$mobilModel = new Mobil();

// Tanggal bisa datang dari GET (tahap 1) atau POST (tahap 2)
$tglSewa    = trim($_POST['tanggal_sewa'] ?? $_GET['tanggal_sewa'] ?? '');
$tglKembali = trim($_POST['tanggal_kembali'] ?? $_GET['tanggal_kembali'] ?? '');
$idPilihan  = (int) ($_POST['id_mobil'] ?? $_GET['id'] ?? 0);
$metode     = $_POST['metode_pembayaran'] ?? '';

// Validasi tanggal untuk menentukan apakah tahap 2 boleh tampil
$dateError  = '';
$datesValid = false;
$hari       = 0;
if ($tglSewa !== '' || $tglKembali !== '') {
    $s = DateTimeImmutable::createFromFormat('!Y-m-d', $tglSewa);
    $k = DateTimeImmutable::createFromFormat('!Y-m-d', $tglKembali);
    if (!$s || !$k || $s->format('Y-m-d') !== $tglSewa || $k->format('Y-m-d') !== $tglKembali) {
        $dateError = 'Isi tanggal sewa dan tanggal kembali dengan benar.';
    } elseif ($s < new DateTimeImmutable('today')) {
        $dateError = 'Tanggal sewa tidak boleh sebelum hari ini.';
    } elseif ($k <= $s) {
        $dateError = 'Tanggal kembali harus setelah tanggal sewa (minimal 1 hari).';
    } else {
        $datesValid = true;
        $hari = (int) $s->diff($k)->days;
    }
}

$message = '';
$isError = false;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaksi = new Transaksi();
        $result = $transaksi->mulaiTransaksi(
            (int) $_SESSION['user']['id_user'],
            $idPilihan,
            $tglSewa,
            $tglKembali,
            $metode
        );

        $message = 'Transaksi berhasil dibuat. Total biaya: Rp ' . number_format((float) $result['total_biaya'], 0, ',', '.');
        $success = true;
    } catch (Exception $e) {
        $message = $e->getMessage();
        $isError = true;
    }
}

// Tahap 2: daftar mobil yang bebas pada rentang tanggal terpilih
$cars = ($datesValid && !$success) ? $mobilModel->getAvailableByDate($tglSewa, $tglKembali) : [];

$fmtTgl = fn($d) => date('d-m-Y', strtotime($d));
$inputCls = 'w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all';
$labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Mobil — AutoRent</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'], mono: ['"JetBrains Mono"', 'monospace'] },
                colors: { brand: { 50:'#eef2ff', 100:'#e0e7ff', 200:'#c7d2fe', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca', 900:'#312e81' } }
            } }
        }
    </script>
</head>
<body class="font-sans text-slate-800 antialiased min-h-screen bg-slate-50 flex flex-col">

    <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 px-4 sm:px-8 flex items-center justify-between gap-4 shadow-sm">
        <a href="dashboard.php" class="flex items-center gap-3 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 17h.01M16 17h.01M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v4a2 2 0 002 2h1a2 2 0 002-2v-1h8v1a2 2 0 002 2h1a2 2 0 002-2v-4a2 2 0 00-2-2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <span class="font-bold tracking-tight text-lg text-slate-900">AutoRent</span>
        </a>
        <div class="flex items-center gap-3 shrink-0">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($userName) ?></p>
                <p class="text-[11px] text-slate-500">Customer</p>
            </div>
            <a href="logout.php" title="Logout"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200/60 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Logout
            </a>
        </div>
    </header>

    <main class="p-4 sm:p-8 flex-1">
        <div class="max-w-5xl mx-auto">
            <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Kembali ke Dashboard
            </a>

            <?php if ($success): ?>
                <!-- Transaksi berhasil -->
                <section class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-8 text-center max-w-xl mx-auto">
                    <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path></svg>
                    </div>
                    <h1 class="text-lg font-bold text-slate-900">Pesanan Terkirim</h1>
                    <p class="message text-sm text-slate-600 mt-2"><?= htmlspecialchars($message) ?></p>
                    <p class="text-xs text-slate-400 mt-1">Menunggu persetujuan admin.</p>
                    <div class="flex items-center justify-center gap-3 mt-6">
                        <a href="dashboard.php" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">Lihat Riwayat</a>
                        <a href="sewa.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Sewa Lagi</a>
                    </div>
                </section>

            <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div class="lg:col-span-2 space-y-6">

                    <!-- Tahap 1: pilih tanggal -->
                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                            <div>
                                <h1 class="text-lg font-bold text-slate-900 leading-tight">Pilih Tanggal Sewa</h1>
                                <p class="text-xs text-slate-500">Tentukan rentang tanggal dulu untuk melihat mobil yang tersedia</p>
                            </div>
                        </div>
                        <form method="GET" action="sewa.php" class="p-6">
                            <?php if ($dateError): ?>
                                <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium border bg-rose-50 text-rose-700 border-rose-200"><?= htmlspecialchars($dateError) ?></div>
                            <?php endif; ?>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                                <div>
                                    <label class="<?= $labelCls ?>" for="tanggal_sewa">Tanggal Sewa</label>
                                    <input class="<?= $inputCls ?>" type="date" id="tanggal_sewa" name="tanggal_sewa"
                                           value="<?= htmlspecialchars($tglSewa) ?>" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div>
                                    <label class="<?= $labelCls ?>" for="tanggal_kembali">Tanggal Kembali</label>
                                    <input class="<?= $inputCls ?>" type="date" id="tanggal_kembali" name="tanggal_kembali"
                                           value="<?= htmlspecialchars($tglKembali) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                </div>
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">
                                    Cek Ketersediaan
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Tahap 2: pilih mobil (hanya setelah tanggal valid) -->
                    <?php if ($datesValid): ?>
                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 leading-tight">Pilih Mobil</h2>
                                    <p class="text-xs text-slate-500"><?= $fmtTgl($tglSewa) ?> s/d <?= $fmtTgl($tglKembali) ?> • <?= $hari ?> hari</p>
                                </div>
                            </div>
                            <span class="self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><?= count($cars) ?> unit tersedia</span>
                        </div>

                        <form method="POST" action="sewa.php" class="p-6 space-y-5">
                            <input type="hidden" name="tanggal_sewa" value="<?= htmlspecialchars($tglSewa) ?>">
                            <input type="hidden" name="tanggal_kembali" value="<?= htmlspecialchars($tglKembali) ?>">

                            <?php if ($message): ?>
                                <div class="message px-4 py-3 rounded-xl text-sm font-medium border <?= $isError
                                    ? 'bg-rose-50 text-rose-700 border-rose-200'
                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                                    <?= htmlspecialchars($message) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!$cars): ?>
                                <div class="text-center py-8 text-sm text-slate-500">
                                    Tidak ada mobil yang tersedia pada rentang tanggal ini.<br>
                                    <span class="text-slate-400">Coba ubah tanggalnya di atas.</span>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($cars as $car): ?>
                                        <label class="cursor-pointer block">
                                            <input type="radio" name="id_mobil" value="<?= (int) $car['id_mobil'] ?>" class="peer sr-only" required
                                                   data-nama="<?= htmlspecialchars($car['nama_mobil']) ?>"
                                                   data-harga="<?= (float) $car['harga_sewa_per_hari'] ?>"
                                                   <?= $idPilihan === (int) $car['id_mobil'] ? 'checked' : '' ?>>
                                            <div class="rounded-2xl border border-slate-200 p-4 transition-all hover:shadow-md peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-500/30 peer-checked:bg-brand-50/40">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-brand-600 shrink-0">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-slate-900 truncate"><?= htmlspecialchars($car['nama_mobil']) ?></div>
                                                        <span class="inline-block mt-1 px-2 py-0.5 font-mono text-[11px] font-bold bg-slate-100 text-slate-800 rounded border border-slate-200 tracking-wider"><?= htmlspecialchars($car['plat_nomor']) ?></span>
                                                    </div>
                                                </div>
                                                <div class="mt-3 flex items-end justify-between">
                                                    <p class="font-bold text-slate-900">Rp <?= number_format($car['harga_sewa_per_hari'], 0, ',', '.') ?> <span class="text-xs font-normal text-slate-400">/ hari</span></p>
                                                    <p class="text-xs text-slate-500">Total Rp <?= number_format($car['harga_sewa_per_hari'] * $hari, 0, ',', '.') ?></p>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div>
                                    <label class="<?= $labelCls ?>" for="metode_pembayaran">Metode Pembayaran</label>
                                    <select class="<?= $inputCls ?>" id="metode_pembayaran" name="metode_pembayaran" required>
                                        <option value="">Pilih Metode Pembayaran</option>
                                        <?php foreach (['Transfer BCA', 'Transfer BRI', 'Transfer Mandiri', 'Cash'] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= $metode === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <a href="dashboard.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</a>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">Buat Transaksi</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Ringkasan -->
                <aside class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:sticky lg:top-24">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">Ringkasan Sewa</h2>
                    <dl class="text-sm space-y-2">
                        <div class="flex justify-between"><dt class="text-slate-500">Tanggal sewa</dt><dd class="font-semibold text-slate-800"><?= $datesValid ? $fmtTgl($tglSewa) : '—' ?></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tanggal kembali</dt><dd class="font-semibold text-slate-800"><?= $datesValid ? $fmtTgl($tglKembali) : '—' ?></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Durasi</dt><dd class="font-semibold text-slate-800"><?= $datesValid ? $hari . ' hari' : '—' ?></dd></div>
                    </dl>
                    <dl class="text-sm space-y-2 border-t border-slate-100 mt-4 pt-4">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Mobil</dt><dd id="sumNama" class="font-semibold text-slate-800 text-right">—</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tarif</dt><dd id="sumHarga" class="font-semibold text-slate-800">—</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Estimasi total</dt><dd id="sumTotal" class="font-extrabold text-slate-900">—</dd></div>
                    </dl>
                    <p class="text-[11px] text-slate-400 mt-3">Total akhir dihitung sistem saat transaksi dibuat.</p>
                </aside>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 px-8 text-xs text-slate-500 text-center">
        &copy; <?= date('Y') ?> AutoRent Car Management
    </footer>

<script>
(function () {
    const s = document.getElementById('tanggal_sewa');
    const k = document.getElementById('tanggal_kembali');
    if (s && k) {
        const sync = () => {
            if (!s.value) return;
            const d = new Date(s.value);
            d.setUTCDate(d.getUTCDate() + 1);
            k.min = d.toISOString().slice(0, 10);
            if (k.value && k.value < k.min) k.value = k.min;
        };
        s.addEventListener('input', sync);
        sync();
    }

    const hari = <?= (int) $hari ?>;
    const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const radios = document.querySelectorAll('input[name="id_mobil"]');
    function upd() {
        const r = document.querySelector('input[name="id_mobil"]:checked');
        document.getElementById('sumNama').textContent = r ? r.dataset.nama : '—';
        document.getElementById('sumHarga').textContent = r ? fmt(parseFloat(r.dataset.harga)) + ' / hari' : '—';
        document.getElementById('sumTotal').textContent = (r && hari) ? fmt(parseFloat(r.dataset.harga) * hari) : '—';
    }
    radios.forEach(r => r.addEventListener('change', upd));
    upd();
})();
</script>
</body>
</html>
