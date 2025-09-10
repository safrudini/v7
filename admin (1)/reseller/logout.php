<?php
include_once '../includes/database.php';
include_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->logout();
header('Location: reseller_login.php');
exit();
?>