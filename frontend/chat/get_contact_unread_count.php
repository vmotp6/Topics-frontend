<?php
/**
 * 獲取每個聯絡人的未讀訊息數量
 */

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
    
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少用戶名']);
        exit;
    }
    
    // 檢查表結構，自動適配
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'topics_good' 
                        AND TABLE_NAME = 'private_chat_history' 
                        AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
    
    // 獲取當前用戶的 ID（如果需要）
    if ($useUserId) {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentUserId = $user ? $user['id'] : null;
        
        if (!$currentUserId) {
            echo json_encode(['success' => true, 'unread_counts' => []]);
            exit;
        }
        
        // 獲取每個聯絡人的未讀訊息數量（使用正規化版本）
        // 只查詢有未讀訊息的聯絡人
        // 檢查是否有 is_read 欄位
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME = 'is_read'");
        $hasIsRead = $stmt->rowCount() > 0;
        
        if ($hasIsRead) {
            $sql = "SELECT u.username, COUNT(pch.id) as unread_count
                    FROM private_chat_history pch
                    JOIN user u ON pch.from_user_id = u.id
                    WHERE pch.to_user_id = ? 
                      AND (pch.is_read = 0 OR pch.is_read IS NULL)
                    GROUP BY u.id, u.username";
        } else {
            // 如果沒有 is_read 欄位，假設所有訊息都是未讀（或使用其他邏輯）
            $sql = "SELECT u.username, COUNT(pch.id) as unread_count
                    FROM private_chat_history pch
                    JOIN user u ON pch.from_user_id = u.id
                    WHERE pch.to_user_id = ?
                    GROUP BY u.id, u.username";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 獲取每個聯絡人的未讀訊息數量（使用舊版本）
        // 檢查 from_user 和 to_user 字段的類型（INT 還是 VARCHAR）
        $stmt = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'private_chat_history' 
                            AND COLUMN_NAME = 'from_user'");
        $fromUserType = $stmt->fetch(PDO::FETCH_COLUMN);
        $isIntType = ($fromUserType === 'int' || $fromUserType === 'tinyint' || $fromUserType === 'smallint' || $fromUserType === 'mediumint' || $fromUserType === 'bigint');
        
        // 如果 from_user 和 to_user 是 INT 類型，需要將 username 轉換為 user.id
        if ($isIntType) {
            // 獲取當前用戶的 ID
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentUserId = $user ? $user['id'] : null;
            
            if (!$currentUserId) {
                echo json_encode(['success' => true, 'unread_counts' => []]);
                exit;
            }
            
            // 檢查是否有 is_read 欄位
            $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'topics_good' 
                                AND TABLE_NAME = 'private_chat_history' 
                                AND COLUMN_NAME = 'is_read'");
            $hasIsRead = $stmt->rowCount() > 0;
            
            if ($hasIsRead) {
                $sql = "SELECT u.username, COUNT(pch.id) as unread_count
                        FROM private_chat_history pch
                        LEFT JOIN user u ON pch.from_user = u.id
                        WHERE pch.to_user = ? 
                          AND (pch.is_read = 0 OR pch.is_read IS NULL)
                        GROUP BY u.id, u.username";
            } else {
                // 如果沒有 is_read 欄位，假設所有訊息都是未讀
                $sql = "SELECT u.username, COUNT(pch.id) as unread_count
                        FROM private_chat_history pch
                        LEFT JOIN user u ON pch.from_user = u.id
                        WHERE pch.to_user = ?
                        GROUP BY u.id, u.username";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$currentUserId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // 如果 from_user 和 to_user 是 VARCHAR 類型，直接使用 username
            $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'topics_good' 
                                AND TABLE_NAME = 'private_chat_history' 
                                AND COLUMN_NAME = 'is_read'");
            $hasIsRead = $stmt->rowCount() > 0;
            
            if ($hasIsRead) {
                $sql = "SELECT from_user as username, COUNT(*) as unread_count
                        FROM private_chat_history
                        WHERE to_user = ? AND (is_read = 0 OR is_read IS NULL)
                        GROUP BY from_user";
            } else {
                // 如果沒有 is_read 欄位，假設所有訊息都是未讀
                $sql = "SELECT from_user as username, COUNT(*) as unread_count
                        FROM private_chat_history
                        WHERE to_user = ?
                        GROUP BY from_user";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // 轉換為以 username 為 key 的陣列
    $unreadCounts = [];
    foreach ($results as $row) {
        $unreadCounts[$row['username']] = (int)$row['unread_count'];
    }
    
    // 調試日誌
    error_log("未讀計數查詢結果: " . json_encode($unreadCounts));
    
    echo json_encode([
        'success' => true,
        'unread_counts' => $unreadCounts,
        'debug' => [
            'username' => $username,
            'use_user_id' => $useUserId,
            'total_contacts' => count($unreadCounts)
        ]
    ]);
    
} catch(PDOException $e) {
    error_log("未讀計數查詢錯誤: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '查詢失敗: ' . $e->getMessage()
    ]);
}

