<?php
/**
 * Class ThongBaoController: Điều khiển các chức năng Thông báo & Cài đặt
 * Phụ trách: Hiếu
 */

require_once __DIR__ . '/../models/ThongBaoModel.php';

class ThongBaoController extends BaseController
{
    private ThongBaoModel $thongBaoModel;

    public function __construct()
    {
        $this->thongBaoModel = new ThongBaoModel();
    }

    /**
     * Hiển thị danh sách thông báo kèm phân trang
     */
    public function index(): void
    {
        $this->requireLogin();
        $user = Session::getUser();
        $maNguoiNhan = $user['MaNguoiDung']; // Lấy ID user đang đăng nhập

        // Xử lý phân trang
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $itemsPerPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 6;
        
        $totalItems = $this->thongBaoModel->countNotificationsByUser($maNguoiNhan);
        $pagination = new Pagination($totalItems, $itemsPerPage, $currentPage);
        
        $notifications = $this->thongBaoModel->getNotificationsByUser(
            $maNguoiNhan, 
            $pagination->getLimit(), 
            $pagination->getOffset()
        );

        $baseUrl = 'index.php?controller=thongbao&action=index';
        $paginationHtml = $pagination->render($baseUrl);

        $this->view('thongbao/index', [
            'notifications' => $notifications,
            'paginationHtml' => $paginationHtml,
            'user' => $user
        ]);
    }

    /**
     * Xử lý lưu cài đặt thông báo của Giáo viên (Form Modal)
     */
    public function saveSettings(): void
    {
        $this->requireRoles(['GiaoVien']); // Chỉ giáo viên mới có cài đặt này
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lấy các tùy chọn cấu hình từ form modal gửi lên
            // Ví dụ lưu trạng thái nhận email/thông báo qua bảng GiaoVien hoặc Session tuỳ thiết kế nhóm
            $nhanThongBaoBaiNop = isset($_POST['nhan_thong_bao_bai_nop']) ? 1 : 0;
            $nhanThongBaoDuyetKhoaHoc = isset($_POST['nhan_thong_bao_duyet']) ? 1 : 0;

            // Cập nhật vào DB (giả sử bảng giao_vien hoặc nguoi_dung có cột lưu cài đặt)
            // Hoặc lưu tạm thông báo thành công:
            $this->redirect('index.php?controller=thongbao&action=index', 'success', 'Cập nhật cài đặt thông báo thành công!');
        }
    }
}