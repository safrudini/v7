<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class Auth {
    private $conn;
    private $table_name = "resellers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($username, $password, $full_name, $email, $phone, $webhook_url) {
        // Check if user already exists
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return false; // User already exists
        }

        // Insert new user
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, password=:password, full_name=:full_name, 
                  email=:email, phone=:phone, webhook_url=:webhook_url, status='pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':webhook_url', $webhook_url);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function login($username, $password) {
        $query = "SELECT id, username, password, full_name, status, balance FROM " . $this->table_name . " 
                  WHERE username = :username AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['reseller_id'] = $row['id'];
                $_SESSION['reseller_username'] = $row['username'];
                $_SESSION['reseller_name'] = $row['full_name'];
                $_SESSION['reseller_balance'] = $row['balance'];
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['reseller_id']);
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function getResellerData($id) {
        $query = "SELECT id, username, full_name, email, phone, webhook_url, balance, status, created_at 
                  FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>