<?php
require_once 'session_config.php';
require_once 'config.php';

$conn = getDatabaseConnection();

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']);
$userId = $isLoggedIn && isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// 處理按讚 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    header('Content-Type: application/json');
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'error' => '請先登入']);
        exit;
    }
    
    $videoId = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
    if ($videoId <= 0) {
        echo json_encode(['success' => false, 'error' => '無效的影片ID']);
        exit;
    }
    
    // 檢查 video_likes 表是否存在，不存在則創建
    $conn->query("CREATE TABLE IF NOT EXISTS video_likes (
        video_id INT NOT NULL,
        user_id INT NOT NULL,
        liked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (video_id, user_id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 檢查並添加欄位（如果不存在）
    $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'like_count'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE videos ADD COLUMN like_count INT DEFAULT 0");
    }
    
    // 檢查是否已按讚
    $stmt = $conn->prepare("SELECT video_id FROM video_likes WHERE video_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $videoId, $userId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // 取消按讚
        $stmt = $conn->prepare("DELETE FROM video_likes WHERE video_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $videoId, $userId);
        $stmt->execute();
        $stmt->close();
        
        // 減少按讚數
        $stmt = $conn->prepare("UPDATE videos SET like_count = GREATEST(0, COALESCE(like_count, 0) - 1) WHERE id = ?");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $stmt->close();
        
        $action = 'unliked';
    } else {
        // 添加按讚
        $stmt = $conn->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $videoId, $userId);
        $stmt->execute();
        $stmt->close();
        
        // 增加按讚數
        $stmt = $conn->prepare("UPDATE videos SET like_count = COALESCE(like_count, 0) + 1 WHERE id = ?");
        $stmt->bind_param('i', $videoId);
        $stmt->execute();
        $stmt->close();
        
        $action = 'liked';
    }
    
    // 取得新的按讚數
    $stmt = $conn->prepare("SELECT COALESCE(like_count, 0) AS like_count FROM videos WHERE id = ?");
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $newCount = intval($row['like_count'] ?? 0);
    $stmt->close();
    
    echo json_encode(['success' => true, 'action' => $action, 'like_count' => $newCount]);
    exit;
}

// 處理觀看次數更新 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view') {
    header('Content-Type: application/json');
    $videoId = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
    if ($videoId <= 0) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    // 檢查並添加 view_count 欄位（如果不存在）
    $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'view_count'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE videos ADD COLUMN view_count INT DEFAULT 0");
    }
    
    $stmt = $conn->prepare("UPDATE videos SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?");
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $stmt->close();
    
    // 取得更新後的觀看次數
    $stmt = $conn->prepare("SELECT COALESCE(view_count, 0) AS view_count FROM videos WHERE id = ?");
    $stmt->bind_param('i', $videoId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $newCount = intval($row['view_count'] ?? 0);
    $stmt->close();
    
    echo json_encode(['success' => true, 'view_count' => $newCount]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$video = null;
$isLiked = false;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND published = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $video = $res->fetch_assoc();
        $stmt->close();
    }
    
    // 確保欄位存在並取得預設值
    if ($video) {
        // 檢查並添加 view_count 欄位（如果不存在）
        $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'view_count'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE videos ADD COLUMN view_count INT DEFAULT 0");
        }
        
        // 檢查並添加 like_count 欄位（如果不存在）
        $result = $conn->query("SHOW COLUMNS FROM videos LIKE 'like_count'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE videos ADD COLUMN like_count INT DEFAULT 0");
        }
        
        // 重新查詢以取得包含新欄位的資料
        $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND published = 1 LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $video = $res->fetch_assoc();
        $stmt->close();
        
        // 如果欄位不存在，設置預設值
        if (!isset($video['view_count'])) {
            $video['view_count'] = 0;
        }
        if (!isset($video['like_count'])) {
            $video['like_count'] = 0;
        }
        
        // 檢查用戶是否已按讚
        if ($isLoggedIn && $userId > 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS video_likes (
                video_id INT NOT NULL,
                user_id INT NOT NULL,
                liked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (video_id, user_id),
                KEY user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $conn->prepare("SELECT video_id FROM video_likes WHERE video_id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $isLiked = $res->num_rows > 0;
            $stmt->close();
        }
    }
}
// 取得相關影片
$related = [];
$relatedPerPage = 5;
$relatedPage = max(1, intval($_GET['related_page'] ?? 1));
$relatedOffset = ($relatedPage - 1) * $relatedPerPage;
$totalRelated = 0;

if ($video && !empty($video['category_id'])) {
    $category_id = $video['category_id'];
    
    // 計算總數
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM videos
        WHERE category_id = ? AND id != ? AND published = 1
    ");
    $stmt->bind_param('ii', $category_id, $video['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $totalRelated = intval($row['c']);
    $stmt->close();

    // 取得分頁資料
    $stmt = $conn->prepare("
        SELECT v.id, v.title, v.thumbnail_url, v.duration, c.name AS category_name
        FROM videos v
        LEFT JOIN video_categories c ON v.category_id = c.id
        WHERE v.category_id = ? AND v.id != ? AND v.published = 1
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('iiii', $category_id, $video['id'], $relatedPerPage, $relatedOffset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $related[] = $r;
    }
    $stmt->close();
}

$totalRelatedPages = max(1, ceil($totalRelated / $relatedPerPage));

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $video ? htmlspecialchars($video['title'], ENT_QUOTES) : '影片播放'; ?></title>
    <style>
/* 全站背景統一 */
body {
    background: #ffffff !important;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial,
                 'Noto Sans TC', 'Microsoft JhengHei', sans-serif;
}

/* 播放頁容器，提供與 header 保持間距 */
.video-page {
    background: #ffffff !important;
    padding-top: 130px; /* 若仍碰到 header，可再調整 */
    padding-bottom: 100px;
}

/* 主要白色卡片內容 - 將在下方重新定義為全寬 */

/* 返回按鈕 */
.video-page .back {
    display: inline-block;
    margin-bottom: 18px;
    color: #2196f3;
    text-decoration: none;
    font-size: 15px;
    float: right;  
}

/* 標題 */
.video-page .video-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 14px;
    color: #222;
}

/* 固定 16:9 容器 */
.video-page .video-box {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
}

/* 影片填滿 */
.video-page video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 資訊區 */
.video-page .meta {
    color: #666;
    margin-bottom: 12px;
    font-size: 14px;
    width:100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

/* 影片統計資訊 */
.video-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.video-stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

/* 按讚按鈕 - 白色邊框圓角按鈕樣式 */
.like-btn {
    background: transparent;
    border: 1px solid #ddd;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    color: #333;
    font-size: 14px;
    padding: 6px 14px;
    border-radius: 18px;
    transition: all 0.2s;
    font-weight: 500;
}

.like-btn:hover {
    background: #f5f5f5;
    border-color: #bbb;
}

.like-btn.liked {
    color: #333;
    border-color: #4CAF50;
}

.like-btn .icon {
    font-size: 16px;
    display: inline-block;
}

/* 分享按鈕 - 白色邊框圓角按鈕樣式 */
.share-btn {
    background: transparent;
    color: #333;
    border: 1px solid #ddd;
    cursor: pointer;
    padding: 6px 14px;
    border-radius: 18px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-decoration: none;
    font-weight: 500;
}

.share-btn:hover {
    background: #f5f5f5;
    border-color: #bbb;
    color: #333;
}

.share-btn:disabled,
.share-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    border-color: #ddd;
    background: transparent;
}

.share-btn .icon {
    font-size: 16px;
    display: inline-block;
}

/* 分隔線 */
.video-stats-separator {
    width: 1px;
    height: 20px;
    background: #ddd;
    margin: 0 4px;
}

/* 影片描述標題 */
.video-page h4 {
    margin-top: 20px;
    margin-bottom: 24px;
    font-size: 30px;
    font-weight: 600;
    color: #222;
    text-align: left;   /* ← 保證靠左 */
    width:100%;
}

/* 描述內容本來就靠左，再強化一次 */
.video-page .desc {
    text-align: left;   /* ← 描述靠左 */
    margin-top: 5px;
}
/* 佈局改為 flex：左側主內容, 右側相關影片（desktop）
   使用 sticky 使側欄在滾動時保持可見但不脫離流 */
.video-layout {
    display: flex;
    gap: 40px;
    align-items: flex-start;
    width: 100%;
    justify-content: space-between; /* 讓主內容和相關影片分開 */
}

/* container 占滿螢幕寬度 */
.video-page .container {
    width: 100%;
    max-width: 100%;
    margin: 0;
    background: #ffffff;
    padding: 28px 40px 28px 100px; /* 左側留出空間，右側也有間距 */
    border-radius: 0;

}

/* 主影片區 - 靠左側 */
.video-main {
    flex: 1; /* 撐滿剩餘空間 */
    min-width: 0; /* 避免內容溢出 */
    max-width: 1100px; /* 限制最大寬度，讓內容不會太寬 */
}

/* 相關影片：使用 sticky 讓它隨頁面滾動，但在滾動時保持可見，靠右側 */
.video-related {      
    width: 420px;         /* 固定寬度 */
    max-height: calc(100vh - 180px); /* 高度不超過螢幕 */
    overflow-y: auto;     /* 超出高度時滾動 */
    background: transparent;
    padding: 8px 0 0 0;
    z-index: 1000;
    flex-shrink: 0;       /* 防止被壓縮 */
    margin-right: 150px;   /* 右側間距 */
    z-index: 1; 
    margin-top: -35px;
}

/* 響應式：螢幕小於 1200px 時改為垂直排列 */
@media screen and (max-width: 1200px) {
    .video-layout { 
        flex-direction: column;
        flex-wrap: wrap; 
    }
    .video-related {
        position: relative;
        width: 100%;
        max-height: none;
        margin-top: 20px;
        top: auto;
        padding: 0;
    }
}

/* 主影片區自動調整寬度 */
.video-main { 
    flex: 1;
    min-width: 0;
}

/* 微調相關卡片樣式，更精緻 */
.related-card { transition: transform .12s ease, box-shadow .12s ease; }
.related-card:hover { transform: translateY(-8px); box-shadow: 0 14px 35px rgba(0,0,0,0.12); }


/* 相關影片卡片 */
.related-card {
    display:flex;
    margin-bottom:30px;
    border-radius:8px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 4px 15px rgba(0,0,0,0.06);
}

.related-thumb {
    width: 280px;
    height: 140px;
    flex-shrink: 0;
    position: relative;
}

.related-thumb img {
    width:100%;
    height:100%;
    object-fit:cover;
}

.related-thumb .duration {
    position:absolute;
    bottom:6px;
    right:6px;
    font-size:12px;
    font-weight:500;
    background:rgba(0,0,0,0.7);
    color:#fff;
    padding:3px 6px;
    border-radius:4px;
}

.related-title {
    padding:10px 14px;
    font-size:15px;
    font-weight:500;
    color:#222;
    display:flex;
    align-items:center;
    flex:1;
}

/* 分頁 */
.related-pagination {
    margin-top:12px;
    text-align:center;
}

.related-pagination a, .related-pagination span {
    display:inline-block;
    padding:6px 10px;
    margin:0 3px;
    border-radius:4px;
    text-decoration:none;
    font-size:13px;
}

.related-pagination a {
    background:#fff;
    color:#333;
    border:1px solid #ddd;
}

.related-pagination span.active {
    background:#4CAF50;
    color:#fff;
    border:1px solid #4CAF50;
}

    </style>
</head>
<body>
<?php include('share/header.php'); ?>
<div class="video-page">
    <div class="container">
        <?php if (!$video): ?>
            <div style="padding:40px;text-align:center;color:#777">
                找不到影片或尚未發布。
            </div>
        <?php else: ?>
        <div class="video-layout">
            <!-- 左側主影片區 -->
            <div class="video-main">
                <div class="video-title"><?php echo htmlspecialchars($video['title'], ENT_QUOTES); ?>
                    <a class="back" href="radio.php">← 返回影片列表</a>
                </div>
                <div class="video-box">
                    <video controls preload="metadata"
                            poster="<?php echo htmlspecialchars($video['thumbnail_url'], ENT_QUOTES); ?>">
                        <source src="<?php echo htmlspecialchars($video['video_url'], ENT_QUOTES); ?>" type="video/mp4">
                        您的瀏覽器不支援影片播放。
                    </video>
                </div>
                <div class="meta">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <span>分類：<?php echo htmlspecialchars($video['category'] ?? '未分類'); ?></span>
                        <span>｜</span>
                        <span>長度：<?php echo htmlspecialchars($video['duration'] ?? ''); ?></span>
                        <span>｜</span>
                        <span id="viewCountText"><?php echo intval($video['view_count'] ?? 0); ?>觀看數</span>
                    </div>
                    <div class="video-stats">
                        <button class="like-btn <?php echo $isLiked ? 'liked' : ''; ?>" id="likeBtn" data-video-id="<?php echo $video['id']; ?>">
                            <span class="icon">👍</span>
                            <span id="likeCount"><?php echo intval($video['like_count'] ?? 0); ?></span>
                        </button>
                        <span class="video-stats-separator"></span>
                        <?php if ($isLoggedIn): ?>
                        <button class="share-btn" id="shareBtn" data-video-id="<?php echo $video['id']; ?>" data-video-title="<?php echo htmlspecialchars($video['title'], ENT_QUOTES); ?>">
                            <span class="icon">↗️</span>
                            <span>分享</span>
                        </button>
                        <?php else: ?>
                        <button class="share-btn disabled" disabled title="請先登入才能分享">
                            <span class="icon">↗️</span>
                            <span>分享</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <h4>影片描述</h4>
                <div class="desc">
                    <?php echo nl2br(htmlspecialchars($video['description'] ?? '')); ?>
                </div>
            </div>

            <!-- 右側相關影片區 -->
            <div class="video-related">
                <h4>相關影片</h4>
                <?php if (empty($related)): ?>
                    <div style="color:#777;">目前沒有相關影片。</div>
                <?php else: ?>
                    <?php foreach ($related as $r): ?>
                        <div class="related-card">
                            <a href="video_player.php?id=<?php echo $r['id']; ?>" style="text-decoration:none;color:inherit;display:flex;">
                                <div class="related-thumb">
                                    <img src="<?php echo htmlspecialchars($r['thumbnail_url'] ?? '/media/thumbnails/default.jpg', ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?>">
                                    <?php if (!empty($r['duration'])): ?>
                                    <div class="duration"><?php echo htmlspecialchars($r['duration']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="related-title"><?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>

                    <!-- 分頁 -->
                    <?php if ($totalRelatedPages > 1): ?>
                    <div class="related-pagination">
                        <?php for ($p=1;$p<=$totalRelatedPages;$p++): ?>
                            <?php if ($p == $relatedPage): ?>
                                <span class="active"><?php echo $p; ?></span>
                            <?php else: ?>
                                <a href="?id=<?php echo $video['id']; ?>&related_page=<?php echo $p; ?>"><?php echo $p; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php include('share/footer.php'); ?>

<?php if ($video): ?>
<script>
// 觀看次數更新（頁面載入時）
document.addEventListener('DOMContentLoaded', function() {
    const videoId = <?php echo $video['id']; ?>;
    
    // 更新觀看次數
    fetch('video_player.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=view&video_id=' + videoId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.view_count !== undefined) {
            const viewCountEl = document.getElementById('viewCountText');
            if (viewCountEl) {
                viewCountEl.textContent = data.view_count + '觀看數';
            }
        }
    })
    .catch(error => console.error('更新觀看次數失敗:', error));
    
    // 按讚功能
    const likeBtn = document.getElementById('likeBtn');
    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            const isLiked = this.classList.contains('liked');
            const videoId = this.getAttribute('data-video-id');
            const likeCountEl = document.getElementById('likeCount');
            const icon = this.querySelector('.icon');
            
            fetch('video_player.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=like&video_id=' + videoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'liked') {
                        this.classList.add('liked');
                        icon.textContent = '👍';
                    } else {
                        this.classList.remove('liked');
                        icon.textContent = '👍';
                    }
                    if (likeCountEl) {
                        likeCountEl.textContent = data.like_count || 0;
                    }
                } else {
                    if (data.error && data.error.includes('登入')) {
                        alert('請先登入才能按讚');
                    }
                }
            })
            .catch(error => {
                console.error('按讚失敗:', error);
                alert('操作失敗，請稍後再試');
            });
        });
    }
    
    // 分享功能
    const shareBtn = document.getElementById('shareBtn');
    if (shareBtn && !shareBtn.disabled) {
        shareBtn.addEventListener('click', function() {
            const videoId = this.getAttribute('data-video-id');
            const videoTitle = this.getAttribute('data-video-title');
            const currentUrl = window.location.href;
            const shareText = '分享影片：' + videoTitle + '\n' + currentUrl;
            
            // 檢查是否支援 Web Share API
            if (navigator.share) {
                navigator.share({
                    title: videoTitle,
                    text: '分享這個影片',
                    url: currentUrl
                })
                .then(() => console.log('分享成功'))
                .catch(error => {
                    // 如果用戶取消分享，不顯示錯誤
                    if (error.name !== 'AbortError') {
                        console.error('分享失敗:', error);
                        // 降級到複製連結
                        copyToClipboard(currentUrl);
                    }
                });
            } else {
                // 降級方案：複製連結到剪貼簿
                copyToClipboard(currentUrl);
            }
        });
    }
    
    // 複製到剪貼簿的輔助函數
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                alert('連結已複製到剪貼簿！');
            }).catch(err => {
                console.error('複製失敗:', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            alert('連結已複製到剪貼簿！');
        } catch (err) {
            console.error('複製失敗:', err);
            prompt('請手動複製以下連結：', text);
        }
        document.body.removeChild(textArea);
    }
});
</script>
<?php endif; ?>

</body>
</html>