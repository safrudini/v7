<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';
include_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Periksa apakah user adalah admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header('Location: ../admin.php');
    exit();
}

// Fungsi untuk mendapatkan semua transaksi
function getTransactions($db, $filters = []) {
    $query = "SELECT bt.*, r.username, r.full_name 
              FROM balance_transactions bt 
              JOIN resellers r ON bt.reseller_id = r.id 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['type'])) {
        $query .= " AND bt.type = :type";
        $params[':type'] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $query .= " AND bt.status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['reseller_id'])) {
        $query .= " AND bt.reseller_id = :reseller_id";
        $params[':reseller_id'] = $filters['reseller_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(bt.created_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(bt.created_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    $query .= " ORDER BY bt.created_at DESC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk update status transaksi
function updateTransactionStatus($db, $transaction_id, $status) {
    $query = "UPDATE balance_transactions SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $transaction_id);
    return $stmt->execute();
}

// Proses aksi admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_transaction'])) {
        $transaction_id = $_POST['transaction_id'];
        
        // Dapatkan data transaksi
        $query = "SELECT * FROM balance_transactions WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $transaction_id);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction && $transaction['type'] == 'topup' && $transaction['status'] == 'pending') {
            // Update saldo reseller
            $updateQuery = "UPDATE resellers SET balance = balance + :amount WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':amount', $transaction['amount']);
            $updateStmt->bindParam(':id', $transaction['reseller_id']);
            
            if ($updateStmt->execute()) {
                // Update status transaksi
                if (updateTransactionStatus($db, $transaction_id, 'completed')) {
                    $success = "Top up saldo berhasil disetujui.";
                } else {
                    $error = "Gagal mengupdate status transaksi.";
                }
            } else {
                $error = "Gagal mengupdate saldo reseller.";
            }
        }
    } elseif (isset($_POST['reject_transaction'])) {
        $transaction_id = $_POST['transaction_id'];
        
        if (updateTransactionStatus($db, $transaction_id, 'failed')) {
            $success = "Transaksi berhasil ditolak.";
        } else {
            $error = "Gagal menolak transaksi.";
        }
    }
}

// Ambil filter dari URL
$filters = [
    'type' => $_GET['type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'reseller_id' => $_GET['reseller_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Ambil data transaksi
$transactions = getTransactions($db, $filters);
$resellers = getAllResellers($db);

// Hitung statistik
$totalTransactions = count($transactions);
$pendingTransactions = count(array_filter($transactions, function($transaction) {
    return $transaction['status'] == 'pending';
}));
$completedTransactions = count(array_filter($transactions, function($transaction) {
    return $transaction['status'] == 'completed';
}));
$failedTransactions = count(array_filter($transactions, function($transaction) {
    return $transaction['status'] == 'failed';
}));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <style>
        /* Gunakan style yang sama seperti halaman admin lainnya */
    </style>
</head>
<body>
    <!-- Sertakan sidebar yang sama -->
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg mb-4">
            <!-- Navbar sama seperti sebelumnya -->
        </nav>

        <h2 class="mb-4">Kelola Transaksi</h2>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #007bff, #0056b3); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h5>Total Transaksi</h5>
                    <h3><?php echo $totalTransactions; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Pending</h5>
                    <h3><?php echo $pendingTransactions; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #28a745, #1e7e34); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5>Completed</h5>
                    <h3><?php echo $completedTransactions; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h5>Failed</h5>
                    <h3><?php echo $failedTransactions; ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Filter Transaksi</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="type" class="form-label">Jenis</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Semua Jenis</option>
                                    <option value="topup" <?php echo ($filters['type'] == 'topup') ? 'selected' : ''; ?>>Top Up</option>
                                    <option value="order" <?php echo ($filters['type'] == 'order') ? 'selected' : ''; ?>>Order</option>
                                    <option value="refund" <?php echo ($filters['type'] == 'refund') ? 'selected' : ''; ?>>Refund</option>
                                    <option value="adjustment" <?php echo ($filters['type'] == 'adjustment') ? 'selected' : ''; ?>>Adjustment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo ($filters['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo ($filters['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($filters['status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="reseller_id" class="form-label">Reseller</label>
                                <select class="form-select" id="reseller_id" name="reseller_id">
                                    <option value="">Semua Reseller</option>
                                    <?php foreach ($resellers as $reseller): ?>
                                        <option value="<?php echo $reseller['id']; ?>" <?php echo ($filters['reseller_id'] == $reseller['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($reseller['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_from" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filters['date_from']; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="date_to" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filters['date_to']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Terapkan Filter
                        </button>
                        <a href="admin_transactions.php" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Data Transaksi</h5>
            </div>
            <div class="card-body">
                <?php if (count($transactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Reseller</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th>Referensi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['id']; ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['username']); ?></td>
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
                                        <td>Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td>
                                            <?php if ($transaction['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($transaction['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $transaction['reference_id'] ?: 'N/A'; ?></td>
                                        <td>
                                            <?php if ($transaction['type'] == 'topup' && $transaction['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                    <button type="submit" name="approve_transaction" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                    <button type="submit" name="reject_transaction" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Tidak ada data transaksi.
                    </div>
                <?php endif; ?>
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