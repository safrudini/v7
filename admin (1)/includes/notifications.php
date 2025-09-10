<?php
class Notification {
    private $conn;
    private $table_name = "notifications";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Buat notifikasi baru
    public function create($user_id, $title, $message, $type = 'info') {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id = :user_id, title = :title, message = :message, type = :type, is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        
        return $stmt->execute();
    }

    // Dapatkan notifikasi untuk user
    public function getForUser($user_id, $limit = 10) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tandai notifikasi sebagai sudah dibaca
    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id);
        return $stmt->execute();
    }

    // Hitung notifikasi yang belum dibaca
    public function countUnread($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}

// Fungsi untuk mengirim notifikasi ke admin ketika ada reseller baru
function notifyNewReseller($db, $reseller_id) {
    $notification = new Notification($db);
    
    // Dapatkan data reseller
    $query = "SELECT username, full_name FROM resellers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reseller_id);
    $stmt->execute();
    $reseller = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reseller) {
        $title = "Reseller Baru Mendaftar";
        $message = "Reseller " . $reseller['full_name'] . " (" . $reseller['username'] . ") menunggu persetujuan.";
        
        // Kirim notifikasi ke semua admin (dalam kasus ini hanya user dengan username 'admin')
        $adminQuery = "SELECT id FROM users WHERE username = 'admin'";
        $adminStmt = $db->prepare($adminQuery);
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            $notification->create($admin['id'], $title, $message, 'warning');
        }
    }
}
?>