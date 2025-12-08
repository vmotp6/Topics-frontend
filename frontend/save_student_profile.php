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

// 檢查是否為學生角色（支援角色代碼 'STU' 和中文名稱 '學生'）
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== '學生' && $_SESSION['role'] !== 'STU')) {
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

// 檢查是否只有頭像上傳（沒有其他資料）
$avatar_only = isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && 
               empty($name) && empty($department) && empty($phone);

// 驗證 username 是否為空（這是唯一必填的）
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => '使用者名稱不能為空']);
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
    
    // 將科系名稱轉換為代碼
    $department_code = null;
    if (!empty($department)) {
        // 先檢查是否已經是代碼
        $stmt_dept = $pdo->prepare("SELECT code FROM departments WHERE code = ?");
        $stmt_dept->execute([$department]);
        $dept_result = $stmt_dept->fetch(PDO::FETCH_ASSOC);
        if ($dept_result) {
            $department_code = $dept_result['code'];
        } else {
            // 如果不是代碼，嘗試用名稱查詢
            $stmt_dept = $pdo->prepare("SELECT code FROM departments WHERE name = ?");
            $stmt_dept->execute([$department]);
            $dept_result = $stmt_dept->fetch(PDO::FETCH_ASSOC);
            if ($dept_result) {
                $department_code = $dept_result['code'];
            } else {
                // 如果找不到對應的代碼，設為 null（允許為空）
                error_log("無法找到科系代碼: {$department}，將設為空值");
                $department_code = null;
            }
        }
    }
    
    // 將年級名稱轉換為代碼
    $grade_code = null;
    if (!empty($grade)) {
        // 年級映射：將表單中的選項映射到代碼
        $grade_code_mapping = [
            // 五專
            '專一' => 'F1',
            '專二' => 'F2',
            '專三' => 'F3',
            '專四' => 'F4',
            '專五' => 'F5',
            // 國中
            '國一' => 'J1',
            '國二' => 'J2',
            '國三' => 'J3',
            // 高中（向後兼容）
            '高一' => 'H1',
            '高二' => 'H2',
            '高三' => 'H3',
            // 舊格式（向後兼容）
            '一年級' => 'F1',
            '二年級' => 'F2',
            '三年級' => 'F3',
            '四年級' => 'F4',
            '五年級' => 'F5'
        ];
        
        // 先檢查是否已經在映射表中
        if (isset($grade_code_mapping[$grade])) {
            $grade_code = $grade_code_mapping[$grade];
            // 驗證代碼是否存在於資料庫中
            $stmt_check = $pdo->prepare("SELECT code FROM identity_options WHERE code = ?");
            $stmt_check->execute([$grade_code]);
            if (!$stmt_check->fetch()) {
                error_log("年級代碼不存在於資料庫: {$grade_code}");
                $grade_code = null;
            }
        } else {
            // 如果不是標準映射，嘗試直接查詢（可能是代碼或名稱）
            $stmt_grade = $pdo->prepare("SELECT code FROM identity_options WHERE name = ? OR code = ?");
            $stmt_grade->execute([$grade, $grade]);
            $grade_result = $stmt_grade->fetch(PDO::FETCH_ASSOC);
            if ($grade_result) {
                $grade_code = $grade_result['code'];
            } else {
                // 如果找不到對應的代碼，設為 null（允許為空）
                error_log("無法找到年級代碼: {$grade}");
                $grade_code = null;
            }
        }
    }
    
    // 檢查 student 表是否存在該用戶的記錄
    $stmt = $pdo->prepare("SELECT user_id FROM student WHERE user_id = ?");
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
        
        // 如果只是上傳頭像，直接返回成功
        if ($avatar_only) {
            echo json_encode([
                'success' => true,
                'message' => '頭像儲存成功',
                'avatar_updated' => true,
                'avatar_path' => $profile_picture_path
            ]);
            exit;
        }
    }
    
    // 檢查 student 表是否有 email 欄位
    $has_email_column = false;
    try {
        $check_stmt = $pdo->query("SHOW COLUMNS FROM student LIKE 'email'");
        $has_email_column = $check_stmt->rowCount() > 0;
    } catch(PDOException $e) {
        $has_email_column = false;
    }
    
    // 獲取email（如果 student 表有 email 欄位且 user 表有 email）
    $email = null;
    if ($has_email_column) {
        $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $user_data['email'] ?? null;
    }
    
    if ($student_exists) {
        // 更新現有資料（不包含 name，name 在 user 表中）
        if ($has_email_column) {
            // 如果 student 表有 email 欄位，包含在更新中
            $stmt = $pdo->prepare("UPDATE student SET department = ?, phone = ?, student_id = ?, grade = ?, class_name = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$department_code, $phone, $student_id ?: null, $grade_code, $class_name ?: null, $email, $user_id]);
        } else {
            // 如果 student 表沒有 email 欄位，不包含在更新中
            $stmt = $pdo->prepare("UPDATE student SET department = ?, phone = ?, student_id = ?, grade = ?, class_name = ? WHERE user_id = ?");
            $stmt->execute([$department_code, $phone, $student_id ?: null, $grade_code, $class_name ?: null, $user_id]);
        }
    } else {
        // 插入新資料
        if ($has_email_column) {
            // 如果 student 表有 email 欄位，包含在插入中
            $stmt = $pdo->prepare("INSERT INTO student (user_id, department, phone, student_id, grade, class_name, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $department_code, $phone, $student_id ?: null, $grade_code, $class_name ?: null, $email]);
        } else {
            // 如果 student 表沒有 email 欄位，不包含在插入中
            $stmt = $pdo->prepare("INSERT INTO student (user_id, department, phone, student_id, grade, class_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $department_code, $phone, $student_id ?: null, $grade_code, $class_name ?: null]);
        }
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











