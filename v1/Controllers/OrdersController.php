<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Models\OrderModel;
use BO_System\Models\ShopModel;
use BO_System\Core\Database;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use Exception;

class OrdersController extends BaseController
{
    private $orderModel;
    private $shopModel;

    public function __construct()
    {
        parent::__construct();
        
        // Kiểm tra quyền xem đơn hàng
        if (!$this->auth->can(Permissions::ORDERS_VIEW)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng quản lý đơn hàng.');
        }
        
        $this->orderModel = new OrderModel(Database::getConnection());
        $this->shopModel = new ShopModel(Database::getConnection());
    }

    /**
     * Hiển thị danh sách đơn hàng
     */
    public function index(): void
    {
        try {
            // Lấy các tham số lọc từ request
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $filters = [
                'platform' => $_GET['platform'] ?? '',
                'ref_current_status' => $_GET['status'] ?? '',
                'ref_shop_id' => $_GET['shop_id'] ?? '',
                'search' => $_GET['search'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            // Lọc bỏ các giá trị rỗng
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });

            // Lấy danh sách đơn hàng
            $orders = $this->orderModel->getOrdersWithShop($filters, $perPage, $offset);
            $totalOrders = $this->orderModel->countOrders($filters);
            $totalPages = ceil($totalOrders / $perPage);

            // Lấy thống kê theo trạng thái
            $stats = $this->orderModel->getOrderStatsByStatus($filters);

            // Lấy danh sách shop để hiển thị trong filter
            $shops = $this->shopModel->all('shop_name', 'ASC');

            $viewData = [
                'orders' => $orders,
                'filters' => $filters,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalOrders,
                    'per_page' => $perPage
                ],
                'stats' => $stats,
                'shops' => $shops,
                'csrf_token' => $this->auth->generateCsrfToken(),
                'flash_message' => SessionManager::get('flash_message'),
            ];

            SessionManager::unset('flash_message');
            $this->loadView('Orders/index', $viewData);

        } catch (Exception $e) {
            LogHelper::error("Orders index error: " . $e->getMessage());
            $this->renderContent(Helper::renderError("Đã có lỗi xảy ra: " . $e->getMessage()));
        }
    }

    /**
     * Hiển thị chi tiết đơn hàng
     */
    public function view(): void
    {
        try {
            $orderId = $_GET['id'] ?? null;

            if (!$orderId) {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Thiếu ID đơn hàng.']);
                header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
                exit();
            }

            // Lấy thông tin đơn hàng
            $order = $this->orderModel->getOrderById($orderId);

            if (!$order) {
                SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Không tìm thấy đơn hàng.']);
                header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
                exit();
            }

            // Lấy lịch sử trạng thái
            $statusHistory = $this->orderModel->getOrderStatusHistory($orderId);

            // Lấy thông tin shop (nếu có)
            $shop = null;
            if ($order['ref_shop_id']) {
                $shops = $this->shopModel->getShopsByPlatform($order['platform']);
                foreach ($shops as $s) {
                    if ($s['platform_shop_id'] == $order['ref_shop_id']) {
                        $shop = $s;
                        break;
                    }
                }
            }

            $viewData = [
                'order' => $order,
                'statusHistory' => $statusHistory,
                'shop' => $shop,
                'csrf_token' => $this->auth->generateCsrfToken(),
                'flash_message' => SessionManager::get('flash_message'),
            ];

            SessionManager::unset('flash_message');
            $this->loadView('Orders/view', $viewData);

        } catch (Exception $e) {
            LogHelper::error("Orders view error: " . $e->getMessage());
            $this->renderContent(Helper::renderError("Đã có lỗi xảy ra: " . $e->getMessage()));
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng (thủ công)
     */
    public function updateStatus(): void
    {
        // Kiểm tra quyền chỉnh sửa
        if (!$this->auth->can(Permissions::ORDERS_EDIT)) {
            http_response_code(403);
            die('403 - Bạn không có quyền chỉnh sửa đơn hàng.');
        }

        // Kiểm tra CSRF Token
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
            exit();
        }

        try {
            $orderId = $_POST['order_id'] ?? null;
            $newStatus = $_POST['new_status'] ?? null;

            if (!$orderId || !$newStatus) {
                throw new Exception("Thiếu thông tin đơn hàng hoặc trạng thái mới.");
            }

            // Lấy thông tin đơn hàng hiện tại
            $order = $this->orderModel->getOrderById($orderId);

            if (!$order) {
                throw new Exception("Không tìm thấy đơn hàng.");
            }

            $oldStatus = $order['ref_current_status'];
            $updatedAt = date('Y-m-d H:i:s');
            $updatedBy = SessionManager::get('username') ?? 'ADMIN';

            // Cập nhật trạng thái
            $this->orderModel->updateOrderStatus($orderId, $newStatus, $updatedAt, $updatedBy);

            // Lưu lịch sử
            $this->orderModel->saveOrderStatusHistory($orderId, $oldStatus, $newStatus, $updatedAt, $updatedBy);

            LogHelper::info("Order status updated: Order ID {$orderId} from '{$oldStatus}' to '{$newStatus}' by {$updatedBy}");

            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật trạng thái đơn hàng thành công.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders', 'action' => 'view', 'id' => $orderId]));
            exit();

        } catch (Exception $e) {
            LogHelper::error("Update order status error: " . $e->getMessage());
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra: ' . $e->getMessage()]);
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
            exit();
        }
    }

    /**
     * Xóa đơn hàng
     */
    public function delete(): void
    {
        // Kiểm tra quyền xóa
        if (!$this->auth->can(Permissions::ORDERS_DELETE)) {
            http_response_code(403);
            die('403 - Bạn không có quyền xóa đơn hàng.');
        }

        // Kiểm tra CSRF Token
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!$this->auth->validateCsrfToken($token)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
            exit();
        }

        try {
            $orderId = $_GET['id'] ?? $_POST['id'] ?? null;

            if (!$orderId) {
                throw new Exception("Thiếu ID đơn hàng.");
            }

            // Lấy thông tin đơn hàng trước khi xóa
            $order = $this->orderModel->getOrderById($orderId);

            if (!$order) {
                throw new Exception("Không tìm thấy đơn hàng.");
            }

            // Xóa đơn hàng
            $this->orderModel->deleteOrder($orderId);

            LogHelper::warning("Order deleted: ID {$orderId} (Ref: {$order['ref_order_id']}) by " . (SessionManager::get('username') ?? 'ADMIN'));

            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa đơn hàng thành công.']);

        } catch (Exception $e) {
            LogHelper::error("Delete order error: " . $e->getMessage());
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra: ' . $e->getMessage()]);
        }

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
        exit();
    }

    /**
     * Xuất dữ liệu đơn hàng (CSV/Excel)
     */
    public function export(): void
    {
        try {
            $filters = [
                'platform' => $_GET['platform'] ?? '',
                'ref_current_status' => $_GET['status'] ?? '',
                'ref_shop_id' => $_GET['shop_id'] ?? '',
                'search' => $_GET['search'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
            ];

            // Lọc bỏ các giá trị rỗng
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });

            // Lấy tất cả đơn hàng (không giới hạn)
            $orders = $this->orderModel->getOrdersWithShop($filters, 10000, 0);

            // Tạo file CSV
            $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // BOM cho UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($output, [
                'ID',
                'Ref Order ID',
                'Order Number',
                'Platform',
                'Shop',
                'Status',
                'Created At',
                'Last Updated',
                'Updated By'
            ]);
            
            // Data
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order['id'],
                    $order['ref_order_id'],
                    $order['ref_order_number'] ?? '',
                    $order['platform'],
                    $order['shop_name'] ?? $order['ref_shop_id'],
                    $order['ref_current_status'],
                    $order['created_at'],
                    $order['last_updated_at'],
                    $order['last_updated_by']
                ]);
            }
            
            fclose($output);
            exit();

        } catch (Exception $e) {
            LogHelper::error("Export orders error: " . $e->getMessage());
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra khi xuất dữ liệu.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Orders']));
            exit();
        }
    }
}
