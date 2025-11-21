<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($data['system_name'] ?? 'Hệ thống Quản trị'); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($data['favicon_url'] ?? ''); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/<?= APP_VERSION ?>/assets/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body
    x-data="{ 'loaded': true, 'darkMode': false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode'));
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}"
>
<!-- Preloader -->
<div x-show="loaded" x-init="window.addEventListener('DOMContentLoaded', () => {setTimeout(() => loaded = false, 500)})" class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black">
    <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
</div>

<!-- Page Wrapper -->
<div class="flex h-screen w-full">
    <div class="flex w-full flex-wrap">
        <!-- ===== Form Area Start ===== -->
        <div class="w-full lg:w-1/2 flex items-center justify-center">
            <div class="w-full max-w-md p-4 sm:p-12 xl:p-16">
                <!-- Heading -->
                <div class="mb-5 sm:mb-8 text-center lg:text-left">
                    <h1 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white/90 sm:text-3xl">
                        <?php echo htmlspecialchars($data['system_name'] ?? 'Hệ thống Quản trị'); ?>
                    </h1>
                </div>

                <!-- Notification -->
                <?php if (!empty($data['error_message'])): ?>
                <div class="mb-4 p-4 rounded-md text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
                    <?php echo htmlspecialchars($data['error_message']); ?>
                </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="/<?= APP_VERSION ?>/login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrf_token'] ?? ''); ?>">
                    <div class="space-y-5">
                        <!-- Username -->
                        <div>
                            <label for="username" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tên đăng nhập</label>
                            <input type="text" id="username" name="username" required placeholder="Nhập tên đăng nhập của bạn" value="<?php echo htmlspecialchars($data['old_input']['username'] ?? ''); ?>"
                                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-500"
                                   <?php if($data['is_locked_out'] ?? false) echo 'disabled'; ?>/>
                                   
                            <?php if (isset($data['errors']['username'])): ?>
                                <p class="text-sm text-red-600 mt-1"><?php echo htmlspecialchars($data['errors']['username'][0]); ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Password -->
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Mật khẩu</label>
                            <div x-data="{ showPassword: false }" class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required placeholder="Nhập mật khẩu của bạn"
                                       class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-500"
                                       <?php if($data['is_locked_out'] ?? false) echo 'disabled'; ?>/>
                                <span @click="showPassword = !showPassword" class="absolute z-10 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500 dark:text-gray-400">
                                    <svg x-show="!showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.41550 9.4593C2.36150 9.61687 2.36150 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.61690 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" fill="#98A2B3"/></svg>
                                    <svg x-show="showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.28420 3.87026 3.28420 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.41550 9.45936C2.36150 9.61694 2.36150 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.2550 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.7160 16.1300 16.7160 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.54610 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.15560 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" fill="#98A2B3"/></svg>
                                </span>
                            </div>
                            <?php if (isset($data['errors']['password'])): ?>
                                <p class="text-sm text-red-600 mt-1"><?php echo htmlspecialchars($data['errors']['password'][0]); ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Button -->
                        <div>
                            <button type="submit" class="btn btn-primary w-full"
                                    <?php if($data['is_locked_out'] ?? false) echo 'disabled'; ?>>
                                Đăng nhập
                            </button>
                        </div>
                         <!-- Liên hệ IT -->
                        <div class="mt-5 text-center">
                            <p class="text-sm font-normal text-gray-700 dark:text-gray-400">
                                Cần hỗ trợ?
                                <a href="mailto:<?php echo htmlspecialchars($data['email_admin'] ?? ''); ?>" class="font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">Liên hệ bộ phận IT</a>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- ===== Image Area Start ===== -->
        <div class="relative hidden h-screen w-full brand-bg dark:bg-white/5 lg:block lg:w-1/2">
             <!-- Background Grids -->
            <div class="absolute right-0 top-0 w-full max-w-[250px] xl:max-w-[450px]">
                <img src="/<?= APP_VERSION ?>/uploads/2025/11/grid-01.svg" alt="grid" />
            </div>
            <div class="absolute bottom-0 left-0 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
                <img src="/<?= APP_VERSION ?>/uploads/2025/11/grid-01.svg" alt="grid" />
            </div>

            <!-- Content -->
            <div class="relative z-1 flex h-full items-center justify-center">
                <div class="max-w-xs text-center">
                    <a href="/<?= APP_VERSION ?>/index.php" class="mb-4 inline-block">
                        <img src="<?php echo htmlspecialchars($data['logo_white_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($data['brand_name'] ?? ''); ?>" class="w-64" />
                    </a>
                    <p class="text-white dark:text-white/60">
                        <?php echo htmlspecialchars($data['system_name'] ?? 'Hệ thống Quản trị'); ?>
                    </p>
                </div>
            </div>
        </div>
        <!-- ===== Image Area End ===== -->
    </div>
</div>
</body>
</html>