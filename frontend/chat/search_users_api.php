<?php
// 搜尋用戶 API
require_once '../session_config.php';
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 所有登入用戶都可以搜尋（不限制角色）
// 移除角色限制，讓所有用戶都可以搜尋

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取搜尋關鍵字
    $keyword = $_GET['keyword'] ?? '';
    
    if (empty($keyword) || strlen($keyword) < 1) {
        echo json_encode(['success' => true, 'users' => []]);
        exit;
    }
    
    // 獲取當前用戶ID（排除自己）
    $currentUsername = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$currentUsername]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentUserId = $currentUser ? (int)$currentUser['id'] : 0;
    
    // 搜尋用戶（直接從 user 表搜尋姓名和帳號）
    $searchTerm = '%' . $keyword . '%';
    $searchTermLower = '%' . strtolower($keyword) . '%';
    
    // 簡化查詢：直接從 user 表搜尋
    $sql = "SELECT u.id as user_id,
                COALESCE(u.name, u.username) as name,
                u.username,
                u.profile_picture,
                u.role,
                CASE 
                    WHEN u.role = 'STU' OR u.role = '學生' THEN '學生'
                    WHEN u.role = 'TEA' OR u.role = '老師' OR u.role = 'STA' THEN '老師'
                    WHEN u.role = 'STA' OR u.role = '學校行政人員' OR u.role = '行政人員' THEN '行政人員'
                    ELSE '其他'
                END as contact_type,
                CASE 
                    WHEN LOWER(COALESCE(u.name, '')) LIKE ? THEN 1
                    WHEN LOWER(COALESCE(u.username, '')) LIKE ? THEN 2
                    ELSE 3
                END as match_priority
         FROM user u
         WHERE u.id != ?
           AND (
               COALESCE(u.name, '') LIKE ? OR
               COALESCE(u.username, '') LIKE ?
           )
         ORDER BY match_priority, u.name, u.username
         LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $searchTermLower,  // match_priority: u.name (LOWER)
        $searchTermLower,  // match_priority: u.username (LOWER)
        $currentUserId,    // WHERE u.id != ?
        $searchTerm,       // WHERE u.name LIKE
        $searchTerm        // WHERE u.username LIKE
    ]);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 如果有 student 或 teacher 表的資料，補充科系、年級等資訊
    if (!empty($users)) {
        $userIds = array_column($users, 'user_id');
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        // 獲取學生資料
        $studentSql = "SELECT user_id, department, grade, class_name 
                       FROM student 
                       WHERE user_id IN ($placeholders)";
        $studentStmt = $pdo->prepare($studentSql);
        $studentStmt->execute($userIds);
        $students = [];
        while ($row = $studentStmt->fetch(PDO::FETCH_ASSOC)) {
            $students[$row['user_id']] = $row;
        }
        
        // 獲取老師資料
        $teacherSql = "SELECT user_id, department 
                       FROM teacher 
                       WHERE user_id IN ($placeholders)";
        $teacherStmt = $pdo->prepare($teacherSql);
        $teacherStmt->execute($userIds);
        $teachers = [];
        while ($row = $teacherStmt->fetch(PDO::FETCH_ASSOC)) {
            $teachers[$row['user_id']] = $row;
        }
        
        // 合併資料
        foreach ($users as &$user) {
            $userId = $user['user_id'];
            if (isset($students[$userId])) {
                $user['department'] = $students[$userId]['department'] ?? '未設定';
                $user['grade'] = $students[$userId]['grade'] ?? '未設定';
                $user['class_name'] = $students[$userId]['class_name'] ?? '未設定';
            } elseif (isset($teachers[$userId])) {
                $user['department'] = $teachers[$userId]['department'] ?? '未設定';
                $user['grade'] = '未設定';
                $user['class_name'] = '未設定';
            } else {
                $user['department'] = '未設定';
                $user['grade'] = '未設定';
                $user['class_name'] = '未設定';
            }
        }
        unset($user);
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    error_log("搜尋用戶失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '搜尋失敗：' . $e->getMessage()
    ]);
}

