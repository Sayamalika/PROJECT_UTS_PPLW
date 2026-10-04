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
$isError = false;
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
        $isError = true;
    }
}

// Pesanan aktif/mendatang: selama ada, mobil tidak boleh diubah ke Perbaikan
$pesananAktif = $mobilModel->countPesananAktif($id);
$perbaikanDikunci = $pesananAktif > 0 && $mobil['status'] !== 'perbaikan';

$inputCls = 'w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all';
$labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mobil — AutoRent</title>
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

    <!-- Header -->
    <div class="app-main flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 px-4 sm:px-8 flex items-center justify-between gap-4 shadow-sm">
            <a href="dashboard.php" class="flex items-center gap-3 shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 17h.01M16 17h.01M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v4a2 2 0 002 2h1a2 2 0 002-2v-1h8v1a2 2 0 002 2h1a2 2 0 002-2v-4a2 2 0 00-2-2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <span class="font-bold tracking-tight text-lg text-slate-900">AutoRent</span>
            </a>
<div class="flex-1"></div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-semibold text-slate-800">Admin</p>
                    <p class="text-[11px] text-slate-500">Admin</p>
                </div>
                <a href="logout.php" title="Logout"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200/60 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    Logout
                </a>
            </div>
        </header>

        <main class="p-4 sm:p-8 flex-1">
            <div class="max-w-2xl mx-auto">
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-600 transition-colors mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    Kembali ke Dashboard
                </a>

                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h1 class="text-lg font-bold text-slate-900">Edit Mobil</h1>
                        <p class="text-xs text-slate-500 mt-1">Perbarui data unit armada rental</p>
                    </div>

                    <form method="POST" class="p-6 space-y-5">
                        <?php if ($message): ?>
                            <div class="message px-4 py-3 rounded-xl text-sm font-medium border <?= $isError
                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($perbaikanDikunci): ?>
                            <div class="px-4 py-3 rounded-xl text-sm font-medium border bg-amber-50 text-amber-700 border-amber-200">
                                Mobil ini punya <?= (int) $pesananAktif ?> pesanan aktif atau mendatang, jadi belum bisa diubah ke Perbaikan.
                                Selesaikan atau tolak pesanannya dulu.
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="<?= $labelCls ?>" for="nama_mobil">Nama Mobil</label>
                            <input class="<?= $inputCls ?>" type="text" id="nama_mobil" name="nama_mobil" value="<?= htmlspecialchars($mobil['nama_mobil']) ?>" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="<?= $labelCls ?>" for="plat_nomor">Plat Nomor</label>
                                <input class="<?= $inputCls ?> font-mono" type="text" id="plat_nomor" name="plat_nomor" value="<?= htmlspecialchars($mobil['plat_nomor']) ?>" required>
                            </div>
                            <div>
                                <label class="<?= $labelCls ?>" for="harga_sewa_per_hari">Harga Sewa / Hari (Rp)</label>
                                <input class="<?= $inputCls ?>" type="number" id="harga_sewa_per_hari" name="harga_sewa_per_hari" value="<?= htmlspecialchars($mobil['harga_sewa_per_hari']) ?>" min="0" step="1000" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="<?= $labelCls ?>" for="status">Kondisi</label>
                                <select class="<?= $inputCls ?>" id="status" name="status">
                                    <option value="tersedia" <?= $mobil['status'] !== 'perbaikan' ? 'selected' : '' ?>>Aktif (bisa disewa)</option>
                                    <option value="perbaikan" <?= $mobil['status'] === 'perbaikan' ? 'selected' : '' ?> <?= $perbaikanDikunci ? 'disabled' : '' ?>>Perbaikan<?= $perbaikanDikunci ? ' (ada pesanan aktif)' : '' ?></option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1.5">Status &quot;sedang disewa&quot; dihitung otomatis dari transaksi.</p>
                            </div>
                            <div>
                                <label class="<?= $labelCls ?>" for="gambar">Nama File Gambar <span class="normal-case font-medium text-slate-400">(opsional)</span></label>
                                <input class="<?= $inputCls ?>" type="text" id="gambar" name="gambar" value="<?= htmlspecialchars($mobil['gambar'] ?? '') ?>" placeholder="avanza.jpg">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="dashboard.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</a>
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">Update</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>