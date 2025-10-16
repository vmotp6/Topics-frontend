<?php
/**
 * 簡單的用戶活動查詢腳本
 */

// 數據庫連接配置
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

$target_user = 'BOB02315213';

echo "正在查詢用戶 {$target_user} 的活動記錄...\n";
echo "查詢時間: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ 數據庫連接成功\n";
    
    // 1. 檢查用戶是否存在
    echo "\n1. 檢查用戶基本信息:\n";
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? OR user_id = ?");
    $stmt->execute([$target_user, $target_user]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_info) {
        echo "✅ 找到用戶記錄:\n";
        foreach ($user_info as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
    } else {
        echo "❌ 未找到用戶 {$target_user}\n";
    }
    
    // 2. 檢查用戶活動記錄
    echo "\n2. 檢查用戶活動記錄:\n";
    $stmt = $pdo->prepare("SELECT * FROM user_activity WHERE username = ?");
    $stmt->execute([$target_user]);
    $activity_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($activity_records) {
        echo "✅ 找到活動記錄:\n";
        foreach ($activity_records as $record) {
            foreach ($record as $key => $value) {
                echo "   {$key}: {$value}\n";
            }
            echo "   ---\n";
        }
    } else {
        echo "❌ 未找到活動記錄\n";
    }
    
    // 3. 檢查聊天記錄
    echo "\n3. 檢查聊天記錄:\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM private_chat_history WHERE from_user = ? OR to_user = ?");
    $stmt->execute([$target_user, $target_user]);
    $chat_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "聊天記錄總數: {$chat_count}\n";
    
    if ($chat_count > 0) {
        $stmt = $pdo->prepare("SELECT * FROM private_chat_history WHERE from_user = ? OR to_user = ? ORDER BY timestamp DESC LIMIT 5");
        $stmt->execute([$target_user, $target_user]);
        $recent_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "最近5條聊天記錄:\n";
        foreach ($recent_chats as $chat) {
            echo "   時間: {$chat['timestamp']}\n";
            echo "   發送者: {$chat['from_user']}\n";
            echo "   接收者: {$chat['to_user']}\n";
            echo "   訊息: " . substr($chat['message'], 0, 50) . "...\n";
            echo "   ---\n";
        }
    }
    
    // 4. 檢查昨天的活動
    echo "\n4. 檢查昨天的活動:\n";
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterday_start = $yesterday . ' 00:00:00';
    $yesterday_end = $yesterday . ' 23:59:59';
    
    echo "查詢日期範圍: {$yesterday_start} 到 {$yesterday_end}\n";
    
    // 統計昨天的聊天活動
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM private_chat_history 
                          WHERE (from_user = ? OR to_user = ?) 
                          AND timestamp BETWEEN ? AND ?");
    $stmt->execute([$target_user, $target_user, $yesterday_start, $yesterday_end]);
    $yesterday_chat_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "昨天聊天訊息數量: {$yesterday_chat_count}\n";
    
    // 統計昨天的已讀活動
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM message_read_status 
                          WHERE reader_username = ? 
                          AND read_at BETWEEN ? AND ?");
    $stmt->execute([$target_user, $yesterday_start, $yesterday_end]);
    $yesterday_read_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "昨天已讀訊息數量: {$yesterday_read_count}\n";
    
    // 5. 檢查所有相關表
    echo "\n5. 檢查所有相關表:\n";
    $tables = ['user', 'user_activity', 'private_chat_history', 'message_read_status', 'unread_notifications', 'notification_sent_log'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   {$table}: {$count} 條記錄\n";
        } else {
            echo "   {$table}: 表不存在\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ 數據庫錯誤: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 發生錯誤: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "查詢完成\n";
?>
