<?php
require_once 'config.php';

// 獲取檔案 ID
$file_id = $_GET['id'] ?? null;

if (!$file_id || !is_numeric($file_id)) {
    http_response_code(400);
    die('無效的檔案ID');
}

try {
    $conn = getDatabaseConnection();
    
    // 查詢檔案資訊
    $sql = "SELECT * FROM bulletin_files WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$file) {
        http_response_code(404);
        die('檔案不存在');
    }

    // 檔案路徑
    $file_path = __DIR__ . '/' . $file['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('檔案不存在');
    }

    // 設定下載標頭
    header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . htmlspecialchars($file['original_filename']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // 輸出檔案
    readfile($file_path);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die('系統錯誤：' . $e->getMessage());
}
?>
