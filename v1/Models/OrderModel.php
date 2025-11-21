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
            $stmt = $this->db->prepare($sql);
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

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':ref_order_id', $orderData['ref_order_id'], PDO::PARAM_STR);
            $stmt->bindValue(':platform', $orderData['platform'], PDO::PARAM_STR);
            $stmt->bindValue(':ref_current_status', $orderData['ref_current_status'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', $orderData['created_at'], PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_at', $orderData['last_updated_at'], PDO::PARAM_STR);
            $stmt->bindValue(':last_updated_by', $orderData['last_updated_by'] ?? 'BO_SYSTEM', PDO::PARAM_STR);
            $stmt->bindValue(':ref_shop_id', $orderData['ref_shop_id'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':ref_order_number', $orderData['ref_order_number'] ?? null, PDO::PARAM_STR);

            $stmt->execute();
            return $this->db->lastInsertId();
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

            $stmt = $this->db->prepare($sql);
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

            $stmt = $this->db->prepare($sql);
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

            $stmt = $this->db->prepare($sql);
            
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
}
