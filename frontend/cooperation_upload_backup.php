<?php
// 載入 session 配置
require_once 'session_config.php';

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
    <link rel="stylesheet" href="assets/csp/cooperation_upload.css">
</head>
<body>
<?php include("share/header.php"); ?>
<div class="cooperation-page-wrapper">
<div class="cooperation-container">
    <div class="cooperation-header">
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
                    <input type="tel" id="phone1" name="phone1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone2">聯絡電話2:</label>
                    <input type="tel" id="phone2" name="phone2">
                </div>
                <div class="form-group">
                    <label for="email">電子郵件信箱:</label>
                    <input type="email" id="email" name="email">
                </div>
            </div>

            <!-- 就讀意願 -->
            <h3 class="section-title"><i class="fas fa-heart"></i> 就讀意願</h3>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention1">就讀意願一:</label>
                    <select id="intention1" name="intention1">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system1">學制:</label>
                    <select id="system1" name="system1">
                        <option value="">請選擇</option>
                        <option value="五專">五專</option>
                        <option value="二專">二專</option>
                        <option value="四技">四技</option>
                        <option value="二技">二技</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department1">科系:</label>
                    <select id="department1" name="department1">
                        <option value="">請選擇</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention2">就讀意願二:</label>
                    <select id="intention2" name="intention2">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system2">學制:</label>
                    <select id="system2" name="system2">
                        <option value="">請選擇</option>
                        <option value="五專">五專</option>
                        <option value="二專">二專</option>
                        <option value="四技">四技</option>
                        <option value="二技">二技</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department2">科系:</label>
                    <select id="department2" name="department2">
                        <option value="">請選擇</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention3">就讀意願三:</label>
                    <select id="intention3" name="intention3">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="system3">學制:</label>
                    <select id="system3" name="system3">
                        <option value="">請選擇</option>
                        <option value="五專">五專</option>
                        <option value="二專">二專</option>
                        <option value="四技">四技</option>
                        <option value="二技">二技</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department3">科系:</label>
                    <select id="department3" name="department3">
                        <option value="">請選擇</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <!-- 就讀或畢業國中資訊 -->
            <h3 class="section-title"><i class="fas fa-school"></i> 就讀或畢業國中資訊</h3>
            
            <div class="form-group">
                <label for="junior_high">就讀或畢業國中:</label>
                <div class="school-search-section">
                    <input type="text" id="junior_high" name="junior_high" placeholder="校名關鍵字,如中正">
                    <button type="button" class="search-btn" onclick="searchSchool()">搜尋學校>></button>
                    <div id="schoolResults" class="school-results"></div>
                </div>
                <div class="help-text">
                    請在左方空格輸入校名關鍵字,並按下「搜尋學校」鈕
                </div>
            </div>

            <div class="form-group">
                <label for="current_grade">請選擇目前年級....</label>
                <select id="current_grade" name="current_grade">
                    <option value="">請選擇年級</option>
                    <option value="國一">國一</option>
                    <option value="國二">國二</option>
                    <option value="國三">國三</option>
                    <option value="已畢業">已畢業</option>
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
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo htmlspecialchars($teacher['name']); ?>">
                            <?php echo htmlspecialchars($teacher['name']); ?> - <?php echo htmlspecialchars($teacher['department']); ?>
                        </option>
                    <?php endforeach; ?>
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
                <h4>驗證碼</h4>
                <div class="captcha-container">
                    <div class="captcha-display" id="captchaDisplay">1234</div>
                    <div class="captcha-input">
                        <input type="text" id="captchaInput" name="captcha" placeholder="請輸入驗證碼" required>
                    </div>
                    <button type="button" class="refresh-captcha" onclick="refreshCaptcha()">刷新</button>
                </div>
                <div class="help-text">
                    驗證碼錯誤，請重新輸入
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-paper-plane"></i> 同意送出
            </button>
        </form>
    </div>
</div>
</div>

<script>
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
</head>
<body>
<?php include("share/header.php"); ?>
<div class="cooperation-page-wrapper">
<div class="cooperation-container">
    <div class="cooperation-header">
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
            <h3 class="section-title"><i class="fas fa-heart"></i> 就讀意願</h3>
            
            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention1">就讀意願一:</label>
                    <select id="intention1" name="intention1">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
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
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention2">就讀意願二:</label>
                    <select id="intention2" name="intention2">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
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
                        <option value="">請選擇</option>
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="intention3">就讀意願三:</label>
                    <select id="intention3" name="intention3">
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
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
                        <option value="無特定">無特定</option>
                        <option value="資訊管理科">資訊管理科</option>
                        <option value="企業管理科">企業管理科</option>
                        <option value="護理科">護理科</option>
                        <option value="幼保科">幼保科</option>
                        <option value="應用外語科">應用外語科</option>
                        <option value="視光科">視光科</option>
                        <option value="動畫科">動畫科</option>
                    </select>
                </div>
            </div>

            <!-- 就讀或畢業國中資訊 -->
            <h3 class="section-title"><i class="fas fa-school"></i> 就讀或畢業國中資訊</h3>
            
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
            <h3 class="section-title"><i class="fas fa-sticky-note"></i> 備註</h3>
            
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
                <i class="fas fa-paper-plane"></i> 同意送出
            </button>
        </form>
    </div>
</div>
</div>

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
        
        // 搜尋學校功能 - 使用完整資料庫
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
            
            // 使用現有的API（包含全台灣國中資料）
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
                    // 如果API失敗，使用備用搜尋
                    console.log('API搜尋失敗，使用備用搜尋...');
                    searchSchoolFallback(keyword);
                })
                .finally(() => {
                    searchBtn.textContent = originalText;
                    searchBtn.disabled = false;
                });
        }
        
        // 備用搜尋功能（當API不可用時）
        function searchSchoolFallback(keyword) {
            // 完整的台灣北部國中資料庫（備用）
            const fallbackSchools = [
                // 台北市立國中
                {name: '台北市立中正國中', city: '台北市', district: '中正區'},
                {name: '台北市立建國中學', city: '台北市', district: '中正區'},
                {name: '台北市立成功國中', city: '台北市', district: '中正區'},
                {name: '台北市立弘道國中', city: '台北市', district: '中正區'},
                {name: '台北市立古亭國中', city: '台北市', district: '中正區'},
                {name: '台北市立螢橋國中', city: '台北市', district: '中正區'},
                {name: '台北市立金華國中', city: '台北市', district: '大安區'},
                {name: '台北市立敦化國中', city: '台北市', district: '大安區'},
                {name: '台北市立仁愛國中', city: '台北市', district: '大安區'},
                {name: '台北市立龍門國中', city: '台北市', district: '大安區'},
                {name: '台北市立懷生國中', city: '台北市', district: '大安區'},
                {name: '台北市立信義國中', city: '台北市', district: '信義區'},
                {name: '台北市立興雅國中', city: '台北市', district: '信義區'},
                {name: '台北市立永吉國中', city: '台北市', district: '信義區'},
                {name: '台北市立松山國中', city: '台北市', district: '松山區'},
                {name: '台北市立民生國中', city: '台北市', district: '松山區'},
                {name: '台北市立西松國中', city: '台北市', district: '松山區'},
                {name: '台北市立介壽國中', city: '台北市', district: '松山區'},
                {name: '台北市立中山國中', city: '台北市', district: '中山區'},
                {name: '台北市立大直國中', city: '台北市', district: '中山區'},
                {name: '台北市立新興國中', city: '台北市', district: '中山區'},
                {name: '台北市立南港國中', city: '台北市', district: '南港區'},
                {name: '台北市立成德國中', city: '台北市', district: '南港區'},
                {name: '台北市立誠正國中', city: '台北市', district: '南港區'},
                {name: '台北市立內湖國中', city: '台北市', district: '內湖區'},
                {name: '台北市立麗山國中', city: '台北市', district: '內湖區'},
                {name: '台北市立三民國中', city: '台北市', district: '內湖區'},
                {name: '台北市立東湖國中', city: '台北市', district: '內湖區'},
                {name: '台北市立明湖國中', city: '台北市', district: '內湖區'},
                {name: '台北市立士林國中', city: '台北市', district: '士林區'},
                {name: '台北市立天母國中', city: '台北市', district: '士林區'},
                {name: '台北市立格致國中', city: '台北市', district: '士林區'},
                {name: '台北市立蘭雅國中', city: '台北市', district: '士林區'},
                {name: '台北市立至善國中', city: '台北市', district: '士林區'},
                {name: '台北市立北投國中', city: '台北市', district: '北投區'},
                {name: '台北市立明德國中', city: '台北市', district: '北投區'},
                {name: '台北市立石牌國中', city: '台北市', district: '北投區'},
                {name: '台北市立關渡國中', city: '台北市', district: '北投區'},
                {name: '台北市立文山國中', city: '台北市', district: '文山區'},
                {name: '台北市立景美國中', city: '台北市', district: '文山區'},
                {name: '台北市立木柵國中', city: '台北市', district: '文山區'},
                {name: '台北市立實踐國中', city: '台北市', district: '文山區'},
                {name: '台北市立萬華國中', city: '台北市', district: '萬華區'},
                {name: '台北市立大理國中', city: '台北市', district: '萬華區'},
                {name: '台北市立雙園國中', city: '台北市', district: '萬華區'},
                {name: '台北市立龍山國中', city: '台北市', district: '萬華區'},
                // 新北市立國中
                {name: '新北市立板橋國中', city: '新北市', district: '板橋區'},
                {name: '新北市立海山國中', city: '新北市', district: '板橋區'},
                {name: '新北市立中山國中', city: '新北市', district: '板橋區'},
                {name: '新北市立重慶國中', city: '新北市', district: '板橋區'},
                {name: '新北市立江翠國中', city: '新北市', district: '板橋區'},
                {name: '新北市立大觀國中', city: '新北市', district: '板橋區'},
                {name: '新北市立溪崑國中', city: '新北市', district: '板橋區'},
                {name: '新北市立新莊國中', city: '新北市', district: '新莊區'},
                {name: '新北市立頭前國中', city: '新北市', district: '新莊區'},
                {name: '新北市立福營國中', city: '新北市', district: '新莊區'},
                {name: '新北市立丹鳳國中', city: '新北市', district: '新莊區'},
                {name: '新北市立中平國中', city: '新北市', district: '新莊區'},
                {name: '新北市立三重國中', city: '新北市', district: '三重區'},
                {name: '新北市立明志國中', city: '新北市', district: '三重區'},
                {name: '新北市立碧華國中', city: '新北市', district: '三重區'},
                {name: '新北市立正義國中', city: '新北市', district: '三重區'},
                {name: '新北市立三和國中', city: '新北市', district: '三重區'},
                {name: '新北市立光榮國中', city: '新北市', district: '三重區'},
                {name: '新北市立中和國中', city: '新北市', district: '中和區'},
                {name: '新北市立積穗國中', city: '新北市', district: '中和區'},
                {name: '新北市立永和國中', city: '新北市', district: '永和區'},
                {name: '新北市立福和國中', city: '新北市', district: '永和區'},
                {name: '新北市立新店國中', city: '新北市', district: '新店區'},
                {name: '新北市立安康國中', city: '新北市', district: '新店區'},
                {name: '新北市立五峰國中', city: '新北市', district: '新店區'},
                {name: '新北市立文山國中', city: '新北市', district: '新店區'},
                {name: '新北市立樹林國中', city: '新北市', district: '樹林區'},
                {name: '新北市立柑園國中', city: '新北市', district: '樹林區'},
                {name: '新北市立三多國中', city: '新北市', district: '樹林區'},
                {name: '新北市立鶯歌國中', city: '新北市', district: '鶯歌區'},
                {name: '新北市立三峽國中', city: '新北市', district: '三峽區'},
                {name: '新北市立安坑國中', city: '新北市', district: '三峽區'},
                {name: '新北市立淡水國中', city: '新北市', district: '淡水區'},
                {name: '新北市立竹圍國中', city: '新北市', district: '淡水區'},
                {name: '新北市立正德國中', city: '新北市', district: '淡水區'},
                {name: '新北市立汐止國中', city: '新北市', district: '汐止區'},
                {name: '新北市立秀峰國中', city: '新北市', district: '汐止區'},
                {name: '新北市立青山國中', city: '新北市', district: '汐止區'},
                {name: '新北市立瑞芳國中', city: '新北市', district: '瑞芳區'},
                {name: '新北市立欽賢國中', city: '新北市', district: '瑞芳區'},
                {name: '新北市立土城國中', city: '新北市', district: '土城區'},
                {name: '新北市立清水國中', city: '新北市', district: '土城區'},
                {name: '新北市立中正國中', city: '新北市', district: '土城區'},
                {name: '新北市立蘆洲國中', city: '新北市', district: '蘆洲區'},
                {name: '新北市立三民國中', city: '新北市', district: '蘆洲區'},
                {name: '新北市立鷺江國中', city: '新北市', district: '蘆洲區'},
                {name: '新北市立五股國中', city: '新北市', district: '五股區'},
                {name: '新北市立泰山國中', city: '新北市', district: '泰山區'},
                {name: '新北市立明志國中', city: '新北市', district: '泰山區'},
                {name: '新北市立林口國中', city: '新北市', district: '林口區'},
                {name: '新北市立崇林國中', city: '新北市', district: '林口區'},
                // 桃園市立國中
                {name: '桃園市立桃園國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立中興國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立文昌國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立青溪國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立大有國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立建國國中', city: '桃園市', district: '桃園區'},
                {name: '桃園市立中壢國中', city: '桃園市', district: '中壢區'},
                {name: '桃園市立內壢國中', city: '桃園市', district: '中壢區'},
                {name: '桃園市立興南國中', city: '桃園市', district: '中壢區'},
                {name: '桃園市立自強國中', city: '桃園市', district: '中壢區'},
                {name: '桃園市立東興國中', city: '桃園市', district: '中壢區'},
                {name: '桃園市立平鎮國中', city: '桃園市', district: '平鎮區'},
                {name: '桃園市立平興國中', city: '桃園市', district: '平鎮區'},
                {name: '桃園市立平南國中', city: '桃園市', district: '平鎮區'},
                {name: '桃園市立八德國中', city: '桃園市', district: '八德區'},
                {name: '桃園市立大安國中', city: '桃園市', district: '八德區'},
                {name: '桃園市立楊梅國中', city: '桃園市', district: '楊梅區'},
                {name: '桃園市立富岡國中', city: '桃園市', district: '楊梅區'},
                {name: '桃園市立瑞原國中', city: '桃園市', district: '楊梅區'},
                {name: '桃園市立蘆竹國中', city: '桃園市', district: '蘆竹區'},
                {name: '桃園市立大竹國中', city: '桃園市', district: '蘆竹區'},
                {name: '桃園市立大溪國中', city: '桃園市', district: '大溪區'},
                {name: '桃園市立仁和國中', city: '桃園市', district: '大溪區'},
                {name: '桃園市立大園國中', city: '桃園市', district: '大園區'},
                {name: '桃園市立竹圍國中', city: '桃園市', district: '大園區'},
                {name: '桃園市立龜山國中', city: '桃園市', district: '龜山區'},
                {name: '桃園市立大崗國中', city: '桃園市', district: '龜山區'},
                {name: '桃園市立龍潭國中', city: '桃園市', district: '龍潭區'},
                {name: '桃園市立石門國中', city: '桃園市', district: '龍潭區'},
                {name: '桃園市立凌雲國中', city: '桃園市', district: '龍潭區'},
                {name: '桃園市立觀音國中', city: '桃園市', district: '觀音區'},
                {name: '桃園市立新屋國中', city: '桃園市', district: '新屋區'},
                {name: '桃園市立復興國中', city: '桃園市', district: '復興區'},
                {name: '台中市立台中國中', city: '台中市', district: '中區'},
                {name: '台中市立西區國中', city: '台中市', district: '西區'},
                {name: '台中市立北區國中', city: '台中市', district: '北區'},
                {name: '台中市立東區國中', city: '台中市', district: '東區'},
                {name: '台中市立南區國中', city: '台中市', district: '南區'},
                {name: '台中市立西屯國中', city: '台中市', district: '西屯區'},
                {name: '台中市立南屯國中', city: '台中市', district: '南屯區'},
                {name: '台中市立北屯國中', city: '台中市', district: '北屯區'},
                {name: '台中市立豐原國中', city: '台中市', district: '豐原區'},
                {name: '台中市立東勢國中', city: '台中市', district: '東勢區'},
                {name: '台中市立大甲國中', city: '台中市', district: '大甲區'},
                {name: '台中市立清水國中', city: '台中市', district: '清水區'},
                {name: '台中市立沙鹿國中', city: '台中市', district: '沙鹿區'},
                {name: '台中市立梧棲國中', city: '台中市', district: '梧棲區'},
                {name: '台中市立后里國中', city: '台中市', district: '后里區'},
                {name: '台中市立神岡國中', city: '台中市', district: '神岡區'},
                {name: '台中市立潭子國中', city: '台中市', district: '潭子區'},
                {name: '台中市立大雅國中', city: '台中市', district: '大雅區'},
                {name: '台中市立新社國中', city: '台中市', district: '新社區'},
                {name: '台中市立石岡國中', city: '台中市', district: '石岡區'},
                {name: '台中市立外埔國中', city: '台中市', district: '外埔區'},
                {name: '台中市立大安國中', city: '台中市', district: '大安區'},
                {name: '台中市立烏日國中', city: '台中市', district: '烏日區'},
                {name: '台中市立大肚國中', city: '台中市', district: '大肚區'},
                {name: '台中市立龍井國中', city: '台中市', district: '龍井區'},
                {name: '台中市立霧峰國中', city: '台中市', district: '霧峰區'},
                {name: '台中市立太平國中', city: '台中市', district: '太平區'},
                {name: '台中市立大里國中', city: '台中市', district: '大里區'},
                {name: '台中市立和平國中', city: '台中市', district: '和平區'},
                {name: '台南市立台南國中', city: '台南市', district: '中西區'},
                {name: '台南市立東區國中', city: '台南市', district: '東區'},
                {name: '台南市立南區國中', city: '台南市', district: '南區'},
                {name: '台南市立北區國中', city: '台南市', district: '北區'},
                {name: '台南市立安平國中', city: '台南市', district: '安平區'},
                {name: '台南市立安南國中', city: '台南市', district: '安南區'},
                {name: '台南市立永康國中', city: '台南市', district: '永康區'},
                {name: '台南市立歸仁國中', city: '台南市', district: '歸仁區'},
                {name: '台南市立新化國中', city: '台南市', district: '新化區'},
                {name: '台南市立左鎮國中', city: '台南市', district: '左鎮區'},
                {name: '台南市立玉井國中', city: '台南市', district: '玉井區'},
                {name: '台南市立楠西國中', city: '台南市', district: '楠西區'},
                {name: '台南市立南化國中', city: '台南市', district: '南化區'},
                {name: '台南市立仁德國中', city: '台南市', district: '仁德區'},
                {name: '台南市立關廟國中', city: '台南市', district: '關廟區'},
                {name: '台南市立龍崎國中', city: '台南市', district: '龍崎區'},
                {name: '台南市立官田國中', city: '台南市', district: '官田區'},
                {name: '台南市立麻豆國中', city: '台南市', district: '麻豆區'},
                {name: '台南市立佳里國中', city: '台南市', district: '佳里區'},
                {name: '台南市立西港國中', city: '台南市', district: '西港區'},
                {name: '台南市立七股國中', city: '台南市', district: '七股區'},
                {name: '台南市立將軍國中', city: '台南市', district: '將軍區'},
                {name: '台南市立學甲國中', city: '台南市', district: '學甲區'},
                {name: '台南市立北門國中', city: '台南市', district: '北門區'},
                {name: '台南市立新營國中', city: '台南市', district: '新營區'},
                {name: '台南市立後壁國中', city: '台南市', district: '後壁區'},
                {name: '台南市立白河國中', city: '台南市', district: '白河區'},
                {name: '台南市立東山國中', city: '台南市', district: '東山區'},
                {name: '台南市立六甲國中', city: '台南市', district: '六甲區'},
                {name: '台南市立下營國中', city: '台南市', district: '下營區'},
                {name: '台南市立柳營國中', city: '台南市', district: '柳營區'},
                {name: '台南市立鹽水國中', city: '台南市', district: '鹽水區'},
                {name: '台南市立善化國中', city: '台南市', district: '善化區'},
                {name: '台南市立大內國中', city: '台南市', district: '大內區'},
                {name: '台南市立山上國中', city: '台南市', district: '山上區'},
                {name: '台南市立新市國中', city: '台南市', district: '新市區'},
                {name: '台南市立安定國中', city: '台南市', district: '安定區'},
                {name: '高雄市立高雄國中', city: '高雄市', district: '新興區'},
                {name: '高雄市立前金國中', city: '高雄市', district: '前金區'},
                {name: '高雄市立苓雅國中', city: '高雄市', district: '苓雅區'},
                {name: '高雄市立鹽埕國中', city: '高雄市', district: '鹽埕區'},
                {name: '高雄市立鼓山國中', city: '高雄市', district: '鼓山區'},
                {name: '高雄市立旗津國中', city: '高雄市', district: '旗津區'},
                {name: '高雄市立前鎮國中', city: '高雄市', district: '前鎮區'},
                {name: '高雄市立三民國中', city: '高雄市', district: '三民區'},
                {name: '高雄市立楠梓國中', city: '高雄市', district: '楠梓區'},
                {name: '高雄市立小港國中', city: '高雄市', district: '小港區'},
                {name: '高雄市立左營國中', city: '高雄市', district: '左營區'},
                {name: '高雄市立仁武國中', city: '高雄市', district: '仁武區'},
                {name: '高雄市立大社區國中', city: '高雄市', district: '大社區'},
                {name: '高雄市立岡山國中', city: '高雄市', district: '岡山區'},
                {name: '高雄市立路竹國中', city: '高雄市', district: '路竹區'},
                {name: '高雄市立阿蓮國中', city: '高雄市', district: '阿蓮區'},
                {name: '高雄市立田寮國中', city: '高雄市', district: '田寮區'},
                {name: '高雄市立燕巢國中', city: '高雄市', district: '燕巢區'},
                {name: '高雄市立橋頭國中', city: '高雄市', district: '橋頭區'},
                {name: '高雄市立梓官國中', city: '高雄市', district: '梓官區'},
                {name: '高雄市立彌陀國中', city: '高雄市', district: '彌陀區'},
                {name: '高雄市立永安國中', city: '高雄市', district: '永安區'},
                {name: '高雄市立湖內國中', city: '高雄市', district: '湖內區'},
                {name: '高雄市立鳳山國中', city: '高雄市', district: '鳳山區'},
                {name: '高雄市立大寮國中', city: '高雄市', district: '大寮區'},
                {name: '高雄市立林園國中', city: '高雄市', district: '林園區'},
                {name: '高雄市立鳥松國中', city: '高雄市', district: '鳥松區'},
                {name: '高雄市立大樹國中', city: '高雄市', district: '大樹區'},
                {name: '高雄市立旗山國中', city: '高雄市', district: '旗山區'},
                {name: '高雄市立美濃國中', city: '高雄市', district: '美濃區'},
                {name: '高雄市立六龜國中', city: '高雄市', district: '六龜區'},
                {name: '高雄市立內門國中', city: '高雄市', district: '內門區'},
                {name: '高雄市立杉林國中', city: '高雄市', district: '杉林區'},
                {name: '高雄市立甲仙國中', city: '高雄市', district: '甲仙區'},
                {name: '高雄市立桃源國中', city: '高雄市', district: '桃源區'},
                {name: '高雄市立那瑪夏國中', city: '高雄市', district: '那瑪夏區'},
                {name: '高雄市立茂林國中', city: '高雄市', district: '茂林區'},
                // 基隆市立國中
                {name: '基隆市立基隆國中', city: '基隆市', district: '中正區'},
                {name: '基隆市立中正國中', city: '基隆市', district: '中正區'},
                {name: '基隆市立信義國中', city: '基隆市', district: '信義區'},
                {name: '基隆市立仁愛國中', city: '基隆市', district: '仁愛區'},
                {name: '基隆市立中山國中', city: '基隆市', district: '中山區'},
                {name: '基隆市立安樂國中', city: '基隆市', district: '安樂區'},
                {name: '基隆市立暖暖國中', city: '基隆市', district: '暖暖區'},
                {name: '基隆市立七堵國中', city: '基隆市', district: '七堵區'},
                // 新竹市立國中
                {name: '新竹市立新竹國中', city: '新竹市', district: '東區'},
                {name: '新竹市立光華國中', city: '新竹市', district: '東區'},
                {name: '新竹市立育賢國中', city: '新竹市', district: '東區'},
                {name: '新竹市立三民國中', city: '新竹市', district: '東區'},
                {name: '新竹市立建華國中', city: '新竹市', district: '東區'},
                {name: '新竹市立香山國中', city: '新竹市', district: '香山區'},
                {name: '新竹市立富禮國中', city: '新竹市', district: '香山區'},
                {name: '新竹市立虎林國中', city: '新竹市', district: '香山區'},
                // 新竹縣立國中
                {name: '新竹縣立竹北國中', city: '新竹縣', district: '竹北市'},
                {name: '新竹縣立博愛國中', city: '新竹縣', district: '竹北市'},
                {name: '新竹縣立六家國中', city: '新竹縣', district: '竹北市'},
                {name: '新竹縣立成功國中', city: '新竹縣', district: '竹北市'},
                {name: '新竹縣立竹東國中', city: '新竹縣', district: '竹東鎮'},
                {name: '新竹縣立二重國中', city: '新竹縣', district: '竹東鎮'},
                {name: '新竹縣立關西國中', city: '新竹縣', district: '關西鎮'},
                {name: '新竹縣立新埔國中', city: '新竹縣', district: '新埔鎮'},
                {name: '新竹縣立湖口國中', city: '新竹縣', district: '湖口鄉'},
                {name: '新竹縣立新豐國中', city: '新竹縣', district: '新豐鄉'},
                {name: '新竹縣立芎林國中', city: '新竹縣', district: '芎林鄉'},
                {name: '新竹縣立寶山國中', city: '新竹縣', district: '寶山鄉'},
                {name: '新竹縣立北埔國中', city: '新竹縣', district: '北埔鄉'},
                {name: '新竹縣立峨眉國中', city: '新竹縣', district: '峨眉鄉'},
                {name: '新竹縣立尖石國中', city: '新竹縣', district: '尖石鄉'},
                {name: '新竹縣立五峰國中', city: '新竹縣', district: '五峰鄉'},
                {name: '苗栗縣立苗栗國中', city: '苗栗縣', district: '苗栗市'},
                {name: '彰化縣立彰化國中', city: '彰化縣', district: '彰化市'},
                {name: '南投縣立南投國中', city: '南投縣', district: '南投市'},
                {name: '雲林縣立斗六國中', city: '雲林縣', district: '斗六市'},
                {name: '嘉義縣立朴子國中', city: '嘉義縣', district: '朴子市'},
                {name: '屏東縣立屏東國中', city: '屏東縣', district: '屏東市'},
                {name: '宜蘭縣立宜蘭國中', city: '宜蘭縣', district: '宜蘭市'},
                {name: '花蓮縣立花蓮國中', city: '花蓮縣', district: '花蓮市'},
                {name: '台東縣立台東國中', city: '台東縣', district: '台東市'},
                {name: '澎湖縣立馬公國中', city: '澎湖縣', district: '馬公市'},
                {name: '金門縣立金城國中', city: '金門縣', district: '金城鎮'},
                {name: '連江縣立介壽國中', city: '連江縣', district: '南竿鄉'}
            ];
            
            // 搜尋匹配的學校
            const results = fallbackSchools.filter(school => 
                school.name.includes(keyword) || 
                school.city.includes(keyword) || 
                school.district.includes(keyword)
            );
            
            displaySearchResults(results, keyword);
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
            
            // 提交到後端API (Python Flask)
            fetch('http://localhost:5000/enrollment/submit', {
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

