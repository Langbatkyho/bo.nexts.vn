<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Helpers\SecurityHelper;
use BO_System\Helpers\JsonHelper;

/**
 * Lớp ApiController cơ sở cho các API Controller.
 * Tự động xử lý bảo mật (CORS/Origin) và cung cấp các phương thức tiện ích cho API.
 */
class ApiController extends BaseController
{
    public function __construct()
    {
        // Gọi constructor của BaseController (khởi tạo AuthService, v.v. nếu cần)
        parent::__construct();

        // Xử lý Preflight Request (OPTIONS) cho CORS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            JsonHelper::jsonResponse([], 200);
        }

        // Tự động kiểm tra bảo mật Origin cho tất cả các API kế thừa lớp này
        SecurityHelper::validateOrigin();
    }

    /**
     * Trả về phản hồi JSON thành công.
     * @param mixed $data
     * @param int $statusCode
     */
    protected function jsonResponse($data, int $statusCode = 200): void
    {
        JsonHelper::jsonResponse($data, $statusCode);
    }

    /**
     * Trả về phản hồi lỗi JSON.
     * @param string $message
     * @param int $statusCode
     */
    protected function jsonError(string $message, int $statusCode = 400): void
    {
        JsonHelper::jsonResponse(['error' => $message], $statusCode);
    }
}
