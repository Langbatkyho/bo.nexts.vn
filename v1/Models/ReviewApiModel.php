<?php
namespace BO_System\Models;

use PDO;
use PDOException;

class ReviewApiModel extends BaseModel
{
    protected string $table = 'reviews';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function fetchProductData(string $productId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM product_review_summary WHERE product_id = ?");
            $stmt->execute([$productId]);
            $summaryData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($summaryData) {
                $summaryData['star_counts'] = json_decode($summaryData['star_counts'], true) ?: [];
                $summaryData['attribute_averages'] = json_decode($summaryData['attribute_averages'] ?? '[]', true) ?? [];
            }

            return [
                'summary' => $summaryData ?: [
                    'total_reviews' => 0,
                    'average_rating' => 0,
                    'star_counts' => [],
                    'photo_count' => 0,
                    'comment_count' => 0,
                    'photo_review_count' => 0,
                    'attribute_averages' => []
                ]
            ];
        } catch (PDOException $e) {
            error_log("Error fetching product data for {$productId}: " . $e->getMessage());
            return [
                'summary' => [
                    'total_reviews' => 0,
                    'average_rating' => 0,
                    'star_counts' => [],
                    'photo_count' => 0,
                    'comment_count' => 0,
                    'photo_review_count' => 0,
                    'attribute_averages' => []
                ]
            ];
        }
    }

    public function fetchPhotos(string $productId, int $limit = 12): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, photos FROM {$this->table} WHERE product_id = ? AND status = 'approved' AND photos IS NOT NULL AND JSON_LENGTH(photos) > 0 ORDER BY created_at DESC LIMIT ?");
            $stmt->bindValue(1, $productId, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $photos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $photoUrls = json_decode($row['photos'], true);
                if (is_array($photoUrls)) {
                    foreach ($photoUrls as $url) {
                        if (count($photos) < $limit) {
                            $photos[] = ['url' => $url, 'reviewId' => $row['id']];
                        } else {
                            break;
                        }
                    }
                }
                if (count($photos) >= $limit) break;
            }
            return $photos;
        } catch (PDOException $e) {
            error_log("Error fetching photos for {$productId}: " . $e->getMessage());
            return [];
        }
    }

    public function fetchReviewsPage(string $productId, int $page, array $filters = []): array
    {
        try {
            $reviewsPerPage = 5;
            $offset = ($page - 1) * $reviewsPerPage;

            $whereClauses = ["product_id = ?", "status = 'approved'"];
            $params = [$productId];

            if (isset($filters['rating']) && $filters['rating'] >= 1 && $filters['rating'] <= 5) {
                $whereClauses[] = "rating = ?";
                $params[] = $filters['rating'];
            }
            if (!empty($filters['has_photo'])) {
                $whereClauses[] = "photos IS NOT NULL AND JSON_LENGTH(photos) > 0";
            }
            if (!empty($filters['has_comment'])) {
                $whereClauses[] = "content IS NOT NULL AND content != ''";
            }

            $whereSql = implode(" AND ", $whereClauses);
            $sql = "SELECT * FROM {$this->table} WHERE $whereSql ORDER BY created_at DESC LIMIT ?, ?";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind params manually
            foreach ($params as $k => $v) {
                $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, $reviewsPerPage, PDO::PARAM_INT);
            
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'reviews' => $reviews,
                'pagination' => [
                    'current_page' => $page,
                    'has_more' => count($reviews) === $reviewsPerPage
                ]
            ];
        } catch (PDOException $e) {
            error_log("Error fetching reviews page for {$productId}: " . $e->getMessage());
            return [
                'reviews' => [],
                'pagination' => [
                    'current_page' => $page,
                    'has_more' => false
                ]
            ];
        }
    }

    public function fetchSingleReview(int $reviewId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? AND status = 'approved' LIMIT 1");
            $stmt->execute([$reviewId]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);

            return $review ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching single review {$reviewId}: " . $e->getMessage());
            return null;
        }
    }

    public function fetchBadgeData(string $productId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT total_reviews, average_rating FROM product_review_summary WHERE product_id = ?");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: ['total_reviews' => 0, 'average_rating' => 0];
        } catch (PDOException $e) {
            error_log("Error fetching badge data for {$productId}: " . $e->getMessage());
            return ['total_reviews' => 0, 'average_rating' => 0];
        }
    }

    public function fetchBulkBadgeData(array $productIds): array
    {
        try {
            $productIds = array_values($productIds);
            if (empty($productIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stmt = $this->pdo->prepare("SELECT product_id, total_reviews, average_rating FROM product_review_summary WHERE product_id IN ($placeholders)");
            $stmt->execute($productIds);

            $response = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $response[$row['product_id']] = [
                    'total_reviews' => (int)$row['total_reviews'],
                    'average_rating' => (float)$row['average_rating']
                ];
            }

            return $response;
        } catch (PDOException $e) {
            error_log("Error fetching bulk badge data: " . $e->getMessage());
            return [];
        }
    }
}
