<?php
namespace BO_System\Controllers;

use BO_System\Services\AuthService;
use BO_System\Helpers\SecurityHelper;

/**
 * Lớp BaseController trừu tượng.
 * Chứa các phương thức và thuộc tính chung mà tất cả các Controller khác sẽ kế thừa.
 */
abstract class BaseController
{
    protected AuthService $auth;

    public function __construct()
    {
        // Thiết lập các header bảo mật cho tất cả các trang
        SecurityHelper::setSecurityHeaders();
        
        $this->auth = AuthService::getInstance();
    }

    /**
     * Tải một file view và truyền dữ liệu vào đó.
     * Dữ liệu được truyền trong một biến $data duy nhất để code trong view rõ ràng hơn.
     *
     * @param string $viewName Tên file view (ví dụ: 'Faqs/edit').
     * @param array $viewData Dữ liệu truyền cho view.
     */
    protected function loadView(string $viewName, array $viewData = []): void
    {
        // Tạo biến $data để View có thể truy cập
        $data = $viewData;
        include ROOT_PATH . 'Views/' . $viewName . '.php';
    }

    /**
     * Render một chuỗi HTML trực tiếp, hữu ích cho việc hiển thị lỗi đơn giản.
     * @param string $content
     */
    protected function renderContent(string $content): void
    {
        echo $content;
    }
}