<?php
/**
 * Class Database: Kết nối CSDL MySQL bằng PDO theo mẫu Singleton & OOP
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die('Lỗi kết nối CSDL KOS LMS: ' . $e->getMessage() . ' (Hãy kiểm tra đã Import file database_kos_lms.sql vào phpMyAdmin chưa).');
            }
        }
        return self::$instance;
    }
}
