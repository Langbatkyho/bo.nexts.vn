<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Models\ShopModel;
use BO_System\Services\TikTokTokenService;
use BO_System\Core\Database;
use BO_System\Helpers\LogHelper;
use Exception;

class TokenRefreshController extends BaseController
{
    private $shopModel;
    private $tiktokTokenService;
    private $processLog = [];
    private $stats = [
        'total_shops' => 0,
        'refreshed' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function __construct()
    {
        parent::__construct();
        $this->shopModel = new ShopModel(Database::getConnection());
        $this->tiktokTokenService = new TikTokTokenService();
    }

    /**
     * Quét và làm mới token cho các shop TikTok (dành cho Cron Job)
     * URL: /v1/index.php?module=TokenRefresh&action=tiktok
     *
     * @return void
     */
    public function tiktok()
    {
        $this->addLog("=== BẮT ĐẦU TIẾN TRÌNH LÀM MỚI TOKEN TIKTOK ===");
        LogHelper::info("Token refresh: Starting TikTok token refresh process");

        try {
            // Lấy danh sách tất cả shop TikTok
            $tiktokShops = $this->shopModel->getShopsByPlatform('TIKTOK');

            if (empty($tiktokShops)) {
                $this->addLog("Không tìm thấy shop TikTok nào trong database.");
                LogHelper::info("Token refresh: No TikTok shops found");
                $this->renderResult();
                return;
            }

            $this->stats['total_shops'] = count($tiktokShops);
            $this->addLog("Tìm thấy {$this->stats['total_shops']} shop TikTok. Đang kiểm tra token...");
            LogHelper::info("Token refresh: Found {$this->stats['total_shops']} TikTok shops");

            // Xử lý từng shop
            foreach ($tiktokShops as $shop) {
                $this->processShopToken($shop);
            }

            $this->addLog("=== HOÀN THÀNH TIẾN TRÌNH LÀM MỚI TOKEN ===");
            LogHelper::info("Token refresh: Completed", [
                'stats' => $this->stats
            ]);

        } catch (Exception $e) {
            $errorMsg = "Lỗi hệ thống: " . $e->getMessage();
            $this->addLog($errorMsg, 'error');
            LogHelper::error("Token refresh fatal error: " . $e->getMessage());
        }

        // Hiển thị kết quả
        $this->renderResult();
    }

    /**
     * Xử lý làm mới token cho một shop
     *
     * @param array $shop Dữ liệu shop
     * @return void
     */
    private function processShopToken($shop)
    {
        $shopId = $shop['id'];
        $shopName = $shop['shop_name'];
        $platformShopId = $shop['platform_shop_id'];
        $refreshToken = $shop['refresh_token'];

        $this->addLog("--- Shop ID: {$shopId} ({$shopName}) ---");

        // Kiểm tra refresh token có tồn tại không
        if (empty($refreshToken)) {
            $this->addLog("✗ Shop ID {$shopId}: Không có refresh token, bỏ qua.", 'warning');
            $this->stats['skipped']++;
            return;
        }

        // Kiểm tra xem có cần làm mới token không (1 ngày = 86400 giây)
        $checkResult = $this->tiktokTokenService->checkTokenRefreshNeeded($shop, 86400);

        $this->addLog("Access Token hết hạn: " . ($shop['access_token_expires_at'] ?? 'N/A'));
        $this->addLog("Refresh Token hết hạn: " . ($shop['refresh_token_expires_at'] ?? 'N/A'));

        if (!$checkResult['needs_refresh']) {
            $this->addLog("✓ Shop ID {$shopId}: Token vẫn còn hiệu lực, không cần làm mới.");
            $this->addLog("  Lý do: {$checkResult['reason']}");
            $this->stats['skipped']++;
            return;
        }

        // Cần làm mới token
        $this->addLog("⚠ Shop ID {$shopId}: Token cần được làm mới.");
        $this->addLog("  Lý do: {$checkResult['reason']}");

        try {
            // Gọi API TikTok để làm mới token
            $this->addLog("Đang gọi API TikTok để làm mới token...");
            
            $newTokenData = $this->tiktokTokenService->refreshAccessToken($refreshToken);

            // Cập nhật vào database
            $updateSuccess = $this->shopModel->updateShopTokens(
                $shopId,
                $newTokenData['access_token'],
                $newTokenData['refresh_token'],
                $newTokenData['access_token_expires_at'],
                $newTokenData['refresh_token_expires_at']
            );

            if ($updateSuccess) {
                $this->addLog("✓ Shop ID {$shopId}: Token đã được làm mới thành công!");
                $this->addLog("  Access Token mới hết hạn: {$newTokenData['access_token_expires_at']}");
                
                if ($newTokenData['refresh_token_expires_at']) {
                    $this->addLog("  Refresh Token mới hết hạn: {$newTokenData['refresh_token_expires_at']}");
                }

                LogHelper::info("Token refreshed successfully for shop ID {$shopId} ({$shopName})");
                $this->stats['refreshed']++;
            } else {
                throw new Exception("Không thể cập nhật token vào database");
            }

        } catch (Exception $e) {
            $errorMsg = "Lỗi khi làm mới token cho Shop ID {$shopId}: " . $e->getMessage();
            $this->addLog("✗ " . $errorMsg, 'error');
            LogHelper::error($errorMsg);
            $this->stats['failed']++;
        }

        $this->addLog(""); // Dòng trống để dễ đọc
    }

    /**
     * Làm mới token cho một shop cụ thể (có thể gọi từ UI)
     * URL: /v1/index.php?module=TokenRefresh&action=refreshShop&shop_id=123
     *
     * @return void
     */
    public function refreshShop()
    {
        $shopId = $_GET['shop_id'] ?? null;

        if (!$shopId) {
            $this->addLog("Thiếu tham số shop_id", 'error');
            $this->renderResult();
            return;
        }

        $this->addLog("=== LÀM MỚI TOKEN CHO SHOP ID: {$shopId} ===");

        try {
            $shop = $this->shopModel->find($shopId);

            if (!$shop) {
                $this->addLog("Không tìm thấy shop với ID: {$shopId}", 'error');
                $this->renderResult();
                return;
            }

            if ($shop['platform'] !== 'TIKTOK') {
                $this->addLog("Shop này không phải là TikTok Shop (Platform: {$shop['platform']})", 'error');
                $this->renderResult();
                return;
            }

            $this->stats['total_shops'] = 1;
            $this->processShopToken($shop);

            $this->addLog("=== HOÀN THÀNH ===");

        } catch (Exception $e) {
            $errorMsg = "Lỗi: " . $e->getMessage();
            $this->addLog($errorMsg, 'error');
            LogHelper::error("Token refresh error for shop ID {$shopId}: " . $e->getMessage());
        }

        $this->renderResult();
    }

    /**
     * Thêm log vào mảng processLog
     *
     * @param string $message Nội dung log
     * @param string $type Loại log (info, error, warning)
     * @return void
     */
    private function addLog($message, $type = 'info')
    {
        $this->processLog[] = [
            'time' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Hiển thị kết quả xử lý
     *
     * @return void
     */
    private function renderResult()
    {
        $viewData = [
            'logs' => $this->processLog,
            'stats' => $this->stats,
            'page_title' => 'Token Refresh - TikTok Shop'
        ];

        $this->loadView('TokenRefresh/tiktok', $viewData);
    }
}
