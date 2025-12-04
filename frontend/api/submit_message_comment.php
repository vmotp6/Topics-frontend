<?php
// 載入 session 配置（與主頁面保持一致）
require_once __DIR__ . '/../session_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 檢查登入狀態（與 senior_messages.php 的檢查邏輯保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

if (!$isLoggedIn) {
    echo json_encode(['success' => false, 'error' => '請先登入']);
    exit;
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'error' => '無效的留言ID']);
        exit;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => '請輸入留言內容']);
        exit;
    }
    
    // 獲取用戶ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => '找不到用戶資料']);
        exit;
    }
    
    $user_id = $user['id'];
    
    // 驗證留言是否存在
    $stmt = $pdo->prepare("SELECT id FROM senior_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => '留言不存在']);
        exit;
    }
    
    // 插入留言（假設表已存在）
    $stmt = $pdo->prepare("INSERT INTO senior_message_comments (message_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$message_id, $user_id, $content]);
    
    echo json_encode([
        'success' => true,
        'message' => '留言發布成功'
    ]);
    
} catch(PDOException $e) {
    // 如果表不存在，返回錯誤提示
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
        echo json_encode([
            'success' => false,
            'error' => '留言功能尚未啟用，請聯繫管理員創建留言表'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => '發布留言失敗: ' . $e->getMessage()
        ]);
    }
}
?>

