<?php
namespace BO_System\Models;

use PDO;

/**
 * Lớp AuthModel xử lý truy vấn liên quan đến xác thực.
 */
class AuthModel extends BaseModel
{
    protected string $table = 'sec_users'; // Định nghĩa tên bảng

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo); // Gọi constructor của lớp cha
    }

    /**
     * Tìm người dùng theo tên đăng nhập.
     *
     * @param string $username Tên đăng nhập.
     * @return array|null Mảng thông tin người dùng hoặc null.
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, password, is_active FROM {$this->table} WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}