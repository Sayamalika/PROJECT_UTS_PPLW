<?php
require_once __DIR__ . '/bootstrap.php';

$auth = new Auth();
$auth->requiredLogin();

$user = $auth->currUser();
$role = $user['role'];

$mobilModel = new Mobil();
$transaksiModel = new Transaksi();

if ($role === 'admin') {
    $mobilList = $mobilModel->getAll();
    $transaksiList = $transaksiModel->getAll();

    // Statistik untuk kartu KPI
    $totalArmada    = count($mobilList);
    $mobilTersedia  = count(array_filter($mobilList, fn($m) => $m['status'] === 'tersedia'));
    $totalTransaksi = count($transaksiList);
    $trxMenunggu    = count(array_filter($transaksiList, fn($t) => $t['status_transaksi'] === 'menunggu'));
    $trxDisetujui   = count(array_filter($transaksiList, fn($t) => $t['status_transaksi'] === 'disetujui'));
    $trxDitolak     = count(array_filter($transaksiList, fn($t) => $t['status_transaksi'] === 'ditolak'));
    $totalNilaiSewa = array_sum(array_map(
        fn($t) => $t['status_transaksi'] === 'disetujui' ? (float) $t['total_biaya'] : 0,
        $transaksiList
    ));
    $persenTersedia = $totalArmada > 0 ? round($mobilTersedia / $totalArmada * 100) : 0;

    $navCounts  = ['armada' => $totalArmada, 'transaksi' => $totalTransaksi];
    $showSearch = true;
} else {
    $availableCars    = $mobilModel->getAvailable();
    $userTransactions = $transaksiModel->getByUser((int) $user['id_user']);
}

$pageTitle  = 'Dashboard ' . ucfirst($role);
$activePage = 'dashboard';
if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('rupiah')) {
    function rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}
if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $out !== '' ? $out : '?';
    }
}
if (!function_exists('statusBadge')) {
    /** Badge berwarna untuk status mobil / transaksi. */
    function statusBadge(string $status): string
    {
        $map = [
            'tersedia'  => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
            'disetujui' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
            'selesai'   => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
            'disewa'    => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
            'menunggu'  => ['bg-amber-50 text-amber-700 border-amber-200', 'bg-amber-500'],
            'perbaikan' => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
            'ditolak'   => ['bg-rose-50 text-rose-700 border-rose-200', 'bg-rose-500'],
        ];
        [$box, $dot] = $map[strtolower($status)] ?? ['bg-slate-100 text-slate-600 border-slate-200', 'bg-slate-400'];
        return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ' . $box . '">'
             . '<span class="w-1.5 h-1.5 rounded-full ' . $dot . '"></span>' . h($status) . '</span>';
    }
}

$role       = $user['role'] ?? 'user';
$navCounts  = $navCounts ?? [];
$showSearch = $showSearch ?? false;
$userName   = $user['nama'] ?? ucfirst($role);

$icons = [
    'dashboard' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    'armada'    => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    'transaksi' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    'plus'      => 'M12 4v16m8-8H4',
    'logout'    => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
];

if ($role === 'admin') {
    $menu = [
        ['dashboard',    'Dashboard',         'dashboard.php',            'dashboard', null],
        ['armada',       'Data Armada',       'dashboard.php#daftar-mobil',     'armada', $navCounts['armada'] ?? null],
        ['transaksi',    'Riwayat Transaksi', 'dashboard.php#daftar-transaksi', 'transaksi', $navCounts['transaksi'] ?? null],
        ['mobil_create', 'Tambah Mobil',      'mobil_create.php',         'plus', null],
    ];
} else {
    $menu = [
        ['dashboard', 'Dashboard',  'dashboard.php', 'dashboard', null],
        ['sewa',      'Sewa Mobil', 'sewa.php',      'armada', null],
    ];
}

function navIcon(string $path, string $cls = 'w-5 h-5'): string
{
    return '<svg class="' . $cls . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="' . $path
         . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Dashboard') ?> — AutoRent</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 500: '#6366f1',
                            600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81', 950: '#1e1b4b'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #f8fafc; font-feature-settings: 'cv02','cv03','cv04','cv11'; }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        @media print {
            aside, header, .no-print { display: none !important; }
            .app-main { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="font-sans text-slate-800 antialiased min-h-screen bg-slate-50 flex flex-col">
<div class="flex min-h-screen w-full">

    <!-- Sidebar -->
    <aside class="hidden lg:flex w-64 bg-slate-900 border-r border-slate-800 flex-col justify-between shrink-0 fixed inset-y-0 left-0 z-30 select-none">
        <div>
            <div class="px-6 py-5 flex items-center gap-3 border-b border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M8 17h.01M16 17h.01M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0a2 2 0 00-2 2v4a2 2 0 002 2h1a2 2 0 002-2v-1h8v1a2 2 0 002 2h1a2 2 0 002-2v-4a2 2 0 00-2-2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-white font-bold tracking-tight text-lg block leading-none">AutoRent</span>
                    <span class="text-xs text-indigo-400 font-medium tracking-wide uppercase mt-1 inline-block">Management v1.0</span>
                </div>
            </div>

            <nav class="mt-6 px-3 space-y-1">
                <?php foreach ($menu as [$key, $label, $href, $icon, $badge]): ?>
                    <?php $active = ($activePage ?? '') === $key; ?>
                    <a href="<?= h($href) ?>"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $active
                           ? 'bg-brand-600 text-white font-semibold shadow-md shadow-brand-600/20'
                           : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60 font-medium' ?>">
                        <?= navIcon($icons[$icon], 'w-5 h-5 ' . ($active ? 'text-indigo-200' : '')) ?>
                        <span class="flex-1"><?= h($label) ?></span>
                        <?php if ($badge !== null): ?>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-slate-800 text-slate-300 font-semibold border border-slate-700"><?= (int) $badge ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between bg-slate-800/60 p-2.5 rounded-xl border border-slate-700/60">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="relative shrink-0">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600/30 text-indigo-400 font-bold text-sm flex items-center justify-center border border-indigo-500/30">
                            <?= h(initials($userName)) ?>
                        </div>
                        <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-slate-900 ring-1 ring-emerald-400/50"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-white truncate">Halo, <?= h($userName) ?></div>
                        <div class="text-xs text-slate-400"><?= h(ucfirst($role)) ?></div>
                    </div>
                </div>
                <a href="logout.php" title="Logout" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                    <?= navIcon($icons['logout']) ?>
                </a>
            </div>
        </div>
    </aside>

    <!-- Konten utama -->
    <div class="app-main flex-1 lg:ml-64 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-20 px-4 sm:px-8 flex items-center justify-between shadow-sm">
            <?php if ($showSearch): ?>
                <div class="relative w-full max-w-lg">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                    <input id="globalSearch" type="text" placeholder="Cari unit mobil, nomor plat, atau customer..."
                           class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-slate-700 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                </div>
            <?php else: ?>
                <div class="font-bold text-slate-900"><?= h($pageTitle ?? '') ?></div>
            <?php endif; ?>

            <div class="flex items-center gap-3 pl-4">
                <!-- Navigasi ringkas untuk layar kecil -->
                <div class="flex lg:hidden items-center gap-2 text-xs font-semibold">
                    <?php foreach ($menu as [$key, $label, $href]): ?>
                        <a href="<?= h($href) ?>" class="px-2.5 py-1.5 rounded-lg <?= ($activePage ?? '') === $key ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' ?>"><?= h($label) ?></a>
                    <?php endforeach; ?>
                    <a href="logout.php" class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-600">Logout</a>
                </div>
                <div class="text-right hidden xl:block pl-3 border-l border-slate-200">
                    <p class="text-xs font-semibold text-slate-800"><?= h($userName) ?></p>
                    <p class="text-[11px] text-slate-500"><?= h(ucfirst($role)) ?></p>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-8 space-y-8 flex-1 max-w-7xl mx-auto w-full">


<?php if ($role === 'admin'): ?>

    <!-- Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-900 p-8 text-white shadow-xl shadow-indigo-950/10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-brand-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-200 border border-indigo-400/20 inline-flex items-center gap-1.5 mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Sistem Rental Aktif
            </span>
            <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Kelola Data Mobil &amp; Transaksi</h1>
            <p class="text-indigo-200/80 mt-1 max-w-xl text-sm leading-relaxed">
                Pantau ketersediaan unit sewa, atur tarif harian, serta proses verifikasi transaksi pelanggan secara real-time.
            </p>
        </div>
        <div class="relative z-10 shrink-0 flex items-center gap-3">
            <a href="mobil_create.php"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-sm shadow-lg shadow-brand-600/40 hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path></svg>
                Tambah Mobil
            </a>
        </div>
    </div>

    <!-- KPI -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Armada</span>
                <div class="p-2.5 rounded-xl bg-indigo-50 text-brand-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $totalArmada ?></span>
                <span class="text-xs font-semibold text-slate-500">Unit Terdaftar</span>
            </div>
            <div class="mt-2 text-xs text-indigo-600 font-medium"><?= $totalArmada - $mobilTersedia ?> unit sedang disewa / perbaikan</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Mobil Tersedia</span>
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-emerald-600 tracking-tight"><?= $mobilTersedia ?></span>
                <span class="text-xs font-semibold text-slate-500">Unit Siap Sewa</span>
            </div>
            <div class="mt-2 text-xs text-emerald-700 font-medium"><?= $persenTersedia ?>% ketersediaan hari ini</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Transaksi</span>
                <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $totalTransaksi ?></span>
                <span class="text-xs font-semibold text-slate-500">Order Masuk</span>
            </div>
            <div class="mt-2 text-xs text-slate-500 font-medium"><?= $trxMenunggu ?> Menunggu • <?= $trxDisetujui ?> Disetujui • <?= $trxDitolak ?> Ditolak</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Nilai Sewa</span>
                <div class="p-2.5 rounded-xl bg-violet-50 text-violet-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= h(rupiah($totalNilaiSewa)) ?></span>
            </div>
            <div class="mt-2 text-xs text-violet-600 font-medium">Akumulasi <?= $trxDisetujui ?> pesanan disetujui</div>
        </div>
    </section>

    <!-- Daftar Mobil -->
    <section id="daftar-mobil" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden scroll-mt-20">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-900">Daftar Mobil (Armada)</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-brand-700 border border-brand-200"><?= $totalArmada ?> Unit</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Katalog mobil rental yang terdaftar di sistem</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <input id="filterMobil" type="text" placeholder="Saring nama / plat..."
                       class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 w-48 text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                <select id="filterStatusMobil"
                        class="text-xs bg-slate-100 border-0 rounded-lg px-3 py-2 pr-8 font-semibold text-slate-700 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Semua status</option>
                    <option value="tersedia">Tersedia</option>
                    <option value="disewa">Disewa</option>
                    <option value="perbaikan">Perbaikan</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="tabelMobil">
                <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6" scope="col">Mobil</th>
                        <th class="py-3.5 px-6" scope="col">Plat Nomor</th>
                        <th class="py-3.5 px-6" scope="col">Tarif Harian</th>
                        <th class="py-3.5 px-6" scope="col">Status</th>
                        <th class="py-3.5 px-6 text-center" scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php foreach ($mobilList as $mobil): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors"
                        data-search="<?= h(strtolower($mobil['nama_mobil'] . ' ' . $mobil['plat_nomor'])) ?>"
                        data-status="<?= h(strtolower($mobil['status'])) ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-brand-600 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                </div>
                                <div class="font-bold text-slate-900"><?= h($mobil['nama_mobil']) ?></div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-block px-3 py-1 font-mono text-xs font-bold bg-slate-100 text-slate-800 rounded-md border border-slate-200 tracking-wider"><?= h($mobil['plat_nomor']) ?></span>
                        </td>
                        <td class="py-4 px-6 font-bold text-slate-900">
                            <?= h(rupiah($mobil['harga_sewa_per_hari'])) ?> <span class="text-xs font-normal text-slate-400">/ hari</span>
                        </td>
                        <td class="py-4 px-6"><?= statusBadge($mobil['status']) ?></td>
                        <td class="py-4 px-6 text-center">
                            <div class="inline-flex items-center gap-2">
                                <a href="mobil_update.php?id=<?= (int) $mobil['id_mobil'] ?>"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition-colors shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Edit
                                </a>
                                <a href="mobil_delete.php?id=<?= (int) $mobil['id_mobil'] ?>"
                                   onclick="return confirm('Hapus mobil ini?')"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200/60 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$mobilList): ?>
                    <tr><td colspan="5" class="py-10 text-center text-slate-400">Belum ada mobil terdaftar.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50/60 border-t border-slate-100 text-xs text-slate-500">
            Menampilkan <span id="countMobil"><?= $totalArmada ?></span> dari <?= $totalArmada ?> armada mobil yang terdaftar
        </div>
    </section>

    <!-- Daftar Transaksi -->
    <section id="daftar-transaksi" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden scroll-mt-20">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-900">Daftar Transaksi Penyewaan</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-50 text-violet-700 border border-violet-200">Total <?= $totalTransaksi ?> Pesanan</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Seluruh reservasi dan permohonan sewa pelanggan</p>
            </div>
            <div class="flex items-center gap-2 no-print">
                <button type="button" onclick="window.print()"
                        class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="tabelTransaksi">
                <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6" scope="col">Customer</th>
                        <th class="py-3.5 px-6" scope="col">Mobil yang Disewa</th>
                        <th class="py-3.5 px-6" scope="col">Total Biaya</th>
                        <th class="py-3.5 px-6" scope="col">Status Verifikasi</th>
                        <th class="py-3.5 px-6 text-center no-print" scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php foreach ($transaksiList as $transaksi): ?>
                    <?php
                        $st = $transaksi['status_transaksi'];
                        $avatar = match ($st) {
                            'disetujui' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'ditolak'   => 'bg-rose-100 text-rose-700 border-rose-200',
                            default     => 'bg-amber-100 text-amber-700 border-amber-200',
                        };
                        $durasi = null;
                        if (!empty($transaksi['tanggal_sewa']) && !empty($transaksi['tanggal_kembali'])) {
                            $durasi = max(1, (int) (new DateTime($transaksi['tanggal_sewa']))->diff(new DateTime($transaksi['tanggal_kembali']))->days);
                        }
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors"
                        data-search="<?= h(strtolower($transaksi['nama'] . ' ' . $transaksi['nama_mobil'])) ?>">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full font-bold text-xs flex items-center justify-center border <?= $avatar ?>"><?= h(initials($transaksi['nama'])) ?></div>
                                <div class="font-bold text-slate-900 capitalize"><?= h($transaksi['nama']) ?></div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-semibold text-slate-800"><?= h($transaksi['nama_mobil']) ?></div>
                            <?php if ($durasi !== null): ?>
                                <span class="text-xs text-slate-400">Durasi sewa: <?= $durasi ?> Hari</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-6 font-bold text-slate-900"><?= h(rupiah($transaksi['total_biaya'])) ?></td>
                        <td class="py-4 px-6"><?= statusBadge($st) ?></td>
                        <td class="py-4 px-6 text-center no-print">
                            <?php if ($st === 'menunggu'): ?>
                                <div class="inline-flex items-center gap-2">
                                    <a href="approve.php?id=<?= (int) $transaksi['id_transaksi'] ?>"
                                       class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">Setujui</a>
                                    <a href="reject.php?id=<?= (int) $transaksi['id_transaksi'] ?>"
                                       class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 border border-rose-200/60 hover:bg-rose-100 transition-colors">Tolak</a>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-300">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$transaksiList): ?>
                    <tr><td colspan="5" class="py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50/60 border-t border-slate-100 text-xs text-slate-500">
            Menampilkan <span id="countTransaksi"><?= $totalTransaksi ?></span> entri transaksi pesanan
        </div>
    </section>

    <script>
    (function () {
        const global = document.getElementById('globalSearch');
        const fMobil = document.getElementById('filterMobil');
        const fStatus = document.getElementById('filterStatusMobil');

        function applyFilters() {
            const g = (global.value || '').toLowerCase().trim();
            const m = (fMobil.value || '').toLowerCase().trim();
            const s = fStatus.value;

            let nMobil = 0;
            document.querySelectorAll('#tabelMobil tbody tr[data-search]').forEach(tr => {
                const text = tr.dataset.search;
                const show = (!g || text.includes(g)) && (!m || text.includes(m)) && (!s || tr.dataset.status === s);
                tr.classList.toggle('hidden', !show);
                if (show) nMobil++;
            });
            document.getElementById('countMobil').textContent = nMobil;

            let nTrx = 0;
            document.querySelectorAll('#tabelTransaksi tbody tr[data-search]').forEach(tr => {
                const show = !g || tr.dataset.search.includes(g);
                tr.classList.toggle('hidden', !show);
                if (show) nTrx++;
            });
            document.getElementById('countTransaksi').textContent = nTrx;
        }

        [global, fMobil].forEach(el => el.addEventListener('input', applyFilters));
        fStatus.addEventListener('change', applyFilters);
    })();
    </script>

<?php else: ?>

    <!-- Tampilan customer -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-900 p-8 text-white shadow-xl shadow-indigo-950/10">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-brand-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight">Halo, <?= h($user['nama'] ?? 'Pelanggan') ?></h1>
            <p class="text-indigo-200/80 mt-1 text-sm">Pilih mobil yang tersedia dan pantau status penyewaan Anda.</p>
        </div>
    </div>

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900">Mobil Tersedia</h2>
        <p class="text-xs text-slate-500 mt-1 mb-5">Unit yang siap disewa hari ini</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($availableCars as $car): ?>
                <div class="rounded-2xl border border-slate-200 p-5 hover:shadow-md transition-shadow flex flex-col">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-brand-600 mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </div>
                    <h3 class="font-bold text-slate-900"><?= h($car['nama_mobil']) ?></h3>
                    <span class="mt-2 self-start px-3 py-1 font-mono text-xs font-bold bg-slate-100 text-slate-800 rounded-md border border-slate-200 tracking-wider"><?= h($car['plat_nomor']) ?></span>
                    <p class="mt-3 font-bold text-slate-900"><?= h(rupiah($car['harga_sewa_per_hari'])) ?> <span class="text-xs font-normal text-slate-400">/ hari</span></p>
                    <a href="sewa.php?id=<?= (int) $car['id_mobil'] ?>"
                       class="mt-4 text-center px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">Sewa</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$availableCars): ?>
                <p class="text-slate-400 text-sm col-span-full">Belum ada mobil yang tersedia.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-lg font-bold text-slate-900">Riwayat Penyewaan Saya</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Mobil</th>
                        <th class="py-3.5 px-6">Mulai</th>
                        <th class="py-3.5 px-6">Selesai</th>
                        <th class="py-3.5 px-6">Total</th>
                        <th class="py-3.5 px-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php foreach ($userTransactions as $tx): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-4 px-6 font-bold text-slate-900"><?= h($tx['nama_mobil']) ?></td>
                        <td class="py-4 px-6"><?= h($tx['tanggal_sewa']) ?></td>
                        <td class="py-4 px-6"><?= h($tx['tanggal_kembali']) ?></td>
                        <td class="py-4 px-6 font-bold text-slate-900"><?= h(rupiah($tx['total_biaya'])) ?></td>
                        <td class="py-4 px-6"><?= statusBadge($tx['status_transaksi']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$userTransactions): ?>
                    <tr><td colspan="5" class="py-10 text-center text-slate-400">Belum ada riwayat penyewaan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php endif; ?>

        </main>

        <footer class="bg-white border-t border-slate-200 py-4 px-8 mt-auto text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 no-print">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-700">AutoRent Car Management</span>
                <span>•</span>
                <span>Sistem Manajemen Rental Mobil</span>
            </div>
            <div>&copy; <?= date('Y') ?> AutoRent</div>
        </footer>
    </div>
</div>
</body>
</html>
