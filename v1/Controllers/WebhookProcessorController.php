<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Models\WebhookModel;
use BO_System\Models\OrderModel;
use BO_System\Core\Database;
use BO_System\Helpers\LogHelper;
use PDO;
use PDOException;
use Exception;

class WebhookProcessorController extends BaseController
{
    private $webhookModel;
    private $orderModel;
    private $processLog = [];
    private $stats = [
        'total_processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0
    ];

    public function __construct()
    {
        parent::__construct();
        $this->webhookModel = new WebhookModel(Database::getConnection());
        $this->orderModel = new OrderModel(Database::getConnection());
    }

    /**
     * Xử lý quét và xử lý webhook (dành cho Cron Job)
     * URL: /v1/index.php?module=WebhookProcessor&action=scan
     *
     * @return void
     */
    public function scan()
    {
        $this->addLog("Bắt đầu tiến trình quét và xử lý webhook TikTok Shop...");
        LogHelper::info("Webhook processor: Starting scan process");

        try {
            // Lấy danh sách webhook cần xử lý (pending hoặc failed chưa vượt max_retries)
            $webhooks = $this->webhookModel->getWebhooksToProcess('TIKTOK', 1, 10);

            if (empty($webhooks)) {
                $this->addLog("Không tìm thấy webhook cần xử lý.");
                LogHelper::info("Webhook processor: No webhooks to process");
                $this->renderResult();
                return;
            }

            $this->addLog("Tìm thấy " . count($webhooks) . " webhook cần xử lý.");
            LogHelper::info("Webhook processor: Found " . count($webhooks) . " webhooks to process");

            // Xử lý từng webhook
            foreach ($webhooks as $webhook) {
                $this->processWebhook($webhook);
            }

            $this->addLog("Hoàn thành tiến trình xử lý webhook.");
            LogHelper::info("Webhook processor: Completed scan process", [
                'stats' => $this->stats
            ]);

        } catch (Exception $e) {
            $errorMsg = "Lỗi hệ thống: " . $e->getMessage();
            $this->addLog($errorMsg, 'error');
            LogHelper::error("Webhook processor fatal error: " . $e->getMessage());
        }

        // Hiển thị kết quả
        $this->renderResult();
    }

    /**
     * Xử lý một webhook đơn lẻ
     *
     * @param array $webhook Dữ liệu webhook
     * @return void
     */
    private function processWebhook($webhook)
    {
        $webhookId = $webhook['id'];
        $rawContent = $webhook['raw_content'];
        $platform = $webhook['platform'];
        $retries = $webhook['retries'];

        $this->stats['total_processed']++;
        $this->addLog("--- Xử lý Webhook ID: {$webhookId} ---");

        try {
            // Parse JSON
            $data = json_decode($rawContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Lỗi giải mã JSON: " . json_last_error_msg());
            }

            // Kiểm tra cấu trúc dữ liệu
            if (!isset($data['data']['order_id']) || !isset($data['data']['order_status']) || !isset($data['data']['update_time'])) {
                throw new Exception("Cấu trúc JSON không hợp lệ, thiếu order_id, order_status hoặc update_time.");
            }

            $tiktokOrderId = $data['data']['order_id'];
            $newOrderStatus = $data['data']['order_status'];
            $updateTimeUnix = $data['data']['update_time'];
            $updateTime = date('Y-m-d H:i:s', $updateTimeUnix);
            $refShopId = $data['shop_id'] ?? null;

            // Bắt đầu transaction
            Database::getConnection()->beginTransaction();

            // Kiểm tra đơn hàng đã tồn tại chưa
            $existingOrder = $this->orderModel->findByRefOrderId($tiktokOrderId, $platform);

            $orderId = null;
            $oldOrderStatus = null;

            if (!$existingOrder) {
                // Tạo đơn hàng mới
                $this->addLog("Đơn hàng TikTok Order ID {$tiktokOrderId} chưa tồn tại, đang tạo mới...");

                $orderData = [
                    'ref_order_id' => $tiktokOrderId,
                    'platform' => $platform,
                    'ref_current_status' => $newOrderStatus,
                    'created_at' => $updateTime,
                    'last_updated_at' => $updateTime,
                    'last_updated_by' => 'BO_SYSTEM',
                    'ref_shop_id' => $refShopId,
                    'ref_order_number' => null
                ];

                $orderId = $this->orderModel->createOrder($orderData);
                $this->addLog("✓ Đã tạo mới đơn hàng ID: {$orderId} cho TikTok Order ID {$tiktokOrderId}");
                LogHelper::info("Created new order ID: {$orderId} for TikTok Order {$tiktokOrderId}");

            } else {
                // Đơn hàng đã tồn tại
                $orderId = $existingOrder['id'];
                $oldOrderStatus = $existingOrder['ref_current_status'];

                if ($oldOrderStatus !== $newOrderStatus) {
                    // Cập nhật trạng thái
                    $this->addLog("Đơn hàng ID {$orderId}: Trạng thái thay đổi từ '{$oldOrderStatus}' sang '{$newOrderStatus}'");
                    
                    $this->orderModel->updateOrderStatus($orderId, $newOrderStatus, $updateTime, 'BO_SYSTEM');
                    
                    $this->addLog("✓ Đã cập nhật trạng thái đơn hàng ID {$orderId}");
                    LogHelper::info("Updated order ID: {$orderId} status from '{$oldOrderStatus}' to '{$newOrderStatus}'");
                } else {
                    $this->addLog("Đơn hàng ID {$orderId}: Trạng thái không thay đổi ('{$newOrderStatus}'). Không cần cập nhật.");
                }
            }

            // Lưu lịch sử trạng thái
            $this->orderModel->saveOrderStatusHistory($orderId, $oldOrderStatus, $newOrderStatus, $updateTime, 'BO_SYSTEM');
            $this->addLog("✓ Đã lưu lịch sử trạng thái cho đơn hàng ID {$orderId}");

            // Commit transaction
            Database::getConnection()->commit();

            // Đánh dấu webhook đã xử lý thành công
            $this->webhookModel->markAsProcessed($webhookId);
            $this->addLog("✓ Webhook ID {$webhookId} đã được xử lý thành công.");
            
            $this->stats['success']++;

        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            if (Database::getConnection()->inTransaction()) {
                Database::getConnection()->rollBack();
            }

            $errorMessage = "Lỗi khi xử lý webhook ID {$webhookId}: " . $e->getMessage();
            $this->addLog("✗ " . $errorMessage, 'error');
            LogHelper::error($errorMessage);

            // Cập nhật trạng thái webhook thành failed và tăng retries
            $newRetries = $retries + 1;
            $this->webhookModel->markAsFailed($webhookId, $errorMessage, $newRetries);
            
            $this->addLog("Webhook ID {$webhookId} thất bại, đã tăng số lần thử lại lên {$newRetries}");
            
            $this->stats['failed']++;
        }

        $this->addLog(""); // Dòng trống để dễ đọc
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
            'page_title' => 'Webhook Processor - TikTok Shop'
        ];

        $this->loadView('WebhookProcessor/scan', $viewData);
    }
}
