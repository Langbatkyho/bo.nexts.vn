<?php

namespace BO_System\Core;

/**
 * Lớp SessionManager quản lý phiên làm việc (session) của người dùng.
 * Đảm bảo session được khởi tạo an toàn và cung cấp các phương thức thao tác với session.
 */
class SessionManager
{
    /**
     * @var bool Cờ để đảm bảo session chỉ được khởi động một lần.
     */
    private static bool $isStarted = false;

    /**
     * Khởi động session với các cài đặt bảo mật.
     * Phương thức này sẽ thay thế cho file sessionmanager.php.
     * Nó phải được gọi trước bất kỳ output nào ra trình duyệt.
     *
     * @return bool True nếu session được khởi động thành công.
     */
    public static function start(): bool
    {
        if (self::$isStarted) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$isStarted = true;
            return true;
        }
        
        // [BẢO MẬT] Logic phát hiện HTTPS nâng cao
        $is_https = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );

        // [BẢO MẬT] Cấu hình session an toàn trước khi khởi động.
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);

        if ($is_https) {
            ini_set('session.cookie_secure', 1);
        }

        ini_set('session.gc_maxlifetime', 1800); // 30 phút

        // Tên session an toàn
        session_name('__Secure-coolmombo');

        if (session_start()) {
            self::$isStarted = true;
            return true;
        }

        return false;
    }

    /**
     * Thiết lập một giá trị trong session.
     *
     * @param string $key Khóa.
     * @param mixed $value Giá trị.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Lấy một giá trị từ session.
     *
     * @param string $key Khóa.
     * @param mixed $default Giá trị mặc định nếu không tìm thấy.
     * @return mixed Giá trị trong session hoặc giá trị mặc định.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Kiểm tra xem một khóa có tồn tại trong session không.
     *
     * @param string $key Khóa cần kiểm tra.
     * @return bool True nếu khóa tồn tại, ngược lại là false.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Xóa một giá trị khỏi session.
     *
     * @param string $key Khóa cần xóa.
     */
    public static function unset(string $key): void
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Hủy toàn bộ session (đăng xuất).
     */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        
        // 1. Xóa tất cả các biến session
        $_SESSION = [];

        // 2. Xóa cookie session phía client
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 3. Hủy session trên server
        session_destroy();

        self::$isStarted = false;
    }
}