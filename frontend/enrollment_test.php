<?php
session_start();

// 臨時放寬權限檢查 - 只要登入就可以訪問
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? '未知';
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學就讀意願登錄 (測試版)</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #fff5e6;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: #333;
        padding-top: 100px;
    }

    .coop-container {
        max-width: 1000px;
        margin: 40px auto;
        background: #fff5e6;
        padding: 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .h11 {
        color: #222;
        text-align: center;
        margin-bottom: 30px;
        font-size: 2em;
        font-weight: 600;
    }

    .form-description {
        text-align: center;
        margin-bottom: 30px;
        font-size: 14px;
        color: #666;
    }

    .user-info {
        background: #e8f4fd;
        border: 1px solid #bee5eb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }

    input[type="text"], 
    input[type="email"], 
    input[type="tel"], 
    input[type="date"], 
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        transition: border 0.3s ease;
        box-sizing: border-box;
        background: #fff;
    }

    input:focus, textarea:focus, select:focus {
        border-color: #0056b3;
        outline: none;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .submit-btn {
        background: #0056b3;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.3s;
    }

    .submit-btn:hover {
        background: #004494;
    }

    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .section-title {
        background: #f8f9fa;
        padding: 15px;
        margin: 30px 0 20px 0;
        border-radius: 6px;
        border-left: 4px solid #0056b3;
        font-weight: 600;
        color: #333;
    }

    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 10px;
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

    .intention-group {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }

    .intention-group h4 {
        margin: 0 0 15px 0;
        color: #495057;
        font-size: 16px;
    }

    .intention-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .intention-row {
            grid-template-columns: 1fr;
        }
        
        .radio-group {
            flex-direction: column;
            gap: 10px;
        }
    }

    .consent-section {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 20px;
        margin: 30px 0;
    }

    .consent-section h4 {
        margin: 0 0 15px 0;
        color: #856404;
    }

    .consent-text {
        color: #856404;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .g-recaptcha {
        margin: 20px 0;
        display: flex;
        justify-content: center;
    }

    .success-message {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        text-align: center;
    }

    .error-message {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        text-align: center;
    }

    .back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: #0056b3;
        text-decoration: none;
        font-weight: 600;
    }

    .back-link:hover {
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <div class="coop-container">
        <a href="index.php" class="back-link">← 回到首頁</a>
        
        <div class="user-info">
            <strong>當前用戶：</strong><?php echo htmlspecialchars($username); ?> 
            <strong>角色：</strong><?php echo htmlspecialchars($role); ?>
            <br><small>這是測試版本，已放寬權限檢查</small>
        </div>

        <h1 class="h11">康寧大學就讀意願登錄</h1>
        <div class="form-description">
            請填寫以下資料，我們會根據您的意願提供相關資訊和服務
        </div>

        <div id="message"></div>

        <form id="enrollmentForm">
            <!-- 個人基本資料 -->
            <div class="section-title">個人基本資料</div>
            
            <div class="form-group">
                <label for="name">姓名 *</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label>身分別 *</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="identity" value="學生" required> 學生
                    </label>
                    <label>
                        <input type="radio" name="identity" value="家長" required> 家長
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>性別</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="gender" value="男"> 男
                    </label>
                    <label>
                        <input type="radio" name="gender" value="女"> 女
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="phone1">聯絡電話1 *</label>
                <input type="tel" id="phone1" name="phone1" maxlength="10" required>
            </div>

            <div class="form-group">
                <label for="phone2">聯絡電話2</label>
                <input type="tel" id="phone2" name="phone2" maxlength="10">
            </div>

            <div class="form-group">
                <label for="email">電子郵件</label>
                <input type="email" id="email" name="email">
            </div>

            <!-- 就讀意願 -->
            <div class="section-title">就讀意願</div>
            
            <div class="intention-group">
                <h4>第一志願</h4>
                <div class="intention-row">
                    <div class="form-group">
                        <label for="intention1">意願</label>
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
                        <label for="system1">學制</label>
                                                 <select id="system1" name="system1">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                    </div>
                    <div class="form-group">
                        <label for="department1">科系</label>
                        <input type="text" id="department1" name="department1">
                    </div>
                </div>
            </div>

            <div class="intention-group">
                <h4>第二志願</h4>
                <div class="intention-row">
                    <div class="form-group">
                        <label for="intention2">意願</label>
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
                        <label for="system2">學制</label>
                                                 <select id="system2" name="system2">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                    </div>
                    <div class="form-group">
                        <label for="department2">科系</label>
                        <input type="text" id="department2" name="department2">
                    </div>
                </div>
            </div>

            <div class="intention-group">
                <h4>第三志願</h4>
                <div class="intention-row">
                    <div class="form-group">
                        <label for="intention3">意願</label>
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
                        <label for="system3">學制</label>
                                                 <select id="system3" name="system3">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                    </div>
                    <div class="form-group">
                        <label for="department3">科系</label>
                        <input type="text" id="department3" name="department3">
                    </div>
                </div>
            </div>

            <!-- 國中資訊 -->
            <div class="section-title">就讀或畢業國中資訊</div>
            
            <div class="form-group">
                <label for="junior_high">就讀或畢業國中</label>
                <input type="text" id="junior_high" name="junior_high">
            </div>

            <div class="form-group">
                <label for="current_grade">目前年級</label>
                <select id="current_grade" name="current_grade">
                    <option value="">請選擇</option>
                    <option value="國一">國一</option>
                    <option value="國二">國二</option>
                    <option value="國三">國三</option>
                    <option value="已畢業">已畢業</option>
                </select>
            </div>

            <!-- 社群媒體資訊 -->
            <div class="section-title">社群媒體資訊</div>
            
            <div class="form-group">
                <label for="line_id">Line ID</label>
                <input type="text" id="line_id" name="line_id">
            </div>

            <div class="form-group">
                <label for="facebook">Facebook</label>
                <input type="text" id="facebook" name="facebook">
            </div>

            <!-- 備註 -->
            <div class="section-title">備註</div>
            
            <div class="form-group">
                <label for="remarks">備註</label>
                <textarea id="remarks" name="remarks" placeholder="請填寫任何額外說明或特殊需求..."></textarea>
            </div>

            <!-- 同意聲明 -->
            <div class="consent-section">
                <h4>同意聲明</h4>
                <div class="consent-text">
                    本人同意康寧大學收集、處理及利用本人之個人資料，用於就讀意願登錄及相關服務之目的。
                    本人了解可依個人資料保護法之規定，向康寧大學查詢、閱覽、製給複製本、補充或更正、停止蒐集處理或利用、刪除個人資料。
                </div>
            </div>

            <!-- reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"></div>

            <button type="submit" class="submit-btn" id="submitBtn">提交就讀意願登錄</button>
        </form>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 檢查必填欄位
            const requiredFields = ['name', 'phone1'];
            const requiredRadios = ['identity'];
            
            let isValid = true;
            let errorMessage = '';
            
            // 檢查文字欄位
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    isValid = false;
                    errorMessage += `請填寫${element.previousElementSibling.textContent.replace(' *', '')}\n`;
                }
            });
            
            // 檢查單選欄位
            requiredRadios.forEach(field => {
                const radios = document.querySelectorAll(`input[name="${field}"]`);
                const checked = Array.from(radios).some(radio => radio.checked);
                if (!checked) {
                    isValid = false;
                    errorMessage += `請選擇身分別\n`;
                }
            });
            
            // 檢查 reCAPTCHA (暫時跳過驗證)
            const recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse) {
                console.log('reCAPTCHA未完成，但繼續提交');
            }
            
            if (!isValid) {
                messageDiv.innerHTML = `<div class="error-message">${errorMessage}</div>`;
                return;
            }
            
            // 提交表單
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            const formData = new FormData(this);
            
            // 加入用戶名
            formData.append('username', '<?php echo htmlspecialchars($username); ?>');
            
            // 調試：顯示要提交的資料
            console.log('準備提交的資料:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('../backend/enrollment_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('API回應狀態:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API回應資料:', data);
                if (data.success) {
                    messageDiv.innerHTML = `<div class="success-message">✅ ${data.message}</div>`;
                    this.reset();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                } else {
                    messageDiv.innerHTML = `<div class="error-message">❌ ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('提交錯誤:', error);
                messageDiv.innerHTML = '<div class="error-message">❌ 提交失敗，請稍後再試。錯誤詳情: ' + error.message + '</div>';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '提交就讀意願登錄';
            });
        });
    </script>
</body>
</html>
