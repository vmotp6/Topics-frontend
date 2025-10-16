<?php
// 直接測試 API 回應
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='zh-TW'>
<head>
    <meta charset='UTF-8'>
    <title>直接 API 測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .result { background: #f0f8ff; padding: 10px; margin: 10px 0; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>直接 API 測試</h1>";

// 模擬 API 調用
$_GET['action'] = 'search';
$_GET['keyword'] = '永吉';

echo "<h2>模擬 API 調用參數：</h2>";
echo "<div class='result'>action: " . $_GET['action'] . "<br>keyword: " . $_GET['keyword'] . "</div>";

// 直接包含 API 檔案並捕獲輸出
ob_start();
include 'school_data_api.php';
$api_output = ob_get_clean();

echo "<h2>API 原始回應：</h2>";
echo "<pre>" . htmlspecialchars($api_output) . "</pre>";

// 解析 JSON
$data = json_decode($api_output, true);

echo "<h2>解析後的資料：</h2>";
if ($data && isset($data['schools'])) {
    echo "<div class='result'>找到 " . count($data['schools']) . " 個學校</div>";
    
    foreach ($data['schools'] as $index => $school) {
        echo "<div class='result'>";
        echo "<strong>學校 " . ($index + 1) . "：</strong><br>";
        echo "名稱: " . htmlspecialchars($school['name']) . "<br>";
        echo "城市: " . htmlspecialchars($school['city']) . "<br>";
        echo "區域: " . htmlspecialchars($school['district']) . "<br>";
        if (isset($school['all_names'])) {
            echo "所有名稱: " . implode(', ', $school['all_names']) . "<br>";
        }
        echo "</div>";
    }
} else {
    echo "<div class='result'>無法解析 API 回應</div>";
}

echo "</body></html>";
?>


