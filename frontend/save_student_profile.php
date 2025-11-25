<?php
// 保存學生個人資料
require_once 'session_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 檢查是否為學生角色
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '學生') {
    echo json_encode(['success' => false, 'message' => '只有學生可以保存個人資料']);
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '請使用 POST 方法提交']);
    exit;
}

// 獲取表單資料
$username = $_POST['username'] ?? '';
$name = $_POST['name'] ?? '';
$department = $_POST['department'] ?? '';
$phone = $_POST['phone'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$grade = $_POST['grade'] ?? '';
$class_name = $_POST['class_name'] ?? '';

// 驗證必填欄位
if (empty($username) || empty($name) || empty($department) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => '請填寫所有必填欄位（姓名、科系、電話）']);
    exit;
}

// 處理頭像上傳
$profile_picture_path = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // 驗證檔案類型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => '不支援的檔案格式，請上傳 JPG、PNG 或 GIF 圖片']);
        exit;
    }
    
    // 驗證檔案大小
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => '檔案大小超過 5MB，請上傳較小的圖片']);
        exit;
    }
    
    // 創建上傳目錄（如果不存在）
    $upload_dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // 生成唯一檔名（使用安全的檔名，避免中文字符問題）
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // 使用用戶ID或時間戳+隨機數來生成檔名，避免中文字符
    $safe_username = preg_replace('/[^a-zA-Z0-9_]/', '_', $username); // 移除特殊字符
    $new_filename = time() . '_' . uniqid() . '_' . $safe_username . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // 移動上傳的檔案
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // 確認檔案真的存在
        if (file_exists($upload_path)) {
            // 儲存相對路徑（相對於 frontend 目錄）
            $profile_picture_path = 'uploads/avatars/' . $new_filename;
            
            // 記錄上傳成功
            error_log("頭像上傳成功: {$upload_path}, 相對路徑: {$profile_picture_path}");
            
            // 如果有舊的頭像（非 Google URL），刪除舊檔案
            try {
                $pdo_temp = new PDO("mysql:host=localhost;dbname=topics_good;charset=utf8mb4", "root", "");
                $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt_temp = $pdo_temp->prepare("SELECT profile_picture FROM user WHERE username = ?");
                $stmt_temp->execute([$username]);
                $old_picture = $stmt_temp->fetchColumn();
                
                if ($old_picture && !filter_var($old_picture, FILTER_VALIDATE_URL)) {
                    // 舊頭像是本地檔案，嘗試刪除
                    $old_path = __DIR__ . '/' . $old_picture;
                    if (file_exists($old_path) && strpos($old_picture, 'uploads/avatars/') !== false) {
                        @unlink($old_path);
                        error_log("已刪除舊頭像: {$old_path}");
                    }
                }
            } catch (Exception $e) {
                // 忽略刪除舊檔案的錯誤
                error_log("刪除舊頭像錯誤: " . $e->getMessage());
            }
        } else {
            error_log("頭像檔案上傳後驗證失敗: {$upload_path} 不存在");
            echo json_encode(['success' => false, 'message' => '頭像檔案上傳後驗證失敗，請稍後再試']);
            exit;
        }
    } else {
        $error_msg = '頭像上傳失敗';
        if (!is_writable($upload_dir)) {
            $error_msg .= '（上傳目錄沒有寫入權限）';
        }
        error_log("頭像上傳失敗: 目錄={$upload_dir}, 可寫=" . (is_writable($upload_dir) ? '是' : '否'));
        echo json_encode(['success' => false, 'message' => $error_msg . '，請稍後再試']);
        exit;
    }
}

try {
    // 資料庫連接
    $host = 'localhost';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取用戶 ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_result) {
        echo json_encode(['success' => false, 'message' => '使用者不存在']);
        exit;
    }
    
    $user_id = $user_result['id'];
    
    // 更新 user 表的 name
    $stmt = $pdo->prepare("UPDATE user SET name = ? WHERE id = ?");
    $stmt->execute([$name, $user_id]);
    error_log("資料庫更新姓名: username={$username}, name={$name}");
    
    // 檢查 student 表是否存在該用戶的記錄
    $stmt = $pdo->prepare("SELECT id FROM student WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_exists = $stmt->fetch();
    
    // 如果有上傳新頭像，更新 user 表的 profile_picture
    if ($profile_picture_path !== null) {
        $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$profile_picture_path, $user_id]);
        error_log("資料庫更新頭像: username={$username}, path={$profile_picture_path}");
        
        // 驗證更新是否成功
        $stmt_check = $pdo->prepare("SELECT profile_picture FROM user WHERE id = ?");
        $stmt_check->execute([$user_id]);
        $updated_path = $stmt_check->fetchColumn();
        error_log("資料庫驗證: 更新後的頭像路徑={$updated_path}");
    }
    
    if ($student_exists) {
        // 更新現有資料（包含姓名，不包含email，email由註冊時設定，不允許修改）
        $stmt = $pdo->prepare("UPDATE student SET name = ?, department = ?, phone = ?, student_id = ?, grade = ?, class_name = ? WHERE user_id = ?");
        $stmt->execute([$name, $department, $phone, $student_id ?: null, $grade ?: null, $class_name ?: null, $user_id]);
    } else {
        // 獲取email（如果有的話）
        $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $user_data['email'] ?? null;
        
        // 插入新資料（使用表單提交的姓名，email從user表獲取，不允許修改）
        $stmt = $pdo->prepare("INSERT INTO student (user_id, name, department, phone, student_id, grade, class_name, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $department, $phone, $student_id ?: null, $grade ?: null, $class_name ?: null, $email]);
    }
    
    $message = '個人資料保存成功';
    if ($profile_picture_path !== null) {
        $message .= '，頭像已更新';
    }
    echo json_encode([
        'success' => true, 
        'message' => $message, 
        'avatar_updated' => $profile_picture_path !== null,
        'avatar_path' => $profile_picture_path
    ]);
    
} catch (PDOException $e) {
    error_log("保存學生個人資料錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '資料庫錯誤，請稍後再試']);
} catch (Exception $e) {
    error_log("保存學生個人資料錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}
?>











