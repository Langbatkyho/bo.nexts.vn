<?php // v2/Views/Permissions/index.php ?>
<div x-data="permissionsManager()">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Quản lý Quyền hạn</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Quản lý Quyền</li>
            </ol>
        </nav>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Cột Quyền mới & Quyền lỗi thời -->
        <div class="space-y-6">
            <!-- Card Quyền mới -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Quyền mới từ Code</h3>
                    <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Permissions&action=sync">
                         <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Đồng bộ ngay</button>
                    </form>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Các quyền này được định nghĩa trong code nhưng chưa có trong database. Nhấn "Đồng bộ ngay" để thêm chúng.</p>
                <?php if (!empty($data['newPermissions'])): ?>
                    <ul class="space-y-2 max-h-40 overflow-y-auto">
                        <?php foreach ($data['newPermissions'] as $slug): ?>
                            <li class="p-2 bg-green-100/50 rounded-md text-sm font-mono text-green-800"><?php echo htmlspecialchars($slug); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-brand-500">✓ Không có quyền mới nào.</p>
                <?php endif; ?>
            </div>

            <!-- Card Quyền lỗi thời -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">Quyền không còn sử dụng</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Các quyền này có trong DB nhưng không còn trong code. Bạn có thể xóa nếu không cần đến.</p>
                <?php if (!empty($data['obsoletePermissions'])): ?>
                    <ul class="space-y-2 max-h-40 overflow-y-auto">
                        <?php foreach ($data['obsoletePermissions'] as $slug): ?>
                            <li class="p-2 bg-yellow-100/50 rounded-md text-sm font-mono text-yellow-800"><?php echo htmlspecialchars($slug); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-brand-500">✓ Không có quyền nào lỗi thời.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cột Danh sách quyền hiện tại -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">Tất cả quyền trong Database</h3>
            <div class="max-h-[600px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white dark:bg-white/[0.03]">
                        <tr>
                            <th class="p-2 text-left font-semibold text-gray-600 dark:text-gray-400">Mô tả</th>
                            <th class="p-2 text-right font-semibold text-gray-600 dark:text-gray-400">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <?php foreach ($data['permissionsFromDb'] as $permission): ?>
                            <tr>
                                <td class="p-3">
                                    <p class="font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($permission['description']); ?></p>
                                    <p class="font-mono text-xs text-gray-500"><?php echo htmlspecialchars($permission['slug']); ?></p>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click='openModal(<?php echo json_encode($permission, JSON_HEX_APOS); ?>)' class="btn btn-secondary">Sửa</button>
                                        <a href="/<?= APP_VERSION ?>/index.php?module=Permissions&action=destroy&id=<?php echo $permission['id']; ?>&csrf_token=<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>" class="btn-sm btn-danger btn-delete">Xóa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Sửa Quyền -->
    <div x-show="isModalOpen" class="fixed inset-0 flex items-center justify-center p-5 z-99999" style="display: none;">
        <div @click="isModalOpen = false" class="fixed inset-0 bg-gray-400/50 backdrop-blur-sm"></div>
        <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Permissions&action=update" @click.outside="isModalOpen = false" class="relative w-full max-w-lg rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-8">
            <input type="hidden" name="id" x-model="formData.id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

            <h3 class="mb-6 text-2xl font-semibold">Chỉnh sửa mô tả quyền</h3>
            <div class="space-y-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Slug (Không thể thay đổi)</label>
                    <input type="text" x-model="formData.slug" class="form-input w-full bg-gray-100 dark:bg-gray-800" readonly>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Mô tả</label>
                    <textarea name="description" x-model="formData.description" rows="3" class="form-input w-full" required></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="isModalOpen = false" class="btn btn-secondary">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Alpine.js Logic -->
<script>
function permissionsManager() {
    return {
        isModalOpen: false,
        formData: { id: '', slug: '', description: '' },
        
        openModal(permission) {
            this.formData = { ...permission };
            this.isModalOpen = true;
        },

        init() {
            // Gắn sự kiện cho tất cả các nút xóa
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const deleteUrl = this.href;
                    
                    Swal.fire({
                        title: 'Bạn có chắc chắn?',
                        text: "Hành động này không thể hoàn tác! Việc xóa quyền có thể ảnh hưởng đến các vai trò đang sử dụng nó.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Có, xóa nó!',
                        cancelButtonText: 'Hủy bỏ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = deleteUrl;
                        }
                    });
                });
            });
        }
    }
}

// Khởi chạy Alpine component khi DOM sẵn sàng
document.addEventListener('alpine:init', () => {
    Alpine.data('permissionsManager', permissionsManager);
});
</script>