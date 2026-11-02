<?php
/**
 * Front Controller (index.php): Điểm vào duy nhất của ứng dụng MVC PHP OOP
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/BaseModel.php';
require_once __DIR__ . '/core/BaseController.php';
require_once __DIR__ . '/core/Pagination.php';
require_once __DIR__ . '/core/FileUploader.php';

// Nạp các Models
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/CourseModel.php';
require_once __DIR__ . '/models/LearningModel.php';
require_once __DIR__ . '/models/ApprovalReportModel.php';

Session::start();

$controllerName = ucfirst(strtolower($_GET['controller'] ?? 'auth')) . 'Controller';
$actionName     = strtolower($_GET['action'] ?? 'login');

$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';
if (!file_exists($controllerFile)) {
    die("Không tìm thấy Controller: {$controllerName}");
}

require_once $controllerFile;
$controller = new $controllerName();

if (!method_exists($controller, $actionName)) {
    die("Không tìm thấy phương thức {$actionName} trong {$controllerName}");
}

$controller->$actionName();
