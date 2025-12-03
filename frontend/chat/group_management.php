<?php
// 載入 session 配置
require_once '../session_config.php';

// 啟動 session（如果尚未啟動）
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

// 檢查登入狀態並獲取當前用戶名（從 session，不信任前端傳遞的值）
$currentUsername = null;
$currentRole = null;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $currentUsername = $_SESSION['username'];
    $currentRole = $_SESSION['role'] ?? '用戶';
} else {
    // 如果未登入，返回錯誤
    echo json_encode([
        'success' => false,
        'error' => '未登入或 session 已過期，請重新登入'
    ]);
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
    
    // 獲取動作類型
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_my_groups':
            getMyGroups($pdo, $currentUsername);
            break;
        case 'create_group':
            createGroup($pdo, $currentUsername, $currentRole);
            break;
        case 'get_group_messages':
            getGroupMessages($pdo);
            break;
        case 'send_group_message':
            sendGroupMessage($pdo, $currentUsername, $currentRole);
            break;
        case 'update_group_name':
            updateGroupName($pdo, $currentUsername);
            break;
        case 'delete_group':
            deleteGroup($pdo, $currentUsername);
            break;
        case 'leave_group':
            leaveGroup($pdo, $currentUsername);
            break;
        case 'mark_group_as_read':
            markGroupAsRead($pdo, $currentUsername);
            break;
        case 'mark_message_as_read':
            markMessageAsRead($pdo, $currentUsername);
            break;
        case 'mark_messages_as_read':
            markMessagesAsRead($pdo, $currentUsername);
            break;
        case 'get_group_members':
            getGroupMembers($pdo, $currentUsername);
            break;
        case 'add_group_members':
            addGroupMembers($pdo, $currentUsername);
            break;
        case 'remove_group_member':
            removeGroupMember($pdo, $currentUsername);
            break;
        default:
            echo json_encode(['success' => false, 'error' => '無效的動作']);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫連接失敗: ' . $e->getMessage()
    ]);
}

// 確保 group_info 表存在且結構正確
function ensureGroupInfoTable($pdo) {
    try {
        // 檢查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_info'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // 表不存在，創建表
            $sql = "CREATE TABLE group_info (
                group_id VARCHAR(255) PRIMARY KEY,
                group_name VARCHAR(255) NOT NULL,
                created_by VARCHAR(255) NOT NULL,
                department VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
        } else {
            // 表存在，檢查是否有 group_id 列
            $stmt = $pdo->query("SHOW COLUMNS FROM group_info LIKE 'group_id'");
            if ($stmt->rowCount() == 0) {
                // 如果沒有 group_id 列，嘗試添加（但這可能失敗，因為是主鍵）
                // 在這種情況下，可能需要刪除表重新創建
                try {
                    $pdo->exec("ALTER TABLE group_info ADD COLUMN group_id VARCHAR(255) PRIMARY KEY FIRST");
                } catch (PDOException $e) {
                    // 如果添加失敗，先禁用外鍵檢查，然後刪除表重新創建
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    try {
                        // 先刪除可能引用此表的子表
                        $pdo->exec("DROP TABLE IF EXISTS group_chat_messages");
                        $pdo->exec("DROP TABLE IF EXISTS group_chat_members");
                        // 然後刪除主表
                        $pdo->exec("DROP TABLE IF EXISTS group_info");
                        $sql = "CREATE TABLE group_info (
                            group_id VARCHAR(255) PRIMARY KEY,
                            group_name VARCHAR(255) NOT NULL,
                            created_by VARCHAR(255) NOT NULL,
                            department VARCHAR(255),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )";
                        $pdo->exec($sql);
                    } finally {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("確保 group_info 表失敗: " . $e->getMessage());
        // 嘗試重新創建表
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            try {
                // 先刪除可能引用此表的子表
                $pdo->exec("DROP TABLE IF EXISTS group_chat_messages");
                $pdo->exec("DROP TABLE IF EXISTS group_chat_members");
                // 然後刪除主表
                $pdo->exec("DROP TABLE IF EXISTS group_info");
                $sql = "CREATE TABLE group_info (
                    group_id VARCHAR(255) PRIMARY KEY,
                    group_name VARCHAR(255) NOT NULL,
                    created_by VARCHAR(255) NOT NULL,
                    department VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $pdo->exec($sql);
            } finally {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        } catch (PDOException $e2) {
            throw $e2;
        }
    }
}

// 確保 group_chat_members 表存在且結構正確
function ensureGroupChatMembersTable($pdo) {
    try {
        // 檢查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_members'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // 表不存在，創建表
            $sql = "CREATE TABLE group_chat_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id VARCHAR(255) NOT NULL,
                member_username VARCHAR(255) NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_group_id (group_id),
                INDEX idx_member_username (member_username),
                UNIQUE KEY unique_group_member (group_id, member_username)
            )";
            $pdo->exec($sql);
        } else {
            // 表存在，檢查是否有 group_id 列
            $stmt = $pdo->query("SHOW COLUMNS FROM group_chat_members LIKE 'group_id'");
            if ($stmt->rowCount() == 0) {
                // 如果沒有 group_id 列，嘗試添加
                try {
                    $pdo->exec("ALTER TABLE group_chat_members ADD COLUMN group_id VARCHAR(255) NOT NULL AFTER id");
                    $pdo->exec("ALTER TABLE group_chat_members ADD INDEX idx_group_id (group_id)");
                    // 檢查是否已存在 unique_group_member 約束
                    $stmt = $pdo->query("SHOW INDEX FROM group_chat_members WHERE Key_name = 'unique_group_member'");
                    if ($stmt->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE group_chat_members ADD UNIQUE KEY unique_group_member (group_id, member_username)");
                    }
                } catch (PDOException $e) {
                    // 如果添加失敗，先禁用外鍵檢查，然後刪除表重新創建
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS group_chat_members");
                        $sql = "CREATE TABLE group_chat_members (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            group_id VARCHAR(255) NOT NULL,
                            member_username VARCHAR(255) NOT NULL,
                            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_group_id (group_id),
                            INDEX idx_member_username (member_username),
                            UNIQUE KEY unique_group_member (group_id, member_username)
                        )";
                        $pdo->exec($sql);
                    } finally {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("確保 group_chat_members 表失敗: " . $e->getMessage());
        // 嘗試重新創建表
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            try {
                $pdo->exec("DROP TABLE IF EXISTS group_chat_members");
                $sql = "CREATE TABLE group_chat_members (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id VARCHAR(255) NOT NULL,
                    member_username VARCHAR(255) NOT NULL,
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_group_id (group_id),
                    INDEX idx_member_username (member_username),
                    UNIQUE KEY unique_group_member (group_id, member_username)
                )";
                $pdo->exec($sql);
            } finally {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        } catch (PDOException $e2) {
            throw $e2;
        }
    }
}

// 確保 group_chat_messages 表存在且結構正確
function ensureGroupChatMessagesTable($pdo) {
    try {
        // 檢查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_messages'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // 表不存在，創建表
            $sql = "CREATE TABLE group_chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id VARCHAR(255) NOT NULL,
                from_user VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                role VARCHAR(50) DEFAULT '用戶',
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_group_id (group_id),
                INDEX idx_from_user (from_user),
                INDEX idx_timestamp (timestamp)
            )";
            $pdo->exec($sql);
        } else {
            // 表存在，檢查並添加必要的欄位
            $columns = $pdo->query("SHOW COLUMNS FROM group_chat_messages")->fetchAll(PDO::FETCH_COLUMN);
            
            // 檢查並添加 read_count 欄位
            if (!in_array('read_count', $columns)) {
                try {
                    $pdo->exec("ALTER TABLE group_chat_messages ADD COLUMN read_count INT DEFAULT 0 AFTER timestamp");
                } catch (PDOException $e) {
                    error_log("添加 read_count 欄位失敗: " . $e->getMessage());
                }
            }
            
            // 檢查並添加 read_user_ids 欄位
            if (!in_array('read_user_ids', $columns)) {
                try {
                    $pdo->exec("ALTER TABLE group_chat_messages ADD COLUMN read_user_ids TEXT DEFAULT NULL AFTER read_count");
                } catch (PDOException $e) {
                    error_log("添加 read_user_ids 欄位失敗: " . $e->getMessage());
                }
            }
            
            // 檢查並添加 total_members 欄位
            if (!in_array('total_members', $columns)) {
                try {
                    $pdo->exec("ALTER TABLE group_chat_messages ADD COLUMN total_members INT DEFAULT 0 AFTER read_user_ids");
                } catch (PDOException $e) {
                    error_log("添加 total_members 欄位失敗: " . $e->getMessage());
                }
            }
            
            // 檢查並添加 last_read_update 欄位
            if (!in_array('last_read_update', $columns)) {
                try {
                    $pdo->exec("ALTER TABLE group_chat_messages ADD COLUMN last_read_update TIMESTAMP NULL DEFAULT NULL AFTER total_members");
                } catch (PDOException $e) {
                    error_log("添加 last_read_update 欄位失敗: " . $e->getMessage());
                }
            }
            
            // 檢查是否有 group_id 列
            $stmt = $pdo->query("SHOW COLUMNS FROM group_chat_messages LIKE 'group_id'");
            if ($stmt->rowCount() == 0) {
                // 如果沒有 group_id 列，嘗試添加
                try {
                    $pdo->exec("ALTER TABLE group_chat_messages ADD COLUMN group_id VARCHAR(255) NOT NULL AFTER id");
                    $pdo->exec("ALTER TABLE group_chat_messages ADD INDEX idx_group_id (group_id)");
                } catch (PDOException $e) {
                    // 如果添加失敗，先禁用外鍵檢查，然後刪除表重新創建
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS group_chat_messages");
                        $sql = "CREATE TABLE group_chat_messages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            group_id VARCHAR(255) NOT NULL,
                            from_user VARCHAR(255) NOT NULL,
                            message TEXT NOT NULL,
                            role VARCHAR(50) DEFAULT '用戶',
                            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_group_id (group_id),
                            INDEX idx_from_user (from_user),
                            INDEX idx_timestamp (timestamp)
                        )";
                        $pdo->exec($sql);
                    } finally {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("確保 group_chat_messages 表失敗: " . $e->getMessage());
        // 嘗試重新創建表
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            try {
                $pdo->exec("DROP TABLE IF EXISTS group_chat_messages");
                $sql = "CREATE TABLE group_chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id VARCHAR(255) NOT NULL,
                    from_user VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    role VARCHAR(50) DEFAULT '用戶',
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_group_id (group_id),
                    INDEX idx_from_user (from_user),
                    INDEX idx_timestamp (timestamp)
                )";
                $pdo->exec($sql);
            } finally {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
        } catch (PDOException $e2) {
            throw $e2;
        }
    }
}

// 獲取我的群組
function getMyGroups($pdo, $currentUsername) {
    try {
        // 先獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'error' => '找不到當前用戶'
            ]);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查是否有 group_read_status 表
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_read_status'");
        $hasReadStatusTable = $stmt->rowCount() > 0;
        
        // 獲取用戶參與的群組 - 使用正確的 INT 類型 ID
        $sql = "SELECT gi.id as id, 
                       gi.group_name, 
                       COUNT(DISTINCT gm2.user) as member_count,
                       u_creator.username as created_by,
                       gi.created_at
                FROM group_chat_members gm 
                JOIN group_info gi ON gm.group_id = gi.id
                LEFT JOIN group_chat_members gm2 ON gm.group_id = gm2.group_id
                LEFT JOIN user u_creator ON gi.created_by = u_creator.id
                WHERE gm.user = ?
                GROUP BY gi.id, gi.group_name, u_creator.username, gi.created_at
                ORDER BY gi.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 為每個群組計算未讀數量
        foreach ($groups as &$group) {
            $groupId = $group['id'];
            $unreadCount = 0;
            
            if ($hasReadStatusTable) {
                // 如果有已讀狀態表，使用更精確的計算
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
                // 如果沒有已讀狀態表，使用簡單邏輯：計算該群組中不是當前用戶發送的訊息數量
                $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                      FROM group_chat_messages 
                                      WHERE group_id = ? AND from_user != ?");
                $stmt->execute([$groupId, $userId]);
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $unreadCount = (int)($result['unread_count'] ?? 0);
            $group['unread_count'] = $unreadCount;
        }
        unset($group);
        
        echo json_encode([
            'success' => true,
            'groups' => $groups
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '獲取群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 創建群組
function createGroup($pdo, $currentUsername, $currentRole) {
    $groupName = $_POST['group_name'] ?? '';
    $department = $_POST['department'] ?? '';
    $members = json_decode($_POST['members'] ?? '[]', true);
    
    // 使用 session 中的用戶名，不信任前端傳遞的值
    $createdBy = $currentUsername;
    
    if (empty($groupName) || empty($createdBy)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    $transactionStarted = false;
    
    try {
        // 先獲取創建者的用戶 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$createdBy]);
        $creator = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$creator) {
            echo json_encode(['success' => false, 'error' => '找不到創建者用戶']);
            return;
        }
        
        $creatorId = $creator['id'];
        
        // 開始事務
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // 注意：不調用 ensure 函數，因為用戶要求不改數據庫格式
        
        // 創建群組 - 使用 AUTO_INCREMENT 的 id
        $sql = "INSERT INTO group_info (group_name, created_by, created_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupName, $creatorId]);
        
        // 獲取新創建的群組 ID
        $groupId = $pdo->lastInsertId();
        
        // 添加創建者為成員
        $sql = "INSERT INTO group_chat_members (group_id, user, joined_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $creatorId]);
        
        // 添加其他成員 - 將 username 轉換為 user.id
        foreach ($members as $member) {
            if (isset($member['username'])) {
                // 先獲取成員的用戶 ID
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$member['username']]);
                $memberUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($memberUser && $memberUser['id'] != $creatorId) {
                    // 避免重複添加創建者
                    $sql = "INSERT IGNORE INTO group_chat_members (group_id, user, joined_at) VALUES (?, ?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$groupId, $memberUser['id']]);
                }
            }
        }
        
        $pdo->commit();
        $transactionStarted = false;
        
        echo json_encode([
            'success' => true,
            'message' => '群組創建成功',
            'group_id' => $groupId
        ]);
        
    } catch(PDOException $e) {
        // 只有在事務已開始時才回滾
        if ($transactionStarted) {
            try {
                $pdo->rollBack();
            } catch (PDOException $rollbackError) {
                // 忽略回滾錯誤，記錄即可
                error_log("回滾事務失敗: " . $rollbackError->getMessage());
            }
            $transactionStarted = false;
        }
        
        echo json_encode([
            'success' => false,
            'error' => '創建群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 獲取群組訊息
function getGroupMessages($pdo) {
    $groupId = $_GET['group_id'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID參數']);
        return;
    }
    
    try {
        // 檢查表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_chat_messages'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo json_encode([
                'success' => true,
                'messages' => [],
                'debug' => '表不存在'
            ]);
            return;
        }
        
        // 使用正確的 INT 類型 ID 查詢
        // from_user 是 INT 類型，外鍵到 user.id
        // 注意：group_chat_messages 表有 id 欄位
        $sql = "SELECT gm.id,
                       gm.group_id, 
                       u.username as from_user, 
                       gm.message, 
                       COALESCE(u.role, '用戶') as role, 
                       gm.timestamp,
                       u.name as from_user_name,
                       u.id as from_user_id,
                       COALESCE(gm.read_count, 0) as read_count,
                       gm.read_user_ids,
                       COALESCE(gm.total_members, 0) as total_members,
                       gm.last_read_update
                FROM group_chat_messages gm 
                LEFT JOIN user u ON gm.from_user = u.id 
                WHERE gm.group_id = ?
                ORDER BY gm.timestamp ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析 read_user_ids JSON 字符串為數組
        foreach ($messages as &$msg) {
            if (!empty($msg['read_user_ids'])) {
                $readUserIds = json_decode($msg['read_user_ids'], true);
                $msg['read_user_ids_array'] = is_array($readUserIds) ? $readUserIds : [];
            } else {
                $msg['read_user_ids_array'] = [];
            }
        }
        unset($msg);
        
        // 先檢查是否有任何訊息（用於調試）
        $checkSql = "SELECT COUNT(*) as count FROM group_chat_messages WHERE group_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$groupId]);
        $countResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $messageCount = $countResult['count'] ?? 0;
        
        // 調試信息
        error_log("獲取群組訊息 - group_id: $groupId, 訊息數量: " . count($messages) . ", 資料庫計數: $messageCount");
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'debug' => [
                'group_id' => $groupId,
                'message_count' => count($messages),
                'db_count' => $messageCount
            ]
        ]);
        
    } catch(PDOException $e) {
        error_log("獲取群組訊息失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '獲取群組訊息失敗: ' . $e->getMessage()
        ]);
    }
}

// 發送群組訊息
function sendGroupMessage($pdo, $currentUsername, $currentRole) {
    $groupId = $_POST['group_id'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // 使用 session 中的用戶名和角色，不信任前端傳遞的值
    $fromUser = $currentUsername;
    $role = $currentRole;
    
    if (empty($groupId) || !is_numeric($groupId) || empty($fromUser) || empty($message)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數或群組ID無效']);
        return;
    }
    
    try {
        // 先獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$fromUser]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到指定的用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查用戶是否為群組成員 - 使用 INT 類型的 ID
        $sql = "SELECT COUNT(*) FROM group_chat_members 
                WHERE group_id = ? 
                AND user = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 獲取群組總成員數
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM group_chat_members WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $memberCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalMembers = (int)($memberCount['total'] ?? 0);
        
        // 如果 total_members 為 0，至少應該是 1（發送者自己）
        if ($totalMembers === 0) {
            $totalMembers = 1;
        }
        
        // 使用 INT 類型的 from_user（對應 user.id）
        // 發送者自己已讀，所以 read_count 初始為 1，read_user_ids 包含發送者ID
        $readUserIds = json_encode([$userId]);
        $sql = "INSERT INTO group_chat_messages (group_id, from_user, message, timestamp, read_count, read_user_ids, total_members, last_read_update) 
                VALUES (?, ?, ?, NOW(), 1, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $userId, $message, $readUserIds, $totalMembers]);
        
        // 獲取插入的訊息ID
        $messageId = $pdo->lastInsertId();
        
        // 更新發送者的群組讀取狀態（發送者自己已讀，所以應該更新 last_read_at）
        try {
            // 檢查並創建 group_read_status 表（如果不存在）
            $stmt = $pdo->query("SHOW TABLES LIKE 'group_read_status'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
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
            }
            
            // 更新或插入最後讀取時間（發送者自己已讀，所以更新 last_read_at 為當前時間）
            $stmt = $pdo->prepare("INSERT INTO group_read_status (group_id, user_id, last_read_at) 
                                  VALUES (?, ?, NOW()) 
                                  ON DUPLICATE KEY UPDATE last_read_at = NOW()");
            $stmt->execute([$groupId, $userId]);
        } catch(PDOException $e) {
            // 如果更新讀取狀態失敗，記錄錯誤但不影響訊息發送
            error_log("更新發送者群組讀取狀態失敗: " . $e->getMessage());
        }
        
        // 調試信息
        error_log("群組訊息發送成功 - message_id: $messageId, group_id: $groupId, from_user_id: $userId, from_user: $fromUser, total_members: $totalMembers");
        
        echo json_encode([
            'success' => true,
            'message' => '訊息發送成功',
            'id' => $messageId,
            'message_id' => $messageId,
            'read_count' => 1,
            'total_members' => $totalMembers,
            'debug' => [
                'group_id' => $groupId,
                'from_user_id' => $userId,
                'from_user' => $fromUser,
                'role' => $role,
                'message_length' => strlen($message),
                'total_members' => $totalMembers
            ]
        ]);
        
    } catch(PDOException $e) {
        error_log("發送群組訊息失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '發送群組訊息失敗: ' . $e->getMessage()
        ]);
    }
}

// 更新群組名稱
function updateGroupName($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    $newName = $_POST['new_name'] ?? '';
    
    // 使用 session 中的用戶名，不信任前端傳遞的值
    $username = $currentUsername;
    
    if (empty($groupId) || !is_numeric($groupId) || empty($newName) || empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數或群組ID無效']);
        return;
    }
    
    try {
        // 先獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查用戶是否為群組成員 - 使用 INT 類型的 ID
        $sql = "SELECT COUNT(*) FROM group_chat_members 
                WHERE group_id = ? 
                AND user = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 更新群組名稱 - 使用 INT 類型的 id
        $sql = "UPDATE group_info SET group_name = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newName, $groupId]);
        
        echo json_encode([
            'success' => true,
            'message' => '群組名稱更新成功'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '更新群組名稱失敗: ' . $e->getMessage()
        ]);
    }
}

// 刪除群組
function deleteGroup($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查群組是否存在
        $stmt = $pdo->prepare("SELECT created_by FROM group_info WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            echo json_encode(['success' => false, 'error' => '找不到該群組']);
            return;
        }
        
        // 檢查用戶是否為群組創建者
        if ($group['created_by'] != $userId) {
            echo json_encode(['success' => false, 'error' => '只有群組創建者才能刪除群組']);
            return;
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 刪除群組訊息
            $stmt = $pdo->prepare("DELETE FROM group_chat_messages WHERE group_id = ?");
            $stmt->execute([$groupId]);
            
            // 刪除群組成員
            $stmt = $pdo->prepare("DELETE FROM group_chat_members WHERE group_id = ?");
            $stmt->execute([$groupId]);
            
            // 刪除群組讀取狀態（如果表存在）
            try {
                $stmt = $pdo->prepare("DELETE FROM group_read_status WHERE group_id = ?");
                $stmt->execute([$groupId]);
            } catch(PDOException $e) {
                // 如果表不存在，忽略錯誤
                error_log("刪除群組讀取狀態失敗（可能表不存在）: " . $e->getMessage());
            }
            
            // 刪除群組資訊
            $stmt = $pdo->prepare("DELETE FROM group_info WHERE id = ?");
            $stmt->execute([$groupId]);
            
            // 提交事務
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => '群組已成功刪除'
            ]);
            
        } catch(PDOException $e) {
            // 回滾事務
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '刪除群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 離開群組（非創建者）
function leaveGroup($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查群組是否存在
        $stmt = $pdo->prepare("SELECT created_by FROM group_info WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            echo json_encode(['success' => false, 'error' => '找不到該群組']);
            return;
        }
        
        // 檢查用戶是否為群組創建者（創建者不能離開，只能刪除）
        if ($group['created_by'] == $userId) {
            echo json_encode(['success' => false, 'error' => '群組創建者不能離開群組，請使用刪除功能']);
            return;
        }
        
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 刪除群組成員關係
            $stmt = $pdo->prepare("DELETE FROM group_chat_members WHERE group_id = ? AND user = ?");
            $stmt->execute([$groupId, $userId]);
            
            // 刪除該用戶的群組讀取狀態（如果表存在）
            try {
                $stmt = $pdo->prepare("DELETE FROM group_read_status WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$groupId, $userId]);
            } catch(PDOException $e) {
                // 如果表不存在，忽略錯誤
                error_log("刪除用戶群組讀取狀態失敗（可能表不存在）: " . $e->getMessage());
            }
            
            // 提交事務
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => '已成功離開群組'
            ]);
            
        } catch(PDOException $e) {
            // 回滾事務
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => '離開群組失敗: ' . $e->getMessage()
        ]);
    }
}

// 標記群組訊息為已讀
function markGroupAsRead($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 檢查並創建 group_read_status 表（如果不存在）
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_read_status'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
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
        }
        
        // 更新或插入最後讀取時間
        $stmt = $pdo->prepare("INSERT INTO group_read_status (group_id, user_id, last_read_at) 
                              VALUES (?, ?, NOW()) 
                              ON DUPLICATE KEY UPDATE last_read_at = NOW()");
        $stmt->execute([$groupId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => '群組已標記為已讀'
        ]);
        
    } catch(PDOException $e) {
        error_log("標記群組為已讀失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '標記群組為已讀失敗: ' . $e->getMessage()
        ]);
    }
}

// 標記單條群組訊息為已讀
function markMessageAsRead($pdo, $currentUsername) {
    $messageId = $_POST['message_id'] ?? '';
    
    if (empty($messageId) || !is_numeric($messageId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的訊息ID']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 獲取訊息資訊
        $stmt = $pdo->prepare("SELECT group_id, read_user_ids, total_members FROM group_chat_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            echo json_encode(['success' => false, 'error' => '找不到指定的訊息']);
            return;
        }
        
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$message['group_id'], $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 解析已讀用戶ID列表
        $readUserIds = [];
        if (!empty($message['read_user_ids'])) {
            $readUserIds = json_decode($message['read_user_ids'], true);
            if (!is_array($readUserIds)) {
                $readUserIds = [];
            }
        }
        
        // 如果用戶已經讀過，不重複計算
        if (!in_array($userId, $readUserIds)) {
            $readUserIds[] = $userId;
            $readCount = count($readUserIds);
            $readUserIdsJson = json_encode($readUserIds);
            
            // 更新訊息已讀狀態
            $stmt = $pdo->prepare("UPDATE group_chat_messages 
                                  SET read_count = ?, 
                                      read_user_ids = ?, 
                                      last_read_update = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$readCount, $readUserIdsJson, $messageId]);
            
            echo json_encode([
                'success' => true,
                'read_count' => $readCount,
                'total_members' => (int)($message['total_members'] ?? 0),
                'message' => '訊息已標記為已讀'
            ]);
        } else {
            // 用戶已經讀過，返回當前狀態
            echo json_encode([
                'success' => true,
                'read_count' => count($readUserIds),
                'total_members' => (int)($message['total_members'] ?? 0),
                'message' => '訊息已讀'
            ]);
        }
        
    } catch(PDOException $e) {
        error_log("標記訊息為已讀失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '標記訊息為已讀失敗: ' . $e->getMessage()
        ]);
    }
}

// 批量標記群組訊息為已讀
function markMessagesAsRead($pdo, $currentUsername) {
    $messageIdsJson = $_POST['message_ids'] ?? '[]';
    $messageIds = json_decode($messageIdsJson, true);
    
    if (empty($messageIds) || !is_array($messageIds)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的訊息ID列表']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        $updatedMessages = [];
        
        // 批量處理每條訊息
        foreach ($messageIds as $messageId) {
            if (!is_numeric($messageId)) continue;
            
            // 獲取訊息資訊
            $stmt = $pdo->prepare("SELECT group_id, read_user_ids, total_members FROM group_chat_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$message) continue;
            
            // 檢查用戶是否為群組成員
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
            $stmt->execute([$message['group_id'], $userId]);
            $isMember = $stmt->fetchColumn() > 0;
            
            if (!$isMember) continue;
            
            // 解析已讀用戶ID列表
            $readUserIds = [];
            if (!empty($message['read_user_ids'])) {
                $readUserIds = json_decode($message['read_user_ids'], true);
                if (!is_array($readUserIds)) {
                    $readUserIds = [];
                }
            }
            
            // 如果用戶已經讀過，跳過
            if (in_array($userId, $readUserIds)) {
                $updatedMessages[] = [
                    'message_id' => $messageId,
                    'read_count' => count($readUserIds),
                    'total_members' => (int)($message['total_members'] ?? 0)
                ];
                continue;
            }
            
            // 添加用戶到已讀列表
            $readUserIds[] = $userId;
            $readCount = count($readUserIds);
            $readUserIdsJson = json_encode($readUserIds);
            
            // 更新訊息已讀狀態
            $stmt = $pdo->prepare("UPDATE group_chat_messages 
                                  SET read_count = ?, 
                                      read_user_ids = ?, 
                                      last_read_update = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$readCount, $readUserIdsJson, $messageId]);
            
            $updatedMessages[] = [
                'message_id' => $messageId,
                'read_count' => $readCount,
                'total_members' => (int)($message['total_members'] ?? 0)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'updated_messages' => $updatedMessages,
            'message' => '訊息已標記為已讀'
        ]);
        
    } catch(PDOException $e) {
        error_log("批量標記訊息為已讀失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '批量標記訊息為已讀失敗: ' . $e->getMessage()
        ]);
    }
}

// 獲取群組成員列表
function getGroupMembers($pdo, $currentUsername) {
    $groupId = $_GET['group_id'] ?? $_POST['group_id'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 獲取群組創建者ID
        $stmt = $pdo->prepare("SELECT created_by FROM group_info WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        $creatorId = $group ? $group['created_by'] : null;
        
        // 獲取群組成員列表
        $sql = "SELECT u.id, u.username, u.name, u.role,
                       t.department,
                       CASE WHEN gi.created_by = u.id THEN 1 ELSE 0 END as is_creator
                FROM group_chat_members gcm
                JOIN user u ON gcm.user = u.id
                LEFT JOIN teacher t ON u.id = t.user_id
                LEFT JOIN group_info gi ON gcm.group_id = gi.id
                WHERE gcm.group_id = ?
                ORDER BY gcm.joined_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 格式化成員數據
        $formattedMembers = [];
        foreach ($members as $member) {
            $formattedMembers[] = [
                'id' => (int)($member['id'] ?? 0),
                'username' => (string)($member['username'] ?? ''),
                'name' => !empty($member['name']) ? (string)$member['name'] : (string)($member['username'] ?? ''),
                'role' => !empty($member['role']) ? (string)$member['role'] : '用戶',
                'department' => !empty($member['department']) ? (string)$member['department'] : '',
                'is_creator' => (bool)($member['is_creator'] ?? false)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'members' => $formattedMembers
        ]);
        
    } catch(PDOException $e) {
        error_log("獲取群組成員失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '獲取群組成員失敗: ' . $e->getMessage()
        ]);
    }
}

// 新增群組成員
function addGroupMembers($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    $membersJson = $_POST['members'] ?? '[]';
    
    if (empty($groupId) || !is_numeric($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的群組ID']);
        return;
    }
    
    $members = json_decode($membersJson, true);
    if (!is_array($members) || empty($members)) {
        echo json_encode(['success' => false, 'error' => '缺少有效的成員列表']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員，無法新增成員']);
            return;
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        $addedCount = 0;
        $errors = [];
        
        foreach ($members as $member) {
            $memberUsername = $member['username'] ?? '';
            if (empty($memberUsername)) continue;
            
            try {
                // 獲取成員的用戶 ID
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$memberUsername]);
                $memberUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$memberUser) {
                    $errors[] = "找不到用戶: $memberUsername";
                    continue;
                }
                
                $memberUserId = $memberUser['id'];
                
                // 檢查是否已經是群組成員
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
                $stmt->execute([$groupId, $memberUserId]);
                $alreadyMember = $stmt->fetchColumn() > 0;
                
                if ($alreadyMember) {
                    $errors[] = "$memberUsername 已經是群組成員";
                    continue;
                }
                
                // 添加成員
                $stmt = $pdo->prepare("INSERT INTO group_chat_members (group_id, user, joined_at) VALUES (?, ?, NOW())");
                $stmt->execute([$groupId, $memberUserId]);
                $addedCount++;
                
            } catch(PDOException $e) {
                $errors[] = "新增 $memberUsername 失敗: " . $e->getMessage();
                error_log("新增群組成員失敗: " . $e->getMessage());
            }
        }
        
        $pdo->commit();
        
        if ($addedCount > 0) {
            echo json_encode([
                'success' => true,
                'message' => "已成功新增 $addedCount 位成員",
                'added_count' => $addedCount,
                'errors' => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '沒有成功新增任何成員',
                'errors' => $errors
            ]);
        }
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("新增群組成員失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '新增群組成員失敗: ' . $e->getMessage()
        ]);
    }
}

// 移除群組成員（踢出）
function removeGroupMember($pdo, $currentUsername) {
    $groupId = $_POST['group_id'] ?? '';
    $memberUsername = $_POST['member_username'] ?? '';
    
    if (empty($groupId) || !is_numeric($groupId) || empty($memberUsername)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 獲取當前用戶的 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$currentUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => '找不到當前用戶']);
            return;
        }
        
        $userId = $user['id'];
        
        // 檢查當前用戶是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $userId]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員，無法移除成員']);
            return;
        }
        
        // 獲取要移除的成員的用戶 ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$memberUsername]);
        $memberUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$memberUser) {
            echo json_encode(['success' => false, 'error' => '找不到要移除的用戶']);
            return;
        }
        
        $memberUserId = $memberUser['id'];
        
        // 檢查要移除的成員是否為群組創建者（創建者不能被踢出）
        $stmt = $pdo->prepare("SELECT created_by FROM group_info WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($group && $group['created_by'] == $memberUserId) {
            echo json_encode(['success' => false, 'error' => '無法移除群組創建者']);
            return;
        }
        
        // 檢查要移除的成員是否為群組成員
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_chat_members WHERE group_id = ? AND user = ?");
        $stmt->execute([$groupId, $memberUserId]);
        $isGroupMember = $stmt->fetchColumn() > 0;
        
        if (!$isGroupMember) {
            echo json_encode(['success' => false, 'error' => '該用戶不是群組成員']);
            return;
        }
        
        // 開始事務
        $pdo->beginTransaction();
        
        try {
            // 移除群組成員
            $stmt = $pdo->prepare("DELETE FROM group_chat_members WHERE group_id = ? AND user = ?");
            $stmt->execute([$groupId, $memberUserId]);
            
            // 刪除該用戶的群組讀取狀態（如果表存在）
            try {
                $stmt = $pdo->prepare("DELETE FROM group_read_status WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$groupId, $memberUserId]);
            } catch(PDOException $e) {
                // 如果表不存在，忽略錯誤
                error_log("刪除用戶群組讀取狀態失敗（可能表不存在）: " . $e->getMessage());
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => '成員已成功移除'
            ]);
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        error_log("移除群組成員失敗: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => '移除群組成員失敗: ' . $e->getMessage()
        ]);
    }
}

