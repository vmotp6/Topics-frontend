<?php
// 快速調試腳本：檢查 recommended 表插入問題
require_once 'config.php';

$conn = getDatabaseConnection();

echo "<h2>Recommended 表結構檢查</h2>";

// 1. 檢查表是否存在
$table_check = $conn->query("SHOW TABLES LIKE 'recommended'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ recommended 表存在</p>";
    
    // 2. 查看表結構
    echo "<h3>表結構：</h3>";
    $columns_result = $conn->query("SHOW COLUMNS FROM recommended");
    if ($columns_result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>欄位</th><th>類型</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $columns_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. 查看現有數據
    echo "<h3>現有數據：</h3>";
    $data_result = $conn->query("SELECT * FROM recommended ORDER BY id DESC LIMIT 5");
    if ($data_result && $data_result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        $first = true;
        while ($row = $data_result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>沒有數據</p>";
    }
    
    // 4. 檢查外鍵約束
    echo "<h3>外鍵約束：</h3>";
    $fk_result = $conn->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'recommended'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    if ($fk_result && $fk_result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>約束名稱</th><th>欄位</th><th>參照表</th><th>參照欄位</th></tr>";
        while ($row = $fk_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($row['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>沒有外鍵約束</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ recommended 表不存在</p>";
}

// 5. 檢查最近的 admission_recommendations 記錄
echo "<h2>最近的 admission_recommendations 記錄：</h2>";
$ar_result = $conn->query("
    SELECT ar.id, ar.created_at, 
           rec.name as recommender_name,
           red.name as recommended_name
    FROM admission_recommendations ar
    LEFT JOIN recommender rec ON ar.id = rec.recommendations_id
    LEFT JOIN recommended red ON ar.id = red.recommendations_id
    ORDER BY ar.id DESC
    LIMIT 10
");
if ($ar_result && $ar_result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>建立時間</th><th>推薦人</th><th>被推薦人</th></tr>";
    while ($row = $ar_result->fetch_assoc()) {
        $has_recommended = !empty($row['recommended_name']);
        $row_style = $has_recommended ? '' : 'style="background-color: #ffebee;"';
        echo "<tr $row_style>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($row['recommender_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['recommended_name'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: #666; font-size: 12px;'>紅色背景表示 recommended 表沒有對應記錄</p>";
} else {
    echo "<p>沒有記錄</p>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th { background: #f0f0f0; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    h3 { color: #666; margin-top: 20px; }
</style>
