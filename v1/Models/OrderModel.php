<?php
namespace BO_System\Models;

use BO_System\Models\BaseModel;
use PDO;
use PDOException;
use Exception;

class OrderModel extends BaseModel
{
    /**
     * Tìm đơn hàng theo ref_order_id và platform
     *
     * @param string $refOrderId ID đơn hàng từ platform (TikTok, Shopee, v.v.)
     * @param string $platform Tên platform
     * @return array|null Thông tin đơn hàng hoặc null nếu không tìm thấy
     */
    public function findByRefOrderId($refOrderId, $platform)
    {
        try {
            $sql = "SELECT * FROM `orders` WHERE ref_order_id = :ref_order_id AND platform = :platform";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':ref_order_id', $refOrderId, PDO::PARAM_STR);
            $stmt->bindValue(':platform', $platform, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Failed to find order: " . $e->getMessage());
        }
    }

    /**
     * Tạo đơn hàng mới
     *
     * @param array $orderData Dữ liệu đơn hàng
     * @return int ID của đơn hàng vừa được tạo
     */
    public function createOrder($orderData)
    {
        try {
            $sql = "
                INSERT INTO `orders` (
                    ref_order_id,
                    platform,
                    ref_current_status,
                    created_at,
                    last_updated_at,
                    last_updated_by,
                    ref_shop_id,
                    ref_order_number
                ) VALUES (
                    :ref_order_id,
                    :platform,
                    :ref_current_status,
                    :created_at,
                    :last_updated_at,
                    :last_updated_by,
                    :ref_shop_id,
                    :ref_order_number
                )
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':ref_order_id', $orderData['ref_order_id'], PDO::PARAM_STR);
            $stmt->bindValue(':platform', $orderData['platform'], PDO::PARAM_STR);
            $stmt->bindValue(':ref_current_status', $orderData['ref_current_status'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', $orderData['created_at'], PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_at', $orderData['last_updated_at'], PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_by', $orderData['last_updated_by'] ?? 'BO_SYSTEM', PDO::PARAM_STR);
            $stmt->bindValue(':ref_shop_id', $orderData['ref_shop_id'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ref_order_number', $orderData['ref_order_number'] ?? null, PDO::PARAM_STR);

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Failed to create order: " . $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng
     *
     * @param int $orderId ID đơn hàng
     * @param string $newStatus Trạng thái mới
     * @param string $updatedAt Thời gian cập nhật
     * @param string $updatedBy Người cập nhật
     * @return bool True nếu cập nhật thành công
     */
    public function updateOrderStatus($orderId, $newStatus, $updatedAt, $updatedBy = 'BO_SYSTEM')
    {
        try {
            $sql = "
                UPDATE `orders`
                SET
                    ref_current_status = :new_status,
                    last_updated_at = :last_updated_at,
                    last_updated_by = :last_updated_by
                WHERE id = :id
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':new_status', $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_at', $updatedAt, PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_by', $updatedBy, PDO::PARAM_STR);
            $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to update order status: " . $e->getMessage());
        }
    }

    /**
     * Lưu lịch sử thay đổi trạng thái đơn hàng
     *
     * @param int $orderId ID đơn hàng
     * @param string|null $oldStatus Trạng thái cũ
     * @param string $newStatus Trạng thái mới
     * @param string $updatedAt Thời gian cập nhật
     * @param string $updatedBy Người cập nhật
     * @return bool True nếu lưu thành công
     */
    public function saveOrderStatusHistory($orderId, $oldStatus, $newStatus, $updatedAt, $updatedBy = 'BO_SYSTEM')
    {
        try {
            $sql = "
                INSERT INTO order_status (order_id, old_status, new_status, updated_at, updated_by)
                VALUES (:order_id, :old_status, :new_status, :updated_at, :updated_by)
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindValue(':old_status', $oldStatus, PDO::PARAM_STR);
            $stmt->bindValue(':new_status', $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', $updatedAt, PDO::PARAM_STR);
            $stmt->bindValue(':updated_by', $updatedBy, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to save order status history: " . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách đơn hàng theo điều kiện
     *
     * @param array $filters Bộ lọc (platform, status, v.v.)
     * @param int $limit Số lượng bản ghi tối đa
     * @param int $offset Vị trí bắt đầu
     * @return array Danh sách đơn hàng
     */
    public function getOrders($filters = [], $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM `orders` WHERE 1=1";
            $params = [];

            if (!empty($filters['platform'])) {
                $sql .= " AND platform = :platform";
                $params[':platform'] = $filters['platform'];
            }

            if (!empty($filters['ref_current_status'])) {
                $sql .= " AND ref_current_status = :ref_current_status";
                $params[':ref_current_status'] = $filters['ref_current_status'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch orders: " . $e->getMessage());
        }
    }

    /**
     * Lấy tổng số đơn hàng theo điều kiện
     *
     * @param array $filters Bộ lọc
     * @return int Tổng số đơn hàng
     */
    public function countOrders($filters = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM `orders` WHERE 1=1";
            $params = [];

            if (!empty($filters['platform'])) {
                $sql .= " AND platform = :platform";
                $params[':platform'] = $filters['platform'];
            }

            if (!empty($filters['ref_current_status'])) {
                $sql .= " AND ref_current_status = :ref_current_status";
                $params[':ref_current_status'] = $filters['ref_current_status'];
            }

            if (!empty($filters['ref_shop_id'])) {
                $sql .= " AND ref_shop_id = :ref_shop_id";
                $params[':ref_shop_id'] = $filters['ref_shop_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (ref_order_id LIKE :search OR ref_order_number LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['total'];
        } catch (PDOException $e) {
            throw new Exception("Failed to count orders: " . $e->getMessage());
        }
    }

    /**
     * Lấy đơn hàng theo ID
     *
     * @param int $orderId ID đơn hàng
     * @return array|null Thông tin đơn hàng
     */
    public function getOrderById($orderId)
    {
        try {
            $sql = "SELECT * FROM `orders` WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch order by ID: " . $e->getMessage());
        }
    }

    /**
     * Lấy lịch sử thay đổi trạng thái của đơn hàng
     *
     * @param int $orderId ID đơn hàng
     * @return array Lịch sử trạng thái
     */
    public function getOrderStatusHistory($orderId)
    {
        try {
            $sql = "
                SELECT * 
                FROM order_status 
                WHERE order_id = :order_id 
                ORDER BY updated_at DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch order status history: " . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách đơn hàng với thông tin shop
     *
     * @param array $filters Bộ lọc
     * @param int $limit Số lượng bản ghi
     * @param int $offset Vị trí bắt đầu
     * @return array Danh sách đơn hàng
     */
    public function getOrdersWithShop($filters = [], $limit = 20, $offset = 0)
    {
        try {
            $sql = "
                SELECT 
                    o.*,
                    s.shop_name,
                    s.platform_shop_code
                FROM `orders` o
                LEFT JOIN shop s ON o.ref_shop_id = s.platform_shop_id AND o.platform = s.platform
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['platform'])) {
                $sql .= " AND o.platform = :platform";
                $params[':platform'] = $filters['platform'];
            }

            if (!empty($filters['ref_current_status'])) {
                $sql .= " AND o.ref_current_status = :ref_current_status";
                $params[':ref_current_status'] = $filters['ref_current_status'];
            }

            if (!empty($filters['ref_shop_id'])) {
                $sql .= " AND o.ref_shop_id = :ref_shop_id";
                $params[':ref_shop_id'] = $filters['ref_shop_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (o.ref_order_id LIKE :search OR o.ref_order_number LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND o.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND o.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch orders with shop: " . $e->getMessage());
        }
    }

    /**
     * Xóa đơn hàng
     *
     * @param int $orderId ID đơn hàng
     * @return bool True nếu xóa thành công
     */
    public function deleteOrder($orderId)
    {
        try {
            $sql = "DELETE FROM `orders` WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to delete order: " . $e->getMessage());
        }
    }

    /**
     * Lấy thống kê đơn hàng theo trạng thái
     *
     * @param array $filters Bộ lọc bổ sung
     * @return array Thống kê theo trạng thái
     */
    public function getOrderStatsByStatus($filters = [])
    {
        try {
            $sql = "
                SELECT 
                    ref_current_status,
                    COUNT(*) as count
                FROM `orders`
                WHERE 1=1
            ";
            $params = [];

            if (!empty($filters['platform'])) {
                $sql .= " AND platform = :platform";
                $params[':platform'] = $filters['platform'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $sql .= " GROUP BY ref_current_status";

            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch order statistics: " . $e->getMessage());
        }
    }
}
