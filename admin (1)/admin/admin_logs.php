<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';
include_once '../includes/activity_log.php';

$database = new Database();
$db = $database->getConnection();

// Periksa apakah user adalah admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] != 'admin') {
    header('Location: ../admin.php');
    exit();
}

$activityLog = new ActivityLog($db);

// Ambil filter dari URL
$filters = [
    'user_id' => $_GET['user_id'] ?? '',
    'action' => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Ambil data log
$logs = $activityLog->getLogs($filters, 100);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Admin Panel</title>
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

        <h2 class="mb-4">Log Aktivitas Sistem</h2>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Filter Log</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="action" class="form-label">Aksi</label>
                                <select class="form-select" id="action" name="action">
                                    <option value="">Semua Aksi</option>
                                    <option value="login" <?php echo ($filters['action'] == 'login') ? 'selected' : ''; ?>>Login</option>
                                    <option value="logout" <?php echo ($filters['action'] == 'logout') ? 'selected' : ''; ?>>Logout</option>
                                    <option value="approve_reseller" <?php echo ($filters['action'] == 'approve_reseller') ? 'selected' : ''; ?>>Approve Reseller</option>
                                    <option value="reject_reseller" <?php echo ($filters['action'] == 'reject_reseller') ? 'selected' : ''; ?>>Reject Reseller</option>
                                    <option value="adjust_balance" <?php echo ($filters['action'] == 'adjust_balance') ? 'selected' : ''; ?>>Adjust Balance</option>
                                    <option value="place_order" <?php echo ($filters['action'] == 'place_order') ? 'selected' : ''; ?>>Place Order</option>
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
                        <a href="admin_logs.php" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                        <a href="export_data.php?export=logs<?php echo !empty($filters) ? '&' . http_build_query($filters) : ''; ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Ekspor CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Data Log Aktivitas</h5>
            </div>
            <div class="card-body">
                <?php if (count($logs) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                    <th>Deskripsi</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo $log['username'] ?: 'System'; ?></td>
                                        <td>
                                            <?php 
                                            $actionLabels = [
                                                'login' => 'Login',
                                                'logout' => 'Logout',
                                                'approve_reseller' => 'Approve Reseller',
                                                'reject_reseller' => 'Reject Reseller',
                                                'adjust_balance' => 'Adjust Balance',
                                                'place_order' => 'Place Order'
                                            ];
                                            echo $actionLabels[$log['action']] ?? $log['action']; 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                                        <td><?php echo $log['ip_address']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Tidak ada data log.
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