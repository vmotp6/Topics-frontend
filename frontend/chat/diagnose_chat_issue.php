<?php
/**
 * 診斷聊天室載入問題
 * 檢查資料表結構是否與程式碼匹配
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html><html lang='zh-TW'><head><meta charset='UTF-8'><title>聊天室問題診斷</title>";
    echo "<style>body{font-family:Arial;margin:20px;background:#f4f4f4;} .container{background:#fff;padding:20px;border-radius:8px;} h2{color:#333;} .error{color:red;font-weight:bold;} .success{color:green;font-weight:bold;} .warning{color:orange;font-weight:bold;} table{border-collapse:collapse;width:100%;margin:15px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style></head><body><div class='container'>";
    
    echo "<h1>🔍 聊天室問題診斷</h1>";
    
    // 檢查 private_chat_history 表
    echo "<h2>1. private_chat_history 表結構檢查</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE private_chat_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasFromUser = false;
        $hasToUser = false;
        $hasFromUserId = false;
        $hasToUserId = false;
        
        echo "<table><tr><th>欄位名稱</th><th>類型</th><th>Null</th><th>Key</th><th>狀態</th></tr>";
        foreach ($columns as $col) {
            $status = '';
            if ($col['Field'] === 'from_user') {
                $hasFromUser = true;
                $status = "<span class='warning'>⚠️ 舊欄位（應改為 from_user_id）</span>";
            } elseif ($col['Field'] === 'to_user') {
                $hasToUser = true;
                $status = "<span class='warning'>⚠️ 舊欄位（應改為 to_user_id）</span>";
            } elseif ($col['Field'] === 'from_user_id') {
                $hasFromUserId = true;
                $status = "<span class='success'>✅ 正規化欄位</span>";
            } elseif ($col['Field'] === 'to_user_id') {
                $hasToUserId = true;
                $status = "<span class='success'>✅ 正規化欄位</span>";
            }
            
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if ($hasFromUser || $hasToUser) {
            echo "<p class='error'>❌ 表使用舊欄位（from_user/to_user），但程式碼可能期望不同的結構</p>";
        } elseif ($hasFromUserId && $hasToUserId) {
            echo "<p class='success'>✅ 表已正規化（使用 from_user_id/to_user_id），但程式碼可能仍使用舊欄位名稱</p>";
        }
        
        // 檢查是否有資料
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM private_chat_history");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>記錄數：<strong>$count</strong></p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ private_chat_history 表不存在或無法訪問: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 檢查 group_chat_messages 表
    echo "<h2>2. group_chat_messages 表結構檢查</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE group_chat_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasFromUser = false;
        $hasUserId = false;
        
        echo "<table><tr><th>欄位名稱</th><th>類型</th><th>Null</th><th>Key</th><th>狀態</th></tr>";
        foreach ($columns as $col) {
            $status = '';
            if ($col['Field'] === 'from_user') {
                $hasFromUser = true;
                $status = "<span class='warning'>⚠️ 舊欄位（應改為 user_id）</span>";
            } elseif ($col['Field'] === 'user_id') {
                $hasUserId = true;
                $status = "<span class='success'>✅ 正規化欄位</span>";
            }
            
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if ($hasFromUser) {
            echo "<p class='error'>❌ 表使用舊欄位（from_user）</p>";
        } elseif ($hasUserId) {
            echo "<p class='success'>✅ 表已正規化（使用 user_id）</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ group_chat_messages 表不存在或無法訪問: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 檢查 user_activity 表
    echo "<h2>3. user_activity 表結構檢查</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE user_activity");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasUsername = false;
        $hasUserId = false;
        
        echo "<table><tr><th>欄位名稱</th><th>類型</th><th>Null</th><th>Key</th><th>狀態</th></tr>";
        foreach ($columns as $col) {
            $status = '';
            if ($col['Field'] === 'username') {
                $hasUsername = true;
                $status = "<span class='warning'>⚠️ 舊欄位（應改為 user_id）</span>";
            } elseif ($col['Field'] === 'user_id') {
                $hasUserId = true;
                $status = "<span class='success'>✅ 正規化欄位</span>";
            }
            
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if ($hasUsername && !$hasUserId) {
            echo "<p class='error'>❌ 表使用舊欄位（username）</p>";
        } elseif ($hasUserId && !$hasUsername) {
            echo "<p class='success'>✅ 表已正規化（使用 user_id）</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ user_activity 表不存在或無法訪問: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 檢查程式碼使用的欄位
    echo "<h2>4. 程式碼使用的欄位檢查</h2>";
    
    $filesToCheck = [
        'load_private_messages.php' => ['from_user', 'to_user'],
        'save_private_message.php' => ['from_user', 'to_user'],
        'update_read_status.php' => ['to_user', 'from_user'],
        'group_management.php' => ['from_user'],
    ];
    
    echo "<table><tr><th>檔案</th><th>使用的欄位</th><th>狀態</th></tr>";
    foreach ($filesToCheck as $file => $fields) {
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $usesOldFields = false;
            foreach ($fields as $field) {
                if (preg_match('/\b' . $field . '\b/', $content)) {
                    $usesOldFields = true;
                    break;
                }
            }
            
            $status = $usesOldFields ? 
                "<span class='error'>❌ 使用舊欄位名稱</span>" : 
                "<span class='success'>✅ 使用正確欄位</span>";
            
            echo "<tr><td>$file</td><td>" . implode(', ', $fields) . "</td><td>$status</td></tr>";
        } else {
            echo "<tr><td>$file</td><td>-</td><td><span class='error'>❌ 檔案不存在</span></td></tr>";
        }
    }
    echo "</table>";
    
    // 總結與建議
    echo "<h2>5. 問題總結與建議</h2>";
    
    $issues = [];
    
    // 檢查表結構與程式碼不匹配
    try {
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'topics_good' AND TABLE_NAME = 'private_chat_history' AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
        $tableFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('from_user_id', $tableFields) && in_array('to_user_id', $tableFields)) {
            // 表已正規化，但程式碼可能使用舊欄位
            $issues[] = "❌ private_chat_history 表已正規化（使用 user_id），但程式碼仍使用 from_user/to_user";
        } elseif (in_array('from_user', $tableFields) && in_array('to_user', $tableFields)) {
            // 表未正規化，程式碼正確
            echo "<p class='success'>✅ private_chat_history 表使用 from_user/to_user，與程式碼匹配</p>";
        } else {
            $issues[] = "❌ private_chat_history 表結構異常，缺少必要的用戶欄位";
        }
    } catch (PDOException $e) {
        $issues[] = "❌ 無法檢查 private_chat_history 表結構";
    }
    
    if (!empty($issues)) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;'>";
        echo "<h3>發現的問題：</h3><ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul></div>";
        
        echo "<div style='background:#d1ecf1;padding:15px;border-radius:5px;margin:15px 0;'>";
        echo "<h3>建議的修復步驟：</h3>";
        echo "<ol>";
        echo "<li>確認資料庫表結構（已在上方顯示）</li>";
        echo "<li>如果表已正規化，更新所有 PHP 檔案使用 user_id 而非 username</li>";
        echo "<li>如果表未正規化，確認程式碼使用正確的欄位名稱</li>";
        echo "<li>測試聊天室載入功能</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<p class='success'>✅ 未發現明顯問題，請檢查瀏覽器控制台的錯誤訊息</p>";
    }
    
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    echo "<p class='error'>資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}

