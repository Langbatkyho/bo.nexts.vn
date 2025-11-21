<?php
namespace BO_System\Helpers;

/**
 * Lớp LogHelper hỗ trợ ghi log lỗi và thông tin vào file.
 */
class LogHelper
{
    private static string $logDir = __DIR__ . '/../logs/';

    /**
     * Ghi log lỗi (Error)
     * Dùng cho các lỗi nghiêm trọng, exception, lỗi database.
     *
     * @param string $message Thông điệp lỗi.
     * @param array $context Dữ liệu ngữ cảnh đi kèm.
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Ghi log cảnh báo (Warning)
     * Dùng cho các hành vi đáng ngờ, input sai, truy cập bị chặn.
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * Ghi log thông tin (Info)
     * Dùng để theo dõi luồng hoạt động bình thường (VD: Cron job chạy, User đăng nhập).
     *
     * @param string $message Thông điệp log.
     * @param array $context Dữ liệu ngữ cảnh đi kèm.
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /**
     * Hàm nội bộ để ghi nội dung vào file log
     *
     * @param string $level Mức độ log (INFO, ERROR, ...).
     * @param string $message Thông điệp.
     * @param array $context Ngữ cảnh.
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        // 1. Tạo thư mục logs nếu chưa tồn tại
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        // 2. Xác định tên file log theo ngày (VD: system-2025-11-20.log)
        $filename = self::$logDir . 'system-' . date('Y-m-d') . '.log';

        // 3. Thu thập thông tin ngữ cảnh
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $url = $_SERVER['REQUEST_URI'] ?? 'N/A';
        
        // Thử lấy User ID từ Session nếu có (giả sử SessionManager đã start)
        $user = 'Guest';
        if (isset($_SESSION['user_id'])) {
            $user = 'User:' . $_SESSION['user_id'];
        }

        // 4. Format context thành JSON để dễ đọc
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        // 5. Tạo dòng log
        // Format: [Time] [Level] [IP] [User] [URL] Message | Context
        $logLine = "[$timestamp] [$level] [IP:$ip] [$user] [$url] $message$contextStr" . PHP_EOL;

        // 6. Ghi vào file (Append mode)
        file_put_contents($filename, $logLine, FILE_APPEND);
    }
}
