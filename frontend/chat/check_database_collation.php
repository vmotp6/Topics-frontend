<?php
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>資料庫字符集檢查</h2>";
    
    // 檢查所有資料表的字符集
    echo "<h3>1. 檢查所有資料表的字符集</h3>";
    $stmt = $pdo->query("SELECT 
        TABLE_NAME,
        TABLE_COLLATION,
        TABLE_SCHEMA
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'topics_good'
    ORDER BY TABLE_NAME");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>資料表名</th><th>字符集</th></tr>";
    foreach ($tables as $table) {
        echo "<tr>";
        echo "<td>{$table['TABLE_NAME']}</td>";
        echo "<td>{$table['TABLE_COLLATION']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 檢查聊天相關資料表的欄位字符集
    echo "<h3>2. 檢查聊天相關資料表的欄位字符集</h3>";
    $chatTables = ['private_chat_history', 'chat_history', 'user', 'teacher02'];
    
    foreach ($chatTables as $tableName) {
        echo "<h4>檢查 {$tableName} 表：</h4>";
        try {
            $stmt = $pdo->query("SHOW FULL COLUMNS FROM {$tableName}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>欄位名</th><th>類型</th><th>字符集</th><th>排序規則</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Collation']}</td>";
                echo "<td>{$column['Collation']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 無法檢查 {$tableName} 表：{$e->getMessage()}</p>";
        }
    }
    
    // 修復字符集衝突
    echo "<h3>3. 修復字符集衝突</h3>";
    
    // 修復 private_chat_history 表
    try {
        $sql = "ALTER TABLE private_chat_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ private_chat_history 表字符集已修復</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ private_chat_history 表修復失敗：{$e->getMessage()}</p>";
    }
    
    // 修復 chat_history 表
    try {
        $sql = "ALTER TABLE chat_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ chat_history 表字符集已修復</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ chat_history 表修復失敗：{$e->getMessage()}</p>";
    }
    
    // 修復 user 表
    try {
        $sql = "ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ user 表字符集已修復</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ user 表修復失敗：{$e->getMessage()}</p>";
    }
    
    // 修復 teacher02 表
    try {
        $sql = "ALTER TABLE teacher02 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ teacher02 表字符集已修復</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ teacher02 表修復失敗：{$e->getMessage()}</p>";
    }
    
} catch(PDOException $e) {
    echo "<h3>❌ 資料庫連接失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>
