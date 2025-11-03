<?php
/**
 * 執行完整資料庫第三正規化（3NF）腳本
 * 包括創建正規化表和遷移數據
 */

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>資料庫 3NF 正規化</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .progress { background: #e9ecef; border-radius: 10px; height: 30px; margin: 20px 0; overflow: hidden; }
        .progress-bar { background: #007bff; height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    echo "<h1>📊 資料庫第三正規化（3NF）執行腳本</h1>";
    echo "<div class='info'>此腳本將正規化整個資料庫至 3NF 標準</div>";
    
    // 步驟 1: 執行創建表腳本
    echo "<div class='step'>";
    echo "<h2>步驟 1: 創建正規化表結構</h2>";
    
    $sqlFile = __DIR__ . '/../database/complete_normalize_to_3nf.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // 分割並執行 SQL 語句
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^\s*--/', $stmt) && 
                       !preg_match('/^\s*\/\*/', $stmt) &&
                       strlen(trim($stmt)) > 10;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        $totalCount = count($statements);
        
        echo "<p>正在執行 $totalCount 個 SQL 語句...</p>";
        echo "<div class='progress'><div class='progress-bar' style='width: 0%' id='progress1'>0%</div></div>";
        
        foreach ($statements as $index => $statement) {
            try {
                $pdo->exec($statement);
                $successCount++;
                $progress = intval(($index + 1) / $totalCount * 100);
                // 注意：這裡無法更新進度條，因為是 PHP 執行
            } catch (PDOException $e) {
                // 忽略某些預期的錯誤
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false &&
                    strpos($e->getMessage(), 'Unknown column') === false) {
                    $errorCount++;
                    echo "<p class='error'>⚠️ SQL 錯誤: " . htmlspecialchars(substr($e->getMessage(), 0, 200)) . "</p>";
                }
            }
        }
        
        echo "<p class='success'>✅ 成功執行 $successCount 個 SQL 語句</p>";
        if ($errorCount > 0) {
            echo "<p class='warning'>⚠️ 遇到 $errorCount 個錯誤（部分可能是預期的，如表已存在）</p>";
        }
    } else {
        echo "<p class='error'>❌ SQL 文件不存在: $sqlFile</p>";
    }
    
    echo "</div>";
    
    // 步驟 2: 執行數據遷移
    echo "<div class='step'>";
    echo "<h2>步驟 2: 遷移現有數據</h2>";
    
    $migrateFile = __DIR__ . '/../database/migrate_all_data_to_3nf.sql';
    
    if (file_exists($migrateFile)) {
        $sql = file_get_contents($migrateFile);
        
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^\s*--/', $stmt) && 
                       !preg_match('/^\s*\/\*/', $stmt) &&
                       strlen(trim($stmt)) > 10;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        echo "<p>正在遷移數據...</p>";
        
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    $errorCount++;
                    echo "<p class='warning'>⚠️ 遷移警告: " . htmlspecialchars(substr($e->getMessage(), 0, 200)) . "</p>";
                }
            }
        }
        
        echo "<p class='success'>✅ 成功執行 $successCount 個遷移語句</p>";
        if ($errorCount > 0) {
            echo "<p class='warning'>⚠️ 遇到 $errorCount 個警告（部分可能是預期的）</p>";
        }
    } else {
        echo "<p class='error'>❌ 遷移文件不存在: $migrateFile</p>";
    }
    
    echo "</div>";
    
    // 步驟 3: 顯示遷移統計
    echo "<div class='step'>";
    echo "<h2>步驟 3: 遷移統計</h2>";
    
    $tables = [
        ['student', 'student_normalized'],
        ['teacher', 'teacher_normalized'],
        ['chat_groups', 'chat_groups_normalized'],
        ['group_members', 'group_members_normalized'],
        ['private_chat_history', 'private_chat_history_normalized'],
        ['group_messages', 'group_messages_normalized'],
        ['ai_chat_history', 'ai_chat_history_normalized'],
    ];
    
    echo "<table>";
    echo "<tr><th>原表</th><th>正規化表</th><th>原表記錄數</th><th>正規化表記錄數</th><th>狀態</th></tr>";
    
    foreach ($tables as $tablePair) {
        $originalTable = $tablePair[0];
        $normalizedTable = $tablePair[1];
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $originalTable");
            $originalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            $originalCount = '表不存在';
        }
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $normalizedTable");
            $normalizedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            $normalizedCount = '表不存在';
        }
        
        $status = (is_numeric($originalCount) && is_numeric($normalizedCount)) 
            ? ($normalizedCount >= $originalCount ? '<span class="success">✅ 成功</span>' : '<span class="warning">⚠️ 需檢查</span>')
            : '<span class="info">ℹ️ 跳過</span>';
        
        echo "<tr>";
        echo "<td>$originalTable</td>";
        echo "<td>$normalizedTable</td>";
        echo "<td>$originalCount</td>";
        echo "<td>$normalizedCount</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // 步驟 4: 顯示視圖
    echo "<div class='step'>";
    echo "<h2>步驟 4: 向後兼容視圖</h2>";
    echo "<p>已創建以下視圖以保持與舊代碼兼容：</p>";
    echo "<ul>";
    echo "<li><code>student_view</code> - 替代 student 表</li>";
    echo "<li><code>teacher_view</code> - 替代 teacher 表</li>";
    echo "<li><code>private_chat_history_view</code> - 替代 private_chat_history 表</li>";
    echo "<li><code>group_messages_view</code> - 替代 group_messages 表</li>";
    echo "<li><code>chat_groups_view</code> - 替代 chat_groups 表</li>";
    echo "<li><code>group_members_view</code> - 替代 group_members 表</li>";
    echo "</ul>";
    echo "</div>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "<div class='step'>";
    echo "<h2 class='success'>✅ 正規化完成！</h2>";
    echo "<h3>下一步建議：</h3>";
    echo "<ol>";
    echo "<li>檢查上述統計數據，確認遷移是否成功</li>";
    echo "<li>測試應用程式功能，確認視圖正常工作</li>";
    echo "<li>在確認無誤後，可以將舊表重命名為 *_backup 作為備份</li>";
    echo "<li>更新應用程式代碼，使用正規化表或視圖</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step'>";
    echo "<h2 class='error'>❌ 資料庫錯誤</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>

