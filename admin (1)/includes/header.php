<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && ($_SESSION['username'] === 'admin');
$isReseller = $isLoggedIn && isset($_SESSION['reseller_id']);

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Set default title if not defined
if (!isset($pageTitle)) {
    $pageTitle = "Rud's Store - Layanan Digital";
}

// Set default description if not defined
if (!isset($pageDescription)) {
    $pageDescription = "Rud's Store menyediakan layanan isi ulang kuota XL/Axis, akun premium, dan berbagai layanan digital lainnya dengan harga terbaik.";
}

// Set default keywords if not defined
if (!isset($pageKeywords)) {
    $pageKeywords = "kuota xl, kuota axis, isi pulsa, akun premium, youtube premium, spotify premium, netflix, layanan digital";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/logo.png">
    <meta property="og:site_name" content="Rud's Store">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/logo.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>/assets/images/favicon-16x16.png">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <?php if ($isAdmin): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Google Analytics -->
    <?php if (defined('GA_TRACKING_ID') && GA_TRACKING_ID): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GA_TRACKING_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GA_TRACKING_ID; ?>');
    </script>
    <?php endif; ?>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Rud's Store",
        "url": "<?php echo SITE_URL; ?>",
        "logo": "<?php echo SITE_URL; ?>/assets/images/logo.png",
        "description": "<?php echo htmlspecialchars($pageDescription); ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+62-878-4752-6737",
            "contactType": "customer service",
            "areaServed": "ID",
            "availableLanguage": "Indonesian"
        },
        "sameAs": [
            "https://wa.me/6287847526737",
            "https://chat.whatsapp.com/FijsJY1wzcSJxR3vpaC5q4"
        ]
    }
    </script>
</head>
<body class="<?php echo $isAdmin ? 'admin-panel' : ($isReseller ? 'reseller-panel' : 'frontend'); ?>">
    <!-- Google Tag Manager (noscript) -->
    <?php if (defined('GTM_ID') && GTM_ID): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo GTM_ID; ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner-overlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat...</p>
        </div>
    </div>
    
    <?php if (!$isAdmin && !$isReseller): ?>
    <!-- Main Navigation for Frontend -->
    <nav class="navbar navbar-expand-lg navbar-main">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-store me-2"></i>
                <strong>Rud's Store</strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'services.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/services.php">
                            <i class="fas fa-concierge-bell me-1"></i> Layanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'pricing.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pricing.php">
                            <i class="fas fa-tags me-1"></i> Harga
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/about.php">
                            <i class="fas fa-info-circle me-1"></i> Tentang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact.php">
                            <i class="fas fa-envelope me-1"></i> Kontak
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php if ($isReseller): ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/reseller/">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                <i class="fas fa-user-circle me-2"></i> Profil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/reseller/reseller_login.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Login Reseller
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin.php" class="btn btn-primary">
                        <i class="fas fa-lock me-1"></i> Admin
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">