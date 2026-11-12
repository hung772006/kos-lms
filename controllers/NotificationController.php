<?php
require_once 'models/NotificationModel.php';

class NotificationController extends BaseController {
    private $notificationModel;

    public function __construct($db) {
        $this->notificationModel = new NotificationModel($db);
    }

    // Hiển thị trang danh sách thông báo
    public function index() {
        // Giả sử cậu lưu user đang đăng nhập trong Session
        $userId = $_SESSION['user_id'] ?? 1; 
        $notifications = $this->notificationModel->getNotificationsByUserId($userId);

        $this->view('notifications/index', [
            'notifications' => $notifications
        ]);
    }

    // Xử lý AJAX đánh dấu đã đọc
    public function markRead() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $result = $this->notificationModel->markAsRead($id);
            echo json_encode(['success' => $result]);
            exit;
        }
    }
}
?>