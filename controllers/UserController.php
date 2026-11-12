<?php
/**
 * Class UserController: Quản trị danh sách người dùng, Phân quyền, Khóa tài khoản & Hồ sơ 1-1
 * Dành cho Bộ phận Quản lý (manager)
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->requireRoles(['manager']);
        $this->userModel = new UserModel();
    }

    /**
     * 1. Danh sách người dùng kèm Thống kê, Tìm kiếm, Lọc vai trò/trạng thái & Phân trang
     */
    public function index(): void
    {
        $currentUser = Session::getUser();
        $keyword     = trim((string) ($_GET['q'] ?? ''));
        $role        = trim((string) ($_GET['role'] ?? 'all'));
        $status      = trim((string) ($_GET['status'] ?? 'all'));
        $page        = max(1, (int) ($_GET['page'] ?? 1));
        $editId      = max(0, (int) ($_GET['edit'] ?? 0));

        if (!in_array($role, ['all', 'student', 'teacher', 'training', 'manager'], true)) {
            $role = 'all';
        }
        if (!in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $stats      = $this->userModel->getUserStats();
        $totalUsers = $this->userModel->countUsers($keyword, $role, $status);
        $pagination = new Pagination($totalUsers, ITEMS_PER_PAGE, $page);
        $users      = $this->userModel->getUsersPaginated(
            $keyword,
            $role,
            $pagination->getLimit(),
            $pagination->getOffset(),
            $status
        );

        $editUser = $editId > 0 ? $this->userModel->findById($editId) : null;

        $this->view('admin/users/index', [
            'user'       => $currentUser,
            'users'      => $users,
            'stats'      => $stats,
            'totalUsers' => $totalUsers,
            'keyword'    => $keyword,
            'role'       => $role,
            'status'     => $status,
            'editUser'   => $editUser,
            'pagination' => $pagination,
            'pageTitle'  => 'Quản lý người dùng',
        ]);
    }

    /**
     * 2. Thêm mới hoặc Cập nhật người dùng (Hỗ trợ Upload Avatar Admin & Dữ liệu 1-1)
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=user&action=index');
        }

        $currentUser = Session::getUser();
        $currentId   = (int) ($currentUser['MaNguoiDung'] ?? 0);

        $userId    = max(0, (int) ($_POST['user_id'] ?? 0));
        $hoTen     = trim((string) ($_POST['HoTen'] ?? ''));
        $email     = strtolower(trim((string) ($_POST['Email'] ?? '')));
        $sdt       = preg_replace('/\s+/', '', trim((string) ($_POST['SDT'] ?? ''))) ?? '';
        $vaiTro    = trim((string) ($_POST['VaiTro'] ?? 'student'));
        $trangThai = trim((string) ($_POST['TrangThai'] ?? 'active'));
        $matKhau   = (string) ($_POST['MatKhau'] ?? '');

        $backUrl = $userId > 0
            ? 'index.php?controller=user&action=index&edit=' . $userId
            : 'index.php?controller=user&action=index';

        if ($hoTen === '' || $email === '' || $sdt === '') {
            $this->redirect($backUrl, 'error', 'Vui lòng nhập đầy đủ Họ tên, Email và Số điện thoại!');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect($backUrl, 'error', 'Địa chỉ Email không đúng định dạng!');
        }
        if (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
            $this->redirect($backUrl, 'error', 'Số điện thoại phải gồm từ 9 đến 15 chữ số!');
        }
        if ($userId === 0 && mb_strlen($matKhau) < 6) {
            $this->redirect($backUrl, 'error', 'Mật khẩu khởi tạo cho tài khoản mới phải có ít nhất 6 ký tự!');
        }
        if ($userId > 0 && $matKhau !== '' && mb_strlen($matKhau) < 6) {
            $this->redirect($backUrl, 'error', 'Mật khẩu cập nhật mới phải có ít nhất 6 ký tự!');
        }
        if ($this->userModel->existsEmail($email, $userId)) {
            $this->redirect($backUrl, 'error', 'Địa chỉ Email này đã tồn tại trên hệ thống!');
        }
        if ($this->userModel->existsPhone($sdt, $userId)) {
            $this->redirect($backUrl, 'error', 'Số điện thoại này đã tồn tại trên hệ thống!');
        }

        // Bảo vệ tài khoản Quản lý đang đăng nhập không tự hạ quyền hoặc tự khóa chính mình
        if ($userId === $currentId) {
            $vaiTro    = 'manager';
            $trangThai = 'active';
        }

        $avatarPath = null;
        if (isset($_FILES['AnhDaiDien']) && $_FILES['AnhDaiDien']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploader = new FileUploader('avatars', ['jpg', 'jpeg', 'png', 'webp'], 5);
            $upload   = $uploader->upload($_FILES['AnhDaiDien']);
            if (!$upload['success']) {
                $this->redirect($backUrl, 'error', $upload['error']);
            }
            $avatarPath = $upload['path'];
        }

        $payload = [
            'HoTen'           => $hoTen,
            'Email'           => $email,
            'SDT'             => $sdt,
            'VaiTro'          => $vaiTro,
            'TrangThai'       => $trangThai,
            'MatKhau'         => $matKhau,
            'AnhDaiDien'      => $avatarPath,
            'NgaySinh'        => trim((string) ($_POST['NgaySinh'] ?? '')) ?: null,
            'GioiThieu'       => trim((string) ($_POST['GioiThieu'] ?? '')),
            'ChuyenMonGV'     => trim((string) ($_POST['ChuyenMonGV'] ?? '')),
            'ChungChi'        => trim((string) ($_POST['ChungChi'] ?? '')),
            'SoNamKinhNghiem' => max(0, (int) ($_POST['SoNamKinhNghiem'] ?? 1)),
            'GioiThieuGV'     => trim((string) ($_POST['GioiThieuGV'] ?? '')),
        ];

        if ($userId > 0) {
            $this->userModel->updateUserByAdmin($userId, $payload);
            if ($userId === $currentId) {
                $fresh = $this->userModel->findById($currentId);
                if ($fresh !== null) {
                    Session::setUser($fresh);
                }
            }
            $this->redirect('index.php?controller=user&action=index', 'success', 'Đã cập nhật thông tin tài khoản #' . $userId . ' thành công!');
        }

        $newId = $this->userModel->createUserByAdmin($payload);
        $this->redirect('index.php?controller=user&action=index', 'success', 'Đã tạo mới tài khoản "' . $hoTen . '" (#' . $newId . ') thành công!');
    }

    /**
     * 3. Đổi nhanh Vai trò (Phân quyền trực tiếp từ bảng danh sách)
     */
    public function changeRole(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=user&action=index');
        }

        $currentId = (int) (Session::getUser()['MaNguoiDung'] ?? 0);
        $targetId  = max(0, (int) ($_POST['user_id'] ?? 0));
        $newRole   = trim((string) ($_POST['new_role'] ?? ''));

        if ($targetId < 1 || !in_array($newRole, ['student', 'teacher', 'training', 'manager'], true)) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Dữ liệu phân quyền không hợp lệ!');
        }
        if ($targetId === $currentId) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Bạn không thể tự thay đổi vai trò của chính mình!');
        }

        $this->userModel->updateRole($targetId, $newRole);
        $this->redirect('index.php?controller=user&action=index', 'success', 'Đã cập nhật phân quyền người dùng thành công!');
    }

    /**
     * 4. Khóa / Mở khóa nhanh trạng thái hoạt động của tài khoản
     */
    public function toggleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=user&action=index');
        }

        $currentId = (int) (Session::getUser()['MaNguoiDung'] ?? 0);
        $targetId  = max(0, (int) ($_POST['user_id'] ?? 0));

        if ($targetId < 1) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Không tìm thấy tài khoản cần thao tác!');
        }
        if ($targetId === $currentId) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Bạn không thể tự khóa tài khoản đang đăng nhập!');
        }

        $next = $this->userModel->toggleStatus($targetId);
        if ($next === null) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Tài khoản không tồn tại!');
        }

        $msg = ($next === 'active')
            ? 'Đã mở khóa tài khoản #' . $targetId . ' thành công!'
            : 'Đã tạm khóa tài khoản #' . $targetId . '!';

        $this->redirect('index.php?controller=user&action=index', 'success', $msg);
    }

    /**
     * 5. Xóa tài khoản người dùng an toàn (Kiểm tra ràng buộc khóa ngoại KhoaHoc trước khi xóa)
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=user&action=index');
        }

        $currentId = (int) (Session::getUser()['MaNguoiDung'] ?? 0);
        $targetId  = max(0, (int) ($_POST['user_id'] ?? 0));

        if ($targetId < 1) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Tài khoản cần xóa không hợp lệ!');
        }
        if ($targetId === $currentId) {
            $this->redirect('index.php?controller=user&action=index', 'error', 'Không thể xóa tài khoản Quản lý đang đăng nhập!');
        }

        $ownedCourses = $this->userModel->countTeacherCourses($targetId);
        if ($ownedCourses > 0) {
            $this->redirect(
                'index.php?controller=user&action=index',
                'error',
                'Giảng viên này đang đứng lớp ' . $ownedCourses . ' khóa học nên không thể xóa trực tiếp. Vui lòng tạm khóa tài khoản hoặc chuyển quyền khóa học trước!'
            );
        }

        try {
            $this->userModel->deleteUser($targetId);
            $this->redirect('index.php?controller=user&action=index', 'success', 'Đã xóa vĩnh viễn tài khoản #' . $targetId . ' khỏi hệ thống!');
        } catch (Throwable) {
            $this->redirect(
                'index.php?controller=user&action=index',
                'error',
                'Tài khoản đang có dữ liệu liên kết trên hệ thống nên không thể xóa. Vui lòng sử dụng chức năng Tạm khóa!'
            );
        }
    }
}