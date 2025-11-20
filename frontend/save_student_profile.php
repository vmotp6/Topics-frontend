<?php
// 保存學生個人資料
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
    echo json_encode(['success' => false, 'message' => '只有學生可以保存個人資料']);
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '請使用 POST 方法提交']);
    exit;
}

// 獲取表單資料
$username = $_POST['username'] ?? '';
$department = $_POST['department'] ?? '';
$phone = $_POST['phone'] ?? '';

// 驗證資料
if (empty($username) || empty($department) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => '請填寫所有欄位']);
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
    
    // 獲取用戶 ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_result) {
        echo json_encode(['success' => false, 'message' => '使用者不存在']);
        exit;
    }
    
    $user_id = $user_result['id'];
    
    // 檢查 student 表是否存在該用戶的記錄
    $stmt = $pdo->prepare("SELECT id FROM student WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_exists = $stmt->fetch();
    
    if ($student_exists) {
        // 更新現有資料
        $stmt = $pdo->prepare("UPDATE student SET department = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$department, $phone, $user_id]);
    } else {
        // 獲取用戶姓名（如果有的話）
        $stmt = $pdo->prepare("SELECT name FROM user WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = $user_data['name'] ?? '';
        
        // 插入新資料
        $stmt = $pdo->prepare("INSERT INTO student (user_id, name, department, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $department, $phone]);
    }
    
    echo json_encode(['success' => true, 'message' => '個人資料保存成功']);
    
} catch (PDOException $e) {
    error_log("保存學生個人資料錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤，請稍後再試']);
} catch (Exception $e) {
    error_log("保存學生個人資料錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}
?>









