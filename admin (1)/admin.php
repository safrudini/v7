<?php
session_start();

// Load config + koneksi database
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi!";
    } else {
        // Query cek user
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // Jika user ditemukan
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userId, $dbUsername, $hashedPassword);
            $stmt->fetch();

            // Verifikasi password
            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id']  = $userId;
                $_SESSION['username'] = $dbUsername;

                // Arahkan admin ke dashboard
                if ($dbUsername === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                    exit();
                } else {
                    // Kalau ada role lain (misal staff), bisa diarahkan ke halaman berbeda
                    header("Location: admin.php");
                    exit();
                }
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Login Admin</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <small>© <?= date("Y") ?> Rud's Store</small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
