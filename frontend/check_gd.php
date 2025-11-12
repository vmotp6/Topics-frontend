<?php
/**
 * GD 擴展檢查工具
 * 用於診斷 GD 擴展是否正確啟用
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>GD 擴展檢查</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 PHP GD 擴展檢查工具</h1>";

// 檢查 GD 擴展
$gd_loaded = extension_loaded('gd');

echo "<div class='info'>";
echo "<h2>檢查結果</h2>";
if ($gd_loaded) {
    echo "<p class='success'>✅ GD 擴展已啟用</p>";
    
    // 獲取 GD 信息
    $gd_info = gd_info();
    echo "<h3>GD 擴展詳細信息：</h3>";
    echo "<ul>";
    echo "<li><strong>版本：</strong>" . $gd_info['GD Version'] . "</li>";
    echo "<li><strong>FreeType 支援：</strong>" . ($gd_info['FreeType Support'] ? '是' : '否') . "</li>";
    echo "<li><strong>JPEG 支援：</strong>" . ($gd_info['JPEG Support'] ? '是' : '否') . "</li>";
    echo "<li><strong>PNG 支援：</strong>" . ($gd_info['PNG Support'] ? '是' : '否') . "</li>";
    echo "<li><strong>GIF 支援：</strong>" . ($gd_info['GIF Read Support'] ? '是' : '否') . "</li>";
    echo "</ul>";
    
    // 測試圖片創建
    echo "<h3>功能測試：</h3>";
    $test_image = @imagecreatetruecolor(100, 50);
    if ($test_image) {
        echo "<p class='success'>✅ 可以創建圖片資源</p>";
        imagedestroy($test_image);
    } else {
        echo "<p class='error'>❌ 無法創建圖片資源</p>";
    }
    
} else {
    echo "<p class='error'>❌ GD 擴展未啟用</p>";
    echo "<h3>解決方案：</h3>";
    echo "<ol>";
    echo "<li><strong>找到 php.ini 文件</strong><br>";
    echo "位置通常在：<br>";
    echo "<div class='code'>" . php_ini_loaded_file() . "</div></li>";
    
    echo "<li><strong>編輯 php.ini 文件</strong><br>";
    echo "找到以下行（可能被註釋掉）：<br>";
    echo "<div class='code'>;extension=gd</div>";
    echo "移除前面的分號，改為：<br>";
    echo "<div class='code'>extension=gd</div></li>";
    
    echo "<li><strong>重啟 Web 服務器</strong><br>";
    echo "重啟 Apache 或 Nginx 服務器</li>";
    
    echo "<li><strong>驗證啟用</strong><br>";
    echo "刷新此頁面，應該會看到 ✅ 標記</li>";
    echo "</ol>";
}

echo "</div>";

// PHP 配置信息
echo "<div class='info'>";
echo "<h2>PHP 配置信息</h2>";
echo "<ul>";
echo "<li><strong>PHP 版本：</strong>" . phpversion() . "</li>";
echo "<li><strong>php.ini 位置：</strong>" . php_ini_loaded_file() . "</li>";
echo "<li><strong>已載入的擴展：</strong>" . implode(', ', get_loaded_extensions()) . "</li>";
echo "</ul>";
echo "</div>";

// 測試驗證碼生成
echo "<div class='info'>";
echo "<h2>驗證碼生成測試</h2>";
if ($gd_loaded) {
    echo "<p>點擊下面的連結測試圖片驗證碼：</p>";
    echo "<p><a href='captcha_image.php' target='_blank'>查看圖片驗證碼</a></p>";
    echo "<p><a href='captcha_image.php?debug=1' target='_blank'>查看調試信息</a></p>";
} else {
    echo "<p>由於 GD 擴展未啟用，將使用 HTML 文字驗證碼：</p>";
    echo "<p><a href='captcha_image.php' target='_blank'>查看 HTML 驗證碼</a></p>";
}
echo "</div>";

echo "<p><a href='cooperation_upload.php'>返回表單頁面</a></p>";
echo "</div></body></html>";
?>

