<?php
namespace BO_System\Core;

use PDO;
use PDOException;
use BO_System\Helpers\LogHelper;

/**
 * Lớp Database quản lý kết nối đến cơ sở dữ liệu MySQL thông qua PDO.
 * Sử dụng Singleton Pattern để đảm bảo chỉ có một kết nối được tạo ra.
 */
class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    /**
     * Thiết lập cấu hình database.
     *
     * @param array $config Mảng cấu hình chứa DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET.
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Lấy đối tượng kết nối PDO.
     * Nếu chưa có kết nối, sẽ tạo mới.
     *
     * @return PDO
     * @throws PDOException Nếu cấu hình chưa được thiết lập hoặc kết nối thất bại.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            if (empty(self::$config)) {
                $msg = "Database configuration not set. Call Database::setConfig() first.";
                LogHelper::error($msg);
                throw new PDOException($msg);
            }

            $dsn = "mysql:host=" . self::$config['DB_HOST'] . ";dbname=" . self::$config['DB_NAME'] . ";charset=" . self::$config['DB_CHARSET'];
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$connection = new PDO($dsn, self::$config['DB_USER'], self::$config['DB_PASS'], $options);
            } catch (PDOException $e) {
                LogHelper::error("Lỗi kết nối database PDO", ['exception' => $e->getMessage()]);
                throw new PDOException("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.", 0, $e);
            }
        }
        return self::$connection;
    }
}