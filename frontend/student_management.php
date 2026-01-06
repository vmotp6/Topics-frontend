<?php
require_once 'session_config.php';
require_once 'config.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$allowed_roles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI'];
$can_access = $isLoggedIn && in_array($user_role, $allowed_roles, true);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>學生管理 - 康寧大學招生平台</title>
  <style>
    .sm-page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 16px 40px;
      box-sizing: border-box;
    }

    .sm-card {
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06);
      padding: 16px;
    }

    .sm-title {
      margin: 0 0 8px 0;
      font-size: 22px;
      font-weight: 700;
      color: #003366;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .sm-subtitle {
      margin: 0 0 16px 0;
      color: #666;
      font-size: 14px;
      line-height: 1.5;
    }

    /* ==== Students block styles (from index.php, slightly de-scoped) ==== */
    .student-stats { display: flex; gap: 14px; margin: 10px 0 14px; }
    .stat-card {
      background: linear-gradient(90deg, #68bbb9);
      color: #fff;
      padding: 14px 14px;
      border-radius: 10px;
      text-align: center;
      flex: 1;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .stat-number { font-size: 32px; font-weight: 800; margin-bottom: 4px; }
    .stat-label { font-size: 14px; opacity: 0.92; }

    .student-list-container { background: #f8f9fa; border-radius: 10px; padding: 12px; }
    .search-container { margin-bottom: 10px; }
    .search-input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid #ddd;
      border-radius: 10px;
      font-size: 16px;
      transition: border-color 0.2s;
      box-sizing: border-box;
    }
    .search-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .student-list { max-height: 520px; overflow-y: auto; }
    .student-item {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      padding: 18px;
      margin-bottom: 12px;
      transition: all 0.2s;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .student-item:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.10); transform: translateY(-1px); }
    .student-header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; gap: 10px; flex-wrap: wrap; }
    .student-name { font-size: 18px; font-weight: 700; color: #003366; margin: 0; }
    .student-identity { background:#e3f2fd; color:#1976d2; padding:4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .student-info { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px; }
    .info-item { display:flex; flex-direction: column; }
    .info-label { font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 700; }
    .info-value { font-size: 14px; color: #333; }
    .student-intentions { background: #f5f5f5; padding: 14px; border-radius: 10px; margin-bottom: 12px; }
    .intentions-title { font-size: 14px; font-weight: 800; color: #333; margin-bottom: 8px; }
    .intention-item { background: #fff; padding: 8px 12px; border-radius: 8px; margin-bottom: 6px; font-size: 13px; color: #555; }
    .student-actions { display:flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }
    .action-btn { padding: 8px 14px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 800; transition: all 0.15s; }
    .btn-contact { background: #28a745; color: #fff; }
    .btn-contact:hover { background: #218838; }
    .btn-notes { background: #17a2b8; color: #fff; }
    .btn-notes:hover { background: #138496; }
    .btn-view-logs { background: #6c757d; color: #fff; }
    .btn-view-logs:hover { background: #5a6268; }
    .loading { text-align:center; padding: 30px; color: #666; font-size: 16px; }
    .empty-state { text-align:center; padding: 30px; color: #666; }
    .empty-state i { font-size: 44px; margin-bottom: 14px; color: #ccc; }

    /* ==== Contact info card styles ==== */
    .contact-info-item {
      background: #fff;
      border-radius: 10px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      border: 1px solid #e0e0e0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      width: 100%;
      box-sizing: border-box;
      transition: box-shadow 0.2s;
    }
    .contact-info-item:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .contact-info-icon {
      width: 36px; height: 36px;
      display: flex; align-items:center; justify-content:center;
      flex-shrink: 0;
      background: #f8f9fa;
      border-radius: 10px;
    }
    .contact-info-icon i { font-size: 20px; }
    .contact-info-content { flex: 1; min-width: 120px; padding-right: 12px; }
    .contact-info-label { font-size: 13px; color: #999; margin-bottom: 4px; white-space: nowrap; }
    .contact-info-value { font-size: 16px; color: #333; font-weight: 700; word-break: break-all; line-height: 1.5; white-space: normal; }
    .contact-info-value.contact-info-empty { color: #999; }
    .contact-info-copy-btn {
      background: #f5f5f5;
      border: none;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      cursor: pointer;
      color: #666;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s;
      flex-shrink: 0;
    }
    .contact-info-copy-btn:hover { background: #e8e8e8; color: #333; transform: scale(1.04); }
    .contact-info-copy-btn i { font-size: 14px; }

    /* ==== Local modals (avoid clashing with header.php login modal) ==== */
    .sm-modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
      z-index: 1200;
      padding: 16px;
      box-sizing: border-box;
    }
    .sm-modal .sm-modal-content {
      background: #fff;
      border-radius: 12px;
      width: 100%;
      max-width: 860px;
      max-height: 82vh;
      overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.18);
      border: 1px solid rgba(0,0,0,0.05);
    }
    .sm-modal .sm-modal-header {
      padding: 18px 22px;
      border-bottom: 1px solid #e0e0e0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f8f9fa;
      border-radius: 12px 12px 0 0;
    }
    .sm-modal .sm-modal-header h3 { margin: 0; color: #003366; font-size: 18px; font-weight: 800; }
    .sm-modal .sm-close { font-size: 28px; font-weight: 900; cursor: pointer; color: #666; line-height: 1; }
    .sm-modal .sm-close:hover { color: #000; }
    .sm-modal .sm-modal-body { padding: 18px 22px; }
    .sm-modal .sm-modal-footer { padding: 14px 22px; border-top: 1px solid #e0e0e0; display:flex; justify-content:flex-end; gap:10px; background: #fff; border-radius: 0 0 12px 12px; }

    @media (max-width: 768px) {
      .student-stats { flex-direction: column; }
      .student-info { grid-template-columns: 1fr; }
      .student-actions { justify-content: center; }
    }
  </style>
</head>
<body>
<?php include("share/header.php"); ?>
<main>
  <div class="sm-page">
    <div class="sm-card">
      <div class="sm-title"><i class="fas fa-user-graduate"></i> 學生管理</div>
      <p class="sm-subtitle">老師端可查看分配學生、快速取得聯絡資訊、查看/新增聯絡紀錄。</p>

      <?php if (!$can_access): ?>
        <div class="sm-inline-panel" style="background:#fff2f0; border-color:#ffccc7;">
          <div style="font-weight:900; color:#a8071a; margin-bottom:6px;">無法使用此功能</div>
          <div style="color:#a8071a;">請先以老師/行政角色登入（或確認權限）。</div>
          <div style="margin-top:12px;">
            <a href="index.php" style="display:inline-block; background:#667eea; color:#fff; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:900;">回首頁</a>
          </div>
        </div>
      <?php else: ?>

      <!-- 分配學生管理 -->
        <div class="student-stats">
          <div class="stat-card">
            <div class="stat-number" id="totalStudents">0</div>
            <div class="stat-label">總學生數</div>
          </div>
          <div class="stat-card">
            <div class="stat-number" id="recentAssignments">0</div>
            <div class="stat-label">近7天分配</div>
          </div>
        </div>

        <div class="student-list-container">
          <div class="search-container">
            <input type="text" id="studentSearch" placeholder="搜尋學生姓名或電話..." class="search-input">
          </div>

          <div class="student-list" id="studentList">
            <div class="loading">載入中...</div>
          </div>
        </div>

      <?php endif; ?>
    </div>
  </div>

  <!-- 聯絡資訊 Modal -->
  <div id="smContactInfoModal" class="sm-modal">
    <div class="sm-modal-content" style="max-width: 620px;">
      <div class="sm-modal-header" style="background:#fff;">
        <h3 style="margin:0; color:#333;">聯絡資訊 - <span id="smContactInfoStudentName"></span></h3>
        <span class="sm-close" onclick="closeContactInfoModal()">&times;</span>
      </div>
      <div class="sm-modal-body">
        <div id="smContactInfoList" style="display:flex; flex-direction:column; gap: 12px;"></div>
      </div>
      <div class="sm-modal-footer" style="justify-content:center;">
        <button class="sm-btn sm-btn-secondary" onclick="closeContactInfoModal()">關閉</button>
      </div>
    </div>
  </div>

  <!-- 查看聯絡紀錄 Modal -->
  <div id="smViewContactLogsModal" class="sm-modal">
    <div class="sm-modal-content" style="max-width: 860px;">
      <div class="sm-modal-header">
        <h3>聯絡紀錄</h3>
        <span class="sm-close" onclick="closeViewContactLogs()">&times;</span>
      </div>
      <div class="sm-modal-body">
        <div style="margin-bottom: 14px; font-weight: 900; color: #003366; font-size: 15px;">
          學生：<span id="smViewLogsStudentName"></span>
        </div>
        <div id="smContactLogsList" style="max-height: 520px; overflow-y: auto;"></div>
      </div>
      <div class="sm-modal-footer">
        <button class="sm-btn sm-btn-secondary" onclick="closeViewContactLogs()">關閉</button>
      </div>
    </div>
  </div>

  <!-- 新增聯絡紀錄 Modal -->
  <div id="smAddContactLogModal" class="sm-modal">
    <div class="sm-modal-content" style="max-width: 700px;">
      <div class="sm-modal-header">
        <h3>新增聯絡紀錄</h3>
        <span class="sm-close" onclick="closeAddContactLog()">&times;</span>
      </div>
      <div class="sm-modal-body">
        <div style="margin-bottom: 12px; font-weight: 900; color: #003366;">學生：<span id="smContactLogStudentName"></span></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
          <div>
            <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px; font-weight:800;">聯絡日期</label>
            <input type="date" id="smContactDate" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px;">
          </div>
          <div>
            <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px; font-weight:800;">聯絡方式</label>
            <select id="smContactMethod" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px;">
              <option value="電話">電話</option>
              <option value="Line">Line</option>
              <option value="Email">Email</option>
              <option value="面談">面談</option>
            </select>
          </div>
        </div>
        <div style="margin-top: 12px;">
          <label style="display:block; font-size: 13px; color:#666; margin-bottom:6px; font-weight:800;">聯絡紀錄</label>
          <textarea id="smContactNotes" rows="6" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px;" placeholder="請填寫聯絡內容和結果..."></textarea>
        </div>
      </div>
      <div class="sm-modal-footer">
        <button class="sm-btn sm-btn-secondary" onclick="closeAddContactLog()">取消</button>
        <button class="sm-btn sm-btn-primary" onclick="submitAddContactLog()">儲存</button>
      </div>
    </div>
  </div>

  <script>
    // ---- Shared helpers ----
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = String(text ?? '');
      return div.innerHTML;
    }

    // ---- Assigned students (teacher_students_api.php) ----
    let studentsData = [];
    let filteredStudents = [];
    let currentContactStudentId = null;

    async function loadStudentsData() {
      const studentList = document.getElementById('studentList');
      if (!studentList) return;
      studentList.innerHTML = '<div class="loading">載入中...</div>';

      try {
        const response = await fetch('api/teacher_students_api.php', { cache: 'no-store' });

        if (!response.ok) {
          const text = await response.text();
          console.error('teacher_students_api.php 回應錯誤:', response.status, text.slice(0, 200));
          throw new Error(`伺服器錯誤 (${response.status})`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const text = await response.text();
          console.error('teacher_students_api.php 回應不是 JSON:', text.slice(0, 200));
          throw new Error('伺服器回應格式錯誤');
        }

        const data = await response.json();
        if (data.success) {
          studentsData = data.students || [];
          filteredStudents = [...studentsData];

          const total = document.getElementById('totalStudents');
          const recent = document.getElementById('recentAssignments');
          if (total) total.textContent = data.statistics?.total_students ?? studentsData.length;
          if (recent) recent.textContent = data.statistics?.recent_assignments ?? 0;

          displayStudents(filteredStudents);
        } else {
          studentList.innerHTML = `
            <div class="empty-state">
              <i class="fas fa-exclamation-triangle"></i>
              <p>載入失敗：${escapeHtml(data.message || '未知錯誤')}</p>
            </div>
          `;
        }
      } catch (error) {
        console.error('載入學生資料錯誤:', error);
        studentList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <p>載入失敗，請稍後再試</p>
            <p style="font-size: 12px; color: #999;">錯誤詳情：${escapeHtml(error.message)}</p>
          </div>
        `;
      }
    }

    function displayStudents(students) {
      const studentList = document.getElementById('studentList');
      if (!studentList) return;

      if (!students || students.length === 0) {
        studentList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-user-graduate"></i>
            <p>目前沒有分配給您的學生</p>
          </div>
        `;
        return;
      }

      studentList.innerHTML = students.map(student => `
        <div class="student-item">
          <div class="student-header">
            <h4 class="student-name">${escapeHtml(student.name)}</h4>
            <span class="student-identity">${escapeHtml(student.identity || '')}</span>
          </div>

          <div class="student-info">
            <div class="info-item">
              <div class="info-label">性別</div>
              <div class="info-value">${escapeHtml(student.gender || '未提供')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">聯絡電話一</div>
              <div class="info-value">${escapeHtml(student.phone1 || '')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">聯絡電話二</div>
              <div class="info-value">${escapeHtml(student.phone2 || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">${escapeHtml(student.email || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">就讀學校</div>
              <div class="info-value">${escapeHtml(student.junior_high || '無')}</div>
            </div>
            <div class="info-item">
              <div class="info-label">年級</div>
              <div class="info-value">${escapeHtml(student.current_grade || '無')}</div>
            </div>
          </div>

          <div class="student-intentions">
            <div class="intentions-title">就讀意願</div>
            ${student.intention1 ? `<div class="intention-item">意願一：${escapeHtml(student.intention1)} (${escapeHtml(student.system1 || 'N/A')})</div>` : ''}
            ${student.intention2 ? `<div class="intention-item">意願二：${escapeHtml(student.intention2)} (${escapeHtml(student.system2 || 'N/A')})</div>` : ''}
            ${student.intention3 ? `<div class="intention-item">意願三：${escapeHtml(student.intention3)} (${escapeHtml(student.system3 || 'N/A')})</div>` : ''}
            ${(!student.intention1 && !student.intention2 && !student.intention3) ? `<div class="intention-item">無</div>` : ''}
          </div>

          <div class="student-actions">
            <button class="action-btn btn-contact" onclick="showContactInfo('${escapeHtml(student.name)}', '${escapeHtml(student.phone1 || '')}', '${escapeHtml(student.phone2 || '')}', '${escapeHtml(student.email || '')}', '${escapeHtml(student.line_id || '')}', '${escapeHtml(student.facebook || '')}')">
              <i class="fas fa-phone"></i> 聯絡資訊
            </button>
            <button class="action-btn btn-view-logs" onclick="viewContactLogs(${Number(student.id)}, '${escapeHtml(student.name)}')">
              <i class="fas fa-history"></i> 查看聯絡紀錄
            </button>
            <button class="action-btn btn-notes" onclick="openAddContactLog(${Number(student.id)}, '${escapeHtml(student.name)}')">
              <i class="fas fa-sticky-note"></i> 新增聯絡紀錄
            </button>
          </div>
        </div>
      `).join('');
    }

    function searchStudents() {
      const input = document.getElementById('studentSearch');
      if (!input) return;
      const searchTerm = input.value.toLowerCase();
      filteredStudents = (studentsData || []).filter(student =>
        String(student.name || '').toLowerCase().includes(searchTerm) ||
        String(student.phone1 || '').includes(searchTerm) ||
        (student.phone2 && String(student.phone2).includes(searchTerm)) ||
        (student.email && String(student.email).toLowerCase().includes(searchTerm))
      );
      displayStudents(filteredStudents);
    }

    // ---- Contact info modal ----
    function showContactInfo(name, phone1, phone2, email, lineId, facebook) {
      document.getElementById('smContactInfoStudentName').textContent = name;
      const contactInfoList = document.getElementById('smContactInfoList');
      contactInfoList.innerHTML = '';

      if (phone1) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fas fa-phone" style="color: #1890ff;"></i></div>
            <div class="contact-info-content">
              <div class="contact-info-label">聯絡電話一</div>
              <div class="contact-info-value">${escapeHtml(phone1)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(phone1)}')" title="複製"><i class="fas fa-copy"></i></button>
          </div>
        `;
      }
      if (phone2) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fas fa-phone" style="color: #1890ff;"></i></div>
            <div class="contact-info-content">
              <div class="contact-info-label">聯絡電話二</div>
              <div class="contact-info-value">${escapeHtml(phone2)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(phone2)}')" title="複製"><i class="fas fa-copy"></i></button>
          </div>
        `;
      }
      if (lineId) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fab fa-line" style="color: #00c300; font-size: 24px;"></i></div>
            <div class="contact-info-content">
              <div class="contact-info-label">Line ID</div>
              <div class="contact-info-value">${escapeHtml(lineId)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(lineId)}')" title="複製"><i class="fas fa-copy"></i></button>
          </div>
        `;
      }

      contactInfoList.innerHTML += `
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-envelope" style="color: #1890ff;"></i></div>
          <div class="contact-info-content">
            <div class="contact-info-label">Email</div>
            <div class="contact-info-value ${email ? '' : 'contact-info-empty'}">${email ? escapeHtml(email) : '無'}</div>
          </div>
          ${email ? `<button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(email)}')" title="複製"><i class="fas fa-copy"></i></button>` : ''}
        </div>
      `;

      if (facebook) {
        contactInfoList.innerHTML += `
          <div class="contact-info-item">
            <div class="contact-info-icon"><i class="fab fa-facebook" style="color: #1877f2;"></i></div>
            <div class="contact-info-content">
              <div class="contact-info-label">Facebook</div>
              <div class="contact-info-value">${escapeHtml(facebook)}</div>
            </div>
            <button class="contact-info-copy-btn" onclick="copyToClipboard('${escapeHtml(facebook)}')" title="複製"><i class="fas fa-copy"></i></button>
          </div>
        `;
      }

      document.getElementById('smContactInfoModal').style.display = 'flex';
    }

    function closeContactInfoModal() {
      document.getElementById('smContactInfoModal').style.display = 'none';
    }

    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.textContent = '已複製到剪貼簿';
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #52c41a; color: white; padding: 12px 18px; border-radius: 10px; z-index: 2000; box-shadow: 0 6px 18px rgba(0,0,0,0.15); font-weight: 900;';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 1800);
      }).catch(err => alert('複製失敗：' + err));
    }

    // ---- Contact logs ----
    function openAddContactLog(studentId, studentName) {
      currentContactStudentId = studentId;
      document.getElementById('smContactLogStudentName').textContent = studentName;
      const today = new Date().toISOString().slice(0, 10);
      document.getElementById('smContactDate').value = today;
      document.getElementById('smContactMethod').value = '電話';
      document.getElementById('smContactNotes').value = '';
      document.getElementById('smAddContactLogModal').style.display = 'flex';
    }

    function closeAddContactLog() {
      document.getElementById('smAddContactLogModal').style.display = 'none';
      currentContactStudentId = null;
    }

    async function submitAddContactLog() {
      if (!currentContactStudentId) return;
      const contact_date = document.getElementById('smContactDate').value;
      const contact_method = document.getElementById('smContactMethod').value;
      const notes = document.getElementById('smContactNotes').value.trim();

      if (!contact_date || !notes) {
        alert('請填寫聯絡日期和聯絡紀錄');
        return;
      }

      try {
        const response = await fetch('api/contact_logs_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            student_id: currentContactStudentId,
            contact_date,
            contact_method,
            notes
          })
        });
        const data = await response.json();
        if (data.success) {
          alert('聯絡紀錄已新增');
          closeAddContactLog();
          // 如果正在查看該學生的聯絡紀錄，重新載入
          if (document.getElementById('smViewContactLogsModal').style.display === 'flex') {
            viewContactLogs(currentContactStudentId, document.getElementById('smViewLogsStudentName').textContent);
          }
        } else {
          alert('新增失敗：' + (data.message || '未知錯誤'));
        }
      } catch (error) {
        console.error('新增聯絡紀錄錯誤:', error);
        alert('新增失敗，請稍後再試');
      }
    }

    async function viewContactLogs(studentId, studentName) {
      document.getElementById('smViewLogsStudentName').textContent = studentName;
      const contactLogsList = document.getElementById('smContactLogsList');
      contactLogsList.innerHTML = '<div class="loading">載入中...</div>';
      document.getElementById('smViewContactLogsModal').style.display = 'flex';

      try {
        const response = await fetch(`api/contact_logs_api.php?student_id=${studentId}`, { cache: 'no-store' });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (data.success) {
          if (!data.logs || data.logs.length === 0) {
            contactLogsList.innerHTML = `
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>目前沒有聯絡紀錄</p>
              </div>
            `;
          } else {
            contactLogsList.innerHTML = data.logs.map(log => `
              <div class="student-item">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; gap:10px;">
                  <div>
                    <strong>${escapeHtml(log.contact_date)}</strong>
                    <span style="margin-left: 10px; background: #e3f2fd; color: #1976d2; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight:900;">${escapeHtml(log.method || log.contact_method || '')}</span>
                  </div>
                </div>
                <div style="color:#666; line-height:1.6; white-space: pre-wrap;">${escapeHtml(log.notes || log.result || '')}</div>
              </div>
            `).join('');
          }
        } else {
          contactLogsList.innerHTML = `
            <div class="empty-state">
              <i class="fas fa-exclamation-triangle"></i>
              <p>載入失敗：${escapeHtml(data.message || '未知錯誤')}</p>
            </div>
          `;
        }
      } catch (error) {
        console.error('載入聯絡紀錄錯誤:', error);
        contactLogsList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <p>載入失敗，請稍後再試</p>
          </div>
        `;
      }
    }

    function closeViewContactLogs() {
      document.getElementById('smViewContactLogsModal').style.display = 'none';
    }

    // ---- Event wiring ----
    document.addEventListener('DOMContentLoaded', function() {
      // students
      document.getElementById('studentSearch')?.addEventListener('input', searchStudents);
      loadStudentsData();

      // click outside to close modals
      ['smContactInfoModal','smViewContactLogsModal','smAddContactLogModal'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
      });
    });
  </script>
</main>
<?php include("share/footer.php"); ?>
</body>
</html>
