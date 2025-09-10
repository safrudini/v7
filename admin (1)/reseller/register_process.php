

<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $webhook_url = $_POST['webhook_url'];
    
    if ($auth->register($username, $password, $full_name, $email, $phone, $webhook_url)) {
        header('Location: reseller_login.php?registered=1');
        exit();
    } else {
        header('Location: reseller_login.php?error=2');
        exit();
    }

    if ($auth->register($username, $password, $full_name, $email, $phone, $webhook_url)) {
    // Dapatkan ID reseller yang baru dibuat
    $query = "SELECT id FROM resellers WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reseller) {
        // Kirim notifikasi ke admin
        notifyNewReseller($db, $reseller['id']);
    }
    
    header('Location: reseller_login.php?registered=1');
    exit();
}
}
?>
