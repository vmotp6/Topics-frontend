<?php
/**
 * 修復缺失的正規化表
 * 執行完整的 3NF 正規化腳本
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
    <title>修復缺失的正規化表</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 5px; }
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
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    echo "<h1>🔧 修復缺失的正規化表</h1>";
    echo "<div class='info'>執行完整的 3NF 正規化腳本來創建所有缺失的表</div>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // 步驟 1: 執行完整的正規化腳本
    echo "<div class='step'>";
    echo "<h2>步驟 1: 創建所有正規化表結構</h2>";
    
    $sqlFile = __DIR__ . '/../database/complete_normalize_to_3nf.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // 移除註釋
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // 分割 SQL 語句
        $statements = array_filter(
            array_map('trim', preg_split('/;(?=(?:[^\'"]*(?:\'|")[^\'"]*(?:\'|"))*[^\'"]*$)/', $sql)),
            function($stmt) {
                return !empty($stmt) && strlen(trim($stmt)) > 5;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                if (preg_match('/^\s*(SELECT|SHOW)/i', $statement)) {
                    $stmt = $pdo->query($statement);
                    $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                } else {
                    $pdo->exec($statement);
                }
                $successCount++;
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                $ignoredPatterns = [
                    '/already exists/i',
                    '/Duplicate/i',
                    '/Unknown column/i',
                    '/unbuffered/i',
                    '/Duplicate column name/i',
                    '/Duplicate key name/i'
                ];
                
                $shouldIgnore = false;
                foreach ($ignoredPatterns as $pattern) {
                    if (preg_match($pattern, $errorMessage)) {
                        $shouldIgnore = true;
                        break;
                    }
                }
                
                if (!$shouldIgnore && $errorCount < 5) {
                    $errorCount++;
                    echo "<p class='warning'>⚠️ 警告: " . htmlspecialchars(substr($errorMessage, 0, 150)) . "</p>";
                }
            }
        }
        
        echo "<p class='success'>✅ 成功執行 $successCount 個 SQL 語句</p>";
        if ($errorCount > 0) {
            echo "<p class='warning'>⚠️ 遇到 $errorCount 個警告（部分可能是預期的）</p>";
        }
    } else {
        echo "<p class='error'>❌ SQL 文件不存在: $sqlFile</p>";
    }
    echo "</div>";
    
    // 步驟 2: 遷移數據
    echo "<div class='step'>";
    echo "<h2>步驟 2: 遷移數據到正規化表</h2>";
    
    $migrateFile = __DIR__ . '/../database/migrate_all_data_to_3nf.sql';
    
    if (file_exists($migrateFile)) {
        $sql = file_get_contents($migrateFile);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = array_filter(
            array_map('trim', preg_split('/;(?=(?:[^\'"]*(?:\'|")[^\'"]*(?:\'|"))*[^\'"]*$)/', $sql)),
            function($stmt) {
                return !empty($stmt) && strlen(trim($stmt)) > 5;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                if (preg_match('/^\s*(SELECT|SHOW)/i', $statement)) {
                    $stmt = $pdo->query($statement);
                    $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                } else {
                    $pdo->exec($statement);
                }
                $successCount++;
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                $ignoredPatterns = [
                    '/already exists/i',
                    '/Duplicate/i',
                    '/Base table or view not found/i',
                    '/Table.*doesn\'t exist/i'
                ];
                
                $shouldIgnore = false;
                foreach ($ignoredPatterns as $pattern) {
                    if (preg_match($pattern, $errorMessage)) {
                        $shouldIgnore = true;
                        break;
                    }
                }
                
                if (!$shouldIgnore && $errorCount < 10) {
                    $errorCount++;
                    echo "<p class='warning'>⚠️ 警告: " . htmlspecialchars(substr($errorMessage, 0, 150)) . "</p>";
                }
            }
        }
        
        echo "<p class='success'>✅ 成功執行 $successCount 個遷移語句</p>";
        if ($errorCount > 0) {
            echo "<p class='warning'>⚠️ 遇到 $errorCount 個警告（部分可能是預期的）</p>";
        }
    }
    echo "</div>";
    
    // 步驟 3: 創建缺失的 role_types 表
    echo "<div class='step'>";
    echo "<h2>步驟 3: 創建缺失的 role_types 表</h2>";
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS role_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE COMMENT '角色代碼',
                name VARCHAR(100) NOT NULL COMMENT '角色名稱',
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色類型資料表';
            
            INSERT IGNORE INTO role_types (code, name) VALUES
            ('TEACHER', '老師'),
            ('STUDENT', '學生'),
            ('ADMIN', '管理員'),
            ('STAFF', '行政人員'),
            ('MEMBER', '成員');
        ");
        echo "<p class='success'>✅ role_types 表創建成功</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ role_types 表創建警告: " . htmlspecialchars(substr($e->getMessage(), 0, 150)) . "</p>";
    }
    echo "</div>";
    
    // 步驟 4: 修復缺失的外鍵
    echo "<div class='step'>";
    echo "<h2>步驟 4: 修復缺失的外鍵關係</h2>";
    
    $foreignKeys = [
        "ALTER TABLE enrollment_applications_normalized 
         ADD CONSTRAINT fk_enrollment_identity 
         FOREIGN KEY (identity_id) REFERENCES identities(id) ON DELETE RESTRICT",
        
        "ALTER TABLE enrollment_applications_normalized 
         ADD CONSTRAINT fk_enrollment_gender 
         FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE SET NULL",
        
        "ALTER TABLE enrollment_applications_normalized 
         ADD CONSTRAINT fk_enrollment_grade 
         FOREIGN KEY (current_grade_id) REFERENCES grades(id) ON DELETE SET NULL",
        
        "ALTER TABLE enrollment_applications_normalized 
         ADD CONSTRAINT fk_enrollment_teacher 
         FOREIGN KEY (recommended_teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE SET NULL",
        
        "ALTER TABLE cooperation_applications_normalized 
         ADD CONSTRAINT fk_cooperation_teacher 
         FOREIGN KEY (teacher_user_id) REFERENCES teacher_normalized(user_id) ON DELETE RESTRICT",
        
        "ALTER TABLE cooperation_applications_normalized 
         ADD CONSTRAINT fk_cooperation_department 
         FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT"
    ];
    
    $fkSuccess = 0;
    $fkFailed = 0;
    
    foreach ($foreignKeys as $fk) {
        try {
            $pdo->exec($fk);
            $fkSuccess++;
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
            // 忽略已存在的外鍵錯誤
            if (strpos($errorMessage, 'Duplicate foreign key') === false && 
                strpos($errorMessage, 'already exists') === false) {
                $fkFailed++;
                if ($fkFailed <= 3) {
                    echo "<p class='warning'>⚠️ 外鍵創建警告: " . htmlspecialchars(substr($errorMessage, 0, 150)) . "</p>";
                }
            }
        }
    }
    
    echo "<p class='success'>✅ 成功設置 $fkSuccess 個外鍵關係</p>";
    if ($fkFailed > 0) {
        echo "<p class='warning'>⚠️ $fkFailed 個外鍵設置失敗（可能需要先創建被引用的表）</p>";
    }
    echo "</div>";
    
    // 步驟 5: 處理其他使用 username 的表
    echo "<div class='step'>";
    echo "<h2>步驟 5: 處理其他聊天相關表</h2>";
    
    $otherChatTablesFile = __DIR__ . '/../database/normalize_other_chat_tables.sql';
    
    if (file_exists($otherChatTablesFile)) {
        $sql = file_get_contents($otherChatTablesFile);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = array_filter(
            array_map('trim', preg_split('/;(?=(?:[^\'"]*(?:\'|")[^\'"]*(?:\'|"))*[^\'"]*$)/', $sql)),
            function($stmt) {
                return !empty($stmt) && strlen(trim($stmt)) > 5;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                if (preg_match('/^\s*(PREPARE|EXECUTE|DEALLOCATE)/i', $statement)) {
                    // PREPARE/EXECUTE 語句直接執行
                    $pdo->exec($statement);
                } elseif (preg_match('/^\s*(SELECT|SHOW)/i', $statement)) {
                    $stmt = $pdo->query($statement);
                    $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();
                } else {
                    $pdo->exec($statement);
                }
                $successCount++;
            } catch (PDOException $e) {
                $errorMessage = $e->getMessage();
                $ignoredPatterns = [
                    '/already exists/i',
                    '/Duplicate/i',
                    '/Base table or view not found/i',
                    '/Table.*doesn\'t exist/i',
                    '/does not exist, skipping/i'
                ];
                
                $shouldIgnore = false;
                foreach ($ignoredPatterns as $pattern) {
                    if (preg_match($pattern, $errorMessage)) {
                        $shouldIgnore = true;
                        break;
                    }
                }
                
                if (!$shouldIgnore && $errorCount < 5) {
                    $errorCount++;
                    echo "<p class='warning'>⚠️ 警告: " . htmlspecialchars(substr($errorMessage, 0, 150)) . "</p>";
                }
            }
        }
        
        echo "<p class='success'>✅ 成功執行 $successCount 個 SQL 語句</p>";
        if ($errorCount > 0) {
            echo "<p class='warning'>⚠️ 遇到 $errorCount 個警告（部分可能是預期的）</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ 其他聊天表正規化腳本不存在</p>";
    }
    
    echo "</div>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // 步驟 6: 顯示執行總結
    echo "<div class='step'>";
    echo "<h2 class='success'>✅ 修復完成！</h2>";
    echo "<h3>已執行的操作：</h3>";
    echo "<ul>";
    echo "<li>✅ 創建所有正規化表結構</li>";
    echo "<li>✅ 遷移數據到正規化表</li>";
    echo "<li>✅ 創建 role_types 表</li>";
    echo "<li>✅ 設置外鍵關係</li>";
    echo "<li>✅ 正規化其他聊天相關表</li>";
    echo "</ul>";
    echo "<h3>下一步：</h3>";
    echo "<ol>";
    echo "<li>重新執行驗證腳本查看結果</li>";
    echo "<li>檢查數據是否正確遷移</li>";
    echo "<li>測試應用程式功能</li>";
    echo "</ol>";
    echo "<p><a href='verify_3nf_normalization.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>📊 重新驗證正規化狀態</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step'>";
    echo "<h2 class='error'>❌ 資料庫錯誤</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>

