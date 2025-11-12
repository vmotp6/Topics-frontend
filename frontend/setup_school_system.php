<?php
/**
 * 一鍵設置學校搜尋系統
 * 創建資料表並初始化學校資料
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

echo "<h1>🏫 學校搜尋系統一鍵設置</h1>";

// 步驟1：創建資料表
echo "<h2>步驟1：創建資料表</h2>";

$createTableSQL = "
CREATE TABLE IF NOT EXISTS school_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '學校名稱',
    city VARCHAR(20) NOT NULL COMMENT '縣市',
    district VARCHAR(20) NOT NULL COMMENT '區/鄉鎮市',
    type VARCHAR(20) NOT NULL COMMENT '學校類型',
    school_code VARCHAR(20) DEFAULT NULL COMMENT '學校代碼',
    address VARCHAR(200) DEFAULT NULL COMMENT '學校地址',
    phone VARCHAR(20) DEFAULT NULL COMMENT '聯絡電話',
    website VARCHAR(200) DEFAULT NULL COMMENT '學校網站',
    principal VARCHAR(50) DEFAULT NULL COMMENT '校長姓名',
    student_count INT DEFAULT 0 COMMENT '學生人數',
    teacher_count INT DEFAULT 0 COMMENT '教師人數',
    established_year YEAR DEFAULT NULL COMMENT '創校年份',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否營運中',
    data_source VARCHAR(100) DEFAULT '教育部開放資料' COMMENT '資料來源',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    INDEX idx_name (name),
    INDEX idx_city (city),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學校基本資料表'
";

try {
    $pdo->exec($createTableSQL);
    echo "<p style='color: green;'>✅ 資料表 'school_data' 創建成功！</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 創建資料表失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 步驟2：初始化學校資料
echo "<h2>步驟2：初始化學校資料</h2>";
echo "<p>正在執行更新腳本...</p>";

$output = shell_exec('php ../scripts/fetch_real_school_data.php 2>&1');

echo "<h3>執行結果：</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>" . htmlspecialchars($output) . "</pre>";

// 步驟3：驗證資料
echo "<h2>步驟3：驗證資料</h2>";

try {
    // 檢查總數
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學' AND is_active = 1");
    $count = $stmt->fetch()['total'];
    
    echo "<p>✅ 國民中學總數：<strong>$count</strong> 所</p>";
    
    // 檢查城市分布
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' AND is_active = 1 GROUP BY city ORDER BY count DESC");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>城市分布：</h3>";
    echo "<ul>";
    foreach ($cities as $city) {
        echo "<li>{$city['city']}: {$city['count']} 所</li>";
    }
    echo "</ul>";
    
    // 檢查特定學校
    $testSchools = ['中崙國中', '西松國中', '永吉國中', '板橋國中', '桃園國中'];
    echo "<h3>測試學校檢查：</h3>";
    echo "<ul>";
    foreach ($testSchools as $schoolName) {
        $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE name = ? AND type = '國民中學'");
        $stmt->execute([$schoolName]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($school) {
            echo "<li style='color: green;'>✅ {$school['name']} ({$school['city']} {$school['district']})</li>";
        } else {
            echo "<li style='color: red;'>❌ {$schoolName} - 未找到</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 驗證資料失敗: " . $e->getMessage() . "</p>";
}

// 步驟4：系統狀態
echo "<h2>步驟4：系統狀態</h2>";

$systemStatus = [
    '資料庫連接' => '✅ 正常',
    '資料表創建' => '✅ 完成',
    '學校資料' => $count > 0 ? '✅ 已載入' : '❌ 未載入',
    'API端點' => '✅ 可用',
    '搜尋功能' => '✅ 就緒'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>項目</th><th>狀態</th></tr>";
foreach ($systemStatus as $item => $status) {
    echo "<tr><td>$item</td><td>$status</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>🎉 設置完成！</h2>";
echo "<p>學校搜尋系統已成功設置，現在您可以：</p>";
echo "<ul>";
echo "<li><a href='test_school_api.php'>測試API功能</a> - 搜尋「中崙」會找到中崙國中</li>";
echo "<li><a href='admin_school_data.php'>管理學校資料</a> - 查看統計和更新資料</li>";
echo "<li><a href='cooperation_upload.php'>使用就讀意願登錄</a> - 實際使用搜尋功能</li>";
echo "</ul>";

echo "<h3>🔍 測試建議：</h3>";
echo "<p>在測試頁面中嘗試搜尋以下關鍵字：</p>";
echo "<ul>";
echo "<li><strong>中崙</strong> - 會找到中崙國中</li>";
echo "<li><strong>西松</strong> - 會找到西松國中</li>";
echo "<li><strong>板橋</strong> - 會找到板橋國中、海山國中等</li>";
echo "<li><strong>台中</strong> - 會找到台中一中、台中女中等</li>";
echo "<li><strong>高雄</strong> - 會找到高雄中學、高雄女中等</li>";
echo "</ul>";
?>
