<?php
// 載入 session 配置
require_once 'session_config.php';

// 載入 reCAPTCHA 設定
require_once '../backend/config/recaptcha_config.php';

// 驗證碼將由 captcha_image.php 生成

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

// 初始化變數
$db_error = '';
$teachers = [];
$grades = [];
$courses = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 獲取所有老師資料（從 teacher 表查詢科系，從 user 表查詢姓名和ID，從 departments 表查詢科系名稱）
    try {
        $stmt = $pdo->prepare("SELECT u.id AS user_id, u.name AS name, d.name AS department 
                              FROM teacher t 
                              JOIN user u ON t.user_id = u.id 
                              LEFT JOIN departments d ON t.department = d.code
                              ORDER BY t.department, u.name");
        $stmt->execute();
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $teachers = [];
    }
    
    // 獲取年級選項（從 identity_options 表，只取國中年級 J1, J2, J3）
    try {
        $grades_stmt = $pdo->prepare("SELECT code, name FROM identity_options WHERE code IN ('J1', 'J2', 'J3') ORDER BY code");
        $grades_stmt->execute();
        $grades = $grades_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("查詢年級選項失敗: " . $e->getMessage());
        $grades = [['code' => 'J1', 'name' => '國一'], ['code' => 'J2', 'name' => '國二'], ['code' => 'J3', 'name' => '國三']];
    }
    
    // 獲取啟用的科系選項（從 admission_courses 表，如果表不存在則使用預設值）
    try {
        $courses_stmt = $pdo->prepare("SELECT course_name FROM admission_courses WHERE is_active = 1 ORDER BY sort_order, id");
        $courses_stmt->execute();
        $courses = $courses_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("查詢科系選項失敗（admission_courses 表可能不存在）: " . $e->getMessage());
        $courses = ['護理科', '嬰幼兒保育科', '視光科', '數位影視動畫科', '資訊管理科', '企業管理科', '應用外語科', '長期照護學系'];
    }
} catch (PDOException $e) {
    $db_error = "資料庫連線失敗，請檢查設定: " . $e->getMessage();
    error_log("資料庫連線失敗: " . $e->getMessage());
}

// 權限檢查已移除 - 任何人都可以訪問此頁面
// 如果需要權限檢查，請取消註解下面的程式碼：
/*
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== '學生' && $_SESSION['role'] !== 'student')) {
    header('Location: index.php');
    exit;
}
*/

$username = $_SESSION['username'] ?? '訪客';
$role = $_SESSION['role'] ?? '訪客';
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學就讀意願登錄</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/csp/cooperation_upload.css?v=20241014-3">
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

<body>
    <?php include("share/header.php"); ?>
    <div class="cooperation-page-wrapper">
        <div class="cooperation-container">
            <div class="cooperation-header" style="background: #667eea !important;">
                <h1><i class="fas fa-graduation-cap"></i> 康寧大學就讀意願登錄</h1>
                <p>填寫您的就讀意願，我們將儘快與您聯絡</p>
            </div>

            <div class="form-container">
                <div class="form-description">
                    <i class="fas fa-info-circle"></i> *為必填之欄位，康寧大學收到資料後將儘快與您聯絡！
                </div>

                <div id="message"></div>

                <form id="enrollmentForm">
                    <!-- 個人基本資料 -->
                    <h3 class="section-title"><i class="fas fa-user"></i> 個人基本資料</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><span class="required">*</span>姓名:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label><span class="required">*</span>身分別:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="identity" value="學生" required checked>
                                    學生
                                </label>
                                <label>
                                    <input type="radio" name="identity" value="家長" required>
                                    家長
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>性別:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="gender" value="男" checked>
                                    男
                                </label>
                                <label>
                                    <input type="radio" name="gender" value="女">
                                    女
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone1"><span class="required">*</span>聯絡電話1:</label>
                            <input type="tel" id="phone1" name="phone1" 
                                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼" required>
                            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼輸入錯誤</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone2">聯絡電話2:</label>
                            <input type="tel" id="phone2" name="phone2" 
                                   pattern="[0-9]{10}" maxlength="10" placeholder="請輸入電話號碼（選填）">
                            <small class="phone-hint" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 4px;">電話號碼輸入錯誤</small>
                        </div>
                        <div class="form-group">
                            <label for="email"><span class="required">*</span>電子郵件信箱:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <!-- 就讀意願 -->
                    <h3 class="section-title"><i class="fas fa-heart"></i> 就讀意願</h3>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="intention1">就讀意願一:</label>
                            <select id="intention1" name="intention1">
                                <option value="無特定">無特定</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>">
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="system1">學制:</label>
                            <select id="system1" name="system1">
                                <option value="">請選擇</option>
                                <option value="五專">五專</option>
                                <option value="四技">四技</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="intention2">就讀意願二:</label>
                            <select id="intention2" name="intention2">
                                <option value="無特定">無特定</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>">
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="system2">學制:</label>
                            <select id="system2" name="system2">
                                <option value="">請選擇</option>
                                <option value="五專">五專</option>
                                <option value="四技">四技</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="intention3">就讀意願三:</label>
                            <select id="intention3" name="intention3">
                                <option value="無特定">無特定</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>">
                                        <?php echo htmlspecialchars($course); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="system3">學制:</label>
                            <select id="system3" name="system3">
                                <option value="">請選擇</option>
                                <option value="五專">五專</option>
                                <option value="四技">四技</option>
                            </select>
                        </div>
                    </div>

                    <!-- 就讀或畢業國中資訊 -->
                    <h3 class="section-title"><i class="fas fa-school"></i> 就讀或畢業國中資訊</h3>

                    <div class="form-group">
                        <label for="junior_high_display"><span class="required">*</span>就讀或畢業國中:</label>
                        <div class="modern-search-container">
                            <div class="search-input-wrapper">
                                <input type="text" id="junior_high_display" placeholder="請輸入學校名稱..." autocomplete="off" required>
                                <input type="hidden" id="junior_high" name="junior_high" value="">
                                <div class="search-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="clear-btn" id="clearSearch" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </div>
                            </div>
                            <div id="schoolResults" class="modern-search-results"></div>
                        </div>
                        <div class="help-text">
                            <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，請從搜尋結果中選擇學校（不能自行輸入）
                        </div>
                        <div id="junior_high_error" class="field-error" style="display: none; color: #d32f2f; font-size: 13px; margin-top: 8px; padding: 8px 12px; background-color: #ffebee; border-left: 3px solid #d32f2f; border-radius: 4px; animation: slideDown 0.3s ease;">
                            <i class="fas fa-exclamation-circle"></i> <span id="junior_high_error_text">請從系統提供的選項中選擇學校，不能自行輸入</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="current_grade"><span class="required">*</span>請選擇目前年級....</label>
                        <select id="current_grade" name="current_grade" required>
                            <option value="">請選擇年級</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade['code']); ?>">
                                    <?php echo htmlspecialchars($grade['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 社群媒體資訊 -->
                    <h3 class="section-title"><i class="fas fa-share-alt"></i> 社群媒體資訊</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="line_id">LineID:</label>
                            <input type="text" id="line_id" name="line_id">
                        </div>
                        <div class="form-group">
                            <label for="facebook">Facebook:</label>
                            <input type="text" id="facebook" name="facebook">
                        </div>
                    </div>

                    <!-- 推薦老師資訊 -->
                    <h3 class="section-title"><i class="fas fa-chalkboard-teacher"></i> 推薦老師資訊</h3>

                    <div class="form-group">
                        <label for="recommended_teacher">推薦老師:</label>
                        <select id="recommended_teacher" name="recommended_teacher">
                            <option value="">請選擇推薦老師（可選）</option>
                            <?php if (!empty($db_error)): ?>
                                <option value="" disabled style="color: red;"><?php echo htmlspecialchars($db_error); ?></option>
                            <?php elseif (!empty($teachers)): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo htmlspecialchars($teacher['user_id'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($teacher['name'] ?? ''); ?> - <?php echo htmlspecialchars($teacher['department'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>目前沒有可選擇的推薦老師（查詢到 <?php echo count($teachers ?? []); ?> 筆）</option>
                            <?php endif; ?>
                        </select>
                        <div class="help-text">可選擇推薦您的老師，或留空</div>
                    </div>

                    <!-- 備註 -->
                    <h3 class="section-title"><i class="fas fa-sticky-note"></i> 備註</h3>

                    <div class="form-group">
                        <label for="remarks">備註:</label>
                        <textarea id="remarks" name="remarks" rows="4"></textarea>
                    </div>

                    <!-- 驗證碼 -->
                    <div class="captcha-section">
                        <h4>驗證碼 <span class="required">*</span></h4>
                        <div class="captcha-container" style="display: flex; align-items: center; gap: 10px; margin: 15px 0;">
                            <input type="text" id="captchaInput" name="captcha" placeholder="請輸入驗證碼" maxlength="4" required autocomplete="off" style="flex: 1; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')" title="請輸入驗證碼圖片中顯示的4位字母和數字（不包含0、O、1、I，不區分大小寫）">
                            <div id="captchaImageContainer" style="height: 50px; width: 180px; border: 2px solid #ddd; border-radius: 5px; display: inline-block; vertical-align: middle; overflow: hidden;">
                                <img src="captcha_image.php" id="captchaImage" alt="驗證碼" onclick="refreshCaptcha()" style="height: 50px; width: 180px; cursor: pointer; display: block;" title="點擊刷新驗證碼" onerror="handleCaptchaError(this)">
                            </div>
                            <div id="captchaError" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 5px; padding: 5px; background: #ffebee; border-radius: 3px;">
                                <i class="fas fa-exclamation-triangle"></i> GD 擴展未啟用，已使用文字驗證碼模式
                            </div>
                            <button type="button" class="refresh-captcha" onclick="refreshCaptcha()" style="padding: 10px 15px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; width:50%;">
                                <i class="fas fa-sync-alt"></i> 刷新
                            </button>
                        </div>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> 請輸入圖片中顯示的4位字母和數字（不包含0、O、1、I，不區分大小寫）
                        </small>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> 同意送出
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 驗證碼錯誤處理
        function handleCaptchaError(img) {
            console.warn('驗證碼圖片載入失敗，嘗試載入 HTML 備用方案');
            const container = document.getElementById('captchaImageContainer');
            const errorDiv = document.getElementById('captchaError');
            
            // 隱藏圖片，載入 HTML 備用方案
            if (img) {
                img.style.display = 'none';
            }
            
            // 使用 iframe 或直接載入 HTML 內容
            fetch('captcha_image.php?t=' + new Date().getTime())
                .then(response => response.text())
                .then(html => {
                    if (container) {
                        // 如果返回的是 SVG 或 HTML，直接插入
                        if (html.includes('<svg') || html.includes('<div')) {
                            container.innerHTML = html;
                            container.style.cursor = 'pointer';
                            container.onclick = refreshCaptcha;
                        }
                    }
                    // 顯示提示信息
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('無法載入驗證碼:', err);
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> 驗證碼載入失敗，請刷新頁面重試';
                    }
                });
        }

        // 驗證碼刷新功能
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captchaImage');
            const captchaInput = document.getElementById('captchaInput');
            const errorDiv = document.getElementById('captchaError');
            
            // 清空輸入框和錯誤訊息
            if (captchaInput) {
                captchaInput.value = '';
            }
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
            
            // 刷新驗證碼圖片（添加 refresh=1 參數強制生成新驗證碼，添加時間戳防止緩存）
            if (captchaImage) {
                captchaImage.src = 'captcha_image.php?refresh=1&t=' + new Date().getTime();
            }
        }

        // 即時搜尋功能 - 使用教育部API
        function performSearch() {
            const keyword = document.getElementById('junior_high_display').value.trim();
            const resultsDiv = document.getElementById('schoolResults');
            const clearBtn = document.getElementById('clearSearch');

            // 顯示/隱藏清除按鈕
            if (keyword.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
                resultsDiv.classList.remove('show');
                // 當搜尋結果隱藏時，清除錯誤提示
                clearSchoolError();
                return;
            }

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
                .then(response => {
                    if (!response.ok) {
                        throw new Error('API 請求失敗: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('搜尋結果:', data); // 調試信息
                    
                    // 過濾掉沒有 school_code 的學校
                    const validSchools = (data.schools || []).filter(school => {
                        return school && school.school_code && school.school_code.trim() !== '';
                    });
                    
                    if (validSchools.length > 0) {
                        resultsDiv.innerHTML = validSchools.map(school => {
                            // 如果有多個名稱，顯示整合資訊
                            let displayName = school.name || '未命名學校';
                            let additionalInfo = '';
                            
                            if (school.all_names && school.all_names.length > 1) {
                                additionalInfo = `<div class="school-alternative-names">其他名稱: ${school.all_names.join(', ')}</div>`;
                            }
                            
                            const schoolCode = school.school_code;
                            const city = school.city || '';
                            const district = school.district || '';
                            
                            // 使用 data 屬性存儲數據，避免 onclick 中的轉義問題
                            return `<div class="search-result-item" 
                                     data-school-name="${displayName.replace(/"/g, '&quot;')}" 
                                     data-city="${city.replace(/"/g, '&quot;')}" 
                                     data-district="${district.replace(/"/g, '&quot;')}" 
                                     data-school-code="${schoolCode.replace(/"/g, '&quot;')}"
                                     onclick="selectSchoolFromElement(this)">
                                <i class="fas fa-school"></i>
                                <div class="school-info">
                                    <span class="school-name">${displayName}</span>
                                    <span class="school-location">${city} ${district}</span>
                                    ${additionalInfo}
                                </div>
                            </div>`;
                        }).join('');

                        if (data.total > 20) {
                            resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                        }
                        
                        // 確保下拉選單顯示
                        resultsDiv.classList.add('show');
                        // 當下拉選單顯示時，清除錯誤提示
                        clearSchoolError();
                    } else {
                        resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                        resultsDiv.classList.add('show');
                        // 即使找不到結果，下拉選單仍然顯示，所以清除錯誤提示
                        clearSchoolError();
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
                    resultsDiv.classList.add('show');
                    // 即使搜尋失敗，下拉選單仍然顯示，所以清除錯誤提示
                    clearSchoolError();
                });
        }

        // 清除學校輸入錯誤提示
        function clearSchoolError() {
            const errorDiv = document.getElementById('junior_high_error');
            const input = document.getElementById('junior_high_display');
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
            const errorDiv = document.getElementById('junior_high_error');
            const errorText = document.getElementById('junior_high_error_text');
            const input = document.getElementById('junior_high_display');
            
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
            const input = document.getElementById('junior_high_display');
            const hiddenInput = document.getElementById('junior_high');
            if (!input || !hiddenInput) return;
            
            const value = input.value.trim();
            const schoolCode = hiddenInput.value.trim();
            const resultsDiv = document.getElementById('schoolResults');
            
            // 調試資訊
            console.log('validateSchoolInput 被調用:', {
                value: value,
                schoolCode: schoolCode,
                resultsDivShowing: resultsDiv && resultsDiv.classList.contains('show')
            });
            
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
            
            // 檢查是否有選擇 school_code（表示用戶已從系統選項中選擇）
            // 如果有 school_code，說明用戶已經選擇了，清除錯誤
            if (schoolCode && schoolCode.trim() !== '') {
                clearSchoolError();
                return;
            }
            
            // 如果沒有 school_code 但有顯示值，說明用戶可能是手動輸入的
            // 但只有在確實有輸入值且沒有 school_code 時才顯示錯誤
            if (value && !schoolCode) {
                console.warn('驗證失敗: 有顯示值但沒有 school_code');
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
            } else {
                clearSchoolError();
            }
        }
        
        // 立即驗證（不延遲）- 用於失去焦點時
        function validateSchoolInputImmediate() {
            validateSchoolInput();
        }
        
        // 清除搜尋
        function clearSearch() {
            document.getElementById('junior_high').value = '';
            document.getElementById('junior_high_display').value = '';
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSearch').style.display = 'none';
            clearSchoolError();
        }

        // 從元素選擇學校（使用 data 屬性，更安全）
        function selectSchoolFromElement(element) {
            const schoolName = element.getAttribute('data-school-name') || '';
            const city = element.getAttribute('data-city') || '';
            const district = element.getAttribute('data-district') || '';
            const schoolCode = element.getAttribute('data-school-code') || '';
            
            selectSchool(schoolName, city, district, schoolCode);
        }
        
        // 選擇學校
        function selectSchool(schoolName, city, district, schoolCode) {
            // 直接儲存 school_code 到 junior_high 欄位（用於外鍵關聯）
            // 同時保存顯示名稱用於驗證和顯示
            const fullSchoolName = `${schoolName} (${city}${district})`;
            
            // 調試資訊
            console.log('選擇學校:', { schoolName, city, district, schoolCode });
            
            // 檢查 schoolCode 是否存在
            if (!schoolCode || schoolCode.trim() === '') {
                console.error('錯誤: school_code 為空！', { schoolName, city, district, schoolCode });
                alert('錯誤：無法獲取學校代碼，請重新選擇學校');
                return;
            }
            
            const juniorHighInput = document.getElementById('junior_high');
            const juniorHighDisplayInput = document.getElementById('junior_high_display');
            
            if (!juniorHighInput || !juniorHighDisplayInput) {
                console.error('錯誤: 找不到輸入框元素');
                return;
            }
            
            juniorHighInput.value = schoolCode.trim(); // 直接儲存 school_code
            juniorHighDisplayInput.value = fullSchoolName; // 保存顯示名稱用於驗證
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSearch').style.display = 'block';
            
            // 調試：驗證值是否設置成功
            console.log('設置後的值:', {
                junior_high: juniorHighInput.value,
                junior_high_display: juniorHighDisplayInput.value
            });
            
            // 清除錯誤提示（因為用戶已從系統選項中選擇）
            clearSchoolError();
        }

        // 點擊其他地方隱藏搜尋結果
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.modern-search-container')) {
                const resultsDiv = document.getElementById('schoolResults');
                if (resultsDiv && resultsDiv.classList.contains('show')) {
                    resultsDiv.classList.remove('show');
                    // 當下拉選單隱藏時，延遲驗證輸入（給選擇操作時間完成）
                    setTimeout(function() {
                        // 再次檢查是否有 school_code，如果有就不驗證（用戶已選擇）
                        const hiddenInput = document.getElementById('junior_high');
                        if (hiddenInput && hiddenInput.value.trim()) {
                            // 用戶已選擇，清除錯誤
                            clearSchoolError();
                        } else {
                            // 用戶未選擇，進行驗證
                            validateSchoolInput();
                        }
                    }, 300);
                }
            }
        });

        // 表單提交處理
        document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');

            // 前端驗證
            const email = document.getElementById('email').value.trim();
            const phone1 = document.getElementById('phone1').value.trim();
            const phone2 = document.getElementById('phone2').value.trim();
            
            // 獲取學校欄位值（調試）
            const juniorHighElement = document.getElementById('junior_high');
            const juniorHighDisplayElement = document.getElementById('junior_high_display');
            
            if (!juniorHighElement) {
                console.error('錯誤: 找不到 junior_high 元素');
            }
            if (!juniorHighDisplayElement) {
                console.error('錯誤: 找不到 junior_high_display 元素');
            }
            
            const juniorHigh = juniorHighElement ? juniorHighElement.value.trim() : ''; // 這是 school_code
            const juniorHighDisplay = juniorHighDisplayElement ? juniorHighDisplayElement.value.trim() : '';
            
            console.log('表單提交時獲取的值:', {
                juniorHighElement: juniorHighElement,
                juniorHighDisplayElement: juniorHighDisplayElement,
                juniorHigh: juniorHigh,
                juniorHighDisplay: juniorHighDisplay,
                juniorHighType: typeof juniorHigh,
                juniorHighLength: juniorHigh ? juniorHigh.length : 0
            });
            const intention1 = document.getElementById('intention1').value;
            const intention2 = document.getElementById('intention2').value;
            const intention3 = document.getElementById('intention3').value;
            
            // 驗證電話1（必填）
            if (!phone1) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請填寫聯絡電話1';
                messageDiv.style.display = 'block';
                document.getElementById('phone1').focus();
                document.getElementById('phone1').style.borderColor = '#d32f2f';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            } else if (phone1.length !== 10 || !/^[0-9]{10}$/.test(phone1)) {
                messageDiv.className = 'error';
                messageDiv.textContent = '聯絡電話1必須為10位數字';
                messageDiv.style.display = 'block';
                document.getElementById('phone1').focus();
                document.getElementById('phone1').style.borderColor = '#d32f2f';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // 驗證電話2（選填，但如果填寫了必須是10位）
            if (phone2 && (phone2.length !== 10 || !/^[0-9]{10}$/.test(phone2))) {
                messageDiv.className = 'error';
                messageDiv.textContent = '聯絡電話2必須為10位數字';
                messageDiv.style.display = 'block';
                document.getElementById('phone2').focus();
                document.getElementById('phone2').style.borderColor = '#d32f2f';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // 驗證電子郵件
            if (!email) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請填寫電子郵件信箱';
                messageDiv.style.display = 'block';
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // 驗證電子郵件格式
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請輸入有效的電子郵件格式';
                messageDiv.style.display = 'block';
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // 驗證至少一個就讀意願（不能全部是「無特定」）
            const hasIntention = (intention1 && intention1 !== '無特定') || 
                                 (intention2 && intention2 !== '無特定') || 
                                 (intention3 && intention3 !== '無特定');
            if (!hasIntention) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請至少選擇一個就讀意願（不能全部選擇「無特定」）';
                messageDiv.style.display = 'block';
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            // 驗證就讀或畢業國中（必須有 school_code，表示已從系統選項中選擇）
            // 調試資訊
            console.log('驗證學校欄位:', {
                juniorHigh: juniorHigh,
                juniorHighLength: juniorHigh ? juniorHigh.length : 0,
                juniorHighDisplay: juniorHighDisplay,
                juniorHighElement: document.getElementById('junior_high')?.value,
                juniorHighDisplayElement: document.getElementById('junior_high_display')?.value,
                juniorHighType: typeof juniorHigh,
                juniorHighIsEmpty: !juniorHigh,
                juniorHighTrimmed: juniorHigh ? juniorHigh.trim() : '',
                juniorHighTrimmedIsEmpty: juniorHigh ? juniorHigh.trim() === '' : true
            });
            
            // 檢查 school_code 是否存在且不為空
            if (!juniorHigh) {
                console.error('驗證失敗: juniorHigh 為空或未定義');
                messageDiv.className = 'error';
                messageDiv.textContent = '請從系統提供的選項中選擇學校，不能自行輸入';
                messageDiv.style.display = 'block';
                document.getElementById('junior_high_display').focus();
                document.getElementById('junior_high_display').style.borderColor = '#d32f2f';
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            const trimmedJuniorHigh = juniorHigh.trim();
            if (trimmedJuniorHigh === '') {
                console.error('验证失败: juniorHigh 去除空白后为空');
                messageDiv.className = 'error';
                messageDiv.textContent = '請從系統提供的選項中選擇學校，不能自行輸入';
                messageDiv.style.display = 'block';
                document.getElementById('junior_high_display').focus();
                document.getElementById('junior_high_display').style.borderColor = '#d32f2f';
                showSchoolError('請從系統提供的選項中選擇學校，不能自行輸入');
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            console.log('學校驗證通過:', trimmedJuniorHigh);

            // 驗證目前年級（必填）
            const currentGrade = document.getElementById('current_grade').value.trim();
            if (!currentGrade) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請選擇目前年級';
                messageDiv.style.display = 'block';
                document.getElementById('current_grade').focus();
                document.getElementById('current_grade').style.borderColor = '#d32f2f';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            // 驗證驗證碼格式（前端額外驗證）
            const captchaInput = document.getElementById('captchaInput');
            const captcha = captchaInput.value.trim().toUpperCase();
            
            // 驗證碼字符集：ABCDEFGHJKLMNPQRSTUVWXYZ23456789（排除0、O、1、I）
            // Pattern: A-H, J-N, P-Z, 2-9（排除 I 和 O），固定4位
            const captchaPattern = /^[A-HJ-NP-Z2-9]{4}$/;
            
            if (!captcha || captcha.length !== 4) {
                messageDiv.className = 'error';
                messageDiv.textContent = '請輸入4位驗證碼';
                messageDiv.style.display = 'block';
                captchaInput.focus();
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            if (!captchaPattern.test(captcha)) {
                messageDiv.className = 'error';
                messageDiv.textContent = '驗證碼格式錯誤，請確認輸入的是圖片中顯示的4位字符（不包含0、O、1、I）';
                messageDiv.style.display = 'block';
                captchaInput.focus();
                // 滾動到頂部
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // 禁用提交按鈕
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';

            // 收集表單數據
            const formData = new FormData(this);

            // 發送AJAX請求（添加調試參數）
            const debugMode = new URLSearchParams(window.location.search).get('debug') === '1';
            if (debugMode) {
                formData.append('debug', '1');
            }
            
            fetch('api/submit_enrollment.php' + (debugMode ? '?debug=1' : ''), {
                    method: 'POST',
                    body: formData
                })
                .then(async response => {
                    // 檢查響應類型
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // 如果不是JSON，讀取文本以查看錯誤
                        const text = await response.text();
                        console.error('非JSON響應:', text);
                        throw new Error('服務器返回了非JSON格式的響應');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        messageDiv.className = 'success';
                        messageDiv.textContent = data.message;
                        messageDiv.style.display = 'block';
                        
                        // 滾動到頂部顯示成功訊息
                        window.scrollTo({ top: 0, behavior: 'smooth' });

                        // 清空表單
                        this.reset();
                        refreshCaptcha();
                        
                        // 重置身份預設為學生
                        document.querySelector('input[name="identity"][value="學生"]').checked = true;

                        // 3秒後隱藏訊息
                        setTimeout(() => {
                            messageDiv.style.display = 'none';
                        }, 3000);
                    } else {
                        messageDiv.className = 'error';
                        let errorMsg = data.message || '提交失敗，請稍後再試';
                        
                        // 如果是調試模式，顯示詳細錯誤信息
                        if (data.debug) {
                            errorMsg += '\n\n調試信息:\n';
                            
                            // 檢查是驗證碼錯誤還是資料庫錯誤
                            if (data.debug.input_original !== undefined || data.debug.session_original !== undefined) {
                                // 驗證碼錯誤的調試信息
                                errorMsg += '輸入: ' + (data.debug.input_original || 'N/A') + '\n';
                                errorMsg += 'Session: ' + (data.debug.session_original || 'N/A') + '\n';
                                errorMsg += 'Session ID: ' + (data.debug.session_id || 'N/A');
                                console.error('驗證碼調試信息:', data.debug);
                            } else if (data.debug.error_message || data.debug.sql_error || data.debug.driver_message) {
                                // 資料庫錯誤的調試信息
                                if (data.debug.error_message) {
                                    errorMsg += '錯誤訊息: ' + data.debug.error_message + '\n';
                                }
                                if (data.debug.sql_error) {
                                    errorMsg += 'SQL錯誤: ' + data.debug.sql_error + '\n';
                                }
                                if (data.debug.driver_message) {
                                    errorMsg += '驅動訊息: ' + data.debug.driver_message + '\n';
                                }
                                if (data.debug.sql_state) {
                                    errorMsg += 'SQL狀態: ' + data.debug.sql_state + '\n';
                                }
                                if (data.debug.driver_code) {
                                    errorMsg += '驅動代碼: ' + data.debug.driver_code;
                                }
                                console.error('資料庫調試信息:', data.debug);
                            } else {
                                // 其他類型的調試信息
                                errorMsg += JSON.stringify(data.debug, null, 2);
                                console.error('調試信息:', data.debug);
                            }
                        }
                        
                        messageDiv.textContent = errorMsg;
                        messageDiv.style.display = 'block';
                        
                        // 滾動到頂部顯示錯誤訊息
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // 如果是驗證碼錯誤，刷新驗證碼
                        if (data.message && data.message.includes('驗證碼')) {
                            refreshCaptcha();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messageDiv.className = 'error';
                    messageDiv.textContent = '提交失敗，請稍後再試';
                    messageDiv.style.display = 'block';
                    
                    // 滾動到頂部顯示錯誤訊息
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                })
                .finally(() => {
                    // 重新啟用提交按鈕
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> 同意送出';
                });
        });

        // 頁面載入時初始化（首次載入時不強制刷新，使用現有驗證碼）
        // 電話號碼驗證功能
        document.addEventListener('DOMContentLoaded', function() {
            // 獲取電話輸入框
            const phone1 = document.getElementById('phone1');
            const phone2 = document.getElementById('phone2');
            
            // 電話號碼驗證函數
            function setupPhoneValidation(phoneInput, isRequired = true) {
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
                    // 移除非數字字元
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
            
            // 設置電話輸入框的驗證
            setupPhoneValidation(phone1, true); // 必填
            setupPhoneValidation(phone2, false); // 選填
            
            // 表單提交驗證
            const form = document.getElementById('enrollmentForm');
            if (form) {
                const originalSubmit = form.onsubmit;
                form.addEventListener('submit', function(e) {
                    // 驗證電話1（必填）
                    if (phone1) {
                        const phoneValue = phone1.value.trim();
                        if (!phoneValue) {
                            e.preventDefault();
                            phone1.style.borderColor = '#d32f2f';
                            phone1.focus();
                            if (phone1.nextElementSibling && phone1.nextElementSibling.classList.contains('phone-hint')) {
                                phone1.nextElementSibling.textContent = '請填寫聯絡電話1';
                                phone1.nextElementSibling.style.display = 'block';
                            }
                            alert('請填寫聯絡電話1！');
                            return false;
                        } else if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                            e.preventDefault();
                            phone1.style.borderColor = '#d32f2f';
                            phone1.focus();
                            if (phone1.nextElementSibling && phone1.nextElementSibling.classList.contains('phone-hint')) {
                                phone1.nextElementSibling.textContent = '電話號碼必須為10位數字';
                                phone1.nextElementSibling.style.display = 'block';
                            }
                            alert('聯絡電話1必須為10位數字！');
                            return false;
                        }
                    }
                    
                    // 驗證電話2（選填，但如果填寫了必須是10位）
                    if (phone2 && phone2.value.trim()) {
                        const phoneValue = phone2.value.trim();
                        if (phoneValue.length !== 10 || !/^[0-9]{10}$/.test(phoneValue)) {
                            e.preventDefault();
                            phone2.style.borderColor = '#d32f2f';
                            phone2.focus();
                            if (phone2.nextElementSibling && phone2.nextElementSibling.classList.contains('phone-hint')) {
                                phone2.nextElementSibling.textContent = '電話號碼必須為10位數字';
                                phone2.nextElementSibling.style.display = 'block';
                            }
                            alert('聯絡電話2必須為10位數字！');
                            return false;
                        }
                    }
                });
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // 首次載入時，如果驗證碼圖片還沒有載入，則載入它（不強制刷新）
            const captchaImage = document.getElementById('captchaImage');
            if (captchaImage && !captchaImage.src.includes('captcha_image.php')) {
                captchaImage.src = 'captcha_image.php?t=' + new Date().getTime();
            }

            // 綁定即時搜尋事件
            const searchInput = document.getElementById('junior_high_display');
            const clearBtn = document.getElementById('clearSearch');

            // 輸入事件（即時搜尋）
            searchInput.addEventListener('input', function() {
                performSearch();
                // 當下拉選單顯示時，不進行驗證（用戶還在輸入和選擇中）
            });
            
            // 失去焦點時立即驗證
            searchInput.addEventListener('blur', function() {
                clearTimeout(searchInput.validationTimeout);
                // 延遲一點驗證，讓點擊下拉選單項目的時間完成
                searchInput.validationTimeout = setTimeout(function() {
                    // 檢查是否有 school_code，如果有就不驗證（用戶已選擇）
                    const hiddenInput = document.getElementById('junior_high');
                    if (hiddenInput && hiddenInput.value.trim()) {
                        // 用戶已選擇，清除錯誤
                        console.log('失去焦點時檢測到已選擇學校，清除錯誤');
                        clearSchoolError();
                    } else {
                        // 用戶未選擇，進行驗證
                        console.log('失去焦點時未檢測到 school_code，進行驗證');
                        validateSchoolInputImmediate();
                    }
                }, 300);
            });
            
            // 當輸入框獲得焦點時，如果已有錯誤且下拉選單未顯示，保持顯示
            searchInput.addEventListener('focus', function() {
                const resultsDiv = document.getElementById('schoolResults');
                const value = this.value.trim();
                const schoolCode = document.getElementById('junior_high').value.trim();
                // 如果有 school_code，說明用戶已選擇，清除錯誤
                if (schoolCode && schoolCode.trim() !== '') {
                    clearSchoolError();
                    return;
                }
                // 只有在下拉選單未顯示時才檢查錯誤
                if (value && !schoolCode && 
                    (!resultsDiv || !resultsDiv.classList.contains('show'))) {
                    validateSchoolInput();
                }
            });

            // 清除按鈕事件
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                clearSearch();
            });

            // 鍵盤事件
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            }); 
        });
       // (你的其他JS程式碼：像驗證碼、搜尋、送出表單的程式...)

        // 科系對應學制設定
        const departmentSystems = {
            "護理科": ["五專"],
            "嬰幼兒保育科": ["五專", "四技"],
            "視光科": ["五專"],
            "數位影視動畫科": ["五專"],
            "資訊管理科": ["五專"],
            "企業管理科": ["五專", "四技"],
            "應用外語科": ["五專"],
            "長期照護學系": ["四技"]
        };

        function updateSystemOptions(departmentSelectId, systemSelectId) {
            const department = document.getElementById(departmentSelectId).value;
            const systemSelect = document.getElementById(systemSelectId);

            systemSelect.innerHTML = '<option value="">請選擇</option>';

            if (department && departmentSystems[department]) {
                const systems = departmentSystems[department];
                systems.forEach(system => {
                    const option = document.createElement("option");
                    option.value = system;
                    option.textContent = system;
                    systemSelect.appendChild(option);
                });
                
                // 如果只有一個學制，自動選取
                if (systems.length === 1) {
                    systemSelect.value = systems[0];
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const pairs = [
                { dep: "intention1", sys: "system1" },
                { dep: "intention2", sys: "system2" },
                { dep: "intention3", sys: "system3" }
            ];

            pairs.forEach(({ dep, sys }) => {
                const depSelect = document.getElementById(dep);
                if (depSelect) {
                    depSelect.addEventListener("change", function() {
                        updateSystemOptions(dep, sys);
                    });
                }
            });
        });
    </script>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>

</html>