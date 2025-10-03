<?php
/**
 * 簡單驗證碼生成器
 */

session_start();

// 生成隨機驗證碼
$captcha_code = '';
for ($i = 0; $i < 4; $i++) {
    $captcha_code .= rand(0, 9);
}

// 將驗證碼存儲到session中
$_SESSION['captcha_code'] = $captcha_code;

// 檢查GD擴展是否可用
if (!extension_loaded('gd')) {
    // 如果GD不可用，返回一個簡單的HTML驗證碼
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="width:120px;height:40px;background:#f0f0f0;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;color:#333;font-family:monospace;">' . $captcha_code . '</div>';
    exit;
}

// 創建圖片
$width = 120;
$height = 40;
$image = imagecreate($width, $height);

if (!$image) {
    // 如果圖片創建失敗，返回HTML驗證碼
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="width:120px;height:40px;background:#f0f0f0;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;color:#333;font-family:monospace;">' . $captcha_code . '</div>';
    exit;
}

// 設置顏色
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 0, 0, 0);
$line_color = imagecolorallocate($image, 200, 200, 200);

// 填充背景
imagefill($image, 0, 0, $bg_color);

// 添加干擾線
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// 添加驗證碼文字
$font_size = 5;
$x = ($width - strlen($captcha_code) * imagefontwidth($font_size)) / 2;
$y = ($height - imagefontheight($font_size)) / 2;

imagestring($image, $font_size, $x, $y, $captcha_code, $text_color);

// 設置header
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 輸出圖片
imagepng($image);
imagedestroy($image);
?>
