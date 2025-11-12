<?php
/**
 * 圖片驗證碼生成器
 * 生成包含變形字母和數字的圖片驗證碼
 */

// 載入 session 配置（如果存在）
if (file_exists(__DIR__ . '/session_config.php')) {
    require_once __DIR__ . '/session_config.php';
} else {
    // 如果沒有 session_config.php，直接啟動 session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 調試模式（通過 URL 參數 debug=1 啟用）
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// 生成隨機驗證碼（5-6位，包含字母和數字，增加長度提高難度）
function generateCaptchaCode($length = 6) {
    // 排除容易混淆的字符：0, O, 1, I, L
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// 檢查GD擴展是否可用
if (!extension_loaded('gd')) {
    // 如果GD不可用，返回一個簡單的HTML驗證碼
    $captcha_code = generateCaptchaCode();
    $_SESSION['captcha_code'] = $captcha_code;
    
    if ($debug_mode) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error' => 'GD extension not loaded',
            'captcha_code' => $captcha_code,
            'fallback' => 'html',
            'gd_loaded' => false
        ]);
    } else {
        // 返回一個可嵌入的 HTML div（作為圖片替代）
        if (!headers_sent()) {
            header('Content-Type: image/svg+xml; charset=utf-8');
        }
        // 使用 SVG 創建帶有變形和干擾的文字驗證碼（更難被機器人識別）
        $svg = '<svg width="150" height="50" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <filter id="noise">
                    <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="1" result="noise"/>
                    <feDisplacementMap in="SourceGraphic" in2="noise" scale="1"/>
                </filter>
            </defs>
            <!-- 背景 -->
            <rect width="150" height="50" fill="#f5f5f5" stroke="#ccc" stroke-width="2" rx="5"/>
            <!-- 背景噪點 -->
            ';
        
        // 添加隨機噪點
        for ($i = 0; $i < 30; $i++) {
            $x = rand(5, 145);
            $y = rand(5, 45);
            $size = rand(1, 2);
            $opacity = rand(20, 60) / 100;
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $size . '" fill="#999" opacity="' . $opacity . '"/>';
        }
        
        // 添加干擾線
        for ($i = 0; $i < 3; $i++) {
            $x1 = rand(10, 140);
            $y1 = rand(10, 40);
            $x2 = rand(10, 140);
            $y2 = rand(10, 40);
            $opacity = rand(20, 40) / 100;
            $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#ccc" stroke-width="1" opacity="' . $opacity . '"/>';
        }
        
        // 添加驗證碼文字（每個字符都有隨機變形）
        $char_width = 20;
        $start_x = (150 - (strlen($captcha_code) * $char_width)) / 2;
        $base_y = 32;
        
        for ($i = 0; $i < strlen($captcha_code); $i++) {
            $char = $captcha_code[$i];
            $x = $start_x + ($i * $char_width) + rand(-3, 3);
            $y = $base_y + rand(-5, 5);
            $angle = rand(-20, 20);
            $font_size = rand(18, 22);
            $colors = ['#333', '#555', '#222', '#444'];
            $color = $colors[rand(0, count($colors) - 1)];
            
            $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . $font_size . '" font-weight="bold" fill="' . $color . '" transform="rotate(' . $angle . ' ' . $x . ' ' . $y . ')">' . htmlspecialchars($char) . '</text>';
        }
        
        $svg .= '</svg>';
        echo $svg;
    }
    exit;
}

// 生成驗證碼
$captcha_code = generateCaptchaCode();
$_SESSION['captcha_code'] = $captcha_code;

// 圖片尺寸
$width = 150;
$height = 50;

// 創建圖片
$image = @imagecreatetruecolor($width, $height);

if (!$image) {
    // 如果圖片創建失敗，返回HTML驗證碼
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    
    if ($debug_mode) {
        echo json_encode([
            'error' => 'Failed to create image',
            'captcha_code' => $captcha_code,
            'fallback' => 'html'
        ]);
    } else {
        echo '<div style="width:150px;height:50px;background:#f0f0f0;border:2px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:bold;color:#333;font-family:monospace;letter-spacing:3px;">' . htmlspecialchars($captcha_code) . '</div>';
    }
    exit;
}

// 設置顏色
$bg_color = imagecolorallocate($image, 245, 245, 245);
$text_colors = [
    imagecolorallocate($image, 50, 50, 50),
    imagecolorallocate($image, 100, 50, 50),
    imagecolorallocate($image, 50, 100, 50),
    imagecolorallocate($image, 50, 50, 100),
    imagecolorallocate($image, 100, 100, 50),
];
$line_color = imagecolorallocate($image, 200, 200, 200);
$noise_color = imagecolorallocate($image, 180, 180, 180);

// 填充背景
imagefill($image, 0, 0, $bg_color);

// 添加背景噪點（增加數量，讓機器人更難識別）
for ($i = 0; $i < 200; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// 添加干擾線（增加數量）
for ($i = 0; $i < 8; $i++) {
    $x1 = rand(0, $width);
    $y1 = rand(0, $height);
    $x2 = rand(0, $width);
    $y2 = rand(0, $height);
    imageline($image, $x1, $y1, $x2, $y2, $line_color);
}

// 添加曲線干擾（如果支援）
if (function_exists('imagearc')) {
    for ($i = 0; $i < 3; $i++) {
        $cx = rand($width/4, 3*$width/4);
        $cy = rand($height/4, 3*$height/4);
        $w = rand(20, 40);
        $h = rand(20, 40);
        imagearc($image, $cx, $cy, $w, $h, 0, 360, $line_color);
    }
}

// 添加驗證碼文字（帶變形效果，增加難度）
$font_size = 5;
$char_width = imagefontwidth($font_size);
$char_height = imagefontheight($font_size);
$total_width = strlen($captcha_code) * $char_width;
$start_x = ($width - $total_width) / 2;
$start_y = ($height - $char_height) / 2;

for ($i = 0; $i < strlen($captcha_code); $i++) {
    $char = $captcha_code[$i];
    $x = $start_x + ($i * $char_width);
    $y = $start_y;
    
    // 增加旋轉角度範圍（-25到25度，讓機器人更難識別）
    $angle = rand(-25, 25);
    
    // 增加Y位置偏移範圍（-5到5像素）
    $y_offset = rand(-5, 5);
    
    // 隨機選擇文字顏色
    $text_color = $text_colors[rand(0, count($text_colors) - 1)];
    
    // 如果支援角度旋轉，使用imagettftext（需要字體文件）
    // 否則使用簡單的imagestring並添加位置變化
    if (function_exists('imagettftext') && file_exists(__DIR__ . '/assets/fonts/arial.ttf')) {
        // 使用TrueType字體（如果可用）
        imagettftext($image, 18, $angle, $x, $y + $y_offset, $text_color, __DIR__ . '/assets/fonts/arial.ttf', $char);
    } else {
        // 使用內建字體，通過位置變化模擬變形
        $x_offset = rand(-3, 3);
        imagestring($image, $font_size, $x + $x_offset, $y + $y_offset, $char, $text_color);
        
        // 添加額外的干擾：在字符周圍添加噪點
        for ($j = 0; $j < 3; $j++) {
            $noise_x = $x + $x_offset + rand(-5, 5);
            $noise_y = $y + $y_offset + rand(-5, 5);
            if ($noise_x >= 0 && $noise_x < $width && $noise_y >= 0 && $noise_y < $height) {
                imagesetpixel($image, $noise_x, $noise_y, $noise_color);
            }
        }
    }
}

// 添加更多干擾點（增加數量）
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// 添加隨機大小的干擾圓點
for ($i = 0; $i < 10; $i++) {
    $x = rand(0, $width);
    $y = rand(0, $height);
    $radius = rand(1, 3);
    if (function_exists('imagefilledellipse')) {
        imagefilledellipse($image, $x, $y, $radius, $radius, $noise_color);
    } else {
        imagesetpixel($image, $x, $y, $noise_color);
    }
}

// 設置header（確保在輸出前設置）
if (!headers_sent()) {
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// 調試模式：輸出 JSON 信息
if ($debug_mode) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'captcha_code' => $captcha_code,
        'session_id' => session_id(),
        'gd_loaded' => extension_loaded('gd'),
        'image_created' => $image !== false
    ]);
    if ($image) {
        imagedestroy($image);
    }
    exit;
}

// 輸出圖片
@imagepng($image);
@imagedestroy($image);
exit; // 確保腳本結束
?>

