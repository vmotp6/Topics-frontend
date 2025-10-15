<?php
// 載入 session 配置
require_once 'session_config.php';
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

// 取得啟用的場次（包含最多人數和剩餘名額）
$sessions = [];
$sessions_query = "SELECT s.id, s.session_name, s.session_date, s.session_type, s.max_participants,
                          (s.max_participants - COUNT(a.id)) as remaining_spots
                   FROM admission_sessions s 
                   LEFT JOIN admission_applications a ON s.id = a.session_id 
                   WHERE s.is_active = 1 
                   GROUP BY s.id, s.session_name, s.session_date, s.session_type, s.max_participants
                   ORDER BY 
                       CASE WHEN (s.max_participants - COUNT(a.id)) <= 0 THEN 1 ELSE 0 END,
                       s.session_date";
$sessions_result = $conn->query($sessions_query);
if ($sessions_result) {
    while ($row = $sessions_result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

// 取得啟用的年級選項
$grades = [];
$grades_query = "SELECT grade_name FROM admission_grades WHERE is_active = 1 ORDER BY sort_order";
$grades_result = $conn->query($grades_query);
if ($grades_result) {
    while ($row = $grades_result->fetch_assoc()) {
        $grades[] = $row['grade_name'];
    }
}

// 取得啟用的體驗課程
$courses = [];
$courses_query = "SELECT c.course_name, d.department_name 
                  FROM admission_courses c 
                  LEFT JOIN admission_departments d ON c.department_id = d.id 
                  WHERE c.is_active = 1 
                  ORDER BY c.sort_order";
$courses_result = $conn->query($courses_query);
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row['course_name'];
    }
}

// 取得招生諮詢老師資訊 (user_id=12的老師資料)
$admission_teacher = [];
$teacher_query = "SELECT u.name, t.department, t.phone 
                  FROM teacher t 
                  LEFT JOIN user u ON t.user_id = u.id 
                  WHERE t.user_id = 12";
$teacher_result = $conn->query($teacher_query);
if ($teacher_result && $teacher_result->num_rows > 0) {
    $admission_teacher = $teacher_result->fetch_assoc();
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
    
    // 驗證場次是否額滿
    if (!empty($_POST['session_choice'])) {
        $session_check_query = "SELECT s.max_participants, 
                                      (s.max_participants - COUNT(a.id)) as remaining_spots
                               FROM admission_sessions s 
                               LEFT JOIN admission_applications a ON s.id = a.session_id 
                               WHERE s.id = ? AND s.is_active = 1
                               GROUP BY s.id, s.max_participants";
        $session_check_stmt = $conn->prepare($session_check_query);
        $session_check_stmt->bind_param("i", $_POST['session_choice']);
        $session_check_stmt->execute();
        $session_check_result = $session_check_stmt->get_result();
        
        if ($session_row = $session_check_result->fetch_assoc()) {
            if ($session_row['remaining_spots'] <= 0) {
                $missing_fields[] = 'session_full';
            }
        } else {
            $missing_fields[] = 'session_invalid';
        }
        $session_check_stmt->close();
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
        // 處理體驗課程優先順序
        $course_priority_1 = '';
        $course_priority_2 = '';
        
        if (isset($_POST['course_priority_1']) && !empty($_POST['course_priority_1'])) {
            $course_priority_1 = $_POST['course_priority_1'];
        }
        
        if (isset($_POST['course_priority_2']) && !empty($_POST['course_priority_2'])) {
            $course_priority_2 = $_POST['course_priority_2'];
        }
        
        // 確保資料庫連接有效
        if (!$conn || $conn->ping() === false) {
            $conn = getDatabaseConnection();
        }
        
        // 取得選擇的場次資訊
        $session_info_query = "SELECT id, session_name FROM admission_sessions WHERE id = ?";
        $session_stmt = $conn->prepare($session_info_query);
        $session_stmt->bind_param("i", $_POST['session_choice']);
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        $session_id = $_POST['session_choice']; // 場次ID
        $session_name = '';
        if ($session_row = $session_result->fetch_assoc()) {
            $session_name = $session_row['session_name'];
        }
        $session_stmt->close();
        
        // 檢查並添加必要的郵件字段（如果不存在）
        $conn->query("ALTER TABLE admission_applications ADD COLUMN IF NOT EXISTS email_sent TINYINT(1) DEFAULT 0 COMMENT '是否已發送確認郵件（0=未發送，1=已發送）'");
        $conn->query("ALTER TABLE admission_applications ADD COLUMN IF NOT EXISTS email_sent_at TIMESTAMP NULL COMMENT '確認郵件發送時間'");
        $conn->query("ALTER TABLE admission_applications ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0 COMMENT '是否已發送活動提醒郵件（0=未發送，1=已發送）'");
        $conn->query("ALTER TABLE admission_applications ADD COLUMN IF NOT EXISTS reminder_sent_at TIMESTAMP NULL COMMENT '提醒郵件發送時間'");
        
        // 插入資料（包含郵件狀態字段和session_id）
        $sql = "INSERT INTO admission_applications (email, school_name, student_name, grade, parent_name, contact_phone, line_id, session_id, session_choice, course_priority_1, course_priority_2, receive_info, email_sent, reminder_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL 準備失敗: " . $conn->error . " | SQL: " . $sql);
        }
        
        $stmt->bind_param("ssssssiissss", 
            $_POST['email'],
            $_POST['school_name'],
            $_POST['student_name'],
            $_POST['grade'],
            $_POST['parent_name'],
            $_POST['contact_phone'],
            $_POST['line_id'],
            $session_id,
            $session_name,
            $course_priority_1,
            $course_priority_2,
            $_POST['receive_info']
        );
        
        if ($stmt->execute()) {
            // 獲取新插入記錄的ID
            $application_id = $conn->insert_id;
            
            // 發送歡迎郵件
            try {
                require_once 'includes/email_functions.php';
                
                // 組合課程資訊用於郵件
                $course_info = [];
                if (!empty($course_priority_1)) {
                    $course_info[] = "第一選擇：" . $course_priority_1;
                }
                if (!empty($course_priority_2)) {
                    $course_info[] = "第二選擇：" . $course_priority_2;
                }
                $course_text = !empty($course_info) ? implode('、', $course_info) : '未選擇體驗課程';
                
                // 嘗試發送歡迎郵件
                $email_sent = sendWelcomeEmail(
                    $_POST['email'],
                    $_POST['student_name'],
                    $_POST['parent_name'],
                    $session_name,
                    $course_text
                );
                
                // 更新郵件發送狀態
                if ($email_sent) {
                    $update_sql = "UPDATE admission_applications SET email_sent = 1, email_sent_at = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $application_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $message = "報名資料已成功提交！歡迎郵件已發送到您的信箱，我們會在活動前一天再次發送提醒郵件。";
                } else {
                    $message = "報名資料已成功提交！歡迎郵件發送失敗，但我們會在活動前一天發送提醒郵件。";
                }
                
            } catch (Exception $e) {
                // 即使郵件發送失敗，也不影響報名成功
                $message = "報名資料已成功提交！我們會在活動前一天發送提醒郵件。";
                error_log("歡迎郵件發送失敗: " . $e->getMessage());
            }
            
            // 檢查是否需要立即發送提醒郵件
            try {
                // 獲取場次日期
                $session_date_query = "SELECT session_date FROM admission_sessions WHERE id = ?";
                $session_date_stmt = $conn->prepare($session_date_query);
                $session_date_stmt->bind_param("i", $session_id);
                $session_date_stmt->execute();
                $session_date_result = $session_date_stmt->get_result();
                
                if ($session_date_row = $session_date_result->fetch_assoc()) {
                    $session_date = $session_date_row['session_date'];
                    $today = new DateTime();
                    $session_date_obj = new DateTime($session_date);
                    $days_until_session = $today->diff($session_date_obj)->days;
                    
                    // 如果活動是明天或今天，立即發送提醒郵件
                    if ($days_until_session <= 1) {
                        $reminder_sent = sendReminderEmail(
                            $_POST['email'],
                            $_POST['student_name'],
                            $_POST['parent_name'],
                            $session_name,
                            $session_date
                        );
                        
                        if ($reminder_sent) {
                            // 更新提醒郵件發送狀態
                            $reminder_update_sql = "UPDATE admission_applications SET reminder_sent = 1, reminder_sent_at = NOW() WHERE id = ?";
                            $reminder_update_stmt = $conn->prepare($reminder_update_sql);
                            $reminder_update_stmt->bind_param("i", $application_id);
                            $reminder_update_stmt->execute();
                            $reminder_update_stmt->close();
                            
                            $message .= " 提醒郵件也已發送！";
                        }
                    }
                }
                $session_date_stmt->close();
                
            } catch (Exception $e) {
                // 提醒郵件發送失敗不影響報名成功
                error_log("提醒郵件發送失敗: " . $e->getMessage());
            }
            
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
            'parent_name' => '請填寫姓名',
            'contact_phone' => '請填寫聯絡電話',
            'phone_invalid' => '聯絡電話格式不正確，請輸入09開頭的10位數字',
            'session_choice' => '請選擇參加場次',
            'session_full' => '所選場次已額滿，請選擇其他場次',
            'session_invalid' => '所選場次無效，請重新選擇',
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
	<link rel="stylesheet" href="assets/csp/admission.css">
</head>
<body>
<?php include("share/header.php"); ?>
<main>
    <div class="admission-container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> 康寧大學五專入學說明會</h1>
            <div class="subtitle">選擇康寧 • 人生雙贏 • 未來罩您</div>
            
            <div class="contact-info">
                    <div><i class="fas fa-phone"></i> 招生諮詢電話：
                        <?php echo !empty($admission_teacher['phone']) ? htmlspecialchars($admission_teacher['phone']) : '請洽學校總機'; ?>
                    </div>
                        <div><i class="fas fa-user"></i> 招生諮詢：
                            <?php echo !empty($admission_teacher['name']) ? htmlspecialchars($admission_teacher['name']) : '請洽學校總機'; ?>
                            <?php if (!empty($admission_teacher['department'])): ?>
                                - <?php echo htmlspecialchars($admission_teacher['department']); ?>
                            <?php endif; ?>
                        </div>
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
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>" <?php echo (isset($_POST['grade']) && $_POST['grade'] === $grade) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grade); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <div class="form-section">
                    <h3><i class="fas fa-address-book"></i> 聯絡資訊</h3>
                    <div class="form-row">
                        <div class="field-group">
                            <label><span class="required">*</span> 姓名：</label>
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
                            <?php foreach ($sessions as $session): 
                                $is_full = $session['remaining_spots'] <= 0;
                                $session_display = $session['session_name'] . ($session['session_type'] === '線上' ? ' (線上)' : '');
                                $spots_info = "（{$session['remaining_spots']}/{$session['max_participants']} 人）";
                            ?>
                                <label class="radio-item <?php echo $is_full ? 'disabled' : ''; ?>">
                                    <input type="radio" name="session_choice" value="<?php echo $session['id']; ?>" 
                                           <?php echo (isset($_POST['session_choice']) && $_POST['session_choice'] == $session['id']) ? 'checked' : ''; ?>
                                           <?php echo $is_full ? 'disabled' : ''; ?> required>
                                    <span class="<?php echo $is_full ? 'full-session' : ''; ?>">
                                        <?php echo htmlspecialchars($session_display); ?>
                                        <span class="spots-info"><?php echo $spots_info; ?></span>
                                        <?php if ($is_full): ?>
                                            <span class="full-badge">額滿</span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 體驗課程 -->
                <div class="form-section">
                    <h3><i class="fas fa-book-open"></i> 體驗課程選擇 <span class="required">*</span></h3>
                    <p style="margin-bottom: 15px; color: #666;">請從下方課程中拖曳最多兩個課程到右側框框中，並可調整優先順序</p>
                    
                    <div class="course-selection-container">
                        <!-- 可選課程列表 -->
                        <div class="available-courses">
                            <h4><i class="fas fa-list"></i> 可選課程</h4>
                            <div class="course-list" id="availableCourses">
                                <?php foreach ($courses as $course): ?>
                                    <div class="course-item" draggable="true" data-course="<?php echo htmlspecialchars($course); ?>">
                                        <i class="fas fa-grip-vertical"></i>
                                        <span><?php echo htmlspecialchars($course); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- 已選課程框 -->
                        <div class="selected-courses">
                            <h4><i class="fas fa-star"></i> 我的選擇 (最多2個)</h4>
                            <div class="course-drop-zone" id="selectedCourses">
                                <div class="drop-placeholder">
                                    <i class="fas fa-hand-point-right"></i>
                                    <p>請拖曳課程到這裡</p>
                                    <small>第一個為優先選擇</small>
                                </div>
                            </div>
                            <div class="priority-info">
                                <small><i class="fas fa-info-circle"></i> 排序說明：上方為第一優先，下方為第二優先</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 隱藏欄位用於表單提交 -->
                    <input type="hidden" name="course_priority_1" id="coursePriority1" value="">
                    <input type="hidden" name="course_priority_2" id="coursePriority2" value="">
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

        // 拖曳式課程選擇功能
        let selectedCourses = [];
        const maxCourses = 2;

        // 初始化拖曳事件
        function initializeDragAndDrop() {
            const availableCourses = document.getElementById('availableCourses');
            const selectedCoursesZone = document.getElementById('selectedCourses');

            // 為所有課程項目添加拖曳事件
            availableCourses.querySelectorAll('.course-item').forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
            });

            // 為選擇區域添加放置事件
            selectedCoursesZone.addEventListener('dragover', handleDragOver);
            selectedCoursesZone.addEventListener('drop', handleDrop);
            selectedCoursesZone.addEventListener('dragenter', handleDragEnter);
            selectedCoursesZone.addEventListener('dragleave', handleDragLeave);
        }

        function handleDragStart(e) {
            e.dataTransfer.setData('text/plain', e.target.dataset.course);
        }

        function handleDragOver(e) {
            e.preventDefault();
        }

        function handleDragEnter(e) {
            e.preventDefault();
            e.target.closest('.course-drop-zone').classList.add('drag-over');
        }

        function handleDragLeave(e) {
            if (!e.target.closest('.course-drop-zone').contains(e.relatedTarget)) {
                e.target.closest('.course-drop-zone').classList.remove('drag-over');
            }
        }

        function handleDrop(e) {
            e.preventDefault();
            const courseName = e.dataTransfer.getData('text/plain');
            const dropZone = e.target.closest('.course-drop-zone');
            
            dropZone.classList.remove('drag-over');

            // 檢查是否已經選擇過這個課程
            if (selectedCourses.includes(courseName)) {
                alert('此課程已經被選擇了！');
                return;
            }

            // 檢查是否超過最大選擇數量
            if (selectedCourses.length >= maxCourses) {
                alert(`最多只能選擇 ${maxCourses} 個課程！`);
                return;
            }

            // 添加到選擇列表
            selectedCourses.push(courseName);
            updateSelectedCoursesDisplay();
            updateHiddenFields();
        }

        function updateSelectedCoursesDisplay() {
            const selectedCoursesZone = document.getElementById('selectedCourses');
            
            if (selectedCourses.length === 0) {
                selectedCoursesZone.innerHTML = `
                    <div class="drop-placeholder">
                        <i class="fas fa-hand-point-right"></i>
                        <p>請拖曳課程到這裡</p>
                        <small>第一個為優先選擇</small>
                    </div>
                `;
                return;
            }

            let html = '';
            selectedCourses.forEach((course, index) => {
                const priorityText = index === 0 ? '第一優先' : '第二優先';
                html += `
                    <div class="selected-course-item" data-course="${course}">
                        <div class="course-info">
                            <div class="course-name">${course}</div>
                        </div>
                        <div class="course-actions">
                            <span class="priority-badge">${priorityText}</span>
                            <button type="button" class="remove-btn" onclick="removeCourse('${course}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            selectedCoursesZone.innerHTML = html;

            // 為已選課程添加排序功能
            selectedCoursesZone.querySelectorAll('.selected-course-item').forEach(item => {
                item.addEventListener('dragstart', handleSelectedDragStart);
                item.addEventListener('dragover', handleSelectedDragOver);
                item.addEventListener('drop', handleSelectedDrop);
                item.setAttribute('draggable', 'true');
            });
        }

        function handleSelectedDragStart(e) {
            e.dataTransfer.setData('text/plain', e.target.dataset.course);
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleSelectedDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }

        function handleSelectedDrop(e) {
            e.preventDefault();
            const draggedCourse = e.dataTransfer.getData('text/plain');
            const targetCourse = e.target.closest('.selected-course-item').dataset.course;

            if (draggedCourse !== targetCourse) {
                // 交換位置
                const draggedIndex = selectedCourses.indexOf(draggedCourse);
                const targetIndex = selectedCourses.indexOf(targetCourse);

                selectedCourses[draggedIndex] = targetCourse;
                selectedCourses[targetIndex] = draggedCourse;

                updateSelectedCoursesDisplay();
                updateHiddenFields();
            }
        }

        function removeCourse(courseName) {
            const index = selectedCourses.indexOf(courseName);
            if (index > -1) {
                selectedCourses.splice(index, 1);
                updateSelectedCoursesDisplay();
                updateHiddenFields();
            }
        }

        function updateHiddenFields() {
            document.getElementById('coursePriority1').value = selectedCourses[0] || '';
            document.getElementById('coursePriority2').value = selectedCourses[1] || '';
        }
        
        // 表單提交驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = phoneInput.value;
            
            // 如果電話號碼格式不正確，阻止提交
            if (phone && !/^09[0-9]{8}$/.test(phone)) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }

            // 檢查是否至少選擇了一個課程
            if (selectedCourses.length === 0) {
                e.preventDefault();
                alert('請至少選擇一個體驗課程！');
                document.getElementById('selectedCourses').scrollIntoView({ behavior: 'smooth' });
                return false;
            }

            // 檢查是否選擇了額滿的場次
            const selectedSession = document.querySelector('input[name="session_choice"]:checked');
            if (selectedSession && selectedSession.disabled) {
                e.preventDefault();
                alert('所選場次已額滿，請選擇其他場次！');
                return false;
            }
        });

        // 頁面載入完成後初始化
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
        });
    </script>
</main>
<?php include("share/footer.php"); ?>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
<?php include("share/ai_widget.php"); ?>
</body>
</html>
