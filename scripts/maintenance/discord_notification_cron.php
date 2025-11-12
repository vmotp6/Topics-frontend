<?php
/**
 * Discord風格通知定時任務
 * 建議每小時執行一次
 * 
 * 使用方法：
 * 1. 在crontab中添加：0 * * * * /usr/bin/php /path/to/discord_notification_cron.php
 * 2. 或者手動執行：php discord_notification_cron.php
 */

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置時區
date_default_timezone_set('Asia/Taipei');

// 引入必要的文件
require_once __DIR__ . '/../../backend/services/discord_like_notification.php';

// 日誌函數
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    echo $logMessage;
    error_log($logMessage);
}

// 主執行函數
function main() {
    logMessage("開始執行Discord風格通知檢查...");
    
    try {
        // 創建通知服務實例
        $notificationService = new DiscordLikeNotificationService();
        
        // 檢查並發送通知
        $notificationsSent = $notificationService->checkUsersForNotification();
        
        logMessage("通知檢查完成，共發送 $notificationsSent 個通知");
        
        // 記錄統計信息
        $stats = getNotificationStats();
        logMessage("統計信息 - 總用戶數: {$stats['total_users']}, 活躍用戶: {$stats['active_users']}, 未讀消息: {$stats['unread_messages']}");
        
    } catch (Exception $e) {
        logMessage("執行過程中發生錯誤: " . $e->getMessage());
        exit(1);
    }
    
    logMessage("Discord風格通知檢查執行完成");
}

// 獲取統計信息
function getNotificationStats() {
    try {
        $host = 'localhost';
        $dbname = 'topics_good';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 總用戶數
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE email IS NOT NULL AND email != ''");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // 活躍用戶數（24小時內有活動）
        $stmt = $pdo->query("SELECT COUNT(DISTINCT username) as count FROM user_activity WHERE last_seen > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // 未讀消息數
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history pch 
                            LEFT JOIN user_activity ua ON pch.to_user = ua.username 
                            WHERE pch.timestamp > COALESCE(ua.last_chat_check, '1970-01-01')");
        $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'unread_messages' => $unreadMessages
        ];
        
    } catch (PDOException $e) {
        logMessage("獲取統計信息失敗: " . $e->getMessage());
        return [
            'total_users' => 0,
            'active_users' => 0,
            'unread_messages' => 0
        ];
    }
}

// 檢查是否應該執行（避免重複執行）
function shouldExecute() {
    $lockFile = __DIR__ . '/discord_notification.lock';
    
    // 檢查鎖文件是否存在且不超過1小時
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        if (time() - $lockTime < 3600) { // 1小時內不重複執行
            logMessage("檢測到鎖文件，跳過執行");
            return false;
        }
    }
    
    // 創建鎖文件
    file_put_contents($lockFile, date('Y-m-d H:i:s'));
    
    // 註冊清理函數
    register_shutdown_function(function() use ($lockFile) {
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    });
    
    return true;
}

// 執行主程序
if (shouldExecute()) {
    main();
} else {
    logMessage("跳過執行，避免重複運行");
}
?>
