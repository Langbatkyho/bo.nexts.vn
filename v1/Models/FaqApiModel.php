<?php
namespace BO_System\Models;

use PDO;
use PDOException;

class FaqApiModel extends BaseModel
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function fetchAllApprovedFaqs($productId)
    {
        $sql = "SELECT * FROM faqs WHERE product_id = :product_id AND status = 'approved' ORDER BY created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchCategories($productId)
    {
        // Get distinct categories
        $sql = "SELECT DISTINCT category FROM faqs WHERE product_id = :product_id AND status = 'approved' AND category IS NOT NULL AND parent_id IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Count per category
        $categoryCounts = [];
        if (!empty($categories)) {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $sqlCount = "SELECT category, COUNT(id) as count FROM faqs WHERE product_id = ? AND status = 'approved' AND parent_id IS NULL AND category IN ($placeholders) GROUP BY category";
            $stmtCount = $this->pdo->prepare($sqlCount);
            $params = array_merge([$productId], $categories);
            $stmtCount->execute($params);
            $results = $stmtCount->fetchAll(PDO::FETCH_KEY_PAIR); // category => count
            $categoryCounts = $results;
        }

        // Total count
        $sqlTotal = "SELECT COUNT(id) as total FROM faqs WHERE product_id = :product_id AND status = 'approved' AND parent_id IS NULL";
        $stmtTotal = $this->pdo->prepare($sqlTotal);
        $stmtTotal->execute([':product_id' => $productId]);
        $totalCount = $stmtTotal->fetchColumn();

        return ['categories' => $categoryCounts, 'total_count' => $totalCount];
    }

    public function submitQuestion($data)
    {
        $sql = "INSERT INTO faqs (product_id, author_name, author_phone, category, question_content, is_question, status, created_at) VALUES (:product_id, :author_name, :author_phone, :category, :question_content, 1, 'pending', NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':product_id' => $data['product_id'],
            ':author_name' => $data['author_name'],
            ':author_phone' => $data['author_phone'] ?? null,
            ':category' => $data['category'],
            ':question_content' => $data['question_content']
        ]);
    }
}
