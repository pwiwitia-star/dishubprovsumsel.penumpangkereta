<?php
require 'FungsiKereta.php';

$union_sql = buat_union($conn, $tabel_per_tahun, 'semua');
$tanggal_awal = $tanggal_akhir = null;
$total_baris = 0;

$q = mysqli_query($conn, "
    SELECT MIN(tanggal) AS awal, MAX(tanggal) AS akhir, COUNT(*) AS n
    FROM ({$union_sql}) AS gabungan
");
if ($q && ($r = mysqli_fetch_assoc($q))) {
    $tanggal_awal  = $r['awal'];
    $tanggal_akhir = $r['akhir'];
    $total_baris   = (int) $r['n'];
}

$pesan_survei = $_GET['survei'] ?? '';


$daftar_stasiun = [
    'Kertapati', 'Prabumulih', 'Muara Enim', 'Lahat', 'Tebing Tinggi',
    'Lubuklinggau', 'Baturaja', 'Martapura', 'Kayu Agung', 'Indralaya','Tanjung Karang'
];

$aspek_penilaian = [
    'kebersihan' => 'Kebersihan kereta',
    'kenyamanan' => 'Kenyamanan tempat duduk',
    'ketepatan'  => 'Ketepatan waktu',
    'pelayanan'  => 'Pelayanan petugas',
    'fasilitas'  => 'Fasilitas (AC, toilet, dll)',
    'harga'      => 'Harga tiket',
];

$halaman_aktif    = 'informasi';
$judul_halaman    = 'Informasi Data';
$label_banner     = 'Tentang Dashboard';
$deskripsi_banner = 'Penjelasan sumber data, arti setiap istilah, dan fungsi tiap halaman.';
$pakai_chart      = false;

include 'HeaderHalaman.php';
?>

<section class="container">

    <div class="section-title">
        <span>Sumber &amp; Cakupan</span>
        <h2>Tentang Data Ini</h2>
    </div>

    <p class="teks-panjang">
        Angkutan Penumpang Divre III Palembang
Angkutan penumpang kereta api merupakan salah satu layanan transportasi yang mendukung mobilitas masyarakat di wilayah Sumatera Selatan dan sekitarnya. 
Layanan ini menyediakan sarana perjalanan dengan memperhatikan kenyamanan, keamanan, dan ketepatan waktu.
        <?php if ($tanggal_awal && $tanggal_akhir): ?>
            Saat ini tersimpan <strong><?= angka($total_baris) ?> baris data</strong>,
            mulai <?= date('d-m-Y', strtotime($tanggal_awal)) ?>
            sampai <?= date('d-m-Y', strtotime($tanggal_akhir)) ?>.
        <?php endif; ?>
        Tiap tahun disimpan dalam tabel terpisah, lalu digabung saat ditampilkan.
    </p>

    <div class="section-title">
        <span>Istilah</span>
        <h2>Arti Setiap Kolom</h2>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <h3>Tiket Terjual</h3>
            <p>Jumlah penumpang yang membeli tiket pada satu hari pencatatan.</p>
        </div>
        <div class="info-item">
            <h3>Kapasitas (TD)</h3>
            <p>Jumlah tempat duduk yang tersedia pada hari yang sama.</p>
        </div>
        <div class="info-item">
            <h3>Load Factor</h3>
            <p>Tingkat keterisian kereta, yaitu perbandingan tiket terjual dengan kapasitas tempat duduk, dalam persen. Makin tinggi angkanya, makin penuh kereta.</p>
        </div>
    </div>

    <div class="section-title">
        <span>Panduan</span>
        <h2>Fungsi Tiap Halaman</h2>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <h3>Dashboard</h3>
            <p>Ringkasan cepat: total tiket, kapasitas, rata-rata load factor, dan grafik bulanan. Bisa difilter per tahun. <a href="index.php">Buka Dashboard</a></p>
        </div>
        <div class="info-item">
            <h3>Data</h3>
            <p>Tabel catatan harian lengkap dengan filter tahun dan bulan. <a href="data.php">Buka Data</a></p>
        </div>
        <div class="info-item">
            <h3>Pegawai</h3>
            <p>Struktur organisasi bidang: kepala bidang, kepala seksi, dan staf. <a href="pegawai.php">Buka Pegawai</a></p>
        </div>
        <div class="info-item">
            <h3>Statistik</h3>
            <p>Perbandingan antar tahun, load factor, dan 10 hari tersibuk. <a href="statistik.php">Buka Statistik</a></p>
        </div>
        <div class="info-item">
            <h3>Galeri</h3>
            <p>Foto dokumentasi kereta api dan Dinas Perhubungan. <a href="index.php#galeri">Buka Galeri</a></p>
        </div>
    </div>

    <!-- SURVEI PENUMPANG -->
    <div class="section-title" id="survei">
        <span>Suara Penumpang</span>
        <h2>Survei Kepuasan Penumpang</h2>
    </div>

    <?php if ($pesan_survei === 'sukses'): ?>
        <p class="survei-pesan sukses">Terima kasih, survei Anda sudah kami terima.</p>
    <?php elseif ($pesan_survei === 'kosong'): ?>
        <p class="survei-pesan gagal">Mohon lengkapi semua penilaian pada bagian 2 sebelum mengirim survei.</p>
    <?php elseif ($pesan_survei === 'gagal'): ?>
        <p class="survei-pesan gagal">Maaf, survei gagal dikirim. Coba lagi beberapa saat lagi.</p>
    <?php endif; ?>

    <form class="survei-card" action="simpan_survei.php" method="post" id="formSurvei">

        <div class="survei-intro">
            <div class="survei-intro-ikon"><i class="bi bi-person-fill"></i></div>
            <div>
                <strong>Terima kasih telah menggunakan jasa Kereta Api Sumatera Selatan.</strong>
                <p>Mohon luangkan waktu untuk mengisi survei ini. Jawaban Anda akan sangat membantu kami dalam meningkatkan kualitas layanan.</p>
            </div>
        </div>

        <div class="survei-grid">

            <!-- Kolom kiri: Bagian 1 & 3 -->
            <div class="survei-kolom">

                <h3 class="survei-subjudul"><span class="survei-nomor">1</span> <i class="bi bi-person"></i> Data Responden</h3>

                <div class="survei-baris">
                    <label for="nama">Nama (opsional)</label>
                    <input type="text" id="nama" name="nama" maxlength="100" placeholder="Masukkan nama Anda">
                </div>

                <div class="survei-baris">
                    <label for="usia">Usia</label>
                    <select id="usia" name="usia">
                        <option value="">Pilih usia</option>
                        <option value="<20">Di bawah 20 tahun</option>
                        <option value="20-29">20 - 29 tahun</option>
                        <option value="30-39">30 - 39 tahun</option>
                        <option value="40-49">40 - 49 tahun</option>
                        <option value="50+">50 tahun ke atas</option>
                    </select>
                </div>

                <div class="survei-baris">
                    <label>Jenis Kelamin</label>
                    <div class="survei-radio-baris">
                        <label class="survei-radio"><input type="radio" name="jenis_kelamin" value="Laki-laki"> Laki-laki</label>
                        <label class="survei-radio"><input type="radio" name="jenis_kelamin" value="Perempuan"> Perempuan</label>
                    </div>
                </div>

                <div class="survei-baris">
                    <label for="tujuan">Tujuan Perjalanan</label>
                    <select id="tujuan" name="tujuan_perjalanan">
                        <option value="">Pilih tujuan perjalanan</option>
                        <option value="Wisata">Wisata</option>
                        <option value="Bisnis/Dinas">Bisnis / Dinas</option>
                        <option value="Pulang Kampung">Pulang Kampung</option>
                        <option value="Sekolah/Kuliah">Sekolah / Kuliah</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <h3 class="survei-subjudul"><span class="survei-nomor">3</span> <i class="bi bi-train-front"></i> Informasi Perjalanan</h3>

                <div class="survei-baris">
                    <label for="tanggal_perjalanan">Tanggal Perjalanan</label>
                    <input type="date" id="tanggal_perjalanan" name="tanggal_perjalanan">
                </div>

                <div class="survei-baris">
                    <label for="stasiun_asal">Stasiun Keberangkatan</label>
                    <select id="stasiun_asal" name="stasiun_keberangkatan">
                        <option value="">Pilih stasiun</option>
                        <?php foreach ($daftar_stasiun as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="survei-baris">
                    <label for="stasiun_tujuan_input">Stasiun Tujuan</label>
                    <select id="stasiun_tujuan_input" name="stasiun_tujuan">
                        <option value="">Pilih stasiun</option>
                        <?php foreach ($daftar_stasiun as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="survei-baris">
                    <label>Kelas Kereta</label>
                    <div class="survei-radio-baris">
                        <label class="survei-radio"><input type="radio" name="kelas_kereta" value="Ekonomi"> Ekonomi</label>
                        <label class="survei-radio"><input type="radio" name="kelas_kereta" value="Bisnis"> Bisnis</label>
                        <label class="survei-radio"><input type="radio" name="kelas_kereta" value="Eksekutif"> Eksekutif</label>
                    </div>
                </div>

            </div>

            <!-- Kolom kanan: Bagian 2 & 4 -->
            <div class="survei-kolom">

                <h3 class="survei-subjudul"><span class="survei-nomor">2</span> <i class="bi bi-star-fill"></i> Penilaian Layanan</h3>
                <p class="survei-ket">Berikan penilaian Anda terhadap pelayanan kereta api berikut (1 = Sangat Tidak Baik, 5 = Sangat Baik).</p>

                <div class="survei-tabel-nilai">
                    <table>
                        <thead>
                            <tr>
                                <th>Aspek Penilaian</th>
                                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aspek_penilaian as $kunci => $label): ?>
                                <tr>
                                    <td><?= htmlspecialchars($label) ?></td>
                                    <?php for ($n = 1; $n <= 5; $n++): ?>
                                        <td><input type="radio" name="nilai_<?= $kunci ?>" value="<?= $n ?>" required></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="survei-subjudul"><span class="survei-nomor">4</span> <i class="bi bi-chat-dots"></i> Saran dan Masukan</h3>
                <p class="survei-ket">Jika ada saran atau masukan, silakan tulis di bawah ini.</p>

                <div class="survei-baris">
                    <textarea id="saran" name="saran" rows="6" maxlength="500" placeholder="Tulis saran atau masukan Anda di sini..."></textarea>
                    <span class="survei-counter"><span id="jumlahKarakter">0</span>/500</span>
                </div>

            </div>

        </div>

        <button type="submit" class="btn-survei btn-survei-lebar"><i class="bi bi-send"></i> Kirim Survei</button>

    </form>

</section>

<?php
$script_tambahan = <<<'HTML'
<script>
const kolomSaran = document.getElementById('saran');
const jumlahKarakter = document.getElementById('jumlahKarakter');
if (kolomSaran && jumlahKarakter) {
    kolomSaran.addEventListener('input', () => {
        jumlahKarakter.textContent = kolomSaran.value.length;
    });
}
</script>
HTML;

include 'FooterHalaman.php';
?>