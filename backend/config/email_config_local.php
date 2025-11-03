<?php
/**
 * Gmail 郵件服務本地配置檔案
 * 
 * 使用說明：
 * 1. 填入您的實際Gmail設定
 * 2. 將此檔案加入 .gitignore 避免洩露敏感資訊
 */

// 覆蓋預設配置
$email_config = array_merge($email_config, [
    // Gmail SMTP 設定
    'smtp_username' => 'your-actual-email@gmail.com',  // 您的Gmail地址
    'smtp_password' => 'your-actual-app-password',     // 您的Gmail應用程式密碼
    'sender_email' => 'your-actual-email@gmail.com',   // 發送者郵箱
    'sender_name' => '康寧大學招生平台',                // 發送者名稱
    
    // 網站設定
    'base_url' => 'http://localhost/Topics-frontend',   // 您的網站基礎URL
    
    // 郵件設定
    'enable_notifications' => true,                    // 是否啟用郵件通知
    'max_message_length' => 200,                       // 郵件中顯示的最大訊息長度
    
    // 測試設定
    'test_mode' => false,                              // 測試模式
    'test_email' => 'test@example.com'                 // 測試郵箱
]);

/*
Gmail 應用程式密碼設定步驟：

1. 登入您的 Google 帳戶
2. 前往「安全性」設定：https://myaccount.google.com/security
3. 在「登入 Google」部分，點擊「2 步驟驗證」
4. 如果尚未啟用，請先啟用 2 步驟驗證
5. 在「2 步驟驗證」頁面底部，點擊「應用程式密碼」
6. 選擇「郵件」和您的裝置
7. 複製生成的 16 位元密碼
8. 將此密碼填入上方的 'smtp_password' 欄位

注意：
- 應用程式密碼是 16 位元，不包含空格
- 請妥善保管此密碼，不要分享給他人
- 如果懷疑密碼洩露，請立即重新生成新的應用程式密碼
*/
?>












