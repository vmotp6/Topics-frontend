<?php
// 更新老師密碼
require_once 'session_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查是否為老師角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '老師') {
    echo json_encode(['success' => false, 'message' => '只有老師可以修改密碼']);
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '請使用 POST 方法提交']);
    exit;
}

// 獲取表單資料
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

// 驗證必填欄位
if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => '請填寫所有必填欄位']);
    exit;
}

// 驗證密碼長度
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => '密碼長度至少需要 6 個字元']);
    exit;
}

try {
    // 資料庫連接
    $host = 'localhost';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取用戶 ID 和當前密碼
    $stmt = $pdo->prepare("SELECT id, password FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_result) {
        echo json_encode(['success' => false, 'message' => '使用者不存在']);
        exit;
    }
    
    $user_id = $user_result['id'];
    $stored_password = $user_result['password'];
    
    // 驗證當前密碼（支援雜湊密碼和明文密碼）
    $password_valid = false;
    
    // 檢查是否為雜湊密碼（PHP password_hash 格式：$2y$... 或 $2b$... 或 $2a$...）
    if ($stored_password && (strpos($stored_password, '$2y$') === 0 || strpos($stored_password, '$2b$') === 0 || strpos($stored_password, '$2a$') === 0)) {
        // 使用 password_verify 驗證雜湊密碼
        $password_valid = password_verify($current_password, $stored_password);
    } else {
        // 明文密碼比較（向後兼容）
        $password_valid = ($current_password === $stored_password);
    }
    
    if (!$password_valid) {
        echo json_encode(['success' => false, 'message' => '當前密碼不正確']);
        exit;
    }
    
    // 更新密碼（直接儲存明文，不進行雜湊）
    // ⚠️ 警告：明文儲存密碼是不安全的做法，僅供測試使用
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$new_password, $user_id]);
    
    // 清除 session（登出）
    $_SESSION = array();
    unset($_SESSION['logged_in']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    unset($_SESSION['user_id']);
    unset($_SESSION['id']);
    unset($_SESSION['login_method']);
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => '密碼更新成功，請重新登入',
        'logout_required' => true
    ]);
    
} catch (PDOException $e) {
    error_log("更新老師密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤，請稍後再試']);
} catch (Exception $e) {
    error_log("更新老師密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}
?>

