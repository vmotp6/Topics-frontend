<?php
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

// 檢查是否為POST請求（確認刪除）
$isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $_POST['action'] ?? '';
$tablesToDelete = $_POST['tables'] ?? [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>刪除未使用的資料表</h2>";
    
    if ($isPostRequest && $action === 'delete' && !empty($tablesToDelete)) {
        // 執行刪除操作
        echo "<h3>正在刪除資料表...</h3>";
        
        $deletedTables = [];
        $failedTables = [];
        
        foreach ($tablesToDelete as $table) {
            try {
                // 檢查表是否存在
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    // 檢查表是否為空
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($count == 0) {
                        // 刪除表
                        $pdo->exec("DROP TABLE `$table`");
                        $deletedTables[] = $table;
                        echo "<p style='color: green;'>✅ 成功刪除資料表：{$table}</p>";
                    } else {
                        $failedTables[] = "$table (表不為空，有 {$count} 筆記錄)";
                        echo "<p style='color: red;'>❌ 無法刪除資料表：{$table} (表不為空)</p>";
                    }
                } else {
                    $failedTables[] = "$table (表不存在)";
                    echo "<p style='color: orange;'>⚠️ 資料表不存在：{$table}</p>";
                }
            } catch (Exception $e) {
                $failedTables[] = "$table (錯誤: {$e->getMessage()})";
                echo "<p style='color: red;'>❌ 刪除資料表失敗：{$table} - {$e->getMessage()}</p>";
            }
        }
        
        echo "<h3>刪除結果摘要</h3>";
        if (!empty($deletedTables)) {
            echo "<p style='color: green;'>✅ 成功刪除 " . count($deletedTables) . " 個資料表：</p>";
            echo "<ul>";
            foreach ($deletedTables as $table) {
                echo "<li>{$table}</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($failedTables)) {
            echo "<p style='color: red;'>❌ 刪除失敗 " . count($failedTables) . " 個資料表：</p>";
            echo "<ul>";
            foreach ($failedTables as $table) {
                echo "<li>{$table}</li>";
            }
            echo "</ul>";
        }
        
        echo "<p><a href='analyze_database_tables.php'>← 返回資料表分析</a></p>";
        
    } else {
        // 顯示確認頁面
        echo "<h3>⚠️ 重要警告</h3>";
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin-bottom: 20px;'>";
        echo "<p><strong>刪除資料表是不可逆的操作！</strong></p>";
        echo "<p>在執行刪除操作前，請務必：</p>";
        echo "<ul>";
        echo "<li>備份整個資料庫</li>";
        echo "<li>確認要刪除的資料表確實沒有被使用</li>";
        echo "<li>在測試環境中先測試</li>";
        echo "</ul>";
        echo "</div>";
        
        // 獲取所有資料表
        $stmt = $pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 定義核心聊天系統使用的資料表（不應該刪除）
        $coreChatTables = [
            'user', 'teacher02', 'private_chat_history', 
            'group_chats', 'group_chat_members', 'group_chat_messages', 
            'ai_chat_history'
        ];
        
        // 定義系統資料表（不應該刪除）
        $systemTables = ['mysql', 'performance_schema', 'phpmyadmin', 'test'];
        
        // 找出可能可以刪除的資料表
        $potentialDeletableTables = [];
        
        foreach ($allTables as $table) {
            if (!in_array($table, $coreChatTables) && !in_array($table, $systemTables)) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($count == 0) {
                        $potentialDeletableTables[] = [
                            'name' => $table,
                            'count' => $count,
                            'description' => getTableDescription($table)
                        ];
                    }
                } catch (Exception $e) {
                    // 忽略錯誤
                }
            }
        }
        
        if (empty($potentialDeletableTables)) {
            echo "<p style='color: green;'>✅ 沒有發現可以安全刪除的資料表</p>";
            echo "<p><a href='analyze_database_tables.php'>← 返回資料表分析</a></p>";
        } else {
            echo "<h3>可以安全刪除的資料表</h3>";
            echo "<p>以下資料表為空且不在核心系統中，可以安全刪除：</p>";
            
            echo "<form method='POST' onsubmit='return confirm(\"確定要刪除選中的資料表嗎？此操作不可逆！\");'>";
            echo "<input type='hidden' name='action' value='delete'>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #f8f9fa;'>";
            echo "<th style='padding: 10px;'><input type='checkbox' id='selectAll' onclick='toggleAll(this)'> 全選</th>";
            echo "<th style='padding: 10px;'>資料表名稱</th>";
            echo "<th style='padding: 10px;'>記錄數</th>";
            echo "<th style='padding: 10px;'>描述</th>";
            echo "</tr>";
            
            foreach ($potentialDeletableTables as $table) {
                echo "<tr>";
                echo "<td style='padding: 10px;'><input type='checkbox' name='tables[]' value='{$table['name']}'></td>";
                echo "<td style='padding: 10px;'>{$table['name']}</td>";
                echo "<td style='padding: 10px;'>{$table['count']}</td>";
                echo "<td style='padding: 10px;'>{$table['description']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<div style='margin-top: 20px;'>";
            echo "<button type='submit' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "刪除選中的資料表";
            echo "</button>";
            echo " <a href='analyze_database_tables.php' style='background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>取消</a>";
            echo "</div>";
            
            echo "</form>";
        }
    }
    
} catch(PDOException $e) {
    echo "<h3>❌ 資料庫連接失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}

function getTableDescription($tableName) {
    $descriptions = [
        'teacher' => '舊版教師資料表（可能已被 teacher02 取代）',
        'users' => '舊版用戶資料表（可能已被 user 取代）',
        'chat_history' => '舊版聊天記錄表（可能已被 private_chat_history 取代）',
        'messages' => '舊版訊息表（可能已被其他表取代）'
    ];
    
    return $descriptions[$tableName] ?? '其他資料表';
}
?>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="tables[]"]');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}
</script>

