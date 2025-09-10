<?php
include_once '../includes/database.php';

$database = new Database();
$db = $database->getConnection();

// Periksa apakah user adalah admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    http_response_code(403);
    echo 'Akses ditolak';
    exit();
}

if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    $query = "SELECT o.*, r.username, r.full_name, r.email, r.phone 
              FROM orders o 
              JOIN resellers r ON o.reseller_id = r.id 
              WHERE o.id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $api_response = json_decode($order['api_response'], true);
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h6>Informasi Order</h6>';
        echo '<table class="table table-sm">';
        echo '<tr><th>ID Order:</th><td>' . $order['id'] . '</td></tr>';
        echo '<tr><th>Tanggal:</th><td>' . date('d M Y H:i:s', strtotime($order['created_at'])) . '</td></tr>';
        echo '<tr><th>Reseller:</th><td>' . htmlspecialchars($order['full_name']) . ' (' . $order['username'] . ')</td></tr>';
        echo '<tr><th>Produk:</th><td>' . $order['product_code'] . '</td></tr>';
        echo '<tr><th>Tujuan:</th><td>' . $order['destination'] . '</td></tr>';
        echo '<tr><th>Harga:</th><td>Rp ' . number_format($order['price'], 0, ',', '.') . '</td></tr>';
        echo '<tr><th>Status:</th><td>';
        if ($order['status'] == 'success') {
            echo '<span class="badge bg-success">Success</span>';
        } elseif ($order['status'] == 'failed') {
            echo '<span class="badge bg-danger">Failed</span>';
        } else {
            echo '<span class="badge bg-warning">Pending</span>';
        }
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<h6>Informasi Reseller</h6>';
        echo '<table class="table table-sm">';
        echo '<tr><th>Nama:</th><td>' . htmlspecialchars($order['full_name']) . '</td></tr>';
        echo '<tr><th>Username:</th><td>' . $order['username'] . '</td></tr>';
        echo '<tr><th>Email:</th><td>' . htmlspecialchars($order['email']) . '</td></tr>';
        echo '<tr><th>Telepon:</th><td>' . htmlspecialchars($order['phone']) . '</td></tr>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        if ($api_response) {
            echo '<div class="mt-3">';
            echo '<h6>Response API</h6>';
            echo '<pre class="bg-light p-3">' . json_encode($api_response, JSON_PRETTY_PRINT) . '</pre>';
            echo '</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Order tidak ditemukan.</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID Order tidak valid.</div>';
}
?>