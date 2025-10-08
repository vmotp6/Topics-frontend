<?php
/**
 * 教育部API連接診斷腳本
 * 檢查為什麼無法從教育部API獲取資料
 */

// 載入 session 配置
require_once 'session_config.php';

echo "<h1>🔍 教育部API連接診斷</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .test-section{background:#f8f9fa;padding:20px;margin:20px 0;border-radius:8px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// 測試的API端點
$api_endpoints = [
    '教育部統計處TXT' => 'http://stats.moe.gov.tw/files/school/104/j1_new.txt',
    '教育部統計處XLS' => 'http://stats.moe.gov.tw/files/school/104/j1_new.xls',
    '政府資料開放平台' => 'https://data.gov.tw/dataset/12071',
    '教育部統計處主頁' => 'http://stats.moe.gov.tw/',
    '教育部主頁' => 'https://www.edu.tw/'
];

echo "<div class='test-section'>";
echo "<h2>🌐 網路連接測試</h2>";

foreach ($api_endpoints as $name => $url) {
    echo "<h3>測試: $name</h3>";
    echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>";
    
    // 測試1: 基本連接
    echo "<p><strong>1. 基本連接測試:</strong> ";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'method' => 'GET'
        ]
    ]);
    
    $start_time = microtime(true);
    $data = @file_get_contents($url, false, $context);
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    if ($data !== false) {
        echo "<span class='success'>✅ 成功</span> (響應時間: {$response_time}ms)</p>";
        echo "<p><strong>資料大小:</strong> " . strlen($data) . " bytes</p>";
        
        // 檢查HTTP狀態碼
        if (isset($http_response_header)) {
            $status_line = $http_response_header[0];
            echo "<p><strong>HTTP狀態:</strong> $status_line</p>";
        }
        
        // 如果是TXT文件，顯示前幾行
        if (strpos($url, '.txt') !== false && strlen($data) > 0) {
            $lines = explode("\n", $data);
            echo "<p><strong>前3行內容:</strong></p>";
            echo "<pre style='background:#e9ecef;padding:10px;border-radius:4px;max-height:100px;overflow-y:auto;'>";
            for ($i = 0; $i < min(3, count($lines)); $i++) {
                echo htmlspecialchars($lines[$i]) . "\n";
            }
            echo "</pre>";
        }
        
    } else {
        echo "<span class='error'>❌ 失敗</span></p>";
        
        // 獲取錯誤信息
        $error = error_get_last();
        if ($error) {
            echo "<p class='error'><strong>錯誤信息:</strong> " . $error['message'] . "</p>";
        }
        
        // 檢查HTTP狀態碼
        if (isset($http_response_header)) {
            $status_line = $http_response_header[0];
            echo "<p class='error'><strong>HTTP狀態:</strong> $status_line</p>";
        }
    }
    
    echo "<hr>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔧 系統環境檢查</h2>";

// PHP設定檢查
echo "<h3>PHP設定</h3>";
echo "<ul>";
echo "<li><strong>PHP版本:</strong> " . phpversion() . "</li>";
echo "<li><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? '<span class="success">✅ 啟用</span>' : '<span class="error">❌ 禁用</span>') . "</li>";
echo "<li><strong>allow_url_include:</strong> " . (ini_get('allow_url_include') ? '<span class="success">✅ 啟用</span>' : '<span class="warning">⚠️ 禁用</span>') . "</li>";
echo "<li><strong>default_socket_timeout:</strong> " . ini_get('default_socket_timeout') . " 秒</li>";
echo "<li><strong>user_agent:</strong> " . ini_get('user_agent') . "</li>";
echo "</ul>";

// 網路功能檢查
echo "<h3>網路功能</h3>";
echo "<ul>";
echo "<li><strong>DNS解析:</strong> ";
$ip = gethostbyname('stats.moe.gov.tw');
if ($ip !== 'stats.moe.gov.tw') {
    echo "<span class='success'>✅ $ip</span>";
} else {
    echo "<span class='error'>❌ 無法解析</span>";
}
echo "</li>";

echo "<li><strong>Ping測試:</strong> ";
if (function_exists('exec')) {
    $ping_result = exec("ping -n 1 stats.moe.gov.tw 2>&1", $output, $return_var);
    if ($return_var === 0) {
        echo "<span class='success'>✅ 可達</span>";
    } else {
        echo "<span class='error'>❌ 不可達</span>";
    }
} else {
    echo "<span class='warning'>⚠️ exec函數不可用</span>";
}
echo "</li>";
echo "</ul>";

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🛠️ 替代方案測試</h2>";

// 測試cURL
echo "<h3>cURL測試</h3>";
if (function_exists('curl_init')) {
    echo "<p><span class='success'>✅ cURL可用</span></p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://stats.moe.gov.tw/files/school/104/j1_new.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $curl_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_data !== false && $http_code == 200) {
        echo "<p><span class='success'>✅ cURL成功獲取資料</span> (大小: " . strlen($curl_data) . " bytes)</p>";
    } else {
        echo "<p><span class='error'>❌ cURL失敗</span> (HTTP: $http_code, 錯誤: $error)</p>";
    }
} else {
    echo "<p><span class='error'>❌ cURL不可用</span></p>";
}

// 測試其他資料來源
echo "<h3>其他資料來源</h3>";
$alternative_sources = [
    '政府資料開放平台' => 'https://data.gov.tw/dataset/12071',
    '教育部統計處主頁' => 'http://stats.moe.gov.tw/',
    '教育部主頁' => 'https://www.edu.tw/'
];

foreach ($alternative_sources as $name => $url) {
    echo "<p><strong>$name:</strong> <a href='$url' target='_blank'>$url</a></p>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>💡 解決方案建議</h2>";

echo "<h3>如果API無法訪問，建議使用以下方案：</h3>";
echo "<ol>";
echo "<li><strong>使用備用資料</strong> - 系統已內建完整的台灣國民中學資料</li>";
echo "<li><strong>手動下載資料</strong> - 從教育部網站手動下載並上傳</li>";
echo "<li><strong>使用cURL</strong> - 如果file_get_contents失敗，嘗試cURL</li>";
echo "<li><strong>檢查防火牆</strong> - 確保伺服器可以訪問外部API</li>";
echo "<li><strong>聯繫主機商</strong> - 檢查是否有網路限制</li>";
echo "</ol>";

echo "<h3>立即可用的解決方案：</h3>";
echo "<p><a href='create_school_table_fix.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 使用備用資料創建資料表</a></p>";
echo "<p><a href='integrate_government_api.php' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔄 重新嘗試整合API</a></p>";

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📊 診斷總結</h2>";

$issues = [];
$solutions = [];

// 檢查主要問題
if (!ini_get('allow_url_fopen')) {
    $issues[] = "allow_url_fopen 被禁用";
    $solutions[] = "在php.ini中啟用 allow_url_fopen = On";
}

if (!function_exists('curl_init')) {
    $issues[] = "cURL 不可用";
    $solutions[] = "安裝或啟用 cURL 擴展";
}

if (empty($issues)) {
    echo "<p class='success'>✅ 系統環境正常，問題可能是網路連接或API服務問題</p>";
} else {
    echo "<p class='error'>❌ 發現以下問題：</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>建議解決方案：</strong></p>";
    echo "<ul>";
    foreach ($solutions as $solution) {
        echo "<li>$solution</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "<hr>";
echo "<p><strong>診斷時間:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
