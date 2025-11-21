<?php
namespace BO_System\Helpers;

use BO_System\Core\SessionManager;
use BO_System\Helpers\JsonHelper;
use BO_System\Helpers\LogHelper;

/**
 * Lớp SecurityHelper cung cấp các phương thức bảo mật như tạo CSRF token.
 */
class SecurityHelper
{
    /**
     * Kiểm tra Origin/Referer của request để hạn chế truy cập.
     * Lưu ý: Origin và Referer có thể bị giả mạo bởi các công cụ như Postman/cURL.
     * Hàm này giúp ngăn chặn việc nhúng API vào các website lạ trên trình duyệt
     * và chặn truy cập trực tiếp từ Postman/cURL.
     *
     * @param array|null $allowedDomains Danh sách các domain được phép. Nếu null sẽ lấy từ config.
     * @return void
     */
    public static function validateOrigin(?array $allowedDomains = null): void
    {
        if ($allowedDomains === null) {
            $allowedDomains = defined('ALLOWED_CORS_ORIGINS') ? ALLOWED_CORS_ORIGINS : [];
        }

        // Nếu là môi trường dev/local thì có thể bỏ qua hoặc thêm localhost vào allowedDomains
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // 1. Kiểm tra Origin (thường được gửi bởi trình duyệt trong các request CORS)
        if (!empty($origin)) {
            if (!in_array($origin, $allowedDomains)) {
                LogHelper::warning("Blocked Invalid Origin", ['origin' => $origin]);
                JsonHelper::jsonResponse(['error' => 'Forbidden: Invalid Origin'], 403);
            }
            return; // Origin hợp lệ
        }

        // 2. Kiểm tra Referer (nếu không có Origin)
        if (!empty($referer)) {
            $isValidReferer = false;
            foreach ($allowedDomains as $domain) {
                if (strpos($referer, $domain) === 0) {
                    $isValidReferer = true;
                    break;
                }
            }
            if (!$isValidReferer) {
                LogHelper::warning("Blocked Invalid Referer", ['referer' => $referer]);
                JsonHelper::jsonResponse(['error' => 'Forbidden: Invalid Referer'], 403);
            }
        }
        
        // Chặn tất cả request nếu cả Origin và Referer đều trống (request trực tiếp từ Postman/cURL)
        // Tuy nhiên, nếu đang ở môi trường development thì cho phép để dễ debug
        else { 
            if (defined('APP_ENV') && APP_ENV === 'development') {
                return; // Cho phép truy cập trực tiếp ở môi trường dev
            }
            LogHelper::warning("Blocked Direct Access (No Origin/Referer)");
            JsonHelper::jsonResponse(['error' => 'Forbidden: Direct access not allowed'], 403); 
        }
    }

    /**
     * Tạo CSRF token và lưu vào session.
     *
     * @return string Token vừa tạo.
     */
    public static function generateCsrfToken(): string
    {
        if (!SessionManager::has('csrf_token')) {
            SessionManager::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return SessionManager::get('csrf_token');
    }

    /**
     * Kiểm tra tính hợp lệ của CSRF token.
     *
     * @param string $token Token cần kiểm tra.
     * @return bool True nếu hợp lệ.
     */
    public static function validateCsrfToken(string $token): bool
    {
        return SessionManager::has('csrf_token') && hash_equals(SessionManager::get('csrf_token'), $token);
    }

    /**
     * Thiết lập các HTTP Security Headers cơ bản.
     * Nên gọi hàm này ở BaseController hoặc index.php.
     */
    public static function setSecurityHeaders(): void
    {
        // Ngăn chặn clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        // Ngăn chặn trình duyệt đoán MIME type
        header("X-Content-Type-Options: nosniff");
        // Kích hoạt bộ lọc XSS của trình duyệt (cho các trình duyệt cũ)
        header("X-XSS-Protection: 1; mode=block");
        // Kiểm soát Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        // (Tùy chọn) Strict-Transport-Security nếu chạy trên HTTPS
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }
}
