<?php
/**
 * 就讀意願表單調試頁面
 */

session_start();

echo "<h1>🔍 就讀意願表單調試</h1>";

// 1. 檢查登入狀態
echo "<h2>1. 登入狀態檢查</h2>";
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    echo "✅ 已登入<br>";
    echo "用戶名: " . ($_SESSION['username'] ?? '未設定') . "<br>";
    echo "角色: " . ($_SESSION['role'] ?? '未設定') . "<br>";
    echo "登入方式: " . ($_SESSION['login_method'] ?? '未設定') . "<br>";
} else {
    echo "❌ 未登入<br>";
}

// 2. 檢查角色權限
echo "<h2>2. 角色權限檢查</h2>";
$role = $_SESSION['role'] ?? '';
if ($role === '學生' || $role === 'student') {
    echo "✅ 角色權限正確 - 可以訪問就讀意願表單<br>";
} else {
    echo "❌ 角色權限不足 - 需要學生身份<br>";
    echo "當前角色: " . ($role ?: '未設定') . "<br>";
}

// 3. 檢查reCAPTCHA配置
echo "<h2>3. reCAPTCHA配置檢查</h2>";
$recaptcha_file = '../backend/config/recaptcha_config.php';
if (file_exists($recaptcha_file)) {
    echo "✅ reCAPTCHA配置文件存在<br>";
    require_once $recaptcha_file;
    if (defined('RECAPTCHA_SITE_KEY') && defined('RECAPTCHA_SECRET_KEY')) {
        echo "✅ reCAPTCHA金鑰已設定<br>";
        echo "Site Key: " . substr(RECAPTCHA_SITE_KEY, 0, 10) . "...<br>";
        echo "Secret Key: " . substr(RECAPTCHA_SECRET_KEY, 0, 10) . "...<br>";
    } else {
        echo "❌ reCAPTCHA金鑰未設定<br>";
    }
} else {
    echo "❌ reCAPTCHA配置文件不存在<br>";
    echo "預期路徑: " . $recaptcha_file . "<br>";
}

// 4. 檢查資料庫連接
echo "<h2>4. 資料庫連接檢查</h2>";
try {
    $host = '100.79.58.120';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連接成功<br>";
    
    // 檢查enrollment_applications表
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ enrollment_applications表存在<br>";
    } else {
        echo "❌ enrollment_applications表不存在<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ 資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 5. 提供解決方案
echo "<h2>5. 解決方案</h2>";

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<p><strong>問題：</strong>未登入</p>";
    echo "<p><strong>解決方案：</strong></p>";
    echo "<ul>";
    echo "<li><a href='index.php'>前往登入頁面</a></li>";
    echo "<li>使用學生身份登入</li>";
    echo "</ul>";
} elseif ($role !== '學生' && $role !== 'student') {
    echo "<p><strong>問題：</strong>角色權限不足</p>";
    echo "<p><strong>解決方案：</strong></p>";
    echo "<ul>";
    echo "<li>需要以學生身份登入</li>";
    echo "<li>當前角色: " . htmlspecialchars($role) . "</li>";
    echo "<li><a href='index.php'>重新登入</a></li>";
    echo "</ul>";
} else {
    echo "<p><strong>狀態：</strong>所有檢查通過！</p>";
    echo "<p><strong>可以訪問：</strong></p>";
    echo "<ul>";
    echo "<li><a href='cooperation_upload.php' target='_blank'>就讀意願表單</a></li>";
    echo "<li><a href='enrollment_test.php' target='_blank'>測試版表單</a></li>";
    echo "</ul>";
}

// 6. 快速修復選項
echo "<h2>6. 快速修復</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<h3>🔧 模擬學生登入（僅供測試）</h3>";
    echo "<form method='POST' style='margin: 10px 0;'>";
    echo "<input type='hidden' name='action' value='simulate_login'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>模擬學生登入</button>";
    echo "</form>";
    echo "<p style='color: #666; font-size: 14px;'>注意：這只是為了測試，不會創建真實的用戶帳號</p>";
}

echo "<h3>📋 其他選項</h3>";
echo "<ul>";
echo "<li><a href='index.php'>前往登入頁面</a></li>";
echo "<li><a href='../index.php'>返回首頁</a></li>";
echo "<li><a href='chat/chat.php'>前往聊天室</a></li>";
echo "</ul>";

echo "</div>";

// 處理模擬登入
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simulate_login') {
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = 'test_student_' . time();
    $_SESSION['role'] = '學生';
    $_SESSION['login_method'] = 'debug';
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ 模擬登入成功！</h3>";
    echo "<p>已設定為學生身份，現在可以訪問就讀意願表單了。</p>";
    echo "<p><a href='cooperation_upload.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>前往就讀意願表單</a></p>";
    echo "</div>";
    
    // 重新載入頁面
    echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
}

echo "<h2>7. 系統信息</h2>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>當前時間: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>會話ID: " . session_id() . "</p>";
?>
