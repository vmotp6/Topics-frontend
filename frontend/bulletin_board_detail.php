<?php 
require_once 'config.php';
include 'share/header.php'; 

// 獲取公告 ID
$bulletin_id = $_GET['id'] ?? null;

if (!$bulletin_id || !is_numeric($bulletin_id)) {
    header("Location: bulletin_board.php");
    exit;
}

// 從資料庫讀取公告詳細資訊
$conn = getDatabaseConnection();
$bulletin = null;
$urls = [];
$files = [];

// 查詢公告詳細資訊
$sql = "SELECT bb.*, u.name as author_name, u.username as author_username,
               bt.name as type_name, bs.name as status_name
        FROM bulletin_board bb
        LEFT JOIN user u ON bb.user_id = u.id
        LEFT JOIN bulletin_types bt ON bb.type_code = bt.code
        LEFT JOIN bulletin_statuses bs ON bb.status_code = bs.code
        WHERE bb.id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $bulletin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bulletin = $result->fetch_assoc();
    $stmt->close();
}

if (!$bulletin) {
    $conn->close();
    header("Location: bulletin_board.php");
    exit;
}

// 查詢相關連結
$url_sql = "SELECT * FROM bulletin_urls WHERE bulletin_id = ? ORDER BY display_order ASC";
$url_stmt = $conn->prepare($url_sql);
if ($url_stmt) {
    $url_stmt->bind_param("i", $bulletin_id);
    $url_stmt->execute();
    $url_result = $url_stmt->get_result();
    while ($row = $url_result->fetch_assoc()) {
        $urls[] = $row;
    }
    $url_stmt->close();
}

// 查詢相關檔案
$file_sql = "SELECT * FROM bulletin_files WHERE bulletin_id = ? ORDER BY display_order ASC";
$file_stmt = $conn->prepare($file_sql);
if ($file_stmt) {
    $file_stmt->bind_param("i", $bulletin_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    while ($row = $file_result->fetch_assoc()) {
        $files[] = $row;
    }
    $file_stmt->close();
}

// 更新瀏覽次數
$update_view_sql = "UPDATE bulletin_board SET view_count = view_count + 1 WHERE id = ?";
$update_stmt = $conn->prepare($update_view_sql);
if ($update_stmt) {
    $update_stmt->bind_param("i", $bulletin_id);
    $update_stmt->execute();
    $update_stmt->close();
}

$conn->close();

// 公告類型對應的圖標
$type_icons = [
    'exam' => 'fa-graduation-cap',
    'interview' => 'fa-user-tie',
    'result' => 'fa-check-circle',
    'general' => 'fa-bullhorn'
];

$icon = $type_icons[$bulletin['type_code']] ?? 'fa-bullhorn';
$date_range = '';
if ($bulletin['start_date'] || $bulletin['end_date']) {
    $start = $bulletin['start_date'] ? date('Y年m月d日', strtotime($bulletin['start_date'])) : '';
    $end = $bulletin['end_date'] ? date('Y年m月d日', strtotime($bulletin['end_date'])) : '';
    if ($start && $end) {
        $date_range = $start . ' - ' . $end;
    } elseif ($start) {
        $date_range = $start . ' 起';
    } elseif ($end) {
        $date_range = '至 ' . $end;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($bulletin['title']); ?> - 康寧大學招生平台</title>
  <style>
    :root {
      --bg: #f9fafb;
      --panel: #ffffff;
      --border: #e5e7eb;
      --text: #2c3e50;
      --muted: #6b7280;
      --accent: #667eea;
      --accent-gradient: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
      --radius: 12px;
      --shadow: rgba(0, 0, 0, 0.08);
    }

    body {
      margin: 0;
      padding-top: 100px;
      font-family: "Microsoft JhengHei", -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
    }

    .page-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--accent);
      text-decoration: none;
      margin-bottom: 24px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .back-link:hover {
      transform: translateX(-4px);
    }

    .detail-card {
      background: var(--panel);
      border-radius: var(--radius);
      box-shadow: 0 4px 12px var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .detail-header {
      padding: 40px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.05) 0%, rgba(149, 109, 189, 0.05) 100%);
    }

    .detail-badge {
      display: inline-block;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 16px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-badge[data-type="exam"] {
      background: #667eea;
      color: white;
    }

    .detail-badge[data-type="interview"] {
      background: #f5576c;
      color: white;
    }

    .detail-badge[data-type="result"] {
      background: #4facfe;
      color: white;
    }

    .detail-badge[data-type="general"] {
      background: #10b981;
      color: white;
    }

    .detail-title {
      font-size: 40px;
      font-weight: 700;
      color: var(--text);
      margin: 0 0 16px;
      line-height: 1.4;
    }

    .detail-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      font-size: 16px;
      color: var(--muted);
      margin-top: 16px;
    }

    .detail-meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .detail-body {
      padding: 40px;
    }

    .detail-image {
      width: 100%;
      max-width: 800px;
      margin: 0 auto 32px;
      border-radius: var(--radius);
      overflow: hidden;
    }

    .detail-image img {
      width: 100%;
      height: auto;
      display: block;
    }

    .detail-content {
      font-size: 18px;
      line-height: 1.8;
      color: var(--text);
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .detail-section {
      margin-top: 40px;
      padding-top: 32px;
      border-top: 1px solid var(--border);
    }

    .detail-section-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 16px;
    }

    .url-list, .file-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .url-item, .file-item {
      padding: 12px 16px;
      margin-bottom: 8px;
      background: #f9fafb;
      border-radius: 8px;
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .url-item a, .file-item a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
      flex: 1;
    }

    .url-item a:hover, .file-item a:hover {
      text-decoration: underline;
    }

    .file-size {
      font-size: 14px;
      color: var(--muted);
    }

    @media (max-width: 768px) {
      .page-container {
        padding: 20px 16px;
      }

      .detail-header, .detail-body {
        padding: 24px 20px;
      }

      .detail-title {
        font-size: 30px;
      }

      .detail-meta {
        flex-direction: column;
        gap: 12px;
      }
    }
  </style>
</head>
<body>

  <div class="page-container">
    <a href="bulletin_board.php" class="back-link">
      <i class="fas fa-arrow-left"></i> 返回公告列表
    </a>

    <article class="detail-card">
      <div class="detail-header">
        <span class="detail-badge" data-type="<?php echo htmlspecialchars($bulletin['type_code']); ?>">
          <?php echo htmlspecialchars($bulletin['type_name'] ?: ucfirst($bulletin['type_code'])); ?>
        </span>
        <h1 class="detail-title"><?php echo htmlspecialchars($bulletin['title']); ?></h1>
        <div class="detail-meta">
          <div class="detail-meta-item">
            <i class="fas fa-user"></i>
            <span>發布者：<?php echo htmlspecialchars($bulletin['author_name'] ?: $bulletin['author_username'] ?: '未知'); ?></span>
          </div>
          <div class="detail-meta-item">
            <i class="fas fa-calendar"></i>
            <span>發布時間：<?php echo date('Y年m月d日 H:i', strtotime($bulletin['created_at'])); ?></span>
          </div>
          <?php if ($date_range): ?>
          <div class="detail-meta-item">
            <i class="fas fa-clock"></i>
            <span>有效期間：<?php echo $date_range; ?></span>
          </div>
          <?php endif; ?>
          <div class="detail-meta-item">
            <i class="fas fa-eye"></i>
            <span>瀏覽次數：<?php echo $bulletin['view_count']; ?></span>
          </div>
        </div>
      </div>

      <div class="detail-body">
        <?php if ($bulletin['image_url']): ?>
        <div class="detail-image">
          <img src="<?php echo htmlspecialchars($bulletin['image_url']); ?>" alt="<?php echo htmlspecialchars($bulletin['title']); ?>">
        </div>
        <?php endif; ?>

        <div class="detail-content">
          <?php echo nl2br(htmlspecialchars($bulletin['content'])); ?>
        </div>

        <?php if (!empty($urls) || !empty($files)): ?>
        <div class="detail-section">
          <h2 class="detail-section-title">相關資源</h2>
          
          <?php if (!empty($urls)): ?>
          <h3 style="font-size: 18px; font-weight: 600; margin: 16px 0 8px; color: var(--text);">相關連結</h3>
          <ul class="url-list">
            <?php foreach ($urls as $url): ?>
            <li class="url-item">
              <a href="<?php echo htmlspecialchars($url['url']); ?>" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <?php echo htmlspecialchars($url['title'] ?: $url['url']); ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <?php if (!empty($files)): ?>
          <h3 style="font-size: 18px; font-weight: 600; margin: 16px 0 8px; color: var(--text);">相關檔案</h3>
          <ul class="file-list">
            <?php foreach ($files as $file): ?>
            <li class="file-item">
              <a href="download_bulletin_file.php?id=<?php echo $file['id']; ?>" target="_blank">
                <i class="fas fa-file-download"></i>
                <?php echo htmlspecialchars($file['original_filename']); ?>
              </a>
              <span class="file-size"><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </article>
  </div>
</body>
</html>
