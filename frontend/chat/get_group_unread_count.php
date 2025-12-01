<?php
/**
 * 獲取每個群組的未讀訊息數量
 */

require_once '../session_config.php';

// 啟動 session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 檢查 session
$currentUsername = null;
$currentRole = null;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $currentUsername = $_SESSION['username'];
    $currentRole = $_SESSION['role'] ?? '用戶';
} else {
    echo json_encode(['success' => false, 'error' => '用戶未登入或 session 已過期']);
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
    
    // 獲取當前用戶的 ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$currentUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => true, 'unread_counts' => []]);
        exit;
    }
    
    $userId = $user['id'];
    
    // 檢查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode(['success' => true, 'unread_counts' => []]);
        exit;
    }
    
    // 檢查是否有 group_read_status 表來追蹤已讀狀態
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_read_status'");
    $hasReadStatusTable = $stmt->rowCount() > 0;
    
    // 獲取用戶加入的所有群組
    $stmt = $pdo->prepare("SELECT group_id FROM group_chat_members WHERE user = ?");
    $stmt->execute([$userId]);
    $userGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $unreadCounts = [];
    
    // 檢查是否有 group_read_status 表，如果沒有則創建
    if (!$hasReadStatusTable) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS group_read_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id VARCHAR(255) NOT NULL,
                user_id INT NOT NULL,
                last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_group_user (group_id, user_id),
                INDEX idx_group_id (group_id),
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $hasReadStatusTable = true;
        } catch (PDOException $e) {
            error_log("創建 group_read_status 表失敗: " . $e->getMessage());
        }
    }
    
    foreach ($userGroups as $groupId) {
        if ($hasReadStatusTable) {
            // 如果有已讀狀態表，使用它來計算未讀數量
            // 獲取用戶最後一次讀取該群組的時間
            $stmt = $pdo->prepare("SELECT last_read_at FROM group_read_status WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $readStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($readStatus && $readStatus['last_read_at']) {
                // 計算最後讀取時間之後的訊息數量（排除自己發送的）
                $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                      FROM group_chat_messages 
                                      WHERE group_id = ? AND timestamp > ? AND from_user != ?");
                $stmt->execute([$groupId, $readStatus['last_read_at'], $userId]);
            } else {
                // 如果沒有讀取記錄，計算所有不是自己發送的訊息
                $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                      FROM group_chat_messages 
                                      WHERE group_id = ? AND from_user != ?");
                $stmt->execute([$groupId, $userId]);
            }
        } else {
            // 如果沒有已讀狀態表，使用簡單邏輯：
            // 計算該群組中不是當前用戶發送的訊息數量
            $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                  FROM group_chat_messages 
                                  WHERE group_id = ? AND from_user != ?");
            $stmt->execute([$groupId, $userId]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unreadCount = (int)($result['unread_count'] ?? 0);
        
        if ($unreadCount > 0) {
            $unreadCounts[$groupId] = $unreadCount;
        }
    }
    
    echo json_encode([
        'success' => true,
        'unread_counts' => $unreadCounts
    ]);
    
} catch(PDOException $e) {
    error_log("獲取群組未讀數量錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '獲取群組未讀數量失敗: ' . $e->getMessage()
    ]);
}

