<?php
session_start();
require_once __DIR__ . '/config.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$message = "";

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = "<div class='alert alert-danger'>Username dan password wajib diisi!</div>";
    } else {
        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='alert alert-warning'>Username sudah ada!</div>";
        } else {
            // Hash password dan simpan
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashedPassword);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Akun admin berhasil dibuat! Silakan login.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Terjadi kesalahan: " . $conn->error . "</div>";
            }
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
    <title>Register Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header bg-dark text-white text-center">
                    <h4>Register Admin</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)) echo $message; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username Admin</label>
                            <input type="text" name="username" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Buat Akun</button>
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
