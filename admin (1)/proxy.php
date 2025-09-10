<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dbHost = 'localhost';
$dbUser = 'rudsstore_stok';
$dbPass = 'TY_?sj![_aj8C@[f';
$dbName = 'rudsstore_stok';

$apiUrl = 'https://panel.khfy-store.com/api/api-xl-v7/cek_stock_akrab';

// Inisialisasi koneksi database
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

// 1. Ambil data stok dari API eksternal
$apiResponse = [];
$apiStockData = [];
$messageStockData = [];
$context = stream_context_create(['http' => ['timeout' => 15]]);
$jsonData = @file_get_contents($apiUrl, false, $context);

if ($jsonData === FALSE) {
    error_log('Gagal terhubung ke API: ' . $apiUrl);
} else {
    $apiResponse = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Ambil dari objek 'data' jika ada
        if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {
            $apiStockData = $apiResponse['data'];
        }
        
        // Ambil dari string 'message' sebagai fallback
        if (isset($apiResponse['message']) && !empty($apiResponse['message'])) {
            preg_match_all('/\((.*?)\)\s*(.*?)\s*:\s*(\d+)/', $apiResponse['message'], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $product_key = $match[1];
                $quantity = (int)$match[3];
                $messageStockData[$product_key] = $quantity;
            }
        }
    } else {
        error_log('Respon API tidak valid: ' . $jsonData);
    }
}

// 2. Ambil data stok manual dari database Anda
$manualStockData = [];
$sqlManual = "SELECT product_key, manual_quantity FROM manual_stock";
$resultManual = $conn->query($sqlManual);
if ($resultManual) {
    while ($row = $resultManual->fetch_assoc()) {
        $manualStockData[$row['product_key']] = (int)$row['manual_quantity'];
    }
} else {
    error_log('Error mengambil stok manual dari database: ' . $conn->error);
}

// 3. Ambil data detail produk yang tidak tersembunyi
$productDetails = [];
$sqlDetails = "SELECT product_key, product_name, harga, kuota, noted FROM product_details WHERE is_hidden = 0";
$resultDetails = $conn->query($sqlDetails);
if ($resultDetails) {
    while ($row = $resultDetails->fetch_assoc()) {
        $productDetails[$row['product_key']] = [
            'product_name' => $row['product_name'],
            'harga' => $row['harga'],
            'kuota' => $row['kuota'],
            'noted' => $row['noted']
        ];
    }
} else {
    error_log('Error mengambil detail produk dari database: ' . $conn->error);
}

$conn->close();

// 4. Gabungkan data stok dari API (objek dan pesan) dan stok manual
$combinedStock = array_merge($apiStockData, $messageStockData);
foreach ($manualStockData as $key => $quantity) {
    if (isset($combinedStock[$key])) {
        $combinedStock[$key] += $quantity;
    } else {
        $combinedStock[$key] = $quantity;
    }
}

// Kembalikan semua data yang diperlukan
echo json_encode([
    'data' => $combinedStock,
    'productDetails' => $productDetails
]);
?>