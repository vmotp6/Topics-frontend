<?php
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = '100.79.58.120';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>建立聊天資料表</h2>";
    
    // 建立 private_chat_history 表
    echo "<h3>1. 建立 private_chat_history 表</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS private_chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user VARCHAR(255) NOT NULL,
            to_user VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            role VARCHAR(50) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_from_user (from_user),
            INDEX idx_to_user (to_user),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ private_chat_history 表建立成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ private_chat_history 表已存在或建立失敗：{$e->getMessage()}</p>";
    }
    
    // 建立 group_chats 表
    echo "<h3>2. 建立 group_chats 表</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS group_chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_name VARCHAR(255) NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ group_chats 表建立成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ group_chats 表已存在或建立失敗：{$e->getMessage()}</p>";
    }
    
    // 建立 group_chat_members 表
    echo "<h3>3. 建立 group_chat_members 表</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS group_chat_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            member_username VARCHAR(255) NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id),
            INDEX idx_member_username (member_username),
            UNIQUE KEY unique_group_member (group_id, member_username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ group_chat_members 表建立成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ group_chat_members 表已存在或建立失敗：{$e->getMessage()}</p>";
    }
    
    // 建立 group_chat_messages 表
    echo "<h3>4. 建立 group_chat_messages 表</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS group_chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            from_user VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            role VARCHAR(50) NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES group_chats(id) ON DELETE CASCADE,
            INDEX idx_group_id (group_id),
            INDEX idx_from_user (from_user),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ group_chat_messages 表建立成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ group_chat_messages 表已存在或建立失敗：{$e->getMessage()}</p>";
    }
    
    // 檢查所有表是否建立成功
    echo "<h3>5. 檢查所有資料表</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE '%chat%'");
    $chatTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = ['private_chat_history', 'group_chats', 'group_chat_members', 'group_chat_messages'];
    $missingTables = array_diff($requiredTables, $chatTables);
    
    if (empty($missingTables)) {
        echo "<p style='color: green;'>✅ 所有聊天資料表都已建立完成！</p>";
        echo "<p>現在可以正常使用聊天功能了。</p>";
    } else {
        echo "<p style='color: red;'>❌ 以下資料表尚未建立：</p>";
        echo "<ul>";
        foreach ($missingTables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    }
    
    // 添加一些測試資料
    echo "<h3>6. 添加測試資料</h3>";
    try {
        // 檢查是否已有測試資料
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            // 添加一些測試聊天記錄
            $testMessages = [
                ['vendor1', 'teacher1', '你好，我是廠商', '廠商'],
                ['teacher1', 'vendor1', '你好，我是老師', '老師'],
                ['vendor1', 'teacher1', '請問有什麼可以幫助您的嗎？', '廠商'],
                ['teacher1', 'vendor1', '我想了解一些產品資訊', '老師']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)");
            foreach ($testMessages as $message) {
                $stmt->execute($message);
            }
            
            echo "<p style='color: green;'>✅ 已添加測試聊天記錄</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ 已有聊天記錄，跳過添加測試資料</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ 添加測試資料失敗：{$e->getMessage()}</p>";
    }
    
} catch(PDOException $e) {
    echo "<h3>❌ 資料庫連接失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>

