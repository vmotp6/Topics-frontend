<?php
/**
 * 快速創建學校資料表腳本
 * 解決 school_data 表不存在的問題
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

echo "<h1>🔧 快速創建學校資料表</h1>";

// 檢查資料表是否存在
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ school_data 資料表已存在</p>";
        
        // 檢查資料數量
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data WHERE type = '國民中學'");
        $count = $stmt->fetch()['count'];
        echo "<p>國民中學資料數量: $count</p>";
        
        if ($count > 0) {
            echo "<p style='color: green;'>✅ 資料表正常，可以開始使用</p>";
            echo "<p><a href='integrate_government_api.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>繼續整合教育部資料</a></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ 資料表存在但沒有資料</p>";
            echo "<p><a href='integrate_government_api.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>開始載入學校資料</a></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ school_data 資料表不存在，正在創建...</p>";
        
        // 創建資料表
        $createTableSQL = "
        CREATE TABLE school_data (
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
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✅ 已成功創建 school_data 資料表</p>";
        
        // 插入一些基本測試資料
        $testSchools = [
            ['name' => '中正國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP001', 'address' => '台北市中正區重慶南路一段139號', 'phone' => '02-2381-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料'],
            ['name' => '西松國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP002', 'address' => '台北市松山區南京東路四段133號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料'],
            ['name' => '永吉國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP003', 'address' => '台北市信義區永吉路30巷158號', 'phone' => '02-2760-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料'],
            ['name' => '中崙國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP004', 'address' => '台北市松山區八德路四段101號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料'],
            ['name' => '板橋國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT001', 'address' => '新北市板橋區文化路一段188號', 'phone' => '02-2968-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料'],
            ['name' => '桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'type' => '國民中學', 'school_code' => 'TY001', 'address' => '桃園市桃園區中正路147號', 'phone' => '03-332-1234', 'website' => '', 'is_active' => 1, 'data_source' => '測試資料']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO school_data (
                name, city, district, type, school_code, 
                address, phone, website, is_active, data_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inserted_count = 0;
        foreach ($testSchools as $school) {
            $stmt->execute([
                $school['name'],
                $school['city'],
                $school['district'],
                $school['type'],
                $school['school_code'],
                $school['address'],
                $school['phone'],
                $school['website'],
                $school['is_active'],
                $school['data_source']
            ]);
            $inserted_count++;
        }
        
        echo "<p style='color: green;'>✅ 已插入 $inserted_count 筆測試資料</p>";
        
        // 測試搜尋功能
        echo "<h2>🧪 測試搜尋功能</h2>";
        $testKeywords = ['中正', '板橋', '桃園'];
        foreach ($testKeywords as $keyword) {
            $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE type = '國民中學' AND name LIKE ?");
            $stmt->execute(["%$keyword%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                echo "<p style='color: green;'>✅ 搜尋「$keyword」: 找到 " . count($results) . " 筆結果</p>";
                foreach ($results as $result) {
                    echo "<p style='margin-left: 20px;'>- {$result['name']} ({$result['city']} {$result['district']})</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ 搜尋「$keyword」: 沒有結果</p>";
            }
        }
        
        echo "<hr>";
        echo "<h2>🎉 資料表創建完成！</h2>";
        echo "<p>現在您可以：</p>";
        echo "<ul>";
        echo "<li><a href='integrate_government_api.php'>載入完整的教育部資料</a></li>";
        echo "<li><a href='cooperation_upload.php'>測試就讀意願登錄功能</a></li>";
        echo "<li><a href='test_full_taiwan_schools.php'>測試搜尋功能</a></li>";
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 操作失敗: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📊 系統狀態</h2>";
echo "<ul>";
echo "<li>PHP版本：" . phpversion() . "</li>";
echo "<li>資料庫：" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
echo "<li>當前時間：" . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";
?>
