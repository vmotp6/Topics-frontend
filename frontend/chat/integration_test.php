<?php
/**
 * FCM整合測試頁面
 */

session_start();

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'] ?? 'test_user';
$role = $_SESSION['role'] ?? '學生';

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <title>FCM整合測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; }
        .log { background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>";

echo "<h1>🚀 FCM整合測試</h1>";
echo "<p>當前用戶: <strong>$username</strong> | 角色: <strong>$role</strong></p>";

// 測試結果
$testResults = [];

// 1. 測試資料庫連接
echo "<div class='test-section'>";
echo "<h2>1. 資料庫連接測試</h2>";

try {
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ 資料庫連接成功</p>";
    $testResults['database'] = true;
    
} catch(PDOException $e) {
    echo "<p class='error'>❌ 資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
    $testResults['database'] = false;
}
echo "</div>";

// 2. 測試FCM表結構
echo "<div class='test-section'>";
echo "<h2>2. FCM表結構測試</h2>";

if ($testResults['database']) {
    $tables = ['user_fcm_tokens', 'push_notification_log', 'user_notification_settings'];
    $tableResults = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ $table 表存在</p>";
                $tableResults[$table] = true;
            } else {
                echo "<p class='error'>❌ $table 表不存在</p>";
                $tableResults[$table] = false;
            }
        } catch(PDOException $e) {
            echo "<p class='error'>❌ 檢查 $table 表失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
            $tableResults[$table] = false;
        }
    }
    
    $testResults['tables'] = $tableResults;
}
echo "</div>";

// 3. 測試FCM服務
echo "<div class='test-section'>";
echo "<h2>3. FCM服務測試</h2>";

if ($testResults['database'] && isset($testResults['tables']['user_fcm_tokens']) && $testResults['tables']['user_fcm_tokens']) {
    try {
        require_once 'fcm_service.php';
        $fcmService = new FCMService();
        
        echo "<p class='success'>✅ FCM服務類載入成功</p>";
        $testResults['fcm_service'] = true;
        
        // 測試註冊token
        $testToken = 'test-token-' . $username . '-' . time();
        $registerResult = $fcmService->registerUserToken($username, $testToken, 'web', 'integration test');
        
        if ($registerResult['success']) {
            echo "<p class='success'>✅ FCM token註冊成功</p>";
            $testResults['token_registration'] = true;
        } else {
            echo "<p class='error'>❌ FCM token註冊失敗: " . htmlspecialchars($registerResult['error']) . "</p>";
            $testResults['token_registration'] = false;
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ FCM服務測試失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
        $testResults['fcm_service'] = false;
    }
} else {
    echo "<p class='warning'>⚠️ 跳過FCM服務測試（資料庫或表結構問題）</p>";
    $testResults['fcm_service'] = false;
}
echo "</div>";

// 4. 測試API端點
echo "<div class='test-section'>";
echo "<h2>4. API端點測試</h2>";

$apiTests = [
    'get_stats' => 'fcm_api.php?action=get_stats',
    'get_notification_settings' => "fcm_api.php?action=get_notification_settings&username=$username"
];

foreach ($apiTests as $testName => $url) {
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if ($result && isset($result['success'])) {
            echo "<p class='success'>✅ $testName API 正常</p>";
            $testResults["api_$testName"] = true;
        } else {
            echo "<p class='error'>❌ $testName API 異常</p>";
            $testResults["api_$testName"] = false;
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ $testName API 測試失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
        $testResults["api_$testName"] = false;
    }
}
echo "</div>";

// 5. 測試通知功能
echo "<div class='test-section'>";
echo "<h2>5. 通知功能測試</h2>";

echo "<div id='notificationTest'>";
echo "<p>點擊下方按鈕測試瀏覽器通知功能：</p>";
echo "<button class='btn btn-success' onclick='testBrowserNotification()'>🔔 測試瀏覽器通知</button>";
echo "<button class='btn btn-warning' onclick='testFCMNotification()'>📱 測試FCM通知</button>";
echo "<div id='notificationResult' class='log' style='display:none;'></div>";
echo "</div>";
echo "</div>";

// 6. 整合狀態總結
echo "<div class='test-section'>";
echo "<h2>6. 整合狀態總結</h2>";

$totalTests = count($testResults);
$passedTests = array_sum($testResults);

echo "<p>總測試項目: $totalTests</p>";
echo "<p>通過測試: <span class='" . ($passedTests == $totalTests ? 'success' : 'warning') . "'>$passedTests</span></p>";
echo "<p>失敗測試: <span class='" . (($totalTests - $passedTests) == 0 ? 'success' : 'error') . "'>" . ($totalTests - $passedTests) . "</span></p>";

if ($passedTests == $totalTests) {
    echo "<p class='success'><strong>🎉 所有測試通過！FCM整合成功！</strong></p>";
} else {
    echo "<p class='warning'><strong>⚠️ 部分測試失敗，請檢查相關配置</strong></p>";
}
echo "</div>";

// 7. 快速操作
echo "<div class='test-section'>";
echo "<h2>7. 快速操作</h2>";
echo "<p><a href='setup_fcm_database.php' class='btn'>設置FCM資料庫</a></p>";
echo "<p><a href='test_fcm.php' class='btn'>FCM功能測試</a></p>";
echo "<p><a href='chat.php' class='btn btn-success'>前往聊天室</a></p>";
echo "</div>";

echo "
<script>
// 測試瀏覽器通知
function testBrowserNotification() {
    const resultDiv = document.getElementById('notificationResult');
    resultDiv.style.display = 'block';
    resultDiv.textContent = '測試中...';
    
    if (!('Notification' in window)) {
        resultDiv.textContent = '❌ 此瀏覽器不支援通知功能';
        resultDiv.className = 'log error';
        return;
    }
    
    if (Notification.permission === 'granted') {
        const notification = new Notification('🔔 測試通知', {
            body: '這是一條測試通知，FCM整合正常！',
            icon: '/assets/icon-192x192.png'
        });
        
        resultDiv.textContent = '✅ 瀏覽器通知測試成功！';
        resultDiv.className = 'log success';
        
        setTimeout(() => notification.close(), 3000);
    } else if (Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                testBrowserNotification();
            } else {
                resultDiv.textContent = '❌ 用戶拒絕了通知權限';
                resultDiv.className = 'log error';
            }
        });
    } else {
        resultDiv.textContent = '❌ 通知權限被拒絕';
        resultDiv.className = 'log error';
    }
}

// 測試FCM通知
async function testFCMNotification() {
    const resultDiv = document.getElementById('notificationResult');
    resultDiv.style.display = 'block';
    resultDiv.textContent = '測試中...';
    
    try {
        const response = await fetch('fcm_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'test_notification',
                to_user: '$username',
                title: '🔔 FCM測試通知',
                body: '這是一條FCM測試通知，系統整合正常！',
                data: {
                    type: 'integration_test',
                    timestamp: Date.now()
                }
            })
        });
        
        const result = await response.json();
        
        if (result.success > 0) {
            resultDiv.textContent = '✅ FCM通知測試成功！發送成功: ' + result.success + ' 個設備';
            resultDiv.className = 'log success';
        } else {
            resultDiv.textContent = '❌ FCM通知測試失敗: ' + (result.error || '未知錯誤');
            resultDiv.className = 'log error';
        }
        
    } catch (error) {
        resultDiv.textContent = '❌ FCM通知測試失敗: ' + error.message;
        resultDiv.className = 'log error';
    }
}
</script>
</body>
</html>";
?>




