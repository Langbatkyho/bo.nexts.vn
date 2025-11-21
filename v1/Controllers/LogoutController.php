<?php
namespace BO_System\Controllers;

use BO_System\Services\AuthService;

/**
 * Controller chịu trách nhiệm xử lý yêu cầu đăng xuất.
 */
class LogoutController
{
    /**
     * Phương thức mặc định được gọi khi module=Logout.
     * Nó sẽ gọi AuthService để thực hiện việc đăng xuất
     * và AuthService sẽ tự động chuyển hướng người dùng.
     */
    public function index(): void  // <<< SỬA TÊN PHƯƠNG THỨC TẠI ĐÂY
    {
        // Lấy instance của AuthService đã được khởi tạo trong app.php
        $authService = AuthService::getInstance();
        
        // Gọi phương thức logout
        $authService->logout();
        
        // Code ở đây sẽ không bao giờ được chạy vì hàm logout() đã có exit()
    }
}