<?php
/**
 * TikTok Shop Webhook Endpoint
 * 
 * File này nhận webhook từ TikTok Shop và chuyển xử lý cho WebhookController
 * theo mô hình MVC của hệ thống v1.
 * 
 * Endpoint URL: https://yourdomain.com/v1/Webhook/tiktokshop-webhook.php
 */

// Trên môi trường production, nên tắt display_errors và chỉ ghi log
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Require file initialize để load autoloader và các config
require_once __DIR__ . '/../initialize.php';

use BO_System\Controllers\WebhookController;

try {
    // Khởi tạo controller và xử lý webhook
    $webhookController = new WebhookController();
    $webhookController->handleTikTokWebhook();
    
} catch (Exception $e) {
    // Log lỗi nếu có exception không được xử lý
    error_log("TikTok Webhook Fatal Error: " . $e->getMessage());
    
    // Trả về response để TikTok không gửi lại liên tục
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'code' => 1,
        'message' => 'Internal server error'
    ]);
}
