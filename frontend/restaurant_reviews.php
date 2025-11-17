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

// 獲取用戶信息
$user_email = $_SESSION['username'];
$user_name = '';
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

// 處理評價提交
$success_message = '';
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
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

// 處理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
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
        body {
            padding-top: 100px !important;
            background: var(--bg-color, #000);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .restaurant-header {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(255, 107, 53, 0.05));
            border: 1px solid rgba(255, 107, 53, 0.3);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }
        
        .restaurant-header h1 {
            margin: 0 0 12px 0;
            color: var(--text-color, #fff);
            font-size: 24px;
        }
        
        .restaurant-header p {
            margin: 8px 0;
            color: var(--secondary-text, #71767b);
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #1d9bf0, #1a8cd8);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 155, 240, 0.4);
        }
        
        .review-form, .review-card {
            background: var(--card-bg, transparent);
            border: 1px solid var(--border-color, #333);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color, #fff);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: var(--hover-bg, #16181c);
            border: 1px solid var(--border-color, #333);
            border-radius: 8px;
            color: var(--text-color, #fff);
            font-size: 14px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }
        
        .review-rating {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
        }
        
        .star.active {
            color: #f39c12;
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
                   style="display: inline-block; margin-top: 12px; padding: 8px 16px; background: #ff6b35; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-map-marker-alt"></i> 在地圖上查看
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($success_message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($form_error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ❌ <?php echo htmlspecialchars($form_error); ?>
            </div>
        <?php endif; ?>
        
        <!-- 發布評價表單 -->
        <div class="review-form">
            <h2>發布評價</h2>
            <form method="POST">
                <input type="hidden" name="action" value="submit_review">
                
                <div class="form-group">
                    <label>評分 <span style="color: #e74c3c;">*</span></label>
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
                    <label for="review_content">評價內容 <span style="color: #e74c3c;">*</span></label>
                    <textarea name="review_content" id="review_content" rows="5" required placeholder="請分享您對這間餐廳的評價..."></textarea>
                </div>
                
                <button type="submit" class="submit-btn">發布評價</button>
            </form>
        </div>
        
        <!-- 評價列表 -->
        <h2 style="margin-top: 40px; margin-bottom: 20px;">所有評價 (<?php echo count($reviews); ?>)</h2>
        
        <?php if (empty($reviews)): ?>
            <div style="text-align: center; padding: 40px; color: var(--secondary-text, #71767b);">
                <p>目前還沒有評價，成為第一個評價的人吧！</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <strong style="color: var(--text-color, #fff);"><?php echo htmlspecialchars($review['author_name']); ?></strong>
                            <span style="color: var(--secondary-text, #71767b); font-size: 13px; margin-left: 8px;">
                                <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?>
                            </span>
                        </div>
                        <div style="color: #f39c12; font-size: 18px;">
                            <?php echo str_repeat('★', $review['rating']); ?>
                        </div>
                    </div>
                    
                    <?php if ($review['delivery_rating']): ?>
                        <div style="margin-bottom: 8px; color: #ff6b35;">
                            <i class="fas fa-motorcycle"></i> 外送評分: <?php echo str_repeat('★', $review['delivery_rating']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <p style="color: var(--text-color, #fff); line-height: 1.6; margin-bottom: 15px;">
                        <?php echo nl2br(htmlspecialchars($review['review_content'])); ?>
                    </p>
                    
                    <!-- 留言表單 -->
                    <form method="POST" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color, #333);">
                        <input type="hidden" name="action" value="submit_comment">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="comment_content" placeholder="留言..." required style="flex: 1;">
                            <button type="submit" style="padding: 8px 16px; background: #1d9bf0; color: white; border: none; border-radius: 6px; cursor: pointer;">留言</button>
                        </div>
                    </form>
                    
                    <!-- 顯示留言 -->
                    <?php if (!empty($review['comments'])): ?>
                        <div style="margin-top: 15px; padding-left: 20px; border-left: 2px solid var(--border-color, #333);">
                            <?php foreach ($review['comments'] as $comment): ?>
                                <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #333);">
                                    <strong style="color: var(--text-color, #fff); font-size: 14px;"><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                    <span style="color: var(--secondary-text, #71767b); font-size: 12px; margin-left: 8px;">
                                        <?php echo date('m-d H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                    <p style="margin: 4px 0 0 0; color: var(--text-color, #fff); font-size: 14px;">
                                        <?php echo nl2br(htmlspecialchars($comment['comment_content'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

