<?php

require_once "dbconn.php";

echo "Koneksi berhasil<br>";
echo "Versi server: " . pg_version($dbconn)['server'];
?>