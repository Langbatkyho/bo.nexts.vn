<?php
namespace BO_System\Services;

use BO_System\Core\SessionManager;
use BO_System\Models\AuthModel;
use BO_System\Models\PermissionModel; 
use BO_System\Helpers\LogHelper;
use BO_System\Helpers\SecurityHelper;

/**
 * Lớp AuthService quản lý toàn bộ logic và quy trình nghiệp vụ liên quan đến xác thực người dùng.
 */
class AuthService
{
    private AuthModel $authModel;
    private PermissionModel $permissionModel;
    private static ?self $instance = null;

    // Sửa constructor để nhận cả PermissionModel (giờ đây PHP đã biết lớp này ở đâu)
    public function __construct(AuthModel $authModel, PermissionModel $permissionModel)
    {
        $this->authModel = $authModel;
        $this->permissionModel = $permissionModel;
    }
    
    // Cập nhật hàm init (giờ đây PHP đã biết lớp này ở đâu)
    public static function init(AuthModel $authModel, PermissionModel $permissionModel): AuthService
    {
        if (self::$instance === null) {
            self::$instance = new self($authModel, $permissionModel);
        }
        return self::$instance;
    }

    /**
     * Lấy instance duy nhất của AuthService (Singleton pattern).
     * 
     * @return AuthService
     * @throws \Exception Nếu AuthService chưa được khởi tạo.
     */
    public static function getInstance(): AuthService
    {
        if (self::$instance === null) {
            throw new \Exception('AuthService chưa được khởi tạo. Vui lòng gọi AuthService::init() trước.');
        }
        return self::$instance;
    }

    /**
     * Nâng cấp hàm attempt để tải quyền sau khi đăng nhập thành công.
     */
    public function attempt(string $username, string $password): bool
    {
        $user = $this->authModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] == 1) {
                $this->clearLoginAttempts();
                session_regenerate_id(true);
                SessionManager::set('user_id', $user['id']);
                SessionManager::set('username', $user['username']);
                SessionManager::set('last_login', time());

                // Log successful login
                LogHelper::info("User '{$username}' (ID: {$user['id']}) logged in successfully.");

                // TẢI VAI TRÒ VÀ QUYỀN HẠN SAU KHI ĐĂNG NHẬP
                $this->loadPermissionsForUser($user['id']);

                return true;
            } else {
                LogHelper::warning("Failed login attempt: User '{$username}' is inactive.");
                SessionManager::set('error_message', 'Tài khoản của bạn đã bị khóa.');
                return false;
            }
        }

        LogHelper::warning("Failed login attempt for username: '{$username}'. Invalid credentials.");
        $this->recordFailedLoginAttempt();
        SessionManager::set('error_message', $this->getFailedLoginMessage());
        return false;
    }
    
    /**
     * Tải và lưu vai trò, quyền hạn của người dùng vào session.
     */
    public function loadPermissionsForUser(string $userId): void
    {
        $roles = $this->permissionModel->getRoleSlugsForUser($userId);
        $permissions = $this->permissionModel->getPermissionSlugsForUser($userId);
        
        SessionManager::set('user_roles', $roles);
        SessionManager::set('user_permissions', $permissions);
    }
    
    /**
     * Kiểm tra xem người dùng hiện tại có một quyền hạn cụ thể hay không.
     * @param string $permissionSlug Slug của quyền cần kiểm tra.
     * @return bool
     */
    public function can(string $permissionSlug): bool
    {
        // Super Admin có mọi quyền
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permissions = SessionManager::get('user_permissions', []);
        return in_array($permissionSlug, $permissions);
    }

    /**
     * Kiểm tra xem người dùng có một vai trò cụ thể hay không.
     * @param string $roleSlug Slug của vai trò cần kiểm tra.
     * @return bool
     */
    public function hasRole(string $roleSlug): bool
    {
        $roles = SessionManager::get('user_roles', []);
        return in_array($roleSlug, $roles);
    }

    /**
     * Kiểm tra xem AuthService đã được khởi tạo hay chưa.
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$instance !== null;
    }
    
    /**
     * Kiểm tra xem người dùng đã đăng nhập hay chưa.
     * 
     * @return bool True nếu đã đăng nhập, False nếu chưa.
     */
    public function isLoggedIn(): bool
    {
        return SessionManager::has('user_id');
    }

    /**
     * Đăng xuất người dùng khỏi hệ thống.
     * Xóa session và chuyển hướng về trang đăng nhập.
     * 
     * @return void
     */
    public function logout(): void
    {
        $username = SessionManager::get('username');
        if ($username) {
            LogHelper::info("User '{$username}' logged out.");
        }
        SessionManager::destroy();
        header('Location: /' . APP_VERSION . '/login.php');
        exit();
    }
    
    /**
     * Xử lý chuyển hướng sau khi đăng nhập thành công.
     * Chuyển đến trang đích đã lưu trước đó hoặc trang chủ.
     * 
     * @return void
     */
    public function handleLoginRedirect(): void
    {
        $redirect_url = SessionManager::get('redirect_url', '/' . APP_VERSION . '/index.php');
        SessionManager::unset('redirect_url');
        header('Location: ' . $redirect_url);
        exit();
    }
    
    /**
     * Làm mới thông tin người dùng trong session từ database.
     * Đảm bảo dữ liệu luôn đồng bộ (ví dụ: khi user bị khóa hoặc đổi tên).
     */
    public function refreshUserData(): void
    {
        $userId = SessionManager::get('user_id');
        $userInfo = SessionManager::get('user_info');

        // Nếu thông tin trong session không khớp hoặc thiếu, query lại từ DB
        if (!$userInfo || $userInfo['id'] !== $userId) {
            try {
                // Sử dụng AuthModel để tìm user (kế thừa từ BaseModel)
                $user = $this->authModel->find($userId);

                if ($user) {
                    // Chỉ lưu những thông tin cần thiết vào session
                    $userData = [
                        'id' => $user['id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'username' => $user['username']
                    ];
                    SessionManager::set('user_info', $userData);
                } else {
                    // Trường hợp user bị xóa khỏi DB nhưng session vẫn còn -> Đăng xuất ngay
                    $this->logout();
                }
            } catch (\Exception $e) {
                LogHelper::error("Lỗi khi làm mới thông tin user: " . $e->getMessage());
                $this->logout();
            }
        }
    }

    // --- CSRF Protection ---

    /**
     * Tạo CSRF token mới hoặc lấy token hiện tại.
     * Sử dụng SecurityHelper để xử lý.
     * 
     * @return string Token CSRF.
     */
    public function generateCsrfToken(): string
    {
        return SecurityHelper::generateCsrfToken();
    }

    /**
     * Kiểm tra tính hợp lệ của CSRF token.
     * Sử dụng SecurityHelper để xử lý.
     * 
     * @param string $token Token cần kiểm tra.
     * @return bool True nếu hợp lệ.
     */
    public function validateCsrfToken(string $token): bool
    {
        return SecurityHelper::validateCsrfToken($token);
    }

    // --- Brute Force Protection ---

    /**
     * Kiểm tra xem người dùng có đang bị khóa tạm thời do đăng nhập sai nhiều lần không.
     * 
     * @return bool True nếu đang bị khóa.
     */
    public function isLockedOut(): bool
    {
        return time() < SessionManager::get('lockout_time', 0);
    }
    
    /**
     * Lấy thời gian còn lại (giây) cho đến khi mở khóa.
     * 
     * @return int Số giây còn lại.
     */
    public function getLockoutTimeRemaining(): int
    {
        return SessionManager::get('lockout_time', 0) - time();
    }

    /**
     * Ghi nhận một lần đăng nhập thất bại.
     * Nếu sai quá 5 lần sẽ khóa tạm thời trong 15 phút.
     * 
     * @return void
     */
    private function recordFailedLoginAttempt(): void
    {
        $attempts = SessionManager::get('login_attempts', 0) + 1;
        SessionManager::set('login_attempts', $attempts);

        if ($attempts >= 5) {
            LogHelper::warning("Security Alert: Multiple failed login attempts detected. Session locked out for 15 minutes.");
            SessionManager::set('lockout_time', time() + (15 * 60));
        }
    }

    /**
     * Lấy thông báo lỗi phù hợp khi đăng nhập thất bại.
     * 
     * @return string Thông báo lỗi.
     */
    private function getFailedLoginMessage(): string
    {
        if ($this->isLockedOut()) {
            return 'Bạn đã đăng nhập sai quá nhiều lần. Tài khoản bị tạm khóa trong 15 phút.';
        }
        return 'Tên đăng nhập hoặc mật khẩu không chính xác.';
    }

    /**
     * Xóa bộ đếm số lần đăng nhập sai sau khi đăng nhập thành công.
     * 
     * @return void
     */
    private function clearLoginAttempts(): void
    {
        SessionManager::unset('login_attempts');
        SessionManager::unset('lockout_time');
    }

    /**
     * Đăng nhập người dùng vào hệ thống.
     * 
     * @param string $username Tên đăng nhập của người dùng.
     * @param string $password Mật khẩu của người dùng.
     * @return array Kết quả đăng nhập bao gồm thành công hay thất bại và thông điệp tương ứng.
     */
    public function login(string $username, string $password): array
    {
        // Tìm user trong database
        $user = $this->authModel->findByUsername($username);

        if (!$user) {
            return ['success' => false, 'message' => 'Tên đăng nhập không tồn tại.'];
        }

        // Kiểm tra mật khẩu (đã được hash)
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu không chính xác.'];
        }

        // Kiểm tra trạng thái hoạt động của tài khoản
        if ($user['is_active'] == 0) {
            return ['success' => false, 'message' => 'Tài khoản đã bị khóa.'];
        }

        // Đăng nhập thành công, lưu thông tin vào session
        $this->setLoginSession($user);

        return ['success' => true, 'message' => 'Đăng nhập thành công.', 'user' => $user];
    }

    /**
     * Thiết lập thông tin phiên đăng nhập cho người dùng.
     * 
     * @param array $user Thông tin người dùng vừa đăng nhập.
     * @return void
     */
    private function setLoginSession(array $user): void
    {
        SessionManager::set('user_id', $user['id']);
        SessionManager::set('username', $user['username']);
        SessionManager::set('last_login', time());

        // TẢI VAI TRÒ VÀ QUYỀN HẠN CỦA NGƯỜI DÙNG
        $this->loadPermissionsForUser($user['id']);
    }
}