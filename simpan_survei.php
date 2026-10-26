<?php
require 'FungsiKereta.php';   // menyediakan $conn dari koneksi_kereta.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: informasi.php#survei');
    exit;
}

function amankan($conn, $nilai): string
{
    $nilai = trim((string) ($nilai ?? ''));
    return $nilai === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $nilai) . "'";
}

$nama          = amankan($conn, $_POST['nama'] ?? '');
$usia          = amankan($conn, $_POST['usia'] ?? '');
$jenis_kelamin = amankan($conn, $_POST['jenis_kelamin'] ?? '');
$tujuan        = amankan($conn, $_POST['tujuan_perjalanan'] ?? '');

/* Informasi Perjalanan */
$tanggal_mentah = trim($_POST['tanggal_perjalanan'] ?? '');
$tanggal_valid  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mentah) === 1;
$tanggal        = $tanggal_valid ? amankan($conn, $tanggal_mentah) : 'NULL';

$stasiun_asal = amankan($conn, $_POST['stasiun_keberangkatan'] ?? '');
$stasiun_tuju = amankan($conn, $_POST['stasiun_tujuan'] ?? '');
$kelas        = amankan($conn, $_POST['kelas_kereta'] ?? '');

/* Saran */
$saran = amankan($conn, $_POST['saran'] ?? '');

/* Penilaian, wajib lengkap semua aspek (angka 1-5) */
$aspek = ['kebersihan', 'kenyamanan', 'ketepatan', 'pelayanan', 'fasilitas', 'harga'];
$nilai = [];
foreach ($aspek as $a) {
    $v = (int) ($_POST['nilai_' . $a] ?? 0);
    $nilai[$a] = ($v >= 1 && $v <= 5) ? $v : null;
}
if (in_array(null, $nilai, true)) {
    header('Location: informasi.php?survei=kosong#survei');
    exit;
}

$sql = "INSERT INTO survei_penumpang
            (nama, usia, jenis_kelamin, tujuan_perjalanan,
             tanggal_perjalanan, stasiun_keberangkatan, stasiun_tujuan, kelas_kereta,
             nilai_kebersihan, nilai_kenyamanan, nilai_ketepatan,
             nilai_pelayanan, nilai_fasilitas, nilai_harga, saran)
        VALUES
            ({$nama}, {$usia}, {$jenis_kelamin}, {$tujuan},
             {$tanggal}, {$stasiun_asal}, {$stasiun_tuju}, {$kelas},
             {$nilai['kebersihan']}, {$nilai['kenyamanan']}, {$nilai['ketepatan']},
             {$nilai['pelayanan']}, {$nilai['fasilitas']}, {$nilai['harga']}, {$saran})";

$berhasil = mysqli_query($conn, $sql);

header('Location: informasi.php?survei=' . ($berhasil ? 'sukses' : 'gagal') . '#survei');
exit;