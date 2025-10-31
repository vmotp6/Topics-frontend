<?php
/**
 * 檢查資料庫狀態
 */

// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

echo "<h1>🔍 資料庫狀態檢查</h1>";

// 檢查資料表是否存在
echo "<h2>1. 檢查資料表</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ 資料表 'school_data' 存在</p>";
        
        // 檢查表結構
        $stmt = $pdo->query("DESCRIBE school_data");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>資料表欄位：</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} ({$column['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ 資料表 'school_data' 不存在</p>";
        echo "<p><a href='create_school_table.php'>創建資料表</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>檢查資料表失敗: " . $e->getMessage() . "</p>";
}

// 檢查資料數量
echo "<h2>2. 檢查資料數量</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data");
    $total = $stmt->fetch()['total'];
    echo "<p>總資料筆數：<strong>$total</strong></p>";
    
    if ($total > 0) {
        // 按類型統計
        $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM school_data GROUP BY type");
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>按類型統計：</p>";
        echo "<ul>";
        foreach ($types as $type) {
            echo "<li>{$type['type']}: {$type['count']} 筆</li>";
        }
        echo "</ul>";
        
        // 按城市統計
        $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC LIMIT 10");
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>按城市統計（前10名）：</p>";
        echo "<ul>";
        foreach ($cities as $city) {
            echo "<li>{$city['city']}: {$city['count']} 所</li>";
        }
        echo "</ul>";
        
        // 顯示部分學校
        $stmt = $pdo->query("SELECT name, city, district FROM school_data WHERE type = '國民中學' LIMIT 10");
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>部分學校範例：</p>";
        echo "<ul>";
        foreach ($schools as $school) {
            echo "<li>{$school['name']} ({$school['city']} {$school['district']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ 資料庫中沒有學校資料</p>";
        echo "<p><a href='setup_school_system.php'>一鍵設置學校系統</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>檢查資料失敗: " . $e->getMessage() . "</p>";
}

// 檢查API端點
echo "<h2>3. 測試API端點</h2>";
echo "<p>測試城市列表API：</p>";
try {
    $url = "http://localhost/Topics-frontend/frontend/api/school_data_api.php?action=cities";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && isset($data['cities'])) {
        echo "<p style='color: green;'>✅ API正常，找到 " . count($data['cities']) . " 個城市</p>";
        echo "<p>城市列表：</p>";
        echo "<ul>";
        foreach ($data['cities'] as $city) {
            echo "<li>$city</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ API回應異常</p>";
        echo "<p>回應內容：$response</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ API測試失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔧 解決方案</h2>";
if ($total == 0) {
    echo "<p>資料庫中沒有學校資料，請執行以下步驟：</p>";
    echo "<ol>";
    echo "<li><a href='create_school_table.php'>創建資料表</a>（如果表不存在）</li>";
    echo "<li><a href='setup_school_system.php'>一鍵設置學校系統</a>（推薦）</li>";
    echo "<li><a href='init_school_data.php'>初始化學校資料</a></li>";
    echo "</ol>";
} else {
    echo "<p>資料庫正常，可以開始使用：</p>";
    echo "<ul>";
    echo "<li><a href='test_school_api.php'>測試API功能</a></li>";
    echo "<li><a href='city_schools.php'>城市學校瀏覽</a></li>";
    echo "<li><a href='cooperation_upload.php'>就讀意願登錄</a></li>";
    echo "</ul>";
}
?>
