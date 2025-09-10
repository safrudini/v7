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

// Get balance transactions
function getBalanceTransactions($db, $resellerId, $limit = 20) {
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

// Get transactions
$transactions = getBalanceTransactions($db, $resellerId);

// Get transaction statistics
function getTransactionStats($db, $resellerId) {
    $query = "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = 'topup' AND status = 'completed' THEN amount ELSE 0 END) as total_topup,
                SUM(CASE WHEN type = 'order' THEN amount ELSE 0 END) as total_orders,
                SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refunds
              FROM balance_transactions 
              WHERE reseller_id = :reseller_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$stats = getTransactionStats($db, $resellerId);

// Page configuration
$pageTitle = "Saldo & Transaksi - Reseller Panel";
$pageDescription = "Kelola saldo dan lihat riwayat transaksi Anda";
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        .balance-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.3);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .transaction-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .transaction-type {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .type-topup { background-color: #d4edda; color: #155724; }
        .type-order { background-color: #fff3cd; color: #856404; }
        .type-refund { background-color: #d1ecf1; color: #0c5460; }
        .type-adjustment { background-color: #f8d7da; color: #721c24; }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
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
                <span class="navbar-brand mb-0 h1">Saldo & Transaksi</span>
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

        <!-- Balance Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="balance-card text-center">
                    <div class="balance-icon mb-3">
                        <i class="fas fa-wallet fa-3x"></i>
                    </div>
                    <h2 class="balance-amount mb-2"><?php echo formatRupiah($resellerData['balance']); ?></h2>
                    <p class="balance-label mb-3">Saldo Tersedia</p>
                    <div class="balance-actions">
                        <a href="reseller_topup.php" class="btn btn-light me-2">
                            <i class="fas fa-plus-circle me-1"></i> Top Up
                        </a>
                        <a href="reseller_order.php" class="btn btn-outline-light">
                            <i class="fas fa-shopping-cart me-1"></i> Order Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon text-primary mb-2">
                        <i class="fas fa-exchange-alt fa-2x"></i>
                    </div>
                    <h3 class="stat-number"><?php echo number_format($stats['total_transactions'] ?? 0); ?></h3>
                    <p class="stat-label text-muted mb-0">Total Transaksi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon text-success mb-2">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <h3 class="stat-number"><?php echo formatRupiah($stats['total_topup'] ?? 0); ?></h3>
                    <p class="stat-label text-muted mb-0">Total Top Up</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon text-warning mb-2">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                    <h3 class="stat-number"><?php echo formatRupiah($stats['total_orders'] ?? 0); ?></h3>
                    <p class="stat-label text-muted mb-0">Total Order</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-icon text-info mb-2">
                        <i class="fas fa-undo fa-2x"></i>
                    </div>
                    <h3 class="stat-number"><?php echo formatRupiah($stats['total_refunds'] ?? 0); ?></h3>
                    <p class="stat-label text-muted mb-0">Total Refund</p>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Riwayat Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="transaction-table">
                            <table class="table table-hover" id="transactionsTable">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Jumlah</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Referensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td>
                                            <span class="transaction-type type-<?php echo $transaction['type']; ?>">
                                                <?php 
                                                $typeLabels = [
                                                    'topup' => 'Top Up',
                                                    'order' => 'Order',
                                                    'refund' => 'Refund',
                                                    'adjustment' => 'Penyesuaian'
                                                ];
                                                echo $typeLabels[$transaction['type']] ?? $transaction['type']; 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transaction['type'] === 'topup' || $transaction['type'] === 'refund'): ?>
                                            <span class="text-success">+ <?php echo formatRupiah($transaction['amount']); ?></span>
                                            <?php else: ?>
                                            <span class="text-danger">- <?php echo formatRupiah($transaction['amount']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                <?php 
                                                $statusLabels = [
                                                    'completed' => 'Selesai',
                                                    'pending' => 'Pending',
                                                    'failed' => 'Gagal'
                                                ];
                                                echo $statusLabels[$transaction['status']] ?? $transaction['status']; 
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $transaction['reference_id'] ?: 'N/A'; ?></td>
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

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                order: [[0, 'desc']],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
            
            // Auto refresh balance every 30 seconds
            setInterval(updateBalance, 30000);
        });
        
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
                    
                    document.querySelector('.balance-amount').textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(data.balance);
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
    </script>
</body>
</html>