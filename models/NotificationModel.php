<?php
class NotificationModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy toàn bộ thông báo của một user, sắp xếp mới nhất lên đầu
    public function getNotificationsByUserId($userId) {
        $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Đếm số lượng thông báo chưa đọc (dùng hiển thị badge trên chuông thông báo)
    public function countUnread($userId) {
        $query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Đánh dấu 1 thông báo là đã đọc
    public function markAsRead($notificationId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        return $stmt->execute();
    }
}
?>