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

// 處理留言 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    header('Content-Type: application/json');
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'error' => '請先登入才能留言']);
        exit;
    }
    
    // 獲取用戶 ID（優先從 session，如果沒有則從 username 查詢）
    $currentUserId = 0;
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $currentUserId = intval($_SESSION['user_id']);
    } else if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        // 從 username 查詢 user_id
        $username = $_SESSION['username'];
        $userStmt = $conn->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        if ($userStmt) {
            $userStmt->bind_param('s', $username);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userRow = $userResult->fetch_assoc()) {
                $currentUserId = intval($userRow['id']);
            }
            $userStmt->close();
        }
    }
    
    if ($currentUserId <= 0) {
        error_log("無法獲取用戶 ID，username: " . ($_SESSION['username'] ?? '未設置'));
        echo json_encode(['success' => false, 'error' => '無法識別用戶身份，請重新登入']);
        exit;
    }
    
    $videoId = isset($_POST['video_id']) ? intval($_POST['video_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if ($videoId <= 0) {
        echo json_encode(['success' => false, 'error' => '無效的影片ID']);
        exit;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => '留言內容不能為空']);
        exit;
    }
    
    if (strlen($content) > 1000) {
        echo json_encode(['success' => false, 'error' => '留言內容不能超過1000字']);
        exit;
    }
    
    // 創建留言表
    $conn->query("CREATE TABLE IF NOT EXISTS video_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_deleted TINYINT DEFAULT 0,
        KEY video_id (video_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 插入留言
    $stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, content) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        error_log("準備插入留言失敗: " . $conn->error);
        echo json_encode(['success' => false, 'error' => '留言失敗，請稍後再試']);
        exit;
    }
    
    $stmt->bind_param('iis', $videoId, $currentUserId, $content);
    
    if ($stmt->execute()) {
        $commentId = $conn->insert_id;
        $stmt->close();
        
        // 獲取留言詳情（包含用戶名和頭像）
        $stmt = $conn->prepare("
            SELECT c.id, c.video_id, c.user_id, c.content, c.created_at,
                   COALESCE(u.username, '匿名用戶') AS username, 
                   u.role,
                   u.profile_picture,
                   u.name
            FROM video_comments c
            LEFT JOIN user u ON c.user_id = u.id
            WHERE c.id = ? AND c.is_deleted = 0
        ");
        
        if ($stmt) {
            $stmt->bind_param('i', $commentId);
            $stmt->execute();
            $res = $stmt->get_result();
            $comment = $res->fetch_assoc();
            $stmt->close();
            
            echo json_encode(['success' => true, 'comment' => $comment]);
        } else {
            error_log("準備查詢留言詳情失敗: " . $conn->error);
            echo json_encode(['success' => true, 'message' => '留言發布成功']);
        }
    } else {
        error_log("執行插入留言失敗: " . $stmt->error);
        $stmt->close();
        echo json_encode(['success' => false, 'error' => '留言失敗，請稍後再試']);
    }
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

// 取得留言（初始化變數）
$comments = [];
$commentsPerPage = 10;
$commentsPage = max(1, intval($_GET['comments_page'] ?? 1));
$commentsOffset = ($commentsPage - 1) * $commentsPerPage;
$totalComments = 0;
$totalCommentsPages = 1;

if ($video && isset($video['id'])) {
    // 創建留言表（如果不存在）
    $conn->query("CREATE TABLE IF NOT EXISTS video_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_deleted TINYINT DEFAULT 0,
        KEY video_id (video_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 計算總留言數
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM video_comments WHERE video_id = ? AND is_deleted = 0");
    if ($stmt) {
        $stmt->bind_param('i', $video['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $totalComments = intval($row['c'] ?? 0);
        $stmt->close();
    } else {
        error_log("準備留言計數查詢失敗: " . $conn->error);
        $totalComments = 0;
    }
    
    // 取得留言列表
    // 注意：LIMIT 和 OFFSET 不能使用參數綁定，需要直接使用變數值（已確保為整數）
    $commentsPerPage = intval($commentsPerPage);
    $commentsOffset = intval($commentsOffset);
    $videoId = intval($video['id']);
    
    // 確保 LIMIT 和 OFFSET 是正整數
    if ($commentsPerPage <= 0) $commentsPerPage = 10;
    if ($commentsOffset < 0) $commentsOffset = 0;
    
    $sql = "
        SELECT c.id, c.video_id, c.user_id, c.content, c.created_at,
               COALESCE(u.username, '匿名用戶') AS username, 
               u.role,
               u.profile_picture,
               u.name
        FROM video_comments c
        LEFT JOIN user u ON c.user_id = u.id AND c.user_id > 0
        WHERE c.video_id = ? AND c.is_deleted = 0
        ORDER BY c.created_at DESC
        LIMIT " . $commentsPerPage . " OFFSET " . $commentsOffset . "
    ";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('i', $videoId);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    if ($r && isset($r['id']) && !empty($r['id'])) {
                        // 確保 username 有值
                        if (empty($r['username']) || $r['username'] === null) {
                            $r['username'] = '匿名用戶';
                        }
                        // 確保 content 有值
                        if (!isset($r['content'])) {
                            $r['content'] = '';
                        }
                        // 確保 name 有值（如果沒有則使用 username）
                        if (empty($r['name']) && !empty($r['username'])) {
                            $r['name'] = $r['username'];
                        }
                        $comments[] = $r;
                    }
                }
            }
        } else {
            error_log("執行留言查詢失敗: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // 如果 prepare 失敗，記錄錯誤
        error_log("準備留言查詢失敗: " . $conn->error);
        error_log("SQL: " . $sql);
        error_log("Video ID: " . $videoId);
    }
    
    $totalCommentsPages = max(1, ceil($totalComments / $commentsPerPage));
} else {
    // 如果沒有影片，確保變數有預設值
    $comments = [];
    $totalComments = 0;
    $totalCommentsPages = 1;
    $commentsPage = 1;
}

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
    width: 120px;
    font-size: 20px;
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
    width: 120px;
    font-size: 20px;
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

/* 留言板樣式 */
.comments-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.comments-section h4 {
    font-size: 24px;
    font-weight: 600;
    color: #222;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.comments-count {
    font-size: 16px;
    font-weight: 400;
    color: #666;
}

/* 留言表單 */
.comment-form-container {
    margin-bottom: 30px;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.comment-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    box-sizing: border-box;
}

.comment-form textarea:focus {
    outline: none;
    border-color: #2196f3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
}

.comment-form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}

.char-count {
    font-size: 12px;
    color: #999;
}

.submit-comment-btn {
    background: #2196f3;
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.submit-comment-btn:hover {
    background: #1976d2;
}

.submit-comment-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* 登入提示 */
.comment-login-prompt {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 30px;
    text-align: center;
}

.comment-login-prompt p {
    margin: 0;
    color: #856404;
}

.comment-login-prompt a {
    color: #2196f3;
    text-decoration: none;
    font-weight: 500;
}

.comment-login-prompt a:hover {
    text-decoration: underline;
}

/* 留言列表 */
.comments-list {
    margin-top: 20px;
}

.no-comments {
    text-align: center;
    color: #999;
    padding: 40px 20px;
    font-size: 14px;
}

.comment-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    transition: box-shadow 0.2s;
}

.comment-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* 留言者資訊區（頭像+名稱） */
.comment-author-section {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.comment-avatar {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    background: #f0f0f0;
    border: 2px solid #e0e0e0;
}

.comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.comment-author-info {
    flex: 1;
    min-width: 0;
}

.comment-author-name {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    flex-wrap: wrap;
}

.author-name {
    font-weight: 600;
    color: #333;
    font-size: 15px;
}

.author-role {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.comment-date {
    font-size: 12px;
    color: #999;
}

/* 留言內容區 */
.comment-content {
    color: #555;
    line-height: 1.6;
    font-size: 14px;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin-left: 60px; /* 對齊頭像下方 */
    padding-top: 8px;
}

/* 留言分頁 */
.comments-pagination {
    margin-top: 20px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.comments-pagination a,
.comments-pagination span {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.comments-pagination a {
    background: #fff;
    color: #333;
    border: 1px solid #ddd;
}

.comments-pagination a:hover {
    background: #f5f5f5;
    border-color: #2196f3;
}

.comments-pagination span.active {
    background: #2196f3;
    color: #fff;
    border: 1px solid #2196f3;
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
                
                <!-- 留言板區塊 -->
                <div class="comments-section">
                    <h4>留言板 <span class="comments-count">(<?php echo isset($totalComments) ? $totalComments : 0; ?>)</span></h4>
                    
                    <!-- 留言表單（僅登入用戶可見） -->
                    <?php if ($isLoggedIn): ?>
                    <div class="comment-form-container">
                        <form id="commentForm" class="comment-form">
                            <textarea 
                                id="commentContent" 
                                name="content" 
                                placeholder="留下您的留言..." 
                                rows="4" 
                                maxlength="1000"
                                required></textarea>
                            <div class="comment-form-footer">
                                <span class="char-count"><span id="charCount">0</span>/1000</span>
                                <button type="submit" class="submit-comment-btn">發布留言</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="comment-login-prompt">
                        <p>請先 <a href="login.php">登入</a> 才能留言</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 留言列表 -->
                    <div class="comments-list" id="commentsList">
                        <?php 
                        // 調試信息（可在生產環境中移除）
                        // error_log("Comments count: " . count($comments));
                        // error_log("Total comments: " . $totalComments);
                        // error_log("Comments array: " . print_r($comments, true));
                        ?>
                        <?php if (empty($comments) || !is_array($comments) || count($comments) == 0): ?>
                            <?php if (isset($totalComments) && $totalComments > 0): ?>
                                <div class="no-comments" style="color: #f44336; padding: 20px;">
                                    留言載入失敗，請重新整理頁面。<br>
                                    總留言數：<?php echo $totalComments; ?><br>
                                    <a href="?id=<?php echo $video['id']; ?>" style="color: #2196f3; text-decoration: underline;">點擊重新整理</a>
                                </div>
                            <?php else: ?>
                                <div class="no-comments">目前還沒有留言，成為第一個留言的人吧！</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <?php if (!empty($comment) && isset($comment['id']) && !empty($comment['id'])): ?>
                                <?php
                                // 處理頭像路徑
                                $avatarUrl = getResourcePath('EIdROxGXsAE_LSs.jpg'); // 預設頭像
                                if (!empty($comment['profile_picture'])) {
                                    $profilePic = $comment['profile_picture'];
                                    // 優先檢查是否為上傳的頭像（uploads/ 開頭）
                                    if (strpos($profilePic, 'uploads/') === 0) {
                                        // 上傳的頭像，使用 getCorrectPath
                                        $avatarUrl = getCorrectPath($profilePic);
                                    } elseif (filter_var($profilePic, FILTER_VALIDATE_URL)) {
                                        // 完整 URL（如 Google 頭像），直接使用
                                        $avatarUrl = $profilePic;
                                    } else {
                                        // share 目錄的檔案，使用 getResourcePath
                                        $avatarUrl = getResourcePath($profilePic);
                                    }
                                }
                                
                                // 顯示名稱（優先使用 name，其次 username）
                                $displayName = !empty($comment['name']) ? $comment['name'] : ($comment['username'] ?? '匿名用戶');
                                ?>
                                <div class="comment-item" data-comment-id="<?php echo htmlspecialchars($comment['id']); ?>">
                                    <div class="comment-author-section">
                                        <div class="comment-avatar">
                                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" onerror="this.src='<?php echo htmlspecialchars(getResourcePath('EIdROxGXsAE_LSs.jpg')); ?>'">
                                        </div>
                                        <div class="comment-author-info">
                                            <div class="comment-author-name">
                                                <span class="author-name"><?php echo htmlspecialchars($displayName); ?></span>
                                                <?php if (!empty($comment['role']) && $comment['role'] != ''): ?>
                                                    <span class="author-role"><?php echo htmlspecialchars($comment['role']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="comment-date"><?php echo !empty($comment['created_at']) ? date('Y-m-d H:i', strtotime($comment['created_at'])) : date('Y-m-d H:i'); ?></span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        <?php 
                                        $content = isset($comment['content']) ? $comment['content'] : '';
                                        if (!empty($content)) {
                                            echo nl2br(htmlspecialchars($content));
                                        } else {
                                            echo '<span style="color: #999; font-style: italic;">（無內容）</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <!-- 留言分頁 -->
                            <?php if (isset($totalCommentsPages) && $totalCommentsPages > 1): ?>
                            <div class="comments-pagination">
                                <?php for ($p = 1; $p <= $totalCommentsPages; $p++): ?>
                                    <?php if ($p == (isset($commentsPage) ? $commentsPage : 1)): ?>
                                        <span class="active"><?php echo $p; ?></span>
                                    <?php else: ?>
                                        <a href="?id=<?php echo $video['id']; ?>&comments_page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
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
    
    // 留言功能
    const commentForm = document.getElementById('commentForm');
    const commentContent = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');
    const commentsList = document.getElementById('commentsList');
    
    // 字數統計
    if (commentContent && charCount) {
        commentContent.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            if (length > 1000) {
                charCount.style.color = '#f44336';
            } else {
                charCount.style.color = '#999';
            }
        });
    }
    
    // 提交留言
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const content = commentContent.value.trim();
            if (!content) {
                alert('請輸入留言內容');
                return;
            }
            
            if (content.length > 1000) {
                alert('留言內容不能超過1000字');
                return;
            }
            
            const videoId = <?php echo $video ? $video['id'] : 0; ?>;
            const submitBtn = this.querySelector('.submit-comment-btn');
            const originalText = submitBtn.textContent;
            
            // 禁用按鈕
            submitBtn.disabled = true;
            submitBtn.textContent = '發布中...';
            
            fetch('video_player.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_comment&video_id=' + videoId + '&content=' + encodeURIComponent(content)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comment) {
                    // 清空表單
                    commentContent.value = '';
                    if (charCount) {
                        charCount.textContent = '0';
                        charCount.style.color = '#999';
                    }
                    
                    // 動態添加新留言到列表頂部
                    addCommentToPage(data.comment);
                    
                    // 更新留言計數
                    updateCommentCount();
                    
                    // 恢復按鈕狀態
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                    // 滾動到新留言位置
                    const newComment = document.querySelector('[data-comment-id="' + data.comment.id + '"]');
                    if (newComment) {
                        newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        // 高亮顯示新留言
                        newComment.style.backgroundColor = '#e3f2fd';
                        setTimeout(() => {
                            newComment.style.backgroundColor = '';
                        }, 2000);
                    }
                } else {
                    alert(data.error || '留言失敗，請稍後再試');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('留言失敗:', error);
                alert('留言失敗，請稍後再試');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
    
    // 動態添加留言到頁面
    function addCommentToPage(comment) {
        if (!commentsList) return;
        
        // 檢查是否已有留言列表，如果沒有則移除「沒有留言」的提示
        const noCommentsMsg = commentsList.querySelector('.no-comments');
        if (noCommentsMsg) {
            noCommentsMsg.remove();
        }
        
        // 創建留言元素
        const commentItem = document.createElement('div');
        commentItem.className = 'comment-item';
        commentItem.setAttribute('data-comment-id', comment.id);
        
        // 格式化日期時間
        const commentDate = new Date(comment.created_at);
        const formattedDate = commentDate.getFullYear() + '-' + 
            String(commentDate.getMonth() + 1).padStart(2, '0') + '-' + 
            String(commentDate.getDate()).padStart(2, '0') + ' ' + 
            String(commentDate.getHours()).padStart(2, '0') + ':' + 
            String(commentDate.getMinutes()).padStart(2, '0');
        
        // 處理頭像路徑
        const defaultAvatar = '<?php echo getResourcePath('EIdROxGXsAE_LSs.jpg'); ?>';
        const basePath = '<?php echo getCorrectPath(''); ?>';
        const sharePath = '<?php echo getResourcePath(''); ?>';
        
        let avatarUrl = defaultAvatar; // 預設頭像
        if (comment.profile_picture) {
            const profilePic = comment.profile_picture;
            // 優先檢查是否為上傳的頭像（uploads/ 開頭）
            if (profilePic.startsWith('uploads/')) {
                // 上傳的頭像，使用 getCorrectPath
                avatarUrl = basePath + profilePic;
            } else if (profilePic.startsWith('http://') || profilePic.startsWith('https://')) {
                // 完整 URL（如 Google 頭像），直接使用
                avatarUrl = profilePic;
            } else {
                // share 目錄的檔案，使用 getResourcePath
                avatarUrl = sharePath + profilePic;
            }
        }
        
        // 顯示名稱（優先使用 name，其次 username）
        const displayName = comment.name || comment.username || '匿名用戶';
        
        // 構建留言 HTML
        let roleBadge = '';
        if (comment.role && comment.role !== '') {
            roleBadge = '<span class="author-role">' + escapeHtml(comment.role) + '</span>';
        }
        
        commentItem.innerHTML = `
            <div class="comment-author-section">
                <div class="comment-avatar">
                    <img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(displayName)}" onerror="this.src='${defaultAvatar}'">
                </div>
                <div class="comment-author-info">
                    <div class="comment-author-name">
                        <span class="author-name">${escapeHtml(displayName)}</span>
                        ${roleBadge}
                    </div>
                    <span class="comment-date">${formattedDate}</span>
                </div>
            </div>
            <div class="comment-content">${escapeHtml(comment.content).replace(/\n/g, '<br>')}</div>
        `;
        
        // 插入到列表頂部（最新留言在最上面）
        const firstComment = commentsList.querySelector('.comment-item');
        if (firstComment) {
            commentsList.insertBefore(commentItem, firstComment);
        } else {
            commentsList.appendChild(commentItem);
        }
    }
    
    // 更新留言計數
    function updateCommentCount() {
        const countElement = document.querySelector('.comments-count');
        if (countElement) {
            const currentCount = parseInt(countElement.textContent.match(/\d+/)?.[0] || '0');
            const newCount = currentCount + 1;
            countElement.textContent = '(' + newCount + ')';
        }
    }
    
    // HTML 轉義函數
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
<?php endif; ?>

</body>
</html>