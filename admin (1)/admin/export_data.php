<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Periksa apakah user adalah admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header('Location: ../admin.php');
    exit();
}

// Fungsi untuk ekspor data ke CSV
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Data CSV
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

// Ekspor data berdasarkan jenis
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $filters = $_GET;
    unset($filters['export']);
    
    switch ($exportType) {
        case 'resellers':
            $data = getResellers($db, $filters['status'] ?? null);
            $filename = 'resellers_' . date('Y-m-d') . '.csv';
            exportToCSV($data, $filename);
            break;
            
        case 'orders':
            $data = getOrders($db, $filters);
            // Format data untuk ekspor
            $exportData = [];
            foreach ($data as $order) {
                $exportData[] = [
                    'ID' => $order['id'],
                    'Tanggal' => $order['created_at'],
                    'Reseller' => $order['username'],
                    'Produk' => $order['product_code'],
                    'Tujuan' => $order['destination'],
                    'Harga' => $order['price'],
                    'Status' => $order['status'],
                    'Referensi' => $order['api_reference']
                ];
            }
            $filename = 'orders_' . date('Y-m-d') . '.csv';
            exportToCSV($exportData, $filename);
            break;
            
        case 'transactions':
            $data = getTransactions($db, $filters);
            // Format data untuk ekspor
            $exportData = [];
            foreach ($data as $transaction) {
                $exportData[] = [
                    'ID' => $transaction['id'],
                    'Tanggal' => $transaction['created_at'],
                    'Reseller' => $transaction['username'],
                    'Jenis' => $transaction['type'],
                    'Jumlah' => $transaction['amount'],
                    'Deskripsi' => $transaction['description'],
                    'Status' => $transaction['status'],
                    'Referensi' => $transaction['reference_id']
                ];
            }
            $filename = 'transactions_' . date('Y-m-d') . '.csv';
            exportToCSV($exportData, $filename);
            break;
            
        default:
            header('Location: admin_dashboard.php');
            exit();
    }
}
?>