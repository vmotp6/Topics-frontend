<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 審核API測試工具</h1>\n";

$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    echo "<h2>1. 連接資料庫</h2>\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>\n";
    
    echo "<h2>2. 檢查待審核的申請</h2>\n";
    $stmt = $pdo->query("SELECT id, teacher_username, project_title, status FROM cooperation_applications WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $pending_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending_applications)) {
        echo "<p>❌ 沒有待審核的申請</p>\n";
    } else {
        echo "<h3>待審核申請列表：</h3>\n";
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>申請人</th><th>專案名稱</th><th>狀態</th><th>操作</th></tr>\n";
        foreach ($pending_applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['teacher_username']}</td>";
            echo "<td>{$app['project_title']}</td>";
            echo "<td>{$app['status']}</td>";
            echo "<td>";
            echo "<button onclick='testReview({$app['id']}, \"approved\")'>通過</button> ";
            echo "<button onclick='testReview({$app['id']}, \"rejected\")'>拒絕</button>";
            echo "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>3. 測試審核API</h2>\n";
    if (!empty($pending_applications)) {
        $first_id = $pending_applications[0]['id'];
        echo "<p>測試申請ID: $first_id</p>\n";
        echo "<button onclick='testReview($first_id, \"approved\")'>測試通過審核</button>\n";
        echo "<button onclick='testReview($first_id, \"rejected\")'>測試拒絕審核</button>\n";
    }
    
    echo "<h2>4. 審核歷史</h2>\n";
    $stmt = $pdo->query("SELECT id, teacher_username, project_title, status, admin_username, review_date FROM cooperation_applications WHERE status IN ('approved', 'rejected') ORDER BY review_date DESC LIMIT 5");
    $reviewed_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviewed_applications)) {
        echo "<p>暫無審核歷史</p>\n";
    } else {
        echo "<table border='1'>\n";
        echo "<tr><th>ID</th><th>申請人</th><th>專案名稱</th><th>審核結果</th><th>審核人</th><th>審核日期</th></tr>\n";
        foreach ($reviewed_applications as $app) {
            echo "<tr>";
            echo "<td>{$app['id']}</td>";
            echo "<td>{$app['teacher_username']}</td>";
            echo "<td>{$app['project_title']}</td>";
            echo "<td>{$app['status']}</td>";
            echo "<td>{$app['admin_username']}</td>";
            echo "<td>{$app['review_date']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h2>5. 立即測試管理介面</h2>\n";
    echo "<p><a href='../frontend/admin_cooperation_review.php' target='_blank'>前往管理介面</a></p>\n";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h3>❌ 資料庫錯誤</h3>\n";
    echo "<p>錯誤訊息: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>

<script>
function testReview(applicationId, status) {
    const statusText = status === 'approved' ? '通過' : '拒絕';
    if (confirm(`確定要${statusText}申請 #${applicationId} 嗎？`)) {
        fetch('/backend/cooperation_review_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                application_id: applicationId,
                status: status,
                comment: `測試${statusText}審核`,
                admin_username: 'test_admin'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`審核成功！申請 #${applicationId} 已${statusText}`);
                location.reload(); // 重新載入頁面
            } else {
                alert('審核失敗: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('審核時發生錯誤');
        });
    }
}
</script>
