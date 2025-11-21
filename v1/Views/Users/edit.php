<?php 
$user = $data['user'] ?? null;
$isCreating = !$user;
?>
<div>
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90"><?php echo $isCreating ? 'Tạo Người dùng mới' : 'Chỉnh sửa Người dùng'; ?></h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800" href="/<?= APP_VERSION ?>/index.php?module=Users">Người dùng <svg class="stroke-current" width="17" height="16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90"><?php echo $isCreating ? 'Tạo mới' : 'Chỉnh sửa'; ?></li>
            </ol>
        </nav>
    </div>

    <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Users&action=store">
        <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">

    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>
        
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Cột trái -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border p-5">
                    <h3 class="text-lg font-semibold mb-5">Thông tin cơ bản</h3>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="col-span-2">
                            <label class="mb-1.5 block text-sm font-medium">Họ và tên <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" class="form-input w-full" required>
                        </div>
                        
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Tên đăng nhập <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="form-input w-full" required <?php if(!$isCreating) echo 'readonly'; ?>>
                            <?php if(!$isCreating): ?><p class="text-xs text-gray-500 mt-1">Không thể thay đổi tên đăng nhập.</p><?php endif; ?>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input w-full" required>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Số điện thoại 1</label>
                            <input type="text" name="phone_1" value="<?php echo htmlspecialchars($user['phone_1'] ?? ''); ?>" class="form-input w-full">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Số điện thoại 2</label>
                            <input type="text" name="phone_2" value="<?php echo htmlspecialchars($user['phone_2'] ?? ''); ?>" class="form-input w-full">
                        </div>
                    </div>
                    
                    <div class="mt-5">
                        <label class="mb-1.5 block text-sm font-medium">Mật khẩu</label>
                        <input type="password" name="password" class="form-input w-full" <?php if ($isCreating) echo 'required'; ?>>
                        <?php if (!$isCreating): ?><p class="text-xs text-gray-500 mt-1">Để trống nếu không muốn thay đổi.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cột phải -->
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-2xl border p-5">
                    <h3 class="text-lg font-semibold mb-5">Trạng thái & Vai trò</h3>
                    <div class="space-y-4">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded" <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Tài khoản hoạt động</span>
                        </label>
                        <hr class="my-4">
                        <p class="font-medium">Gán Vai trò</p>
                        <div class="space-y-2">
                            <?php if(empty($data['allRoles'])): ?>
                                <p class="text-sm text-gray-500">Chưa có vai trò nào được tạo.</p>
                            <?php else: ?>
                                <?php foreach($data['allRoles'] as $role): 
                                    $isChecked = in_array($role['id'], $data['userRoleIds']);
                                ?>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" class="h-5 w-5 rounded" <?php if($isChecked) echo 'checked'; ?>>
                                    <span><?php echo htmlspecialchars($role['name']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-5 mt-6">
            <h3 class="text-lg font-semibold mb-5">Cấp thêm quyền cho người dùng</h3>
            <p class="text-sm text-gray-500 mb-4">Ngoài các quyền có sẵn trong Vai trò (đã được làm mờ), người dùng này còn được cấp thêm các quyền dưới đây.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <?php if(empty($data['allPermissions'])): ?>
                    <p class="col-span-full text-sm text-gray-500">Chưa có quyền nào trong hệ thống.</p>
                <?php else: ?>
                    <?php foreach($data['allPermissions'] as $permission): 
                        $hasViaRole = in_array($permission['id'], $data['permissionsFromRoles']);
                        $hasDirectly = in_array($permission['id'], $data['userDirectPermissionIds']);
                    ?>
                     <label class="flex items-center space-x-2 <?php if($hasViaRole) echo 'opacity-60 cursor-not-allowed'; ?>">
                        <input type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" class="h-5 w-5 rounded" 
                               <?php if($hasViaRole || $hasDirectly) echo 'checked'; ?>
                               <?php if($hasViaRole) echo 'disabled'; ?>>
                        <span><?php echo htmlspecialchars($permission['description']); ?></span>
                    </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="/<?= APP_VERSION ?>/index.php?module=Users" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary">Lưu thông tin</button>
        </div>
    </form>
</div>