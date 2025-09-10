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

// Fungsi untuk mendapatkan semua order
function getOrders($db, $filters = []) {
    $query = "SELECT o.*, r.username, r.full_name 
              FROM orders o 
              JOIN resellers r ON o.reseller_id = r.id 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $query .= " AND o.status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['reseller_id'])) {
        $query .= " AND o.reseller_id = :reseller_id";
        $params[':reseller_id'] = $filters['reseller_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(o.created_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(o.created_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan semua reseller
function getAllResellers($db) {
    $query = "SELECT id, username, full_name FROM resellers ORDER BY username";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil filter dari URL
$filters = [
    'status' => $_GET['status'] ?? '',
    'reseller_id' => $_GET['reseller_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Ambil data order
$orders = getOrders($db, $filters);
$resellers = getAllResellers($db);

// Hitung statistik
$totalOrders = count($orders);
$successOrders = count(array_filter($orders, function($order) {
    return $order['status'] == 'success';
}));
$failedOrders = count(array_filter($orders, function($order) {
    return $order['status'] == 'failed';
}));
$pendingOrders = count(array_filter($orders, function($order) {
    return $order['status'] == 'pending';
}));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Order - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <style>
        /* Gunakan style yang sama seperti halaman admin_reseller.php */
    </style>
</head>
<body>
    <!-- Sertakan sidebar yang sama -->
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg mb-4">
            <!-- Navbar sama seperti sebelumnya -->
        </nav>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #007bff, #0056b3); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5>Total Order</h5>
                    <h3><?php echo $totalOrders; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #28a745, #1e7e34); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5>Sukses</h5>
                    <h3><?php echo $successOrders; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h5>Gagal</h5>
                    <h3><?php echo $failedOrders; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Pending</h5>
                    <h3><?php echo $pendingOrders; ?></h3>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Filter Order</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo ($filters['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="success" <?php echo ($filters['status'] == 'success') ? 'selected' : ''; ?>>Success</option>
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
                        <a href="admin_orders.php" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Data Order</h5>
            </div>
            <div class="card-body">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Reseller</th>
                                    <th>Produk</th>
                                    <th>Tujuan</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>API Reference</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo htmlspecialchars($order['product_code']); ?></td>
                                        <td><?php echo htmlspecialchars($order['destination']); ?></td>
                                        <td>Rp <?php echo number_format($order['price'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($order['status'] == 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php elseif ($order['status'] == 'failed'): ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $order['api_reference'] ?: 'N/A'; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailModal" 
                                                    data-order-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Tidak ada data order.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Order Detail -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Detail Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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

        // Load order detail via AJAX
        var orderDetailModal = document.getElementById('orderDetailModal');
        orderDetailModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var orderId = button.getAttribute('data-order-id');
            
            var modalBody = orderDetailModal.querySelector('#orderDetailContent');
            modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
            
            // AJAX request to get order details
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajax_get_order_detail.php?order_id=' + orderId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    modalBody.innerHTML = xhr.responseText;
                } else {
                    modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat detail order.</div>';
                }
            };
            xhr.send();
        });
    </script>
</body>
</html>