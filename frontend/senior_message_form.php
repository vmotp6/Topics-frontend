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
    $author_name = trim($_POST['author_name'] ?? '');
    $author_department = trim($_POST['author_department'] ?? '');
    $author_contact = trim($_POST['author_contact'] ?? '');
    $message_type = $_POST['message_type'] ?? '經驗分享';
    
    // 驗證表單資料
    if (empty($title) || empty($content) || empty($author_name)) {
        $form_error = '請填寫所有必填欄位';
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
        body { 
            padding-top: 100px; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.2rem;
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <a href="senior_messages.php" class="back-btn">← 返回留言板</a>
        
        <div class="header">
            <h1>📝 發布學長姐留言</h1>
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
            <div class="permission-info">
                <h3>✅ 您有留言權限</h3>
                <p><strong>您的帳號：</strong><?php echo htmlspecialchars($user_email); ?></p>
                <p><strong>您的年級：</strong><?php echo $auth->getGradeDisplay($grade_year); ?></p>
                <p><strong>入學年份：</strong><?php echo $grade_year; ?>年</p>
            </div>
            
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
                        <label for="author_name">您的姓名 <span class="required">*</span></label>
                        <input type="text" id="author_name" name="author_name" value="<?php echo htmlspecialchars($_POST['author_name'] ?? ''); ?>" required>
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
    
    <script>
        // 表單驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            const authorName = document.getElementById('author_name').value.trim();
            
            if (!title || !content || !authorName) {
                e.preventDefault();
                alert('請填寫所有必填欄位');
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
