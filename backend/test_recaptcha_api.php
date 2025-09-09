<?php
// 載入 reCAPTCHA 設定
require_once 'recaptcha_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只支援POST請求']);
    exit;
}

// reCAPTCHA 驗證函數
function verifyRecaptcha($response, $secret_key) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return false;
    }
    
    $json = json_decode($result, true);
    return $json['success'] === true;
}

try {
    // 驗證 reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptcha_response)) {
        echo json_encode(['success' => false, 'message' => '缺少 reCAPTCHA 回應']);
        exit;
    }
    
    if (!verifyRecaptcha($recaptcha_response, RECAPTCHA_SECRET_KEY)) {
        echo json_encode(['success' => false, 'message' => 'reCAPTCHA 驗證失敗，請重新驗證']);
        exit;
    }
    
    // 檢查必要欄位
    $required_fields = ['name', 'email', 'message'];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "缺少必要欄位: $field"]);
            exit;
        }
    }
    
    // 模擬處理表單資料
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    
    // 這裡可以加入實際的資料處理邏輯
    // 例如：儲存到資料庫、發送郵件等
    
    echo json_encode([
        'success' => true,
        'message' => "測試成功！收到來自 $name ($email) 的訊息：$message"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '伺服器錯誤：' . $e->getMessage()
    ]);
}
?>