<?php
include 'koneksi_kereta.php';
$tabel_per_tahun = [
    '2024' => 'tahun_2024',
    '2025' => 'tahun_2025',
    '2026' => 'tahun_2026',
];
 
$tahun_tersedia = array_map('strval', array_keys($tabel_per_tahun));
$tahun_dipilih  = $_GET['tahun'] ?? 'semua';
if ($tahun_dipilih !== 'semua' && !in_array($tahun_dipilih, $tahun_tersedia, true)) {
    $tahun_dipilih = 'semua';
}
$tabel_aktif = $tahun_dipilih === 'semua'
    ? $tabel_per_tahun
    : [$tahun_dipilih => $tabel_per_tahun[$tahun_dipilih]];
$union_parts = [];
foreach ($tabel_aktif as $thn => $nama_tabel) {
    $nama_tabel = mysqli_real_escape_string($conn, $nama_tabel);
    $union_parts[] = "SELECT '{$thn}' AS tahun_data, tanggal, tiket_terjual, kapasitas_td, load_factor
                       FROM `{$nama_tabel}`";
}
$union_sql = implode(" UNION ALL ", $union_parts);
 
$total_tiket = 0;
$total_kapasitas = 0;
$rata_load = 0;
$total_data = 0;
 
$query = mysqli_query($conn, "
    SELECT
        SUM(tiket_terjual) AS total_tiket,
        SUM(kapasitas_td)  AS total_kapasitas,
        AVG(load_factor)   AS rata_load,
        COUNT(*)           AS total_data
    FROM ({$union_sql}) AS gabungan
");
 
if ($query && ($row = mysqli_fetch_assoc($query))) {
    $total_tiket     = $row['total_tiket'] ?? 0;
    $total_kapasitas = $row['total_kapasitas'] ?? 0;
    $rata_load       = $row['rata_load'] ?? 0;
    $total_data      = $row['total_data'] ?? 0;
}
 
$nama_bulan = [
    1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr",
    5 => "Mei", 6 => "Jun", 7 => "Jul", 8 => "Agu",
    9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des",
];

$data_grafik = [];
foreach ($tabel_aktif as $thn => $nama_tabel) {
    $data_grafik[$thn] = array_fill(1, 12, 0);
}
 
$query = mysqli_query($conn, "
    SELECT
        tahun_data,
        MONTH(tanggal) AS bulan,
        SUM(tiket_terjual) AS total
    FROM ({$union_sql}) AS gabungan
    GROUP BY tahun_data, MONTH(tanggal)
    ORDER BY tahun_data, MONTH(tanggal)
");
 
while ($query && ($row = mysqli_fetch_assoc($query))) {
    $thn = $row['tahun_data'];
    $bln = (int) $row['bulan'];
    if (isset($data_grafik[$thn][$bln])) {
        $data_grafik[$thn][$bln] = (int) $row['total'];
    }
}
 
$label_bulan = array_values($nama_bulan);
 
$warna_tahun = [
    '2024' => '#8FB8E0',
    '2025' => '#F5B700',
    '2026' => '#0B2A4A',
];
 
$dataset_js = [];
foreach ($data_grafik as $thn => $per_bulan) {
    $dataset_js[] = [
        'label' => (string) $thn,
        'data'  => array_values($per_bulan),
        'color' => $warna_tahun[$thn] ?? '#0B2A4A',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Angkutan Penumpang KA Sumatera Selatan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="style.css?v=5">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Tambahan tampilan desktop (menu diperkecil + hero dirapikan).. -->
<style>
@media (min-width: 901px) {
    /* header & menu lebih kecil */
    .header { height: 90px !important; }
    .header nav { gap: 28px !important; }
    .header nav a { font-size: 14px !important; gap: 8px !important; }
    .header nav a b { font-size: 18px !important; }
    .header nav a.active:after { bottom: 22px !important; height: 3px !important; }
    .logo-icon { width: 58px !important; height: 58px !important; }
    .logo h2 { font-size: 15px !important; }
    .logo span { font-size: 11px !important; margin-top: 5px !important; }

    /* hero lebih rapi */
    .hero { height: clamp(540px, 36vw, 660px) !important; min-height: 0 !important; }
    .hero-text { padding: 40px 0 100px 4.5% !important; }
    .hero h1 {
        font-size: clamp(36px, 3.4vw, 54px) !important;
        line-height: 1.12 !important;
        margin-bottom: 18px !important;
    }
    .hero p {
        font-size: clamp(16px, 1.25vw, 19px) !important;
        line-height: 1.65 !important;
        max-width: 560px !important;
        margin-bottom: 24px !important;
    }
    .hero-rail img { object-position: 62% center !important; }
}

.copyright {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(11, 42, 74, 0.12);
    font-size: 13px;
    color: #6b7c93;
}
.header {
    position: sticky;
    top: 0;
    z-index: 1000;
}
</style>
</head>
<body>
 
<header class="header ">
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
       <a href="index.php" class="active"><b><i class="bi bi-house-door"></i></b> Dashboard</a>
       <a href="data.php"><b><i class="bi bi-table"></i></b> Data</a>
       <a href="pegawai.php"><b><i class="bi bi-people"></i></b> Pegawai</a>
       <a href="statistik.php"><b><i class="bi bi-bar-chart-line"></i></b> Statistik</a>
       <a href="#galeri"><b><i class="bi bi-images"></i></b> Galeri</a>
       <a href="informasi.php"><b><i class="bi bi-info-circle"></i></b> Informasi</a>
    </nav>
</header>
 
<section class="hero">
    <div class="hero-bg"></div>
    <div class="yellow-corner"></div>

    <div class="hero-text">
        <span class="badge">Data Penumpang &ndash; KA Sumsel</span>
        <h1>Dashboard <span>Angkutan</span> Penumpang Kereta Api</h1>
        <p>Informasi dan statistik volume angkutan penumpang kereta api Divre III Palembang, disajikan agar mudah dipantau dari satu layar.</p>
        <a href="data.php" class="btn">Lihat Data Lengkap <b>&rarr;</b></a>
    </div>

    <div class="hero-rail">
        <div class="photo-fade"></div>
        <img src="img/dishub.png" alt="Kereta Api Sumatera Selatan">
    </div>

    <div class="hero-bottom-line"></div>
</section>
 
<section class="container">
 
    <div class="section-title">
        <span>Ringkasan Data</span>
        <h2>Statistik Penumpang</h2>
    </div>
 
    <div class="year-tabs" role="tablist" aria-label="Pilih tahun data">
        <a href="?tahun=semua" class="year-tab <?= $tahun_dipilih === 'semua' ? 'active' : '' ?>">Semua Tahun</a>
        <?php foreach ($tahun_tersedia as $thn): ?>
            <a href="?tahun=<?= urlencode($thn) ?>" class="year-tab <?= $tahun_dipilih === $thn ? 'active' : '' ?>"><?= htmlspecialchars($thn) ?></a>
        <?php endforeach; ?>
    </div>
 
    <div class="cards">
        <div class="card ticket-card">
            <div class="card-icon">👥</div>
            <div>
                <p>Total Tiket Terjual</p>
                <h2><?= number_format($total_tiket, 0, ',', '.'); ?></h2>
                <small>Penumpang</small>
            </div>
        </div>
 
        <div class="card ticket-card card-accent">
            <div class="card-icon">🎫</div>
            <div>
                <p>Total Kapasitas</p>
                <h2><?= number_format($total_kapasitas, 0, ',', '.'); ?></h2>
                <small>Kursi</small>
            </div>
        </div>
 
        <div class="card ticket-card">
            <div class="card-icon">📊</div>
            <div>
                <p>Rata-rata Load Factor</p>
                <h2><?= number_format($rata_load, 2, ',', '.'); ?>%</h2>
                <small>Tingkat keterisian</small>
            </div>
        </div>
 
        <div class="card ticket-card card-accent">
            <div class="card-icon">📅</div>
            <div>
                <p>Data Harian</p>
                <h2><?= number_format($total_data, 0, ',', '.'); ?></h2>
                <small>Baris data (<?= $tahun_dipilih === 'semua' ? implode('-', $tahun_tersedia) : htmlspecialchars($tahun_dipilih) ?>)</small>
            </div>
        </div>
    </div>
 
    <div class="chart-box">
        <div class="chart-header">
            <div>
                <span>Perkembangan Data</span>
                <h2>Volume Tiket Terjual per Bulan</h2>
            </div>
            <div class="year"><?= $tahun_dipilih === 'semua' ? implode(' &bull; ', $tahun_tersedia) : htmlspecialchars($tahun_dipilih) ?></div>
        </div>
        <canvas id="grafikPenumpang"></canvas>
    </div>
 
    <div class="section-title" id="galeri">
        <span>Dokumentasi</span>
        <h2>Galeri </h2>
    </div>
 
    <div class="gallery">
        <div class="photo-frame">
            <img src="img/KAsumsel.png" alt="Kereta Api">
            <span>Kereta Api</span>
        </div>
        <div class="photo-frame">
            <img src="img/dishubsumsel.png" alt="DISHUB">
            <span>DISHUB SUMSEL</span>
        </div>
        <div class="photo-frame">
            <img src="img/skertapati.png" alt="Kereta Api">
            <span>Stasiun Kertapati Palembang</span>
        </div>
        <div class="photo-frame">
            <img src="img/dishublogo.png" alt="DISHUB">
            <span>Dinas Perhubungan </span>
        </div>
    </div>
 
    <div class="info-section">
        <div>
            <span>Informasi Data</span>
            <h2>Data Angkutan Penumpang Divre III Palembang</h2>
            <p>
                Data menampilkan volume angkutan penumpang berdasarkan
                pencatatan harian selama tahun 2024 sampai 2026. Informasi dapat
                digunakan untuk melihat perkembangan jumlah penumpang
                dan tingkat keterisian kapasitas kereta dari tahun ke tahun.
            </p>
        </div>
        <div class="info-box">
            <div>
                <strong>12</strong>
                <span>Bulan</span>
            </div>
            <div>
                <strong><?= count($tahun_tersedia) ?></strong>
                <span>Tahun Data</span>
            </div>
            <div>
                <strong>III</strong>
                <span>Divre</span>
            </div>
        </div>
    </div>
 
</section>
 
<footer class="footer">
    <div class="footer-atas">

        <div class="footer-kolom footer-profil">
            <div class="footer-logo">
                <img src="img/logodinassumsel.png" alt="Logo Dinas Perhubungan">
                <div>
                    <strong>Dinas Perhubungan</strong>
                    <strong>Provinsi Sumatera Selatan</strong>
                </div>
            </div>

            <p class="footer-alamat">
              <b> Jl. Kapten A. Rivai No. 51 Palembang, Provinsi Sumatera Selatan </b>
            </p>

            <h4>Email</h4>
            <p><b>dishub.sumselprov@.gmail.com </b></p>

            <h4>Telepon</h4>
            <p><b> (0711) 352005- 363125 </b> </p>

    <div class="footer-sosial">
    <a href="https://www.facebook.com/dinasperhubungan.provinsisumsel" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
    <a href="https://www.instagram.com/dishub_sumsel" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
    <a href="https://www.youtube.com/@dishubsumsel2198" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
</div>
        </div>

        <div class="footer-kolom">
            <h4>Navigasi</h4>
            <ul>
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="data.php">Data</a></li>
                <li><a href="pegawai.php">Pegawai</a></li>
                <li><a href="statistik.php">Statistik</a></li>
                <li><a href="index.php#galeri">Galeri</a></li>
                <li><a href="informasi.php">Informasi</a></li>
            </ul>
        </div>

        <div class="footer-kolom">
            <h4>Data Per Tahun</h4>
            <ul>
                <li><a href="data.php?tahun=2024">Data 2024</a></li>
                <li><a href="data.php?tahun=2025">Data 2025</a></li>
                <li><a href="data.php?tahun=2026">Data 2026</a></li>
                <li><a href="data.php?tahun=semua">Semua Tahun</a></li>
            </ul>
        </div>

        <div class="footer-kolom">
            <h4>Instansi Terkait</h4>
            <ul>
    <li><a href="https://dishub.sumselprov.go.id" target="_blank" rel="noopener noreferrer">Dinas Perhubungan Sumatera Selatan</a></li>
    <li><a href="https://www.kai.id" target="_blank" rel="noopener noreferrer">KAI Divre III Palembang</a></li>
    <li><a href="https://dishub.sumselprov.go.id/page/bidang-perkeretaapian-dan-pengembangan" target="_blank" rel="noopener noreferrer">Bidang Perkeretaapiaan & Pengembangan</a></li>
        </ul>
           </div>

    </div>

    <div class="footer-bawah">
        <p>&copy; <?= date('Y') ?> Dinas Perhubungan Provinsi Sumatera Selatan. Hak cipta dilindungi.</p>
        <p>Data 2024 &ndash; 2026 &bull; Divre III Palembang</p>
    </div>
</footer>
 
 
<script>
const ctx = document.getElementById('grafikPenumpang');
const labelBulan = <?= json_encode($label_bulan); ?>;
const datasetData = <?= json_encode($dataset_js); ?>;
 
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labelBulan,
        datasets: datasetData.map((set) => ({
            label: set.label,
            data: set.data,
            backgroundColor: set.color,
            hoverBackgroundColor: set.color,
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: datasetData.length > 1 ? 20 : 42
        }))
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: datasetData.length > 1, labels: { font: { family: 'Inter' } } },
            tooltip: {
                backgroundColor: '#0B2A4A',
                titleFont: { family: 'Inter' },
                bodyFont: { family: 'Inter' },
                callbacks: {
                    label: (item) => ' ' + item.dataset.label + ': ' + item.formattedValue.replace(/,/g, '.') + ' tiket'
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Inter' } }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(11,42,74,0.08)' },
                ticks: {
                    font: { family: 'Inter' },
                    callback: (value) => value.toLocaleString('id-ID')
                }
            }
        }
    }
});
 
const navToggle = document.getElementById('navToggle');
const navMenu = document.getElementById('navMenu');
navToggle.addEventListener('click', () => {
    const terbuka = navMenu.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
});
</script>
 
</body>
</html>