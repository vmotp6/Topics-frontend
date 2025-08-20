<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔧 API 存取測試</h1>\n";

echo "<h2>1. 檢查檔案是否存在</h2>\n";
$files_to_check = [
    'cooperation_upload_api.php',
    'test_cooperation_api.php',
    'quick_fix.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file 存在<br>\n";
    } else {
        echo "❌ $file 不存在<br>\n";
    }
}

echo "<h2>2. 測試 API 回應</h2>\n";
echo "<p>點擊以下連結測試 API：</p>\n";
echo "<ul>\n";
echo "<li><a href='cooperation_upload_api.php' target='_blank'>測試原始 API</a></li>\n";
echo "<li><a href='test_cooperation_api.php' target='_blank'>測試改進 API</a></li>\n";
echo "</ul>\n";

echo "<h2>3. 測試 POST 請求</h2>\n";
echo "<form method='POST' action='test_cooperation_api.php'>\n";
echo "<input type='hidden' name='test' value='1'>\n";
echo "<input type='submit' value='測試 POST 請求'>\n";
echo "</form>\n";

echo "<h2>4. 伺服器資訊</h2>\n";
echo "<p>當前目錄：" . getcwd() . "</p>\n";
echo "<p>請求 URI：" . $_SERVER['REQUEST_URI'] . "</p>\n";
echo "<p>腳本名稱：" . $_SERVER['SCRIPT_NAME'] . "</p>\n";
echo "<p>文件根目錄：" . $_SERVER['DOCUMENT_ROOT'] . "</p>\n";

echo "<h2>5. 路徑測試</h2>\n";
$test_paths = [
    './cooperation_upload_api.php',
    '../backend/cooperation_upload_api.php',
    'cooperation_upload_api.php'
];

foreach ($test_paths as $path) {
    if (file_exists($path)) {
        echo "✅ $path 可存取<br>\n";
    } else {
        echo "❌ $path 不可存取<br>\n";
    }
}

echo "<h2>6. 快速修復</h2>\n";
echo "<p><a href='quick_fix.php' target='_blank'>執行快速修復</a></p>\n";
echo "<p><a href='debug_cooperation_submission.php' target='_blank'>查看詳細診斷</a></p>\n";
?>
