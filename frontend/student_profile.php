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

// 檢查是否為學生角色（支援角色代碼 'STU' 和中文名稱 '學生'）
$user_role = $_SESSION['role'] ?? '';
if ($user_role !== '學生' && $user_role !== 'STU') {
    header("Location: index.php");
    exit;
}

// 獲取用戶姓名（學生）
$user_name = '';
$current_department = '';
$current_phone = '';
$current_student_id = '';
$current_grade = '';
$current_class_name = '';
$current_email = '';
$current_username = $_SESSION['username'] ?? '';

// 使用直接 PDO 連接（與其他頁面一致）
try {
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 先從 user 表獲取基本資訊（包括 email 和頭像）
    $stmt = $pdo->prepare("SELECT name, email, profile_picture FROM user WHERE username = ?");
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
        // 獲取當前頭像
        if (!empty($result['profile_picture'])) {
            $current_profile_picture = $result['profile_picture'];
        }
    }
    
    // 從 student 表獲取所有欄位（包括代碼轉換為名稱）
    // 使用 LEFT JOIN 以確保即使 student 表中沒有記錄也能獲取 user 資料
    $stmt = $pdo->prepare("
        SELECT s.department, s.phone, s.student_id, s.grade, s.class_name, s.email 
        FROM user u
        LEFT JOIN student s ON u.id = s.user_id
        WHERE u.username = ?
    ");
    $stmt->execute([$_SESSION['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 調試：記錄查詢結果
    error_log("Student profile query result: " . print_r($result, true));
    error_log("Current username: " . ($_SESSION['username'] ?? 'not set'));
    
    // 即使 result 為 false 或空，也要處理（可能 student 表沒有記錄）
    if ($result !== false) {
        // 獲取學生資料（可能為 NULL）
        $current_phone = isset($result['phone']) && $result['phone'] !== null ? $result['phone'] : '';
        $current_student_id = isset($result['student_id']) && $result['student_id'] !== null ? $result['student_id'] : '';
        $current_class_name = isset($result['class_name']) && $result['class_name'] !== null ? $result['class_name'] : '';
        
        // 如果student表有email，優先使用；否則使用之前從user表獲取的
        if (!empty($result['email']) && $result['email'] !== null) {
            $current_email = $result['email'];
        }
        
        // 將科系代碼轉換為名稱
        $dept_code = isset($result['department']) && $result['department'] !== null ? $result['department'] : '';
        if (!empty($dept_code)) {
            $stmt_dept = $pdo->prepare("SELECT name FROM departments WHERE code = ?");
            $stmt_dept->execute([$dept_code]);
            $dept_result = $stmt_dept->fetch(PDO::FETCH_ASSOC);
            if ($dept_result && !empty($dept_result['name'])) {
                $current_department = $dept_result['name'];
            } else {
                // 如果找不到名稱，使用代碼（但這不應該發生）
                $current_department = $dept_code;
            }
        } else {
            $current_department = '';
        }
        
        // 將年級代碼轉換為名稱
        $grade_code = isset($result['grade']) && $result['grade'] !== null ? $result['grade'] : '';
        if (!empty($grade_code)) {
            $stmt_grade = $pdo->prepare("SELECT name FROM identity_options WHERE code = ?");
            $stmt_grade->execute([$grade_code]);
            $grade_result = $stmt_grade->fetch(PDO::FETCH_ASSOC);
            if ($grade_result && !empty($grade_result['name'])) {
                $grade_name = $grade_result['name'];
                // 將年級代碼/名稱轉換為表單顯示格式
                $grade_display_mapping = [
                    'F1' => '專一', 'F2' => '專二', 'F3' => '專三', 'F4' => '專四', 'F5' => '專五',
                    'J1' => '國一', 'J2' => '國二', 'J3' => '國三',
                    'H1' => '高一', 'H2' => '高二', 'H3' => '高三'
                ];
                // 先檢查代碼映射，如果沒有則使用資料庫中的名稱
                $current_grade = $grade_display_mapping[$grade_code] ?? $grade_name;
            } else {
                // 如果找不到，嘗試直接使用代碼映射
                $grade_display_mapping = [
                    'F1' => '專一', 'F2' => '專二', 'F3' => '專三', 'F4' => '專四', 'F5' => '專五',
                    'J1' => '國一', 'J2' => '國二', 'J3' => '國三',
                    'H1' => '高一', 'H2' => '高二', 'H3' => '高三'
                ];
                $current_grade = $grade_display_mapping[$grade_code] ?? '';
            }
        } else {
            $current_grade = '';
        }
        
        // 調試：記錄處理後的變數值
        error_log("Loaded values - name: {$user_name}, dept: {$current_department}, grade: {$current_grade}, phone: {$current_phone}, student_id: {$current_student_id}, class: {$current_class_name}");
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
    <title>學生個人資料</title>
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
        
        <?php
        // 調試：顯示載入的資料（僅在開發時使用，生產環境應移除）
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>調試信息：</strong><br>";
            echo "姓名: " . htmlspecialchars($user_name) . "<br>";
            echo "科系: " . htmlspecialchars($current_department) . "<br>";
            echo "年級: " . htmlspecialchars($current_grade) . "<br>";
            echo "電話: " . htmlspecialchars($current_phone) . "<br>";
            echo "學號: " . htmlspecialchars($current_student_id) . "<br>";
            echo "班級: " . htmlspecialchars($current_class_name) . "<br>";
            echo "Username: " . htmlspecialchars($_SESSION['username'] ?? 'not set') . "<br>";
            echo "</div>";
        }
        ?>
        
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
            <button type="button" id="saveAvatarBtn" class="submit-btn" style="margin-top: 10px; max-width: 150px; padding: 8px 16px; font-size: 14px; margin-left: auto; margin-right: auto; display: block;">儲存頭像</button>
            <div class="avatar-info">支援 JPG、PNG 格式，建議大小 200x200 像素</div>
            <div id="avatarMessage"></div>
        </div>
        
        <form id="profileForm" enctype="multipart/form-data">
            <!-- 基本資料 -->
            <div class="form-group">
                <label for="name">姓名</label>
                <input type="text" id="name" name="name" placeholder="請輸入姓名" value="<?php echo htmlspecialchars($user_name ?? ''); ?>">
            </div>
            
            <!-- 學生專用欄位 -->
            <div class="form-group">
                <label for="student_id">學號</label>
                <input type="text" id="student_id" name="student_id" placeholder="請輸入學號" value="<?php echo htmlspecialchars($current_student_id ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="department">科系</label>
                <select id="department" name="department">
                    <option value="" <?php echo empty($current_department ?? '') ? 'selected' : ''; ?>>請選擇科系</option>
                    <?php
                    // 從資料庫動態載入科系選項
                    try {
                        $stmt_depts = $pdo->prepare("SELECT code, name FROM departments ORDER BY name");
                        $stmt_depts->execute();
                        $departments = $stmt_depts->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($departments as $dept) {
                            $dept_name = htmlspecialchars($dept['name']);
                            $is_selected = ($current_department === $dept_name) ? 'selected' : '';
                            echo "<option value=\"{$dept_name}\" {$is_selected}>{$dept_name}</option>";
                        }
                    } catch (PDOException $e) {
                        error_log("載入科系選項錯誤: " . $e->getMessage());
                        // 如果載入失敗，使用預設選項
                        echo '<option value="資訊管理科" ' . ($current_department === '資訊管理科' ? 'selected' : '') . '>資訊管理科</option>';
                        echo '<option value="企業管理科" ' . ($current_department === '企業管理科' ? 'selected' : '') . '>企業管理科</option>';
                        echo '<option value="護理科" ' . ($current_department === '護理科' ? 'selected' : '') . '>護理科</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="grade">年級</label>
                <select id="grade" name="grade">
                    <option value="" <?php echo empty($current_grade ?? '') ? 'selected' : ''; ?>>請選擇年級</option>
                    <optgroup label="五專">
                        <option value="專一" <?php echo $current_grade === '專一' ? 'selected' : ''; ?>>專一</option>
                        <option value="專二" <?php echo $current_grade === '專二' ? 'selected' : ''; ?>>專二</option>
                        <option value="專三" <?php echo $current_grade === '專三' ? 'selected' : ''; ?>>專三</option>
                        <option value="專四" <?php echo $current_grade === '專四' ? 'selected' : ''; ?>>專四</option>
                        <option value="專五" <?php echo $current_grade === '專五' ? 'selected' : ''; ?>>專五</option>
                    </optgroup>
                    <optgroup label="國中">
                        <option value="國一" <?php echo $current_grade === '國一' ? 'selected' : ''; ?>>國一</option>
                        <option value="國二" <?php echo $current_grade === '國二' ? 'selected' : ''; ?>>國二</option>
                        <option value="國三" <?php echo $current_grade === '國三' ? 'selected' : ''; ?>>國三</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group">
                <label for="class_name">班級</label>
                <input type="text" id="class_name" name="class_name" placeholder="例如：資管一甲" value="<?php echo htmlspecialchars($current_class_name ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">電話</label>
                <input type="tel" id="phone" name="phone" placeholder="請輸入電話號碼" value="<?php echo htmlspecialchars($current_phone ?? ''); ?>">
            </div>
            
            <button type="submit" class="submit-btn">儲存資料</button>
        </form>
        
        <div id="message"></div>
        
        <!-- 修改密碼區塊 -->
        <div class="credentials-section">
            <h2>修改密碼</h2>
            <form id="passwordForm">
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
                
                <button type="submit" class="submit-btn">更新密碼</button>
            </form>
            
            <div id="passwordMessage"></div>
        </div>
        
        <a href="index.php" class="back-btn">← 返回首頁</a>
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

        // 單獨儲存頭像
        document.getElementById('saveAvatarBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const avatarInput = document.getElementById('avatarInput');
            if (!avatarInput || !avatarInput.files || !avatarInput.files[0]) {
                const messageDiv = document.getElementById('avatarMessage');
                messageDiv.className = 'message error';
                messageDiv.textContent = '請先選擇頭像';
                return;
            }
            
            const formData = new FormData();
            formData.append('avatar', avatarInput.files[0]);
            formData.append('username', '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>');
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            
            fetch('save_student_profile.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                return response.json().then(data => {
                    const messageDiv = document.getElementById('avatarMessage');
                    if (response.ok && data.success) {
                        messageDiv.className = 'message success';
                        messageDiv.textContent = data.message || '頭像儲存成功';
                        
                        // 如果頭像已更新，更新預覽
                        if (data.avatar_updated && data.avatar_path) {
                            console.log('頭像已更新，新路徑:', data.avatar_path);
                            const avatarPreview = document.getElementById('avatarPreview');
                            if (avatarPreview) {
                                const newSrc = data.avatar_path + '?t=' + new Date().getTime();
                                console.log('更新頭像預覽，新 src:', newSrc);
                                avatarPreview.src = newSrc;
                                
                                avatarPreview.onerror = function() {
                                    console.error('頭像載入失敗，路徑:', newSrc);
                                    this.src = 'share/EIdROxGXsAE_LSs.jpg';
                                };
                                
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
                        messageDiv.textContent = data.message || '頭像儲存失敗，請稍後再試';
                    }
                });
            })
            .catch(error => {
                clearTimeout(timeoutId);
                const messageDiv = document.getElementById('avatarMessage');
                messageDiv.className = 'message error';
                if (error.name === 'AbortError') {
                    messageDiv.textContent = '請求超時，請稍後再試';
                } else {
                    messageDiv.textContent = '頭像儲存失敗，請稍後再試';
                }
            });
        });

        // PHP 已經在 HTML 的 value 屬性中設置了所有資料，不需要 JavaScript 再次設置
        // 這裡保留空的事件監聽器以備將來使用

        // 表單提交（學生資料）
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            const role = '<?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>';
            const name = document.getElementById('name') ? document.getElementById('name').value : '';
            const department = document.getElementById('department') ? document.getElementById('department').value : '';
            const phone = document.getElementById('phone') ? document.getElementById('phone').value : '';
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('name', name); // 從表單獲取姓名
            formData.append('department', department);
            formData.append('phone', phone);
            formData.append('role', role); // 添加角色資訊
            
            // 學生專用欄位
            const studentId = document.getElementById('student_id') ? document.getElementById('student_id').value : '';
            const grade = document.getElementById('grade') ? document.getElementById('grade').value : '';
            const className = document.getElementById('class_name') ? document.getElementById('class_name').value : '';
            
            formData.append('student_id', studentId);
            formData.append('grade', grade);
            formData.append('class_name', className);
            
            // 頭像不再包含在個人資料表單中，已單獨處理
            
            // 使用 AbortController 來設置超時
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒超時
            
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
                        messageDiv.textContent = data.message || '個人資料儲存成功';
                        
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
                messageDiv.textContent = '儲存存失敗，請稍後再試。';
            });
        });

        // 密碼表單提交
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
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
                
                // 驗證密碼必須包含至少一個英文字母
                if (!/[a-zA-Z]/.test(newPassword)) {
                    const messageDiv = document.getElementById('passwordMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '密碼必須包含至少一個英文字母';
                    return;
                }
                
                // 驗證密碼必須包含至少一個數字
                if (!/[0-9]/.test(newPassword)) {
                    const messageDiv = document.getElementById('passwordMessage');
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '密碼必須包含至少一個數字';
                    return;
                }
                
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                fetch('update_student_password.php', {
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
    </script>
<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>

</html>
