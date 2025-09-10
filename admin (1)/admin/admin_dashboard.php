<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

// Periksa apakah user adalah admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: ../admin.php');
    exit();
}

// Fungsi untuk mendapatkan statistik
function getDashboardStats($db) {
    $stats = [];
    
    // Total reseller
    $query = "SELECT COUNT(*) as total FROM resellers";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_resellers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Reseller pending
    $query = "SELECT COUNT(*) as total FROM resellers WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_resellers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total orders
    $query = "SELECT COUNT(*) as total FROM orders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's orders
    $query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $query = "SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE status = 'success'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's revenue
    $query = "SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE status = 'success' AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

// Ambil statistik
$stats = getDashboardStats($db);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
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

        <h2 class="mb-4">Dashboard Admin</h2>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #007bff, #0056b3); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Total Reseller</h5>
                    <h3><?php echo $stats['total_resellers']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h5>Pending Approval</h5>
                    <h3><?php echo $stats['pending_resellers']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #28a745, #1e7e34); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5>Total Order</h5>
                    <h3><?php echo $stats['total_orders']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card card-dashboard text-center p-3" style="background: linear-gradient(45deg, #17a2b8, #138496); color: white;">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h5>Total Revenue</h5>
                    <h3>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Reseller Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM resellers ORDER BY created_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $recentResellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (count($recentResellers) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($recentResellers as $reseller): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($reseller['full_name']); ?></h6>
                                            <small><?php echo date('d M', strtotime($reseller['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">@<?php echo $reseller['username']; ?></p>
                                        <small>
                                            Status: 
                                            <?php if ($reseller['status'] == 'active'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif ($reseller['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditangguhkan</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada reseller.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Order Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT o.*, r.username 
                                  FROM orders o 
                                  JOIN resellers r ON o.reseller_id = r.id 
                                  ORDER BY o.created_at DESC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (count($recentOrders) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo $order['product_code']; ?></h6>
                                            <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">Ke: <?php echo $order['destination']; ?></p>
                                        <small>
                                            Oleh: <?php echo $order['username']; ?> | 
                                            Status: 
                                            <?php if ($order['status'] == 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php elseif ($order['status'] == 'failed'): ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada order.
                            </div>
                        <?php endif; ?>
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