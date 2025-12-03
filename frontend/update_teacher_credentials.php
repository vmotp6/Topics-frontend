<?php
// 更新老師帳號和密碼
require_once 'session_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查是否為老師角色（支援角色代碼 'TEA' 和中文名稱 '老師'）
$user_role = $_SESSION['role'] ?? '';
if ($user_role !== '老師' && $user_role !== 'TEA') {
    echo json_encode(['success' => false, 'message' => '只有老師可以修改帳號密碼']);
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '請使用 POST 方法提交']);
    exit;
}

// 獲取表單資料
$old_username = $_POST['old_username'] ?? $_SESSION['username'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$new_username = $_POST['new_username'] ?? '';

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

// 如果要修改帳號，驗證帳號長度
if (!empty($new_username) && $new_username !== $old_username) {
    if (strlen($new_username) < 3) {
        echo json_encode(['success' => false, 'message' => '帳號長度至少需要 3 個字元']);
        exit;
    }
}

try {
    // 資料庫連接
    $host = 'localhost';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取用戶 ID、當前密碼和 username_changed 狀態
    $stmt = $pdo->prepare("SELECT id, password, username_changed FROM user WHERE username = ?");
    $stmt->execute([$old_username]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_result) {
        echo json_encode(['success' => false, 'message' => '使用者不存在']);
        exit;
    }
    
    $user_id = $user_result['id'];
    $stored_password = $user_result['password'];
    // NULL = 手動創建的帳號（不適用此功能）
    // 0 = 系統生成的帳號，未修改過
    // 1 = 系統生成的帳號，已修改過
    $username_changed = isset($user_result['username_changed']) && $user_result['username_changed'] !== null 
        ? (int)$user_result['username_changed'] 
        : null;
    
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
    
    // 如果要修改帳號，檢查是否允許修改
    if (!empty($new_username) && $new_username !== $old_username) {
        // 檢查是否已經修改過帳號（使用嚴格比較，確保 NULL 不會等於 1）
        if ($username_changed === 1) {
            echo json_encode(['success' => false, 'message' => '帳號只能修改一次，您已經修改過帳號了']);
            exit;
        }
        
        // 如果是 NULL，表示這是手動創建的帳號，不應該允許修改
        // 但實際上，因為前端會通過 $is_auto_generated 判斷，手動創建的帳號不會進入這個流程
        // 這裡額外檢查以確保安全
        if ($username_changed === null) {
            echo json_encode(['success' => false, 'message' => '此帳號不支援修改功能']);
            exit;
        }
        
        // 檢查新帳號是否已存在
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
        $stmt->execute([$new_username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '此帳號已被使用']);
            exit;
        }
        
        // 更新帳號和 username_changed 標記
        $stmt = $pdo->prepare("UPDATE user SET username = ?, username_changed = 1 WHERE id = ?");
        $stmt->execute([$new_username, $user_id]);
        
        // 更新 session 中的 username
        $_SESSION['username'] = $new_username;
    }
    
    // 更新密碼（使用 password_hash 進行雜湊）
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);
    
    // 準備回應訊息
    $result_message = '密碼更新成功';
    $response_data = [
        'success' => true,
        'message' => $result_message
    ];
    
    if (!empty($new_username) && $new_username !== $old_username) {
        $result_message = '帳號和密碼更新成功';
        $response_data['message'] = $result_message;
        $response_data['new_username'] = $new_username;
    }
    
    echo json_encode($response_data);
    
} catch (PDOException $e) {
    error_log("更新老師帳號密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤，請稍後再試']);
} catch (Exception $e) {
    error_log("更新老師帳號密碼錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}
?>

