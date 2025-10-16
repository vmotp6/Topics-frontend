<?php
/**
 * 查詢用戶BOB02315213的活動記錄
 */

// 數據庫連接配置
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>用戶 BOB02315213 活動記錄查詢</h1>";
    echo "<p>查詢時間: " . date('Y-m-d H:i:s') . "</p>";
    
    $target_user = 'BOB02315213';
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    echo "<h2>查詢日期範圍: {$yesterday} 到 {$today}</h2>";
    
    // 1. 檢查用戶基本信息
    echo "<h3>1. 用戶基本信息</h3>";
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? OR user_id = ?");
    $stmt->execute([$target_user, $target_user]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_info) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>欄位</th><th>值</th></tr>";
        foreach ($user_info as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ 未找到用戶 {$target_user}</p>";
    }
    
    // 2. 檢查用戶活動記錄
    echo "<h3>2. 用戶活動記錄 (user_activity)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM user_activity WHERE username = ?");
    $stmt->execute([$target_user]);
    $activity_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($activity_records) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>用戶名</th><th>最後活動時間</th><th>最後聊天檢查</th><th>通知偏好</th><th>創建時間</th><th>更新時間</th></tr>";
        foreach ($activity_records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>" . htmlspecialchars($record['username']) . "</td>";
            echo "<td>{$record['last_seen']}</td>";
            echo "<td>{$record['last_chat_check']}</td>";
            echo "<td>" . htmlspecialchars($record['notification_preferences']) . "</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "<td>{$record['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 未找到用戶活動記錄</p>";
    }
    
    // 3. 檢查聊天記錄
    echo "<h3>3. 聊天記錄 (private_chat_history)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM private_chat_history WHERE from_user = ? OR to_user = ? ORDER BY timestamp DESC LIMIT 20");
    $stmt->execute([$target_user, $target_user]);
    $chat_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($chat_records) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>時間</th><th>已讀</th><th>讀取時間</th></tr>";
        foreach ($chat_records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>" . htmlspecialchars($record['from_user']) . "</td>";
            echo "<td>" . htmlspecialchars($record['to_user']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['message'], 0, 50)) . "...</td>";
            echo "<td>{$record['timestamp']}</td>";
            echo "<td>" . ($record['is_read'] ? '是' : '否') . "</td>";
            echo "<td>{$record['read_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 未找到聊天記錄</p>";
    }
    
    // 4. 檢查已讀狀態記錄
    echo "<h3>4. 已讀狀態記錄 (message_read_status)</h3>";
    $stmt = $pdo->prepare("SELECT mrs.*, pch.from_user, pch.to_user, pch.message, pch.timestamp as message_time 
                          FROM message_read_status mrs 
                          JOIN private_chat_history pch ON mrs.message_id = pch.id 
                          WHERE mrs.reader_username = ? 
                          ORDER BY mrs.read_at DESC LIMIT 20");
    $stmt->execute([$target_user]);
    $read_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($read_records) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>訊息ID</th><th>讀取者</th><th>發送者</th><th>接收者</th><th>訊息</th><th>訊息時間</th><th>讀取時間</th></tr>";
        foreach ($read_records as $record) {
            echo "<tr>";
            echo "<td>{$record['message_id']}</td>";
            echo "<td>" . htmlspecialchars($record['reader_username']) . "</td>";
            echo "<td>" . htmlspecialchars($record['from_user']) . "</td>";
            echo "<td>" . htmlspecialchars($record['to_user']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['message'], 0, 30)) . "...</td>";
            echo "<td>{$record['message_time']}</td>";
            echo "<td>{$record['read_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 未找到已讀狀態記錄</p>";
    }
    
    // 5. 檢查通知記錄
    echo "<h3>5. 通知記錄 (unread_notifications)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM unread_notifications WHERE username = ? ORDER BY sent_at DESC LIMIT 20");
    $stmt->execute([$target_user]);
    $notification_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($notification_records) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>用戶名</th><th>通知類型</th><th>發送者</th><th>訊息預覽</th><th>發送時間</th><th>已讀</th></tr>";
        foreach ($notification_records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>" . htmlspecialchars($record['username']) . "</td>";
            echo "<td>{$record['notification_type']}</td>";
            echo "<td>" . htmlspecialchars($record['sender_username']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($record['message_preview'], 0, 30)) . "...</td>";
            echo "<td>{$record['sent_at']}</td>";
            echo "<td>" . ($record['is_read'] ? '是' : '否') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 未找到通知記錄</p>";
    }
    
    // 6. 檢查通知發送記錄
    echo "<h3>6. 通知發送記錄 (notification_sent_log)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM notification_sent_log WHERE username = ? ORDER BY sent_at DESC LIMIT 20");
    $stmt->execute([$target_user]);
    $sent_log_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sent_log_records) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>用戶名</th><th>通知類型</th><th>發送時間</th><th>郵件發送</th></tr>";
        foreach ($sent_log_records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>" . htmlspecialchars($record['username']) . "</td>";
            echo "<td>{$record['notification_type']}</td>";
            echo "<td>{$record['sent_at']}</td>";
            echo "<td>" . ($record['email_sent'] ? '是' : '否') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 未找到通知發送記錄</p>";
    }
    
    // 7. 檢查其他可能的活動記錄表
    echo "<h3>7. 其他活動記錄</h3>";
    
    // 檢查是否有activity_records表
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_records'");
    if ($stmt->rowCount() > 0) {
        echo "<h4>活動記錄表 (activity_records)</h4>";
        $stmt = $pdo->prepare("SELECT * FROM activity_records WHERE teacher_id = ? OR user_id = ? ORDER BY activity_date DESC LIMIT 20");
        $stmt->execute([$target_user, $target_user]);
        $activity_table_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($activity_table_records) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>教師ID</th><th>活動類型</th><th>活動描述</th><th>活動日期</th><th>創建時間</th></tr>";
            foreach ($activity_table_records as $record) {
                echo "<tr>";
                echo "<td>{$record['id']}</td>";
                echo "<td>" . htmlspecialchars($record['teacher_id']) . "</td>";
                echo "<td>{$record['activity_type']}</td>";
                echo "<td>" . htmlspecialchars(substr($record['activity_description'], 0, 50)) . "...</td>";
                echo "<td>{$record['activity_date']}</td>";
                echo "<td>{$record['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ 未找到活動記錄表記錄</p>";
        }
    }
    
    // 8. 總結昨天的活動
    echo "<h3>8. 昨天活動總結</h3>";
    $yesterday_start = $yesterday . ' 00:00:00';
    $yesterday_end = $yesterday . ' 23:59:59';
    
    echo "<p><strong>查詢時間範圍:</strong> {$yesterday_start} 到 {$yesterday_end}</p>";
    
    // 統計昨天的聊天活動
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM private_chat_history 
                          WHERE (from_user = ? OR to_user = ?) 
                          AND timestamp BETWEEN ? AND ?");
    $stmt->execute([$target_user, $target_user, $yesterday_start, $yesterday_end]);
    $yesterday_chat_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>昨天聊天訊息數量:</strong> {$yesterday_chat_count}</p>";
    
    // 統計昨天的已讀活動
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM message_read_status 
                          WHERE reader_username = ? 
                          AND read_at BETWEEN ? AND ?");
    $stmt->execute([$target_user, $yesterday_start, $yesterday_end]);
    $yesterday_read_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>昨天已讀訊息數量:</strong> {$yesterday_read_count}</p>";
    
    // 檢查最後活動時間
    if ($activity_records) {
        $last_activity = $activity_records[0];
        echo "<p><strong>最後活動時間:</strong> {$last_activity['last_seen']}</p>";
        echo "<p><strong>最後聊天檢查時間:</strong> {$last_activity['last_chat_check']}</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 數據庫連接錯誤: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 發生錯誤: " . $e->getMessage() . "</p>";
}
?>
