<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if already logged in
if ($auth->isLoggedIn()) {
    header('Location: reseller_dashboard.php');
    exit();
}

// Process login if form submitted
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        header('Location: reseller_dashboard.php');
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}

// Process registration if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $webhook_url = trim($_POST['webhook_url']);
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($phone)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Check if username or email already exists
        $check_query = "SELECT id FROM resellers WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            // Register new reseller
            $register_success = $auth->register($username, $password, $full_name, $email, $phone, $webhook_url);
            
            if ($register_success) {
                $success = "Pendaftaran berhasil! Menunggu persetujuan admin.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
            }
        }
    }
}

// Page configuration
$pageTitle = "Login Reseller - Rud's Store";
$pageDescription = "Login atau daftar sebagai reseller Rud's Store";
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
        .login-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .login-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .login-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .login-tab.active {
            border-bottom-color: #007bff;
            color: #007bff;
        }
        
        .login-tab:hover {
            background-color: #f8f9fa;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .social-login {
            text-align: center;
            margin: 20px 0;
        }
        
        .social-divider {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }
        
        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }
        
        .social-divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #6c757d;
        }
        
        .social-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-google {
            background: #db4437;
        }
        
        .btn-facebook {
            background: #4267B2;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            margin: 0 10px;
            font-size: 0.9rem;
        }
        
        .footer-links a:hover {
            color: #007bff;
        }
        
        @media (max-width: 576px) {
            .login-card {
                margin: 10px;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <i class="fas fa-store fa-3x mb-3"></i>
                    <h2 class="mb-1">Rud's Store</h2>
                    <p class="mb-0">Portal Reseller</p>
                </div>
            </div>
            
            <div class="login-body">
                <!-- Notifications -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Tabs -->
                <div class="login-tabs">
                    <div class="login-tab active" data-tab="login">Login</div>
                    <div class="login-tab" data-tab="register">Daftar</div>
                </div>
                
                <!-- Login Form -->
                <div class="tab-content active" id="loginTab">
                    <form method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        
                        <div class="form-group">
                            <label for="loginUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="loginUsername" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   required autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                            <label class="form-check-label" for="rememberMe">Ingat saya</label>
                        </div>
                        
                        <button type="submit" class="btn btn-login mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                        
                        <div class="text-center">
                            <a href="forgot_password.php" class="text-decoration-none">Lupa password?</a>
                        </div>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div class="tab-content" id="registerTab">
                    <form method="POST" action="">
                        <input type="hidden" name="register" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="regUsername" class="form-label">Username*</label>
                                    <input type="text" class="form-control" id="regUsername" name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="regFullName" class="form-label">Nama Lengkap*</label>
                                    <input type="text" class="form-control" id="regFullName" name="full_name" 
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="regEmail" class="form-label">Email*</label>
                            <input type="email" class="form-control" id="regEmail" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="regPhone" class="form-label">Nomor Telepon*</label>
                            <input type="tel" class="form-control" id="regPhone" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="regWebhook" class="form-label">URL Webhook</label>
                            <input type="url" class="form-control" id="regWebhook" name="webhook_url" 
                                   value="<?php echo htmlspecialchars($_POST['webhook_url'] ?? ''); ?>" 
                                   placeholder="https://example.com/webhook">
                            <small class="form-text text-muted">URL untuk menerima notifikasi transaksi (opsional)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="regPassword" class="form-label">Password*</label>
                                    <input type="password" class="form-control" id="regPassword" name="password" required>
                                    <small class="form-text text-muted">Minimal 6 karakter</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="regConfirmPassword" class="form-label">Konfirmasi Password*</label>
                                    <input type="password" class="form-control" id="regConfirmPassword" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label" for="agreeTerms">
                                Saya menyetujui <a href="<?php echo SITE_URL; ?>/terms.php" target="_blank">Syarat & Ketentuan</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </button>
                    </form>
                </div>
                
                <!-- Social Login
                <div class="social-divider">
                    <span>Atau lanjutkan dengan</span>
                </div>
                
                <div class="social-buttons">
                    <a href="#" class="social-btn btn-google">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-btn btn-facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
                -->
            </div>
            
            <div class="footer-links">
                <a href="<?php echo SITE_URL; ?>">Kembali ke Beranda</a>
                <a href="<?php echo SITE_URL; ?>/admin.php">Login Admin</a>
                <a href="<?php echo WHATSAPP_URL; ?>" target="_blank">Bantuan</a>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tab switching functionality
        document.querySelectorAll('.login-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabId + 'Tab').classList.add('active');
            });
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const inputs = this.querySelectorAll('input[required]');
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                // Password confirmation check
                const password = document.getElementById('regPassword');
                const confirmPassword = document.getElementById('regConfirmPassword');
                
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    isValid = false;
                    confirmPassword.classList.add('is-invalid');
                    alert('Konfirmasi password tidak cocok!');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
        
        // Clear validation on input
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
        
        // Check if there's a success message and switch to login tab
        <?php if ($success): ?>
        document.querySelector('[data-tab="login"]').click();
        <?php endif; ?>
        
        // Check URL parameters for tab
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam === 'register') {
            document.querySelector('[data-tab="register"]').click();
        }
    </script>
</body>
</html>