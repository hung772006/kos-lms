<?php
/**
 * Class AuthController: Xử lý Đăng nhập, Đăng ký, Đăng xuất và Hồ sơ cá nhân
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('index.php?controller=dashboard&action=index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';

            $user = $this->userModel->findByEmailOrPhone($identifier);
            if ($user && ($user['MatKhau'] === $password || password_verify($password, $user['MatKhau']))) {
                Session::setUser($user);
                $this->redirect('index.php?controller=dashboard&action=index', 'success', 'Đăng nhập thành công! Chào mừng ' . $user['HoTen']);
            } else {
                Session::setFlash('error', 'Sai thông tin Email/SĐT hoặc mật khẩu!');
            }
        }

        $this->view('auth/login', [], false);
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('index.php?controller=auth&action=login', 'info', 'Đã đăng xuất khỏi hệ thống KOS English.');
    }
}
