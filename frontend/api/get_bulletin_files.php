<?php
// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

// 獲取公告 ID
$bulletin_id = $_GET['id'] ?? null;

if (!$bulletin_id || !is_numeric($bulletin_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '無效的公告ID'
    ]);
    exit;
}

try {
    $conn = getDatabaseConnection();
    
    // 查詢相關連結
    $urls = [];
    $url_sql = "SELECT * FROM bulletin_urls WHERE bulletin_id = ? ORDER BY display_order ASC";
    $url_stmt = $conn->prepare($url_sql);
    if ($url_stmt) {
        $url_stmt->bind_param("i", $bulletin_id);
        $url_stmt->execute();
        $url_result = $url_stmt->get_result();
        while ($row = $url_result->fetch_assoc()) {
            $urls[] = $row;
        }
        $url_stmt->close();
    }

    // 查詢相關檔案
    $files = [];
    $file_sql = "SELECT * FROM bulletin_files WHERE bulletin_id = ? ORDER BY display_order ASC";
    $file_stmt = $conn->prepare($file_sql);
    if ($file_stmt) {
        $file_stmt->bind_param("i", $bulletin_id);
        $file_stmt->execute();
        $file_result = $file_stmt->get_result();
        while ($row = $file_result->fetch_assoc()) {
            $files[] = $row;
        }
        $file_stmt->close();
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'urls' => $urls,
        'files' => $files
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤：' . $e->getMessage()
    ]);
}
?>
