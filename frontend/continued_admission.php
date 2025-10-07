<?php
// 確保 session 正確啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>康寧大學續招報名表</title>
	<link rel="stylesheet" href="assets/csp/continued_admission.css">
</head>
<?php include("share/header.php"); ?>
<body>
  <div class="form-container">
    <div class="form-header">
      <h1>康寧大學續招報名表</h1>
    </div>

    <!-- 錄取查詢區域 -->
    <div class="query-section">
      <h2>錄取查詢</h2>
      <div class="query-form">
        <div class="form-group">
          <label>身分證字號</label>
          <input type="text" id="queryIdNumber" placeholder="例：A123456789" pattern="[A-Za-z][0-9]{9}" maxlength="10">
        </div>
        <button type="button" id="queryBtn" class="query-btn">查詢錄取狀態</button>
      </div>
      <div id="queryResult" class="query-result" style="display: none;"></div>
    </div>

    <div class="form-content" id="formContent">
  <form id="admissionForm" method="post" enctype="multipart/form-data">
    <fieldset>
      <legend>基本資料</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label>報名編號</label>
              <input type="text" name="apply_no" disabled placeholder="限由報名單位填寫">
            </div>
            <div class="form-group">
              <label>准考證號碼</label>
              <input type="text" name="exam_no">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>姓名</label>
              <input type="text" name="name" required>
            </div>
            <div class="form-group">
              <label>身分證字號</label>
              <input type="text" name="id" placeholder="例：A123456789" pattern="[A-Za-z][0-9]{9}" maxlength="10" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group small">
              <label>出生年</label>
              <input type="number" name="birth_year" placeholder="年" min="1990" max="2010" required>
            </div>
            <div class="form-group small">
              <label>出生月</label>
              <input type="number" name="birth_month" placeholder="月" min="1" max="12" required>
            </div>
            <div class="form-group small">
              <label>出生日</label>
              <input type="number" name="birth_day" placeholder="日" min="1" max="31" required>
            </div>
            <div class="form-group">
              <label>性別</label>
              <div class="radio-group">
                <label><input type="radio" name="gender" value="male" required> 男</label>
                <label><input type="radio" name="gender" value="female" required> 女</label>
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>室內電話</label>
              <input type="tel" name="phone" placeholder="例：02-12345678">
            </div>
            <div class="form-group">
              <label>行動電話</label>
              <input type="tel" name="mobile" placeholder="例：0912345678" pattern="[0-9]{10}" maxlength="10" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group medium">
              <label>就讀縣市</label>
              <input type="text" name="school_city" placeholder="縣/市" required>
            </div>
            <div class="form-group">
              <label>就讀國中</label>
              <input type="text" name="school_name" placeholder="國中名稱" required>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>戶籍與通訊地址</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label>戶籍地址</label>
              <div class="address-group">
                <input type="text" name="zip" placeholder="郵遞區號" maxlength="5">
                <input type="text" name="city" placeholder="縣/市" required>
                <input type="text" name="district" placeholder="市/區/鄉/鎮" required>
                <input type="text" name="village" placeholder="村/里">
                <input type="text" name="neighbor" placeholder="鄰">
                <input type="text" name="road" placeholder="路(街)" required>
                <input type="text" name="section" placeholder="段">
                <input type="text" name="lane" placeholder="巷">
                <input type="text" name="alley" placeholder="弄">
                <input type="text" name="no" placeholder="號" required>
                <input type="text" name="floor" placeholder="樓之">
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>通訊地址</label>
              <div class="checkbox-group">
                <label><input type="checkbox" name="same_address" value="yes" onchange="toggleContactAddress(this)"> 同戶籍地址</label>
              </div>
              <input type="text" name="contact_address" id="contact_address" placeholder="若與戶籍地址不同，請填寫完整通訊地址">
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>監護人資訊</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label>監護人姓名</label>
              <input type="text" name="guardian" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>監護人室內電話</label>
              <input type="tel" name="guardian_phone" placeholder="例：02-12345678">
            </div>
            <div class="form-group">
              <label>監護人行動電話</label>
              <input type="tel" name="guardian_mobile" placeholder="例：0912345678" pattern="[0-9]{10}" maxlength="10" required>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>繳驗資料</legend>
          
      <div class="checkbox-group">
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="exam" required> 114 年國中教育會考成績單（必填）</label>
              <input type="file" name="doc_exam" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="skill"> 技藝教育課程結業證明</label>
              <input type="file" name="doc_skill" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="leader"> 擔任班級幹部證明</label>
              <input type="file" name="doc_leader" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="service"> 服務學習時數證明</label>
              <input type="file" name="doc_service" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="fitness"> 體適能成績證明</label>
              <input type="file" name="doc_fitness" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="contest"> 競賽成績證明</label>
              <input type="file" name="doc_contest" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
            <div class="document-item">
              <label><input type="checkbox" name="docs[]" value="other"> 其他相關證明文件</label>
              <input type="file" name="doc_other" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
            </div>
      </div>
      
      <div class="note">
        <i class="fas fa-info-circle"></i> 請上傳相關證明文件（支援 PDF、JPG、PNG 格式，單個文件大小不超過 5MB）
      </div>
    </fieldset>

    <fieldset>
      <legend>自傳 / 自我介紹</legend>
          
          <div class="form-row">
            <div class="form-group">
              <textarea name="self_intro" id="self_intro" rows="8" placeholder="請簡述個人學習經歷、興趣愛好、未來規劃等。表格若不敷使用，請自行以 A4 紙書寫。" maxlength="1000" required></textarea>
              <div class="char-count">字數：<span id="self_intro_count">0</span>/1000</div>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>興趣 / 專長</legend>
          
          <div class="form-row">
            <div class="form-group">
              <textarea name="skills" id="skills" rows="6" placeholder="請詳述個人興趣、專長、特殊才能、社團經驗、競賽成果等。" maxlength="500" required></textarea>
              <div class="char-count">字數：<span id="skills_count">0</span>/500</div>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>志願序</legend>
          
          <div class="note">
            ※ 請從下方科系中拖曳到右側框框中，並可調整優先順序。至少需選擇一個志願。
          </div>
          
          <div class="choice-selection-container">
            <!-- 可選科系列表 -->
            <div class="available-choices">
              <h4><i class="fas fa-list"></i> 可選科系</h4>
              <div class="choice-list" id="availableChoices">
                <div class="choice-item" draggable="true" data-choice="護理科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>護理科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="視光科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>視光科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="幼保科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>幼保科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="應用外語科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>應用外語科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="資訊管理科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>資訊管理科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="企業管理科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>企業管理科</span>
                </div>
                <div class="choice-item" draggable="true" data-choice="動畫科">
                  <i class="fas fa-grip-vertical"></i>
                  <span>動畫科</span>
                </div>
              </div>
            </div>
            
            <!-- 已選科系框 -->
            <div class="selected-choices">
              <h4><i class="fas fa-star"></i> 我的志願序</h4>
              <div class="choice-drop-zone" id="selectedChoices">
                <div class="drop-placeholder">
                  <i class="fas fa-hand-point-right"></i>
                  <p>請拖曳科系到這裡</p>
                  <small>第一個為第一志願</small>
                </div>
              </div>
              <div class="priority-info">
                <small><i class="fas fa-info-circle"></i> 排序說明：上方為第一志願，下方為第二志願，以此類推</small>
              </div>
            </div>
          </div>
          
          <!-- 隱藏欄位用於表單提交 -->
          <input type="hidden" name="choice_nursing" id="hidden_choice_nursing">
          <input type="hidden" name="choice_optometry" id="hidden_choice_optometry">
          <input type="hidden" name="choice_childcare" id="hidden_choice_childcare">
          <input type="hidden" name="choice_language" id="hidden_choice_language">
          <input type="hidden" name="choice_im" id="hidden_choice_im">
          <input type="hidden" name="choice_ba" id="hidden_choice_ba">
          <input type="hidden" name="choice_animation" id="hidden_choice_animation">
    </fieldset>

        <button type="submit" class="submit-btn">送出報名表</button>
  </form>
    </div>
  </div>

  <script>
    // 全域變數
    let selectedChoices = [];
    const maxChoices = 7; // 最多7個科系
    
    function toggleContactAddress(checkbox) {
      const contactAddress = document.getElementById('contact_address');
      if (checkbox.checked) {
        contactAddress.disabled = true;
        contactAddress.value = '';
        contactAddress.placeholder = '已選擇同戶籍地址';
      } else {
        contactAddress.disabled = false;
        contactAddress.placeholder = '若與戶籍地址不同，請填寫完整通訊地址';
      }
    }

    // 拖曳式科系選擇功能 - 與admission.php的體驗課程選擇一致
    function initializeCharCount() {
      const selfIntroTextarea = document.getElementById('self_intro');
      const skillsTextarea = document.getElementById('skills');
      const selfIntroCount = document.getElementById('self_intro_count');
      const skillsCount = document.getElementById('skills_count');
      
      function updateCharCount(textarea, countElement, maxLength) {
        const length = textarea.value.length;
        countElement.textContent = length;
        
        const countDiv = countElement.parentElement;
        countDiv.classList.remove('warning', 'danger');
        
        if (length > maxLength * 0.8) {
          countDiv.classList.add('warning');
        }
        if (length > maxLength * 0.95) {
          countDiv.classList.add('danger');
        }
      }
      
      if (selfIntroTextarea && selfIntroCount) {
        selfIntroTextarea.addEventListener('input', function() {
          updateCharCount(this, selfIntroCount, 1000);
        });
        // 初始化
        updateCharCount(selfIntroTextarea, selfIntroCount, 1000);
      }
      
      if (skillsTextarea && skillsCount) {
        skillsTextarea.addEventListener('input', function() {
          updateCharCount(this, skillsCount, 500);
        });
        // 初始化
        updateCharCount(skillsTextarea, skillsCount, 500);
      }
    }

    function initializeDragAndDrop() {
        const availableChoices = document.getElementById('availableChoices');
        const selectedChoicesZone = document.getElementById('selectedChoices');

        // 為所有科系項目添加拖曳事件
        availableChoices.querySelectorAll('.choice-item').forEach(item => {
          item.addEventListener('dragstart', handleDragStart);
        });

        // 為選擇區域添加放置事件
        selectedChoicesZone.addEventListener('dragover', handleDragOver);
        selectedChoicesZone.addEventListener('drop', handleDrop);
        selectedChoicesZone.addEventListener('dragenter', handleDragEnter);
        selectedChoicesZone.addEventListener('dragleave', handleDragLeave);
      }

      function handleDragStart(e) {
        e.dataTransfer.setData('text/plain', e.target.dataset.choice);
      }

      function handleDragOver(e) {
        e.preventDefault();
      }

      function handleDragEnter(e) {
        e.preventDefault();
        e.target.closest('.choice-drop-zone').classList.add('drag-over');
      }

      function handleDragLeave(e) {
        if (!e.target.closest('.choice-drop-zone').contains(e.relatedTarget)) {
          e.target.closest('.choice-drop-zone').classList.remove('drag-over');
        }
      }

      function handleDrop(e) {
        e.preventDefault();
        const choiceName = e.dataTransfer.getData('text/plain');
        const dropZone = e.target.closest('.choice-drop-zone');
        
        dropZone.classList.remove('drag-over');

        // 檢查是否已經選擇過這個科系
        if (selectedChoices.includes(choiceName)) {
          alert('此科系已經被選擇了！');
          return;
        }

        // 檢查是否超過最大選擇數量
        if (selectedChoices.length >= maxChoices) {
          alert(`最多只能選擇 ${maxChoices} 個科系！`);
          return;
        }

        // 添加到選擇列表
        selectedChoices.push(choiceName);
        updateSelectedChoicesDisplay();
        updateHiddenFields();
      }

      function updateSelectedChoicesDisplay() {
        const selectedChoicesZone = document.getElementById('selectedChoices');
        
        if (selectedChoices.length === 0) {
          selectedChoicesZone.innerHTML = `
            <div class="drop-placeholder">
              <i class="fas fa-hand-point-right"></i>
              <p>請拖曳科系到這裡</p>
              <small>第一個為第一志願</small>
            </div>
          `;
          return;
        }

        let html = '';
        selectedChoices.forEach((choice, index) => {
          const priorityText = `第${index + 1}志願`;
          html += `
            <div class="selected-choice-item" data-choice="${choice}">
              <div class="choice-info">
                <div class="choice-name">${choice}</div>
              </div>
              <div class="choice-actions">
                <span class="priority-badge">${priorityText}</span>
                <button type="button" class="remove-btn" onclick="removeChoice('${choice}')">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          `;
        });

        selectedChoicesZone.innerHTML = html;

        // 為已選科系添加排序功能
        selectedChoicesZone.querySelectorAll('.selected-choice-item').forEach(item => {
          item.addEventListener('dragstart', handleSelectedDragStart);
          item.addEventListener('dragover', handleSelectedDragOver);
          item.addEventListener('drop', handleSelectedDrop);
          item.setAttribute('draggable', 'true');
        });
      }

      function handleSelectedDragStart(e) {
        e.dataTransfer.setData('text/plain', e.target.dataset.choice);
        e.dataTransfer.effectAllowed = 'move';
      }

      function handleSelectedDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
      }

      function handleSelectedDrop(e) {
        e.preventDefault();
        const draggedChoice = e.dataTransfer.getData('text/plain');
        const targetChoice = e.target.closest('.selected-choice-item').dataset.choice;

        if (draggedChoice !== targetChoice) {
          // 交換位置
          const draggedIndex = selectedChoices.indexOf(draggedChoice);
          const targetIndex = selectedChoices.indexOf(targetChoice);

          selectedChoices[draggedIndex] = targetChoice;
          selectedChoices[targetIndex] = draggedChoice;

          updateSelectedChoicesDisplay();
          updateHiddenFields();
        }
      }

      window.removeChoice = function(choiceName) {
        const index = selectedChoices.indexOf(choiceName);
        if (index > -1) {
          selectedChoices.splice(index, 1);
          updateSelectedChoicesDisplay();
          updateHiddenFields();
        }
      }

      function updateHiddenFields() {
        // 清空所有隱藏欄位
        document.querySelectorAll('input[type="hidden"][name^="choice_"]').forEach(input => {
          input.value = '';
        });
        
        // 設定新的值
        selectedChoices.forEach((choice, index) => {
          const choiceMap = {
            '護理科': 'choice_nursing',
            '視光科': 'choice_optometry', 
            '幼保科': 'choice_childcare',
            '應用外語科': 'choice_language',
            '資訊管理科': 'choice_im',
            '企業管理科': 'choice_ba',
            '動畫科': 'choice_animation'
          };
          
          const inputName = choiceMap[choice];
          if (inputName) {
            const input = document.getElementById(`hidden_${inputName}`);
            if (input) {
              input.value = index + 1;
            }
          }
        });
      }
    
    // 防止重複提交的全局變量
    let isSubmitting = false;
    let isFormDisabled = false;
    
    // 頁面載入完成後初始化所有功能
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded, initializing...');
      
      // 調試：檢查所有表單元素
      const allForms = document.querySelectorAll('form');
      console.log('All forms found:', allForms.length);
      allForms.forEach((form, index) => {
        console.log(`Form ${index}:`, form.id, form);
      });
      
      // 初始化拖曳功能
      initializeDragAndDrop();
      
      // 初始化字數計算
      initializeCharCount();
      
      // 初始化查詢功能
      initializeQueryFunction();
      
      // 初始化表單提交 - 直接使用 submit-btn 來找到表單
      const submitBtn = document.querySelector('.submit-btn');
      let form = null;
      
      if (submitBtn) {
        form = submitBtn.closest('form');
        console.log('Form found by submit button:', form);
      }
      
      if (!form) {
        // 備用方法：通過 action 屬性查找
        form = document.querySelector('form[action*="test_simple"]');
        console.log('Form found by action:', form);
      }
      
      if (!form) {
        // 最後備用方法：通過 ID 查找
        form = document.getElementById('admissionForm');
        console.log('Form found by ID:', form);
      }
      
      if (form) {
        console.log('Final form ID:', form.id);
        console.log('Final form tagName:', form.tagName);
        console.log('Final form action:', form.action);
        console.log('Final form method:', form.method);
        console.log('Final form enctype:', form.enctype);
      } else {
        console.log('Form not found by any method!');
      }
      
      if (form) {
        console.log('Adding submit event listener to form');
        
        // 直接在按鈕點擊事件中處理表單提交
        if (submitBtn) {
          console.log('Submit button found:', submitBtn);
          
          // 移除所有現有的事件監聽器
          submitBtn.onclick = null;
          
          // 只使用一個事件監聽器，避免重複提交
          submitBtn.addEventListener('click', function(e) {
            console.log('Submit button clicked - event triggered');
            e.preventDefault(); // 阻止默認提交行為
            e.stopPropagation(); // 阻止事件冒泡
            
            // 防止重複提交
            if (isSubmitting || submitBtn.disabled || isFormDisabled) {
              console.log('Form already being submitted or disabled, preventing duplicate submission');
              return false;
            }
            
            console.log('Preparing form data...');
            
            // 驗證身分證字號格式
            const idInput = document.querySelector('input[name="id"]');
            if (idInput) {
              const idValue = idInput.value.trim();
              if (idValue.length !== 10) {
                showMessage('身分證字號必須為10個字符', 'error');
                return false;
              }
              if (!/^[A-Za-z][0-9]{9}$/.test(idValue)) {
                showMessage('身分證字號格式不正確，第一個字符必須是英文字母，後面9個字符必須是數字', 'error');
                return false;
              }
            }
            
            // 驗證行動電話格式
            const mobileInput = document.querySelector('input[name="mobile"]');
            if (mobileInput && mobileInput.value.trim()) {
              const mobileValue = mobileInput.value.trim();
              if (!/^[0-9]{10}$/.test(mobileValue)) {
                showMessage('行動電話必須為10個數字', 'error');
                return false;
              }
            }
            
            // 驗證監護人行動電話格式
            const guardianMobileInput = document.querySelector('input[name="guardian_mobile"]');
            if (guardianMobileInput && guardianMobileInput.value.trim()) {
              const guardianMobileValue = guardianMobileInput.value.trim();
              if (!/^[0-9]{10}$/.test(guardianMobileValue)) {
                showMessage('監護人行動電話必須為10個數字', 'error');
                return false;
              }
            }
            
            console.log('All validations passed, submitting form...');
            
            // 提交表單
            submitForm();
            
            return false; // 額外的阻止默認行為
          });
        } else {
          console.log('Submit button not found');
        }
      }
      
      // 提交表單函數
      function submitForm() {
        console.log('submitForm function called');
        
        // 防止重複提交
        if (isSubmitting) {
          console.log('Form already being submitted, preventing duplicate submission');
          return;
        }
        
        isSubmitting = true;
        const submitBtn = document.querySelector('.submit-btn');
        
        // 直接使用表單 ID
        const form = document.getElementById('admissionForm');
        
        if (!form) {
          console.error('Form not found in submitForm');
          return;
        }
        
        console.log('Form found in submitForm:', form);
        const formData = new FormData(form);
        
        // 顯示載入狀態
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
          submitBtn.textContent = '提交中...';
          submitBtn.disabled = true;
        }
        
        console.log('About to send request to submit_continued_admission.php');
        
        // 調試：顯示表單資料
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
          console.log(key + ': ' + value);
        }
        
        // 檢查表單是否有數據
        if (formData.entries().next().done) {
          console.log('FormData is empty!');
        } else {
          console.log('FormData has content');
        }
        
        // 添加請求 ID 來追蹤重複請求
        const requestId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        formData.append('request_id', requestId);
        console.log('Sending request with ID:', requestId);
        
        fetch('submit_continued_admission.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          console.log('Response status:', response.status);
          console.log('Response statusText:', response.statusText);
          console.log('Response headers:', response.headers);
          console.log('Response URL:', response.url);
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          return response.text().then(text => {
            console.log('Raw response text:', text);
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('JSON parse error:', e);
              return { success: false, message: 'Invalid JSON response', raw: text };
            }
          });
        })
        .then(data => {
          console.log('Response data:', data);
          if (data.success) {
            showMessage(data.message, 'success');
            // 成功後清空表單
            setTimeout(() => {
              form.reset();
              selectedChoices = [];
              updateSelectedChoicesDisplay();
              updateHiddenFields();
            }, 2000);
          } else {
            showMessage(data.message, 'error');
            // 錯誤時保留表單內容，不清空
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);
          showMessage('提交失敗，請稍後再試', 'error');
        })
        .finally(() => {
          // 恢復按鈕狀態和提交狀態
          submitBtn.textContent = originalText;
          submitBtn.disabled = false;
          isSubmitting = false;
        });
      }
      
      // 顯示訊息函數
      function showMessage(message, type) {
        // 移除現有訊息
        const existingMessage = document.querySelector('.message');
        if (existingMessage) {
          existingMessage.remove();
        }
        
        // 創建新訊息
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        
        // 如果是錯誤訊息，添加關閉按鈕
        if (type === 'error') {
          const closeBtn = document.createElement('button');
          closeBtn.textContent = '×';
          closeBtn.className = 'close-btn';
          closeBtn.onclick = () => messageDiv.remove();
          messageDiv.appendChild(closeBtn);
        }
        
        document.body.appendChild(messageDiv);
        
        // 只有成功訊息才自動移除，錯誤訊息需要手動關閉
        if (type === 'success') {
          setTimeout(() => {
            if (messageDiv.parentNode) {
              messageDiv.remove();
            }
          }, 3000);
        }
      }

      // 頁面載入完成後初始化
      initializeDragAndDrop();
    });
    
    // 初始化查詢功能
    function initializeQueryFunction() {
      const queryBtn = document.getElementById('queryBtn');
      const queryIdNumber = document.getElementById('queryIdNumber');
      const queryResult = document.getElementById('queryResult');
      
      if (queryBtn && queryIdNumber && queryResult) {
        queryBtn.addEventListener('click', function() {
          const idNumber = queryIdNumber.value.trim();
          
          if (!idNumber) {
            showQueryResult('請輸入身分證字號', 'error');
            return;
          }
          
          if (idNumber.length !== 10) {
            showQueryResult('身分證字號必須為10個字符', 'error');
            return;
          }
          
          if (!/^[A-Za-z][0-9]{9}$/.test(idNumber)) {
            showQueryResult('身分證字號格式不正確，第一個字符必須是英文字母，後面9個字符必須是數字', 'error');
            return;
          }
          
          // 顯示載入狀態
          queryBtn.textContent = '查詢中...';
          queryBtn.disabled = true;
          
          // 發送查詢請求
          const formData = new FormData();
          formData.append('id_number', idNumber);
          
          fetch('check_admission_status.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              if (data.reviewed) {
                // 已檢測，顯示結果並禁用表單
                showQueryResult(`錄取結果：${data.status_text}`, 'success');
                disableForm();
              } else {
                // 未檢測，填充表單資料
                showQueryResult(data.message, 'info');
                fillFormWithData(data.form_data);
                enableForm();
              }
            } else {
              showQueryResult(data.message, 'error');
            }
          })
          .catch(error => {
            console.error('Query error:', error);
            showQueryResult('查詢失敗，請稍後再試', 'error');
          })
          .finally(() => {
            queryBtn.textContent = '查詢錄取狀態';
            queryBtn.disabled = false;
          });
        });
      }
    }
    
    // 顯示查詢結果
    function showQueryResult(message, type) {
      const queryResult = document.getElementById('queryResult');
      if (queryResult) {
        queryResult.textContent = message;
        queryResult.className = `query-result ${type}`;
        queryResult.style.display = 'block';
      }
    }
    
    // 禁用表單
    function disableForm() {
      const formContent = document.getElementById('formContent');
      if (formContent) {
        formContent.classList.add('form-disabled');
        isFormDisabled = true;
      }
    }
    
    // 啟用表單
    function enableForm() {
      const formContent = document.getElementById('formContent');
      if (formContent) {
        formContent.classList.remove('form-disabled');
        isFormDisabled = false;
      }
    }
    
    // 填充表單資料
    function fillFormWithData(formData) {
      // 填充基本資料
      const fields = [
        'exam_no', 'name', 'id', 'birth_year', 'birth_month', 'birth_day', 'gender',
        'phone', 'mobile', 'school_city', 'school_name', 'zip', 'city', 'district',
        'village', 'neighbor', 'road', 'section', 'lane', 'alley', 'no', 'floor',
        'guardian', 'guardian_phone', 'guardian_mobile', 'self_intro', 'skills'
      ];
      
      fields.forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input && formData[field] !== undefined) {
          if (input.type === 'radio') {
            const radioInput = document.querySelector(`[name="${field}"][value="${formData[field]}"]`);
            if (radioInput) radioInput.checked = true;
          } else {
            input.value = formData[field];
          }
        }
      });
      
      // 處理地址相同選項
      if (formData.same_address === 'yes') {
        const sameAddressInput = document.querySelector('[name="same_address"][value="yes"]');
        if (sameAddressInput) sameAddressInput.checked = true;
      }
      
      // 填充志願序
      if (formData.choices && Array.isArray(formData.choices)) {
        selectedChoices = formData.choices;
        updateSelectedChoicesDisplay();
        updateHiddenFields();
      }
      
      // 填充文件上傳信息
      if (formData.documents && Array.isArray(formData.documents)) {
        // 這裡可以顯示已上傳的文件信息
        console.log('已上傳的文件:', formData.documents);
      }
    }
  </script>

<?php include("share/footer.php"); ?>
</body>
</html>
