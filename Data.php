<?php
require 'FungsiKereta.php';

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$tahun_dipilih = ambil_tahun_dipilih($tahun_tersedia);
$bulan_dipilih = (int) ($_GET['bulan'] ?? 0);
if ($bulan_dipilih < 1 || $bulan_dipilih > 12) {
    $bulan_dipilih = 0;
}
$halaman = max(1, (int) ($_GET['hal'] ?? 1));
$per_halaman = 20;

$union_sql = buat_union($conn, $tabel_per_tahun, $tahun_dipilih);
$where = $bulan_dipilih ? "WHERE MONTH(tanggal) = {$bulan_dipilih}" : '';

/* ---- hitung total baris & halaman ---- */
$total_baris = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) AS n FROM ({$union_sql}) AS gabungan {$where}");
if ($q && ($r = mysqli_fetch_assoc($q))) {
    $total_baris = (int) $r['n'];
}
$total_halaman = max(1, (int) ceil($total_baris / $per_halaman));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $per_halaman;

/* ---- ambil data halaman ini ---- */
$hasil = mysqli_query($conn, "
    SELECT tahun_data, tanggal, tiket_terjual, kapasitas_td, load_factor
    FROM ({$union_sql}) AS gabungan
    {$where}
    ORDER BY tanggal DESC
    LIMIT {$per_halaman} OFFSET {$offset}
");

/* ---- pembuat URL ---- */
function url_data(string $tahun, int $bulan, int $hal): string
{
    $param = ['tahun' => $tahun];
    if ($bulan) $param['bulan'] = $bulan;
    if ($hal > 1) $param['hal'] = $hal;
    return 'data.php?' . http_build_query($param);
}

/* ---- pengaturan header ---- */
$halaman_aktif    = 'data';
$judul_halaman    = 'Data Harian';
$label_banner     = 'Tabel Data';
$deskripsi_banner = 'Catatan harian tiket terjual, kapasitas tempat duduk, dan load factor. Pilih tahun atau bulan untuk mempersempit tabel.';
$pakai_chart      = false;

include 'HeaderHalaman.php';
?>

<section class="container">

    <div class="year-tabs" role="tablist" aria-label="Pilih tahun data">
        <a href="<?= url_data('semua', $bulan_dipilih, 1) ?>" class="year-tab <?= $tahun_dipilih === 'semua' ? 'active' : '' ?>">Semua Tahun</a>
        <?php foreach ($tahun_tersedia as $thn): ?>
            <a href="<?= url_data($thn, $bulan_dipilih, 1) ?>" class="year-tab <?= $tahun_dipilih === $thn ? 'active' : '' ?>"><?= htmlspecialchars($thn) ?></a>
        <?php endforeach; ?>
    </div>

    <form class="filter-bar" method="get" action="data.php">
        <input type="hidden" name="tahun" value="<?= htmlspecialchars($tahun_dipilih) ?>">
        <label for="bulan">Bulan</label>
        <select name="bulan" id="bulan">
            <option value="0">Semua bulan</option>
            <?php foreach ($nama_bulan as $no => $nama): ?>
                <option value="<?= $no ?>" <?= $bulan_dipilih === $no ? 'selected' : '' ?>><?= $nama ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Terapkan</button>
        <?php if ($bulan_dipilih): ?>
            <a class="reset" href="<?= url_data($tahun_dipilih, 0, 1) ?>">Hapus filter bulan</a>
        <?php endif; ?>
    </form>

    <p class="hasil-info">
        Menampilkan <?= angka($total_baris) ?> baris data
        <?= $tahun_dipilih === 'semua' ? '(semua tahun)' : '(tahun ' . htmlspecialchars($tahun_dipilih) . ')' ?>
        <?= $bulan_dipilih ? ', bulan ' . $nama_bulan[$bulan_dipilih] : '' ?>
        &mdash; halaman <?= $halaman ?> dari <?= $total_halaman ?>.
    </p>

    <div class="table-box">
        <table class="tabel-data">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="angka">Tiket Terjual</th>
                    <th class="angka">Kapasitas (TD)</th>
                    <th class="angka">Load Factor</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($hasil && mysqli_num_rows($hasil) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
                    <?php $lf = (float) $row['load_factor']; ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td class="angka"><?= angka($row['tiket_terjual']) ?></td>
                        <td class="angka"><?= angka($row['kapasitas_td']) ?></td>
                        <td class="angka">
                            <?= angka($lf, 2) ?>%
                            <span class="lf-bar" aria-hidden="true"><i style="width: <?= max(0, min(100, $lf)) ?>%"></i></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" class="kosong">Belum ada data untuk pilihan ini. Coba pilih tahun atau bulan lain.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_halaman > 1): ?>
    <nav class="pagination" aria-label="Halaman data">
        <?php if ($halaman > 1): ?>
            <a href="<?= url_data($tahun_dipilih, $bulan_dipilih, $halaman - 1) ?>">&laquo; Sebelumnya</a>
        <?php else: ?>
            <span class="mati">&laquo; Sebelumnya</span>
        <?php endif; ?>

        <?php for ($i = max(1, $halaman - 2); $i <= min($total_halaman, $halaman + 2); $i++): ?>
            <?php if ($i === $halaman): ?>
                <span class="aktif"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= url_data($tahun_dipilih, $bulan_dipilih, $i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($halaman < $total_halaman): ?>
            <a href="<?= url_data($tahun_dipilih, $bulan_dipilih, $halaman + 1) ?>">Berikutnya &raquo;</a>
        <?php else: ?>
            <span class="mati">Berikutnya &raquo;</span>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

</section>

<?php include 'FooterHalaman.php'; ?>