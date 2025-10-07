<?php
/**
 * 簡化版就讀意願表單 - 無需登入
 */

// 檢查reCAPTCHA配置
$recaptcha_available = false;
$recaptcha_file = '../backend/recaptcha_config.php';
if (file_exists($recaptcha_file)) {
    require_once $recaptcha_file;
    $recaptcha_available = defined('RECAPTCHA_SITE_KEY') && defined('RECAPTCHA_SECRET_KEY');
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學就讀意願登錄 - 簡化版</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f9fa;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: #333;
        padding-top: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #007bff;
    }

    .header h1 {
        color: #007bff;
        margin: 0;
        font-size: 28px;
    }

    .header p {
        color: #666;
        margin: 10px 0 0 0;
    }

    .form-section {
        margin-bottom: 30px;
    }

    .section-title {
        color: #007bff;
        font-size: 18px;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #e9ecef;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        flex: 1;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
    }

    .radio-group {
        display: flex;
        gap: 20px;
    }

    .radio-group label {
        display: flex;
        align-items: center;
        font-weight: normal;
        cursor: pointer;
    }

    .radio-group input[type="radio"] {
        width: auto;
        margin-right: 8px;
    }

    .required {
        color: #dc3545;
    }

    .submit-btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s ease;
    }

    .submit-btn:hover {
        background: #0056b3;
    }

    .submit-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
    }

    .message {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: none;
    }

    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .recaptcha-container {
        margin: 20px 0;
        text-align: center;
    }

    .help-text {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .debug-info {
        background: #e9ecef;
        padding: 15px;
        border-radius: 6px;
        margin-top: 20px;
        font-size: 14px;
    }

    .debug-info h4 {
        margin: 0 0 10px 0;
        color: #495057;
    }
    </style>
    
    <?php if ($recaptcha_available): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>康寧大學就讀意願登錄</h1>
            <p>簡化版表單 - 無需登入即可填寫</p>
        </div>

        <div id="message" class="message"></div>

        <form id="enrollmentForm">
            <!-- 個人基本資料 -->
            <div class="form-section">
                <h3 class="section-title">個人基本資料</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><span class="required">*</span> 姓名:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label><span class="required">*</span> 身分別:</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="identity" value="學生" required>
                                學生
                            </label>
                            <label>
                                <input type="radio" name="identity" value="家長" required>
                                家長
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">性別:</label>
                        <select id="gender" name="gender">
                            <option value="">請選擇</option>
                            <option value="男">男</option>
                            <option value="女">女</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone1"><span class="required">*</span> 聯絡電話1:</label>
                        <input type="tel" id="phone1" name="phone1" required maxlength="10">
                        <div class="help-text">請輸入10位數手機號碼</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone2">聯絡電話2:</label>
                        <input type="tel" id="phone2" name="phone2" maxlength="10">
                    </div>
                    <div class="form-group">
                        <label for="email">電子郵件:</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
            </div>

            <!-- 就讀意願 -->
            <div class="form-section">
                <h3 class="section-title">就讀意願</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="intention1">就讀意願一:</label>
                        <select id="intention1" name="intention1">
                            <option value="">請選擇</option>
                            <option value="無特定">無特定</option>
                            <option value="資訊管理科">資訊管理科</option>
                            <option value="企業管理科">企業管理科</option>
                            <option value="護理科">護理科</option>
                            <option value="幼保科">幼保科</option>
                            <option value="應用外語科">應用外語科</option>
                            <option value="視光科">視光科</option>
                            <option value="動畫科">動畫科</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="system1">學制:</label>
                        <select id="system1" name="system1">
                            <option value="">請選擇</option>
                            <option value="五專">五專</option>
                            <option value="大學部">大學部</option>
                            <option value="碩士班">碩士班</option>
                            <option value="博士班">博士班</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="intention2">就讀意願二:</label>
                        <select id="intention2" name="intention2">
                            <option value="">請選擇</option>
                            <option value="無特定">無特定</option>
                            <option value="資訊管理科">資訊管理科</option>
                            <option value="企業管理科">企業管理科</option>
                            <option value="護理科">護理科</option>
                            <option value="幼保科">幼保科</option>
                            <option value="應用外語科">應用外語科</option>
                            <option value="視光科">視光科</option>
                            <option value="動畫科">動畫科</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="system2">學制:</label>
                        <select id="system2" name="system2">
                            <option value="">請選擇</option>
                            <option value="五專">五專</option>
                            <option value="大學部">大學部</option>
                            <option value="碩士班">碩士班</option>
                            <option value="博士班">博士班</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="intention3">就讀意願三:</label>
                        <select id="intention3" name="intention3">
                            <option value="">請選擇</option>
                            <option value="無特定">無特定</option>
                            <option value="資訊管理科">資訊管理科</option>
                            <option value="企業管理科">企業管理科</option>
                            <option value="護理科">護理科</option>
                            <option value="幼保科">幼保科</option>
                            <option value="應用外語科">應用外語科</option>
                            <option value="視光科">視光科</option>
                            <option value="動畫科">動畫科</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="system3">學制:</label>
                        <select id="system3" name="system3">
                            <option value="">請選擇</option>
                            <option value="五專">五專</option>
                            <option value="大學部">大學部</option>
                            <option value="碩士班">碩士班</option>
                            <option value="博士班">博士班</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 其他資訊 -->
            <div class="form-section">
                <h3 class="section-title">其他資訊</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="junior_high">國中學校:</label>
                        <input type="text" id="junior_high" name="junior_high">
                    </div>
                    <div class="form-group">
                        <label for="current_grade">目前年級:</label>
                        <select id="current_grade" name="current_grade">
                            <option value="">請選擇</option>
                            <option value="國一">國一</option>
                            <option value="國二">國二</option>
                            <option value="國三">國三</option>
                            <option value="高一">高一</option>
                            <option value="高二">高二</option>
                            <option value="高三">高三</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="line_id">LINE ID:</label>
                        <input type="text" id="line_id" name="line_id">
                    </div>
                    <div class="form-group">
                        <label for="facebook">Facebook:</label>
                        <input type="text" id="facebook" name="facebook">
                    </div>
                </div>

                <div class="form-group">
                    <label for="remarks">備註:</label>
                    <textarea id="remarks" name="remarks" rows="3" placeholder="其他想說的話..."></textarea>
                </div>
            </div>

            <?php if ($recaptcha_available): ?>
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>
            <?php endif; ?>

            <button type="submit" class="submit-btn">提交就讀意願</button>
        </form>

        <div class="debug-info">
            <h4>🔧 調試信息</h4>
            <p><strong>reCAPTCHA狀態:</strong> <?php echo $recaptcha_available ? '✅ 已啟用' : '❌ 未啟用'; ?></p>
            <p><strong>表單版本:</strong> 簡化版（無需登入）</p>
            <p><strong>API端點:</strong> backend/api/enrollment/enrollment_api.php</p>
            <p><a href="enrollment_debug.php">查看詳細調試信息</a></p>
        </div>
    </div>

    <script>
    document.getElementById('enrollmentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.querySelector('.submit-btn');
        const messageDiv = document.getElementById('message');
        
        // 禁用提交按鈕
        submitBtn.disabled = true;
        submitBtn.textContent = '提交中...';
        
        // 隱藏之前的訊息
        messageDiv.style.display = 'none';
        
        try {
            // 收集表單數據
            const formData = new FormData(this);
            formData.append('username', 'guest_' + Date.now()); // 生成臨時用戶名
            
            // 發送請求
            const response = await fetch('../backend/api/enrollment/enrollment_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                messageDiv.className = 'message success';
                messageDiv.innerHTML = '✅ ' + result.message;
                messageDiv.style.display = 'block';
                
                // 清空表單
                this.reset();
                
                // 滾動到訊息
                messageDiv.scrollIntoView({ behavior: 'smooth' });
            } else {
                messageDiv.className = 'message error';
                messageDiv.innerHTML = '❌ ' + result.message;
                messageDiv.style.display = 'block';
                
                // 滾動到訊息
                messageDiv.scrollIntoView({ behavior: 'smooth' });
            }
            
        } catch (error) {
            messageDiv.className = 'message error';
            messageDiv.innerHTML = '❌ 提交失敗: ' + error.message;
            messageDiv.style.display = 'block';
            
            // 滾動到訊息
            messageDiv.scrollIntoView({ behavior: 'smooth' });
        } finally {
            // 恢復提交按鈕
            submitBtn.disabled = false;
            submitBtn.textContent = '提交就讀意願';
        }
    });
    </script>
</body>
</html>



