<?php
session_start();

// 載入 reCAPTCHA 設定
require_once '../backend/config/recaptcha_config.php';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取所有老師資料
    $stmt = $pdo->prepare("SELECT t.user_id, t.name, t.department, u.username 
                          FROM teacher t 
                          JOIN user u ON t.user_id = u.id 
                          WHERE u.role = '老師'
                          ORDER BY t.department, t.name");
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $teachers = [];
    error_log("獲取老師資料失敗: " . $e->getMessage());
}

// 檢查是否已登入且為學生身份
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== '學生' && $_SESSION['role'] !== 'student')) {
    header('Location: one.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學就讀意願登錄</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #fff5e6; /* 淺黃色背景 */
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: #333;
        padding-top: 100px;
    }

    main {
        flex: 1;
    }

    .coop-container {
        max-width: 1000px;
        margin: 40px auto;
        background: #fff5e6;
        padding: 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .h11 {
        color: #222;
        text-align: center;
        margin-bottom: 30px;
        font-size: 2em;
        font-weight: 600;
    }

    .form-description {
        text-align: center;
        margin-bottom: 30px;
        font-size: 14px;
        color: #666;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }

    input[type="text"], 
    input[type="email"], 
    input[type="tel"], 
    input[type="date"], 
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        transition: border 0.3s ease;
        box-sizing: border-box;
        background: #fff;
    }

    input:focus, textarea:focus, select:focus {
        border-color: #0056b3;
        outline: none;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .submit-btn {
        background: #0056b3;
        color: white;
        padding: 14px 24px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s ease;
        margin-top: 20px;
    }

    .submit-btn:hover {
        background: #004494;
    }

    .message {
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        font-size: 14px;
    }

    .message.success {
        background: #e6f4ea;
        color: #1e4620;
        border: 1px solid #9ccc9c;
    }

    .message.error {
        background: #fdecea;
        color: #611a15;
        border: 1px solid #f5c6cb;
    }

    .required {
        color: #e74c3c;
    }

    .form-row,
    .form-row-3 {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .form-row .form-group,
    .form-row-3 .form-group {
        flex: 1;
    }

    .radio-group {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .radio-group label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: normal;
        cursor: pointer;
    }

    .radio-group input[type="radio"] {
        width: auto;
        margin: 0;
    }

    .school-search-section {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .school-search-section input[type="text"] {
        flex: 1;
        min-width: 200px;
    }

    .search-btn {
        background: #0056b3;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
    }

    .search-btn:hover {
        background: #004494;
    }

    .school-search-section select {
        flex: 1;
        min-width: 200px;
    }

    .school-search-info {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .section-title {
        color: #333;
        border-bottom: 2px solid #0056b3;
        padding-bottom: 6px;
        margin: 25px 0 15px 0;
        font-size: 1.2em;
        font-weight: 600;
    }

    .agreement-text {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin: 20px 0;
        font-size: 14px;
        color: #333;
    }

    .captcha-section {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f8f9fa;
    }
    
    .captcha-container {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .captcha-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .captcha-input-group label {
        font-weight: 500;
        color: #333;
        margin-right: 10px;
        min-width: 60px;
    }
    
    .captcha-input-group input {
        width: 100px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 2px;
        height: 40px;
        box-sizing: border-box;
    }
    
    .captcha-display {
        height: 40px;
        width: 120px;
        border: 2px solid #ccc;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        font-size: 20px;
        font-weight: bold;
        color: #333;
        font-family: 'Courier New', monospace;
        letter-spacing: 3px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        user-select: none;
        position: relative;
        overflow: hidden;
    }
    
    .captcha-display:hover {
        background: linear-gradient(45deg, #e8e8e8, #d8d8d8);
        border-color: #999;
    }
    
    .captcha-display::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shine 2s infinite;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .captcha-display::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 10%;
        right: 10%;
        height: 1px;
        background: rgba(0,0,0,0.1);
        transform: rotate(-15deg);
    }
    
    .refresh-btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .refresh-btn:hover {
        background: #0056b3;
    }
    
    .captcha-error {
        color: #e74c3c;
        font-size: 14px;
        margin-top: 5px;
    }
    
    .help-text {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .form-row,
        .form-row-3 {
            flex-direction: column;
        }
        
        .school-search-section {
            flex-direction: column;
        }
        
        .radio-group {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .captcha-input-group {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }
        
        .captcha-input-group label {
            min-width: auto;
            margin-right: 0;
            margin-bottom: 5px;
        }
        
        .captcha-input-group input {
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }
        
        .captcha-input-group .captcha-display {
            margin: 0 auto;
        }
        
        .refresh-btn {
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }
    }
    </style>
</head>
<body>
    <?php include("share/header.php"); ?>
    <main>
    <div class="coop-container">
        <h1 class="h11">康寧大學就讀意願登錄</h1>
        
        <div class="form-description">
            *為必填之欄位，康寧大學收到資料後將儘快與您聯絡！
        </div>
        
        <div id="message"></div>
        
        <form id="enrollmentForm">
            <!-- 個人基本資料 -->
            <h3 class="section-title">個人基本資料</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">*姓名:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label>*身分別:</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="identity" value="學生" required>
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
                            <input type="radio" name="gender" value="男">
                            男
                        </label>
                        <label>
                            <input type="radio" name="gender" value="女">
                            女
                        </label>
                    </div>
                </div>
                <div class="form-group">
                                    <label for="phone1">*聯絡電話1:</label>
                <input type="tel" id="phone1" name="phone1" maxlength="10" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                                    <label for="phone2">聯絡電話2:</label>
                <input type="tel" id="phone2" name="phone2" maxlength="10">
                </div>
                <div class="form-group">
                    <label for="email">電子郵件信箱:</label>
                    <input type="email" id="email" name="email">
                </div>
            </div>

            <!-- 就讀意願 -->
            <h3 class="section-title">就讀意願</h3>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention1">就讀意願一:</label>
                    <select id="intention1" name="intention1">
                        <option value="無特定">無特定</option>
                        <option value="資訊工程學系">資訊管理科科</option>
                        <option value="電機工程學系">企業管理科</option>
                        <option value="機械工程學系">護理科護理科學系</option>
                        <option value="護理科">護理科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system1">學制:</label>
                                             <select id="system1" name="system1">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                </div>
                <div class="form-group">
                    <label for="department1">科系:</label>
                    <select id="department1" name="department1">
                        <option value="">請選擇</option>
                        <option value="無特定">無特定</option>
                        <option value="資訊工程學系">資訊管理科科</option>
                        <option value="電機工程學系">企業管理科</option>
                        <option value="機械工程學系">護理科護理科學系</option>
                        <option value="護理科">護理科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention2">就讀意願二:</label>
                    <select id="intention2" name="intention2">
                    <option value="無特定">無特定</option>
                        <option value="資訊工程學系">資訊管理科科</option>
                        <option value="電機工程學系">企業管理科</option>
                        <option value="機械工程學系">護理科護理科學系</option>
                        <option value="護理科">護理科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system2">學制:</label>
                                             <select id="system2" name="system2">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                </div>
                <div class="form-group">
                    <label for="department2">科系:</label>
                    <select id="department2" name="department2">
                    <option value="無特定">無特定</option>
                        <option value="資訊工程學系">資訊管理科科</option>
                        <option value="電機工程學系">企業管理科</option>
                        <option value="機械工程學系">護理科護理科學系</option>
                        <option value="護理科">護理科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention3">就讀意願三:</label>
                    <select id="intention3" name="intention3">
                        <option value="無特定">無特定</option>
                        <option value="資訊工程學系">資訊工程學系</option>
                        <option value="電機工程學系">電機工程學系</option>
                        <option value="機械工程學系">機械工程學系</option>
                        <option value="企業管理學系">企業管理學系</option>
                        <option value="會計學系">會計學系</option>
                        <option value="財務金融學系">財務金融學系</option>
                        <option value="外國語文學系">外國語文學系</option>
                        <option value="中國文學系">中國文學系</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system3">學制:</label>
                                             <select id="system3" name="system3">
                             <option value="">請選擇</option>
                             <option value="五專">五專</option>
                             <option value="大學部">大學部</option>
                             <option value="碩士班">碩士班</option>
                             <option value="博士班">博士班</option>
                         </select>
                </div>
                <div class="form-group">
                    <label for="department3">科系:</label>
                    <select id="department3" name="department3">
                        <option value="">請選擇</option>
                        <option value="資訊工程學系">資訊工程學系</option>
                        <option value="電機工程學系">電機工程學系</option>
                        <option value="機械工程學系">機械工程學系</option>
                        <option value="企業管理學系">企業管理學系</option>
                        <option value="會計學系">會計學系</option>
                        <option value="財務金融學系">財務金融學系</option>
                        <option value="外國語文學系">外國語文學系</option>
                        <option value="中國文學系">中國文學系</option>
                    </select>
                </div>
            </div>

            <!-- 就讀或畢業國中資訊 -->
            <h3 class="section-title">就讀或畢業國中資訊</h3>
            
            <div class="form-group">
                <label for="junior_high">就讀或畢業國中:</label>
                <div class="school-search-section">
                    <input type="text" id="junior_high" name="junior_high" placeholder="校名關鍵字,如中正">
                    <button type="button" class="search-btn" onclick="searchSchool()">搜尋學校>></button>
                    <select id="current_grade" name="current_grade">
                        <option value="">請選擇目前年級...</option>
                        <option value="國一">國一</option>
                        <option value="國二">國二</option>
                        <option value="國三">國三</option>
                        <option value="已畢業">已畢業</option>
                    </select>
                </div>
                <div class="school-search-info">
                    請在左方空格輸入校名關鍵字,並按下「搜尋學校」鈕
                </div>
            </div>

            <!-- 社群媒體資訊 -->
            <h3 class="section-title">社群媒體資訊</h3>
            
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
            <h3 class="section-title">推薦老師資訊</h3>
            
            <div class="form-group">
                <label for="recommended_teacher">推薦老師:</label>
                <select id="recommended_teacher" name="recommended_teacher">
                    <option value="">請選擇推薦老師（可選）</option>
                    <?php if (!empty($teachers)): ?>
                        <?php 
                        $currentDepartment = '';
                        foreach ($teachers as $teacher): 
                            if ($teacher['department'] !== $currentDepartment):
                                if ($currentDepartment !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($teacher['department']) . '">';
                                $currentDepartment = $teacher['department'];
                            endif;
                        ?>
                            <option value="<?php echo htmlspecialchars($teacher['name']); ?>">
                                <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['department']); ?>)
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentDepartment !== '') echo '</optgroup>'; ?>
                    <?php else: ?>
                        <option value="">暫無老師資料</option>
                    <?php endif; ?>
                </select>
                <div class="help-text">可選擇推薦您的老師，或留空</div>
            </div>

            <!-- 備註 -->
            <h3 class="section-title">備註</h3>
            
            <div class="form-group">
                <label for="remarks">備註:</label>
                <textarea id="remarks" name="remarks" rows="4"></textarea>
            </div>

            <!-- 同意聲明 -->
            <div class="agreement-text">
                ※本人願意提供上開個人資料並授權相關單位對資料之處理及合理使用。
            </div>

            <!-- 驗證碼 -->
            <div class="captcha-section">
                <div class="captcha-container">
                    <div class="captcha-input-group">
                        <label for="captcha">驗證碼:</label>
                        <input type="text" id="captcha" name="captcha" placeholder="請輸入驗證碼" maxlength="4" required>
                        <div id="captcha-display" class="captcha-display" onclick="refreshCaptcha()">
                            <?php 
                            // 生成驗證碼
                            $captcha_code = '';
                            for ($i = 0; $i < 4; $i++) {
                                $captcha_code .= rand(0, 9);
                            }
                            $_SESSION['captcha_code'] = $captcha_code;
                            echo $captcha_code;
                            ?>
                        </div>
                        <button type="button" onclick="refreshCaptcha()" class="refresh-btn">刷新</button>
                    </div>
                    <div id="captcha-error" class="captcha-error" style="display: none; color: #e74c3c; font-size: 14px; margin-top: 5px;">
                        驗證碼錯誤，請重新輸入
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                同意送出
            </button>
        </form>
    </div>
    </main>

    <script>
        // 驗證碼刷新功能
        function refreshCaptcha() {
            // 生成新的4位數字驗證碼
            let newCaptcha = '';
            for (let i = 0; i < 4; i++) {
                newCaptcha += Math.floor(Math.random() * 10);
            }
            
            // 更新顯示
            document.getElementById('captcha-display').textContent = newCaptcha;
            document.getElementById('captcha').value = '';
            document.getElementById('captcha-error').style.display = 'none';
            
            // 發送AJAX請求更新後端驗證碼
            fetch('update_captcha.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'captcha=' + encodeURIComponent(newCaptcha)
            }).catch(error => {
                console.log('更新驗證碼失敗:', error);
            });
        }
        
        // 搜尋學校功能
        function searchSchool() {
            const keyword = document.getElementById('junior_high').value;
            if (keyword.trim() === '') {
                alert('請輸入校名關鍵字');
                return;
            }
            
            // 顯示搜尋中
            const searchBtn = document.querySelector('.search-btn');
            const originalText = searchBtn.textContent;
            searchBtn.textContent = '搜尋中...';
            searchBtn.disabled = true;
            
            // 發送搜尋請求
            fetch(`school_search_simple.php?keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.schools, keyword);
                    } else {
                        alert('搜尋失敗：' + data.message);
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    alert('搜尋時發生錯誤，請稍後再試');
                })
                .finally(() => {
                    searchBtn.textContent = originalText;
                    searchBtn.disabled = false;
                });
        }
        
        // 顯示搜尋結果
        function displaySearchResults(schools, keyword) {
            const infoDiv = document.querySelector('.school-search-info');
            
            if (schools.length === 0) {
                infoDiv.innerHTML = `
                    <div style="color: #e74c3c; margin: 10px 0;">
                        <strong>未找到包含「${keyword}」的學校</strong><br>
                        請嘗試其他關鍵字，或直接輸入完整校名
                    </div>
                `;
                return;
            }
            
            let html = `
                <div style="margin: 10px 0;">
                    <strong>找到 ${schools.length} 所學校：</strong>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
            `;
            
            schools.forEach(school => {
                html += `
                    <div style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer;" 
                         onclick="selectSchool('${school.name}', '${school.city}', '${school.district}')"
                         onmouseover="this.style.backgroundColor='#f8f9fa'" 
                         onmouseout="this.style.backgroundColor='white'">
                        <strong>${school.name}</strong><br>
                        <small style="color: #666;">${school.city} ${school.district}</small>
                    </div>
                `;
            });
            
            html += `
                    </div>
                    <small style="color: #666; margin-top: 5px; display: block;">
                        點擊學校名稱可自動填入
                    </small>
                </div>
            `;
            
            infoDiv.innerHTML = html;
        }
        
        // 選擇學校
        function selectSchool(schoolName, city, district) {
            document.getElementById('junior_high').value = schoolName;
            
            // 清空搜尋結果
            const infoDiv = document.querySelector('.school-search-info');
            infoDiv.innerHTML = '請在左方空格輸入校名關鍵字,並按下「搜尋學校」鈕';
            
            // 顯示選中的學校信息
            infoDiv.innerHTML = `
                <div style="color: #28a745; margin: 10px 0;">
                    <strong>已選擇：${schoolName}</strong><br>
                    <small>${city} ${district}</small>
                </div>
            `;
        }

        // 表單提交處理
        document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 檢查必填欄位
            const requiredFields = ['name', 'phone1'];
            const missingFields = [];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    missingFields.push(field);
                }
            });
            
            // 檢查身分別
            const identity = document.querySelector('input[name="identity"]:checked');
            if (!identity) {
                missingFields.push('身分別');
            }
            
            if (missingFields.length > 0) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請填寫必填欄位: ' + missingFields.join(', ');
                return;
            }
            
            // 檢查驗證碼
            const captchaInput = document.getElementById('captcha');
            if (!captchaInput.value.trim()) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請輸入驗證碼';
                document.getElementById('captcha-error').style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            // 收集表單資料
            const formData = new FormData(this);
            formData.append('username', '<?php echo $username; ?>');
            
            // 調試：顯示要提交的資料
            console.log('準備提交的資料:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // 提交到後端API
            fetch('../backend/enrollment_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('API回應狀態:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API回應資料:', data);
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    document.getElementById('enrollmentForm').reset();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '提交失敗: ' + data.message;
                }
            })
            .catch(error => {
                console.error('提交錯誤:', error);
                messageDiv.className = 'message error';
                messageDiv.textContent = '提交失敗，請稍後再試。錯誤詳情: ' + error.message;
            })
             .finally(() => {
                 submitBtn.disabled = false;
                 submitBtn.textContent = '同意送出';
             });
        });

        // 就讀意願連動功能
        function updateDepartments(intentionNum) {
            const intention = document.getElementById('intention' + intentionNum);
            const department = document.getElementById('department' + intentionNum);
            
            if (intention.value !== '無特定') {
                department.value = intention.value;
            }
        }

        // 綁定就讀意願變更事件
        document.getElementById('intention1').addEventListener('change', () => updateDepartments(1));
        document.getElementById('intention2').addEventListener('change', () => updateDepartments(2));
        document.getElementById('intention3').addEventListener('change', () => updateDepartments(3));
    </script>
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>

