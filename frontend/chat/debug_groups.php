<?php
// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>群組數據調試</h1>";
    echo "<p style='color: green;'>✓ 資料庫連接成功</p>";
    
    // 檢查群組資訊表
    echo "<h2>群組資訊表 (group_info)</h2>";
    $stmt = $pdo->query("SELECT * FROM group_info ORDER BY created_at DESC LIMIT 5");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($groups) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>群組ID</th><th>群組名稱</th><th>創建者</th><th>部門</th><th>創建時間</th></tr>";
        foreach ($groups as $group) {
            echo "<tr>";
            echo "<td>{$group['group_id']}</td>";
            echo "<td>{$group['group_name']}</td>";
            echo "<td>{$group['created_by']}</td>";
            echo "<td>{$group['department']}</td>";
            echo "<td>{$group['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>沒有群組資訊</p>";
    }
    
    // 檢查群組成員表
    echo "<h2>群組成員表 (group_chat_members)</h2>";
    $stmt = $pdo->query("SELECT * FROM group_chat_members ORDER BY joined_at DESC LIMIT 10");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($members) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>群組ID</th><th>成員</th><th>加入時間</th></tr>";
        foreach ($members as $member) {
            echo "<tr>";
            echo "<td>{$member['id']}</td>";
            echo "<td>{$member['group_id']}</td>";
            echo "<td>{$member['member_username']}</td>";
            echo "<td>{$member['joined_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>沒有群組成員</p>";
    }
    
    // 測試 getMyGroups 查詢
    echo "<h2>測試 getMyGroups 查詢</h2>";
    
    // 獲取一個用戶來測試
    $stmt = $pdo->query("SELECT member_username FROM group_chat_members LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        $username = $testUser['member_username'];
        echo "<p>測試用戶: $username</p>";
        
        $sql = "SELECT gm.group_id as id, 
                       COALESCE(gi.group_name, gm.group_id) as group_name, 
                       COUNT(gm2.id) as member_count,
                       COALESCE(gi.created_by, gm.member_username) as created_by,
                       gi.department
                FROM group_chat_members gm 
                JOIN group_chat_members gm2 ON gm.group_id = gm2.group_id 
                LEFT JOIN group_info gi ON gm.group_id = gi.group_id
                WHERE gm.member_username = ? 
                GROUP BY gm.group_id 
                ORDER BY gm.joined_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>查詢結果:</p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        
        // 測試API調用
        echo "<h3>測試API調用</h3>";
        echo "<a href='group_management.php?action=get_my_groups&username=$username' target='_blank'>測試 getMyGroups API</a>";
        
    } else {
        echo "<p>沒有用戶可以測試</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ 資料庫錯誤: " . $e->getMessage() . "</p>";
}
?>
