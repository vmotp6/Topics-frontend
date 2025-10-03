<?php
/**
 * 更新後的就讀意願表單測試頁面
 */

session_start();

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<h1>❌ 未登入</h1>";
    echo "<p>請先登入後再訪問此頁面。</p>";
    echo "<p><a href='one.php'>前往登入</a></p>";
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

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

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>就讀意願表單 - 更新測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 15px; border-radius: 6px; margin: 20px 0; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .required { color: red; }
        .captcha-container { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
        .captcha-container input { width: 100px; text-align: center; font-weight: bold; letter-spacing: 2px; }
        .captcha-container img { border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
        .refresh-btn { background: #007bff; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; font-style: italic; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🎓 康寧大學就讀意願表單 - 更新測試</h1>";

// 顯示用戶信息
echo "<div class='status success'>";
echo "<h3>✅ 登入狀態正常</h3>";
echo "<p><strong>用戶名:</strong> " . htmlspecialchars($username) . "</p>";
echo "<p><strong>角色:</strong> " . htmlspecialchars($role) . "</p>";
echo "<p><strong>登入方式:</strong> " . htmlspecialchars($_SESSION['login_method'] ?? '未知') . "</p>";
echo "</div>";

// 顯示老師資料
if (!empty($teachers)) {
    echo "<div class='status success'>";
    echo "<h3>✅ 老師資料載入成功</h3>";
    echo "<p>找到 " . count($teachers) . " 位老師</p>";
    echo "<details><summary>查看老師列表</summary>";
    echo "<ul>";
    foreach ($teachers as $teacher) {
        echo "<li>" . htmlspecialchars($teacher['name']) . " (" . htmlspecialchars($teacher['department']) . ")</li>";
    }
    echo "</ul></details>";
    echo "</div>";
} else {
    echo "<div class='status warning'>";
    echo "<h3>⚠️ 老師資料載入失敗</h3>";
    echo "<p>無法載入老師資料，請檢查資料庫連接。</p>";
    echo "</div>";
}

// 測試表單
echo "<h2>📝 更新功能測試</h2>";
echo "<form id='testForm'>";
echo "<div class='form-group'>";
echo "<label for='name'><span class='required'>*</span> 姓名:</label>";
echo "<input type='text' id='name' name='name' required value='" . htmlspecialchars($username) . "'>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='identity'><span class='required'>*</span> 身分別:</label>";
echo "<select id='identity' name='identity' required>";
echo "<option value='學生'" . ($role === '學生' ? ' selected' : '') . ">學生</option>";
echo "<option value='家長'>家長</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='phone1'><span class='required'>*</span> 聯絡電話:</label>";
echo "<input type='tel' id='phone1' name='phone1' required placeholder='0912345678'>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='recommended_teacher'>推薦老師:</label>";
echo "<select id='recommended_teacher' name='recommended_teacher'>";
echo "<option value=''>請選擇推薦老師（可選）</option>";
if (!empty($teachers)) {
    $currentDepartment = '';
    foreach ($teachers as $teacher) {
        if ($teacher['department'] !== $currentDepartment) {
            if ($currentDepartment !== '') echo '</optgroup>';
            echo '<optgroup label="' . htmlspecialchars($teacher['department']) . '">';
            $currentDepartment = $teacher['department'];
        }
        echo "<option value='" . htmlspecialchars($teacher['name']) . "'>";
        echo htmlspecialchars($teacher['name']) . " (" . htmlspecialchars($teacher['department']) . ")";
        echo "</option>";
    }
    if ($currentDepartment !== '') echo '</optgroup>';
} else {
    echo "<option value=''>暫無老師資料</option>";
}
echo "</select>";
echo "<div class='help-text'>可選擇推薦您的老師，或留空</div>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='captcha'><span class='required'>*</span> 驗證碼:</label>";
echo "<div class='captcha-container'>";
echo "<input type='text' id='captcha' name='captcha' placeholder='請輸入驗證碼' maxlength='4' required>";
echo "<img src='captcha.php' id='captcha-image' alt='驗證碼' onclick='refreshCaptcha()'>";
echo "<button type='button' onclick='refreshCaptcha()' class='refresh-btn'>刷新</button>";
echo "</div>";
echo "</div>";

echo "<button type='submit' class='btn btn-success'>提交測試</button>";
echo "</form>";

echo "<div id='result' style='margin-top: 20px;'></div>";

// 提供其他選項
echo "<h2>🔗 其他選項</h2>";
echo "<p><a href='cooperation_upload.php' class='btn'>前往正式表單</a></p>";
echo "<p><a href='enrollment_simple.php' class='btn'>簡化版表單</a></p>";
echo "<p><a href='enrollment_debug.php' class='btn'>調試頁面</a></p>";
echo "<p><a href='chat/chat.php' class='btn'>前往聊天室</a></p>";

echo "</div>";

echo "
<script>
// 驗證碼刷新功能
function refreshCaptcha() {
    const captchaImage = document.getElementById('captcha-image');
    captchaImage.src = 'captcha.php?' + Math.random();
    document.getElementById('captcha').value = '';
}

document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<div class=\"status warning\">提交中...</div>';
    
    try {
        const formData = new FormData(this);
        formData.append('username', '" . addslashes($username) . "');
        
        const response = await fetch('../backend/api/enrollment/enrollment_api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = '<div class=\"status success\">✅ ' + result.message + '</div>';
            // 刷新驗證碼
            refreshCaptcha();
        } else {
            resultDiv.innerHTML = '<div class=\"status error\">❌ ' + result.message + '</div>';
            // 刷新驗證碼
            refreshCaptcha();
        }
        
    } catch (error) {
        resultDiv.innerHTML = '<div class=\"status error\">❌ 提交失敗: ' + error.message + '</div>';
        // 刷新驗證碼
        refreshCaptcha();
    }
});
</script>
</body>
</html>";
?>

