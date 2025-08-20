<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 管理介面API測試工具</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 檢查資料表結構</h2>\n";
    $stmt = $pdo->query("DESCRIBE cooperation_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>資料表欄位：</h3>\n";
    echo "<table border='1'>\n";
    echo "<tr><th>欄位名</th><th>類型</th><th>NULL</th><th>預設值</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>3. 檢查資料數量</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cooperation_applications");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✅ 總共有 $total 筆申請資料<br>\n";
    
    echo "<h2>4. 測試API查詢</h2>\n";
    $sql = "SELECT id, teacher_username, department, project_title, 
                   company_name, project_amount as budget_amount, status, created_at 
            FROM cooperation_applications 
            ORDER BY created_at DESC 
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>最新5筆申請資料：</h3>\n";
    if (empty($applications)) {
        echo "<p>❌ 沒有找到任何申請資料</p>\n";
    } else {
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>申請人</th><th>科系</th><th>專案名稱</th><th>企業</th><th>金額</th><th>狀態</th><th>日期</th></tr>\n";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['teacher_username']}</td>";
            echo "<td>{$app['department']}</td>";
            echo "<td>{$app['project_title']}</td>";
            echo "<td>{$app['company_name']}</td>";
            echo "<td>NT$ " . number_format($app['budget_amount']) . "</td>";
            echo "<td>{$app['status']}</td>";
            echo "<td>{$app['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>5. 測試API端點</h2>\n";
    echo "<p><a href='cooperation_list_api.php' target='_blank'>測試 cooperation_list_api.php</a></p>\n";
    echo "<p><a href='cooperation_stats_api.php' target='_blank'>測試 cooperation_stats_api.php</a></p>\n";
    
    if (!empty($applications)) {
        $first_id = $applications[0]['id'];
        echo "<p><a href='cooperation_detail_api.php?id=$first_id' target='_blank'>測試 cooperation_detail_api.php (ID: $first_id)</a></p>\n";
    }
    
    echo "<h2>6. 立即測試管理介面</h2>\n";
    echo "<p><a href='../frontend/admin_cooperation_review.php' target='_blank'>前往管理介面</a></p>\n";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 資料庫錯誤</h3>\n";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>
