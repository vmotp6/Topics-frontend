<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>📝 簡單測試資料插入</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 插入測試資料</h2>\n";
    
    // 插入待審核申請
    $sql1 = "INSERT INTO cooperation_applications (
        teacher_username, department, project_title, company_name, 
        project_amount, status, created_at
    ) VALUES (
        'teacher1', '資訊管理科', 'AI技術研發合作計畫', '科技公司A',
        150000.00, 'pending', NOW()
    )";
    
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    echo "✅ 插入待審核申請成功<br>\n";
    
    // 插入已通過申請
    $sql2 = "INSERT INTO cooperation_applications (
        teacher_username, department, project_title, company_name, 
        project_amount, status, admin_username, admin_comment, review_date, created_at
    ) VALUES (
        'teacher2', '企業管理科', '智慧電網技術合作', '電子公司B',
        200000.00, 'approved', 'admin1', '計畫內容完整，經費編列合理', NOW(), NOW()
    )";
    
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    echo "✅ 插入已通過申請成功<br>\n";
    
    // 插入已拒絕申請
    $sql3 = "INSERT INTO cooperation_applications (
        teacher_username, department, project_title, company_name, 
        project_amount, status, admin_username, admin_comment, review_date, created_at
    ) VALUES (
        'teacher3', '護理科', '精密製造技術研發', '製造公司C',
        120000.00, 'rejected', 'admin1', '經費編列過於樂觀，需要重新評估', NOW(), NOW()
    )";
    
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    echo "✅ 插入已拒絕申請成功<br>\n";
    
    echo "<h2>3. 檢查插入結果</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cooperation_applications");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "總記錄數: $total<br>\n";
    
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM cooperation_applications GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>\n";
    echo "<tr><th>狀態</th><th>數量</th></tr>\n";
    foreach ($status_counts as $status) {
        echo "<tr>";
        echo "<td>{$status['status']}</td>";
        echo "<td>{$status['count']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>4. 立即測試管理介面</h2>\n";
    echo "<p><a href='../frontend/admin.php' target='_blank'>前往管理主頁面</a></p>\n";
    echo "<p><a href='diagnose_stats.php' target='_blank'>診斷統計數據</a></p>\n";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 資料庫錯誤</h3>\n";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>
