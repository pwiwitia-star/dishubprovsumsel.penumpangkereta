<?php
include_once 'koneksi_kereta.php';

$tabel_per_tahun = [
    '2024' => 'tahun_2024',
    '2025' => 'tahun_2025',
    '2026' => 'tahun_2026',
];

$tahun_tersedia = array_map('strval', array_keys($tabel_per_tahun));

function ambil_tahun_dipilih(array $tahun_tersedia): string
{
    $tahun = $_GET['tahun'] ?? 'semua';
    return ($tahun === 'semua' || in_array($tahun, $tahun_tersedia, true)) ? $tahun : 'semua';
}

function buat_union(mysqli $conn, array $tabel_per_tahun, string $tahun_dipilih): string
{
    $aktif = $tahun_dipilih === 'semua'
        ? $tabel_per_tahun
        : [$tahun_dipilih => $tabel_per_tahun[$tahun_dipilih]];

    $bagian = [];
    foreach ($aktif as $thn => $nama_tabel) {
        $nama_tabel = mysqli_real_escape_string($conn, $nama_tabel);
        $bagian[] = "SELECT '{$thn}' AS tahun_data, tanggal, tiket_terjual, kapasitas_td, load_factor
                     FROM `{$nama_tabel}`";
    }
    return implode(' UNION ALL ', $bagian);
}

function angka($nilai, int $desimal = 0): string
{
    return number_format((float) $nilai, $desimal, ',', '.');
}