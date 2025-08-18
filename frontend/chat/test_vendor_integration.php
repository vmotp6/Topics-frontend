<?php
// 測試廠商群聊整合功能
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

echo "<h1>廠商群聊功能整合測試</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. 檢查資料庫表格</h2>";
    
    // 檢查群聊相關表格
    $tables = ['group_chats', 'group_chat_members', 'group_chat_messages'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✅ 表格 $table 存在，有 $count 條記錄</p>";
        } catch (Exception $e) {
            echo "<p>❌ 表格 $table 不存在或無法訪問: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>2. 測試廠商群聊查詢</h2>";
    
    // 測試廠商群聊查詢
    $testVendors = ['vendor1', 'vendor2', 'test_vendor'];
    foreach ($testVendors as $vendor) {
        try {
            $stmt = $pdo->prepare("SELECT gc.id, gc.group_name, gc.created_by, gc.created_at
                                  FROM group_chats gc 
                                  JOIN group_chat_members gcm ON gc.id = gcm.group_id 
                                  WHERE gcm.member_username = ?
                                  ORDER BY gc.created_at DESC");
            $stmt->execute([$vendor]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>廠商 $vendor 參與的群聊: " . count($groups) . " 個</p>";
            foreach ($groups as $group) {
                echo "<ul><li>群聊ID: {$group['id']}, 名稱: {$group['group_name']}, 建立者: {$group['created_by']}</li></ul>";
            }
        } catch (Exception $e) {
            echo "<p>❌ 查詢廠商 $vendor 的群聊失敗: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>3. 測試群聊成員查詢</h2>";
    
    // 測試群聊成員查詢
    try {
        $stmt = $pdo->query("SELECT id, group_name FROM group_chats ORDER BY created_at DESC LIMIT 3");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($groups as $group) {
            $stmt = $pdo->prepare("SELECT member_username, joined_at FROM group_chat_members WHERE group_id = ?");
            $stmt->execute([$group['id']]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>群聊 {$group['group_name']} (ID: {$group['id']}) 的成員: " . count($members) . " 個</p>";
            foreach ($members as $member) {
                echo "<ul><li>成員: {$member['member_username']}, 加入時間: {$member['joined_at']}</li></ul>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ 查詢群聊成員失敗: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>4. 測試群聊訊息查詢</h2>";
    
    // 測試群聊訊息查詢
    try {
        $stmt = $pdo->query("SELECT id, group_name FROM group_chats ORDER BY created_at DESC LIMIT 2");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($groups as $group) {
            $stmt = $pdo->prepare("SELECT from_user, message, timestamp FROM group_chat_messages WHERE group_id = ? ORDER BY timestamp DESC LIMIT 5");
            $stmt->execute([$group['id']]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>群聊 {$group['group_name']} (ID: {$group['id']}) 的訊息: " . count($messages) . " 條</p>";
            foreach ($messages as $message) {
                echo "<ul><li>{$message['from_user']}: {$message['message']} (時間: {$message['timestamp']})</li></ul>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ 查詢群聊訊息失敗: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>5. 模擬廠商登入測試</h2>";
    
    // 模擬廠商登入測試
    $testVendor = 'vendor1';
    try {
        // 獲取教師列表
        $stmt = $pdo->query("SELECT t2.u_id, t2.name, t2.department, u.username 
                             FROM teacher02 t2 
                             JOIN user u ON t2.u_id = u.id 
                             WHERE u.role = '老師'");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 獲取廠商參與的群聊
        $stmt = $pdo->prepare("SELECT gc.id, gc.group_name, gc.created_by, '群聊' as contact_type
                              FROM group_chats gc 
                              JOIN group_chat_members gcm ON gc.id = gcm.group_id 
                              WHERE gcm.member_username = ?
                              ORDER BY gc.created_at DESC");
        $stmt->execute([$testVendor]);
        $userGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 合併教師列表和群聊
        $contacts = array_merge($teachers, $userGroups);
        
        echo "<p>✅ 廠商 $testVendor 登入成功</p>";
        echo "<p>聯絡人列表: " . count($contacts) . " 個項目</p>";
        echo "<ul>";
        echo "<li>教師: " . count($teachers) . " 個</li>";
        echo "<li>群聊: " . count($userGroups) . " 個</li>";
        echo "</ul>";
        
        echo "<p>詳細列表:</p>";
        foreach ($contacts as $contact) {
            if (isset($contact['contact_type']) && $contact['contact_type'] === '群聊') {
                echo "<p>👥 群聊: {$contact['group_name']} (ID: {$contact['id']}, 建立者: {$contact['created_by']})</p>";
            } else {
                echo "<p>👨‍🏫 教師: {$contact['name']} ({$contact['department']})</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ 模擬廠商登入失敗: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>6. 測試結果總結</h2>";
    echo "<p>✅ 廠商群聊功能整合測試完成</p>";
    echo "<p>現在廠商帳號登入時應該能夠看到:</p>";
    echo "<ul>";
    echo "<li>所有教師列表</li>";
    echo "<li>參與的群聊列表</li>";
    echo "<li>群聊顯示為 👥 圖標</li>";
    echo "<li>教師顯示為首字母頭像</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p>❌ 資料庫連接失敗: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h1, h2 {
    color: #333;
}
p {
    margin: 10px 0;
    padding: 10px;
    background: white;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
ul {
    margin: 5px 0;
    padding-left: 20px;
}
li {
    margin: 2px 0;
}
</style>

