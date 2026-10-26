<?php
require 'DataPegawai.php';
function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama));
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil !== '' ? $hasil : '?';
}

function kartu_pegawai(array $p, string $kelas, string $label = ''): void
{
    $path_foto = !empty($p['foto']) ? 'img/pegawai/' . $p['foto'] : '';
    $ada_foto  = $path_foto !== '' && file_exists($path_foto);
    $nip       = $p['nip'] ?? '';
    $email     = $p['email'] ?? '';
    $telepon   = $p['telepon'] ?? '';
    ?>
    <div class="org-card <?= $kelas ?>">
        <div class="pegawai-foto">
            <?php if ($ada_foto): ?>
                <img src="<?= htmlspecialchars($path_foto) ?>" alt="Foto <?= htmlspecialchars($p['nama']) ?>">
            <?php else: ?>
                <span class="pegawai-inisial"><?= htmlspecialchars(inisial($p['nama'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="org-info">
            <?php if ($label !== ''): ?>
                <span class="pegawai-bidang"><?= htmlspecialchars($label) ?></span>
            <?php endif; ?>
            <h3><?= htmlspecialchars($p['nama']) ?></h3>
            <p class="pegawai-jabatan"><?= htmlspecialchars($p['jabatan']) ?></p>
            <?php if ($nip !== '' || $email !== '' || $telepon !== ''): ?>
                <ul class="pegawai-kontak">
                    <?php if ($nip !== ''): ?>
                        <li><i class="bi bi-person-vcard"></i> NIP <?= htmlspecialchars($nip) ?></li>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <li><i class="bi bi-envelope"></i> <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></li>
                    <?php endif; ?>
                    <?php if ($telepon !== ''): ?>
                        <li><i class="bi bi-telephone"></i> <?= htmlspecialchars($telepon) ?></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

$halaman_aktif    = 'pegawai';
$judul_halaman    = 'Struktur Pegawai';
$label_banner     = 'Struktur Organisasi';
$deskripsi_banner = 'Susunan pegawai ' . $nama_bidang . ': kepala bidang, kepala seksi, dan staf di tiap seksi.';
$pakai_chart      = false;

include 'HeaderHalaman.php';
?>

<section class="container">

    <div class="section-title">
        <span>Dinas Perhubungan Provinsi Sumatera Selatan</span>
        <h2><?= htmlspecialchars($nama_bidang) ?></h2>
    </div>

    <div class="org">

        <!-- Tingkat 1: Kepala Bidang -->
        <div class="org-top">
            <?php kartu_pegawai($kepala_bidang, 'org-kabid'); ?>
        </div>

        <!-- Tingkat 2 & 3: Kasi dan stafnya -->
        <ul class="org-seksi">
            <?php foreach ($daftar_seksi as $seksi): ?>
                <li class="org-col">
                    <?php kartu_pegawai($seksi['kasi'], 'org-kasi', $seksi['seksi']); ?>

                    <?php if (!empty($seksi['staf'])): ?>
                        <ul class="org-staf">
                            <?php foreach ($seksi['staf'] as $staf): ?>
                                <li><?php kartu_pegawai($staf, 'org-staf-card'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

    </div>

</section>

<?php include 'FooterHalaman.php'; ?>