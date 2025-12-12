<?php
require_once 'session_config.php';
require_once 'config.php';

// 連接資料庫
$conn = getDatabaseConnection();

// 參數
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// 取得分類清單
$categories = [];
$catSql = "SELECT DISTINCT IFNULL(category, '') AS category FROM videos WHERE published = 1 ORDER BY category";
if ($res = $conn->query($catSql)) {
    while ($row = $res->fetch_assoc()) {
        if ($row['category'] !== '') $categories[] = $row['category'];
    }
    $res->free();
}

// 建立查詢條件
$where = "WHERE published = 1";
$params = [];
if ($search !== '') {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($category !== '') {
    $where .= " AND category = ?";
    $params[] = $category;
}

// 計算總數
$total = 0;
$countSql = "SELECT COUNT(*) AS c FROM videos $where";
$stmt = $conn->prepare($countSql);
if ($stmt) {
    if (!empty($params)) {
        // 動態 bind 參數
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $cr = $stmt->get_result();
    $row = $cr->fetch_assoc();
    $total = intval($row['c']);
    $stmt->close();
}

// 取得影片
$list = [];
$listSql = "SELECT * FROM videos $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($listSql);
if ($stmt) {
    // bind params: existing params + two integers
    $bindTypes = '';
    $bindValues = [];
    if (!empty($params)) {
        $bindTypes .= str_repeat('s', count($params));
        $bindValues = $params;
    }
    $bindTypes .= 'ii';
    $bindValues[] = $perPage;
    $bindValues[] = $offset;
    // bind
    $stmt->bind_param($bindTypes, ...$bindValues);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $list[] = $r;
    $stmt->close();
}

$totalPages = max(1, ceil($total / $perPage));

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>影片列表</title>
    <style>
/* ====== 全域設定 ====== */
* {
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial,
        'Noto Sans TC', 'Microsoft JhengHei', sans-serif;
    background: #f6f8fb;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* ====== 整體容器 ====== */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* 內容主區，避免被 header 蓋住 */
.main-content {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 100px; /* ← 讓標題與 header 保持距離 */
}

/* ====== 頁面標題 ====== */
header {
    text-align: center;
    margin-bottom: 25px;
}
header h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #222;
}

/* ====== 🔍 搜尋列美化 ====== */
.search-bar {
    display: flex;
    justify-content: center;
    margin-bottom: 28px;
}

.search-bar form {
    display: flex;
    width: 100%;
    max-width: 680px;
    gap: 0; /* 按鈕緊貼輸入框 */
}

/* 輸入框：圓角、陰影、無邊框 */
.search-bar input[type=text] {
    flex: 1;
    padding: 13px 15px;
    border: none;
    border-radius: 10px 0 0 10px;
    background: #ffffff;
    font-size: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
    outline: none;
    transition: 0.2s ease;
}

.search-bar input[type=text]:focus {
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.28);
}

/* 搜尋按鈕：主題綠 + 圓角 */
.search-bar button {
    padding: 13px 22px;
    background: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 0 10px 10px 0;
    font-size: 15px;
    cursor: pointer;
    transition: 0.22s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
	width:30%;
}

.search-bar button:hover {
    background: #45A049;
}

/* ====== 🏷️ 分類按鈕美容 ====== */
.categories {
    text-align: center;
    margin-bottom: 25px;
}

/* 無底線、圓角 pill、陰影、hover 顏色 */
.category-btn {
    display: inline-block;
    padding: 7px 16px;
    margin: 5px 6px;
    background: #ffffff;
    border: 1px solid #ddd;
    color: #333;
    text-decoration: none !important;  /* 去底線 */
    font-size: 14px;
    border-radius: 20px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
    transition: 0.25s ease;
}

/* hover：輕微變亮 */
.category-btn:hover {
    background: #f5f5f5;
    border-color: #cfcfcf;
}

/* active 類別 */
.category-btn.active {
    background: #4CAF50;
    color: #fff !important;
    border-color: #4CAF50;
    box-shadow: 0 3px 10px rgba(76,175,80,0.35);
}


/* ====== 分類按鈕 ====== */
.categories {
    text-align: center;
    margin-bottom: 25px;
}

.category-btn {
    display: inline-block;
    background: #fff;
    border: 1px solid #cccccc;
    padding: 6px 14px;
    margin: 4px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
}

.category-btn.active {
    background: #4CAF50;
    color: #fff;
    border-color: #4CAF50;
}

/* ====== 影片三欄區 ====== */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 26px;
    margin-top: 15px;
}

/* 平板 */
@media (max-width: 900px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* 手機 */
@media (max-width: 600px) {
    .grid {
        grid-template-columns: repeat(1, 1fr);
    }
}

/* ====== 卡片 ====== */
.card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    transition: transform .2s;
}

.card:hover {
    transform: translateY(-4px);
}

.thumb {
    position: relative;
    height: 0;
    padding-bottom: 56.25%;
    background: #000;
}

.thumb img {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.play-overlay {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 56px;
    height: 56px;
    background: rgba(0, 0, 0, 0.45);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.play-overlay::after {
    content: '▶';
    color: #fff;
    font-size: 22px;
    margin-left: 3px;
}

.duration {
    position: absolute;
    right: 8px;
    bottom: 8px;
    background: rgba(0, 0, 0, 0.75);
    color: #fff;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.card-body {
    padding: 14px;
}

.title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 6px;
}

.desc {
    font-size: 13px;
    color: #666;
    line-height: 1.4;
    max-height: 3.2em;
    overflow: hidden;
}

/* ====== 分頁 ====== */
.pagination {
    text-align: center;
    margin-top: 30px;
}

.pagination a {
    display: inline-block;
    padding: 8px 14px;
    margin: 0 5px;
    background: #fff;
    border: 1px solid #ccc;
    color: #333;
    border-radius: 6px;
    text-decoration: none;
}


    </style>
</head>
<body>
<?php include('share/header.php'); ?>
<div class="container">
    <div class="main-content">
    <header>
        <h1>影片專區</h1>
        <p style="color:#666;margin-top:6px">搜尋與分類瀏覽影片，點擊縮圖可進入播放頁面。</p>
    </header>

    <div class="search-bar">
        <form id="searchForm" method="get" style="display:flex;width:100%">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <input type="text" name="q" placeholder="搜尋影片標題或描述" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
            <button type="submit">搜尋</button>
        </form>
    </div>

    <div class="categories">
        <a class="category-btn <?php echo $category === '' ? 'active' : ''; ?>" href="radio.php">全部</a>
        <?php foreach ($categories as $c): ?>
            <a class="category-btn <?php echo $category === $c ? 'active' : ''; ?>" href="radio.php?category=<?php echo urlencode($c); ?>"><?php echo htmlspecialchars($c); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="grid">
        <?php if (empty($list)): ?>
            <div style="grid-column:1/-1;padding:24px;background:#fff;border-radius:8px;text-align:center;color:#777">目前沒有符合條件的影片。</div>
        <?php else: foreach ($list as $v): ?>
            <div class="card">
                <a href="video_player.php?id=<?php echo $v['id']; ?>" style="text-decoration:none;color:inherit;display:block">
                    <div class="thumb">
                        <?php if (!empty($v['thumbnail_url'])): ?>
                            <img src="<?php echo htmlspecialchars($v['thumbnail_url'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>">
                        <?php else: ?>
                            <img src="/media/thumbnails/default.jpg" alt="thumbnail">
                        <?php endif; ?>
                        <div class="play-overlay"></div>
                        <?php if (!empty($v['duration'])): ?><div class="duration"><?php echo htmlspecialchars($v['duration']); ?></div><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="title"><?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?></div>
                        <div class="desc"><?php echo htmlspecialchars($v['description'] ? mb_substr($v['description'],0,120) : '', ENT_QUOTES); ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>">‹ 上一頁</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>">下一頁 ›</a>
        <?php endif; ?>
    </div>

</div>
</div>
<?php include('share/footer.php'); ?>
</body>
</html>