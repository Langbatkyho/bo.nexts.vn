<?php
/**
 * File khởi tạo ứng dụng.
 * Thiết lập môi trường, autoloader, xử lý lỗi và khởi tạo kết nối database.
 */

// Hiển thị lỗi trong quá trình phát triển (tắt khi production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tắt/bật hiển thị lỗi dựa trên môi trường
if (defined('APP_ENV') && APP_ENV === 'development') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
define('ROOT_PATH', __DIR__ . '/');

// --- Import các lớp Core & Services ---
use BO_System\Core\Config;
use BO_System\Core\Database;
use BO_System\Core\SessionManager;
use BO_System\Services\AuthService;
use BO_System\Models\PermissionModel;
use BO_System\Models\AuthModel;
use BO_System\Models\MenuModel;
use BO_System\Services\MenuService;
use BO_System\Helpers\LogHelper;

// Đăng ký hàm xử lý lỗi tùy chỉnh
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- Cấu hình Autoloader ---
spl_autoload_register(function ($className) {
    $prefix = 'BO_System\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }

    $relative_class = substr($className, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// --- Global Exception & Error Handler ---
set_exception_handler(function ($e) {
    $message = "Uncaught Exception: " . $e->getMessage();
    $context = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    // Ghi log lỗi
    if (class_exists('BO_System\Helpers\LogHelper')) {
        LogHelper::error($message, $context);
    } else {
        error_log($message . ' | ' . json_encode($context));
    }

    // Hiển thị thông báo lỗi thân thiện (nếu không phải API)
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "<h1>System Error</h1><p>{$message}</p><pre>{$context['trace']}</pre>";
        } else {
            echo "<h1>Đã có lỗi xảy ra</h1><p>Hệ thống đang gặp sự cố. Vui lòng thử lại sau.</p>";
        }
    }
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false; // Error code is not included in error_reporting
    }
    
    $message = "PHP Error [$errno]: $errstr";
    $context = ['file' => $errfile, 'line' => $errline];

    if (class_exists('BO_System\Helpers\LogHelper')) {
        switch ($errno) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                LogHelper::error($message, $context);
                break;
            case E_USER_WARNING:
            case E_WARNING:
                LogHelper::warning($message, $context);
                break;
            default:
                LogHelper::info($message, $context);
        }
    } else {
        error_log($message . ' | ' . json_encode($context));
    }

    // Không dừng script với các lỗi warning/notice
    return true; 
});

// --- Khởi động Session ---
SessionManager::start();

// --- Tải cấu hình ---
try {
    Config::load(ROOT_PATH . 'Config.php');
} catch (\Exception $e) {
    die("Lỗi tải cấu hình: " . $e->getMessage());
}

// --- Khởi tạo Database và các Service cốt lõi ---
try {
    Database::setConfig(Config::get('APP_DB_CONFIG'));
    $pdo = Database::getConnection();
    
    // Khởi tạo các model
    $authModel = new AuthModel($pdo);
    $permissionModel = new PermissionModel($pdo);
    $menuModel = new MenuModel($pdo);
    
    // Khởi tạo AuthService
    AuthService::init($authModel, $permissionModel);
    
    // Khởi tạo MenuService
    MenuService::init($menuModel, AuthService::getInstance());

} catch (\Exception $e) {
    die("Lỗi khởi tạo hệ thống: " . $e->getMessage());
}