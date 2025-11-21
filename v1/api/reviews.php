<?php
// Khởi tạo hệ thống
require_once __DIR__ . '/../initialize.php';

// Sử dụng các lớp cần thiết
use BO_System\Controllers\ReviewsApiController;
use BO_System\Helpers\JsonHelper;

// Khởi tạo controller
$controller = new ReviewsApiController();

// Nhận hành động từ chuỗi truy vấn
$action = $_GET['action'] ?? '';

// Kiểm tra nếu phương thức tồn tại trong controller và có thể gọi được
if (method_exists($controller, $action) && is_callable([$controller, $action])) {
    $controller->$action();
} else {
    JsonHelper::jsonResponse(["error" => "Invalid action"], 400);
}