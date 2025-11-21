<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Token Refresh') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            opacity: 0.95;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            padding: 30px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 2px solid #e0e0e0;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #3a7bd5;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card.refreshed {
            border-left-color: #10b981;
        }

        .stat-card.failed {
            border-left-color: #ef4444;
        }

        .stat-card.skipped {
            border-left-color: #f59e0b;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 40px;
            font-weight: bold;
            color: #333;
            line-height: 1;
        }

        .stat-card .label {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }

        .logs {
            padding: 30px;
        }

        .logs h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .log-entry {
            padding: 14px 18px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            border-left: 4px solid #e0e0e0;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }

        .log-entry:hover {
            background: #f0f0f0;
            transform: translateX(4px);
        }

        .log-entry.info {
            border-left-color: #3b82f6;
            background: linear-gradient(to right, #eff6ff 0%, #f8f9fa 100%);
        }

        .log-entry.error {
            border-left-color: #ef4444;
            background: linear-gradient(to right, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            font-weight: 500;
        }

        .log-entry.warning {
            border-left-color: #f59e0b;
            background: linear-gradient(to right, #fffbeb 0%, #fef3c7 100%);
            color: #92400e;
        }

        .log-time {
            color: #888;
            font-size: 11px;
            margin-right: 12px;
            font-weight: bold;
        }

        .log-message {
            line-height: 1.5;
        }

        .no-logs {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .no-logs svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .footer {
            padding: 25px 30px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .summary-box h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .summary-box p {
            margin: 5px 0;
            font-size: 14px;
            opacity: 0.95;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 <?= htmlspecialchars($page_title ?? 'Token Refresh') ?></h1>
            <p>Hệ thống tự động làm mới Access Token & Refresh Token</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Tổng Shop</h3>
                <div class="number"><?= $stats['total_shops'] ?? 0 ?></div>
                <div class="label">Đã quét</div>
            </div>
            <div class="stat-card refreshed">
                <h3>Làm mới thành công</h3>
                <div class="number"><?= $stats['refreshed'] ?? 0 ?></div>
                <div class="label">Token đã cập nhật</div>
            </div>
            <div class="stat-card failed">
                <h3>Thất bại</h3>
                <div class="number"><?= $stats['failed'] ?? 0 ?></div>
                <div class="label">Cần xử lý thủ công</div>
            </div>
            <div class="stat-card skipped">
                <h3>Bỏ qua</h3>
                <div class="number"><?= $stats['skipped'] ?? 0 ?></div>
                <div class="label">Không cần làm mới</div>
            </div>
        </div>

        <div class="logs">
            <?php if (!empty($stats) && $stats['total_shops'] > 0): ?>
                <div class="summary-box">
                    <h3>📊 Tóm tắt</h3>
                    <p>
                        ✓ Thành công: <?= $stats['refreshed'] ?>/<?= $stats['total_shops'] ?> 
                        (<?= $stats['total_shops'] > 0 ? round(($stats['refreshed'] / $stats['total_shops']) * 100, 1) : 0 ?>%)
                    </p>
                    <p>✗ Thất bại: <?= $stats['failed'] ?> | ⊘ Bỏ qua: <?= $stats['skipped'] ?></p>
                </div>
            <?php endif; ?>

            <h2>📋 Chi tiết quá trình</h2>
            
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry <?= htmlspecialchars($log['type']) ?>">
                        <span class="log-time">[<?= htmlspecialchars($log['time']) ?>]</span>
                        <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                        
                        <?php if ($log['type'] === 'error'): ?>
                            <span class="badge error">ERROR</span>
                        <?php elseif ($log['type'] === 'warning'): ?>
                            <span class="badge warning">WARNING</span>
                        <?php elseif (strpos($log['message'], '✓') === 0): ?>
                            <span class="badge success">SUCCESS</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-logs">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p>Không có log nào được ghi nhận.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>Token Refresh System v1.0</strong> | Thời gian thực thi: <?= date('Y-m-d H:i:s') ?></p>
            <p style="margin-top: 5px; font-size: 11px; opacity: 0.7;">
                💡 Cron job nên chạy mỗi ngày vào lúc 3h sáng để đảm bảo token luôn còn hiệu lực
            </p>
        </div>
    </div>
</body>
</html>
