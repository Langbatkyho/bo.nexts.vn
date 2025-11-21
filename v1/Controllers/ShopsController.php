<?php
namespace BO_System\Controllers;

use BO_System\Core\Config;
use BO_System\Core\SessionManager;
use BO_System\Core\Validator;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\ShopModel;

class ShopsController extends BaseController
{
    private ShopModel $shopModel;

    public function __construct()
    {
        parent::__construct();

        // Nếu không có quyền xem, dừng lại ngay lập tức
        if (!$this->auth->can('shops.view')) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }

        $pdo = Config::connectPDO();
        $this->shopModel = new ShopModel($pdo);
    }

    public function index(): void
    {
        $shops = $this->shopModel->all('id', 'DESC');

        // Lấy và xóa flash message
        $flash_message = SessionManager::get('flash_message');
        SessionManager::unset('flash_message');

        $this->loadView('Shops/index', [
            'shops' => $shops,
            'flash_message' => $flash_message
        ]);
    }

    /**
     * Hiển thị form để chỉnh sửa một shop (GET request).
     */
    public function edit(): void
    {
        // Kiểm tra quyền
        if (!$this->auth->can('shops.edit')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $shop_id = (int)($_GET['id'] ?? 0);
        if ($shop_id <= 0) {
            $this->renderContent(Helper::renderError("ID shop không hợp lệ."));
            return;
        }
        
        $shop = $this->shopModel->find($shop_id);
        if (!$shop) {
            $this->renderContent(Helper::renderError("Không tìm thấy shop với ID này."));
            return;
        }

        // Lấy và xóa flash data từ session
        $flash_message = SessionManager::get('flash_message');
        $errors = SessionManager::get('errors', []);
        $old_input = SessionManager::get('old_input', []);
        SessionManager::unset('flash_message');
        SessionManager::unset('errors');
        SessionManager::unset('old_input');

        // Chuẩn bị dữ liệu cho View
        $viewData = [
            'shop' => $old_input ?: $shop,
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => $flash_message,
            'errors' => $errors,
        ];

        $this->loadView('Shops/edit', $viewData);
    }

    /**
     * Xử lý việc cập nhật dữ liệu từ form edit (POST request).
     */
    public function update(): void
    {
        // Kiểm tra quyền
        if (!$this->auth->can('shops.edit')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $shop_id = (int)($_GET['id'] ?? 0);
        $redirectUrl = Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Shops', 'action' => 'edit', 'id' => $shop_id]);

        // Kiểm tra CSRF Token
        if (!hash_equals(SessionManager::get('csrf_token'), $_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực form không hợp lệ. Vui lòng thử lại.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Validate dữ liệu đầu vào
        $validator = Validator::make($_POST, [
            'shop_name' => 'required|max:255',
            'platform' => 'required',
            'platform_shop_id' => 'required',
            'platform_shop_code' => 'required',
        ]);

        if ($validator->fails()) {
            SessionManager::set('errors', $validator->getErrors());
            SessionManager::set('old_input', $_POST);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Chuẩn bị dữ liệu và gọi Model
        $shopData = [
            'shop_name'           => trim($_POST['shop_name']),
            'platform'            => trim($_POST['platform']),
            'platform_shop_id'    => trim($_POST['platform_shop_id']),
            'platform_shop_code'  => trim($_POST['platform_shop_code']),
            'access_token'        => trim($_POST['access_token'] ?? ''),
            'refresh_token'       => trim($_POST['refresh_token'] ?? ''),
        ];

        $success = $this->shopModel->update($shop_id, $shopData);
        
        // Set Flash Message và Redirect
        if ($success) {
            LogHelper::info("Shop updated: ID {$shop_id} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật shop thành công!']);
        } else {
            LogHelper::error("Failed to update shop: ID {$shop_id}.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra trong quá trình cập nhật.']);
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
    
    /**
     * Xóa một shop.
     */
    public function destroy(): void
    {
        if (!$this->auth->can('shops.delete')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $shopIdToDelete = (int)($_GET['id'] ?? 0);
        if ($shopIdToDelete > 0) {
            $this->shopModel->delete($shopIdToDelete);
            LogHelper::warning("Shop deleted: ID {$shopIdToDelete} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa shop thành công.']);
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'ID shop không hợp lệ.']);
        }
        header("Location: " . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Shops', 'action' => 'index']));
        exit();
    }

    // Optional: helper action to redirect to connect endpoint (not required)
    public function connectPlatform(): void
    {
        $platform = $_GET['platform'] ?? '';
        if (empty($platform)) {
            LogHelper::warning('connectPlatform called without platform');
            $this->renderContent('Missing platform parameter');
            return;
        }

        // Redirect to auth connector file which will handle state and redirect to provider
        header('Location: /' . APP_VERSION . '/auth/connect_platform.php?platform=' . urlencode($platform));
        exit();
    }
}
