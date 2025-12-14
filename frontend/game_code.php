<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';

// 獲取地圖資料
function getGameMap($mapId = 1) {
    try {
        $conn = getDatabaseConnection();
        
        // 檢查表是否存在，如果不存在則創建
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
        
        $sql = "SELECT * FROM game_code_maps WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mapId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $map = [
                'id' => $row['id'],
                'name' => $row['name'],
                'width' => (int)$row['width'],
                'height' => (int)$row['height'],
                'start' => ['x' => (int)$row['start_x'], 'y' => (int)$row['start_y']],
                'end' => ['x' => (int)$row['end_x'], 'y' => (int)$row['end_y']],
                'map_data' => json_decode($row['map_data'], true)
            ];
            $stmt->close();
            $conn->close();
            return $map;
        }
        
        $stmt->close();
        $conn->close();
        
        // 如果沒有地圖，返回預設地圖
        return getDefaultMap();
    } catch (Exception $e) {
        error_log("獲取地圖錯誤: " . $e->getMessage());
        return getDefaultMap();
    }
}

// 獲取下一關地圖ID
function getNextMapId($currentMapId) {
    try {
        $conn = getDatabaseConnection();
        
        // 獲取當前地圖的 id，查找下一個啟用的地圖
        $sql = "SELECT id FROM game_code_maps WHERE id > ? AND is_active = 1 ORDER BY id ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $currentMapId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $nextId = $row['id'];
            $stmt->close();
            $conn->close();
            return $nextId;
        }
        
        $stmt->close();
        $conn->close();
        return null; // 沒有下一關
    } catch (Exception $e) {
        error_log("獲取下一關錯誤: " . $e->getMessage());
        return null;
    }
}

function getDefaultMap() {
    // 預設 10x10 地圖
    $mapData = [];
    for ($y = 0; $y < 10; $y++) {
        for ($x = 0; $x < 10; $x++) {
            $mapData[$y][$x] = 'empty'; // empty, wall, obstacle
        }
    }
    
    // 添加一些障礙物
    $mapData[2][3] = 'wall';
    $mapData[3][3] = 'wall';
    $mapData[4][5] = 'wall';
    $mapData[5][5] = 'wall';
    $mapData[6][7] = 'wall';
    
    return [
        'id' => 0,
        'name' => '預設地圖',
        'width' => 10,
        'height' => 10,
        'start' => ['x' => 0, 'y' => 0],
        'end' => ['x' => 9, 'y' => 9],
        'map_data' => $mapData
    ];
}

// 從 URL 參數獲取地圖ID，預設為1
$currentMapId = isset($_GET['map_id']) ? (int)$_GET['map_id'] : 1;
$map = getGameMap($currentMapId);
$nextMapId = getNextMapId($currentMapId);
$characterImage = 'http://localhost/game/pixilart-drawing.png';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>程式碼挑戰 - 康寧大學</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            color: #2c3e50;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 100px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .game-main {
            flex: 1;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
			margin-top: 100px;
        }

        /* 遊戲標題區域 */
        .game-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .game-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: headerGlow 3s ease-in-out infinite;
        }

        @keyframes headerGlow {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .game-title {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .game-title::before {
            content: '🎮';
            font-size: 40px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .game-stats {
            display: flex;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
        }

        .game-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 1024px) {
            .game-container {
                grid-template-columns: 1fr;
            }
        }

        /* 地圖區域 */
        .map-section {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .map-title {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .map-grid {
            display: grid;
            gap: 2px;
            background: #dee2e6;
            padding: 2px;
            border-radius: 5px;
            margin: 0 auto;
        }

        .grid-cell {
            background: #ffffff;
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border: 1px solid #e9ecef;
        }

        .grid-cell.wall {
            background: #6c757d;
        }

        .grid-cell.start {
            background: #4ade80;
        }

        .grid-cell.end {
            background: #ff6b6b;
        }

        .grid-cell.character {
            background: #fff3cd;
        }

        .character-img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            position: absolute;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .start-marker, .end-marker {
            font-size: 24px;
            font-weight: bold;
        }

        /* 積木區域 */
        .blocks-section {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 200px);
        }

        .blocks-header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #667eea;
        }

        .blocks-toolbox {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }

        .toolbox-category {
            margin-bottom: 15px;
        }

        .toolbox-category-title {
            font-size: 14px;
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toolbox-category-title::before {
            content: '📦';
            font-size: 16px;
        }

        .block-item {
            padding: 12px 18px;
            margin: 6px 0;
            cursor: grab;
            user-select: none;
            font-weight: bold;
            transition: all 0.2s;
            font-size: 15px;
            position: relative;
            min-height: 45px;
            display: flex;
            align-items: center;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left-width: 4px;
        }

        /* 動作積木（紫色矩形，立體效果） */
        .block-item.action {
            background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);
            color: #ffffff;
            border-left-color: #6c3483;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                inset -1px 0 0 rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .block-item.action::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid #6c3483;
        }

        .block-item.action::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-left: 6px solid #6c3483;
        }

        .block-item.action:hover {
            background: linear-gradient(135deg, #7d3c98 0%, #6c3483 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 5px 10px rgba(142, 68, 173, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        /* 控制積木（橙色 C 形，立體效果） */
        .block-item.control {
            background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%);
            color: #ffffff;
            border-left-color: #d35400;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                inset -1px 0 0 rgba(0, 0, 0, 0.1);
            border-radius: 4px 4px 0 0;
            padding-bottom: 25px;
            clip-path: polygon(8px 0%, 100% 0%, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0% 100%, 0% 8px);
        }

        .block-item.control::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid #d35400;
        }

        .block-item.control::after {
            content: '';
            position: absolute;
            bottom: -18px;
            left: 0;
            right: 0;
            height: 18px;
            background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%);
            border-left: 4px solid #d35400;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            clip-path: polygon(0% 0%, 8px 0%, 8px 100%, calc(100% - 8px) 100%, calc(100% - 8px) 0%, 100% 0%, 100% 100%, 0% 100%);
        }

        .block-item.control:hover {
            background: linear-gradient(135deg, #e6850e 0%, #d35400 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 5px 10px rgba(255, 149, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .block-item.control:hover::after {
            background: linear-gradient(135deg, #e6850e 0%, #d35400 100%);
        }

        .block-item:active {
            cursor: grabbing;
            opacity: 0.9;
            transform: scale(0.98);
        }

        .blocks-workspace {
            flex: 1;
            min-height: 300px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            overflow-y: auto;
        }

        .blocks-workspace.drag-over {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .block-placed {
            padding: 12px 15px;
            margin: 2px 0;
            cursor: move;
            position: relative;
            user-select: none;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        /* 動作積木（紫色矩形，立體積木效果） */
        .block-placed.action {
            background: linear-gradient(135deg, #8e44ad 0%, #7d3c98 100%);
            color: #ffffff;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left: 4px solid #6c3483;
            border-radius: 4px;
            margin-left: 0;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                inset -1px 0 0 rgba(0, 0, 0, 0.1);
        }

        .block-placed.action::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid #6c3483;
        }

        .block-placed.action::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-left: 6px solid #6c3483;
        }

        .block-placed.action:hover {
            background: linear-gradient(135deg, #7d3c98 0%, #6c3483 100%);
            box-shadow: 
                0 5px 10px rgba(142, 68, 173, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .block-placed.action:hover::before {
            border-right-color: #6c3483;
        }

        .block-placed.action:hover::after {
            border-left-color: #6c3483;
        }

        /* 控制積木（橙色 C 形，立體積木效果） */
        .block-placed.control {
            background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%);
            color: #ffffff;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left: 4px solid #d35400;
            border-radius: 4px 4px 0 0;
            padding: 15px;
            flex-direction: column;
            align-items: stretch;
            margin-left: 0;
            padding-bottom: 25px;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                inset -1px 0 0 rgba(0, 0, 0, 0.1);
            clip-path: polygon(8px 0%, 100% 0%, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0% 100%, 0% 8px);
        }

        .block-placed.control::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid #d35400;
        }

        .block-placed.control::after {
            content: '';
            position: absolute;
            bottom: -18px;
            left: 0;
            right: 0;
            height: 18px;
            background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%);
            border-left: 4px solid #d35400;
            box-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            clip-path: polygon(0% 0%, 8px 0%, 8px 100%, calc(100% - 8px) 100%, calc(100% - 8px) 0%, 100% 0%, 100% 100%, 0% 100%);
        }

        .block-placed.control:hover {
            background: linear-gradient(135deg, #e6850e 0%, #d35400 100%);
            box-shadow: 
                0 5px 10px rgba(255, 149, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .block-placed.control:hover::before {
            border-right-color: #d35400;
        }

        .block-placed.control:hover::after {
            background: linear-gradient(135deg, #e6850e 0%, #d35400 100%);
        }

        .block-number-input {
            width: 60px;
            padding: 5px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }

        .block-number-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .block-loop-count {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .block-loop-content {
            margin-left: 20px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            min-height: 50px;
        }

        .block-remove {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            background: #ff4444;
            color: #fff;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .control-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-run {
            background: #4ade80;
            color: #fff;
        }

        .btn-run:hover {
            background: #22c55e;
        }

        .btn-reset {
            background: #ff6b6b;
            color: #fff;
        }

        .btn-reset:hover {
            background: #ef4444;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 結果訊息 */
        .result-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            display: none;
        }

        .result-message.success {
            background: #d1fae5;
            color: #065f46;
            display: block;
        }

        .result-message.error {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }

        /* 彈跳視窗 */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .modal-title.failure {
            color: #ff4444;
        }

        .modal-title.success {
            color: #4ade80;
        }

        .modal-message {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-btn-restart {
            background: #667eea;
            color: #fff;
        }

        .modal-btn-restart:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .modal-btn-leave {
            background: #6c757d;
            color: #fff;
        }

        .modal-btn-leave:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .modal-btn-next {
            background: #667eea;
            color: #fff;
        }

        .modal-btn-next:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }

            .game-container {
                grid-template-columns: 1fr;
            }

            .blocks-section {
                max-height: none;
            }
        }
    </style>
</head>
<?php include("share/header.php"); ?>
<body>
    <div class="game-main">
        <!-- 遊戲標題區域 -->
        <div class="game-header">
            <div class="game-title">程式碼挑戰</div>
            <div class="game-stats">
                <div class="stat-item">
                    <div class="stat-label">關卡</div>
                    <div class="stat-value" id="levelDisplay"><?= $currentMapId ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">步數</div>
                    <div class="stat-value" id="stepDisplay">0</div>
                </div>
            </div>
        </div>
        <div class="game-container">
            <!-- 地圖區域 -->
            <div class="map-section">
                <div class="map-header">
                    <div class="map-title"><?= htmlspecialchars($map['name']) ?></div>
                    <div>
                        <span style="background: #4ade80; padding: 5px 10px; border-radius: 5px; color: #fff; margin-right: 10px;">起點</span>
                        <span style="background: #ff6b6b; padding: 5px 10px; border-radius: 5px; color: #fff;">終點</span>
                    </div>
                </div>
                <div class="map-grid" id="mapGrid" style="grid-template-columns: repeat(<?= $map['width'] ?>, 1fr); grid-template-rows: repeat(<?= $map['height'] ?>, 1fr);"></div>
            </div>

            <!-- 積木區域 -->
            <div class="blocks-section">
                <div class="blocks-header">📦 積木工具箱</div>
                
                <div class="blocks-toolbox">
                    <div class="toolbox-category">
                        <div class="toolbox-category-title">🎮 動作</div>
                        <div class="block-item action" draggable="true" data-block="move-up">⬆️ 向上移動</div>
                        <div class="block-item action" draggable="true" data-block="move-down">⬇️ 向下移動</div>
                        <div class="block-item action" draggable="true" data-block="move-left">⬅️ 向左移動</div>
                        <div class="block-item action" draggable="true" data-block="move-right">➡️ 向右移動</div>
                    </div>
                    
                    <div class="toolbox-category">
                        <div class="toolbox-category-title">🔄 控制</div>
                        <div class="block-item control" draggable="true" data-block="loop">重複執行</div>
                    </div>
                </div>

                <div class="blocks-header">🔧 程式區</div>
                <div class="blocks-workspace" id="workspace"></div>

                <div class="control-buttons">
                    <button class="btn btn-run" id="btnRun">▶️ 執行程式</button>
                    <button class="btn btn-reset" id="btnReset">🔄 重置</button>
                </div>

                <div class="result-message" id="resultMessage"></div>
            </div>
        </div>
    </div>

    <!-- 失敗彈跳視窗 -->
    <div class="modal" id="failureModal">
        <div class="modal-content">
            <div class="modal-title failure">❌ 失敗</div>
            <div class="modal-message">很遺憾，角色未能成功到達終點！<br>請重新設計程式或選擇離開。</div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-restart" onclick="restartGame()">🔄 重新開始</button>
                <button class="modal-btn modal-btn-leave" onclick="leaveGame()">🚪 離開</button>
            </div>
        </div>
    </div>

    <!-- 成功彈跳視窗 -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-title success">🎉 成功</div>
            <div class="modal-message">恭喜！角色成功到達終點！<br>您完成了這個挑戰！</div>
            <div class="modal-buttons">
                <?php if ($nextMapId): ?>
                <button class="modal-btn modal-btn-next" onclick="nextLevel()">➡️ 下一關</button>
                <?php endif; ?>
                <button class="modal-btn modal-btn-restart" onclick="restartGame()">🔄 再來一局</button>
                <button class="modal-btn modal-btn-leave" onclick="leaveGame()">🚪 離開</button>
            </div>
        </div>
    </div>

    <?php include("share/footer.php"); ?>

    <script>
        // 地圖資料
        const mapData = <?= json_encode($map) ?>;
        const characterImage = '<?= htmlspecialchars($characterImage) ?>';
        const currentMapId = <?= json_encode($currentMapId) ?>;
        const nextMapId = <?= $nextMapId ? json_encode($nextMapId) : 'null' ?>;
        
        // 遊戲狀態
        let characterPos = { x: mapData.start.x, y: mapData.start.y };
        let placedBlocks = [];
        let isRunning = false;
        let stepCount = 0;
        
        // 更新關卡顯示
        function updateLevelDisplay() {
            document.getElementById('levelDisplay').textContent = currentMapId;
        }
        
        // 更新步數顯示
        function updateStepDisplay() {
            document.getElementById('stepDisplay').textContent = stepCount;
        }
        
        // 初始化時更新關卡顯示
        updateLevelDisplay();

        // 初始化地圖
        function initMap() {
            const mapGrid = document.getElementById('mapGrid');
            mapGrid.innerHTML = '';

            for (let y = 0; y < mapData.height; y++) {
                for (let x = 0; x < mapData.width; x++) {
                    const cell = document.createElement('div');
                    cell.className = 'grid-cell';
                    cell.dataset.x = x;
                    cell.dataset.y = y;

                    // 設置格子類型
                    const cellType = mapData.map_data[y][x];
                    if (cellType === 'wall') {
                        cell.classList.add('wall');
                    }

                    // 起點
                    if (x === mapData.start.x && y === mapData.start.y) {
                        cell.classList.add('start');
                        cell.innerHTML = '<div class="start-marker">🚩</div>';
                    }

                    // 終點
                    if (x === mapData.end.x && y === mapData.end.y) {
                        cell.classList.add('end');
                        cell.innerHTML = '<div class="end-marker">🏁</div>';
                    }

                    // 角色位置
                    if (x === characterPos.x && y === characterPos.y) {
                        cell.classList.add('character');
                        const img = document.createElement('img');
                        img.src = characterImage;
                        img.className = 'character-img';
                        img.alt = '角色';
                        cell.appendChild(img);
                    }

                    mapGrid.appendChild(cell);
                }
            }
        }

        // 積木拖放功能
        const toolbox = document.querySelector('.blocks-toolbox');
        const workspace = document.getElementById('workspace');

        // 工具箱積木拖動
        toolbox.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('block-item')) {
                e.dataTransfer.setData('text/plain', e.target.dataset.block);
            }
        });

        // 工作區放置
        workspace.addEventListener('dragover', (e) => {
            e.preventDefault();
            workspace.classList.add('drag-over');
        });

        workspace.addEventListener('dragleave', () => {
            workspace.classList.remove('drag-over');
        });

        workspace.addEventListener('drop', (e) => {
            e.preventDefault();
            workspace.classList.remove('drag-over');
            
            const blockType = e.dataTransfer.getData('text/plain');
            if (blockType) {
                addBlockToWorkspace(blockType);
            }
        });

        // 添加積木到工作區
        function addBlockToWorkspace(blockType) {
            const block = document.createElement('div');
            block.className = 'block-placed';
            block.dataset.block = blockType;
            block.draggable = true;
            
            if (blockType === 'loop') {
                block.classList.add('control');
                
                // 迴圈標題
                const loopHeader = document.createElement('div');
                loopHeader.className = 'block-loop-count';
                loopHeader.style.display = 'flex';
                loopHeader.style.alignItems = 'center';
                loopHeader.style.gap = '8px';
                loopHeader.style.marginBottom = '10px';
                loopHeader.innerHTML = '重複 <input type="number" class="block-number-input" value="2" min="1" max="10" data-param="count" placeholder="次數" style="width: 50px;"> 次';
                block.appendChild(loopHeader);
                
                // 迴圈內容區
                const loopContent = document.createElement('div');
                loopContent.className = 'block-loop-content';
                loopContent.dataset.loopContent = 'true';
                loopContent.style.marginLeft = '20px';
                loopContent.style.padding = '10px';
                loopContent.style.background = 'rgba(255, 255, 255, 0.1)';
                loopContent.style.borderRadius = '5px';
                loopContent.style.minHeight = '50px';
                block.appendChild(loopContent);
            } else {
                // 動作積木（帶數字輸入）
                block.classList.add('action');
                
                const blockNames = {
                    'move-up': '⬆️ 向上移動',
                    'move-down': '⬇️ 向下移動',
                    'move-left': '⬅️ 向左移動',
                    'move-right': '➡️ 向右移動'
                };
                
                const text = document.createElement('span');
                text.textContent = blockNames[blockType] + ' ';
                block.appendChild(text);
                
                const numberInput = document.createElement('input');
                numberInput.type = 'number';
                numberInput.className = 'block-number-input';
                numberInput.value = '1';
                numberInput.min = '1';
                numberInput.max = '10';
                numberInput.dataset.param = 'steps';
                numberInput.style.width = '50px';
                numberInput.onchange = updatePlacedBlocks;
                block.appendChild(numberInput);
                
                const unit = document.createElement('span');
                unit.textContent = ' 格';
                block.appendChild(unit);
            }
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'block-remove';
            removeBtn.textContent = '×';
            removeBtn.onclick = () => {
                block.remove();
                updatePlacedBlocks();
            };
            
            block.appendChild(removeBtn);
            
            // 如果是迴圈，允許在迴圈內容區放置積木
            if (blockType === 'loop') {
                const loopContent = block.querySelector('.block-loop-content');
                setupDropZone(loopContent);
            }
            
            workspace.appendChild(block);
            updatePlacedBlocks();
        }

        // 設置拖放區域
        function setupDropZone(container) {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.style.border = '2px dashed #667eea';
                container.style.background = '#f0f4ff';
            });

            container.addEventListener('dragleave', () => {
                container.style.border = 'none';
                container.style.background = 'rgba(255, 255, 255, 0.1)';
            });

            container.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.style.border = 'none';
                container.style.background = 'rgba(255, 255, 255, 0.1)';
                
                const blockType = e.dataTransfer.getData('text/plain');
                if (blockType && blockType !== 'loop') {
                    addBlockToLoop(container, blockType);
                }
            });
        }
        
        // 在迴圈內添加積木
        function addBlockToLoop(loopContent, blockType) {
            const block = document.createElement('div');
            block.className = 'block-placed action';
            block.dataset.block = blockType;
            block.draggable = true;
            
            const blockNames = {
                'move-up': '⬆️ 向上移動',
                'move-down': '⬇️ 向下移動',
                'move-left': '⬅️ 向左移動',
                'move-right': '➡️ 向右移動'
            };
            
            const text = document.createElement('span');
            text.textContent = blockNames[blockType] + ' ';
            block.appendChild(text);
            
            const numberInput = document.createElement('input');
            numberInput.type = 'number';
            numberInput.className = 'block-number-input';
            numberInput.value = '1';
            numberInput.min = '1';
            numberInput.max = '10';
            numberInput.dataset.param = 'steps';
            numberInput.style.width = '50px';
            numberInput.onchange = updatePlacedBlocks;
            block.appendChild(numberInput);
            
            const unit = document.createElement('span');
            unit.textContent = ' 格';
            block.appendChild(unit);
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'block-remove';
            removeBtn.textContent = '×';
            removeBtn.onclick = () => {
                block.remove();
                updatePlacedBlocks();
            };
            block.appendChild(removeBtn);
            
            loopContent.appendChild(block);
            updatePlacedBlocks();
        }

        // 更新已放置的積木陣列（遞迴處理迴圈）
        function updatePlacedBlocks() {
            placedBlocks = [];
            const topLevelBlocks = workspace.querySelectorAll('.block-placed:not(.block-loop-content .block-placed)');
            
            topLevelBlocks.forEach(block => {
                const blockData = extractBlockData(block);
                if (blockData) {
                    placedBlocks.push(blockData);
                }
            });
        }

        // 提取積木資料（包含參數和迴圈內容）
        function extractBlockData(blockElement) {
            const blockType = blockElement.dataset.block;
            const blockData = { type: blockType };
            
            if (blockType === 'loop') {
                // 獲取迴圈次數
                const countInput = blockElement.querySelector('[data-param="count"]');
                blockData.count = parseInt(countInput?.value || 2);
                
                // 獲取迴圈內的積木
                const loopContent = blockElement.querySelector('.block-loop-content');
                blockData.blocks = [];
                if (loopContent) {
                    const innerBlocks = loopContent.querySelectorAll('.block-placed');
                    innerBlocks.forEach(innerBlock => {
                        const innerData = extractBlockData(innerBlock);
                        if (innerData) {
                            blockData.blocks.push(innerData);
                        }
                    });
                }
            } else {
                // 獲取移動步數
                const stepsInput = blockElement.querySelector('[data-param="steps"]');
                blockData.steps = parseInt(stepsInput?.value || 1);
            }
            
            return blockData;
        }

        // 執行程式
        document.getElementById('btnRun').addEventListener('click', async () => {
            if (isRunning) return;
            
            updatePlacedBlocks();
            if (placedBlocks.length === 0) {
                showResult('請先添加積木！', 'error');
                return;
            }

            isRunning = true;
            document.getElementById('btnRun').disabled = true;
            document.getElementById('resultMessage').className = 'result-message';
            
            // 重置角色位置
            characterPos = { x: mapData.start.x, y: mapData.start.y };
            initMap();
            
            // 執行每個積木
            await executeBlocks(placedBlocks);
            
            // 檢查是否到達終點
            if (characterPos.x === mapData.end.x && characterPos.y === mapData.end.y) {
                // 顯示成功視窗
                document.getElementById('successModal').classList.add('show');
            } else {
                // 顯示失敗視窗
                document.getElementById('failureModal').classList.add('show');
            }
            
            isRunning = false;
            document.getElementById('btnRun').disabled = false;
        });

        // 執行積木陣列
        async function executeBlocks(blocks) {
            for (let i = 0; i < blocks.length; i++) {
                const block = blocks[i];
                
                if (block.type === 'loop') {
                    // 執行迴圈
                    const loopCount = block.count || 2;
                    for (let j = 0; j < loopCount; j++) {
                        if (block.blocks && block.blocks.length > 0) {
                            await executeBlocks(block.blocks);
                        }
                    }
                } else {
                    // 執行移動積木
                    const steps = block.steps || 1;
                    for (let step = 0; step < steps; step++) {
                        await executeBlock(block.type);
                        stepCount++;
                        updateStepDisplay();
                        
                        // 檢查是否撞牆或出界
                        if (characterPos.x < 0 || characterPos.x >= mapData.width || 
                            characterPos.y < 0 || characterPos.y >= mapData.height) {
                            showResult('角色超出地圖範圍！', 'error');
                            isRunning = false;
                            document.getElementById('btnRun').disabled = false;
                            return;
                        }
                        
                        const cellType = mapData.map_data[characterPos.y][characterPos.x];
                        if (cellType === 'wall') {
                            showResult('角色撞到牆壁！', 'error');
                            isRunning = false;
                            document.getElementById('btnRun').disabled = false;
                            return;
                        }
                        
                        // 更新地圖顯示
                        initMap();
                        await sleep(300);
                    }
                }
            }
        }

        // 執行單個積木
        function executeBlock(blockType) {
            return new Promise((resolve) => {
                switch(blockType) {
                    case 'move-up':
                        characterPos.y--;
                        break;
                    case 'move-down':
                        characterPos.y++;
                        break;
                    case 'move-left':
                        characterPos.x--;
                        break;
                    case 'move-right':
                        characterPos.x++;
                        break;
                }
                resolve();
            });
        }

        // 重置
        document.getElementById('btnReset').addEventListener('click', () => {
            restartGame();
        });

        // 重新開始遊戲
        function restartGame() {
            characterPos = { x: mapData.start.x, y: mapData.start.y };
            workspace.innerHTML = '';
            placedBlocks = [];
            stepCount = 0;
            updateStepDisplay();
            document.getElementById('resultMessage').className = 'result-message';
            document.getElementById('failureModal').classList.remove('show');
            document.getElementById('successModal').classList.remove('show');
            isRunning = false;
            document.getElementById('btnRun').disabled = false;
            initMap();
        }

        // 離開遊戲
        function leaveGame() {
            window.location.href = 'game.php';
        }

        // 下一關
        function nextLevel() {
            if (nextMapId) {
                window.location.href = 'game_code.php?map_id=' + nextMapId;
            } else {
                alert('恭喜！您已經完成所有關卡！');
                leaveGame();
            }
        }

        // 顯示結果
        function showResult(message, type) {
            const resultMsg = document.getElementById('resultMessage');
            resultMsg.textContent = message;
            resultMsg.className = `result-message ${type}`;
        }

        // 工具函數
        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // 初始化
        initMap();
    </script>
</body>
</html>
