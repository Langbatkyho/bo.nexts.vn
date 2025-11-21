<?php
namespace BO_System\Models;

use BO_System\Models\BaseModel;
use PDO;
use PDOException;
use Exception;

class WebhookModel extends BaseModel
{
    /**
     * Lưu dữ liệu webhook vào database
     *
     * @param string $platform Platform gửi webhook (TIKTOK, SHOPEE, v.v.)
     * @param string $type Loại sự kiện webhook
     * @param string $rawContent Nội dung raw của webhook (JSON string)
     * @param int $maxRetries Số lần thử lại tối đa
     * @param string $status Trạng thái webhook (pending, processing, completed, failed)
     * @param int $priority Độ ưu tiên xử lý
     * @return int ID của bản ghi vừa được tạo
     * @throws Exception Nếu có lỗi xảy ra khi lưu vào database
     */
    public function saveWebhook($platform, $type, $rawContent, $maxRetries = 3, $status = 'pending', $priority = 1)
    {
        try {
            $sql = "
                INSERT INTO webhook (
                    created_date,
                    platform,
                    type,
                    raw_content,
                    max_retries,
                    status,
                    priority
                ) VALUES (
                    NOW(),
                    :platform,
                    :type,
                    :raw_content,
                    :max_retries,
                    :status,
                    :priority
                )
            ";

            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':platform', $platform, PDO::PARAM_STR);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':raw_content', $rawContent, PDO::PARAM_STR);
            $stmt->bindValue(':max_retries', $maxRetries, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':priority', $priority, PDO::PARAM_INT);

            $stmt->execute();

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Failed to save webhook to database: " . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin webhook theo ID
     *
     * @param int $id ID của webhook
     * @return array|null Thông tin webhook hoặc null nếu không tìm thấy
     */
    public function getWebhookById($id)
    {
        try {
            $sql = "SELECT * FROM webhook WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch webhook: " . $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái webhook
     *
     * @param int $id ID của webhook
     * @param string $status Trạng thái mới
     * @param string|null $errorMessage Thông báo lỗi (nếu có)
     * @return bool True nếu cập nhật thành công
     */
    public function updateWebhookStatus($id, $status, $errorMessage = null)
    {
        try {
            $sql = "
                UPDATE webhook 
                SET 
                    status = :status,
                    updated_date = NOW()
            ";

            if ($errorMessage !== null) {
                $sql .= ", error_message = :error_message";
            }

            $sql .= " WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);

            if ($errorMessage !== null) {
                $stmt->bindValue(':error_message', $errorMessage, PDO::PARAM_STR);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Failed to update webhook status: " . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách webhook pending để xử lý
     *
     * @param int $limit Số lượng webhook tối đa
     * @return array Danh sách webhook
     */
    public function getPendingWebhooks($limit = 100)
    {
        try {
            $sql = "
                SELECT * 
                FROM webhook 
                WHERE status = 'pending' 
                ORDER BY priority DESC, created_date ASC 
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch pending webhooks: " . $e->getMessage());
        }
    }
}
