<?php
// 更新學生密碼
require_once 'session_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查是否為學生角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '學生') {
    echo json_encode(['success' => false, 'message' => '只有學生可以修改密碼']);
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
    
    // 驗證當前密碼
    if (!password_verify($current_password, $stored_password)) {
        echo json_encode(['success' => false, 'message' => '當前密碼不正確']);
        exit;
    }
    
    // 更新密碼
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$new_hashed_password, $user_id]);
    
    echo json_encode(['success' => true, 'message' => '密碼更新成功']);
    
} catch (PDOException $e) {
    error_log("更新學生密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤，請稍後再試']);
} catch (Exception $e) {
    error_log("更新學生密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}
?>

