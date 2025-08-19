<?php
echo "🔍 測試Flask登入API...\n\n";

// 測試帳號
$username = 'teacher1';
$password = '123456';

echo "測試帳號：$username\n";
echo "測試密碼：$password\n\n";

// 準備POST資料
$postData = http_build_query([
    'username' => $username,
    'password' => $password
]);

echo "發送資料：$postData\n\n";

// 設定請求選項
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData,
        'timeout' => 10
    ]
];

$context = stream_context_create($options);

// 發送請求到Flask API
$url = 'http://localhost:5000/login';
echo "發送請求到：$url\n\n";

$result = file_get_contents($url, false, $context);

if ($result === false) {
    echo "❌ 無法連接到Flask API\n";
    echo "錯誤信息：" . error_get_last()['message'] . "\n";
    echo "請確認Flask服務是否正在運行\n";
} else {
    echo "✅ 收到Flask API回應：\n";
    echo $result . "\n";
    
    // 解析JSON回應
    $response = json_decode($result, true);
    if ($response) {
        if (isset($response['message'])) {
            echo "\n📋 回應訊息：" . $response['message'] . "\n";
        }
        if (isset($response['username'])) {
            echo "👤 用戶名：" . $response['username'] . "\n";
        }
        if (isset($response['role'])) {
            echo "🎭 角色：" . $response['role'] . "\n";
        }
    }
}

// 檢查HTTP回應標頭
$http_response_header_info = $http_response_header[0] ?? 'No response headers';
echo "\n📋 HTTP回應標頭：" . $http_response_header_info . "\n";
?>
