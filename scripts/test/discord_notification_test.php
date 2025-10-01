<?php
/**
 * Discord風格通知系統測試腳本
 * 用於測試通知功能是否正常工作
 */

require_once __DIR__ . '/../../backend/services/discord_like_notification.php';

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 Discord風格通知系統測試\n";
echo "========================\n\n";

try {
    // 測試1: 創建通知服務
    echo "1. 測試創建通知服務...\n";
    $service = new DiscordLikeNotificationService();
    echo "✅ 通知服務創建成功\n\n";
    
    // 測試2: 更新用戶活動
    echo "2. 測試更新用戶活動...\n";
    $testUsername = 'test_user_' . time();
    $result = $service->updateUserActivity($testUsername);
    if ($result) {
        echo "✅ 用戶活動更新成功\n\n";
    } else {
        echo "❌ 用戶活動更新失敗\n\n";
    }
    
    // 測試3: 檢查需要通知的用戶
    echo "3. 測試檢查需要通知的用戶...\n";
    $notificationsSent = $service->checkUsersForNotification();
    echo "✅ 檢查完成，共發送 {$notificationsSent} 個通知\n\n";
    
    // 測試4: 測試郵件發送（需要配置Gmail）
    echo "4. 測試郵件發送功能...\n";
    $emailService = new EmailNotificationService();
    
    // 創建測試郵件內容
    $testHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .test-box { background: #f0f8ff; padding: 20px; border-radius: 8px; border-left: 4px solid #5865F2; }
        </style>
    </head>
    <body>
        <div class='test-box'>
            <h2>🧪 測試郵件</h2>
            <p>這是一封Discord風格通知系統的測試郵件。</p>
            <p>如果您收到此郵件，說明通知系統工作正常！</p>
            <p>發送時間: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </body>
    </html>";
    
    // 注意：這裡需要配置真實的Gmail設置才能發送
    echo "⚠️  郵件發送測試需要配置Gmail設置\n";
    echo "   請在 .env 文件中設置 GMAIL_SENDER_EMAIL 和 GMAIL_APP_PASSWORD\n\n";
    
    // 測試5: 檢查資料庫表
    echo "5. 測試資料庫表結構...\n";
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = ['user_activity', 'unread_notifications', 'notification_sent_log'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✅ 表 $table 存在，記錄數: $count\n";
        } catch (PDOException $e) {
            echo "❌ 表 $table 不存在或查詢失敗: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // 測試6: 模擬用戶數據
    echo "6. 測試模擬用戶數據...\n";
    try {
        // 檢查是否有測試用戶
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE username LIKE 'test_%'");
        $testUserCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ 找到 $testUserCount 個測試用戶\n";
        
        // 檢查未讀消息
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
        $messageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ 總消息數: $messageCount\n";
        
    } catch (PDOException $e) {
        echo "❌ 查詢用戶數據失敗: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "🎉 測試完成！\n";
    echo "========================\n";
    echo "如果所有測試都通過，您的Discord風格通知系統已經準備就緒！\n";
    echo "\n下一步：\n";
    echo "1. 配置Gmail設置（.env文件）\n";
    echo "2. 設置定時任務（crontab）\n";
    echo "3. 測試實際通知發送\n";
    
} catch (Exception $e) {
    echo "❌ 測試過程中發生錯誤: " . $e->getMessage() . "\n";
    echo "請檢查資料庫連接和配置設置\n";
}
?>
