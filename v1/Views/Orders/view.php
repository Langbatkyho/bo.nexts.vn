<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn hàng #<?= $order['id'] ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .header-left .breadcrumb {
            color: #666;
            font-size: 14px;
        }

        .header-left .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
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

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .card-header h2 {
            font-size: 18px;
            color: #333;
        }

        .card-body {
            padding: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            color: #111827;
            font-size: 14px;
            text-align: right;
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

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 8px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3b82f6;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #3b82f6;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 20px;
            width: 2px;
            height: calc(100% - 8px);
            background: #e5e7eb;
        }

        .timeline-item:last-child::after {
            display: none;
        }

        .timeline-status {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .timeline-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .update-status-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }

        .shop-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .shop-info h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .shop-info p {
            margin: 5px 0;
            font-size: 14px;
            opacity: 0.95;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>📦 Chi tiết Đơn hàng #<?= $order['id'] ?></h1>
                <div class="breadcrumb">
                    <a href="?module=Orders">Đơn hàng</a> / Chi tiết
                </div>
            </div>
            <div class="header-right">
                <a href="?module=Orders" class="btn btn-secondary">← Quay lại</a>
            </div>
        </div>

        <?php if (isset($flash_message)): ?>
            <div class="alert alert-<?= $flash_message['type'] ?>">
                <?= htmlspecialchars($flash_message['text']) ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Cột trái: Thông tin đơn hàng -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>Thông tin đơn hàng</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">ID đơn hàng</span>
                            <span class="info-value"><strong><?= $order['id'] ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ref Order ID</span>
                            <span class="info-value"><strong><?= htmlspecialchars($order['ref_order_id']) ?></strong></span>
                        </div>
                        <?php if ($order['ref_order_number']): ?>
                        <div class="info-row">
                            <span class="info-label">Order Number</span>
                            <span class="info-value"><?= htmlspecialchars($order['ref_order_number']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Platform</span>
                            <span class="info-value">
                                <span class="badge badge-info"><?= htmlspecialchars($order['platform']) ?></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Trạng thái hiện tại</span>
                            <span class="info-value">
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
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Shop ID</span>
                            <span class="info-value"><?= htmlspecialchars($order['ref_shop_id'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ngày tạo</span>
                            <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cập nhật lần cuối</span>
                            <span class="info-value">
                                <?= date('d/m/Y H:i:s', strtotime($order['last_updated_at'])) ?>
                                <br>
                                <small>bởi <?= htmlspecialchars($order['last_updated_by']) ?></small>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử trạng thái -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2>Lịch sử thay đổi trạng thái</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($statusHistory)): ?>
                            <p style="color: #9ca3af; text-align: center; padding: 20px;">Chưa có lịch sử thay đổi trạng thái</p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($statusHistory as $history): ?>
                                <div class="timeline-item">
                                    <div class="timeline-status">
                                        <?php if ($history['old_status']): ?>
                                            <?= htmlspecialchars($history['old_status']) ?> → 
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($history['new_status']) ?></strong>
                                    </div>
                                    <div class="timeline-meta">
                                        <?= date('d/m/Y H:i:s', strtotime($history['updated_at'])) ?> 
                                        • Bởi <?= htmlspecialchars($history['updated_by']) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Thông tin shop và actions -->
            <div>
                <!-- Thông tin Shop -->
                <?php if ($shop): ?>
                <div class="shop-info">
                    <h3>🏪 Thông tin Shop</h3>
                    <p><strong><?= htmlspecialchars($shop['shop_name']) ?></strong></p>
                    <p>Platform Shop ID: <?= htmlspecialchars($shop['platform_shop_id']) ?></p>
                    <?php if ($shop['platform_shop_code']): ?>
                        <p>Shop Code: <?= htmlspecialchars($shop['platform_shop_code']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Form cập nhật trạng thái -->
                <?php if (isset($auth) && method_exists($auth, 'can') && $auth->can(\BO_System\Core\Permissions::ORDERS_EDIT)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Cập nhật trạng thái</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="?module=Orders&action=updateStatus">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            
                            <div class="form-group">
                                <label>Trạng thái mới</label>
                                <input type="text" name="new_status" class="form-control" 
                                       placeholder="VD: COMPLETED, SHIPPED..." required>
                                <small style="color: #6b7280; font-size: 12px; display: block; margin-top: 5px;">
                                    Nhập trạng thái mới phù hợp với platform
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                ✓ Cập nhật trạng thái
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2>Thao tác</h2>
                    </div>
                    <div class="card-body">
                        <a href="?module=Orders" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                            ← Quay lại danh sách
                        </a>
                        
                        <?php if (isset($auth) && method_exists($auth, 'can') && $auth->can(\BO_System\Core\Permissions::ORDERS_DELETE)): ?>
                        <a href="?module=Orders&action=delete&id=<?= $order['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                           class="btn btn-danger" 
                           style="width: 100%;"
                           onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">
                            🗑️ Xóa đơn hàng
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2>📊 Thống kê nhanh</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Số lần thay đổi trạng thái</span>
                            <span class="info-value"><strong><?= count($statusHistory) ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Thời gian tồn tại</span>
                            <span class="info-value">
                                <?php
                                $created = new DateTime($order['created_at']);
                                $now = new DateTime();
                                $diff = $created->diff($now);
                                
                                if ($diff->days > 0) {
                                    echo $diff->days . ' ngày';
                                } else {
                                    echo $diff->h . ' giờ ' . $diff->i . ' phút';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
