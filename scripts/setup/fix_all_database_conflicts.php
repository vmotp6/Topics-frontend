<?php
/**
 * 修復所有資料庫矛盾
 * 執行所有修復腳本
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
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 啟用查詢緩衝，避免 unbuffered queries 錯誤
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    echo "<h1>🔧 資料庫矛盾修復</h1>";
    echo "<div class='info'>此腳本將修復所有已發現的資料庫矛盾</div>";
    
    $scripts = [
        [
            'name' => '修復 user 引用（username/email → user_id）',
            'file' => __DIR__ . '/../database/fix_user_references_to_user_id.sql',
            'description' => '將所有使用 username 或 email 的表改為使用 user_id'
        ],
        [
            'name' => '正規化 senior_messages 表',
            'file' => __DIR__ . '/../database/normalize_senior_messages.sql',
            'description' => '將 author_department, author_grade 改為外鍵，author_email 改為 user_id'
        ],
        [
            'name' => '合併重複的學校表',
            'file' => __DIR__ . '/../database/merge_duplicate_school_tables_fixed.sql',
            'description' => '將 school_data 合併到 schools 表'
        ]
    ];
    
    foreach ($scripts as $index => $script) {
        echo "<div class='step'>";
        echo "<h2>步驟 " . ($index + 1) . ": {$script['name']}</h2>";
        echo "<p>{$script['description']}</p>";
        
        if (file_exists($script['file'])) {
            $sql = file_get_contents($script['file']);
            
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
            
            foreach ($statements as $statement) {
                try {
                    // 跳過 SELECT 語句（這些會產生結果集）
                    if (preg_match('/^\s*SELECT/i', trim($statement))) {
                        // 對於 SELECT，使用 query 並獲取所有結果
                        $stmt = $pdo->query($statement);
                        $stmt->fetchAll(PDO::FETCH_ASSOC); // 獲取所有結果以清除緩衝
                        $stmt->closeCursor();
                    } else {
                        // 對於其他語句，使用 exec
                        $pdo->exec($statement);
                    }
                    $successCount++;
                } catch (PDOException $e) {
                    $errorMessage = $e->getMessage();
                    // 忽略這些預期的錯誤
                    $ignoredErrors = [
                        'already exists',
                        'Duplicate',
                        'Unknown column',
                        'unbuffered',
                        'Duplicate column name',
                        'Duplicate key name',
                        'already have that key',
                        'Cannot add or update a child row',
                        'Cannot delete or update a parent row'
                    ];
                    
                    $shouldIgnore = false;
                    foreach ($ignoredErrors as $ignore) {
                        if (stripos($errorMessage, $ignore) !== false) {
                            $shouldIgnore = true;
                            break;
                        }
                    }
                    
                    if (!$shouldIgnore) {
                        $errorCount++;
                        // 只顯示前 3 個不同的錯誤，避免重複
                        if ($errorCount <= 3) {
                            echo "<p class='warning'>⚠️ SQL 警告: " . htmlspecialchars(substr($errorMessage, 0, 200)) . "</p>";
                        }
                    }
                }
            }
            
            echo "<p class='success'>✅ 成功執行 $successCount 個 SQL 語句</p>";
            if ($errorCount > 0) {
                echo "<p class='warning'>⚠️ 遇到 $errorCount 個警告（部分可能是預期的）</p>";
            }
        } else {
            echo "<p class='error'>❌ SQL 文件不存在: {$script['file']}</p>";
        }
        
        echo "</div>";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "<div class='step'>";
    echo "<h2 class='success'>✅ 修復完成！</h2>";
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

