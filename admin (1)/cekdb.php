<?php
// Ambil config
require_once __DIR__ . '/config.php';

// Koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("<h3 style='color:red'>Koneksi Gagal: " . $conn->connect_error . "</h3>");
} else {
    echo "<h3 style='color:green'>Koneksi ke database berhasil!</h3>";
}

// Coba query test ke tabel users
$query = "SELECT * FROM users LIMIT 5";
$result = $conn->query($query);

if (!$result) {
    die("<p style='color:red'>Query error: " . $conn->error . "</p>");
} else {
    echo "<p style='color:green'>Query berhasil: $query</p>";
    echo "<b>Hasil data:</b><br><pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

// Tutup koneksi
$conn->close();
?>
