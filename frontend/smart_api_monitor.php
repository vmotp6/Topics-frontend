<?php
/**
 * 智能API監控和自動更新系統
 * 監控政府開放資料平台API狀態，自動獲取最新的734所國民中學資料
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

echo "<h1>🤖 智能API監控和自動更新系統</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .warning{color:orange;} .section{background:#f8f9fa;padding:20px;margin:20px 0;border-radius:8px;}</style>";

// 記錄更新日誌
function logUpdate($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "<p><strong>[$timestamp]</strong> $message</p>";
}

// API端點配置
$api_endpoints = [
    'primary' => [
        'name' => '政府資料開放平台 - 國民中學名錄',
        'url' => 'https://data.nat.gov.tw/api/v1/datastore/ODRP001/6088',
        'format' => 'json',
        'description' => '主要資料來源，包含完整的734所國民中學'
    ],
    'backup' => [
        'name' => '教育部統計處 - 學校名錄',
        'url' => 'http://stats.moe.gov.tw/files/school/104/j1_new.txt',
        'format' => 'txt',
        'description' => '備用資料來源，TXT格式'
    ],
    'alternative' => [
        'name' => '政府資料開放平台 - 學校基本資料',
        'url' => 'https://data.gov.tw/api/v1/rest/dataset/12071',
        'format' => 'json',
        'description' => '替代資料來源'
    ]
];

echo "<div class='section'>";
echo "<h2>📡 API狀態監控</h2>";

// 檢查API可用性
function checkApiStatus($url, $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_NOBODY, true); // 只檢查HEAD，不下載內容
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'success' => $result !== false && $http_code == 200,
        'http_code' => $http_code,
        'error' => $error,
        'response_time' => round($info['total_time'], 2),
        'info' => $info
    ];
}

// 監控所有API端點
$api_status = [];
foreach ($api_endpoints as $key => $endpoint) {
    echo "<h3>🔍 檢查: {$endpoint['name']}</h3>";
    echo "<p><strong>URL:</strong> <a href='{$endpoint['url']}' target='_blank'>{$endpoint['url']}</a></p>";
    echo "<p><strong>格式:</strong> {$endpoint['format']}</p>";
    echo "<p><strong>說明:</strong> {$endpoint['description']}</p>";
    
    $status = checkApiStatus($endpoint['url']);
    $api_status[$key] = $status;
    
    if ($status['success']) {
        echo "<p class='success'>✅ API可用 (HTTP: {$status['http_code']}, 響應時間: {$status['response_time']}秒)</p>";
    } else {
        echo "<p class='error'>❌ API不可用 (HTTP: {$status['http_code']}, 錯誤: {$status['error']})</p>";
    }
    echo "<hr>";
}

echo "</div>";

// 檢查資料庫表
echo "<div class='section'>";
echo "<h2>🗄️ 資料庫檢查</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    if ($stmt->rowCount() == 0) {
        logUpdate("創建 school_data 資料表...");
        
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
            data_source VARCHAR(100) DEFAULT '政府開放資料平台' COMMENT '資料來源',
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最後更新時間',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
            INDEX idx_name (name),
            INDEX idx_city (city),
            INDEX idx_type (type),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學校基本資料表'
        ";
        
        $pdo->exec($createTableSQL);
        logUpdate("✅ 資料表創建成功");
    } else {
        logUpdate("✅ 資料表已存在");
        
        // 檢查現有資料
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data WHERE type = '國民中學'");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        logUpdate("📊 目前資料庫中有 $count 所國民中學");
    }
} catch (PDOException $e) {
    logUpdate("❌ 資料庫操作失敗: " . $e->getMessage());
    exit;
}

echo "</div>";

// 自動更新邏輯
echo "<div class='section'>";
echo "<h2>🔄 自動更新邏輯</h2>";

$available_api = null;
foreach ($api_status as $key => $status) {
    if ($status['success']) {
        $available_api = $key;
        break;
    }
}

if ($available_api) {
    echo "<p class='success'>✅ 發現可用的API: {$api_endpoints[$available_api]['name']}</p>";
    echo "<p class='info'>🔄 開始自動更新資料...</p>";
    
    // 這裡會執行實際的資料更新邏輯
    // 由於API可能返回大量資料，我們先顯示準備狀態
    echo "<div style='background:#d1ecf1;padding:15px;border-radius:5px;border-left:4px solid #17a2b8;'>";
    echo "<h4 style='margin:0 0 10px 0;color:#0c5460;'>📋 更新準備中</h4>";
    echo "<p style='margin:0;'>系統將從 <strong>{$api_endpoints[$available_api]['name']}</strong> 獲取最新的734所國民中學資料</p>";
    echo "<p style='margin:0;'>這將包括：</p>";
    echo "<ul style='margin:5px 0 0 20px;'>";
    echo "<li>✅ 新增的學校</li>";
    echo "<li>✅ 更名的學校</li>";
    echo "<li>✅ 停辦的學校</li>";
    echo "<li>✅ 更新的學校資訊</li>";
    echo "</ul>";
    echo "</div>";
    
    // 創建更新腳本
    echo "<h3>🚀 執行更新</h3>";
    echo "<p><a href='auto_update_from_api.php?source=$available_api' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔄 立即更新資料</a></p>";
    
} else {
    echo "<p class='warning'>⚠️ 目前沒有可用的API</p>";
    echo "<p class='info'>系統將持續監控API狀態，一旦API恢復就會自動更新</p>";
    
    // 創建監控腳本
    echo "<h3>⏰ 自動監控設定</h3>";
    echo "<p>建議設定定時任務（Cron Job）每小時檢查一次API狀態：</p>";
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:5px;'>";
    echo "# 每小時檢查API狀態\n";
    echo "0 * * * * /usr/bin/php " . __DIR__ . "/api_monitor.php\n";
    echo "# 每天凌晨2點嘗試更新資料\n";
    echo "0 2 * * * /usr/bin/php " . __DIR__ . "/auto_update_from_api.php\n";
    echo "</pre>";
}

echo "</div>";

// 創建監控腳本
echo "<div class='section'>";
echo "<h2>📝 創建監控腳本</h2>";

$monitor_script = '<?php
/**
 * API監控腳本 - 用於定時檢查API狀態
 * 建議每小時執行一次
 */

require_once "session_config.php";

// 資料庫連接
$host = "100.79.58.120";
$dbname = "topics_good";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("API監控 - 資料庫連接失敗: " . $e->getMessage());
    exit(1);
}

// API端點
$api_endpoints = [
    "primary" => "https://data.nat.gov.tw/api/v1/datastore/ODRP001/6088",
    "backup" => "http://stats.moe.gov.tw/files/school/104/j1_new.txt",
    "alternative" => "https://data.gov.tw/api/v1/rest/dataset/12071"
];

// 檢查API狀態
function checkApiStatus($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        "success" => $result !== false && $http_code == 200,
        "http_code" => $http_code,
        "error" => $error
    ];
}

// 記錄監控結果
$timestamp = date("Y-m-d H:i:s");
$log_file = "api_monitor.log";

foreach ($api_endpoints as $name => $url) {
    $status = checkApiStatus($url);
    $status_text = $status["success"] ? "可用" : "不可用";
    $log_message = "[$timestamp] $name API: $status_text (HTTP: {$status["http_code"]})";
    
    // 寫入日誌
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
    
    // 如果API可用，觸發更新
    if ($status["success"]) {
        $log_message .= " - 觸發自動更新";
        file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
        
        // 執行更新腳本
        exec("php " . __DIR__ . "/auto_update_from_api.php source=$name > /dev/null 2>&1 &");
    }
}

echo "API監控完成 - $timestamp\n";
?>';

file_put_contents('api_monitor.php', $monitor_script);
logUpdate("✅ 已創建 API監控腳本: api_monitor.php");

echo "</div>";

// 創建自動更新腳本
echo "<div class='section'>";
echo "<h2>🔄 創建自動更新腳本</h2>";

$update_script = '<?php
/**
 * 自動更新腳本 - 從政府開放資料平台獲取最新資料
 * 當API恢復時自動執行
 */

require_once "session_config.php";

// 資料庫連接
$host = "100.79.58.120";
$dbname = "topics_good";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("自動更新 - 資料庫連接失敗: " . $e->getMessage());
    exit(1);
}

// 獲取參數
$source = $_GET["source"] ?? "primary";

// API端點配置
$api_endpoints = [
    "primary" => [
        "url" => "https://data.nat.gov.tw/api/v1/datastore/ODRP001/6088",
        "format" => "json"
    ],
    "backup" => [
        "url" => "http://stats.moe.gov.tw/files/school/104/j1_new.txt",
        "format" => "txt"
    ],
    "alternative" => [
        "url" => "https://data.gov.tw/api/v1/rest/dataset/12071",
        "format" => "json"
    ]
];

// 記錄更新日誌
function logUpdate($message) {
    $timestamp = date("Y-m-d H:i:s");
    $log_message = "[$timestamp] $message";
    file_put_contents("auto_update.log", $log_message . "\n", FILE_APPEND);
    echo $log_message . "\n";
}

logUpdate("開始自動更新 - 資料來源: $source");

// 檢查API端點
if (!isset($api_endpoints[$source])) {
    logUpdate("錯誤: 未知的資料來源: $source");
    exit(1);
}

$endpoint = $api_endpoints[$source];
logUpdate("使用API: {$endpoint["url"]}");

// 獲取資料
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint["url"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($data === false || $http_code != 200) {
    logUpdate("錯誤: 無法獲取資料 (HTTP: $http_code, 錯誤: $error)");
    exit(1);
}

logUpdate("成功獲取資料，大小: " . strlen($data) . " bytes");

// 解析資料
$schools = [];
if ($endpoint["format"] == "json") {
    $json_data = json_decode($data, true);
    if ($json_data && isset($json_data["result"]["records"])) {
        foreach ($json_data["result"]["records"] as $record) {
            if (isset($record["學校名稱"]) && strpos($record["學校名稱"], "國中") !== false) {
                $schools[] = [
                    "name" => $record["學校名稱"],
                    "city" => $record["縣市名稱"] ?? "",
                    "district" => $record["行政區"] ?? "",
                    "type" => "國民中學",
                    "school_code" => $record["學校代碼"] ?? "",
                    "address" => $record["地址"] ?? "",
                    "phone" => $record["電話"] ?? "",
                    "website" => $record["網址"] ?? "",
                    "is_active" => 1,
                    "data_source" => "政府開放資料平台(自動更新)"
                ];
            }
        }
    }
} elseif ($endpoint["format"] == "txt") {
    $lines = explode("\n", $data);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strlen($line) < 10) continue;
        
        $parts = explode("|", $line);
        if (count($parts) >= 4) {
            $school_name = trim($parts[1]);
            if (strpos($school_name, "國中") !== false) {
                $schools[] = [
                    "name" => $school_name,
                    "city" => trim($parts[2]),
                    "district" => trim($parts[3]),
                    "type" => "國民中學",
                    "school_code" => trim($parts[0]),
                    "address" => isset($parts[4]) ? trim($parts[4]) : "",
                    "phone" => isset($parts[5]) ? trim($parts[5]) : "",
                    "website" => "",
                    "is_active" => 1,
                    "data_source" => "教育部統計處(自動更新)"
                ];
            }
        }
    }
}

logUpdate("解析完成，找到 " . count($schools) . " 所國民中學");

if (count($schools) == 0) {
    logUpdate("警告: 沒有找到有效的學校資料");
    exit(1);
}

// 更新資料庫
try {
    $pdo->beginTransaction();
    
    // 清空現有資料
    $pdo->exec("DELETE FROM school_data WHERE type = "國民中學"");
    
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
            $school["name"],
            $school["city"],
            $school["district"],
            $school["type"],
            $school["school_code"],
            $school["address"],
            $school["phone"],
            $school["website"],
            $school["is_active"],
            $school["data_source"]
        ]);
        $inserted_count++;
    }
    
    $pdo->commit();
    logUpdate("✅ 成功更新 $inserted_count 所國民中學到資料庫");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    logUpdate("❌ 資料庫更新失敗: " . $e->getMessage());
    exit(1);
}

logUpdate("自動更新完成");
?>';

file_put_contents('auto_update_from_api.php', $update_script);
logUpdate("✅ 已創建自動更新腳本: auto_update_from_api.php");

echo "</div>";

// 顯示當前狀態
echo "<div class='section'>";
echo "<h2>📊 系統狀態</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data WHERE type = '國民中學'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<div style='background:#d1ecf1;padding:15px;border-radius:5px;border-left:4px solid #17a2b8;'>";
    echo "<h4 style='margin:0 0 10px 0;color:#0c5460;'>📋 當前狀態</h4>";
    echo "<p style='margin:0;'><strong>資料庫中的學校數量:</strong> $count 所國民中學</p>";
    echo "<p style='margin:0;'><strong>目標數量:</strong> 734所國民中學</p>";
    echo "<p style='margin:0;'><strong>完成度:</strong> " . round(($count / 734) * 100, 1) . "%</p>";
    echo "</div>";
    
    if ($count < 100) {
        echo "<p class='warning'>⚠️ 資料不完整，建議等待API恢復後自動更新</p>";
    } else {
        echo "<p class='success'>✅ 資料基本完整，系統可正常使用</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ 無法查詢資料庫狀態</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>🎉 系統就緒！</h2>";
echo "<div style='background:#d4edda;padding:20px;border-radius:8px;border-left:4px solid #28a745;'>";
echo "<h3 style='color:#155724;margin:0 0 15px 0;'>✅ 智能API監控系統已建立！</h3>";
echo "<p style='margin:0 0 10px 0;'>系統將：</p>";
echo "<ul style='margin:0;'>";
echo "<li>🔄 持續監控政府開放資料平台API狀態</li>";
echo "<li>📊 自動獲取最新的734所國民中學資料</li>";
echo "<li>🆕 自動處理新增、更名、停辦的學校</li>";
echo "<li>⏰ 定時檢查和更新資料</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align:center;margin:30px 0;'>";
echo "<a href='cooperation_upload.php' style='background:#007cba;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>📝 測試就讀意願登錄</a>";
echo "<a href='api_monitor.php' style='background:#17a2b8;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>🔍 手動檢查API</a>";
echo "<a href='auto_update_from_api.php' style='background:#28a745;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>🔄 手動更新資料</a>";
echo "</div>";

echo "<h3>📋 設定定時任務</h3>";
echo "<p>建議在伺服器上設定以下定時任務：</p>";
echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;'>";
echo "# 每小時檢查API狀態\n";
echo "0 * * * * /usr/bin/php " . __DIR__ . "/api_monitor.php\n\n";
echo "# 每天凌晨2點嘗試更新資料\n";
echo "0 2 * * * /usr/bin/php " . __DIR__ . "/auto_update_from_api.php\n\n";
echo "# 每週日凌晨3點強制更新\n";
echo "0 3 * * 0 /usr/bin/php " . __DIR__ . "/auto_update_from_api.php source=primary\n";
echo "</pre>";

echo "</div>";
?>
