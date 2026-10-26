<?php
$menu = [
    'dashboard' => ['index.php',        'bi-house-door',     'Dashboard'],
    'data'      => ['data.php',         'bi-table',          'Data'],
    'pegawai'   => ['pegawai.php',      'bi-people',         'Pegawai'],
    'statistik' => ['statistik.php',    'bi-bar-chart-line', 'Statistik'],
    'galeri'    => ['index.php#galeri', 'bi-images',         'Galeri'],
    'informasi' => ['informasi.php',    'bi-info-circle',    'Informasi'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($judul_halaman) ?> | Dashboard Angkutan Penumpang KA Sumatera Selatan</title>
<link rel="icon" type="image/png" href="img/logodishubb.png?v=5">
<link rel="apple-touch-icon" href="img/logodishubb.png?v=5">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="style.css?v=5">
<link rel="stylesheet" href="halaman.css?v=5">
<?php if (!empty($pakai_chart)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<style>
@media (min-width: 901px) {
    .header { height: 90px !important; }
    .header nav { gap: 28px !important; }
    .header nav a { font-size: 14px !important; gap: 8px !important; }
    .header nav a b { font-size: 18px !important; }
    .header nav a.active:after { bottom: 22px !important; height: 3px !important; }
    .logo-icon { width: 58px !important; height: 58px !important; }
    .logo h2 { font-size: 15px !important; }
    .logo span { font-size: 11px !important; margin-top: 5px !important; }
}
.header {
    position: sticky;
    top: 0;
    z-index: 1000;
}
</style>
</head>
<body>

<header class="header">
    <div class="logo">
        <div class="logo-icon"><img src="img/logodishubb.png" alt="Logo Dinas Perhubungan"></div>
        <div class="logo-text">
            <h2>Dinas Perhubungan</h2>
            <h2>Provinsi Sumatera Selatan</h2>
            <span>Data Angkutan Penumpang KA</span>
        </div>
    </div>

    <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>

    <nav id="navMenu">
        <?php foreach ($menu as $kunci => [$url, $ikon, $teks]): ?>
            <a href="<?= $url ?>" class="<?= $kunci === $halaman_aktif ? 'active' : '' ?>"><b><i class="bi <?= $ikon ?>"></i></b> <?= $teks ?></a>
        <?php endforeach; ?>
    </nav>
</header>

<section class="page-banner">
    <span class="page-banner-label"><?= htmlspecialchars($label_banner) ?></span>
    <h1><?= htmlspecialchars($judul_halaman) ?></h1>
    <p><?= htmlspecialchars($deskripsi_banner) ?></p>
</section>