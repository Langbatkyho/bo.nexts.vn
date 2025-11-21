<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\MenuModel;
use BO_System\Models\PermissionModel;
use BO_System\Services\AuthService;

class MenusController extends BaseController
{
    private MenuModel $menuModel;
    private PermissionModel $permissionModel;
    
    // Đặt quyền cần thiết để truy cập module này
    const MANAGE_MENU_PERMISSION = Permissions::RBAC_MANAGE_ROLES;

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->can(self::MANAGE_MENU_PERMISSION)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }
        $this->menuModel = new MenuModel(Database::getConnection());
        $this->permissionModel = new PermissionModel(Database::getConnection());
    }

    /**
     * Hiển thị danh sách menu và form quản lý.
     */
    public function index(): void
    {
        $allItems = $this->menuModel->findAllOrdered();
        $menuTree = $this->buildTree($allItems);
        
        $viewData = [
            'menuTree' => $menuTree,
            'allPermissions' => $this->permissionModel->getAllPermissions(),
            'allItems' => $allItems,
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => SessionManager::get('flash_message'),
        ];
        SessionManager::unset('flash_message');
        
        $this->loadView('Menus/index', $viewData);
    }

    /**
     * Tạo mục menu mới.
     */
    public function store(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Menus']));
            exit();
        }

        $data = $_POST;
        if ($this->menuModel->createMenuItem($data)) {
            LogHelper::info("Menu item created: '{$data['title']}' by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã tạo mục menu thành công.']);
        } else {
            LogHelper::error("Failed to create menu item: '{$data['title']}'.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tạo mục menu thất bại.']);
        }
        $this->clearMenuCache();
        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Menus']));
        exit();
    }

    /**
     * Cập nhật mục menu.
     */
    public function update(): void
    {
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Menus']));
            exit();
        }

        $id = (int)$_POST['id'];
        $data = $_POST;
        if ($this->menuModel->updateMenuItem($id, $data)) {
            LogHelper::info("Menu item updated: ID {$id} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật mục menu thành công.']);
        } else {
            LogHelper::error("Failed to update menu item: ID {$id}.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Cập nhật mục menu thất bại.']);
        }
        $this->clearMenuCache();
        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Menus']));
        exit();
    }

    /**
     * Xóa mục menu.
     */
    public function destroy(): void
    {
        $id = (int)$_GET['id'];
        if ($this->menuModel->delete($id)) {
            LogHelper::warning("Menu item deleted: ID {$id} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa mục menu.']);
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Xóa mục menu thất bại.']);
        }
        $this->clearMenuCache();
        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Menus']));
        exit();
    }

    /**
     * Lưu thứ tự sắp xếp menu (AJAX).
     */
    public function saveOrder(): void
    {
        header('Content-Type: application/json');

        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Lỗi xác thực bảo mật (CSRF).']);
            exit();
        }

        // Dữ liệu cấu trúc menu được gửi dưới dạng JSON từ thư viện Nestable/Sortable
        $menuStructure = json_decode($_POST['structure'] ?? '[]', true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // Cập nhật cấu trúc vào database
            $success = $this->menuModel->updateMenuStructure($menuStructure);
            if ($success) {
                LogHelper::info("Menu order updated by Admin ID " . SessionManager::get('user_id'));
                $this->clearMenuCache();
                echo json_encode(['success' => true, 'message' => 'Đã lưu thứ tự menu.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu thứ tự.']);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        }
        exit();
    }
    
    /**
     * Hàm đệ quy xây dựng cây menu.
     *
     * @param array $elements Danh sách các mục menu.
     * @param int|null $parentId ID cha.
     * @return array Cây menu.
     */
    private function buildTree(array &$elements, $parentId = null): array
    {
        $branch = [];
        foreach ($elements as &$element) {
            // Nếu phần tử này thuộc về parentId đang xét
            if ($element['parent_id'] == $parentId) {
                // Đệ quy để tìm các con của phần tử này
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
                // Giải phóng bộ nhớ (tùy chọn, nhưng tốt cho mảng lớn)
                unset($element);
            }
        }
        return $branch;
    }
    
    /**
     * Xóa cache menu để cập nhật thay đổi ngay lập tức.
     */
    private function clearMenuCache(): void
    {
        // Xóa cache menu trong session để nó được tải lại
        // Sau này có thể cải tiến để xóa cache theo từng vai trò
        $sessionData = &$_SESSION;
        foreach ($sessionData as $key => $value) {
            if (strpos($key, 'sidebar_menu_') === 0) {
                unset($sessionData[$key]);
            }
        }
    }
}