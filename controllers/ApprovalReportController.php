<?php
/**
 * Class ApprovalReportController: Điều phối Phê duyệt khóa học, Báo cáo thống kê & Hệ thống Thông báo
 * Người phụ trách: Nguyễn Trung Hiếu (Nhánh feature/hieu-approval-reports)
 */

class ApprovalReportController extends BaseController
{
    private ApprovalReportModel $model;

    public function __construct()
    {
        $this->requireLogin();
        $this->model = new ApprovalReportModel();
    }

    /**
     * Điều hướng thông minh theo vai trò nếu truy cập action=index
     */
    public function index(): void
    {
        $user = Session::getUser() ?? [];
        $role = $user['VaiTro'] ?? 'student';

        if ($role === 'training') {
            $this->approvals();
            return;
        }
        if ($role === 'manager') {
            $this->reports();
            return;
        }
        $this->notifications();
    }

    /**
     * 1. Giao diện Phê duyệt khóa học (Dành cho Bộ phận Quản lý đào tạo - training)
     */
    public function approvals(): void
    {
        $this->requireRoles(['training']);

        $user    = Session::getUser() ?? [];
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $limit   = defined('ITEMS_PER_PAGE') ? (int) ITEMS_PER_PAGE : 10;

        $totalPending   = $this->model->countPendingCourses($keyword);
        $pagination     = new Pagination($totalPending, $limit, $page);
        $pendingCourses = $this->model->getPendingCoursesPaginated(
            $keyword,
            $pagination->getLimit(),
            $pagination->getOffset()
        );
        $recentLogs = $this->model->getRecentApprovalLogs(6);

        $this->view('admin/duyet_khoahoc', [
            'user'           => $user,
            'pendingCourses' => $pendingCourses,
            'totalPending'   => $totalPending,
            'recentLogs'     => $recentLogs,
            'keyword'        => $keyword,
            'pagination'     => $pagination,
            'pageTitle'      => 'Phê duyệt khóa học',
        ]);
    }

    /**
     * 2. Xử lý hành động Duyệt / Từ chối khóa học & Gửi thông báo cho Giáo viên
     */
    public function handleApproval(): void
    {
        $this->requireRoles(['training']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=approvalreport&action=approvals');
        }

        $user     = Session::getUser() ?? [];
        $adminId  = (int) ($user['MaNguoiDung'] ?? 0);
        $courseId = max(0, (int) ($_POST['course_id'] ?? 0));
        $status   = trim((string) ($_POST['status'] ?? ''));
        $lyDo     = trim((string) ($_POST['ly_do'] ?? ''));

        if ($courseId < 1 || !in_array($status, ['approved', 'rejected'], true)) {
            $this->redirect(
                'index.php?controller=approvalreport&action=approvals',
                'error',
                'Thông tin xét duyệt khóa học không hợp lệ!'
            );
        }

        if ($status === 'rejected' && $lyDo === '') {
            $this->redirect(
                'index.php?controller=approvalreport&action=approvals',
                'error',
                'Vui lòng nhập lý do cần chỉnh sửa trước khi từ chối khóa học!'
            );
        }

        if ($status === 'approved' && $lyDo === '') {
            $lyDo = 'Đạt chuẩn chất lượng đào tạo KOS English Center.';
        }

        $ok = $this->model->updateCourseApprovalStatus($courseId, $adminId, $status, $lyDo);
        if (!$ok) {
            $this->redirect(
                'index.php?controller=approvalreport&action=approvals',
                'error',
                'Không thể cập nhật trạng thái duyệt cho khóa học này.'
            );
        }

        $msg = ($status === 'approved')
            ? 'Đã phê duyệt khóa học và gửi thông báo tới giáo viên phụ trách!'
            : 'Đã từ chối khóa học và gửi yêu cầu chỉnh sửa tới giáo viên!';

        $this->redirect('index.php?controller=approvalreport&action=approvals', 'success', $msg);
    }

    /**
     * 3. Giao diện Báo cáo & Thống kê hệ thống (Dành cho Bộ phận Quản lý - manager)
     */
    public function reports(): void
    {
        $this->requireRoles(['manager']);

        $user          = Session::getUser() ?? [];
        $keyword       = trim((string) ($_GET['q'] ?? ''));
        $overviewStats = $this->model->getOverviewStats();
        $courseStats   = $this->model->getCourseStatistics($keyword);

        $this->view('admin/baocao_thongke', [
            'user'          => $user,
            'keyword'       => $keyword,
            'overviewStats' => $overviewStats,
            'courseStats'   => $courseStats,
            'pageTitle'     => 'Báo cáo & Thống kê',
        ]);
    }

    /**
     * 4. Xuất file báo cáo thống kê ra định dạng Excel (.CSV chuẩn UTF-8 BOM)
     */
    public function exportCsv(): void
    {
        $this->requireRoles(['manager']);

        $keyword       = trim((string) ($_GET['q'] ?? ''));
        $overviewStats = $this->model->getOverviewStats();
        $courseStats   = $this->model->getCourseStatistics($keyword);

        $filename = 'KOS_BaoCao_ThongKe_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        // Ghi UTF-8 BOM để Microsoft Excel đọc tiếng Việt có dấu không bị lỗi font
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['BÁO CÁO THỐNG KÊ HỆ THỐNG ĐÀO TẠO — KOS ENGLISH CENTER']);
        fputcsv($out, ['Thời gian xuất báo cáo:', date('d/m/Y H:i:s')]);
        fputcsv($out, []);

        fputcsv($out, ['CHỈ SỐ TỔNG QUAN HỆ THỐNG', 'GIÁ TRỊ']);
        fputcsv($out, ['Tổng khóa học đã duyệt', $overviewStats['total_courses']]);
        fputcsv($out, ['Khóa học đang chờ duyệt', $overviewStats['pending_courses']]);
        fputcsv($out, ['Tổng số học viên', $overviewStats['total_students']]);
        fputcsv($out, ['Tổng số giảng viên', $overviewStats['total_teachers']]);
        fputcsv($out, ['Tổng lượt đăng ký học', $overviewStats['total_enrollments']]);
        fputcsv($out, ['Tiến độ hoàn thành trung bình (%)', $overviewStats['avg_completion'] . '%']);
        fputcsv($out, ['Tổng doanh thu hệ thống (VNĐ)', number_format($overviewStats['total_revenue'], 0, ',', '.')]);
        fputcsv($out, []);

        fputcsv($out, ['MÃ KH', 'TÊN KHÓA HỌC', 'DANH MỤC', 'GIẢNG VIÊN', 'HỌC PHÍ (VNĐ)', 'SỐ HỌC VIÊN', 'TIẾN ĐỘ TB (%)', 'DOANH THU (VNĐ)']);
        foreach ($courseStats as $row) {
            fputcsv($out, [
                '#' . $row['MaKhoaHoc'],
                $row['TenKhoaHoc'],
                $row['DanhMuc'],
                $row['TenGiaoVien'],
                (float) $row['Gia'],
                (int) $row['SoHocVien'],
                (float) $row['TienDoTB'] . '%',
                (float) $row['DoanhThu'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * 5. Màn hình Hệ thống Thông báo chung cho cả 4 vai trò (Có lọc & Phân trang)
     */
    public function notifications(): void
    {
        $user      = Session::getUser() ?? [];
        $userId    = (int) ($user['MaNguoiDung'] ?? 0);
        $rawFilter = trim((string) ($_GET['filter'] ?? 'all'));
        $filter    = in_array($rawFilter, ['all', 'unread'], true) ? $rawFilter : 'all';
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $limit     = defined('ITEMS_PER_PAGE') ? (int) ITEMS_PER_PAGE : 10;

        $totalItems    = $this->model->countNotificationsByUser($userId, $filter);
        $unreadCount   = $this->model->countUnreadByUser($userId);
        $pagination    = new Pagination($totalItems, $limit, $page);
        $notifications = $this->model->getNotificationsByUser(
            $userId,
            $pagination->getLimit(),
            $pagination->getOffset(),
            $filter
        );

        $notifSettings = (($user['VaiTro'] ?? '') === 'teacher')
            ? $this->model->getNotificationSettings($userId)
            : [];

        $this->view('thongbao/index', [
            'user'          => $user,
            'notifications' => $notifications,
            'filter'        => $filter,
            'totalItems'    => $totalItems,
            'unreadCount'   => $unreadCount,
            'pagination'    => $pagination,
            'notifSettings' => $notifSettings,
            'pageTitle'     => 'Thông báo hệ thống',
        ]);
    }

    /**
     * 6. Đánh dấu đã đọc 1 thông báo
     */
    public function markRead(): void
    {
        $user   = Session::getUser() ?? [];
        $userId = (int) ($user['MaNguoiDung'] ?? 0);
        $id     = max(0, (int) ($_POST['id'] ?? $_GET['id'] ?? 0));

        if ($id > 0) {
            $this->model->markAsRead($id, $userId);
        }

        $this->redirect('index.php?controller=approvalreport&action=notifications', 'success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    /**
     * 7. Đánh dấu đã đọc tất cả thông báo
     */
    public function markAllRead(): void
    {
        $user   = Session::getUser() ?? [];
        $userId = (int) ($user['MaNguoiDung'] ?? 0);
        $this->model->markAllAsRead($userId);

        $this->redirect('index.php?controller=approvalreport&action=notifications', 'success', 'Đã đánh dấu đọc toàn bộ thông báo.');
    }

    /**
     * 8. Lưu cài đặt thông báo dành riêng cho Giáo viên
     */
    public function saveNotificationSettings(): void
    {
        $this->requireRoles(['teacher']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user   = Session::getUser() ?? [];
            $userId = (int) ($user['MaNguoiDung'] ?? 0);

            $this->model->saveNotificationSettings($userId, [
                'submission' => isset($_POST['nhan_thong_bao_bai_nop']) ? 1 : 0,
                'approval'   => isset($_POST['nhan_thong_bao_duyet']) ? 1 : 0,
                'email'      => isset($_POST['nhan_thong_bao_email']) ? 1 : 0,
            ]);

            $this->redirect(
                'index.php?controller=approvalreport&action=notifications',
                'success',
                'Đã cập nhật cài đặt nhận thông báo thành công!'
            );
        }

        $this->redirect('index.php?controller=approvalreport&action=notifications');
    }
}