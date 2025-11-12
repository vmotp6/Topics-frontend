<?php
/**
 * 初始化學校資料
 * 執行此腳本來初始化全台灣國民中學資料
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

// 檢查資料表是否存在
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() == 0) {
        echo "<h2>⚠️ 資料表不存在</h2>";
        echo "<p>請先創建資料表：<a href='create_school_table.php'>創建學校資料表</a></p>";
        echo "<hr>";
        echo "<p><a href='create_school_table.php'>創建資料表</a> | <a href='test_school_api.php'>測試API功能</a></p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>檢查資料表失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 執行更新腳本
echo "<h1>初始化學校資料</h1>";
echo "<p>正在執行更新腳本...</p>";

$output = shell_exec('php ../scripts/fetch_real_school_data.php 2>&1');

echo "<h2>執行結果：</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// 檢查資料庫中的資料
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學' AND is_active = 1");
    $count = $stmt->fetch()['total'];
    
    echo "<h2>資料庫統計：</h2>";
    echo "<p>國民中學總數：<strong>$count</strong> 所</p>";
    
    // 顯示部分學校
    $stmt = $pdo->query("SELECT name, city, district FROM school_data WHERE type = '國民中學' AND is_active = 1 ORDER BY city, name LIMIT 20");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>部分學校列表：</h3>";
    echo "<ul>";
    foreach ($schools as $school) {
        echo "<li>{$school['name']} ({$school['city']} {$school['district']})</li>";
    }
    echo "</ul>";
    
    if ($count > 20) {
        echo "<p>... 還有 " . ($count - 20) . " 所學校</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>查詢資料庫失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_school_api.php'>測試API功能</a> | <a href='admin_school_data.php'>管理介面</a> | <a href='cooperation_upload.php'>就讀意願登錄</a></p>";
?>
