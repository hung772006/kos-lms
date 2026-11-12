<?php
/**
 * Class AuthController: Xử lý Đăng nhập, Đăng ký, Đăng xuất, Hồ sơ cá nhân & Đổi mật khẩu
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * 1. Đăng nhập hệ thống cho cả 4 vai trò (Kiểm tra trạng thái khóa tài khoản)
     */
    public function login(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('index.php?controller=dashboard&action=index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $password   = (string) ($_POST['password'] ?? '');

            $user = $identifier !== '' ? $this->userModel->findByEmailOrPhone($identifier) : null;

            if ($user && ($user['MatKhau'] === $password || password_verify($password, (string) $user['MatKhau']))) {
                if (($user['TrangThai'] ?? 'active') !== 'active') {
                    Session::setFlash('error', 'Tài khoản của bạn hiện đang bị tạm khóa. Vui lòng liên hệ Bộ phận Quản lý!');
                } else {
                    $uid = (int) $user['MaNguoiDung'];
                    if (($user['VaiTro'] ?? '') === 'student') {
                        $this->userModel->updateLastLogin($uid);
                        $user = $this->userModel->findById($uid) ?? $user;
                    }

                    Session::setUser($user);
                    $this->redirect(
                        'index.php?controller=dashboard&action=index',
                        'success',
                        'Đăng nhập thành công! Chào mừng ' . $user['HoTen']
                    );
                }
            } else {
                Session::setFlash('error', 'Sai thông tin Email/SĐT hoặc mật khẩu!');
            }
        }

        $this->view('auth/login', [], false);
    }

    /**
     * 2. Đăng ký tài khoản Học viên mới (Tạo đồng thời NguoiDung + HocVien 1-1)
     */
    public function register(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('index.php?controller=dashboard&action=index');
        }

        $old = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hoTen    = trim((string) ($_POST['HoTen'] ?? ''));
            $email    = strtolower(trim((string) ($_POST['Email'] ?? '')));
            $sdt      = preg_replace('/\s+/', '', trim((string) ($_POST['SDT'] ?? ''))) ?? '';
            $ngaySinh = trim((string) ($_POST['NgaySinh'] ?? ''));
            $matKhau  = (string) ($_POST['MatKhau'] ?? '');
            $xacNhan  = (string) ($_POST['MatKhauXacNhan'] ?? '');

            $old = [
                'HoTen'    => $hoTen,
                'Email'    => $email,
                'SDT'      => $sdt,
                'NgaySinh' => $ngaySinh,
            ];

            if ($hoTen === '' || $email === '' || $sdt === '' || $matKhau === '') {
                Session::setFlash('error', 'Vui lòng điền đầy đủ các trường thông tin bắt buộc!');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Session::setFlash('error', 'Địa chỉ Email không đúng định dạng!');
            } elseif (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
                Session::setFlash('error', 'Số điện thoại phải gồm từ 9 đến 15 chữ số!');
            } elseif (mb_strlen($matKhau) < 6) {
                Session::setFlash('error', 'Mật khẩu phải có tối thiểu 6 ký tự!');
            } elseif ($matKhau !== $xacNhan) {
                Session::setFlash('error', 'Mật khẩu xác nhận không khớp!');
            } elseif ($this->userModel->existsEmail($email)) {
                Session::setFlash('error', 'Email này đã được sử dụng trên hệ thống!');
            } elseif ($this->userModel->existsPhone($sdt)) {
                Session::setFlash('error', 'Số điện thoại này đã được đăng ký!');
            } else {
                $newId   = $this->userModel->createStudent($hoTen, $email, $sdt, $matKhau, $ngaySinh ?: null);
                $newUser = $this->userModel->findById($newId);
                if ($newUser !== null) {
                    Session::setUser($newUser);
                    $this->redirect(
                        'index.php?controller=dashboard&action=index&view=courses',
                        'success',
                        'Đăng ký tài khoản thành công! Chào mừng ' . $hoTen . ' đến với KOS English Center.'
                    );
                }
                $this->redirect('index.php?controller=auth&action=login', 'success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            }
        }

        $this->view('auth/register', ['old' => $old], false);
    }

    /**
     * 3. Trang Hồ sơ cá nhân & Upload Ảnh đại diện (Dùng chung cho cả 4 vai trò)
     */
    public function profile(): void
    {
        $this->requireLogin();

        $sessionUser = Session::getUser();
        $userId      = (int) ($sessionUser['MaNguoiDung'] ?? 0);
        $user        = $this->userModel->findById($userId);

        if ($user === null) {
            Session::destroy();
            $this->redirect('index.php?controller=auth&action=login', 'error', 'Phiên đăng nhập không hợp lệ.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hoTen = trim((string) ($_POST['HoTen'] ?? ''));
            $sdt   = preg_replace('/\s+/', '', trim((string) ($_POST['SDT'] ?? ''))) ?? '';

            if ($hoTen === '' || $sdt === '') {
                $this->redirect('index.php?controller=auth&action=profile', 'error', 'Họ tên và Số điện thoại không được để trống!');
            }
            if (!preg_match('/^[0-9]{9,15}$/', $sdt)) {
                $this->redirect('index.php?controller=auth&action=profile', 'error', 'Số điện thoại phải gồm từ 9 đến 15 chữ số!');
            }
            if ($this->userModel->existsPhone($sdt, $userId)) {
                $this->redirect('index.php?controller=auth&action=profile', 'error', 'Số điện thoại này đã thuộc về tài khoản khác!');
            }

            // Xử lý Upload file Ảnh đại diện phía Client qua lớp FileUploader
            $avatarPath = null;
            if (isset($_FILES['AnhDaiDien']) && $_FILES['AnhDaiDien']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploader = new FileUploader('avatars', ['jpg', 'jpeg', 'png', 'webp'], 5);
                $upload   = $uploader->upload($_FILES['AnhDaiDien']);
                if (!$upload['success']) {
                    $this->redirect('index.php?controller=auth&action=profile', 'error', $upload['error']);
                }
                $avatarPath = $upload['path'];
            }

            $role  = (string) ($user['VaiTro'] ?? 'student');
            $extra = ['VaiTro' => $role];

            if ($role === 'student') {
                $extra['NgaySinh']  = trim((string) ($_POST['NgaySinh'] ?? '')) ?: null;
                $extra['GioiThieu'] = trim((string) ($_POST['GioiThieu'] ?? ''));
            } elseif ($role === 'teacher') {
                $extra['ChuyenMonGV']     = trim((string) ($_POST['ChuyenMonGV'] ?? ''));
                $extra['ChungChi']        = trim((string) ($_POST['ChungChi'] ?? ''));
                $extra['SoNamKinhNghiem'] = max(0, (int) ($_POST['SoNamKinhNghiem'] ?? 1));
                $extra['GioiThieuGV']     = trim((string) ($_POST['GioiThieuGV'] ?? ''));
            }

            $this->userModel->updateProfile($userId, $hoTen, $sdt, $avatarPath, $extra);

            // Làm mới ngay dữ liệu Session để Sidebar cập nhật tức thì Tên & Avatar mới
            $freshUser = $this->userModel->findById($userId);
            if ($freshUser !== null) {
                Session::setUser($freshUser);
            }

            $this->redirect('index.php?controller=auth&action=profile', 'success', 'Đã cập nhật hồ sơ cá nhân thành công!');
        }

        Session::setUser($user);
        $this->view('auth/profile', [
            'user'      => $user,
            'pageTitle' => 'Hồ sơ cá nhân',
        ]);
    }

    /**
     * 4. Xử lý Đổi mật khẩu trên trang Hồ sơ cá nhân
     */
    public function changePassword(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=auth&action=profile');
        }

        $sessionUser = Session::getUser();
        $userId      = (int) ($sessionUser['MaNguoiDung'] ?? 0);
        $user        = $this->userModel->findById($userId);

        $currentPass = (string) ($_POST['current_password'] ?? '');
        $newPass     = (string) ($_POST['new_password'] ?? '');
        $confirmPass = (string) ($_POST['confirm_password'] ?? '');

        if (!$user || !($user['MatKhau'] === $currentPass || password_verify($currentPass, (string) $user['MatKhau']))) {
            $this->redirect('index.php?controller=auth&action=profile', 'error', 'Mật khẩu hiện tại không chính xác!');
        }
        if (mb_strlen($newPass) < 6) {
            $this->redirect('index.php?controller=auth&action=profile', 'error', 'Mật khẩu mới phải có ít nhất 6 ký tự!');
        }
        if ($newPass !== $confirmPass) {
            $this->redirect('index.php?controller=auth&action=profile', 'error', 'Xác nhận mật khẩu mới không khớp!');
        }

        $this->userModel->updatePassword($userId, $newPass);
        $freshUser = $this->userModel->findById($userId);
        if ($freshUser !== null) {
            Session::setUser($freshUser);
        }

        $this->redirect('index.php?controller=auth&action=profile', 'success', 'Đổi mật khẩu thành công!');
    }

    /**
     * 5. Đăng xuất khỏi hệ thống
     */
    public function logout(): void
    {
        Session::destroy();
        $this->redirect('index.php?controller=auth&action=login', 'info', 'Đã đăng xuất khỏi hệ thống KOS English.');
    }
}