<?php
global $gravatar_url;
?>
<!-- Khởi tạo Alpine.js data cho các popup -->
<div x-data="{ isProfileModalOpen: false, isPasswordModalOpen: false }">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Hồ sơ cá nhân</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Cài đặt</li>
            </ol>
        </nav>
    </div>
    
    <!-- Thông báo Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <!-- Card Profile Header -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col items-center gap-5 sm:flex-row">
                <div class="w-20 h-20 overflow-hidden border border-gray-200 rounded-full dark:border-gray-800 shrink-0">
                    <img src="<?php echo htmlspecialchars($gravatar_url ?? ''); ?>" alt="User Avatar" />
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                        <?php echo htmlspecialchars($data['user']['full_name'] ?? 'N/A'); ?>
                    </h4>
                    <p class="text-sm text-gray-500 text-left"><?php echo htmlspecialchars($data['user']['email'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Card Thông tin cá nhân (Read-only) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-6">Thông tin chi tiết</h4>
                    <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2 lg:gap-7 2xl:gap-x-32">
                        <div><p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Tên đăng nhập</p><p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($data['user']['username'] ?? 'N/A'); ?></p></div>
                        <div><p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Địa chỉ Email</p><p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($data['user']['email'] ?? 'N/A'); ?></p></div>
                        <div><p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Số điện thoại 1</p><p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($data['user']['phone_1'] ?? 'Chưa cập nhật'); ?></p></div>
                        <div><p class="mb-2 text-xs leading-normal text-gray-500 dark:text-gray-400">Số điện thoại 2</p><p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo htmlspecialchars($data['user']['phone_2'] ?? 'Chưa cập nhật'); ?></p></div>
                    </div>
                </div>
                <button @click="isProfileModalOpen = true" class="btn btn-secondary">Cập nhật thông tin</button>
            </div>
        </div>

        <!-- Card Đổi mật khẩu (Read-only) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div><h4 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">Bảo mật</h4><p class="text-sm text-gray-500 dark:text-gray-400">Thay đổi mật khẩu đăng nhập của bạn.</p></div>
                <button @click="isPasswordModalOpen = true" class="btn btn-secondary">Đổi mật khẩu</button>
            </div>
        </div>
    </div>
    
    <!-- ===== MODAL: Chỉnh sửa thông tin cá nhân ===== -->
    <div x-show="isProfileModalOpen" class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto z-99999" style="display: none;">
        <div @click="isProfileModalOpen = false" class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-sm"></div>
        <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Settings&action=updateProfile" @click.outside="isProfileModalOpen = false" class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-11">
             <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
             <button @click.prevent="isProfileModalOpen = false" class="transition-color absolute right-5 top-5 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-800"><svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z" fill=""/></svg></button>
            <div class="px-2 pr-14"><h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Chỉnh sửa thông tin</h4><p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Cập nhật thông tin chi tiết của bạn.</p></div>
            <div class="custom-scrollbar h-auto max-h-[450px] overflow-y-auto px-2">
                <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Họ và Tên</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($data['user']['full_name'] ?? ''); ?>" class="form-input"/></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Tên đăng nhập</label><input type="text" name="username" value="<?php echo htmlspecialchars($data['user']['username'] ?? ''); ?>" class="form-input"/></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium">Địa chỉ Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" class="form-input"/></div>
                    <div class="sm:col-span-1"><label class="mb-1.5 block text-sm font-medium">Số điện thoại 1</label><input type="tel" name="phone_1" value="<?php echo htmlspecialchars($data['user']['phone_1'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"/></div>
                    <div class="sm:col-span-1"><label class="mb-1.5 block text-sm font-medium">Số điện thoại 2</label><input type="tel" name="phone_2" value="<?php echo htmlspecialchars($data['user']['phone_2'] ?? ''); ?>" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"/></div>
                </div>
            </div>
            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
                <button @click.prevent="isProfileModalOpen = false" type="button" class="btn btn-secondary">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>

    <!-- ===== MODAL: Đổi mật khẩu ===== -->
    <div x-show="isPasswordModalOpen" class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto z-99999" style="display: none;">
        <div @click="isPasswordModalOpen = false" class="fixed inset-0 bg-gray-400/50 backdrop-blur-sm"></div>
        <form method="post" action="/<?= APP_VERSION ?>/index.php?module=Settings&action=changePassword" @click.outside="isPasswordModalOpen = false" class="no-scrollbar relative overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-11">
             <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
             <button @click.prevent="isPasswordModalOpen = false" class="transition-color absolute right-5 top-5 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-800"><svg class="fill-current" width="24" height="24" viewBox="0 0 24 24"><path d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z" fill=""/></svg></button>
            <div class="px-2 pr-14"><h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Đổi mật khẩu</h4><p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Nhập mật khẩu mới cho tài khoản của bạn.</p></div>
            <div class="px-2 space-y-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                <div>
                    <label for="current_password_modal" class="mb-1.5 block text-sm font-medium">Mật khẩu hiện tại</label>
                    <div class="relative">
                        <input :type="showCurrent ? 'text' : 'password'" id="current_password_modal" name="current_password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"/>
                        <span @click="showCurrent = !showCurrent" class="absolute z-10 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500">
                           <svg x-show="!showCurrent" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.41550 9.4593C2.36150 9.61687 2.36150 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.61690 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="#98A2B3"/></svg>
                                <svg x-show="showCurrent" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.28420 3.87026 3.28420 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.41550 9.45936C2.36150 9.61694 2.36150 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.2550 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.7160 16.1300 16.7160 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.54610 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.15560 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="#98A2B3"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="new_password_modal" class="mb-1.5 block text-sm font-medium">Mật khẩu mới</label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" id="new_password_modal" name="new_password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"/>
                        <span @click="showNew = !showNew" class="absolute z-10 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500">
                           <svg x-show="!showNew" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.41550 9.4593C2.36150 9.61687 2.36150 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.61690 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="#98A2B3"/></svg>
                                <svg x-show="showNew" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.28420 3.87026 3.28420 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.41550 9.45936C2.36150 9.61694 2.36150 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.2550 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.7160 16.1300 16.7160 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.54610 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.15560 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="#98A2B3"/></svg>
                        </span>
                    </div>
                </div>
                <div>
                    <label for="confirm_password_modal" class="mb-1.5 block text-sm font-medium">Xác nhận mật khẩu mới</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" id="confirm_password_modal" name="confirm_password" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"/>
                        <span @click="showConfirm = !showConfirm" class="absolute z-10 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500">
                           <svg x-show="!showConfirm" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.41550 9.4593C2.36150 9.61687 2.36150 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.61690 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="#98A2B3"/></svg>
                                <svg x-show="showConfirm" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.28420 3.87026 3.28420 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.41550 9.45936C2.36150 9.61694 2.36150 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.2550 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.7160 16.1300 16.7160 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.54610 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.15560 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="#98A2B3"/></svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 px-2 mt-6 lg:justify-end">
                <button @click.prevent="isPasswordModalOpen = false" type="button" class="btn btn-secondary">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>