<?php
/**
 * 測試訊息發送功能
 */

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>測試訊息發送功能</h1>";
    
    // 檢查 private_chat_history 表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'private_chat_history'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ private_chat_history 表不存在</p>";
        echo "<p>請執行 create_chat_tables.sql 創建聊天表</p>";
        exit;
    } else {
        echo "<p style='color: green;'>✅ private_chat_history 表存在</p>";
    }
    
    // 檢查表結構
    $stmt = $pdo->query("DESCRIBE private_chat_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>表結構</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>欄位名</th><th>類型</th><th>允許NULL</th><th>預設值</th></tr>";
    foreach ($columns as $column) {
        echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Default']}</td></tr>";
    }
    echo "</table>";
    
    // 測試插入訊息
    echo "<h2>測試插入訊息</h2>";
    
    $testData = [
        'from' => 'assistant1',
        'to' => 'student',
        'message' => '這是一條測試訊息',
        'role' => '老師'
    ];
    
    try {
        $sql = "INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$testData['from'], $testData['to'], $testData['message'], $testData['role']]);
        
        if ($result) {
            $message_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ 測試訊息插入成功，ID: $message_id</p>";
        } else {
            echo "<p style='color: red;'>❌ 測試訊息插入失敗</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 插入測試訊息時發生錯誤: " . $e->getMessage() . "</p>";
    }
    
    // 測試查詢訊息
    echo "<h2>測試查詢訊息</h2>";
    
    try {
        $sql = "SELECT * FROM private_chat_history 
                WHERE (from_user = ? AND to_user = ?) 
                OR (from_user = ? AND to_user = ?) 
                ORDER BY timestamp DESC 
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$testData['from'], $testData['to'], $testData['to'], $testData['from']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($messages)) {
            echo "<p style='color: green;'>✅ 查詢到 " . count($messages) . " 條訊息</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>發送者</th><th>接收者</th><th>訊息</th><th>角色</th><th>時間</th></tr>";
            foreach ($messages as $message) {
                echo "<tr><td>{$message['id']}</td><td>{$message['from_user']}</td><td>{$message['to_user']}</td><td>{$message['message']}</td><td>{$message['role']}</td><td>{$message['timestamp']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ 沒有找到任何訊息</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 查詢訊息時發生錯誤: " . $e->getMessage() . "</p>";
    }
    
    // 測試 API 端點
    echo "<h2>測試 API 端點</h2>";
    
    // 測試 save_private_message.php
    $apiUrl = 'http://localhost/Topics-frontend/frontend/chat/save_private_message.php';
    $postData = json_encode([
        'from' => 'assistant1',
        'to' => 'student',
        'message' => 'API 測試訊息',
        'role' => '老師'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        echo "<p style='color: green;'>✅ API 響應成功</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // 檢查是否為有效的 JSON
        $jsonData = json_decode($response, true);
        if ($jsonData !== null) {
            echo "<p style='color: green;'>✅ 響應是有效的 JSON</p>";
        } else {
            echo "<p style='color: red;'>❌ 響應不是有效的 JSON</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ API 請求失敗</p>";
    }
    
} catch(PDOException $e) {
    echo "資料庫連接失敗: " . $e->getMessage();
}
?>















