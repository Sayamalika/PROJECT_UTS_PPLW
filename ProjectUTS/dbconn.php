<?php

$host = "localhost";
$port = "5432";
$dbname = "persewaan_mobil_pak_rama";
$user = "posgres";
$password = "codename0";

$dbconn = pg_connect("host = $host port = $port dbname = $dbname user = $user password = $password");

if (!$dbconn){
    die ("koneksi gagal dibuat ke database");
}