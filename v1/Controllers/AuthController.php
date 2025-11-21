<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Config;
use BO_System\Core\SessionManager;
use BO_System\Core\Validator;
use BO_System\Helpers\LogHelper;

class AuthController extends BaseController
{
    /**
     * Hiển thị form đăng nhập (GET request).
     */
    public function index(): void
    {
        if ($this->auth->isLoggedIn()) {
            $this->auth->handleLoginRedirect();
        }

        $errors = SessionManager::get('errors', []);
        $old_input = SessionManager::get('old_input', []);
        SessionManager::unset('errors');
        SessionManager::unset('old_input');
        
        $error_message = SessionManager::get('error_message', '');
        SessionManager::unset('error_message');
        
        // Chuẩn bị dữ liệu cho View
        $viewData = [
            'system_name'    => Config::get('SYSTEM_NAME'),
            'brand_name'     => Config::get('BRAND_NAME'),
            'email_admin'    => Config::get('EMAIL_ADMIN'),
            'logo_white_url' => Config::get('LOGO_WHITE_URL'),
            'favicon_url'    => Config::get('FAVICON_URL'),
            'csrf_token'     => $this->auth->generateCsrfToken(),
            'error_message'  => $error_message,
            'errors'         => $errors,
            'old_input'      => $old_input,
            'is_locked_out'  => $this->auth->isLockedOut()
        ];
        
        $this->loadView('Auth/login', $viewData);
    }

    /**
     * Xử lý dữ liệu form đăng nhập (POST request).
     */
    public function login(): void
    {
        // Kiểm tra CSRF token để ngăn chặn tấn công giả mạo
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->auth->validateCsrfToken($token)) {
            LogHelper::warning("CSRF validation failed during login attempt.");
            SessionManager::set('error_message', 'Lỗi xác thực form. Vui lòng thử lại.');
            header('Location: /' . APP_VERSION . '/login.php');
            exit();
        }
        
        // Validate dữ liệu đầu vào
        $validator = Validator::make($_POST, [
            'username' => 'required|max:100',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            SessionManager::set('errors', $validator->getErrors());
            SessionManager::set('old_input', $_POST);
            header('Location: /' . APP_VERSION . '/login.php');
            exit();
        }

        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Thử đăng nhập thông qua AuthService
        if ($this->auth->attempt($username, $password)) {
            // Đăng nhập thành công -> chuyển hướng
            $this->auth->handleLoginRedirect();
        } else {
            // Đăng nhập thất bại -> lưu input cũ và quay lại trang login
            SessionManager::set('old_input', $_POST);
            header('Location: /' . APP_VERSION . '/login.php');
            exit();
        }
    }
}