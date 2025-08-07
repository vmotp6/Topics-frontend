<?php
session_start();
header('Content-Type: application/json');

// 資料庫連線設定
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '請先登入']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save_message':
        // 儲存AI聊天訊息
        $message_type = $_POST['message_type'] ?? '';
        $message_content = $_POST['message_content'] ?? '';
        
        if (empty($message_type) || empty($message_content)) {
            echo json_encode(['error' => '缺少必要參數']);
            exit;
        }
        
        if (!in_array($message_type, ['user', 'ai'])) {
            echo json_encode(['error' => '無效的訊息類型']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO ai_chat_history (user_id, message_type, message_content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $message_type, $message_content]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch(PDOException $e) {
            echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_history':
        // 獲取AI聊天記錄
        try {
            $stmt = $pdo->prepare("SELECT message_type, message_content, created_at FROM ai_chat_history WHERE user_id = ? ORDER BY created_at ASC");
            $stmt->execute([$user_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'history' => $history]);
        } catch(PDOException $e) {
            echo json_encode(['error' => '獲取記錄失敗: ' . $e->getMessage()]);
        }
        break;
        
    case 'clear_history':
        // 清除AI聊天記錄
        try {
            $stmt = $pdo->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            echo json_encode(['error' => '清除記錄失敗: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => '無效的操作']);
        break;
}
?> 