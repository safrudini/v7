<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';
include_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: reseller_login.php');
    exit();
}

// Ambil data produk dari API
$apiUrl = 'https://panel.khfy-store.com/api/api-xl-v7/cek_stock_akrab';
$context = stream_context_create(['http' => ['timeout' => 15]]);
$jsonData = @file_get_contents($apiUrl, false, $context);
$apiResponse = json_decode($jsonData, true);
$stockData = [];

if (isset($apiResponse['data']) && !empty($apiResponse['data'])) {
    $stockData = $apiResponse['data'];
}

// Proses order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $product_code = $_POST['product_code'];
    $destination = $_POST['destination'];
    
    // Validasi saldo
    $product_price = 0; // Harus disesuaikan dengan harga produk
    if ($_SESSION['reseller_balance'] < $product_price) {
        $error = "Saldo tidak mencukupi untuk melakukan order ini.";
    } else {
        // Siapkan data untuk API provider
        $requestData = [
            'req' => 'topup',
            'kodereseller' => 'NF00087', // Kode reseller utama
            'produk' => $product_code,
            'msisdn' => $destination,
            'reffid' => generateTrxId(),
            'time' => getCurrentTime(),
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
        $stmt->bindParam(':reseller_id', $_SESSION['reseller_id']);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':destination', $destination);
        $stmt->bindParam(':price', $product_price);
        $stmt->bindParam(':api_reference', $apiResult['trxid'] ?? '');
        $stmt->bindParam(':api_response', json_encode($apiResult));
        
        if (isset($apiResult['status_code']) && $apiResult['status_code'] == '0') {
            // Order sukses
            $status = 'success';
            
            // Kurangi saldo reseller
            $updateBalance = "UPDATE resellers SET balance = balance - :price WHERE id = :id";
            $stmt2 = $db->prepare($updateBalance);
            $stmt2->bindParam(':price', $product_price);
            $stmt2->bindParam(':id', $_SESSION['reseller_id']);
            $stmt2->execute();
            
            // Catat transaksi
            $recordTransaction = "INSERT INTO balance_transactions 
                                 (reseller_id, type, amount, description, reference_id, status) 
                                 VALUES (:reseller_id, 'order', :amount, :description, :reference_id, 'completed')";
            $stmt3 = $db->prepare($recordTransaction);
            $stmt3->bindParam(':reseller_id', $_SESSION['reseller_id']);
            $stmt3->bindParam(':amount', $product_price);
            $stmt3->bindParam(':description', "Order $product_code untuk $destination");
            $stmt3->bindParam(':reference_id', $apiResult['trxid'] ?? '');
            $stmt3->execute();
            
            $_SESSION['reseller_balance'] -= $product_price;
            $success = "Order berhasil diproses. TRX ID: " . ($apiResult['trxid'] ?? 'N/A');
        } else {
            // Order gagal
            $status = 'failed';
            $error = "Order gagal: " . ($apiResult['msg'] ?? 'Terjadi kesalahan');
        }
        
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        // Kirim webhook ke reseller
        $resellerData = $auth->getResellerData($_SESSION['reseller_id']);
        if (!empty($resellerData['webhook_url'])) {
            $webhookData = [
                'event' => 'order_processed',
                'trx_id' => $apiResult['trxid'] ?? '',
                'product' => $product_code,
                'destination' => $destination,
                'status' => $status,
                'message' => $apiResult['msg'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            sendWebhook($resellerData['webhook_url'], $webhookData);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order - Rud's Store Reseller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Tetap gunakan style yang sama seperti dashboard */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }
        
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 60px;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        /* ... (style lainnya sama dengan dashboard) ... */
    </style>
</head>
<body>
    <!-- Sertakan sidebar yang sama -->
    <?php include 'reseller_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <button class="btn btn-sm btn-primary me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Order Produk</span>
                <div class="d-flex align-items-center">
                    <span class="me-3">Saldo: <strong><?php echo formatRupiah($_SESSION['reseller_balance']); ?></strong></span>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['reseller_username']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="reseller_profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Order Form -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Form Order</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="product_code" class="form-label">Pilih Produk</label>
                                <select class="form-select" id="product_code" name="product_code" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($stockData as $productCode => $stock): ?>
                                        <option value="<?php echo $productCode; ?>">
                                            <?php echo $productCode; ?> (Stok: <?php echo $stock; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="destination" class="form-label">Nomor Tujuan</label>
                                <input type="text" class="form-control" id="destination" name="destination" 
                                       placeholder="Contoh: 087812345678" required>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Proses Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Informasi Stok</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stockData as $productCode => $stock): ?>
                                        <tr>
                                            <td><?php echo $productCode; ?></td>
                                            <td>
                                                <?php if ($stock > 5): ?>
                                                    <span class="badge bg-success"><?php echo $stock; ?></span>
                                                <?php elseif ($stock > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $stock; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Habis</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
        });
    </script>
</body>
</html>