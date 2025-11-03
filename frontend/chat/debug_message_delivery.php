<?php
/**
 * 診斷訊息發送和接收問題
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
    
    echo "<!DOCTYPE html><html lang='zh-TW'><head><meta charset='UTF-8'><title>訊息發送診斷</title>";
    echo "<style>body{font-family:Arial;margin:20px;background:#f4f4f4;} .container{background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;} h2{color:#333;border-bottom:2px solid #4CAF50;padding-bottom:10px;} table{border-collapse:collapse;width:100%;margin:15px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .success{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body>";
    
    echo "<div class='container'><h1>🔍 訊息發送與接收診斷工具</h1>";
    
    // 檢查表結構
    echo "<h2>1. 檢查資料表結構</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE private_chat_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>欄位名稱</th><th>類型</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ 無法讀取表結構: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 檢查最近的訊息
    echo "<h2>2. 最近的訊息記錄（最新10筆）</h2>";
    try {
        // 檢查使用哪種欄位
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
        $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('from_user_id', $columnNames) && in_array('to_user_id', $columnNames)) {
            // 使用正規化版本
            $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                    FROM private_chat_history pch
                    LEFT JOIN user u1 ON pch.from_user_id = u1.id
                    LEFT JOIN user u2 ON pch.to_user_id = u2.id
                    ORDER BY pch.timestamp DESC LIMIT 10";
            $stmt = $pdo->query($sql);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table><tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息內容</th><th>時間</th><th>已讀</th></tr>";
            foreach ($messages as $msg) {
                $isRead = isset($msg['is_read']) ? ($msg['is_read'] ? '✅ 已讀' : '❌ 未讀') : '❓ 未知';
                echo "<tr>";
                echo "<td>{$msg['id']}</td>";
                echo "<td>" . htmlspecialchars($msg['from_username'] ?? '未知') . "</td>";
                echo "<td>" . htmlspecialchars($msg['to_username'] ?? '未知') . "</td>";
                echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '') . "</td>";
                echo "<td>{$msg['timestamp']}</td>";
                echo "<td>$isRead</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            // 使用舊版本
            $sql = "SELECT * FROM private_chat_history ORDER BY timestamp DESC LIMIT 10";
            $stmt = $pdo->query($sql);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table><tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息內容</th><th>時間</th><th>已讀</th></tr>";
            foreach ($messages as $msg) {
                $isRead = isset($msg['is_read']) ? ($msg['is_read'] ? '✅ 已讀' : '❌ 未讀') : '❓ 未知';
                echo "<tr>";
                echo "<td>{$msg['id']}</td>";
                echo "<td>" . htmlspecialchars($msg['from_user'] ?? '未知') . "</td>";
                echo "<td>" . htmlspecialchars($msg['to_user'] ?? '未知') . "</td>";
                echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '') . "</td>";
                echo "<td>{$msg['timestamp']}</td>";
                echo "<td>$isRead</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ 無法讀取訊息: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // 測試訊息查詢
    echo "<h2>3. 測試訊息查詢</h2>";
    echo "<form method='GET' style='margin:15px 0;'>";
    echo "<label>發送者 username: <input type='text' name='from' value='" . htmlspecialchars($_GET['from'] ?? '') . "'></label> ";
    echo "<label>接收者 username: <input type='text' name='to' value='" . htmlspecialchars($_GET['to'] ?? '') . "'></label> ";
    echo "<button type='submit'>測試查詢</button>";
    echo "</form>";
    
    if (isset($_GET['from']) && isset($_GET['to'])) {
        $from = $_GET['from'];
        $to = $_GET['to'];
        
        try {
            // 檢查使用哪種欄位
            $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'topics_good' 
                                AND TABLE_NAME = 'private_chat_history' 
                                AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
            $columnNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('from_user_id', $columnNames) && in_array('to_user_id', $columnNames)) {
                // 使用正規化版本
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$from]);
                $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
                $fromUserId = $fromUser ? $fromUser['id'] : null;
                
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$to]);
                $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
                $toUserId = $toUser ? $toUser['id'] : null;
                
                if (!$fromUserId || !$toUserId) {
                    echo "<p class='error'>❌ 找不到指定的用戶</p>";
                    echo "<p>from_user_id: " . ($fromUserId ?? 'null') . "</p>";
                    echo "<p>to_user_id: " . ($toUserId ?? 'null') . "</p>";
                } else {
                    $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                            FROM private_chat_history pch
                            LEFT JOIN user u1 ON pch.from_user_id = u1.id
                            LEFT JOIN user u2 ON pch.to_user_id = u2.id
                            WHERE (pch.from_user_id = ? AND pch.to_user_id = ?) 
                            OR (pch.from_user_id = ? AND pch.to_user_id = ?) 
                            ORDER BY pch.timestamp ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
                    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<p class='success'>✅ 找到 " . count($messages) . " 筆訊息</p>";
                    if (!empty($messages)) {
                        echo "<table><tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>時間</th></tr>";
                        foreach ($messages as $msg) {
                            echo "<tr>";
                            echo "<td>{$msg['id']}</td>";
                            echo "<td>" . htmlspecialchars($msg['from_username']) . "</td>";
                            echo "<td>" . htmlspecialchars($msg['to_username']) . "</td>";
                            echo "<td>" . htmlspecialchars($msg['message']) . "</td>";
                            echo "<td>{$msg['timestamp']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
            } else {
                // 使用舊版本
                $sql = "SELECT * FROM private_chat_history 
                        WHERE (from_user = ? AND to_user = ?) 
                        OR (from_user = ? AND to_user = ?) 
                        ORDER BY timestamp ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$from, $to, $to, $from]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p class='success'>✅ 找到 " . count($messages) . " 筆訊息（使用舊欄位）</p>";
                if (!empty($messages)) {
                    echo "<table><tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>時間</th></tr>";
                    foreach ($messages as $msg) {
                        echo "<tr>";
                        echo "<td>{$msg['id']}</td>";
                        echo "<td>" . htmlspecialchars($msg['from_user']) . "</td>";
                        echo "<td>" . htmlspecialchars($msg['to_user']) . "</td>";
                        echo "<td>" . htmlspecialchars($msg['message']) . "</td>";
                        echo "<td>{$msg['timestamp']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ 查詢失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    
    // 檢查用戶表
    echo "<h2>4. 檢查用戶表</h2>";
    try {
        $stmt = $pdo->query("SELECT id, username, role FROM user ORDER BY id DESC LIMIT 10");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>ID</th><th>Username</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['id']}</td><td>" . htmlspecialchars($user['username']) . "</td><td>" . htmlspecialchars($user['role']) . "</td></tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ 無法讀取用戶表: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // API 測試
    echo "<h2>5. API 測試</h2>";
    echo "<p>測試 <code>load_private_messages.php</code> API:</p>";
    if (isset($_GET['from']) && isset($_GET['to'])) {
        $testUrl = "load_private_messages.php?from=" . urlencode($_GET['from']) . "&to=" . urlencode($_GET['to']);
        echo "<p><a href='$testUrl' target='_blank'>測試 API</a></p>";
    } else {
        echo "<p class='warning'>⚠️ 請先輸入發送者和接收者 username 進行測試</p>";
    }
    
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    echo "<p class='error'>資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}

