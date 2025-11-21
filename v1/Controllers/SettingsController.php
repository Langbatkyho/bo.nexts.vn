<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\SessionManager;
use BO_System\Core\Validator;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\UserModel;
use BO_System\Services\AuthService;

class SettingsController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        
        // Cổng kiểm tra chính
        if (!$this->auth->can('settings.view')) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }

        $this->userModel = new UserModel(Database::getConnection());
    }

    /**
     * Hiển thị trang cài đặt thông tin tài khoản (GET request).
     */
    public function index(): void
    {
        $userId = SessionManager::get('user_id');
        if (!$userId) {
            // Trường hợp hy hữu session bị mất giữa chừng
            header('Location: /' . APP_VERSION . '/login.php');
            exit();
        }

        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->renderContent(Helper::renderError("Không tìm thấy thông tin người dùng."));
            return;
        }

        // Lấy và xóa flash data từ session (sau một lần redirect)
        $flash_message = SessionManager::get('flash_message');
        $errors = SessionManager::get('errors', []);
        $old_input = SessionManager::get('old_input', []);
        SessionManager::unset('flash_message');
        SessionManager::unset('errors');
        SessionManager::unset('old_input');

        // Chuẩn bị dữ liệu cho View
        $viewData = [
            'user' => $old_input ?: $user, // Ưu tiên input cũ nếu có lỗi validation
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => $flash_message,
            'errors' => $errors,
        ];

        $this->loadView('Settings/index', $viewData);
    }

    /**
     * Xử lý cập nhật thông tin profile (không bao gồm mật khẩu).
     */
    public function updateProfile(): void
    {
        if (!$this->auth->can('settings.update-profile')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $userId = SessionManager::get('user_id');
        $redirectUrl = Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Settings']);

        // Kiểm tra CSRF Token
        if (!hash_equals($this->auth->generateCsrfToken(), $_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực form.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Validate dữ liệu
        $validator = Validator::make($_POST, [
            'full_name' => 'required|max:255',
            'username' => 'required|max:50',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            SessionManager::set('errors', $validator->getErrors());
            SessionManager::set('old_input', $_POST);
            header('Location: ' . $redirectUrl);
            exit();
        }

        $dataToUpdate = [
            'full_name' => trim($_POST['full_name']),
            'username' => trim($_POST['username']),
            'email' => trim($_POST['email']),
            'phone_1' => trim($_POST['phone_1'] ?? null),
            'phone_2' => trim($_POST['phone_2'] ?? null),
        ];

        $success = $this->userModel->update($userId, $dataToUpdate);

        if ($success) {
            LogHelper::info("User profile updated: ID {$userId}");
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật thông tin thành công!']);
            
            // Cập nhật lại thông tin user trong session để hiển thị ngay trên layout (header/sidebar)
            $updatedUser = $this->userModel->find($userId);
            if ($updatedUser) {
                SessionManager::set('user_info', $updatedUser);
                SessionManager::set('username', $updatedUser['username']);
            }
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra hoặc không có gì thay đổi.']);
            SessionManager::set('old_input', $_POST);
        }

        header('Location: ' . $redirectUrl);
        exit();
    }
    
    /**
     * Xử lý thay đổi mật khẩu từ form trong popup.
     */
    public function changePassword(): void
    {
        if (!$this->auth->can('settings.change-password')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $userId = SessionManager::get('user_id');
        $redirectUrl = Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Settings']);
        
        if (!hash_equals($this->auth->generateCsrfToken(), $_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực form mật khẩu.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        $validator = Validator::make($_POST, [
            'current_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required',
        ]);
        if ($validator->fails()) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Vui lòng nhập đầy đủ các trường mật khẩu.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Xác thực mật khẩu cũ
        if (!$this->userModel->verifyPassword($userId, $_POST['current_password'])) {
            LogHelper::warning("Password change failed: Incorrect current password for User ID {$userId}");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Mật khẩu hiện tại không chính xác.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Kiểm tra mật khẩu mới và xác nhận
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Mật khẩu mới và mật khẩu xác nhận không khớp.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        $success = $this->userModel->update($userId, ['password' => $_POST['new_password']]);

        if ($success) {
            LogHelper::info("Password changed successfully for User ID {$userId}");
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Thay đổi mật khẩu thành công!']);
        } else {
            LogHelper::error("Password change failed for User ID {$userId} (Database error)");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra khi thay đổi mật khẩu.']);
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
}