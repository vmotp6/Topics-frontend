<?php
// CSS快取控制檔案
header('Content-Type: text/css');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 根據請求的檔案名稱載入對應的CSS
$file = $_GET['file'] ?? '';

switch($file) {
    case 'voice_styles':
        include 'voice_styles.css';
        break;
    case 'chat':
        include '../assets/csp/chat.css';
        break;
    case 'color_schemes':
        include 'color_schemes.css';
        break;
    default:
        http_response_code(404);
        break;
}
?>
