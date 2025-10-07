<?php
/**
 * 修復後的就讀意願表單測試頁面
 */

session_start();

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<h1>❌ 未登入</h1>";
    echo "<p>請先登入後再訪問此頁面。</p>";
    echo "<p><a href='index.php'>前往登入</a></p>";
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// 檢查reCAPTCHA配置
$recaptcha_available = false;
$recaptcha_file = '../backend/config/recaptcha_config.php';
if (file_exists($recaptcha_file)) {
    require_once $recaptcha_file;
    $recaptcha_available = defined('RECAPTCHA_SITE_KEY') && defined('RECAPTCHA_SECRET_KEY');
}

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>就讀意願表單 - 修復測試</title>
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
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🎓 康寧大學就讀意願表單 - 修復測試</h1>";

// 顯示用戶信息
echo "<div class='status success'>";
echo "<h3>✅ 登入狀態正常</h3>";
echo "<p><strong>用戶名:</strong> " . htmlspecialchars($username) . "</p>";
echo "<p><strong>角色:</strong> " . htmlspecialchars($role) . "</p>";
echo "<p><strong>登入方式:</strong> " . htmlspecialchars($_SESSION['login_method'] ?? '未知') . "</p>";
echo "</div>";

// 檢查reCAPTCHA狀態
if ($recaptcha_available) {
    echo "<div class='status success'>";
    echo "<h3>✅ reCAPTCHA配置正常</h3>";
    echo "<p>reCAPTCHA已正確配置，可以正常使用表單。</p>";
    echo "</div>";
} else {
    echo "<div class='status warning'>";
    echo "<h3>⚠️ reCAPTCHA配置問題</h3>";
    echo "<p>reCAPTCHA配置文件路徑可能有問題，但表單仍可使用。</p>";
    echo "</div>";
}

// 測試表單
echo "<h2>📝 測試表單</h2>";
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
echo "<label for='intention1'>就讀意願:</label>";
echo "<select id='intention1' name='intention1'>";
echo "<option value=''>請選擇</option>";
echo "<option value='無特定'>無特定</option>";
echo "<option value='資訊管理科'>資訊管理科</option>";
echo "<option value='企業管理科'>企業管理科</option>";
echo "<option value='護理科'>護理科</option>";
echo "<option value='幼保科'>幼保科</option>";
echo "<option value='應用外語科'>應用外語科</option>";
echo "<option value='視光科'>視光科</option>";
echo "<option value='動畫科'>動畫科</option>";
echo "</select>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='system1'>學制:</label>";
echo "<select id='system1' name='system1'>";
echo "<option value=''>請選擇</option>";
echo "<option value='五專'>五專</option>";
echo "<option value='大學部'>大學部</option>";
echo "<option value='碩士班'>碩士班</option>";
echo "</select>";
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
        } else {
            resultDiv.innerHTML = '<div class=\"status error\">❌ ' + result.message + '</div>';
        }
        
    } catch (error) {
        resultDiv.innerHTML = '<div class=\"status error\">❌ 提交失敗: ' + error.message + '</div>';
    }
});
</script>
</body>
</html>";
?>


