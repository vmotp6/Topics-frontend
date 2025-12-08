<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';

// 建立資料庫連接
$conn = getDatabaseConnection();

// 取得科系資料（從 departments 表）
$courses = [];
$courses_query = "SELECT code, name 
                  FROM departments 
                  ORDER BY code, name";
$courses_result = $conn->query($courses_query);
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        // 統一使用 name 作為 course_name，以便與後續代碼兼容
        $courses[] = [
            'id' => $row['code'],
            'course_name' => $row['name'],
            'code' => $row['code'] // 保留 code 用於生成字段名
        ];
    }
}

// 建立科系名稱到隱藏欄位名稱的映射（用於 JavaScript）
// 使用科系代碼（code）來生成字段名，避免中文字符轉換問題
$courseNameToFieldMap = [];
foreach ($courses as $course) {
    // 使用科系代碼生成字段名稱（例如：FOREIGN_LANG -> choice_foreign_lang）
    // 這樣可以避免中文字符轉換問題，確保每個科系都有唯一的字段名
    $fieldName = 'choice_' . strtolower($course['code']);
    $courseNameToFieldMap[$course['course_name']] = $fieldName;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>康寧大學續招報名表</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/csp/continued_admission.css">
  <style>
    /* 錯誤提示動畫 */
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .field-error {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .field-error i {
      font-size: 14px;
    }
  </style>
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
          <label>身分證字號 / 護照號碼</label>
          <input type="text" id="queryIdNumber" placeholder="本國籍：例：A123456789 | 外籍生：例：護照號碼" maxlength="10">
        </div>
        <button type="button" id="queryBtn" class="query-btn" style="margin-bottom: 1px">查詢錄取狀態</button>
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
              <label for="student_name"><span class="required">*</span>姓名</label>
              <input type="text" id="student_name" name="student_name" required>
            </div>
            <div class="form-group">
              <label><span class="required">*</span>是否為外籍生</label>
              <div class="radio-group">
                <label><input type="radio" name="is_foreign_student" value="no" checked onchange="toggleIdentityFields()"> 否（本國籍）</label>
                <label><input type="radio" name="is_foreign_student" value="yes" onchange="toggleIdentityFields()"> 是（外籍生）</label>
              </div>
            </div>
          </div>
          
          <div class="form-row" id="local_student_fields">
            <div class="form-group">
              <label><span class="required">*</span>身分證字號</label>
              <input type="text" name="id" id="id_number_input" placeholder="例：A123456789" pattern="[A-Za-z][0-9]{9}" maxlength="10">
            </div>
          </div>
          
          <div class="form-row" id="foreign_student_fields" style="display: none;">
            <div class="form-group">
              <label><span class="required">*</span>國籍</label>
              <input type="text" name="nationality" id="nationality_input" placeholder="例：美國、日本、越南等">
            </div>
            <div class="form-group">
              <label><span class="required">*</span>護照號碼</label>
              <input type="text" name="passport_number" id="passport_number_input" placeholder="例：A12345678" maxlength="20">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label><span class="required">*</span>出生日期</label>
              <input type="date" id="birth_date" name="birth_date" min="1900-01-01" max="2025-12-31" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
              <!-- 隱藏欄位，用於向後端提交年、月、日 -->
              <input type="hidden" name="birth_year" id="birth_year">
              <input type="hidden" name="birth_month" id="birth_month">
              <input type="hidden" name="birth_day" id="birth_day">
            </div>
            <div class="form-group gender-group">
              <label><span class="required">*</span>性別</label>
              <div class="radio-group">
                <label><input type="radio" name="gender" value="male" required checked> 男</label>
                <label><input type="radio" name="gender" value="female" required> 女</label>
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>室內電話</label>
              <input type="tel" name="phone" placeholder="例：02-12345678"  maxlength="10">
            </div>
            <div class="form-group">
              <label><span class="required">*</span>行動電話</label>
              <input type="tel" name="mobile" id="mobile" placeholder="例：0912345678" pattern="[0-9]{10}" maxlength="10" required>
              <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼輸入錯誤</small>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group medium">
              <label><span class="required" id="school_city_required">*</span>就讀縣市</label>
              <select name="school_city" id="school_city" required>
                <option value="">請選擇縣市</option>
                <option value="台北市">台北市</option>
                <option value="新北市">新北市</option>
                <option value="桃園市">桃園市</option>
                <option value="台中市">台中市</option>
                <option value="台南市">台南市</option>
                <option value="高雄市">高雄市</option>
                <option value="基隆市">基隆市</option>
                <option value="新竹市">新竹市</option>
                <option value="嘉義市">嘉義市</option>
                <option value="新竹縣">新竹縣</option>
                <option value="苗栗縣">苗栗縣</option>
                <option value="彰化縣">彰化縣</option>
                <option value="南投縣">南投縣</option>
                <option value="雲林縣">雲林縣</option>
                <option value="嘉義縣">嘉義縣</option>
                <option value="屏東縣">屏東縣</option>
                <option value="宜蘭縣">宜蘭縣</option>
                <option value="花蓮縣">花蓮縣</option>
                <option value="台東縣">台東縣</option>
                <option value="澎湖縣">澎湖縣</option>
                <option value="金門縣">金門縣</option>
                <option value="連江縣">連江縣</option>
              </select>
            </div>
            <div class="form-group">
              <label><span class="required" id="school_name_required">*</span>就讀國中</label>
              <div class="modern-search-container">
                <div class="search-input-wrapper">
                  <input type="text" name="school_name" id="school_name" placeholder="請輸入學校名稱..." autocomplete="off" required>
                  <input type="hidden" id="school_code" name="school_code" value="">
                  <input type="hidden" id="school_city_actual" name="school_city_actual" value="">
                  <div class="search-icon">
                    <i class="fas fa-search"></i>
                  </div>
                  <div class="clear-btn" id="clearSchoolSearch" style="display: none;">
                    <i class="fas fa-times"></i>
                  </div>
                </div>
                <div id="schoolResults" class="modern-search-results"></div>
              </div>
              <div class="help-text">
                <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
              </div>
              <div id="school_name_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                <i class="fas fa-exclamation-circle"></i> <span id="school_name_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
              </div>
              <div id="school_city_mismatch_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                <i class="fas fa-exclamation-circle"></i> <span id="school_city_mismatch_error_text">就讀縣市與選擇的學校所在縣市不一致，系統已自動更新為正確的縣市</span>
              </div>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>戶籍與通訊地址</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label> <small style="color: #d32f2f;" id="address_required_note">(<span class="required" id="address_required">*</span>為必填)</small> 戶籍地址 </label>
              <div class="address-group">
                <div class="zip-input-wrapper">
                  <input type="text" name="zip" placeholder="郵遞區號" maxlength="6">
                    <span class="zip-info-icon" data-tooltip="郵遞區號(相容三碼)">
                      <i class="fas fa-info-circle"></i>
                    </span>
                </div>
                <input type="text" name="city" id="address_city" placeholder="*縣/市" required>
                <input type="text" name="district" id="address_district" placeholder="*市/區/鄉/鎮" required>
                <input type="text" name="village" placeholder="村/里">
                <input type="text" name="neighbor" placeholder="鄰">
                <input type="text" name="road" id="address_road" placeholder="*路(街)" required>
                <input type="text" name="section" placeholder="段">
                <input type="text" name="lane" placeholder="巷">
                <input type="text" name="alley" placeholder="弄">
                <input type="text" name="no" id="address_no" placeholder="*號" required>
                <input type="text" name="floor" placeholder="樓之">
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>通訊地址 <span class="required" id="contact_address_required" style="display: none;">*</span></label>
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
              <label><span class="required">*</span>監護人姓名</label>
              <input type="text" name="guardian" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label>監護人室內電話</label>
              <input type="tel" name="guardian_phone" placeholder="例：02-12345678" maxlength="10">
            </div>
            <div class="form-group">
              <label><span class="required">*</span>監護人行動電話</label>
              <input type="tel" name="guardian_mobile" id="guardian_mobile" placeholder="例：0912345678" pattern="[0-9]{10}" maxlength="10" required>
              <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼輸入錯誤</small>
            </div>
          </div>
    </fieldset>
    <fieldset>
      <legend>自傳 / 自我介紹</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label><span class="required">*</span>自傳 / 自我介紹</label>
              <textarea name="self_intro" id="self_intro" rows="8" placeholder="請簡述個人學習經歷、興趣愛好、未來規劃等。表格若不敷使用，請自行以 A4 紙書寫。" maxlength="1000" required></textarea>
              <div class="char-count">字數：<span id="self_intro_count">0</span>/1000</div>
            </div>
          </div>
    </fieldset>

    <fieldset>
      <legend>興趣 / 專長</legend>
          
          <div class="form-row">
            <div class="form-group">
              <label><span class="required">*</span>興趣 / 專長</label>
              <textarea name="skills" id="skills" rows="6" placeholder="請詳述個人興趣、專長、特殊才能、社團經驗、競賽成果等。" maxlength="500" required></textarea>
              <div class="char-count">字數：<span id="skills_count">0</span>/500</div>
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
      <legend>志願序 <span class="required">*</span></legend>
          
          <div class="note">
            ※ 請從下方科系中拖曳到右側框框中，並可調整優先順序。至少需選擇一個志願。
          </div>
          
          <div class="choice-selection-container">
            <!-- 可選科系列表 -->
            <div class="available-choices">
              <h4><i class="fas fa-list"></i> 可選科系</h4>
              <div class="choice-list" id="availableChoices">
                <?php foreach ($courses as $course): 
                  if ($course['code'] === 'AA') continue; ?>  
                <div class="choice-item" draggable="true" data-choice="<?php echo htmlspecialchars($course['course_name']); ?>">
                  <i class="fas fa-grip-vertical"></i>
                  <span><?php echo htmlspecialchars($course['course_name']); ?></span>
                </div>
                <?php endforeach; ?>
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
          <?php foreach ($courseNameToFieldMap as $courseName => $fieldName): ?>
          <input type="hidden" name="<?php echo htmlspecialchars($fieldName); ?>" id="hidden_<?php echo htmlspecialchars($fieldName); ?>">
          <?php endforeach; ?>
    </fieldset>

        <button type="submit" class="submit-btn">送出報名表</button>
  </form>
    </div>
  </div>

  <script>
    // 全域變數
    let selectedChoices = [];
    const maxChoices = 2; // 最多選擇的科系數量
    
    // 科系名稱到隱藏欄位名稱的映射（從 PHP 傳遞）
    const choiceMap = <?php echo json_encode($courseNameToFieldMap, JSON_UNESCAPED_UNICODE); ?>;
    function toggleContactAddress(checkbox) {
      const contactAddress = document.getElementById('contact_address');
      const contactAddressRequired = document.getElementById('contact_address_required');
      const isForeign = document.querySelector('input[name="is_foreign_student"]:checked')?.value === 'yes';
      
      if (checkbox.checked) {
        contactAddress.disabled = true;
        contactAddress.value = '';
        contactAddress.placeholder = '已選擇同戶籍地址';
        // 如果勾選「同戶籍地址」，則通訊地址不是必填
        contactAddress.removeAttribute('required');
        if (contactAddressRequired) {
          contactAddressRequired.style.display = 'none';
        }
      } else {
        contactAddress.disabled = false;
        contactAddress.placeholder = '若與戶籍地址不同，請填寫完整通訊地址';
        // 如果未勾選「同戶籍地址」，且是本國籍，則通訊地址必填
        if (!isForeign) {
          contactAddress.setAttribute('required', 'required');
          if (contactAddressRequired) {
            contactAddressRequired.style.display = 'inline';
          }
        }
      }
    }
    
    // 郵遞區號自動填充功能（簡化版本：只查詢縣市和鄉鎮）
    function initializeZipCodeAutoFill() {
      const zipInput = document.querySelector('input[name="zip"]');
      const cityInput = document.querySelector('input[name="city"]');
      const districtInput = document.querySelector('input[name="district"]');
      
      if (!zipInput || !cityInput || !districtInput) {
        return;
      }
      
      let zipDebounceTimer = null;
      
      // 監聽郵遞區號輸入 → 查詢縣市和鄉鎮
      zipInput.addEventListener('input', function() {
        const zip = this.value.trim();
        
        // 如果郵遞區號被清除或修改，解除縣市和地區的只讀狀態
        if (zip.length === 0 || zip.length < 3) {
          // 清除只讀狀態，讓使用者可以手動輸入
          cityInput.removeAttribute('readonly');
          districtInput.removeAttribute('readonly');
          cityInput.style.backgroundColor = '';
          districtInput.style.backgroundColor = '';
          cityInput.style.cursor = '';
          districtInput.style.cursor = '';
        }
        
        // 清除之前的計時器
        if (zipDebounceTimer) {
          clearTimeout(zipDebounceTimer);
        }
        
        // 當輸入3-6位數字時，通過API查詢對應的縣市和鄉鎮
        if (zip.length >= 3 && zip.length <= 6 && /^\d+$/.test(zip)) {
          // 如果是6碼（完整郵遞區號），立即查詢；否則防抖500ms
          if (zip.length === 6) {
            fetchZipCodeByCode(zip, cityInput, districtInput);
          } else {
            // 防抖：等待用戶停止輸入500ms後再查詢
            zipDebounceTimer = setTimeout(() => {
              fetchZipCodeByCode(zip, cityInput, districtInput);
            }, 500);
          }
        }
      });
    }
    
    // 從API獲取郵遞區號資料（根據郵遞區號查詢縣市鄉鎮）
    function fetchZipCodeByCode(zipcode, cityInput, districtInput) {
      // 發送API請求
      fetch(`api/zipcode_api.php?zipcode=${encodeURIComponent(zipcode)}`)
        .then(response => response.json())
        .then(data => {
          console.log('API回應:', data);
          
          if (data.success && data.data) {
            const addressInfo = data.data;
            console.log('地址資訊:', addressInfo);
            
            // 先清空縣市和地區輸入框，確保資料一致性
            cityInput.value = '';
            districtInput.value = '';
            
            // 解除只讀狀態（如果之前有設定的話）
            cityInput.removeAttribute('readonly');
            districtInput.removeAttribute('readonly');
            cityInput.style.backgroundColor = '';
            districtInput.style.backgroundColor = '';
            cityInput.style.cursor = '';
            districtInput.style.cursor = '';
            
            // 自動填充縣市和鄉鎮
            if (addressInfo.city) {
              cityInput.value = addressInfo.city;
              // 設為只讀，避免使用者修改
              cityInput.setAttribute('readonly', 'readonly');
              cityInput.style.backgroundColor = '#f5f5f5';
              cityInput.style.cursor = 'not-allowed';
            }
            if (addressInfo.district) {
              districtInput.value = addressInfo.district;
              // 設為只讀，避免使用者修改
              districtInput.setAttribute('readonly', 'readonly');
              districtInput.style.backgroundColor = '#f5f5f5';
              districtInput.style.cursor = 'not-allowed';
            }
            
            // 添加視覺提示
            cityInput.style.borderColor = '#4facfe';
            districtInput.style.borderColor = '#4facfe';
            
            // 1秒後恢復正常顏色
            setTimeout(() => {
              cityInput.style.borderColor = '';
              districtInput.style.borderColor = '';
            }, 1000);
          } else {
            console.log('❌ 找不到郵遞區號資料:', data.message || '');
            // 如果查詢失敗，確保欄位不是只讀狀態
            cityInput.removeAttribute('readonly');
            districtInput.removeAttribute('readonly');
            cityInput.style.backgroundColor = '';
            districtInput.style.backgroundColor = '';
            cityInput.style.cursor = '';
            districtInput.style.cursor = '';
          }
        })
        .catch(error => {
          console.error('❌ 查詢郵遞區號失敗:', error);
          // 如果查詢失敗，確保欄位不是只讀狀態
          cityInput.removeAttribute('readonly');
          districtInput.removeAttribute('readonly');
          cityInput.style.backgroundColor = '';
          districtInput.style.backgroundColor = '';
          cityInput.style.cursor = '';
          districtInput.style.cursor = '';
        });
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

        // 檢查表單是否處於只讀模式
        if (isFormDisabled) {
          console.log('表單處於只讀模式，跳過拖曳功能初始化');
          return;
        }

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
        // 確保只有 .choice-item 元素可以被拖曳
        const choiceItem = e.target.closest('.choice-item');
        if (!choiceItem) {
          e.preventDefault();
          return;
        }
        
        const choiceName = choiceItem.dataset.choice;
        if (!choiceName) {
          e.preventDefault();
          return;
        }
        
        // 設置拖曳數據和標記
        e.dataTransfer.setData('text/plain', choiceName);
        e.dataTransfer.setData('application/choice-item', 'true'); // 標記這是有效的科系項目
        e.dataTransfer.effectAllowed = 'copy';
      }

      function handleDragOver(e) {
        e.preventDefault();
        // 只有有效的科系項目才允許放置
        const isValidChoice = e.dataTransfer.types.includes('application/choice-item');
        e.dataTransfer.dropEffect = isValidChoice ? 'copy' : 'none';
      }

      function handleDragEnter(e) {
        e.preventDefault();
        // 只有有效的科系項目才顯示拖曳效果
        const isValidChoice = e.dataTransfer.types.includes('application/choice-item');
        if (isValidChoice) {
          const dropZone = e.target.closest('.choice-drop-zone');
          if (dropZone) {
            dropZone.classList.add('drag-over');
          }
        }
      }

      function handleDragLeave(e) {
        if (!e.target.closest('.choice-drop-zone').contains(e.relatedTarget)) {
          e.target.closest('.choice-drop-zone').classList.remove('drag-over');
        }
      }

      function handleDrop(e) {
        e.preventDefault();
        const dropZone = e.target.closest('.choice-drop-zone');
        
        if (!dropZone) {
          return;
        }
        
        dropZone.classList.remove('drag-over');

        // 檢查是否為有效的科系項目（只有從 available-choices 拖來的才有效）
        const isValidChoice = e.dataTransfer.getData('application/choice-item') === 'true';
        if (!isValidChoice) {
          // 不是從科系列表拖來的，忽略
          return;
        }

        const choiceName = e.dataTransfer.getData('text/plain');
        
        // 驗證 choiceName 是否為空或無效
        if (!choiceName || choiceName.trim() === '') {
          return;
        }

        // 檢查是否已經選擇過這個科系
        if (selectedChoices.includes(choiceName)) {
          alert('此科系已經被選擇了！');
          return;
        }

        // 檢查是否超過最大選擇數量（最多2個）
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
        // 使用 currentTarget 或 closest 確保獲取正確的元素
        const item = e.currentTarget || e.target.closest('.selected-choice-item');
        const choice = item ? item.dataset.choice : e.target.dataset.choice;
        e.dataTransfer.setData('text/plain', choice);
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
        console.log('updateHiddenFields 被調用');
        console.log('selectedChoices:', selectedChoices);
        console.log('choiceMap:', choiceMap);
        
        // 清空所有隱藏欄位
        document.querySelectorAll('input[type="hidden"][name^="choice_"]').forEach(input => {
          input.value = '';
        });
        
        // 設定新的值
        selectedChoices.forEach((choice, index) => {
          const inputName = choiceMap[choice];
          console.log(`處理志願 #${index + 1}: ${choice} -> 字段名: ${inputName}`);
          
          if (inputName) {
            const input = document.getElementById(`hidden_${inputName}`);
            if (input) {
              input.value = index + 1;
              console.log(`設置隱藏字段 ${inputName} = ${index + 1}`);
            } else {
              console.error(`找不到隱藏字段: hidden_${inputName}`);
            }
          } else {
            console.error(`找不到 ${choice} 的字段映射`);
          }
        });
        
        // 驗證所有隱藏字段的值
        console.log('隱藏字段驗證:');
        document.querySelectorAll('input[type="hidden"][name^="choice_"]').forEach(input => {
          if (input.value) {
            console.log(`${input.name} = ${input.value}`);
          }
        });
      }
    
    // 防止重複提交的全局變量
    let isSubmitting = false;
    let isFormDisabled = false;
    
    // 頁面載入完成後初始化所有功能
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded, initializing...');
      
      // 阻止所有非科系項目的拖曳行為
      document.addEventListener('dragstart', function(e) {
        // 只允許 .choice-item 和 .selected-choice-item 被拖曳
        const isChoiceItem = e.target.closest('.choice-item') || e.target.closest('.selected-choice-item');
        if (!isChoiceItem) {
          e.preventDefault();
          return false;
        }
      }, true);
      
      // 設置出生日期選擇器的事件監聽器
      const birthDateInput = document.getElementById('birth_date');
      if (birthDateInput) {
        birthDateInput.addEventListener('change', function() {
          const dateValue = this.value; // 格式：YYYY-MM-DD
          if (dateValue) {
            const dateParts = dateValue.split('-');
            if (dateParts.length === 3) {
              document.getElementById('birth_year').value = dateParts[0];
              document.getElementById('birth_month').value = dateParts[1];
              document.getElementById('birth_day').value = dateParts[2];
            }
          }
        });
      }
      
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
      
      // 初始化外籍生欄位切換
      toggleIdentityFields();
      
      // 初始化學校搜尋功能
      initializeSchoolSearch();
      
      // 初始化電話號碼驗證
      initializePhoneValidation();
      
      // 初始化身分證字號欄位為可編輯
      setIdNumberReadOnly(false);
      
      // 初始化郵遞區號自動填充功能
      initializeZipCodeAutoFill();
      
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
            
            // 檢查是否為外籍生
            const isForeign = document.querySelector('input[name="is_foreign_student"]:checked')?.value === 'yes';
            
            // 驗證所有必填欄位
            const requiredFields = [
              { name: 'student_name', label: '姓名' },
              { name: 'birth_year', label: '出生年' },
              { name: 'birth_month', label: '出生月' },
              { name: 'birth_day', label: '出生日' },
              { name: 'mobile', label: '行動電話' },
              { name: 'guardian', label: '監護人姓名' },
              { name: 'guardian_mobile', label: '監護人行動電話' },
              { name: 'self_intro', label: '自傳/自我介紹', type: 'textarea' },
              { name: 'skills', label: '興趣/專長', type: 'textarea' }
            ];
            
            // 根據是否外籍生添加不同的必填欄位
            if (isForeign) {
              requiredFields.push(
                { name: 'nationality', label: '國籍' },
                { name: 'passport_number', label: '護照號碼' }
              );
              // 外籍生：就讀縣市、就讀國中、戶籍地址、通訊地址不是必填
            } else {
              requiredFields.push({ name: 'id', label: '身分證字號' });
              // 本國籍：就讀縣市、就讀國中、戶籍地址為必填
              requiredFields.push(
                { name: 'school_city', label: '就讀縣市' },
                { name: 'school_name', label: '就讀國中' },
                { name: 'city', label: '戶籍地址縣/市' },
                { name: 'district', label: '戶籍地址市/區/鄉/鎮' },
                { name: 'road', label: '戶籍地址路(街)' },
                { name: 'no', label: '戶籍地址號' }
              );
            }
            
            // 檢查必填欄位
            for (let field of requiredFields) {
              let element;
              if (field.type === 'textarea') {
                element = document.querySelector(`textarea[name="${field.name}"]`);
              } else {
                element = document.querySelector(`input[name="${field.name}"]`);
              }
              
              if (!element) {
                console.warn(`找不到欄位: ${field.name}`);
                continue;
              }
              
              const value = element.value.trim();
              if (!value) {
                showMessage(`${field.label}為必填欄位，請填寫`, 'error');
                element.focus();
                element.style.borderColor = '#d32f2f';
                setTimeout(() => {
                  element.style.borderColor = '';
                }, 3000);
                return false;
              }
            }
            
            // 驗證性別（radio）
            const genderInputs = document.querySelectorAll('input[name="gender"]');
            let genderSelected = false;
            genderInputs.forEach(input => {
              if (input.checked) {
                genderSelected = true;
              }
            });
            if (!genderSelected) {
              showMessage('性別為必填欄位，請選擇', 'error');
              if (genderInputs.length > 0) {
                genderInputs[0].focus();
              }
              return false;
            }
            
            // 驗證就讀國中格式（必須從系統選項中選擇）
            const schoolNameInput = document.getElementById('school_name');
            if (schoolNameInput) {
              const schoolName = schoolNameInput.value.trim();
              if (schoolName) {
                // 檢查格式是否為：學校名稱 (縣市區)
                const schoolFormatPattern = /^.+ \(.+\)$/;
                if (!schoolFormatPattern.test(schoolName)) {
                  showMessage('請從系統提供的選項中選擇學校，不能自行輸入', 'error');
                  schoolNameInput.focus();
                  schoolNameInput.style.borderColor = '#d32f2f';
                  showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                  setTimeout(() => {
                    schoolNameInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
              }
            }
            
            // 驗證身分證字號或護照號碼格式
            if (isForeign) {
              // 外籍生：驗證護照號碼
              const passportInput = document.querySelector('input[name="passport_number"]');
              if (passportInput) {
                const passportValue = passportInput.value.trim();
                if (!passportValue) {
                  showMessage('護照號碼為必填欄位', 'error');
                  passportInput.focus();
                  passportInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    passportInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
                if (passportValue.length < 6 || passportValue.length > 20) {
                  showMessage('護照號碼長度應為6-20個字符', 'error');
                  passportInput.focus();
                  passportInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    passportInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
              }
              
              // 驗證國籍
              const nationalityInput = document.querySelector('input[name="nationality"]');
              if (nationalityInput && !nationalityInput.value.trim()) {
                showMessage('國籍為必填欄位', 'error');
                nationalityInput.focus();
                nationalityInput.style.borderColor = '#d32f2f';
                setTimeout(() => {
                  nationalityInput.style.borderColor = '';
                }, 3000);
                return false;
              }
            } else {
              // 本國籍：驗證身分證字號格式
              const idInput = document.querySelector('input[name="id"]');
              if (idInput) {
                const idValue = idInput.value.trim();
                if (!idValue) {
                  showMessage('身分證字號為必填欄位', 'error');
                  idInput.focus();
                  idInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    idInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
                if (idValue.length !== 10) {
                  showMessage('身分證字號必須為10個字符', 'error');
                  idInput.focus();
                  idInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    idInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
                if (!/^[A-Za-z][0-9]{9}$/.test(idValue)) {
                  showMessage('身分證字號格式不正確，第一個字符必須是英文字母，後面9個字符必須是數字', 'error');
                  idInput.focus();
                  idInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    idInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
              }
            }
            
            // 驗證行動電話格式
            const mobileInput = document.getElementById('mobile');
            if (mobileInput) {
              const mobileValue = mobileInput.value.trim();
              if (!mobileValue) {
                showMessage('行動電話為必填欄位，請填寫', 'error');
                mobileInput.focus();
                mobileInput.style.borderColor = '#d32f2f';
                mobileInput.style.borderWidth = '2px';
                mobileInput.classList.add('phone-error');
                if (mobileInput.nextElementSibling && mobileInput.nextElementSibling.classList.contains('phone-hint')) {
                  mobileInput.nextElementSibling.textContent = '請填寫行動電話';
                  mobileInput.nextElementSibling.style.display = 'block';
                }
                setTimeout(() => {
                  mobileInput.style.borderColor = '';
                  mobileInput.style.borderWidth = '';
                }, 3000);
                return false;
              } else if (!/^[0-9]{10}$/.test(mobileValue)) {
                showMessage('行動電話必須為10個數字', 'error');
                mobileInput.focus();
                mobileInput.style.borderColor = '#d32f2f';
                mobileInput.style.borderWidth = '2px';
                mobileInput.classList.add('phone-error');
                if (mobileInput.nextElementSibling && mobileInput.nextElementSibling.classList.contains('phone-hint')) {
                  mobileInput.nextElementSibling.textContent = '電話號碼必須為10位數字';
                  mobileInput.nextElementSibling.style.display = 'block';
                }
                setTimeout(() => {
                  mobileInput.style.borderColor = '';
                  mobileInput.style.borderWidth = '';
                }, 3000);
                return false;
              }
            }
            
            // 驗證監護人行動電話格式
            const guardianMobileInput = document.getElementById('guardian_mobile');
            if (guardianMobileInput) {
              const guardianMobileValue = guardianMobileInput.value.trim();
              if (!guardianMobileValue) {
                showMessage('監護人行動電話為必填欄位，請填寫', 'error');
                guardianMobileInput.focus();
                guardianMobileInput.style.borderColor = '#d32f2f';
                guardianMobileInput.style.borderWidth = '2px';
                guardianMobileInput.classList.add('phone-error');
                if (guardianMobileInput.nextElementSibling && guardianMobileInput.nextElementSibling.classList.contains('phone-hint')) {
                  guardianMobileInput.nextElementSibling.textContent = '請填寫監護人行動電話';
                  guardianMobileInput.nextElementSibling.style.display = 'block';
                }
                setTimeout(() => {
                  guardianMobileInput.style.borderColor = '';
                  guardianMobileInput.style.borderWidth = '';
                }, 3000);
                return false;
              } else if (!/^[0-9]{10}$/.test(guardianMobileValue)) {
                showMessage('監護人行動電話必須為10個數字', 'error');
                guardianMobileInput.focus();
                guardianMobileInput.style.borderColor = '#d32f2f';
                guardianMobileInput.style.borderWidth = '2px';
                guardianMobileInput.classList.add('phone-error');
                if (guardianMobileInput.nextElementSibling && guardianMobileInput.nextElementSibling.classList.contains('phone-hint')) {
                  guardianMobileInput.nextElementSibling.textContent = '電話號碼輸入錯誤';
                  guardianMobileInput.nextElementSibling.style.display = 'block';
                }
                setTimeout(() => {
                  guardianMobileInput.style.borderColor = '';
                  guardianMobileInput.style.borderWidth = '';
                }, 3000);
                return false;
              }
            }
            
            // 驗證出生日期合理性
            const birthDateInput = document.getElementById('birth_date');
            if (birthDateInput && birthDateInput.value) {
              const selectedDate = new Date(birthDateInput.value);
              const today = new Date();
              if (selectedDate > today) {
                showMessage('出生日期不能是未來日期', 'error');
                return false;
              }
            } else {
              showMessage('請選擇出生日期', 'error');
              return false;
            }
            
            // 驗證必填文件（114 年國中教育會考成績單）
            const examDocCheckbox = document.querySelector('input[name="docs[]"][value="exam"]');
            const examDocFile = document.querySelector('input[name="doc_exam"]');
            
            if (!examDocCheckbox || !examDocCheckbox.checked) {
              showMessage('請勾選「114 年國中教育會考成績單（必填）」', 'error');
              if (examDocCheckbox) {
                examDocCheckbox.focus();
              }
              return false;
            }
            
            // 驗證是否已上傳檔案
            if (!examDocFile || !examDocFile.files || examDocFile.files.length === 0) {
              showMessage('請上傳「114 年國中教育會考成績單」檔案', 'error');
              if (examDocFile) {
                examDocFile.focus();
                examDocFile.style.borderColor = '#d32f2f';
                setTimeout(() => {
                  examDocFile.style.borderColor = '';
                }, 3000);
              }
              return false;
            }
            
            // 驗證檔案大小（例如：限制為10MB）
            const maxFileSize = 10 * 1024 * 1024; // 10MB
            if (examDocFile.files[0].size > maxFileSize) {
              showMessage('「114 年國中教育會考成績單」檔案大小不能超過10MB', 'error');
              examDocFile.focus();
              examDocFile.style.borderColor = '#d32f2f';
              setTimeout(() => {
                examDocFile.style.borderColor = '';
              }, 3000);
              return false;
            }
            
            // 驗證檔案格式
            const allowedExtensions = ['.pdf', '.jpg', '.jpeg', '.png'];
            const fileName = examDocFile.files[0].name.toLowerCase();
            const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
            if (!allowedExtensions.includes(fileExtension)) {
              showMessage('「114 年國中教育會考成績單」檔案格式不正確，請上傳 PDF、JPG、JPEG 或 PNG 格式', 'error');
              examDocFile.focus();
              examDocFile.style.borderColor = '#d32f2f';
              setTimeout(() => {
                examDocFile.style.borderColor = '';
              }, 3000);
              return false;
            }
            
            // 驗證通訊地址（如果未勾選「同戶籍地址」，則通訊地址必填，但外籍生除外）
            if (!isForeign) {
              const sameAddressCheckbox = document.querySelector('input[name="same_address"]');
              const contactAddressInput = document.querySelector('input[name="contact_address"]');
              if (sameAddressCheckbox && !sameAddressCheckbox.checked && contactAddressInput) {
                const contactAddress = contactAddressInput.value.trim();
                if (!contactAddress) {
                  showMessage('通訊地址為必填欄位，請填寫完整通訊地址或勾選「同戶籍地址」', 'error');
                  contactAddressInput.focus();
                  contactAddressInput.style.borderColor = '#d32f2f';
                  setTimeout(() => {
                    contactAddressInput.style.borderColor = '';
                  }, 3000);
                  return false;
                }
              }
            }
            
            // 驗證志願序（至少需選擇一個志願）
            if (selectedChoices.length === 0) {
              showMessage('志願序為必填欄位，請至少選擇一個志願', 'error');
              const selectedChoicesZone = document.getElementById('selectedChoices');
              if (selectedChoicesZone) {
                selectedChoicesZone.style.border = '2px solid #d32f2f';
                setTimeout(() => {
                  selectedChoicesZone.style.border = '';
                }, 3000);
              }
              return false;
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
        
        // 確保隱藏字段被更新（在創建 FormData 之前）
        updateHiddenFields();
        
        // 直接使用 selectedChoices 數組構建 FormData，不依賴隱藏字段
        console.log('=== 開始提交流程 ===');
        console.log('selectedChoices 數組:', selectedChoices);
        console.log('selectedChoices 長度:', selectedChoices.length);
        console.log('choiceMap:', choiceMap);
        
        // 再次確保隱藏字段已更新（用於顯示）
        updateHiddenFields();
        
        // 處理出生日期：將日期選擇器的值拆分為年、月、日
        const birthDateInput = document.getElementById('birth_date');
        if (birthDateInput && birthDateInput.value) {
          const dateValue = birthDateInput.value; // 格式：YYYY-MM-DD
          const dateParts = dateValue.split('-');
          if (dateParts.length === 3) {
            document.getElementById('birth_year').value = dateParts[0];
            document.getElementById('birth_month').value = dateParts[1];
            document.getElementById('birth_day').value = dateParts[2];
          }
        }
        
        // 創建 FormData
        const formData = new FormData(form);
        
        // 直接從 selectedChoices 數組添加志願序字段到 FormData
        // 這是主要方法，不依賴隱藏字段
        console.log('開始直接從 selectedChoices 添加志願序字段到 FormData');
        selectedChoices.forEach((choice, index) => {
          const inputName = choiceMap[choice];
          const priority = index + 1;
          
          if (inputName) {
            // 先刪除舊值（如果存在）
            if (formData.has(inputName)) {
              formData.delete(inputName);
            }
            // 添加新值
            formData.append(inputName, priority.toString());
            console.log(`✅ 直接添加: ${inputName} = ${priority} (志願 #${priority}: ${choice})`);
          } else {
            console.error(`❌ 找不到 ${choice} 的字段映射`);
          }
        });
        
        // 驗證 FormData 中的志願序字段
        console.log('=== FormData 驗證 ===');
        let foundInFormData = 0;
        for (let [key, value] of formData.entries()) {
          if (key.startsWith('choice_')) {
            console.log(`  ✅ ${key} = ${value}`);
            foundInFormData++;
          }
        }
        console.log(`FormData 中共找到 ${foundInFormData} 個志願序字段`);
        console.log(`selectedChoices 中有 ${selectedChoices.length} 個志願`);
        
        if (foundInFormData !== selectedChoices.length) {
          console.error(`⚠️ 警告：FormData 中的字段數量 (${foundInFormData}) 與 selectedChoices 數量 (${selectedChoices.length}) 不匹配！`);
        }
        
        // 驗證縣市與學校是否一致
        if (!validateCitySchoolMatch()) {
          // 如果驗證失敗（縣市不一致），阻止提交
          const originalText = submitBtn ? submitBtn.textContent : '';
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText || '提交';
          }
          // 滾動到錯誤位置
          const schoolCitySelect = document.getElementById('school_city');
          if (schoolCitySelect) {
            schoolCitySelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            schoolCitySelect.focus();
          }
          return;
        }
        
        // 繼續提交流程
        continueSubmit(formData, submitBtn);
      }
      
      // 繼續提交的函數
      function continueSubmit(formData, submitBtn) {
        
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
        
        // 調試：檢查志願序隱藏字段
        console.log('志願序隱藏字段檢查:');
        const choiceFields = Object.values(choiceMap); // 使用動態生成的映射
        let foundChoices = 0;
        choiceFields.forEach(field => {
          const input = document.getElementById(`hidden_${field}`);
          if (input) {
            const value = input.value;
            console.log(`${field}: ${value}`);
            if (value) {
              foundChoices++;
              // 確保 FormData 中包含這個字段
              if (!formData.has(field)) {
                console.warn(`警告：FormData 中缺少字段 ${field}，手動添加`);
                formData.append(field, value);
              }
            }
          } else {
            console.warn(`找不到隱藏字段元素: hidden_${field}`);
          }
        });
        console.log(`找到 ${foundChoices} 個志願序字段`);
        
        // 檢查表單是否有數據
        if (formData.entries().next().done) {
          console.log('FormData is empty!');
        } else {
          console.log('FormData has content');
        }
        
        // 再次驗證：檢查所有 choice_ 開頭的字段
        console.log('FormData 中所有 choice_ 字段:');
        for (let [key, value] of formData.entries()) {
          if (key.startsWith('choice_')) {
            console.log(`  ${key} = ${value}`);
          }
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
              setIdNumberReadOnly(false); // 清空表單後設置身分證字號為可編輯
              
              // 清空表單後自動重整頁面
              window.location.reload();
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
        
        // 創建文本節點
        const messageText = document.createTextNode(message);
        messageDiv.appendChild(messageText);
        
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

      // 頁面載入完成後初始化（拖曳功能在DOMContentLoaded中已初始化）
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
            showQueryResult('請輸入身分證字號或護照號碼', 'error');
            return;
          }
          
          // 檢查是否為護照號碼格式（外籍生）
          // 如果輸入以 PASSPORT_ 開頭，保留原樣；否則檢查是否為身分證字號格式
          const hasPassportPrefix = idNumber.startsWith('PASSPORT_');
          let isPassportFormat = hasPassportPrefix;
          let queryValue = idNumber; // 用於查詢的最終值
          
          if (!hasPassportPrefix) {
            // 檢查是否為身分證字號格式（10個字符，第一個是字母，後面9個是數字）
            const isIdNumberFormat = (idNumber.length === 10 && /^[A-Za-z][0-9]{9}$/.test(idNumber));
            
            if (!isIdNumberFormat) {
              // 不是身分證字號格式，當作護照號碼處理
              isPassportFormat = true;
              // 驗證護照號碼長度
              if (idNumber.length < 6 || idNumber.length > 20) {
                showQueryResult('護照號碼長度應為6-20個字符', 'error');
                return;
              }
              // 自動加上 PASSPORT_ 前綴
              queryValue = 'PASSPORT_' + idNumber;
            } else {
              // 是身分證字號格式，直接使用
              isPassportFormat = false;
              queryValue = idNumber;
            }
          } else {
            // 已經有 PASSPORT_ 前綴，驗證護照號碼部分
            const passportValue = idNumber.replace(/^PASSPORT_/, '');
            if (passportValue.length < 6 || passportValue.length > 20) {
              showQueryResult('護照號碼長度應為6-20個字符', 'error');
              return;
            }
            queryValue = idNumber; // 保持原樣
          }
          
          // 顯示載入狀態
          queryBtn.textContent = '查詢中...';
          queryBtn.disabled = true;
          
          // 發送查詢請求
          const formData = new FormData();
          formData.append('id_number', queryValue);
          
          fetch('check_admission_status.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              if (data.reviewed) {
                // 已審核，顯示結果並填充表單資料（只讀模式）
                let statusMessage = data.message;
                if (data.status_text) {
                  statusMessage += ` (${data.status_text})`;
                }
                if (data.review_notes) {
                  statusMessage += `\n備註：${data.review_notes}`;
                }
                // 使用後端返回的 display_type
                const displayType = data.display_type || 'success';
                showQueryResult(statusMessage, displayType);
                fillFormWithData(data.form_data);
                setFormReadOnly(true); // 所有欄位設為只讀
                setIdNumberReadOnly(true); // 身分證字號也設為只讀
              } else {
                // 待審核狀態，填充表單資料供修改
                const displayType = data.display_type || 'info';
                showQueryResult(data.message, displayType);
                fillFormWithData(data.form_data);
                setFormReadOnly(false); // 允許編輯所有欄位
                setIdNumberReadOnly(true); // 但身分證字號仍為只讀
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
        // 支持多行文本顯示
        queryResult.innerHTML = message.replace(/\n/g, '<br>');
        queryResult.className = `query-result ${type}`;
        queryResult.style.display = 'block';
      }
    }
    
    // 設置表單只讀模式
    function setFormReadOnly(readOnly) {
      console.log('設置表單只讀模式:', readOnly);
      const formContent = document.getElementById('formContent');
      if (formContent) {
        if (readOnly) {
          formContent.classList.add('form-readonly');
          // 禁用所有輸入框
          const inputs = formContent.querySelectorAll('input, textarea, select');
          inputs.forEach(input => {
            input.disabled = true;
          });
          
          // 禁用志願序拖曳功能
          const choiceItems = formContent.querySelectorAll('.choice-item');
          choiceItems.forEach(item => {
            item.draggable = false;
            item.style.pointerEvents = 'none';
            item.style.opacity = '0.6';
          });
          
          const selectedChoiceItems = formContent.querySelectorAll('.selected-choice-item');
          selectedChoiceItems.forEach(item => {
            item.draggable = false;
            item.style.pointerEvents = 'none';
          });
          
          // 隱藏提交按鈕
          const submitBtn = document.querySelector('.submit-btn');
          if (submitBtn) {
            submitBtn.style.display = 'none';
          }
        } else {
          formContent.classList.remove('form-readonly');
          // 啟用所有輸入框
          const inputs = formContent.querySelectorAll('input, textarea, select');
          inputs.forEach(input => {
            input.disabled = false;
          });
          
          // 啟用志願序拖曳功能
          const choiceItems = formContent.querySelectorAll('.choice-item');
          choiceItems.forEach(item => {
            item.draggable = true;
            item.style.pointerEvents = 'auto';
            item.style.opacity = '1';
          });
          
          const selectedChoiceItems = formContent.querySelectorAll('.selected-choice-item');
          selectedChoiceItems.forEach(item => {
            item.draggable = true;
            item.style.pointerEvents = 'auto';
          });
          
          // 顯示提交按鈕
          const submitBtn = document.querySelector('.submit-btn');
          if (submitBtn) {
            submitBtn.style.display = 'block';
          }
        }
        isFormDisabled = readOnly;
        console.log('表單狀態已更新，isFormDisabled:', isFormDisabled);
      }
    }
    
    // 設置身分證字號欄位只讀狀態
    function setIdNumberReadOnly(readOnly) {
      console.log('設置身分證字號只讀狀態:', readOnly);
      const idInput = document.getElementById('id_number_input');
      if (idInput) {
        if (readOnly) {
          idInput.setAttribute('readonly', 'readonly');
          console.log('身分證字號設為只讀');
        } else {
          idInput.removeAttribute('readonly');
          console.log('身分證字號設為可編輯');
        }
      }
    }
    
    // 切換身分證字號/護照號碼欄位顯示
    function toggleIdentityFields() {
      const isForeign = document.querySelector('input[name="is_foreign_student"]:checked')?.value === 'yes';
      const localFields = document.getElementById('local_student_fields');
      const foreignFields = document.getElementById('foreign_student_fields');
      const idInput = document.getElementById('id_number_input');
      const nationalityInput = document.getElementById('nationality_input');
      const passportInput = document.getElementById('passport_number_input');
      
      // 就讀縣市和就讀國中欄位
      const schoolCitySelect = document.getElementById('school_city');
      const schoolNameInput = document.getElementById('school_name');
      const schoolCityRequired = document.getElementById('school_city_required');
      const schoolNameRequired = document.getElementById('school_name_required');
      
      // 戶籍地址欄位
      const addressCity = document.getElementById('address_city');
      const addressDistrict = document.getElementById('address_district');
      const addressRoad = document.getElementById('address_road');
      const addressNo = document.getElementById('address_no');
      const addressRequired = document.getElementById('address_required');
      const addressRequiredNote = document.getElementById('address_required_note');
      
      // 通訊地址欄位
      const contactAddressInput = document.getElementById('contact_address');
      const contactAddressRequired = document.getElementById('contact_address_required');
      
      if (isForeign) {
        // 顯示外籍生欄位，隱藏本國籍欄位
        if (localFields) localFields.style.display = 'none';
        if (foreignFields) foreignFields.style.display = 'flex';
        
        // 設定必填屬性
        if (idInput) {
          idInput.removeAttribute('required');
          idInput.value = ''; // 清空身分證字號
        }
        if (nationalityInput) nationalityInput.setAttribute('required', 'required');
        if (passportInput) passportInput.setAttribute('required', 'required');
        
        // 外籍生：就讀縣市和就讀國中改為非必填
        if (schoolCitySelect) {
          schoolCitySelect.removeAttribute('required');
        }
        if (schoolNameInput) {
          schoolNameInput.removeAttribute('required');
        }
        if (schoolCityRequired) {
          schoolCityRequired.style.display = 'none';
        }
        if (schoolNameRequired) {
          schoolNameRequired.style.display = 'none';
        }
        
        // 外籍生：戶籍地址改為非必填
        if (addressCity) addressCity.removeAttribute('required');
        if (addressDistrict) addressDistrict.removeAttribute('required');
        if (addressRoad) addressRoad.removeAttribute('required');
        if (addressNo) addressNo.removeAttribute('required');
        if (addressRequired) {
          addressRequired.style.display = 'none';
        }
        if (addressRequiredNote) {
          addressRequiredNote.style.display = 'none';
        }
        
        // 外籍生：通訊地址改為非必填（如果未勾選「同戶籍地址」）
        if (contactAddressInput) {
          contactAddressInput.removeAttribute('required');
        }
        if (contactAddressRequired) {
          contactAddressRequired.style.display = 'none';
        }
      } else {
        // 顯示本國籍欄位，隱藏外籍生欄位
        if (localFields) localFields.style.display = 'flex';
        if (foreignFields) foreignFields.style.display = 'none';
        
        // 設定必填屬性
        if (idInput) idInput.setAttribute('required', 'required');
        if (nationalityInput) {
          nationalityInput.removeAttribute('required');
          nationalityInput.value = ''; // 清空國籍
        }
        if (passportInput) {
          passportInput.removeAttribute('required');
          passportInput.value = ''; // 清空護照號碼
        }
        
        // 本國籍：就讀縣市和就讀國中為必填
        if (schoolCitySelect) {
          schoolCitySelect.setAttribute('required', 'required');
        }
        if (schoolNameInput) {
          schoolNameInput.setAttribute('required', 'required');
        }
        if (schoolCityRequired) {
          schoolCityRequired.style.display = 'inline';
        }
        if (schoolNameRequired) {
          schoolNameRequired.style.display = 'inline';
        }
        
        // 本國籍：戶籍地址為必填
        if (addressCity) addressCity.setAttribute('required', 'required');
        if (addressDistrict) addressDistrict.setAttribute('required', 'required');
        if (addressRoad) addressRoad.setAttribute('required', 'required');
        if (addressNo) addressNo.setAttribute('required', 'required');
        if (addressRequired) {
          addressRequired.style.display = 'inline';
        }
        if (addressRequiredNote) {
          addressRequiredNote.style.display = 'inline';
        }
        
        // 本國籍：通訊地址根據「同戶籍地址」選項決定是否必填
        // 如果未勾選「同戶籍地址」，則通訊地址必填
        const sameAddressCheckbox = document.querySelector('input[name="same_address"]');
        if (sameAddressCheckbox && !sameAddressCheckbox.checked && contactAddressInput) {
          contactAddressInput.setAttribute('required', 'required');
          if (contactAddressRequired) {
            contactAddressRequired.style.display = 'inline';
          }
        } else if (contactAddressInput) {
          contactAddressInput.removeAttribute('required');
          if (contactAddressRequired) {
            contactAddressRequired.style.display = 'none';
          }
        }
      }
    }
    
    // 頁面載入時初始化欄位顯示狀態
    window.toggleIdentityFields = toggleIdentityFields;
    
    // 初始化電話號碼驗證功能
    function initializePhoneValidation() {
      const mobileInput = document.getElementById('mobile');
      const guardianMobileInput = document.getElementById('guardian_mobile');
      
      // 電話號碼驗證函數
      function setupPhoneValidation(phoneInput) {
        if (!phoneInput) return;
        
        const hint = phoneInput.nextElementSibling;
        
        // 驗證函數
        function validatePhone() {
          const value = phoneInput.value.trim();
          if (value.length > 0 && value.length !== 10) {
            // 顯示錯誤狀態
            if (hint && hint.classList.contains('phone-hint')) {
              hint.style.display = 'block';
            }
            phoneInput.style.borderColor = '#d32f2f';
            phoneInput.style.borderWidth = '2px';
            phoneInput.classList.add('phone-error');
          } else {
            // 清除錯誤狀態
            if (hint && hint.classList.contains('phone-hint')) {
              hint.style.display = 'none';
            }
            phoneInput.style.borderColor = '';
            phoneInput.style.borderWidth = '';
            phoneInput.classList.remove('phone-error');
          }
        }
        
        // 只允許輸入數字
        phoneInput.addEventListener('input', function(e) {
          // 移除非數字字符
          this.value = this.value.replace(/[^0-9]/g, '');
          
          // 限制最大長度為10
          if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
          }
          
          // 即時驗證
          validatePhone();
        });
        
        // 失去焦點時驗證
        phoneInput.addEventListener('blur', function() {
          validatePhone();
        });
        
        // 獲得焦點時也檢查（處理初始值）
        phoneInput.addEventListener('focus', function() {
          validatePhone();
        });
        
        // 頁面載入時檢查初始值
        validatePhone();
      }
      
      // 設置兩個電話輸入框的驗證
      setupPhoneValidation(mobileInput);
      setupPhoneValidation(guardianMobileInput);
    }
    
    // 初始化電話號碼驗證功能
    function initializePhoneValidation() {
      const mobileInput = document.getElementById('mobile');
      const guardianMobileInput = document.getElementById('guardian_mobile');
      
      // 電話號碼驗證函數
      function setupPhoneValidation(phoneInput) {
        if (!phoneInput) return;
        
        const hint = phoneInput.nextElementSibling;
        
        // 驗證函數
        function validatePhone() {
          const value = phoneInput.value.trim();
          if (value.length > 0 && value.length !== 10) {
            // 顯示錯誤狀態
            if (hint && hint.classList.contains('phone-hint')) {
              hint.style.display = 'block';
            }
            phoneInput.style.borderColor = '#d32f2f';
            phoneInput.style.borderWidth = '2px';
            phoneInput.classList.add('phone-error');
          } else {
            // 清除錯誤狀態
            if (hint && hint.classList.contains('phone-hint')) {
              hint.style.display = 'none';
            }
            phoneInput.style.borderColor = '';
            phoneInput.style.borderWidth = '';
            phoneInput.classList.remove('phone-error');
          }
        }
        
        // 只允許輸入數字
        phoneInput.addEventListener('input', function(e) {
          // 移除非數字字符
          this.value = this.value.replace(/[^0-9]/g, '');
          
          // 限制最大長度為10
          if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
          }
          
          // 即時驗證
          validatePhone();
        });
        
        // 失去焦點時驗證
        phoneInput.addEventListener('blur', function() {
          validatePhone();
        });
        
        // 獲得焦點時也檢查（處理初始值）
        phoneInput.addEventListener('focus', function() {
          validatePhone();
        });
        
        // 頁面載入時檢查初始值
        validatePhone();
      }
      
      // 設置兩個電話輸入框的驗證
      setupPhoneValidation(mobileInput);
      setupPhoneValidation(guardianMobileInput);
    }
    
    // 初始化學校搜尋功能
    function initializeSchoolSearch() {
      const schoolInput = document.getElementById('school_name');
      const resultsDiv = document.getElementById('schoolResults');
      const clearBtn = document.getElementById('clearSchoolSearch');
      
      if (!schoolInput || !resultsDiv) {
        console.warn('學校搜尋元素未找到');
        return;
      }
      
      // 防抖函數
      let searchTimeout;
      const debounceSearch = (callback, delay) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(callback, delay);
      };
      
      // 輸入事件監聽
      schoolInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        
        // 顯示/隱藏清除按鈕
        if (clearBtn) {
          clearBtn.style.display = keyword.length > 0 ? 'block' : 'none';
        }
        
        if (keyword.length === 0) {
          resultsDiv.classList.remove('show');
          // 當搜尋結果隱藏時，清除錯誤提示
          clearSchoolError();
          return;
        }
        
        // 防抖搜尋
        debounceSearch(() => {
          performSchoolSearch(keyword);
        }, 300);
      });
      
      // 失去焦點時立即驗證
      schoolInput.addEventListener('blur', function() {
        clearTimeout(schoolInput.validationTimeout);
        // 延遲一點驗證，讓點擊下拉選單項目的時間完成
        schoolInput.validationTimeout = setTimeout(validateSchoolInputImmediate, 200);
      });
      
      // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
      schoolInput.addEventListener('focus', function() {
        const resultsDiv = document.getElementById('schoolResults');
        const value = this.value.trim();
        // 只有在下拉選單未顯示時才檢查錯誤
        if (value && !/^.+ \(.+\)$/.test(value) && 
            (!resultsDiv || !resultsDiv.classList.contains('show'))) {
          validateSchoolInput();
        }
      });
      
      // 清除按鈕事件
      if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          schoolInput.value = '';
          const schoolCodeInput = document.getElementById('school_code');
          const schoolCityActualInput = document.getElementById('school_city_actual');
          if (schoolCodeInput) schoolCodeInput.value = '';
          if (schoolCityActualInput) schoolCityActualInput.value = '';
          resultsDiv.classList.remove('show');
          clearBtn.style.display = 'none';
          clearSchoolError();
          clearCityMismatchError();
          schoolInput.focus();
        });
      }
      
      // 為縣市下拉選單添加 change 事件監聽器
      const schoolCitySelect = document.getElementById('school_city');
      if (schoolCitySelect) {
        schoolCitySelect.addEventListener('change', function() {
          // 當用戶手動改變縣市時，驗證是否與選擇的學校一致
          validateCitySchoolMatch();
        });
      }
      
      // 點擊其他地方隱藏搜尋結果
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.modern-search-container')) {
          const resultsDiv = document.getElementById('schoolResults');
          if (resultsDiv && resultsDiv.classList.contains('show')) {
            resultsDiv.classList.remove('show');
            // 當下拉選單隱藏時，驗證輸入
            setTimeout(validateSchoolInput, 100);
          }
        }
      });
    }
    
    // 執行學校搜尋
    function performSchoolSearch(keyword) {
      const resultsDiv = document.getElementById('schoolResults');
      const schoolInput = document.getElementById('school_name');
      
      if (keyword.length < 2) {
        resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
        resultsDiv.classList.add('show');
        // 當下拉選單顯示時，清除錯誤提示（用戶還在輸入中）
        clearSchoolError();
        return;
      }
      
      // 顯示載入中
      resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
      resultsDiv.classList.add('show');
      // 當下拉選單顯示時，清除錯誤提示（用戶還在選擇中）
      clearSchoolError();
      
      // 從API獲取搜尋結果
      fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}&v=20241014-4`)
        .then(response => response.json())
        .then(data => {
          console.log('搜尋結果:', data);
          if (data.schools && data.schools.length > 0) {
            resultsDiv.innerHTML = data.schools.map(school => {
              let displayName = school.name;
              let additionalInfo = '';
              
              if (school.all_names && school.all_names.length > 1) {
                additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
              }
              
              const schoolCode = school.school_code || '';
              return `<div class="search-result-item" onclick="selectSchool('${school.name.replace(/'/g, "\\'")}', '${school.city || ''}', '${school.district || ''}', '${schoolCode.replace(/'/g, "\\'")}')">
                <i class="fas fa-school"></i>
                <div class="school-info">
                  <span class="school-name">${displayName}</span>
                  <span class="school-location">${school.city || ''} ${school.district || ''}</span>
                  ${additionalInfo}
                </div>
              </div>`;
            }).join('');
            
            if (data.total > 20) {
              resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
            }
            // 當下拉選單顯示時，清除錯誤提示
            clearSchoolError();
          } else {
            resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
            // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
            clearSchoolError();
          }
        })
        .catch(error => {
          console.error('搜尋錯誤:', error);
          resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
          // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
          clearSchoolError();
        });
    }
    
    // 清除學校輸入錯誤提示
    function clearSchoolError() {
      const errorDiv = document.getElementById('school_name_error');
      const input = document.getElementById('school_name');
      if (errorDiv) {
        errorDiv.style.display = 'none';
      }
      if (input) {
        input.style.borderColor = '';
        input.style.borderWidth = '';
        input.style.boxShadow = '';
      }
    }
    
    // 顯示學校輸入錯誤提示
    function showSchoolError(message) {
      const errorDiv = document.getElementById('school_name_error');
      const errorText = document.getElementById('school_name_error_text');
      const input = document.getElementById('school_name');
      
      if (errorDiv && errorText) {
        errorText.textContent = message || '請從系統提供的選項中選擇學校，不能自行輸入';
        errorDiv.style.display = 'block';
        // 添加動畫效果
        errorDiv.style.animation = 'none';
        setTimeout(() => {
          errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
      }
      
      if (input) {
        input.style.borderColor = '#d32f2f';
        input.style.borderWidth = '2px';
        input.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
      }
    }
    
    // 驗證學校輸入格式
    function validateSchoolInput() {
      const input = document.getElementById('school_name');
      if (!input) return;
      
      const value = input.value.trim();
      const resultsDiv = document.getElementById('schoolResults');
      
      // 如果為空，不顯示錯誤（由required屬性處理）
      if (!value) {
        clearSchoolError();
        return;
      }
      
      // 如果下拉選單正在顯示，表示用戶還在選擇中，不顯示錯誤
      if (resultsDiv && resultsDiv.classList.contains('show')) {
        clearSchoolError();
        return;
      }
      
      // 檢查格式是否為：學校名稱 (縣市區)
      const schoolFormatPattern = /^.+ \(.+\)$/;
      if (!schoolFormatPattern.test(value)) {
        // 只有在下拉選單隱藏時才顯示錯誤
        showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
      } else {
        clearSchoolError();
      }
    }
    
    // 立即驗證（不延遲）- 用於失去焦點時
    function validateSchoolInputImmediate() {
      validateSchoolInput();
    }
    
    // 縣市名稱對應表（處理「臺」vs「台」等變體）
    const cityNameMap = {
      '臺北市': '台北市',
      '台北市': '台北市',
      '臺中市': '台中市',
      '台中市': '台中市',
      '臺南市': '台南市',
      '台南市': '台南市',
      '臺東縣': '台東縣',
      '台東縣': '台東縣'
    };
    
    // 標準化縣市名稱
    function normalizeCityName(city) {
      if (!city) return '';
      // 先檢查對應表
      if (cityNameMap[city]) {
        return cityNameMap[city];
      }
      // 處理「臺」轉「台」
      return city.replace(/臺/g, '台');
    }
    
    // 選擇學校
    function selectSchool(schoolName, city, district, schoolCode) {
      const schoolInput = document.getElementById('school_name');
      const schoolCodeInput = document.getElementById('school_code');
      const schoolCityActualInput = document.getElementById('school_city_actual');
      const schoolCitySelect = document.getElementById('school_city');
      const resultsDiv = document.getElementById('schoolResults');
      const clearBtn = document.getElementById('clearSchoolSearch');
      
      // 設置學校名稱（格式：學校名稱 (縣市區)）
      if (schoolInput) {
        const fullSchoolName = `${schoolName} (${city || ''}${district || ''})`;
        schoolInput.value = fullSchoolName;
      }
      
      // 存儲學校代碼和實際縣市
      if (schoolCodeInput && schoolCode) {
        schoolCodeInput.value = schoolCode;
      }
      if (schoolCityActualInput && city) {
        schoolCityActualInput.value = city;
      }
      
      // 自動設置縣市（精確匹配）
      if (schoolCitySelect && city) {
        const normalizedCity = normalizeCityName(city);
        const options = schoolCitySelect.options;
        let matched = false;
        
        // 優先精確匹配
        for (let i = 0; i < options.length; i++) {
          const optionValue = normalizeCityName(options[i].value);
          const optionText = normalizeCityName(options[i].text);
          
          if (optionValue === normalizedCity || optionText === normalizedCity) {
            schoolCitySelect.value = options[i].value;
            matched = true;
            break;
          }
        }
        
        // 如果精確匹配失敗，嘗試包含匹配
        if (!matched) {
          for (let i = 0; i < options.length; i++) {
            const optionValue = normalizeCityName(options[i].value);
            const optionText = normalizeCityName(options[i].text);
            
            if (optionValue.includes(normalizedCity) || normalizedCity.includes(optionValue) ||
                optionText.includes(normalizedCity) || normalizedCity.includes(optionText)) {
              schoolCitySelect.value = options[i].value;
              matched = true;
              break;
            }
          }
        }
        
        // 如果匹配成功，清除縣市不一致錯誤
        if (matched) {
          clearCityMismatchError();
        }
      }
      
      // 隱藏搜尋結果
      if (resultsDiv) {
        resultsDiv.classList.remove('show');
      }
      
      // 顯示清除按鈕
      if (clearBtn) {
        clearBtn.style.display = 'block';
      }
      
      // 清除錯誤提示（因為用戶已從系統選項中選擇）
      clearSchoolError();
    }
    
    // 清除縣市不一致錯誤
    function clearCityMismatchError() {
      const errorDiv = document.getElementById('school_city_mismatch_error');
      const citySelect = document.getElementById('school_city');
      if (errorDiv) {
        errorDiv.style.display = 'none';
      }
      if (citySelect) {
        citySelect.style.borderColor = '';
        citySelect.style.borderWidth = '';
        citySelect.style.boxShadow = '';
      }
    }
    
    // 顯示縣市不一致錯誤
    function showCityMismatchError(message) {
      const errorDiv = document.getElementById('school_city_mismatch_error');
      const errorText = document.getElementById('school_city_mismatch_error_text');
      const citySelect = document.getElementById('school_city');
      
      if (errorDiv && errorText) {
        errorText.textContent = message || '就讀縣市與選擇的學校所在縣市不一致，系統已自動更新為正確的縣市';
        errorDiv.style.display = 'block';
        errorDiv.style.animation = 'none';
        setTimeout(() => {
          errorDiv.style.animation = 'slideDown 0.3s ease';
        }, 10);
      }
      
      if (citySelect) {
        citySelect.style.borderColor = '#d32f2f';
        citySelect.style.borderWidth = '2px';
        citySelect.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
      }
      
      // 3秒後自動清除錯誤提示和紅色框框
      setTimeout(() => {
        clearCityMismatchError();
      }, 3000);
    }
    
    // 驗證縣市與學校是否一致
    function validateCitySchoolMatch() {
      const schoolCitySelect = document.getElementById('school_city');
      const schoolCityActualInput = document.getElementById('school_city_actual');
      const schoolInput = document.getElementById('school_name');
      
      if (!schoolCitySelect || !schoolCityActualInput || !schoolInput) {
        return true; // 如果元素不存在，跳過驗證
      }
      
      const selectedCity = schoolCitySelect.value;
      const actualCity = schoolCityActualInput.value;
      const schoolName = schoolInput.value.trim();
      
      // 如果沒有選擇學校，不需要驗證
      if (!schoolName || !actualCity) {
        clearCityMismatchError();
        return true;
      }
      
      // 標準化縣市名稱後比較
      const normalizedSelected = normalizeCityName(selectedCity);
      const normalizedActual = normalizeCityName(actualCity);
      
      if (normalizedSelected && normalizedActual && normalizedSelected !== normalizedActual) {
        // 縣市不一致，自動修正
        const options = schoolCitySelect.options;
        for (let i = 0; i < options.length; i++) {
          const optionValue = normalizeCityName(options[i].value);
          if (optionValue === normalizedActual) {
            schoolCitySelect.value = options[i].value;
            showCityMismatchError('就讀縣市與選擇的學校所在縣市不一致，已自動更新為正確的縣市');
            return false; // 已自動修正，但返回 false 表示需要用戶確認
          }
        }
        // 如果找不到匹配的選項，顯示錯誤
        showCityMismatchError('就讀縣市與選擇的學校所在縣市不一致，請選擇正確的縣市');
        return false;
      }
      
      // 縣市一致，清除錯誤
      clearCityMismatchError();
      return true;
    }
    
    // 將函數暴露到全局作用域
    window.selectSchool = selectSchool;
    
    // 填充表單資料
    function fillFormWithData(formData) {
      console.log('收到的表單資料:', formData);
      console.log('開始填充表單資料，姓名值:', formData.student_name);
      
      // 處理出生日期：將年、月、日組合成日期選擇器的值
      if (formData.birth_year && formData.birth_month && formData.birth_day) {
        const year = String(formData.birth_year).padStart(4, '0');
        const month = String(formData.birth_month).padStart(2, '0');
        const day = String(formData.birth_day).padStart(2, '0');
        const birthDateValue = `${year}-${month}-${day}`;
        const birthDateInput = document.getElementById('birth_date');
        if (birthDateInput) {
          birthDateInput.value = birthDateValue;
          // 同時設置隱藏欄位
          document.getElementById('birth_year').value = year;
          document.getElementById('birth_month').value = month;
          document.getElementById('birth_day').value = day;
        }
      }
      
      // 填充基本資料
      const fields = [
        'exam_no', 'student_name', 'id', 'gender',
        'phone', 'mobile', 'school_city', 'school_name', 'zip', 'city', 'district',
        'village', 'neighbor', 'road', 'section', 'lane', 'alley', 'no', 'floor',
        'guardian', 'guardian_phone', 'guardian_mobile', 'self_intro', 'skills'
      ];
      
      fields.forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        console.log(`處理欄位 ${field}:`, {
          input: input,
          value: formData[field],
          hasValue: formData[field] !== undefined
        });
        
        if (input && formData[field] !== undefined) {
          if (input.type === 'radio') {
            const radioInput = document.querySelector(`[name="${field}"][value="${formData[field]}"]`);
            if (radioInput) radioInput.checked = true;
          } else {
            input.value = formData[field];
            console.log(`已設置 ${field} 的值:`, formData[field]);
            
            // 特別檢查姓名字段
            if (field === 'student_name') {
              console.log('姓名字段詳細信息:', {
                input: input,
                inputValue: input.value,
                inputDisplay: input.style.display,
                inputVisibility: input.style.visibility,
                inputOpacity: input.style.opacity,
                inputDisabled: input.disabled,
                inputReadOnly: input.readOnly,
                parentDisplay: input.parentElement.style.display,
                parentVisibility: input.parentElement.style.visibility,
                computedStyle: window.getComputedStyle(input),
                formDisabled: input.form ? input.form.disabled : 'no form',
                formContent: document.getElementById('formContent') ? document.getElementById('formContent').classList.toString() : 'no formContent',
                isFormDisabled: isFormDisabled
              });
              
              // 只有在表單不是只讀模式時才強制設為可編輯
              if (!isFormDisabled) {
                // 強制確保姓名字段可見和可編輯
                input.disabled = false;
                input.readOnly = false;
                input.style.display = 'block';
                input.style.visibility = 'visible';
                input.style.opacity = '1';
                input.style.background = '#fff';
                input.style.color = '#333';
                console.log('姓名字段設為可編輯模式');
              } else {
                console.log('表單處於只讀模式，保持姓名字段只讀狀態');
              }
              
              // 再次設置值
              input.value = formData[field];
              console.log('姓名字段設置後的值:', input.value);
            }
          }
        } else {
          console.log(`跳過欄位 ${field}:`, {
            inputFound: !!input,
            hasValue: formData[field] !== undefined
          });
        }
      });
      
      // 處理地址相同選項
      if (formData.same_address === 'yes') {
        const sameAddressInput = document.querySelector('[name="same_address"][value="yes"]');
        if (sameAddressInput) sameAddressInput.checked = true;
      }
      
      // 處理是否為外籍生選項
      if (formData.is_foreign_student !== undefined) {
        const foreignStudentValue = formData.is_foreign_student === 'yes' || formData.is_foreign_student === true ? 'yes' : 'no';
        const foreignStudentRadio = document.querySelector(`input[name="is_foreign_student"][value="${foreignStudentValue}"]`);
        if (foreignStudentRadio) {
          foreignStudentRadio.checked = true;
          // 觸發切換函數以更新顯示的欄位
          if (typeof toggleIdentityFields === 'function') {
            toggleIdentityFields();
          }
        }
      }
      
      // 填充外籍生相關欄位（國籍、護照號碼）
      if (formData.nationality !== undefined) {
        const nationalityInput = document.getElementById('nationality');
        if (nationalityInput) {
          nationalityInput.value = formData.nationality || '';
        }
      }
      if (formData.passport_number !== undefined) {
        const passportInput = document.getElementById('passport_number');
        if (passportInput) {
          passportInput.value = formData.passport_number || '';
        }
      }
      
      // 填充志願序
      if (formData.choices && Array.isArray(formData.choices)) {
        selectedChoices = formData.choices;
        updateSelectedChoicesDisplay();
        updateHiddenFields();
      }
      
      // 填充文件上傳信息
      if (formData.documents && Array.isArray(formData.documents)) {
        // 顯示已上傳的文件信息
        console.log('已上傳的文件:', formData.documents);
        
        // 填充文件上傳狀態
        formData.documents.forEach(doc => {
          if (doc.type && doc.filename) {
            // 勾選對應的checkbox
            const checkbox = document.querySelector(`input[name="docs[]"][value="${doc.type}"]`);
            if (checkbox) {
              checkbox.checked = true;
            }
            
            // 顯示文件名稱
            const fileInput = document.querySelector(`input[name="doc_${doc.type}"]`);
            if (fileInput) {
              // 創建一個顯示已上傳文件的元素
              const fileDisplay = document.createElement('div');
              fileDisplay.className = 'uploaded-file';
              fileDisplay.innerHTML = `
                <i class="fas fa-file"></i>
                <span>已上傳: ${doc.filename}</span>
              `;
              fileDisplay.style.cssText = `
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 8px 12px;
                border-radius: 4px;
                margin-top: 5px;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 8px;
              `;
              
              // 將顯示元素插入到文件輸入框後面
              fileInput.parentNode.insertBefore(fileDisplay, fileInput.nextSibling);
            }
          }
        });
      }
      
      // 強制確保姓名字段可見
      setTimeout(() => {
        // 只有在表單不是只讀模式時才強制移除禁用狀態
        if (!isFormDisabled) {
          const formContent = document.getElementById('formContent');
          if (formContent) {
            formContent.classList.remove('form-readonly', 'form-disabled');
            console.log('強制移除表單禁用狀態');
          }
        }
        
        const nameInput = document.querySelector('input[name="student_name"]');
        if (nameInput) {
          console.log('強制檢查姓名字段:', {
            value: nameInput.value,
            disabled: nameInput.disabled,
            style: nameInput.style.cssText,
            computedStyle: window.getComputedStyle(nameInput),
            parentElement: nameInput.parentElement,
            parentStyle: nameInput.parentElement ? window.getComputedStyle(nameInput.parentElement) : null,
            isFormDisabled: isFormDisabled
          });
          
          // 只有在表單不是只讀模式時才強制啟用輸入框
          if (!isFormDisabled) {
            // 強制啟用輸入框
            nameInput.disabled = false;
            nameInput.readOnly = false;
          
            // 強制設置樣式確保可見
            nameInput.style.display = 'block';
            nameInput.style.visibility = 'visible';
            nameInput.style.opacity = '1';
            nameInput.style.background = '#fff';
            nameInput.style.color = '#333';
            nameInput.style.border = '2px solid #e1e5e9';
            nameInput.style.padding = '12px 15px';
            nameInput.style.borderRadius = '8px';
            nameInput.style.fontSize = '14px';
            nameInput.style.width = '100%';
            nameInput.style.boxSizing = 'border-box';
          } else {
            console.log('表單處於只讀模式，保持姓名字段只讀狀態');
          }
          
          // 確保正確值被設置
          if (formData.student_name) {
            // 方法1：設置value屬性
            nameInput.value = formData.student_name;
            
            // 方法2：直接設置HTML屬性
            nameInput.setAttribute('value', formData.student_name);
            
            // 方法3：使用defaultValue
            nameInput.defaultValue = formData.student_name;
            
            console.log('最終設置姓名值:', formData.student_name);
            console.log('設置後輸入框的值:', nameInput.value);
            console.log('設置後輸入框的屬性值:', nameInput.getAttribute('value'));
            console.log('設置後輸入框的defaultValue:', nameInput.defaultValue);
            console.log('設置後輸入框的顯示值:', nameInput.outerHTML);
            
            // 觸發事件確保瀏覽器知道值已改變
            nameInput.dispatchEvent(new Event('input', { bubbles: true }));
            nameInput.dispatchEvent(new Event('change', { bubbles: true }));
            nameInput.dispatchEvent(new Event('blur', { bubbles: true }));
            nameInput.dispatchEvent(new Event('focus', { bubbles: true }));
            
            // 強制重新渲染
            nameInput.style.display = 'none';
            nameInput.offsetHeight; // 觸發重排
            nameInput.style.display = 'block';
            
            // 最後檢查
            setTimeout(() => {
              console.log('延遲檢查 - 輸入框的值:', nameInput.value);
              console.log('延遲檢查 - 輸入框的屬性值:', nameInput.getAttribute('value'));
              console.log('延遲檢查 - 輸入框的HTML:', nameInput.outerHTML);
            }, 50);
          }
        } else {
          console.error('找不到姓名字段！');
        }
      }, 100);
    }
  </script>

<?php include("share/footer.php"); ?>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
<?php include("share/ai_widget.php"); ?>
</body>
</html>
