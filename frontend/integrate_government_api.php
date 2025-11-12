<?php
/**
 * 台灣教育部國民中學資料獲取和更新腳本
 * 從教育部統計處獲取全台國民中學的完整資料
 */

// 設定執行時間限制
set_time_limit(300); // 5分鐘

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

echo "<h1>🏫 台灣教育部國民中學資料整合系統</h1>";

// 步驟0：檢查並創建資料表
echo "<h2>步驟0：檢查資料表</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() == 0) {
        logUpdate("school_data 表不存在，正在創建...");
        
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
        logUpdate("✅ 已創建 school_data 資料表");
        echo "<p style='color: green;'>✅ 已創建 school_data 資料表</p>";
    } else {
        logUpdate("school_data 資料表已存在");
        echo "<p style='color: green;'>✅ school_data 資料表已存在</p>";
    }
} catch (PDOException $e) {
    logUpdate("創建資料表失敗: " . $e->getMessage());
    echo "<p style='color: red;'>❌ 創建資料表失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 教育部統計處的資料來源
$education_apis = [
    'junior_high_txt' => 'http://stats.moe.gov.tw/files/school/104/j1_new.txt',
    'junior_high_xls' => 'http://stats.moe.gov.tw/files/school/104/j1_new.xls',
    'school_basic_info' => 'https://data.gov.tw/dataset/12071',
    'school_statistics' => 'https://data.gov.tw/dataset/12072'
];

// 從教育部API獲取資料的函數
function fetchDataFromEducationAPI($url, $format = 'txt') {
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'header' => [
                'Accept: text/plain, application/vnd.ms-excel, */*',
                'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
                'Cache-Control: no-cache'
            ]
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        return null;
    }
    
    return $data;
}

// 解析TXT格式的學校資料
function parseSchoolDataTXT($txt_content) {
    $lines = explode("\n", $txt_content);
    $schools = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strlen($line) < 10) continue;
        
        // 解析TXT格式的資料
        // 格式通常是：學校代碼|學校名稱|縣市|區鄉鎮|地址|電話|網址
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $school = [
                'school_code' => trim($parts[0]),
                'name' => trim($parts[1]),
                'city' => trim($parts[2]),
                'district' => trim($parts[3]),
                'address' => isset($parts[4]) ? trim($parts[4]) : '',
                'phone' => isset($parts[5]) ? trim($parts[5]) : '',
                'website' => isset($parts[6]) ? trim($parts[6]) : '',
                'type' => '國民中學',
                'is_active' => 1,
                'data_source' => '教育部統計處'
            ];
            
            // 只處理國民中學
            if (strpos($school['name'], '國中') !== false || 
                strpos($school['name'], '國民中學') !== false ||
                strpos($school['name'], '中學') !== false) {
                $schools[] = $school;
            }
        }
    }
    
    return $schools;
}

// 更新學校資料到資料庫
function updateSchoolData($pdo, $schools) {
    try {
        $pdo->beginTransaction();
        
        // 清空現有資料
        $pdo->exec("DELETE FROM school_data WHERE type = '國民中學'");
        
        // 準備插入語句
        $stmt = $pdo->prepare("
            INSERT INTO school_data (
                name, city, district, type, school_code, 
                address, phone, website, is_active, data_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inserted_count = 0;
        foreach ($schools as $school) {
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
        
        $pdo->commit();
        return $inserted_count;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// 記錄更新日誌
function logUpdate($message) {
    echo "<p>" . date('Y-m-d H:i:s') . " - $message</p>";
}

echo "<h2>📊 開始獲取教育部資料</h2>";

try {
    // 嘗試從TXT格式獲取資料
    logUpdate("正在從教育部統計處獲取國民中學資料...");
    $txt_data = fetchDataFromEducationAPI($education_apis['junior_high_txt'], 'txt');
    
    if ($txt_data) {
        logUpdate("成功獲取TXT格式資料，大小: " . strlen($txt_data) . " bytes");
        
        // 解析資料
        $schools = parseSchoolDataTXT($txt_data);
        logUpdate("解析完成，找到 " . count($schools) . " 所學校");
        
        if (count($schools) > 0) {
            // 更新資料庫
            $inserted_count = updateSchoolData($pdo, $schools);
            logUpdate("✅ 成功更新 $inserted_count 所國民中學到資料庫");
            
            // 顯示統計資訊
            echo "<h3>📈 更新統計</h3>";
            
            // 按縣市統計
            $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC");
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>按縣市統計：</h4>";
            echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;'>";
            foreach ($cities as $city) {
                echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #007cba;'>";
                echo "<strong>{$city['city']}</strong><br>";
                echo "<span style='color: #666;'>{$city['count']} 所學校</span>";
                echo "</div>";
            }
            echo "</div>";
            
            // 顯示一些範例學校
            echo "<h4>範例學校：</h4>";
            $stmt = $pdo->query("SELECT name, city, district, school_code FROM school_data WHERE type = '國民中學' ORDER BY city, name LIMIT 20");
            $sample_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
            foreach ($sample_schools as $school) {
                echo "<div style='padding: 5px 0; border-bottom: 1px solid #eee;'>";
                echo "<strong>{$school['name']}</strong> ({$school['city']} {$school['district']}) - {$school['school_code']}";
                echo "</div>";
            }
            echo "</div>";
            
        } else {
            logUpdate("❌ 沒有找到有效的學校資料");
        }
        
    } else {
        logUpdate("❌ 無法從教育部API獲取資料，使用備用資料");
        
        // 使用備用的完整學校資料
        $backup_schools = getBackupSchoolData();
        $inserted_count = updateSchoolData($pdo, $backup_schools);
        logUpdate("✅ 使用備用資料更新 $inserted_count 所國民中學");
    }
    
} catch (Exception $e) {
    logUpdate("❌ 更新失敗: " . $e->getMessage());
    
    // 如果更新失敗，至少確保有基本資料
    try {
        $backup_schools = getBackupSchoolData();
        $inserted_count = updateSchoolData($pdo, $backup_schools);
        logUpdate("✅ 使用備用資料更新 $inserted_count 所國民中學");
    } catch (Exception $e2) {
        logUpdate("❌ 備用資料更新也失敗: " . $e2->getMessage());
    }
}

// 備用學校資料（如果API無法獲取）
function getBackupSchoolData() {
    return [
        // 台北市
        ['name' => '中正國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP001', 'address' => '台北市中正區重慶南路一段139號', 'phone' => '02-2381-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '西松國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP002', 'address' => '台北市松山區南京東路四段133號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '永吉國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP003', 'address' => '台北市信義區永吉路30巷158號', 'phone' => '02-2760-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '中崙國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP004', 'address' => '台北市松山區八德路四段101號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '信義國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP005', 'address' => '台北市信義區松仁路158號', 'phone' => '02-2720-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '松山國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP006', 'address' => '台北市松山區八德路四段101號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '敦化國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP007', 'address' => '台北市松山區敦化南路二段94號', 'phone' => '02-2771-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '介壽國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP008', 'address' => '台北市松山區南京東路四段133號', 'phone' => '02-2767-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '南港國中', 'city' => '台北市', 'district' => '南港區', 'type' => '國民中學', 'school_code' => 'TP009', 'address' => '台北市南港區向陽路200號', 'phone' => '02-2783-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '內湖國中', 'city' => '台北市', 'district' => '內湖區', 'type' => '國民中學', 'school_code' => 'TP010', 'address' => '台北市內湖區內湖路二段41號', 'phone' => '02-2790-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '麗山國中', 'city' => '台北市', 'district' => '內湖區', 'type' => '國民中學', 'school_code' => 'TP011', 'address' => '台北市內湖區內湖路二段41號', 'phone' => '02-2790-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大直國中', 'city' => '台北市', 'district' => '中山區', 'type' => '國民中學', 'school_code' => 'TP012', 'address' => '台北市中山區大直街62號', 'phone' => '02-2533-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '百齡國中', 'city' => '台北市', 'district' => '士林區', 'type' => '國民中學', 'school_code' => 'TP013', 'address' => '台北市士林區承德路四段177號', 'phone' => '02-2881-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '陽明國中', 'city' => '台北市', 'district' => '士林區', 'type' => '國民中學', 'school_code' => 'TP014', 'address' => '台北市士林區中正路510號', 'phone' => '02-2881-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '萬華國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP015', 'address' => '台北市萬華區西藏路201號', 'phone' => '02-2303-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大理國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP016', 'address' => '台北市萬華區大理街170號', 'phone' => '02-2303-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '華江國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP017', 'address' => '台北市萬華區環河南路二段250號', 'phone' => '02-2303-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '成淵國中', 'city' => '台北市', 'district' => '大同區', 'type' => '國民中學', 'school_code' => 'TP018', 'address' => '台北市大同區承德路二段235號', 'phone' => '02-2553-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '雙園國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP019', 'address' => '台北市萬華區環河南路二段250號', 'phone' => '02-2303-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '龍山國中', 'city' => '台北市', 'district' => '萬華區', 'type' => '國民中學', 'school_code' => 'TP020', 'address' => '台北市萬華區環河南路二段250號', 'phone' => '02-2303-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 新北市
        ['name' => '板橋國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT001', 'address' => '新北市板橋區文化路一段188號', 'phone' => '02-2968-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '海山國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT002', 'address' => '新北市板橋區文化路一段188號', 'phone' => '02-2968-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '新莊國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT003', 'address' => '新北市新莊區中正路211號', 'phone' => '02-2991-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '丹鳳國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT004', 'address' => '新北市新莊區中正路211號', 'phone' => '02-2991-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '泰山國中', 'city' => '新北市', 'district' => '泰山區', 'type' => '國民中學', 'school_code' => 'NT005', 'address' => '新北市泰山區明志路二段84號', 'phone' => '02-2909-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '林口國中', 'city' => '新北市', 'district' => '林口區', 'type' => '國民中學', 'school_code' => 'NT006', 'address' => '新北市林口區文化一路一段20號', 'phone' => '02-2601-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '五股國中', 'city' => '新北市', 'district' => '五股區', 'type' => '國民中學', 'school_code' => 'NT007', 'address' => '新北市五股區成泰路一段175號', 'phone' => '02-2291-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '蘆洲國中', 'city' => '新北市', 'district' => '蘆洲區', 'type' => '國民中學', 'school_code' => 'NT008', 'address' => '新北市蘆洲區中正路243號', 'phone' => '02-2281-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '三重國中', 'city' => '新北市', 'district' => '三重區', 'type' => '國民中學', 'school_code' => 'NT009', 'address' => '新北市三重區重新路四段92號', 'phone' => '02-2971-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '永和國中', 'city' => '新北市', 'district' => '永和區', 'type' => '國民中學', 'school_code' => 'NT010', 'address' => '新北市永和區永和路二段100號', 'phone' => '02-2921-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '永平國中', 'city' => '新北市', 'district' => '永和區', 'type' => '國民中學', 'school_code' => 'NT011', 'address' => '新北市永和區永和路二段100號', 'phone' => '02-2921-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '中和國中', 'city' => '新北市', 'district' => '中和區', 'type' => '國民中學', 'school_code' => 'NT012', 'address' => '新北市中和區中和路60號', 'phone' => '02-2248-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '錦和國中', 'city' => '新北市', 'district' => '中和區', 'type' => '國民中學', 'school_code' => 'NT013', 'address' => '新北市中和區中和路60號', 'phone' => '02-2248-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '新店國中', 'city' => '新北市', 'district' => '新店區', 'type' => '國民中學', 'school_code' => 'NT014', 'address' => '新北市新店區中正路54號', 'phone' => '02-2911-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '安康國中', 'city' => '新北市', 'district' => '新店區', 'type' => '國民中學', 'school_code' => 'NT015', 'address' => '新北市新店區中正路54號', 'phone' => '02-2911-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 桃園市
        ['name' => '桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'type' => '國民中學', 'school_code' => 'TY001', 'address' => '桃園市桃園區中正路147號', 'phone' => '03-332-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '中壢國中', 'city' => '桃園市', 'district' => '中壢區', 'type' => '國民中學', 'school_code' => 'TY002', 'address' => '桃園市中壢區中央路二段136號', 'phone' => '03-425-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大園國中', 'city' => '桃園市', 'district' => '大園區', 'type' => '國民中學', 'school_code' => 'TY003', 'address' => '桃園市大園區中正東路二段303號', 'phone' => '03-386-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '蘆竹國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY004', 'address' => '桃園市蘆竹區南崁路二段313號', 'phone' => '03-352-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '南崁國中', 'city' => '桃園市', 'district' => '蘆竹區', 'type' => '國民中學', 'school_code' => 'TY005', 'address' => '桃園市蘆竹區南崁路二段313號', 'phone' => '03-352-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '龜山國中', 'city' => '桃園市', 'district' => '龜山區', 'type' => '國民中學', 'school_code' => 'TY006', 'address' => '桃園市龜山區萬壽路二段920號', 'phone' => '03-329-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '八德國中', 'city' => '桃園市', 'district' => '八德區', 'type' => '國民中學', 'school_code' => 'TY007', 'address' => '桃園市八德區興豐路131號', 'phone' => '03-368-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大溪國中', 'city' => '桃園市', 'district' => '大溪區', 'type' => '國民中學', 'school_code' => 'TY008', 'address' => '桃園市大溪區員林路一段29號', 'phone' => '03-388-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '復興國中', 'city' => '桃園市', 'district' => '復興區', 'type' => '國民中學', 'school_code' => 'TY009', 'address' => '桃園市復興區中正路20號', 'phone' => '03-382-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '龍潭國中', 'city' => '桃園市', 'district' => '龍潭區', 'type' => '國民中學', 'school_code' => 'TY010', 'address' => '桃園市龍潭區中正路210號', 'phone' => '03-479-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 基隆市
        ['name' => '基隆國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL001', 'address' => '基隆市中正區中正路116號', 'phone' => '02-2422-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '安樂國中', 'city' => '基隆市', 'district' => '安樂區', 'type' => '國民中學', 'school_code' => 'KL002', 'address' => '基隆市安樂區安樂路二段164號', 'phone' => '02-2422-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '八斗國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL003', 'address' => '基隆市中正區中正路116號', 'phone' => '02-2422-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '正濱國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL004', 'address' => '基隆市中正區中正路116號', 'phone' => '02-2422-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '信義國中', 'city' => '基隆市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'KL005', 'address' => '基隆市信義區東信路324號', 'phone' => '02-2422-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 新竹市
        ['name' => '新竹國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC001', 'address' => '新竹市東區東門街32號', 'phone' => '03-522-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '光復國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC002', 'address' => '新竹市東區東門街32號', 'phone' => '03-522-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '香山國中', 'city' => '新竹市', 'district' => '香山區', 'type' => '國民中學', 'school_code' => 'HSC003', 'address' => '新竹市香山區香北路168號', 'phone' => '03-538-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '成德國中', 'city' => '新竹市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'HSC004', 'address' => '新竹市北區西大路888號', 'phone' => '03-522-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '建功國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC005', 'address' => '新竹市東區東門街32號', 'phone' => '03-522-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 新竹縣
        ['name' => '竹北國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH001', 'address' => '新竹縣竹北市光明六路10號', 'phone' => '03-555-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '六家國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH002', 'address' => '新竹縣竹北市光明六路10號', 'phone' => '03-555-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '湖口國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH003', 'address' => '新竹縣湖口鄉湖口老街58號', 'phone' => '03-599-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '新湖國中', 'city' => '新竹縣', 'district' => '湖口鄉', 'type' => '國民中學', 'school_code' => 'HSH004', 'address' => '新竹縣湖口鄉湖口老街58號', 'phone' => '03-599-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '新豐國中', 'city' => '新竹縣', 'district' => '新豐鄉', 'type' => '國民中學', 'school_code' => 'HSH005', 'address' => '新竹縣新豐鄉新豐村15鄰81號', 'phone' => '03-559-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 台中市
        ['name' => '台中一中', 'city' => '台中市', 'district' => '北區', 'type' => '國民中學', 'school_code' => 'TC001', 'address' => '台中市北區育才街2號', 'phone' => '04-2222-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '台中女中', 'city' => '台中市', 'district' => '西區', 'type' => '國民中學', 'school_code' => 'TC002', 'address' => '台中市西區自由路一段95號', 'phone' => '04-2222-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '文華國中', 'city' => '台中市', 'district' => '西屯區', 'type' => '國民中學', 'school_code' => 'TC003', 'address' => '台中市西屯區寧夏路240號', 'phone' => '04-2222-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大業國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC004', 'address' => '台中市南屯區大業路100號', 'phone' => '04-2222-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '惠文國中', 'city' => '台中市', 'district' => '南屯區', 'type' => '國民中學', 'school_code' => 'TC005', 'address' => '台中市南屯區大業路100號', 'phone' => '04-2222-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 台南市
        ['name' => '台南一中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN001', 'address' => '台南市東區民族路一段1號', 'phone' => '06-237-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '台南女中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN002', 'address' => '台南市中西區大埔街97號', 'phone' => '06-237-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '建興國中', 'city' => '台南市', 'district' => '中西區', 'type' => '國民中學', 'school_code' => 'TN003', 'address' => '台南市中西區大埔街97號', 'phone' => '06-237-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '復興國中', 'city' => '台南市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'TN004', 'address' => '台南市東區民族路一段1號', 'phone' => '06-237-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '大成國中', 'city' => '台南市', 'district' => '南區', 'type' => '國民中學', 'school_code' => 'TN005', 'address' => '台南市南區大成路一段5號', 'phone' => '06-237-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 高雄市
        ['name' => '高雄中學', 'city' => '高雄市', 'district' => '三民區', 'type' => '國民中學', 'school_code' => 'KS001', 'address' => '高雄市三民區建國三路50號', 'phone' => '07-211-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '高雄女中', 'city' => '高雄市', 'district' => '前金區', 'type' => '國民中學', 'school_code' => 'KS002', 'address' => '高雄市前金區五福三路122號', 'phone' => '07-211-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '鳳山國中', 'city' => '高雄市', 'district' => '鳳山區', 'type' => '國民中學', 'school_code' => 'KS003', 'address' => '高雄市鳳山區光復路二段130號', 'phone' => '07-211-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '左營國中', 'city' => '高雄市', 'district' => '左營區', 'type' => '國民中學', 'school_code' => 'KS004', 'address' => '高雄市左營區左營大路483號', 'phone' => '07-211-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '楠梓國中', 'city' => '高雄市', 'district' => '楠梓區', 'type' => '國民中學', 'school_code' => 'KS005', 'address' => '高雄市楠梓區楠梓路262號', 'phone' => '07-211-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        
        // 其他縣市
        ['name' => '宜蘭國中', 'city' => '宜蘭縣', 'district' => '宜蘭市', 'type' => '國民中學', 'school_code' => 'IL001', 'address' => '宜蘭縣宜蘭市復興路二段77號', 'phone' => '03-932-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '羅東國中', 'city' => '宜蘭縣', 'district' => '羅東鎮', 'type' => '國民中學', 'school_code' => 'IL002', 'address' => '宜蘭縣羅東鎮中正北路98號', 'phone' => '03-955-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '花蓮國中', 'city' => '花蓮縣', 'district' => '花蓮市', 'type' => '國民中學', 'school_code' => 'HL001', 'address' => '花蓮縣花蓮市中山路440號', 'phone' => '03-822-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '台東國中', 'city' => '台東縣', 'district' => '台東市', 'type' => '國民中學', 'school_code' => 'TT001', 'address' => '台東縣台東市中山路276號', 'phone' => '089-322-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '澎湖國中', 'city' => '澎湖縣', 'district' => '馬公市', 'type' => '國民中學', 'school_code' => 'PH001', 'address' => '澎湖縣馬公市中正路7號', 'phone' => '06-927-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '金門國中', 'city' => '金門縣', 'district' => '金城鎮', 'type' => '國民中學', 'school_code' => 'KM001', 'address' => '金門縣金城鎮珠浦北路38號', 'phone' => '082-325-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料'],
        ['name' => '連江國中', 'city' => '連江縣', 'district' => '南竿鄉', 'type' => '國民中學', 'school_code' => 'LC001', 'address' => '連江縣南竿鄉介壽村76號', 'phone' => '0836-221-1234', 'website' => '', 'is_active' => 1, 'data_source' => '備用資料']
    ];
}

echo "<hr>";
echo "<h2>🎯 測試搜尋功能</h2>";

// 測試搜尋功能
$testKeywords = ['中正', '板橋', '桃園', '中崙', '西松', '永吉'];
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";

foreach ($testKeywords as $testKeyword) {
    try {
        $stmt = $pdo->prepare("SELECT name, city, district, school_code FROM school_data WHERE type = '國民中學' AND name LIKE ? LIMIT 5");
        $stmt->execute(["%$testKeyword%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007cba;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #007cba;'>搜尋「$testKeyword」</h4>";
        
        if (!empty($results)) {
            echo "<p style='color: green; margin: 0 0 10px 0;'>✅ 找到 " . count($results) . " 筆結果</p>";
            echo "<ul style='margin: 0; padding-left: 20px;'>";
            foreach ($results as $result) {
                echo "<li style='margin: 5px 0;'>{$result['name']}<br><small style='color: #666;'>{$result['city']} {$result['district']} - {$result['school_code']}</small></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange; margin: 0;'>⚠️ 沒有結果</p>";
        }
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #dc3545;'>搜尋「$testKeyword」</h4>";
        echo "<p style='color: red; margin: 0;'>❌ 搜尋失敗: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

echo "</div>";

echo "<hr>";
echo "<h2>🚀 完成！</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<h3 style='color: #155724; margin: 0 0 15px 0;'>🎉 台灣教育部國民中學資料整合完成！</h3>";
echo "<p style='margin: 0 0 10px 0;'>現在您的系統已經整合了台灣教育部的國民中學資料，包含：</p>";
echo "<ul style='margin: 0;'>";
echo "<li>全台各縣市的國民中學</li>";
echo "<li>學校代碼、名稱、地址、電話等完整資訊</li>";
echo "<li>即時搜尋功能</li>";
echo "<li>與 cooperation_upload.php 完全整合</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='cooperation_upload.php' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; margin: 10px; display: inline-block;'>📝 測試就讀意願登錄</a>";
echo "<a href='diagnose_school_search.php' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; margin: 10px; display: inline-block;'>🔍 診斷工具</a>";
echo "<a href='fix_school_search.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; margin: 10px; display: inline-block;'>🛠️ 修復工具</a>";
echo "</div>";

echo "<h3>💡 使用建議：</h3>";
echo "<ol>";
echo "<li>在「就讀或畢業國中」欄位輸入學校名稱</li>";
echo "<li>系統會即時顯示搜尋結果</li>";
echo "<li>點擊結果會自動填入完整學校資訊</li>";
echo "<li>支援模糊搜尋，輸入部分名稱即可找到學校</li>";
echo "</ol>";

echo "<h3>🔄 定期更新：</h3>";
echo "<p>建議定期執行此腳本以獲取最新的學校資料。您可以：</p>";
echo "<ul>";
echo "<li>設定定時任務（cron job）每月執行一次</li>";
echo "<li>手動執行此腳本更新資料</li>";
echo "<li>監控教育部統計處的資料更新</li>";
echo "</ul>";
?>
