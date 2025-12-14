<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$action = $_GET['action'] ?? '';

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
    } else {
        echo json_encode(['success' => false, 'message' => '無效的操作']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

