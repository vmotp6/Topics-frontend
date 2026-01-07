<?php
require_once 'session_config.php';
require_once 'config.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$allowed_roles = ['老師', 'TEA', 'STA', '學校行政人員', 'AA', 'DI'];
$can_access = $isLoggedIn && in_array($user_role, $allowed_roles, true);

// 預設帶入「聯絡教師」為目前登入者 user.name
$default_teacher_name = $_SESSION['username'] ?? '';
try {
  if ($can_access && !empty($_SESSION['username'])) {
    $conn = getDatabaseConnection();
    if ($conn) {
      $stmt = $conn->prepare("SELECT name FROM user WHERE username = ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          if (!empty($row['name'])) {
            $default_teacher_name = $row['name'];
          }
        }
        $stmt->close();
      }
      $conn->close();
    }
  }
} catch (Exception $e) {
  // fallback to username
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>學生聯絡管理 - 康寧大學招生平台</title>
  <style>
    /* 這頁的 navbar 連結較多，會換行變成雙排；用 !important 當作 fallback，
       真正的值會由 JS 依 navbar 實際高度動態設定，避免橫幅被遮住。 */
    body.custom-spacing { padding-top: 140px !important; }
    @media (max-width: 768px) { body.custom-spacing { padding-top: 160px !important; } }
    @media (max-width: 480px) { body.custom-spacing { padding-top: 175px !important; } }

    /* 最上方留白區塊（依需求：橫幅前先留出一段空白） */
    .scm-top-spacer {
      /* 顯眼的白色間隔（在 header 下方、橫幅之前） */
      height: 12px;
      width: 100%;
      background: #ffffff;
    }

    /* 頁首介紹橫幅（參考使用者提供圖片的風格） */
    .scm-hero {
      /* 淡藍色背景 */
      background: #667eea !important;
      border-radius: 18px;
      /* 最適大小：縮小高度 + 仍保留底部留白帶 */
      /* 讓橫幅上方留一段空白（參考 records.php 橫幅，上面會先留白再放標題） */
      padding: 34px 18px 28px; /* 增加上方內距避免文字被裁切，縮小底部高度 */
      margin: 0 0 14px 0;
      color: #ffffff;
      box-shadow: 0 10px 24px rgba(100, 120, 224, 0.14);
      position: relative;
      overflow: visible; /* 避免標題文字被裁切 */
    }

    /* 依需求：移除橫幅下方更淺色區塊（原本的 ::after 留白帶） */

    .scm-hero-inner {
      max-width: 980px;
      margin: 0 auto;
      text-align: center;
    }

    .scm-hero-title {
      margin: 0;
      font-size: 36px;
      font-weight: 900;
      letter-spacing: 1px;
      line-height: 1.2;
    }

    .scm-hero-desc {
      margin: 10px 0 0 0;
      font-size: 20px;
      line-height: 1.7;
      color: rgba(34, 32, 32, 0.92);
    }

    @media (max-width: 768px) {
      .scm-top-spacer { height: 10px; }
      .scm-hero { padding: 28px 14px 24px; }
      .scm-hero-title { font-size: 28px; }
    }

    @media (max-width: 480px) {
      .scm-top-spacer { height: 8px; }
      .scm-hero { padding: 24px 12px 20px; }
      .scm-hero-title { font-size: 28px; }
      .scm-hero-desc { font-size: 13px; }
    }

    /* 使用說明區塊已移除（依需求） */

    .scm-page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 16px 40px;
      box-sizing: border-box;
    }

    .scm-card {
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.06);
      padding: 20px;
    }

    .scm-title {
      margin: 0 0 8px 0;
      font-size: 22px;
      font-weight: 800;
      color: #003366;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .scm-subtitle {
      margin: 0 0 16px 0;
      color: #666;
      font-size: 14px;
      line-height: 1.5;
    }

    .scm-inline-panel {
      background: #f8f9fa;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 16px;
      margin: 14px 0;
    }

    .scm-form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }

    .scm-form-grid label {
      display: block;
      font-size: 12px;
      color: #666;
      margin-bottom: 6px;
      font-weight: 800;
    }

    /* 必填 * 紅色 */
    .req-star { color: #cf1322; font-weight: 900; }

    .scm-form-grid input, .scm-form-grid select, .scm-form-grid textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 0;
      box-sizing: border-box;
      background: #fff;
    }

    /* 聯絡教師（唯讀）字體灰色 */
    #newContactTeacher[readonly] {
      color: #8c8c8c;
    }

    .scm-form-actions {
      margin-top: 12px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      flex-wrap: nowrap;
      align-items: center;
    }

    .scm-btn {
      border: none;
      border-radius: 0;
      padding: 10px 14px;
      cursor: pointer;
      font-weight: 900;
      transition: transform 0.15s, box-shadow 0.15s;
    }

    .scm-btn-primary {
      background: #1890ff;
      color: #fff;
      box-shadow: 0 6px 14px rgba(24,144,255,0.20);
    }

    .scm-btn-primary:hover { transform: translateY(-1px); }

    .scm-btn-secondary {
      background: #f5f5f5;
      color: #333;
      border: 1px solid #e0e0e0;
    }

    .scm-table-wrap { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; }
    .scm-table-toolbar { padding: 12px 14px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; display:flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .scm-table-toolbar .scm-mini { padding: 10px 12px; border-radius: 0; border: 1px solid #ddd; }
    .scm-table-toolbar .scm-btn-group { display: flex; gap: 10px; flex-wrap: nowrap; align-items: center; }
    table.scm-table { width: 100%; border-collapse: collapse; }
    /* 表格欄位間距：調整為更緊湊的最適大小 */
    table.scm-table th, table.scm-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 13px; vertical-align: top; }
    table.scm-table th { background: #fff; color: #003366; font-weight: 900; position: sticky; top: 0; z-index: 1; }
    .scm-pill { display:inline-block; padding: 2px 10px; border-radius: 0; font-weight: 900; font-size: 12px; }
    /* 狀態顏色：已聯絡=綠色、未聯絡=紅色 */
    .scm-pill.scm-pill-contacted { background: #52c41a; color: #fff; }
    .scm-pill.scm-pill-not-contacted { background: #cf1322; color: #fff; }
    .scm-loading { text-align:center; padding: 18px; color: #666; font-weight: 800; }
    .scm-empty { text-align:center; padding: 18px; color: #666; font-weight: 800; }
  </style>
</head>
<body class="custom-spacing">
<?php include("share/header.php"); ?>
<main>
  <div class="scm-page">
    <div class="scm-top-spacer"></div>
    <section class="scm-hero">
      <div class="scm-hero-inner">
        <h2 class="scm-hero-title">學生聯絡管理</h2>
        <p class="scm-hero-desc">請詳細填寫學生聯絡相關資訊；姓名為必填。可透過下方篩選快速查詢名單。</p>
      </div>
    </section>

    <div class="scm-card">
      <div class="scm-title"><i class="fas fa-address-book"></i> 學生聯絡管理</div>
      <p class="scm-subtitle">新增/查詢學生聯絡名單（來源、狀態、聯絡方式與備註）。</p>

      <?php if (!$can_access): ?>
        <div class="scm-inline-panel" style="background:#fff2f0; border-color:#ffccc7;">
          <div style="font-weight:900; color:#a8071a; margin-bottom:6px;">無法使用此功能</div>
          <div style="color:#a8071a;">請先以老師/行政角色登入（或確認權限）。</div>
          <div style="margin-top:12px;">
            <a href="index.php" style="display:inline-block; background:#667eea; color:#fff; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:900;">回首頁</a>
          </div>
        </div>
      <?php else: ?>

      <div class="scm-inline-panel">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
          <h3 style="margin:0; color:#003366; font-weight:900; font-size:16px;"><i class="fas fa-user-plus"></i> 新增學生聯絡資訊</h3>
        </div>

        <div style="height:10px;"></div>

        <div class="scm-form-grid">
          <div>
            <label>姓名 <span class="req-star">*</span></label>
            <input id="newContactName" type="text" placeholder="必填">
          </div>
          <div>
            <label>國中 <span class="req-star">*</span></label>
            <input id="newContactJuniorHigh" type="text" placeholder="例：永吉國中">
          </div>
          <div>
            <label>年級</label>
            <select id="newContactGrade">
              <option value="">請選擇</option>
              <option value="七年級">七年級</option>
              <option value="八年級">八年級</option>
              <option value="九年級">九年級</option>
            </select>
          </div>
          <div>
            <label>興趣科系</label>
            <select id="newContactInterest">
              <option value="">請選擇</option>
              <option value="護理科">護理科</option>
              <option value="嬰幼兒保育科">嬰幼兒保育科</option>
              <option value="視光科">視光科</option>
              <option value="數位影視動畫科">數位影視動畫科</option>
              <option value="資訊管理科">資訊管理科</option>
              <option value="企業管理科">企業管理科</option>
              <option value="應用外語科">應用外語科</option>
            </select>
          </div>
          <div>
            <label>活動來源</label>
            <select id="newContactSource">
              <option value="">請選擇</option>
              <option value="升學博覽會">升學博覽會</option>
              <option value="五專入學說明會">五專入學說明會</option>
              <option value="帶社團">帶社團</option>
              <option value="來校體驗">來校體驗</option>
              <option value="其他">其他</option>
            </select>
          </div>
          <div>
            <label>聯絡教師</label>
            <input id="newContactTeacher" type="text" readonly value="<?php echo htmlspecialchars($default_teacher_name, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div>
            <label>聯絡日期</label>
            <input id="newContactDate" type="date">
          </div>
          <div>
            <label>聯絡方式</label>
            <select id="newContactMethod">
              <option value="">請選擇</option>
              <option value="電話">電話</option>
              <option value="Line">Line</option>
              <option value="Email">Email</option>
              <option value="面談">面談</option>
              <option value="其他">其他</option>
            </select>
          </div>
          <div>
            <label>聯絡方式（電話/Line ID/Email）</label>
            <input id="newContactMethodValue" type="text" >
          </div>
          <div>
            <label>聯絡內容</label>
             <textarea id="newContactContent" rows="1"></textarea>
          </div>
          <div style="grid-column: 1 / -1;">
            <label>備註</label>
            <textarea id="newContactNote" rows="3" ></textarea>
          </div>
        </div>

        <div class="scm-form-actions">
          <button class="scm-btn scm-btn-primary" onclick="submitNewStudentContact()"><i class="fas fa-save"></i> 儲存</button>
          <button class="scm-btn scm-btn-secondary" onclick="resetNewStudentContactForm()"><i class="fas fa-undo"></i> 清除</button>
        </div>
      </div>

      <div class="scm-table-wrap">
        <div class="scm-table-toolbar">
          <input id="filterName" class="scm-mini" style="flex:1; min-width: 220px;" type="text" placeholder="姓名篩選（可輸入部分姓名）">
          <select id="filterActivitySource" class="scm-mini" style="flex:1; min-width: 220px;">
            <option value="">活動來源（全部）</option>
            <option value="升學博覽會">升學博覽會</option>
            <option value="五專入學說明會">五專入學說明會</option>
            <option value="帶社團">帶社團</option>
            <option value="來校體驗">來校體驗</option>
            <option value="其他">其他</option>
          </select>
          <select id="filterStatus" class="scm-mini" style="flex:1; min-width: 220px;">
            <option value="">狀態（全部）</option>
            <option value="已聯絡">已聯絡</option>
            <option value="未聯絡">未聯絡</option>
          </select>
          <div class="scm-btn-group">
            <button class="scm-btn scm-btn-secondary" onclick="onQueryContacts()"><i class="fas fa-search"></i> 查詢</button>
            <button class="scm-btn scm-btn-secondary" onclick="onClearContactsFilters()"><i class="fas fa-eraser"></i> 清除</button>
          </div>
        </div>
        <div style="max-height: 520px; overflow: auto;">
          <table class="scm-table">
            <thead>
              <tr>
                <th style="min-width:90px;">姓名</th>
                <th style="min-width:110px;">國中</th>
                <th style="min-width:70px;">年級</th>
                <th style="min-width:110px;">興趣科系</th>
                <th style="min-width:110px;">活動來源</th>
                <th style="min-width:90px;">聯絡教師</th>
                <th style="min-width:80px;">狀態</th>
                <th style="min-width:105px;">聯絡日期</th>
                <th style="min-width:160px;">聯絡方式</th>
                <th style="min-width:180px;">聯絡內容</th>
                <th style="min-width:200px;">備註</th>
                <th style="min-width:135px;">建立時間</th>
              </tr>
            </thead>
            <tbody id="contactsTbody">
              <tr><td colspan="12" class="scm-loading">載入中...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <script>
        function escapeHtml(text) {
          const div = document.createElement('div');
          div.textContent = String(text ?? '');
          return div.innerHTML;
        }

        function resetNewStudentContactForm() {
          const ids = [
            'newContactName','newContactJuniorHigh','newContactGrade','newContactInterest','newContactSource',
            'newContactTeacher','newContactMethodValue','newContactContent','newContactNote'
          ];
          ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
          });
          const method = document.getElementById('newContactMethod');
          const date = document.getElementById('newContactDate');
          if (method) method.value = '';
          if (date) date.value = '';
        }

        async function submitNewStudentContact() {
          const name = document.getElementById('newContactName')?.value.trim() || '';
          const junior_high = document.getElementById('newContactJuniorHigh')?.value.trim() || '';
          const current_grade = document.getElementById('newContactGrade')?.value.trim() || '';
          const interest_department = document.getElementById('newContactInterest')?.value.trim() || '';
          const activity_source = document.getElementById('newContactSource')?.value.trim() || '';
          const contact_teacher = document.getElementById('newContactTeacher')?.value.trim() || '';
          const contact_method = document.getElementById('newContactMethod')?.value.trim() || '';
          const contact_method_value = document.getElementById('newContactMethodValue')?.value.trim() || '';
          const contact_content = document.getElementById('newContactContent')?.value.trim() || '';
          const contact_note = document.getElementById('newContactNote')?.value.trim() || '';
          const contact_date = document.getElementById('newContactDate')?.value || '';

          if (!name) {
            alert('請填寫姓名');
            return;
          }
          if (!junior_high) {
            alert('請填寫國中');
            return;
          }

          try {
            const res = await fetch('api/add_student_contact_api.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                name,
                junior_high,
                current_grade,
                interest_department,
                activity_source,
                contact_teacher,
                contact_method,
                contact_method_value,
                contact_content,
                contact_note,
                contact_date
              })
            });
            const data = await res.json();
            if (data.success) {
              alert('新增成功');
              resetNewStudentContactForm();
              await loadStudentContacts(true);
            } else {
              alert(data.message || '新增失敗');
            }
          } catch (e) {
            console.error(e);
            alert('新增失敗，請稍後再試');
          }
        }

        function renderContactsRows(contacts) {
          const tbody = document.getElementById('contactsTbody');
          if (!tbody) return;

          if (!contacts || contacts.length === 0) {
            tbody.innerHTML = `<tr><td colspan="12" class="scm-empty">目前沒有資料</td></tr>`;
            return;
          }

          tbody.innerHTML = contacts.map(c => `
            <tr>
              <td><strong>${escapeHtml(c.name || '')}</strong></td>
              <td>${escapeHtml(c.junior_high || '')}</td>
              <td>${escapeHtml(c.current_grade || '')}</td>
              <td>${escapeHtml(c.interest_department || '')}</td>
              <td>${escapeHtml(c.activity_source || '')}</td>
              <td>${escapeHtml(c.contact_teacher || '')}</td>
              <td>
                ${(() => {
                  const contacted = ((c.contact_content || '').trim() !== '');
                  const text = contacted ? '已聯絡' : '未聯絡';
                  const cls = contacted ? 'scm-pill-contacted' : 'scm-pill-not-contacted';
                  return `<span class="scm-pill ${cls}">${text}</span>`;
                })()}
              </td>
              <td>${escapeHtml(c.contact_date || '')}</td>
              <td>${escapeHtml(c.contact_method || '')}${c.contact_method_value ? ` / ${escapeHtml(c.contact_method_value)}` : ''}</td>
              <td style="white-space: pre-wrap;">${escapeHtml(c.contact_content || '')}</td>
              <td style="white-space: pre-wrap;">${escapeHtml(c.contact_note || '')}</td>
              <td>${escapeHtml(c.created_at || '')}</td>
            </tr>
          `).join('');
        }

        let contactsLoading = false;
        const CONTACTS_LIMIT = 50;

        async function loadStudentContacts(reset = false) {
          if (contactsLoading) return;
          contactsLoading = true;

          const tbody = document.getElementById('contactsTbody');
          if (tbody) tbody.innerHTML = `<tr><td colspan="12" class="scm-loading">載入中...</td></tr>`;

          const name = document.getElementById('filterName')?.value.trim() || '';
          const activity_source = document.getElementById('filterActivitySource')?.value.trim() || '';
          const status = document.getElementById('filterStatus')?.value.trim() || '';

          const params = new URLSearchParams();
          if (name) params.set('name', name);
          if (activity_source) params.set('activity_source', activity_source);
          if (status) params.set('status', status);
          params.set('limit', String(CONTACTS_LIMIT));
          params.set('offset', '0');

          try {
            const res = await fetch(`api/list_student_contacts_api.php?${params.toString()}`, { cache: 'no-store' });
            if (!res.ok) {
              const t = await res.text();
              console.error('list_student_contacts_api.php error:', res.status, t.slice(0, 200));
              throw new Error(`HTTP ${res.status}`);
            }
            const data = await res.json();
            if (data.success) {
              renderContactsRows(data.contacts || []);
            } else {
              if (tbody) tbody.innerHTML = `<tr><td colspan="12" class="scm-empty">載入失敗：${escapeHtml(data.message || '未知錯誤')}</td></tr>`;
            }
          } catch (e) {
            if (tbody) tbody.innerHTML = `<tr><td colspan="12" class="scm-empty">載入失敗，請稍後再試</td></tr>`;
          } finally {
            contactsLoading = false;
          }
        }

        document.addEventListener('DOMContentLoaded', function() {
          document.getElementById('filterName')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') onQueryContacts();
          });
          document.getElementById('filterActivitySource')?.addEventListener('change', () => onQueryContacts());
          document.getElementById('filterStatus')?.addEventListener('change', () => onQueryContacts());
          loadStudentContacts(true);
        });

        function onQueryContacts() {
          loadStudentContacts(true);
        }

        function onClearContactsFilters() {
          const name = document.getElementById('filterName');
          const src = document.getElementById('filterActivitySource');
          const st = document.getElementById('filterStatus');
          if (name) name.value = '';
          if (src) src.value = '';
          if (st) st.value = '';
          loadStudentContacts(true);
        }
      </script>

      <?php endif; ?>
    </div>
  </div>
</main>
<script>
  // 動態調整本頁 padding-top，避免固定導覽列（可能兩排）遮住橫幅標題
  (function() {
    function applyNavbarOffset() {
      const navbar = document.querySelector('.navbar');
      if (!navbar) return;
      const extraGap = 8; // 額外留白（縮小空白但仍避免貼到導覽列）
      const h = navbar.offsetHeight || 0;
      if (h > 0) {
        document.body.style.paddingTop = (h + extraGap) + 'px';
      }
    }

    document.addEventListener('DOMContentLoaded', applyNavbarOffset);
    window.addEventListener('load', applyNavbarOffset);
    window.addEventListener('resize', function() {
      // resize 時導覽列可能換行高度改變，重新計算
      applyNavbarOffset();
    });
  })();
</script>
<?php include("share/footer.php"); ?>
</body>
</html>


