<?php
// 測試teacher表的連接和數據
echo "<h1>Teacher表測試</h1>";

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ 數據庫連接成功</p>";
    
    // 檢查teacher表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'teacher'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ teacher表存在</p>";
    } else {
        echo "<p style='color: red;'>✗ teacher表不存在</p>";
        exit;
    }
    
    // 檢查teacher表結構
    echo "<h2>Teacher表結構</h2>";
    $stmt = $pdo->query("DESCRIBE teacher");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>欄位名</th><th>類型</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 檢查teacher表數據
    echo "<h2>Teacher表數據</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>教師表中有 $teacherCount 個教師</p>";
    
    if ($teacherCount > 0) {
        // 顯示前10個教師
        $stmt = $pdo->query("SELECT * FROM teacher LIMIT 10");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Department</th></tr>";
        foreach ($teachers as $teacher) {
            echo "<tr>";
            echo "<td>{$teacher['id']}</td>";
            echo "<td>{$teacher['user_id']}</td>";
            echo "<td>{$teacher['name']}</td>";
            echo "<td>{$teacher['department']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 測試與user表的關聯
    echo "<h2>Teacher與User表關聯測試</h2>";
    try {
        $stmt = $pdo->query("SELECT t.name, t.department, u.username, u.role 
                             FROM teacher t 
                             JOIN user u ON t.user_id = u.id 
                             WHERE u.role = '老師' 
                             LIMIT 5");
        $teacherUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($teacherUsers) > 0) {
            echo "<p style='color: green;'>✓ Teacher與User表關聯正常</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>教師姓名</th><th>科系</th><th>用戶名</th><th>角色</th></tr>";
            foreach ($teacherUsers as $tu) {
                echo "<tr>";
                echo "<td>{$tu['name']}</td>";
                echo "<td>{$tu['department']}</td>";
                echo "<td>{$tu['username']}</td>";
                echo "<td>{$tu['role']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ 沒有找到符合條件的教師用戶</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Teacher與User表關聯失敗: " . $e->getMessage() . "</p>";
    }
    
    // 測試chat.php中使用的查詢
    echo "<h2>Chat.php查詢測試</h2>";
    
    // 測試獲取所有老師的查詢
    try {
        $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username, '老師' as contact_type
                              FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.role = '老師'
                              ORDER BY t.name");
        $stmt->execute();
        $allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ 獲取所有老師查詢成功，找到 " . count($allTeachers) . " 個老師</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 獲取所有老師查詢失敗: " . $e->getMessage() . "</p>";
    }
    
    // 測試獲取特定老師科系的查詢
    try {
        $stmt = $pdo->prepare("SELECT t.department FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              WHERE u.username = ? AND u.role = '老師'");
        $stmt->execute(['test_teacher']); // 使用測試用戶名
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✓ 獲取老師科系查詢成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 獲取老師科系查詢失敗: " . $e->getMessage() . "</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ 數據庫連接失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>測試結果</h2>";
echo "<p>如果所有測試都通過，您的teacher表設置正確，可以正常使用聊天功能。</p>";
echo "<p><a href='chat.php'>前往聊天室</a></p>";
echo "<p><a href='test_chat_connection.php'>運行完整測試</a></p>";
?>

