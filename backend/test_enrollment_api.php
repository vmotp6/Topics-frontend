<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 記錄請求資訊
error_log("API被調用: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
error_log("POST資料: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

// 檢查必要欄位
$required_fields = ['username', 'name', 'identity', 'phone1'];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "缺少必要欄位: $field"]);
        exit;
    }
}

// 模擬成功回應（不連接資料庫）
echo json_encode([
    'success' => true, 
    'message' => '測試成功！就讀意願登錄已收到。',
    'received_data' => $_POST,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
