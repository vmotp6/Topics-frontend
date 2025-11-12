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

// 使用直接 PDO 連接（與其他頁面一致）
try {
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 先從 user 表獲取基本資訊
    $stmt = $pdo->prepare("SELECT name FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['name'])) {
        $user_name = $result['name'];
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
        // 從 student 表獲取
        $stmt = $pdo->prepare("
            SELECT s.department, s.phone 
            FROM student s
            JOIN user u ON s.user_id = u.id
            WHERE u.username = ?
        ");
        $stmt->execute([$_SESSION['username']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $current_department = $result['department'] ?? '';
            $current_phone = $result['phone'] ?? '';
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
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
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
    </style>
</head>

<body>
    <div class="profile-container">
        <h1 class="profile-title">個人資料設定</h1>
        
        <form id="profileForm">
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
            
            <button type="submit" class="submit-btn">保存資料</button>
        </form>
        
        <div id="message"></div>
        
        <a href="<?php echo $is_teacher ? 'teacher.php' : 'student.php'; ?>" class="back-btn">← 返回<?php echo $is_teacher ? '老師' : '學生'; ?>頁面</a>
    </div>

    <script>
        // 頁面載入時自動填入現有資料（如果 PHP 已經載入）
        window.addEventListener('load', function() {
            // 如果 PHP 已經從資料庫載入了資料，直接使用（不需要 API 調用）
            const currentDept = '<?php echo htmlspecialchars($current_department ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            const currentPhone = '<?php echo htmlspecialchars($current_phone ?? '', ENT_QUOTES, 'UTF-8'); ?>';
            
            if (currentDept) {
                document.getElementById('department').value = currentDept;
            }
            if (currentPhone) {
                document.getElementById('phone').value = currentPhone;
            }
        });

        // 表單提交
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            const name = '<?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>'; // 從PHP變數獲取姓名
            const role = '<?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>';
            const department = document.getElementById('department').value;
            const phone = document.getElementById('phone').value;
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('name', name); // 將姓名加入表單數據
            formData.append('department', department);
            formData.append('phone', phone);
            formData.append('role', role); // 添加角色資訊
            
            // 使用 AbortController 來設置超時
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒超時
            
            // 根據角色選擇不同的保存方式
            if (role === '老師') {
                // 老師使用後端 API
                fetch('http://localhost:5000/teacher/profile', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(response => {
                    clearTimeout(timeoutId);
                    return response.json().then(data => {
                        const messageDiv = document.getElementById('message');
                        if (response.ok) {
                            messageDiv.className = 'message success';
                            messageDiv.textContent = data.message;
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
    </script>
<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>

</html> 