<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\PermissionModel;
use BO_System\Models\RoleModel;
use BO_System\Services\AuthService;

class RolesController extends BaseController
{
    private RoleModel $roleModel;
    private PermissionModel $permissionModel;

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->can(Permissions::RBAC_MANAGE_ROLES)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }
        $this->roleModel = new RoleModel(Database::getConnection());
        $this->permissionModel = new PermissionModel(Database::getConnection());
    }

    /**
     * Hiển thị ma trận Vai trò & Quyền.
     */
    public function index(): void
    {
        $roles = $this->roleModel->getAllRoles();
        $permissions = $this->permissionModel->getAllPermissions();

        // Tạo một mảng để dễ dàng truy cập quyền của từng vai trò
        $rolePermissions = [];
        foreach ($roles as $role) {
            $rolePermissions[$role['id']] = $this->roleModel->getPermissionIdsForRole($role['id']);
        }
        
        $flash_message = SessionManager::get('flash_message');
        SessionManager::unset('flash_message');

        $viewData = [
            'roles' => $roles,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => $flash_message,
        ];

        $this->loadView('Roles/index', $viewData);
    }

    /**
     * Xử lý việc lưu ma trận quyền.
     */
    public function updatePermissions(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực form.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Roles']));
            exit();
        }

        // $_POST['permissions'] sẽ là một mảng dạng ['role_id' => ['permission_id_1', 'permission_id_2', ...]]
        $permissionsByRole = $_POST['permissions'] ?? [];
        $allRoles = $this->roleModel->getAllRoles();

        $allSuccess = true;
        // Duyệt qua từng vai trò để cập nhật quyền
        foreach ($allRoles as $role) {
            $roleId = $role['id'];
            // Lấy danh sách permission ID cho vai trò này từ form, nếu không có thì là mảng rỗng (xóa hết quyền)
            $permissionIdsForThisRole = $permissionsByRole[$roleId] ?? [];
            
            // Đồng bộ hóa quyền cho vai trò
            if (!$this->roleModel->syncPermissions($roleId, $permissionIdsForThisRole)) {
                $allSuccess = false;
            }
        }

        if ($allSuccess) {
            LogHelper::info("Permissions matrix updated by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật quyền cho các vai trò thành công!']);
        } else {
            LogHelper::error("Failed to update permissions matrix.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra trong quá trình cập nhật.']);
        }

        // Xóa cache menu của tất cả user để họ thấy thay đổi ngay lập tức
        SessionManager::unset('sidebar_menu_tree'); 

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Roles']));
        exit();
    }
    
    /**
     * Xử lý việc tạo vai trò mới.
     */
    public function store(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Roles']));
            exit();
        }

        // Validation có thể được thêm ở đây
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($slug)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tên và Slug của vai trò là bắt buộc.']);
        } else {
            $result = $this->roleModel->createRole($name, $slug, $description);
            if ($result) {
                LogHelper::info("Role created: '{$name}' ({$slug}) by Admin ID " . SessionManager::get('user_id'));
                SessionManager::set('flash_message', ['type' => 'success', 'text' => "Đã tạo vai trò '{$name}' thành công."]);
            } else {
                LogHelper::error("Failed to create role: '{$name}' ({$slug}). Slug might exist.");
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tạo vai trò thất bại. Slug có thể đã tồn tại.']);
            }
        }

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Roles']));
        exit();
    }
}