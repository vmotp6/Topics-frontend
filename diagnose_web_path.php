<?php
echo "<h2>Web 服務器路徑診斷</h2>";

echo "<h3>服務器資訊</h3>";
echo "Web 根目錄: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "當前腳本路徑: " . __FILE__ . "<br>";
echo "當前目錄: " . __DIR__ . "<br>";
echo "服務器名稱: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "服務器端口: " . $_SERVER['SERVER_PORT'] . "<br>";
echo "請求 URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h3>文件檢查</h3>";
$files_to_check = [
    'frontend/ollama_admin.php',
    'frontend/QA.php',
    'backend/api/ollama/ollama_api.php',
    'scripts/database/create_ollama_tables.sql'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file 存在<br>";
    } else {
        echo "❌ $file 不存在<br>";
    }
}

echo "<h3>建議的訪問路徑</h3>";
$base_url = "http://" . $_SERVER['SERVER_NAME'];
if ($_SERVER['SERVER_PORT'] != 80) {
    $base_url .= ":" . $_SERVER['SERVER_PORT'];
}

// 計算相對路徑
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);

echo "基礎 URL: $base_url<br>";
echo "腳本目錄: $script_dir<br>";
echo "相對路徑: $relative_path<br>";

echo "<h3>可能的訪問路徑</h3>";
echo "1. <a href='frontend/ollama_admin.php'>$base_url$script_dir/frontend/ollama_admin.php</a><br>";
echo "2. <a href='frontend/QA.php'>$base_url$script_dir/frontend/QA.php</a><br>";
echo "3. <a href='frontend/test_path.php'>$base_url$script_dir/frontend/test_path.php</a><br>";

echo "<h3>目錄結構</h3>";
function listDirectory($dir, $level = 0) {
    $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level);
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                echo $indent . "📁 " . $item . "/<br>";
                if ($level < 2) { // 限制深度
                    listDirectory($path, $level + 1);
                }
            } else {
                echo $indent . "📄 " . $item . "<br>";
            }
        }
    }
}

listDirectory('.');
?>
