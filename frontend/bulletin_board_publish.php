<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';
require_once 'share/header.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到首頁
if (!$isLoggedIn) {
    header("Location: index.php");
    exit;
}

// 檢查角色：只有主任、科助、行政人員可以發布公告
$user_role = $_SESSION['role'] ?? '';
$is_teacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA');
$is_director = ($user_role === 'DI');
$is_assistant = ($user_role === 'AS' || $user_role === '科助');
$is_staff = ($user_role === 'STA' || $user_role === '學校行政人員');
$allowed = $is_director || $is_assistant || $is_staff;

if (!$allowed) {
    header("Location: index.php");
    exit;
}

// 獲取 user_id
$username = $_SESSION['username'] ?? '';
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

// 如果沒有 user_id，從資料庫查詢
if (!$user_id && $username) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>發布公告 - 康寧大學招生平台</title>
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
      --success: #10b981;
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

    .page-header {
      text-align: center;
      margin-bottom: 40px;
      padding: 40px 20px;
      background: linear-gradient(135deg, rgba(122, 201, 199, 0.1) 0%, rgba(149, 109, 189, 0.1) 100%);
      border-radius: 20px;
      border: 1px solid rgba(102, 126, 234, 0.1);
    }

    .page-header h1 {
      margin: 0 0 16px;
      font-size: 44px;
      font-weight: 700;
      background: var(--accent-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .page-header p {
      margin: 0;
      font-size: 18px;
      color: var(--muted);
    }

    .form-container {
      background: var(--panel);
      border-radius: var(--radius);
      box-shadow: 0 4px 12px var(--shadow);
      border: 1px solid var(--border);
      padding: 40px;
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text);
      font-size: 16px;
    }

    .form-group label .required {
      color: var(--danger);
      margin-left: 4px;
    }

    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="url"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      background: var(--panel);
      color: var(--text);
      font-size: 14px;
      font-family: inherit;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
      min-height: 150px;
      resize: vertical;
    }

    .form-group .help-text {
      margin-top: 6px;
      font-size: 14px;
      color: var(--muted);
    }

    .form-actions {
      display: flex;
      gap: 16px;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }

    .btn {
      padding: 12px 24px;
      border-radius: var(--radius);
      border: none;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      transition: all 0.3s ease;
      flex: 1;
    }

    .btn-primary {
      background: #667eea;
      color: white;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .btn-primary:hover {
      background: #5a6fd8;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
      background: var(--border);
      color: var(--text);
    }

    .btn-secondary:hover {
      background: #d1d5db;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .message {
      padding: 16px;
      border-radius: var(--radius);
      margin-bottom: 24px;
      display: none;
    }

    .message.success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #10b981;
    }

    .message.error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #dc2626;
    }

    .message.show {
      display: block;
    }

    /* URL 和檔案輸入區塊樣式 */
    .optional-badge {
      display: inline-block;
      padding: 2px 8px;
      margin-left: 8px;
      background: #e5e7eb;
      color: var(--muted);
      border-radius: 4px;
      font-size: 13px;
      font-weight: 500;
    }

    #urls-container, #files-container {
      margin-bottom: 12px;
    }

    .url-item, .file-item {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
      padding: 12px;
      background: #f9fafb;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      transition: all 0.3s ease;
    }

    .url-item:hover, .file-item:hover {
      background: #f3f4f6;
      border-color: var(--accent);
    }

    .url-inputs {
      display: flex;
      gap: 12px;
      flex: 1;
      flex-wrap: wrap;
    }

    .url-input, .url-title-input {
      flex: 1;
      min-width: 200px;
      padding: 10px 14px;
      border: 2px solid var(--border);
      border-radius: 8px;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .url-input:focus, .url-title-input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .file-input {
      flex: 1;
      padding: 8px;
      border: 2px solid var(--border);
      border-radius: 8px;
      font-size: 16px;
      background: white;
      cursor: pointer;
    }

    .file-input:hover {
      border-color: var(--accent);
    }

    .btn-remove-url, .btn-remove-file {
      padding: 4px 6px;
      background: var(--danger);
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      transition: all 0.3s ease;
      white-space: nowrap;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 28px;
      height: 26px;
      flex-shrink: 0;
    }

    .btn-remove-url:hover, .btn-remove-file:hover {
      background: #b91c1c;
      transform: scale(1.1);
    }


    .btn-add-item {
      padding: 10px 20px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: var(--radius);
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }

    .btn-add-item:hover {
      background: var(--accent-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-add-item i {
      font-size: 12px;
    }

    @media (max-width: 768px) {
      .url-inputs {
        flex-direction: column;
      }

      .url-input, .url-title-input {
        min-width: 100%;
      }

      .url-item, .file-item {
        flex-direction: column;
        align-items: stretch;
      }

      .btn-remove-url, .btn-remove-file {
        width: 100%;
        justify-content: center;
      }
    }

    @media (max-width: 768px) {
      .page-container {
        padding: 20px 16px;
      }

      .form-container {
        padding: 24px 20px;
      }

      .form-actions {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>

  <div class="page-container">
    <!-- 頁面標題 -->
    <div class="page-header">
      <h1>發布公告</h1>
      <p>填寫以下資訊以發布新的招生公告</p>
    </div>

    <!-- 訊息顯示區域 -->
    <div id="message" class="message"></div>

    <!-- 表單容器 -->
    <div class="form-container">
      <form id="bulletinForm">
        <!-- 公告標題 -->
        <div class="form-group">
          <label for="title">公告標題 <span class="required">*</span></label>
          <input type="text" id="title" name="title" required placeholder="例如：112 學年度入學考試報名開始">
        </div>

        <!-- 公告內容 -->
        <div class="form-group">
          <label for="content">公告內容/描述 <span class="required">*</span></label>
          <textarea id="content" name="content" required placeholder="請詳細描述公告內容..."></textarea>
        </div>

        <!-- 公告類型 -->
        <div class="form-group">
          <label for="type_code">公告類型 <span class="required">*</span></label>
          <select id="type_code" name="type_code" required>
            <option value="">請選擇公告類型</option>
            <option value="exam">考試資訊</option>
            <option value="interview">面試通知</option>
            <option value="result">錄取結果</option>
            <option value="general">一般公告</option>
          </select>
        </div>

        <!-- 公告狀態 -->
        <div class="form-group">
          <label for="status_code">公告狀態 <span class="required">*</span></label>
          <select id="status_code" name="status_code" required>
            <option value="draft">草稿</option>
            <option value="published" selected>已發布</option>
            <option value="archived">已歸檔</option>
          </select>
          <div class="help-text">選擇「已發布」後，公告將立即顯示在公告欄中</div>
        </div>

        <!-- 開始日期 -->
        <div class="form-group">
          <label for="start_date">公告開始日期</label>
          <input type="date" id="start_date" name="start_date" min="<?php echo date('Y-m-d'); ?>">
          <div class="help-text">可選，公告開始生效的日期</div>
        </div>

        <!-- 結束日期 -->
        <div class="form-group">
          <label for="end_date">公告結束日期</label>
          <input type="date" id="end_date" name="end_date" min="<?php echo date('Y-m-d'); ?>">
          <div class="help-text">可選，公告結束的日期</div>
        </div>

        <!-- 圖片URL -->
        <div class="form-group">
          <label for="image_url">公告圖片URL</label>
          <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
          <div class="help-text">可選，公告相關圖片的完整網址</div>
        </div>

        <!-- 相關連結URL（多個） -->
        <div class="form-group">
          <label>相關連結URL <span class="optional-badge">可選</span></label>
          <div id="urls-container">
            <div class="url-item">
              <div class="url-inputs">
                <input type="url" name="urls[]" placeholder="https://example.com" class="url-input">
                <input type="text" name="url_titles[]" placeholder="連結標題（可選）" class="url-title-input">
              </div>
              <button type="button" class="btn-remove-url" title="移除此連結">刪</button>
            </div>
          </div>
          <button type="button" id="add-url-btn" class="btn-add-item">
            <i class="fas fa-plus"></i> 新增連結
          </button>
          <div class="help-text">可新增多個與公告相關的網頁連結，每個連結可設定自訂標題</div>
        </div>

        <!-- 檔案上傳 -->
        <div class="form-group">
          <label>相關檔案 <span class="optional-badge">可選</span></label>
          <div id="files-container">
            <div class="file-item">
              <input type="file" name="files[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" class="file-input">
              <button type="button" class="btn-remove-file" title="移除此檔案">刪</button>
            </div>
          </div>
          <button type="button" id="add-file-btn" class="btn-add-item">
            <i class="fas fa-plus"></i> 新增檔案
          </button>
          <div class="help-text">可上傳多個相關檔案（PDF、Word、Excel、圖片等），每個檔案最大 10MB</div>
        </div>

        <!-- 隱藏欄位：user_id -->
        <input type="hidden" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user_id ?? ''); ?>">

        <!-- 表單操作按鈕 -->
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="window.location.href='bulletin_board.php'">取消</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">發布公告</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const form = document.getElementById('bulletinForm');
    const messageDiv = document.getElementById('message');
    const submitBtn = document.getElementById('submitBtn');

    // 顯示訊息
    function showMessage(text, type) {
      messageDiv.textContent = text;
      messageDiv.className = `message ${type} show`;
      setTimeout(() => {
        messageDiv.classList.remove('show');
      }, 5000);
    }

    // 驗證表單並滾動到第一個錯誤欄位
    function validateAndScrollToError() {
      const requiredFields = [
        { id: 'title', name: '公告標題' },
        { id: 'content', name: '公告內容' },
        { id: 'type_code', name: '公告類型' },
        { id: 'status_code', name: '公告狀態' }
      ];

      for (let field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value || element.value.trim() === '') {
          // 滾動到該欄位
          element.scrollIntoView({ behavior: 'smooth', block: 'center' });
          // 聚焦並高亮
          element.focus();
          element.style.borderColor = '#dc2626';
          element.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
          
          // 3秒後恢復樣式
          setTimeout(() => {
            element.style.borderColor = '';
            element.style.boxShadow = '';
          }, 3000);
          
          showMessage(`請填寫「${field.name}」`, 'error');
          return false;
        }
      }
      return true;
    }

    // 表單提交處理
    form.addEventListener('submit', async function(e) {
      e.preventDefault();

      // 先進行客戶端驗證
      if (!validateAndScrollToError()) {
        return;
      }

      // 禁用提交按鈕
      submitBtn.disabled = true;
      submitBtn.textContent = '發布中...';

      // 收集表單資料
      const formData = new FormData(form);

      try {
        const response = await fetch('api/publish_bulletin.php', {
          method: 'POST',
          body: formData,
          // 不要設定 Content-Type，讓瀏覽器自動設定（包含 boundary）
        });

        const data = await response.json();

        if (data.success) {
          showMessage(data.message || '公告發布成功！', 'success');
          // 3秒後跳轉到公告欄
          setTimeout(() => {
            window.location.href = 'bulletin_board.php';
          }, 2000);
        } else {
          showMessage(data.message || '發布失敗，請稍後再試', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = '發布公告';
          
          // 如果錯誤訊息包含欄位名稱，嘗試滾動到該欄位
          const errorMessage = data.message || '';
          const fieldMap = {
            '標題': 'title',
            '內容': 'content',
            '類型': 'type_code',
            '狀態': 'status_code'
          };
          
          for (let [keyword, fieldId] of Object.entries(fieldMap)) {
            if (errorMessage.includes(keyword)) {
              const element = document.getElementById(fieldId);
              if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
                element.style.borderColor = '#dc2626';
                element.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
                setTimeout(() => {
                  element.style.borderColor = '';
                  element.style.boxShadow = '';
                }, 3000);
              }
              break;
            }
          }
        }
      } catch (error) {
        console.error('Error:', error);
        showMessage('發布失敗，請檢查網路連線', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = '發布公告';
      }
    });

    // 清除錯誤樣式
    const requiredFields = ['title', 'content', 'type_code', 'status_code'];
    requiredFields.forEach(fieldId => {
      const element = document.getElementById(fieldId);
      if (element) {
        element.addEventListener('focus', function() {
          this.style.borderColor = '';
          this.style.boxShadow = '';
        });
        element.addEventListener('input', function() {
          this.style.borderColor = '';
          this.style.boxShadow = '';
        });
      }
    });

    // 日期驗證：結束日期不能早於開始日期
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const today = new Date().toISOString().split('T')[0]; // 今天的日期

    startDateInput.addEventListener('change', function() {
      // 當開始日期改變時，更新結束日期的最小值
      if (this.value) {
        endDateInput.min = this.value;
        // 如果結束日期早於開始日期，清空結束日期
        if (endDateInput.value && endDateInput.value < this.value) {
          showMessage('結束日期不能早於開始日期', 'error');
          endDateInput.value = '';
        }
      } else {
        endDateInput.min = today;
      }
    });

    endDateInput.addEventListener('change', function() {
      if (startDateInput.value && this.value < startDateInput.value) {
        showMessage('結束日期不能早於開始日期', 'error');
        this.value = '';
      }
    });

    // 新增連結功能
    const addUrlBtn = document.getElementById('add-url-btn');
    const urlsContainer = document.getElementById('urls-container');

    addUrlBtn.addEventListener('click', function() {
      const urlItem = document.createElement('div');
      urlItem.className = 'url-item';
      urlItem.innerHTML = `
        <div class="url-inputs">
          <input type="url" name="urls[]" placeholder="https://example.com" class="url-input">
          <input type="text" name="url_titles[]" placeholder="連結標題（可選）" class="url-title-input">
        </div>
        <button type="button" class="btn-remove-url" title="移除此連結">刪</button>
      `;
      urlsContainer.appendChild(urlItem);
      // 聚焦到新添加的 URL 輸入框
      urlItem.querySelector('.url-input').focus();
    });

    // 移除連結功能
    urlsContainer.addEventListener('click', function(e) {
      if (e.target.closest('.btn-remove-url')) {
        const items = urlsContainer.querySelectorAll('.url-item');
        const itemToRemove = e.target.closest('.url-item');
        
        if (items.length > 1) {
          // 添加淡出動畫
          itemToRemove.style.transition = 'opacity 0.3s ease';
          itemToRemove.style.opacity = '0';
          setTimeout(() => {
            itemToRemove.remove();
          }, 300);
        } else {
          // 如果只剩一個，清空內容而不是移除
          const urlInput = itemToRemove.querySelector('input[type="url"]');
          const titleInput = itemToRemove.querySelector('input[type="text"]');
          if (urlInput) urlInput.value = '';
          if (titleInput) titleInput.value = '';
          urlInput?.focus();
        }
      }
    });

    // 新增檔案功能
    const addFileBtn = document.getElementById('add-file-btn');
    const filesContainer = document.getElementById('files-container');

    addFileBtn.addEventListener('click', function() {
      const fileItem = document.createElement('div');
      fileItem.className = 'file-item';
      fileItem.innerHTML = `
        <input type="file" name="files[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" class="file-input">
        <button type="button" class="btn-remove-file" title="移除此檔案">刪</button>
      `;
      filesContainer.appendChild(fileItem);
    });

    // 移除檔案功能
    filesContainer.addEventListener('click', function(e) {
      if (e.target.closest('.btn-remove-file')) {
        const items = filesContainer.querySelectorAll('.file-item');
        const itemToRemove = e.target.closest('.file-item');
        
        if (items.length > 1) {
          // 添加淡出動畫
          itemToRemove.style.transition = 'opacity 0.3s ease';
          itemToRemove.style.opacity = '0';
          setTimeout(() => {
            itemToRemove.remove();
          }, 300);
        } else {
          // 如果只剩一個，清空內容而不是移除
          const fileInput = itemToRemove.querySelector('input[type="file"]');
          if (fileInput) fileInput.value = '';
        }
      }
    });

    // 顯示已選擇的檔案名稱
    filesContainer.addEventListener('change', function(e) {
      if (e.target.type === 'file' && e.target.files.length > 0) {
        const fileItem = e.target.closest('.file-item');
        const fileName = e.target.files[0].name;
        const fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2); // MB
        
        // 檢查檔案大小
        if (e.target.files[0].size > 10 * 1024 * 1024) {
          showMessage(`檔案 "${fileName}" 超過 10MB 限制，請選擇較小的檔案`, 'error');
          e.target.value = '';
          return;
        }
        
        // 顯示檔案資訊
        let fileInfo = fileItem.querySelector('.file-info');
        if (!fileInfo) {
          fileInfo = document.createElement('div');
          fileInfo.className = 'file-info';
          fileInfo.style.cssText = 'font-size: 14px; color: var(--muted); margin-top: 4px;';
          fileItem.appendChild(fileInfo);
        }
        fileInfo.textContent = `已選擇：${fileName} (${fileSize} MB)`;
      }
    });
  </script>
</body>
</html>
