<?php
/**
 * 檢查資料庫表結構
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>資料庫表結構檢查</h1>";
    
    // 檢查 user 表
    echo "<h2>1. user 表結構</h2>";
    $stmt = $pdo->query("DESCRIBE user");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>欄位</th><th>類型</th><th>允許NULL</th><th>鍵</th><th>預設值</th><th>額外</th></tr>";
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
    
    // 檢查 teacher 表
    echo "<h2>2. teacher 表結構</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'teacher'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE teacher");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>欄位</th><th>類型</th><th>允許NULL</th><th>鍵</th><th>預設值</th><th>額外</th></tr>";
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
        
        // 顯示 teacher 表資料
        echo "<h3>teacher 表資料</h3>";
        $stmt = $pdo->query("SELECT * FROM teacher LIMIT 5");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($teachers) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr>";
            foreach (array_keys($teachers[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            foreach ($teachers as $teacher) {
                echo "<tr>";
                foreach ($teacher as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>teacher 表沒有資料</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ teacher 表不存在</p>";
    }
    
    // 檢查 student 表
    echo "<h2>3. student 表結構</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'student'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE student");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>欄位</th><th>類型</th><th>允許NULL</th><th>鍵</th><th>預設值</th><th>額外</th></tr>";
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
    } else {
        echo "<p style='color: red;'>❌ student 表不存在</p>";
    }
    
    // 檢查所有表
    echo "<h2>4. 所有表列表</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h1>❌ 錯誤</h1>";
    echo "<p>資料庫錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>




















