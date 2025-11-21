<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Core\Validator;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\PermissionModel;
use BO_System\Models\RoleModel;
use BO_System\Models\UserModel;
use BO_System\Services\AuthService;

class UsersController extends BaseController
{
    private UserModel $userModel;
    private RoleModel $roleModel;
    private PermissionModel $permissionModel;

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->can(Permissions::USERS_VIEW)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }
        
        $this->userModel = new UserModel(Database::getConnection());
        $this->roleModel = new RoleModel(Database::getConnection());
        $this->permissionModel = new PermissionModel(Database::getConnection());
    }

    /**
     * Hiển thị danh sách người dùng.
     */
    public function index(): void
    {
        $viewData = [
            'users' => $this->userModel->getAllUsersWithRoles(),
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => SessionManager::get('flash_message'),
        ];
        SessionManager::unset('flash_message');
        $this->loadView('Users/index', $viewData);
    }

    /**
     * Hiển thị form tạo người dùng mới.
     */
    public function create(): void
    {
        if (!$this->auth->can(Permissions::USERS_CREATE)) {
            http_response_code(403); die('403 - Bạn không có quyền tạo người dùng mới.');
        }
        
        $this->showForm();
    }

    /**
     * Hiển thị form chỉnh sửa người dùng.
     */
    public function edit(): void
    {
        // Kiểm tra quyền sửa
        if (!$this->auth->can(Permissions::USERS_EDIT)) {
            http_response_code(403); die('403 - Bạn không có quyền chỉnh sửa người dùng.');
        }
        
        $this->showForm((string)$_GET['id'] ?? null);
    }
    
    /**
     * Hàm nội bộ để hiển thị form (dùng chung cho create và edit).
     *
     * @param string|null $userId ID người dùng (nếu là edit).
     */
    private function showForm(?string $userId = null): void
    {
        $user = null;
        if ($userId) {
            // Nếu là edit, tìm user theo ID
            $user = $this->userModel->find($userId);
            if (!$user) {
                $this->renderContent(Helper::renderError("Người dùng không tồn tại."));
                return;
            }
        }
        
        // Lấy danh sách tất cả vai trò và quyền để hiển thị trong form
        $allRoles = $this->roleModel->getAllRoles();
        $allPermissions = $this->permissionModel->getAllPermissions();
        
        // Lấy các vai trò và quyền hiện tại của user (nếu đang edit)
        $userRoleIds = $userId ? $this->userModel->getRoleIdsForUser($userId) : [];
        $userDirectPermissionIds = $userId ? $this->userModel->getDirectPermissionIdsForUser($userId) : [];

        // Tính toán các quyền mà user đã có thông qua vai trò (để disable checkbox hoặc hiển thị thông tin)
        $permissionsFromRoles = [];
        if(!empty($userRoleIds)){
            foreach($userRoleIds as $roleId){
                $permissionsFromRoles = array_merge($permissionsFromRoles, $this->roleModel->getPermissionIdsForRole($roleId));
            }
        }
        $permissionsFromRoles = array_unique($permissionsFromRoles);

        $viewData = [
            'user' => $user,
            'allRoles' => $allRoles,
            'allPermissions' => $allPermissions,
            'userRoleIds' => $userRoleIds,
            'userDirectPermissionIds' => $userDirectPermissionIds,
            'permissionsFromRoles' => $permissionsFromRoles,
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => SessionManager::get('flash_message'),
            'errors' => SessionManager::get('errors', []),
            'old_input' => SessionManager::get('old_input', []),
        ];
        
        // Xóa session flash data sau khi đã lấy
        SessionManager::unset('flash_message');
        SessionManager::unset('errors');
        SessionManager::unset('old_input');

        $this->loadView('Users/edit', $viewData);
    }


    /**
     * Xử lý lưu thông tin người dùng (Tạo mới hoặc Cập nhật).
     */
    public function store(): void
    {
        $userId = $_POST['user_id'] ?? null;
        $isCreating = empty($userId);

        if ($isCreating && !$this->auth->can(Permissions::USERS_CREATE)) {
            http_response_code(403); die('403 - Bạn không có quyền tạo người dùng mới.');
        }
        if (!$isCreating && !$this->auth->can(Permissions::USERS_EDIT)) {
            http_response_code(403); die('403 - Bạn không có quyền chỉnh sửa người dùng.');
        }

        // Kiểm tra quyền quản lý vai trò
        if (isset($_POST['roles']) && !$this->auth->can(Permissions::USERS_MANAGE_ROLES)) {
             // Xử lý lỗi: Người dùng cố tình gửi dữ liệu vai trò mà không có quyền
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Bạn không có quyền thay đổi vai trò.']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // 1. Kiểm tra CSRF Token
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF). Vui lòng thử lại.']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // 2. Định nghĩa Rules Validation
        $rules = [
            'full_name' => 'required|max:255',
            'username' => 'required|max:50',
            'email' => 'required|email|max:100',
        ];
        
        if ($isCreating) {
            $rules['password'] = 'required|min:6';
        }

        $validator = Validator::make($_POST, $rules);

        if ($validator->fails()) {
            SessionManager::set('errors', $validator->getErrors());
            SessionManager::set('old_input', $_POST);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }

        // Dữ liệu profile cơ bản
        $profileData = [
            'full_name' => trim($_POST['full_name']),
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'phone_1' => $_POST['phone_1'] ?? null,
            'phone_2' => $_POST['phone_2'] ?? null,
        ];
        
        // Chỉ cập nhật mật khẩu nếu người dùng nhập vào
        if (!empty($_POST['password'])) {
            $profileData['password'] = $_POST['password'];
        }

        // Dữ liệu phân quyền (Roles & Direct Permissions)
        $roleIds = $_POST['roles'] ?? [];
        $directPermissionIds = $_POST['permissions'] ?? [];
        
        $allSuccess = true;
        if ($isCreating) {
            // Tạo mới user
            $newUserId = $this->userModel->createUser($profileData);
            if ($newUserId) {
                $userId = $newUserId;
                SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Tạo người dùng thành công!']);
            } else {
                $allSuccess = false;
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tạo người dùng thất bại. Username hoặc email có thể đã tồn tại.']);
            }
        } else { 
            // Cập nhật user hiện tại
            if (!$this->userModel->update($userId, $profileData)) {
                $allSuccess = false;
            }
        }
        
        // Đồng bộ vai trò và quyền nếu profile được tạo/cập nhật thành công
        if ($allSuccess && $userId) {
            if (!$this->userModel->syncRoles($userId, $roleIds)) $allSuccess = false;
            if (!$this->userModel->syncDirectPermissions($userId, $directPermissionIds)) $allSuccess = false;
        }

        if ($allSuccess) {
            $action = $isCreating ? 'created' : 'updated';
            LogHelper::info("User {$action}: '{$profileData['username']}' (ID: {$userId}) by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Lưu thông tin người dùng thành công!']);
        } else if (!SessionManager::has('flash_message')) {
            LogHelper::error("Failed to save user '{$profileData['username']}'.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra khi lưu.']);
        }
        
        $redirectUrl = Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Users', 'action' => 'edit', 'id' => $userId]);
        header('Location: ' . $redirectUrl);
        exit();
    }
    
    /**
     * Xóa người dùng.
     */
    public function destroy(): void
    {
        if (!$this->auth->can(Permissions::USERS_DELETE)) {
            http_response_code(403); die('403 - Bạn không có quyền xóa người dùng.');
        }
        
        // Kiểm tra CSRF Token
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!$this->auth->validateCsrfToken($token)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Users']));
            exit();
        }

        $userId = $_GET['id'] ?? null;
        // Thêm logic để ngăn người dùng tự xóa chính mình
        if ($userId === SessionManager::get('user_id')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Bạn không thể tự xóa tài khoản của chính mình.']);
        } else if ($userId) {
            if ($this->userModel->delete($userId)) {
                LogHelper::warning("User deleted: ID {$userId} by Admin ID " . SessionManager::get('user_id'));
                SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa người dùng thành công.']);
            } else {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra khi xóa người dùng.']);
            }
        }
        
        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Users']));
        exit();
    }
}