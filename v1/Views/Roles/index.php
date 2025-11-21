<?php // v2/Views/Roles/index.php ?>
<div x-data="{ isRoleModalOpen: false }">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Quản lý Vai trò & Quyền hạn</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Vai trò & Quyền hạn</li>
            </ol>
        </nav>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Roles&action=updatePermissions">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Phân quyền theo vai trò</h3>
                <button @click="isRoleModalOpen = true" type="button" class="btn btn-secondary">Thêm vai trò</button>
            </div>
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 text-left font-semibold text-gray-600 dark:text-gray-400 sticky left-0 bg-white dark:bg-white/[0.03]">Quyền hạn</th>
                            <?php foreach ($data['roles'] ?? [] as $role): ?>
                                <th class="p-4 text-center font-semibold text-gray-600 dark:text-gray-400 min-w-[150px]"><?php echo htmlspecialchars($role['name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php foreach ($data['permissions'] ?? [] as $permission): ?>
                            <tr>
                                <td class="p-4 sticky left-0 bg-white dark:bg-white/[0.03]">
                                    <p class="font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($permission['description']); ?></p>
                                    <p class="font-mono text-xs text-gray-500"><?php echo htmlspecialchars($permission['slug']); ?></p>
                                </td>
                                <?php foreach ($data['roles'] as $role): 
                                    $hasPermission = in_array($permission['id'], $data['rolePermissions'][$role['id']] ?? []);
                                ?>
                                    <td class="p-4 text-center">
                                        <input type="checkbox" name="permissions[<?php echo $role['id']; ?>][]" value="<?php echo $permission['id']; ?>"
                                            class="h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600"
                                            <?php if ($hasPermission) echo 'checked'; ?>
                                            <?php if ($role['slug'] === 'super-admin') echo ' disabled'; // Super Admin luôn có mọi quyền ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary">Lưu tất cả thay đổi</button>
        </div>
    </form>

    <!-- ===== MODAL: Thêm vai trò mới ===== -->
    <div x-show="isRoleModalOpen" class="fixed inset-0 flex items-center justify-center p-5 z-99999" style="display: none;">
        <div @click="isRoleModalOpen = false" class="fixed inset-0 bg-gray-400/50 backdrop-blur-sm"></div>
        <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Roles&action=store" @click.outside="isRoleModalOpen = false" class="relative w-full max-w-lg rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-8">
             <button @click.prevent="isRoleModalOpen = false" class="transition-color absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-800"><svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z" fill=""/></svg></button>
            <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Tạo vai trò mới</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Tạo một nhóm quyền mới cho người dùng.</p>
            <div class="space-y-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Tên vai trò</label>
                    <input type="text" name="name" placeholder="Ví dụ: Nhân viên Marketing" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Slug (dùng trong code)</label>
                    <input type="text" name="slug" placeholder="Ví dụ: marketing-staff" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Mô tả</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500" placeholder="Mô tả ngắn về các quyền của vai trò này..."></textarea>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-6 justify-end">
                <button @click.prevent="isRoleModalOpen = false" type="button" class="btn btn-secondary">Đóng</button>
                <button type="submit" class="btn btn-primary">Tạo vai trò</button>
            </div>
        </form>
    </div>
</div>