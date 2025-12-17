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

// 檢查留言權限（只有 @stu.ukn.edu.tw 的 email 可以留言）
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

try {
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo_check = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo_check->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo_check->prepare("SELECT email FROM user WHERE username = ? LIMIT 1");
    $stmt->execute([$_SESSION['username']]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 檢查 email 是否存在且為 @stu.ukn.edu.tw
    if (!$user_result || empty($user_result['email'])) {
        header("Location: index.php");
        exit;
    }
    
    $user_email = $user_result['email'];
    // 只有 @stu.ukn.edu.tw 的 email 可以留言
    if (strpos($user_email, '@stu.ukn.edu.tw') === false) {
        header("Location: index.php");
        exit;
    }
} catch(PDOException $e) {
    // 如果查詢失敗，跳轉到首頁
    header("Location: index.php");
    exit;
}

$auth = new SeniorMessageAuth();

// 從資料庫獲取用戶的email（而不是直接使用username）
// 因為一般註冊的帳號，username不是email，只有Google登入的才是
$user_email = $_SESSION['username']; // 預設值（Google登入時username就是email）
try {
    // 使用直接 PDO 連接（與 senior_messages.php 一致）
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT email FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_result && !empty($user_result['email'])) {
        $user_email = $user_result['email']; // 使用資料庫中的email
    }
} catch(PDOException $e) {
    error_log("獲取用戶email失敗: " . $e->getMessage());
    // 如果查詢失敗，繼續使用username作為email（兼容Google登入）
}

$permission_result = $auth->checkPermission($user_email);

// 從資料庫獲取用戶姓名和科系
$user_name = '';
$user_department = '';
try {
    // 使用直接 PDO 連接
    $host = 'localhost';
    $dbname = 'topics_good';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 從 user 表和 student 表獲取資料（姓名在 user 表，科系在 student 表）
    $stmt = $pdo->prepare("
        SELECT 
            u.name as user_name,
            u.id as user_id,
            s.department as department_code,
            d.name as department_name
        FROM user u
        LEFT JOIN student s ON u.id = s.user_id
        LEFT JOIN departments d ON s.department = d.code
        WHERE u.username = ? OR u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$user_email, $user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // 獲取姓名（優先使用 user 表的 name，如果沒有則使用 username）
        if (!empty($result['user_name'])) {
            $user_name = $result['user_name'];
        } elseif (!empty($user_email)) {
            // 如果沒有姓名，使用 username 或 email 的前綴
            $user_name = explode('@', $user_email)[0];
        }
        
        // 獲取科系名稱（優先使用 departments 表的名稱，如果沒有則使用代碼）
        if (!empty($result['department_name'])) {
            $user_department = $result['department_name'];
        } elseif (!empty($result['department_code'])) {
            $user_department = $result['department_code'];
        }
    } else {
        // 如果完全找不到用戶，至少嘗試從 user 表獲取姓名
        $stmt = $pdo->prepare("SELECT name FROM user WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$user_email, $user_email]);
        $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_result && !empty($user_result['name'])) {
            $user_name = $user_result['name'];
        } elseif (!empty($user_email)) {
            // 最後備用方案：使用 username 或 email
            $user_name = explode('@', $user_email)[0];
        }
    }
} catch (PDOException $e) {
    error_log("獲取用戶資料錯誤: " . $e->getMessage());
    // 如果查詢失敗，至少嘗試使用 username 作為姓名
    if (empty($user_name) && !empty($user_email)) {
        $user_name = explode('@', $user_email)[0];
    }
} catch (Exception $e) {
    error_log("獲取用戶資料錯誤: " . $e->getMessage());
    // 如果查詢失敗，至少嘗試使用 username 作為姓名
    if (empty($user_name) && !empty($user_email)) {
        $user_name = explode('@', $user_email)[0];
    }
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
    $author_department = $user_department; // 使用從資料庫獲取的科系（完全鎖定，不允許手動修改）
    $author_contact = trim($_POST['author_contact'] ?? '');
    $message_type = $_POST['message_type'] ?? '經驗分享';
    
    // 餐廳相關欄位（僅用於推薦餐廳類型）
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $restaurant_address = trim($_POST['restaurant_address'] ?? '');
    $restaurant_lat = $_POST['restaurant_lat'] ?? null;
    $restaurant_lng = $_POST['restaurant_lng'] ?? null;
    $restaurant_place_id = trim($_POST['restaurant_place_id'] ?? '');
    $restaurant_rating = $_POST['restaurant_rating'] ?? null;
    $delivery_rating = $_POST['delivery_rating'] ?? null;
    $price_level = $_POST['price_level'] ?? null;
    
    // 驗證表單資料
    if (empty($title) || empty($content)) {
        $form_error = '請填寫標題和留言內容';
    } elseif (empty($author_name)) {
        $form_error = '系統錯誤：無法獲取您的姓名資料，請聯繫管理員';
    } elseif ($message_type === '推薦餐廳') {
        // 推薦餐廳類型的額外驗證
        if (empty($restaurant_name)) {
            $form_error = '請填寫餐廳名稱';
        } elseif (empty($restaurant_address)) {
            $form_error = '請填寫餐廳地址';
        } elseif (empty($restaurant_rating)) {
            $form_error = '請選擇餐廳評分';
        } else {
            // 如果標題為空，使用餐廳名稱作為標題
            if (empty($title)) {
                $title = $restaurant_name;
            }
            
            // 將評分資訊追加到留言內容後面
            $rating_text = "\n\n";
            
            // 餐廳評分
            if (!empty($restaurant_rating)) {
                $rating_text .= "餐廳評分：" . intval($restaurant_rating) . "星\n";
            }
            
            // 外送評分
            if (!empty($delivery_rating)) {
                $rating_text .= "外送評分：" . intval($delivery_rating) . "星\n";
            } else {
                // 如果外送評分為空，表示無外送
                $rating_text .= "外送評分：無外送\n";
            }
            
            // 價格等級
            if (!empty($price_level)) {
                $price_level_text = '';
                switch (intval($price_level)) {
                    case 1:
                        $price_level_text = '$ - 平價';
                        break;
                    case 2:
                        $price_level_text = '$$ - 中等';
                        break;
                    case 3:
                        $price_level_text = '$$$ - 較貴';
                        break;
                    case 4:
                        $price_level_text = '$$$$ - 高檔';
                        break;
                    default:
                        $price_level_text = '未知';
                }
                $rating_text .= "價格等級：" . $price_level_text . "\n";
            }
            
            // 將評分資訊追加到內容後面
            $content_with_rating = $content . $rating_text;
            
            // 準備留言資料（包含餐廳信息）
            $messageData = [
                'title' => $title,
                'content' => $content_with_rating,
                'author_name' => $author_name,
                'author_email' => $user_email,
                'author_department' => $author_department,
                'author_grade' => $auth->getGradeDisplay($grade_year),
                'author_contact' => $author_contact,
                'message_type' => $message_type,
                'author_grade_year' => $grade_year,
                'restaurant_name' => $restaurant_name,
                'restaurant_address' => $restaurant_address,
                'restaurant_lat' => $restaurant_lat ? floatval($restaurant_lat) : null,
                'restaurant_lng' => $restaurant_lng ? floatval($restaurant_lng) : null,
                'restaurant_place_id' => $restaurant_place_id,
                'restaurant_rating' => $restaurant_rating ? intval($restaurant_rating) : null,
                'delivery_rating' => $delivery_rating ? intval($delivery_rating) : null,
                'price_level' => $price_level ? intval($price_level) : null
            ];
            
            // 創建留言
            $result = $auth->createMessage($messageData);
            
            if ($result['success']) {
                $success_message = $result['message'];
                // 清空表單
                $_POST = [];
                // 重定向到留言板
                header("Location: senior_messages.php?success=1");
                exit;
            } else {
                $form_error = $result['error'];
            }
        }
    } else {
        // 非餐廳推薦類型的正常流程
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
            // 重定向到留言板
            header("Location: senior_messages.php?success=1");
            exit;
        } else {
            $form_error = $result['error'];
            // 記錄詳細錯誤信息以便調試
            error_log("發布留言失敗: " . ($result['error'] ?? '未知錯誤'));
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
                        <select id="message_type" name="message_type" onchange="toggleRestaurantFields()">
                            <option value="經驗分享" <?php echo ($_POST['message_type'] ?? '') === '經驗分享' ? 'selected' : ''; ?>>經驗分享</option>
                            <option value="學習建議" <?php echo ($_POST['message_type'] ?? '') === '學習建議' ? 'selected' : ''; ?>>學習建議</option>
                            <option value="生活指南" <?php echo ($_POST['message_type'] ?? '') === '生活指南' ? 'selected' : ''; ?>>生活指南</option>
                            <option value="就業資訊" <?php echo ($_POST['message_type'] ?? '') === '就業資訊' ? 'selected' : ''; ?>>就業資訊</option>
                            <option value="推薦餐廳" <?php echo ($_POST['message_type'] ?? '') === '推薦餐廳' ? 'selected' : ''; ?>>推薦餐廳</option>
                            <option value="其他" <?php echo ($_POST['message_type'] ?? '') === '其他' ? 'selected' : ''; ?>>其他</option>
                        </select>
                    </div>
                    
                    <!-- 餐廳推薦專用欄位 -->
                    <div id="restaurant-fields" style="display: <?php echo ($_POST['message_type'] ?? '') === '推薦餐廳' ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="restaurant_search">搜尋餐廳位置 <span class="required">*</span></label>
                            <div style="display: flex; gap: 8px; align-items: stretch;">
                                <input type="text" id="restaurant_search" 
                                       placeholder="輸入餐廳名稱或地址搜尋..." 
                                       style="flex: 1; min-width: 0;">
                                <button type="button" onclick="searchRestaurant()" 
                                        style="padding: 12px 14px; background: var(--accent-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap; flex-shrink: 0; font-size: 13px; min-width: auto; width: auto;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="restaurant-results" style="margin-top: 10px; max-height: 200px; overflow-y: auto; display: none;"></div>
                            <small style="color: var(--secondary-text); font-size: 0.9rem; display: block; margin-top: 5px;">搜尋並選擇餐廳後，將自動填入所有餐廳資訊</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="restaurant_name">餐廳名稱 <span class="required">*</span></label>
                            <input type="text" id="restaurant_name" name="restaurant_name" 
                                   value="<?php echo htmlspecialchars($_POST['restaurant_name'] ?? ''); ?>" 
                                   placeholder="選擇餐廳後自動填入">
                        </div>
                        
                        <div class="form-group">
                            <label>餐廳地址 <span class="required">*</span></label>
                            <input type="text" id="restaurant_address" name="restaurant_address" 
                                   value="<?php echo htmlspecialchars($_POST['restaurant_address'] ?? ''); ?>" 
                                   placeholder="選擇餐廳後自動填入">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="restaurant_rating">餐廳評分 <span class="required">*</span></label>
                                <select id="restaurant_rating" name="restaurant_rating">
                                    <option value="">請選擇</option>
                                    <option value="5" <?php echo ($_POST['restaurant_rating'] ?? '') === '5' ? 'selected' : ''; ?>>5 星 - 非常推薦</option>
                                    <option value="4" <?php echo ($_POST['restaurant_rating'] ?? '') === '4' ? 'selected' : ''; ?>>4 星 - 推薦</option>
                                    <option value="3" <?php echo ($_POST['restaurant_rating'] ?? '') === '3' ? 'selected' : ''; ?>>3 星 - 普通</option>
                                    <option value="2" <?php echo ($_POST['restaurant_rating'] ?? '') === '2' ? 'selected' : ''; ?>>2 星 - 不推薦</option>
                                    <option value="1" <?php echo ($_POST['restaurant_rating'] ?? '') === '1' ? 'selected' : ''; ?>>1 星 - 非常不推薦</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="delivery_rating">外送評分</label>
                                <select id="delivery_rating" name="delivery_rating">
                                    <option value="">無外送</option>
                                    <option value="5" <?php echo ($_POST['delivery_rating'] ?? '') === '5' ? 'selected' : ''; ?>>5 星</option>
                                    <option value="4" <?php echo ($_POST['delivery_rating'] ?? '') === '4' ? 'selected' : ''; ?>>4 星</option>
                                    <option value="3" <?php echo ($_POST['delivery_rating'] ?? '') === '3' ? 'selected' : ''; ?>>3 星</option>
                                    <option value="2" <?php echo ($_POST['delivery_rating'] ?? '') === '2' ? 'selected' : ''; ?>>2 星</option>
                                    <option value="1" <?php echo ($_POST['delivery_rating'] ?? '') === '1' ? 'selected' : ''; ?>>1 星</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_level">價格等級</label>
                            <select id="price_level" name="price_level">
                                <option value="">請選擇</option>
                                <option value="1" <?php echo ($_POST['price_level'] ?? '') === '1' ? 'selected' : ''; ?>>$ - 平價</option>
                                <option value="2" <?php echo ($_POST['price_level'] ?? '') === '2' ? 'selected' : ''; ?>>$$ - 中等</option>
                                <option value="3" <?php echo ($_POST['price_level'] ?? '') === '3' ? 'selected' : ''; ?>>$$$ - 較貴</option>
                                <option value="4" <?php echo ($_POST['price_level'] ?? '') === '4' ? 'selected' : ''; ?>>$$$$ - 高檔</option>
                            </select>
                        </div>
                        
                        <!-- 隱藏欄位用於儲存地圖座標 -->
                        <input type="hidden" id="restaurant_lat" name="restaurant_lat" value="<?php echo htmlspecialchars($_POST['restaurant_lat'] ?? ''); ?>">
                        <input type="hidden" id="restaurant_lng" name="restaurant_lng" value="<?php echo htmlspecialchars($_POST['restaurant_lng'] ?? ''); ?>">
                        <input type="hidden" id="restaurant_place_id" name="restaurant_place_id" value="<?php echo htmlspecialchars($_POST['restaurant_place_id'] ?? ''); ?>">
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
                        <input type="text" id="author_department" name="author_department" value="<?php echo htmlspecialchars($user_department); ?>" placeholder="例如：資訊管理系" readonly style="background-color: var(--border-color); cursor: not-allowed;">
                        <small style="color: var(--secondary-text); font-size: 0.9rem;">科系已從您的帳號資料中自動填入，無法修改</small>
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
    
    <!-- 草稿系統 -->
    <script src="assets/js/draft-system.js"></script>
    <script>
        // 初始化草稿系統
        let draftSystem;
        document.addEventListener('DOMContentLoaded', function() {
            draftSystem = new DraftSystem({
                storageKey: 'senior_message_draft',
                formSelector: 'form[method="POST"]',
                excludeFields: ['author_name'], // 排除只讀欄位
                autoLoad: true,
                showStatus: true
            });
            
            // 添加清除草稿按鈕
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                const draftActions = document.createElement('div');
                draftActions.style.cssText = 'margin-bottom: 20px; padding: 15px; background: var(--hover-bg); border: 1px solid var(--border-color); border-radius: 10px; display: flex; gap: 10px; justify-content: flex-end;';
                draftActions.innerHTML = `
                    <button type="button" onclick="if(confirm('確定要清除草稿嗎？')) { draftSystem.clearDraft(); form.reset(); }" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 0.9rem;">
                        <i class="fas fa-trash"></i> 清除草稿
                    </button>
                `;
                form.insertBefore(draftActions, form.firstChild);
            }
        });
        
        // 表單驗證
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title');
                const content = document.getElementById('content');
                const messageType = document.getElementById('message_type');
                
                if (!title || !content || !messageType) {
                    e.preventDefault();
                    alert('表單欄位載入錯誤，請重新整理頁面');
                    return false;
                }
                
                const titleValue = title.value.trim();
                const contentValue = content.value.trim();
                const messageTypeValue = messageType.value;
                
                if (!titleValue || !contentValue) {
                    e.preventDefault();
                    alert('請填寫標題和留言內容');
                    return false;
                }
                
                if (contentValue.length < 20) {
                    e.preventDefault();
                    alert('留言內容至少需要20個字（目前：' + contentValue.length + '字）');
                    return false;
                }
                
                // 如果是推薦餐廳類型，檢查餐廳相關欄位
                if (messageTypeValue === '推薦餐廳') {
                    const restaurantName = document.getElementById('restaurant_name');
                    const restaurantAddress = document.getElementById('restaurant_address');
                    const restaurantRating = document.getElementById('restaurant_rating');
                    
                    if (!restaurantName || !restaurantAddress || !restaurantRating) {
                        e.preventDefault();
                        alert('餐廳欄位載入錯誤，請重新整理頁面');
                        return false;
                    }
                    
                    if (!restaurantName.value.trim()) {
                        e.preventDefault();
                        alert('請填寫餐廳名稱');
                        restaurantName.focus();
                        return false;
                    }
                    
                    if (!restaurantAddress.value.trim()) {
                        e.preventDefault();
                        alert('請填寫餐廳地址');
                        restaurantAddress.focus();
                        return false;
                    }
                    
                    if (!restaurantRating.value) {
                        e.preventDefault();
                        alert('請選擇餐廳評分');
                        restaurantRating.focus();
                        return false;
                    }
                    
                    // 如果標題為空，自動使用餐廳名稱作為標題
                    if (!titleValue || titleValue.trim() === '') {
                        title.value = restaurantName.value.trim();
                        console.log('標題為空，自動使用餐廳名稱作為標題:', restaurantName.value.trim());
                    }
                }
                
                // 提交成功後清除草稿
                if (draftSystem) {
                    draftSystem.clearDraft();
                }
            });
        }
        
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
        
        // 切換餐廳欄位顯示
        function toggleRestaurantFields() {
            const messageType = document.getElementById('message_type').value;
            const restaurantFields = document.getElementById('restaurant-fields');
            const restaurantAddress = document.getElementById('restaurant_address');
            const restaurantRating = document.getElementById('restaurant_rating');
            const restaurantName = document.getElementById('restaurant_name');
            const titleInput = document.getElementById('title');
            
            if (messageType === '推薦餐廳') {
                restaurantFields.style.display = 'block';
                // 顯示時設置為必填
                if (restaurantAddress) restaurantAddress.setAttribute('required', 'required');
                if (restaurantRating) restaurantRating.setAttribute('required', 'required');
                if (restaurantName) restaurantName.setAttribute('required', 'required');
                
                // 如果餐廳名稱已有值，自動填入標題
                if (restaurantName && restaurantName.value.trim() && titleInput) {
                    const currentTitle = titleInput.value.trim();
                    // 如果標題為空，或標題等於之前的餐廳名稱，則更新標題
                    if (!currentTitle || currentTitle === restaurantName.value.trim()) {
                        titleInput.value = restaurantName.value.trim();
                    }
                }
            } else {
                restaurantFields.style.display = 'none';
                // 隱藏時移除必填屬性，避免瀏覽器驗證錯誤
                if (restaurantAddress) restaurantAddress.removeAttribute('required');
                if (restaurantRating) restaurantRating.removeAttribute('required');
                if (restaurantName) restaurantName.removeAttribute('required');
            }
        }
        
        // 監聽餐廳名稱變化，自動更新標題
        document.addEventListener('DOMContentLoaded', function() {
            const restaurantName = document.getElementById('restaurant_name');
            const messageType = document.getElementById('message_type');
            const titleInput = document.getElementById('title');
            
            if (restaurantName && messageType && titleInput) {
                // 記錄之前的餐廳名稱，用於判斷是否需要更新標題
                let previousRestaurantName = restaurantName.value.trim();
                
                restaurantName.addEventListener('input', function() {
                    const currentMessageType = messageType.value;
                    const currentRestaurantName = restaurantName.value.trim();
                    const currentTitle = titleInput.value.trim();
                    
                    // 如果是推薦餐廳類型，且標題為空或標題等於之前的餐廳名稱
                    if (currentMessageType === '推薦餐廳') {
                        if (!currentTitle || currentTitle === previousRestaurantName) {
                            titleInput.value = currentRestaurantName;
                        }
                        previousRestaurantName = currentRestaurantName;
                    }
                });
            }
        });
        
        // 初始化時檢查
        document.addEventListener('DOMContentLoaded', function() {
            toggleRestaurantFields();
        });
    </script>
    
    <!-- Google Maps API for restaurant search -->
    <?php
    if (!defined('GOOGLE_MAPS_API_KEY')) {
        require_once 'config.php';
    }
    $google_maps_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_maps_key; ?>&libraries=places&language=zh-TW&callback=initRestaurantSearch" async defer></script>
    <script>
        let autocomplete;
        let placesService;
        let geocoder;
        
        // 統一的餐廳資訊填充函數
        function fillRestaurantInfo(place) {
            if (!place || !place.geometry) return;
            
            const restaurantName = place.name || '';
            const titleInput = document.getElementById('title');
            const messageType = document.getElementById('message_type');
            const restaurantNameInput = document.getElementById('restaurant_name');
            const searchInput = document.getElementById('restaurant_search');
            
            // 記錄之前的餐廳名稱
            const previousRestaurantName = restaurantNameInput.value.trim();
            
            // 填充所有表單欄位
            restaurantNameInput.value = restaurantName;
            document.getElementById('restaurant_address').value = place.formatted_address || '';
            document.getElementById('restaurant_lat').value = place.geometry.location.lat();
            document.getElementById('restaurant_lng').value = place.geometry.location.lng();
            document.getElementById('restaurant_place_id').value = place.place_id || '';
            
            // 更新搜索框顯示餐廳名稱
            if (searchInput) {
                searchInput.value = restaurantName;
            }
            
            // 如果是推薦餐廳類型，自動將餐廳名稱填入標題
            if (messageType && messageType.value === '推薦餐廳' && titleInput && restaurantName) {
                const currentTitle = titleInput.value.trim();
                // 如果標題為空，或標題等於之前的餐廳名稱，則更新標題
                if (!currentTitle || currentTitle === previousRestaurantName) {
                    titleInput.value = restaurantName;
                }
            }
            
            // 如果有評分，自動填入（四捨五入到最接近的整數）
            if (place.rating !== undefined && place.rating !== null) {
                const rating = Math.round(place.rating);
                const ratingSelect = document.getElementById('restaurant_rating');
                if (ratingSelect) {
                    ratingSelect.value = rating;
                }
            }
            
            // 如果有價格等級，自動填入
            if (place.price_level !== undefined && place.price_level !== null) {
                const priceLevelSelect = document.getElementById('price_level');
                if (priceLevelSelect) {
                    priceLevelSelect.value = place.price_level;
                }
            }
            
            // 檢查是否有外送服務
            if (place.types && place.types.includes('meal_delivery')) {
                console.log('此餐廳提供外送服務');
            }
            
            // 隱藏搜索結果
            const resultsDiv = document.getElementById('restaurant-results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
        }
        
        function initRestaurantSearch() {
            if (typeof google === 'undefined' || !google.maps) {
                console.error('Google Maps API 未載入');
                return;
            }
            
            const searchInput = document.getElementById('restaurant_search');
            if (!searchInput) return;
            
            // 初始化自動完成
            autocomplete = new google.maps.places.Autocomplete(searchInput, {
                types: ['establishment'],
                componentRestrictions: { country: 'tw' },
                fields: ['name', 'formatted_address', 'geometry', 'place_id', 'rating', 'price_level', 'types']
            });
            
            // 初始化 Places Service
            const map = new google.maps.Map(document.createElement('div'));
            placesService = new google.maps.places.PlacesService(map);
            geocoder = new google.maps.Geocoder();
            
            // 當選擇餐廳時（使用自動完成）
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                fillRestaurantInfo(place);
            });
        }
        
        // 手動搜尋餐廳
        function searchRestaurant() {
            const searchInput = document.getElementById('restaurant_search');
            const query = searchInput.value.trim();
            
            if (!query) {
                alert('請輸入餐廳名稱或地址');
                return;
            }
            
            if (!placesService) {
                alert('地圖服務未初始化，請稍候再試');
                return;
            }
            
            const resultsDiv = document.getElementById('restaurant-results');
            resultsDiv.innerHTML = '<p style="padding: 10px; text-align: center;">搜尋中...</p>';
            resultsDiv.style.display = 'block';
            
            // 使用 Text Search
            const request = {
                query: query + ' 台北市',
                type: 'restaurant'
            };
            
            placesService.textSearch(request, function(results, status) {
                resultsDiv.innerHTML = '';
                
                if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
                    results.slice(0, 5).forEach(function(place) {
                        const item = document.createElement('div');
                        item.style.cssText = 'padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 8px; cursor: pointer; background: var(--hover-bg); transition: all 0.2s ease;';
                        item.innerHTML = `
                            <div style="font-weight: 600; color: var(--text-color); margin-bottom: 4px;">${place.name}</div>
                            <div style="font-size: 0.9rem; color: var(--secondary-text); margin-bottom: 4px;">${place.formatted_address || '地址未知'}</div>
                            ${place.rating ? `<div style="font-size: 0.85rem; color: #f39c12;">★ ${place.rating.toFixed(1)}</div>` : ''}
                        `;
                        
                        item.addEventListener('click', function() {
                            // 使用統一的填充函數
                            fillRestaurantInfo(place);
                        });
                        
                        item.addEventListener('mouseenter', function() {
                            item.style.background = 'var(--accent-color)';
                            item.style.color = 'white';
                        });
                        
                        item.addEventListener('mouseleave', function() {
                            item.style.background = 'var(--hover-bg)';
                            item.style.color = '';
                        });
                        
                        resultsDiv.appendChild(item);
                    });
                } else {
                    resultsDiv.innerHTML = '<p style="padding: 10px; text-align: center; color: var(--secondary-text);">找不到餐廳，請嘗試其他關鍵字</p>';
                }
            });
        }
        
        // 允許按 Enter 鍵搜尋
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('restaurant_search');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchRestaurant();
                    }
                });
            }
        });
    </script>
    
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
</body>
</html>
