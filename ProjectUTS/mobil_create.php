<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requireRole('admin');

$message = '';
$isError = false;
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
        $isError = true;
    }
}

$inputCls = 'w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all';
$labelCls = 'block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mobil — AutoRent</title>
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
<body class="font-sans text-slate-800 antialiased min-h-screen bg-slate-50 flex">

    <!-- Sidebar -->
    <aside class="hidden lg:flex w-64 bg-slate-900 border-r border-slate-800 flex-col justify-between fixed inset-y-0 left-0 z-30">
        <div>
            <div class="px-6 py-5 flex items-center gap-3 border-b border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 17h.01M16 17h.01M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v4a2 2 0 002 2h1a2 2 0 002-2v-1h8v1a2 2 0 002 2h1a2 2 0 002-2v-4a2 2 0 00-2-2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <div>
                    <span class="text-white font-bold tracking-tight text-lg block leading-none">AutoRent</span>
                    <span class="text-xs text-indigo-400 font-medium tracking-wide uppercase mt-1 inline-block">Management v1.0</span>
                </div>
            </div>
            <nav class="mt-6 px-3 space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-slate-100 hover:bg-slate-800/60 font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    Dashboard
                </a>
                <a href="mobil_create.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-brand-600 text-white font-semibold shadow-md shadow-brand-600/20">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    Tambah Mobil
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Konten -->
    <div class="flex-1 lg:ml-64 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-8 flex items-center justify-between shadow-sm">
            <div class="font-bold text-slate-900">Tambah Mobil</div>
            <div class="flex lg:hidden items-center gap-2 text-xs font-semibold">
                <a href="dashboard.php" class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-600">Dashboard</a>
                <a href="logout.php" class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-600">Logout</a>
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
                        <h1 class="text-lg font-bold text-slate-900">Tambah Mobil</h1>
                        <p class="text-xs text-slate-500 mt-1">Daftarkan unit baru ke armada rental</p>
                    </div>

                    <form method="POST" class="p-6 space-y-5">
                        <?php if ($message): ?>
                            <div class="message px-4 py-3 rounded-xl text-sm font-medium border <?= $isError
                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="<?= $labelCls ?>" for="nama_mobil">Nama Mobil</label>
                            <input class="<?= $inputCls ?>" type="text" id="nama_mobil" name="nama_mobil" placeholder="Contoh: Toyota Avanza" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="<?= $labelCls ?>" for="plat_nomor">Plat Nomor</label>
                                <input class="<?= $inputCls ?> font-mono" type="text" id="plat_nomor" name="plat_nomor" placeholder="B 1234 ABC" required>
                            </div>
                            <div>
                                <label class="<?= $labelCls ?>" for="harga_sewa_per_hari">Harga Sewa / Hari (Rp)</label>
                                <input class="<?= $inputCls ?>" type="number" id="harga_sewa_per_hari" name="harga_sewa_per_hari" placeholder="350000" min="0" step="1000" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="<?= $labelCls ?>" for="status">Status</label>
                                <select class="<?= $inputCls ?>" id="status" name="status">
                                    <option value="tersedia">Tersedia</option>
                                    <option value="disewa">Disewa</option>
                                    <option value="perbaikan">Perbaikan</option>
                                </select>
                            </div>
                            <div>
                                <label class="<?= $labelCls ?>" for="gambar">Nama File Gambar <span class="normal-case font-medium text-slate-400">(opsional)</span></label>
                                <input class="<?= $inputCls ?>" type="text" id="gambar" name="gambar" placeholder="avanza.jpg">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="dashboard.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</a>
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">Simpan</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
