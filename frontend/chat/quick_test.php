<?php
// 快速測試聊天系統
echo "<h1>聊天系統快速測試</h1>";

// 測試數據庫連接
echo "<h2>1. 數據庫連接測試</h2>";
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ 數據庫連接成功</p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ 數據庫連接失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 測試API端點
echo "<h2>2. API端點測試</h2>";

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/';

// 測試私聊訊息API
echo "<h3>私聊訊息API</h3>";
$testUrl = $baseUrl . 'load_private_messages.php?from=test_user&to=test_user2';
echo "<p>測試URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";

// 測試群組管理API
echo "<h3>群組管理API</h3>";
$testUrl = $baseUrl . 'group_management.php?action=get_my_groups&username=test_user';
echo "<p>測試URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";

// 檢查文件是否存在
echo "<h2>3. 文件檢查</h2>";
$files = [
    'chat.php',
    'group_management.php',
    'load_private_messages.php',
    'save_private_message.php',
    'create_chat_tables.sql'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file 存在</p>";
    } else {
        echo "<p style='color: red;'>✗ $file 不存在</p>";
    }
}

// 測試數據庫表
echo "<h2>4. 數據庫表檢查</h2>";
    $tables = [
        'private_chat_history',
        'chat_groups',
        'group_members',
        'group_messages',
        'user',
        'teacher'
    ];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ 表 '$table' 存在</p>";
        } else {
            echo "<p style='color: red;'>✗ 表 '$table' 不存在</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ 檢查表 '$table' 失敗: " . $e->getMessage() . "</p>";
    }
}

// 測試插入私聊訊息
echo "<h2>5. 功能測試</h2>";
try {
    // 測試插入私聊訊息
    $sql = "INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['test_user', 'test_user2', '測試訊息', '測試']);
    $messageId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✓ 私聊訊息插入成功 (ID: $messageId)</p>";
    
    // 清理測試數據
    $pdo->exec("DELETE FROM private_chat_history WHERE id = $messageId");
    echo "<p style='color: blue;'>✓ 測試數據已清理</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 功能測試失敗: " . $e->getMessage() . "</p>";
}

echo "<h2>6. 下一步</h2>";
echo "<p>如果所有測試都通過，您可以：</p>";
echo "<ul>";
echo "<li><a href='chat.php'>訪問聊天室</a></li>";
echo "<li><a href='test_chat_connection.php'>運行詳細測試</a></li>";
echo "<li>檢查瀏覽器控制台是否有JavaScript錯誤</li>";
echo "</ul>";

echo "<h2>7. 故障排除</h2>";
echo "<ul>";
echo "<li>如果數據庫連接失敗，檢查IP地址和防火牆設置</li>";
echo "<li>如果表不存在，執行 create_chat_tables.sql</li>";
echo "<li>如果API返回錯誤，檢查PHP錯誤日誌</li>";
echo "<li>確保用戶已登入並有適當的權限</li>";
echo "</ul>";
?>
