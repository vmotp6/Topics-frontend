<?php
/**
 * 設置聊天系統支援學生
 * 不需要創建額外的表，直接使用現有的 user 表
 */

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 設置聊天系統支援學生...\n";
    
    // 檢查 user 表是否存在學生角色
    $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'role'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'user' AND COLUMN_NAME = 'role'");
        $columnType = $stmt->fetch(PDO::FETCH_ASSOC)['COLUMN_TYPE'];
        
        if (strpos($columnType, '學生') === false) {
            // 更新 user 表，添加學生角色
            $sql = "ALTER TABLE user MODIFY COLUMN role ENUM('老師', '學校行政人員', '學生', '廠商') NOT NULL";
            $pdo->exec($sql);
            echo "✅ 用戶表角色欄位已更新，支援學生角色\n";
        } else {
            echo "✅ 用戶表已支援學生角色\n";
        }
    } else {
        echo "❌ user 表不存在或沒有 role 欄位\n";
        exit(1);
    }
    
    // 檢查現有的學生用戶
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '學生'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 發現 $studentCount 個學生用戶\n";
    
    if ($studentCount > 0) {
        // 顯示現有學生
        $stmt = $pdo->query("SELECT username FROM user WHERE role = '學生' ORDER BY username");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "👨‍🎓 現有學生用戶：\n";
        foreach ($students as $student) {
            echo "  - {$student['username']}\n";
        }
    } else {
        echo "⚠️  沒有找到學生用戶\n";
        echo "💡 請在 user 表中添加 role='學生' 的用戶\n";
    }
    
    // 檢查老師用戶
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = '老師'");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 發現 $teacherCount 個老師用戶\n";
    
    // 檢查必要的聊天表
    $tables = ['private_chat_history', 'chat_groups', 'group_members', 'group_messages'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "⚠️  缺少以下聊天表：\n";
        foreach ($missingTables as $table) {
            echo "  - $table\n";
        }
        echo "💡 請執行 create_chat_tables.sql 創建聊天表\n";
    } else {
        echo "✅ 所有必要的聊天表都已存在\n";
    }
    
    echo "\n🎉 聊天系統設置完成！\n";
    echo "📝 現在可以：\n";
    echo "  - 老師可以看到所有學生和同科系老師\n";
    echo "  - 學生可以看到所有老師和其他學生\n";
    echo "  - 使用搜尋功能快速找到聯絡人\n";
    echo "  - 創建群組進行群聊\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}
?>

