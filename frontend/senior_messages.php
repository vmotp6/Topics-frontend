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

// 檢查留言權限
$auth = new SeniorMessageAuth();
$user_email = $_SESSION['username'];
$permission_result = $auth->checkPermission($user_email);
$can_post_message = $permission_result['has_permission'];

// 資料庫連接 - 使用與現有系統相同的配置
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 獲取留言資料
$messages = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM senior_messages WHERE is_published = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 為每個留言檢查用戶是否已點讚
    $user_email = $_SESSION['username'];
    foreach ($messages as &$message) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM message_likes WHERE message_id = ? AND user_email = ?");
            $stmt->execute([$message['id'], $user_email]);
            $message['user_liked'] = $stmt->fetch() ? true : false;
        } catch(PDOException $e) {
            // 如果 message_likes 表不存在，設為 false
            $message['user_liked'] = false;
        }
    }
} catch(PDOException $e) {
    $error_message = "載入留言失敗: " . $e->getMessage();
}

// 處理愛心切換功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    // 設定 JSON 回應頭
    header('Content-Type: application/json; charset=utf-8');
    
    $message_id = (int)$_POST['message_id'];
    $is_liked = $_POST['is_liked'] === '1';
    $user_email = $_SESSION['username'] ?? '';
    
    // 檢查用戶是否登入
    if (empty($user_email)) {
        echo json_encode(['success' => false, 'error' => '請先登入']);
        exit;
    }
    
    try {
        // 檢查 message_likes 表是否存在，如果不存在則創建
        $stmt = $pdo->query("SHOW TABLES LIKE 'message_likes'");
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // 自動創建表
            $createTableSQL = "CREATE TABLE IF NOT EXISTS message_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_like (message_id, user_email),
                FOREIGN KEY (message_id) REFERENCES senior_messages(id) ON DELETE CASCADE,
                INDEX idx_message_id (message_id),
                INDEX idx_user_email (user_email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createTableSQL);
        }
        
        // 檢查是否已經點讚過
        $stmt = $pdo->prepare("SELECT id FROM message_likes WHERE message_id = ? AND user_email = ?");
        $stmt->execute([$message_id, $user_email]);
        $existing_like = $stmt->fetch();
        
        if ($is_liked) {
            // 取消愛心
            if ($existing_like) {
                // 刪除點讚記錄
                $stmt = $pdo->prepare("DELETE FROM message_likes WHERE message_id = ? AND user_email = ?");
                $stmt->execute([$message_id, $user_email]);
                
                // 減少點讚數（確保不會變成負數）
                $stmt = $pdo->prepare("UPDATE senior_messages SET like_count = GREATEST(0, like_count - 1) WHERE id = ?");
                $stmt->execute([$message_id]);
                
                // 獲取新的點讚數
                $stmt = $pdo->prepare("SELECT like_count FROM senior_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_count = $result['like_count'] ?? 0;
                
                echo json_encode(['success' => true, 'action' => 'unliked', 'new_count' => (int)$new_count]);
            } else {
                echo json_encode(['success' => true, 'action' => 'no_change', 'message' => '尚未點讚，無需取消']);
            }
        } else {
            // 添加愛心
            if (!$existing_like) {
                // 添加點讚記錄
                $stmt = $pdo->prepare("INSERT INTO message_likes (message_id, user_email, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$message_id, $user_email]);
                
                // 增加點讚數
                $stmt = $pdo->prepare("UPDATE senior_messages SET like_count = like_count + 1 WHERE id = ?");
                $stmt->execute([$message_id]);
                
                // 獲取新的點讚數
                $stmt = $pdo->prepare("SELECT like_count FROM senior_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_count = $result['like_count'] ?? 0;
                
                echo json_encode(['success' => true, 'action' => 'liked', 'new_count' => (int)$new_count]);
            } else {
                echo json_encode(['success' => true, 'action' => 'no_change', 'message' => '已經點讚過了']);
            }
        }
        exit;
    } catch(PDOException $e) {
        error_log("點讚功能錯誤: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => '操作失敗: ' . $e->getMessage()]);
        exit;
    }
}

// 處理瀏覽次數更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view') {
    $message_id = (int)$_POST['message_id'];
    try {
        $stmt = $pdo->prepare("UPDATE senior_messages SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在校生留言板</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            padding-top: 100px !important; /* 恢復間距避免被固定 header 遮擋 */
            background: var(--bg-color);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* 強制覆蓋 QA.css 的樣式 */
        main h2 {
            padding: 0 0 30px 0 !important; /* 移除頂部 padding */
            margin-top: 0 !important;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px 20px; /* 減少頂部 padding */
            min-height: calc(100vh - 120px);
        }
        
        .header {
            margin-bottom: 30px; /* 減少底部間距 */
            padding: 20px 20px; /* 減少內部 padding */
            background: linear-gradient(135deg, rgba(29, 155, 240, 0.1) 0%, rgba(29, 155, 240, 0.05) 100%);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
        }
        
        .header-text {
            flex: 1;
            text-align: left;
        }
        
        .post-button-container {
            margin-top: 0;
            position: relative;
            z-index: 10;
            flex-shrink: 0;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), #1a8cd8, var(--accent-color));
        }
        
        .header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: var(--secondary-text);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .permission-notice {
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(29, 155, 240, 0.3);
            border: none;
        }
        
        .post-button-container {
            margin-top: 25px;
            position: relative;
            z-index: 10;
        }
        
        .post-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .post-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
            color: white;
        }
        
        .post-btn:hover::before {
            left: 100%;
        }
        
        .post-btn:active {
            transform: translateY(0);
        }
        
        .theme-toggle {
            position: fixed;
            top: 120px; /* 恢復到 header 下方位置 */
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

        /* 移除不必要的間距設定 */
        .page-top-spacer {
            height: 0; /* 不再需要額外間距 */
        }
        
        .filter-tabs {
            display: flex;
            margin-bottom: 30px;
            background: var(--hover-bg);
            border-radius: 15px;
            padding: 8px;
            border: 1px solid var(--border-color);
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-tab {
            padding: 12px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: var(--secondary-text);
            font-size: 0.9rem;
            white-space: nowrap;
            border-radius: 10px;
            position: relative;
            flex: 1;
            text-align: center;
        }
        
        .filter-tab:hover {
            background: rgba(29, 155, 240, 0.1);
            color: var(--accent-color);
            transform: translateY(-1px);
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            box-shadow: 0 2px 8px rgba(29, 155, 240, 0.3);
        }
        
        .messages-feed {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            min-height: 200px;
        }
        
        .message-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .message-card[style*="display: none"] {
            display: none !important;
            margin-bottom: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        
        .message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--accent-color), #1a8cd8);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .message-card:hover {
            background: var(--hover-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--accent-color);
        }
        
        .message-card:hover::before {
            opacity: 1;
        }
        
        .message-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(29, 155, 240, 0.3);
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 700;
            color: var(--text-color);
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        
        .user-details {
            color: var(--secondary-text);
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        
        .message-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .message-type {
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .message-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .message-content {
            color: var(--secondary-text);
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.95rem;
            word-wrap: break-word;
        }
        
        .like-btn {
            background: transparent;
            color: var(--secondary-text);
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }
        
        .like-btn:hover {
            background: rgba(249, 24, 128, 0.1);
            color: #f91880;
            transform: scale(1.05);
        }
        
        .like-btn:hover .like-icon {
            transform: scale(1.2);
        }
        
        .like-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .like-btn.liked {
            color: #f91880;
        }
        
        .like-btn.liked:hover {
            background: rgba(249, 24, 128, 0.15);
        }
        
        .like-icon {
            transition: all 0.3s ease;
            font-size: 1.1rem;
            display: inline-block;
        }
        
        /* 空心愛心樣式（未點過） */
        .like-btn:not(.liked) .like-icon {
            filter: grayscale(0);
        }
        
        /* 實心愛心樣式（已點過） */
        .like-btn.liked .like-icon {
            filter: none;
            animation: heartBeat 0.5s ease;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1.1); }
        }
        
        .like-count {
            font-weight: 600;
        }
        
        .view-count {
            color: var(--secondary-text);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        .no-messages {
            text-align: center;
            padding: 80px 20px;
            color: var(--secondary-text);
            background: var(--hover-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            margin: 40px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .no-messages h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: 700;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent-color), #1a8cd8);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(29, 155, 240, 0.3);
            text-align: center;
            width: auto;
            max-width: 200px;
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, #1a8cd8, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
        }
        
        .back-btn-container {
            text-align: center;
            margin-top: 40px;
            padding: 20px 0;
            border-top: 1px solid var(--border-color);
        }
        
        /* 動畫效果 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .message-card:nth-child(1) { animation-delay: 0.1s; }
        .message-card:nth-child(2) { animation-delay: 0.2s; }
        .message-card:nth-child(3) { animation-delay: 0.3s; }
        .message-card:nth-child(4) { animation-delay: 0.4s; }
        .message-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* 滾動條樣式 */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        /* 響應式設計 */
        @media (max-width: 1200px) {
            .container {
                max-width: 1000px;
            }
        }
        
        @media (max-width: 1200px) {
            .messages-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 120px !important; /* 手機版恢復間距 */
            }
            
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 20px 15px;
                margin-bottom: 30px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 20px;
            }
            
            .header-text {
                text-align: center;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }
            
            .post-button-container {
                display: flex;
                justify-content: center;
            }
            
            .post-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
                border-radius: 12px;
            }
            
            .theme-toggle {
                top: 140px; /* 手機版恢復位置 */
                right: 15px;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .filter-tab {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .messages-feed {
                gap: 15px;
            }
            
            .message-card {
                padding: 15px;
            }
            
            .message-title {
                font-size: 1.1rem;
            }
            
            .message-content {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 130px !important; /* 更小螢幕恢復間距 */
            }
            
            .theme-toggle {
                top: 150px; /* 更小螢幕恢復位置 */
                right: 10px;
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .messages-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                max-width: 100%;
            }
            
            .filter-tabs {
                justify-content: flex-start;
                overflow-x: auto;
                padding: 15px;
                gap: 8px;
            }
            
            .filter-tab {
                padding: 8px 14px;
                font-size: 0.8rem;
                white-space: nowrap;
            }
            
            .message-card {
                padding: 15px;
            }
            
            .message-title {
                font-size: 1.1rem;
            }
            
            .author-avatar {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body class="custom-spacing">
    <?php include("share/header.php"); ?>
    <div class="page-top-spacer"></div>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="切換主題">
        <span id="theme-icon">🌙</span>
    </button>
    
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1>在校生留言板</h1>
                    <p>來自學長姐的經驗分享與建議</p>
                </div>
                
                <?php if ($can_post_message): ?>
                    <div class="post-button-container">
                        <a href="senior_message_form.php" class="post-btn">
                            <span>✍️</span>
                            <span>發布留言</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="permission-notice">
                        權限提示：只有 @stu.ukn.edu.tw 的學生帳號可以留言
                        <?php if (isset($permission_result['error'])): ?>
                            <br><small><?php echo htmlspecialchars($permission_result['error']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="filter-tabs">
            <div class="filter-tab active" data-type="all">全部留言</div>
            <div class="filter-tab" data-type="經驗分享">經驗分享</div>
            <div class="filter-tab" data-type="學習建議">學習建議</div>
            <div class="filter-tab" data-type="生活指南">生活指南</div>
            <div class="filter-tab" data-type="就業資訊">就業資訊</div>
            <div class="filter-tab" data-type="其他">其他</div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($messages)): ?>
            <div class="no-messages">
                <h3>📝 暫無留言</h3>
                <p>目前還沒有學長姐的留言，請稍後再來查看。</p>
            </div>
        <?php else: ?>
            <div class="messages-feed" id="messagesFeed">
                <?php foreach ($messages as $message): ?>
                    <div class="message-card" data-type="<?php echo htmlspecialchars($message['message_type']); ?>">
                        <div class="message-header">
                            <div class="user-avatar"><?php echo mb_substr(htmlspecialchars($message['author_name']), 0, 1); ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($message['author_name']); ?></div>
                                <div class="user-details"><?php echo htmlspecialchars($message['author_department'] ?? '未知科系'); ?> · <?php echo htmlspecialchars($message['author_grade'] ?? '未知年級'); ?></div>
                                <div class="message-meta">
                                    <span class="message-type"><?php echo htmlspecialchars($message['message_type']); ?></span>
                                    <span class="message-date"><?php echo date('M j', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-title"><?php echo htmlspecialchars($message['title']); ?></div>
                        
                        <div class="message-content" id="content-<?php echo $message['id']; ?>">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                        
                        <?php if (strlen($message['content']) > 200): ?>
                            <span class="read-more" onclick="toggleContent(<?php echo $message['id']; ?>)">展開更多</span>
                        <?php endif; ?>
                        
                        <div class="message-stats">
                            <button type="button" class="like-btn <?php echo $message['user_liked'] ? 'liked' : ''; ?>" 
                                    data-message-id="<?php echo $message['id']; ?>"
                                    onclick="toggleLike(<?php echo $message['id']; ?>)">
                                <span class="like-icon"><?php echo $message['user_liked'] ? '💖' : '🤍'; ?></span>
                                <span class="like-count"><?php echo $message['like_count'] ?? 0; ?></span>
                            </button>
                            <div class="view-count">
                                👁️ <?php echo $message['view_count'] ?? 0; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
        
        // 篩選功能
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // 更新活動狀態
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // 篩選留言
                const type = this.getAttribute('data-type');
                const cards = document.querySelectorAll('.message-card');
                let visibleCount = 0;
                
                cards.forEach(card => {
                    if (type === 'all' || card.getAttribute('data-type') === type) {
                        // 移除隱藏樣式，恢復正常顯示
                        card.style.display = '';
                        card.style.visibility = 'visible';
                        card.style.height = '';
                        card.style.padding = '';
                        card.style.margin = '';
                        visibleCount++;
                    } else {
                        // 完全隱藏卡片
                        card.style.display = 'none';
                        card.style.visibility = 'hidden';
                    }
                });
                
                // 如果沒有可見的留言，顯示提示訊息
                const feed = document.getElementById('messagesFeed');
                let noMessagesMsg = document.getElementById('noMessagesMsg');
                
                if (visibleCount === 0) {
                    if (!noMessagesMsg) {
                        noMessagesMsg = document.createElement('div');
                        noMessagesMsg.id = 'noMessagesMsg';
                        noMessagesMsg.className = 'no-messages';
                        noMessagesMsg.innerHTML = '<h3>📝 暫無留言</h3><p>此分類目前還沒有留言。</p>';
                        feed.appendChild(noMessagesMsg);
                    }
                    noMessagesMsg.style.display = 'block';
                } else {
                    if (noMessagesMsg) {
                        noMessagesMsg.style.display = 'none';
                    }
                }
            });
        });
        
        // 展開/收縮內容
        function toggleContent(messageId) {
            const content = document.getElementById('content-' + messageId);
            const readMore = content.nextElementSibling;
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                readMore.textContent = '展開更多';
            } else {
                content.classList.add('expanded');
                readMore.textContent = '收起';
            }
        }
        
        // 愛心按鈕功能 - 切換模式
        function toggleLike(messageId, event) {
            // 使用更可靠的选择器
            let likeBtn = null;
            if (event && event.target) {
                likeBtn = event.target.closest('.like-btn');
            }
            if (!likeBtn) {
                likeBtn = document.querySelector(`.like-btn[data-message-id="${messageId}"]`);
            }
            
            if (!likeBtn) {
                console.error('找不到愛心按鈕，messageId:', messageId);
                return;
            }
            
            const likeIcon = likeBtn.querySelector('.like-icon');
            const likeCount = likeBtn.querySelector('.like-count');
            
            if (!likeIcon || !likeCount) {
                console.error('找不到愛心圖標或計數器');
                return;
            }
            
            // 檢查當前狀態 - 實心愛心表示已點過
            const isLiked = likeIcon.textContent === '💖' || likeBtn.classList.contains('liked');
            const currentCount = parseInt(likeCount.textContent) || 0;
            
            // 保存原始狀態以便恢復
            const originalCount = currentCount;
            const originalIcon = likeIcon.textContent;
            const originalLiked = likeBtn.classList.contains('liked');
            
            // 立即更新視覺效果（樂觀更新）
            if (isLiked) {
                // 取消愛心 - 改為空心愛心
                likeCount.textContent = Math.max(0, currentCount - 1);
                likeIcon.textContent = '🤍';
                likeBtn.classList.remove('liked');
            } else {
                // 添加愛心 - 改為實心粉紅愛心
                likeCount.textContent = currentCount + 1;
                likeIcon.textContent = '💖';
                likeBtn.classList.add('liked');
            }
            
            // 暫時禁用按鈕防止重複點擊
            likeBtn.disabled = true;
            likeBtn.style.opacity = '0.7';
            likeBtn.style.cursor = 'wait';
            
            // 發送 AJAX 請求
            fetch('senior_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_like&message_id=${messageId}&is_liked=${isLiked ? '1' : '0'}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 如果後端返回了新的計數，使用它
                    if (data.new_count !== undefined) {
                        likeCount.textContent = data.new_count;
                    }
                    // 確保圖標狀態正確
                    if (data.action === 'liked') {
                        likeIcon.textContent = '💖';
                        likeBtn.classList.add('liked');
                    } else if (data.action === 'unliked') {
                        likeIcon.textContent = '🤍';
                        likeBtn.classList.remove('liked');
                    }
                    console.log(data.action === 'liked' ? '點讚成功' : data.action === 'unliked' ? '取消愛心成功' : data.message || '操作成功');
                } else {
                    // 如果失敗，恢復原狀
                    likeCount.textContent = originalCount;
                    likeIcon.textContent = originalIcon;
                    if (originalLiked) {
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    alert('操作失敗: ' + (data.error || '未知錯誤'));
                }
            })
            .catch(error => {
                console.error('操作失敗:', error);
                // 如果失敗，恢復原狀
                likeCount.textContent = originalCount;
                likeIcon.textContent = originalIcon;
                if (originalLiked) {
                    likeBtn.classList.add('liked');
                    // 確保圖標是實心愛心
                    if (likeIcon.textContent !== '💖') {
                        likeIcon.textContent = '💖';
                    }
                } else {
                    likeBtn.classList.remove('liked');
                    // 確保圖標是空心愛心
                    if (likeIcon.textContent !== '🤍') {
                        likeIcon.textContent = '🤍';
                    }
                }
                alert('操作失敗，請檢查網路連線或稍後再試');
            })
            .finally(() => {
                // 重新啟用按鈕
                likeBtn.disabled = false;
                likeBtn.style.opacity = '1';
                likeBtn.style.cursor = 'pointer';
            });
        }
        
        // 增加瀏覽次數
        document.querySelectorAll('.message-card').forEach(card => {
            const likeBtn = card.querySelector('button[onclick*="toggleLike"]');
            if (!likeBtn) return; // 如果找不到按鈕就跳過
            
            const messageId = likeBtn.onclick.toString().match(/\d+/);
            if (!messageId) return; // 如果找不到 messageId 就跳過
            
            // 使用 fetch 增加瀏覽次數（不重新載入頁面）
            fetch('senior_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=view&message_id=' + messageId[0]
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新顯示的瀏覽次數
                    const viewCount = card.querySelector('.view-count');
                    if (viewCount) {
                        const currentCount = parseInt(viewCount.textContent.match(/\d+/)[0]);
                        viewCount.innerHTML = `👁️ ${currentCount + 1}`;
                    }
                }
            }).catch(error => console.log('瀏覽次數更新失敗:', error));
        });
    </script>
    
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
