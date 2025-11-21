<?php 
// Hàm đệ quy để render cây menu trong khu vực quản trị
function renderAdminMenuItem($item) {
    $hasChildren = !empty($item['children']);
    ?>
    <li data-id="<?php echo $item['id']; ?>" class="mb-3 group block rounded-lg bg-gray-50 dark:bg-gray-800 border dark:border-gray-700">
        
        <!-- ===== THAY ĐỔI CẤU TRÚC Ở ĐÂY ===== -->
        <div class="flex items-center justify-between p-3">
            
            <!-- Group 1: Phần bên trái (Icon + Thông tin) -->
            <div class="flex items-center">
                <div class="handle cursor-move text-gray-400 hover:text-gray-600 mr-3">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M10 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm8 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm-8-6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm8 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm-8-6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm8 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                </div>
                
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($item['title']); ?></p>
                    <p class="text-xs text-gray-500 font-mono">
                        Module: <?php echo htmlspecialchars($item['module']); ?> | 
                        Quyền: <?php echo htmlspecialchars($item['required_permission_slug'] ?? 'Công khai'); ?>
                    </p>
                </div>
            </div>

            <!-- Group 2: Phần bên phải (Các nút) -->
            <div class="flex items-center gap-2">
                <button @click='openModal(<?php echo json_encode($item, JSON_HEX_APOS); ?>)' class="btn-edit-on-form text-sm">Sửa</button>
                <a href="/<?= APP_VERSION ?>/index.php?module=Menus&action=destroy&id=<?php echo $item['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa mục menu này?')" class="btn-edit-on-form text-sm">Xóa</a>
            </div>

        </div>
        <!-- ===== KẾT THÚC THAY ĐỔI ===== -->
        
        <?php if ($hasChildren): ?>
            <ul class="pl-4 pr-3 pb-3 space-y-2">
                <?php foreach ($item['children'] as $child) { renderAdminMenuItem($child); } ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}
?>

<div x-data="menuManager(<?php echo htmlspecialchars(json_encode($data['allItems']), ENT_QUOTES, 'UTF-8'); ?>)">
    <!-- Breadcrumb & Buttons -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Quản lý Menu Sidebar</h2>
        <div class="flex items-center gap-3">
            <button @click="openModal()" class="btn btn-secondary">Thêm menu mới</button>
            <button @click="saveOrder" class="btn btn-primary" :disabled="isSaving" x-text="isSaving ? 'Đang lưu...' : 'Lưu thứ tự Menu'"></button>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($data['flash_message'])): ?>
        <div class="mb-6 p-4 rounded-lg text-sm text-white shadow-md <?php echo $data['flash_message']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>" role="alert">
            <?php echo htmlspecialchars($data['flash_message']['text']); ?>
        </div>
    <?php endif; ?>
    
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Kéo và thả các mục menu để sắp xếp lại. Nhấn "Lưu thứ tự Menu" sau khi hoàn tất.</p>

    <!-- Cây Menu -->
    <div class="rounded-2xl border bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <ul id="menu-list" class="space-y-2">
            <?php 
                if (!empty($data['menuTree'])) {
                    foreach($data['menuTree'] as $item) { renderAdminMenuItem($item); }
                } else {
                    echo '<p class="text-center text-gray-500 py-4">Chưa có mục menu nào.</p>';
                }
            ?>
        </ul>
    </div>
    
    <!-- Modal Thêm/Sửa -->
    <div x-show="isModalOpen" class="fixed inset-0 flex items-center justify-center p-5 z-99999" style="display: none;">
        <div @click="isModalOpen = false" class="fixed inset-0 bg-gray-400/50 backdrop-blur-sm"></div>
        <form method="post" :action="formAction" @submit.prevent="submitForm" @click.outside="isModalOpen = false" class="relative w-full max-w-2xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-8">
            <input type="hidden" name="id" x-model="formData.id">
            <input type="hidden" name="display_order" x-model="formData.display_order">
            <h3 x-text="isEditing ? 'Chỉnh sửa Mục Menu' : 'Tạo menu mới'" class="mb-6 text-2xl font-semibold"></h3>
            <div class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tiêu đề</label>
                        <input type="text" name="title" x-model="formData.title" class="form-input w-full" required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Module</label>
                        <input type="text" name="module" x-model="formData.module" class="form-input w-full" required>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Mục cha</label>
                    <select name="parent_id" x-model="formData.parent_id" class="form-select w-full">
                        <option value="">-- Là mục menu gốc --</option>
                        <?php foreach($data['allItems'] as $item): ?>
                            <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Quyền yêu cầu</label>
                    <select name="required_permission_slug" x-model="formData.required_permission_slug" class="form-select w-full">
                        <option value="">-- Mọi người đều thấy --</option>
                        <?php foreach($data['allPermissions'] as $permission): ?>
                            <option value="<?php echo $permission['slug']; ?>"><?php echo htmlspecialchars($permission['description']); ?> (<?php echo htmlspecialchars($permission['slug']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Mã Icon SVG</label>
                    <textarea name="icon_svg" x-model="formData.icon_svg" rows="4" class="form-input w-full font-mono text-xs"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="isModalOpen = false" class="btn btn-secondary">Hủy</button>
                <button type="submit" x-ref="submitBtn" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Thư viện và Logic JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function menuManager(allItems) {
    return {
        isModalOpen: false,
        isEditing: false,
        isSaving: false,
        formAction: '',
        formData: { id: '', parent_id: '', title: '', module: '', icon_svg: '', required_permission_slug: '' },
        
        openModal(item = null) {
            this.isEditing = !!item;
            if (this.isEditing) {
                this.formAction = `/<?= APP_VERSION ?>/index.php?module=Menus&action=update`;
                this.formData = { ...item };
            } else {
                this.formAction = `/<?= APP_VERSION ?>/index.php?module=Menus&action=store`;
                this.formData = { id: '', parent_id: '', title: '', module: '', icon_svg: '', required_permission_slug: '', display_order: 0 };
            }
            this.isModalOpen = true;
        },
        
        submitForm(e) {
            const form = e.target;
            const formData = new FormData(form);
            const csrfToken = '<?php echo htmlspecialchars($data['csrf_token'] ?? '', ENT_QUOTES); ?>';
            formData.append('csrf_token', csrfToken);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            }).then(response => {
                // Redirect sau khi submit để trang tải lại và hiển thị flash message
                window.location.href = '/<?= APP_VERSION ?>/index.php?module=Menus';
            }).catch(error => {
                Swal.fire('Lỗi!', 'Không thể gửi form.', 'error');
            });
        },

        initSortable() {
            document.querySelectorAll('#menu-list, #menu-list ul').forEach(el => {
                new Sortable(el, {
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    handle: '.handle'
                });
            });
        },

        serialize(parent) {
            const children = Array.from(parent.children).filter(child => child.tagName === 'LI');
            return children.map(child => {
                const id = child.dataset.id;
                const nestedList = child.querySelector('ul');
                const result = { id };
                if (nestedList && nestedList.children.length > 0) {
                    result.children = this.serialize(nestedList);
                }
                return result;
            });
        },

        async saveOrder() {
            this.isSaving = true;
            const structure = this.serialize(document.getElementById('menu-list'));
            
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($data['csrf_token'] ?? '', ENT_QUOTES); ?>');
            formData.append('structure', JSON.stringify(structure));
            
            try {
                const response = await fetch('/<?= APP_VERSION ?>/index.php?module=Menus&action=saveOrder', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if(result.success) {
                    Swal.fire('Thành công!', result.message, 'success');
                } else {
                    Swal.fire('Lỗi!', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Lỗi!', 'Không thể kết nối đến máy chủ.', 'error');
            } finally {
                this.isSaving = false;
            }
        },
        
        init() {
            this.initSortable();
        }
    }
}
</script>