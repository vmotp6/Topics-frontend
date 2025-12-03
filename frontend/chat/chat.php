<?php
// 載入 session 配置
require_once '../session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 如果未登入，重定向到 Google 登入頁面
if (!$isLoggedIn) {
    header("Location: google_chat_integration.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// 載入資料庫配置
require_once '../config.php';

// 初始化聯絡人陣列，確保無論什麼角色都有此變數
$contacts = [];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 創建聯絡人表（如果不存在）- 所有角色都需要
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT '用戶ID',
        contact_user_id INT NOT NULL COMMENT '聯絡人用戶ID',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_contact (user_id, contact_user_id),
        INDEX idx_user_id (user_id),
        INDEX idx_contact_user_id (contact_user_id),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 檢查角色（支援代碼和中文名稱）
    $isStudent = ($role === 'STU' || $role === '學生' || $role === 'student');
    $isTeacher = ($role === 'TEA' || $role === '老師' || $role === 'teacher');
    $isStaff = ($role === 'STA' || $role === '學校行政人員' || $role === '行政人員');
    
    // 調試：記錄角色信息
    error_log("用戶角色檢查 - role: " . $role . ", isTeacher: " . ($isTeacher ? 'true' : 'false') . ", isStudent: " . ($isStudent ? 'true' : 'false') . ", isStaff: " . ($isStaff ? 'true' : 'false'));
    
    // 獲取當前用戶ID（所有角色都需要）
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentUserId = $currentUser ? $currentUser['id'] : null;
    
    // ========== 自動同步聯絡人到 user_contacts 表 ==========
    if ($currentUserId) {
        try {
            // TEA 角色：自動同步其他 TEA 用戶到 user_contacts
            if ($isTeacher) {
                error_log("TEA 角色登入，開始自動同步其他 TEA 用戶到 user_contacts 表");
                
                // 查詢所有其他 TEA 用戶
                $stmt = $pdo->prepare("SELECT id FROM user WHERE (role = 'TEA' OR role = '老師') AND id != ?");
                $stmt->execute([$currentUserId]);
                $teaUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                error_log("查詢到的其他 TEA 用戶 ID 列表: " . json_encode($teaUsers));
                
                if (!empty($teaUsers)) {
                    // 檢查哪些已經存在於 user_contacts
                    $placeholders = implode(',', array_fill(0, count($teaUsers), '?'));
                    $checkStmt = $pdo->prepare("SELECT contact_user_id FROM user_contacts WHERE user_id = ? AND contact_user_id IN ($placeholders)");
                    $checkStmt->execute(array_merge([$currentUserId], $teaUsers));
                    $existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    error_log("已存在於 user_contacts 的 TEA 用戶 ID: " . json_encode($existingIds));
                    
                    // 找出需要新增的
                    $newIds = array_diff($teaUsers, $existingIds);
                    error_log("需要新增的 TEA 用戶 ID: " . json_encode($newIds));
                    
                    if (!empty($newIds)) {
                        // 批量插入
                        $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_contacts (user_id, contact_user_id) VALUES (?, ?)");
                        $insertedCount = 0;
                        foreach ($newIds as $teaId) {
                            $result = $insertStmt->execute([$currentUserId, $teaId]);
                            if ($insertStmt->rowCount() > 0) {
                                $insertedCount++;
                                error_log("成功插入聯絡人: user_id=" . $currentUserId . ", contact_user_id=" . $teaId);
                            } else {
                                error_log("插入失敗或已存在: user_id=" . $currentUserId . ", contact_user_id=" . $teaId);
                            }
                        }
                        error_log("✅ TEA 角色：已自動同步 " . $insertedCount . " 位其他 TEA 用戶到 user_contacts 表");
                    } else {
                        error_log("✅ TEA 角色：所有其他 TEA 用戶已存在於 user_contacts 表");
                    }
                } else {
                    error_log("⚠️ TEA 角色：沒有找到其他 TEA 用戶");
                }
            }
            
            // STU 角色：自動同步所有 TEA 用戶到 user_contacts
            if ($isStudent) {
                error_log("STU 角色登入，開始自動同步所有 TEA 用戶到 user_contacts 表");
                
                // 查詢所有 TEA 用戶
                $stmt = $pdo->prepare("SELECT id FROM user WHERE role = 'TEA' OR role = '老師'");
                $stmt->execute();
                $teaUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($teaUsers)) {
                    // 檢查哪些已經存在於 user_contacts
                    $placeholders = implode(',', array_fill(0, count($teaUsers), '?'));
                    $checkStmt = $pdo->prepare("SELECT contact_user_id FROM user_contacts WHERE user_id = ? AND contact_user_id IN ($placeholders)");
                    $checkStmt->execute(array_merge([$currentUserId], $teaUsers));
                    $existingIds = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // 找出需要新增的
                    $newIds = array_diff($teaUsers, $existingIds);
                    
                    if (!empty($newIds)) {
                        // 批量插入
                        $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_contacts (user_id, contact_user_id) VALUES (?, ?)");
                        $insertedCount = 0;
                        foreach ($newIds as $teaId) {
                            $insertStmt->execute([$currentUserId, $teaId]);
                            if ($insertStmt->rowCount() > 0) {
                                $insertedCount++;
                            }
                        }
                        error_log("✅ STU 角色：已自動同步 " . $insertedCount . " 位 TEA 用戶到 user_contacts 表");
                    } else {
                        error_log("✅ STU 角色：所有 TEA 用戶已存在於 user_contacts 表");
                    }
                } else {
                    error_log("⚠️ STU 角色：沒有找到 TEA 用戶");
                }
            }
        } catch (PDOException $e) {
            error_log("❌ 自動同步聯絡人失敗: " . $e->getMessage());
        }
    }
    // ========== 自動同步結束 ==========
    
    // 根據角色獲取不同的資料
    if ($isStudent) {
        // 學生：從 user_contacts 表載入所有聯絡人（包括 TEA 和其他角色）
        $contacts = [];
        
        if ($currentUserId) {
            // 從 user_contacts 表載入所有聯絡人（使用 contact_user_id）
            // 查詢邏輯：uc.user_id = 當前用戶ID，uc.contact_user_id = 聯絡人ID
            // 重要：載入所有角色，不僅僅是 TEA，因為用戶可能新增了其他角色的聯絡人
            $stmt = $pdo->prepare("SELECT DISTINCT 
                            uc.contact_user_id as user_id,  -- 使用 contact_user_id 作為聯絡人ID
                            u.id as contact_user_table_id,  -- 聯絡人在 user 表中的實際 ID
                            COALESCE(u.name, u.username, '未知用戶') as name,  -- 從 user 表獲取 name
                            COALESCE(s.department, t.department, '未設定') as department,
                            u.username,
                            u.profile_picture,
                            CASE 
                                WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                                WHEN u.role = 'TEA' OR u.role = '老師' THEN '老師'
                                ELSE '其他'
                            END as contact_type,
                            COALESCE(s.grade, '未設定') as grade,
                            COALESCE(s.class_name, '未設定') as class_name
                     FROM user_contacts uc
                     LEFT JOIN user u ON uc.contact_user_id = u.id  -- 使用 LEFT JOIN 確保所有 contact_user_id 都被載入
                     LEFT JOIN student s ON uc.contact_user_id = s.user_id
                     LEFT JOIN teacher t ON uc.contact_user_id = t.user_id
                     WHERE uc.user_id = ?  -- 當前用戶的 ID
                     ORDER BY 
                         CASE WHEN u.role = 'TEA' OR u.role = '老師' THEN 0 ELSE 1 END,
                         COALESCE(u.name, u.username, '未知用戶'), 
                         u.username");
            $stmt->execute([$currentUserId]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("STU 載入邏輯確認：使用 uc.contact_user_id = u.id 來載入聯絡人，當前用戶ID: " . $currentUserId);
            
            error_log("STU 角色：從 user_contacts 表載入 " . count($contacts) . " 位聯絡人（包括所有角色）");
            
            // 調試：顯示載入的聯絡人詳情
            if (!empty($contacts)) {
                error_log("STU 載入的聯絡人詳情: " . json_encode($contacts, JSON_UNESCAPED_UNICODE));
            } else {
                error_log("⚠️ STU 角色：沒有載入到任何聯絡人，請檢查 user_contacts 表中是否有記錄");
                // 調試：檢查 user_contacts 表中是否有記錄
                $debugStmt = $pdo->prepare("SELECT * FROM user_contacts WHERE user_id = ?");
                $debugStmt->execute([$currentUserId]);
                $debugContacts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("user_contacts 表中的記錄: " . json_encode($debugContacts, JSON_UNESCAPED_UNICODE));
            }
        }
        
        // 如果沒有聯絡人，顯示空陣列
        if (empty($contacts)) {
            $contacts = [];
        }
        
        // 獲取有未讀消息的老師（補充到列表中）
        try {
            // 檢查表結構
            $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'topics_good' 
                                AND TABLE_NAME = 'private_chat_history' 
                                AND COLUMN_NAME IN ('from_user_id', 'to_user_id', 'is_read')");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
            
            // 獲取當前用戶ID
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentUserId = $currentUser ? $currentUser['id'] : null;
            
            if ($currentUserId) {
                if ($useUserId) {
                    $sql = "SELECT DISTINCT u.id as user_id,
                                COALESCE(u.name, u.username) as name,
                                COALESCE(t.department, '未設定') as department,
                                u.username,
                                u.profile_picture,
                                '老師' as contact_type
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user_id = u.id
                         LEFT JOIN teacher t ON u.id = t.user_id
                         WHERE pch.to_user_id = ?
                           AND u.id != ?
                           AND (u.role = 'TEA' OR u.role = '老師')
                         GROUP BY u.id, u.username";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$currentUserId, $currentUserId]);
                } else {
                    $sql = "SELECT DISTINCT u.id as user_id,
                                COALESCE(u.name, u.username) as name,
                                COALESCE(t.department, '未設定') as department,
                                u.username,
                                u.profile_picture,
                                '老師' as contact_type
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user = u.username
                         LEFT JOIN teacher t ON u.id = t.user_id
                         WHERE pch.to_user = ?
                           AND u.username != ?
                           AND (u.role = 'TEA' OR u.role = '老師')
                         GROUP BY u.id, u.username";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $username]);
                }
                
                if ($stmt) {
                    $unreadContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 合併到聯絡人列表（避免重複）
                    $existingUsernames = array_column($contacts, 'username');
                    foreach ($unreadContacts as $contact) {
                        if (!in_array($contact['username'], $existingUsernames)) {
                            $contacts[] = $contact;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("獲取未讀消息聯絡人失敗: " . $e->getMessage());
        }
        
        // 注意：STU 角色的聯絡人已從 user_contacts 表載入，不需要再次載入
    } elseif ($isTeacher || $isStaff) {
        // 老師和學校行政人員：從 user_contacts 表載入聯絡人
        $contacts = [];
        
        error_log("=== 進入老師/行政角色區塊 ===");
        error_log("角色: " . $role . ", isTeacher: " . ($isTeacher ? 'true' : 'false') . ", isStaff: " . ($isStaff ? 'true' : 'false'));
        error_log("當前用戶ID（外層）: " . ($currentUserId ?? 'null'));
        
        try {
            // 創建聯絡人表（如果不存在）
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL COMMENT '用戶ID',
                contact_user_id INT NOT NULL COMMENT '聯絡人用戶ID',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_contact (user_id, contact_user_id),
                INDEX idx_user_id (user_id),
                INDEX idx_contact_user_id (contact_user_id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
                FOREIGN KEY (contact_user_id) REFERENCES user(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // 使用外層已獲取的 $currentUserId（避免重複查詢導致不一致）
            // 如果外層沒有，才重新查詢
            if (!isset($currentUserId) || !$currentUserId) {
                $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$username]);
                $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentUserId = $currentUser ? $currentUser['id'] : null;
                error_log("重新查詢 currentUserId: " . $currentUserId);
            } else {
                error_log("使用外層 currentUserId: " . $currentUserId);
            }
            
            error_log("TEA/STA 角色區塊 - 最終使用的 currentUserId: " . $currentUserId . ", username: " . $username);
            
            if ($currentUserId) {
                // TEA/STA 角色：從 user_contacts 表載入聯絡人
                error_log("開始載入聯絡人 - 角色: " . $role . ", isTeacher: " . ($isTeacher ? 'true' : 'false') . ", currentUserId: " . $currentUserId);
                
                // 先檢查 user_contacts 表中有多少記錄
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_contacts WHERE user_id = ?");
                $checkStmt->execute([$currentUserId]);
                $contactCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
                error_log("user_contacts 表中當前用戶的聯絡人記錄數: " . $contactCount);
                
                // 從 user_contacts 表載入所有聯絡人（使用 contact_user_id）
                // 查詢邏輯：uc.user_id = 當前用戶ID，uc.contact_user_id = 聯絡人ID
                // 重要：使用 LEFT JOIN 確保即使 user 表中沒有對應記錄也能載入 contact_user_id
                $stmt = $pdo->prepare("SELECT DISTINCT 
                            uc.contact_user_id as user_id,  -- 使用 contact_user_id 作為聯絡人ID
                            u.id as contact_user_table_id,  -- 聯絡人在 user 表中的實際 ID（可能為 NULL）
                            COALESCE(u.name, u.username, '未知用戶') as name,  -- 從 user 表獲取 name（teacher 和 student 表都沒有 name）
                            COALESCE(s.department, t.department, '未設定') as department,
                            COALESCE(u.username, 'unknown_' . uc.contact_user_id) as username,
                            u.profile_picture,
                            CASE 
                                WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                                WHEN u.role = 'TEA' OR u.role = '老師' THEN '老師'
                                ELSE '其他'
                            END as contact_type,
                            COALESCE(s.grade, '未設定') as grade,
                            COALESCE(s.class_name, '未設定') as class_name
                     FROM user_contacts uc
                     LEFT JOIN user u ON uc.contact_user_id = u.id  -- 使用 LEFT JOIN 確保所有 contact_user_id 都被載入
                     LEFT JOIN student s ON uc.contact_user_id = s.user_id
                     LEFT JOIN teacher t ON uc.contact_user_id = t.user_id
                     WHERE uc.user_id = ?  -- 當前用戶的 ID
                     ORDER BY 
                         CASE WHEN u.role = 'TEA' OR u.role = '老師' THEN 0 ELSE 1 END,
                         COALESCE(u.name, u.username, '未知用戶'), 
                         COALESCE(u.username, 'unknown_' . uc.contact_user_id)");
                $stmt->execute([$currentUserId]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("=== 查詢執行完成 ===");
                error_log("查詢找到 " . count($contacts) . " 位聯絡人");
                error_log("當前用戶ID: " . $currentUserId);
                error_log("載入邏輯確認：使用 uc.contact_user_id = u.id 來載入聯絡人");
                
                // 詳細調試：檢查資料庫中的實際記錄
                $debugStmt = $pdo->prepare("SELECT uc.id, uc.user_id, uc.contact_user_id, u.id as user_table_id, u.username, u.name, u.role 
                                           FROM user_contacts uc 
                                           LEFT JOIN user u ON uc.contact_user_id = u.id 
                                           WHERE uc.user_id = ?");
                $debugStmt->execute([$currentUserId]);
                $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("調試：user_contacts 表中的所有記錄（user_id=" . $currentUserId . "）: " . json_encode($debugResults, JSON_UNESCAPED_UNICODE));
                
                // 如果查詢結果為空，立即使用備用邏輯
                if (count($contacts) === 0 && count($debugResults) > 0) {
                    error_log("❌ JOIN 查詢失敗，使用備用邏輯構建聯絡人列表");
                    $contacts = []; // 重置陣列
                    
                    foreach ($debugResults as $debugResult) {
                        $contactUserId = $debugResult['contact_user_id'];
                        
                        if ($debugResult['user_table_id']) {
                            // user 表中有對應記錄
                            $userInfo = [
                                'id' => $debugResult['user_table_id'],
                                'username' => $debugResult['username'],
                                'name' => $debugResult['name'],
                                'role' => $debugResult['role']
                            ];
                            
                            // 查詢 student 或 teacher 資訊
                            $studentStmt = $pdo->prepare("SELECT department, grade, class_name FROM student WHERE user_id = ?");
                            $studentStmt->execute([$contactUserId]);
                            $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);
                            
                            $teacherStmt = $pdo->prepare("SELECT department FROM teacher WHERE user_id = ?");
                            $teacherStmt->execute([$contactUserId]);
                            $teacherInfo = $teacherStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // 查詢 profile_picture
                            $profileStmt = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
                            $profileStmt->execute([$contactUserId]);
                            $profileInfo = $profileStmt->fetch(PDO::FETCH_ASSOC);
                            
                            $contacts[] = [
                                'user_id' => $contactUserId,
                                'name' => $userInfo['name'] ?: $userInfo['username'],
                                'username' => $userInfo['username'],
                                'department' => $studentInfo['department'] ?? $teacherInfo['department'] ?? '未設定',
                                'profile_picture' => $profileInfo['profile_picture'] ?? null,
                                'contact_type' => ($userInfo['role'] === 'TEA' || $userInfo['role'] === '老師') ? '老師' : 
                                                 (($userInfo['role'] === 'STU' || $userInfo['role'] === '學生') ? '學生' : '其他'),
                                'grade' => $studentInfo['grade'] ?? '未設定',
                                'class_name' => $studentInfo['class_name'] ?? '未設定'
                            ];
                        } else {
                            // user 表中沒有對應記錄，至少顯示 contact_user_id
                            $contacts[] = [
                                'user_id' => $contactUserId,
                                'name' => '未知用戶 (ID: ' . $contactUserId . ')',
                                'username' => 'unknown_' . $contactUserId,
                                'department' => '未設定',
                                'profile_picture' => null,
                                'contact_type' => '其他',
                                'grade' => '未設定',
                                'class_name' => '未設定'
                            ];
                        }
                    }
                    error_log("✅ 使用備用邏輯構建了 " . count($contacts) . " 位聯絡人");
                }
                
                error_log("TEA/STA 角色：最終載入 " . count($contacts) . " 位聯絡人");
                
                if (count($contacts) > 0) {
                    error_log("✅ 載入的聯絡人詳情: " . json_encode($contacts, JSON_UNESCAPED_UNICODE));
                } else {
                    error_log("❌ 警告：從 user_contacts 表載入的聯絡人為空，但記錄數為 " . $contactCount);
                    if ($contactCount > 0) {
                        error_log("❌ 問題：資料庫中有 " . $contactCount . " 筆記錄，但查詢結果為空！");
                        error_log("❌ 可能原因：");
                        error_log("   1. JOIN 條件失敗（contact_user_id 在 user 表中不存在）");
                        error_log("   2. 查詢語法錯誤");
                        error_log("   3. 變數作用域問題");
                        
                        // 嘗試直接查詢，不使用 JOIN
                        $directStmt = $pdo->prepare("SELECT uc.contact_user_id as user_id FROM user_contacts uc WHERE uc.user_id = ?");
                        $directStmt->execute([$currentUserId]);
                        $directResults = $directStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("直接查詢 user_contacts（不使用 JOIN）結果: " . json_encode($directResults, JSON_UNESCAPED_UNICODE));
                        
                        // 如果直接查詢有結果，但 JOIN 查詢沒有，說明 JOIN 失敗
                        if (count($directResults) > 0 && count($contacts) === 0) {
                            error_log("❌ 確認：直接查詢有 " . count($directResults) . " 筆記錄，但 JOIN 查詢為空，說明 JOIN 失敗");
                            error_log("❌ 嘗試使用直接查詢結果構建聯絡人列表");
                            
                            // 使用直接查詢結果構建聯絡人
                            foreach ($directResults as $directResult) {
                                $contactUserId = $directResult['user_id'];
                                // 查詢該用戶的詳細資訊
                                $userStmt = $pdo->prepare("SELECT u.id, u.username, u.name, u.role, u.profile_picture FROM user u WHERE u.id = ?");
                                $userStmt->execute([$contactUserId]);
                                $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($userInfo) {
                                    // 查詢 student 或 teacher 資訊
                                    $studentStmt = $pdo->prepare("SELECT department, grade, class_name FROM student WHERE user_id = ?");
                                    $studentStmt->execute([$contactUserId]);
                                    $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    $teacherStmt = $pdo->prepare("SELECT department FROM teacher WHERE user_id = ?");
                                    $teacherStmt->execute([$contactUserId]);
                                    $teacherInfo = $teacherStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    $contacts[] = [
                                        'user_id' => $contactUserId,
                                        'name' => $userInfo['name'] ?: $userInfo['username'],
                                        'username' => $userInfo['username'],
                                        'department' => $studentInfo['department'] ?? $teacherInfo['department'] ?? '未設定',
                                        'profile_picture' => $userInfo['profile_picture'],
                                        'contact_type' => ($userInfo['role'] === 'TEA' || $userInfo['role'] === '老師') ? '老師' : 
                                                         (($userInfo['role'] === 'STU' || $userInfo['role'] === '學生') ? '學生' : '其他'),
                                        'grade' => $studentInfo['grade'] ?? '未設定',
                                        'class_name' => $studentInfo['class_name'] ?? '未設定'
                                    ];
                                } else {
                                    // 如果 user 表中沒有，至少顯示 contact_user_id
                                    $contacts[] = [
                                        'user_id' => $contactUserId,
                                        'name' => '未知用戶 (ID: ' . $contactUserId . ')',
                                        'username' => 'unknown_' . $contactUserId,
                                        'department' => '未設定',
                                        'profile_picture' => null,
                                        'contact_type' => '其他',
                                        'grade' => '未設定',
                                        'class_name' => '未設定'
                                    ];
                                }
                            }
                            error_log("✅ 使用直接查詢結果構建了 " . count($contacts) . " 位聯絡人");
                        }
                    }
                }
                
                // 獲取有傳過消息給當前用戶的人（用於排序和標記）
                $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                    WHERE TABLE_SCHEMA = 'topics_good' 
                                    AND TABLE_NAME = 'private_chat_history' 
                                    AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
                
                $messagedUsers = [];
                if ($useUserId) {
                    $sql = "SELECT DISTINCT u.id as user_id,
                                MAX(pch.timestamp) as last_message_time
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user_id = u.id
                         WHERE pch.to_user_id = ?
                           AND u.id != ?
                         GROUP BY u.id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$currentUserId, $currentUserId]);
                } else {
                    $sql = "SELECT DISTINCT u.id as user_id,
                                MAX(pch.timestamp) as last_message_time
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user = u.username
                         WHERE pch.to_user = ?
                           AND u.username != ?
                         GROUP BY u.id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $username]);
                }
                
                if ($stmt) {
                    $messagedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    // 轉換為以 user_id 為 key 的陣列
                    $messagedUsersMap = [];
                    foreach ($messagedUsers as $mu) {
                        $messagedUsersMap[$mu['user_id']] = $mu['last_message_time'];
                    }
                    
                    // 為聯絡人添加最後訊息時間（用於排序）
                    foreach ($contacts as &$contact) {
                        $contact['last_message_time'] = $messagedUsersMap[$contact['user_id']] ?? null;
                    }
                    unset($contact);
                    
                    // 排序：有訊息的在前面（按時間降序），然後是其他聯絡人（按姓名）
                    // TEA 聯絡人優先顯示
                    usort($contacts, function($a, $b) {
                        $aIsTea = ($a['contact_type'] ?? '') === '老師';
                        $bIsTea = ($b['contact_type'] ?? '') === '老師';
                        
                        // TEA 優先
                        if ($aIsTea && !$bIsTea) {
                            return -1;
                        } elseif (!$aIsTea && $bIsTea) {
                            return 1;
                        }
                        
                        // 有訊息的在前面
                        if ($a['last_message_time'] && $b['last_message_time']) {
                            return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
                        } elseif ($a['last_message_time']) {
                            return -1;
                        } elseif ($b['last_message_time']) {
                            return 1;
                        } else {
                            return strcmp($a['name'] ?? '', $b['name'] ?? '');
                        }
                    });
                }
                
                // 注意：聯絡人已從 user_contacts 表載入，不再從 user 表載入其他用戶
                
                // 注意：聯絡人已從 user_contacts 表載入，不需要再次載入
            } else {
                error_log("currentUserId 為空，無法載入聯絡人");
            }
        } catch (PDOException $e) {
            error_log("❌ 獲取聯絡人失敗（老師/行政）: " . $e->getMessage());
            error_log("❌ 錯誤堆疊: " . $e->getTraceAsString());
            $contacts = []; // 確保 $contacts 是陣列
        }
        
        // 調試：記錄最終聯絡人數量（在輸出前）
        error_log("=== TEA/STA 角色區塊結束 ===");
        error_log("最終聯絡人數量（老師/行政）: " . count($contacts));
        if (count($contacts) > 0) {
            error_log("聯絡人 user_id 列表: " . json_encode(array_column($contacts, 'user_id'), JSON_UNESCAPED_UNICODE));
        } else {
            error_log("⚠️ 警告：聯絡人數量為 0！角色: " . $role . ", isTeacher: " . ($isTeacher ? 'true' : 'false') . ", username: " . $username);
        }
    } else {
        // 其他角色：只顯示傳過消息給他們的人
        $contacts = [];
        
        try {
            // 獲取當前用戶ID
            $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentUserId = $currentUser ? $currentUser['id'] : null;
            
            if ($currentUserId) {
                // 檢查表結構，判斷使用哪種查詢方式
                $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                    WHERE TABLE_SCHEMA = 'topics_good' 
                                    AND TABLE_NAME = 'private_chat_history' 
                                    AND COLUMN_NAME IN ('from_user', 'to_user', 'from_user_id', 'to_user_id')");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
                
                if ($useUserId) {
                    // 使用正規化版本（user_id）- 獲取所有傳過消息給當前用戶的人
                    $sql = "SELECT DISTINCT u.id as user_id,
                                COALESCE(u.name, u.username, '未知用戶') as name,  -- 從 user 表獲取 name（teacher 和 student 表都沒有 name）
                                COALESCE(s.department, t.department, '未設定') as department,
                                u.username,
                                u.profile_picture,
                                CASE 
                                    WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                                    WHEN u.role = 'TEA' OR u.role = '老師' THEN '老師'
                                    ELSE '其他'
                                END as contact_type,
                                COALESCE(s.grade, '未設定') as grade,
                                COALESCE(s.class_name, '未設定') as class_name
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user_id = u.id
                         LEFT JOIN student s ON u.id = s.user_id
                         LEFT JOIN teacher t ON u.id = t.user_id
                         WHERE pch.to_user_id = ?
                           AND u.id != ?
                         GROUP BY u.id, u.username
                         ORDER BY MAX(pch.timestamp) DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$currentUserId, $currentUserId]);
                } else {
                    // 使用舊版本（username）- 獲取所有傳過消息給當前用戶的人
                    $sql = "SELECT DISTINCT u.id as user_id,
                                COALESCE(u.name, u.username, '未知用戶') as name,  -- 從 user 表獲取 name（teacher 和 student 表都沒有 name）
                                COALESCE(s.department, t.department, '未設定') as department,
                                u.username,
                                u.profile_picture,
                                CASE 
                                    WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                                    WHEN u.role = 'TEA' OR u.role = '老師' THEN '老師'
                                    ELSE '其他'
                                END as contact_type,
                                COALESCE(s.grade, '未設定') as grade,
                                COALESCE(s.class_name, '未設定') as class_name
                         FROM private_chat_history pch
                         JOIN user u ON pch.from_user = u.username
                         LEFT JOIN student s ON u.id = s.user_id
                         LEFT JOIN teacher t ON u.id = t.user_id
                         WHERE pch.to_user = ?
                           AND u.username != ?
                         GROUP BY u.id, u.username
                         ORDER BY MAX(pch.timestamp) DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $username]);
                }
                
                if ($stmt) {
                    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            // 載入已保存的聯絡人（其他角色）- 從 user_contacts 表
            if ($currentUserId) {
                try {
                    $stmt = $pdo->prepare("SELECT DISTINCT u.id as user_id,
                                COALESCE(u.name, u.username, '未知用戶') as name,  -- 從 user 表獲取 name（teacher 和 student 表都沒有 name）
                                COALESCE(s.department, t.department, '未設定') as department,
                                u.username,
                                u.profile_picture,
                                CASE 
                                    WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                                    WHEN u.role = 'TEA' OR u.role = '老師' THEN '老師'
                                    ELSE '其他'
                                END as contact_type,
                                COALESCE(s.grade, '未設定') as grade,
                                COALESCE(s.class_name, '未設定') as class_name
                         FROM user_contacts uc
                         JOIN user u ON uc.contact_user_id = u.id
                         LEFT JOIN student s ON u.id = s.user_id
                         LEFT JOIN teacher t ON u.id = t.user_id
                         WHERE uc.user_id = ?");
                    $stmt->execute([$currentUserId]);
                    $savedContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 合併已保存的聯絡人（避免重複）
                    $existingUserIds = array_column($contacts, 'user_id');
                    foreach ($savedContacts as $savedContact) {
                        if (!in_array($savedContact['user_id'], $existingUserIds)) {
                            $contacts[] = $savedContact;
                            $existingUserIds[] = $savedContact['user_id'];
                        }
                    }
                    error_log("載入已保存聯絡人（其他角色），找到 " . count($savedContacts) . " 位，合併後總數: " . count($contacts));
                } catch (PDOException $e) {
                    error_log("載入已保存聯絡人失敗（其他角色）: " . $e->getMessage());
                }
                
                // 如果有訊息記錄但未保存的聯絡人，自動保存到 user_contacts 表
                if (!empty($contacts)) {
                    try {
                        // 獲取所有已保存的聯絡人ID
                        $stmt = $pdo->prepare("SELECT contact_user_id FROM user_contacts WHERE user_id = ?");
                        $stmt->execute([$currentUserId]);
                        $savedContactIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // 為每個有訊息記錄但未保存的聯絡人創建記錄
                        $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_contacts (user_id, contact_user_id) VALUES (?, ?)");
                        $newContactsCount = 0;
                        foreach ($contacts as $contact) {
                            $contactUserId = $contact['user_id'] ?? null;
                            if ($contactUserId && !in_array($contactUserId, $savedContactIds)) {
                                $insertStmt->execute([$currentUserId, $contactUserId]);
                                $newContactsCount++;
                            }
                        }
                        if ($newContactsCount > 0) {
                            error_log("自動保存 " . $newContactsCount . " 位有訊息記錄的聯絡人到 user_contacts 表（其他角色）");
                        }
                    } catch (PDOException $e) {
                        error_log("自動保存聯絡人失敗（其他角色）: " . $e->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("獲取聯絡人失敗: " . $e->getMessage());
        }
        
        // 如果沒有聯絡人，顯示空陣列
        if (empty($contacts)) {
            $contacts = [];
        }
        
        // 調試：記錄最終聯絡人數量
        error_log("最終聯絡人數量（老師/行政）: " . count($contacts));
    }
    
    // 為每個聯絡人添加未讀消息數量，並按未讀消息數量排序
    // 調試：記錄所有角色的最終聯絡人數量
    error_log("所有角色最終聯絡人數量: " . count($contacts ?? []));
    try {
        // 確保 $contacts 是陣列
        if (!is_array($contacts)) {
            $contacts = [];
        }
        
        // 獲取當前用戶ID
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentUserId = $currentUser ? $currentUser['id'] : null;
        
        if ($currentUserId && !empty($contacts)) {
            // 檢查表結構
            $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                                WHERE TABLE_SCHEMA = 'topics_good' 
                                AND TABLE_NAME = 'private_chat_history' 
                                AND COLUMN_NAME IN ('from_user_id', 'to_user_id', 'is_read')");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $useUserId = in_array('from_user_id', $columns) && in_array('to_user_id', $columns);
            $hasIsRead = in_array('is_read', $columns);
            
            // 為每個聯絡人計算未讀消息數量
            foreach ($contacts as &$contact) {
                $contact['unread_count'] = 0;
                
                // 獲取聯絡人的user_id
                $contactUserId = $contact['user_id'] ?? null;
                if (!$contactUserId) {
                    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
                    $stmt->execute([$contact['username']]);
                    $contactUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    $contactUserId = $contactUser ? $contactUser['id'] : null;
                }
                
                if ($contactUserId) {
                    if ($useUserId && $hasIsRead) {
                        // 使用正規化版本（user_id + is_read）
                        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                              FROM private_chat_history 
                                              WHERE from_user_id = ? AND to_user_id = ? 
                                              AND (is_read = 0 OR is_read IS NULL)");
                        $stmt->execute([$contactUserId, $currentUserId]);
                    } elseif ($useUserId) {
                        // 使用正規化版本（user_id，但沒有is_read欄位）
                        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                              FROM private_chat_history 
                                              WHERE from_user_id = ? AND to_user_id = ?");
                        $stmt->execute([$contactUserId, $currentUserId]);
                    } else {
                        // 使用舊版本（username）
                        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count 
                                              FROM private_chat_history 
                                              WHERE from_user = ? AND to_user = ?");
                        $stmt->execute([$contact['username'], $username]);
                    }
                    
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $contact['unread_count'] = $result['unread_count'] ?? 0;
                }
            }
            unset($contact); // 釋放引用
            
            // 按未讀消息數量排序（有未讀消息的排在前面，未讀消息多的排在更前面）
            if (is_array($contacts) && !empty($contacts)) {
                usort($contacts, function($a, $b) {
                    // 先按未讀消息數量排序（降序）
                    if ($b['unread_count'] != $a['unread_count']) {
                        return $b['unread_count'] - $a['unread_count'];
                    }
                    // 如果未讀消息數量相同，按名稱排序（升序）
                    return strcmp($a['name'] ?? '', $b['name'] ?? '');
                });
            }
        }
    } catch (PDOException $e) {
        // 如果獲取未讀消息數量失敗，不影響聯絡人列表顯示
        error_log("獲取未讀消息數量失敗: " . $e->getMessage());
    }
    
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <link rel="stylesheet" href="css_cache_buster.php?file=chat&v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css_cache_buster.php?file=color_schemes&v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css_cache_buster.php?file=voice_styles&v=<?php echo time(); ?>">
  <title>聊天室</title>
  <script src="fcm_client.js"></script>
  <script src="voice_recorder.js"></script>
</head>
<body>
<?php include("../share/header.php"); ?>
<main>
  <?php if ($role === 'STU' || $role === '學生' || $role === 'student'): ?>
    <!-- 學生聊天介面 -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表 <span id="unreadBadge" style="background: #ff4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; display: none;">0</span></h2>
          <div style="margin-top: 10px;">
            <button id="createGroupBtn" style="margin-right: 5px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">建立群組</button>
          </div>
        </div>
        
        <!-- 搜尋和新增聯絡人區域 -->
        <div class="search-container" style="padding: 10px; border-bottom: 1px solid #eee;">
          <!-- 搜尋現有聯絡人 -->
          <div style="margin-bottom: 10px;">
            <input type="text" id="contactSearch" placeholder="搜尋現有聯絡人..." 
                   style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
          </div>
          
          <!-- 新增聯絡人區域 -->
          <div style="border-top: 1px solid #eee; padding-top: 10px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: bold;">➕ 新增聯絡人</div>
            <div style="display: flex; gap: 5px;">
              <input type="text" id="addContactSearch" placeholder="輸入姓名或帳號搜尋用戶..." 
                     style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
              <button id="searchUserBtn" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap;">搜尋</button>
            </div>
            <!-- 搜尋結果區域 -->
            <div id="addContactResults" style="display: none; margin-top: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
              <div style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #666; font-size: 12px;">搜尋結果</div>
              <div id="addContactResultsList" style="padding: 5px;"></div>
            </div>
          </div>
        </div>
        
        <!-- 群組列表 -->
        <div id="groupList" style="margin-bottom: 20px;">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">我的群組</h3>
          <div id="groupsContainer"></div>
        </div>
        
        <!-- 聯絡人列表 -->
        <div id="contactList">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">聯絡人 <span id="contactCount"></span></h3>
          <ul class="user-list" id="contactListItems">
            <!-- 聯絡人項目將通過 JavaScript 動態生成 -->
          </ul>
          <!-- 分頁控制 -->
          <div id="contactPagination" style="padding: 10px; text-align: center; border-top: 1px solid #eee; display: none;">
            <button id="prevPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">上一頁</button>
            <span id="pageInfo" style="margin: 0 10px; color: #666;"></span>
            <button id="nextPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">下一頁</button>
          </div>
        </div>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇老師開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
          <div class="chat-header-actions" id="chatHeaderActions" style="display: none;">
            <button id="addMemberBtn" onclick="showAddMemberModal()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px; font-size: 14px;">
              ➕ 新增成員
            </button>
            <button id="manageMembersBtn" onclick="showManageMembersModal()" style="padding: 8px 16px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
              👥 管理成員
            </button>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位老師開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button id="voiceRecordBtn" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
      </div>
    </div>

  <?php elseif ($role === 'TEA' || $role === '老師' || $role === 'teacher' || $role === 'STU' || $role === '學生' || $role === 'student' || $role === 'STA' || $role === '學校行政人員' || $role === '行政人員'): ?>
    <!-- 老師和學生聊天介面 -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表 <span id="unreadBadge" style="background: #ff4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; display: none;">0</span></h2>
          <div style="margin-top: 10px;">
            <button id="createGroupBtn" style="margin-right: 5px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">建立群組</button>
          </div>
        </div>
        
        <!-- 搜尋和新增聯絡人區域 -->
        <div class="search-container" style="padding: 10px; border-bottom: 1px solid #eee;">
          <!-- 搜尋現有聯絡人 -->
          <div style="margin-bottom: 10px;">
            <input type="text" id="contactSearch" placeholder="搜尋現有聯絡人..." 
                   style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
          </div>
          
          <!-- 新增聯絡人區域 -->
          <div style="border-top: 1px solid #eee; padding-top: 10px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: bold;">➕ 新增聯絡人</div>
            <div style="display: flex; gap: 5px;">
              <input type="text" id="addContactSearch" placeholder="輸入姓名或帳號搜尋用戶..." 
                     style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
              <button id="searchUserBtn" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap;">搜尋</button>
            </div>
            <!-- 搜尋結果區域 -->
            <div id="addContactResults" style="display: none; margin-top: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
              <div style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #666; font-size: 12px;">搜尋結果</div>
              <div id="addContactResultsList" style="padding: 5px;"></div>
            </div>
          </div>
        </div>
        
        <!-- 群組列表 -->
        <div id="groupList" style="margin-bottom: 20px;">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">我的群組</h3>
          <div id="groupsContainer"></div>
        </div>
        
        <!-- 聯絡人列表 -->
        <div id="contactList">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">聯絡人 <span id="contactCount"></span></h3>
          <ul class="user-list" id="contactListItems">
            <!-- 聯絡人項目將通過 JavaScript 動態生成 -->
          </ul>
          <!-- 分頁控制 -->
          <div id="contactPagination" style="padding: 10px; text-align: center; border-top: 1px solid #eee; display: none;">
            <button id="prevPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">上一頁</button>
            <span id="pageInfo" style="margin: 0 10px; color: #666;"></span>
            <button id="nextPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">下一頁</button>
          </div>
        </div>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇聯絡人開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
          <div class="chat-header-actions" id="chatHeaderActions" style="display: none;">
            <button id="addMemberBtn" onclick="showAddMemberModal()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px; font-size: 14px;">
              ➕ 新增成員
            </button>
            <button id="manageMembersBtn" onclick="showManageMembersModal()" style="padding: 8px 16px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
              👥 管理成員
            </button>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位聯絡人開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button id="voiceRecordBtn" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
        
        <!-- 語音錄製指示器 -->
        <div id="recordingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">🎤</div>
            <div>正在錄製語音...</div>
            <div id="recordingTimer" style="font-size: 18px; margin-top: 5px;"></div>
          </div>
        </div>
        
        <!-- 處理中指示器 -->
        <div id="processingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
            <div>正在轉換語音為文字...</div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- 其他角色的完整聊天介面（如學校行政人員） -->
    <div class="chat-container">
      <!-- 左側聯絡人列表 -->
      <div class="sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">聯絡人列表 <span id="unreadBadge" style="background: #ff4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; display: none;">0</span></h2>
          <div style="margin-top: 10px;">
            <button id="createGroupBtn" style="margin-right: 5px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">建立群組</button>
          </div>
        </div>
        
        <!-- 搜尋和新增聯絡人區域 -->
        <div class="search-container" style="padding: 10px; border-bottom: 1px solid #eee;">
          <!-- 搜尋現有聯絡人 -->
          <div style="margin-bottom: 10px;">
            <input type="text" id="contactSearch" placeholder="搜尋現有聯絡人..." 
                   style="width: 100%; max-width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
          </div>
          
          <!-- 新增聯絡人區域 -->
          <div style="border-top: 1px solid #eee; padding-top: 10px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: bold;">➕ 新增聯絡人</div>
            <div style="display: flex; gap: 5px;">
              <input type="text" id="addContactSearch" placeholder="輸入姓名或帳號搜尋用戶..." 
                     style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
              <button id="searchUserBtn" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap;">搜尋</button>
            </div>
            <!-- 搜尋結果區域 -->
            <div id="addContactResults" style="display: none; margin-top: 10px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
              <div style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #666; font-size: 12px;">搜尋結果</div>
              <div id="addContactResultsList" style="padding: 5px;"></div>
            </div>
          </div>
        </div>
        
        <!-- 群組列表 -->
        <div id="groupList" style="margin-bottom: 20px;">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">我的群組</h3>
          <div id="groupsContainer"></div>
        </div>
        
        <!-- 聯絡人列表 -->
        <div id="contactList">
          <h3 style="margin: 10px 0; color: #666; font-size: 14px;">聯絡人 <span id="contactCount"></span></h3>
          <ul class="user-list" id="contactListItems">
            <!-- 聯絡人項目將通過 JavaScript 動態生成 -->
          </ul>
          <!-- 分頁控制 -->
          <div id="contactPagination" style="padding: 10px; text-align: center; border-top: 1px solid #eee; display: none;">
            <button id="prevPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">上一頁</button>
            <span id="pageInfo" style="margin: 0 10px; color: #666;"></span>
            <button id="nextPageBtn" style="padding: 5px 15px; margin: 0 5px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">下一頁</button>
          </div>
        </div>
      </div>
      
      <!-- 右側聊天區域 -->
      <div class="chat-main">
        <div class="chat-header">
          <div class="current-chat-info">
            <div class="current-chat-name">選擇聯絡人開始聊天</div>
            <div class="current-chat-role"></div>
          </div>
          <div class="chat-header-actions" id="chatHeaderActions" style="display: none;">
            <button id="addMemberBtn" onclick="showAddMemberModal()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 8px; font-size: 14px;">
              ➕ 新增成員
            </button>
            <button id="manageMembersBtn" onclick="showManageMembersModal()" style="padding: 8px 16px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
              👥 管理成員
            </button>
          </div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
          <div class="no-chat-selected">
            請從左側選擇一位聯絡人開始聊天
          </div>
        </div>
        
        <div class="chat-input">
          <input type="text" id="messageInput" placeholder="輸入訊息..." disabled>
          <button id="voiceRecordBtn" onclick="toggleVoiceRecording()" disabled title="語音輸入">🎤 語音</button>
          <button onclick="sendMessage()" disabled>發送</button>
        </div>
        
        <!-- 語音錄製指示器 -->
        <div id="recordingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">🎤</div>
            <div>正在錄製語音...</div>
            <div id="recordingTimer" style="font-size: 18px; margin-top: 5px;"></div>
          </div>
        </div>
        
        <!-- 處理中指示器 -->
        <div id="processingIndicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 1000;">
          <div style="text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">⏳</div>
            <div>正在轉換語音為文字...</div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script>
    const username = "<?php echo $username; ?>";
    const role = "<?php echo $role; ?>";
    
    let currentUserId = null;
    let currentUserName = null;
    let currentChatType = 'private'; // 'private' 或 'group'
    let currentGroupId = null;
    
    // 分頁相關變數
    let currentPage = 1;
    const itemsPerPage = 10; // 每頁顯示的聯絡人數量
    let allContacts = <?php 
        // 確保 $contacts 已正確初始化
        if (!isset($contacts) || !is_array($contacts)) {
            $contacts = [];
        }
        
        // 調試：記錄傳遞到前端的聯絡人資料
        error_log("=== 傳遞到前端的聯絡人資料 ===");
        error_log("聯絡人總數: " . count($contacts));
        if (count($contacts) > 0) {
            error_log("前 3 位聯絡人: " . print_r(array_slice($contacts, 0, 3), true));
        }
        
        // 調試：在頁面輸出聯絡人數量
        error_log("=== 最終傳遞到前端的聯絡人資料 ===");
        error_log("準備輸出到 JavaScript 的聯絡人數量: " . count($contacts));
        $currentUserIdStr = isset($currentUserId) ? (string)$currentUserId : 'null';
        error_log("當前用戶角色: " . $role . ", username: " . $username . ", currentUserId: " . $currentUserIdStr);
        if (count($contacts) > 0) {
            error_log("所有聯絡人 user_id: " . json_encode(array_column($contacts, 'user_id'), JSON_UNESCAPED_UNICODE));
        } else {
            error_log("❌ 警告：準備輸出到 JavaScript 的聯絡人數量為 0！");
            // 最後一次檢查：直接查詢 user_contacts 表
            if (isset($currentUserId) && $currentUserId) {
                try {
                    $finalCheckStmt = $pdo->prepare("SELECT contact_user_id FROM user_contacts WHERE user_id = ?");
                    $finalCheckStmt->execute([$currentUserId]);
                    $finalCheckResults = $finalCheckStmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("最後檢查：user_contacts 表中 user_id=" . $currentUserId . " 的 contact_user_id 列表: " . json_encode($finalCheckResults, JSON_UNESCAPED_UNICODE));
                    
                    if (count($finalCheckResults) > 0 && count($contacts) === 0) {
                        error_log("❌ 嚴重問題：資料庫中有 " . count($finalCheckResults) . " 筆記錄，但聯絡人陣列為空！");
                        error_log("❌ 執行緊急修復：直接構建聯絡人列表");
                        
                        // 緊急修復：直接構建聯絡人列表
                        foreach ($finalCheckResults as $contactUserId) {
                            // 查詢 user 表
                            $userStmt = $pdo->prepare("SELECT id, username, name, role, profile_picture FROM user WHERE id = ?");
                            $userStmt->execute([$contactUserId]);
                            $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($userInfo) {
                                // 查詢 student 或 teacher 資訊
                                $studentStmt = $pdo->prepare("SELECT department, grade, class_name FROM student WHERE user_id = ?");
                                $studentStmt->execute([$contactUserId]);
                                $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);
                                
                                $teacherStmt = $pdo->prepare("SELECT department FROM teacher WHERE user_id = ?");
                                $teacherStmt->execute([$contactUserId]);
                                $teacherInfo = $teacherStmt->fetch(PDO::FETCH_ASSOC);
                                
                                $contacts[] = [
                                    'user_id' => $contactUserId,
                                    'name' => $userInfo['name'] ?: $userInfo['username'],
                                    'username' => $userInfo['username'],
                                    'department' => $studentInfo['department'] ?? $teacherInfo['department'] ?? '未設定',
                                    'profile_picture' => $userInfo['profile_picture'],
                                    'contact_type' => ($userInfo['role'] === 'TEA' || $userInfo['role'] === '老師') ? '老師' : 
                                                     (($userInfo['role'] === 'STU' || $userInfo['role'] === '學生') ? '學生' : '其他'),
                                    'grade' => $studentInfo['grade'] ?? '未設定',
                                    'class_name' => $studentInfo['class_name'] ?? '未設定'
                                ];
                            } else {
                                // 如果 user 表中沒有，至少顯示 contact_user_id
                                $contacts[] = [
                                    'user_id' => $contactUserId,
                                    'name' => '未知用戶 (ID: ' . $contactUserId . ')',
                                    'username' => 'unknown_' . $contactUserId,
                                    'department' => '未設定',
                                    'profile_picture' => null,
                                    'contact_type' => '其他',
                                    'grade' => '未設定',
                                    'class_name' => '未設定'
                                ];
                            }
                        }
                        error_log("✅ 緊急修復完成：構建了 " . count($contacts) . " 位聯絡人");
                    }
                } catch (Exception $e) {
                    error_log("最後檢查失敗: " . $e->getMessage());
                }
            }
        }
        echo json_encode($contacts, JSON_UNESCAPED_UNICODE); 
    ?>;
    console.log('=== 聯絡人載入調試 ===');
    console.log('從 PHP 載入的聯絡人數量:', allContacts.length);
    console.log('當前用戶角色:', role);
    console.log('當前用戶名:', username);
    console.log('所有聯絡人資料:', allContacts);
    
    // 檢查聯絡人資料結構
    if (allContacts.length > 0) {
      console.log('前 3 位聯絡人詳情:', allContacts.slice(0, 3));
      
      // 檢查是否有 user_id
      const contactsWithUserId = allContacts.filter(c => c.user_id);
      console.log('有 user_id 的聯絡人數量:', contactsWithUserId.length);
      if (contactsWithUserId.length > 0) {
        console.log('有 user_id 的聯絡人:', contactsWithUserId);
      }
      
      // 檢查是否有 username
      const contactsWithUsername = allContacts.filter(c => c.username);
      console.log('有 username 的聯絡人數量:', contactsWithUsername.length);
    } else {
      console.warn('⚠️ 沒有載入到任何聯絡人！');
      console.warn('請檢查 PHP 錯誤日誌中的聯絡人載入信息');
    }
    
    // 調試：檢查是否有 TEA 聯絡人
    const teaContacts = allContacts.filter(c => c.contact_type === '老師');
    console.log('TEA 聯絡人數量:', teaContacts.length);
    if (teaContacts.length > 0) {
      console.log('TEA 聯絡人列表:', teaContacts);
    } else {
      console.warn('⚠️ 沒有找到 TEA 聯絡人！');
      console.warn('請檢查 PHP 錯誤日誌中的調試訊息');
    }
    let filteredContacts = [];
    let displayedContacts = [];
    
    // 已讀聯絡人管理
    let readContacts = new Set();
    
    // 從 localStorage 載入已讀聯絡人
    function loadReadContactsFromStorage() {
      try {
        const stored = localStorage.getItem(`readContacts_${username}`);
        if (stored) {
          readContacts = new Set(JSON.parse(stored));
        }
      } catch (error) {
        console.error('載入已讀聯絡人失敗:', error);
        readContacts = new Set();
      }
    }
    
    // 保存已讀聯絡人到 localStorage
    function saveReadContactsToStorage() {
      try {
        localStorage.setItem(`readContacts_${username}`, JSON.stringify(Array.from(readContacts)));
      } catch (error) {
        console.error('保存已讀聯絡人失敗:', error);
      }
    }
    
    // 初始化時載入已讀聯絡人
    loadReadContactsFromStorage();
    
    // 檢查輸入框內容並更新發送按鈕狀態（提前定義，確保在所有地方都能使用）
    function updateSendButtonState() {
      const messageInput = document.getElementById('messageInput');
      const sendButton = document.querySelector('.chat-input button:not(#voiceRecordBtn)');
      
      if (messageInput && sendButton) {
        const hasContent = messageInput.value.trim().length > 0;
        const isInputEnabled = !messageInput.disabled;
        
        // 只有當輸入框啟用且有內容時，才啟用發送按鈕
        sendButton.disabled = !isInputEnabled || !hasContent;
      }
    }
    let lastMessageId = 0;
    let messageCache = new Map(); // 快取聊天記錄
    
    // 清除所有快取
    function clearMessageCache() {
        messageCache.clear();
        console.log('清除所有聊天記錄快取');
    }
    
    <?php if ($role === 'STU' || $role === '學生' || $role === 'student' || $role === 'TEA' || $role === '老師' || $role === 'teacher' || $role === 'STA' || $role === '學校行政人員' || $role === '行政人員'): ?>
    // 載入群組列表
    async function loadGroups() {
      try {
        const response = await fetch('group_management.php?action=get_my_groups&username=' + username);
        
        // 檢查響應狀態
        if (!response.ok) {
          throw new Error('HTTP錯誤: ' + response.status);
        }
        
        // 獲取響應文本以便調試
        const responseText = await response.text();
        console.log('群組API響應文本:', responseText.substring(0, 200));
        
        // 嘗試解析JSON
        let result;
        try {
          result = JSON.parse(responseText);
        } catch (parseError) {
          console.error('JSON解析失敗:', parseError);
          console.error('響應內容:', responseText);
          alert('載入群組失敗: 伺服器回應格式錯誤');
          return;
        }
        
        if (result.success && result.groups) {
          const groupsContainer = document.getElementById('groupsContainer');
          groupsContainer.innerHTML = '';
          
          result.groups.forEach(group => {
            const groupItem = document.createElement('div');
            groupItem.className = 'user-item';
            groupItem.dataset.groupId = group.id;
            groupItem.dataset.groupName = group.group_name;
            groupItem.dataset.chatType = 'group';
            groupItem.dataset.createdBy = group.created_by || '';
            
            // 判斷當前用戶是否為群組創建者
            const isCreator = group.created_by === username;
            
            // 獲取未讀數量（從後端直接返回，立即顯示）
            const unreadCount = parseInt(group.unread_count || 0);
            const badgeHtml = unreadCount > 0 
              ? `<span class="unread-badge" data-group-id="${group.id}" data-count="${unreadCount}" style="display: flex; visibility: visible;">${unreadCount > 99 ? '99+' : unreadCount}</span>`
              : `<span class="unread-badge" data-group-id="${group.id}" style="display: none; visibility: hidden;"></span>`;
            
            // 根據是否為創建者顯示不同的按鈕
            const actionButtonHtml = isCreator 
              ? `<button class="delete-group-btn" onclick="event.stopPropagation(); deleteGroup('${group.id}', '${group.group_name}')" style="padding: 5px 10px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">刪除</button>`
              : `<button class="leave-group-btn" onclick="event.stopPropagation(); leaveGroup('${group.id}', '${group.group_name}')" style="padding: 5px 10px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">離開</button>`;
            
            groupItem.innerHTML = `
              <div class="user-avatar" style="background: #4CAF50;">
                <i class="fas fa-users" style="font-size: 16px;">👥</i>
              </div>
              ${badgeHtml}
              <div class="user-info">
                <div class="user-name" id="group-name-${group.id}">${group.group_name}</div>
                <div class="user-role">${group.member_count} 位成員</div>
                <div class="contact-type">群組</div>
                <div style="display: flex; gap: 5px; margin-top: 5px;">
                  <button class="edit-group-btn" onclick="event.stopPropagation(); editGroupName('${group.id}', '${group.group_name}')" style="padding: 5px 10px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">編輯</button>
                  ${actionButtonHtml}
                </div>
              </div>
            `;
            
            groupItem.addEventListener('click', function(e) {
              // 如果點擊的是編輯、刪除或離開按鈕，不觸發群組選擇
              if (e.target && e.target.classList && (e.target.classList.contains('edit-group-btn') || e.target.classList.contains('delete-group-btn') || e.target.classList.contains('leave-group-btn'))) {
                return;
              }
              selectGroup(group.id, group.group_name);
            });
            
            groupsContainer.appendChild(groupItem);
          });
          
          // 未讀數量已經從後端返回並顯示，這裡只做後續更新（可選）
          // 使用 setTimeout 讓初始渲染完成後再更新，避免阻塞
          setTimeout(() => {
            updateGroupUnreadCounts();
          }, 100);
        } else if (result.error) {
          console.error('載入群組失敗:', result.error);
          alert('載入群組失敗: ' + result.error);
        }
      } catch (error) {
        console.error('載入群組失敗:', error);
        alert('載入群組失敗: ' + (error.message || '未知錯誤'));
      }
    }
    
    // 更新所有群組的未讀訊息數量
    async function updateGroupUnreadCounts() {
      try {
        const response = await fetch(`get_group_unread_count.php?username=${encodeURIComponent(username)}`);
        const result = await response.json();
        
        console.log('群組未讀數量 API 響應:', result);
        
        if (result.success) {
          const unreadCounts = result.unread_counts || {};
          
          // 獲取所有群組項目的徽章
          const allGroupBadges = document.querySelectorAll('.unread-badge[data-group-id]');
          
          // 更新所有群組的未讀徽章（包括沒有未讀訊息的群組）
          allGroupBadges.forEach(badge => {
            const groupId = badge.getAttribute('data-group-id');
            const unreadCount = parseInt(unreadCounts[groupId] || 0);
            
            if (unreadCount > 0) {
              badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
              badge.setAttribute('data-count', unreadCount);
              badge.style.display = 'flex';
              badge.style.visibility = 'visible';
              badge.classList.add('show', 'pulse');
            } else {
              badge.style.display = 'none';
              badge.style.visibility = 'hidden';
              badge.classList.remove('show', 'pulse');
            }
          });
          
          // 處理 API 返回的未讀群組（確保所有有未讀的群組都顯示徽章）
          Object.keys(unreadCounts).forEach(groupId => {
            const unreadCount = parseInt(unreadCounts[groupId] || 0);
            if (unreadCount > 0) {
              let badge = document.querySelector(`.unread-badge[data-group-id="${groupId}"]`);
              
              // 如果徽章不存在，嘗試創建它（這通常不應該發生，但為了安全起見）
              if (!badge) {
                const groupItem = document.querySelector(`.user-item[data-group-id="${groupId}"]`);
                if (groupItem) {
                  const avatarContainer = groupItem.querySelector('.user-avatar');
                  if (avatarContainer) {
                    badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.setAttribute('data-group-id', groupId);
                    avatarContainer.appendChild(badge);
                  }
                }
              }
              
              if (badge) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                badge.setAttribute('data-count', unreadCount);
                badge.style.display = 'flex';
                badge.style.visibility = 'visible';
                badge.classList.add('show', 'pulse');
              }
            }
          });
        }
      } catch (error) {
        console.error('更新群組未讀數量失敗:', error);
      }
    }
    
    // 標記群組訊息為已讀
    async function markGroupAsRead(groupId) {
      try {
        // 更新資料庫中的最後讀取時間
        const response = await fetch('group_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=mark_group_as_read&group_id=${groupId}`
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('群組已標記為已讀:', groupId);
        }
        
        // 隱藏徽章
        const badge = document.querySelector(`.unread-badge[data-group-id="${groupId}"]`);
        if (badge) {
          badge.style.display = 'none';
          badge.style.visibility = 'hidden';
          badge.classList.remove('show', 'pulse');
        }
      } catch (error) {
        console.error('標記群組為已讀失敗:', error);
      }
    }
    
    // 選擇群組
    function selectGroup(groupId, groupName) {
      console.log('選擇群組:', groupId, groupName);
      
      // 移除其他項目的active狀態
      document.querySelectorAll('.user-item').forEach(i => {
        if (i && i.classList) {
          i.classList.remove('active');
        }
      });
      
      // 添加當前項目的active狀態
      const currentElement = document.querySelector(`[data-group-id="${groupId}"]`);
      if (currentElement && currentElement.classList) {
        currentElement.classList.add('active');
      }
      
      // 標記群組為已讀
      markGroupAsRead(groupId);
      
      // 檢查是否切換到不同的群組
      if (currentGroupId !== groupId || currentChatType !== 'group') {
        // 重置訊息ID並清空聊天區域
        lastMessageId = 0;
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
          chatMessages.innerHTML = '';
        }
      }
      
      // 設置當前聊天類型
      currentChatType = 'group';
      currentGroupId = groupId;
      currentUserId = null;
      currentUserName = groupName;
      
      // 更新聊天標題
      const chatNameElement = document.querySelector('.current-chat-name');
      const chatRoleElement = document.querySelector('.current-chat-role');
      const chatHeaderActions = document.getElementById('chatHeaderActions');
      if (chatNameElement) {
        chatNameElement.textContent = groupName;
      }
      if (chatRoleElement) {
        chatRoleElement.textContent = '群組聊天';
      }
      // 顯示群組管理按鈕
      if (chatHeaderActions) {
        chatHeaderActions.style.display = 'flex';
      }
      
      // 啟用輸入框
      const messageInput = document.getElementById('messageInput');
      const sendButton = document.querySelector('.chat-input button:not(#voiceRecordBtn)');
      if (messageInput) {
        messageInput.disabled = false;
      }
      if (sendButton) {
        // 初始狀態：禁用發送按鈕（等待用戶輸入）
        sendButton.disabled = true;
      }
      // 更新按鈕狀態（檢查是否有內容）
      updateSendButtonState();
      
      // 隱藏提示訊息
      const noChatSelected = document.querySelector('.no-chat-selected');
      if (noChatSelected && noChatSelected.classList) {
        noChatSelected.classList.add('hidden');
      }
      
      // 載入群組聊天記錄
      loadGroupChatHistory();
    }
    
    // 顯示新增成員模態框
    async function showAddMemberModal() {
      if (!currentGroupId) {
        alert('請先選擇一個群組');
        return;
      }
      
      // 獲取群組成員列表（排除已在群組中的成員）
      try {
        const response = await fetch(`group_management.php?action=get_group_members&group_id=${currentGroupId}`);
        const result = await response.json();
        
        if (!result.success) {
          alert('獲取群組成員失敗: ' + (result.error || '未知錯誤'));
          return;
        }
        
        const groupMembers = result.members || [];
        const memberUsernames = groupMembers.map(m => m.username);
        
        // 過濾出不在群組中的聯絡人
        const availableContacts = allContacts.filter(c => 
          c.username !== username && !memberUsernames.includes(c.username)
        );
        
        if (availableContacts.length === 0) {
          alert('沒有可新增的成員');
          return;
        }
        
        // 創建模態框
        const modal = document.createElement('div');
        modal.id = 'addMemberModal';
        modal.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0,0,0,0.6);
          backdrop-filter: blur(4px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          animation: fadeIn 0.2s ease-out;
        `;
        
        modal.innerHTML = `
          <div style="background: white; padding: 0; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease-out;">
            <!-- 標題區域 -->
            <div style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); padding: 24px 28px; color: white;">
              <h3 style="margin: 0; font-size: 22px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">➕</span>
                <span>新增成員</span>
              </h3>
            </div>
            
            <!-- 內容區域 -->
            <div style="padding: 28px;">
              <!-- 搜尋成員 -->
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">搜尋成員</label>
                <div style="position: relative;">
                  <input type="text" id="addMemberSearch" placeholder="搜尋姓名、科系或班級..." 
                         style="width: 100%; padding: 12px 16px 12px 44px; border: 2px solid #e0e0e0; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: all 0.3s ease; outline: none;"
                         onfocus="this.style.borderColor='#4CAF50'; this.style.boxShadow='0 0 0 3px rgba(76, 175, 80, 0.1)'"
                         onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                  <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;">🔍</span>
                </div>
              </div>
              
              <!-- 選擇成員 -->
              <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">選擇成員</label>
                <div id="addMemberSelection" style="max-height: 350px; overflow-y: auto; border: 2px solid #e0e0e0; padding: 12px; margin-top: 8px; border-radius: 8px; background: #fafafa;">
                  ${generateAddMemberCheckboxes(availableContacts)}
                </div>
                <div style="margin-top: 12px; font-size: 13px; color: #666; display: flex; align-items: center; gap: 6px;">
                  <span style="color: #4CAF50; font-weight: 600;">已選擇 <span id="selectedAddMemberCount" style="color: #4CAF50;">0</span> 位成員</span>
                </div>
              </div>
              
              <!-- 按鈕區域 -->
              <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; padding-top: 24px; border-top: 1px solid #e0e0e0;">
                <button onclick="closeAddMemberModal()" 
                        style="padding: 12px 24px; background: #f5f5f5; color: #666; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#e0e0e0'"
                        onmouseout="this.style.background='#f5f5f5'">取消</button>
                <button onclick="addMembersToGroup()" 
                        style="padding: 12px 32px; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(76, 175, 80, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(76, 175, 80, 0.3)'">新增</button>
              </div>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        // 設置成員搜尋功能
        const memberSearchInput = document.getElementById('addMemberSearch');
        if (memberSearchInput) {
          memberSearchInput.addEventListener('input', function() {
            filterAddMemberCheckboxes(this.value.toLowerCase().trim());
          });
        }
        
        // 設置成員選擇計數更新
        updateSelectedAddMemberCount();
        
        // 點擊背景關閉
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            closeAddMemberModal();
          }
        });
      } catch (error) {
        console.error('顯示新增成員模態框失敗:', error);
        alert('顯示新增成員模態框失敗: ' + error.message);
      }
    }
    
    // 生成新增成員選擇框
    function generateAddMemberCheckboxes(contacts) {
      if (contacts.length === 0) {
        return '<div style="color: #999; text-align: center; padding: 40px 20px;"><div style="font-size: 48px; margin-bottom: 12px;">👤</div><div style="font-size: 14px;">暫無可新增的成員</div></div>';
      }
      
      let html = '';
      contacts.forEach(contact => {
        const displayName = contact.name || contact.username;
        const role = contact.contact_type || '';
        const department = contact.department || '';
        const grade = contact.grade || '';
        const className = contact.class_name || '';
        const additionalInfo = grade ? ` - ${grade}${className ? ' ' + className : ''}` : '';
        const searchableText = `${displayName} ${role} ${department} ${grade} ${className}`.toLowerCase();
        
        html += `
          <div class="add-member-item" data-search-text="${searchableText}" 
               style="margin: 6px 0; padding: 12px; border: 2px solid #e8e8e8; border-radius: 8px; display: flex; align-items: center; background: white; transition: all 0.2s ease; cursor: pointer;"
               onmouseover="this.style.borderColor='#4CAF50'; this.style.backgroundColor='#f1f8f4'; this.style.transform='translateX(4px)'"
               onmouseout="this.style.borderColor='#e8e8e8'; this.style.backgroundColor='white'; this.style.transform='translateX(0)'"
               onclick="document.getElementById('add_member_${contact.username}').click()">
            <input type="checkbox" id="add_member_${contact.username}" value="${contact.username}" data-role="${role}" 
                   style="margin-right: 12px; cursor: pointer; width: 18px; height: 18px; accent-color: #4CAF50;" 
                   onchange="updateSelectedAddMemberCount(); this.closest('.add-member-item').style.borderColor = this.checked ? '#4CAF50' : '#e8e8e8'; this.closest('.add-member-item').style.backgroundColor = this.checked ? '#f1f8f4' : 'white';">
            <label for="add_member_${contact.username}" style="flex: 1; cursor: pointer; margin: 0;">
              <div style="font-weight: 600; color: #333; margin-bottom: 4px; font-size: 14px;">${escapeHtml(displayName)}</div>
              <div style="font-size: 12px; color: #888;">${escapeHtml(role)}${department ? ' - ' + escapeHtml(department) : ''}${additionalInfo}</div>
            </label>
          </div>
        `;
      });
      
      return html;
    }
    
    // 過濾新增成員選擇框
    function filterAddMemberCheckboxes(searchTerm) {
      const memberItems = document.querySelectorAll('#addMemberSelection .add-member-item');
      let visibleCount = 0;
      
      memberItems.forEach(item => {
        const searchText = item.dataset.searchText || '';
        if (searchTerm === '' || searchText.includes(searchTerm)) {
          item.style.display = 'flex';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });
      
      // 如果沒有可見項目，顯示提示
      if (visibleCount === 0 && searchTerm !== '') {
        const memberSelection = document.getElementById('addMemberSelection');
        if (memberSelection && !memberSelection.querySelector('.no-results')) {
          const noResults = document.createElement('div');
          noResults.className = 'no-results';
          noResults.style.cssText = 'text-align: center; padding: 40px 20px; color: #999; font-size: 14px;';
          noResults.innerHTML = '<div style="font-size: 48px; margin-bottom: 12px;">🔍</div><div>找不到符合條件的成員</div>';
          memberSelection.appendChild(noResults);
        }
      } else {
        const noResults = document.querySelector('#addMemberSelection .no-results');
        if (noResults) {
          noResults.remove();
        }
      }
    }
    
    // 更新已選擇新增成員計數
    function updateSelectedAddMemberCount() {
      const selectedCount = document.querySelectorAll('#addMemberSelection input[type="checkbox"]:checked').length;
      const countElement = document.getElementById('selectedAddMemberCount');
      if (countElement) {
        countElement.textContent = selectedCount;
      }
    }
    
    // 關閉新增成員模態框
    function closeAddMemberModal() {
      const modal = document.getElementById('addMemberModal');
      if (modal) {
        modal.remove();
      }
    }
    
    // 新增成員到群組
    async function addMembersToGroup() {
      if (!currentGroupId) {
        alert('請先選擇一個群組');
        return;
      }
      
      const selectedMembers = [];
      document.querySelectorAll('#addMemberSelection input[type="checkbox"]:checked').forEach(checkbox => {
        selectedMembers.push({
          username: checkbox.value,
          role: checkbox.dataset.role
        });
      });
      
      if (selectedMembers.length === 0) {
        alert('請至少選擇一位成員');
        return;
      }
      
      try {
        const formData = new FormData();
        formData.append('action', 'add_group_members');
        formData.append('group_id', currentGroupId);
        formData.append('members', JSON.stringify(selectedMembers));
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert(`已成功新增 ${selectedMembers.length} 位成員到群組！`);
          closeAddMemberModal();
          // 重新載入群組列表以更新成員數量
          loadGroups();
        } else {
          alert('新增成員失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('新增成員失敗:', error);
        alert('新增成員失敗: ' + error.message);
      }
    }
    
    // 顯示管理成員模態框（包含踢出成員功能）
    async function showManageMembersModal() {
      if (!currentGroupId) {
        alert('請先選擇一個群組');
        return;
      }
      
      try {
        const response = await fetch(`group_management.php?action=get_group_members&group_id=${currentGroupId}`);
        const result = await response.json();
        
        if (!result.success) {
          alert('獲取群組成員失敗: ' + (result.error || '未知錯誤'));
          return;
        }
        
        const groupMembers = result.members || [];
        
        // 創建模態框
        const modal = document.createElement('div');
        modal.id = 'manageMembersModal';
        modal.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0,0,0,0.6);
          backdrop-filter: blur(4px);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          animation: fadeIn 0.2s ease-out;
        `;
        
        let membersHtml = '';
        if (groupMembers.length === 0) {
          membersHtml = '<div style="color: #999; text-align: center; padding: 40px 20px;"><div style="font-size: 48px; margin-bottom: 12px;">👤</div><div style="font-size: 14px;">群組中暫無成員</div></div>';
        } else {
          groupMembers.forEach(member => {
            const isCurrentUser = member.username === username;
            const isCreator = Boolean(member.is_creator);
            // 不能踢出自己
            const canKick = !isCurrentUser;
            
            // 確保名稱是字符串
            const memberName = (member.name && String(member.name).trim()) || (member.username && String(member.username).trim()) || '未知用戶';
            const memberRole = (member.role && String(member.role).trim()) || '';
            const memberDepartment = (member.department && String(member.department).trim()) || '';
            
            membersHtml += `
              <div class="manage-member-item" 
                   style="margin: 8px 0; padding: 16px; border: 2px solid #e8e8e8; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; background: white; transition: all 0.2s ease;"
                   onmouseover="this.style.borderColor='#ff9800'; this.style.backgroundColor='#fff8f0'"
                   onmouseout="this.style.borderColor='#e8e8e8'; this.style.backgroundColor='white'">
                <div style="flex: 1;">
                  <div style="font-weight: 600; color: #333; margin-bottom: 4px; font-size: 14px;">
                    ${escapeHtml(memberName)} 
                    ${isCreator ? '<span style="color: #ff9800; font-size: 12px; margin-left: 8px;">(創建者)</span>' : ''}
                    ${isCurrentUser ? '<span style="color: #4CAF50; font-size: 12px; margin-left: 8px;">(我)</span>' : ''}
                  </div>
                  <div style="font-size: 12px; color: #888;">${memberRole ? escapeHtml(memberRole) : ''}${memberDepartment ? (memberRole ? ' - ' : '') + escapeHtml(memberDepartment) : ''}</div>
                </div>
                ${canKick ? `
                  <button onclick="removeMemberFromGroup('${escapeHtml(member.username || '')}', '${escapeHtml(memberName)}')" 
                          style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.2s ease; width:50%;"
                          onmouseover="this.style.background='#d32f2f'; this.style.transform='scale(1.05)'"
                          onmouseout="this.style.background='#f44336'; this.style.transform='scale(1)'">
                    ❌ 踢出
                  </button>
                ` : '<span style="color: #999; font-size: 12px;">無法移除</span>'}
              </div>
            `;
          });
        }
        
        modal.innerHTML = `
          <div style="background: white; padding: 0; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease-out;">
            <!-- 標題區域 -->
            <div style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); padding: 24px 28px; color: white;">
              <h3 style="margin: 0; font-size: 22px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">👥</span>
                <span>管理成員 (${groupMembers.length} 位)</span>
              </h3>
            </div>
            
            <!-- 內容區域 -->
            <div style="padding: 28px;">
              <div id="manageMemberList" style="max-height: 400px; overflow-y: auto;">
                ${membersHtml}
              </div>
              
              <!-- 按鈕區域 -->
              <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; padding-top: 24px; border-top: 1px solid #e0e0e0;">
                <button onclick="closeManageMembersModal()" 
                        style="padding: 12px 24px; background: #f5f5f5; color: #666; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#e0e0e0'"
                        onmouseout="this.style.background='#f5f5f5'">關閉</button>
              </div>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        // 點擊背景關閉
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            closeManageMembersModal();
          }
        });
      } catch (error) {
        console.error('顯示管理成員模態框失敗:', error);
        alert('顯示管理成員模態框失敗: ' + error.message);
      }
    }
    
    // 關閉管理成員模態框
    function closeManageMembersModal() {
      const modal = document.getElementById('manageMembersModal');
      if (modal) {
        modal.remove();
      }
    }
    
    // 從群組中移除成員（踢出）
    async function removeMemberFromGroup(memberUsername, memberName) {
      if (!currentGroupId) {
        alert('請先選擇一個群組');
        return;
      }
      
      if (!confirm(`確定要將「${memberName}」從群組中移除嗎？`)) {
        return;
      }
      
      try {
        const formData = new FormData();
        formData.append('action', 'remove_group_member');
        formData.append('group_id', currentGroupId);
        formData.append('member_username', memberUsername);
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert(`已成功將「${memberName}」從群組中移除！`);
          // 重新載入管理成員模態框
          closeManageMembersModal();
          setTimeout(() => {
            showManageMembersModal();
          }, 300);
          // 重新載入群組列表以更新成員數量
          loadGroups();
        } else {
          alert('移除成員失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('移除成員失敗:', error);
        alert('移除成員失敗: ' + error.message);
      }
    }
    
    // 編輯群組名稱
    async function editGroupName(groupId, currentName) {
      const newName = prompt('請輸入新的群組名稱:', currentName);
      if (newName && newName.trim() && newName !== currentName) {
        try {
          const formData = new FormData();
          formData.append('action', 'update_group_name');
          formData.append('group_id', groupId);
          formData.append('new_name', newName.trim());
          formData.append('username', username);
          
          const response = await fetch('group_management.php', {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            // 更新顯示的群組名稱
            const nameElement = document.getElementById(`group-name-${groupId}`);
            if (nameElement) {
              nameElement.textContent = newName.trim();
            }
            
            // 更新群組項目的data屬性
            const groupItem = document.querySelector(`[data-group-id="${groupId}"]`);
            if (groupItem) {
              groupItem.dataset.groupName = newName.trim();
            }
            
            // 如果當前選中的是這個群組，也要更新聊天標題
            if (currentGroupId === groupId) {
              document.querySelector('.current-chat-name').textContent = newName.trim();
              currentUserName = newName.trim();
            }
            
            alert('群組名稱更新成功！');
            
            // 重新載入群組列表以確保數據同步
            loadGroups();
          } else {
            alert('更新失敗: ' + result.error);
          }
        } catch (error) {
          console.error('更新群組名稱失敗:', error);
          alert('更新群組名稱失敗');
        }
      }
    }
    
    // 刪除群組（僅創建者可用）
    async function deleteGroup(groupId, groupName) {
      if (!confirm(`確定要刪除群組「${groupName}」嗎？此操作無法復原。`)) {
        return;
      }
      
      try {
        const formData = new FormData();
        formData.append('action', 'delete_group');
        formData.append('group_id', groupId);
        formData.append('username', username);
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // 如果當前選中的是這個群組，清除聊天區域
          if (currentGroupId === groupId) {
            currentGroupId = null;
            currentChatType = 'private';
            currentUserName = null;
            
            // 清除聊天區域
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
              chatMessages.innerHTML = '<div class="no-chat-selected">請選擇一個聯絡人或群組開始聊天</div>';
            }
            
            // 清除聊天標題
            const chatNameElement = document.querySelector('.current-chat-name');
            if (chatNameElement) {
              chatNameElement.textContent = '';
            }
            
            const chatRoleElement = document.querySelector('.current-chat-role');
            if (chatRoleElement) {
              chatRoleElement.textContent = '';
            }
          }
          
          alert('群組已成功刪除！');
          
          // 重新載入群組列表
          loadGroups();
        } else {
          alert('刪除失敗: ' + result.error);
        }
      } catch (error) {
        console.error('刪除群組失敗:', error);
        alert('刪除群組失敗');
      }
    }
    
    // 離開群組（非創建者可用）
    async function leaveGroup(groupId, groupName) {
      if (!confirm(`確定要離開群組「${groupName}」嗎？`)) {
        return;
      }
      
      try {
        const formData = new FormData();
        formData.append('action', 'leave_group');
        formData.append('group_id', groupId);
        formData.append('username', username);
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // 如果當前選中的是這個群組，清除聊天區域
          if (currentGroupId === groupId) {
            currentGroupId = null;
            currentChatType = 'private';
            currentUserName = null;
            
            // 清除聊天區域
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
              chatMessages.innerHTML = '<div class="no-chat-selected">請選擇一個聯絡人或群組開始聊天</div>';
            }
            
            // 清除聊天標題
            const chatNameElement = document.querySelector('.current-chat-name');
            if (chatNameElement) {
              chatNameElement.textContent = '';
            }
            
            const chatRoleElement = document.querySelector('.current-chat-role');
            if (chatRoleElement) {
              chatRoleElement.textContent = '';
            }
          }
          
          alert('已成功離開群組！');
          
          // 重新載入群組列表
          loadGroups();
        } else {
          alert('離開失敗: ' + result.error);
        }
      } catch (error) {
        console.error('離開群組失敗:', error);
        alert('離開群組失敗');
      }
    }
    
    // 載入群組聊天記錄
    async function loadGroupChatHistory(append = false) {
      console.log('載入群組聊天記錄，群組ID:', currentGroupId, '追加模式:', append);
      try {
        const response = await fetch('group_management.php?action=get_group_messages&group_id=' + currentGroupId);
        console.log('群組訊息API響應:', response);
        
        // 檢查響應狀態
        if (!response.ok) {
          throw new Error('HTTP錯誤: ' + response.status);
        }
        
        // 獲取響應文本以便調試
        const responseText = await response.text();
        console.log('群組訊息API響應文本:', responseText.substring(0, 200));
        
        // 嘗試解析JSON
        let result;
        try {
          result = JSON.parse(responseText);
        } catch (parseError) {
          console.error('JSON解析失敗:', parseError);
          console.error('響應內容:', responseText);
          alert('載入群組訊息失敗: 伺服器回應格式錯誤');
          return;
        }
        
        console.log('群組訊息API結果:', result);
        
        if (result.success) {
          console.log('群組訊息API成功，結果:', result);
          console.log('群組訊息數量:', result.messages ? result.messages.length : 0);
          console.log('調試信息:', result.debug);
          
          if (result.messages && result.messages.length > 0) {
            displayGroupMessages(result.messages, append);
            
            // 批量標記所有不是自己發送的訊息為已讀
            const unreadMessageIds = result.messages
              .filter(msg => msg.from_user !== username && msg.id)
              .map(msg => parseInt(msg.id));
            
            if (unreadMessageIds.length > 0) {
              markGroupMessagesAsRead(unreadMessageIds);
            }
            
            // 使用最後一條訊息的時間戳作為 lastMessageId
            if (result.messages.length > 0) {
              const lastMsg = result.messages[result.messages.length - 1];
              lastMessageId = lastMsg.timestamp ? new Date(lastMsg.timestamp).getTime() : Date.now();
            }
            console.log('最後訊息時間戳:', lastMessageId);
          } else {
            console.log('沒有訊息或訊息陣列為空');
            // 即使沒有訊息，也調用 displayGroupMessages 以顯示空狀態
            displayGroupMessages([], append);
          }
        } else {
          console.error('群組訊息載入失敗:', result.error);
          alert('載入訊息失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('載入群組聊天記錄失敗:', error);
        alert('載入群組訊息失敗: ' + (error.message || '未知錯誤'));
      }
    }
    
    // 顯示群組訊息
    function displayGroupMessages(messages, append = false) {
      const chatMessages = document.getElementById('chatMessages');
      
      if (!chatMessages) return;
      
      // 如果不是追加模式，清空聊天區域
      if (!append) {
        chatMessages.innerHTML = '';
      }
      
      // 檢查是否有訊息
      if (!messages || messages.length === 0) {
        if (!append) {
          // 顯示無訊息的提示
          const noMessageDiv = document.createElement('div');
          noMessageDiv.style.cssText = `
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 14px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px;
            border: 1px dashed #ddd;
          `;
          noMessageDiv.innerHTML = `
            <div style="font-size: 24px; margin-bottom: 10px;">👥</div>
            <div>群組還沒有任何訊息</div>
            <div style="font-size: 12px; margin-top: 5px;">開始發送訊息吧！</div>
          `;
          chatMessages.appendChild(noMessageDiv);
        }
        return;
      }
      
      // 獲取已存在的訊息ID，避免重複顯示
      const existingMessageIds = new Set();
      if (append) {
        chatMessages.querySelectorAll('[data-message-id]').forEach(el => {
          const id = el.dataset.messageId;
          if (id) existingMessageIds.add(id.toString());
        });
      }
      
      let hasNewMessages = false;
      messages.forEach(message => {
        const messageId = message.id.toString();
        
        // 如果是追加模式且訊息已存在，跳過
        if (append && existingMessageIds.has(messageId)) {
          return;
        }
        
        hasNewMessages = true;
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user === username ? 'sent' : 'received'}`;
        messageDiv.dataset.messageId = messageId;
        
        const isReceived = message.from_user !== username;
        
        // 如果是對方發送的訊息，添加頭像
        if (isReceived) {
          // 處理頭像路徑
          let avatarSrc = '';
          if (message.profile_picture) {
            if (message.profile_picture.startsWith('http://') || message.profile_picture.startsWith('https://')) {
              // 完整 URL（如 Google 頭像）
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('/')) {
              // 絕對路徑
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('uploads/')) {
              // 上傳的頭像，使用 ../uploads/（包括 uploads/avatars/）
              avatarSrc = '../' + message.profile_picture;
            } else if (message.profile_picture.includes('avatars/')) {
              // 如果包含 avatars/，確保路徑正確
              if (message.profile_picture.startsWith('avatars/')) {
                avatarSrc = '../uploads/' + message.profile_picture;
              } else {
                avatarSrc = '../' + message.profile_picture;
              }
            } else {
              // share 目錄的檔案，使用 ../share/
              avatarSrc = '../share/' + message.profile_picture;
            }
          } else {
            // 預設頭像
            avatarSrc = '../share/EIdROxGXsAE_LSs.jpg';
          }
          
          // 創建頭像元素
          const avatarDiv = document.createElement('div');
          avatarDiv.className = 'message-avatar';
          avatarDiv.innerHTML = `<img src="${avatarSrc}" alt="${escapeHtml(message.from_user || '未知用戶')}" class="avatar-img">`;
          messageDiv.appendChild(avatarDiv);
        }
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // 已讀狀態顯示（類似 LINE）
        // 排除發送者自己，所以顯示的已讀人數 = read_count - 1
        const readCount = parseInt(message.read_count || 0);
        const displayReadCount = message.from_user === username ? Math.max(0, readCount - 1) : readCount;
        let readStatusHtml = '';
        
        // 只有自己發送的訊息才顯示已讀狀態，且已讀人數大於0（排除自己）
        if (message.from_user === username && displayReadCount > 0) {
          readStatusHtml = `
            <div class="read-status-group" style="font-size: 11px; color: #999; margin-top: 4px; text-align: right;">
              <span class="read-count-text">${displayReadCount} 已讀</span>
            </div>
          `;
        }
        
        contentDiv.innerHTML = `
          <div style="font-size: 12px; color: #000000; margin-bottom: 4px;">
            ${escapeHtml(message.from_user || '未知用戶')} (${escapeHtml(message.role || '用戶')})
          </div>
          <div style="color: #000000;">${escapeHtml(message.message || '')}</div>
          ${readStatusHtml}
        `;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date(message.timestamp || new Date()).toLocaleString();
        
        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(contentDiv);
        chatMessages.appendChild(messageDiv);
        
      });
      
      // 如果有新訊息，滾動到底部
      if (hasNewMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    }
    
    // 標記單條群組訊息為已讀
    async function markGroupMessageAsRead(messageId) {
      try {
        const response = await fetch('group_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=mark_message_as_read&message_id=${messageId}`
        });
        
        const result = await response.json();
        if (result.success) {
          // 更新顯示的已讀狀態
          updateMessageReadStatus(messageId, result.read_count, result.total_members);
        }
      } catch (error) {
        console.error('標記訊息為已讀失敗:', error);
      }
    }
    
    // 批量標記群組訊息為已讀
    async function markGroupMessagesAsRead(messageIds) {
      if (!messageIds || messageIds.length === 0) return;
      
      try {
        const response = await fetch('group_management.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=mark_messages_as_read&message_ids=${JSON.stringify(messageIds)}`
        });
        
        const result = await response.json();
        if (result.success && result.updated_messages) {
          // 更新所有已讀訊息的顯示狀態
          result.updated_messages.forEach(msg => {
            updateMessageReadStatus(msg.message_id, msg.read_count, msg.total_members);
          });
        }
      } catch (error) {
        console.error('批量標記訊息為已讀失敗:', error);
      }
    }
    
    // 更新訊息顯示的已讀狀態
    function updateMessageReadStatus(messageId, readCount, totalMembers) {
      const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
      if (!messageDiv) return;
      
      const contentDiv = messageDiv.querySelector('.message-content');
      if (!contentDiv) return;
      
      // 獲取訊息發送者
      const messageFromUser = messageDiv.querySelector('.message-content > div:first-child')?.textContent?.split('(')[0]?.trim();
      // 排除發送者自己，所以顯示的已讀人數 = readCount - 1
      const displayReadCount = messageFromUser === username ? Math.max(0, readCount - 1) : readCount;
      
      // 如果已讀人數為0（排除發送者後），隱藏已讀狀態
      if (displayReadCount <= 0) {
        const readStatusDiv = contentDiv.querySelector('.read-status-group');
        if (readStatusDiv) {
          readStatusDiv.remove();
        }
        return;
      }
      
      // 只有自己發送的訊息才顯示已讀狀態
      if (messageFromUser !== username) {
        return;
      }
      
      // 查找或創建已讀狀態元素
      let readStatusDiv = contentDiv.querySelector('.read-status-group');
      if (!readStatusDiv) {
        readStatusDiv = document.createElement('div');
        readStatusDiv.className = 'read-status-group';
        readStatusDiv.style.cssText = 'font-size: 11px; color: #999; margin-top: 4px; text-align: right;';
        const timeDiv = contentDiv.querySelector('.message-time');
        if (timeDiv) {
          contentDiv.insertBefore(readStatusDiv, timeDiv);
        } else {
          contentDiv.appendChild(readStatusDiv);
        }
      }
      
      readStatusDiv.innerHTML = `
        <span class="read-count-text">${displayReadCount} 已讀</span>
      `;
    }
    
    // 更新已存在訊息的已讀狀態
    function updateExistingMessagesReadStatus(messages) {
      messages.forEach(message => {
        // 只更新自己發送的訊息的已讀狀態
        if (message.from_user === username && message.id) {
          const readCount = parseInt(message.read_count || 0);
          const totalMembers = parseInt(message.total_members || 0);
          // 只顯示已讀人數，不顯示總成員數
          updateMessageReadStatus(message.id, readCount, totalMembers);
        }
      });
    }
    
    // 建立群組按鈕事件
    const createGroupBtn = document.getElementById('createGroupBtn');
    if (createGroupBtn) {
      createGroupBtn.addEventListener('click', function() {
        showCreateGroupModal();
      });
    }
    
    // 顯示建立群組模態框
    function showCreateGroupModal() {
      const modal = document.createElement('div');
      modal.id = 'createGroupModal';
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.2s ease-out;
      `;
      
      // 添加動畫樣式
      if (!document.getElementById('modalAnimationStyle')) {
        const style = document.createElement('style');
        style.id = 'modalAnimationStyle';
        style.textContent = `
          @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
          }
        `;
        document.head.appendChild(style);
      }
      
      modal.innerHTML = `
        <div style="background: white; padding: 0; border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease-out;">
          <!-- 標題區域 -->
          <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px 28px; color: white;">
            <h3 style="margin: 0; font-size: 22px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
              <span style="font-size: 24px;">👥</span>
              <span>建立群組</span>
            </h3>
          </div>
          
          <!-- 內容區域 -->
          <div style="padding: 28px;">
            <!-- 群組名稱 -->
            <div style="margin-bottom: 24px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">群組名稱</label>
              <input type="text" id="groupName" placeholder="請輸入群組名稱..." 
                     style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: all 0.3s ease; outline: none;"
                     onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                     onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
            </div>
            
            <!-- 搜尋成員 -->
            <div style="margin-bottom: 24px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">搜尋成員</label>
              <div style="position: relative;">
                <input type="text" id="memberSearch" placeholder="搜尋姓名、科系或班級..." 
                       style="width: 100%; padding: 12px 16px 12px 44px; border: 2px solid #e0e0e0; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: all 0.3s ease; outline: none;"
                       onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;">🔍</span>
              </div>
            </div>
            
            <!-- 選擇成員 -->
            <div style="margin-bottom: 24px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">選擇成員</label>
              <div id="memberSelection" style="max-height: 350px; overflow-y: auto; border: 2px solid #e0e0e0; padding: 12px; margin-top: 8px; border-radius: 8px; background: #fafafa;">
                ${generateMemberCheckboxes()}
              </div>
              <div style="margin-top: 12px; font-size: 13px; color: #666; display: flex; align-items: center; gap: 6px;">
                <span style="color: #667eea; font-weight: 600;">已選擇 <span id="selectedMemberCount" style="color: #667eea;">0</span> 位成員</span>
              </div>
            </div>
            
            <!-- 按鈕區域 -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; padding-top: 24px; border-top: 1px solid #e0e0e0;">
              <button onclick="closeCreateGroupModal()" 
                      style="padding: 12px 24px; background: #f5f5f5; color: #666; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s ease;"
                      onmouseover="this.style.background='#e0e0e0'"
                      onmouseout="this.style.background='#f5f5f5'">取消</button>
              <button onclick="createGroup()" 
                      style="padding: 12px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);"
                      onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)'"
                      onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.3)'">建立</button>
            </div>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // 設置成員搜尋功能
      const memberSearchInput = document.getElementById('memberSearch');
      if (memberSearchInput) {
        memberSearchInput.addEventListener('input', function() {
          filterMemberCheckboxes(this.value.toLowerCase().trim());
        });
      }
      
      // 設置成員選擇計數更新
      updateSelectedMemberCount();
    }
    
    // 生成成員選擇框
    function generateMemberCheckboxes() {
      const contacts = allContacts.filter(c => c.username !== username); // 排除自己
      let html = '';
      
      if (contacts.length === 0) {
        return '<div style="color: #999; text-align: center; padding: 40px 20px;"><div style="font-size: 48px; margin-bottom: 12px;">👤</div><div style="font-size: 14px;">暫無聯絡人可選擇</div></div>';
      }
      
      contacts.forEach(contact => {
        const displayName = contact.name || contact.username;
        const role = contact.contact_type || '';
        const department = contact.department || '';
        const grade = contact.grade || '';
        const className = contact.class_name || '';
        const additionalInfo = grade ? ` - ${grade}${className ? ' ' + className : ''}` : '';
        const searchableText = `${displayName} ${role} ${department} ${grade} ${className}`.toLowerCase();
        
        html += `
          <div class="member-item" data-search-text="${searchableText}" 
               style="margin: 6px 0; padding: 12px; border: 2px solid #e8e8e8; border-radius: 8px; display: flex; align-items: center; background: white; transition: all 0.2s ease; cursor: pointer;"
               onmouseover="this.style.borderColor='#667eea'; this.style.backgroundColor='#f8f9ff'; this.style.transform='translateX(4px)'"
               onmouseout="this.style.borderColor='#e8e8e8'; this.style.backgroundColor='white'; this.style.transform='translateX(0)'"
               onclick="document.getElementById('member_${contact.username}').click()">
            <input type="checkbox" id="member_${contact.username}" value="${contact.username}" data-role="${role}" 
                   style="margin-right: 12px; cursor: pointer; width: 18px; height: 18px; accent-color: #667eea;" 
                   onchange="updateSelectedMemberCount(); this.closest('.member-item').style.borderColor = this.checked ? '#667eea' : '#e8e8e8'; this.closest('.member-item').style.backgroundColor = this.checked ? '#f8f9ff' : 'white';">
            <label for="member_${contact.username}" style="flex: 1; cursor: pointer; margin: 0;">
              <div style="font-weight: 600; color: #333; margin-bottom: 4px; font-size: 14px;">${escapeHtml(displayName)}</div>
              <div style="font-size: 12px; color: #888;">${escapeHtml(role)}${department ? ' - ' + escapeHtml(department) : ''}${additionalInfo}</div>
            </label>
          </div>
        `;
      });
      
      return html;
    }
    
    // 過濾成員選擇框
    function filterMemberCheckboxes(searchTerm) {
      const memberItems = document.querySelectorAll('#memberSelection .member-item');
      let visibleCount = 0;
      
      memberItems.forEach(item => {
        const searchText = item.dataset.searchText || '';
        if (searchTerm === '' || searchText.includes(searchTerm)) {
          item.style.display = 'flex';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });
      
      // 如果沒有可見項目，顯示提示
      if (visibleCount === 0 && searchTerm !== '') {
        const memberSelection = document.getElementById('memberSelection');
        if (memberSelection && !memberSelection.querySelector('.no-results')) {
          const noResults = document.createElement('div');
          noResults.className = 'no-results';
          noResults.style.cssText = 'text-align: center; padding: 40px 20px; color: #999; font-size: 14px;';
          noResults.innerHTML = '<div style="font-size: 48px; margin-bottom: 12px;">🔍</div><div>找不到符合條件的成員</div>';
          memberSelection.appendChild(noResults);
        }
      } else {
        const noResults = document.querySelector('#memberSelection .no-results');
        if (noResults) {
          noResults.remove();
        }
      }
    }
    
    // 更新已選擇成員計數
    function updateSelectedMemberCount() {
      const selectedCount = document.querySelectorAll('#memberSelection input[type="checkbox"]:checked').length;
      const countElement = document.getElementById('selectedMemberCount');
      if (countElement) {
        countElement.textContent = selectedCount;
      }
    }
    
    // 關閉建立群組模態框
    function closeCreateGroupModal() {
      const modal = document.getElementById('createGroupModal');
      if (modal) {
        modal.remove();
      } else {
        // 備用方案：查找包含建立群組的模態框
        const modals = document.querySelectorAll('div[style*="position: fixed"]');
        modals.forEach(m => {
          if (m.querySelector('#groupName')) {
            m.remove();
          }
        });
      }
    }
    
    // 顯示通知設定
    function showNotificationSettings() {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
      `;
      
      modal.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 8px; width: 400px; max-height: 80vh; overflow-y: auto;">
          <h3>🔔 通知設定</h3>
          
          <div style="margin: 15px 0;">
            <label style="display: flex; align-items: center; margin-bottom: 10px;">
              <input type="checkbox" id="chatNotifications" checked style="margin-right: 10px;">
              聊天訊息通知
            </label>
            
            <label style="display: flex; align-items: center; margin-bottom: 10px;">
              <input type="checkbox" id="groupNotifications" checked style="margin-right: 10px;">
              群組訊息通知
            </label>
            
            <label style="display: flex; align-items: center; margin-bottom: 15px;">
              <input type="checkbox" id="systemNotifications" checked style="margin-right: 10px;">
              系統通知
            </label>
          </div>
          
          <div style="margin: 15px 0;">
            <label>安靜時間：</label>
            <div style="display: flex; align-items: center; margin-top: 5px;">
              <input type="time" id="quietStart" value="22:00" style="margin-right: 10px;">
              <span>到</span>
              <input type="time" id="quietEnd" value="08:00" style="margin-left: 10px;">
            </div>
          </div>
          
          <div style="margin: 15px 0;">
            <label>通知權限狀態：</label>
            <div id="notificationStatus" style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
              ${Notification.permission === 'granted' ? 
                '<span style="color: green;">✅ 已啟用推播通知</span>' : 
                '<span style="color: orange;">⚠️ 推播通知未啟用</span>'}
            </div>
          </div>
          
          <div style="text-align: right; margin-top: 20px;">
            <button onclick="closeNotificationSettings()" style="margin-right: 10px; padding: 8px 16px;">取消</button>
            <button onclick="saveNotificationSettings()" style="padding: 8px 16px; background: #2196F3; color: white; border: none; border-radius: 4px;">儲存</button>
          </div>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // 載入現有設定
      loadNotificationSettings();
    }
    
    // 載入通知設定
    async function loadNotificationSettings() {
      try {
        const response = await fetch('simple_fcm_api.php?action=get_notification_settings&username=' + username);
        const result = await response.json();
        
        if (result.success && result.settings) {
          const settings = result.settings;
          document.getElementById('chatNotifications').checked = settings.chat_notifications;
          document.getElementById('groupNotifications').checked = settings.group_notifications;
          document.getElementById('systemNotifications').checked = settings.system_notifications;
          document.getElementById('quietStart').value = settings.quiet_hours_start || '22:00';
          document.getElementById('quietEnd').value = settings.quiet_hours_end || '08:00';
        }
      } catch (error) {
        console.error('載入通知設定失敗:', error);
      }
    }
    
    // 儲存通知設定
    async function saveNotificationSettings() {
      try {
        const settings = {
          chat_notifications: document.getElementById('chatNotifications').checked,
          group_notifications: document.getElementById('groupNotifications').checked,
          system_notifications: document.getElementById('systemNotifications').checked,
          quiet_hours_start: document.getElementById('quietStart').value,
          quiet_hours_end: document.getElementById('quietEnd').value
        };
        
        const response = await fetch('simple_fcm_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'update_notification_settings',
            username: username,
            settings: settings
          })
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert('通知設定已儲存！');
          closeNotificationSettings();
        } else {
          alert('儲存失敗: ' + result.error);
        }
        
      } catch (error) {
        console.error('儲存通知設定失敗:', error);
        alert('儲存失敗: ' + error.message);
      }
    }
    
    // 關閉通知設定
    function closeNotificationSettings() {
      const modal = document.querySelector('div[style*="position: fixed"]');
      if (modal) {
        modal.remove();
      }
    }
    
    // 應用配色方案
    function applyColorScheme() {
      // 從PHP獲取配色方案
      const colorScheme = '<?php echo $_SESSION["chat_color_scheme"] ?? "white"; ?>';
      
      // 移除現有的配色方案類
      document.body.classList.remove('color-scheme-white', 'color-scheme-warm', 'color-scheme-mint', 'color-scheme-pink', 'color-scheme-gray', 'color-scheme-blue');
      
      // 添加新的配色方案類
      document.body.classList.add(`color-scheme-${colorScheme}`);
    }
    
    // 建立群組
    async function createGroup() {
      const groupNameInput = document.getElementById('groupName');
      if (!groupNameInput) {
        alert('找不到群組名稱輸入框');
        return;
      }
      
      const groupName = groupNameInput.value.trim();
      if (!groupName) {
        alert('請輸入群組名稱');
        groupNameInput.focus();
        return;
      }
      
      const selectedMembers = [];
      document.querySelectorAll('#memberSelection input[type="checkbox"]:checked').forEach(checkbox => {
        selectedMembers.push({
          username: checkbox.value,
          role: checkbox.dataset.role
        });
      });
      
      if (selectedMembers.length === 0) {
        alert('請至少選擇一位成員');
        return;
      }
      
      try {
        // 獲取當前用戶的科系（如果有的話）
        let department = '';
        const currentUserContact = allContacts.find(c => c.username === username);
        if (currentUserContact && currentUserContact.department) {
          department = currentUserContact.department;
        }
        
        const formData = new FormData();
        formData.append('action', 'create_group');
        formData.append('group_name', groupName);
        formData.append('created_by', username);
        formData.append('department', department);
        formData.append('members', JSON.stringify(selectedMembers));
        
        const response = await fetch('group_management.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          alert('群組建立成功！');
          closeCreateGroupModal();
          loadGroups(); // 重新載入群組列表
        } else {
          console.error('建立群組失敗:', result);
          alert('建立群組失敗: ' + (result.error || '未知錯誤'));
        }
      } catch (error) {
        console.error('建立群組失敗:', error);
        alert('建立群組失敗: ' + error.message);
      }
    }
    
    // 私聊功能已整合到 selectContact 函數中，不再需要單獨的事件監聽器

    // 載入聊天記錄
    async function loadChatHistory() {
      const chatMessages = document.getElementById('chatMessages');
      const cacheKey = `${username}-${currentUserId}`;
      
      // 檢查快取
      if (messageCache.has(cacheKey)) {
        const cachedData = messageCache.get(cacheKey);
        displayMessages(cachedData.messages);
        lastMessageId = cachedData.lastMessageId;
        console.log('使用快取聊天記錄:', cacheKey);
        return;
      }
      
      // 顯示載入指示器
      chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">載入中...</div>';
      
      try {
        const startTime = performance.now();
        const response = await fetch('load_private_messages.php?from=' + username + '&to=' + currentUserId);
        
        const result = await response.json();
        
        if (result.success && result.messages) {
          displayMessages(result.messages);
          
          if (result.messages.length > 0) {
            lastMessageId = Math.max(...result.messages.map(m => m.id));
          }
          
          // 儲存到快取
          messageCache.set(cacheKey, {
            messages: result.messages,
            lastMessageId: lastMessageId,
            timestamp: Date.now()
          });
          console.log('儲存快取:', cacheKey, '訊息數量:', result.messages.length);
          
          const loadTime = performance.now() - startTime;
          console.log(`聊天記錄載入完成，耗時: ${loadTime.toFixed(2)}ms`);
        } else {
          console.error('載入聊天記錄失敗:', result.error || result);
          if (chatMessages) {
          chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗: ' + (result.error || '未知錯誤') + '</div>';
          }
        }
      } catch (error) {
        console.error('載入聊天記錄失敗:', error);
        if (chatMessages) {
          chatMessages.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">載入失敗</div>';
        }
      }
    }
    
    // 顯示訊息
    function displayMessages(messages) {
      const chatMessages = document.getElementById('chatMessages');
      
      // 清空聊天區域
      chatMessages.innerHTML = '';
      
      // 檢查是否有訊息
      if (!messages || messages.length === 0) {
        // 顯示無訊息的提示
        const noMessageDiv = document.createElement('div');
        noMessageDiv.style.cssText = `
          text-align: center;
          padding: 40px 20px;
          color: #999;
          font-size: 14px;
          background: #f8f9fa;
          border-radius: 8px;
          margin: 20px;
          border: 1px dashed #ddd;
        `;
        noMessageDiv.innerHTML = `
          <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
          <div>還沒有任何訊息</div>
          <div style="font-size: 12px; margin-top: 5px;">開始發送訊息吧！</div>
        `;
        chatMessages.appendChild(noMessageDiv);
        return;
      }
      
      // 使用 DocumentFragment 優化DOM操作
      const fragment = document.createDocumentFragment();
      
      messages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user === username ? 'sent' : 'received'}`;
        messageDiv.dataset.messageId = message.id;
        
        const isReceived = message.from_user !== username;
        
        // 如果是對方發送的訊息，添加頭像
        if (isReceived) {
          // 處理頭像路徑
          let avatarSrc = '';
          if (message.profile_picture) {
            if (message.profile_picture.startsWith('http://') || message.profile_picture.startsWith('https://')) {
              // 完整 URL（如 Google 頭像）
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('/')) {
              // 絕對路徑
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('uploads/')) {
              // 上傳的頭像，使用 ../uploads/（包括 uploads/avatars/）
              avatarSrc = '../' + message.profile_picture;
            } else if (message.profile_picture.includes('avatars/')) {
              // 如果包含 avatars/，確保路徑正確
              if (message.profile_picture.startsWith('avatars/')) {
                avatarSrc = '../uploads/' + message.profile_picture;
              } else {
                avatarSrc = '../' + message.profile_picture;
              }
            } else {
              // share 目錄的檔案，使用 ../share/
              avatarSrc = '../share/' + message.profile_picture;
            }
          } else {
            // 預設頭像
            avatarSrc = '../share/EIdROxGXsAE_LSs.jpg';
          }
          
          // 創建頭像元素
          const avatarDiv = document.createElement('div');
          avatarDiv.className = 'message-avatar';
          avatarDiv.innerHTML = `<img src="${avatarSrc}" alt="${escapeHtml(message.from_user || '未知用戶')}" class="avatar-img">`;
          messageDiv.appendChild(avatarDiv);
        }
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // 檢查訊息是否包含圖片URL，並正確處理
        let messageText = message.message || '';
        
        // 如果訊息是圖片URL，檢查並修正路徑
        if (messageText && (messageText.includes('.jpg') || messageText.includes('.png') || messageText.includes('.gif') || messageText.includes('.jpeg') || messageText.includes('.webp'))) {
          let imageUrl = messageText.trim();
          
          // 處理各種圖片路徑格式
          // 優先處理：如果已經是完整 URL，直接使用
          if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
            // 已經是完整 URL，不需要處理
          } 
          // 處理絕對路徑（以 / 開頭）
          else if (imageUrl.startsWith('/')) {
            // 如果是 /Topics-frontend/frontend/chat/share/ 格式，改為 /Topics-frontend/frontend/share/
            if (imageUrl.includes('/chat/share/')) {
              imageUrl = imageUrl.replace('/chat/share/', '/share/');
            } 
            // 如果是 /Topics-frontend/frontend/chat/share/filename.jpg 格式
            else if (imageUrl.includes('Topics-frontend/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('Topics-frontend/frontend/chat/share/', 'Topics-frontend/frontend/share/');
            }
            // 如果是 /frontend/chat/share/ 格式
            else if (imageUrl.includes('/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('/frontend/chat/share/', '/frontend/share/');
            }
          }
          // 處理相對路徑
          else {
            // 提取文件名（無論路徑格式如何）
            const fileName = imageUrl.split('/').pop();
            
            // 處理各種可能的錯誤路徑格式
            if (imageUrl.includes('chat/share/') || 
                imageUrl.includes('/frontend/chat/share/') || 
                imageUrl.includes('Topics-frontend/') ||
                imageUrl.includes('frontend/chat/share/')) {
              // 從任何包含 chat/share 的路徑中提取文件名
              imageUrl = '../share/' + fileName;
            } else if (imageUrl.includes('/share/')) {
              // 處理包含 "/share/" 但不是 chat/share 的路徑
              const shareIndex = imageUrl.indexOf('/share/');
              imageUrl = '../share/' + imageUrl.substring(shareIndex + 7);
            } else if (imageUrl.startsWith('share/')) {
              // 處理 "share/filename.jpg" 格式
              imageUrl = '../share/' + imageUrl.substring(6);
            } else if (!imageUrl.includes('/')) {
              // 如果只是文件名（如 "EIdROxGXsAE_LSs.jpg"），直接加上 share 路徑
              imageUrl = '../share/' + imageUrl;
            } else {
              // 其他情況，嘗試提取文件名
                imageUrl = '../share/' + fileName;
            }
          }
          
          // 創建圖片元素（無論路徑格式如何）
          const img = document.createElement('img');
          img.src = imageUrl;
          img.alt = '分享的圖片';
          img.style.cssText = 'max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 5px; cursor: pointer; display: block; object-fit: contain;';
          img.onclick = function() {
            // 點擊圖片可以查看大圖
            window.open(this.src, '_blank');
          };
          contentDiv.appendChild(img);
        } else {
          // 普通文字訊息
          contentDiv.textContent = messageText;
        }
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date(message.timestamp).toLocaleString();
        
        // 添加已讀狀態
        if (message.from_user === username) {
          // 自己發送的訊息，顯示已讀狀態
          const readStatusDiv = document.createElement('div');
          readStatusDiv.className = 'read-status';
          readStatusDiv.style.cssText = `
            font-size: 11px; 
            margin-top: 4px; 
            text-align: right;
            font-weight: 500;
          `;
          
          if (message.is_read && message.read_at) {
            readStatusDiv.innerHTML = `
              <span class="read-indicator read">✓ 已讀</span>
              <span class="read-time">${new Date(message.read_at).toLocaleTimeString()}</span>
            `;
          } else {
            readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
          }
          
          contentDiv.appendChild(readStatusDiv);
        } else {
          // 接收的訊息，標記為已讀
          markMessageAsRead(message.id);
        }
        
        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(contentDiv);
        fragment.appendChild(messageDiv);
      });
      
      // 一次性更新DOM
      chatMessages.appendChild(fragment);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // 追加新訊息（用於實時同步，不清空現有訊息）
    function appendNewMessages(messages) {
      const chatMessages = document.getElementById('chatMessages');
      
      if (!chatMessages) return;
      
      // 獲取已存在的訊息ID，避免重複顯示
      const existingMessageIds = new Set();
      chatMessages.querySelectorAll('[data-message-id]').forEach(el => {
        const id = el.dataset.messageId;
        if (id) existingMessageIds.add(id.toString());
      });
      
      // 移除"沒有訊息"的提示
      const noMessageDiv = chatMessages.querySelector('div[style*="text-align: center"]');
      if (noMessageDiv && noMessageDiv.textContent.includes('還沒有任何訊息')) {
        noMessageDiv.remove();
      }
      
      // 使用 DocumentFragment 優化DOM操作
      const fragment = document.createDocumentFragment();
      let hasNewMessages = false;
      
      messages.forEach(message => {
        const messageId = message.id.toString();
        
        // 如果訊息已存在，跳過
        if (existingMessageIds.has(messageId)) {
          return;
        }
        
        hasNewMessages = true;
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.from_user === username ? 'sent' : 'received'}`;
        messageDiv.dataset.messageId = messageId;
        
        const isReceived = message.from_user !== username;
        
        // 如果是對方發送的訊息，添加頭像
        if (isReceived) {
          // 處理頭像路徑
          let avatarSrc = '';
          if (message.profile_picture) {
            if (message.profile_picture.startsWith('http://') || message.profile_picture.startsWith('https://')) {
              // 完整 URL（如 Google 頭像）
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('/')) {
              // 絕對路徑
              avatarSrc = message.profile_picture;
            } else if (message.profile_picture.startsWith('uploads/')) {
              // 上傳的頭像，使用 ../uploads/（包括 uploads/avatars/）
              avatarSrc = '../' + message.profile_picture;
            } else if (message.profile_picture.includes('avatars/')) {
              // 如果包含 avatars/，確保路徑正確
              if (message.profile_picture.startsWith('avatars/')) {
                avatarSrc = '../uploads/' + message.profile_picture;
              } else {
                avatarSrc = '../' + message.profile_picture;
              }
            } else {
              // share 目錄的檔案，使用 ../share/
              avatarSrc = '../share/' + message.profile_picture;
            }
          } else {
            // 預設頭像
            avatarSrc = '../share/EIdROxGXsAE_LSs.jpg';
          }
          
          // 創建頭像元素
          const avatarDiv = document.createElement('div');
          avatarDiv.className = 'message-avatar';
          avatarDiv.innerHTML = `<img src="${avatarSrc}" alt="${escapeHtml(message.from_user || '未知用戶')}" class="avatar-img">`;
          messageDiv.appendChild(avatarDiv);
        }
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        // 檢查訊息是否包含圖片URL，並正確處理
        let messageText = message.message || '';
        
        // 如果訊息是圖片URL，檢查並修正路徑
        if (messageText && (messageText.includes('.jpg') || messageText.includes('.png') || messageText.includes('.gif') || messageText.includes('.jpeg') || messageText.includes('.webp'))) {
          let imageUrl = messageText.trim();
          
          // 處理各種圖片路徑格式（與 displayMessages 相同的邏輯）
          if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
            // 已經是完整 URL
          } else if (imageUrl.startsWith('/')) {
            if (imageUrl.includes('/chat/share/')) {
              imageUrl = imageUrl.replace('/chat/share/', '/share/');
            } else if (imageUrl.includes('Topics-frontend/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('Topics-frontend/frontend/chat/share/', 'Topics-frontend/frontend/share/');
            } else if (imageUrl.includes('/frontend/chat/share/')) {
              imageUrl = imageUrl.replace('/frontend/chat/share/', '/frontend/share/');
            }
          } else {
            const fileName = imageUrl.split('/').pop();
            if (imageUrl.includes('chat/share/') || 
                imageUrl.includes('/frontend/chat/share/') || 
                imageUrl.includes('Topics-frontend/') ||
                imageUrl.includes('frontend/chat/share/')) {
              imageUrl = '../share/' + fileName;
            } else if (imageUrl.includes('/share/')) {
              const shareIndex = imageUrl.indexOf('/share/');
              imageUrl = '../share/' + imageUrl.substring(shareIndex + 7);
            } else if (imageUrl.startsWith('share/')) {
              imageUrl = '../share/' + imageUrl.substring(6);
            } else if (!imageUrl.includes('/')) {
              imageUrl = '../share/' + imageUrl;
            } else {
              imageUrl = '../share/' + fileName;
            }
          }
          
          const img = document.createElement('img');
          img.src = imageUrl;
          img.alt = '分享的圖片';
          img.style.cssText = 'max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 5px; cursor: pointer; display: block; object-fit: contain;';
          img.onclick = function() {
            window.open(this.src, '_blank');
          };
          contentDiv.appendChild(img);
        } else {
          // 普通文字訊息
          contentDiv.textContent = messageText;
        }
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date(message.timestamp).toLocaleString();
        
        // 添加已讀狀態
        if (message.from_user === username) {
          const readStatusDiv = document.createElement('div');
          readStatusDiv.className = 'read-status';
          readStatusDiv.style.cssText = `
            font-size: 11px; 
            margin-top: 4px; 
            text-align: right;
            font-weight: 500;
          `;
          
          if (message.is_read && message.read_at) {
            readStatusDiv.innerHTML = `
              <span class="read-indicator read">✓ 已讀</span>
              <span class="read-time">${new Date(message.read_at).toLocaleTimeString()}</span>
            `;
          } else {
            readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
          }
          
          contentDiv.appendChild(readStatusDiv);
        } else {
          // 接收的訊息，標記為已讀
          markMessageAsRead(message.id);
        }
        
        contentDiv.appendChild(timeDiv);
        messageDiv.appendChild(contentDiv);
        fragment.appendChild(messageDiv);
      });
      
      // 如果有新訊息，追加到DOM並滾動到底部
      if (hasNewMessages) {
        chatMessages.appendChild(fragment);
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    }
    
    // 更新已存在訊息的已讀狀態（用於實時同步已讀狀態）
    function updateReadStatusForExistingMessages(messages) {
      if (!messages || messages.length === 0) return;
      
      const chatMessages = document.getElementById('chatMessages');
      if (!chatMessages) return;
      
      // 遍歷所有消息，更新已存在消息的已讀狀態
      messages.forEach(message => {
        const messageId = message.id.toString();
        const messageElement = chatMessages.querySelector(`[data-message-id="${messageId}"]`);
        
        if (messageElement) {
          // 只更新自己發送的消息的已讀狀態
          if (message.from_user === username) {
            const readStatusDiv = messageElement.querySelector('.read-status');
            
            if (readStatusDiv) {
              // 檢查已讀狀態是否改變
              const currentReadStatus = readStatusDiv.querySelector('.read-indicator');
              const isCurrentlyRead = currentReadStatus && currentReadStatus.classList.contains('read');
              
              // 檢查消息是否應該顯示為已讀
              // is_read 可能是 1, true, '1' 等格式
              const isRead = message.is_read === 1 || message.is_read === true || message.is_read === '1' || message.is_read === 1;
              const hasReadAt = message.read_at && message.read_at !== null && message.read_at !== '';
              const shouldBeRead = isRead && hasReadAt;
              
              // 如果狀態改變了，更新顯示
              if (!isCurrentlyRead && shouldBeRead) {
                readStatusDiv.innerHTML = `
                  <span class="read-indicator read">✓ 已讀</span>
                  <span class="read-time">${new Date(message.read_at).toLocaleTimeString()}</span>
                `;
                console.log('已更新訊息已讀狀態: 未讀 -> 已讀', messageId);
              } else if (isCurrentlyRead && !shouldBeRead) {
                readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
                console.log('已更新訊息已讀狀態: 已讀 -> 未讀', messageId);
              }
            } else {
              // 如果沒有已讀狀態元素，但消息應該顯示已讀狀態，則創建它
              if (message.is_read || message.read_at) {
                const contentDiv = messageElement.querySelector('.message-content');
                if (contentDiv) {
                  const readStatusDiv = document.createElement('div');
                  readStatusDiv.className = 'read-status';
                  readStatusDiv.style.cssText = `
                    font-size: 11px; 
                    margin-top: 4px; 
                    text-align: right;
                    font-weight: 500;
                  `;
                  
                  const isRead = message.is_read === 1 || message.is_read === true || message.is_read === '1' || message.is_read === 1;
                  const hasReadAt = message.read_at && message.read_at !== null && message.read_at !== '';
                  
                  if (isRead && hasReadAt) {
                    readStatusDiv.innerHTML = `
                      <span class="read-indicator read">✓ 已讀</span>
                      <span class="read-time">${new Date(message.read_at).toLocaleTimeString()}</span>
                    `;
                  } else {
                    readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
                  }
                  
                  // 插入到時間元素之前
                  const timeDiv = contentDiv.querySelector('.message-time');
                  if (timeDiv) {
                    contentDiv.insertBefore(readStatusDiv, timeDiv);
                  } else {
                    contentDiv.appendChild(readStatusDiv);
                  }
                }
              }
            }
          }
        }
      });
    }
    
    // 發送訊息
    async function sendMessage() {
      const input = document.getElementById('messageInput');
      const message = input.value.trim();
      
      if (!message) return;
      
      const sendButton = document.querySelector('.chat-input button:not(#voiceRecordBtn)');
      if (sendButton) {
        sendButton.disabled = true;
      }
      
      try {
        if (currentChatType === 'group') {
          console.log('發送群組訊息:', {
            groupId: currentGroupId,
            fromUser: username,
            message: message,
            role: role
          });
          
          // 發送群組訊息
          const formData = new FormData();
          formData.append('action', 'send_group_message');
          formData.append('group_id', currentGroupId);
          formData.append('from_user', username);
          formData.append('message', message);
          formData.append('role', role);
          
          console.log('發送請求到:', 'group_management.php');
          
          const response = await fetch('group_management.php', {
            method: 'POST',
            body: formData
          });
          
          console.log('收到響應:', response);
          
          const result = await response.json();
          console.log('響應結果:', result);
          
          if (result.success) {
            input.value = '';
            updateSendButtonState(); // 清空後更新按鈕狀態
            console.log('群組訊息發送成功');
            
            // 重新載入訊息列表（使用追加模式，避免重複）
            // 延遲一點時間確保資料庫已更新
            setTimeout(() => {
              loadGroupChatHistory(true);
              // 發送訊息後更新未讀徽章（因為其他成員會有未讀）
              updateGroupUnreadCounts();
            }, 200);
          } else {
            console.error('群組訊息發送失敗:', result.error);
            alert('發送失敗: ' + (result.error || '未知錯誤'));
          }
        } else {
          // 發送私聊訊息
          if (!currentUserId) return;
          
          const response = await fetch('save_private_message.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              from: username,
              to: currentUserId,
              message: message,
              role: role
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            input.value = '';
            updateSendButtonState(); // 清空後更新按鈕狀態
            console.log('訊息發送成功:', result);
            
            // 如果有保存的訊息資料，直接添加到顯示中
            if (result.saved_message) {
              const chatMessages = document.getElementById('chatMessages');
              
              // 檢查元素是否存在
              if (!chatMessages) {
                console.error('找不到聊天訊息容器元素，無法顯示新訊息，但訊息已成功發送');
                // 即使找不到元素，也清除快取以便下次載入時顯示
                const cacheKey = `${username}-${currentUserId}`;
                messageCache.delete(cacheKey);
                return;
              }
              
              // 將新訊息添加到顯示中
              const messageDiv = document.createElement('div');
              messageDiv.className = 'message sent';
              messageDiv.dataset.messageId = result.saved_message.id;
              
              const contentDiv = document.createElement('div');
              contentDiv.className = 'message-content';
              contentDiv.textContent = result.saved_message.message;
              
              const timeDiv = document.createElement('div');
              timeDiv.className = 'message-time';
              timeDiv.textContent = new Date(result.saved_message.timestamp).toLocaleString();
              
              // 添加未讀狀態（自己的訊息）
              const readStatusDiv = document.createElement('div');
              readStatusDiv.className = 'read-status';
              readStatusDiv.style.cssText = 'font-size: 11px; margin-top: 4px; text-align: right;';
              readStatusDiv.innerHTML = '<span class="read-indicator unread">⏳ 未讀</span>';
              
              contentDiv.appendChild(readStatusDiv);
              contentDiv.appendChild(timeDiv);
              messageDiv.appendChild(contentDiv);
              
              if (chatMessages) {
              chatMessages.appendChild(messageDiv);
              
              // 滾動到底部
              chatMessages.scrollTop = chatMessages.scrollHeight;
              }
              
              // 更新 lastMessageId
              lastMessageId = Math.max(lastMessageId, result.saved_message.id);
              
              // 清除快取，確保下次載入時獲取最新資料
              const cacheKey = `${username}-${currentUserId}`;
              messageCache.delete(cacheKey);
              
              console.log('訊息已立即顯示，ID:', result.saved_message.id);
            } else {
              // 如果沒有返回訊息資料，清除快取並重新載入
              // 清除快取，強制重新載入
              const cacheKey = `${username}-${currentUserId}`;
              messageCache.delete(cacheKey);
              // 重新載入聊天記錄以顯示新訊息
              loadChatHistory();
            }
          } else {
            alert('發送失敗: ' + (result.error || '未知錯誤'));
          }
        }
      } catch (error) {
        console.error('發送訊息失敗:', error);
        alert('發送失敗: ' + error.message);
      } finally {
        // 發送完成後，根據輸入框內容更新按鈕狀態
        updateSendButtonState();
      }
    }
    
    // 標記訊息為已讀
    async function markMessageAsRead(messageId) {
      try {
        const response = await fetch('update_read_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'mark_as_read',
            message_ids: [messageId],
            reader: username
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('訊息已標記為已讀:', messageId);
        }
      } catch (error) {
        console.error('標記已讀失敗:', error);
      }
    }
    
    // 標記聯絡人的所有未讀訊息為已讀
    async function markContactMessagesAsRead(contactUsername) {
      try {
        if (!contactUsername || !username) {
          console.error('標記已讀失敗: 缺少必要的用戶名');
          return false;
        }
        
        console.log(`🔍 開始標記 ${contactUsername} 的未讀訊息為已讀...`);
        
        let unreadMessageIds = [];
        
        // 直接從資料庫查詢該聯絡人的所有未讀訊息ID（更可靠）
        try {
        const queryResponse = await fetch(`update_read_status.php?action=get_unread_messages&from=${encodeURIComponent(contactUsername)}&to=${encodeURIComponent(username)}`);
          if (queryResponse && queryResponse.ok) {
        const queryResult = await queryResponse.json();
        if (queryResult.success && queryResult.message_ids && queryResult.message_ids.length > 0) {
          unreadMessageIds = queryResult.message_ids;
          console.log(`📬 從資料庫查詢到 ${unreadMessageIds.length} 條來自 ${contactUsername} 的未讀訊息`);
            }
          }
        } catch (queryError) {
          console.warn('查詢未讀訊息失敗，將從聊天記錄中獲取:', queryError);
        }
        
        // 如果沒有從專門API獲取到，則從聊天記錄中獲取
        if (unreadMessageIds.length === 0) {
          try {
          const response = await fetch(`load_private_messages.php?from=${encodeURIComponent(contactUsername)}&to=${encodeURIComponent(username)}`);
            if (response && response.ok) {
          const result = await response.json();
          
          if (result.success && result.messages) {
            // 找出所有未讀的訊息ID（發送者是聯絡人，接收者是當前用戶）
            unreadMessageIds = result.messages
              .filter(msg => {
                const isFromContact = msg.from_user === contactUsername;
                const isToCurrentUser = msg.to_user === username;
                const isUnread = !msg.is_read || msg.is_read === false || msg.is_read === 0 || msg.is_read === null;
                return isFromContact && isToCurrentUser && isUnread;
              })
              .map(msg => parseInt(msg.id));
              }
            }
          } catch (loadError) {
            console.warn('從聊天記錄中獲取未讀訊息失敗:', loadError);
          }
        }
        
        if (unreadMessageIds.length > 0) {
          console.log(`📬 準備標記 ${unreadMessageIds.length} 條來自 ${contactUsername} 的未讀訊息為已讀...`);
          
          // 批次標記為已讀
          let markResponse;
          let markResult = { success: false };
          
          try {
            markResponse = await fetch('update_read_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              action: 'mark_as_read',
              message_ids: unreadMessageIds,
              reader: username
            })
          });
          
            if (markResponse && markResponse.ok) {
              markResult = await markResponse.json();
            }
          } catch (markError) {
            console.error('標記已讀請求失敗:', markError);
            return false;
          }
          
            if (markResult.success) {
            console.log(`✅ 已成功標記 ${unreadMessageIds.length} 條訊息為已讀`);
            
            // 將該聯絡人添加到已讀列表
            readContacts.add(contactUsername);
            saveReadContactsToStorage(); // 保存到 localStorage
            
            // 更新 allContacts 中的未讀數量為 0
            const contact = allContacts.find(c => c.username === contactUsername);
            if (contact) {
              contact.unread_count = 0;
            }
            
            // 立即更新該聯絡人的徽章狀態（完全隱藏）
            const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
            if (contactBadge) {
              contactBadge.classList.remove('show', 'pulse', 'hiding');
              contactBadge.style.display = 'none'; // 完全隱藏
              contactBadge.style.visibility = 'hidden'; // 完全隱藏
            }
            
            // 重新排序聯絡人列表（已讀的會排到後面）
            sortAndRenderContacts();
            
            // 清除該聯絡人的聊天記錄快取
            const cacheKey = `${username}-${contactUsername}`;
            messageCache.delete(cacheKey);
            
            // 更新已讀狀態顯示
            unreadMessageIds.forEach(msgId => {
              const msgElement = document.querySelector(`[data-message-id="${msgId}"]`);
              if (msgElement) {
                const readStatusDiv = msgElement.querySelector('.read-status');
                if (readStatusDiv) {
                  readStatusDiv.innerHTML = '<span class="read-indicator read">✓ 已讀</span>';
                }
              }
            });
            
            return true; // 標記成功
          } else {
            console.error('❌ 標記已讀失敗:', markResult.error);
            return false;
          }
        } else {
          console.log(`ℹ️ ${contactUsername} 沒有未讀訊息`);
          
          // 將該聯絡人添加到已讀列表（沒有未讀訊息也算已讀）
          readContacts.add(contactUsername);
          saveReadContactsToStorage(); // 保存到 localStorage
          
          // 更新 allContacts 中的未讀數量為 0
          const contact = allContacts.find(c => c.username === contactUsername);
          if (contact) {
            contact.unread_count = 0;
          }
          
          // 即使沒有未讀訊息，也確保徽章被完全隱藏
          const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contactUsername}"]`);
          if (contactBadge) {
            contactBadge.classList.remove('show', 'pulse', 'hiding');
            contactBadge.style.display = 'none'; // 完全隱藏
            contactBadge.style.visibility = 'hidden'; // 完全隱藏
          }
          
          // 重新排序聯絡人列表（已讀的會排到後面）
          sortAndRenderContacts();
          
          return true;
        }
      } catch (error) {
        console.error('❌ 標記聯絡人訊息為已讀失敗:', error);
        return false;
      }
    }
    
    // 獲取未讀訊息數量（總數）
    async function getUnreadCount() {
      try {
        const response = await fetch(`update_read_status.php?action=get_unread_count&username=${username}`);
        const result = await response.json();
        
        if (result.success) {
          // 更新未讀數量顯示
          const unreadBadge = document.getElementById('unreadBadge');
          if (unreadBadge) {
            if (result.unread_count > 0) {
              unreadBadge.textContent = result.unread_count;
              unreadBadge.style.display = 'inline';
            } else {
              unreadBadge.style.display = 'none';
            }
          }
        }
      } catch (error) {
        console.error('獲取未讀數量失敗:', error);
      }
    }
    
    // 更新所有聯絡人的未讀訊息數量
    async function updateContactUnreadCounts() {
      try {
        const response = await fetch(`get_contact_unread_count.php?username=${encodeURIComponent(username)}`);
        const result = await response.json();
        
        console.log('未讀數量 API 響應:', result);
        
        if (result.success && result.unread_counts) {
          // 先隱藏所有徽章
          document.querySelectorAll('.unread-badge').forEach(badge => {
            badge.classList.remove('show', 'pulse', 'hiding');
            badge.style.display = 'none';
            badge.style.visibility = 'hidden';
          });
          
          // 處理 unread_counts（可能是對象或數組）
          let unreadCountsArray = [];
          if (Array.isArray(result.unread_counts)) {
            // 如果是數組，直接使用
            unreadCountsArray = result.unread_counts;
          } else if (typeof result.unread_counts === 'object' && result.unread_counts !== null) {
            // 如果是對象，轉換為數組格式
            unreadCountsArray = Object.keys(result.unread_counts).map(username => ({
              username: username,
              unread_count: result.unread_counts[username]
            }));
          }
          
          console.log('處理後的未讀數量數組:', unreadCountsArray);
          
          // 更新每個聯絡人的未讀數量，並更新 allContacts 中的 unread_count
          unreadCountsArray.forEach(item => {
            const contactId = item.username || item.contact_id;
            const unreadCount = parseInt(item.unread_count || 0);
            
            console.log(`處理聯絡人 ${contactId}，未讀數量: ${unreadCount}`);
            
            // 更新 allContacts 中的未讀數量
            const contact = allContacts.find(c => c.username === contactId);
            if (contact) {
              contact.unread_count = unreadCount;
            }
            
            if (unreadCount > 0) {
              const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contactId}"]`);
              console.log(`查找徽章 - contactId: ${contactId}, 找到徽章:`, contactBadge);
              if (contactBadge) {
                contactBadge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                contactBadge.setAttribute('data-count', unreadCount);
                contactBadge.style.display = 'flex';
                contactBadge.style.visibility = 'visible';
                contactBadge.classList.add('show');
                
                // 如果未讀數量 > 0，添加脈衝動畫
                if (unreadCount > 0) {
                  contactBadge.classList.add('pulse');
                }
                console.log(`✅ 已更新聯絡人 ${contactId} 的未讀徽章: ${unreadCount}`);
              } else {
                console.warn(`❌ 未找到聯絡人 ${contactId} 的徽章元素`);
                // 調試：列出所有現有的徽章
                const allBadges = document.querySelectorAll('.unread-badge');
                console.log(`現有的徽章數量: ${allBadges.length}`);
                allBadges.forEach((badge, index) => {
                  console.log(`徽章 ${index}: data-contact-id="${badge.getAttribute('data-contact-id')}"`);
                });
              }
            }
          });
          
          // 重新排序並重新渲染聯絡人列表（未讀消息的排在前面）
          // 注意：sortAndRenderContacts 會調用 renderContacts，而 renderContacts 會延遲調用 updateContactUnreadCounts
          // 所以我們需要避免循環調用，只在這裡調用一次排序和渲染
          sortAndRenderContacts();
        } else {
          console.warn('未讀數量 API 響應失敗或無數據:', result);
        }
      } catch (error) {
        console.error('更新聯絡人未讀數量失敗:', error);
      }
    }
    
    // 排序並重新渲染聯絡人列表（未讀消息的排在前面）
    function sortAndRenderContacts() {
      // 按未讀消息數量排序（有未讀消息的排在前面，未讀消息多的排在更前面）
      allContacts.sort((a, b) => {
        const aUnread = parseInt(a.unread_count || 0);
        const bUnread = parseInt(b.unread_count || 0);
        
        // 先按未讀消息數量排序（降序）
        if (bUnread !== aUnread) {
          return bUnread - aUnread;
        }
        // 如果未讀消息數量相同，按名稱排序（升序）
        const aName = (a.name || a.username || '').toLowerCase();
        const bName = (b.name || b.username || '').toLowerCase();
        return aName.localeCompare(bName);
      });
      
      // 更新 filteredContacts
      filteredContacts = [...allContacts];
      currentPage = 1;
      
      // 保存當前選中的聯絡人ID（在重新渲染前）
      const previousSelectedUserId = currentUserId && currentChatType === 'private' ? currentUserId : null;
      
      // 重新渲染聯絡人列表
      renderContacts();
      updatePagination();
      
      // 渲染完成後，恢復選中狀態
      if (previousSelectedUserId) {
        setTimeout(() => {
          const selectedItem = document.querySelector(`[data-user-id="${previousSelectedUserId}"]`);
          if (selectedItem && selectedItem.classList) {
            selectedItem.classList.add('active');
            console.log('排序後恢復選中狀態:', previousSelectedUserId);
          }
        }, 50);
      }
      
      // 渲染完成後，更新徽章顯示（不重新排序，只更新顯示）
      setTimeout(() => {
        updateBadgeDisplay();
      }, 100);
    }
    
    // 只更新徽章顯示，不重新排序和渲染（避免循環）
    function updateBadgeDisplay() {
      // 先隱藏所有徽章
      document.querySelectorAll('.unread-badge').forEach(badge => {
        badge.classList.remove('show', 'pulse', 'hiding');
        badge.style.display = 'none';
        badge.style.visibility = 'hidden';
      });
      
      // 根據 allContacts 中的 unread_count 更新徽章
      allContacts.forEach(contact => {
        const unreadCount = parseInt(contact.unread_count || 0);
        if (unreadCount > 0) {
          const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${contact.username}"]`);
          if (contactBadge) {
            contactBadge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
            contactBadge.setAttribute('data-count', unreadCount);
            contactBadge.style.display = 'flex';
            contactBadge.style.visibility = 'visible';
            contactBadge.classList.add('show');
            
            // 如果未讀數量 > 0，添加脈衝動畫
            if (unreadCount > 0) {
              contactBadge.classList.add('pulse');
            }
          }
        }
      });
    }
    
    // 更新用戶活動時間
    async function updateUserActivity() {
      try {
        const response = await fetch('update_read_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'update_activity',
            username: username,
            is_online: true
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('活動時間已更新');
        }
      } catch (error) {
        console.error('更新活動時間失敗:', error);
      }
    }
    
    // 初始化FCM推播通知
    async function initializeFCM() {
      try {
        // 檢查瀏覽器是否支援通知
        if (!('Notification' in window)) {
          console.log('此瀏覽器不支援推播通知');
          return;
        }
        
        // 請求通知權限
        if (Notification.permission === 'default') {
          const permission = await Notification.requestPermission();
          if (permission !== 'granted') {
            console.log('用戶拒絕了通知權限');
            return;
          }
        }
        
        if (Notification.permission === 'granted') {
          // 註冊FCM token（模擬）
          const fcmToken = 'web-token-' + username + '-' + Date.now();
          await registerFCMToken(fcmToken);
          
          // 設置通知點擊處理
          setupNotificationHandlers();
          
          console.log('FCM推播通知已啟用');
        }
        
      } catch (error) {
        console.error('FCM初始化失敗:', error);
      }
    }
    
    // 註冊FCM token
    async function registerFCMToken(token) {
      try {
        const response = await fetch('simple_fcm_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            action: 'register_token',
            username: username,
            fcm_token: token,
            device_type: 'web',
            device_info: JSON.stringify({
              userAgent: navigator.userAgent,
              platform: navigator.platform,
              language: navigator.language,
              timestamp: new Date().toISOString()
            })
          })
        });
        
        const result = await response.json();
        if (result.success) {
          console.log('FCM token註冊成功');
        } else {
          console.error('FCM token註冊失敗:', result.error);
        }
        
      } catch (error) {
        console.error('註冊FCM token時發生錯誤:', error);
      }
    }
    
    // 設置通知處理器
    function setupNotificationHandlers() {
      // 監聽頁面可見性變化
      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          // 頁面隱藏時，可以發送推播通知
          console.log('頁面已隱藏，推播通知已啟用');
        } else {
          // 頁面可見時，更新活動狀態
          updateUserActivity();
        }
      });
      
      // 監聽頁面關閉事件
      window.addEventListener('beforeunload', function() {
        // 標記用戶為離線
        navigator.sendBeacon('update_read_status.php', JSON.stringify({
          action: 'update_activity',
          username: username,
          is_online: false
        }));
      });
    }
    
    // 顯示本地通知
    function showLocalNotification(title, body, data = {}) {
      if (Notification.permission === 'granted') {
        const options = {
          body: body,
          icon: '../assets/icon-192x192.svg',
          badge: '../assets/badge-72x72.svg',
          tag: 'chat-notification',
          data: data,
          requireInteraction: false,
          silent: false
        };
        
        const notification = new Notification(title, options);
        
        notification.onclick = function(event) {
          event.preventDefault();
          window.focus();
          
          if (data.chat_url) {
            window.open(data.chat_url, '_blank');
          }
          
          notification.close();
        };
        
        // 自動關閉通知
        setTimeout(() => {
          notification.close();
        }, 5000);
        
        return notification;
      }
    }
    
    // 監聽輸入框變化（在頁面載入時設置）
    document.addEventListener('DOMContentLoaded', function() {
      const messageInputForListener = document.getElementById('messageInput');
      if (messageInputForListener) {
        // 監聽 input 事件（實時更新）
        messageInputForListener.addEventListener('input', updateSendButtonState);
        // 監聽 paste 事件（貼上時也更新）
        messageInputForListener.addEventListener('paste', function() {
          setTimeout(updateSendButtonState, 10);
        });
      }
    });
    
    // 按Enter發送訊息
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
      messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          sendMessage();
        }
      });
    }
    
    // 語音錄製功能 - 類似LINE的開關模式
    function toggleVoiceRecording() {
      if (!voiceRecorder) {
        alert('語音錄製功能尚未初始化');
        return;
      }
      
      if (voiceRecorder.isCurrentlyRecording()) {
        // 如果正在錄製，停止錄製
        voiceRecorder.stopRecording();
      } else {
        // 如果沒有在錄製，開始錄製
        voiceRecorder.startRecording();
      }
    }
    
    // 更新語音按鈕狀態
    function updateVoiceButtonState() {
      const voiceBtn = document.getElementById('voiceRecordBtn');
      const messageInput = document.getElementById('messageInput');
      
      if (voiceBtn && messageInput) {
        // 當輸入框啟用時，語音按鈕也啟用
        voiceBtn.disabled = messageInput.disabled;
      }
    }
    
    // 初始化聯絡人列表和分頁
    function initializeContactList() {
      filteredContacts = [...allContacts];
      currentPage = 1;
      renderContacts();
      updatePagination();
    }
    
    // 渲染聯絡人列表
    function renderContacts() {
      const contactListItems = document.getElementById('contactListItems');
      if (!contactListItems) return;
      
      // 計算當前頁的聯絡人
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = startIndex + itemsPerPage;
      displayedContacts = filteredContacts.slice(startIndex, endIndex);
      
      // 清空列表
      contactListItems.innerHTML = '';
      
      if (displayedContacts.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.style.cssText = 'padding: 20px; text-align: center; color: #999;';
        emptyItem.textContent = '暫無聯絡人';
        contactListItems.appendChild(emptyItem);
        return;
      }
      
      // 渲染聯絡人項目
      displayedContacts.forEach(contact => {
        const li = document.createElement('li');
        li.className = 'user-item';
        li.dataset.userId = contact.username;
        li.dataset.userName = contact.name;
        li.dataset.chatType = 'private';
        li.dataset.contactType = contact.contact_type || '';
        li.dataset.department = contact.department || '';
        li.dataset.grade = contact.grade || '';
        li.dataset.class = contact.class_name || '';
        
        // 頭像處理
        let avatarHtml = '';
        // 修復路徑：chat.php 在 frontend/chat/ 目錄
        let avatarSrc = '';
        if (contact.profile_picture) {
          if (contact.profile_picture.startsWith('http://') || contact.profile_picture.startsWith('https://')) {
            // 完整 URL（如 Google 頭像）
            avatarSrc = contact.profile_picture;
          } else if (contact.profile_picture.startsWith('/')) {
            // 絕對路徑
            avatarSrc = contact.profile_picture;
          } else if (contact.profile_picture.startsWith('uploads/')) {
            // 上傳的頭像，使用 ../uploads/（包括 uploads/avatars/）
            avatarSrc = '../' + contact.profile_picture;
          } else if (contact.profile_picture.includes('avatars/')) {
            // 如果包含 avatars/，確保路徑正確
            if (contact.profile_picture.startsWith('avatars/')) {
              avatarSrc = '../uploads/' + contact.profile_picture;
            } else {
              avatarSrc = '../' + contact.profile_picture;
            }
          } else {
            // share 目錄的檔案，使用 ../share/
            avatarSrc = '../share/' + contact.profile_picture;
          }
        } else {
          // 預設頭像
          avatarSrc = '../share/EIdROxGXsAE_LSs.jpg';
        }
        avatarHtml = `
          <img src="${avatarSrc}" alt="${escapeHtml(contact.name)}" class="avatar-img">
        `;
        
        li.innerHTML = `
          <div class="user-avatar" style="position: relative;">
            ${avatarHtml}
          </div>
          <span class="unread-badge" data-contact-id="${contact.username}" style="display: none;"></span>
          <div class="user-info">
            <div class="user-name">${escapeHtml(contact.name)}</div>
            <div class="user-role">${escapeHtml(contact.department || '')}</div>
            ${contact.contact_type === '學生' && contact.grade ? `
              <div class="student-info" style="font-size: 12px; color: #666;">
                ${escapeHtml(contact.grade)}${contact.class_name ? ' - ' + escapeHtml(contact.class_name) : ''}
              </div>
            ` : ''}
            <div class="contact-type">${contact.contact_type || ''}</div>
          </div>
        `;
        
        // 添加點擊事件（支援點擊整個項目或頭像）
        // 點擊項目的任何部分（包括頭像）都會觸發選擇
        li.addEventListener('click', function(e) {
          // 無論點擊項目中的哪個部分（包括頭像），都觸發選擇
          selectContact(contact.username, contact.name);
        });
        
        // 特別為頭像設置可點擊樣式，讓用戶知道頭像可以點擊
        const avatarElement = li.querySelector('.user-avatar');
        if (avatarElement) {
          avatarElement.style.cursor = 'pointer';
          // 為頭像添加點擊事件，確保點擊頭像時也能觸發選擇
          // 阻止事件冒泡，避免重複調用 selectContact
          avatarElement.addEventListener('click', function(e) {
            e.stopPropagation(); // 阻止冒泡到 li，避免重複調用
            selectContact(contact.username, contact.name);
          });
        }
        
        contactListItems.appendChild(li);
      });
      
      // 不在此處調用 updateContactUnreadCounts，避免循環調用
      // updateContactUnreadCounts 會調用 sortAndRenderContacts
      // sortAndRenderContacts 會調用 renderContacts
      // 如果這裡再調用 updateContactUnreadCounts，會造成循環
      // 徽章更新應該在 sortAndRenderContacts 完成後通過 updateBadgeDisplay 處理
      
      // 渲染完成後，恢復選中狀態（如果有當前選中的聯絡人）
      if (currentUserId && currentChatType === 'private') {
        const selectedItem = document.querySelector(`[data-user-id="${currentUserId}"]`);
        if (selectedItem && selectedItem.classList) {
          selectedItem.classList.add('active');
          console.log('渲染後恢復選中狀態:', currentUserId);
        }
      }
    }
    
    // HTML 轉義函數
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    // 隱藏搜尋結果
    function hideSearchResults() {
      const searchResults = document.getElementById('searchResults');
      if (searchResults) {
        searchResults.style.display = 'none';
      }
    }
    
    // 新增聯絡人到列表
    // 從 data 屬性直接新增聯絡人（新方法，避免編碼問題）
    function addContactToListFromData(username, name, userData) {
      console.log('=== 開始新增聯絡人 ===');
      console.log('參數 - username:', username, 'name:', name);
      console.log('userData:', userData);
      console.log('userData.user_id:', userData?.user_id);
      
      if (!userData) {
        console.error('❌ 無法新增聯絡人：userData 為空');
        alert('無法新增聯絡人：缺少用戶資料');
        return;
      }
      
      if (!userData.user_id) {
        console.error('❌ 無法新增聯絡人：缺少用戶ID');
        console.error('userData 完整內容:', JSON.stringify(userData, null, 2));
        alert('無法新增聯絡人：缺少用戶ID\n請檢查用戶資料是否完整');
        return;
      }
      
      // 檢查是否已經存在
      const existingIndex = allContacts.findIndex(c => {
        const cId = (c.user_id || c.username || '').toString();
        const uId = (userData.user_id || username || '').toString();
        return cId === uId || c.username === username;
      });
      
      if (existingIndex !== -1) {
        console.log('聯絡人已存在，跳過新增');
        alert('該聯絡人已存在於列表中');
        return;
      }
      
      // 先保存到資料庫
      const contactUserId = parseInt(userData.user_id);
      if (isNaN(contactUserId) || contactUserId <= 0) {
        console.error('❌ 無效的用戶ID:', userData.user_id);
        alert('錯誤：無效的用戶ID');
        return;
      }
      
      // 發送請求保存到資料庫
      const formData = new URLSearchParams();
      formData.append('contact_user_id', contactUserId);
      
      console.log('準備發送 API 請求');
      console.log('API URL: add_contact_api.php');
      console.log('contact_user_id:', contactUserId);
      console.log('formData:', formData.toString());
      
      fetch('add_contact_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
      })
      .then(response => {
        console.log('API 響應狀態:', response.status, response.statusText);
        console.log('響應 headers:', response.headers);
        if (!response.ok) {
          return response.text().then(text => {
            console.error('API 響應錯誤內容:', text);
            throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
          });
        }
        return response.json();
      })
      .then(data => {
        console.log('API 響應數據:', data);
        if (data.success) {
          console.log('✅ 聯絡人已成功保存到資料庫', data);
          // 添加到聯絡人列表
          const newContact = {
            user_id: contactUserId,
            name: name || userData.name || username,
            username: username,
            department: userData.department || '未設定',
            profile_picture: userData.profile_picture || null,
            contact_type: userData.contact_type || '其他',
            grade: userData.grade || '未設定',
            class_name: userData.class_name || '未設定'
          };
          
          console.log('準備添加到列表的新聯絡人:', newContact);
          
          allContacts.unshift(newContact); // 添加到開頭
          filteredContacts = [...allContacts];
          currentPage = 1;
          renderContacts();
          updatePagination();
          
          console.log('✅ 聯絡人已添加到列表，當前總數:', allContacts.length);
          
          // 隱藏搜尋結果
          hideAddContactResults();
          
          // 顯示成功訊息
          alert('聯絡人新增成功！');
          
          // 可選：自動選擇新增的聯絡人
          setTimeout(() => {
            selectContact(username, name);
          }, 100);
        } else {
          console.error('❌ 新增聯絡人失敗:', data);
          const errorMsg = '新增聯絡人失敗：' + (data.message || '未知錯誤');
          const debugMsg = data.debug ? '\n調試信息: ' + JSON.stringify(data.debug, null, 2) : '';
          alert(errorMsg + debugMsg);
        }
      })
      .catch(error => {
        console.error('❌ 新增聯絡人失敗（網路錯誤）:', error);
        console.error('錯誤詳情:', error.stack);
        console.error('錯誤訊息:', error.message);
        alert('新增聯絡人失敗：網路錯誤\n' + error.message + '\n\n請檢查瀏覽器控制台以獲取更多信息');
      });
    }
    
    // 舊的函數（保留以向後兼容，但現在主要使用 addContactToListFromData）
    function addContactToList(username, name, userDataStr) {
      // 解析用戶資料
      let userData;
      try {
        if (typeof userDataStr === 'string') {
          // 嘗試 JSON 解析
          try {
            userData = JSON.parse(userDataStr.replace(/&quot;/g, '"').replace(/&#39;/g, "'"));
          } catch (e1) {
            console.error('JSON 解析失敗，嘗試從 DOM 獲取:', e1);
            // 如果解析失敗，嘗試從 DOM 獲取
            const btn = document.querySelector(`[data-username="${username}"] .add-contact-btn`);
            if (btn) {
              userData = {
                user_id: btn.dataset.userId ? parseInt(btn.dataset.userId) : null,
                username: username,
                name: name,
                department: btn.dataset.department || '未設定',
                grade: btn.dataset.grade || '未設定',
                class_name: btn.dataset.className || '未設定',
                contact_type: btn.dataset.contactType || '其他',
                profile_picture: btn.dataset.profilePicture || null
              };
            } else {
              throw new Error('無法從 DOM 獲取用戶資料');
            }
          }
        } else {
          userData = userDataStr;
        }
        
        console.log('解析後的用戶資料:', userData);
        
        // 調用新的函數
        addContactToListFromData(username, name, userData);
      } catch (e) {
        console.error('解析用戶資料失敗:', e, '原始資料:', userDataStr);
        alert('新增聯絡人失敗：資料格式錯誤 - ' + e.message);
        return;
      }
      
      // 檢查是否已經存在（使用字符串比較）
      const existingIndex = allContacts.findIndex(c => {
        const cId = (c.user_id || c.username || '').toString();
        const uId = (userData.user_id || username || '').toString();
        return cId === uId || c.username === username;
      });
      
      if (existingIndex === -1) {
        // 先保存到資料庫
        const contactUserId = userData.user_id || null;
        console.log('準備新增聯絡人 - userData:', userData, 'contactUserId:', contactUserId);
        
        if (!contactUserId) {
          console.error('❌ 無法新增聯絡人：缺少用戶ID');
          alert('無法新增聯絡人：缺少用戶ID');
          return;
        }
        
        // 發送請求保存到資料庫
        const formData = new URLSearchParams();
        formData.append('contact_user_id', contactUserId);
        
        console.log('發送 API 請求 - contact_user_id:', contactUserId);
        
        fetch('add_contact_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: formData.toString()
        })
        .then(response => {
          console.log('API 響應狀態:', response.status, response.statusText);
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('API 響應數據:', data);
          if (data.success) {
            console.log('✅ 聯絡人已成功保存到資料庫', data);
            // 添加到聯絡人列表
            const newContact = {
              user_id: userData.user_id || null,
              name: name,
              username: username,
              department: userData.department || '未設定',
              profile_picture: userData.profile_picture || null,
              contact_type: userData.contact_type || '其他',
              grade: userData.grade || '未設定',
              class_name: userData.class_name || '未設定'
            };
            
            allContacts.unshift(newContact); // 添加到開頭
            filteredContacts = [...allContacts];
            
            // 更新顯示
            currentPage = 1;
            renderContacts();
            updatePagination();
            
            // 隱藏新增聯絡人搜尋結果
            hideAddContactResults();
            
            // 清空搜尋框
            const addContactSearchInput = document.getElementById('addContactSearch');
            if (addContactSearchInput) {
              addContactSearchInput.value = '';
            }
            
            // 顯示成功訊息
            alert(`已新增 ${name} 到聯絡人列表`);
            
            // 自動選擇該聯絡人
            setTimeout(() => {
              selectContact(username, name);
            }, 100);
          } else {
            console.error('❌ 新增聯絡人失敗:', data);
            alert('新增聯絡人失敗：' + (data.message || '未知錯誤') + (data.debug ? '\n調試信息: ' + JSON.stringify(data.debug) : ''));
          }
        })
        .catch(error => {
          console.error('❌ 新增聯絡人失敗（網路錯誤）:', error);
          console.error('錯誤詳情:', error.stack);
          alert('新增聯絡人失敗：網路錯誤 - ' + error.message);
        });
      } else {
        // 如果已存在，直接選擇
        hideAddContactResults();
        selectContact(username, name);
      }
    }
    
    // 選擇聯絡人
    function selectContact(userId, userName) {
      console.log('選擇聯絡人:', userId, userName);
      // 隱藏新增聯絡人搜尋結果
      hideAddContactResults();
      
      // 移除所有項目的active狀態（包括群組和聯絡人）
      const userItems = document.querySelectorAll('.user-item');
      userItems.forEach(item => {
        if (item && item.classList) {
          item.classList.remove('active');
        }
      });
      
      // 添加當前項目的active狀態
      const selectedItem = document.querySelector(`[data-user-id="${userId}"]`);
      console.log('查找選中項目:', `[data-user-id="${userId}"]`, '找到:', selectedItem);
      if (selectedItem) {
        // 確保移除可能存在的內聯樣式
        selectedItem.style.background = '';
        selectedItem.style.borderLeft = '';
        selectedItem.style.transform = '';
        selectedItem.style.boxShadow = '';
        selectedItem.style.borderColor = '';
        
        // 添加 active 類
        if (selectedItem.classList) {
          selectedItem.classList.add('active');
          console.log('已添加 active 類到選中項目');
          
          // 強制觸發重繪，確保樣式生效
          selectedItem.offsetHeight;
        }
      } else {
        console.warn('未找到選中的聯絡人項目:', userId);
        // 嘗試查找所有可能的選擇器
        const allItems = document.querySelectorAll('.user-item');
        console.log('所有 user-item 數量:', allItems.length);
        allItems.forEach((item, index) => {
          console.log(`項目 ${index}:`, {
            'data-user-id': item.getAttribute('data-user-id'),
            'data-group-id': item.getAttribute('data-group-id'),
            'classList': Array.from(item.classList || [])
          });
        });
      }
      
      if (currentUserId !== userId || currentChatType !== 'private') {
        lastMessageId = 0;
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
          chatMessages.innerHTML = '';
        }
        const cacheKey = `${username}-${currentUserId}`;
        messageCache.delete(cacheKey);
      }
      
      currentUserId = userId;
      currentUserName = userName;
      currentChatType = 'private';
      currentGroupId = null;
      
      const chatNameElement = document.querySelector('.current-chat-name');
      const chatRoleElement = document.querySelector('.current-chat-role');
      const chatHeaderActions = document.getElementById('chatHeaderActions');
      if (chatNameElement) {
        chatNameElement.textContent = currentUserName;
      }
      
      const selectedContact = allContacts.find(c => c.username === userId);
      if (chatRoleElement && selectedContact) {
        chatRoleElement.textContent = selectedContact.department || '';
      }
      // 隱藏群組管理按鈕（私聊時不顯示）
      if (chatHeaderActions) {
        chatHeaderActions.style.display = 'none';
      }
      
      const messageInput = document.getElementById('messageInput');
      const sendButton = document.querySelector('.chat-input button:not(#voiceRecordBtn)');
      if (messageInput) {
        messageInput.disabled = false;
      }
      if (sendButton) {
        sendButton.disabled = true;
      }
      updateVoiceButtonState();
      
      const noChatSelected = document.querySelector('.no-chat-selected');
      if (noChatSelected && noChatSelected.classList) {
        noChatSelected.classList.add('hidden');
      }
      
      const contactBadge = document.querySelector(`.unread-badge[data-contact-id="${userId}"]`);
      if (contactBadge) {
        if (contactBadge.classList.contains('show')) {
          contactBadge.classList.add('hiding');
          contactBadge.classList.remove('pulse');
          setTimeout(() => {
            contactBadge.classList.remove('show', 'hiding');
            contactBadge.style.display = 'none';
            contactBadge.style.visibility = 'hidden';
          }, 250);
        } else {
          contactBadge.classList.remove('show', 'pulse', 'hiding');
          contactBadge.style.display = 'none';
          contactBadge.style.visibility = 'hidden';
        }
      }
      
      const cacheKey = `${username}-${userId}`;
      messageCache.delete(cacheKey);
      loadChatHistory();
      
      readContacts.add(userId);
      saveReadContactsToStorage();
      
      markContactMessagesAsRead(userId).then((success) => {
        if (success) {
          console.log(`✅ ${userId} 的未讀訊息已標記為已讀`);
          readContacts.add(userId);
          saveReadContactsToStorage();
          // markContactMessagesAsRead 已經更新了 allContacts 並重新排序，不需要再次調用 updateContactUnreadCounts
        }
      }).catch((error) => {
        console.error('標記聯絡人訊息為已讀時發生錯誤:', error);
        readContacts.add(userId);
        saveReadContactsToStorage();
      });
      
      // 不需要再次調用 updateContactUnreadCounts，因為 markContactMessagesAsRead 已經處理了排序和徽章更新
      // setTimeout(() => {
      //   updateContactUnreadCounts();
      // }, 800);
      
      loadChatHistory();
    }
    
    // 更新分頁控制
    function updatePagination() {
      const totalPages = Math.ceil(filteredContacts.length / itemsPerPage);
      const pagination = document.getElementById('contactPagination');
      const pageInfo = document.getElementById('pageInfo');
      const prevBtn = document.getElementById('prevPageBtn');
      const nextBtn = document.getElementById('nextPageBtn');
      
      if (totalPages <= 1) {
        if (pagination) pagination.style.display = 'none';
        return;
      }
      
      if (pagination) pagination.style.display = 'block';
      if (pageInfo) {
        pageInfo.textContent = `第 ${currentPage} / ${totalPages} 頁 (共 ${filteredContacts.length} 人)`;
      }
      
      if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
        prevBtn.style.opacity = currentPage === 1 ? '0.5' : '1';
        prevBtn.style.cursor = currentPage === 1 ? 'not-allowed' : 'pointer';
      }
      
      if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.style.opacity = currentPage === totalPages ? '0.5' : '1';
        nextBtn.style.cursor = currentPage === totalPages ? 'not-allowed' : 'pointer';
      }
      
      // 更新聯絡人計數
      const contactCount = document.getElementById('contactCount');
      if (contactCount) {
        contactCount.textContent = `(${filteredContacts.length})`;
      }
    }
    
    // 分頁按鈕事件
    document.addEventListener('DOMContentLoaded', function() {
      const prevBtn = document.getElementById('prevPageBtn');
      const nextBtn = document.getElementById('nextPageBtn');
      
      if (prevBtn) {
        prevBtn.addEventListener('click', function() {
          if (currentPage > 1) {
            currentPage--;
            renderContacts();
            updatePagination();
          }
        });
      }
      
      if (nextBtn) {
        nextBtn.addEventListener('click', function() {
          const totalPages = Math.ceil(filteredContacts.length / itemsPerPage);
          if (currentPage < totalPages) {
            currentPage++;
            renderContacts();
            updatePagination();
          }
        });
      }
    });
    
    // 1. 搜尋現有聯絡人功能（只過濾已載入的聯絡人列表）
    const contactSearchInput = document.getElementById('contactSearch');
    
    // 本地過濾聯絡人
    function filterContactsLocally(searchTerm) {
      if (searchTerm === '') {
        filteredContacts = [...allContacts];
      } else {
        const searchLower = searchTerm.toLowerCase();
        filteredContacts = allContacts.filter(contact => {
          const name = (contact.name || '').toLowerCase();
          const department = (contact.department || '').toLowerCase();
          const grade = (contact.grade || '').toLowerCase();
          const className = (contact.class_name || '').toLowerCase();
          const contactType = (contact.contact_type || '').toLowerCase();
          const username = (contact.username || '').toLowerCase();
          
          return name.includes(searchLower) || 
                 department.includes(searchLower) || 
                 grade.includes(searchLower) || 
                 className.includes(searchLower) ||
                 contactType.includes(searchLower) ||
                 username.includes(searchLower);
        });
      }
    }
    
    if (contactSearchInput) {
      contactSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        filterContactsLocally(searchTerm);
        currentPage = 1;
        renderContacts();
        updatePagination();
      });
    }
    
    // 2. 新增聯絡人功能（從資料庫搜尋並新增）
    const addContactSearchInput = document.getElementById('addContactSearch');
    const searchUserBtn = document.getElementById('searchUserBtn');
    const addContactResults = document.getElementById('addContactResults');
    const addContactResultsList = document.getElementById('addContactResultsList');
    
    // 隱藏新增聯絡人搜尋結果
    function hideAddContactResults() {
      if (addContactResults) {
        addContactResults.style.display = 'none';
      }
    }
    
    // 搜尋用戶並顯示結果
    async function searchUsersForAdd(keyword) {
      if (!keyword || keyword.trim().length < 1) {
        hideAddContactResults();
        return;
      }
      
      try {
        const response = await fetch(`search_users_api.php?keyword=${encodeURIComponent(keyword.trim())}`);
        const data = await response.json();
        
        if (data.success && data.users) {
          // 檢查哪些用戶已經在聯絡人列表中
          const existingUserIds = new Set();
          allContacts.forEach(c => {
            if (c.user_id) existingUserIds.add(c.user_id.toString());
            if (c.username) existingUserIds.add(c.username);
          });
          
          // 渲染搜尋結果
          if (data.users.length === 0) {
            addContactResultsList.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">未找到相關用戶</div>';
          } else {
            let html = '';
            data.users.forEach(user => {
              const userId = (user.user_id || user.username || '').toString();
              const isInContacts = existingUserIds.has(userId) || existingUserIds.has(user.username);
              const displayName = user.name || user.username;
              // 修復圖片路徑：chat.php 在 frontend/chat/ 目錄
              let avatarSrc = '';
              if (user.profile_picture) {
                if (user.profile_picture.startsWith('http://') || user.profile_picture.startsWith('https://')) {
                  // 完整 URL（如 Google 頭像）
                  avatarSrc = user.profile_picture;
                } else if (user.profile_picture.startsWith('/')) {
                  // 絕對路徑
                  avatarSrc = user.profile_picture;
                } else if (user.profile_picture.startsWith('uploads/')) {
                  // 上傳的頭像，使用 ../uploads/（包括 uploads/avatars/）
                  avatarSrc = '../' + user.profile_picture;
                } else if (user.profile_picture.includes('avatars/')) {
                  // 如果包含 avatars/，確保路徑正確
                  if (user.profile_picture.startsWith('avatars/')) {
                    avatarSrc = '../uploads/' + user.profile_picture;
                  } else {
                    avatarSrc = '../' + user.profile_picture;
                  }
                } else {
                  // share 目錄的檔案，使用 ../share/
                  avatarSrc = '../share/' + user.profile_picture;
                }
              } else {
                // 預設頭像
                avatarSrc = '../share/EIdROxGXsAE_LSs.jpg';
              }
              
              html += `
                <div class="add-contact-result-item" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between;" 
                     data-username="${escapeHtml(user.username)}" 
                     data-name="${escapeHtml(displayName)}"
                     data-user-id="${user.user_id || ''}">
                  <div style="display: flex; align-items: center; flex: 1;">
                    <img src="${avatarSrc}" alt="${escapeHtml(displayName)}" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; object-fit: cover;">
                    <div>
                      <div style="font-weight: 500;">${escapeHtml(displayName)}</div>
                      <div style="font-size: 12px; color: #666;">
                        ${escapeHtml(user.contact_type || '')}${user.department && user.department !== '未設定' ? ' - ' + escapeHtml(user.department) : ''}
                        ${user.grade && user.grade !== '未設定' ? ' - ' + escapeHtml(user.grade) : ''}
                      </div>
                      <div style="font-size: 11px; color: #999;">@${escapeHtml(user.username)}</div>
                    </div>
                  </div>
                  ${!isInContacts ? `
                    <button class="add-contact-btn" 
                            data-username="${escapeHtml(user.username)}"
                            data-name="${escapeHtml(displayName)}"
                            data-user-id="${user.user_id || ''}"
                            data-department="${escapeHtml(user.department || '未設定')}"
                            data-grade="${escapeHtml(user.grade || '未設定')}"
                            data-class-name="${escapeHtml(user.class_name || '未設定')}"
                            data-contact-type="${escapeHtml(user.contact_type || '其他')}"
                            data-profile-picture="${escapeHtml(user.profile_picture || '')}"
                            style="padding: 6px 12px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 10px; white-space: nowrap;">
                      ➕ 新增
                    </button>
                  ` : `
                    <span style="padding: 6px 12px; color: #4CAF50; font-size: 12px; margin-left: 10px; white-space: nowrap;">✓ 已存在</span>
                  `}
                </div>
              `;
            });
            addContactResultsList.innerHTML = html;
          }
          
          if (addContactResults) {
            addContactResults.style.display = 'block';
          }
        } else {
          addContactResultsList.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">搜尋失敗，請稍後再試</div>';
          if (addContactResults) {
            addContactResults.style.display = 'block';
          }
        }
      } catch (error) {
        console.error('搜尋用戶失敗:', error);
        addContactResultsList.innerHTML = '<div style="padding: 15px; text-align: center; color: #ff4444;">搜尋失敗：' + escapeHtml(error.message) + '</div>';
        if (addContactResults) {
          addContactResults.style.display = 'block';
        }
      }
    }
    
    // 搜尋按鈕點擊事件
    if (searchUserBtn) {
      searchUserBtn.addEventListener('click', function() {
        const keyword = addContactSearchInput ? addContactSearchInput.value.trim() : '';
        if (keyword) {
          searchUsersForAdd(keyword);
        } else {
          hideAddContactResults();
        }
      });
    }
    
    // 搜尋框 Enter 鍵事件
    if (addContactSearchInput) {
      addContactSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          const keyword = this.value.trim();
          if (keyword) {
            searchUsersForAdd(keyword);
          } else {
            hideAddContactResults();
          }
        }
      });
    }
    
    // 使用事件委派處理「新增」按鈕點擊（避免 btoa 編碼問題）
    if (addContactResultsList) {
      console.log('✅ 事件委派已設置 - addContactResultsList');
      addContactResultsList.addEventListener('click', function(e) {
        console.log('點擊事件觸發 - target:', e.target, 'currentTarget:', e.currentTarget);
        const btn = e.target.closest('.add-contact-btn');
        console.log('找到按鈕:', btn);
        
        if (btn) {
          e.preventDefault();
          e.stopPropagation();
          
          const username = btn.dataset.username || '';
          const name = btn.dataset.name || '';
          const userId = btn.dataset.userId || '';
          
          console.log('按鈕 data 屬性:', {
            username: username,
            name: name,
            userId: userId,
            department: btn.dataset.department,
            grade: btn.dataset.grade,
            className: btn.dataset.className,
            contactType: btn.dataset.contactType,
            profilePicture: btn.dataset.profilePicture
          });
          
          // 從 data 屬性構建 userData 物件
          const userData = {
            user_id: userId ? parseInt(userId) : null,
            username: username,
            name: name,
            department: btn.dataset.department || '未設定',
            grade: btn.dataset.grade || '未設定',
            class_name: btn.dataset.className || '未設定',
            contact_type: btn.dataset.contactType || '其他',
            profile_picture: btn.dataset.profilePicture || null
          };
          
          console.log('從按鈕 data 屬性獲取的用戶資料:', userData);
          
          if (!userData.user_id) {
            console.error('❌ 錯誤：user_id 為空或無效');
            alert('錯誤：無法獲取用戶ID，請重新搜尋');
            return;
          }
          
          // 調用新增聯絡人函數
          addContactToListFromData(username, name, userData);
        } else {
          console.log('點擊的不是新增按鈕');
        }
      });
    } else {
      console.error('❌ 找不到 addContactResultsList 元素，無法設置事件委派');
    }
    
    // 清空搜尋時隱藏結果
    if (addContactSearchInput) {
      addContactSearchInput.addEventListener('input', function() {
        if (this.value.trim() === '') {
          hideAddContactResults();
        }
      });
    }
    
    // 初始化聯絡人列表和分頁
    // 使用 setTimeout 確保 DOM 完全載入
    setTimeout(function() {
      // 檢查是否存在聯絡人列表容器（所有角色都可以使用完整聊天界面）
      const contactListItems = document.getElementById('contactListItems');
      if (contactListItems) {
        console.log('初始化聯絡人列表，聯絡人數量:', allContacts.length);
        initializeContactList();
      } else {
        console.warn('找不到聯絡人列表容器 contactListItems');
      }
      
      // 如果角色是老師或學生，載入群組列表
      if (role === 'TEA' || role === '老師' || role === 'teacher' || role === 'STU' || role === '學生' || role === 'student' || role === 'STA' || role === '學校行政人員' || role === '行政人員') {
        loadGroups();
      }
    }, 100);
    
    // 初始化已讀功能
    updateUserActivity(); // 更新活動時間
    getUnreadCount(); // 獲取未讀數量
    updateContactUnreadCounts(); // 更新聯絡人未讀數量
    
    // 初始化FCM推播通知
    initializeFCM();
    
    // 應用配色方案
    applyColorScheme();
    
    // 定期更新未讀數量和活動時間（每30秒）
    setInterval(() => {
      getUnreadCount();
      updateContactUnreadCounts(); // 定期更新聯絡人未讀數量
      updateUserActivity();
    }, 30000);
    
    // 定期檢查已讀狀態（每3秒檢查一次，確保已讀狀態實時更新）
    setInterval(async () => {
      if (currentChatType === 'private' && currentUserId) {
        try {
          // 獲取當前聊天的最新消息狀態（包括已讀狀態）
          const url = `load_private_messages.php?from=${encodeURIComponent(username)}&to=${encodeURIComponent(currentUserId)}`;
          const response = await fetch(url);
          const result = await response.json();
          
          if (result.success && result.messages && result.messages.length > 0) {
            // 只更新已存在消息的已讀狀態，不添加新消息（新消息由上面的輪詢處理）
            updateReadStatusForExistingMessages(result.messages);
          }
        } catch (error) {
          console.error('檢查已讀狀態失敗:', error);
        }
      }
    }, 3000); // 每3秒檢查一次已讀狀態
    
    // 定期清理快取（每5分鐘清理一次）
    setInterval(() => {
      const now = Date.now();
      const maxAge = 5 * 60 * 1000; // 5分鐘
      
      for (const [key, data] of messageCache.entries()) {
        if (now - data.timestamp > maxAge) {
          messageCache.delete(key);
        }
      }
      console.log('快取清理完成');
    }, 5 * 60 * 1000);
    
    // 定期更新群組未讀數量（每3秒更新一次，提高實時性）
    setInterval(async () => {
      if (document.getElementById('groupsContainer')) {
        await updateGroupUnreadCounts();
      }
    }, 3000);
    
    // 輪詢新訊息
    setInterval(async () => {
      if (currentChatType === 'group' && currentGroupId) {
        // 檢查群組新訊息
        try {
          const response = await fetch('group_management.php?action=get_group_messages&group_id=' + currentGroupId);
          const result = await response.json();
          
          if (result.success && result.messages) {
            // 使用時間戳來比較，因為群組訊息的ID是組合字符串
            const currentTime = lastMessageId || 0;
            const newMessages = result.messages.filter(m => {
              const msgTime = m.timestamp ? new Date(m.timestamp).getTime() : 0;
              return msgTime > currentTime;
            });
            
            if (newMessages.length > 0) {
              // 使用追加模式，只顯示新訊息
              displayGroupMessages(newMessages, true);
              
              // 批量標記新訊息為已讀（如果不是自己發送的）
              const unreadMessageIds = newMessages
                .filter(msg => msg.from_user !== username && msg.id)
                .map(msg => parseInt(msg.id));
              
              if (unreadMessageIds.length > 0) {
                markGroupMessagesAsRead(unreadMessageIds);
              }
              
              // 更新最後訊息時間戳
              const lastMsg = newMessages[newMessages.length - 1];
              if (lastMsg.timestamp) {
                lastMessageId = new Date(lastMsg.timestamp).getTime();
              }
              
              // 有新訊息時立即更新未讀徽章
              updateGroupUnreadCounts();
            }
            
            // 更新已存在訊息的已讀狀態
            updateExistingMessagesReadStatus(result.messages);
          }
        } catch (error) {
          console.error('檢查群組新訊息失敗:', error);
        }
      } else if (currentChatType === 'private' && currentUserId) {
        // 檢查私聊新訊息和已讀狀態（只獲取比 lastMessageId 更新的消息）
        try {
          // 使用 lastMessageId 參數只獲取新消息，提高效率
          const url = `load_private_messages.php?from=${encodeURIComponent(username)}&to=${encodeURIComponent(currentUserId)}&lastMessageId=${lastMessageId}`;
          const response = await fetch(url);
          
          const result = await response.json();
          
          if (result.success && result.messages) {
            if (result.messages.length > 0) {
              // 有新訊息，追加顯示
              try {
                // 只追加新消息，不重新載入全部
                appendNewMessages(result.messages);
                
                // 更新 lastMessageId
                const newMaxId = Math.max(...result.messages.map(m => parseInt(m.id) || 0));
                if (newMaxId > lastMessageId) {
                  lastMessageId = newMaxId;
                  console.log('發現新訊息，已更新顯示，最後訊息ID:', lastMessageId);
                }
                
                // 如果有新消息且不是當前正在查看的聯絡人，更新未讀數量並重新排序
                const newMessageFrom = result.messages[0].from_user || result.messages[0].from_username;
                if (newMessageFrom && newMessageFrom !== currentUserId && newMessageFrom !== username) {
                  // 更新未讀數量並重新排序聯絡人列表
                  setTimeout(() => {
                    updateContactUnreadCounts();
                  }, 500);
                }
              } catch (displayError) {
                console.error('顯示新訊息時發生錯誤:', displayError);
              }
            }
            
            // 更新已存在訊息的已讀狀態
            updateReadStatusForExistingMessages(result.messages);
          }
        } catch (error) {
          console.error('檢查新訊息失敗:', error);
        }
      }
    }, 1500); // 改為1.5秒輪詢一次，提高實時性
    <?php endif; ?>
  </script>
</main>
<?php include("../share/footer.php"); ?>
</body>
</html>