<?php
session_start();
require_once 'config.php';

// 產生驗證碼函數
function generateCaptcha() {
    return CAPTCHA_CODES[array_rand(CAPTCHA_CODES)];
}

// 初始化驗證碼
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateCaptcha();
}

// 建立資料庫連接
$conn = getDatabaseConnection();

// 取得啟用的場次
$sessions = [];
$sessions_query = "SELECT id, session_name, session_date, session_type FROM admission_sessions WHERE is_active = 1 ORDER BY session_date";
$sessions_result = $conn->query($sessions_query);
if ($sessions_result) {
    while ($row = $sessions_result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

$message = "";
$messageType = "";

// 處理表單提交
if ($_POST) {
    // 驗證必填欄位 (移除 line_id，改為選填)
    $required_fields = ['email', 'school_name', 'student_name', 'grade', 'parent_name', 'contact_phone', 'session_choice', 'receive_info', 'captcha'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    // 驗證驗證碼
    if (!isset($_SESSION['captcha']) || $_POST['captcha'] !== $_SESSION['captcha']) {
        $missing_fields[] = 'captcha_invalid';
    }
    
    // 驗證電子郵件格式
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $missing_fields[] = 'email_invalid';
    }
    
    // 驗證電話號碼格式 (必須是09開頭的10位數字)
    if (!empty($_POST['contact_phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $_POST['contact_phone']); // 移除非數字字符
        if (!preg_match('/^09[0-9]{8}$/', $phone)) {
            $missing_fields[] = 'phone_invalid';
        }
        $_POST['contact_phone'] = $phone; // 標準化電話號碼格式
    }
    
    if (empty($missing_fields)) {
        // 處理體驗課程多選
        $experience_courses = [];
        if (isset($_POST['experience_course'])) {
            $experience_courses = $_POST['experience_course'];
        }
        $experience_course_str = implode(',', $experience_courses);
        
        // 確保資料庫連接有效
        $conn = reconnectDatabase($conn);
        
        // 取得選擇的場次資訊
        $session_info_query = "SELECT session_name FROM admission_sessions WHERE id = ?";
        $session_stmt = $conn->prepare($session_info_query);
        $session_stmt->bind_param("i", $_POST['session_choice']);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session_name = '';
        if ($session_row = $session_result->fetch_assoc()) {
            $session_name = $session_row['session_name'];
        }
        $session_stmt->close();
        
        // 插入資料庫
        $sql = "INSERT INTO admission_applications (email, school_name, student_name, grade, parent_name, contact_phone, line_id, session_id, session_choice, experience_course, receive_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssss", 
            $_POST['email'],
            $_POST['school_name'],
            $_POST['student_name'],
            $_POST['grade'],
            $_POST['parent_name'],
            $_POST['contact_phone'],
            $_POST['line_id'],
            $_POST['session_choice'],
            $session_name,
            $experience_course_str,
            $_POST['receive_info']
        );
        
        if ($stmt->execute()) {
            $message = "報名資料已成功提交！我們會在活動前發送提醒郵件。";
            $messageType = "success";
            // 提交成功後重新生成驗證碼
            $_SESSION['captcha'] = generateCaptcha();
            // 清空 POST 資料，避免表單資料被保留
            $_POST = array();
        } else {
            $message = "報名失敗：" . $stmt->error;
            $messageType = "error";
        }
        
        $stmt->close();
    } else {
        $error_messages = [
            'email' => '請填寫電子郵件',
            'email_invalid' => '電子郵件格式不正確',
            'school_name' => '請填寫學校名稱',
            'student_name' => '請填寫學生姓名',
            'grade' => '請選擇就讀年級',
            'parent_name' => '請填寫家長姓名',
            'contact_phone' => '請填寫聯絡電話',
            'phone_invalid' => '聯絡電話格式不正確，請輸入09開頭的10位數字',
            'session_choice' => '請選擇參加場次',
            'receive_info' => '請選擇是否願意收到升學訊息',
            'captcha' => '請填寫驗證碼',
            'captcha_invalid' => '驗證碼錯誤'
        ];
        
        $message = "請檢查以下欄位：" . implode('、', array_map(function($field) use ($error_messages) {
            return $error_messages[$field] ?? $field;
        }, $missing_fields));
        $messageType = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學五專入學說明會報名</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            line-height: 1.6 !important;
            color: #333 !important;
            background: white !important;
            min-height: 100vh !important;
            padding-top: 120px !important; /* 為了header留出更多空間 */
            padding-bottom: 100px !important; /* 為了footer留出更多空間 */
        }

        .admission-container {
            max-width: 900px !important;
            margin: 0 auto !important;
            padding: 20px !important;
            position: relative !important;
            z-index: 10 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #667eea;
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: none;
            color: #667eea;
        }

        .header .subtitle {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #764ba2;
        }

        .header .hashtags {
            font-size: 1em;
            margin-bottom: 20px;
            color: #666;
        }

        .contact-info {
            background: #667eea;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
        }

        .form-container {
            background: white !important;
            border-radius: 15px !important;
            padding: 30px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
            margin-top: 20px !important;
            position: relative !important;
            z-index: 15 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            clear: both !important;
            border: 1px solid #e0e0e0 !important;
        }

        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }

        .form-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }

        .required {
            color: #e74c3c;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .checkbox-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        .captcha-section {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .captcha-display {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            background: white;
            padding: 10px 15px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 140px !important; /* 手機版header可能更高 */
                padding-bottom: 120px !important;
            }
            
            .admission-container {
                padding: 10px !important;
                margin-top: 10px !important;
                max-width: 95% !important;
            }
            
            .form-container {
                padding: 20px !important;
                margin-top: 10px !important;
            }
            
            .form-row {
                grid-template-columns: 1fr !important;
            }
            
            .checkbox-grid {
                grid-template-columns: 1fr !important;
            }
            
            .header h1 {
                font-size: 2em !important;
            }
            
            .radio-group {
                flex-direction: column !important;
                gap: 10px !important;
            }
        }
        
        /* 覆蓋可能的外部樣式 */
        .admission-container * {
            box-sizing: border-box !important;
        }
        
        /* 確保表單不會被分割 */
        .admission-container .form-container {
            display: block !important;
            float: none !important;
            position: relative !important;
        }
    </style>
</head>
<?php include("share/header.php"); ?>
<body>
    <div class="admission-container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> 康寧大學五專入學說明會</h1>
            <div class="subtitle">選擇康寧 • 人生雙贏 • 未來罩您</div>
            <div class="hashtags">
                #念五專有前途 #升學就業兩相宜 #五專前三年免學費<br>
                #展翅計畫免學雜費保證就業
            </div>
            
            <div class="contact-info">
                <div><i class="fas fa-phone"></i> 招生諮詢電話：2632-1181*310 / 0916-051-882</div>
                <div><i class="fab fa-line"></i> LINE ID：@ukn_taipei 招生中心高老師</div>
            </div>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- 基本資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 基本資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 電子郵件：</label>
                            <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 學校名稱：</label>
                            <input type="text" name="school_name" value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 學生姓名：</label>
                            <input type="text" name="student_name" value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 就讀年級：</label>
                            <select name="grade" required>
                                <option value="">請選擇年級</option>
                                <option value="九年級" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '九年級') ? 'selected' : ''; ?>>九年級</option>
                                <option value="八年級" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '八年級') ? 'selected' : ''; ?>>八年級</option>
                                <option value="七年級" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '七年級') ? 'selected' : ''; ?>>七年級</option>
                                <option value="其他" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '其他') ? 'selected' : ''; ?>>其他</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-address-book"></i> 聯絡資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 家長姓名：</label>
                            <input type="text" name="parent_name" value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>" required>
                        </div>
                        <div class="field-group">
                            <label><span class="required">*</span> 聯絡電話：</label>
                            <input type="tel" name="contact_phone" value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-group">
                            <label>LINE ID (選填)：</label>
                            <input type="text" name="line_id" placeholder="如有LINE帳號可填寫，方便聯繫" value="<?php echo isset($_POST['line_id']) ? htmlspecialchars($_POST['line_id']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- 活動選擇 -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt"></i> 參加場次 <span class="required">*</span></h3>
                    <div class="radio-group">
                        <?php if (empty($sessions)): ?>
                            <p style="color: #e74c3c; font-weight: bold;">目前沒有開放報名的場次，請稍後再試。</p>
                        <?php else: ?>
                            <?php foreach ($sessions as $session): ?>
                                <label class="radio-item">
                                    <input type="radio" name="session_choice" value="<?php echo $session['id']; ?>" <?php echo (isset($_POST['session_choice']) && $_POST['session_choice'] == $session['id']) ? 'checked' : ''; ?> required>
                                    <span><?php echo htmlspecialchars($session['session_name'] . ($session['session_type'] === '線上' ? ' (線上)' : '')); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 體驗課程 -->
                <div class="form-section">
                    <h3><i class="fas fa-book-open"></i> 體驗課程 (線上無) <span class="required">*</span></h3>
                    <div class="checkbox-grid">
                        <?php 
                        $courses = ['護理科', '視光科', '應用外語科', '資訊管理科', '嬰幼兒保育科', '企業管理科', '數位影視動畫科', '現場決定'];
                        $selected_courses = isset($_POST['experience_course']) ? $_POST['experience_course'] : [];
                        ?>
                        <?php foreach ($courses as $course): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="experience_course[]" value="<?php echo htmlspecialchars($course); ?>" <?php echo in_array($course, $selected_courses) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($course); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 資訊接收 -->
                <div class="form-section">
                    <h3><i class="fas fa-envelope"></i> 資訊接收 <span class="required">*</span></h3>
                    <p style="margin-bottom: 15px; color: #666;">活動結束後，是否願意收到其他相關五專升學訊息</p>
                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="receive_info" value="是，願意" <?php echo (isset($_POST['receive_info']) && $_POST['receive_info'] === '是，願意') ? 'checked' : ''; ?> required>
                            <span>是，願意</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="receive_info" value="否，不願意" <?php echo (isset($_POST['receive_info']) && $_POST['receive_info'] === '否，不願意') ? 'checked' : ''; ?> required>
                            <span>否，不願意</span>
                        </label>
                    </div>
                </div>

                <!-- 驗證碼 -->
                <div class="captcha-section">
                    <h3><i class="fas fa-shield-alt"></i> 安全驗證 <span class="required">*</span></h3>
                    <p>請輸入下方顯示的驗證碼：</p>
                    <div class="captcha-display"><?php echo $_SESSION['captcha']; ?></div>
                    <input type="text" name="captcha" placeholder="請輸入驗證碼" required maxlength="4" style="width: 150px;">
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> 提交報名資料
                </button>
            </form>
        </div>
    </div>

    <script>
        
        // 電話號碼格式驗證
        const phoneInput = document.querySelector('[name="contact_phone"]');
        
        phoneInput.addEventListener('input', function(e) {
            // 只保留數字
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // 檢查格式並設定邊框顏色
            if (this.value.length === 10 && this.value.startsWith('09')) {
                // 正確格式：綠色邊框
                this.style.borderColor = '#27ae60';
                this.style.borderWidth = '2px';
            } else if (this.value.length > 0) {
                // 錯誤格式：紅色邊框  
                this.style.borderColor = '#e74c3c';
                this.style.borderWidth = '2px';
            } else {
                // 空白：預設邊框
                this.style.borderColor = '#ddd';
                this.style.borderWidth = '2px';
            }
        });
        
        // 表單提交驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = phoneInput.value;
            
            // 如果電話號碼格式不正確，阻止提交
            if (phone && !/^09[0-9]{8}$/.test(phone)) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }
        });
    </script>
<?php include("share/footer.php"); ?>
</body>
</html>
