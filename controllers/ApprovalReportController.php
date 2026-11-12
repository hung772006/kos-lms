<?php
/**
 * Class ApprovalReportController: Xử lý Phê duyệt khóa học, Thống kê và Thông báo
 * Người phụ trách: Hiếu
 */
class ApprovalReportController extends BaseController
{
    private ApprovalReportModel $model;

    public function __construct()
    {
        $this->model = new ApprovalReportModel();
    }

    // 1. Giao diện Phê duyệt khóa học (Dành cho Bộ phận đào tạo - training)
    public function index(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        if (($user['VaiTro'] ?? '') !== 'training') {
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }

        $keyword = trim($_GET['q'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $limit   = 10;

        $totalPending = $this->model->countPendingCourses($keyword);
        $pagination   = new Pagination($totalPending, $limit, $page);
        $pendingCourses = $this->model->getPendingCoursesPaginated($keyword, $limit, $pagination->getOffset());

        $this->view('admin/duyet_khoahoc', [
            'user'           => $user,
            'pendingCourses' => $pendingCourses,
            'keyword'        => $keyword,
            'pagination'     => $pagination,
        ]);
    }

    // 2. Xử lý logic Duyệt / Không duyệt
    public function handleApproval(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            $status   = trim($_POST['status'] ?? '');
            $lyDo     = trim($_POST['ly_do'] ?? '');

            if ($courseId > 0 && in_array($status, ['approved', 'rejected'])) {
                $result = $this->model->updateCourseApprovalStatus($courseId, $user['MaNguoiDung'], $status, $lyDo);
                if ($result) {
                    header('Location: index.php?controller=approvalreport&action=index&status=success');
                    exit;
                }
            }
        }
        header('Location: index.php?controller=approvalreport&action=index&status=error');
        exit;
    }

    // 3. Giao diện Báo cáo thống kê (Dành cho Quản lý - manager)
    public function reports(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        if (($user['VaiTro'] ?? '') !== 'manager') {
            header('Location: index.php?controller=dashboard&action=index');
            exit;
        }

        $overviewStats = $this->model->getOverviewStats();
        $courseStats   = $this->model->getCourseStatistics();

        $this->view('admin/baocao_thongke', [
            'user'          => $user,
            'overviewStats' => $overviewStats,
            'courseStats'   => $courseStats,
        ]);
    }

    // 4. Giao diện Thông báo cá nhân
    public function notifications(): void
    {
        $this->requireLogin();
        $user = Session::getUser();

        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 10;

        $totalNotifs = $this->model->countNotificationsByUser($user['MaNguoiDung']);
        $pagination  = new Pagination($totalNotifs, $limit, $page);
        $notifications = $this->model->getNotificationsByUser($user['MaNguoiDung'], $limit, $pagination->getOffset());

        $this->view('admin/thongbao', [
            'user'          => $user,
            'notifications' => $notifications,
            'pagination'    => $pagination,
        ]);
    }
}