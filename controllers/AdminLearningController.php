<?php
/**
 * Class AdminLearningController: Quản lý hoạt động học tập phía Admin/Giáo viên
 * Phụ trách: Khánh (Nhánh feature/khanh-learning)
 */
class AdminLearningController extends BaseController
{
    private $learningModel;

    public function __construct()
    {
        // Lấy thông tin user đang đăng nhập
        $user = Session::getUser();
        
        // Nếu chưa đăng nhập HOẶC tài khoản là học viên -> Chặn và đá văng về trang chủ
        if (!$user || $user['VaiTro'] === 'student') {
            Session::setFlash([
                'type' => 'error', 
                'message' => 'Truy cập bị từ chối! Bạn không có quyền quản trị.'
            ]);
            header('Location: index.php?controller=dashboard&action=index');
            exit();
        }

        // Khởi tạo model đúng chuẩn bên trong hàm khởi tạo
        $this->learningModel = new LearningModel();
    }

    // 1. Hiển thị danh sách hoạt động học tập (Có phân trang & tìm kiếm)
    public function index()
    {
        $keyword = $_GET['search'] ?? '';
        $page = $_GET['page'] ?? 1;
        $limit = 5; // Số dòng trên 1 trang
        $offset = ($page - 1) * $limit; // Đã fix chuẩn công thức phân trang

        // Lấy danh sách kết hợp tìm kiếm và phân trang
        $activities = $this->learningModel->getActivitiesPaginated($keyword, $offset, $limit);
        $totalRows = $this->learningModel->countActivities($keyword);
        $totalPages = ceil($totalRows / $limit);

        // Gọi View hiển thị
        $this->view('admin/learning/index', [
            'activities' => $activities,
            'keyword' => $keyword,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    // 2. Xử lý Thêm mới hoạt động học tập (Đã đồng bộ bắt course_id để điều hướng chính xác)
    public function create()
    {
        $courseId = $_GET['course_id'] ?? $_POST['course_id'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'MaChuong' => $_POST['MaChuong'] ?? 1,
                'TenHoatDong' => $_POST['TenHoatDong'] ?? '',
                'Loai' => $_POST['Loai'] ?? 'video',
                'NoiDung' => $_POST['NoiDung'] ?? null,
                'LinkVideo' => $_POST['LinkVideo'] ?? null,
                'DiemToiDa' => $_POST['DiemToiDa'] ?? 10.0,
                'HanNop' => !empty($_POST['HanNop']) ? $_POST['HanNop'] : null,
                'ThuTu' => $_POST['ThuTu'] ?? 1,
                'TrangThai' => $_POST['TrangThai'] ?? 'published',
                'DuLieuQuizJSON' => $_POST['DuLieuQuizJSON'] ?? null
            ];

            // Xử lý upload file đính kèm trực tiếp an toàn (PDF, ZIP, PPTX)
            if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] === UPLOAD_ERR_OK) {
                $targetDir = 'uploads/materials/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['FileDinhKem']['name']);
                $targetFilePath = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['FileDinhKem']['tmp_name'], $targetFilePath)) {
                    $data['FileDinhKem'] = $targetFilePath;
                    $data['DinhDangFile'] = strtoupper(pathinfo($_FILES['FileDinhKem']['name'], PATHINFO_EXTENSION));
                    $data['DungLuongFile'] = round($_FILES['FileDinhKem']['size'] / (1024 * 1024), 1) . ' MB'; // Đã fix dấu nhân
                }
            }

            // Nếu loại là quiz, lưu chuỗi JSON đáp án
            if ($data['Loai'] === 'quiz' && isset($_POST['DuLieuQuizJSON'])) {
                $data['DuLieuQuizJSON'] = $_POST['DuLieuQuizJSON'];
            }

            // Gọi Model để thêm vào CSDL
            $this->learningModel->insertActivity($data);
            
            // Chuyển hướng thông minh về đúng khóa học đang quản lý nếu có course_id
            header('Location: index.php?controller=adminLearning&action=index&course_id=' . $courseId);
            exit();
        }

        // Lấy danh sách chương học để hiển thị dropdown chọn MaChuong
        $chapters = $this->learningModel->getAllChapters();
        $this->view('admin/learning/create', [
            'chapters' => $chapters,
            'courseId' => $courseId
        ]);
    }

    // 3. Xử lý Chỉnh sửa hoạt động học tập
    public function edit()
    {
        $id = $_GET['id'] ?? 0;
        $courseId = $_GET['course_id'] ?? 1;
        $activity = $this->learningModel->getById($id);
        
        if (!$activity) {
            header('Location: index.php?controller=adminLearning&action=index&course_id=' . $courseId);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'MaChuong' => $_POST['MaChuong'] ?? $activity['MaChuong'],
                'TenHoatDong' => $_POST['TenHoatDong'] ?? $activity['TenHoatDong'],
                'Loai' => $_POST['Loai'] ?? $activity['Loai'],
                'LinkVideo' => $_POST['LinkVideo'] ?? $activity['LinkVideo'],
                'HanNop' => !empty($_POST['HanNop']) ? $_POST['HanNop'] : null
            ];

            // Nếu có upload file mới
            if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] === UPLOAD_ERR_OK) {
                $targetDir = 'uploads/materials/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['FileDinhKem']['name']);
                $targetFilePath = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['FileDinhKem']['tmp_name'], $targetFilePath)) {
                    $data['FileDinhKem'] = $targetFilePath;
                    $data['DinhDangFile'] = strtoupper(pathinfo($_FILES['FileDinhKem']['name'], PATHINFO_EXTENSION));
                    $data['DungLuongFile'] = round($_FILES['FileDinhKem']['size'] / (1024 * 1024), 1) . ' MB';
                }
            }

            // Gọi Model cập nhật CSDL
            $this->learningModel->updateActivity($id, $data);
            header('Location: index.php?controller=adminLearning&action=index&course_id=' . $courseId);
            exit();
        }

        $chapters = $this->learningModel->getAllChapters();
        $this->view('admin/learning/edit', [
            'activity' => $activity,
            'chapters' => $chapters,
            'courseId' => $courseId
        ]);
    }

    // 4. Xử lý Xóa hoạt động học tập
    public function delete()
    {
        $id = $_GET['id'] ?? 0;
        $courseId = $_GET['course_id'] ?? 1;
        if ($id > 0) {
            $this->learningModel->deleteActivity($id);
        }
        header('Location: index.php?controller=adminLearning&action=index&course_id=' . $courseId);
        exit();
    }
}