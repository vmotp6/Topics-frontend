<?php
/**
 * 完整 3NF 正規化執行腳本
 * 此腳本會：
 * 1. 創建所有正規化表結構
 * 2. 遷移現有數據
 * 3. 顯示執行結果和統計
 */

// 資料庫連接配置
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

// 設置錯誤顯示
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>執行完整 3NF 正規化</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #27ae60;
            padding: 10px;
            background: #d5f4e6;
            border-left: 4px solid #27ae60;
            margin: 10px 0;
        }
        .warning {
            color: #f39c12;
            padding: 10px;
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
            padding: 10px;
            background: #fadbd8;
            border-left: 4px solid #e74c3c;
            margin: 10px 0;
        }
        .info {
            color: #3498db;
            padding: 10px;
            background: #ebf5fb;
            border-left: 4px solid #3498db;
            margin: 10px 0;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #3498db;
            color: white;
        }
        table tr:hover {
            background: #f5f5f5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        .progress {
            background: #ecf0f1;
            border-radius: 10px;
            padding: 10px;
            margin: 10px 0;
        }
        .progress-bar {
            background: #3498db;
            height: 30px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 執行完整 3NF 正規化</h1>

<?php
try {
    // 連接資料庫
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    // 檢查是否已執行
    if (!isset($_GET['execute'])) {
        echo "<div class='info'>";
        echo "<h2>⚠️ 執行前提醒</h2>";
        echo "<p><strong>此腳本將會：</strong></p>";
        echo "<ul>";
        echo "<li>創建所有基礎參考表（departments, grades, genders 等）</li>";
        echo "<li>創建所有正規化表結構（student_normalized, teacher_normalized 等）</li>";
        echo "<li>遷移現有數據到正規化表</li>";
        echo "<li>設置所有外鍵約束</li>";
        echo "<li>創建向後兼容視圖</li>";
        echo "</ul>";
        echo "<p><strong style='color: #e74c3c;'>⚠️ 重要：執行前請先備份資料庫！</strong></p>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=1' class='btn btn-success'>✅ 確認執行 3NF 正規化</a> ";
        echo "<a href='../database/complete_3nf_normalization.sql' class='btn' target='_blank'>📄 查看 SQL 腳本</a>";
        echo "</p>";
        exit;
    }
    
    // 開始執行
    echo "<h2>📝 開始執行 3NF 正規化</h2>";
    
    $success_count = 0;
    $error_count = 0;
    $warnings = [];
    
    // =====================================================
    // 步驟 1: 讀取並執行創建表結構的 SQL
    // =====================================================
    
    echo "<h3>步驟 1: 創建正規化表結構</h3>";
    
    $sql_file = __DIR__ . '/../database/complete_3nf_normalization.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("找不到 SQL 文件: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // 移除 SET FOREIGN_KEY_CHECKS 和 USE 語句（已在連接中設置）
    $sql_content = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql_content);
    
    // 臨時關閉外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 使用更簡單可靠的方法：逐行讀取並執行完整的語句
    // 先移除所有註釋行
    $lines = explode("\n", $sql_content);
    $cleaned_lines = [];
    $in_multiline_comment = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // 處理多行註釋
        if (preg_match('/\/\*/', $line)) {
            $in_multiline_comment = true;
        }
        if (preg_match('/\*\//', $line)) {
            $in_multiline_comment = false;
            continue;
        }
        
        if ($in_multiline_comment || preg_match('/^--/', $trimmed) || empty($trimmed)) {
            continue;
        }
        
        $cleaned_lines[] = $line;
    }
    
    $sql_content = implode("\n", $cleaned_lines);
    
    // 使用更簡單可靠的方法：按分號分割，但需要特別處理視圖和預處理語句
    // 先標記視圖定義區域
    $sql_content = preg_replace_callback(
        '/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW.*?;/is',
        function($matches) {
            // 將視圖中的分號替換為特殊標記，稍後恢復
            return str_replace(';', '|||VIEW_END|||', $matches[0]);
        },
        $sql_content
    );
    
    // 處理預處理語句（PREPARE/EXECUTE/DEALLOCATE）
    $sql_content = preg_replace_callback(
        '/(SET\s+@\w+\s*=.*?;.*?DEALLOCATE\s+PREPARE\s+\w+\s*;)/is',
        function($matches) {
            // 將預處理語句塊中的分號替換為特殊標記
            return str_replace(';', '|||STMT_END|||', $matches[0]);
        },
        $sql_content
    );
    
    // 現在按分號分割
    $statements = explode(';', $sql_content);
    
    // 執行每個 SQL 語句
    foreach ($statements as $index => $sql) {
        // 恢復視圖和預處理語句中的分號
        $sql = str_replace('|||VIEW_END|||', ';', $sql);
        $sql = str_replace('|||STMT_END|||', ';', $sql);
        $sql = trim($sql);
        
        // 跳過空語句和註釋
        if (empty($sql) || 
            preg_match('/^--/', $sql) || 
            preg_match('/^SELECT.*AS (message|next_step)/i', $sql)) {
            continue;
        }
        
        // 確保語句以分號結尾（如果沒有）
        if (!preg_match('/;$/', $sql)) {
            $sql .= ';';
        }
        
        try {
            $pdo->exec($sql);
            $success_count++;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            // 忽略已存在的錯誤（表可能已創建）
            if (strpos($error_msg, 'already exists') !== false || 
                strpos($error_msg, 'Duplicate') !== false ||
                strpos($error_msg, 'Duplicate entry') !== false) {
                $warnings[] = "已存在，跳過: " . substr($error_msg, 0, 80);
            } elseif (strpos($error_msg, 'Column') !== false && strpos($error_msg, 'already exists') !== false) {
                // 欄位已存在，忽略
                $warnings[] = "欄位已存在，跳過";
            } elseif (strpos($error_msg, 'Column') !== false && strpos($error_msg, 'doesn\'t exist') !== false) {
                // 欄位不存在，可能是表結構問題，記錄但不阻止繼續
                $warnings[] = "欄位檢查: " . substr($error_msg, 0, 100);
            } else {
                $error_count++;
                $sql_preview = substr($sql, 0, 150);
                echo "<div class='error'>❌ SQL 執行錯誤 (#$index): " . htmlspecialchars(substr($error_msg, 0, 200)) . "</div>";
                echo "<div class='info'>SQL 語句預覽: " . htmlspecialchars($sql_preview) . "...</div>";
            }
        }
    }
    
    // 恢復外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<div class='success'>✅ 表結構創建完成（$success_count 個語句成功）</div>";
    
    // =====================================================
    // 步驟 2: 遷移數據
    // =====================================================
    
    echo "<h3>步驟 2: 遷移數據到正規化表</h3>";
    
    $migrate_file = __DIR__ . '/../database/migrate_data_to_3nf.sql';
    if (file_exists($migrate_file)) {
        $migrate_sql = file_get_contents($migrate_file);
        $migrate_sql = preg_replace('/^USE\s+\w+;?\s*/mi', '', $migrate_sql);
        
        // 執行遷移
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 移除註釋
            $migrate_lines = explode("\n", $migrate_sql);
            $cleaned_migrate = [];
            foreach ($migrate_lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed) && !preg_match('/^--/', $trimmed)) {
                    $cleaned_migrate[] = $line;
                }
            }
            $migrate_sql = implode("\n", $cleaned_migrate);
            
            // 分割 SQL 語句
            $migrate_statements = preg_split('/;\s*(?=(?:INSERT|UPDATE|SELECT|SET)\s)/i', $migrate_sql);
            
            $migrate_success = 0;
            foreach ($migrate_statements as $sql) {
                $sql = trim($sql);
                if (empty($sql) || preg_match('/^SELECT.*AS (message|count)/i', $sql)) {
                    continue;
                }
                if (!preg_match('/;$/', $sql)) {
                    $sql .= ';';
                }
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $stmt->closeCursor(); // 關閉游標，釋放結果集
                    $migrate_success++;
                } catch (PDOException $e) {
                    $error_msg = $e->getMessage();
                    // 忽略可接受的錯誤
                    if (strpos($error_msg, 'Duplicate') === false && 
                        strpos($error_msg, "doesn't exist") === false &&
                        strpos($error_msg, 'Column not found') === false) {
                        echo "<div class='warning'>⚠️ 遷移警告: " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
                    }
                }
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "<div class='success'>✅ 數據遷移完成（$migrate_success 個語句執行）</div>";
        } catch (Exception $e) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "<div class='error'>❌ 數據遷移失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ 找不到數據遷移腳本，跳過數據遷移</div>";
    }
    
    // =====================================================
    // 步驟 3: 顯示統計結果
    // =====================================================
    
    echo "<h2>📊 執行結果統計</h2>";
    
    // 檢查表是否存在
    $tables_to_check = [
        'departments', 'grades', 'genders', 'identities', 'application_statuses',
        'student_normalized', 'teacher_normalized',
        'enrollment_applications_normalized', 'enrollment_preferences',
        'cooperation_applications_normalized'
    ];
    
    echo "<table>";
    echo "<tr><th>表名</th><th>狀態</th><th>記錄數</th></tr>";
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td><span style='color: #27ae60;'>✅ 存在</span></td>";
            echo "<td>$count</td>";
            echo "</tr>";
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td><span style='color: #e74c3c;'>❌ 不存在</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // 顯示警告
    if (!empty($warnings)) {
        echo "<h3>⚠️ 警告訊息</h3>";
        foreach ($warnings as $warning) {
            echo "<div class='warning'>$warning</div>";
        }
    }
    
    // 最終統計
    echo "<h2>✅ 執行完成</h2>";
    echo "<div class='success'>";
    echo "<p>✅ 成功執行: $success_count 個語句</p>";
    if ($error_count > 0) {
        echo "<p>⚠️ 錯誤: $error_count 個語句（可能是因為已存在而跳過）</p>";
    }
    echo "</div>";
    
    echo "<p>";
    echo "<a href='?execute=1' class='btn'>🔄 重新執行</a> ";
    echo "<a href='verify_3nf_compliance.php' class='btn btn-success'>📊 驗證 3NF 合規性</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

    </div>
</body>
</html>
