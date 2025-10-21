<?php
echo "測試文件可以正常訪問！<br>";
echo "當前目錄: " . __DIR__ . "<br>";
echo "當前文件: " . __FILE__ . "<br>";
echo "Web 根目錄: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "請求 URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "服務器名稱: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "服務器端口: " . $_SERVER['SERVER_PORT'] . "<br>";

// 檢查 ollama_admin.php 是否存在
if (file_exists('ollama_admin.php')) {
    echo "<br>✅ ollama_admin.php 文件存在<br>";
} else {
    echo "<br>❌ ollama_admin.php 文件不存在<br>";
}

// 列出當前目錄的文件
echo "<br>當前目錄文件列表:<br>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "- " . $file . "<br>";
    }
}
?>
