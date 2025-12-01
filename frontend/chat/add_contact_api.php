<?php
// 新增聯絡人 API
require_once '../session_config.php';
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 記錄所有請求
error_log("=== 新增聯絡人 API 被調用 ===");
error_log("POST 數據: " . print_r($_POST, true));
error_log("GET 數據: " . print_r($_GET, true));
error_log("SESSION 數據: " . print_r($_SESSION, true));

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']);

if (!$isLoggedIn) {
    error_log("❌ 未登入，無法新增聯絡人");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查資料表是否存在
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'user_contacts'");
    if ($tableCheck->rowCount() == 0) {
        error_log("user_contacts 資料表不存在，正在創建...");
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
        error_log("✅ user_contacts 資料表創建成功");
    } else {
        error_log("✅ user_contacts 資料表已存在");
    }
    
    // 獲取當前用戶ID
    $currentUsername = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$currentUsername]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentUserId = $currentUser ? (int)$currentUser['id'] : 0;
    
    if (!$currentUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '找不到當前用戶']);
        exit;
    }
    
    // 獲取要新增的聯絡人ID
    $contactUserId = $_POST['contact_user_id'] ?? $_GET['contact_user_id'] ?? 0;
    $contactUserId = (int)$contactUserId;
    
    // 調試：記錄接收到的參數
    error_log("新增聯絡人 API - 當前用戶ID: " . $currentUserId . ", 接收到的 contact_user_id: " . ($_POST['contact_user_id'] ?? $_GET['contact_user_id'] ?? 'null') . ", 轉換後: " . $contactUserId);
    
    if (!$contactUserId || $contactUserId === $currentUserId) {
        error_log("無效的聯絡人ID: contactUserId=" . $contactUserId . ", currentUserId=" . $currentUserId);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '無效的聯絡人ID', 'debug' => [
            'contact_user_id' => $contactUserId,
            'current_user_id' => $currentUserId
        ]]);
        exit;
    }
    
    // 檢查聯絡人是否存在
    $stmt = $pdo->prepare("SELECT id FROM user WHERE id = ?");
    $stmt->execute([$contactUserId]);
    $contactUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contactUser) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '找不到該聯絡人']);
        exit;
    }
    
    // 檢查是否已經存在
    $stmt = $pdo->prepare("SELECT id FROM user_contacts WHERE user_id = ? AND contact_user_id = ?");
    $stmt->execute([$currentUserId, $contactUserId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode(['success' => true, 'message' => '聯絡人已存在', 'already_exists' => true]);
        exit;
    }
    
    // 新增聯絡人
    error_log("準備執行 INSERT - user_id: " . $currentUserId . ", contact_user_id: " . $contactUserId);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_contacts (user_id, contact_user_id) VALUES (?, ?)");
        $result = $stmt->execute([$currentUserId, $contactUserId]);
        
        error_log("INSERT 執行結果: " . ($result ? 'true' : 'false'));
        error_log("影響的行數: " . $stmt->rowCount());
        
        $insertId = $pdo->lastInsertId();
        error_log("✅ 聯絡人新增成功 - ID: " . $insertId . ", user_id: " . $currentUserId . ", contact_user_id: " . $contactUserId);
        
        // 驗證是否真的寫入資料庫
        $verifyStmt = $pdo->prepare("SELECT id, user_id, contact_user_id, created_at FROM user_contacts WHERE user_id = ? AND contact_user_id = ?");
        $verifyStmt->execute([$currentUserId, $contactUserId]);
        $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verifyResult) {
            error_log("✅ 驗證成功：聯絡人已寫入資料庫，記錄ID: " . $verifyResult['id'] . ", 完整記錄: " . print_r($verifyResult, true));
        } else {
            error_log("❌ 驗證失敗：聯絡人未寫入資料庫！");
            // 再次查詢所有記錄以調試
            $allStmt = $pdo->query("SELECT * FROM user_contacts ORDER BY id DESC LIMIT 5");
            $allRecords = $allStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("資料庫中最近的 5 筆記錄: " . print_r($allRecords, true));
        }
        
        echo json_encode([
            'success' => true,
            'message' => '聯絡人新增成功',
            'contact_id' => $contactUserId,
            'insert_id' => $insertId,
            'verified' => $verifyResult ? true : false,
            'debug' => [
                'insert_result' => $result,
                'rows_affected' => $stmt->rowCount(),
                'verify_result' => $verifyResult
            ]
        ]);
    } catch (PDOException $insertError) {
        error_log("❌ INSERT 執行失敗: " . $insertError->getMessage());
        error_log("錯誤代碼: " . $insertError->getCode());
        throw $insertError;
    }
    
} catch (PDOException $e) {
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    
    error_log("❌ 新增聯絡人失敗: " . $errorMessage);
    error_log("錯誤代碼: " . $errorCode);
    error_log("錯誤詳情: " . print_r($e->errorInfo, true));
    
    // 檢查是否是外鍵約束錯誤
    if ($errorCode == 23000 || strpos($errorMessage, 'foreign key') !== false) {
        error_log("❌ 外鍵約束錯誤：可能是 user_id 或 contact_user_id 不存在於 user 表中");
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '新增聯絡人失敗：' . $errorMessage,
        'error_code' => $errorCode,
        'error_info' => $e->errorInfo
    ]);
} catch (Exception $e) {
    error_log("❌ 一般錯誤: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '新增聯絡人失敗：' . $e->getMessage()
    ]);
}

