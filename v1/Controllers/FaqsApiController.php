<?php
namespace BO_System\Controllers;

use BO_System\Controllers\ApiController;
use BO_System\Models\FaqApiModel;
use BO_System\Core\Database;
use BO_System\Helpers\LogHelper;

class FaqsApiController extends ApiController
{
    private FaqApiModel $faqModel;

    public function __construct()
    {
        parent::__construct();
        $this->faqModel = new FaqApiModel(Database::getConnection());
    }

    public function get_faqs()
    {
        $productId = $_GET['product_id'] ?? 0;
        $page = (int)($_GET['page'] ?? 1);
        $filterCategory = $_GET['filter_category'] ?? '';
        $faqsPerPage = 10;
        $offset = ($page - 1) * $faqsPerPage;

        // 1. Fetch all approved FAQs
        $allFaqs = $this->faqModel->fetchAllApprovedFaqs($productId);

        // 2. Build Tree
        $faqsById = [];
        foreach ($allFaqs as $faq) {
            $faq['replies'] = [];
            $faqsById[$faq['id']] = $faq;
        }

        $tree = [];
        foreach ($faqsById as $id => &$faq) {
            if (!empty($faq['parent_id']) && isset($faqsById[$faq['parent_id']])) {
                $faqsById[$faq['parent_id']]['replies'][] = &$faq;
            } else {
                $tree[] = &$faq;
            }
        }
        unset($faq);

        // 3. Filter
        $filteredTree = $tree;
        if (!empty($filterCategory)) {
            $filteredTree = array_filter($tree, function($question) use ($filterCategory) {
                return $question['category'] === $filterCategory;
            });
        }

        // 4. Paginate
        $totalQuestions = count($filteredTree);
        // array_reverse to show newest first (since query was ASC)
        $paginatedQuestions = array_slice(array_reverse($filteredTree), $offset, $faqsPerPage);

        $response = [
            'faqs' => array_values($paginatedQuestions), // Ensure array keys are reset
            'pagination' => [
                'current_page' => $page,
                'has_more' => ($offset + $faqsPerPage) < $totalQuestions
            ]
        ];

        $this->jsonResponse($response);
    }

    public function get_categories()
    {
        $productId = $_GET['product_id'] ?? 0;
        $data = $this->faqModel->fetchCategories($productId);
        $this->jsonResponse($data);
    }

    public function submit_question()
    {
        // Only allow POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $this->jsonError('Method not allowed', 405);
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['product_id']) || empty($data['author_name']) || empty($data['question_content']) || empty($data['category'])) {
            LogHelper::warning('FAQ Submission Failed: Missing required fields', ['input' => $data]);
            $this->jsonError('Vui lòng điền đầy đủ các trường bắt buộc.', 400);
        }

        $success = $this->faqModel->submitQuestion($data);

        if ($success) {
            LogHelper::info('FAQ Submitted Successfully', [
                'product_id' => $data['product_id'], 
                'author' => $data['author_name']
            ]);
            $this->jsonResponse(['success' => 'Câu hỏi của bạn đã được gửi thành công và đang chờ duyệt.'], 201);
        } else {
            LogHelper::error('FAQ Submission Failed: Database Error', ['input' => $data]);
            $this->jsonError('Đã có lỗi xảy ra. Vui lòng thử lại.', 500);
        }
    }
}
