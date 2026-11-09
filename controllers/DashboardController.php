<?php
/**
 * Class DashboardController: Điều phối màn hình chính sau khi đăng nhập cho các vai trò
 * Người phụ trách: Nguyễn Thành Hưng (Khung chính) + Hiếu (Module Phê duyệt & Báo cáo)
 */

class DashboardController extends BaseController
{
    private CourseModel $courseModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
        $this->userModel   = new UserModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $user    = Session::getUser();
        $keyword = trim($_GET['q'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));

        $totalCourses = $this->courseModel->countCourses($keyword, $user['VaiTro'] === 'student');
        $pagination   = new Pagination($totalCourses, ITEMS_PER_PAGE, $page);
        $courses      = $this->courseModel->getCoursesPaginated(
            $keyword,
            $pagination->getLimit(),
            $pagination->getOffset(),
            $user['VaiTro'] === 'student'
        );

        $this->view('dashboard/index', [
            'user'       => $user,
            'courses'    => $courses,
            'keyword'    => $keyword,
            'pagination' => $pagination,
        ]);
    }

    // --- MODULE PHÊ DUYỆT KHÓA HỌC (Bộ phận đào tạo - training) ---
    public function approvalcourses(): void
    {
        $this->requireLogin();
        $user = Session::getUser();
        
        // Phân quyền: Chặn nếu không phải Bộ phận đào tạo
        if (($user['VaiTro'] ?? '') !== 'training') {
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }

        $keyword = trim($_GET['q'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $limit   = 10;

        $approvalModel = new ApprovalReportModel();

        $totalPending = $approvalModel->countPendingCourses($keyword);
        $pagination   = new Pagination($totalPending, $limit, $page);
        $pendingCourses = $approvalModel->getPendingCoursesPaginated($keyword, $limit, $pagination->getOffset());

        $this->view('admin/duyet_khoahoc', [
            'user'           => $user,
            'pendingCourses' => $pendingCourses,
            'keyword'        => $keyword,
            'pagination'     => $pagination,
        ]);
    }

    // Xử lý Phê duyệt / Không duyệt
    public function handleApproval(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            $status   = trim($_POST['status'] ?? '');
            $lyDo     = trim($_POST['ly_do'] ?? '');

            if ($courseId > 0 && in_array($status, ['approved', 'rejected'])) {
                $approvalModel = new ApprovalReportModel();
                $result = $approvalModel->updateCourseApprovalStatus($courseId, $user['MaNguoiDung'], $status, $lyDo);
                
                if ($result) {
                    header('Location: index.php?controller=dashboard&action=approvalcourses&status=success');
                    exit;
                }
            }
        }
        header('Location: index.php?controller=dashboard&action=approvalcourses&status=error');
        exit;
    }

    // --- MODULE BÁO CÁO THỐNG KÊ (Bộ phận quản lý - manager) ---
    public function reports(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        // Phân quyền: Chặn nếu không phải Bộ phận quản lý
        if (($user['VaiTro'] ?? '') !== 'manager') {
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }

        $approvalModel = new ApprovalReportModel();
        $overviewStats = $approvalModel->getOverviewStats();
        $courseStats   = $approvalModel->getCourseStatistics();

        $this->view('admin/baocao_thongke', [
            'user'          => $user,
            'overviewStats' => $overviewStats,
            'courseStats'   => $courseStats,
        ]);
    }
}