<?php
// 文件下載處理頁面
require_once 'session_config.php';
require_once 'config.php';

// 檢查登入狀態
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    http_response_code(403);
    die('未授權訪問');
}

// 獲取文件路徑和記錄ID
$file_path = isset($_GET['file']) ? $_GET['file'] : '';
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

if (empty($file_path) || $record_id <= 0) {
    http_response_code(400);
    die('參數錯誤');
}

// 驗證文件是否屬於該記錄
$conn = getDatabaseConnection();
$teacher_id = null;

// 獲取教師ID
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['id'])) {
    $teacher_id = $_SESSION['id'];
} elseif (isset($_SESSION['username'])) {
    $teacher_sql = "SELECT t.user_id FROM teacher t 
                    INNER JOIN user u ON t.user_id = u.id 
                    WHERE u.username = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    if ($teacher_stmt) {
        $teacher_stmt->bind_param("s", $_SESSION['username']);
        $teacher_stmt->execute();
        $result = $teacher_stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $teacher_id = $row['user_id'];
        }
        $teacher_stmt->close();
    }
}

// 檢查記錄是否存在且屬於該教師（或管理員可以查看所有記錄）
$check_sql = "SELECT uploaded_files FROM activity_records WHERE id = ?";
if (isset($_SESSION['role']) && $_SESSION['role'] === '學校行政人員') {
    // 管理員可以查看所有記錄
    $check_sql .= " LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $record_id);
    }
} else {
    // 一般教師只能查看自己的記錄
    $check_sql .= " AND teacher_id = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt && $teacher_id) {
        $check_stmt->bind_param("ii", $record_id, $teacher_id);
    } else {
        http_response_code(403);
        die('未授權訪問');
    }
}

if ($check_stmt) {
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $uploaded_files = json_decode($row['uploaded_files'], true) ?: [];
        
        // 檢查文件是否在列表中
        if (!in_array($file_path, $uploaded_files)) {
            http_response_code(404);
            die('文件不存在');
        }
        
        // 檢查文件是否存在
        if (!file_exists($file_path)) {
            http_response_code(404);
            die('文件不存在');
        }
        
        // 獲取文件名
        $fileName = basename($file_path);
        
        // 設置下載頭
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // 輸出文件
        readfile($file_path);
        exit;
    } else {
        http_response_code(404);
        die('記錄不存在');
    }
    $check_stmt->close();
} else {
    http_response_code(500);
    die('資料庫錯誤');
}

$conn->close();
?>

