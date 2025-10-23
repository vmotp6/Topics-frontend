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
$host = '100.79.58.120';
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
} catch(PDOException $e) {
    $error_message = "載入留言失敗: " . $e->getMessage();
}

// 處理點讚功能
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $message_id = (int)$_POST['message_id'];
    try {
        $stmt = $pdo->prepare("UPDATE senior_messages SET like_count = like_count + 1 WHERE id = ?");
        $stmt->execute([$message_id]);
        header("Location: senior_messages.php");
        exit;
    } catch(PDOException $e) {
        $error_message = "點讚失敗: " . $e->getMessage();
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
    <title>學長姐留言板</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            padding-top: 80px; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Microsoft JhengHei', 'PingFang TC', 'Hiragino Sans GB', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
            background: rgba(255, 255, 255, 0.95);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 3rem;
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: #5a6c7d;
            font-size: 1.3rem;
            font-weight: 500;
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .filter-tab {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid transparent;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            color: #5a6c7d;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .filter-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: left 0.4s ease;
            z-index: -1;
        }
        
        .filter-tab:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .filter-tab:hover::before {
            left: 0;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .messages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .message-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .message-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .message-type {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }
        
        .message-date {
            color: #95a5a6;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(149, 165, 166, 0.1);
            padding: 4px 10px;
            border-radius: 12px;
        }
        
        .message-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.3;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .message-content {
            color: #5a6c7d;
            line-height: 1.5;
            margin-bottom: 15px;
            max-height: 80px;
            overflow: hidden;
            position: relative;
            font-size: 0.9rem;
            background: rgba(0,0,0,0.02);
            padding: 10px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
        
        .message-content.expanded {
            max-height: none;
        }
        
        .read-more {
            color: #667eea;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.85rem;
            background: rgba(102, 126, 234, 0.1);
            padding: 4px 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 8px;
        }
        
        .read-more:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-1px);
        }
        
        .message-author {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .author-info {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .author-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-right: 10px;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .author-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .author-details h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 0.9rem;
            font-weight: 700;
        }
        
        .author-details p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .author-contact {
            color: #667eea;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(102, 126, 234, 0.1);
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .message-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
            margin-top: 10px;
        }
        
        .like-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        .like-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.5);
        }
        
        .like-btn:active {
            transform: translateY(-1px) scale(1.02);
        }
        
        .view-count {
            color: #667eea;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
            background: rgba(102, 126, 234, 0.1);
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .no-messages h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
                padding-top: 70px;
            }
            
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 30px 20px;
                margin-bottom: 30px;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .header p {
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
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .message-card {
                padding: 12px;
            }
            
            .message-stats {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .like-btn {
                width: 100%;
                justify-content: center;
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <div class="header">
            <h1>🎓 學長姐留言板</h1>
            <p>來自學長姐的經驗分享與建議，幫助你更好地適應大學生活</p>
            <?php if ($can_post_message): ?>
                <div style="margin-top: 20px;">
                    <a href="senior_message_form.php" class="back-btn" style="background: #28a745; color: white; text-decoration: none; padding: 12px 24px; border-radius: 25px; font-weight: 600; transition: all 0.3s ease;">
                        ✍️ 發布留言
                    </a>
                </div>
            <?php else: ?>
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; color: #856404;">
                    <strong>⚠️ 權限提示：</strong><?php echo htmlspecialchars($permission_result['error']); ?>
                </div>
            <?php endif; ?>
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
                <a href="student.php" class="back-btn">返回學生頁面</a>
            </div>
        <?php else: ?>
            <div class="messages-grid" id="messagesGrid">
                <?php foreach ($messages as $message): ?>
                    <div class="message-card" data-type="<?php echo htmlspecialchars($message['message_type']); ?>">
                        <div class="message-header">
                            <span class="message-type"><?php echo htmlspecialchars($message['message_type']); ?></span>
                            <span class="message-date"><?php echo date('Y-m-d', strtotime($message['created_at'])); ?></span>
                        </div>
                        
                        <h3 class="message-title"><?php echo htmlspecialchars($message['title']); ?></h3>
                        
                        <div class="message-content" id="content-<?php echo $message['id']; ?>">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                        
                        <?php if (strlen($message['content']) > 200): ?>
                            <span class="read-more" onclick="toggleContent(<?php echo $message['id']; ?>)">展開更多</span>
                        <?php endif; ?>
                        
                        <div class="message-author">
                            <div class="author-info">
                                <div class="author-avatar">
                                    <?php echo mb_substr($message['author_name'], 0, 1, 'UTF-8'); ?>
                                </div>
                                <div class="author-details">
                                    <h4><?php echo htmlspecialchars($message['author_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($message['author_department'] . ' ' . $message['author_grade']); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($message['author_contact'])): ?>
                                <div class="author-contact">
                                    📞 <?php echo htmlspecialchars($message['author_contact']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="message-stats">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="like">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <button type="submit" class="like-btn">
                                    ❤️ <?php echo $message['like_count']; ?>
                                </button>
                            </form>
                            <div class="view-count">
                                👁️ <?php echo $message['view_count']; ?> 次瀏覽
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="student.php" class="back-btn">返回學生頁面</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // 篩選功能
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // 更新活動狀態
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // 篩選留言
                const type = this.getAttribute('data-type');
                const cards = document.querySelectorAll('.message-card');
                
                cards.forEach(card => {
                    if (type === 'all' || card.getAttribute('data-type') === type) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
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
        
        // 增加瀏覽次數
        document.querySelectorAll('.message-card').forEach(card => {
            const messageId = card.querySelector('input[name="message_id"]').value;
            
            // 使用 fetch 增加瀏覽次數（不重新載入頁面）
            fetch('senior_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=view&message_id=' + messageId
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新顯示的瀏覽次數
                    const viewCount = card.querySelector('.view-count');
                    const currentCount = parseInt(viewCount.textContent.match(/\d+/)[0]);
                    viewCount.innerHTML = `👁️ ${currentCount + 1} 次瀏覽`;
                }
            }).catch(error => console.log('瀏覽次數更新失敗:', error));
        });
    </script>
    
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
