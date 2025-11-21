<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\PermissionModel;
use BO_System\Services\AuthService;
use ReflectionClass;

class PermissionsController extends BaseController
{
    private PermissionModel $permissionModel;

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->can(Permissions::RBAC_MANAGE_PERMISSIONS)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }
        $this->permissionModel = new PermissionModel(Database::getConnection());
    }

    /**
     * Hiển thị trang đồng bộ quyền.
     */
    public function index(): void
    {
        // Lấy tất cả quyền từ code (dùng ReflectionClass)
        $reflection = new ReflectionClass(Permissions::class);
        $permissionsFromCode = $reflection->getConstants();

        // Lấy tất cả quyền từ DB
        $permissionsFromDb = $this->permissionModel->getAllPermissions();
        $dbSlugs = array_column($permissionsFromDb, 'slug');

        // So sánh để tìm quyền mới và quyền không còn dùng
        $newPermissions = array_diff($permissionsFromCode, $dbSlugs);
        $obsoletePermissions = array_diff($dbSlugs, $permissionsFromCode);
        
        $flash_message = SessionManager::get('flash_message');
        SessionManager::unset('flash_message');

        $viewData = [
            'permissionsFromDb' => $permissionsFromDb,
            'newPermissions' => $newPermissions,
            'obsoletePermissions' => $obsoletePermissions,
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => $flash_message,
        ];

        $this->loadView('Permissions/index', $viewData);
    }

    /**
     * Xử lý hành động đồng bộ.
     */
    public function sync(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
            exit();
        }

        // Sử dụng Reflection để lấy danh sách quyền được định nghĩa trong code (class Permissions)
        $reflection = new ReflectionClass(Permissions::class);
        $permissionsFromCode = $reflection->getConstants();
        
        $permissionsToSync = [];
        foreach ($permissionsFromCode as $name => $slug) {
            // Tạo description tự động từ tên hằng số để hiển thị đẹp hơn
            // Ví dụ: FAQS_VIEW -> "Faqs View"
            $description = ucwords(strtolower(str_replace('_', ' ', $name)));
            $permissionsToSync[] = [
                'slug' => $slug,
                'description' => $description
            ];
        }

        // Gọi Model để thêm các quyền còn thiếu vào database
        $insertedCount = $this->permissionModel->addMissingPermissions($permissionsToSync);

        if ($insertedCount > 0) {
            LogHelper::info("Permissions synced. Added {$insertedCount} new permissions.");
            SessionManager::set('flash_message', ['type' => 'success', 'text' => "Đã đồng bộ thành công! Thêm mới {$insertedCount} quyền."]);
        } else {
            SessionManager::set('flash_message', ['type' => 'info', 'text' => 'Không có quyền nào mới cần đồng bộ.']);
        }
        
        // Xóa cache menu vì quyền có thể đã thay đổi, ảnh hưởng đến việc hiển thị menu
        SessionManager::unset('sidebar_menu_tree');

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
        exit();
    }
    
    /**
     * Xử lý cập nhật mô tả của một quyền.
     */
    public function update(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && !empty($description)) {
            if ($this->permissionModel->updateDescription($id, $description)) {
                LogHelper::info("Permission description updated: ID {$id} by Admin ID " . SessionManager::get('user_id'));
                SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã cập nhật mô tả quyền thành công.']);
            } else {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Cập nhật thất bại.']);
            }
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Dữ liệu không hợp lệ.']);
        }
        
        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
        exit();
    }

    /**
     * Xử lý xóa một quyền.
     */
    public function destroy(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        // Kiểm tra CSRF token (hỗ trợ cả GET và POST)
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!$this->auth->validateCsrfToken($token)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
            exit();
        }
        
        if ($id > 0) {
            // Kiểm tra xem quyền này có đang được sử dụng ở đâu không trước khi xóa
            if ($this->permissionModel->isPermissionInUse($id)) {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Không thể xóa. Quyền này đang được sử dụng bởi vai trò hoặc người dùng.']);
            } else if ($this->permissionModel->delete($id)) {
                LogHelper::warning("Permission deleted: ID {$id} by Admin ID " . SessionManager::get('user_id'));
                SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa quyền thành công.']);
            } else {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Xóa thất bại. Đã có lỗi xảy ra.']);
            }
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'ID quyền không hợp lệ.']);
        }

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Permissions']));
        exit();
    }
}