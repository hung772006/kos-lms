<?php
/**
 * Class BaseController: Lớp điều khiển cơ sở giúp render View, chuyển hướng và kiểm tra phân quyền
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

abstract class BaseController
{
    /**
     * Hiển thị giao diện View kèm dữ liệu
     */
    protected function view(string $viewPath, array $data = [], bool $useLayout = true): void
    {
        extract($data);
        $fullViewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($fullViewFile)) {
            die("Không tìm thấy file View: {$viewPath}.php");
        }

        if ($useLayout) {
            require __DIR__ . '/../views/layouts/header.php';
            require $fullViewFile;
            require __DIR__ . '/../views/layouts/footer.php';
        } else {
            require $fullViewFile;
        }
    }

    /**
     * Chuyển hướng trang kèm thông báo Toast
     */
    protected function redirect(string $url, string $type = '', string $message = ''): void
    {
        if ($message !== '') {
            Session::setFlash($type, $message);
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Bắt buộc đăng nhập mới được truy cập
     */
    protected function requireLogin(): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('index.php?controller=auth&action=login', 'error', 'Vui lòng đăng nhập để tiếp tục!');
        }
    }

    /**
     * Kiểm tra phân quyền theo danh sách vai trò cho phép
     */
    protected function requireRoles(array $allowedRoles): void
    {
        $this->requireLogin();
        $user = Session::getUser();
        if (!in_array($user['VaiTro'], $allowedRoles, true)) {
            $this->redirect('index.php?controller=dashboard&action=index', 'error', 'Bạn không có quyền truy cập chức năng này!');
        }
    }
}
