<?php
// v2/Views/Shops/edit.php
?>
<div>
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Chỉnh sửa Shop</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white" href="/<?= APP_VERSION ?>/index.php?module=Shops&action=index">Shops <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Sửa</li>
            </ol>
        </nav>
    </div>

    <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Shops&action=update&id=<?php echo $data['shop']['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
        
        <!-- Flash Message -->
        <?php if (!empty($data['flash_message'])): ?>
            <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
                <?php echo htmlspecialchars($data['flash_message']['text']); ?>
            </div>
        <?php endif; ?>

        <!-- Bố cục 7/3 -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-10">
            <!-- Cột trái (7 phần) -->
            <div class="lg:col-span-7">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-5">Thông tin Shop</h3>
                    <div class="space-y-5">
                        <div>
                            <label for="shop_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tên Shop <span class="text-red-500">*</span></label>
                            <input type="text" id="shop_name" name="shop_name" value="<?php echo htmlspecialchars($data['shop']['shop_name'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500" required>
                            <?php if (isset($data['errors']['shop_name'])): ?><p class="text-sm text-red-600 mt-1.5"><?php echo htmlspecialchars($data['errors']['shop_name'][0]); ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="platform" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nền tảng <span class="text-red-500">*</span></label>
                            <input type="text" id="platform" name="platform" value="<?php echo htmlspecialchars($data['shop']['platform'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" readonly>
                            <?php if (isset($data['errors']['platform'])): ?><p class="text-sm text-red-600 mt-1.5"><?php echo htmlspecialchars($data['errors']['platform'][0]); ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="platform_shop_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Platform Shop ID <span class="text-red-500">*</span></label>
                            <input type="text" id="platform_shop_id" name="platform_shop_id" value="<?php echo htmlspecialchars($data['shop']['platform_shop_id'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" readonly>
                            <?php if (isset($data['errors']['platform_shop_id'])): ?><p class="text-sm text-red-600 mt-1.5"><?php echo htmlspecialchars($data['errors']['platform_shop_id'][0]); ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="platform_shop_code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Platform Shop Code <span class="text-red-500">*</span></label>
                            <input type="text" id="platform_shop_code" name="platform_shop_code" value="<?php echo htmlspecialchars($data['shop']['platform_shop_code'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" readonly>
                            <?php if (isset($data['errors']['platform_shop_code'])): ?><p class="text-sm text-red-600 mt-1.5"><?php echo htmlspecialchars($data['errors']['platform_shop_code'][0]); ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="access_token" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Access Token</label>
                            <textarea id="access_token" name="access_token" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500"><?php echo htmlspecialchars($data['shop']['access_token'] ?? ''); ?></textarea>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Token để xác thực API với nền tảng.</p>
                        </div>

                        <div>
                            <label for="refresh_token" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Refresh Token</label>
                            <textarea id="refresh_token" name="refresh_token" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500"><?php echo htmlspecialchars($data['shop']['refresh_token'] ?? ''); ?></textarea>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Token để làm mới access token khi hết hạn.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải (3 phần) -->
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6 space-y-4">
                    <h3 class="font-semibold text-black dark:text-white">Thông tin khác</h3>
                    
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ID Shop</label>
                        <input type="text" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" value="<?php echo htmlspecialchars($data['shop']['id'] ?? ''); ?>" readonly>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ngày tạo</label>
                        <input type="text" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" value="<?php echo isset($data['shop']['created_at']) ? date('d/m/Y H:i', strtotime($data['shop']['created_at'])) : 'N/A'; ?>" readonly>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cập nhật lần cuối</label>
                        <input type="text" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" value="<?php echo isset($data['shop']['updated_at']) ? date('d/m/Y H:i', strtotime($data['shop']['updated_at'])) : 'N/A'; ?>" readonly>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Access Token hết hạn</label>
                        <input type="text" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" value="<?php echo isset($data['shop']['access_token_expires_at']) && $data['shop']['access_token_expires_at'] ? date('d/m/Y H:i', strtotime($data['shop']['access_token_expires_at'])) : 'N/A'; ?>" readonly>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Refresh Token hết hạn</label>
                        <input type="text" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 shadow-theme-xs" value="<?php echo isset($data['shop']['refresh_token_expires_at']) && $data['shop']['refresh_token_expires_at'] ? date('d/m/Y H:i', strtotime($data['shop']['refresh_token_expires_at'])) : 'N/A'; ?>" readonly>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700">
                    
                    <div class="flex flex-col gap-3">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Lưu thay đổi
                        </button>
                        <a href="/<?= APP_VERSION ?>/index.php?module=Shops&action=index" class="btn btn-secondary w-full text-center">
                            Hủy bỏ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
