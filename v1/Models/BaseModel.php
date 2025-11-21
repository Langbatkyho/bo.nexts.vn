<?php
namespace BO_System\Models;

use PDO;

/**
 * Lớp BaseModel trừu tượng.
 * Cung cấp các phương thức CRUD cơ bản cho các Model con.
 */
abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table; // Mỗi model con sẽ định nghĩa tên bảng của nó

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tìm một bản ghi theo ID.
     *
     * @param int|string $id ID của bản ghi.
     * @return array|null Mảng dữ liệu bản ghi hoặc null nếu không tìm thấy.
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lấy tất cả bản ghi trong bảng.
     *
     * @param string $orderBy Tên cột để sắp xếp.
     * @param string $orderDir Hướng sắp xếp (ASC hoặc DESC).
     * @return array Mảng các bản ghi.
     */
    public function all(string $orderBy = 'id', string $orderDir = 'DESC'): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$orderDir}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Xóa một bản ghi theo ID.
     *
     * @param int|string $id ID của bản ghi cần xóa.
     * @return bool True nếu xóa thành công, False nếu thất bại.
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}