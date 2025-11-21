<?php
$shops = $data['shops'] ?? [];
?>
<div>
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Quản lý Shop đã đồng bộ</h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white" href="/<?= APP_VERSION ?>/index.php">Trang chủ <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16"><path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                <li class="text-sm text-gray-800 dark:text-white/90">Shops</li>
            </ol>
        </nav>
    </div>

    <!-- Thông báo Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>

    <!-- Nút thêm shop -->
    <div class="mb-6">
        <button id="btnAddShop" class="btn btn-primary">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm shop
        </button>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Nền tảng</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Tên Shop</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Shop ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Shop Code</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Cập nhật</p></th>
                        <th class="px-5 py-3 text-right sm:px-6"><p class="font-medium text-gray-500 text-xs dark:text-gray-400 uppercase">Hành động</p></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php
                    use BO_System\Services\AuthService;
                    $auth = AuthService::getInstance();

                    if (count($shops) > 0): 
                        foreach($shops as $s): 
                    ?>
                    <tr>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="font-medium text-gray-800 text-sm dark:text-white/90"><?= htmlspecialchars($s['id']) ?></p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <?php 
                                $platform = strtoupper($s['platform']);
                                $platform_colors = [
                                    'TIKTOK' => 'bg-pink-100 text-pink-800',
                                    'SHOPEE' => 'bg-orange-100 text-orange-800',
                                    'LAZADA' => 'bg-blue-100 text-blue-800',
                                ];
                                $platform_class = $platform_colors[$platform] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $platform_class; ?>">
                                <?= htmlspecialchars($platform) ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="font-medium text-gray-800 text-sm dark:text-white/90"><?= htmlspecialchars($s['shop_name']) ?></p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="text-gray-500 text-sm"><?= htmlspecialchars($s['platform_shop_id']) ?></p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <p class="text-gray-500 text-sm"><?= htmlspecialchars($s['platform_shop_code']) ?></p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <?php $date = $s['updated_at'] ?? $s['created_at']; ?>
                            <p class="text-gray-500 text-sm"><?php echo $date ? date('d/m/Y', strtotime($date)) : 'N/A'; ?></p>
                            <p class="text-gray-400 text-xs"><?php echo $date ? date('H:i', strtotime($date)) : ''; ?></p>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <div class="flex items-center justify-end space-x-2">
                                <?php if ($auth->can('shops.edit')): ?>
                                    <a href="/<?= APP_VERSION ?>/index.php?module=Shops&action=edit&id=<?php echo $s['id']; ?>" class="inline-flex items-center px-3 py-1.5 rounded-md border border-blue-500 bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-medium transition">Sửa</a>
                                <?php endif; ?>
                
                                <?php if ($auth->can('shops.delete')): ?>
                                    <button type="button" class="inline-flex items-center px-3 py-1.5 rounded-md border border-red-500 bg-red-50 text-red-700 hover:bg-red-100 text-xs font-medium transition btn-delete-shop" data-id="<?php echo $s['id']; ?>">Xóa</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 px-5 text-gray-500 dark:text-gray-400">
                            Chưa có shop nào được đồng bộ.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phân trang -->
    <?php
    $current_page = $data['current_page'] ?? 1;
    $total_pages = $data['total_pages'] ?? 1;
    $total_rows = $data['total_rows'] ?? count($shops);
    if ($total_rows > 0 && $total_pages > 1):
    ?>
    <div class="mt-6 flex flex-wrap justify-between items-center gap-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">Trang <?php echo $current_page; ?> / <?php echo $total_pages; ?> (Tổng <?php echo $total_rows; ?> shop)</p>
        <nav class="flex items-center gap-2">
            <?php
            $query_params = $_GET;
            unset($query_params['page']);
            $base_url = '/' . APP_VERSION . '/index.php?' . http_build_query($query_params);

            // Nút Đầu & Trước
            if ($current_page > 1) {
                echo '<a href="' . $base_url . '&page=1" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&laquo;</a>';
                echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&lsaquo;</a>';
            } else {
                echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&laquo;</span>';
                echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&lsaquo;</span>';
            }

            // Các trang số
            $range = 2;
            for ($i = $current_page - $range; $i <= $current_page + $range; $i++) {
                if ($i > 0 && $i <= $total_pages) {
                    if ($i == $current_page) {
                        echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border brand-border brand-bg-light brand-text text-sm font-semibold">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $base_url . '&page=' . $i . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">' . $i . '</a>';
                    }
                }
            }
            
            // Nút Sau & Cuối
            if ($current_page < $total_pages) {
                echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&rsaquo;</a>';
                echo '<a href="' . $base_url . '&page=' . $total_pages . '" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 text-sm font-medium">&raquo;</a>';
            } else {
                echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&rsaquo;</span>';
                echo '<span class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-200 bg-gray-50 text-gray-400 text-sm font-medium">&raquo;</span>';
            }
            ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal chọn nền tảng -->
<div id="platformModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                            Chọn nền tảng để kết nối
                        </h3>
                        <div class="mt-4 space-y-3">
                            <button id="connectTiktok" class="w-full inline-flex justify-center items-center px-4 py-3 border border-pink-500 rounded-md shadow-sm text-sm font-medium text-pink-700 bg-pink-50 hover:bg-pink-100 focus:outline-none transition">
                                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                </svg>
                                TikTok Shop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button id="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('platformModal');
    const btnAddShop = document.getElementById('btnAddShop');
    const closeModal = document.getElementById('closeModal');
    const connectTiktok = document.getElementById('connectTiktok');

    btnAddShop.addEventListener('click', function(){
        modal.classList.remove('hidden');
    });

    closeModal.addEventListener('click', function(){
        modal.classList.add('hidden');
    });

    connectTiktok.addEventListener('click', function(){
        window.location.href = '/v1/auth/connect_platform.php?platform=TIKTOK';
    });

    // Đóng modal khi click bên ngoài
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // Xử lý nút xóa
    document.querySelectorAll('.btn-delete-shop').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const shopId = this.dataset.id;
            
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc chắn muốn xóa shop này không?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Có, xóa nó!',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `/<?php echo APP_VERSION; ?>/index.php?module=Shops&action=destroy&id=${shopId}`;
                }
            });
        });
    });
});
</script>
