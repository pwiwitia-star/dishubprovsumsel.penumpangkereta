<?php
require 'FungsiKereta.php';
$union_sql = buat_union($conn, $tabel_per_tahun, 'semua');

/* ---- 1. Ringkasan per tahun ---- */
$ringkasan = [];
$q = mysqli_query($conn, "
    SELECT
        tahun_data,
        SUM(tiket_terjual) AS total_tiket,
        SUM(kapasitas_td)  AS total_kapasitas,
        AVG(load_factor)   AS rata_load,
        MAX(load_factor)   AS load_tertinggi,
        MIN(load_factor)   AS load_terendah,
        COUNT(*)           AS jumlah_hari
    FROM ({$union_sql}) AS gabungan
    GROUP BY tahun_data
    ORDER BY tahun_data
");
while ($q && ($r = mysqli_fetch_assoc($q))) {
    $ringkasan[$r['tahun_data']] = $r;
}

/* ---- 2. Load factor per bulan ---- */
$label_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$warna_tahun = ['2024' => '#8FB8E0', '2025' => '#F5B700', '2026' => '#0B2A4A'];

$lf_bulanan = [];
foreach ($tahun_tersedia as $thn) {
    $lf_bulanan[$thn] = array_fill(1, 12, null);
}
$q = mysqli_query($conn, "
    SELECT tahun_data, MONTH(tanggal) AS bulan, AVG(load_factor) AS rata
    FROM ({$union_sql}) AS gabungan
    GROUP BY tahun_data, MONTH(tanggal)
");
while ($q && ($r = mysqli_fetch_assoc($q))) {
    $lf_bulanan[$r['tahun_data']][(int) $r['bulan']] = round((float) $r['rata'], 2);
}

$dataset_lf = [];
foreach ($lf_bulanan as $thn => $per_bulan) {
    $dataset_lf[] = [
        'label' => (string) $thn,
        'data'  => array_values($per_bulan),
        'color' => $warna_tahun[$thn] ?? '#0B2A4A',
    ];
}

/* ---- 3. Total tiket per tahun ---- */
$label_tahun = [];
$total_per_tahun = [];
foreach ($ringkasan as $thn => $r) {
    $label_tahun[] = (string) $thn;
    $total_per_tahun[] = (int) $r['total_tiket'];
}

/* ---- 4. Sepuluh hari tersibuk ---- */
$hari_tertinggi = mysqli_query($conn, "
    SELECT tahun_data, tanggal, tiket_terjual, kapasitas_td, load_factor
    FROM ({$union_sql}) AS gabungan
    ORDER BY tiket_terjual DESC
    LIMIT 10
");

/* ---- pengaturan header ---- */
$halaman_aktif    = 'statistik';
$judul_halaman    = 'Statistik Perbandingan';
$label_banner     = 'Analisis Data';
$deskripsi_banner = 'Bandingkan angkutan penumpang dan tingkat keterisian kereta dari tahun ke tahun.';
$pakai_chart      = false; // Chart.js dimuat langsung di bawah halaman ini

include 'HeaderHalaman.php';
?>

<section class="container">

    <div class="section-title">
        <span>Per Tahun</span>
        <h2>Ringkasan Perbandingan</h2>
    </div>

    <div class="table-box">
        <table class="tabel-data">
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th class="angka">Total Tiket</th>
                    <th class="angka">Total Kapasitas</th>
                    <th class="angka">Rata-rata Load Factor</th>
                    <th class="angka">Tertinggi</th>
                    <th class="angka">Terendah</th>
                    <th class="angka">Hari Tercatat</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ringkasan): ?>
                <?php foreach ($ringkasan as $thn => $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($thn) ?></strong></td>
                        <td class="angka"><?= angka($r['total_tiket']) ?></td>
                        <td class="angka"><?= angka($r['total_kapasitas']) ?></td>
                        <td class="angka"><?= angka($r['rata_load'], 2) ?>%</td>
                        <td class="angka"><?= angka($r['load_tertinggi'], 2) ?>%</td>
                        <td class="angka"><?= angka($r['load_terendah'], 2) ?>%</td>
                        <td class="angka"><?= angka($r['jumlah_hari']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="kosong">Data belum tersedia.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="chart-grid">
        <div class="chart-box">
            <div class="chart-header">
                <div>
                    <span>Tingkat Keterisian</span>
                    <h2>Rata-rata Load Factor per Bulan</h2>
                </div>
            </div>
            <canvas id="grafikLoad"></canvas>
        </div>

        <div class="chart-box">
            <div class="chart-header">
                <div>
                    <span>Volume</span>
                    <h2>Total Tiket Terjual per Tahun</h2>
                </div>
            </div>
            <canvas id="grafikTahun"></canvas>
        </div>
    </div>

    <h3 class="sub-judul">10 Hari dengan Tiket Terjual Tertinggi</h3>
    <div class="table-box">
        <table class="tabel-data">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Tanggal</th>
                    <th class="angka">Tiket Terjual</th>
                    <th class="angka">Kapasitas (TD)</th>
                    <th class="angka">Load Factor</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; ?>
            <?php if ($hari_tertinggi && mysqli_num_rows($hari_tertinggi) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($hari_tertinggi)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td class="angka"><?= angka($row['tiket_terjual']) ?></td>
                        <td class="angka"><?= angka($row['kapasitas_td']) ?></td>
                        <td class="angka"><?= angka($row['load_factor'], 2) ?>%</td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="kosong">Data belum tersedia.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const fontInter = { family: "Inter" };
const dataLoad  = <?= json_encode($dataset_lf) ?>;
const labelBln  = <?= json_encode($label_bulan) ?>;

/* Grafik garis: load factor per bulan */
new Chart(document.getElementById("grafikLoad"), {
    type: "line",
    data: {
        labels: labelBln,
        datasets: dataLoad.map(s => ({
            label: s.label,
            data: s.data,
            borderColor: s.color,
            backgroundColor: s.color,
            borderWidth: 3,
            tension: 0.3,
            pointRadius: 3,
            spanGaps: false
        }))
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { font: fontInter } },
            tooltip: {
                backgroundColor: "#0B2A4A",
                callbacks: {
                    label: (i) => " " + i.dataset.label + ": " + (i.parsed.y ?? 0).toLocaleString("id-ID") + "%"
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: fontInter } },
            y: {
                beginAtZero: true,
                grid: { color: "rgba(11,42,74,0.08)" },
                ticks: { font: fontInter, callback: (v) => v + "%" }
            }
        }
    }
});

/* Grafik batang: total tiket per tahun */
new Chart(document.getElementById("grafikTahun"), {
    type: "bar",
    data: {
        labels: <?= json_encode($label_tahun) ?>,
        datasets: [{
            label: "Tiket terjual",
            data: <?= json_encode($total_per_tahun) ?>,
            backgroundColor: ["#8FB8E0", "#F5B700", "#0B2A4A"],
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 64
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: "#0B2A4A",
                callbacks: {
                    label: (i) => " " + i.parsed.y.toLocaleString("id-ID") + " tiket"
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: fontInter } },
            y: {
                beginAtZero: true,
                grid: { color: "rgba(11,42,74,0.08)" },
                ticks: { font: fontInter, callback: (v) => v.toLocaleString("id-ID") }
            }
        }
    }
});
</script>

<?php include 'FooterHalaman.php'; ?>