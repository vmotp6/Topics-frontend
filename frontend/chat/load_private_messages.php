<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查表結構，自動適配正規化或非正規化版本
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
    $useUsername = in_array('from_user', $columns) && in_array('to_user', $columns);
    
    // 獲取GET參數
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $lastMessageId = isset($_GET['lastMessageId']) ? (int)$_GET['lastMessageId'] : 0;
    
    // 添加調試日誌
    error_log("載入消息請求: from={$from}, to={$to}, lastMessageId={$lastMessageId}");
    
    if (empty($from) || empty($to)) {
        error_log("缺少必要參數: from={$from}, to={$to}");
        echo json_encode(['error' => '缺少必要參數']);
        exit;
    }
    
    if ($useUserId) {
        // 使用正規化版本：先將 username 轉換為 user_id
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$from]);
        $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $fromUserId = $fromUser ? $fromUser['id'] : null;
        
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$to]);
        $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $toUserId = $toUser ? $toUser['id'] : null;
        
        if (!$fromUserId || !$toUserId) {
            echo json_encode([
                'success' => false,
                'error' => '找不到指定的用戶'
            ]);
            exit;
        }
        
        // 使用 user_id 查詢
        if ($lastMessageId > 0) {
            // 只獲取比 lastMessageId 更新的消息
            $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                    FROM private_chat_history pch
                    LEFT JOIN user u1 ON pch.from_user_id = u1.id
                    LEFT JOIN user u2 ON pch.to_user_id = u2.id
                    WHERE ((pch.from_user_id = ? AND pch.to_user_id = ?) 
                    OR (pch.from_user_id = ? AND pch.to_user_id = ?))
                    AND pch.id > ?
                    ORDER BY pch.timestamp ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId, $lastMessageId]);
        } else {
            // 獲取所有消息
            $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                    FROM private_chat_history pch
                    LEFT JOIN user u1 ON pch.from_user_id = u1.id
                    LEFT JOIN user u2 ON pch.to_user_id = u2.id
                    WHERE (pch.from_user_id = ? AND pch.to_user_id = ?) 
                    OR (pch.from_user_id = ? AND pch.to_user_id = ?) 
                    ORDER BY pch.timestamp ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
        }
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("使用 from_user_id/to_user_id 查詢，找到 " . count($messages) . " 條消息");
        
        // 轉換為兼容格式
        foreach ($messages as &$msg) {
            $msg['from_user'] = $msg['from_username'];
            $msg['to_user'] = $msg['to_username'];
            unset($msg['from_username'], $msg['to_username']);
        }
        
    } elseif ($useUsername) {
        // 檢查 from_user 和 to_user 字段的類型（INT 還是 VARCHAR）
        $stmt = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME = 'from_user'");
        $fromUserType = $stmt->fetch(PDO::FETCH_COLUMN);
        $isIntType = ($fromUserType === 'int' || $fromUserType === 'tinyint' || $fromUserType === 'smallint' || $fromUserType === 'mediumint' || $fromUserType === 'bigint');
        
        if ($isIntType) {
            // 如果是 INT 類型，需要將 username 轉換為 user.id
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$from]);
            $fromUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $fromUserId = $fromUser ? $fromUser['id'] : null;
            
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$to]);
            $toUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $toUserId = $toUser ? $toUser['id'] : null;
            
            if (!$fromUserId || !$toUserId) {
                echo json_encode([
                    'success' => false,
                    'error' => '找不到指定的用戶'
                ]);
                exit;
            }
            
            if ($lastMessageId > 0) {
                // 只獲取比 lastMessageId 更新的消息
                $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                        FROM private_chat_history pch
                        LEFT JOIN user u1 ON pch.from_user = u1.id
                        LEFT JOIN user u2 ON pch.to_user = u2.id
                        WHERE ((pch.from_user = ? AND pch.to_user = ?) 
                        OR (pch.from_user = ? AND pch.to_user = ?))
                        AND pch.id > ?
                        ORDER BY pch.timestamp ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId, $lastMessageId]);
            } else {
                // 獲取所有消息
                $sql = "SELECT pch.*, u1.username as from_username, u2.username as to_username 
                        FROM private_chat_history pch
                        LEFT JOIN user u1 ON pch.from_user = u1.id
                        LEFT JOIN user u2 ON pch.to_user = u2.id
                        WHERE (pch.from_user = ? AND pch.to_user = ?) 
                        OR (pch.from_user = ? AND pch.to_user = ?) 
                        ORDER BY pch.timestamp ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
            }
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("使用 from_user/to_user (INT) 查詢，找到 " . count($messages) . " 條消息");
            
            // 轉換為兼容格式
            foreach ($messages as &$msg) {
                $msg['from_user'] = $msg['from_username'];
                $msg['to_user'] = $msg['to_username'];
                unset($msg['from_username'], $msg['to_username']);
            }
        } else {
            // 如果是 VARCHAR 類型，直接使用 username
            if ($lastMessageId > 0) {
                // 只獲取比 lastMessageId 更新的消息
                $sql = "SELECT * FROM private_chat_history 
                        WHERE ((from_user = ? AND to_user = ?) 
                        OR (from_user = ? AND to_user = ?))
                        AND id > ?
                        ORDER BY timestamp ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$from, $to, $to, $from, $lastMessageId]);
            } else {
                // 獲取所有消息
                $sql = "SELECT * FROM private_chat_history 
                        WHERE (from_user = ? AND to_user = ?) 
                        OR (from_user = ? AND to_user = ?) 
                        ORDER BY timestamp ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$from, $to, $to, $from]);
            }
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("使用 from_user/to_user (VARCHAR) 查詢，找到 " . count($messages) . " 條消息");
        }
    } else {
        error_log("表結構異常: 找不到用戶欄位");
        echo json_encode([
            'success' => false,
            'error' => 'private_chat_history 表結構異常，找不到用戶欄位'
        ]);
        exit;
    }
    
    error_log("返回消息: " . count($messages) . " 條");
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '載入私聊訊息失敗: ' . $e->getMessage()
    ]);
}
?>
