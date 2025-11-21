<?php
namespace BO_System\Helpers;

/**
 * Lớp JsonHelper hỗ trợ trả về phản hồi JSON chuẩn.
 */
class JsonHelper
{
    /**
     * Gửi phản hồi JSON và kết thúc script.
     *
     * @param mixed $data Dữ liệu trả về (có thể là mảng hoặc object).
     * @param int $statusCode Mã trạng thái HTTP.
     */
    public static function jsonResponse($data, int $statusCode = 200): void
    {
        // 1. Chỉ cho phép các Origin được cấu hình (CORS) trong Contfig.php
        $allowedOrigins = defined('ALLOWED_CORS_ORIGINS') ? ALLOWED_CORS_ORIGINS : ['https://coolmom.vn'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Fallback: Cho phép một origin mặc định nếu không khớp
            header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
        }

        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        // 2. Security Headers (Tăng cường bảo mật)
        header("Content-Type: application/json; charset=UTF-8");
        header("X-Content-Type-Options: nosniff"); // Ngăn trình duyệt đoán sai MIME type
        header("X-Frame-Options: DENY");           // Ngăn chặn nhúng API vào iframe (Clickjacking)
        header("Referrer-Policy: strict-origin-when-cross-origin"); // Kiểm soát thông tin Referer gửi đi
        header("X-XSS-Protection: 1; mode=block"); // Kích hoạt bộ lọc XSS
        
        // (Tùy chọn) Strict-Transport-Security nếu chạy trên HTTPS
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Trả về phản hồi thành công (HTTP 200).
     *
     * @param string $message Thông điệp thành công.
     * @param array $data Dữ liệu kèm theo.
     */
    public static function success(string $message = 'Success', array $data = []): void
    {
        self::jsonResponse(['success' => true, 'message' => $message, 'data' => $data], 200);
    }

    /**
     * Trả về phản hồi lỗi (HTTP 400, 500, ...).
     *
     * @param string $message Thông điệp lỗi.
     * @param array $errors Chi tiết lỗi (ví dụ: lỗi validation).
     * @param int $statusCode Mã trạng thái HTTP.
     */
    public static function error(string $message = 'Error', array $errors = [], int $statusCode = 400): void
    {
        self::jsonResponse(['success' => false, 'message' => $message, 'errors' => $errors], $statusCode);
    }
}
