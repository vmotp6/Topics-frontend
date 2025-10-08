<?php
// 簡單的SMTP測試腳本
require_once 'config.php';

echo "<h2>SMTP 連線測試</h2>";

// 測試基本連線
$smtp_host = SMTP_HOST;
$smtp_port = SMTP_PORT;

echo "<p>測試連線到: {$smtp_host}:{$smtp_port}</p>";

$connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($connection) {
    echo "<p style='color: green;'>✅ 連線成功! 可以連接到 SMTP 伺服器</p>";
    fclose($connection);
} else {
    echo "<p style='color: red;'>❌ 連線失敗! 錯誤: {$errstr} (錯誤代碼: {$errno})</p>";
}

// 測試PHPMailer載入
echo "<h3>PHPMailer 載入測試</h3>";

try {
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    require_once 'PHPMailer/src/Exception.php';
    
    echo "<p style='color: green;'>✅ PHPMailer 檔案載入成功</p>";
    
    // 測試類別是否存在
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color: green;'>✅ PHPMailer 類別存在</p>";
    } else {
        echo "<p style='color: red;'>❌ PHPMailer 類別不存在</p>";
    }
    
    if (class_exists('PHPMailer\PHPMailer\SMTP')) {
        echo "<p style='color: green;'>✅ SMTP 類別存在</p>";
    } else {
        echo "<p style='color: red;'>❌ SMTP 類別不存在</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ PHPMailer 載入失敗: " . $e->getMessage() . "</p>";
}

// 測試基本郵件發送
echo "<h3>基本郵件發送測試</h3>";

try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // 啟用除錯模式
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px 0; font-family: monospace;'>" . htmlspecialchars($str) . "</div>";
    };
    
    // SMTP 設定
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    
    // 發件人設定
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    
    // 收件人設定
    $mail->addAddress('vichuang2005@gmail.com', '測試用戶');
    
    // 郵件內容
    $mail->isHTML(true);
    $mail->Subject = 'SMTP 測試郵件';
    $mail->Body = '<h2>SMTP 測試</h2><p>這是一封測試郵件，發送時間：' . date('Y-m-d H:i:s') . '</p>';
    
    // 發送郵件
    $result = $mail->send();
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>🎉 郵件發送成功!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ 郵件發送失敗!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ 發送錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='email_diagnosis.php'>返回詳細診斷工具</a></p>";
echo "<p><a href='email_test_simple.php'>返回簡單郵件測試</a></p>";
?>
