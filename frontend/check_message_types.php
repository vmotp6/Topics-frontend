<?php
/**
 * 檢查並修復 message_type 分類問題
 */

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 檢查 message_type 分類</h1>";
    
    // 檢查欄位類型
    $stmt = $pdo->query("SHOW COLUMNS FROM senior_messages WHERE Field = 'message_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>1. 欄位類型</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // 檢查所有不同的 message_type 值
    echo "<h2>2. 現有的 message_type 值</h2>";
    $stmt = $pdo->query("SELECT message_type, COUNT(*) as count FROM senior_messages GROUP BY message_type ORDER BY count DESC");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>message_type</th><th>數量</th></tr>";
    foreach ($types as $type) {
        $value = $type['message_type'] === null ? 'NULL' : htmlspecialchars($type['message_type']);
        echo "<tr><td>{$value}</td><td>{$type['count']}</td></tr>";
    }
    echo "</table>";
    
    // 檢查是否有推薦餐廳類型的留言
    echo "<h2>3. 推薦餐廳類型的留言</h2>";
    $stmt = $pdo->query("SELECT id, title, message_type, restaurant_name FROM senior_messages WHERE message_type = '推薦餐廳' OR restaurant_name IS NOT NULL");
    $restaurantMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($restaurantMessages)) {
        echo "<p>❌ 沒有找到推薦餐廳類型的留言</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>標題</th><th>message_type</th><th>餐廳名稱</th></tr>";
        foreach ($restaurantMessages as $msg) {
            echo "<tr>";
            echo "<td>{$msg['id']}</td>";
            echo "<td>" . htmlspecialchars($msg['title']) . "</td>";
            echo "<td>" . htmlspecialchars($msg['message_type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($msg['restaurant_name'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 修復建議
    echo "<h2>4. 修復建議</h2>";
    
    if (strpos($column['Type'], 'enum') !== false) {
        echo "<p style='color: red;'>⚠️ message_type 是 ENUM 類型，需要改為 VARCHAR 以支援「推薦餐廳」</p>";
        echo "<p>執行以下 SQL：</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "ALTER TABLE senior_messages MODIFY COLUMN message_type VARCHAR(50) DEFAULT '經驗分享';";
        echo "</pre>";
    }
    
    // 檢查是否有 message_type 為 NULL 的記錄
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM senior_messages WHERE message_type IS NULL");
    $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($nullCount > 0) {
        echo "<p style='color: orange;'>⚠️ 發現 {$nullCount} 筆 message_type 為 NULL 的記錄</p>";
        echo "<p>執行以下 SQL 修復：</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "UPDATE senior_messages SET message_type = '其他' WHERE message_type IS NULL;";
        echo "</pre>";
    }
    
    // 檢查是否有餐廳名稱但 message_type 不是「推薦餐廳」的記錄
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM senior_messages WHERE restaurant_name IS NOT NULL AND message_type != '推薦餐廳'");
    $wrongTypeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($wrongTypeCount > 0) {
        echo "<p style='color: orange;'>⚠️ 發現 {$wrongTypeCount} 筆有餐廳名稱但 message_type 不是「推薦餐廳」的記錄</p>";
        echo "<p>執行以下 SQL 修復：</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo "UPDATE senior_messages SET message_type = '推薦餐廳' WHERE restaurant_name IS NOT NULL AND message_type != '推薦餐廳';";
        echo "</pre>";
    }
    
    // 提供一鍵修復按鈕
    echo "<h2>5. 一鍵修復</h2>";
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<input type='hidden' name='action' value='fix'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 執行修復</button>";
    echo "</form>";
    
    // 處理修復請求
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix') {
        echo "<h2>執行修復中...</h2>";
        
        try {
            // 1. 修改欄位類型
            if (strpos($column['Type'], 'enum') !== false) {
                $pdo->exec("ALTER TABLE senior_messages MODIFY COLUMN message_type VARCHAR(50) DEFAULT '經驗分享'");
                echo "<p style='color: green;'>✅ 已將 message_type 改為 VARCHAR 類型</p>";
            }
            
            // 2. 修復 NULL 值
            $pdo->exec("UPDATE senior_messages SET message_type = '其他' WHERE message_type IS NULL");
            echo "<p style='color: green;'>✅ 已修復 NULL 值</p>";
            
            // 3. 修復有餐廳名稱但類型不對的記錄
            $pdo->exec("UPDATE senior_messages SET message_type = '推薦餐廳' WHERE restaurant_name IS NOT NULL AND message_type != '推薦餐廳'");
            echo "<p style='color: green;'>✅ 已修復餐廳推薦類型</p>";
            
            echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ 修復完成！請重新載入留言板頁面查看效果。</p>";
            echo "<p><a href='senior_messages.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>返回留言板</a></p>";
            
        } catch(PDOException $e) {
            echo "<p style='color: red;'>❌ 修復失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}
?>

