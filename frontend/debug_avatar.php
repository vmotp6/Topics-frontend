<?php
// 調試頭像路徑
require_once 'session_config.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['username'])) {
    die('請先登入');
}

$username = $_SESSION['username'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=topics_good;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT profile_picture FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $profile_picture = $stmt->fetchColumn();
    
    echo "<h2>頭像調試資訊</h2>";
    echo "<p><strong>用戶名:</strong> {$username}</p>";
    echo "<p><strong>資料庫中的路徑:</strong> " . htmlspecialchars($profile_picture ?? 'NULL') . "</p>";
    
    if (!empty($profile_picture)) {
        if (filter_var($profile_picture, FILTER_VALIDATE_URL)) {
            echo "<p><strong>類型:</strong> Google URL</p>";
            echo "<p><strong>圖片:</strong> <img src='" . htmlspecialchars($profile_picture) . "' style='max-width: 200px;'></p>";
        } else {
            echo "<p><strong>類型:</strong> 本地檔案</p>";
            $file_path = __DIR__ . '/' . $profile_picture;
            echo "<p><strong>完整路徑:</strong> " . htmlspecialchars($file_path) . "</p>";
            echo "<p><strong>檔案存在:</strong> " . (file_exists($file_path) ? '是' : '否') . "</p>";
            
            if (file_exists($file_path)) {
                echo "<p><strong>檔案大小:</strong> " . filesize($file_path) . " bytes</p>";
                echo "<p><strong>相對路徑:</strong> " . htmlspecialchars($profile_picture) . "</p>";
                echo "<p><strong>圖片:</strong> <img src='" . htmlspecialchars($profile_picture) . "' style='max-width: 200px;'></p>";
            } else {
                echo "<p style='color: red;'>❌ 檔案不存在！</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ 資料庫中沒有頭像路徑</p>";
    }
    
    // 列出上傳目錄中的所有檔案
    echo "<h3>上傳目錄中的檔案</h3>";
    $upload_dir = __DIR__ . '/uploads/avatars/';
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $full_path = $upload_dir . $file;
                echo "<li>";
                echo htmlspecialchars($file) . " (" . filesize($full_path) . " bytes)";
                echo " - <a href='uploads/avatars/" . urlencode($file) . "' target='_blank'>查看</a>";
                echo "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>上傳目錄不存在: " . htmlspecialchars($upload_dir) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

