<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);

// 如果未登入，重定向到首頁
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 檢查是否為老師或學生角色
$user_role = $_SESSION['role'] ?? '';
if (!in_array($user_role, ['老師', '學生'])) {
    header("Location: index.php");
    exit;
}

$is_teacher = ($user_role === '老師');
$is_student = ($user_role === '學生');

// 獲取用戶姓名（老師或學生）
$user_name = '';
$current_department = '';
$current_phone = '';
// 學生專用欄位
$current_student_id = '';
$current_grade = '';
$current_class_name = '';
$current_email = '';
$current_username = $_SESSION['username'] ?? '';
$username_changed = 0;
$is_auto_generated = false;

// 檢查是否為系統生成的帳號（通過前綴判斷）
if (preg_match('/^(user_|staff_|admin_)/', $current_username)) {
    $is_auto_generated = true;
}

// 使用直接 PDO 連接（與其他頁面一致）
try {
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 先從 user 表獲取基本資訊（包括 email、username_changed 和頭像）
    $stmt = $pdo->prepare("SELECT name, email, username_changed, profile_picture FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_profile_picture = '';
    if ($result) {
        if (!empty($result['name'])) {
            $user_name = $result['name'];
        }
        // 如果student表中沒有email，使用user表的email作為預設值
        if (!empty($result['email'])) {
            $current_email = $result['email'];
        }
        // 獲取 username_changed 欄位（如果存在）
        $username_changed = isset($result['username_changed']) ? (int)$result['username_changed'] : 0;
        // 獲取當前頭像
        if (!empty($result['profile_picture'])) {
            $current_profile_picture = $result['profile_picture'];
        }
    }
    
    // 根據角色從不同表獲取詳細資料
    if ($is_teacher) {
        // 從 teacher 表獲取
        $stmt = $pdo->prepare("
            SELECT t.department, t.phone 
            FROM teacher t
            JOIN user u ON t.user_id = u.id
            WHERE u.username = ?
        ");
        $stmt->execute([$_SESSION['username']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $current_department = $result['department'] ?? '';
            $current_phone = $result['phone'] ?? '';
        }
    } elseif ($is_student) {
        // 從 student 表獲取所有欄位
        $stmt = $pdo->prepare("
            SELECT s.department, s.phone, s.student_id, s.grade, s.class_name, s.email 
            FROM student s
            JOIN user u ON s.user_id = u.id
            WHERE u.username = ?
        ");
        $stmt->execute([$_SESSION['username']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $current_department = $result['department'] ?? '';
            $current_phone = $result['phone'] ?? '';
            $current_student_id = $result['student_id'] ?? '';
            $current_grade = $result['grade'] ?? '';
            $current_class_name = $result['class_name'] ?? '';
            // 如果student表有email，優先使用；否則使用之前從user表獲取的
            if (!empty($result['email'])) {
                $current_email = $result['email'];
            }
        }
    }
} catch (PDOException $e) {
    error_log("無法從資料庫獲取用戶資料: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <?php include("share/header.php"); ?>
    <title><?php echo $is_teacher ? '老師' : '學生'; ?>個人資料</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        .profile-container {
            width: 80%;
            max-width: 600px;
            margin: 120px auto 40px;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-title {
            text-align: center;
            color: #003366;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #003366;
            font-weight: 600;
            font-size: 16px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
            background-color: #ffffff;
            color: #333;
            cursor: text;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            background-color: #ffffff;
        }
        
        /* 確保字段可編輯，覆蓋可能的只讀樣式 */
        .form-group input:not([readonly]):not([disabled]),
        .form-group select:not([readonly]):not([disabled]) {
            background-color: #ffffff !important;
            color: #333 !important;
            cursor: text !important;
            opacity: 1 !important;
        }

        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
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

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .back-btn:hover {
            color: #0056b3;
        }

        .credentials-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .credentials-section h2 {
            color: #003366;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .info-text {
            background: #e6f7ff;
            border: 1px solid #91d5ff;
            color: #1890ff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-text.warning {
            background: #fff7e6;
            border-color: #ffd591;
            color: #fa8c16;
        }

        /* 頭像上傳區域 */
        .avatar-section {
            margin-bottom: 30px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            margin: 0 auto 15px;
            display: block;
            background: #e0e0e0;
        }

        .avatar-upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .avatar-upload-btn:hover {
            background: #0056b3;
        }

        .avatar-upload-btn input[type="file"] {
            display: none;
        }

        .avatar-info {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <h1 class="profile-title">個人資料設定</h1>
        
        <!-- 頭像上傳區域 -->
        <div class="avatar-section">
            <img id="avatarPreview" class="avatar-preview" 
                 src="<?php 
                    if (!empty($current_profile_picture)) {
                        if (filter_var($current_profile_picture, FILTER_VALIDATE_URL)) {
                            // Google 頭像 URL，直接使用
                            echo htmlspecialchars($current_profile_picture);
                        } else {
                            // 本地上傳的頭像
                            if (strpos($current_profile_picture, 'uploads/') === 0) {
                                // 檢查檔案是否存在
                                $file_path = __DIR__ . '/' . $current_profile_picture;
                                if (file_exists($file_path)) {
                                    // 檔案存在，使用相對路徑
                                    $avatar_url = htmlspecialchars($current_profile_picture);
                                    // 添加時間戳避免快取
                                    $avatar_url .= '?v=' . filemtime($file_path);
                                    echo $avatar_url;
                                } else {
                                    // 檔案不存在，記錄錯誤並使用預設頭像
                                    error_log("頭像檔案不存在: {$file_path}, 資料庫路徑: {$current_profile_picture}");
                                    echo 'share/EIdROxGXsAE_LSs.jpg';
                                }
                            } else {
                                // 可能是 share 目錄的檔案
                                $share_path = __DIR__ . '/share/' . basename($current_profile_picture);
                                if (file_exists($share_path)) {
                                    echo htmlspecialchars('share/' . basename($current_profile_picture));
                                } else {
                                    echo 'share/EIdROxGXsAE_LSs.jpg';
                                }
                            }
                        }
                    } else {
                        echo 'share/EIdROxGXsAE_LSs.jpg';
                    }
                 ?>" 
                 alt="頭像預覽"
                 onerror="this.src='share/EIdROxGXsAE_LSs.jpg';">
            <label class="avatar-upload-btn">
                <input type="file" id="avatarInput" accept="image/*" onchange="previewAvatar(this)">
                選擇頭像
            </label>
            <div class="avatar-info">支援 JPG、PNG 格式，建議大小 200x200 像素</div>
        </div>
        
        <?php if ($is_teacher && $is_auto_generated): ?>
        <div class="credentials-section">
            <h2>帳號密碼設定</h2>
            <?php if ($username_changed == 0): ?>
                <div class="info-text">
                    <strong>提示：</strong>這是系統為您自動生成的帳號，建議您首次登入後立即修改為個人專屬帳號和密碼。
                </div>
            <?php else: ?>
                <div class="info-text warning">
                    <strong>注意：</strong>您已經修改過帳號，現在只能修改密碼。
                </div>
            <?php endif; ?>
            
            <form id="credentialsForm">
                <?php if ($username_changed == 0): ?>
                <div class="form-group">
                    <label for="new_username">新帳號 <span style="color: #f5222d;">*</span></label>
                    <input type="text" id="new_username" name="new_username" placeholder="請輸入新帳號" value="" required>
                    <small style="display:block;margin-top:6px;color:#8c8c8c;">帳號只能修改一次，請謹慎選擇。</small>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>目前帳號</label>
                    <input type="text" value="<?php echo htmlspecialchars($current_username); ?>" disabled style="background: #f5f5f5;">
                    <small style="display:block;margin-top:6px;color:#8c8c8c;">帳號已修改過，無法再次修改。</small>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="current_password">當前密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="current_password" name="current_password" placeholder="請輸入當前密碼" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">新密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="new_password" name="new_password" placeholder="請輸入新密碼" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">確認新密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="請再次輸入新密碼" required>
                </div>
                
                <button type="submit" class="submit-btn">更新帳號密碼</button>
            </form>
            
            <div id="credentialsMessage"></div>
        </div>
        <?php endif; ?>
        
        <form id="profileForm" enctype="multipart/form-data">
            <?php if ($is_teacher): ?>
                <!-- 老師專用欄位 -->
                <div class="form-group">
                    <label for="name">姓名</label>
                    <input type="text" id="name" name="name" placeholder="請輸入姓名" value="<?php echo htmlspecialchars($user_name); ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">科系</label>
                    <select id="department" name="department" required>
                        <option value="" disabled <?php echo empty($current_department) ? 'selected' : ''; ?>>請選擇科系</option>
                        <option value="資訊管理科" <?php echo $current_department === '資訊管理科' ? 'selected' : ''; ?>>資訊管理科</option>
                        <option value="企業管理科" <?php echo $current_department === '企業管理科' ? 'selected' : ''; ?>>企業管理科</option>
                        <option value="護理科" <?php echo $current_department === '護理科' ? 'selected' : ''; ?>>護理科</option>
                        <option value="幼保科" <?php echo $current_department === '幼保科' ? 'selected' : ''; ?>>幼保科</option>
                        <option value="應用外語科" <?php echo $current_department === '應用外語科' ? 'selected' : ''; ?>>應用外語科</option>
                        <option value="視光科" <?php echo $current_department === '視光科' ? 'selected' : ''; ?>>視光科</option>
                        <option value="動畫科" <?php echo $current_department === '動畫科' ? 'selected' : ''; ?>>動畫科</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">電話</label>
                    <input type="tel" id="phone" name="phone" placeholder="請輸入電話號碼" value="<?php echo htmlspecialchars($current_phone); ?>" required>
                </div>
            <?php else: ?>
                <!-- 學生專用欄位 -->
                <div class="form-group">
                    <label for="student_id">學號</label>
                    <input type="text" id="student_id" name="student_id" placeholder="請輸入學號" value="<?php echo htmlspecialchars($current_student_id); ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">科系</label>
                    <select id="department" name="department" required>
                        <option value="" disabled <?php echo empty($current_department) ? 'selected' : ''; ?>>請選擇科系</option>
                        <option value="資訊管理科" <?php echo $current_department === '資訊管理科' ? 'selected' : ''; ?>>資訊管理科</option>
                        <option value="企業管理科" <?php echo $current_department === '企業管理科' ? 'selected' : ''; ?>>企業管理科</option>
                        <option value="護理科" <?php echo $current_department === '護理科' ? 'selected' : ''; ?>>護理科</option>
                        <option value="幼保科" <?php echo $current_department === '幼保科' ? 'selected' : ''; ?>>幼保科</option>
                        <option value="應用外語科" <?php echo $current_department === '應用外語科' ? 'selected' : ''; ?>>應用外語科</option>
                        <option value="視光科" <?php echo $current_department === '視光科' ? 'selected' : ''; ?>>視光科</option>
                        <option value="動畫科" <?php echo $current_department === '動畫科' ? 'selected' : ''; ?>>動畫科</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="grade">年級</label>
                    <select id="grade" name="grade">
                        <option value="" <?php echo empty($current_grade) ? 'selected' : ''; ?>>請選擇年級</option>
                        <option value="一年級" <?php echo $current_grade === '一年級' ? 'selected' : ''; ?>>一年級</option>
                        <option value="二年級" <?php echo $current_grade === '二年級' ? 'selected' : ''; ?>>二年級</option>
                        <option value="三年級" <?php echo $current_grade === '三年級' ? 'selected' : ''; ?>>三年級</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="class_name">班級</label>
                    <input type="text" id="class_name" name="class_name" placeholder="例如：資管一孝" value="<?php echo htmlspecialchars($current_class_name); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">電話</label>
                    <input type="tel" id="phone" name="phone" placeholder="請輸入電話號碼" value="<?php echo htmlspecialchars($current_phone); ?>" required>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="submit-btn">保存資料</button>
        </form>
        
        <div id="message"></div>
        
        <!-- 修改密碼區塊（所有老師都可以使用） -->
        <div class="credentials-section">
            <h2>修改密碼</h2>
            <form id="passwordForm">
                <div class="form-group">
                    <label for="password_current_password">當前密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="password_current_password" name="current_password" placeholder="請輸入當前密碼" required>
                </div>
                
                <div class="form-group">
                    <label for="password_new_password">新密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="password_new_password" name="new_password" placeholder="請輸入新密碼" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm_password">確認新密碼 <span style="color: #f5222d;">*</span></label>
                    <input type="password" id="password_confirm_password" name="confirm_password" placeholder="請再次輸入新密碼" required>
                </div>
                
                <button type="submit" class="submit-btn">更新密碼</button>
            </form>
            
            <div id="passwordMessage"></div>
        </div>
        
        <a href="<?php echo $is_teacher ? 'teacher.php' : 'student.php'; ?>" class="back-btn">← 返回<?php echo $is_teacher ? '老師' : '學生'; ?>頁面</a>
    </div>

    <script>
        // 頭像預覽功能
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 頁面載入時自動填入現有資料（如果 PHP 已經載入）
        window.addEventListener('load', function() {
            // 如果 PHP 已經從資料庫載入了資料，直接使用（不需要 API 調用）
            const currentName = '<?php echo htmlspecialchars($user_name ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const currentDept = '<?php echo htmlspecialchars($current_department ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const currentPhone = '<?php echo htmlspecialchars($current_phone ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            
            if (currentName && document.getElementById('name')) {
                document.getElementById('name').value = currentName;
            }
            if (currentDept && document.getElementById('department')) {
                document.getElementById('department').value = currentDept;
            }
            if (currentPhone && document.getElementById('phone')) {
                document.getElementById('phone').value = currentPhone;
            }
            
            <?php if ($is_student): ?>
            // 學生專用欄位
            const currentStudentId = '<?php echo htmlspecialchars($current_student_id ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const currentGrade = '<?php echo htmlspecialchars($current_grade ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const currentClassName = '<?php echo htmlspecialchars($current_class_name ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            
            if (currentStudentId && document.getElementById('student_id')) {
                document.getElementById('student_id').value = currentStudentId;
            }
            if (currentGrade && document.getElementById('grade')) {
                document.getElementById('grade').value = currentGrade;
            }
            if (currentClassName && document.getElementById('class_name')) {
                document.getElementById('class_name').value = currentClassName;
            }
            <?php endif; ?>
        });

        // 帳號密碼表單提交（僅老師且為系統生成帳號時顯示）
        <?php if ($is_teacher && $is_auto_generated): ?>
        const credentialsForm = document.getElementById('credentialsForm');
        if (credentialsForm) {
            credentialsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const oldUsername = '<?php echo htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8'); ?>';
                const newUsername = document.getElementById('new_username') ? document.getElementById('new_username').value : '';
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const usernameChanged = <?php echo $username_changed; ?>;
                
                // 驗證密碼確認
                if (newPassword !== confirmPassword) {
                    const messageDiv = document.getElementById('credentialsMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '新密碼與確認密碼不一致';
                    return;
                }
                
                // 驗證密碼長度
                if (newPassword.length < 6) {
                    const messageDiv = document.getElementById('credentialsMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '密碼長度至少需要 6 個字元';
                    return;
                }
                
                const formData = new FormData();
                formData.append('old_username', oldUsername);
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                // 只有在尚未修改過帳號時才允許修改帳號
                if (usernameChanged == 0 && newUsername && newUsername.trim() !== '') {
                    // 驗證新帳號長度
                    if (newUsername.length < 3) {
                        const messageDiv = document.getElementById('credentialsMessage');
                        messageDiv.className = 'message error';
                        messageDiv.textContent = '帳號長度至少需要 3 個字元';
                        return;
                    }
                    formData.append('new_username', newUsername.trim());
                }
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                fetch('http://localhost:5000/teacher/update-credentials', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => {
                        const messageDiv = document.getElementById('credentialsMessage');
                        if (response.ok) {
                            messageDiv.className = 'message success';
                            messageDiv.textContent = data.message;
                            
                            // 如果帳號更新成功，更新 session 並重新載入頁面
                            if (data.new_username && data.new_username !== oldUsername) {
                                setTimeout(() => {
                                    alert('帳號已更新，頁面將重新載入。請使用新帳號登入。');
                                    window.location.href = 'logout.php';
                                }, 2000);
                            } else {
                                // 只更新密碼，清空表單
                                document.getElementById('current_password').value = '';
                                document.getElementById('new_password').value = '';
                                document.getElementById('confirm_password').value = '';
                            }
                        } else {
                            messageDiv.className = 'message error';
                            messageDiv.textContent = data.message || '更新失敗，請稍後再試';
                        }
                    });
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const messageDiv = document.getElementById('credentialsMessage');
                    messageDiv.className = 'message error';
                    if (error.name === 'AbortError') {
                        messageDiv.textContent = '請求超時，請稍後再試';
                    } else {
                        messageDiv.textContent = '更新失敗，請稍後再試';
                    }
                });
            });
        }
        <?php endif; ?>
        
        // 表單提交
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            const role = '<?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>';
            const name = document.getElementById('name') ? document.getElementById('name').value : '';
            const department = document.getElementById('department') ? document.getElementById('department').value : '';
            const phone = document.getElementById('phone') ? document.getElementById('phone').value : '';
            
            // 驗證必填欄位
            if (!department || !phone) {
                const messageDiv = document.getElementById('message');
                messageDiv.className = 'message error';
                messageDiv.textContent = '請填寫所有必填欄位（科系、電話）';
                return;
            }
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('name', name); // 從表單獲取姓名
            formData.append('department', department);
            formData.append('phone', phone);
            formData.append('role', role); // 添加角色資訊
            
            <?php if ($is_student): ?>
            // 學生專用欄位
            const studentId = document.getElementById('student_id') ? document.getElementById('student_id').value : '';
            const grade = document.getElementById('grade') ? document.getElementById('grade').value : '';
            const className = document.getElementById('class_name') ? document.getElementById('class_name').value : '';
            
            formData.append('student_id', studentId);
            formData.append('grade', grade);
            formData.append('class_name', className);
            <?php endif; ?>
            
            // 如果有選擇新的頭像，加入表單
            const avatarInput = document.getElementById('avatarInput');
            if (avatarInput && avatarInput.files && avatarInput.files[0]) {
                formData.append('avatar', avatarInput.files[0]);
            }
            
            // 使用 AbortController 來設置超時
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒超時
            
            // 根據角色選擇不同的保存方式
            if (role === '老師') {
                // 老師使用前端 PHP 保存（支援頭像上傳）
                fetch('save_teacher_profile.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => {
                        const messageDiv = document.getElementById('message');
                        if (response.ok && data.success) {
                            messageDiv.className = 'message success';
                            messageDiv.textContent = data.message || '個人資料保存成功';
                            
                            // 如果頭像已更新，更新預覽
                            if (data.avatar_updated && data.avatar_path) {
                                console.log('頭像已更新，新路徑:', data.avatar_path);
                                // 立即更新預覽圖片
                                const avatarPreview = document.getElementById('avatarPreview');
                                if (avatarPreview) {
                                    // 添加時間戳避免快取問題
                                    const newSrc = data.avatar_path + '?t=' + new Date().getTime();
                                    console.log('更新頭像預覽，新 src:', newSrc);
                                    avatarPreview.src = newSrc;
                                    
                                    // 監聽圖片載入錯誤
                                    avatarPreview.onerror = function() {
                                        console.error('頭像載入失敗，路徑:', newSrc);
                                        this.src = 'share/EIdROxGXsAE_LSs.jpg';
                                    };
                                    
                                    // 監聽圖片載入成功
                                    avatarPreview.onload = function() {
                                        console.log('頭像載入成功');
                                    };
                                }
                                // 1.5秒後重新載入頁面以確保所有地方都更新
                                setTimeout(() => {
                                    console.log('重新載入頁面以顯示新頭像');
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            messageDiv.className = 'message error';
                            messageDiv.textContent = data.message || '提交失敗，請稍後再試';
                        }
                    });
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const messageDiv = document.getElementById('message');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '保存失敗，請稍後再試。';
                });
            } else {
                // 學生使用前端 PHP 保存
                fetch('save_student_profile.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => {
                        const messageDiv = document.getElementById('message');
                        if (response.ok && data.success) {
                            messageDiv.className = 'message success';
                            messageDiv.textContent = data.message || '個人資料保存成功';
                            
                            // 如果頭像已更新，更新預覽
                            if (data.avatar_updated && data.avatar_path) {
                                console.log('頭像已更新，新路徑:', data.avatar_path);
                                // 立即更新預覽圖片
                                const avatarPreview = document.getElementById('avatarPreview');
                                if (avatarPreview) {
                                    // 添加時間戳避免快取問題
                                    const newSrc = data.avatar_path + '?t=' + new Date().getTime();
                                    console.log('更新頭像預覽，新 src:', newSrc);
                                    avatarPreview.src = newSrc;
                                    
                                    // 監聽圖片載入錯誤
                                    avatarPreview.onerror = function() {
                                        console.error('頭像載入失敗，路徑:', newSrc);
                                        this.src = 'share/EIdROxGXsAE_LSs.jpg';
                                    };
                                    
                                    // 監聽圖片載入成功
                                    avatarPreview.onload = function() {
                                        console.log('頭像載入成功');
                                    };
                                }
                                // 1.5秒後重新載入頁面以確保所有地方都更新
                                setTimeout(() => {
                                    console.log('重新載入頁面以顯示新頭像');
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            messageDiv.className = 'message error';
                            messageDiv.textContent = data.message || '提交失敗，請稍後再試';
                        }
                    });
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const messageDiv = document.getElementById('message');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '保存失敗，請稍後再試。';
                });
            }
        });
        
        // 修改密碼表單提交（所有老師都可以使用）
        <?php if ($is_teacher): ?>
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('password_current_password').value;
                const newPassword = document.getElementById('password_new_password').value;
                const confirmPassword = document.getElementById('password_confirm_password').value;
                
                // 驗證密碼確認
                if (newPassword !== confirmPassword) {
                    const messageDiv = document.getElementById('passwordMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '新密碼與確認密碼不一致';
                    return;
                }
                
                // 驗證密碼長度
                if (newPassword.length < 6) {
                    const messageDiv = document.getElementById('passwordMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '密碼長度至少需要 6 個字元';
                    return;
                }
                
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                fetch('update_teacher_password.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => {
                        const messageDiv = document.getElementById('passwordMessage');
                        if (response.ok && data.success) {
                            messageDiv.className = 'message success';
                            messageDiv.textContent = data.message || '密碼更新成功';
                            
                            // 如果密碼更新成功，自動登出並重定向到首頁
                            if (data.logout_required) {
                                setTimeout(() => {
                                    // 重定向到登出頁面，然後會自動跳轉到首頁
                                    window.location.href = 'logout.php';
                                }, 1500); // 1.5秒後登出，讓用戶看到成功訊息
                            } else {
                                // 清空表單
                                document.getElementById('password_current_password').value = '';
                                document.getElementById('password_new_password').value = '';
                                document.getElementById('password_confirm_password').value = '';
                            }
                        } else {
                            messageDiv.className = 'message error';
                            messageDiv.textContent = data.message || '更新失敗，請稍後再試';
                        }
                    });
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const messageDiv = document.getElementById('passwordMessage');
                    messageDiv.className = 'message error';
                    if (error.name === 'AbortError') {
                        messageDiv.textContent = '請求超時，請稍後再試';
                    } else {
                        messageDiv.textContent = '更新失敗，請稍後再試';
                    }
                });
            });
        }
        <?php endif; ?>
    </script>
<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>

</html> 