<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>📝 插入測試資料</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 檢查現有資料</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cooperation_applications");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "現有記錄數: $total<br>\n";
    
    if ($total > 0) {
        echo "<p style='color: orange;'>⚠️ 資料表中已有資料，是否要插入測試資料？</p>\n";
        echo "<button onclick='insertTestData()'>插入測試資料</button>\n";
    } else {
        echo "<p style='color: green;'>✅ 資料表為空，可以插入測試資料</p>\n";
        echo "<button onclick='insertTestData()'>插入測試資料</button>\n";
    }
    
    echo "<h2>3. 查看現有資料</h2>\n";
    $stmt = $pdo->query("SELECT id, teacher_username, project_title, status, created_at FROM cooperation_applications ORDER BY created_at DESC LIMIT 10");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($applications)) {
        echo "<p>沒有找到任何申請記錄</p>\n";
    } else {
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>申請人</th><th>專案名稱</th><th>狀態</th><th>建立時間</th></tr>\n";
        foreach ($applications as $app) {
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

<script>
function insertTestData() {
    if (confirm('確定要插入測試資料嗎？')) {
        fetch('/backend/insert_test_data_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'insert_test_data'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ 測試資料插入成功！');
                location.reload();
            } else {
                alert('❌ 插入失敗: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ 插入時發生錯誤');
        });
    }
}
</script>
