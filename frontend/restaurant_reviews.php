<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'senior_message_auth.php';

// 檢查登入狀態（允許未登入用戶查看，但只有登入用戶才能發布評價）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 獲取參數
$message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;
$restaurant_name = isset($_GET['restaurant']) ? urldecode($_GET['restaurant']) : '';

// 資料庫連接
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

// 獲取留言信息
$message = null;
if ($message_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM senior_messages WHERE id = ? AND message_type = '推薦餐廳'");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error_message = "載入留言失敗: " . $e->getMessage();
    }
}

if (!$message) {
    header("Location: senior_messages.php");
    exit;
}

// 獲取用戶信息（只有登入用戶才獲取）
$user_email = $isLoggedIn ? ($_SESSION['username'] ?? '') : '';
$user_name = '';
if ($isLoggedIn && !empty($user_email)) {
    try {
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
        }
    } catch(PDOException $e) {
        error_log("獲取用戶姓名錯誤: " . $e->getMessage());
    }
}

// 處理評價提交
$success_message = '';
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    // 檢查是否登入
    if (!$isLoggedIn) {
        $form_error = '請先登入才能發布評價';
    } else {
        $rating = (int)$_POST['rating'];
        $review_content = trim($_POST['review_content'] ?? '');
        $delivery_rating = !empty($_POST['delivery_rating']) ? (int)$_POST['delivery_rating'] : null;
        
        if (empty($review_content)) {
            $form_error = '請填寫評價內容';
        } elseif ($rating < 1 || $rating > 5) {
            $form_error = '請選擇有效的評分';
        } else {
            try {
                // 檢查 restaurant_reviews 表是否存在
                $stmt = $pdo->query("SHOW TABLES LIKE 'restaurant_reviews'");
                if ($stmt->rowCount() == 0) {
                    // 創建表
                    $createTableSQL = file_get_contents('scripts/database/create_restaurant_reviews_table.sql');
                    $pdo->exec($createTableSQL);
                }
                
                $stmt = $pdo->prepare("INSERT INTO restaurant_reviews (restaurant_name, restaurant_address, restaurant_lat, restaurant_lng, restaurant_place_id, rating, review_content, author_name, author_email, delivery_rating, message_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $message['restaurant_name'],
                    $message['restaurant_address'],
                    $message['restaurant_lat'],
                    $message['restaurant_lng'],
                    $message['restaurant_place_id'],
                    $rating,
                    $review_content,
                    $user_name,
                    $user_email,
                    $delivery_rating,
                    $message_id
                ]);
                
                $success_message = '評價發布成功！';
            } catch(PDOException $e) {
                $form_error = '發布評價失敗: ' . $e->getMessage();
            }
        }
    }
}

// 處理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    // 檢查是否登入
    if (!$isLoggedIn) {
        $form_error = '請先登入才能留言';
    } else {
        $comment_content = trim($_POST['comment_content'] ?? '');
        $review_id = (int)$_POST['review_id'];
        
        if (empty($comment_content)) {
            $form_error = '請填寫留言內容';
        } else {
            try {
                // 檢查 restaurant_comments 表是否存在
                $stmt = $pdo->query("SHOW TABLES LIKE 'restaurant_comments'");
                if ($stmt->rowCount() == 0) {
                    // 創建表
                    $createTableSQL = file_get_contents('scripts/database/create_restaurant_reviews_table.sql');
                    $pdo->exec($createTableSQL);
                }
                
                $stmt = $pdo->prepare("INSERT INTO restaurant_comments (review_id, comment_content, author_name, author_email) VALUES (?, ?, ?, ?)");
                $stmt->execute([$review_id, $comment_content, $user_name, $user_email]);
                
                $success_message = '留言發布成功！';
            } catch(PDOException $e) {
                $form_error = '發布留言失敗: ' . $e->getMessage();
            }
        }
    }
}

// 獲取所有評價
$reviews = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM restaurant_reviews WHERE message_id = ? AND is_published = 1 ORDER BY created_at DESC");
    $stmt->execute([$message_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 為每個評價獲取留言
    foreach ($reviews as &$review) {
        $stmt = $pdo->prepare("SELECT * FROM restaurant_comments WHERE review_id = ? AND is_published = 1 ORDER BY created_at ASC");
        $stmt->execute([$review['id']]);
        $review['comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    // 如果表不存在，忽略錯誤
    $reviews = [];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>餐廳評價與留言 - <?php echo htmlspecialchars($message['restaurant_name']); ?></title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #fff;
            --text-color: #1a1a1a;
            --secondary-text: #6b7280;
            --border-color: #e5e7eb;
            --hover-bg: #f9fafb;
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --accent-color: #f59e0b;
            --accent-hover: #d97706;
            --success-color: #10b981;
            --error-color: #ef4444;
        }
        
        body {
            padding-top: 100px !important;
            background: linear-gradient(to bottom, #f9fafb 0%, #ffffff 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 40px;
        }
        
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 32px;
        }
        
        @media (max-width: 1200px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 24px;
            transition: all 0.2s ease;
            border: 2px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .back-btn:hover {
            background: var(--hover-bg);
            border-color: var(--primary-color);
            transform: translateX(-2px);
        }
        
        .restaurant-header {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
        }
        
        .restaurant-header h1 {
            margin: 0 0 16px 0;
            color: var(--text-color);
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .restaurant-header p {
            margin: 12px 0;
            color: var(--secondary-text);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .restaurant-header .map-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
        }
        
        .restaurant-header .map-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -1px rgba(245, 158, 11, 0.4);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .review-form {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .review-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
        }
        
        .review-form h2 {
            margin: 0 0 24px 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 15px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: var(--hover-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-color);
            font-size: 15px;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .review-rating {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }
        
        .star {
            font-size: 32px;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }
        
        .star.active {
            color: var(--accent-color);
        }
        
        .star:hover {
            color: var(--accent-color);
            transform: scale(1.1);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--accent-color), var(--accent-hover));
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
            width: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -1px rgba(245, 158, 11, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .reviews-section {
            margin-top: 0;
        }
        
        .reviews-section h2 {
            margin: 0 0 24px 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .empty-reviews {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-text);
            background: white;
            border-radius: 20px;
            border: 2px dashed var(--border-color);
        }
        
        .empty-reviews p {
            margin: 0;
            font-size: 16px;
        }
        
        .review-card {
            padding: 24px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .review-author {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .review-author strong {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 600;
        }
        
        .review-time {
            color: var(--secondary-text);
            font-size: 13px;
        }
        
        .review-rating-display {
            color: var(--accent-color);
            font-size: 20px;
        }
        
        .review-content {
            color: var(--text-color);
            line-height: 1.7;
            margin-bottom: 16px;
            font-size: 15px;
        }
        
        .delivery-rating-display {
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #fef3c7;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #92400e;
            font-size: 14px;
            font-weight: 500;
        }
        
        .comment-form {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .comment-input-group {
            display: flex;
            gap: 8px;
        }
        
        .comment-input-group input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
        }
        
        .comment-input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .comment-btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .comment-btn:hover {
            background: var(--primary-hover);
        }
        
        .comments-list {
            margin-top: 16px;
            padding-left: 20px;
            border-left: 3px solid var(--border-color);
        }
        
        .comment-item {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .comment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .comment-author {
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }
        
        .comment-time {
            color: var(--secondary-text);
            font-size: 12px;
            margin-left: 8px;
        }
        
        .comment-content {
            margin-top: 4px;
            color: var(--text-color);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .login-prompt {
            text-align: center;
            padding: 60px 40px;
        }
        
        .login-prompt p {
            color: var(--secondary-text);
            margin-bottom: 24px;
            font-size: 16px;
        }
        
        .login-btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -1px rgba(59, 130, 246, 0.4);
        }
        
        .login-info-box {
            margin-top: 16px;
            padding: 12px 16px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            color: #856404;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <a href="senior_messages.php" class="back-btn">← 返回留言板</a>
        
        <div class="restaurant-header">
            <h1>🍽️ <?php echo htmlspecialchars($message['restaurant_name']); ?></h1>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($message['restaurant_address']); ?></p>
            <?php if (!empty($message['restaurant_lat']) && !empty($message['restaurant_lng'])): ?>
                <a href="campus_map.php?restaurant=<?php echo urlencode($message['restaurant_name']); ?>&lat=<?php echo $message['restaurant_lat']; ?>&lng=<?php echo $message['restaurant_lng']; ?>&address=<?php echo urlencode($message['restaurant_address']); ?>" 
                   target="_blank" 
                   class="map-btn">
                    <i class="fas fa-map-marker-alt"></i> 在地圖上查看
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($form_error): ?>
            <div class="alert alert-error">
                <span>❌</span>
                <span><?php echo htmlspecialchars($form_error); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="main-layout">
            <!-- 左側：發布評價表單 -->
            <div>
                <?php if ($isLoggedIn): ?>
                    <div class="review-form">
                        <h2>發布評價</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_review">
                            
                            <div class="form-group">
                                <label>評分 <span style="color: var(--error-color);">*</span></label>
                                <div class="review-rating" id="rating-stars">
                                    <span class="star" data-rating="1">★</span>
                                    <span class="star" data-rating="2">★</span>
                                    <span class="star" data-rating="3">★</span>
                                    <span class="star" data-rating="4">★</span>
                                    <span class="star" data-rating="5">★</span>
                                </div>
                                <input type="hidden" name="rating" id="rating-value" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="delivery_rating">外送評分（選填）</label>
                                <select name="delivery_rating" id="delivery_rating">
                                    <option value="">無外送</option>
                                    <option value="5">5 星</option>
                                    <option value="4">4 星</option>
                                    <option value="3">3 星</option>
                                    <option value="2">2 星</option>
                                    <option value="1">1 星</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="review_content">評價內容 <span style="color: var(--error-color);">*</span></label>
                                <textarea name="review_content" id="review_content" rows="6" required placeholder="請分享您對這間餐廳的評價..."></textarea>
                            </div>
                            
                            <button type="submit" class="submit-btn">發布評價</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="review-form login-prompt">
                        <p>請先登入才能發布評價</p>
                        <a href="index.php" class="login-btn">前往登入</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 右側：評價列表 -->
            <div class="reviews-section">
                <h2>所有評價 (<?php echo count($reviews); ?>)</h2>
                
                <?php if (empty($reviews)): ?>
                    <div class="empty-reviews">
                        <p>目前還沒有評價<?php echo $isLoggedIn ? '，成為第一個評價的人吧！' : '，請先登入後發布評價。'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-author">
                                    <strong><?php echo htmlspecialchars($review['author_name']); ?></strong>
                                    <span class="review-time"><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></span>
                                </div>
                                <div class="review-rating-display">
                                    <?php echo str_repeat('★', $review['rating']); ?>
                                </div>
                            </div>
                            
                            <?php if ($review['delivery_rating']): ?>
                                <div class="delivery-rating-display">
                                    <i class="fas fa-motorcycle"></i>
                                    <span>外送評分: <?php echo str_repeat('★', $review['delivery_rating']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-content">
                                <?php echo nl2br(htmlspecialchars($review['review_content'])); ?>
                            </div>
                            
                            <!-- 留言表單 -->
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" class="comment-form">
                                    <input type="hidden" name="action" value="submit_comment">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <div class="comment-input-group">
                                        <input type="text" name="comment_content" placeholder="留言..." required>
                                        <button type="submit" class="comment-btn">留言</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="login-info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <span>請先登入才能留言</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 顯示留言 -->
                            <?php if (!empty($review['comments'])): ?>
                                <div class="comments-list">
                                    <?php foreach ($review['comments'] as $comment): ?>
                                        <div class="comment-item">
                                            <div>
                                                <span class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></span>
                                                <span class="comment-time"><?php echo date('m-d H:i', strtotime($comment['created_at'])); ?></span>
                                            </div>
                                            <div class="comment-content">
                                                <?php echo nl2br(htmlspecialchars($comment['comment_content'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // 評分星星功能
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('rating-value');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingValue.value = rating;
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#f39c12';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
    
    <?php include("share/footer.php"); ?>
</body>
</html>

