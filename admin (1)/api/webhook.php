<?php
include_once '../includes/database.php';

$database = new Database();
$db = $database->getConnection();

// Terima data webhook dari provider
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data && isset($data['trxid'])) {
    // Cari order berdasarkan reference ID
    $query = "SELECT o.*, r.webhook_url 
              FROM orders o 
              JOIN resellers r ON o.reseller_id = r.id 
              WHERE o.api_reference = :trxid";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':trid', $data['trxid']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update status order
        $status = ($data['status_code'] == '0') ? 'success' : 'failed';
        $updateQuery = "UPDATE orders SET status = :status, api_response = :response WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':response', json_encode($data));
        $updateStmt->bindParam(':id', $order['id']);
        $updateStmt->execute();
        
        // Jika order gagal, refund saldo
        if ($status == 'failed') {
            $refundQuery = "UPDATE resellers SET balance = balance + :amount WHERE id = :id";
            $refundStmt = $db->prepare($refundQuery);
            $refundStmt->bindParam(':amount', $order['price']);
            $refundStmt->bindParam(':id', $order['reseller_id']);
            $refundStmt->execute();
            
            // Catat transaksi refund
            $transactionQuery = "INSERT INTO balance_transactions 
                                (reseller_id, type, amount, description, reference_id, status) 
                                VALUES (:reseller_id, 'refund', :amount, :description, :reference_id, 'completed')";
            $transactionStmt = $db->prepare($transactionQuery);
            $transactionStmt->bindParam(':reseller_id', $order['reseller_id']);
            $transactionStmt->bindParam(':amount', $order['price']);
            $transactionStmt->bindParam(':description', "Refund untuk order gagal: " . $order['product_code']);
            $transactionStmt->bindParam(':reference_id', $data['trxid']);
            $transactionStmt->execute();
        }
        
        // Kirim webhook ke reseller
        if (!empty($order['webhook_url'])) {
            $webhookData = [
                'event' => 'order_status_update',
                'trx_id' => $data['trxid'],
                'product' => $order['product_code'],
                'destination' => $order['destination'],
                'status' => $status,
                'message' => $data['msg'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $ch = curl_init($order['webhook_url']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);
        }
        
        http_response_code(200);
        echo "Webhook processed successfully";
    } else {
        http_response_code(404);
        echo "Order not found";
    }
} else {
    http_response_code(400);
    echo "Invalid webhook data";
}
?>