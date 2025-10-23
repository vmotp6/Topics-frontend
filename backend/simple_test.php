<?php
/**
 * 簡單測試 API
 * 用於驗證基本Web服務器功能
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
    $response = [
        'success' => true,
        'message' => '簡單測試API正常工作',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown'
        ],
        'path_info' => [
            'current_dir' => __DIR__,
            'parent_dir' => dirname(__DIR__),
            'api_dir_exists' => is_dir(__DIR__ . '/api'),
            'voice_dir_exists' => is_dir(__DIR__ . '/api/voice')
        ],
        'test_files' => [
            'test_api_exists' => file_exists(__DIR__ . '/api/voice/test_api.php'),
            'diagnosis_api_exists' => file_exists(__DIR__ . '/api/voice/診斷API.php'),
            'simple_env_api_exists' => file_exists(__DIR__ . '/api/voice/簡化環境檢查API.php')
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>
