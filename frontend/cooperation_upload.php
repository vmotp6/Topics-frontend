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
                <div class="modern-search-container">
                    <div class="search-input-wrapper">
                        <input type="text" id="junior_high" name="junior_high" placeholder="請輸入學校名稱..." autocomplete="off">
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
                    <i class="fas fa-info-circle"></i> 輸入學校名稱即可即時搜尋，支援模糊匹配
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
        // 驗證碼刷新功能
        function refreshCaptcha() {
            // 生成新的4位數字驗證碼
            let newCaptcha = '';
            for (let i = 0; i < 4; i++) {
                newCaptcha += Math.floor(Math.random() * 10);
            }
            document.getElementById('captchaDisplay').textContent = newCaptcha;
        }

        // 即時搜尋功能 - 使用教育部API
        function performSearch() {
            const keyword = document.getElementById('junior_high').value.trim();
            const resultsDiv = document.getElementById('schoolResults');
            const clearBtn = document.getElementById('clearSearch');
            
            // 顯示/隱藏清除按鈕
            if (keyword.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
                resultsDiv.classList.remove('show');
                return;
            }
            
            if (keyword.length < 2) {
                resultsDiv.innerHTML = '<div class="search-result-item">請輸入至少2個字元</div>';
                resultsDiv.classList.add('show');
                return;
            }
            
            // 顯示載入中
            resultsDiv.innerHTML = '<div class="search-result-item"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
            resultsDiv.classList.add('show');
            
            // 從API獲取搜尋結果
            fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.schools && data.schools.length > 0) {
                        resultsDiv.innerHTML = data.schools.map(school => 
                            `<div class="search-result-item" onclick="selectSchool('${school.name}', '${school.city}', '${school.district}')">
                                <i class="fas fa-school"></i>
                                <div class="school-info">
                                    <span class="school-name">${school.name}</span>
                                    <span class="school-location">${school.city} ${school.district}</span>
                                </div>
                            </div>`
                        ).join('');
                        
                        if (data.total > 20) {
                            resultsDiv.innerHTML += `<div class="search-result-item more-results">還有 ${data.total - 20} 個結果...</div>`;
                        }
                    } else {
                        resultsDiv.innerHTML = '<div class="search-result-item">找不到匹配的學校</div>';
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    resultsDiv.innerHTML = '<div class="search-result-item">搜尋失敗，請稍後再試</div>';
                });
        }

        // 清除搜尋
        function clearSearch() {
            document.getElementById('junior_high').value = '';
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSearch').style.display = 'none';
        }
        
        // 選擇學校
        function selectSchool(schoolName, city, district) {
            const fullSchoolName = `${schoolName} (${city}${district})`;
            document.getElementById('junior_high').value = fullSchoolName;
            document.getElementById('schoolResults').classList.remove('show');
            document.getElementById('clearSearch').style.display = 'block';
        }
        
        // 點擊其他地方隱藏搜尋結果
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.modern-search-container')) {
                document.getElementById('schoolResults').classList.remove('show');
            }
        });

        // 表單提交處理
        document.getElementById('enrollmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 禁用提交按鈕
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            // 收集表單數據
            const formData = new FormData(this);
            
            // 發送AJAX請求
            fetch('api/submit_enrollment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'success';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                    
                    // 清空表單
                    this.reset();
                    refreshCaptcha();
                    
                    // 3秒後隱藏訊息
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 3000);
                } else {
                    messageDiv.className = 'error';
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.className = 'error';
                messageDiv.textContent = '提交失敗，請稍後再試';
                messageDiv.style.display = 'block';
            })
            .finally(() => {
                // 重新啟用提交按鈕
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> 同意送出';
            });
        });

        // 頁面載入時初始化
        document.addEventListener('DOMContentLoaded', function() {
            refreshCaptcha();
            
            // 綁定即時搜尋事件
            const searchInput = document.getElementById('junior_high');
            const clearBtn = document.getElementById('clearSearch');
            
            // 輸入事件（即時搜尋）
            searchInput.addEventListener('input', performSearch);
            
            // 清除按鈕事件
            clearBtn.addEventListener('click', clearSearch);
            
            // 鍵盤事件
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            });
        });
    </script>
</body>
</html>