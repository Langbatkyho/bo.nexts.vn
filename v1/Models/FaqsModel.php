<?php
namespace BO_System\Models;

use PDO;
use PDOException;

/**
 * Lớp FaqsModel quản lý dữ liệu câu hỏi thường gặp (FAQs).
 */
class FaqsModel extends BaseModel
{
    protected string $table = 'faqs';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    // Thay thế hàm deleteFaq cũ
    public function deleteFaq(int $id): bool
    {
        // Sử dụng hàm delete từ BaseModel
        return $this->delete($id);
    }

    // Thay thế hàm getFaqById cũ
    public function getFaqById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT f.*, p.p_name 
                 FROM faqs f 
                 LEFT JOIN products p ON f.product_id = p.ref_id 
                 WHERE f.id = :id AND f.parent_id IS NULL"
            );
            $stmt->execute([':id' => $id]);
            $faq = $stmt->fetch(PDO::FETCH_ASSOC);
            return $faq ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching FAQ by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy danh sách câu hỏi thường gặp với các bộ lọc và phân trang.
     *
     * @param string $searchTerm Từ khóa tìm kiếm.
     * @param string $productFilter Bộ lọc theo sản phẩm.
     * @param string $categoryFilter Bộ lọc theo danh mục.
     * @param string $statusFilter Bộ lọc theo trạng thái.
     * @param int $limit Số lượng câu hỏi trên một trang.
     * @param int $offset Vị trí bắt đầu lấy dữ liệu.
     * @return array Danh sách câu hỏi thường gặp.
     */
    public function getFaqs(
        string $searchTerm,
        string $productFilter,
        string $categoryFilter,
        string $statusFilter,
        int $limit,
        int $offset
    ): array {
        $whereClauses = ["f.parent_id IS NULL"];
        $params = [];

        if (!empty($searchTerm)) {
            $whereClauses[] = "f.question_content LIKE :search_term";
            $params[':search_term'] = "%" . $searchTerm . "%";
        }
        if (!empty($productFilter)) {
            $whereClauses[] = "f.product_id = :product_filter";
            $params[':product_filter'] = $productFilter;
        }
        if (!empty($categoryFilter)) {
            $whereClauses[] = "f.category = :category_filter";
            $params[':category_filter'] = $categoryFilter;
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "f.status = :status_filter";
            $params[':status_filter'] = $statusFilter;
        }

        $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";

        $faqsSql = "SELECT f.*, p.p_name
                    FROM faqs f
                    LEFT JOIN products p ON f.product_id = p.ref_id
                    $whereSql
                    ORDER BY f.created_at DESC
                    LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($faqsSql);

            // Bind các tham số đã có
            foreach ($params as $key => $val) { // Bỏ & ở đây vì bạn không dùng bindParam thủ công
                $stmt->bindValue($key, $val);
            }
            // Bind limit và offset
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching FAQs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy câu trả lời cho một câu hỏi.
     * @param int $faqId ID của câu hỏi cha.
     * @return array|null Dữ liệu câu trả lời hoặc null nếu không có.
     */
    public function getAnswerByFaqId(int $faqId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM faqs WHERE parent_id = :parent_id AND is_question = 0");
            $stmt->execute([':parent_id' => $faqId]);
            $answer = $stmt->fetch(PDO::FETCH_ASSOC);
            return $answer ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching answer by FAQ ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cập nhật câu hỏi và câu trả lời trong một transaction.
     * @param int $faqId ID của câu hỏi cần cập nhật.
     * @param array $questionData Dữ liệu cho câu hỏi.
     * @param array $answerData Dữ liệu cho câu trả lời.
     * @return bool True nếu thành công, False nếu thất bại.
     */
    public function updateFaqAndAnswer(int $faqId, array $questionData, array $answerData): bool
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Cập nhật câu hỏi
            $stmt_q = $this->pdo->prepare(
                "UPDATE faqs SET author_name = :author_name, category = :category, 
                 question_content = :question_content, status = :status, created_at = :created_at 
                 WHERE id = :id"
            );
            $stmt_q->execute([
                ':author_name' => $questionData['author_name'],
                ':category' => $questionData['category'],
                ':question_content' => $questionData['question_content'],
                ':status' => $questionData['status'],
                ':created_at' => $questionData['created_at'],
                ':id' => $faqId
            ]);

            // 2. Xử lý câu trả lời
            $existingAnswer = $this->getAnswerByFaqId($faqId);

            if ($existingAnswer) {
                // Đã có câu trả lời -> Cập nhật hoặc Xóa
                if (!empty($answerData['answer_content'])) {
                    $stmt_a = $this->pdo->prepare("UPDATE faqs SET answer_content = :content, answered_at = :answered_at WHERE id = :id");
                    $stmt_a->execute([
                        ':content' => $answerData['answer_content'],
                        ':answered_at' => $answerData['answered_at'],
                        ':id' => $existingAnswer['id']
                    ]);
                } else {
                    $stmt_a = $this->pdo->prepare("DELETE FROM faqs WHERE id = :id");
                    $stmt_a->execute([':id' => $existingAnswer['id']]);
                }
            } else {
                // Chưa có câu trả lời -> Thêm mới nếu có nội dung
                if (!empty($answerData['answer_content'])) {
                    $stmt_a = $this->pdo->prepare(
                        "INSERT INTO faqs (product_id, parent_id, answer_content, is_question, status, author_name, answered_at) 
                         VALUES (:product_id, :parent_id, :content, 0, 'approved', 'Coolmom', :answered_at)"
                    );
                    $stmt_a->execute([
                        ':product_id' => $questionData['product_id'],
                        ':parent_id' => $faqId,
                        ':content' => $answerData['answer_content'],
                        ':answered_at' => $answerData['answered_at']
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating FAQ: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đếm số lượng câu hỏi thường gặp theo các bộ lọc.
     *
     * @param string $searchTerm Từ khóa tìm kiếm.
     * @param string $productFilter Bộ lọc theo sản phẩm.
     * @param string $categoryFilter Bộ lọc theo danh mục.
     * @param string $statusFilter Bộ lọc theo trạng thái.
     * @return int Số lượng câu hỏi thường gặp.
     */
    public function countFaqs(
        string $searchTerm,
        string $productFilter,
        string $categoryFilter,
        string $statusFilter
    ): int {
        $whereClauses = ["f.parent_id IS NULL"];
        $params = [];

        if (!empty($searchTerm)) {
            $whereClauses[] = "f.question_content LIKE :search_term";
            $params[':search_term'] = "%" . $searchTerm . "%";
        }
        if (!empty($productFilter)) {
            $whereClauses[] = "f.product_id = :product_filter";
            $params[':product_filter'] = $productFilter;
        }
        if (!empty($categoryFilter)) {
            $whereClauses[] = "f.category = :category_filter";
            $params[':category_filter'] = $categoryFilter;
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "f.status = :status_filter";
            $params[':status_filter'] = $statusFilter;
        }

        $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";
        $countSql = "SELECT COUNT(f.id) as total FROM faqs f $whereSql";

        try {
            $stmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $val) { // Bỏ & ở đây
                $stmt->bindValue($key, $val); // Sử dụng bindValue thay vì bindParam khi không cần tham chiếu
            }
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error counting FAQs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy danh sách sản phẩm để lọc.
     *
     * @return array Danh sách sản phẩm.
     */
    public function getProductsForFilter(): array {
        try {
            $stmt = $this->pdo->query("SELECT ref_id, p_name FROM products ORDER BY p_name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching products for filter: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách danh mục để lọc.
     *
     * @return array Danh sách danh mục.
     */
    public function getCategoriesForFilter(): array {
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL AND parent_id IS NULL ORDER BY category ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching categories for filter: " . $e->getMessage());
            return [];
        }
    }
}