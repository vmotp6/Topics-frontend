<?php
// 檢查teacher表的實際結構
echo "<h1>Teacher表結構檢查</h1>";

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
    
    // 顯示teacher表的完整結構
    echo "<h2>Teacher表結構</h2>";
    $stmt = $pdo->query("DESCRIBE teacher");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>欄位名</th><th>類型</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 顯示teacher表的樣本數據
    echo "<h2>Teacher表樣本數據</h2>";
    $stmt = $pdo->query("SELECT * FROM teacher LIMIT 5");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($teachers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($teachers[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        
        foreach ($teachers as $teacher) {
            echo "<tr>";
            foreach ($teacher as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>teacher表中沒有數據</p>";
    }
    
    // 檢查user表結構
    echo "<h2>User表結構</h2>";
    $stmt = $pdo->query("DESCRIBE user");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>欄位名</th><th>類型</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($userColumns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 顯示user表的樣本數據
    echo "<h2>User表樣本數據</h2>";
    $stmt = $pdo->query("SELECT * FROM user WHERE role = '老師' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>user表中沒有教師數據</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ 數據庫連接失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>下一步</h2>";
echo "<p>請根據上面的表結構信息，告訴我teacher表的實際欄位名稱，我將修正程式碼。</p>";
echo "<p>特別是：</p>";
echo "<ul>";
echo "<li>教師姓名的欄位名稱是什麼？</li>";
echo "<li>科系的欄位名稱是什麼？</li>";
echo "<li>關聯到user表的欄位名稱是什麼？</li>";
echo "</ul>";
?>

