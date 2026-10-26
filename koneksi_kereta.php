<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "db_kereta_api_sumsel";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>

 