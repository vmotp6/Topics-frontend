<?php
/**
 * 頁面健康檢查腳本
 * 檢查所有主要頁面是否正常工作
 */

echo "🔍 頁面健康檢查開始...\n";
echo "========================\n\n";

// 要檢查的頁面列表
$pages = [
    'index.php' => '首頁',
    'QA.php' => 'Q&A頁面',
    'records.php' => '記錄頁面',
    'admission.php' => '入學頁面',
    'cooperation_upload.php' => '合作上傳頁面',
    'activity_records_management.php' => '活動記錄管理',
    'my_records.php' => '我的記錄',
    'AI.php' => 'AI頁面',
    'teacher.php' => '老師頁面',
    'teacher_profile.php' => '老師個人資料',
    'chat/chat.php' => '聊天頁面'
];

$baseUrl = 'http://localhost/Topics-frontend/frontend/';
$results = [];

foreach ($pages as $page => $description) {
    echo "檢查 $description ($page)... ";
    
    $url = $baseUrl . $page;
    
    // 使用 cURL 檢查頁面
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Health Check Bot');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ 連接錯誤: $error\n";
        $results[$page] = ['status' => 'error', 'message' => $error];
    } elseif ($httpCode == 200) {
        // 檢查是否包含PHP錯誤
        if (strpos($response, 'Fatal error') !== false || 
            strpos($response, 'Warning:') !== false ||
            strpos($response, 'Parse error') !== false) {
            echo "❌ 包含PHP錯誤\n";
            $results[$page] = ['status' => 'php_error', 'message' => '包含PHP錯誤'];
        } else {
            echo "✅ 正常\n";
            $results[$page] = ['status' => 'ok', 'message' => '頁面正常'];
        }
    } else {
        echo "❌ HTTP錯誤: $httpCode\n";
        $results[$page] = ['status' => 'http_error', 'message' => "HTTP $httpCode"];
    }
}

echo "\n📊 檢查結果摘要:\n";
echo "================\n";

$okCount = 0;
$errorCount = 0;

foreach ($results as $page => $result) {
    $status = $result['status'];
    $message = $result['message'];
    
    if ($status === 'ok') {
        echo "✅ $page: $message\n";
        $okCount++;
    } else {
        echo "❌ $page: $message\n";
        $errorCount++;
    }
}

echo "\n📈 統計:\n";
echo "正常頁面: $okCount\n";
echo "有問題頁面: $errorCount\n";
echo "總頁面數: " . count($pages) . "\n";

if ($errorCount == 0) {
    echo "\n🎉 所有頁面都正常工作！\n";
} else {
    echo "\n⚠️  有 $errorCount 個頁面需要修復\n";
}

echo "\n💡 建議:\n";
echo "- 如果看到連接錯誤，請確認XAMPP/WAMP正在運行\n";
echo "- 如果看到PHP錯誤，請檢查錯誤日誌\n";
echo "- 確保所有必要的文件都存在\n";
?>

