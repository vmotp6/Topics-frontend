<?php
/**
 * 創建學校資料表
 */

// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 創建資料表的SQL
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

echo "<h1>創建學校資料表</h1>";

try {
    // 執行創建表的SQL
    $pdo->exec($createTableSQL);
    echo "<p style='color: green;'>✅ 資料表 'school_data' 創建成功！</p>";
    
    // 檢查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ 確認資料表已存在</p>";
        
        // 顯示表結構
        $stmt = $pdo->query("DESCRIBE school_data");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>資料表結構：</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>欄位名稱</th><th>資料類型</th><th>允許NULL</th><th>預設值</th><th>備註</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Comment']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ 資料表創建失敗</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 創建資料表失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='init_school_data.php'>初始化學校資料</a> | <a href='test_school_api.php'>測試API功能</a> | <a href='admin_school_data.php'>管理介面</a></p>";
?>
