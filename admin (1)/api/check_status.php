<?php
header('Content-Type: application/json');
require_once '../includes/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);
$trx_id = $input['trx_id'] ?? '';

if (empty($trx_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaction ID is required']);
    exit;
}

// Siapkan data untuk API provider
$requestData = [
    'req' => 'cmd',
    'kodereseller' => 'NF00087',
    'perintah' => 'CEK.' . $trx_id,
    'time' => date('His'),
    'pin' => '999105',
    'password' => 'Rudal123'
];

// Panggil API provider
$apiResult = callProviderAPI($requestData);

// Update status order di database jika diperlukan
if (isset($apiResult['status_code'])) {
    $status = ($apiResult['status_code'] == '0') ? 'success' : 'failed';
    
    $query = "UPDATE orders SET status = :status, api_response = :response 
              WHERE api_reference = :trx_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':response', json_encode($apiResult));
    $stmt->bindParam(':trx_id', $trx_id);
    $stmt->execute();
}

echo json_encode($apiResult);
?>