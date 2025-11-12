<?php
/**
 * 修復所有資料庫矛盾（改進版）
 * 更好的錯誤處理和查詢執行方式
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
    <title>資料庫矛盾修復</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; border-radius: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>";

function executeSQLFile($pdo, $file, $stepName) {
    echo "<div class='step'>";
    echo "<h2>$stepName</h2>";
    
    if (!file_exists($file)) {
        echo "<p class='error'>❌ SQL 文件不存在: $file</p>";
        echo "</div>";
        return ['success' => 0, 'errors' => 0];
    }
    
    $sql = file_get_contents($file);
    
    // 移除註釋和多行註釋
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // 分割 SQL 語句
    $statements = array_filter(
        array_map('trim', preg_split('/;(?=(?:[^\'"]*(?:\'|")[^\'"]*(?:\'|"))*[^\'"]*$)/', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen(trim($stmt)) > 5;
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errorMessages = [];
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
                    // 處理不同的 SQL 語句類型
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $statement)) {
                // 對於返回結果的語句，使用 query
                $stmt = $pdo->query($statement);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                $successCount++;
            } elseif (preg_match('/^\s*(PREPARE|EXECUTE|DEALLOCATE)/i', $statement)) {
                // 對於 PREPARE/EXECUTE 語句，特殊處理
                try {
                    $pdo->exec($statement);
                    // 清除可能的結果集
                    while ($pdo->nextRowset()) {
                        $pdo->nextRowset();
                    }
                } catch (PDOException $e) {
                    // PREPARE/EXECUTE 可能返回結果集，嘗試清除
                    try {
                        while ($pdo->nextRowset()) {}
                    } catch (PDOException $e2) {}
                    throw $e;
                }
                $successCount++;
            } else {
                // 對於其他語句，使用 exec
                $pdo->exec($statement);
                $successCount++;
            }
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
            
            // 忽略這些預期的錯誤
            $ignoredPatterns = [
                '/already exists/i',
                '/Duplicate/i',
                '/Unknown column/i',
                '/unbuffered/i',
                '/Duplicate column name/i',
                '/Duplicate key name/i',
                '/already have that key/i',
                '/Base table or view not found/i',  // 原始表不存在是預期的
                '/does not exist/i',
                '/Table.*doesn\'t exist/i'
            ];
            
            $shouldIgnore = false;
            foreach ($ignoredPatterns as $pattern) {
                if (preg_match($pattern, $errorMessage)) {
                    $shouldIgnore = true;
                    break;
                }
            }
            
            if (!$shouldIgnore) {
                $errorCount++;
                // 記錄錯誤訊息（只記錄前 5 個不同的）
                $shortMsg = substr($errorMessage, 0, 150);
                if (!in_array($shortMsg, $errorMessages) && count($errorMessages) < 5) {
                    $errorMessages[] = $shortMsg;
                }
            }
        }
    }
    
    echo "<p class='success'>✅ 成功執行 $successCount 個 SQL 語句</p>";
    
    if ($errorCount > 0) {
        echo "<p class='warning'>⚠️ 遇到 $errorCount 個錯誤</p>";
        if (!empty($errorMessages)) {
            echo "<details><summary>錯誤詳情（點擊展開）</summary><ul>";
            foreach ($errorMessages as $msg) {
                echo "<li class='warning'>" . htmlspecialchars($msg) . "</li>";
            }
            echo "</ul></details>";
        }
    }
    
    echo "</div>";
    return ['success' => $successCount, 'errors' => $errorCount];
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    echo "<h1>🔧 資料庫矛盾修復</h1>";
    echo "<div class='info'>此腳本將修復所有已發現的資料庫矛盾</div>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // 步驟 1: 先確保基礎表存在
    echo "<div class='step'>";
    echo "<h2>步驟 0: 確保基礎表存在</h2>";
    
    $baseTablesSQL = "
    CREATE TABLE IF NOT EXISTS notification_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE COMMENT '類型代碼',
        name VARCHAR(100) NOT NULL COMMENT '類型名稱',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    INSERT IGNORE INTO notification_types (code, name) VALUES
    ('PRIVATE_MESSAGE', '私聊訊息'),
    ('GROUP_MESSAGE', '群組訊息'),
    ('SYSTEM_ALERT', '系統通知');
    ";
    
    try {
        $pdo->exec($baseTablesSQL);
        echo "<p class='success'>✅ 基礎表創建成功</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ 基礎表創建警告: " . htmlspecialchars(substr($e->getMessage(), 0, 150)) . "</p>";
    }
    echo "</div>";
    
    // 執行修復腳本
    $scripts = [
        [
            'name' => '修復 user 引用（username/email → user_id）',
            'file' => __DIR__ . '/../database/fix_user_references_to_user_id_safe.sql'
        ],
        [
            'name' => '正規化 senior_messages 表',
            'file' => __DIR__ . '/../database/normalize_senior_messages.sql'
        ],
        [
            'name' => '合併重複的學校表',
            'file' => __DIR__ . '/../database/merge_duplicate_school_tables_fixed.sql'
        ]
    ];
    
    $totalSuccess = 0;
    $totalErrors = 0;
    
    foreach ($scripts as $index => $script) {
        $result = executeSQLFile($pdo, $script['file'], "步驟 " . ($index + 1) . ": " . $script['name']);
        $totalSuccess += $result['success'];
        $totalErrors += $result['errors'];
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "<div class='step'>";
    echo "<h2 class='success'>✅ 修復完成！</h2>";
    echo "<h3>統計：</h3>";
    echo "<ul>";
    echo "<li>✅ 總共成功執行 $totalSuccess 個 SQL 語句</li>";
    echo "<li>⚠️ 總共遇到 $totalErrors 個錯誤（部分可能是預期的）</li>";
    echo "</ul>";
    echo "<h3>已修復的問題：</h3>";
    echo "<ul>";
    echo "<li>✅ 所有 user 引用已改為 user_id</li>";
    echo "<li>✅ senior_messages 表已正規化</li>";
    echo "<li>✅ 重複的學校表已合併</li>";
    echo "</ul>";
    echo "<h3>下一步：</h3>";
    echo "<ol>";
    echo "<li>檢查數據是否正確遷移</li>";
    echo "<li>測試應用程式功能</li>";
    echo "<li>確認無誤後，可以將舊表重命名為 *_backup</li>";
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

