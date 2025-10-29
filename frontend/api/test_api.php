<?php
// 簡單的測試 API
header('Content-Type: application/json');

try {
    // 載入 session 配置
    require_once '../session_config.php';
    
    // 載入資料庫配置
    require_once '../config.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'API 基本功能正常',
        'session_status' => session_status(),
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'config_loaded' => defined('DB_HOST')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API 測試失敗：' . $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
}
?>
