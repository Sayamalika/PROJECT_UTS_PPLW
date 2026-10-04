<?php

require_once 'dbconn.php';


if (isset($conn) && $conn) {
    echo "<br>Versi MySQL server: " . mysqli_get_server_info($conn);
} else {
    echo "<br>Koneksi belum terbentuk.";
}
?>