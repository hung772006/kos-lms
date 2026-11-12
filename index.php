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

$rawController  = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['controller'] ?? 'auth');
$controllerName = ucfirst(strtolower($rawController ?: 'auth')) . 'Controller';
$actionName     = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['action'] ?? 'login') ?: 'login';

$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

// Hỗ trợ khớp chính xác tên file Controller ghép nhiều từ (VD: ClientLearningController, AdminGradingController)
if (!file_exists($controllerFile)) {
    foreach (glob(__DIR__ . '/controllers/*Controller.php') ?: [] as $file) {
        if (strcasecmp(basename($file), $controllerName . '.php') === 0) {
            $controllerFile = $file;
            $controllerName = pathinfo($file, PATHINFO_FILENAME);
            break;
        }
    }
}

if (!file_exists($controllerFile)) {
    die("Không tìm thấy Controller: {$controllerName}");
}

require_once $controllerFile;
$controller = new $controllerName();

if (!method_exists($controller, $actionName)) {
    die("Không tìm thấy phương thức {$actionName} trong {$controllerName}");
}

$controller->$actionName();