<?php
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>資料庫表格分析報告</h2>";
    
    // 獲取所有資料表
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>1. 所有資料表列表</h3>";
    echo "<p>總共找到 " . count($allTables) . " 個資料表：</p>";
    echo "<ul>";
    foreach ($allTables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
    // 定義聊天系統使用的核心資料表
    $coreChatTables = [
        'user' => '用戶基本資料表',
        'teacher02' => '教師詳細資料表',
        'private_chat_history' => '私聊訊息記錄表',
        'group_chats' => '群聊資料表',
        'group_chat_members' => '群聊成員表',
        'group_chat_messages' => '群聊訊息記錄表',
        'ai_chat_history' => 'AI聊天記錄表'
    ];
    
    // 定義可能相關但需要檢查的資料表
    $potentialRelatedTables = [
        'teacher' => '教師資料表（可能是舊版本）',
        'users' => '用戶資料表（可能是舊版本）',
        'chat_history' => '聊天記錄表（可能是舊版本）',
        'messages' => '訊息表（可能是舊版本）'
    ];
    
    // 定義系統資料表（不應該刪除）
    $systemTables = [
        'mysql' => 'MySQL系統資料表',
        'performance_schema' => '效能監控資料表',
        'phpmyadmin' => 'phpMyAdmin管理資料表',
        'test' => '測試資料表'
    ];
    
    echo "<h3>2. 核心聊天系統資料表分析</h3>";
    $foundCoreTables = [];
    $missingCoreTables = [];
    
    foreach ($coreChatTables as $table => $description) {
        if (in_array($table, $allTables)) {
            $foundCoreTables[$table] = $description;
            echo "<p style='color: green;'>✅ {$table} - {$description}</p>";
        } else {
            $missingCoreTables[$table] = $description;
            echo "<p style='color: red;'>❌ {$table} - {$description} (缺失)</p>";
        }
    }
    
    echo "<h3>3. 可能相關的資料表分析</h3>";
    $foundRelatedTables = [];
    
    foreach ($potentialRelatedTables as $table => $description) {
        if (in_array($table, $allTables)) {
            $foundRelatedTables[$table] = $description;
            echo "<p style='color: orange;'>⚠️ {$table} - {$description} (需要檢查是否使用)</p>";
        }
    }
    
    echo "<h3>4. 系統資料表</h3>";
    $foundSystemTables = [];
    
    foreach ($systemTables as $table => $description) {
        if (in_array($table, $allTables)) {
            $foundSystemTables[$table] = $description;
            echo "<p style='color: blue;'>🔧 {$table} - {$description}</p>";
        }
    }
    
    echo "<h3>5. 其他資料表</h3>";
    $otherTables = array_diff($allTables, array_keys($coreChatTables), array_keys($potentialRelatedTables), array_keys($systemTables));
    
    if (empty($otherTables)) {
        echo "<p>沒有其他資料表。</p>";
    } else {
        echo "<p>找到 " . count($otherTables) . " 個其他資料表：</p>";
        echo "<ul>";
        foreach ($otherTables as $table) {
            echo "<li style='color: gray;'>{$table}</li>";
        }
        echo "</ul>";
    }
    
    // 檢查可能重複的資料表
    echo "<h3>6. 可能重複的資料表檢查</h3>";
    
    // 檢查 teacher vs teacher02
    if (in_array('teacher', $allTables) && in_array('teacher02', $allTables)) {
        echo "<p style='color: orange;'>⚠️ 發現可能的重複：teacher 和 teacher02</p>";
        
        // 比較兩個表的結構
        try {
            $stmt = $pdo->query("DESCRIBE teacher");
            $teacherColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $stmt = $pdo->query("DESCRIBE teacher02");
            $teacher02Columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p>teacher 欄位：" . implode(', ', $teacherColumns) . "</p>";
            echo "<p>teacher02 欄位：" . implode(', ', $teacher02Columns) . "</p>";
            
            // 檢查哪個表有資料
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
            $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher02");
            $teacher02Count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>teacher 記錄數：{$teacherCount}</p>";
            echo "<p>teacher02 記錄數：{$teacher02Count}</p>";
            
            if ($teacherCount == 0 && $teacher02Count > 0) {
                echo "<p style='color: red;'>💡 建議：teacher 表為空，可以刪除</p>";
            } elseif ($teacher02Count == 0 && $teacherCount > 0) {
                echo "<p style='color: red;'>💡 建議：teacher02 表為空，可以刪除</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 無法比較表結構：{$e->getMessage()}</p>";
        }
    }
    
    // 檢查 user vs users
    if (in_array('user', $allTables) && in_array('users', $allTables)) {
        echo "<p style='color: orange;'>⚠️ 發現可能的重複：user 和 users</p>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $usersCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>user 記錄數：{$userCount}</p>";
            echo "<p>users 記錄數：{$usersCount}</p>";
            
            if ($userCount == 0 && $usersCount > 0) {
                echo "<p style='color: red;'>💡 建議：user 表為空，可以刪除</p>";
            } elseif ($usersCount == 0 && $userCount > 0) {
                echo "<p style='color: red;'>💡 建議：users 表為空，可以刪除</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 無法比較記錄數：{$e->getMessage()}</p>";
        }
    }
    
    // 檢查 chat_history vs private_chat_history
    if (in_array('chat_history', $allTables) && in_array('private_chat_history', $allTables)) {
        echo "<p style='color: orange;'>⚠️ 發現可能的重複：chat_history 和 private_chat_history</p>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_history");
            $chatHistoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
            $privateChatHistoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>chat_history 記錄數：{$chatHistoryCount}</p>";
            echo "<p>private_chat_history 記錄數：{$privateChatHistoryCount}</p>";
            
            if ($chatHistoryCount == 0 && $privateChatHistoryCount > 0) {
                echo "<p style='color: red;'>💡 建議：chat_history 表為空，可以刪除</p>";
            } elseif ($privateChatHistoryCount == 0 && $chatHistoryCount > 0) {
                echo "<p style='color: red;'>💡 建議：private_chat_history 表為空，可以刪除</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 無法比較記錄數：{$e->getMessage()}</p>";
        }
    }
    
    echo "<h3>7. 刪除建議</h3>";
    echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7;'>";
    echo "<h4>可以安全刪除的資料表：</h4>";
    
    $canDeleteTables = [];
    
    // 檢查空的可能重複表
    if (in_array('teacher', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM teacher");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($count == 0) {
                $canDeleteTables[] = 'teacher (空的教師表)';
            }
        } catch (Exception $e) {
            // 忽略錯誤
        }
    }
    
    if (in_array('users', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($count == 0) {
                $canDeleteTables[] = 'users (空的用戶表)';
            }
        } catch (Exception $e) {
            // 忽略錯誤
        }
    }
    
    if (in_array('chat_history', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_history");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($count == 0) {
                $canDeleteTables[] = 'chat_history (空的聊天記錄表)';
            }
        } catch (Exception $e) {
            // 忽略錯誤
        }
    }
    
    if (in_array('messages', $allTables)) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($count == 0) {
                $canDeleteTables[] = 'messages (空的訊息表)';
            }
        } catch (Exception $e) {
            // 忽略錯誤
        }
    }
    
    if (empty($canDeleteTables)) {
        echo "<p style='color: green;'>✅ 沒有發現可以安全刪除的資料表</p>";
    } else {
        echo "<ul>";
        foreach ($canDeleteTables as $table) {
            echo "<li style='color: red;'>{$table}</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    
    echo "<h3>8. 重要提醒</h3>";
    echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<p><strong>⚠️ 刪除前請務必：</strong></p>";
    echo "<ul>";
    echo "<li>備份整個資料庫</li>";
    echo "<li>確認資料表確實沒有被使用</li>";
    echo "<li>在測試環境中先測試刪除操作</li>";
    echo "<li>檢查是否有外鍵約束</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<h3>❌ 資料庫連接失敗</h3>";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
}
?>

