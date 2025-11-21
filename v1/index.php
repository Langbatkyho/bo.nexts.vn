<?php
// v2/index.php
// Đây là điểm vào (Entry Point) chính của hệ thống BO.
// Mọi request đến hệ thống đều đi qua file này để được điều hướng đến Controller tương ứng.

// 1. Khởi tạo môi trường và cấu hình hệ thống
// File initialize.php sẽ load Config, Database, Autoloader, Error Handler, và start Session.
require_once __DIR__ . '/initialize.php';

// 2. Import các lớp cần thiết
use BO_System\Core\Config;
use BO_System\Core\SessionManager;
use BO_System\Services\AuthService;
use BO_System\Helpers\Helper;

// Lấy instance của AuthService để kiểm tra trạng thái đăng nhập
$authService = AuthService::getInstance();

// 3. Kiểm tra xác thực (Authentication Check)
// Nếu người dùng chưa đăng nhập, lưu URL hiện tại và chuyển hướng về trang Login.
if (!$authService->isLoggedIn()) {
    SessionManager::set('redirect_url', $_SERVER['REQUEST_URI']);
    header('Location: /' . APP_VERSION . '/login.php');
    exit();
}

// 4. Cập nhật thông tin người dùng từ Database (Session Refresh)
// Đảm bảo thông tin trong Session luôn đồng bộ với Database (ví dụ: nếu user bị khóa hoặc đổi tên).
$authService->refreshUserData();

// Cập nhật lại biến $user_info sau khi refresh
$user_info = SessionManager::get('user_info');


// 5. Chuẩn bị dữ liệu chung cho Layout (View Shared Data)
// Các biến này sẽ được sử dụng trong file Views/Layouts/IndexLayout.php (Header, Sidebar, Footer).
$system_name      = Config::get('SYSTEM_NAME');
$brand_name       = Config::get('BRAND_NAME');
$logo_white_url   = Config::get('LOGO_WHITE_URL');
$logo_black_url   = Config::get('LOGO_BLACK_URL');
$favicon_url      = Config::get('FAVICON_URL');

// Xử lý hiển thị tên và avatar người dùng
$user_fullname = $user_info && !empty($user_info['full_name']) ? htmlspecialchars($user_info['full_name']) : 'No Name';
$user_email = $user_info && !empty($user_info['email']) ? htmlspecialchars($user_info['email']) : 'No Email';
// Tạo Gravatar từ email
$gravatar_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($user_email))) . "?s=80&d=mp&r=g";

// 6. Hệ thống Định tuyến (Routing System)
// Cơ chế: /{APP_VERSION}/index.php?module={Module}&action={Action}
// Ví dụ: module=Users&action=index -> Gọi UsersController::index()

$module = $_GET['module'] ?? 'Dashboard'; // Mặc định vào Dashboard
$action = $_GET['action'] ?? 'index';     // Mặc định gọi hàm index

// Tạo tên lớp Controller đầy đủ (Fully Qualified Class Name)
$controllerName = 'BO_System\\Controllers\\' . ucfirst($module) . 'Controller';
$page = strtolower($module); // Biến dùng để active menu sidebar

// Kiểm tra xem Controller có tồn tại không
if (class_exists($controllerName)) {
    $controller = new $controllerName();

    // Tự động ánh xạ action trên URL vào tên hàm trong Controller
    $method = $action;

    // Xử lý Alias (Bí danh): Hỗ trợ các link cũ dùng 'delete' thay vì 'destroy'
    if ($method === 'delete') {
        $method = 'destroy';
    }

    // Kiểm tra xem hàm có tồn tại trong Controller không
    if (method_exists($controller, $method)) {
        // Sử dụng Reflection để kiểm tra tính truy cập (Visibility) của hàm
        // Chỉ cho phép gọi các hàm public, ngăn chặn gọi private/protected method qua URL
        $reflection = new ReflectionMethod($controller, $method);
        if ($reflection->isPublic()) {
            // Bắt đầu Output Buffering để hứng nội dung view
            ob_start();
            $controller->$method();
            // Lấy nội dung view và gán vào biến $content_for_layout
            $content_for_layout = ob_get_clean();
        } else {
            // Trả về lỗi 404 nếu cố tình truy cập hàm private/protected
            header("HTTP/1.0 404 Not Found");
            $content_for_layout = Helper::renderError("Hành động '{$method}' không được phép truy cập (Private/Protected).");
        }
    } else {
        // Trả về lỗi 404 nếu hàm không tồn tại
        header("HTTP/1.0 404 Not Found");
        $content_for_layout = Helper::renderError("Hành động '{$method}' không được tìm thấy trong controller '{$controllerName}'.");
    }
} else {
    // Trả về lỗi 404 nếu Controller không tồn tại
    header("HTTP/1.0 404 Not Found");
    $content_for_layout = Helper::renderError("Controller cho module '{$module}' không được tìm thấy.");
}

// 7. Render Layout chính
// File layout sẽ hiển thị header, sidebar và nội dung ($content_for_layout) đã được render ở trên.
require_once ROOT_PATH . 'Views/Layouts/IndexLayout.php';
