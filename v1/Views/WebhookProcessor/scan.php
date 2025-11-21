<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Webhook Processor') ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .stat-card.success {
            border-left-color: #10b981;
        }

        .stat-card.failed {
            border-left-color: #ef4444;
        }

        .stat-card.skipped {
            border-left-color: #f59e0b;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .logs {
            padding: 30px;
        }

        .logs h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }

        .log-entry {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border-left: 3px solid #e0e0e0;
            background: #f8f9fa;
        }

        .log-entry.info {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }

        .log-entry.error {
            border-left-color: #ef4444;
            background: #fef2f2;
            color: #991b1b;
        }

        .log-entry.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
            color: #92400e;
        }

        .log-time {
            color: #666;
            font-size: 11px;
            margin-right: 10px;
        }

        .no-logs {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }

        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 <?= htmlspecialchars($page_title ?? 'Webhook Processor') ?></h1>
            <p>Hệ thống xử lý webhook tự động từ TikTok Shop</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Tổng xử lý</h3>
                <div class="number"><?= $stats['total_processed'] ?? 0 ?></div>
            </div>
            <div class="stat-card success">
                <h3>Thành công</h3>
                <div class="number"><?= $stats['success'] ?? 0 ?></div>
            </div>
            <div class="stat-card failed">
                <h3>Thất bại</h3>
                <div class="number"><?= $stats['failed'] ?? 0 ?></div>
            </div>
            <div class="stat-card skipped">
                <h3>Bỏ qua</h3>
                <div class="number"><?= $stats['skipped'] ?? 0 ?></div>
            </div>
        </div>

        <div class="logs">
            <h2>📋 Chi tiết xử lý</h2>
            
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry <?= htmlspecialchars($log['type']) ?>">
                        <span class="log-time">[<?= htmlspecialchars($log['time']) ?>]</span>
                        <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                        
                        <?php if ($log['type'] === 'error'): ?>
                            <span class="badge error">ERROR</span>
                        <?php elseif (strpos($log['message'], '✓') === 0): ?>
                            <span class="badge success">SUCCESS</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-logs">
                    <p>Không có log nào được ghi nhận.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Webhook Processor v1.0 | Thời gian xử lý: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
