<?php
// 檔案上傳管理（教師）
require_once 'session_config.php';
require_once 'config.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$role = $_SESSION['role'] ?? '';
$allowedRoles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI', 'IM'];

if (!$isLoggedIn || !in_array($role, $allowedRoles, true)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>檔案上傳 - 康寧大學招生平台</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #1890ff;
      --text-color: #262626;
      --text-secondary-color: #8c8c8c;
      --border-color: #f0f0f0;
      --background-color: #f0f2f5;
      --card-background-color: #fff;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      padding-top: 100px;
      background: var(--background-color);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Microsoft JhengHei';
      color: var(--text-color);
      overflow-x: hidden;
    }
    .container { max-width: 1400px; margin: 24px auto; padding: 24px; }
    .main-wrapper { max-width: 1100px; margin: 0 auto; width: 100%; }
    .page-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 16px; }
    .page-header { margin-bottom: 24px; }
    .page-title { color: var(--text-color); font-size: 24px; font-weight: 600; margin-bottom: 8px; }
    .page-subtitle { color: var(--text-secondary-color); font-size: 14px; }

    .upload-section {
      background: var(--card-background-color);
      border-radius: 8px;
      padding: 24px;
      margin-bottom: 24px;
      border: 2px dashed #d9d9d9;
      text-align: center;
      transition: all 0.3s;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
      border: 1px solid var(--border-color);
    }
    .upload-section.dragover { border-color: var(--primary-color); background: #e6f7ff; }
    .upload-icon { font-size: 48px; color: var(--primary-color); margin-bottom: 16px; }
    .upload-text { font-size: 16px; color: var(--text-color); margin-bottom: 16px; }
    .file-input-wrapper { position: relative; display: inline-block; }
    .file-input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    .upload-btn {
      background: var(--primary-color); color: white; padding: 8px 16px; border-radius: 4px;
      border: none; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s;
    }
    .upload-btn:hover { background: #40a9ff; transform: translateY(-1px); }

    .table-wrapper {
      background: var(--card-background-color);
      border-radius: 8px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
      border: 1px solid var(--border-color);
      margin-bottom: 24px;
      display: flex;
      flex-direction: column;
    }
    .table-container { overflow-x: auto; flex: 1; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 16px 24px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
    .table th:first-child, .table td:first-child { padding-left: 24px; }
    .table th { background: #fafafa; font-weight: 600; color: var(--text-color); }
    .table td { color: #595959; }
    .table tr:hover { background: #fafafa; }
    .file-name-cell { font-weight: 500; color: var(--text-color); word-break: break-all; }
    .file-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: nowrap; }
    .action-btn {
      padding: 4px 12px; border: 1px solid; border-radius: 4px; cursor: pointer; font-size: 14px;
      font-weight: 500; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 92px; height: 36px;
      white-space: nowrap;
    }
    .btn-download { background: #fff; color: #52c41a; border-color: #52c41a; }
    .btn-download:hover { background: #52c41a; color: white; }
    .btn-delete { background: #fff; color: #ff4d4f; border-color: #ff4d4f; }
    .btn-delete:hover { background: #ff4d4f; color: white; }
    .btn-preview { background: #fff; color: #fa8c16; border-color: #fa8c16; }
    .btn-preview:hover { background: #fa8c16; color: #fff; }

    .search-input { padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; width: 250px; transition: all 0.3s; }
    .search-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(24,144,255,0.2); }

    .empty-state { text-align: center; padding: 40px; color: var(--text-secondary-color); }
    .empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
    .loading { text-align: center; padding: 40px; color: var(--text-secondary-color); }

    .progress-bar { width: 100%; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden; margin-top: 16px; display: none; }
    .progress-fill { height: 100%; background: var(--primary-color); width: 0%; transition: width 0.3s; }

    .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; display: none; font-size: 14px; }
    .alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
    .alert-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }

    .pagination { padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); background: #fafafa; }
    .pagination-info { display: flex; align-items: center; gap: 16px; color: var(--text-secondary-color); font-size: 14px; }
    .pagination-controls { display: flex; align-items: center; gap: 8px; }
    .pagination select { padding: 6px 12px; border: 1px solid #d9d9d9; border-radius: 6px; font-size: 14px; background: #fff; cursor: pointer; }
    .pagination select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 2px rgba(24,144,255,0.2); }
    .pagination button { padding: 6px 12px; border: 1px solid #d9d9d9; background: #fff; color: #595959; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
    .pagination button:hover:not(:disabled) { border-color: var(--primary-color); color: var(--primary-color); }
    .pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
    .pagination button.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }

    /* 共享確認彈窗（固定在頁面最上方，避免被擋） */
    .confirm-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 20000;
      display: none;
      align-items: flex-start;
      justify-content: center;
      padding-top: 18px;
    }
    .confirm-modal {
      width: min(720px, calc(100vw - 24px));
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.18);
      overflow: hidden;
    }
    .confirm-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 16px;
      background: #f8f9fa;
      border-bottom: 1px solid #e9ecef;
      font-weight: 700;
    }
    .confirm-close {
      border: none;
      background: transparent;
      cursor: pointer;
      color: #666;
      font-size: 18px;
      line-height: 1;
      padding: 6px 8px;
    }
    .confirm-body { padding: 14px 16px; color: #333; font-size: 14px; line-height: 1.6; }
    .confirm-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 12px 16px 16px;
      border-top: 1px solid #f0f0f0;
      background: #fff;
    }
    .confirm-actions .btn {
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 700;
      border: 1px solid #d9d9d9;
      background: #fff;
      color: #333;
      white-space: nowrap;
    }
    .confirm-actions .btn.primary {
      border-color: var(--primary-color);
      background: var(--primary-color);
      color: #fff;
    }

    @media (max-width: 768px) {
      .container { padding: 16px; }
      .page-controls { flex-direction: column; align-items: stretch; }
      .search-input { width: 100%; }
      .table th, .table td { padding: 12px 16px; font-size: 12px; }
      .file-actions { flex-direction: column; gap: 4px; }
      .action-btn { width: 100%; padding: 6px 12px; font-size: 12px; }
    }
  </style>
</head>
<body>
<?php include("share/header.php"); ?>

<main>
  <div class="container">
    <div class="main-wrapper">
    <div class="page-header">
      <h1 class="page-title">檔案上傳管理</h1>
      <p class="page-subtitle">上傳和管理您的檔案（PPT、Word、圖片等），單個檔案限制 50GB</p>
    </div>
    
    <div id="alertContainer"></div>
    
    <div class="upload-section" id="uploadSection">
      <div class="upload-icon">
        <i class="fas fa-cloud-upload-alt"></i>
      </div>
      <div class="upload-text">
        拖放檔案到此處或點擊下方按鈕選擇檔案
      </div>
      <div class="file-input-wrapper">
        <input type="file" id="fileInput" class="file-input" multiple>
        <button class="upload-btn" onclick="document.getElementById('fileInput').click()">
          <i class="fas fa-upload"></i> 選擇檔案
        </button>
      </div>
      <div class="progress-bar" id="progressBar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
    </div>
    
    <div class="page-controls">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span id="viewLabel" style="color: var(--text-secondary-color); font-size: 14px;">我的檔案</span>
        <button class="upload-btn" onclick="loadFiles(true)" style="padding: 6px 12px; font-size: 12px;">
          <i class="fas fa-sync-alt"></i> 重新整理
        </button>
        <button id="sharedViewBtn" class="upload-btn" onclick="toggleSharedView()" style="padding: 6px 12px; font-size: 12px; background:#fff; color: var(--primary-color); border:1px solid var(--primary-color);">
          <i class="fas fa-share-alt"></i> 共享檔案
        </button>
      </div>
      <div class="table-search">
        <input type="text" id="searchInput" class="search-input" placeholder="搜尋檔案名稱...">
      </div>
    </div>
    
    <div class="table-wrapper">
      <div class="table-container">
        <div id="filesContainer">
          <div class="loading">載入中...</div>
        </div>
      </div>
      <div class="pagination" id="paginationContainer" style="display: none;">
        <div class="pagination-info">
          <span>每頁顯示：</span>
          <select id="itemsPerPage" onchange="changeItemsPerPage()">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">全部</option>
          </select>
          <span id="pageInfo">顯示第 <span id="currentRange">1-10</span> 筆，共 <span id="totalItems">0</span> 筆</span>
        </div>
        <div class="pagination-controls">
          <button id="prevPage" onclick="changePage(-1)" disabled>上一頁</button>
          <span id="pageNumbers"></span>
          <button id="nextPage" onclick="changePage(1)">下一頁</button>
        </div>
      </div>
    </div>
  </div>
  </div>
</main>

<!-- 共享/取消共享確認彈窗 -->
<div id="confirmOverlay" class="confirm-overlay" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="confirm-modal">
    <div class="confirm-header">
      <span id="confirmTitle">確認</span>
      <button class="confirm-close" type="button" onclick="hideConfirmModal()" aria-label="close">&times;</button>
    </div>
    <div class="confirm-body" id="confirmMessage"></div>
    <div class="confirm-actions">
      <button class="btn" type="button" onclick="hideConfirmModal(false)">否</button>
      <button class="btn primary" type="button" onclick="hideConfirmModal(true)">是</button>
    </div>
  </div>
</div>

<?php include("share/footer.php"); ?>

<script>
  let filesData = [];
  let allRows = [];
  let filteredRows = [];
  let currentPage = 1;
  let itemsPerPage = 10;
  let sortKey = 'upload_time';
  let sortDirection = 'desc';
  let currentSearch = '';
  let currentView = 'mine'; // mine | shared
  let _confirmResolver = null;
  
  document.addEventListener('DOMContentLoaded', function() {
    loadFiles();
    setupDragAndDrop();
    setupFileInput();
    setupSearch();
  });
  
  function setupDragAndDrop() {
    const uploadSection = document.getElementById('uploadSection');
    
    uploadSection.addEventListener('dragover', function(e) {
      e.preventDefault();
      uploadSection.classList.add('dragover');
    });
    
    uploadSection.addEventListener('dragleave', function(e) {
      e.preventDefault();
      uploadSection.classList.remove('dragover');
    });
    
    uploadSection.addEventListener('drop', function(e) {
      e.preventDefault();
      uploadSection.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        uploadFiles(files);
      }
    });
  }
  
  function setupFileInput() {
    const fileInput = document.getElementById('fileInput');
    fileInput.addEventListener('change', function(e) {
      if (e.target.files.length > 0) {
        uploadFiles(e.target.files);
      }
    });
  }
  
  async function uploadFiles(files) {
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const maxSize = 50 * 1024 * 1024 * 1024;
      if (file.size > maxSize) {
        showAlert('錯誤', `檔案 ${file.name} 超過 50GB 限制`, 'error');
        continue;
      }
      
      const formData = new FormData();
      formData.append('file', file);
      
      progressBar.style.display = 'block';
      progressFill.style.width = '0%';
      
      try {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
          if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressFill.style.width = percent + '%';
          }
        });
        
        xhr.addEventListener('load', function() {
          if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
              showAlert('成功', `檔案 ${file.name} 上傳成功`, 'success');
              loadFiles();
            } else {
              showAlert('錯誤', response.message || '上傳失敗', 'error');
            }
          } else {
            let response = {};
            try { response = JSON.parse(xhr.responseText); } catch {}
            showAlert('錯誤', response.message || '上傳失敗', 'error');
          }
          progressBar.style.display = 'none';
          progressFill.style.width = '0%';
        });
        
        xhr.addEventListener('error', function() {
          showAlert('錯誤', '上傳失敗，請稍後再試', 'error');
          progressBar.style.display = 'none';
          progressFill.style.width = '0%';
        });
        
        xhr.open('POST', 'api/teacher_files_api.php');
        xhr.send(formData);
        
      } catch (error) {
        console.error('Upload error:', error);
        showAlert('錯誤', '上傳失敗：' + error.message, 'error');
        progressBar.style.display = 'none';
        progressFill.style.width = '0%';
      }
    }
    document.getElementById('fileInput').value = '';
  }
  
  async function loadFiles(resetSearch = false) {
    if (resetSearch) {
      currentSearch = '';
      const searchInput = document.getElementById('searchInput');
      if (searchInput) searchInput.value = '';
    }

    const container = document.getElementById('filesContainer');
    container.innerHTML = '<div class="loading">載入中...</div>';
    
    try {
      const url = currentView === 'shared'
        ? 'api/teacher_files_api.php?scope=shared'
        : 'api/teacher_files_api.php';
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.success) {
        filesData = data.files || [];
        renderTable();
      } else {
        container.innerHTML = `
          <div class="empty-state">
            <p>載入失敗：${data.message || '未知錯誤'}</p>
          </div>
        `;
      }
    } catch (error) {
      console.error('Load files error:', error);
      container.innerHTML = `
        <div class="empty-state">
          <p>載入失敗，請稍後再試</p>
        </div>
      `;
    }
  }
  
  async function deleteFile(fileId, filename) {
    if (!confirm(`確定要刪除檔案「${filename}」嗎？此操作無法復原。`)) {
      return;
    }
    
    try {
      const response = await fetch('api/teacher_files_api.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: fileId })
      });
      
      const data = await response.json();
      
      if (data.success) {
        showAlert('成功', '檔案已刪除', 'success');
        loadFiles();
      } else {
        showAlert('錯誤', data.message || '刪除失敗', 'error');
      }
    } catch (error) {
      console.error('Delete error:', error);
      showAlert('錯誤', '刪除失敗，請稍後再試', 'error');
    }
  }
  
  function showAlert(title, message, alertType) {
    const container = document.getElementById('alertContainer');
    const alertClass = alertType === 'success' ? 'alert-success' : 'alert-error';
    const icon = alertType === 'success' ? 'check-circle' : 'exclamation-circle';
    
    container.innerHTML = `
      <div class="alert ${alertClass}">
        <i class="fas fa-${icon}"></i> ${escapeHtml(message)}
      </div>
    `;
    
    const alertDiv = container.querySelector('.alert');
    alertDiv.style.display = 'block';
    
    setTimeout(() => {
      alertDiv.style.display = 'none';
    }, 5000);
  }
  
  function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function toggleSharedView() {
    currentView = (currentView === 'shared') ? 'mine' : 'shared';
    const label = document.getElementById('viewLabel');
    const btn = document.getElementById('sharedViewBtn');
    if (label) label.textContent = currentView === 'shared' ? '共享檔案' : '我的檔案';
    if (btn) {
      btn.innerHTML = currentView === 'shared'
        ? '<i class="fas fa-user"></i> 我的檔案'
        : '<i class="fas fa-share-alt"></i> 共享檔案';
    }
    currentPage = 1;
    loadFiles(true);
  }

  function showConfirmModal(message, title = '確認') {
    const overlay = document.getElementById('confirmOverlay');
    const msgEl = document.getElementById('confirmMessage');
    const titleEl = document.getElementById('confirmTitle');
    if (!overlay || !msgEl || !titleEl) return Promise.resolve(false);

    titleEl.textContent = title;
    msgEl.textContent = message;

    // 讓彈窗顯示在網頁最上方，並避免被 navbar 擋住
    window.scrollTo({ top: 0, behavior: 'smooth' });

    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    return new Promise(resolve => { _confirmResolver = resolve; });
  }

  function hideConfirmModal(result) {
    const overlay = document.getElementById('confirmOverlay');
    if (overlay) {
      overlay.style.display = 'none';
      overlay.setAttribute('aria-hidden', 'true');
    }
    if (typeof _confirmResolver === 'function') {
      const r = _confirmResolver;
      _confirmResolver = null;
      r(!!result);
    }
  }

  async function toggleShare(fileId, filename, nextShared) {
    const actionText = nextShared ? '共享' : '取消共享';
    const ok = await showConfirmModal(`是否要${actionText}這個檔案(${filename})`, `${actionText}確認`);
    if (!ok) return;

    try {
      const response = await fetch('api/teacher_files_api.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_id: fileId, shared: !!nextShared })
      });
      const data = await response.json();
      if (data.success) {
        showAlert('成功', data.message || (nextShared ? '已共享' : '已取消共享'), 'success');
        loadFiles();
      } else {
        showAlert('錯誤', data.message || '更新失敗', 'error');
      }
    } catch (error) {
      console.error('Share error:', error);
      showAlert('錯誤', '更新失敗，請稍後再試', 'error');
    }
  }

  function renderTable() {
    const container = document.getElementById('filesContainer');
    if (!container) return;

    const filteredData = filesData.filter(file => {
      if (!currentSearch) return true;
      const t = currentSearch.toLowerCase();
      return (file.original_filename || '').toLowerCase().includes(t);
    });

    const toDate = v => new Date(v);
    filteredData.sort((a, b) => {
      let result = 0;
      if (sortKey === 'upload_time') {
        result = toDate(a.upload_time) - toDate(b.upload_time);
      }
      return sortDirection === 'asc' ? result : -result;
    });

    if (filteredData.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-folder-open"></i>
          </div>
          <p>${filesData.length > 0 ? '沒有符合搜尋的檔案' : '目前沒有上傳的檔案'}</p>
        </div>
      `;
      const pager = document.getElementById('paginationContainer');
      if (pager) pager.style.display = 'none';
      return;
    }

    const sortIcon = sortDirection === 'asc' ? '▲' : '▼';

    const canManage = currentView === 'mine';

    container.innerHTML = `
      <table class="table" id="filesTable">
        <thead>
          <tr>
            <th style="width: 5%;"><i class="fas fa-file"></i></th>
            <th style="width: 34%;">檔案名稱</th>
            <th style="width: 15%;">檔案大小</th>
            <th style="width: 20%; cursor: pointer;" onclick="toggleSort('upload_time')">
              上傳時間 <span id="sortIcon" style="font-size:12px; color: var(--text-secondary-color);">${sortIcon}</span>
            </th>
            <th style="width: 26%; text-align: center;">操作</th>
          </tr>
        </thead>
        <tbody>
          ${filteredData.map(file => `
            <tr>
              <td><i class="fas fa-file" style="color: var(--primary-color);"></i></td>
              <td class="file-name-cell">${escapeHtml(file.original_filename)}</td>
              <td>${file.file_size_formatted}</td>
              <td>${formatDateTime(file.upload_time)}</td>
              <td>
                <div class="file-actions">
                  <a href="preview_teacher_file.php?id=${file.id}" class="action-btn btn-preview" target="_blank" rel="noopener">
                    <i class="fas fa-eye"></i> 預覽
                  </a>
                  ${canManage ? `
                    <button class="action-btn btn-download" style="border-color:#1890ff; color:#1890ff;" onclick="toggleShare(${file.id}, '${escapeHtml(file.original_filename)}', ${file.is_shared ? 'false' : 'true'})">
                      <i class="fas fa-share-alt"></i> ${file.is_shared ? '取消共享' : '共享'}
                    </button>
                  ` : ''}
                  <a href="download_teacher_file.php?id=${file.id}" class="action-btn btn-download" download>
                    <i class="fas fa-download"></i> 下載
                  </a>
                  ${canManage ? `
                    <button class="action-btn btn-delete" onclick="deleteFile(${file.id}, '${escapeHtml(file.original_filename)}')">
                      <i class="fas fa-trash"></i> 刪除
                    </button>
                  ` : ''}
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    setTimeout(() => {
      initTableRows();
      updatePagination();
      const pager = document.getElementById('paginationContainer');
      if (pager) pager.style.display = 'flex';
    }, 50);
  }

  function toggleSort(key) {
    if (sortKey === key) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortKey = key;
      sortDirection = 'desc';
    }
    currentPage = 1;
    renderTable();
  }
  
  function initTableRows() {
    const table = document.getElementById('filesTable');
    if (!table) return;
    const tbody = table.getElementsByTagName('tbody')[0];
    if (!tbody) return;
    allRows = Array.from(tbody.getElementsByTagName('tr'));
    filteredRows = allRows;
  }
  
  function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    searchInput.addEventListener('keyup', function() {
      currentSearch = searchInput.value.trim();
      currentPage = 1;
      renderTable();
    });
  }
  
  function changeItemsPerPage() {
    const selectValue = document.getElementById('itemsPerPage').value;
    itemsPerPage = selectValue === 'all' ? 'all' : parseInt(selectValue);
    currentPage = 1;
    updatePagination();
  }
  
  function changePage(direction) {
    const totalItems = filteredRows.length;
    let pageSize;
    if (itemsPerPage === 'all') {
      pageSize = totalItems;
    } else {
      pageSize = typeof itemsPerPage === 'number' ? itemsPerPage : parseInt(itemsPerPage);
    }
    const totalPages = pageSize >= totalItems ? 1 : Math.ceil(totalItems / pageSize);
    
    currentPage += direction;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    updatePagination();
  }
  
  function goToPage(page) {
    currentPage = page;
    updatePagination();
  }
  
  function updatePagination() {
    const totalItems = filteredRows.length;
    let pageSize;
    if (itemsPerPage === 'all') {
      pageSize = totalItems;
    } else {
      pageSize = typeof itemsPerPage === 'number' ? itemsPerPage : parseInt(itemsPerPage);
      if (isNaN(pageSize) || pageSize <= 0) {
        pageSize = 10;
        itemsPerPage = 10;
      }
    }
    const totalPages = pageSize >= totalItems ? 1 : Math.ceil(totalItems / pageSize);
    allRows.forEach(row => row.style.display = 'none');
    if (itemsPerPage === 'all' || pageSize >= totalItems) {
      filteredRows.forEach(row => row.style.display = '');
      document.getElementById('currentRange').textContent = totalItems > 0 ? `1-${totalItems}` : '0-0';
    } else {
      const start = (currentPage - 1) * pageSize;
      const end = Math.min(start + pageSize, totalItems);
      for (let i = start; i < end; i++) {
        if (filteredRows[i]) filteredRows[i].style.display = '';
      }
      document.getElementById('currentRange').textContent = totalItems > 0 ? `${start + 1}-${end}` : '0-0';
    }
    document.getElementById('totalItems').textContent = totalItems;
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
    updatePageNumbers(totalPages);
  }
  
  function updatePageNumbers(totalPages) {
    const pageNumbers = document.getElementById('pageNumbers');
    if (!pageNumbers) return;
    pageNumbers.innerHTML = '';
    if (totalPages >= 1) {
      const pagesToShow = totalPages === 1 ? [1] : Array.from({length: totalPages}, (_, i) => i + 1);
      for (let i of pagesToShow) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.onclick = () => goToPage(i);
        if (i === currentPage) btn.classList.add('active');
        pageNumbers.appendChild(btn);
      }
    }
  }
</script>

</body>
</html>



