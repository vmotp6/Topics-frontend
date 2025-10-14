<?php
// 郵件診斷工具
require_once 'config.php';
require_once 'config/email_notification_config.php';

// 檢查PHPMailer是否正確載入
$phpmailer_loaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
$smtp_loaded = class_exists('PHPMailer\PHPMailer\SMTP');
$exception_loaded = class_exists('PHPMailer\PHPMailer\Exception');

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>郵件診斷工具 - 康寧大學</title>
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .diagnosis-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .test-form { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 郵件診斷工具</h1>
            <p>康寧大學招生推薦系統 - 郵件功能診斷</p>
        </div>

        <!-- 基本環境檢查 -->
        <div class="diagnosis-section">
            <h3>📋 基本環境檢查</h3>
            
            <div class="status <?php echo $phpmailer_loaded ? 'success' : 'error'; ?>">
                <strong>PHPMailer 類別:</strong> <?php echo $phpmailer_loaded ? '✅ 已載入' : '❌ 未載入'; ?>
            </div>
            
            <div class="status <?php echo $smtp_loaded ? 'success' : 'error'; ?>">
                <strong>SMTP 類別:</strong> <?php echo $smtp_loaded ? '✅ 已載入' : '❌ 未載入'; ?>
            </div>
            
            <div class="status <?php echo $exception_loaded ? 'success' : 'error'; ?>">
                <strong>Exception 類別:</strong> <?php echo $exception_loaded ? '✅ 已載入' : '❌ 未載入'; ?>
            </div>
            
            <div class="status info">
                <strong>PHP 版本:</strong> <?php echo PHP_VERSION; ?>
            </div>
            
            <div class="status info">
                <strong>OpenSSL 支援:</strong> <?php echo extension_loaded('openssl') ? '✅ 已啟用' : '❌ 未啟用'; ?>
            </div>
        </div>

        <!-- SMTP 設定檢查 -->
        <div class="diagnosis-section">
            <h3>⚙️ SMTP 設定檢查</h3>
            
            <div class="status info">
                <strong>SMTP 主機:</strong> <?php echo SMTP_HOST; ?>
            </div>
            
            <div class="status info">
                <strong>SMTP 端口:</strong> <?php echo SMTP_PORT; ?>
            </div>
            
            <div class="status info">
                <strong>SMTP 加密:</strong> <?php echo SMTP_SECURE; ?>
            </div>
            
            <div class="status info">
                <strong>發件人郵件:</strong> <?php echo SMTP_FROM_EMAIL; ?>
            </div>
            
            <div class="status info">
                <strong>發件人名稱:</strong> <?php echo SMTP_FROM_NAME; ?>
            </div>
            
            <div class="status <?php echo !empty(SMTP_USERNAME) ? 'success' : 'error'; ?>">
                <strong>SMTP 使用者名稱:</strong> <?php echo !empty(SMTP_USERNAME) ? '✅ 已設定' : '❌ 未設定'; ?>
            </div>
            
            <div class="status <?php echo !empty(SMTP_PASSWORD) ? 'success' : 'error'; ?>">
                <strong>SMTP 密碼:</strong> <?php echo !empty(SMTP_PASSWORD) ? '✅ 已設定' : '❌ 未設定'; ?>
            </div>
        </div>

        <!-- 網路連線測試 -->
        <div class="diagnosis-section">
            <h3>🌐 網路連線測試</h3>
            
            <?php
            // 測試SMTP主機連線
            $smtp_host = SMTP_HOST;
            $smtp_port = SMTP_PORT;
            
            echo "<div class='status info'>";
            echo "<strong>測試連線到:</strong> {$smtp_host}:{$smtp_port}";
            echo "</div>";
            
            $connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
            if ($connection) {
                echo "<div class='status success'>";
                echo "✅ <strong>連線成功!</strong> 可以連接到 SMTP 伺服器";
                echo "</div>";
                fclose($connection);
            } else {
                echo "<div class='status error'>";
                echo "❌ <strong>連線失敗!</strong> 錯誤: {$errstr} (錯誤代碼: {$errno})";
                echo "</div>";
            }
            ?>
        </div>

        <!-- 詳細郵件測試 -->
        <div class="diagnosis-section">
            <h3>📧 詳細郵件測試</h3>
            
            <div class="test-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="test_email">測試郵件地址:</label>
                        <input type="email" id="test_email" name="test_email" value="vichuang2005@gmail.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="test_name">測試姓名:</label>
                        <input type="text" id="test_name" name="test_name" value="診斷測試" required>
                    </div>
                    
                    <button type="submit" name="detailed_test" class="btn btn-success">🔍 執行詳細測試</button>
                </form>
            </div>
            
            <?php
            if (isset($_POST['detailed_test'])) {
                $test_email = $_POST['test_email'];
                $test_name = $_POST['test_name'];
                
                echo "<div class='diagnosis-section'>";
                echo "<h4>📤 詳細測試結果</h4>";
                
                try {
                    // 創建 PHPMailer 實例
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    
                    echo "<div class='status success'>✅ PHPMailer 實例創建成功</div>";
                    
                    // 設定 SMTP
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port = SMTP_PORT;
                    $mail->CharSet = 'UTF-8';
                    
                    echo "<div class='status success'>✅ SMTP 設定完成</div>";
                    
                    // 啟用除錯模式
                    $mail->SMTPDebug = 2;
                    $mail->Debugoutput = function($str, $level) {
                        echo "<div class='code'>SMTP Debug: " . htmlspecialchars($str) . "</div>";
                    };
                    
                    // 設定發件人
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    echo "<div class='status success'>✅ 發件人設定完成</div>";
                    
                    // 設定收件人
                    $mail->addAddress($test_email, $test_name);
                    echo "<div class='status success'>✅ 收件人設定完成</div>";
                    
                    // 設定郵件內容
                    $mail->isHTML(true);
                    $mail->Subject = '【康寧大學】郵件診斷測試';
                    $mail->Body = "
                        <h2>郵件診斷測試</h2>
                        <p>這是一封診斷測試郵件。</p>
                        <p><strong>測試時間:</strong> " . date('Y-m-d H:i:s') . "</p>
                        <p><strong>收件人:</strong> {$test_name} ({$test_email})</p>
                        <p>如果您收到這封郵件，表示郵件系統運作正常。</p>
                    ";
                    
                    echo "<div class='status success'>✅ 郵件內容設定完成</div>";
                    
                    // 發送郵件
                    $result = $mail->send();
                    
                    if ($result) {
                        echo "<div class='status success'>";
                        echo "🎉 <strong>郵件發送成功!</strong><br>";
                        echo "收件人: {$test_email}<br>";
                        echo "發送時間: " . date('Y-m-d H:i:s');
                        echo "</div>";
                    } else {
                        echo "<div class='status error'>";
                        echo "❌ <strong>郵件發送失敗!</strong>";
                        echo "</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='status error'>";
                    echo "❌ <strong>發送錯誤:</strong><br>";
                    echo htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
                
                echo "</div>";
            }
            ?>
        </div>

        <!-- 常見問題解決方案 -->
        <div class="diagnosis-section">
            <h3>🔧 常見問題解決方案</h3>
            
            <div class="status warning">
                <strong>1. Gmail 應用程式密碼問題</strong><br>
                請確認您使用的是 Gmail 應用程式密碼，而不是 Gmail 帳戶密碼。<br>
                設定步驟：<br>
                • 登入 Google 帳戶 → 安全性 → 兩步驟驗證 → 應用程式密碼<br>
                • 選擇「郵件」和「其他（自訂名稱）」<br>
                • 輸入「康寧大學系統」並複製生成的16位密碼
            </div>
            
            <div class="status warning">
                <strong>2. 兩步驟驗證未啟用</strong><br>
                Gmail 必須啟用兩步驟驗證才能使用應用程式密碼。<br>
                設定步驟：<br>
                • 登入 Google 帳戶 → 安全性 → 兩步驟驗證 → 開始使用
            </div>
            
            <div class="status warning">
                <strong>3. 防火牆或網路限制</strong><br>
                請確認伺服器可以連接到 smtp.gmail.com:587<br>
                如果使用公司網路，可能需要聯繫 IT 部門開放相關端口。
            </div>
            
            <div class="status warning">
                <strong>4. PHP OpenSSL 擴展</strong><br>
                郵件發送需要 OpenSSL 擴展支援。<br>
                如果未啟用，請聯繫系統管理員啟用 PHP OpenSSL 擴展。
            </div>
        </div>

        <!-- 快速修復按鈕 -->
        <div class="diagnosis-section">
            <h3>🚀 快速修復</h3>
            <a href="email_test_simple.php" class="btn">📧 簡單郵件測試</a>
            <a href="test_email_notification.php" class="btn">📋 完整郵件測試</a>
            <a href="admission_recommend.php" class="btn">📝 推薦申請頁面</a>
            <button onclick="location.reload()" class="btn btn-warning">🔄 重新診斷</button>
        </div>
    </div>
</body>
</html>
