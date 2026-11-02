<?php
/**
 * File cấu hình hằng số kết nối CSDL và đường dẫn gốc hệ thống KOS LMS
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'kos_lms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'KOS English — Hệ thống quản lý đào tạo tiếng Anh');
define('ITEMS_PER_PAGE', 4); // Số bản ghi mặc định trên 1 trang để dễ demo Phân trang
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
