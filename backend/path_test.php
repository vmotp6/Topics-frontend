<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 API路徑測試工具</h1>\n";

echo "<h2>1. 當前檔案路徑</h2>\n";
echo "<p>當前檔案: " . __FILE__ . "</p>\n";
echo "<p>當前目錄: " . __DIR__ . "</p>\n";

echo "<h2>2. 伺服器資訊</h2>\n";
echo "<p>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</p>\n";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>\n";
echo "<p>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</p>\n";

echo "<h2>3. 測試API檔案是否存在</h2>\n";
$api_files = [
    'cooperation_list_api.php',
    'cooperation_detail_api.php',
    'cooperation_review_api.php',
    'cooperation_stats_api.php'
];

foreach ($api_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "<p>✅ $file 存在於: $full_path</p>\n";
    } else {
        echo "<p>❌ $file 不存在於: $full_path</p>\n";
    }
}

echo "<h2>4. 測試API存取</h2>\n";
$base_url = 'http://' . $_SERVER['HTTP_HOST'];
$current_path = dirname($_SERVER['REQUEST_URI']);

echo "<p>基礎URL: $base_url</p>\n";
echo "<p>當前路徑: $current_path</p>\n";

// 測試相對路徑
echo "<h3>相對路徑測試:</h3>\n";
echo "<p><a href='cooperation_list_api.php' target='_blank'>cooperation_list_api.php (相對路徑)</a></p>\n";

// 測試絕對路徑
echo "<h3>絕對路徑測試:</h3>\n";
echo "<p><a href='$base_url$current_path/cooperation_list_api.php' target='_blank'>$base_url$current_path/cooperation_list_api.php (絕對路徑)</a></p>\n";

// 測試根路徑
echo "<h3>根路徑測試:</h3>\n";
echo "<p><a href='$base_url/backend/cooperation_list_api.php' target='_blank'>$base_url/backend/cooperation_list_api.php (根路徑)</a></p>\n";

echo "<h2>5. 建議的路徑設定</h2>\n";
echo "<p>根據您的伺服器設定，建議使用以下路徑之一：</p>\n";
echo "<ul>\n";
echo "<li><code>cooperation_list_api.php</code> (相對路徑)</li>\n";
echo "<li><code>$base_url$current_path/cooperation_list_api.php</code> (絕對路徑)</li>\n";
echo "<li><code>$base_url/backend/cooperation_list_api.php</code> (根路徑)</li>\n";
echo "</ul>\n";

echo "<h2>6. 立即測試管理介面</h2>\n";
echo "<p><a href='../frontend/admin_cooperation_review.php' target='_blank'>前往管理介面</a></p>\n";
?>
