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

// 檢查是否為老師角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
    header("Location: index.php");
    exit;
}

// 獲取老師姓名
require_once 'config.php'; // 引入資料庫配置
$teacher_name = '';
try {
    $conn = getDatabaseConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT name FROM user WHERE username = ?");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $teacher_name = $row['name'];
        }
        $conn->close();
    }
} catch (Exception $e) {
    // 如果查詢失敗，teacher_name 會是空字串，但頁面仍可正常運作
    error_log("無法從資料庫獲取老師姓名: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <?php include("share/header.php"); ?>
    <title>老師個人資料</title>
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
                    <option value="" disabled selected>請選擇科系</option>
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
                <label for="phone">電話</label>
                <input type="tel" id="phone" name="phone" placeholder="請輸入電話號碼" required>
            </div>
            
            <button type="submit" class="submit-btn">保存資料</button>
        </form>
        
        <div id="message"></div>
        
        <a href="teacher.php" class="back-btn">← 返回老師頁面</a>
    </div>

    <script>
        // 頁面載入時檢查是否已有個人資料（暫時禁用，避免 500 錯誤）
        window.addEventListener('load', function() {
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            if (username) {
                // 暫時禁用自動填入功能，等後端服務器修復後再啟用
                console.log('個人資料檢查功能已暫時禁用');
                return;
                
                // 使用 AbortController 來設置超時
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000); // 5秒超時
                
                fetch(`http://100.79.58.120:5000/teacher/profile/${username}`, {
                    signal: controller.signal,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    }
                })
                    .then(response => {
                        clearTimeout(timeoutId);
                        if (response.ok) {
                            return response.json();
                        } else if (response.status === 404) {
                            // 尚未填寫個人資料，不填入表單
                            return null;
                        } else {
                            // 其他錯誤狀態碼，靜默處理
                            return null;
                        }
                    })
                    .then(data => {
                        if (data) {
                            // 如果已有資料，填入表單
                            document.getElementById('department').value = data.department;
                            document.getElementById('phone').value = data.phone;
                        }
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);
                        // 靜默處理錯誤，不顯示任何錯誤訊息
                    });
            }
        });

        // 表單提交
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            const name = '<?php echo htmlspecialchars($teacher_name, ENT_QUOTES, 'UTF-8'); ?>'; // 從PHP變數獲取姓名
            const department = document.getElementById('department').value;
            const phone = document.getElementById('phone').value;
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('name', name); // 將姓名加入表單數據
            formData.append('department', department);
            formData.append('phone', phone);
            
            // 使用 AbortController 來設置超時
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒超時
            
            fetch('http://100.79.58.120:5000/teacher/profile', {
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
        });
    </script>
<?php include("share/footer.php"); ?>
<?php include("share/ai_widget.php"); ?>

</body>

</html> 