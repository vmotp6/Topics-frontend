<?php
/**
 * 修復缺失的 3NF 正規化表
 * 專門用於創建執行過程中缺失的表和約束
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
    <title>修復缺失的 3NF 表</title>
    <style>
        body { font-family: 'Microsoft JhengHei', Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; padding: 10px; background: #d5f4e6; border-left: 4px solid #27ae60; margin: 10px 0; }
        .error { color: #e74c3c; padding: 10px; background: #fadbd8; border-left: 4px solid #e74c3c; margin: 10px 0; }
        .info { color: #3498db; padding: 10px; background: #ebf5fb; border-left: 4px solid #3498db; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 修復缺失的 3NF 正規化表</h1>

<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    if (!isset($_GET['execute'])) {
        echo "<div class='info'>";
        echo "<p>此腳本會創建缺失的表和外鍵約束：</p>";
        echo "<ul>";
        echo "<li>message_types（訊息類型表）</li>";
        echo "<li>role_types（角色類型表）</li>";
        echo "<li>student_normalized（正規化學生表）</li>";
        echo "<li>teacher_normalized（正規化老師表）</li>";
        echo "<li>chat_groups_normalized（正規化聊天群組表）</li>";
        echo "<li>group_members_normalized（正規化群組成員表）</li>";
        echo "<li>private_chat_history_normalized（正規化私聊訊息表）</li>";
        echo "<li>group_messages_normalized（正規化群組訊息表）</li>";
        echo "<li>以及所有缺失的外鍵約束</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=1' class='btn'>✅ 執行修復</a>";
        echo "</p>";
        exit;
    }
    
    $sql_file = __DIR__ . '/../database/fix_missing_3nf_tables.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("找不到 SQL 文件: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    $sql_content = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql_content);
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 移除註釋和空行
    $lines = explode("\n", $sql_content);
    $cleaned_lines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && !preg_match('/^--/', $trimmed)) {
            $cleaned_lines[] = $line;
        }
    }
    $sql_content = implode("\n", $cleaned_lines);
    
    // 處理 PREPARE/EXECUTE 語句塊（需要作為一個整體執行）
    $sql_content = preg_replace_callback(
        '/(SET\s+@\w+\s*=.*?;.*?DEALLOCATE\s+PREPARE\s+\w+\s*;)/is',
        function($matches) {
            return str_replace(';', '|||STMT_END|||', $matches[0]);
        },
        $sql_content
    );
    
    // 分割 SQL 語句
    $statements = explode(';', $sql_content);
    
    $success = 0;
    foreach ($statements as $sql) {
        // 恢復預處理語句中的分號
        $sql = str_replace('|||STMT_END|||', ';', $sql);
        $sql = trim($sql);
        
        if (empty($sql) || preg_match('/^SELECT.*AS message/i', $sql)) {
            continue;
        }
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor(); // 關閉游標，釋放結果集
            $success++;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            // 忽略已存在的錯誤
            if (strpos($error_msg, 'already exists') === false && 
                strpos($error_msg, 'Duplicate') === false &&
                strpos($error_msg, "doesn't exist") === false) {
                echo "<div class='error'>❌ 錯誤: " . htmlspecialchars(substr($error_msg, 0, 200)) . "</div>";
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<div class='success'>✅ 修復完成！執行 $success 個語句</div>";
    echo "<p><a href='verify_3nf_compliance.php' class='btn'>📊 驗證結果</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

    </div>
</body>
</html>

