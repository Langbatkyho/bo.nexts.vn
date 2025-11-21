<?php
namespace BO_System\Services;

use BO_System\Core\SessionManager;
use BO_System\Models\MenuModel;

/**
 * Lớp MenuService xử lý logic liên quan đến hiển thị menu.
 */
class MenuService
{
    private MenuModel $menuModel;
    private AuthService $auth;
    private static ?self $instance = null;

    public function __construct(MenuModel $menuModel, AuthService $authService)
    {
        $this->menuModel = $menuModel;
        $this->auth = $authService;
    }

    public static function init(MenuModel $menuModel, AuthService $authService): self
    {
        if (self::$instance === null) {
            self::$instance = new self($menuModel, $authService);
        }
        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \Exception('MenuService chưa được khởi tạo.');
        }
        return self::$instance;
    }

    /**
     * Lấy cây menu đã được lọc và xác định menu cha đang active.
     * Tích hợp cache vào session để tối ưu hiệu suất.
     *
     * @param string $currentPageModule Module của trang hiện tại.
     * @return array Mảng chứa ['tree' => ..., 'activeParent' => ...].
     */
    public function getFilteredMenu(string $currentPageModule): array
    {
        // Tạo một key cache duy nhất dựa trên vai trò của user
        // Điều này đảm bảo mỗi vai trò có cache riêng
        $roles = SessionManager::get('user_roles', []);
        sort($roles); // Sắp xếp để đảm bảo key nhất quán
        $cacheKey = 'sidebar_menu_' . md5(implode(',', $roles));
    
        $cachedData = SessionManager::get($cacheKey);
        if ($cachedData !== null) {
            // Cache đã có, chỉ cần tìm active parent
            $cachedData['activeParent'] = $this->findActiveParent($cachedData['tree'], $currentPageModule);
            return $cachedData;
        }
    
        // 1. Lấy tất cả menu items từ DB
        $allItems = $this->menuModel->findAllOrdered();
        if (empty($allItems)) {
            return ['tree' => [], 'activeParent' => ''];
        }
    
        // 2. Lọc các menu items dựa trên quyền của user
        $allowedItems = [];
        foreach ($allItems as $item) {
            if (empty($item['required_permission_slug']) || $this->auth->can($item['required_permission_slug'])) {
                $allowedItems[] = $item;
            }
        }
    
        // 3. Xây dựng cây từ danh sách đã lọc
        $tree = $this->buildTree($allowedItems);
    
        // 4. Tìm menu cha đang active
        $activeParent = $this->findActiveParent($tree, $currentPageModule);
    
        // 5. Lưu vào cache session và trả về
        $dataToCache = ['tree' => $tree, 'activeParent' => $activeParent];
        SessionManager::set($cacheKey, $dataToCache);
        
        return $dataToCache;
    }
    
    /**
     * Tìm tên (title) của menu cha chứa mục menu đang active.
     *
     * @param array $menuTree Cây menu.
     * @param string $activeModule Module đang active.
     * @return string Tên của menu cha, hoặc chuỗi rỗng.
     */
    private function findActiveParent(array $menuTree, string $activeModule): string
    {
        foreach ($menuTree as $parentItem) {
            if (!empty($parentItem['children'])) {
                foreach ($parentItem['children'] as $childItem) {
                    if ($childItem['module'] === $activeModule) {
                        return $parentItem['title']; // Tìm thấy! Trả về title của cha.
                    }
                }
            }
        }
        return ''; // Không tìm thấy trong bất kỳ group nào
    }

    /**
     * Xây dựng cấu trúc cây đa cấp từ một danh sách phẳng.
     *
     * @param array $elements Mảng các items có 'id' và 'parent_id'.
     * @return array Mảng cây đa cấp.
     */
    private function buildTree(array &$elements): array
    {
        $menuById = [];
        foreach ($elements as $element) {
            $menuById[$element['id']] = $element;
            $menuById[$element['id']]['children'] = [];
        }

        $tree = [];
        foreach ($menuById as $id => &$node) {
            if ($node['parent_id'] && isset($menuById[$node['parent_id']])) {
                // Nếu có cha, thêm mình vào mảng children của cha
                $menuById[$node['parent_id']]['children'][] = &$node;
            } else {
                // Nếu không có cha, đây là mục cấp 1
                $tree[] = &$node;
            }
        }
        return $tree;
    }

    /**
     * Lấy menu bên sidebar đã được lọc theo quyền của user.
     *
     * @param string $userId ID của user cần lấy menu.
     * @return array Danh sách menu hiển thị bên sidebar.
     */
    public function getSidebarMenu(string $userId): array
    {
        // Tải quyền của user trước khi lọc menu
        $this->permissions->loadUserPermissions($userId);
        $allMenus = $this->menuModel->findAllOrdered();
        
        // Lọc menu theo quyền
        $visibleMenus = array_filter($allMenus, function ($menu) {
            if (empty($menu['required_permission_slug'])) {
                return true; // Không yêu cầu quyền -> hiển thị cho tất cả
            }
            // Kiểm tra xem user có quyền yêu cầu không
            return $this->permissions->hasPermission($menu['required_permission_slug']);
        });

        // Xây dựng cây menu phân cấp từ danh sách phẳng
        return $this->buildMenuTree($visibleMenus);
    }
}