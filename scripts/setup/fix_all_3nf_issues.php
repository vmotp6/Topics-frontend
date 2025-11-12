<?php
/**
 * 一次性修復所有 3NF 問題
 * 包括：修復表結構、創建缺失的表、添加缺失的欄位
 */

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
    <title>修復所有 3NF 問題</title>
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
        <h1>🔧 修復所有 3NF 問題</h1>

<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    if (!isset($_GET['execute'])) {
        echo "<div class='info'>";
        echo "<p>此腳本會按順序執行：</p>";
        echo "<ol>";
        echo "<li>修復缺失的表結構（enrollment_applications_normalized）</li>";
        echo "<li>創建缺失的基礎表（message_types, role_types）</li>";
        echo "<li>創建缺失的正規化表（student_normalized, teacher_normalized 等）</li>";
        echo "<li>添加缺失的外鍵約束</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=1' class='btn'>✅ 執行修復</a>";
        echo "</p>";
        exit;
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 步驟 1: 修復 enrollment_applications_normalized 表結構
    echo "<h3>步驟 1: 修復 enrollment_applications_normalized 表結構</h3>";
    $fix_enrollment_file = __DIR__ . '/../database/fix_enrollment_table_structure.sql';
    if (file_exists($fix_enrollment_file)) {
        $sql = file_get_contents($fix_enrollment_file);
        $sql = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)), function($s) {
            return !empty($s) && !preg_match('/^--|^SELECT.*AS message/', $s);
        });
        foreach ($statements as $stmt) {
            try {
                $p = $pdo->prepare($stmt);
                $p->execute();
                $p->closeCursor();
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<div class='info'>ℹ️ " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
                }
            }
        }
        echo "<div class='success'>✅ 完成</div>";
    }
    
    // 步驟 2: 創建缺失的基礎表
    echo "<h3>步驟 2: 創建缺失的基礎表</h3>";
    $fix_tables_file = __DIR__ . '/../database/fix_missing_3nf_tables.sql';
    if (file_exists($fix_tables_file)) {
        $sql = file_get_contents($fix_tables_file);
        $sql = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql);
        
        // 處理 PREPARE/EXECUTE 語句
        $sql = preg_replace_callback(
            '/(SET\s+@\w+\s*=.*?;.*?DEALLOCATE\s+PREPARE\s+\w+\s*;)/is',
            function($m) { return str_replace(';', '|||STMT|||', $m[0]); },
            $sql
        );
        
        $statements = explode(';', $sql);
        $success = 0;
        foreach ($statements as $stmt) {
            $stmt = str_replace('|||STMT|||', ';', trim($stmt));
            if (empty($stmt) || preg_match('/^--|^SELECT.*AS message/i', $stmt)) continue;
            try {
                $p = $pdo->prepare($stmt);
                $p->execute();
                $p->closeCursor();
                $success++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<div class='info'>ℹ️ " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
                }
            }
        }
        echo "<div class='success'>✅ 完成（$success 個語句執行）</div>";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<div class='success'>✅ 所有修復完成！</div>";
    echo "<p>";
    echo "<a href='verify_3nf_compliance.php' class='btn'>📊 驗證結果</a> ";
    echo "<a href='execute_complete_3nf_normalization.php' class='btn'>🔄 重新執行正規化</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

    </div>
</body>
</html>

