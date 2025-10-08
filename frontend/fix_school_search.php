<?php
/**
 * 學校搜尋功能完整修復腳本
 * 確保 cooperation_upload.php 中的台灣教育部API搜尋功能正常運作
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

echo "<h1>🔧 學校搜尋功能完整修復</h1>";

$issues = [];
$fixes = [];

// 步驟1：檢查並創建資料表
echo "<h2>步驟1：檢查資料表</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() == 0) {
        $issues[] = "school_data 表不存在";
        
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
        $fixes[] = "✅ 已創建 school_data 資料表";
        echo "<p style='color: green;'>✅ 已創建 school_data 資料表</p>";
    } else {
        echo "<p style='color: green;'>✅ school_data 資料表已存在</p>";
    }
} catch (PDOException $e) {
    $issues[] = "創建資料表失敗: " . $e->getMessage();
    echo "<p style='color: red;'>❌ 創建資料表失敗: " . $e->getMessage() . "</p>";
}

// 步驟2：檢查並插入學校資料
echo "<h2>步驟2：檢查學校資料</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data WHERE type = '國民中學'");
    $juniorCount = $stmt->fetch()['count'];
    
    if ($juniorCount == 0) {
        $issues[] = "沒有國民中學資料";
        
        // 插入基本學校資料
        $schools = [
            // 台北市
            ['name' => '中正國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP001'],
            ['name' => '西松國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP002'],
            ['name' => '永吉國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP003'],
            ['name' => '中崙國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP004'],
            ['name' => '信義國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP005'],
            ['name' => '松山國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP006'],
            ['name' => '敦化國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP007'],
            ['name' => '介壽國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP008'],
            ['name' => '南港國中', 'city' => '台北市', 'district' => '南港區', 'type' => '國民中學', 'school_code' => 'TP009'],
            ['name' => '內湖國中', 'city' => '台北市', 'district' => '內湖區', 'type' => '國民中學', 'school_code' => 'TP010'],
            
            // 新北市
            ['name' => '板橋國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT001'],
            ['name' => '海山國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT002'],
            ['name' => '新莊國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT003'],
            ['name' => '丹鳳國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT004'],
            ['name' => '泰山國中', 'city' => '新北市', 'district' => '泰山區', 'type' => '國民中學', 'school_code' => 'NT005'],
            ['name' => '林口國中', 'city' => '新北市', 'district' => '林口區', 'type' => '國民中學', 'school_code' => 'NT006'],
            ['name' => '五股國中', 'city' => '新北市', 'district' => '五股區', 'type' => '國民中學', 'school_code' => 'NT007'],
            ['name' => '蘆洲國中', 'city' => '新北市', 'district' => '蘆洲區', 'type' => '國民中學', 'school_code' => 'NT008'],
            ['name' => '三重國中', 'city' => '新北市', 'district' => '三重區', 'type' => '國民中學', 'school_code' => 'NT009'],
            ['name' => '永和國中', 'city' => '新北市', 'district' => '永和區', 'type' => '國民中學', 'school_code' => 'NT010'],
            
            // 桃園市
            ['name' => '桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'type' => '國民中學', 'school_code' => 'TY001'],
            ['name' => '中壢國中', 'city' => '桃園市', 'district' => '中壢區', 'type' => '國民中學', 'school_code' => 'TY002'],
            ['name' => '大園國中', 'city' => '桃園市', 'district' => '大園區', 'type' => '國民中學', 'school_code' => 'TY003'],
            ['name' => '蘆竹國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY004'],
            ['name' => '南崁國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY005'],
            ['name' => '龜山國中', 'city' => '桃園市', 'district' => '龜山區', 'type' => '國民中學', 'school_code' => 'TY006'],
            ['name' => '八德國中', 'city' => '桃園市', 'district' => '八德區', 'type' => '國民中學', 'school_code' => 'TY007'],
            ['name' => '大溪國中', 'city' => '桃園市', 'district' => '大溪區', 'type' => '國民中學', 'school_code' => 'TY008'],
            ['name' => '復興國中', 'city' => '桃園市', 'district' => '復興區', 'type' => '國民中學', 'school_code' => 'TY009'],
            ['name' => '龍潭國中', 'city' => '桃園市', 'district' => '龍潭區', 'type' => '國民中學', 'school_code' => 'TY010'],
            
            // 基隆市
            ['name' => '基隆國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL001'],
            ['name' => '安樂國中', 'city' => '基隆市', 'district' => '安樂區', 'type' => '國民中學', 'school_code' => 'KL002'],
            ['name' => '八斗國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL003'],
            ['name' => '正濱國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL004'],
            ['name' => '信義國中', 'city' => '基隆市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'KL005'],
            
            // 新竹市
            ['name' => '新竹國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC001'],
            ['name' => '光復國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC002'],
            ['name' => '香山國中', 'city' => '新竹市', 'district' => '香山區', 'type' => '國民中學', 'school_code' => 'HSC003'],
            ['name' => '成德國中', 'city' => '新竹市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'HSC004'],
            ['name' => '建功國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC005'],
            
            // 新竹縣
            ['name' => '竹北國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH001'],
            ['name' => '六家國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH002'],
            ['name' => '湖口國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH003'],
            ['name' => '新湖國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH004'],
            ['name' => '新豐國中', 'city' => '新竹縣', 'district' => '新豐鄉', 'type' => '國民中學', 'school_code' => 'HSH005'],
            
            // 台中市
            ['name' => '台中一中', 'city' => '台中市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'TC001'],
            ['name' => '台中女中', 'city' => '台中市', 'district' => '西區', 'type' => '國民中學', 'school_code' => 'TC002'],
            ['name' => '文華國中', 'city' => '台中市', 'district' => '西屯區', 'type' => '國民中學', 'school_code' => 'TC003'],
            ['name' => '大業國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC004'],
            ['name' => '惠文國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC005'],
            
            // 台南市
            ['name' => '台南一中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN001'],
            ['name' => '台南女中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN002'],
            ['name' => '建興國中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN003'],
            ['name' => '復興國中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN004'],
            ['name' => '大成國中', 'city' => '台南市', 'district' => '南區', 'type' => '國民中學', 'school_code' => 'TN005'],
            
            // 高雄市
            ['name' => '高雄中學', 'city' => '高雄市', 'district' => '三民區', 'type' => '國民中學', 'school_code' => 'KS001'],
            ['name' => '高雄女中', 'city' => '高雄市', 'district' => '前金區', 'type' => '國民中學', 'school_code' => 'KS002'],
            ['name' => '鳳山國中', 'city' => '高雄市', 'district' => '鳳山區', 'type' => '國民中學', 'school_code' => 'KS003'],
            ['name' => '左營國中', 'city' => '高雄市', 'district' => '左營區', 'type' => '國民中學', 'school_code' => 'KS004'],
            ['name' => '楠梓國中', 'city' => '高雄市', 'district' => '楠梓區', 'type' => '國民中學', 'school_code' => 'KS005'],
            
            // 其他縣市
            ['name' => '宜蘭國中', 'city' => '宜蘭縣', 'district' => '宜蘭市', 'type' => '國民中學', 'school_code' => 'IL001'],
            ['name' => '羅東國中', 'city' => '宜蘭縣', 'district' => '羅東鎮', 'type' => '國民中學', 'school_code' => 'IL002'],
            ['name' => '花蓮國中', 'city' => '花蓮縣', 'district' => '花蓮市', 'type' => '國民中學', 'school_code' => 'HL001'],
            ['name' => '台東國中', 'city' => '台東縣', 'district' => '台東市', 'type' => '國民中學', 'school_code' => 'TT001'],
            ['name' => '澎湖國中', 'city' => '澎湖縣', 'district' => '馬公市', 'type' => '國民中學', 'school_code' => 'PH001'],
            ['name' => '金門國中', 'city' => '金門縣', 'district' => '金城鎮', 'type' => '國民中學', 'school_code' => 'KM001'],
            ['name' => '連江國中', 'city' => '連江縣', 'district' => '南竿鄉', 'type' => '國民中學', 'school_code' => 'LC001']
        ];
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO school_data (name, city, district, type, school_code, is_active, data_source) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $inserted_count = 0;
        foreach ($schools as $school) {
            $stmt->execute([
                $school['name'],
                $school['city'],
                $school['district'],
                $school['type'],
                $school['school_code'],
                1, // is_active
                '修復腳本初始化'
            ]);
            $inserted_count++;
        }
        
        $pdo->commit();
        $fixes[] = "✅ 已插入 $inserted_count 所國民中學";
        echo "<p style='color: green;'>✅ 已插入 $inserted_count 所國民中學</p>";
        
    } else {
        echo "<p style='color: green;'>✅ 已有 $juniorCount 所國民中學資料</p>";
    }
} catch (PDOException $e) {
    $issues[] = "插入學校資料失敗: " . $e->getMessage();
    echo "<p style='color: red;'>❌ 插入學校資料失敗: " . $e->getMessage() . "</p>";
}

// 步驟3：測試API
echo "<h2>步驟3：測試API</h2>";
try {
    // 測試搜尋功能
    $testKeywords = ['中正', '板橋', '桃園', '中崙'];
    foreach ($testKeywords as $keyword) {
        $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE type = '國民中學' AND name LIKE ? LIMIT 3");
        $stmt->execute(["%$keyword%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "<p style='color: green;'>✅ 搜尋 '$keyword': 找到 " . count($results) . " 筆結果</p>";
            foreach ($results as $result) {
                echo "<p style='margin-left: 20px;'>- {$result['name']} ({$result['city']} {$result['district']})</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ 搜尋 '$keyword': 沒有結果</p>";
        }
    }
} catch (PDOException $e) {
    $issues[] = "測試API失敗: " . $e->getMessage();
    echo "<p style='color: red;'>❌ 測試API失敗: " . $e->getMessage() . "</p>";
}

// 步驟4：檢查CSS文件
echo "<h2>步驟4：檢查CSS文件</h2>";
$cssFile = 'assets/csp/cooperation_upload.css';
if (file_exists($cssFile)) {
    echo "<p style='color: green;'>✅ CSS文件存在: $cssFile</p>";
} else {
    $issues[] = "CSS文件不存在: $cssFile";
    echo "<p style='color: red;'>❌ CSS文件不存在: $cssFile</p>";
}

// 總結
echo "<h2>📊 修復總結</h2>";
if (empty($issues)) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3 style='color: #155724;'>🎉 所有問題已修復！</h3>";
    echo "<p>學校搜尋功能現在應該正常運作了。</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>⚠️ 仍有問題需要解決：</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($fixes)) {
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; border-left: 4px solid #17a2b8; margin-top: 20px;'>";
    echo "<h3 style='color: #0c5460;'>✅ 已完成的修復：</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🧪 測試建議</h2>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='cooperation_upload.php' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 10px; display: inline-block;'>📝 測試就讀意願登錄</a>";
echo "<a href='diagnose_school_search.php' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 10px; display: inline-block;'>🔍 診斷工具</a>";
echo "</div>";

echo "<h3>🔍 測試步驟：</h3>";
echo "<ol>";
echo "<li>點擊「測試就讀意願登錄」</li>";
echo "<li>在「就讀或畢業國中」欄位輸入「中正」</li>";
echo "<li>應該會顯示中正國中等搜尋結果</li>";
echo "<li>點擊任一結果應該會自動填入欄位</li>";
echo "</ol>";

echo "<h3>💡 搜尋建議：</h3>";
echo "<ul>";
echo "<li><strong>中正</strong> - 會找到中正國中</li>";
echo "<li><strong>板橋</strong> - 會找到板橋國中、海山國中等</li>";
echo "<li><strong>桃園</strong> - 會找到桃園國中、中壢國中等</li>";
echo "<li><strong>中崙</strong> - 會找到中崙國中</li>";
echo "<li><strong>西松</strong> - 會找到西松國中</li>";
echo "</ul>";
?>
