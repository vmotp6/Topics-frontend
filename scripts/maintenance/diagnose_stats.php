<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 統計數據診斷工具</h1>\n";

$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 檢查資料表是否存在</h2>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'cooperation_applications'");
    $table_exists = $stmt->rowCount() > 0;
    echo "cooperation_applications 資料表: " . ($table_exists ? "✅ 存在" : "❌ 不存在") . "<br>\n";
    
    if (!$table_exists) {
        echo "<p style='color: red;'>❌ 資料表不存在，這是統計數據為0的原因！</p>\n";
        exit;
    }
    
    echo "<h2>3. 檢查資料表結構</h2>\n";
    $stmt = $pdo->query("DESCRIBE cooperation_applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>\n";
    echo "<tr><th>欄位名稱</th><th>類型</th><th>是否為空</th><th>預設值</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>4. 檢查資料總數</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cooperation_applications");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "總記錄數: $total<br>\n";
    
    if ($total == 0) {
        echo "<p style='color: orange;'>⚠️ 資料表中沒有任何記錄，這是統計數據為0的原因！</p>\n";
    }
    
    echo "<h2>5. 檢查各狀態的資料數量</h2>\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM cooperation_applications GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($status_counts)) {
        echo "<p>沒有找到任何狀態資料</p>\n";
    } else {
        echo "<table border='1'>\n";
        echo "<tr><th>狀態</th><th>數量</th></tr>\n";
        foreach ($status_counts as $status) {
            echo "<tr>";
            echo "<td>{$status['status']}</td>";
            echo "<td>{$status['count']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>6. 檢查最近的申請記錄</h2>\n";
    $stmt = $pdo->query("SELECT id, teacher_username, project_title, status, created_at FROM cooperation_applications ORDER BY created_at DESC LIMIT 5");
    $recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent_applications)) {
        echo "<p>沒有找到任何申請記錄</p>\n";
    } else {
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>申請人</th><th>專案名稱</th><th>狀態</th><th>建立時間</th></tr>\n";
        foreach ($recent_applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['teacher_username']}</td>";
            echo "<td>{$app['project_title']}</td>";
            echo "<td>{$app['status']}</td>";
            echo "<td>{$app['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>7. 測試統計API</h2>\n";
    echo "<button onclick='testStatsAPI()'>測試統計API</button>\n";
    echo "<div id='statsResult'></div>\n";
    
    echo "<h2>8. 手動執行統計查詢</h2>\n";
    
    // 總申請數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cooperation_applications");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "總申請數: $total<br>\n";
    
    // 待審核申請數
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM cooperation_applications WHERE status = 'pending'");
    $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    echo "待審核申請數: $pending<br>\n";
    
    // 已通過申請數
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM cooperation_applications WHERE status = 'approved'");
    $approved = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
    echo "已通過申請數: $approved<br>\n";
    
    // 已拒絕申請數
    $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM cooperation_applications WHERE status = 'rejected'");
    $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
    echo "已拒絕申請數: $rejected<br>\n";
    
    echo "<h2>9. 立即測試管理介面</h2>\n";
    echo "<p><a href='../frontend/admin.php' target='_blank'>前往管理主頁面</a></p>\n";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 資料庫錯誤</h3>\n";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>

<script>
function testStatsAPI() {
    fetch('/backend/cooperation_stats_api.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('statsResult').innerHTML = `
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <h4>✅ 統計API測試成功</h4>
                        <p>總申請數: ${stats.total}</p>
                        <p>待審核: ${stats.pending}</p>
                        <p>已通過: ${stats.approved}</p>
                        <p>已拒絕: ${stats.rejected}</p>
                    </div>
                `;
            } else {
                document.getElementById('statsResult').innerHTML = `
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <h4>❌ 統計API測試失敗</h4>
                        <p>錯誤: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('statsResult').innerHTML = `
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h4>❌ 統計API測試失敗</h4>
                    <p>錯誤: ${error.message}</p>
                </div>
            `;
        });
}
</script>
