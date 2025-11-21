<?php
use BO_System\Helpers\Helper;

// Extract data from BaseController
$filename = $data['filename'] ?? '';
$logs = $data['logs'] ?? [];
$filesize = $data['filesize'] ?? '';
$csrf_token = $data['csrf_token'] ?? '';
$pagination = $data['pagination'] ?? ['current_page' => 1, 'total_pages' => 1, 'total_records' => 0];
$filterLevel = $data['filterLevel'] ?? 'ALL';
?>

<div class="bg-white p-6 rounded-lg shadow-sm mb-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <?php echo htmlspecialchars($filename); ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Kích thước: <?php echo $filesize; ?> | 
                Tổng số dòng: <?php echo number_format($pagination['total_records']); ?> |
                Trang <?php echo $pagination['current_page']; ?>/<?php echo $pagination['total_pages']; ?>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs']); ?>" class="btn btn-secondary">
                Quay lại
            </a>
            
            <form action="<?php echo Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs', 'action' => 'destroy']); ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file log này không? Hành động này không thể hoàn tác.');">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($filename); ?>">
                <button type="submit" class="btn btn-primary">Xóa File</button>
            </form>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="mb-4 flex gap-4 items-center bg-gray-50 p-3 rounded-lg border border-gray-200">
        <span class="text-sm font-medium text-gray-700">Lọc theo cấp độ:</span>
        <?php
        $levels = ['ALL' => 'Tất cả', 'ERROR' => 'ERROR', 'WARNING' => 'WARNING', 'INFO' => 'INFO'];
        foreach ($levels as $key => $label):
            $isActive = ($filterLevel === $key);
            $btnClass = '';
            if ($isActive) {
                if ($key === 'ALL') $btnClass = 'bg-gray-800 text-white';
                elseif ($key === 'ERROR') $btnClass = 'bg-red-600 text-white';
                elseif ($key === 'WARNING') $btnClass = 'bg-yellow-500 text-white';
                elseif ($key === 'INFO') $btnClass = 'bg-blue-500 text-white';
            } else {
                if ($key === 'ALL') $btnClass = 'bg-gray-200 text-gray-700 hover:bg-gray-300';
                elseif ($key === 'ERROR') $btnClass = 'bg-red-100 text-red-700 hover:bg-red-200';
                elseif ($key === 'WARNING') $btnClass = 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200';
                elseif ($key === 'INFO') $btnClass = 'bg-blue-100 text-blue-700 hover:bg-blue-200';
            }
            
            $urlParams = $_GET;
            $urlParams['level'] = $key;
            $urlParams['page'] = 1; // Reset page
            $url = '?' . http_build_query($urlParams);
        ?>
            <a href="<?php echo $url; ?>" class="px-3 py-1 rounded text-sm font-medium transition-colors <?php echo $btnClass; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Log Content Table -->
    <div class="mt-4 w-full overflow-x-auto border rounded-lg">
        <table class="min-w-full bg-white text-sm font-mono">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="py-2 px-4 text-left w-40">Thời gian</th>
                    <th class="py-2 px-4 text-center w-24">Level</th>
                    <th class="py-2 px-4 text-left">Nội dung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($logs as $log): ?>
                    <?php 
                        $levelClass = 'bg-gray-100 text-gray-800';
                        if ($log['level'] === 'ERROR') $levelClass = 'bg-red-100 text-red-800';
                        elseif ($log['level'] === 'WARNING') $levelClass = 'bg-yellow-100 text-yellow-800';
                        elseif ($log['level'] === 'INFO') $levelClass = 'bg-blue-100 text-blue-800';
                    ?>
                    <tr>
                        <td class="py-2 px-4 text-gray-500 whitespace-nowrap align-top"><?php echo $log['time']; ?></td>
                        <td class="py-2 px-4 text-center align-top">
                            <span class="px-2 py-1 rounded text-xs font-bold <?php echo $levelClass; ?>">
                                <?php echo $log['level']; ?>
                            </span>
                        </td>
                        <td class="py-2 px-4 text-gray-800 whitespace-pre-wrap break-words align-top"><?php echo htmlspecialchars($log['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="3" class="py-8 text-center text-gray-500">
                            <?php echo ($filterLevel !== 'ALL') ? 'Không có log nào phù hợp với bộ lọc.' : 'File log trống.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

        <!-- Pagination -->
        <?php
        $current_page = $pagination['current_page'];
        $total_pages = $pagination['total_pages'];
        $total_rows = $pagination['total_records'];
        
        if ($total_rows > 0):
        ?>
        <div class="mt-6 flex flex-wrap justify-between items-center gap-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?> (Tổng <?php echo number_format($total_rows); ?> dòng)</p>
            <?php if ($total_pages > 1): ?>
            <nav class="flex items-center gap-2">
                <?php
                $queryParams = $_GET;
                // Helper to build URL
                $buildUrl = function($page) use ($queryParams) {
                    $queryParams['page'] = $page;
                    return '?' . http_build_query($queryParams);
                };

                // First & Prev
                if ($current_page > 1) {
                    echo '<a href="' . $buildUrl(1) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&laquo;</a>';
                    echo '<a href="' . $buildUrl($current_page - 1) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&lsaquo;</a>';
                } else {
                    echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&laquo;</span>';
                    echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&lsaquo;</span>';
                }

                // Page Numbers
                $range = 2;
                for ($i = $current_page - $range; $i <= $current_page + $range; $i++) {
                    if ($i > 0 && $i <= $total_pages) {
                        if ($i == $current_page) {
                            echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-indigo-500 bg-indigo-50 text-indigo-600 text-sm font-semibold">' . $i . '</span>';
                        } else {
                            echo '<a href="' . $buildUrl($i) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">' . $i . '</a>';
                        }
                    }
                }

                // Next & Last
                if ($current_page < $total_pages) {
                    echo '<a href="' . $buildUrl($current_page + 1) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&rsaquo;</a>';
                    echo '<a href="' . $buildUrl($total_pages) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&raquo;</a>';
                } else {
                    echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&rsaquo;</span>';
                    echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&raquo;</span>';
                }
                ?>
            </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
