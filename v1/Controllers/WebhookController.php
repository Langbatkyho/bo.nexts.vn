<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Models\WebhookModel;
use BO_System\Core\Database;
use BO_System\Helpers\LogHelper;
use Exception;

class WebhookController extends BaseController
{
    private $webhookModel;
    private $logFile;

    public function __construct()
    {
        parent::__construct();
        $this->webhookModel = new WebhookModel(Database::getConnection());
        
        // Đường dẫn file log cho webhook
        $this->logFile = __DIR__ . '/../logs/tiktok_webhook.log';
    }

    /**
     * Xử lý webhook từ TikTok Shop
     *
     * @return void
     */
    public function handleTikTokWebhook()
    {
        try {
            // Log thông tin request
            $this->logWebhook("Webhook request received from " . $_SERVER['REMOTE_ADDR']);

            // 1. Lấy dữ liệu raw từ yêu cầu POST
            $input = file_get_contents('php://input');
            $this->logWebhook("Raw input: " . $input);

            // 2. Parse JSON
            $data = json_decode($input, true);

            // 3. Kiểm tra JSON hợp lệ
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logWebhook("Invalid JSON received: " . json_last_error_msg());
                $this->sendResponse(400, [
                    'status' => 'error',
                    'message' => 'Invalid JSON'
                ]);
                return;
            }

            $this->logWebhook("Decoded data: " . print_r($data, true));

            // 4. Xử lý PING request từ TikTok (bắt buộc)
            if (isset($data['type']) && $data['type'] === 'PING') {
                $this->handlePingRequest();
                return;
            }

            // 5. Lưu webhook vào database
            $this->saveWebhookData($data, $input);

        } catch (Exception $e) {
            $this->logWebhook("Error: " . $e->getMessage());
            $this->sendResponse(200, [
                'code' => 1,
                'message' => 'An unexpected error occurred.'
            ]);
        }
    }

    /**
     * Xử lý yêu cầu PING từ TikTok
     *
     * @return void
     */
    private function handlePingRequest()
    {
        $this->logWebhook("Received PING request from TikTok. Responding with success.");
        $this->sendResponse(200, [
            'code' => 0,
            'message' => 'PING_SUCCESS'
        ]);
        exit();
    }

    /**
     * Lưu dữ liệu webhook vào database
     *
     * @param array $data Dữ liệu đã được parse
     * @param string $rawContent Nội dung raw
     * @return void
     */
    private function saveWebhookData($data, $rawContent)
    {
        try {
            $platform = 'TIKTOK';
            $eventType = $data['type'] ?? 'UNKNOWN';
            
            // Xác định độ ưu tiên dựa trên loại event (tuỳ chỉnh theo nhu cầu)
            $priority = $this->getEventPriority($eventType);

            // Lưu vào database
            $webhookId = $this->webhookModel->saveWebhook(
                $platform,
                $eventType,
                $rawContent,
                3,           // max_retries
                'pending',   // status
                $priority    // priority
            );

            $this->logWebhook("Webhook data saved to database with ID: {$webhookId}. Event Type: {$eventType}");

            // Trả về phản hồi thành công cho TikTok
            $this->sendResponse(200, [
                'code' => 0,
                'message' => 'Webhook data successfully received and queued.'
            ]);

        } catch (Exception $e) {
            $this->logWebhook("Database Error: " . $e->getMessage());
            
            // Trả về phản hồi lỗi nhưng vẫn 200 OK để TikTok không gửi lại liên tục
            $this->sendResponse(200, [
                'code' => 1,
                'message' => 'Failed to queue webhook data to database.'
            ]);
        }
    }

    /**
     * Xác định độ ưu tiên của event
     *
     * @param string $eventType Loại event
     * @return int Độ ưu tiên (1-10, số càng cao càng ưu tiên)
     */
    private function getEventPriority($eventType)
    {
        // Định nghĩa độ ưu tiên cho từng loại event
        $priorityMap = [
            'ORDER_STATUS_CHANGE' => 10,
            'REVERSE_ORDER_STATUS_CHANGE' => 9,
            'PRODUCT_UPDATE' => 5,
            'PACKAGE_UPDATE' => 8,
            'UNKNOWN' => 1
        ];

        return $priorityMap[$eventType] ?? 5; // Mặc định là 5
    }

    /**
     * Ghi log webhook
     *
     * @param string $message Nội dung log
     * @return void
     */
    private function logWebhook($message)
    {
        // Sử dụng LogHelper nếu có, hoặc ghi trực tiếp
        if (class_exists('LogHelper')) {
            LogHelper::log('webhook', $message);
        } else {
            file_put_contents(
                $this->logFile,
                date('[Y-m-d H:i:s]') . ' ' . $message . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    /**
     * Gửi response JSON
     *
     * @param int $httpCode HTTP status code
     * @param array $data Dữ liệu response
     * @return void
     */
    private function sendResponse($httpCode, $data)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
