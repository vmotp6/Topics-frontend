<?php
// 保存老師 / 主任 個人資料
require_once 'session_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 目前登入者角色
$user_role = $_SESSION['role'] ?? '';
$is_teacher = ($user_role === 'TEA' || $user_role === 'STA');
$is_director = ($user_role === 'DI');

// 限定只有 TEA / DI 可以編輯此頁
if (!$is_teacher && !$is_director) {
    echo json_encode(['success' => false, 'message' => '您沒有權限修改此資料']);
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

// 檢查是否只有頭像上傳（沒有其他資料）
$avatar_only = isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK &&
               empty($name) && empty($department) && empty($phone);

// 驗證 username 是否為空（必填）
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
        echo json_encode(['success' => false, 'message' => '不支援的檔案格式，請上傳 JPG、PNG 或 GIF']);
        exit;
    }

    // 驗證檔案大小
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => '檔案大小超過 5MB']);
        exit;
    }

    // 上傳目錄
    $upload_dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // 安全檔名
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safe_username = preg_replace('/[^a-zA-Z0-9_]/', '_', $username);
    $new_filename = time() . '_' . uniqid() . '_' . $safe_username . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        if (file_exists($upload_path)) {
            $profile_picture_path = 'uploads/avatars/' . $new_filename;

            // 刪除舊頭像（如果不是 URL）
            try {
                $pdo_temp = new PDO("mysql:host=localhost;dbname=topics_good;charset=utf8mb4", "root", "");
                $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt_temp = $pdo_temp->prepare("SELECT profile_picture FROM user WHERE username = ?");
                $stmt_temp->execute([$username]);
                $old_picture = $stmt_temp->fetchColumn();

                if ($old_picture && !filter_var($old_picture, FILTER_VALIDATE_URL)) {
                    $old_path = __DIR__ . '/' . $old_picture;
                    if (file_exists($old_path) && strpos($old_picture, 'uploads/avatars/') !== false) {
                        @unlink($old_path);
                    }
                }
            } catch (Exception $e) {
                // ignore
            }

        } else {
            echo json_encode(['success' => false, 'message' => '頭像上傳後驗證失敗']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '頭像上傳失敗']);
        exit;
    }
}

try {
    // 資料庫連接
    $pdo = new PDO("mysql:host=localhost;dbname=topics_good;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 取得用戶 ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_result) {
        echo json_encode(['success' => false, 'message' => '使用者不存在']);
        exit;
    }

    $user_id = $user_result['id'];

    // 更新使用者姓名
    if (!$avatar_only && !empty($name)) {
        $stmt = $pdo->prepare("UPDATE user SET name = ? WHERE id = ?");
        $stmt->execute([$name, $user_id]);
    }

    // 科系名稱 → 科系代碼
    $department_code = null;
    if (!empty($department)) {
        $stmt_dept = $pdo->prepare("SELECT code FROM departments WHERE code = ? OR name = ?");
        $stmt_dept->execute([$department, $department]);
        $dept_result = $stmt_dept->fetch(PDO::FETCH_ASSOC);
        $department_code = $dept_result['code'] ?? null;
    }

    // 更新頭像
    if ($profile_picture_path !== null) {
        $stmt = $pdo->prepare("UPDATE user SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$profile_picture_path, $user_id]);

        if ($avatar_only) {
            echo json_encode([
                'success' => true,
                'message' => '頭像更新成功',
                'avatar_updated' => true,
                'avatar_path' => $profile_picture_path
            ]);
            exit;
        }
    }

    // 🔥 選擇身分對應的資料表
    $target_table = $is_director ? 'director' : 'teacher';

    // 檢查是更新還是新增
    $stmt = $pdo->prepare("SELECT user_id FROM {$target_table} WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // 更新
        $stmt = $pdo->prepare("UPDATE {$target_table} SET department = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$department_code, $phone, $user_id]);
    } else {
        // 新增
        $stmt = $pdo->prepare("INSERT INTO {$target_table} (user_id, department, phone) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $department_code, $phone]);
    }

    $message = '個人資料保存成功';
    if ($profile_picture_path !== null) $message .= '（頭像已更新）';

    echo json_encode([
        'success' => true,
        'message' => $message,
        'avatar_updated' => $profile_picture_path !== null,
        'avatar_path' => $profile_picture_path
    ]);

} catch (Exception $e) {
    error_log("錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
}

?>
