<?php
use BO_System\Helpers\Helper;
use BO_System\Core\Permissions;
use BO_System\Services\AuthService;

$auth = AuthService::getInstance();

// Extract data from BaseController
$files = $data['files'] ?? [];
$flash_message = $data['flash_message'] ?? null;
$logPath = $data['logPath'] ?? '';
$csrf_token = $data['csrf_token'] ?? '';
?>

<div class="bg-white p-6 rounded-lg shadow-sm mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Nhật ký hệ thống (System Logs)</h1>
        <button onclick="location.reload()" class="btn btn-secondary">Làm mới</button>
    </div>

    <?php if (isset($flash_message)): ?>
        <div class="mb-4 p-4 rounded <?php echo $flash_message['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo $flash_message['text']; ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Tên File</th>
                    <th class="py-3 px-6 text-left">Kích thước</th>
                    <th class="py-3 px-6 text-left">Cập nhật lần cuối</th>
                    <th class="py-3 px-6 text-center">Hành động</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="4" class="py-3 px-6 text-center">
                            Không có file log nào.<br>
                            <span class="text-xs text-gray-400">(Đường dẫn: <?php echo htmlspecialchars($logPath ?? 'N/A'); ?>)</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="font-medium"><?php echo htmlspecialchars($file['name']); ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-6 text-left">
                                <?php echo $file['size']; ?>
                            </td>
                            <td class="py-3 px-6 text-left">
                                <?php echo $file['modified']; ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center gap-2">
                                    <a href="<?php echo Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs', 'action' => 'view', 'file' => $file['name']]); ?>" 
                                       class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200" title="Xem chi tiết">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    <a href="<?php echo Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs', 'action' => 'download', 'file' => $file['name']]); ?>" 
                                       class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200" title="Tải về">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>

                                    <?php if ($auth->can(Permissions::SYSTEM_LOGS_DELETE)): ?>
                                        <form action="<?php echo Helper::buildUrl('/' . APP_VERSION . '/index.php', ['module' => 'Logs', 'action' => 'destroy']); ?>" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file log này không? Hành động này không thể hoàn tác.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <button type="submit" class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-200" title="Xóa">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
