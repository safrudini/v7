<?php
// File sidebar yang dapat diinclude di semua halaman admin
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="text-center mb-4">
        <h4>Admin Panel</h4>
        <p class="text-muted">Rud's Store</p>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reseller.php' ? 'active' : ''; ?>" href="admin_reseller.php">
                <i class="fas fa-users"></i>
                <span>Kelola Reseller</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>" href="admin_orders.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Data Order</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_transactions.php' ? 'active' : ''; ?>" href="admin_transactions.php">
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
        <li class="nav-item">
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>" href="admin_logs.php">
        <i class="fas fa-clipboard-list"></i>
        <span>Log Aktivitas</span>
    </a>
</li>
    </ul>
</div>
