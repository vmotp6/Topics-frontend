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
            // 表存在，檢查是否有 group_id 列
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
        // 注意：group_chat_messages 表沒有 id 欄位，使用組合鍵作為唯一標識
        $sql = "SELECT CONCAT(gm.group_id, '_', gm.from_user, '_', UNIX_TIMESTAMP(gm.timestamp)) as id,
                       gm.group_id, 
                       u.username as from_user, 
                       gm.message, 
                       COALESCE(u.role, '用戶') as role, 
                       gm.timestamp,
                       u.name as from_user_name,
                       u.id as from_user_id
                FROM group_chat_messages gm 
                LEFT JOIN user u ON gm.from_user = u.id 
                WHERE gm.group_id = ?
                ORDER BY gm.timestamp ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
        
        // 使用 INT 類型的 from_user（對應 user.id）
        $sql = "INSERT INTO group_chat_messages (group_id, from_user, message, timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $userId, $message]);
        
        // 調試信息
        error_log("群組訊息發送成功 - group_id: $groupId, from_user_id: $userId, from_user: $fromUser");
        
        echo json_encode([
            'success' => true,
            'message' => '訊息發送成功',
            'id' => $groupId, // 注意：group_chat_messages 表沒有 id 欄位，所以返回 group_id
            'debug' => [
                'group_id' => $groupId,
                'from_user_id' => $userId,
                'from_user' => $fromUser,
                'role' => $role,
                'message_length' => strlen($message)
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
?>

