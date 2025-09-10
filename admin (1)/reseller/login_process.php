<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        header('Location: reseller_dashboard.php');
        exit();
    } else {
        header('Location: reseller_login.php?error=1');
        exit();
    }
}
?>