<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Database;
use BO_System\Core\SessionManager;
use BO_System\Core\Validator;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;
use BO_System\Models\FaqsModel;
use BO_System\Services\AuthService;

/**
 * FaqsController xử lý tất cả các yêu cầu liên quan đến module Hỏi & Đáp (FAQ).
 * Tuân thủ quy ước đặt tên phương thức CRUD:
 * - index(): Hiển thị danh sách (GET)
 * - edit(): Hiển thị form chỉnh sửa (GET)
 * - update(): Cập nhật dữ liệu từ form (POST)
 * - destroy(): Xóa một bản ghi (GET/POST)
 */
class FaqsController extends BaseController
{
    private FaqsModel $faqModel;

    public function __construct()
    {
        parent::__construct();

        // Nếu không có quyền xem, dừng lại ngay lập tức
        if (!$this->auth->can('faqs.view')) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }

        $this->faqModel = new FaqsModel(Database::getConnection());
    }

    /**
     * Hiển thị danh sách các câu hỏi (thay thế cho hàm view() cũ).
     */
    public function index(): void
    {
        // Lấy các tham số lọc và phân trang từ URL
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Các bộ lọc tìm kiếm
        $searchTerm = $_GET['search_term'] ?? '';
        $productFilter = $_GET['product_filter'] ?? '';
        $categoryFilter = $_GET['category_filter'] ?? '';
        $statusFilter = $_GET['status_filter'] ?? '';

        // Lấy dữ liệu từ Model với các bộ lọc đã áp dụng
        $faqsData = $this->faqModel->getFaqs($searchTerm, $productFilter, $categoryFilter, $statusFilter, $limit, $offset);
        
        // Đếm tổng số dòng để phân trang
        $totalRows = $this->faqModel->countFaqs($searchTerm, $productFilter, $categoryFilter, $statusFilter);
        $totalPages = ceil($totalRows / $limit);
        
        // Lấy danh sách sản phẩm và danh mục để hiển thị trong dropdown filter
        $productsForFilter = $this->faqModel->getProductsForFilter();
        $categoriesForFilter = $this->faqModel->getCategoriesForFilter();
        
        // Lấy và xóa flash message (nếu có, ví dụ sau khi xóa)
        $flash_message = SessionManager::get('flash_message');
        SessionManager::unset('flash_message');

        // Chuẩn bị dữ liệu để truyền cho View
        $viewData = [
            'faqs_data' => $faqsData,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'search_term' => $searchTerm, 
            'product_filter' => $productFilter, 
            'category_filter' => $categoryFilter, 
            'status_filter' => $statusFilter, 
            'products_for_filter' => $productsForFilter,
            'categories_for_filter' => $categoriesForFilter,
            'flash_message' => $flash_message
        ];

        // Tải View và truyền dữ liệu
        $this->loadView('Faqs/index', $viewData);
    }

    /**
     * Hiển thị form để chỉnh sửa một câu hỏi (GET request).
     */
    public function edit(): void
    {
        // **CỔNG KIỂM TRA HÀNH ĐỘNG**
        if (!$this->auth->can('faqs.edit')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $faq_id = (int)($_GET['id'] ?? 0);
        if ($faq_id <= 0) {
            $this->renderContent(Helper::renderError("ID câu hỏi không hợp lệ."));
            return;
        }
        
        $faq = $this->faqModel->getFaqById($faq_id);
        if (!$faq) {
            $this->renderContent(Helper::renderError("Không tìm thấy câu hỏi với ID này."));
            return;
        }
        
        $answer = $this->faqModel->getAnswerByFaqId($faq_id);

        // Lấy và xóa flash data từ session (sau một lần redirect)
        $flash_message = SessionManager::get('flash_message');
        $errors = SessionManager::get('errors', []);
        $old_input = SessionManager::get('old_input', []);
        SessionManager::unset('flash_message');
        SessionManager::unset('errors');
        SessionManager::unset('old_input');

        // Chuẩn bị dữ liệu cho View
        $viewData = [
            'faq' => $old_input ?: $faq, // Ưu tiên dữ liệu cũ nếu có lỗi validation
            'answer_content' => $old_input['answer_content'] ?? ($answer['answer_content'] ?? ''),
            'product_name' => $faq['p_name'] ?? 'Không xác định',
            'created_at_formatted' => Helper::formatForDatetimeLocal($old_input['created_at_time'] ?? $faq['created_at']),
            'answered_at_formatted' => Helper::formatForDatetimeLocal($old_input['answered_at_time'] ?? ($answer['answered_at'] ?? '')),
            'csrf_token' => $this->auth->generateCsrfToken(),
            'flash_message' => $flash_message,
            'errors' => $errors,
        ];

        $this->loadView('Faqs/edit', $viewData);
    }

    /**
     * Xử lý việc cập nhật dữ liệu từ form edit (POST request).
     */
    public function update(): void
    {
        // **CỔNG KIỂM TRA HÀNH ĐỘNG**
        if (!$this->auth->can('faqs.edit')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        $faq_id = (int)($_GET['id'] ?? 0);
        $redirectUrl = Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Faqs', 'action' => 'edit', 'id' => $faq_id]);

        // 1. Kiểm tra CSRF Token
        if (!hash_equals(SessionManager::get('csrf_token'), $_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực form không hợp lệ. Vui lòng thử lại.']);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // 2. Validate dữ liệu đầu vào
        $validator = Validator::make($_POST, [
            'author_name' => 'required|max:255',
            'category' => 'required',
            'question_content' => 'required',
            'status' => 'required',
            'created_at_time' => 'required',
        ]);

        if ($validator->fails()) {
            SessionManager::set('errors', $validator->getErrors());
            SessionManager::set('old_input', $_POST);
            header('Location: ' . $redirectUrl);
            exit();
        }

        // 3. Chuẩn bị dữ liệu và gọi Model
        $questionData = [
            'author_name'      => trim($_POST['author_name']),
            'category'         => trim($_POST['category']),
            'question_content' => trim($_POST['question_content']),
            'status'           => trim($_POST['status']),
            'created_at'       => date('Y-m-d H:i:s', strtotime(trim($_POST['created_at_time']))),
            'product_id'       => $_POST['product_id']
        ];
        
        $answered_at_str = trim($_POST['answered_at_time']);
        $answerData = [
            'answer_content' => trim($_POST['answer_content']), // TinyMCE có thể trả về HTML, trim là đủ
            'answered_at'    => !empty($answered_at_str) ? date('Y-m-d H:i:s', strtotime($answered_at_str)) : date('Y-m-d H:i:s')
        ];

        $success = $this->faqModel->updateFaqAndAnswer($faq_id, $questionData, $answerData);
        
        // 4. Set Flash Message và Redirect (PRG Pattern)
        if ($success) {
            LogHelper::info("FAQ updated: ID {$faq_id} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Cập nhật thành công!']);
        } else {
            LogHelper::error("Failed to update FAQ: ID {$faq_id}.");
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Đã có lỗi xảy ra trong quá trình cập nhật.']);
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
    
    /**
     * Xóa một câu hỏi (thay thế cho hàm delete() cũ).
     */
    public function destroy(): void
    {
        if (!$this->auth->can('faqs.delete')) {
            http_response_code(403);
            die('403 - Bạn không có quyền thực hiện hành động này.');
        }
        
        // Kiểm tra CSRF Token
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!$this->auth->validateCsrfToken($token)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật (CSRF).']);
            header("Location: " . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Faqs', 'action' => 'index']));
            exit();
        }

        $faqIdToDelete = (int)($_GET['id'] ?? 0);
        if ($faqIdToDelete > 0) {
            $this->faqModel->deleteFaq($faqIdToDelete);
            LogHelper::warning("FAQ deleted: ID {$faqIdToDelete} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => 'Đã xóa câu hỏi thành công.']);
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'ID câu hỏi không hợp lệ.']);
        }
        header("Location: " . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Faqs', 'action' => 'index']));
        exit();
    }
}