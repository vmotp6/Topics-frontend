<?php
// 方塊射擊遊戲 API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

$action = $_GET['action'] ?? '';

try {
    $conn = getDatabaseConnection();
    
    switch ($action) {
        case 'get_level':
            $levelNumber = intval($_GET['level'] ?? 1);
            
            $stmt = $conn->prepare("SELECT * FROM game_im_levels WHERE level_number = ? AND is_active = 1");
            $stmt->bind_param("i", $levelNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $level = $result->fetch_assoc();
                // 解析 JSON 欄位
                $level['win_condition'] = json_decode($level['win_condition'], true);
                $level['enemy_config'] = json_decode($level['enemy_config'], true);
                $level['map_config'] = json_decode($level['map_config'], true);
                
                echo json_encode([
                    'success' => true,
                    'level' => $level
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '關卡不存在'
                ]);
            }
            break;
            
        case 'get_all_levels':
            $stmt = $conn->prepare("SELECT id, level_number, level_name, level_description, level_type, is_active 
                                    FROM game_im_levels 
                                    WHERE is_active = 1 
                                    ORDER BY level_number ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $levels = [];
            while ($row = $result->fetch_assoc()) {
                $levels[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'levels' => $levels
            ]);
            break;
            
        case 'get_max_level':
            $stmt = $conn->prepare("SELECT MAX(level_number) as max_level FROM game_im_levels WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'max_level' => intval($row['max_level'] ?? 1)
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => '無效的操作'
            ]);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ]);
}

