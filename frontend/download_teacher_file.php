<?php
// 載入 session 配置
require_once 'session_config.php';
require_once 'config.php';

// 檢查是否已登入且為老師角色
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

$isTeacher = ($_SESSION['role'] === '老師' || $_SESSION['role'] === 'TEA' || $_SESSION['role'] === 'STA' || $_SESSION['role'] === '學校行政人員' || $_SESSION['role'] === 'AA');

if (!$isLoggedIn || !$isTeacher) {
    http_response_code(403);
    die('權限不足');
}

try {
    $conn = getDatabaseConnection();
    
    // 獲取當前老師的用戶ID
    $username = $_SESSION['username'];
    $teacher_stmt = $conn->prepare("
        SELECT u.id 
        FROM user u 
        WHERE u.username = ? AND (u.role = '老師' OR u.role = 'TEA' OR u.role = 'STA' OR u.role = 'AA')
    ");
    $teacher_stmt->bind_param("s", $username);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher = $teacher_result->fetch_assoc();
    
    if (!$teacher) {
        http_response_code(403);
        die('找不到老師資料');
    }
    
    $teacher_id = (int)$teacher['id'];
    
    // 獲取檔案ID
    $file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($file_id <= 0) {
        http_response_code(400);
        die('缺少檔案ID');
    }
    
    // 查詢檔案資訊（確保檔案屬於該老師）
    $file_stmt = $conn->prepare("
        SELECT original_filename, file_path 
        FROM teacher_files 
        WHERE id = ? AND teacher_id = ?
    ");
    $file_stmt->bind_param("ii", $file_id, $teacher_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    $file_data = $file_result->fetch_assoc();
    
    if (!$file_data) {
        http_response_code(404);
        die('找不到檔案或無權限下載');
    }
    
    $file_path = __DIR__ . '/' . $file_data['file_path'];
    $original_filename = $file_data['original_filename'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('檔案不存在');
    }
    
    // 設定下載標頭
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . addslashes($original_filename) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // 輸出檔案
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log('Download Teacher File Error: ' . $e->getMessage());
    http_response_code(500);
    die('下載失敗');
}
?>

