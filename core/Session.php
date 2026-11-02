<?php
/**
 * Class Session: Quản lý phiên đăng nhập, phân quyền và thông báo Flash
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function setUser(array $user): void
    {
        $_SESSION['kos_user'] = $user;
    }

    public static function getUser(): ?array
    {
        return $_SESSION['kos_user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['kos_user']);
    }

    public static function destroy(): void
    {
        unset($_SESSION['kos_user']);
        session_destroy();
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['kos_flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (isset($_SESSION['kos_flash'])) {
            $flash = $_SESSION['kos_flash'];
            unset($_SESSION['kos_flash']);
            return $flash;
        }
        return null;
    }
}
