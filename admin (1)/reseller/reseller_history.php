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

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query for orders
function getOrders($db, $resellerId, $filters = [], $limit = 20, $offset = 0) {
    $query = "SELECT o.*, p.product_name, p.harga as product_price 
              FROM orders o 
              LEFT JOIN product_details p ON o.product_code = p.product_key 
              WHERE o.reseller_id = :reseller_id";
    
    $params = [':reseller_id' => $resellerId];
    
    if (!empty($filters['status'])) {
        $query .= " AND o.status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(o.created_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(o.created_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total count for pagination
function getOrdersCount($db, $resellerId, $filters = []) {
    $query = "SELECT COUNT(*) as total FROM orders WHERE reseller_id = :reseller_id";
    $params = [':reseller_id' => $resellerId];
    
    if (!empty($filters['status'])) {
        $query .= " AND status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(created_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(created_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Apply filters
$filters = [
    'status' => $statusFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

// Get orders
$orders = getOrders($db, $resellerId, $filters, $limit, $offset);
$totalOrders = getOrdersCount($db, $resellerId, $filters);
$totalPages = ceil($totalOrders / $limit);

// Get order statistics
function getOrderStats($db, $resellerId) {
    $query = "SELECT 
                status,
                COUNT(*) as count,
                COALESCE(SUM(price), 0) as total_amount
              FROM orders 
              WHERE reseller_id = :reseller_id 
              GROUP BY status";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $resellerId, PDO::PARAM_INT);
    $stmt->execute();
    
    $stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row;
    }
    return $stats;
}

$orderStats = getOrderStats($db, $resellerId);

// Page configuration
$pageTitle = "Riwayat Order - Reseller Panel";
$pageDescription = "Lihat riwayat order dan transaksi Anda";
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .orders-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-success { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
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
                <span class="navbar-brand mb-0 h1">Riwayat Order</span>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number text-primary"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-label">Total Order</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-success"><?php echo number_format($orderStats['success']['count'] ?? 0); ?></div>
                <div class="stat-label">Order Sukses</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-warning"><?php echo number_format($orderStats['pending']['count'] ?? 0); ?></div>
                <div class="stat-label">Order Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-danger"><?php echo number_format($orderStats['failed']['count'] ?? 0); ?></div>
                <div class="stat-label">Order Gagal</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <h5 class="mb-3">Filter Order</h5>
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="success" <?php echo $statusFilter === 'success' ? 'selected' : ''; ?>>Sukses</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Gagal</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Terapkan
                        </button>
                        <a href="reseller_history.php" class="btn btn-secondary">
                            <i class="fas fa-sync me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Order</h5>
                    <a href="../api/export_orders.php?<?php echo http_build_query($filters); ?>" class="export-btn">
                        <i class="fas fa-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="orders-table">
                    <?php if (!empty($orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Produk</th>
                                        <th>Tujuan</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Referensi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['product_name'] ?? $order['product_code']); ?>
                                            <?php if ($order['product_price']): ?>
                                                <br><small class="text-muted"><?php echo $order['product_price']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['destination']); ?></td>
                                        <td><?php echo formatRupiah($order['price']); ?></td>
                                        <td>
                                            <?php if ($order['status'] == 'success'): ?>
                                                <span class="status-badge status-success">Sukses</span>
                                            <?php elseif ($order['status'] == 'pending'): ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php else: ?>
                                                <span class="status-badge status-failed">Gagal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $order['api_reference'] ?: 'N/A'; ?></small>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderDetailModal" 
                                                    data-order-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">Belum ada order</h5>
                            <p class="text-muted">Mulai dengan membuat order pertama Anda</p>
                            <a href="reseller_order.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Order Baru
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Detail Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Memuat detail order...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto refresh balance
        setInterval(updateBalance, 30000);
        
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
        
        // Order detail modal
        const orderDetailModal = document.getElementById('orderDetailModal');
        orderDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orderId = button.getAttribute('data-order-id');
            const modalBody = orderDetailModal.querySelector('#orderDetailContent');
            
            // Load order details via AJAX
            fetch(`../api/get_order_detail.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalBody.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Informasi Order</h6>
                                    <table class="table table-sm">
                                        <tr><th>ID Order:</th><td>${data.order.id}</td></tr>
                                        <tr><th>Tanggal:</th><td>${new Date(data.order.created_at).toLocaleString('id-ID')}</td></tr>
                                        <tr><th>Produk:</th><td>${data.order.product_name || data.order.product_code}</td></tr>
                                        <tr><th>Tujuan:</th><td>${data.order.destination}</td></tr>
                                        <tr><th>Harga:</th><td>${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR'}).format(data.order.price)}</td></tr>
                                        <tr><th>Status:</th><td>${getStatusBadge(data.order.status)}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Detail Respons</h6>
                                    ${data.order.api_response ? `
                                        <pre class="bg-light p-3" style="max-height: 200px; overflow-y: auto;">
                                            ${JSON.stringify(JSON.parse(data.order.api_response), null, 2)}
                                        </pre>
                                    ` : '<p class="text-muted">Tidak ada data respons</p>'}
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Gagal memuat detail order: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Terjadi kesalahan saat memuat detail order.
                        </div>
                    `;
                    console.error('Error loading order details:', error);
                });
        });
        
        function getStatusBadge(status) {
            const statuses = {
                'success': ['Sukses', 'success'],
                'pending': ['Pending', 'warning'],
                'failed': ['Gagal', 'danger']
            };
            
            const [text, type] = statuses[status] || [status, 'secondary'];
            return `<span class="badge bg-${type}">${text}</span>`;
        }
    </script>
</body>
</html>