<?php
/**
 * 自動更新腳本 - 從政府開放資料平台獲取最新資料
 * 當API恢復時自動執行
 */

// 增加執行時間限制到 10 分鐘（600秒）
set_time_limit(600);
ini_set('max_execution_time', 600);

// 增加記憶體限制
ini_set('memory_limit', '256M');

require_once "session_config.php";

// 資料庫連接
$host = 'localhost';
$dbname = "topics_good";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 增加資料庫連接超時時間
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 300);
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
    logUpdate("開始清空現有資料...");
    $pdo->exec("DELETE FROM school_data WHERE type = '國民中學'");
    logUpdate("現有資料已清空");
    
    // 批量插入優化（每批 100 筆）
    $batch_size = 100;
    $total = count($schools);
    $inserted_count = 0;
    $batch_count = 0;
    
    logUpdate("開始批量插入資料，總共 $total 筆，每批 $batch_size 筆");
    
    for ($i = 0; $i < $total; $i += $batch_size) {
        $batch = array_slice($schools, $i, $batch_size);
        $batch_count++;
        
        // 構建批量插入 SQL
        $values = [];
        $params = [];
        foreach ($batch as $school) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params[] = $school["name"];
            $params[] = $school["city"];
            $params[] = $school["district"];
            $params[] = $school["type"];
            $params[] = $school["school_code"];
            $params[] = $school["address"];
            $params[] = $school["phone"];
            $params[] = $school["website"];
            $params[] = $school["is_active"];
            $params[] = $school["data_source"];
        }
        
        $sql = "INSERT INTO school_data (
            name, city, district, type, school_code, 
            address, phone, website, is_active, data_source
        ) VALUES " . implode(", ", $values);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inserted_count += count($batch);
        
        // 記錄進度
        if ($batch_count % 5 == 0 || $i + $batch_size >= $total) {
            $progress = round(($inserted_count / $total) * 100, 1);
            logUpdate("進度: $inserted_count/$total ($progress%) - 已處理 $batch_count 批");
        }
        
        // 每批處理後稍作休息，避免資料庫鎖定
        if ($i + $batch_size < $total) {
            usleep(50000); // 0.05 秒，減少鎖定時間
        }
    }
    
    $pdo->commit();
    logUpdate("✅ 成功更新 $inserted_count 所國民中學到資料庫（共 $batch_count 批）");
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logUpdate("❌ 資料庫更新失敗: " . $e->getMessage());
    error_log("自動更新資料庫錯誤: " . $e->getMessage());
    exit(1);
}

logUpdate("自動更新完成");
?>