<?php
/**
 * reCAPTCHA 設定檔案
 * 
 * 要取得您的 reCAPTCHA 金鑰，請前往：
 * https://www.google.com/recaptcha/admin/create
 * 
 * 1. 登入您的 Google 帳號
 * 2. 點擊 "+" 建立新的網站
 * 3. 填寫網站資訊：
 *    - Label: 您的網站名稱
 *    - reCAPTCHA Type: 選擇 "reCAPTCHA v2" > "I'm not a robot Checkbox"
 *    - Domains: 輸入您的網域 (例如: yourdomain.com)
 * 4. 點擊 Submit 後會得到 Site Key 和 Secret Key
 * 5. 將金鑰填入下面的設定中
 */

// reCAPTCHA 設定
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'); // 測試用 Site Key
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // 測試用 Secret Key

// 注意：上面的金鑰是 Google 提供的測試用金鑰，僅供開發測試使用
// 在正式環境中，請替換為您自己申請的金鑰

/**
 * 使用說明：
 * 
 * 1. 將此檔案包含在需要 reCAPTCHA 的 PHP 檔案中：
 *    require_once 'recaptcha_config.php';
 * 
 * 2. 在前端 HTML 中使用 Site Key：
 *    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
 * 
 * 3. 在後端驗證中使用 Secret Key：
 *    verifyRecaptcha($response, RECAPTCHA_SECRET_KEY);
 */
?>