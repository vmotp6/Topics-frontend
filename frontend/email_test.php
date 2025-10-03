<?php
/**
 * Gmail 通知測試頁面
 * 用於測試傳送訊息時的 Gmail 通知功能
 */

session_start();
require_once 'config.php';
require_once 'includes/email_functions.php';

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = '';
    $success = false;
    
    switch ($action) {
        case 'test_basic_email':
            // 測試基本郵件發送
            $testEmail = $_POST['test_email'] ?? '';
            if (empty($testEmail)) {
                $result = '❌ 請輸入測試郵箱地址';
            } else {
                $success = testEmailFunction($testEmail);
                $result = $success ? '✅ 基本郵件發送測試成功！請檢查您的郵箱。' : '❌ 基本郵件發送測試失敗，請檢查配置。';
            }
            break;
            
        case 'test_private_message':
            // 測試私訊通知郵件
            $testEmail = $_POST['test_email'] ?? '';
            $fromName = $_POST['from_name'] ?? '測試用戶';
            $messageContent = $_POST['message_content'] ?? '這是一條測試私訊，用於測試 Gmail 通知功能。';
            
            if (empty($testEmail)) {
                $result = '❌ 請輸入測試郵箱地址';
            } else {
                // 模擬私訊通知郵件
                $subject = "康寧大學聊天系統 - 新私訊通知";
                $body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .message-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                        .highlight { color: #667eea; font-weight: bold; }
                        .chat-button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>💬 康寧大學聊天系統</h1>
                            <p>新私訊通知</p>
                        </div>
                        <div class='content'>
                            <h2>您收到了一條新私訊</h2>
                            
                            <div class='message-box'>
                                <h3>📨 來自：{$fromName}</h3>
                                <p><strong>訊息內容：</strong></p>
                                <p style='background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 3px solid #667eea;'>
                                    {$messageContent}
                                </p>
                                <p><small>發送時間：" . date('Y-m-d H:i:s') . "</small></p>
                            </div>
                            
                            <p>請登入聊天系統查看完整對話。</p>
                            
                            <div style='text-align: center;'>
                                <a href='" . (defined('BASE_URL') ? BASE_URL : 'http://100.79.58.120') . "/frontend/chat/chat.php' class='chat-button'>
                                    🔗 前往聊天系統
                                </a>
                            </div>
                            
                            <div class='footer'>
                                <p>康寧大學聊天系統</p>
                                <p>此為測試郵件，用於驗證通知功能</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $success = sendEmail($testEmail, $subject, $body);
                $result = $success ? '✅ 私訊通知郵件發送成功！請檢查您的郵箱。' : '❌ 私訊通知郵件發送失敗，請檢查配置。';
            }
            break;
            
        case 'test_discord_style':
            // 測試 Discord 風格通知
            $testEmail = $_POST['test_email'] ?? '';
            if (empty($testEmail)) {
                $result = '❌ 請輸入測試郵箱地址';
            } else {
                $subject = "康寧大學聊天系統 - Discord風格通知測試";
                $body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; background: #36393f; color: #dcddde; }
                        .discord-container { max-width: 600px; margin: 0 auto; background: #2f3136; border-radius: 8px; overflow: hidden; }
                        .discord-header { background: #5865f2; padding: 20px; text-align: center; }
                        .discord-content { padding: 30px; }
                        .discord-message { background: #40444b; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #5865f2; }
                        .discord-footer { background: #2c2f33; padding: 15px; text-align: center; color: #72767d; }
                    </style>
                </head>
                <body>
                    <div class='discord-container'>
                        <div class='discord-header'>
                            <h1>🔔 Discord風格通知測試</h1>
                        </div>
                        <div class='discord-content'>
                            <div class='discord-message'>
                                <h3>📢 系統通知</h3>
                                <p>這是一封 Discord 風格的通知測試郵件。</p>
                                <p>如果您收到此郵件，說明通知系統工作正常！</p>
                                <p><strong>測試時間：</strong>" . date('Y-m-d H:i:s') . "</p>
                            </div>
                            
                            <div class='discord-message'>
                                <h3>🎯 功能測試</h3>
                                <p>✅ 郵件發送功能</p>
                                <p>✅ HTML 格式支援</p>
                                <p>✅ 樣式渲染</p>
                                <p>✅ 時間戳記</p>
                            </div>
                        </div>
                        <div class='discord-footer'>
                            <p>康寧大學聊天系統 - Discord風格通知</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $success = sendEmail($testEmail, $subject, $body);
                $result = $success ? '✅ Discord風格通知郵件發送成功！請檢查您的郵箱。' : '❌ Discord風格通知郵件發送失敗，請檢查配置。';
            }
            break;
            
        case 'check_config':
            // 檢查配置狀態
            $configStatus = [];
            
            // 檢查 SMTP 配置
            $configStatus['smtp_host'] = defined('SMTP_HOST') && !empty(SMTP_HOST) ? '✅' : '❌';
            $configStatus['smtp_port'] = defined('SMTP_PORT') && !empty(SMTP_PORT) ? '✅' : '❌';
            $configStatus['smtp_username'] = defined('SMTP_USERNAME') && !empty(SMTP_USERNAME) && SMTP_USERNAME !== 'your-email@gmail.com' ? '✅' : '❌';
            $configStatus['smtp_password'] = defined('SMTP_PASSWORD') && !empty(SMTP_PASSWORD) && SMTP_PASSWORD !== 'your-app-password' ? '✅' : '❌';
            $configStatus['smtp_from_email'] = defined('SMTP_FROM_EMAIL') && !empty(SMTP_FROM_EMAIL) && SMTP_FROM_EMAIL !== 'your-email@gmail.com' ? '✅' : '❌';
            
            // 檢查 PHPMailer
            $phpmailerAvailable = checkPHPMailerAvailability();
            $configStatus['phpmailer'] = $phpmailerAvailable ? '✅' : '❌';
            
            $result = '<h3>📋 配置狀態檢查</h3>';
            $result .= '<ul>';
            $result .= '<li>SMTP Host: ' . $configStatus['smtp_host'] . '</li>';
            $result .= '<li>SMTP Port: ' . $configStatus['smtp_port'] . '</li>';
            $result .= '<li>SMTP Username: ' . $configStatus['smtp_username'] . '</li>';
            $result .= '<li>SMTP Password: ' . $configStatus['smtp_password'] . '</li>';
            $result .= '<li>SMTP From Email: ' . $configStatus['smtp_from_email'] . '</li>';
            $result .= '<li>PHPMailer: ' . $configStatus['phpmailer'] . '</li>';
            $result .= '</ul>';
            
            $allConfigured = !in_array('❌', $configStatus);
            $result .= $allConfigured ? '<p style="color: green;">✅ 所有配置都已正確設置！</p>' : '<p style="color: red;">❌ 部分配置需要完善，請檢查 config.php 文件。</p>';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail 通知測試 - 康寧大學聊天系統</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #667eea;
        }
        
        .test-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online {
            background: #28a745;
        }
        
        .status-offline {
            background: #dc3545;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-action {
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .quick-action:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .quick-action h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .quick-action p {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Gmail 通知測試</h1>
            <p>測試傳送訊息時的 Gmail 通知功能</p>
        </div>
        
        <div class="content">
            <?php if (isset($result)): ?>
                <div class="result <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo $result; ?>
                </div>
            <?php endif; ?>
            
            <!-- 快速操作 -->
            <div class="quick-actions">
                <div class="quick-action" onclick="document.getElementById('config-check').scrollIntoView()">
                    <h4>🔧 檢查配置</h4>
                    <p>檢查 Gmail 配置狀態</p>
                </div>
                <div class="quick-action" onclick="document.getElementById('basic-test').scrollIntoView()">
                    <h4>📧 基本測試</h4>
                    <p>測試基本郵件發送</p>
                </div>
                <div class="quick-action" onclick="document.getElementById('private-test').scrollIntoView()">
                    <h4>💬 私訊測試</h4>
                    <p>測試私訊通知郵件</p>
                </div>
                <div class="quick-action" onclick="document.getElementById('discord-test').scrollIntoView()">
                    <h4>🎮 Discord風格</h4>
                    <p>測試 Discord 風格通知</p>
                </div>
            </div>
            
            <!-- 配置檢查 -->
            <div class="test-section" id="config-check">
                <h3>🔧 配置狀態檢查</h3>
                <p class="help-text">檢查 Gmail SMTP 配置是否正確設置</p>
                <form method="POST">
                    <input type="hidden" name="action" value="check_config">
                    <button type="submit" class="btn">檢查配置狀態</button>
                </form>
            </div>
            
            <!-- 基本郵件測試 -->
            <div class="test-section" id="basic-test">
                <h3>📧 基本郵件發送測試</h3>
                <p class="help-text">測試基本的郵件發送功能，確認 SMTP 配置是否正確</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_basic_email">
                    <div class="form-group">
                        <label for="test_email">測試郵箱地址：</label>
                        <input type="email" id="test_email" name="test_email" required 
                               placeholder="your-email@example.com">
                        <div class="help-text">請輸入您要接收測試郵件的郵箱地址</div>
                    </div>
                    <button type="submit" class="btn">發送測試郵件</button>
                </form>
            </div>
            
            <!-- 私訊通知測試 -->
            <div class="test-section" id="private-test">
                <h3>💬 私訊通知郵件測試</h3>
                <p class="help-text">模擬發送私訊時的 Gmail 通知郵件</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_private_message">
                    <div class="form-group">
                        <label for="test_email2">測試郵箱地址：</label>
                        <input type="email" id="test_email2" name="test_email" required 
                               placeholder="your-email@example.com">
                    </div>
                    <div class="form-group">
                        <label for="from_name">發送者姓名：</label>
                        <input type="text" id="from_name" name="from_name" 
                               value="測試用戶" placeholder="發送者姓名">
                    </div>
                    <div class="form-group">
                        <label for="message_content">測試訊息內容：</label>
                        <textarea id="message_content" name="message_content" 
                                  placeholder="輸入測試訊息內容...">這是一條測試私訊，用於測試 Gmail 通知功能。如果您收到此郵件，說明私訊通知系統工作正常！</textarea>
                    </div>
                    <button type="submit" class="btn">發送私訊通知測試</button>
                </form>
            </div>
            
            <!-- Discord 風格通知測試 -->
            <div class="test-section" id="discord-test">
                <h3>🎮 Discord 風格通知測試</h3>
                <p class="help-text">測試 Discord 風格的 Gmail 通知郵件</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_discord_style">
                    <div class="form-group">
                        <label for="test_email3">測試郵箱地址：</label>
                        <input type="email" id="test_email3" name="test_email" required 
                               placeholder="your-email@example.com">
                    </div>
                    <button type="submit" class="btn">發送 Discord 風格測試</button>
                </form>
            </div>
            
            <!-- 使用說明 -->
            <div class="test-section">
                <h3>📖 使用說明</h3>
                <div style="line-height: 1.8;">
                    <h4>🔧 配置要求：</h4>
                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>在 <code>config.php</code> 中正確配置 Gmail SMTP 設定</li>
                        <li>設置 Gmail 應用程式密碼（非登入密碼）</li>
                        <li>確保 PHPMailer 已正確安裝</li>
                    </ul>
                    
                    <h4>🧪 測試步驟：</h4>
                    <ol style="margin-left: 20px; margin-bottom: 15px;">
                        <li>首先點擊「檢查配置狀態」確認設定正確</li>
                        <li>使用「基本郵件發送測試」驗證 SMTP 連接</li>
                        <li>使用「私訊通知郵件測試」模擬實際使用場景</li>
                        <li>使用「Discord 風格通知測試」測試特殊格式</li>
                    </ol>
                    
                    <h4>⚠️ 注意事項：</h4>
                    <ul style="margin-left: 20px;">
                        <li>請使用真實的郵箱地址進行測試</li>
                        <li>檢查垃圾郵件文件夾</li>
                        <li>如果測試失敗，請檢查 PHP 錯誤日誌</li>
                        <li>確保伺服器可以連接到 Gmail SMTP 伺服器</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // 自動填充郵箱地址
        document.addEventListener('DOMContentLoaded', function() {
            const emailInputs = document.querySelectorAll('input[type="email"]');
            const firstEmail = emailInputs[0];
            
            if (firstEmail) {
                firstEmail.addEventListener('input', function() {
                    const value = this.value;
                    emailInputs.forEach(input => {
                        if (input !== this && !input.value) {
                            input.value = value;
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>

