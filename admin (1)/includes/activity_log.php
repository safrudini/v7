<?php
class ActivityLog {
    private $conn;
    private $table_name = "activity_logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Catat aktivitas
    public function log($user_id, $action, $description, $ip_address = null) {
        if ($ip_address === null) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id = :user_id, action = :action, description = :description, ip_address = :ip_address";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip_address', $ip_address);
        
        return $stmt->execute();
    }

    // Dapatkan log aktivitas
    public function getLogs($filters = [], $limit = 50) {
        $query = "SELECT al.*, u.username 
                  FROM " . $this->table_name . " al 
                  LEFT JOIN users u ON al.user_id = u.id 
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $query .= " AND al.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND al.action = :action";
            $params[':action'] = $filters['action'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(al.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(al.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY al.created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fungsi helper untuk mencatat aktivitas
function logActivity($db, $user_id, $action, $description) {
    $activityLog = new ActivityLog($db);
    return $activityLog->log($user_id, $action, $description);
}
?>