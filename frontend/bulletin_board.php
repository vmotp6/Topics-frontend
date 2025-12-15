<?php 
require_once 'config.php';
include 'share/header.php'; 

// 從資料庫讀取公告
$conn = getDatabaseConnection();
$bulletin_list = [];

// 查詢已發布的公告（status_code = 'published'）
// 並且檢查日期範圍（如果設定了 start_date 和 end_date）
$today = date('Y-m-d');
$sql = "SELECT bb.*, u.name as author_name, u.username as author_username,
               bt.name as type_name, bs.name as status_name
        FROM bulletin_board bb
        LEFT JOIN user u ON bb.user_id = u.id
        LEFT JOIN bulletin_types bt ON bb.type_code = bt.code
        LEFT JOIN bulletin_statuses bs ON bb.status_code = bs.code
        WHERE bb.status_code = 'published'
        AND (bb.start_date IS NULL OR bb.start_date <= ?)
        AND (bb.end_date IS NULL OR bb.end_date >= ?)
        ORDER BY bb.is_pinned DESC, bb.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bulletin_list[] = $row;
    }
    $stmt->close();
}
$conn->close();

// 公告類型對應的圖標
$type_icons = [
    'exam' => 'fa-graduation-cap',
    'interview' => 'fa-user-tie',
    'result' => 'fa-check-circle',
    'general' => 'fa-bullhorn'
];

// 格式化日期顯示
function formatDateRange($start_date, $end_date) {
    if (!$start_date && !$end_date) {
        return '';
    }
    $start = $start_date ? date('Y年m月d日', strtotime($start_date)) : '';
    $end = $end_date ? date('Y年m月d日', strtotime($end_date)) : '';
    if ($start && $end) {
        return $start . ' - ' . $end;
    } elseif ($start) {
        return $start . ' 起';
    } elseif ($end) {
        return '至 ' . $end;
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>招生公告欄 - 康寧大學招生平台</title>
  <style>
    :root {
      --bg: #f9fafb;
      --panel: #ffffff;
      --border: #e5e7eb;
      --text: #2c3e50;
      --muted: #6b7280;
      --accent: #667eea;
      --accent-gradient: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
      --accent-hover: #5a6fd8;
      --danger: #dc2626;
      --radius: 12px;
      --shadow: rgba(0, 0, 0, 0.08);
      --shadow-hover: rgba(102, 126, 234, 0.2);
    }

    body {
      margin: 0;
      padding-top: 100px;
      font-family: "Microsoft JhengHei", -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
    }

    /* 頁面主容器 */
    .page-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    /* 頁面標題區域 - 類似 Rockstar Social Club 風格 */
    .page-hero {
      text-align: center;
      margin-bottom: 60px;
      padding: 60px 20px;
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.1) 100%);
      border-radius: 20px;
      border: 1px solid rgba(102, 126, 234, 0.1);
      position: relative;
      overflow: hidden;
    }

    .page-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.05) 0%, rgba(149, 109, 189, 0.05) 100%);
      opacity: 0.5;
    }

    .page-hero h1 {
      margin: 0 0 16px;
      font-size: 56px;
      font-weight: 700;
      color: var(--text);
      position: relative;
      z-index: 1;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .page-hero p {
      margin: 0;
      font-size: 20px;
      color: var(--muted);
      position: relative;
      z-index: 1;
      max-width: 600px;
      margin: 0 auto;
    }

    /* 控制區域 */
    .controls-section {
      margin-bottom: 40px;
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      padding: 24px;
      background: var(--panel);
      border-radius: var(--radius);
      box-shadow: 0 2px 8px var(--shadow);
      border: 1px solid var(--border);
    }

    .controls-left {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      flex: 1;
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-group label {
      font-size: 16px;
      font-weight: 600;
      color: var(--text);
      white-space: nowrap;
    }

    .select {
      padding: 12px 16px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      background: var(--panel);
      color: var(--text);
      font-size: 16px;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.3s ease;
      min-width: 180px;
    }

    .select:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-wrapper {
      position: relative;
      flex: 1;
      min-width: 250px;
      max-width: 500px;
    }

    .search-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 18px;
    }

    .search {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      background: var(--panel);
      color: var(--text);
      font-size: 14px;
      font-family: inherit;
      transition: all 0.3s ease;
    }

    .search:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search::placeholder {
      color: var(--muted);
    }

    /* 網格佈局 */
    .grid {
      display: flex;
      flex-direction: column;
      gap: 24px;
      margin-bottom: 40px;
    }

    /* 卡片樣式 - 現代化設計 */
    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 4px 12px var(--shadow);
      display: flex;
      flex-direction: row;
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px var(--shadow-hover);
      border-color: var(--accent);
    }

    .card-header {
      padding: 20px 20px 16px;
      border-bottom: 1px solid var(--border);
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.05) 0%, rgba(149, 109, 189, 0.05) 100%);
    }

    .card-type-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .card-type-badge[data-type="exam"] {
      background: #667eea;
      color: white;
    }

    .card-type-badge[data-type="interview"] {
      background: #f5576c;
      color: white;
    }

    .card-type-badge[data-type="result"] {
      background: #4facfe;
      color: white;
    }

    .card-type-badge[data-type="general"] {
      background: #10b981;
      color: white;
    }

    .card-image {
      width: 300px;
      min-width: 300px;
      height: 100%;
      min-height: 200px;
      flex-shrink: 0;
      overflow: hidden;
      display: flex;
      align-items: stretch;
      margin-right: 24px;
    }

    .card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .card-image-placeholder {
      width: 100%;
      height: 100%;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.1) 100%);
      color: var(--accent);
      font-size: 48px;
    }

    .card-body {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .card-title {
      font-weight: 700;
      font-size: 24px;
      color: var(--text);
      margin: 0;
      line-height: 1.4;
    }

    .card-desc {
      color: var(--muted);
      font-size: 16px;
      line-height: 1.6;
      margin: 0;
      flex: 1;
    }

    .card-meta {
      display: flex;
      align-items: center;
      gap: 16px;
      font-size: 13px;
      color: var(--muted);
      margin-top: 8px;
    }

    .card-meta-item {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .card-meta-item i {
      font-size: 14px;
    }

    .card-footer {
      margin-top: auto;
      padding: 16px 0;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-start;
      align-items: center;
      background: rgba(102, 126, 234, 0.02);
    }

    .btn {
      padding: 10px 20px;
      border-radius: var(--radius);
      border: none;
      background: #667eea;
      color: #fff;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      font-family: inherit;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
      white-space: nowrap;
    }

    .btn:hover {
      background: #5a6fd8;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn:active {
      transform: translateY(0);
    }

    /* 空狀態 */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    .empty-state p {
      font-size: 18px;
      margin: 0;
    }

    /* 響應式設計 */
    @media (max-width: 768px) {
      body {
        padding-top: 120px;
      }

      .page-container {
        padding: 20px 16px;
      }

      .page-hero {
        padding: 40px 16px;
        margin-bottom: 40px;
      }

      .page-hero h1 {
        font-size: 40px;
      }

      .page-hero p {
        font-size: 18px;
      }

      .controls-section {
        flex-direction: column;
        align-items: stretch;
        padding: 20px;
      }

      .controls-left {
        flex-direction: column;
        width: 100%;
      }

      .filter-group {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
      }

      .select {
        width: 100%;
      }

      .search-wrapper {
        max-width: 100%;
      }

      .card {
        flex-direction: column;
        min-height: auto;
      }

      .card-image {
        width: 100%;
        min-width: 100%;
        height: 200px;
        min-height: 200px;
      }

    .card-image-placeholder {
      width: 100%;
      height: 100%;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.1) 100%);
      color: var(--accent);
      font-size: 48px;
    }

    .card-content {
      padding: 20px 20px 20px 0;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .card-date {
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 8px;
    }

      .card-content {
        padding: 24px 20px;
      }

      .card-title {
        font-size: 22px;
      }

      .card-desc {
        font-size: 15px;
      }

      .btn {
        width: 100%;
      }
    }

    /* Modal 關閉按鈕樣式 */
    .modal-close-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      background: #4b5563 !important;
      color: white !important;
      border: none !important;
      border-radius: 50% !important;
      width: 24px !important;
      height: 24px !important;
      min-width: 24px !important;
      min-height: 24px !important;
      max-width: 24px !important;
      max-height: 24px !important;
      padding: 0 !important;
      margin: 0 !important;
      cursor: pointer;
      font-size: 14px;
      font-weight: 300;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10001;
      transition: all 0.3s ease;
      box-shadow: 0 0 8px rgba(255, 255, 255, 0.25), 0 0 15px rgba(255, 255, 255, 0.1);
      line-height: 1;
      box-sizing: border-box;
    }

    .modal-close-btn:hover {
      background: #374151 !important;
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.35), 0 0 20px rgba(255, 255, 255, 0.12);
      transform: scale(1.05);
    }

    .modal-close-btn:active {
      transform: scale(0.95);
    }
  </style>
</head>
<body>

  <div class="page-container">
    <!-- 頁面標題區域 -->
    <section class="page-hero">
      <h1>招生公告欄</h1>
      <p>提供招生考試、報名日期、面試資訊與重要通知，讓您掌握最新招生訊息</p>
    </section>

    <!-- 控制區域 -->
    <section class="controls-section">
      <div class="controls-left">
        <div class="filter-group">
          <label for="filter-type"><i class="fas fa-filter"></i> 篩選：</label>
          <select class="select" id="filter-type">
            <option value="all">所有公告</option>
            <option value="exam">考試資訊</option>
            <option value="interview">面試通知</option>
            <option value="result">錄取結果</option>
            <option value="general">一般公告</option>
          </select>
        </div>
        <div class="search-wrapper">
          <i class="fas fa-search"></i>
          <input class="search" id="search" type="search" placeholder="搜尋公告標題或內容…" />
        </div>
      </div>
      <?php
      // 檢查是否有發布公告的權限
      $can_publish = false;
      if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        $can_publish = ($role === '老師' || $role === 'TEA' || $role === 'DI' || $role === 'STA' || $role === '學校行政人員');
      }
      if ($can_publish):
      ?>
      <div style="margin-left: auto;">
        <a href="bulletin_board_publish.php" class="btn" style="text-decoration: none; display: inline-block;">
          <i class="fas fa-plus"></i> 發布公告
        </a>
      </div>
      <?php endif; ?>
    </section>

    <!-- 公告列表 -->
    <main class="grid" id="grid">
      <?php if (empty($bulletin_list)): ?>
        <!-- 空狀態 -->
        <div class="empty-state" style="grid-column: 1 / -1;">
          <i class="fas fa-inbox"></i>
          <p>目前沒有公告</p>
        </div>
      <?php else: ?>
        <?php foreach ($bulletin_list as $bulletin): 
          $type_code = $bulletin['type_code'];
          $icon = $type_icons[$type_code] ?? 'fa-bullhorn';
          $date_range = formatDateRange($bulletin['start_date'], $bulletin['end_date']);
        ?>
        <article class="card" data-type="<?php echo htmlspecialchars($type_code); ?>">
          <?php if ($bulletin['image_url']): ?>
            <div class="card-image">
              <img src="<?php echo htmlspecialchars($bulletin['image_url']); ?>" alt="<?php echo htmlspecialchars($bulletin['title']); ?>" onerror="this.parentElement.innerHTML='<div class=\'card-image-placeholder\'><i class=\'fas <?php echo $icon; ?>\'></i></div>';">
            </div>
          <?php else: ?>
            <div class="card-image">
              <div class="card-image-placeholder">
                <i class="fas <?php echo $icon; ?>"></i>
              </div>
            </div>
          <?php endif; ?>
          <div class="card-content">
            <h2 class="card-title"><?php echo htmlspecialchars($bulletin['title']); ?></h2>
            <?php if ($date_range): ?>
              <div class="card-date"><?php echo $date_range; ?></div>
            <?php endif; ?>
            <span class="card-type-badge" data-type="<?php echo htmlspecialchars($type_code); ?>">
              <?php echo htmlspecialchars($bulletin['type_name'] ?: ucfirst($type_code)); ?>
            </span>
            <p class="card-desc"><?php echo nl2br(htmlspecialchars(mb_substr($bulletin['content'], 0, 200))); ?><?php echo mb_strlen($bulletin['content']) > 200 ? '...' : ''; ?></p>
            <div class="card-meta">
              <div class="card-meta-item">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($bulletin['author_name'] ?: $bulletin['author_username'] ?: '未知'); ?></span>
              </div>
              <div class="card-meta-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo date('Y/m/d', strtotime($bulletin['created_at'])); ?></span>
              </div>
              <?php if ($bulletin['view_count'] > 0): ?>
              <div class="card-meta-item">
                <i class="fas fa-eye"></i>
                <span><?php echo $bulletin['view_count']; ?> 次瀏覽</span>
              </div>
              <?php endif; ?>
            </div>
            <div class="card-footer">
              <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="bulletin_board_detail.php?id=<?php echo $bulletin['id']; ?>" class="btn" style="text-decoration: none; display: inline-block;">
                  查看詳細資訊
                </a>
                <button class="btn" onclick="showBulletinFiles(<?php echo $bulletin['id']; ?>)" style="background: #10b981;">
                  查看相關文件
                </button>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>

  <script>
    const grid = document.getElementById('grid');
    const search = document.getElementById('search');
    const filterType = document.getElementById('filter-type');

    function applyFilters() {
      const q = (search.value || '').toLowerCase().trim();
      const type = filterType.value;
      let visibleCount = 0;

      grid.querySelectorAll('.card').forEach(card => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';
        const matchText = !q || title.includes(q) || desc.includes(q);
        const matchType = type === 'all' || card.dataset.type === type;

        if (matchText && matchType) {
          card.style.display = '';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // 顯示空狀態（如果需要）
      if (visibleCount === 0) {
        showEmptyState();
      } else {
        hideEmptyState();
      }
    }

    function showEmptyState() {
      let emptyState = document.querySelector('.empty-state');
      if (!emptyState) {
        emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = '<i class="fas fa-inbox"></i><p>找不到符合條件的公告</p>';
        grid.appendChild(emptyState);
      }
    }

    function hideEmptyState() {
      const emptyState = document.querySelector('.empty-state');
      if (emptyState) {
        emptyState.remove();
      }
    }

    search.addEventListener('input', applyFilters);
    filterType.addEventListener('change', applyFilters);
    applyFilters();

    // 顯示相關文件
    function showBulletinFiles(bulletinId) {
      fetch(`api/get_bulletin_files.php?id=${bulletinId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            let content = '<div style="max-width: 800px; padding: 20px;">';
            content += '<h2 style="margin-top: 0; margin-bottom: 20px;">相關文件</h2>';
            
            if (data.urls && data.urls.length > 0) {
              content += '<h3 style="font-size: 18px; font-weight: 600; margin: 16px 0 8px;">相關連結</h3>';
              content += '<ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">';
              data.urls.forEach(url => {
                content += `<li style="padding: 12px; margin-bottom: 8px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">`;
                content += `<a href="${url.url}" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600;">`;
                content += `<i class="fas fa-external-link-alt"></i> ${url.title || url.url}`;
                content += `</a></li>`;
              });
              content += '</ul>';
            }
            
            if (data.files && data.files.length > 0) {
              content += '<h3 style="font-size: 18px; font-weight: 600; margin: 16px 0 8px;">相關檔案</h3>';
              content += '<ul style="list-style: none; padding: 0; margin: 0;">';
              data.files.forEach(file => {
                const fileSize = (file.file_size / 1024).toFixed(2);
                content += `<li style="padding: 12px; margin-bottom: 8px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">`;
                content += `<a href="download_bulletin_file.php?id=${file.id}" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600;">`;
                content += `<i class="fas fa-file-download"></i> ${file.original_filename}`;
                content += `</a>`;
                content += `<span style="font-size: 14px; color: #6b7280;">${fileSize} KB</span>`;
                content += `</li>`;
              });
              content += '</ul>';
            }
            
            if ((!data.urls || data.urls.length === 0) && (!data.files || data.files.length === 0)) {
              content += '<p style="color: #6b7280; text-align: center; padding: 40px 0;">目前沒有相關文件</p>';
            }
            
            content += '</div>';
            
            // 顯示 modal
            const modal = document.createElement('div');
            modal.className = 'bulletin-files-modal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = 'background: white; border-radius: 12px; max-width: 900px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;';
            modalContent.innerHTML = `
              <button class="modal-close-btn">×</button>
              ${content}
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // 關閉按鈕事件
            const closeBtn = modalContent.querySelector('.modal-close-btn');
            closeBtn.addEventListener('click', function() {
              modal.remove();
            });
            
            // 點擊背景關閉
            modal.addEventListener('click', function(e) {
              if (e.target === modal) {
                modal.remove();
              }
            });
          } else {
            alert('無法載入相關文件：' + (data.message || '未知錯誤'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('載入相關文件時發生錯誤');
        });
    }
  </script>
</body>
</html>