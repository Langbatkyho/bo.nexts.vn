<?php
namespace BO_System\Models;

use PDO;
use PDOException;

/**
 * Lớp PermissionModel quản lý dữ liệu quyền hạn (Permissions).
 */
class PermissionModel extends BaseModel
{
    protected string $table = 'sec_permissions';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Lấy tất cả các quyền hạn từ database.
     * @return array
     */
    public function getAllPermissions(): array
    {
        try {
            // Sử dụng hàm all() từ BaseModel nhưng sắp xếp theo slug
            $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY slug ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all permissions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Thêm các quyền mới vào database nếu chúng chưa tồn tại.
     *
     * @param array $permissions Mảng các quyền, mỗi quyền là một mảng ['slug' => ..., 'description' => ...].
     * @return int Số lượng quyền mới đã được thêm.
     */
    public function addMissingPermissions(array $permissions): int
    {
        $existingSlugsStmt = $this->pdo->query("SELECT slug FROM {$this->table}");
        $existingSlugs = $existingSlugsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $newPermissions = array_filter($permissions, function($permission) use ($existingSlugs) {
            return !in_array($permission['slug'], $existingSlugs);
        });

        if (empty($newPermissions)) {
            return 0;
        }

        $sql = "INSERT INTO {$this->table} (slug, description) VALUES (:slug, :description)";
        $stmt = $this->pdo->prepare($sql);
        
        $insertedCount = 0;
        foreach ($newPermissions as $permission) {
            try {
                if ($stmt->execute($permission)) {
                    $insertedCount++;
                }
            } catch (PDOException $e) {
                // Bỏ qua lỗi nếu có (ví dụ: unique constraint) và tiếp tục
                error_log("Could not insert permission {$permission['slug']}: " . $e->getMessage());
            }
        }

        return $insertedCount;
    }
    
    /**
     * Lấy danh sách các slug vai trò của một người dùng.
     * @param string $userId
     * @return array Mảng các slug vai trò (vd: ['super-admin', 'content-editor']).
     */
    public function getRoleSlugsForUser(string $userId): array
    {
        $sql = "SELECT r.slug
                FROM sec_roles r
                JOIN sec_user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            error_log("Error fetching roles for user: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tất cả các slug quyền hạn của một người dùng, bao gồm cả quyền
     * từ vai trò và quyền được gán trực tiếp.
     * @param string $userId
     * @return array Mảng các slug quyền duy nhất (vd: ['faqs.view', 'faqs.edit']).
     */
    public function getPermissionSlugsForUser(string $userId): array
    {
        // Câu SQL này sử dụng UNION để kết hợp hai nguồn quyền:
        // 1. Quyền từ các vai trò của người dùng.
        // 2. Quyền được gán trực tiếp cho người dùng.
        // UNION tự động loại bỏ các quyền trùng lặp.
        $sql = "
            -- Lấy quyền từ vai trò
            SELECT p.slug
            FROM sec_permissions p
            JOIN sec_role_permissions rp ON p.id = rp.permission_id
            JOIN sec_user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = :user_id

            UNION

            -- Lấy quyền trực tiếp
            SELECT p.slug
            FROM sec_permissions p
            JOIN sec_user_permissions up ON p.id = up.permission_id
            WHERE up.user_id = :user_id_direct
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            // Cần bind cùng một user_id cho cả hai phần của UNION
            $stmt->execute([
                ':user_id' => $userId,
                ':user_id_direct' => $userId
            ]);
            // FETCH_COLUMN trả về một mảng phẳng chỉ chứa giá trị của cột đầu tiên
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            error_log("Error fetching permissions for user: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cập nhật mô tả của một quyền hạn.
     * @param int $id ID của quyền.
     * @param string $description Mô tả mới.
     * @return bool
     */
    public function updateDescription(int $id, string $description): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET description = :description WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':description' => $description,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating permission description for ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem quyền có đang được sử dụng hay không.
     *
     * @param int $permissionId ID của quyền.
     * @return bool True nếu quyền đang được sử dụng bởi vai trò hoặc người dùng.
     */
    public function isPermissionInUse(int $permissionId): bool
    {
        try {
            // Kiểm tra trong bảng sec_role_permissions
            $stmtRole = $this->pdo->prepare("SELECT COUNT(*) FROM sec_role_permissions WHERE permission_id = :id");
            $stmtRole->execute([':id' => $permissionId]);
            if ($stmtRole->fetchColumn() > 0) {
                return true;
            }

            // Kiểm tra trong bảng sec_user_permissions
            $stmtUser = $this->pdo->prepare("SELECT COUNT(*) FROM sec_user_permissions WHERE permission_id = :id");
            $stmtUser->execute([':id' => $permissionId]);
            if ($stmtUser->fetchColumn() > 0) {
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error checking permission usage for ID {$permissionId}: " . $e->getMessage());
            // Nếu có lỗi, giả định là đang sử dụng để an toàn (không xóa nhầm)
            return true;
        }
    }
}