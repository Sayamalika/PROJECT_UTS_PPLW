<?php

session_start();

// Autoload: memuat file class secara otomatis ketika class pertama kali digunakan,
// sehingga tidak diperlukan lagi daftar include_once yang panjang di setiap halaman.
spl_autoload_register(function (string $nama_class) {
 $file = __DIR__."/model/" . strtolower($nama_class) . ".php";
 if (file_exists($file)) {
 include_once($file);
 return;
 }

 $file_demo = __DIR__ . "/demo/" . strtolower($nama_class) . ".php";
 if (file_exists($file_demo)) {
	include_once($file_demo);
 }
}); 
