<?php
use BO_System\Core\Permissions;
use BO_System\Services\AuthService;
use BO_System\Core\SessionManager;
$auth = AuthService::getInstance();
?>
<div x-data="usersManager()">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Quản lý Người dùng</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Người dùng</li>
            </ol>
        </nav>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Danh sách người dùng</h3>
            <?php if ($auth->can(Permissions::USERS_CREATE)): ?>
                <a href="/<?= APP_VERSION ?>/index.php?module=Users&action=create" class="btn btn-secondary">Thêm người dùng mới</a>
            <?php endif; ?>
        </div>
        
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left"><p class="font-medium text-gray-500 text-xs uppercase">Người dùng</p></th>
                        <th class="px-5 py-3 text-left"><p class="font-medium text-gray-500 text-xs uppercase">Vai trò</p></th>
                        <th class="px-5 py-3 text-left"><p class="font-medium text-gray-500 text-xs uppercase">Trạng thái</p></th>
                        <th class="px-5 py-3 text-right"><p class="font-medium text-gray-500 text-xs uppercase">Hành động</p></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php foreach ($data['users'] as $user): ?>
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($user['roles_list'] ?? 'Chưa có vai trò'); ?></p>
                        </td>
                        <td class="px-5 py-4">
                            <?php if ($user['is_active']): ?>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800">Hoạt động</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800">Bị khóa</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <?php if ($auth->can(Permissions::USERS_EDIT)): ?>
                                    <a href="/<?= APP_VERSION ?>/index.php?module=Users&action=edit&id=<?php echo $user['id']; ?>" class="inline-flex items-center px-3 py-1.5 rounded-md border border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-medium transition">Sửa / Phân quyền</a>
                                <?php endif; ?>
                                
                                <?php // Nút xóa, chỉ hiển thị nếu có quyền và không phải là user hiện tại
                                if ($auth->can(Permissions::USERS_DELETE) && $user['id'] !== SessionManager::get('user_id')): ?>
                                    <button type="button" 
                                            @click="confirmDelete('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')"
                                            class="inline-flex items-center px-3 py-1.5 rounded-md border border-red-500 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-medium transition">Xóa</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function usersManager() {
    return {
        csrfToken: '<?php echo $data['csrf_token'] ?? ''; ?>',
        
        confirmDelete(userId, username) {
            Swal.fire({
                title: 'Bạn có chắc chắn?',
                text: "Bạn đang chuẩn bị xóa người dùng: " + username + ". Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Có, xóa ngay!',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Chuyển hướng đến action destroy kèm theo CSRF token
                    window.location.href = `/<?= APP_VERSION ?>/index.php?module=Users&action=destroy&id=${userId}&csrf_token=${this.csrfToken}`;
                }
            });
        }
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('usersManager', usersManager);
});
</script>