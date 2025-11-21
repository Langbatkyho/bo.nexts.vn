<?php
namespace BO_System\Controllers;

use BO_System\Controllers\ApiController;
use BO_System\Models\ReviewApiModel;
use BO_System\Core\Database;
use BO_System\Helpers\LogHelper;

class ReviewsApiController extends ApiController
{
    private ReviewApiModel $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new ReviewApiModel(Database::getConnection());
    }

    public function get_product_data()
    {
        $productId = $_GET['product_id'] ?? 0;
        
        if (empty($productId)) {
            LogHelper::warning('Reviews API: Missing product_id for get_product_data');
        }

        $data = $this->reviewModel->fetchProductData($productId);
        $photos = $this->reviewModel->fetchPhotos($productId, 12);
        $reviewsData = $this->reviewModel->fetchReviewsPage($productId, 1);

        $data['summary']['photos'] = $photos;
        $data['reviews'] = $reviewsData['reviews'];
        $data['pagination'] = $reviewsData['pagination'];
        $data['pagination']['has_more'] = ($data['summary']['total_reviews'] ?? 0) > 5;

        $this->jsonResponse($data);
    }

    public function get_reviews_page()
    {
        $productId = $_GET['product_id'] ?? 0;
        $page = (int)($_GET['page'] ?? 1);
        
        $filters = [
            'rating' => isset($_GET['filter_rating']) ? (int)$_GET['filter_rating'] : 0,
            'has_photo' => isset($_GET['filter_has_photo']) ? 1 : 0,
            'has_comment' => isset($_GET['filter_has_comment']) ? 1 : 0,
        ];

        $data = $this->reviewModel->fetchReviewsPage($productId, $page, $filters);
        $this->jsonResponse($data);
    }

    public function get_single_review()
    {
        $reviewId = (int)($_GET['review_id'] ?? 0);
        if ($reviewId <= 0) {
            LogHelper::warning('Reviews API: Invalid review_id', ['review_id' => $_GET['review_id'] ?? 'null']);
            $this->jsonError("Invalid review ID", 400);
            return;
        }

        $data = $this->reviewModel->fetchSingleReview($reviewId);
        if ($data) {
            $this->jsonResponse($data);
        } else {
            LogHelper::info('Reviews API: Review not found', ['review_id' => $reviewId]);
            $this->jsonError("Review not found or not approved", 404);
        }
    }

    public function get_badge_data()
    {
        $productId = $_GET['product_id'] ?? 0;
        $data = $this->reviewModel->fetchBadgeData($productId);
        $this->jsonResponse($data);
    }

    public function get_bulk_badge_data()
    {
        $productIdsStr = $_GET['product_ids'] ?? '';
        if (empty($productIdsStr)) {
            $this->jsonResponse([]);
            return;
        }
        
        $productIds = array_unique(explode(',', $productIdsStr));
        $sanitizedIds = array_filter($productIds, function($id) { return !empty(trim($id)); });
        
        if (empty($sanitizedIds)) {
            LogHelper::warning('Reviews API: Empty sanitized product_ids for bulk badge', ['raw_input' => $productIdsStr]);
            $this->jsonResponse([]);
            return;
        }

        $data = $this->reviewModel->fetchBulkBadgeData($sanitizedIds);
        
        foreach ($sanitizedIds as $id) {
            if (!isset($data[$id])) {
                $data[$id] = ['total_reviews' => 0, 'average_rating' => 0];
            }
        }

        $this->jsonResponse($data);
    }
}
