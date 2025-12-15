<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'session_config.php';
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $conn = getDatabaseConnection();
    
    // 確保表存在
    $conn->query("CREATE TABLE IF NOT EXISTS game_code_maps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL COMMENT '地圖名稱',
        width INT NOT NULL DEFAULT 10 COMMENT '地圖寬度',
        height INT NOT NULL DEFAULT 10 COMMENT '地圖高度',
        start_x INT NOT NULL DEFAULT 0 COMMENT '起始X座標',
        start_y INT NOT NULL DEFAULT 0 COMMENT '起始Y座標',
        end_x INT NOT NULL DEFAULT 9 COMMENT '終點X座標',
        end_y INT NOT NULL DEFAULT 9 COMMENT '終點Y座標',
        map_data TEXT NOT NULL COMMENT '地圖資料JSON',
        is_active TINYINT DEFAULT 1 COMMENT '是否啟用',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    if ($action === 'get_map') {
        $mapId = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM game_code_maps WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mapId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'map' => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'width' => (int)$row['width'],
                    'height' => (int)$row['height'],
                    'start_x' => (int)$row['start_x'],
                    'start_y' => (int)$row['start_y'],
                    'end_x' => (int)$row['end_x'],
                    'end_y' => (int)$row['end_y'],
                    'map_data' => json_decode($row['map_data'], true)
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '地圖不存在']);
        }
        $stmt->close();
    } elseif ($action === 'increment_level') {
        // 增加關卡數（session 已經在 session_config.php 中啟動）
        $difficulty = $_POST['difficulty'] ?? $_GET['difficulty'] ?? $_SESSION['game_difficulty'] ?? 'easy';
        
        // 確保困難度已設定
        if (!isset($_SESSION['game_difficulty'])) {
            $_SESSION['game_difficulty'] = $difficulty;
        }
        
        // 初始化關卡數（如果不存在）
        if (!isset($_SESSION['game_level'])) {
            $_SESSION['game_level'] = 1;
        }
        
        // 增加關卡數（無限增加）
        $currentLevel = (int)$_SESSION['game_level'];
        $newLevel = $currentLevel + 1;
        $_SESSION['game_level'] = $newLevel;
        $_SESSION['game_map'] = null; // 清除舊地圖，強制生成新地圖
        
        // 確保 session 寫入
        if (function_exists('session_write_close')) {
            // 不關閉 session，讓主頁面可以讀取
        }
        
        echo json_encode([
            'success' => true,
            'level' => $newLevel,
            'difficulty' => $difficulty,
            'previous_level' => $currentLevel
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '無效的操作']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

