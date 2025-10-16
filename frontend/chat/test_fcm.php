<?php
/**
 * Firebase Cloud Messaging 測試頁面
 */

// 引入FCM服務
require_once 'fcm_service.php';

echo "<h1>Firebase Cloud Messaging 測試</h1>";

// 處理測試請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $fcmService = new FCMService();
        
        switch ($action) {
            case 'register_token':
                $result = $fcmService->registerUserToken(
                    $_POST['username'],
                    $_POST['fcm_token'],
                    $_POST['device_type'] ?? 'web',
                    $_POST['device_info'] ?? null
                );
                echo "<div style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
                echo "<h3>" . ($result['success'] ? '✅' : '❌') . " " . ($result['message'] ?? $result['error']) . "</h3>";
                echo "</div>";
                break;
                
            case 'test_chat_notification':
                $result = $fcmService->sendChatNotification(
                    $_POST['to_user'],
                    $_POST['from_user'],
                    $_POST['message']
                );
                echo "<div style='background: " . ($result['success'] > 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
                echo "<h3>" . ($result['success'] > 0 ? '✅' : '❌') . " 推播通知測試結果</h3>";
                if ($result['success'] > 0) {
                    echo "<p>成功發送: {$result['success']} 個設備</p>";
                    if (isset($result['failure']) && $result['failure'] > 0) {
                        echo "<p>失敗: {$result['failure']} 個設備</p>";
                    }
                } else {
                    echo "<p>錯誤: " . ($result['error'] ?? '未知錯誤') . "</p>";
                }
                echo "</div>";
                break;
                
            case 'test_custom_notification':
                // 獲取用戶的FCM tokens
                $host = '100.79.58.120';
                $dbname = 'topics_good';
                $db_username = 'root';
                $db_password = '';
                
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->prepare("SELECT fcm_token FROM user_fcm_tokens WHERE username = ? AND is_active = TRUE");
                $stmt->execute([$_POST['to_user']]);
                $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($tokens)) {
                    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
                    echo "<h3>❌ 用戶沒有註冊FCM token</h3>";
                    echo "</div>";
                } else {
                    $result = $fcmService->sendNotification(
                        $tokens,
                        $_POST['title'],
                        $_POST['body'],
                        ['type' => 'test', 'custom_data' => 'test_value']
                    );
                    
                    echo "<div style='background: " . ($result['success'] > 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
                    echo "<h3>" . ($result['success'] > 0 ? '✅' : '❌') . " 自定義通知測試結果</h3>";
                    if ($result['success'] > 0) {
                        echo "<p>成功發送: {$result['success']} 個設備</p>";
                    } else {
                        echo "<p>錯誤: " . ($result['error'] ?? '未知錯誤') . "</p>";
                    }
                    echo "</div>";
                }
                break;
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>❌ 錯誤</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

// 顯示統計信息
try {
    $fcmService = new FCMService();
    $stats = $fcmService->getNotificationStats();
    
    echo "<h2>推播統計</h2>";
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
    echo "<p><strong>總推播數:</strong> {$stats['total']}</p>";
    echo "<p><strong>成功發送:</strong> {$stats['sent']}</p>";
    echo "<p><strong>發送失敗:</strong> {$stats['failed']}</p>";
    echo "<p><strong>等待中:</strong> {$stats['pending']}</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p>無法獲取統計信息: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FCM 測試</title>
    <meta charset="UTF-8">
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            max-width: 600px;
        }
        .form-section h3 {
            margin-top: 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background: #4285f4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #3367d6;
        }
        .btn-success {
            background: #34a853;
        }
        .btn-success:hover {
            background: #2d8f47;
        }
        .btn-warning {
            background: #fbbc04;
            color: #333;
        }
        .btn-warning:hover {
            background: #f9ab00;
        }
    </style>
</head>
<body>
    
    <h2>1. 註冊FCM Token</h2>
    <div class="form-section">
        <form method="POST">
            <input type="hidden" name="action" value="register_token">
            
            <div class="form-group">
                <label>用戶名：</label>
                <input type="text" name="username" required placeholder="輸入用戶名">
            </div>
            
            <div class="form-group">
                <label>FCM Token：</label>
                <input type="text" name="fcm_token" required placeholder="輸入FCM token">
            </div>
            
            <div class="form-group">
                <label>設備類型：</label>
                <select name="device_type">
                    <option value="web">網頁</option>
                    <option value="android">Android</option>
                    <option value="ios">iOS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>設備信息（可選）：</label>
                <textarea name="device_info" rows="3" placeholder="設備詳細信息..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">📱 註冊Token</button>
        </form>
    </div>
    
    <h2>2. 測試聊天通知</h2>
    <div class="form-section">
        <form method="POST">
            <input type="hidden" name="action" value="test_chat_notification">
            
            <div class="form-group">
                <label>接收者用戶名：</label>
                <input type="text" name="to_user" required placeholder="接收者的用戶名">
            </div>
            
            <div class="form-group">
                <label>發送者用戶名：</label>
                <input type="text" name="from_user" required placeholder="發送者的用戶名">
            </div>
            
            <div class="form-group">
                <label>測試訊息：</label>
                <textarea name="message" rows="4" required placeholder="輸入測試訊息...">這是一條測試推播通知訊息！</textarea>
            </div>
            
            <button type="submit" class="btn">💬 發送聊天通知</button>
        </form>
    </div>
    
    <h2>3. 測試自定義通知</h2>
    <div class="form-section">
        <form method="POST">
            <input type="hidden" name="action" value="test_custom_notification">
            
            <div class="form-group">
                <label>接收者用戶名：</label>
                <input type="text" name="to_user" required placeholder="接收者的用戶名">
            </div>
            
            <div class="form-group">
                <label>通知標題：</label>
                <input type="text" name="title" required placeholder="通知標題" value="🔔 系統測試通知">
            </div>
            
            <div class="form-group">
                <label>通知內容：</label>
                <textarea name="body" rows="4" required placeholder="通知內容...">這是一條自定義測試通知，用於驗證FCM功能是否正常工作。</textarea>
            </div>
            
            <button type="submit" class="btn btn-warning">🔔 發送自定義通知</button>
        </form>
    </div>
    
    <h2>設置說明</h2>
    <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 20px 0;">
        <h3>📋 Firebase設置步驟</h3>
        <ol>
            <li>前往 <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
            <li>創建新專案或選擇現有專案</li>
            <li>在專案設定中啟用 Cloud Messaging</li>
            <li>獲取 Server Key（在專案設定 → Cloud Messaging → Server Key）</li>
            <li>設置環境變數：<code>FIREBASE_SERVER_KEY=your-server-key</code></li>
        </ol>
        
        <h3>🔧 測試步驟</h3>
        <ol>
            <li>先運行 <a href="setup_fcm_database.php">setup_fcm_database.php</a> 設置資料庫</li>
            <li>註冊一個測試FCM token</li>
            <li>測試發送通知</li>
            <li>檢查手機或瀏覽器是否收到通知</li>
        </ol>
    </div>
    
    <h2>相關連結</h2>
    <ul>
        <li><a href="setup_fcm_database.php">設置FCM資料庫</a></li>
        <li><a href="fcm_api.php">FCM API文檔</a></li>
        <li><a href="chat.php">返回聊天室</a></li>
    </ul>
    
</body>
</html>














