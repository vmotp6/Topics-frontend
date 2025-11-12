<?php
/**
 * 修復 3 個缺失的外鍵約束
 * 1. enrollment_applications_normalized.recommended_teacher_user_id
 * 2. cooperation_applications_normalized.teacher_user_id
 * 3. cooperation_applications_normalized.department_id
 */

// 資料庫連接配置
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修復 3 個外鍵約束</title>
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
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 修復 3 個外鍵約束</h1>

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
        echo "<h2>⚠️ 執行前說明</h2>";
        echo "<p>此腳本將會：</p>";
        echo "<ol>";
        echo "<li>清理無效的外鍵引用數據（設為 NULL）</li>";
        echo "<li>添加以下 3 個外鍵約束：</li>";
        echo "<ul>";
        echo "<li><strong>enrollment_applications_normalized.recommended_teacher_user_id</strong> → teacher_normalized(user_id)</li>";
        echo "<li><strong>cooperation_applications_normalized.teacher_user_id</strong> → teacher_normalized(user_id)</li>";
        echo "<li><strong>cooperation_applications_normalized.department_id</strong> → departments(id)</li>";
        echo "</ul>";
        echo "</ol>";
        echo "<p><strong style='color: #e74c3c;'>⚠️ 注意：執行前請先備份資料庫！</strong></p>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=1' class='btn btn-success'>✅ 確認執行修復</a>";
        echo "</p>";
        exit;
    }
    
    // 開始執行
    echo "<h2>📝 開始修復外鍵約束</h2>";
    
    $sql_file = __DIR__ . '/../database/fix_3_foreign_keys.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("找不到 SQL 文件: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    $sql_content = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql_content);
    
    // 臨時關閉外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 移除註釋
    $lines = explode("\n", $sql_content);
    $cleaned_lines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && !preg_match('/^--/', $trimmed)) {
            $cleaned_lines[] = $line;
        }
    }
    $sql_content = implode("\n", $cleaned_lines);
    
    // 處理 PREPARE/EXECUTE 語句塊
    $sql_content = preg_replace_callback(
        '/(SET\s+@\w+\s*=.*?;.*?DEALLOCATE\s+PREPARE\s+\w+\s*;)/is',
        function($matches) {
            return str_replace(';', '|||STMT_END|||', $matches[0]);
        },
        $sql_content
    );
    
    // 分割 SQL 語句
    $statements = explode(';', $sql_content);
    
    $success_count = 0;
    $error_count = 0;
    $warnings = [];
    
    // 步驟 1: 清理無效數據
    echo "<h3>步驟 1: 清理無效的外鍵引用</h3>";
    
    foreach ($statements as $index => $sql) {
        // 恢復預處理語句中的分號
        $sql = str_replace('|||STMT_END|||', ';', $sql);
        $sql = trim($sql);
        
        if (empty($sql) || preg_match('/^SELECT.*AS (message|外鍵名稱)/i', $sql)) {
            continue;
        }
        
        // 跳過驗證查詢（最後的 SELECT）
        if (preg_match('/SELECT.*FROM information_schema/i', $sql)) {
            continue;
        }
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            
            // 檢查是否為 UPDATE 語句（清理數據）
            if (preg_match('/^UPDATE/i', $sql)) {
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    echo "<div class='info'>ℹ️ 清理了 $affected 筆無效記錄</div>";
                }
            }
            
            $success_count++;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            
            // 忽略已存在的錯誤
            if (strpos($error_msg, 'already exists') !== false || 
                strpos($error_msg, 'Duplicate') !== false) {
                $warnings[] = "已存在，跳過: " . substr($error_msg, 0, 80);
            } else {
                $error_count++;
                echo "<div class='error'>❌ 錯誤: " . htmlspecialchars(substr($error_msg, 0, 200)) . "</div>";
                echo "<div class='info'>SQL: " . htmlspecialchars(substr($sql, 0, 150)) . "...</div>";
            }
        }
    }
    
    // 恢復外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h3>步驟 2: 驗證外鍵約束</h3>";
    
    // 檢查外鍵是否已添加
    $fks_to_check = [
        'fk_enrollment_teacher' => 'enrollment_applications_normalized.recommended_teacher_user_id',
        'fk_cooperation_teacher' => 'cooperation_applications_normalized.teacher_user_id',
        'fk_cooperation_department' => 'cooperation_applications_normalized.department_id'
    ];
    
    echo "<table>";
    echo "<tr><th>外鍵名稱</th><th>表名.欄位名</th><th>狀態</th></tr>";
    
    foreach ($fks_to_check as $fk_name => $description) {
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME = '$fk_name'
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($result['count'] > 0) {
                echo "<tr>";
                echo "<td>$fk_name</td>";
                echo "<td>$description</td>";
                echo "<td><span style='color: #27ae60; font-weight: bold;'>✅ 已設置</span></td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td>$fk_name</td>";
                echo "<td>$description</td>";
                echo "<td><span style='color: #e74c3c; font-weight: bold;'>❌ 未設置</span></td>";
                echo "</tr>";
            }
        } catch (PDOException $e) {
            echo "<tr>";
            echo "<td>$fk_name</td>";
            echo "<td>$description</td>";
            echo "<td><span style='color: #e74c3c;'>❌ 檢查失敗</span></td>";
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
    
    // 最終結果
    echo "<h2>✅ 執行完成</h2>";
    echo "<div class='success'>";
    echo "<p>✅ 成功執行: $success_count 個語句</p>";
    if ($error_count > 0) {
        echo "<p>⚠️ 錯誤: $error_count 個語句</p>";
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

