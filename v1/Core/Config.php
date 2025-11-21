<?php

namespace BO_System\Core;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Config
{
    private static array $settings = [];
    private static ?PDO $pdo_conn = null;
    private static ?mysqli $mysqli_conn = null;

    /**
     * Tải tất cả các cấu hình từ các hằng số define và biến $GLOBALS
     * từ file cấu hình bên ngoài.
     * Phương thức này phải được gọi một lần duy nhất khi ứng dụng khởi động.
     *
     * @param string $configFilePath Đường dẫn tuyệt đối đến file Config.php bên ngoài.
     */
    public static function load(string $configFilePath): void
    {
        if (!file_exists($configFilePath)) {
            throw new \Exception("File cấu hình không tồn tại tại: {$configFilePath}");
        }

        // Đảm bảo các hằng số đã được định nghĩa và mảng $GLOBALS['app_db_config'] được thiết lập
        require_once $configFilePath;

        // Lưu các hằng số vào mảng settings
        self::$settings = [
            'BRAND_NAME'             => defined('BRAND_NAME') ? BRAND_NAME : null,
            'SYSTEM_NAME'            => defined('SYSTEM_NAME') ? SYSTEM_NAME : null,
            'SUB_DOMAIN'             => defined('SUB_DOMAIN') ? SUB_DOMAIN : null,
            'DOMAIN'                 => defined('DOMAIN') ? DOMAIN : null,
            'EMAIL_ADMIN'            => defined('EMAIL_ADMIN') ? EMAIL_ADMIN : null,
            'LOGO_WHITE_URL'         => defined('LOGO_WHITE_URL') ? LOGO_WHITE_URL : null,
            'LOGO_BLACK_URL'         => defined('LOGO_BLACK_URL') ? LOGO_BLACK_URL : null,
            'FAVICON_URL'            => defined('FAVICON_URL') ? FAVICON_URL : null,
            'DB_HOST_MYSQL'          => defined('DB_HOST_MYSQL') ? DB_HOST_MYSQL : null,
            'DB_NAME_MYSQL'          => defined('DB_NAME_MYSQL') ? DB_NAME_MYSQL : null,
            'DB_USER_MYSQL'          => defined('DB_USER_MYSQL') ? DB_USER_MYSQL : null,
            'DB_PASS_MYSQL'          => defined('DB_PASS_MYSQL') ? DB_PASS_MYSQL : null,
            'DB_CHARSET_MYSQL'       => defined('DB_CHARSET_MYSQL') ? DB_CHARSET_MYSQL : null,
            'HARAVAN_STORE_DOMAIN'   => defined('HARAVAN_STORE_DOMAIN') ? HARAVAN_STORE_DOMAIN : null,
            'HARAVAN_ACCESS_TOKEN'   => defined('HARAVAN_ACCESS_TOKEN') ? HARAVAN_ACCESS_TOKEN : null,
            'GEMINI_API_KEYS_BY_EMAIL' => defined('GEMINI_API_KEYS_BY_EMAIL') ? GEMINI_API_KEYS_BY_EMAIL : [],
            'SMTP_HOST'              => defined('SMTP_HOST') ? SMTP_HOST : null,
            'SMTP_PORT'              => defined('SMTP_PORT') ? SMTP_PORT : null,
            'SMTP_USERNAME'          => defined('SMTP_USERNAME') ? SMTP_USERNAME : null,
            'SMTP_PASSWORD'          => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : null,
            'SMTP_FROM_EMAIL'        => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : null,
            'SMTP_FROM_NAME'         => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : null,
            'EMAIL_SERVICE_API_KEYS' => defined('EMAIL_SERVICE_API_KEYS') ? EMAIL_SERVICE_API_KEYS : [],
            'EMAIL_SERVICE_API_KEY'  => defined('EMAIL_SERVICE_API_KEY') ? EMAIL_SERVICE_API_KEY : null,
            'AFFILIATE_API_BASE_URL' => defined('AFFILIATE_API_BASE_URL') ? AFFILIATE_API_BASE_URL : null,
        ];

        // Lấy cấu hình DB từ biến toàn cục
        if (isset($GLOBALS['app_db_config'])) {
            self::$settings['APP_DB_CONFIG'] = $GLOBALS['app_db_config'];
        } else {
            // Fallback nếu $GLOBALS['app_db_config'] không tồn tại
            self::$settings['APP_DB_CONFIG'] = [
                'DB_HOST'    => self::$settings['DB_HOST_MYSQL'],
                'DB_NAME'    => self::$settings['DB_NAME_MYSQL'],
                'DB_USER'    => self::$settings['DB_USER_MYSQL'],
                'DB_PASS'    => self::$settings['DB_PASS_MYSQL'],
                'DB_CHARSET' => self::$settings['DB_CHARSET_MYSQL'],
            ];
        }
    }

    /**
     * Lấy một giá trị cấu hình.
     *
     * @param string $key Tên của cài đặt.
     * @param mixed $default Giá trị mặc định nếu cài đặt không tồn tại.
     * @return mixed Giá trị của cài đặt.
     */
    public static function get(string $key, $default = null): mixed
    {
        return self::$settings[$key] ?? $default;
    }

    /**
     * Khởi tạo và trả về một đối tượng PDO connection.
     * Sử dụng singleton pattern để đảm bảo chỉ có một kết nối PDO duy nhất.
     *
     * @return PDO
     * @throws PDOException
     */
    public static function connectPDO(): PDO
    {
        if (self::$pdo_conn === null) {
            $dbConfig = self::get('APP_DB_CONFIG');

            if (!$dbConfig || empty($dbConfig['DB_HOST']) || empty($dbConfig['DB_NAME']) || empty($dbConfig['DB_USER'])) {
                throw new PDOException("Cấu hình database PDO không đầy đủ hoặc không được tải.");
            }

            $dsn = "mysql:host=" . $dbConfig['DB_HOST'] . ";dbname=" . $dbConfig['DB_NAME'] . ";charset=" . $dbConfig['DB_CHARSET'];
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$pdo_conn = new PDO($dsn, $dbConfig['DB_USER'], $dbConfig['DB_PASS'], $options);
            } catch (PDOException $e) {
                error_log("Lỗi kết nối cơ sở dữ liệu PDO: " . $e->getMessage());
                throw new PDOException("Không thể kết nối đến cơ sở dữ liệu (PDO). Vui lòng thử lại sau.", 0, $e);
            }
        }
        return self::$pdo_conn;
    }

    /**
     * Hàm gửi email chung sử dụng PHPMailer.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param bool $isHtml
     * @param string|null $cc
     * @param string|null $bcc
     * @param array $attachments
     * @return array
     */
    public static function sendEmail(string $to, string $subject, string $body, bool $isHtml = true, ?string $cc = null, ?string $bcc = null, array $attachments = []): array
    {
        // Chỉ require các class PHPMailer khi cần thiết
        // Điều chỉnh đường dẫn tương đối từ v2/Core/Config.php
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = self::get('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = self::get('SMTP_USERNAME');
            $mail->Password   = self::get('SMTP_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = self::get('SMTP_PORT');
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(self::get('SMTP_FROM_EMAIL'), self::get('SMTP_FROM_NAME'));
            $mail->addAddress($to);

            if (!empty($cc)) {
                $cc_emails = explode(',', $cc);
                foreach ($cc_emails as $email) {
                    $mail->addCC(trim($email));
                }
            }

            if (!empty($bcc)) {
                $bcc_emails = explode(',', $bcc);
                foreach ($bcc_emails as $email) {
                    $mail->addBCC(trim($email));
                }
            }

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            if (is_array($attachments) && !empty($attachments)) {
                foreach ($attachments as $attachment_path) {
                    if (file_exists($attachment_path)) {
                        $mail->addAttachment($attachment_path);
                    }
                }
            }

            $mail->send();
            return ['status' => true, 'message' => 'Email sent successfully.'];
        } catch (PHPMailerException $e) { // Sử dụng PHPMailerException
            // Ghi log chi tiết lỗi PHPMailer vào file log chính
            if (function_exists('logMessage')) {
                logMessage("Email to {$to} failed. Mailer Error: {$mail->ErrorInfo}. Exception: " . $e->getMessage());
            } else {
                // Điều chỉnh đường dẫn tương đối cho file log nếu cần
                $logFile = ROOT_PATH . 'logs/email_queue_cron.log'; // Giả định có thư mục logs trong v2/
                if (!is_dir(dirname($logFile))) {
                    mkdir(dirname($logFile), 0775, true);
                }
                file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Email to {$to} failed. Mailer Error: {$mail->ErrorInfo}. Exception: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
            return ['status' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
        }
    }
}