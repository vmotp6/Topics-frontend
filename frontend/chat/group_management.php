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
        // 獲取用戶參與的群組 - 使用 group_chat_members 表和 group_info 表 - 修復 collation 衝突
        $sql = "SELECT gm.group_id as id, 
                       COALESCE(gi.group_name, gm.group_id) as group_name, 
                       COUNT(gm2.id) as member_count,
                       COALESCE(gi.created_by, gm.member_username) as created_by,
                       gi.department
                FROM group_chat_members gm 
                JOIN group_chat_members gm2 ON gm.group_id COLLATE utf8mb4_unicode_ci = gm2.group_id COLLATE utf8mb4_unicode_ci
                LEFT JOIN group_info gi ON gm.group_id COLLATE utf8mb4_unicode_ci = gi.group_id COLLATE utf8mb4_unicode_ci
                WHERE gm.member_username = ? COLLATE utf8mb4_unicode_ci
                GROUP BY gm.group_id 
                ORDER BY gm.joined_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUsername]);
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
        // 先禁用外鍵檢查，確保表創建過程順利
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        try {
            // 檢查並創建/修復群組資訊表（必須先創建，因為其他表可能引用它）
            ensureGroupInfoTable($pdo);
            
            // 檢查並創建/修復群組成員表
            ensureGroupChatMembersTable($pdo);
            
            // 檢查並創建/修復群組訊息表
            ensureGroupChatMessagesTable($pdo);
        } finally {
            // 重新啟用外鍵檢查
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        // 開始事務
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // 創建群組 - 使用簡單的群組ID
        $groupId = time() . '_' . rand(1000, 9999); // 生成唯一的群組ID
        
        // 保存群組資訊
        $sql = "INSERT INTO group_info (group_id, group_name, created_by, department, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $groupName, $createdBy, $department]);
        
        // 添加創建者為成員
        $sql = "INSERT INTO group_chat_members (group_id, member_username, joined_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $createdBy]);
        
        // 添加其他成員
        foreach ($members as $member) {
            $sql = "INSERT INTO group_chat_members (group_id, member_username, joined_at) VALUES (?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $member['username']]);
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
    
    if (empty($groupId)) {
        echo json_encode(['success' => false, 'error' => '缺少群組ID參數']);
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
        
        // 檢查表結構，自動適配
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'group_chat_messages' 
                            AND COLUMN_NAME IN ('from_user', 'user_id')");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 先檢查是否有任何訊息（用於調試）
        $checkSql = "SELECT COUNT(*) as count FROM group_chat_messages WHERE group_id = ? COLLATE utf8mb4_unicode_ci";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$groupId]);
        $countResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $messageCount = $countResult['count'] ?? 0;
        
        if (in_array('user_id', $columns)) {
            // 使用正規化版本
            $sql = "SELECT gm.id, gm.group_id, u.username as from_user, gm.message, gm.role, gm.timestamp, u.role as user_role 
                    FROM group_chat_messages gm 
                    LEFT JOIN user u ON gm.user_id = u.id 
                    WHERE gm.group_id = ? COLLATE utf8mb4_unicode_ci
                    ORDER BY gm.timestamp ASC";
        } else {
            // 使用舊版本 - 修復 collation 衝突
            $sql = "SELECT gm.id, gm.group_id, gm.from_user, gm.message, gm.role, gm.timestamp, COALESCE(u.role, gm.role) as user_role 
                    FROM group_chat_messages gm 
                    LEFT JOIN user u ON gm.from_user COLLATE utf8mb4_unicode_ci = u.username COLLATE utf8mb4_unicode_ci
                    WHERE gm.group_id = ? COLLATE utf8mb4_unicode_ci
                    ORDER BY gm.timestamp ASC";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 調試信息
        error_log("獲取群組訊息 - group_id: $groupId, 訊息數量: " . count($messages) . ", 資料庫計數: $messageCount");
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'debug' => [
                'group_id' => $groupId,
                'message_count' => count($messages),
                'db_count' => $messageCount,
                'has_user_id_column' => in_array('user_id', $columns)
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
    
    if (empty($groupId) || empty($fromUser) || empty($message)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員 - 修復 collation 衝突
        $sql = "SELECT COUNT(*) FROM group_chat_members 
                WHERE group_id = ? COLLATE utf8mb4_unicode_ci 
                AND member_username = ? COLLATE utf8mb4_unicode_ci";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $fromUser]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 檢查表結構，自動適配
        $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                            WHERE TABLE_SCHEMA = 'topics_good' 
                            AND TABLE_NAME = 'group_chat_messages' 
                            AND COLUMN_NAME IN ('from_user', 'user_id')");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('user_id', $columns)) {
            // 使用正規化版本：先將 username 轉換為 user_id - 修復 collation 衝突
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ? COLLATE utf8mb4_unicode_ci");
            $stmt->execute([$fromUser]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $user ? $user['id'] : null;
            
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => '找不到指定的用戶']);
                return;
            }
            
            $sql = "INSERT INTO group_chat_messages (group_id, user_id, message, role, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $userId, $message, $role]);
        } else {
            // 使用舊版本
            $sql = "INSERT INTO group_chat_messages (group_id, from_user, message, role, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $fromUser, $message, $role]);
        }
        
        $messageId = $pdo->lastInsertId();
        
        // 調試信息
        error_log("群組訊息發送成功 - group_id: $groupId, from_user: $fromUser, message_id: $messageId");
        
        echo json_encode([
            'success' => true,
            'message' => '訊息發送成功',
            'id' => $messageId,
            'debug' => [
                'group_id' => $groupId,
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
    
    if (empty($groupId) || empty($newName) || empty($username)) {
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        // 檢查用戶是否為群組成員 - 修復 collation 衝突
        $sql = "SELECT COUNT(*) FROM group_chat_members 
                WHERE group_id = ? COLLATE utf8mb4_unicode_ci 
                AND member_username = ? COLLATE utf8mb4_unicode_ci";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $username]);
        $isMember = $stmt->fetchColumn() > 0;
        
        if (!$isMember) {
            echo json_encode(['success' => false, 'error' => '您不是該群組的成員']);
            return;
        }
        
        // 檢查群組資訊是否存在，如果不存在則創建 - 修復 collation 衝突
        $sql = "SELECT COUNT(*) FROM group_info WHERE group_id = ? COLLATE utf8mb4_unicode_ci";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // 更新現有群組資訊
            $sql = "UPDATE group_info SET group_name = ? WHERE group_id = ? COLLATE utf8mb4_unicode_ci";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newName, $groupId]);
        } else {
            // 創建新的群組資訊記錄
            $sql = "INSERT INTO group_info (group_id, group_name, created_by, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$groupId, $newName, $username]);
        }
        
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

