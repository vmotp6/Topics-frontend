<?php
/**
 * 建立國中學校招生申請表
 * 執行此腳本會自動建立資料表和相關觸發器
 */

// 資料庫連接配置
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "<h1>🔧 建立國中學校招生申請表</h1>";
    echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
    
    // 讀取 SQL 檔案
    $sqlFile = __DIR__ . '/../database/create_junior_school_recruitment_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("找不到 SQL 檔案: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // 移除註解和空行（簡化處理）
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // 分割 SQL 語句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^(USE|DELIMITER)/i', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            // 處理 CREATE TRIGGER（需要特殊處理）
            if (preg_match('/CREATE\s+TRIGGER/i', $statement)) {
                // 觸發器需要單獨執行
                $pdo->exec($statement);
                echo "<p style='color: green;'>✅ 觸發器建立成功</p>";
                $successCount++;
            } else {
                $pdo->exec($statement);
                if (preg_match('/CREATE\s+TABLE/i', $statement)) {
                    echo "<p style='color: green;'>✅ 資料表建立成功</p>";
                } elseif (preg_match('/CREATE\s+(OR\s+REPLACE\s+)?VIEW/i', $statement)) {
                    echo "<p style='color: green;'>✅ 視圖建立成功</p>";
                }
                $successCount++;
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // 如果是表已存在的錯誤，視為成功
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false) {
                echo "<p style='color: orange;'>⚠️ " . htmlspecialchars($errorMsg) . "</p>";
                $successCount++;
                $errorCount--;
            } else {
                $errors[] = $errorMsg;
                echo "<p style='color: red;'>❌ 錯誤: " . htmlspecialchars($errorMsg) . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h2>📊 執行結果</h2>";
    echo "<p><strong>成功:</strong> $successCount 個操作</p>";
    echo "<p><strong>錯誤:</strong> $errorCount 個操作</p>";
    
    if (!empty($errors)) {
        echo "<h3>❌ 錯誤詳情:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: red;'>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    // 檢查表是否存在
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'junior_school_recruitment_applications'");
        if ($stmt->rowCount() > 0) {
            echo "<h3>✅ 驗證結果</h3>";
            echo "<p style='color: green;'>資料表 'junior_school_recruitment_applications' 已存在</p>";
            
            // 顯示表結構
            $stmt = $pdo->query("DESCRIBE junior_school_recruitment_applications");
            $columns = $stmt->fetchAll();
            
            echo "<h4>📋 表結構:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #e0e0e0;'><th>欄位名稱</th><th>類型</th><th>Null</th><th>Key</th><th>預設值</th><th>備註</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($column['Extra'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 檢查觸發器
            $stmt = $pdo->query("SHOW TRIGGERS LIKE 'junior_school_recruitment_applications'");
            $triggers = $stmt->fetchAll();
            
            if (!empty($triggers)) {
                echo "<h4>🔧 觸發器:</h4>";
                echo "<ul>";
                foreach ($triggers as $trigger) {
                    echo "<li>" . htmlspecialchars($trigger['Trigger']) . " (" . htmlspecialchars($trigger['Event']) . ")</li>";
                }
                echo "</ul>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ 驗證失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
    echo "<br><a href='../database/create_junior_school_recruitment_table.sql' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>查看 SQL 腳本</a>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>";
    echo "<h2>❌ 資料庫連接錯誤</h2>";
    echo "<p><strong>錯誤訊息:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>請檢查資料庫連接設定。</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px;'>";
    echo "<h2>❌ 執行錯誤</h2>";
    echo "<p><strong>錯誤訊息:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>










