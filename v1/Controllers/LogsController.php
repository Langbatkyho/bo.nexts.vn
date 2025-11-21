<?php
namespace BO_System\Controllers;

use BO_System\Controllers\BaseController;
use BO_System\Core\Permissions;
use BO_System\Core\SessionManager;
use BO_System\Helpers\Helper;
use BO_System\Helpers\LogHelper;

class LogsController extends BaseController
{
    private string $logPath;

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->can(Permissions::SYSTEM_LOGS_VIEW)) {
            http_response_code(403);
            die('403 - Bạn không có quyền truy cập chức năng này.');
        }

        // Sử dụng dirname để lấy đường dẫn cha (v2) rồi vào logs
        $this->logPath = dirname(__DIR__) . '/logs/';
    }

    /**
     * Hiển thị danh sách các file log.
     */
    public function index(): void
    {
        $files = [];
        if (is_dir($this->logPath)) {
            $scannedFiles = scandir($this->logPath);
            if ($scannedFiles !== false) {
                foreach ($scannedFiles as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if (pathinfo($file, PATHINFO_EXTENSION) !== 'log') continue;

                    $filePath = $this->logPath . $file;
                    $files[] = [
                        'name' => $file,
                        'size' => $this->formatSize(filesize($filePath)),
                        'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'path' => $filePath
                    ];
                }
            }
        }

        // Sắp xếp file mới nhất lên đầu
        usort($files, function ($a, $b) {
            return filemtime($b['path']) - filemtime($a['path']);
        });

        $viewData = [
            'files' => $files,
            'flash_message' => SessionManager::get('flash_message'),
            'logPath' => $this->logPath,
            'csrf_token' => $this->auth->generateCsrfToken(),
        ];
        SessionManager::unset('flash_message');

        $this->loadView('Logs/index', $viewData);
    }

    /**
     * Xem chi tiết nội dung một file log.
     */
    public function view(): void
    {
        $filename = $_GET['file'] ?? '';
        
        // Bảo mật: Ngăn chặn Path Traversal (ví dụ: ../../config.php)
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename) || strpos($filename, '..') !== false) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tên file không hợp lệ.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
            exit();
        }

        $filePath = $this->logPath . $filename;
        if (!file_exists($filePath)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'File không tồn tại.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
            exit();
        }

        // Đọc nội dung file
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $parsedLogs = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;

            // Format log chuẩn: [Y-m-d H:i:s] [LEVEL] Message
            if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $line, $matches)) {
                $parsedLogs[] = [
                    'time' => $matches[1],
                    'level' => $matches[2],
                    'message' => $matches[3]
                ];
            } else {
                // Trường hợp log không đúng định dạng (ví dụ stack trace nhiều dòng), gộp vào dòng log trước đó
                if (!empty($parsedLogs)) {
                    $lastIndex = count($parsedLogs) - 1;
                    $parsedLogs[$lastIndex]['message'] .= "\n" . $line;
                } else {
                    // Nếu dòng đầu tiên đã lỗi format
                    $parsedLogs[] = [
                        'time' => '',
                        'level' => 'UNKNOWN',
                        'message' => $line
                    ];
                }
            }
        }
        
        // Đảo ngược để xem log mới nhất trước
        $parsedLogs = array_reverse($parsedLogs);

        // Filter by Level
        $filterLevel = $_GET['level'] ?? 'ALL';
        if ($filterLevel !== 'ALL') {
            $parsedLogs = array_filter($parsedLogs, function($log) use ($filterLevel) {
                return $log['level'] === $filterLevel;
            });
            // Re-index array after filtering
            $parsedLogs = array_values($parsedLogs);
        }

        // Phân trang
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $totalRecords = count($parsedLogs);
        $totalPages = ceil($totalRecords / $limit);
        
        // Đảm bảo page hợp lệ
        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

        $offset = ($page - 1) * $limit;
        $paginatedLogs = array_slice($parsedLogs, $offset, $limit);

        $viewData = [
            'filename' => $filename,
            'logs' => $paginatedLogs,
            'filesize' => $this->formatSize(filesize($filePath)),
            'csrf_token' => $this->auth->generateCsrfToken(),
            'filterLevel' => $filterLevel,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ];

        $this->loadView('Logs/view', $viewData);
    }

    /**
     * Xóa file log.
     */
    public function destroy(): void
    {
        if (!$this->auth->can(Permissions::SYSTEM_LOGS_DELETE)) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Bạn không có quyền xóa log.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
            exit();
        }

        $filename = $_POST['filename'] ?? '';
        
        // Validate CSRF
        if (!$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Lỗi xác thực bảo mật.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
            exit();
        }

        // Validate Filename
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename) || strpos($filename, '..') !== false) {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'Tên file không hợp lệ.']);
            header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
            exit();
        }

        $filePath = $this->logPath . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
            LogHelper::warning("Log file deleted: {$filename} by Admin ID " . SessionManager::get('user_id'));
            SessionManager::set('flash_message', ['type' => 'success', 'text' => "Đã xóa file {$filename}."]);
        } else {
            SessionManager::set('flash_message', ['type' => 'error', 'text' => 'File không tồn tại.']);
        }

        header('Location: ' . Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']));
        exit();
    }
    
    /**
     * Tải xuống file log.
     */
    public function download(): void
    {
        $filename = $_GET['file'] ?? '';
        
        if (empty($filename) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename) || strpos($filename, '..') !== false) {
            die('Invalid filename');
        }

        $filePath = $this->logPath . $filename;
        if (!file_exists($filePath)) {
            die('File not found');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Định dạng kích thước file từ bytes sang KB, MB, GB.
     *
     * @param int $bytes Kích thước file tính bằng bytes.
     * @return string Chuỗi kích thước đã định dạng.
     */
    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
}
