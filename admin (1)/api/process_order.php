<?php
header('Content-Type: application/json');
require_once '../includes/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);
$reseller_id = $input['reseller_id'] ?? '';
$product_code = $input['product_code'] ?? '';
$destination = $input['destination'] ?? '';

if (empty($reseller_id) || empty($product_code) || empty($destination)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Validasi saldo reseller
$query = "SELECT balance FROM resellers WHERE id = :id AND status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $reseller_id);
$stmt->execute();
$reseller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reseller) {
    http_response_code(404);
    echo json_encode(['error' => 'Reseller not found or inactive']);
    exit;
}

// Dapatkan harga produk (harus disesuaikan dengan database produk)
$product_price = 10000; // Contoh harga, harus disesuaikan

if ($reseller['balance'] < $product_price) {
    http_response_code(400);
    echo json_encode(['error' => 'Insufficient balance']);
    exit;
}

// Siapkan data untuk API provider
$requestData = [
    'req' => 'topup',
    'kodereseller' => 'NF00087',
    'produk' => $product_code,
    'msisdn' => $destination,
    'reffid' => uniqid('TRX-'),
    'time' => date('His'),
    'pin' => '999105',
    'password' => 'Rudal123'
];

// Panggil API provider
$apiResult = callProviderAPI($requestData);

// Simpan order ke database
$query = "INSERT INTO orders 
          (reseller_id, product_code, destination, price, status, api_reference, api_response) 
          VALUES (:reseller_id, :product_code, :destination, :price, :status, :api_reference, :api_response)";

$stmt = $db->prepare($query);
$status = (isset($apiResult['status_code']) && $apiResult['status_code'] == '0') ? 'success' : 'failed';

$stmt->bindParam(':reseller_id', $reseller_id);
$stmt->bindParam(':product_code', $product_code);
$stmt->bindParam(':destination', $destination);
$stmt->bindParam(':price', $product_price);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':api_reference', $apiResult['trxid'] ?? '');
$stmt->bindParam(':api_response', json_encode($apiResult));
$stmt->execute();

// Kurangi saldo jika order berhasil
if ($status == 'success') {
    $updateQuery = "UPDATE resellers SET balance = balance - :price WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':price', $product_price);
    $updateStmt->bindParam(':id', $reseller_id);
    $updateStmt->execute();
}

echo json_encode($apiResult);
?>