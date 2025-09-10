<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect ke login reseller
header("Location: reseller/reseller_login.php");
exit();
