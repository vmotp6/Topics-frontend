<?php
// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

// 載入 session 配置
require_once '../session_config.php';
require_once '../config.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '請先登入'
    ]);
    exit;
}

// 檢查角色：只有 TEA、DI、STA 可以發布公告
$user_role = $_SESSION['role'] ?? '';
$is_teacher = ($user_role === '老師' || $user_role === 'TEA' || $user_role === 'STA');
$is_director = ($user_role === 'DI');
$is_staff = ($user_role === 'STA' || $user_role === '學校行政人員');
$allowed = $is_teacher || $is_director || $is_staff;

if (!$allowed) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '您沒有權限發布公告'
    ]);
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '僅支援 POST 請求'
    ]);
    exit;
}

try {
    // 獲取表單資料
    $user_id = $_POST['user_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type_code = $_POST['type_code'] ?? 'general';
    $status_code = $_POST['status_code'] ?? 'published';
    $source = trim($_POST['source'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $image_url = trim($_POST['image_url'] ?? '');
    
    // 處理多個 URL（從表單陣列）
    $urls = $_POST['urls'] ?? [];
    $url_titles = $_POST['url_titles'] ?? [];

    // 驗證必填欄位
    if (empty($title)) {
        echo json_encode([
            'success' => false,
            'message' => '請輸入公告標題'
        ]);
        exit;
    }

    if (empty($content)) {
        echo json_encode([
            'success' => false,
            'message' => '請輸入公告內容'
        ]);
        exit;
    }

    if (empty($type_code)) {
        echo json_encode([
            'success' => false,
            'message' => '請選擇公告類型'
        ]);
        exit;
    }

    if (empty($status_code)) {
        echo json_encode([
            'success' => false,
            'message' => '請選擇公告狀態'
        ]);
        exit;
    }

    // 驗證類型代碼
    $valid_types = ['exam', 'interview', 'result', 'general'];
    if (!in_array($type_code, $valid_types)) {
        echo json_encode([
            'success' => false,
            'message' => '無效的公告類型'
        ]);
        exit;
    }

    // 驗證狀態代碼
    $valid_statuses = ['draft', 'published', 'archived'];
    if (!in_array($status_code, $valid_statuses)) {
        echo json_encode([
            'success' => false,
            'message' => '無效的公告狀態'
        ]);
        exit;
    }

    // 如果沒有 user_id，從 username 查詢
    if (!$user_id) {
        $username = $_SESSION['username'] ?? '';
        if ($username) {
            $conn = getDatabaseConnection();
            $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $user_id = $row['id'];
            }
            $stmt->close();
            $conn->close();
        }
    }

    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => '無法取得用戶ID'
        ]);
        exit;
    }

    // 驗證日期格式
    if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        echo json_encode([
            'success' => false,
            'message' => '開始日期格式錯誤'
        ]);
        exit;
    }

    if ($end_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        echo json_encode([
            'success' => false,
            'message' => '結束日期格式錯誤'
        ]);
        exit;
    }

    // 驗證日期邏輯：結束日期不能早於開始日期
    if ($start_date && $end_date && $end_date < $start_date) {
        echo json_encode([
            'success' => false,
            'message' => '結束日期不能早於開始日期'
        ]);
        exit;
    }

    // 驗證 URL 格式（如果提供）
    if ($image_url && !filter_var($image_url, FILTER_VALIDATE_URL)) {
        echo json_encode([
            'success' => false,
            'message' => '圖片URL格式錯誤'
        ]);
        exit;
    }

    // 驗證並處理多個 URL
    $valid_urls = [];
    if (is_array($urls)) {
        foreach ($urls as $index => $url) {
            $url = trim($url);
            if (!empty($url)) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    echo json_encode([
                        'success' => false,
                        'message' => "連結URL格式錯誤：{$url}"
                    ]);
                    exit;
                }
                $title = isset($url_titles[$index]) ? trim($url_titles[$index]) : '';
                $valid_urls[] = [
                    'url' => $url,
                    'title' => $title
                ];
            }
        }
    }

    // 處理檔案上傳
    $uploaded_files = [];
    $upload_dir = __DIR__ . '/../uploads/bulletin_files/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['files']) && !empty($_FILES['files']['tmp_name'][0])) {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 10 * 1024 * 1024; // 10MB

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                $original_name = $_FILES['files']['name'][$key];
                $file_size = $_FILES['files']['size'][$key];
                $file_type = $_FILES['files']['type'][$key];
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                // 檢查檔案大小
                if ($file_size > $max_file_size) {
                    echo json_encode([
                        'success' => false,
                        'message' => "檔案 {$original_name} 大小超過 10MB 限制"
                    ]);
                    exit;
                }

                // 檢查檔案類型
                if (!in_array($file_extension, $allowed_extensions)) {
                    echo json_encode([
                        'success' => false,
                        'message' => "檔案 {$original_name} 類型不允許，只允許: " . implode(', ', $allowed_extensions)
                    ]);
                    exit;
                }

                // 生成唯一檔名
                $safe_filename = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
                $target_file = $upload_dir . $safe_filename;

                // 移動檔案
                if (move_uploaded_file($tmp_name, $target_file)) {
                    // 儲存相對路徑（從 frontend 目錄開始）
                    $relative_path = 'uploads/bulletin_files/' . $safe_filename;
                    $uploaded_files[] = [
                        'file_path' => $relative_path,
                        'original_filename' => $original_name,
                        'file_size' => $file_size,
                        'file_type' => $file_type
                    ];
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "檔案 {$original_name} 上傳失敗"
                    ]);
                    exit;
                }
            }
        }
    }

    // 處理空值：將空字串轉為 NULL
    $source = empty($source) ? null : $source;
    $start_date = empty($start_date) ? null : $start_date;
    $end_date = empty($end_date) ? null : $end_date;
    $image_url = empty($image_url) ? null : $image_url;

    // 建立資料庫連接
    $conn = getDatabaseConnection();

    // 檢查公告類型是否存在
    $stmt = $conn->prepare("SELECT id FROM bulletin_types WHERE code = ?");
    $stmt->bind_param("s", $type_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        echo json_encode([
            'success' => false,
            'message' => '公告類型不存在，請先執行資料表設置腳本'
        ]);
        exit;
    }
    $stmt->close();

    // 檢查公告狀態是否存在
    $stmt = $conn->prepare("SELECT id FROM bulletin_statuses WHERE code = ?");
    $stmt->bind_param("s", $status_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        echo json_encode([
            'success' => false,
            'message' => '公告狀態不存在，請先執行資料表設置腳本'
        ]);
        exit;
    }
    $stmt->close();

    // 插入公告資料（移除 link_url，改用 bulletin_urls 表）
    $sql = "INSERT INTO bulletin_board (
        user_id, title, content, type_code, status_code, source,
        start_date, end_date, image_url, view_count, is_pinned,
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, FALSE, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        echo json_encode([
            'success' => false,
            'message' => '資料庫準備失敗：' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "issssssss",
        $user_id,
        $title,
        $content,
        $type_code,
        $status_code,
        $source,
        $start_date,
        $end_date,
        $image_url
    );

    if ($stmt->execute()) {
        $bulletin_id = $conn->insert_id;
        $stmt->close();

        // 插入多個 URL
        if (!empty($valid_urls)) {
            $url_sql = "INSERT INTO bulletin_urls (bulletin_id, url, title, display_order) VALUES (?, ?, ?, ?)";
            $url_stmt = $conn->prepare($url_sql);
            if ($url_stmt) {
                foreach ($valid_urls as $index => $url_data) {
                    $url_stmt->bind_param("issi", $bulletin_id, $url_data['url'], $url_data['title'], $index);
                    $url_stmt->execute();
                }
                $url_stmt->close();
            }
        }

        // 插入多個檔案
        if (!empty($uploaded_files)) {
            $file_sql = "INSERT INTO bulletin_files (bulletin_id, file_path, original_filename, file_size, file_type, display_order) VALUES (?, ?, ?, ?, ?, ?)";
            $file_stmt = $conn->prepare($file_sql);
            if ($file_stmt) {
                foreach ($uploaded_files as $index => $file_data) {
                    $file_stmt->bind_param("issisi", 
                        $bulletin_id, 
                        $file_data['file_path'], 
                        $file_data['original_filename'], 
                        $file_data['file_size'], 
                        $file_data['file_type'],
                        $index
                    );
                    $file_stmt->execute();
                }
                $file_stmt->close();
            }
        }

        $conn->close();

        echo json_encode([
            'success' => true,
            'message' => '公告發布成功！',
            'bulletin_id' => $bulletin_id
        ]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();

        echo json_encode([
            'success' => false,
            'message' => '發布失敗：' . $error
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤：' . $e->getMessage()
    ]);
}
?>
