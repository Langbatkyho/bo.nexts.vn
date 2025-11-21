<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .header .breadcrumb {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #4b5563;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .pagination {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e5e7eb;
        }

        .pagination-info {
            font-size: 14px;
            color: #666;
        }

        .pagination-links a {
            padding: 6px 12px;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }

        .pagination-links a:hover {
            background: #f3f4f6;
        }

        .pagination-links a.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 15px;
        }

        .actions {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Quản lý Đơn hàng</h1>
            <div class="breadcrumb">Trang chủ / Đơn hàng</div>
        </div>

        <?php if (isset($flash_message)): ?>
            <div class="alert alert-<?= $flash_message['type'] ?>">
                <?= htmlspecialchars($flash_message['text']) ?>
            </div>
        <?php endif; ?>

        <!-- Thống kê -->
        <?php if (!empty($stats)): ?>
        <div class="stats-grid">
            <?php 
            $totalOrders = 0;
            foreach ($stats as $stat) {
                $totalOrders += $stat['count'];
            }
            ?>
            <div class="stat-card">
                <h3>Tổng đơn hàng</h3>
                <div class="number"><?= number_format($totalOrders) ?></div>
            </div>
            <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <h3><?= htmlspecialchars($stat['ref_current_status']) ?></h3>
                <div class="number"><?= number_format($stat['count']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bộ lọc -->
        <div class="filters">
            <h3>🔍 Bộ lọc</h3>
            <form method="GET" action="">
                <input type="hidden" name="module" value="Orders">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="TIKTOK" <?= ($filters['platform'] ?? '') === 'TIKTOK' ? 'selected' : '' ?>>TikTok</option>
                            <option value="SHOPEE" <?= ($filters['platform'] ?? '') === 'SHOPEE' ? 'selected' : '' ?>>Shopee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Trạng thái</label>
                        <input type="text" name="status" class="form-control" value="<?= htmlspecialchars($filters['ref_current_status'] ?? '') ?>" placeholder="VD: COMPLETED">
                    </div>

                    <div class="form-group">
                        <label>Shop</label>
                        <select name="shop_id" class="form-control">
                            <option value="">Tất cả</option>
                            <?php foreach ($shops as $shop): ?>
                            <option value="<?= htmlspecialchars($shop['platform_shop_id']) ?>" <?= ($filters['ref_shop_id'] ?? '') === $shop['platform_shop_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($shop['shop_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tìm kiếm</label>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Order ID, Order Number...">
                    </div>

                    <div class="form-group">
                        <label>Từ ngày</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Đến ngày</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
                    <a href="?module=Orders" class="btn btn-secondary">↻ Đặt lại</a>
                    <a href="?module=Orders&action=export<?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" class="btn btn-success">📥 Xuất Excel</a>
                </div>
            </form>
        </div>

        <!-- Bảng đơn hàng -->
        <div class="table-container">
            <div class="table-header">
                <h3>Danh sách đơn hàng</h3>
                <span><?= number_format($pagination['total_items']) ?> đơn hàng</span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p>Không tìm thấy đơn hàng nào</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ref Order ID</th>
                            <th>Platform</th>
                            <th>Shop</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Cập nhật lần cuối</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($order['ref_order_id']) ?></strong>
                                <?php if ($order['ref_order_number']): ?>
                                    <br><small><?= htmlspecialchars($order['ref_order_number']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($order['platform']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($order['shop_name'] ?? $order['ref_shop_id'] ?? '-') ?></td>
                            <td>
                                <?php
                                $statusClass = 'badge-secondary';
                                $status = strtoupper($order['ref_current_status']);
                                if (strpos($status, 'COMPLETED') !== false || strpos($status, 'DELIVERED') !== false) {
                                    $statusClass = 'badge-success';
                                } elseif (strpos($status, 'PENDING') !== false || strpos($status, 'AWAITING') !== false) {
                                    $statusClass = 'badge-warning';
                                } elseif (strpos($status, 'CANCELLED') !== false || strpos($status, 'FAILED') !== false) {
                                    $statusClass = 'badge-danger';
                                } elseif (strpos($status, 'PROCESSING') !== false || strpos($status, 'SHIPPED') !== false) {
                                    $statusClass = 'badge-info';
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($order['ref_current_status']) ?></span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($order['last_updated_at'])) ?>
                                <br><small>by <?= htmlspecialchars($order['last_updated_by']) ?></small>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="?module=Orders&action=view&id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">👁️ Xem</a>
                                    <?php if (isset($auth) && method_exists($auth, 'can') && $auth->can(\BO_System\Core\Permissions::ORDERS_DELETE)): ?>
                                    <a href="?module=Orders&action=delete&id=<?= $order['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">🗑️</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Phân trang -->
                <div class="pagination">
                    <div class="pagination-info">
                        Hiển thị <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> 
                        đến <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total_items']) ?> 
                        trong tổng số <?= number_format($pagination['total_items']) ?> đơn hàng
                    </div>
                    <div class="pagination-links">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="?module=Orders&page=<?= $pagination['current_page'] - 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>">‹ Trước</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <a href="?module=Orders&page=<?= $i ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" 
                               class="<?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="?module=Orders&page=<?= $pagination['current_page'] + 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>">Sau ›</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
