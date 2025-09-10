<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if reseller is logged in
if (!$auth->isLoggedIn()) {
    header('Location: reseller_login.php');
    exit();
}

// Get reseller data
$resellerId = $_SESSION['reseller_id'];
$resellerData = $auth->getResellerData($resellerId);

// Get dashboard statistics
function getDashboardStats($db, $resellerId) {
    $stats = [];
    
    // Total orders
    $query = "SELECT COUNT(*) as total_orders FROM orders WHERE reseller_id = :reseller_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    // Successful orders
    $query = "SELECT COUNT(*) as success_orders FROM orders WHERE reseller_id = :reseller_id AND status = 'success'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['success_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['success_orders'];
    
    // Failed orders
    $query = "SELECT COUNT(*) as failed_orders FROM orders WHERE reseller_id = :reseller_id AND status = 'failed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['failed_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['failed_orders'];
    
    // Pending orders
    $query = "SELECT COUNT(*) as pending_orders FROM orders WHERE reseller_id = :reseller_id AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];
    
    // Total spending
    $query = "SELECT COALESCE(SUM(price), 0) as total_spent FROM orders WHERE reseller_id = :reseller_id AND status = 'success'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['total_spent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
    
    // Today's orders
    $query = "SELECT COUNT(*) as today_orders FROM orders WHERE reseller_id = :reseller_id AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    $stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_orders'];
    
    return $stats;
}

// Get recent orders
function getRecentOrders($db, $resellerId, $limit = 5) {
    $query = "SELECT o.*, p.product_name 
              FROM orders o 
              LEFT JOIN product_details p ON o.product_code = p.product_key 
              WHERE o.reseller_id = :reseller_id 
              ORDER BY o.created_at DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent transactions
function getRecentTransactions($db, $resellerId, $limit = 5) {
    $query = "SELECT * FROM balance_transactions 
              WHERE reseller_id = :reseller_id 
              ORDER BY created_at DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data
$stats = getDashboardStats($db, $resellerId);
$recentOrders = getRecentOrders($db, $resellerId);
$recentTransactions = getRecentTransactions($db, $resellerId);

// Get stock data from API
$apiUrl = 'https://panel.khfy-store.com/api/api-xl-v7/cek_stock_akrab';
$context = stream_context_create(['http' => ['timeout' => 15]]);
$jsonData = @file_get_contents($apiUrl, false, $context);
$stockData = [];

if ($jsonData !== FALSE) {
    $apiResponse = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($apiResponse['data'])) {
        $stockData = $apiResponse['data'];
    }
}

// Page configuration
$pageTitle = "Dashboard - Reseller Panel";
$pageDescription = "Dashboard utama reseller Rud's Store";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease;
            text-align: center;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            color: white;
        }
        
        .quick-action-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .recent-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            height: 100%;
        }
        
        .stock-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-high { background-color: #d4edda; color: #155724; }
        .stock-medium { background-color: #fff3cd; color: #856404; }
        .stock-low { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'reseller_sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <button class="btn btn-sm btn-primary me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Dashboard Reseller</span>
                <div class="d-flex align-items-center">
                    <span class="me-3">Saldo: <strong class="balance-display"><?php echo formatRupiah($resellerData['balance']); ?></strong></span>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['reseller_username']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="reseller_profile.php">
                                <i class="fas fa-user me-2"></i>Profil
                            </a></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-primary">
                    <h4 class="alert-heading">Selamat datang, <?php echo htmlspecialchars($resellerData['full_name']); ?>! 👋</h4>
                    <p class="mb-0">Selamat berjualan di Rud's Store. Lihat statistik terbaru dan kelola bisnis Anda dari dashboard ini.</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stats-label">Total Order</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['success_orders']); ?></div>
                    <div class="stats-label">Order Sukses</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number"><?php echo number_format($stats['today_orders']); ?></div>
                    <div class="stats-label">Order Hari Ini</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stats-number"><?php echo formatRupiah($stats['total_spent']); ?></div>
                    <div class="stats-label">Total Pengeluaran</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Stock Info -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="quick-actions">
                    <h5 class="mb-3">Aksi Cepat</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="reseller_order.php" class="quick-action-btn">
                                <div class="quick-action-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Order Baru</h6>
                                    <small>Buat order produk</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="reseller_topup.php" class="quick-action-btn" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);">
                                <div class="quick-action-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Top Up Saldo</h6>
                                    <small>Tambah saldo akun</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="reseller_history.php" class="quick-action-btn" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                                <div class="quick-action-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Riwayat Order</h6>
                                    <small>Lihat history order</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo WHATSAPP_URL; ?>" target="_blank" class="quick-action-btn" style="background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);">
                                <div class="quick-action-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Bantuan</h6>
                                    <small>Hubungi CS</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="recent-box">
                    <h5 class="mb-3">Informasi Stok</h5>
                    <?php if (!empty($stockData)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stockData as $product => $stock): ?>
                                        <?php if ($stock > 0): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product); ?></td>
                                            <td>
                                                <?php if ($stock > 10): ?>
                                                    <span class="stock-badge stock-high"><?php echo $stock; ?>+</span>
                                                <?php elseif ($stock > 5): ?>
                                                    <span class="stock-badge stock-medium"><?php echo $stock; ?></span>
                                                <?php else: ?>
                                                    <span class="stock-badge stock-low"><?php echo $stock; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Terakhir update: <?php echo date('H:i:s'); ?></small>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                            <p class="text-muted">Data stok tidak tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders & Transactions -->
        <div class="row">
            <div class="col-lg-6">
                <div class="recent-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Order Terbaru</h5>
                        <a href="reseller_history.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <?php if (!empty($recentOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Tujuan</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['product_name'] ?? $order['product_code']); ?></td>
                                        <td><?php echo htmlspecialchars($order['destination']); ?></td>
                                        <td>
                                            <?php if ($order['status'] == 'success'): ?>
                                                <span class="badge bg-success">Sukses</span>
                                            <?php elseif ($order['status'] == 'failed'): ?>
                                                <span class="badge bg-danger">Gagal</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-shopping-cart text-muted fa-2x mb-2"></i>
                            <p class="text-muted">Belum ada order</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="recent-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Transaksi Terbaru</h5>
                        <a href="reseller_balance.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <?php if (!empty($recentTransactions)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $typeLabels = [
                                                'topup' => 'Top Up',
                                                'order' => 'Order',
                                                'refund' => 'Refund',
                                                'adjustment' => 'Penyesuaian'
                                            ];
                                            echo $typeLabels[$transaction['type']] ?? $transaction['type']; 
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['type'] === 'topup' || $transaction['type'] === 'refund'): ?>
                                                <span class="text-success">+ <?php echo formatRupiah($transaction['amount']); ?></span>
                                            <?php else: ?>
                                                <span class="text-danger">- <?php echo formatRupiah($transaction['amount']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php elseif ($transaction['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Gagal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d M H:i', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-exchange-alt text-muted fa-2x mb-2"></i>
                            <p class="text-muted">Belum ada transaksi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto refresh balance every 30 seconds
        setInterval(updateBalance, 30000);
        
        // Function to update balance display
        async function updateBalance() {
            try {
                const response = await fetch('../api/get_balance.php');
                const data = await response.json();
                
                if (data.success) {
                    document.querySelectorAll('.balance-display').forEach(element => {
                        element.textContent = new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                        }).format(data.balance);
                    });
                }
            } catch (error) {
                console.error('Error updating balance:', error);
            }
        }
        
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
        });
        
        // Auto refresh stock every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>