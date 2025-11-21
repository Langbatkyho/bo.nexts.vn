<?php
namespace BO_System\Models;

use PDO;
use PDOException;

/**
 * Lớp RoleModel quản lý dữ liệu vai trò (Roles).
 */
class RoleModel extends BaseModel
{
    protected string $table = 'sec_roles';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Lấy tất cả các vai trò.
     * @return array
     */
    public function getAllRoles(): array
    {
        return $this->all('name', 'ASC'); // Sử dụng hàm all() từ BaseModel
    }

    /**
     * Lấy danh sách các permission ID đã được gán cho một vai trò cụ thể.
     * @param int $roleId
     * @return array Mảng các permission ID.
     */
    public function getPermissionIdsForRole(int $roleId): array
    {
        $stmt = $this->pdo->prepare("SELECT permission_id FROM sec_role_permissions WHERE role_id = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Đồng bộ hóa (thêm/xóa) các quyền cho một vai trò.
     * @param int $roleId
     * @param array $permissionIds Mảng các permission ID mới cần gán cho vai trò.
     * @return bool
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Xóa tất cả các quyền hiện tại của vai trò này
            $deleteStmt = $this->pdo->prepare("DELETE FROM sec_role_permissions WHERE role_id = :role_id");
            $deleteStmt->execute([':role_id' => $roleId]);

            // 2. Thêm lại các quyền mới từ danh sách được cung cấp
            if (!empty($permissionIds)) {
                $insertSql = "INSERT INTO sec_role_permissions (role_id, permission_id) VALUES ";
                $placeholders = [];
                $values = [];
                foreach ($permissionIds as $pId) {
                    $placeholders[] = "(?, ?)";
                    $values[] = $roleId;
                    $values[] = $pId;
                }
                $insertSql .= implode(', ', $placeholders);
                
                $insertStmt = $this->pdo->prepare($insertSql);
                $insertStmt->execute($values);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error syncing permissions for role {$roleId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tạo một vai trò mới.
     * @param string $name
     * @param string $slug
     * @param string|null $description
     * @return int|false ID của vai trò mới hoặc false nếu thất bại.
     */
    public function createRole(string $name, string $slug, ?string $description)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (name, slug, description) VALUES (:name, :slug, :description)"
            );
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating role: " . $e->getMessage());
            return false;
        }
    }
}