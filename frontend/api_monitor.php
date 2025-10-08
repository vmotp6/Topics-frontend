<?php
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
?>