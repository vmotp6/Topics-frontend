<?php
// 載入 Gmail 郵件配置
require_once '../../backend/config/email_config.php';

// 測試郵件發送功能
function testEmailSending() {
    $config = getEmailConfig();
    
    echo "<h2>Gmail 郵件發送測試</h2>";
    echo "<p>當前配置：</p>";
    echo "<ul>";
    echo "<li>SMTP Host: " . $config['smtp_host'] . "</li>";
    echo "<li>SMTP Port: " . $config['smtp_port'] . "</li>";
    echo "<li>發送者郵箱: " . $config['sender_email'] . "</li>";
    echo "<li>發送者名稱: " . $config['sender_name'] . "</li>";
    echo "<li>啟用通知: " . ($config['enable_notifications'] ? '是' : '否') . "</li>";
    echo "</ul>";
    
    // 測試郵件內容
    $testData = [
        'name' => '測試用戶',
        'identity' => '學生',
        'gender' => '男',
        'phone1' => '0912345678',
        'phone2' => '',
        'email' => 'test@example.com',
        'intention1' => '護理科',
        'intention2' => '嬰幼兒保育科',
        'intention3' => '視光科',
        'system1' => '五專',
        'system2' => '五專',
        'system3' => '五專',
        'junior_high' => '測試國中',
        'current_grade' => '國三',
        'line_id' => 'test_line_id',
        'facebook' => 'test_facebook',
        'recommended_teacher' => '測試老師',
        'remarks' => '這是一封測試郵件'
    ];
    
    // 設定收件人（請替換為您的測試郵箱）
    $to_email = 'your-test-email@gmail.com'; // 請替換為您的測試郵箱
    
    // 郵件主題
    $subject = '【測試】康寧大學就讀意願登錄確認通知';
    
    // 郵件內容
    $message = "
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .field { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #667eea; }
            .label { font-weight: bold; color: #667eea; }
            .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>🎓 康寧大學就讀意願登錄確認（測試）</h2>
        </div>
        
        <div class='content'>
            <p>親愛的 {$testData['name']}，您好！</p>
            <p>這是一封測試郵件，用於驗證 Gmail 發送功能是否正常運作。</p>
            <p>感謝您對康寧大學的關注！我們已收到您的就讀意願登錄，詳細資訊如下：</p>
            
            <div class='field'>
                <span class='label'>姓名：</span>{$testData['name']}
            </div>
            
            <div class='field'>
                <span class='label'>身分別：</span>{$testData['identity']}
            </div>
            
            <div class='field'>
                <span class='label'>聯絡電話：</span>{$testData['phone1']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願一：</span>{$testData['intention1']} - {$testData['system1']}
            </div>
            
            <div class='field'>
                <span class='label'>提交時間：</span>" . date('Y-m-d H:i:s') . "
            </div>
        </div>
        
        <div class='footer'>
            <p>此郵件由康寧大學招生平台自動發送，請勿直接回覆。</p>
        </div>
    </body>
    </html>
    ";
    
    // 郵件標頭
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $config['sender_name'] . ' <' . $config['sender_email'] . '>',
        'Reply-To: ' . $config['sender_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // 發送郵件
    $result = mail($to_email, $subject, $message, implode("\r\n", $headers));
    
    if ($result) {
        echo "<p style='color: green;'>✅ 測試郵件發送成功！請檢查您的郵箱。</p>";
    } else {
        echo "<p style='color: red;'>❌ 測試郵件發送失敗！請檢查 Gmail 配置。</p>";
    }
    
    return $result;
}

// 執行測試
testEmailSending();
?>

<p><strong>注意：</strong></p>
<ol>
    <li>請先編輯 <code>backend/config/email_config_local.php</code> 檔案，填入您的實際 Gmail 設定</li>
    <li>將上面的 <code>your-test-email@gmail.com</code> 替換為您的測試郵箱</li>
    <li>確保您的 Gmail 帳號已啟用 2 步驟驗證並生成了應用程式密碼</li>
</ol>
