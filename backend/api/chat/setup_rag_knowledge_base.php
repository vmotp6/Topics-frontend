<?php
/**
 * RAG 知識庫資料表設置腳本
 * 在 ollama 資料庫中建立 RAG 系統所需的資料表
 */

require_once dirname(__DIR__, 2) . '/config/ollama_config.php';

// 設定錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <title>RAG 知識庫設置</title>
    <style>
        body { font-family: 'Microsoft JhengHei', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1>📚 RAG 知識庫資料表設置</h1>
    <pre>";

try {
    // 確保 ollama 資料庫存在
    ensureOllamaDatabase();
    
    $conn = getOllamaDatabaseConnection();
    
    if (!$conn) {
        die("❌ 資料庫連接失敗\n");
    }
    
    echo "✅ Ollama 資料庫連接成功\n\n";
    
    // 讀取 SQL 文件
    $sqlFile = __DIR__ . '/setup_rag_knowledge_base.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ SQL 文件不存在: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // 分割 SQL 語句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt) &&
                   !preg_match('/^USE/', $stmt); // 跳過 USE 語句，因為已經連接
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            if (stripos($statement, 'CREATE TABLE') !== false ||
                stripos($statement, 'CREATE OR REPLACE VIEW') !== false ||
                stripos($statement, 'INSERT INTO') !== false) {
                
                $result = $conn->query($statement);
                
                if ($result) {
                    $successCount++;
                    if (stripos($statement, 'CREATE TABLE') !== false) {
                        preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches);
                        $tableName = $matches[1] ?? '未知表';
                        echo "✅ 成功建立資料表: $tableName\n";
                    } elseif (stripos($statement, 'CREATE OR REPLACE VIEW') !== false) {
                        preg_match('/CREATE OR REPLACE VIEW.*?`(\w+)`/i', $statement, $matches);
                        $viewName = $matches[1] ?? '未知視圖';
                        echo "✅ 成功建立視圖: $viewName\n";
                    } elseif (stripos($statement, 'INSERT INTO') !== false) {
                        preg_match('/INSERT INTO.*?`(\w+)`/i', $statement, $matches);
                        $tableName = $matches[1] ?? '未知表';
                        echo "✅ 成功插入資料到: $tableName\n";
                    }
                } else {
                    $errorCount++;
                    echo "⚠️ 執行語句時發生錯誤: " . $conn->error . "\n";
                }
            } elseif (stripos($statement, 'SELECT') !== false) {
                $result = $conn->query($statement);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        foreach ($row as $key => $value) {
                            echo "📢 $key: $value\n";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ 錯誤: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "執行完成！\n";
    echo "✅ 成功: $successCount 項\n";
    if ($errorCount > 0) {
        echo "❌ 錯誤: $errorCount 項\n";
    }
    echo "========================================\n\n";
    
    // 驗證資料表
    echo "驗證資料表...\n";
    $tables = ['rag_knowledge_base', 'rag_categories'];
    
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
            $row = $count->fetch_assoc();
            echo "✅ 資料表 '$table' 存在，共有 {$row['cnt']} 筆資料\n";
        } else {
            echo "❌ 資料表 '$table' 不存在\n";
        }
    }
    
    // 檢查視圖
    $checkView = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_ollama = 'rag_knowledge_view'");
    if ($checkView && $checkView->num_rows > 0) {
        echo "✅ 視圖 'rag_knowledge_view' 存在\n";
    }
    
    $conn->close();
    
    echo "\n";
    echo "🎉 RAG 知識庫設置完成！\n";
    echo "\n";
    echo "下一步：\n";
    echo "1. 可以在資料庫中添加更多知識資料\n";
    echo "2. 系統會自動搜索這個資料表\n";
    echo "3. 使用小助手聊天功能測試\n";
    echo "4. 訪問 test_rag_system.php 進行完整測試\n";
    
} catch (Exception $e) {
    echo "❌ 發生錯誤: " . $e->getMessage() . "\n";
    echo "錯誤詳情: " . $e->getTraceAsString() . "\n";
}

echo "</pre>
</div>
</body>
</html>";
?>

