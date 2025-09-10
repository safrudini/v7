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

// Fungsi untuk mendapatkan semua reseller
function getResellers($db, $status = null) {
    $query = "SELECT * FROM resellers";
    if ($status) {
        $query .= " WHERE status = :status";
    }
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    if ($status) {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mengubah status reseller
function updateResellerStatus($db, $reseller_id, $status) {
    $query = "UPDATE resellers SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $reseller_id);
    return $stmt->execute();
}

// Fungsi untuk menambah/mengurangi saldo reseller
function adjustResellerBalance($db, $reseller_id, $amount, $description) {
    // Update saldo reseller
    $query = "UPDATE resellers SET balance = balance + :amount WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':id', $reseller_id);
    
    if ($stmt->execute()) {
        // Catat transaksi
        $type = $amount > 0 ? 'adjustment' : 'deduction';
        $transactionQuery = "INSERT INTO balance_transactions 
                            (reseller_id, type, amount, description, status) 
                            VALUES (:reseller_id, :type, :amount, :description, 'completed')";
        $transactionStmt = $db->prepare($transactionQuery);
        $transactionStmt->bindParam(':reseller_id', $reseller_id);
        $transactionStmt->bindParam(':type', $type);
        $transactionStmt->bindParam(':amount', abs($amount));
        $transactionStmt->bindParam(':description', $description);
        return $transactionStmt->execute();
    }
    return false;
}
// Setelah approve reseller
if (updateResellerStatus($db, $reseller_id, 'active')) {
    logActivity($db, $_SESSION['user_id'], 'approve_reseller', 
                "Menyetujui reseller ID: $reseller_id");
    $success = "Reseller berhasil disetujui.";
}

// Setelah reject reseller
if (updateResellerStatus($db, $reseller_id, 'suspended')) {
    logActivity($db, $_SESSION['user_id'], 'reject_reseller', 
                "Menolak reseller ID: $reseller_id");
    $success = "Reseller berhasil ditolak.";
}

// Setelah adjust balance
if (adjustResellerBalance($db, $reseller_id, $amount, $description)) {
    logActivity($db, $_SESSION['user_id'], 'adjust_balance', 
                "Menyesuaikan saldo reseller ID: $reseller_id sebesar $amount");
    $success = "Saldo reseller berhasil disesuaikan.";
}

// Proses aksi admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_reseller'])) {
        $reseller_id = $_POST['reseller_id'];
        if (updateResellerStatus($db, $reseller_id, 'active')) {
            $success = "Reseller berhasil disetujui.";
        } else {
            $error = "Gagal menyetujui reseller.";
        }
    } elseif (isset($_POST['reject_reseller'])) {
        $reseller_id = $_POST['reseller_id'];
        if (updateResellerStatus($db, $reseller_id, 'suspended')) {
            $success = "Reseller berhasil ditolak.";
        } else {
            $error = "Gagal menolak reseller.";
        }
    } elseif (isset($_POST['adjust_balance'])) {
        $reseller_id = $_POST['reseller_id'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        
        if (adjustResellerBalance($db, $reseller_id, $amount, $description)) {
            $success = "Saldo reseller berhasil disesuaikan.";
        } else {
            $error = "Gagal menyesuaikan saldo reseller.";
        }
    }
}

// Ambil data reseller berdasarkan status
$pendingResellers = getResellers($db, 'pending');
$activeResellers = getResellers($db, 'active');
$suspendedResellers = getResellers($db, 'suspended');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reseller - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        .sidebar {
            background-color: var(--dark);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 60px;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .card-dashboard {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            border: none;
        }
        
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .badge-pending {
            background-color: var(--warning);
        }
        
        .badge-active {
            background-color: var(--success);
        }
        
        .badge-suspended {
            background-color: var(--danger);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>Admin Panel</h4>
            <p class="text-muted">Rud's Store</p>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="admin_reseller.php">
                    <i class="fas fa-users"></i>
                    <span>Kelola Reseller</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Data Order</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin_transactions.php">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg mb-4">
            <div class="container-fluid">
                <button class="btn btn-sm btn-primary me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Kelola Reseller</span>
                <div class="d-flex align-items-center">
                    <span class="me-3">Hai, <strong>Admin</strong></span>
                </div>
            </div>
        </nav>

        <!-- Notifikasi -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="resellerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                    Menunggu Persetujuan <span class="badge bg-warning"><?php echo count($pendingResellers); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="false">
                    Aktif <span class="badge bg-success"><?php echo count($activeResellers); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suspended-tab" data-bs-toggle="tab" data-bs-target="#suspended" type="button" role="tab" aria-controls="suspended" aria-selected="false">
                    Ditangguhkan <span class="badge bg-danger"><?php echo count($suspendedResellers); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="resellerTabsContent">
            <!-- Pending Resellers -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Reseller Menunggu Persetujuan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingResellers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Telepon</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingResellers as $reseller): ?>
                                            <tr>
                                                <td><?php echo $reseller['id']; ?></td>
                                                <td><?php echo htmlspecialchars($reseller['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['email']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['phone']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($reseller['created_at'])); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="reseller_id" value="<?php echo $reseller['id']; ?>">
                                                        <button type="submit" name="approve_reseller" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Setujui
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="reseller_id" value="<?php echo $reseller['id']; ?>">
                                                        <button type="submit" name="reject_reseller" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada reseller yang menunggu persetujuan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Resellers -->
            <div class="tab-pane fade" id="active" role="tabpanel" aria-labelledby="active-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Reseller Aktif</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($activeResellers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Saldo</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeResellers as $reseller): ?>
                                            <tr>
                                                <td><?php echo $reseller['id']; ?></td>
                                                <td><?php echo htmlspecialchars($reseller['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['email']); ?></td>
                                                <td>Rp <?php echo number_format($reseller['balance'], 0, ',', '.'); ?></td>
                                                <td><?php echo date('d M Y', strtotime($reseller['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjustBalanceModal" 
                                                            data-reseller-id="<?php echo $reseller['id']; ?>" 
                                                            data-reseller-name="<?php echo htmlspecialchars($reseller['full_name']); ?>">
                                                        <i class="fas fa-pencil-alt"></i> Edit Saldo
                                                    </button>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="reseller_id" value="<?php echo $reseller['id']; ?>">
                                                        <button type="submit" name="reject_reseller" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-ban"></i> Nonaktifkan
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada reseller aktif.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Suspended Resellers -->
            <div class="tab-pane fade" id="suspended" role="tabpanel" aria-labelledby="suspended-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Reseller Ditangguhkan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($suspendedResellers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suspendedResellers as $reseller): ?>
                                            <tr>
                                                <td><?php echo $reseller['id']; ?></td>
                                                <td><?php echo htmlspecialchars($reseller['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                                <td><?php echo htmlspecialchars($reseller['email']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($reseller['created_at'])); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="reseller_id" value="<?php echo $reseller['id']; ?>">
                                                        <button type="submit" name="approve_reseller" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Aktifkan
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada reseller yang ditangguhkan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adjust Balance -->
    <div class="modal fade" id="adjustBalanceModal" tabindex="-1" aria-labelledby="adjustBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustBalanceModalLabel">Sesuaikan Saldo Reseller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="reseller_id" id="modalResellerId">
                        <div class="mb-3">
                            <label for="resellerName" class="form-label">Nama Reseller</label>
                            <input type="text" class="form-control" id="resellerName" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Jumlah Penyesuaian</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                            <div class="form-text">Masukkan jumlah positif untuk menambah saldo, negatif untuk mengurangi.</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="adjust_balance" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
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

        // Handle modal data
        var adjustBalanceModal = document.getElementById('adjustBalanceModal');
        adjustBalanceModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var resellerId = button.getAttribute('data-reseller-id');
            var resellerName = button.getAttribute('data-reseller-name');
            
            var modalTitle = adjustBalanceModal.querySelector('.modal-title');
            var modalResellerId = adjustBalanceModal.querySelector('#modalResellerId');
            var modalResellerName = adjustBalanceModal.querySelector('#resellerName');
            
            modalTitle.textContent = 'Sesuaikan Saldo - ' + resellerName;
            modalResellerId.value = resellerId;
            modalResellerName.value = resellerName;
        });
    </script>
</body>
</html>