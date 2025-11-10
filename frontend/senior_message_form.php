<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'senior_message_auth.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到首頁
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 檢查是否為學生角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '學生') {
    header("Location: index.php");
    exit;
}

$auth = new SeniorMessageAuth();
$user_email = $_SESSION['username']; // 假設username就是email
$permission_result = $auth->checkPermission($user_email);

// 從資料庫獲取用戶姓名 - 使用與 senior_messages.php 相同的連接方式
$user_name = '';
try {
    // 使用直接 PDO 連接（與 senior_messages.php 一致）
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 優先從 student 表獲取姓名（因為學生資料主要在 student 表）
    $stmt = $pdo->prepare("
        SELECT s.name 
        FROM student s
        JOIN user u ON s.user_id = u.id
        WHERE u.username = ?
    ");
    $stmt->execute([$user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['name'])) {
        $user_name = $result['name'];
    } else {
        // 如果 student 表中沒有，嘗試從 user 表獲取（備用方案）
        $stmt = $pdo->prepare("SELECT name FROM user WHERE username = ?");
        $stmt->execute([$user_email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['name'])) {
            $user_name = $result['name'];
        }
    }
} catch (PDOException $e) {
    error_log("獲取用戶姓名錯誤: " . $e->getMessage());
} catch (Exception $e) {
    error_log("獲取用戶姓名錯誤: " . $e->getMessage());
}

// 如果沒有權限，顯示錯誤頁面
if (!$permission_result['has_permission']) {
    $error_message = $permission_result['error'];
    $error_code = $permission_result['error_code'];
    $grade_year = $permission_result['grade_year'] ?? null;
} else {
    $error_message = null;
    $error_code = null;
    $grade_year = $permission_result['grade_year'];
}

// 處理表單提交
$success_message = '';
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $permission_result['has_permission']) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author_name = $user_name; // 使用從資料庫獲取的姓名
    $author_department = trim($_POST['author_department'] ?? '');
    $author_contact = trim($_POST['author_contact'] ?? '');
    $message_type = $_POST['message_type'] ?? '經驗分享';
    
    // 驗證表單資料（姓名已從資料庫自動填入，不需要檢查）
    if (empty($title) || empty($content)) {
        $form_error = '請填寫標題和留言內容';
    } elseif (empty($author_name)) {
        $form_error = '系統錯誤：無法獲取您的姓名資料，請聯繫管理員';
    } else {
        // 準備留言資料
        $messageData = [
            'title' => $title,
            'content' => $content,
            'author_name' => $author_name,
            'author_email' => $user_email,
            'author_department' => $author_department,
            'author_grade' => $auth->getGradeDisplay($grade_year),
            'author_contact' => $author_contact,
            'message_type' => $message_type,
            'author_grade_year' => $grade_year
        ];
        
        // 創建留言
        $result = $auth->createMessage($messageData);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // 清空表單
            $_POST = [];
        } else {
            $form_error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>發布學長姐留言</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        :root {
            --bg-color: #000;
            --text-color: #fff;
            --secondary-text: #71767b;
            --border-color: #333;
            --hover-bg: #16181c;
            --accent-color: #1d9bf0;
            --card-bg: transparent;
        }
        
        [data-theme="light"] {
            --bg-color: #fff;
            --text-color: #000;
            --secondary-text: #536471;
            --border-color: #e1e8ed;
            --hover-bg: #f7f9fa;
            --accent-color: #1d9bf0;
            --card-bg: transparent;
        }
        
        body { 
            padding-top: 100px !important; /* 適當間距避免被固定 header 遮擋 */
            background: var(--bg-color);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px !important; /* 手機版間距 */
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 130px !important; /* 更小螢幕間距 */
            }
        }
        
        /* 響應式間距調整 */
        @media (max-width: 768px) {
            .theme-toggle {
                top: 140px; /* 手機版適當間距 */
                right: 15px;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .theme-toggle {
                top: 150px; /* 更小螢幕適當間距 */
                right: 10px;
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
        }
        
        .container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 20px 40px;
            min-height: calc(100vh - 120px);
            position: relative;
            z-index: 1;
            display: flex;
            gap: 40px;
            box-sizing: border-box;
        }
        
        .left-panel {
            flex: 0 0 400px;
            background: var(--hover-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            height: fit-content;
        }
        
        .right-panel {
            flex: 1;
            min-width: 0;
            max-width: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 0;
        }
        
        .header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: var(--secondary-text);
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 20px;
        }
        
        .user-info {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            margin-bottom: 0;
            text-align: left;
            position: relative;
            z-index: 2;
            clear: both;
        }
        
        .user-info h3 {
            color: var(--text-color);
            font-size: 1.3rem;
            margin: 0 0 20px 0;
            font-weight: 600;
            text-align: center;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .user-detail {
            text-align: left;
            padding: 15px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }
        
        .user-detail .label {
            color: var(--secondary-text);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .user-detail .value {
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .form-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 35px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            z-index: 1;
            clear: both;
        }
        
        .form-container:hover {
            border-color: var(--accent-color);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            background: var(--hover-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1.1rem;
            color: var(--text-color);
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(29, 155, 240, 0.1);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .submit-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
        }
        
        .back-btn {
            display: inline-block;
            background: transparent;
            color: var(--secondary-text);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .back-btn:hover {
            background: var(--hover-bg);
            color: var(--text-color);
            border-color: var(--accent-color);
        }
        
        .theme-toggle {
            position: fixed;
            top: 120px; /* 適當位置避免與 header 重疊 */
            right: 20px;
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.3rem;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(29, 155, 240, 0.3);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        }
        
        .submit-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,117,125,0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .permission-info {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .permission-info h3 {
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .permission-info p {
            margin: 5px 0;
            color: #155724;
        }
        
        .no-permission {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .no-permission h2 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .no-permission p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }
            
            .left-panel {
                flex: none;
                margin-bottom: 30px;
            }
            
            .right-panel {
                flex: none;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .form-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="切換主題">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <a href="senior_messages.php" class="back-btn">← 返回留言板</a>
        
        <div class="left-panel">
            <div class="user-info">
                <h3>✅ 您有留言權限</h3>
                <div class="user-details">
                    <div class="user-detail">
                        <div class="label">帳號</div>
                        <div class="value"><?php echo htmlspecialchars($user_email); ?></div>
                    </div>
                    <div class="user-detail">
                        <div class="label">年級</div>
                        <div class="value"><?php echo $permission_result['current_grade'] ?? '未知'; ?>年級</div>
                    </div>
                    <div class="user-detail">
                        <div class="label">入學年</div>
                        <div class="value"><?php echo $permission_result['grade_year'] ?? '未知'; ?>年</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="header">
                <h1>✍️ 發布留言</h1>
                <p>分享您的經驗與建議，幫助學弟妹更好地適應大學生活</p>
            </div>
        
        <?php if ($error_message): ?>
            <div class="no-permission">
                <h2>❌ 權限不足</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <?php if ($error_code === 'INVALID_EMAIL'): ?>
                    <p>請使用 @stu.ukn.edu.tw 的學生帳號登入</p>
                <?php elseif ($error_code === 'GRADE_TOO_HIGH'): ?>
                    <p>您的年級：<?php echo $grade_year; ?>年級（目前五年級是110）</p>
                    <p>只有110年級以下的學生可以發布留言</p>
                <?php endif; ?>
                <a href="senior_messages.php" class="back-btn">返回留言板</a>
            </div>
        <?php else: ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($form_error): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo htmlspecialchars($form_error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">留言標題 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message_type">留言類型</label>
                        <select id="message_type" name="message_type">
                            <option value="經驗分享" <?php echo ($_POST['message_type'] ?? '') === '經驗分享' ? 'selected' : ''; ?>>經驗分享</option>
                            <option value="學習建議" <?php echo ($_POST['message_type'] ?? '') === '學習建議' ? 'selected' : ''; ?>>學習建議</option>
                            <option value="生活指南" <?php echo ($_POST['message_type'] ?? '') === '生活指南' ? 'selected' : ''; ?>>生活指南</option>
                            <option value="就業資訊" <?php echo ($_POST['message_type'] ?? '') === '就業資訊' ? 'selected' : ''; ?>>就業資訊</option>
                            <option value="其他" <?php echo ($_POST['message_type'] ?? '') === '其他' ? 'selected' : ''; ?>>其他</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">留言內容 <span class="required">*</span></label>
                        <textarea id="content" name="content" placeholder="請分享您的經驗、建議或心得..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_name">您的姓名</label>
                        <input type="text" id="author_name" name="author_name" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="background-color: var(--border-color); cursor: not-allowed;">
                        <small style="color: var(--secondary-text); font-size: 0.9rem;">姓名已從您的帳號資料中自動填入</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_department">科系</label>
                        <input type="text" id="author_department" name="author_department" value="<?php echo htmlspecialchars($_POST['author_department'] ?? ''); ?>" placeholder="例如：資訊管理系">
                    </div>
                    
                    <div class="form-group">
                        <label for="author_contact">聯絡方式</label>
                        <input type="text" id="author_contact" name="author_contact" value="<?php echo htmlspecialchars($_POST['author_contact'] ?? ''); ?>" placeholder="例如：Line ID、電話號碼等">
                    </div>
                    
                    <button type="submit" class="submit-btn">發布留言</button>
                </form>
            </div>
        <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 主題切換功能
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            if (body.getAttribute('data-theme') === 'light') {
                body.setAttribute('data-theme', 'dark');
                themeIcon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                themeIcon.textContent = '☀️';
                localStorage.setItem('theme', 'light');
            }
        }
        
        // 載入保存的主題
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            body.setAttribute('data-theme', savedTheme);
            themeIcon.textContent = savedTheme === 'light' ? '☀️' : '🌙';
        }
        
        // 頁面載入時應用主題
        document.addEventListener('DOMContentLoaded', loadTheme);
        
        // 表單驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            
            if (!title || !content) {
                e.preventDefault();
                alert('請填寫標題和留言內容');
                return false;
            }
            
            if (content.length < 20) {
                e.preventDefault();
                alert('留言內容至少需要20個字');
                return false;
            }
        });
        
        // 字數統計
        const contentTextarea = document.getElementById('content');
        const charCount = document.createElement('div');
        charCount.style.textAlign = 'right';
        charCount.style.color = '#6c757d';
        charCount.style.fontSize = '0.9rem';
        charCount.style.marginTop = '5px';
        contentTextarea.parentNode.appendChild(charCount);
        
        function updateCharCount() {
            const length = contentTextarea.value.length;
            charCount.textContent = `${length} 字`;
            if (length < 20) {
                charCount.style.color = '#e74c3c';
            } else {
                charCount.style.color = '#6c757d';
            }
        }
        
        contentTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
    </script>
    
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
