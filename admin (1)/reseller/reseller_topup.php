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

// Proses top up
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_topup'])) {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    // Simpan permintaan top up
    $query = "INSERT INTO balance_transactions 
              (reseller_id, type, amount, description, reference_id, status) 
              VALUES (:reseller_id, 'topup', :amount, :description, :reference_id, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reseller_id', $_SESSION['reseller_id']);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':description', "Top up via $payment_method");
    $stmt->bindParam(':reference_id', generateTrxId());
    
    if ($stmt->execute()) {
        $success = "Permintaan top up berhasil dikirim. Menunggu konfirmasi admin.";
    } else {
        $error = "Terjadi kesalahan saat mengirim permintaan top up.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Up Saldo - Rud's Store Reseller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Gunakan style yang sama */
    </style>
</head>
<body>
    <?php include 'reseller_sidebar.php'; ?>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg mb-4">
            <!-- Navbar sama seperti sebelumnya -->
        </nav>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Top Up Saldo</h5>
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
                                <label for="amount" class="form-label">Jumlah Top Up</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       min="10000" step="1000" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Metode Pembayaran</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="QRIS">QRIS</option>
                                    <option value="Shopeepay">Shopeepay</option>
                                    <option value="Seabank">Seabank</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="request_topup" class="btn btn-primary">
                                <i class="fas fa-wallet me-2"></i>Request Top Up
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Informasi Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div id="paymentInfo">
                            <p class="text-center">Pilih metode pembayaran untuk melihat detail</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Riwayat Top Up</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM balance_transactions 
                                              WHERE reseller_id = :reseller_id AND type = 'topup' 
                                              ORDER BY created_at DESC LIMIT 10";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':reseller_id', $_SESSION['reseller_id']);
                                    $stmt->execute();
                                    
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $statusClass = '';
                                        if ($row['status'] == 'completed') $statusClass = 'success';
                                        elseif ($row['status'] == 'pending') $statusClass = 'warning';
                                        else $statusClass = 'danger';
                                        
                                        echo "<tr>
                                                <td>" . date('d M Y H:i', strtotime($row['created_at'])) . "</td>
                                                <td>" . formatRupiah($row['amount']) . "</td>
                                                <td>" . explode(' via ', $row['description'])[1] . "</td>
                                                <td><span class='badge bg-$statusClass'>" . ucfirst($row['status']) . "</span></td>
                                              </tr>";
                                    }
                                    ?>
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
        
        // Tampilkan info pembayaran berdasarkan metode yang dipilih
        document.getElementById('payment_method').addEventListener('change', function() {
            const method = this.value;
            const infoDiv = document.getElementById('paymentInfo');
            
            let html = '';
            switch(method) {
                case 'QRIS':
                    html = `<div class="text-center">
                                <img src="../assets/images/qris.jpeg" alt="QRIS" class="img-fluid mb-3" style="max-height: 200px;">
                                <p>Scan QR code di atas untuk melakukan pembayaran</p>
                            </div>`;
                    break;
                case 'Shopeepay':
                    html = `<p><strong>Shopeepay:</strong> 085891356836 a/n safrudini</p>
                            <p>Setelah transfer, harap konfirmasi ke admin dengan mengirimkan bukti transfer.</p>`;
                    break;
                case 'Seabank':
                    html = `<p><strong>Seabank:</strong> 901867336761 a/n safrudini</p>
                            <p>Setelah transfer, harap konfirmasi ke admin dengan mengirimkan bukti transfer.</p>`;
                    break;
                case 'Transfer Bank':
                    html = `<p><strong>BCA:</strong> 1234567890 a/n safrudini</p>
                            <p><strong>BRI:</strong> 0987654321 a/n safrudini</p>
                            <p>Setelah transfer, harap konfirmasi ke admin dengan mengirimkan bukti transfer.</p>`;
                    break;
                default:
                    html = '<p class="text-center">Pilih metode pembayaran untuk melihat detail</p>';
            }
            
            infoDiv.innerHTML = html;
        });
    </script>
</body>
</html>