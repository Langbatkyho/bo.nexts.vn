<?php
namespace BO_System\Models;

use PDO;
use PDOException;

class UserModel extends BaseModel
{
    protected string $table = 'sec_users';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Lấy danh sách tất cả người dùng cùng với vai trò của họ.
     *
     * @return array Mảng người dùng.
     */
    public function getAllUsersWithRoles(): array
    {
        $sql = "
            SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') as roles_list
            FROM {$this->table} u
            LEFT JOIN sec_user_roles ur ON u.id = ur.user_id
            LEFT JOIN sec_roles r ON ur.role_id = r.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ";
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users with roles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Tạo người dùng mới.
     *
     * @param array $data Dữ liệu người dùng.
     * @return string|false ID của người dùng mới hoặc false nếu thất bại.
     */
    public function createUser(array $data): string|false
    {
        $sql = "INSERT INTO {$this->table} (id, full_name, username, email, password, is_active, phone_1, phone_2) 
                VALUES (UUID(), :full_name, :username, :email, :password, :is_active, :phone_1, :phone_2)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $data['full_name'],
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':is_active' => $data['is_active'] ?? 1,
                ':phone_1' => $data['phone_1'] ?? null,
                ':phone_2' => $data['phone_2'] ?? null,
            ]);
            
            // Lấy UUID vừa được tạo
            $id = $this->pdo->query("SELECT UUID()")->fetchColumn();
            return $this->findUserByUsername($data['username'])['id'] ?? false; // Cách an toàn hơn để lấy ID
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tìm người dùng theo username.
     *
     * @param string $username
     * @return mixed Mảng dữ liệu người dùng hoặc false.
     */
    public function findUserByUsername(string $username)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật thông tin người dùng.
     *
     * @param mixed $id ID người dùng.
     * @param array $data Dữ liệu cần cập nhật.
     * @return bool True nếu thành công.
     */
    public function update($id, array $data): bool
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        if (empty($data)) return true;

        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "`$key` = :$key";
        }
        $setSql = implode(', ', $setClauses);

        $sql = "UPDATE {$this->table} SET {$setSql} WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $data['id'] = $id; // Thêm id vào mảng data để bind
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error updating user {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách ID các vai trò của người dùng.
     *
     * @param string $userId
     * @return array Mảng ID vai trò.
     */
    public function getRoleIdsForUser(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT role_id FROM sec_user_roles WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
    }

    /**
     * Đồng bộ vai trò cho người dùng (Xóa cũ, thêm mới).
     *
     * @param string $userId
     * @param array $roleIds Mảng ID vai trò mới.
     * @return bool
     */
    public function syncRoles(string $userId, array $roleIds): bool
    {
        // Sử dụng transaction để đảm bảo tính toàn vẹn dữ liệu
        $this->pdo->beginTransaction();
        try {
            // 1. Xóa tất cả vai trò cũ của user
            $this->pdo->prepare("DELETE FROM sec_user_roles WHERE user_id = ?")->execute([$userId]);
            
            // 2. Thêm các vai trò mới (nếu có)
            if (!empty($roleIds)) {
                // Tạo câu lệnh INSERT nhiều dòng: VALUES (?,?), (?,?), ...
                $sql = "INSERT INTO sec_user_roles (user_id, role_id) VALUES " . rtrim(str_repeat('(?,?),', count($roleIds)), ',');
                $values = [];
                foreach ($roleIds as $roleId) {
                    $values[] = $userId;
                    $values[] = (int)$roleId;
                }
                $this->pdo->prepare($sql)->execute($values);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error syncing roles for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách ID các quyền được gán trực tiếp cho người dùng.
     *
     * @param string $userId
     * @return array Mảng ID quyền.
     */
    public function getDirectPermissionIdsForUser(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT permission_id FROM sec_user_permissions WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0));
    }

    /**
     * Đồng bộ quyền trực tiếp cho người dùng.
     *
     * @param string $userId
     * @param array $permissionIds Mảng ID quyền mới.
     * @return bool
     */
    public function syncDirectPermissions(string $userId, array $permissionIds): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM sec_user_permissions WHERE user_id = ?")->execute([$userId]);
            if (!empty($permissionIds)) {
                $sql = "INSERT INTO sec_user_permissions (user_id, permission_id) VALUES " . rtrim(str_repeat('(?,?),', count($permissionIds)), ',');
                $values = [];
                foreach ($permissionIds as $pId) {
                    $values[] = $userId;
                    $values[] = (int)$pId;
                }
                $this->pdo->prepare($sql)->execute($values);
            }
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error syncing direct permissions for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xác thực mật khẩu người dùng.
     *
     * @param mixed $id ID người dùng.
     * @param string $password Mật khẩu nhập vào.
     * @return bool True nếu mật khẩu đúng.
     */
    public function verifyPassword($id, string $password): bool
    {
        $user = $this->find($id);
        if (!$user) return false;
        return password_verify($password, $user['password']);
    }

    /**
     * Xóa người dùng và các dữ liệu liên quan.
     *
     * @param int|string $id ID người dùng.
     * @return bool True nếu xóa thành công.
     */
    public function delete(int|string $id): bool
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Xóa vai trò của user
            $this->pdo->prepare("DELETE FROM sec_user_roles WHERE user_id = ?")->execute([$id]);
            
            // 2. Xóa quyền trực tiếp của user
            $this->pdo->prepare("DELETE FROM sec_user_permissions WHERE user_id = ?")->execute([$id]);
            
            // 3. Xóa user
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error deleting user {$id}: " . $e->getMessage());
            return false;
        }
    }
}