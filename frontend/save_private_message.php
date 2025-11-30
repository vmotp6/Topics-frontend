<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支援POST請求']);
    exit;
}

// 資料庫連接
$host = 'localhost';  // 使用本機資料庫
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['from']) || !isset($data['to']) || !isset($data['message'])) {
        echo json_encode(['error' => '無效的資料格式']);
        exit;
    }
    
    // 檢查表結構
    $stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'role')");
    $columnInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasRole = false;
    $fromUserType = null;
    
    foreach ($columnInfo as $col) {
        if ($col['COLUMN_NAME'] === 'role') {
            $hasRole = true;
        }
        if ($col['COLUMN_NAME'] === 'from_user') {
            $fromUserType = $col['DATA_TYPE'];
        }
    }
    
    // 判斷 from_user 和 to_user 是 INT 還是 VARCHAR
    $isIntType = ($fromUserType === 'int' || $fromUserType === 'tinyint' || $fromUserType === 'smallint' || $fromUserType === 'mediumint' || $fromUserType === 'bigint');
    
    if ($isIntType) {
        // 如果是 INT 類型，需要將 username 轉換為 user.id
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$data['from']]);
        $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $fromUserId = $fromUser ? $fromUser['id'] : null;
        
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$data['to']]);
        $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $toUserId = $toUser ? $toUser['id'] : null;
        
        if (!$fromUserId || !$toUserId) {
            echo json_encode(['error' => '找不到指定的用戶']);
            exit;
        }
        
        if ($hasRole) {
            $sql = "INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fromUserId, $toUserId, $data['message'], $data['role'] ?? '用戶']);
        } else {
            $sql = "INSERT INTO private_chat_history (from_user, to_user, message) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fromUserId, $toUserId, $data['message']]);
        }
    } else {
        // 如果是 VARCHAR 類型，直接使用 username
        if ($hasRole) {
            $sql = "INSERT INTO private_chat_history (from_user, to_user, message, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['from'], $data['to'], $data['message'], $data['role'] ?? '用戶']);
        } else {
            $sql = "INSERT INTO private_chat_history (from_user, to_user, message) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['from'], $data['to'], $data['message']]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => '私聊訊息已儲存',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => '儲存私聊訊息失敗: ' . $e->getMessage()]);
}
?>
