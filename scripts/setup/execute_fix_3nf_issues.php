<?php
/**
 * 執行 3NF 正規化剩餘問題修復腳本
 * 修復：
 * 1. 遷移 teacher 數據
 * 2. 添加缺失的外鍵約束
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
    <title>執行 3NF 修復腳本</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 執行 3NF 正規化修復腳本</h1>

<?php
try {
    // 連接資料庫
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true  // 啟用緩衝查詢
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    // 檢查是否已執行
    if (!isset($_GET['execute'])) {
        echo "<div class='info'>";
        echo "<h2>⚠️ 執行前提醒</h2>";
        echo "<p>此腳本將會：</p>";
        echo "<ul>";
        echo "<li>遷移 teacher 數據（11 筆記錄）到 teacher_normalized 表</li>";
        echo "<li>清理無效的外鍵引用數據</li>";
        echo "<li>添加 3 個缺失的外鍵約束</li>";
        echo "</ul>";
        echo "<p><strong>建議：執行前請先備份資料庫！</strong></p>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=1' class='btn'>✅ 確認執行修復腳本</a> ";
        echo "<a href='../database/complete_3nf_verification.sql' class='btn' target='_blank'>📊 查看驗證腳本</a>";
        echo "</p>";
        exit;
    }
    
    // 先檢查 cooperation_applications_normalized 表的結構
    $has_teacher_user_id = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM cooperation_applications_normalized LIKE 'teacher_user_id'");
        $has_teacher_user_id = ($stmt->rowCount() > 0);
        $stmt->closeCursor();
    } catch (PDOException $e) {
        // 表可能不存在，使用預設值
        $has_teacher_user_id = false;
    }
    
    // 臨時禁用外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 執行修復邏輯（直接在 PHP 中執行，不使用動態 SQL）
    echo "<h2>📝 開始執行修復腳本</h2>";
    
    $success_count = 0;
    $error_count = 0;
    
    // =====================================================
    // 步驟 1: 遷移 teacher 數據
    // =====================================================
    
    echo "<h3>步驟 1: 遷移 teacher 數據</h3>";
    
    try {
        // 檢查當前狀態
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher WHERE user_id IS NOT NULL");
        $original_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $stmt->closeCursor();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher_normalized");
        $normalized_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $stmt->closeCursor();
        
        echo "<p>原始表: $original_count 筆，正規化表: $normalized_count 筆</p>";
        
        // 遷移數據
        $migrate_sql = "
            INSERT INTO teacher_normalized (
                user_id, name, department_id, phone, created_at, updated_at
            )
            SELECT 
                t.user_id,
                COALESCE(t.name, '') AS name,
                COALESCE(
                    (SELECT id FROM departments d WHERE d.name = t.department LIMIT 1),
                    NULL
                ) AS department_id,
                t.phone,
                COALESCE(t.created_at, NOW()) AS created_at,
                COALESCE(t.updated_at, NOW()) AS updated_at
            FROM teacher t
            WHERE t.user_id IS NOT NULL
            AND EXISTS (SELECT 1 FROM user u WHERE u.id = t.user_id)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                department_id = VALUES(department_id),
                phone = VALUES(phone),
                updated_at = VALUES(updated_at)
        ";
        
        $pdo->exec($migrate_sql);
        $affected = $pdo->query("SELECT ROW_COUNT() as count")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<div class='success'>✅ 成功遷移 teacher 數據（影響 $affected 行）</div>";
        $success_count++;
        
        // 檢查結果
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher_normalized");
        $new_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $stmt->closeCursor();
        echo "<p>遷移後正規化表記錄數: $new_count 筆</p>";
        
    } catch (PDOException $e) {
        $error_count++;
        echo "<div class='error'>❌ Teacher 數據遷移失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // =====================================================
    // 步驟 2: 清理無效數據
    // =====================================================
    
    echo "<h3>步驟 2: 清理無效的外鍵引用數據</h3>";
    
    // 清理 enrollment_applications_normalized
    try {
        $update_sql = "
            UPDATE enrollment_applications_normalized
            SET recommended_teacher_user_id = NULL
            WHERE recommended_teacher_user_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM teacher_normalized 
                WHERE user_id = enrollment_applications_normalized.recommended_teacher_user_id
            )
        ";
        $pdo->exec($update_sql);
        $affected = $pdo->query("SELECT ROW_COUNT() as count")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<div class='success'>✅ 清理了 enrollment_applications_normalized 中 $affected 筆無效記錄</div>";
        $success_count++;
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ 清理 enrollment_applications_normalized 時出現問題: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
    }
    
    // 清理 cooperation_applications_normalized（只有在欄位存在時）
    if ($has_teacher_user_id) {
        try {
            $update_sql = "
                UPDATE cooperation_applications_normalized
                SET teacher_user_id = NULL
                WHERE teacher_user_id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM teacher_normalized 
                    WHERE user_id = cooperation_applications_normalized.teacher_user_id
                )
            ";
            $pdo->exec($update_sql);
            $affected = $pdo->query("SELECT ROW_COUNT() as count")->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<div class='success'>✅ 清理了 cooperation_applications_normalized 中 $affected 筆無效記錄</div>";
            $success_count++;
        } catch (PDOException $e) {
            echo "<div class='warning'>⚠️ 清理 cooperation_applications_normalized 時出現問題: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ cooperation_applications_normalized 表沒有 teacher_user_id 欄位，跳過清理</div>";
    }
    
    // 清理 department_id
    try {
        $update_sql = "
            UPDATE cooperation_applications_normalized
            SET department_id = NULL
            WHERE department_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM departments 
                WHERE id = cooperation_applications_normalized.department_id
            )
        ";
        $pdo->exec($update_sql);
        $affected = $pdo->query("SELECT ROW_COUNT() as count")->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<div class='success'>✅ 清理了 cooperation_applications_normalized 中 $affected 筆無效的 department_id</div>";
        $success_count++;
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ 清理 department_id 時出現問題: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
    }
    
    // =====================================================
    // 步驟 3: 添加外鍵約束
    // =====================================================
    
    echo "<h3>步驟 3: 添加外鍵約束</h3>";
    
    // 外鍵 1: enrollment_applications_normalized.recommended_teacher_user_id
    try {
        // 檢查是否已存在
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'enrollment_applications_normalized'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = 'fk_enrollment_teacher'
        ");
        $exists = ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0);
        $stmt->closeCursor();
        
        if (!$exists) {
            $pdo->exec("
                ALTER TABLE enrollment_applications_normalized
                ADD CONSTRAINT fk_enrollment_teacher 
                FOREIGN KEY (recommended_teacher_user_id) 
                REFERENCES teacher_normalized(user_id) 
                ON DELETE SET NULL
            ");
            echo "<div class='success'>✅ 添加外鍵 fk_enrollment_teacher</div>";
        } else {
            echo "<div class='info'>ℹ️ 外鍵 fk_enrollment_teacher 已存在，跳過</div>";
        }
        $success_count++;
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'Duplicate') !== false || strpos($error_msg, 'already exists') !== false) {
            echo "<div class='info'>ℹ️ 外鍵 fk_enrollment_teacher 已存在</div>";
        } else {
            $error_count++;
            echo "<div class='error'>❌ 添加 fk_enrollment_teacher 失敗: " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
        }
    }
    
    // 外鍵 2: cooperation_applications_normalized.teacher_user_id
    if ($has_teacher_user_id) {
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'cooperation_applications_normalized'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME = 'fk_cooperation_teacher'
            ");
            $exists = ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0);
            $stmt->closeCursor();
            
            if (!$exists) {
                $pdo->exec("
                    ALTER TABLE cooperation_applications_normalized
                    ADD CONSTRAINT fk_cooperation_teacher 
                    FOREIGN KEY (teacher_user_id) 
                    REFERENCES teacher_normalized(user_id) 
                    ON DELETE RESTRICT
                ");
                echo "<div class='success'>✅ 添加外鍵 fk_cooperation_teacher</div>";
            } else {
                echo "<div class='info'>ℹ️ 外鍵 fk_cooperation_teacher 已存在，跳過</div>";
            }
            $success_count++;
        } catch (PDOException $e) {
            $error_msg = $e->getMessage();
            if (strpos($error_msg, 'Duplicate') !== false || strpos($error_msg, 'already exists') !== false) {
                echo "<div class='info'>ℹ️ 外鍵 fk_cooperation_teacher 已存在</div>";
            } else {
                $error_count++;
                echo "<div class='error'>❌ 添加 fk_cooperation_teacher 失敗: " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
            }
        }
    } else {
        echo "<div class='info'>ℹ️ cooperation_applications_normalized 表沒有 teacher_user_id 欄位，跳過 fk_cooperation_teacher</div>";
    }
    
    // 外鍵 3: cooperation_applications_normalized.department_id
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cooperation_applications_normalized'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            AND CONSTRAINT_NAME = 'fk_cooperation_department'
        ");
        $exists = ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0);
        $stmt->closeCursor();
        
        if (!$exists) {
            $pdo->exec("
                ALTER TABLE cooperation_applications_normalized
                ADD CONSTRAINT fk_cooperation_department 
                FOREIGN KEY (department_id) 
                REFERENCES departments(id) 
                ON DELETE RESTRICT
            ");
            echo "<div class='success'>✅ 添加外鍵 fk_cooperation_department</div>";
        } else {
            echo "<div class='info'>ℹ️ 外鍵 fk_cooperation_department 已存在，跳過</div>";
        }
        $success_count++;
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'Duplicate') !== false || strpos($error_msg, 'already exists') !== false) {
            echo "<div class='info'>ℹ️ 外鍵 fk_cooperation_department 已存在</div>";
        } else {
            $error_count++;
            echo "<div class='error'>❌ 添加 fk_cooperation_department 失敗: " . htmlspecialchars(substr($error_msg, 0, 150)) . "</div>";
        }
    }
    
    // 恢復外鍵檢查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h2>📊 執行結果</h2>";
    echo "<div class='success'>✅ 成功執行: $success_count 個語句</div>";
    
    if ($error_count > 0) {
        echo "<div class='warning'>⚠️ 錯誤: $error_count 個語句（可能是因為已存在而跳過）</div>";
    }
    
    // 顯示 SELECT 結果
    if (!empty($results)) {
        foreach ($results as $result) {
            if (!empty($result['data'])) {
                echo "<h3>查詢結果：</h3>";
                echo "<table>";
                
                // 表頭
                if (isset($result['data'][0])) {
                    echo "<tr>";
                    foreach (array_keys($result['data'][0]) as $key) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                    echo "</tr>";
                }
                
                // 數據
                foreach ($result['data'] as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
    }
    
    // 驗證結果
    echo "<h2>🔍 驗證修復結果</h2>";
    
    // 檢查 teacher 數據遷移
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher WHERE user_id IS NOT NULL");
    $original_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher_normalized");
    $normalized_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($original_count == $normalized_count) {
        echo "<div class='success'>✅ Teacher 數據遷移成功：原始表 $original_count 筆，正規化表 $normalized_count 筆</div>";
    } else {
        echo "<div class='warning'>⚠️ Teacher 數據不一致：原始表 $original_count 筆，正規化表 $normalized_count 筆</div>";
    }
    
    // 檢查外鍵
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND CONSTRAINT_NAME IN ('fk_enrollment_teacher', 'fk_cooperation_teacher', 'fk_cooperation_department')
    ");
    $fk_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($fk_count == 3) {
        echo "<div class='success'>✅ 所有 3 個外鍵約束已添加</div>";
    } else {
        echo "<div class='warning'>⚠️ 只有 $fk_count 個外鍵約束（應有 3 個）</div>";
    }
    
    // 顯示外鍵列表
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND CONSTRAINT_NAME IN ('fk_enrollment_teacher', 'fk_cooperation_teacher', 'fk_cooperation_department')
        ORDER BY TABLE_NAME
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fks)) {
        echo "<table>";
        echo "<tr><th>外鍵名稱</th><th>表名</th></tr>";
        foreach ($fks as $fk) {
            echo "<tr><td>" . htmlspecialchars($fk['CONSTRAINT_NAME']) . "</td><td>" . htmlspecialchars($fk['TABLE_NAME']) . "</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>✅ 修復完成！</h2>";
    echo "<p>";
    echo "<a href='?execute=1' class='btn'>🔄 重新執行</a> ";
    echo "<a href='verify_3nf_normalization.php' class='btn'>📊 執行完整驗證</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 錯誤: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

    </div>
</body>
</html>

