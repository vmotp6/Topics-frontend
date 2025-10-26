<?php
/**
 * 狀態檢查 API
 * 最簡單的狀態檢查工具
 */

// 開啟錯誤顯示以便調試
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // 基本狀態檢查
    $status = [
        'success' => true,
        'message' => '狀態檢查成功',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_status' => 'running',
        'php_version' => phpversion(),
        'current_time' => time(),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ];
    
    // 檢查基本目錄
    $current_dir = __DIR__;
    $api_dir = $current_dir . '/api/voice';
    
    $status['directories'] = [
        'current_dir' => $current_dir,
        'api_dir' => $api_dir,
        'api_dir_exists' => is_dir($api_dir),
        'api_dir_readable' => is_dir($api_dir) ? is_readable($api_dir) : false
    ];
    
    // 檢查關鍵文件
    $key_files = [
        'test_api.php' => $api_dir . '/test_api.php',
        '簡化環境檢查API.php' => $api_dir . '/簡化環境檢查API.php',
        '診斷API.php' => $api_dir . '/診斷API.php'
    ];
    
    $status['files'] = [];
    foreach ($key_files as $name => $path) {
        $status['files'][$name] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => file_exists($path) ? is_readable($path) : false,
            'size' => file_exists($path) ? filesize($path) : 0
        ];
    }
    
    // 檢查環境變量
    $status['environment'] = [
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'php_sapi' => php_sapi_name()
    ];
    
    echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
