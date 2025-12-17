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

// 處理困難度選擇和地圖生成
$difficulty = $_GET['difficulty'] ?? $_SESSION['game_difficulty'] ?? null;
$reset = isset($_GET['reset']) && $_GET['reset'] == '1';
$fromGamePage = isset($_GET['from_game']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'game.php') !== false);

// 如果從 game.php 進入，強制重置困難度選擇
if ($fromGamePage && !isset($_GET['difficulty'])) {
    unset($_SESSION['game_difficulty']);
    unset($_SESSION['game_level']);
    unset($_SESSION['game_map']);
}

// 如果選擇了困難度或重置，初始化遊戲狀態
// 只有在明確重置（reset=1）或第一次選擇困難度時才重置關卡數
if ($difficulty) {
    if ($reset) {
        // 明確重置：重置關卡數到 1
        $_SESSION['game_difficulty'] = $difficulty;
        $_SESSION['game_level'] = 1;
        $_SESSION['game_map'] = null;
    } elseif (!isset($_SESSION['game_difficulty'])) {
        // 第一次選擇困難度：初始化
        $_SESSION['game_difficulty'] = $difficulty;
        $_SESSION['game_level'] = 1;
        $_SESSION['game_map'] = null;
    } elseif ($_SESSION['game_difficulty'] !== $difficulty) {
        // 困難度改變：重置關卡數
        $_SESSION['game_difficulty'] = $difficulty;
        $_SESSION['game_level'] = 1;
        $_SESSION['game_map'] = null;
    }
    // 如果困難度相同且不是重置，保持現有的關卡數
}

// 如果沒有選擇困難度，顯示選擇介面
if (!isset($_SESSION['game_difficulty'])) {
    $showDifficultySelection = true;
    $map = null;
    $currentLevel = 0;
    $currentMapId = 0;
    $nextMapId = null;
} else {
    $showDifficultySelection = false;
    // 確保關卡數是整數
    $currentLevel = isset($_SESSION['game_level']) ? (int)$_SESSION['game_level'] : 1;
    $currentMapId = $currentLevel; // 使用關卡數作為地圖ID
    
    // 如果有現存的地圖且關卡沒變，使用現存地圖
    if (isset($_SESSION['game_map']) && isset($_SESSION['game_map']['level']) && $_SESSION['game_map']['level'] == $currentLevel) {
        $map = $_SESSION['game_map']['data'];
    } else {
        // 生成新地圖（使用 Ollama）
        $map = generateMapForLevel($_SESSION['game_difficulty'], $currentLevel);
        $_SESSION['game_map'] = ['level' => $currentLevel, 'data' => $map];
    }
    
    // 下一關永遠存在（無限關卡）
    $nextMapId = $currentLevel + 1;
}

$characterImage = 'http://localhost/game/pixilart-drawing.png';

// 生成地圖的函數
function generateMapForLevel($difficulty, $level) {
    $url = 'game_map_generator_api.php?action=generate_map&difficulty=' . urlencode($difficulty) . '&level=' . $level;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success'] && isset($data['map'])) {
            return $data['map'];
        }
    }
    
    // 如果 API 失敗，使用預設地圖生成
    return generateDefaultMapForDifficulty($difficulty, $level);
}

// 根據困難度生成預設地圖
function generateDefaultMapForDifficulty($difficulty, $level) {
    $configs = [
        'easy' => ['width' => 8, 'height' => 8, 'wall_ratio' => 0.15],
        'medium' => ['width' => 12, 'height' => 12, 'wall_ratio' => 0.25],
        'hard' => ['width' => 15, 'height' => 15, 'wall_ratio' => 0.35]
    ];
    
    $config = $configs[$difficulty] ?? $configs['easy'];
    $levelMultiplier = 1 + ($level - 1) * 0.1;
    $width = (int)($config['width'] * $levelMultiplier);
    $height = (int)($config['height'] * $levelMultiplier);
    $wallRatio = min(0.5, $config['wall_ratio'] * $levelMultiplier);
    
    $mapData = [];
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $mapData[$y][$x] = 'empty';
        }
    }
    
    // 簡單的牆壁生成
    $wallCount = (int)(($width * $height) * $wallRatio);
    for ($i = 0; $i < $wallCount; $i++) {
        $x = rand(0, $width - 1);
        $y = rand(0, $height - 1);
        if (($x != 0 || $y != 0) && ($x != $width - 1 || $y != $height - 1)) {
            $mapData[$y][$x] = 'wall';
        }
    }
    
    return [
        'id' => 0,
        'name' => "關卡 {$level}",
        'width' => $width,
        'height' => $height,
        'start' => ['x' => 0, 'y' => 0],
        'end' => ['x' => $width - 1, 'y' => $height - 1],
        'map_data' => $mapData
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>程式碼挑戰 - 康寧大學</title>
    <!-- Blockly 庫 -->
    <script src="https://unpkg.com/blockly@10.4.0/blockly_compressed.js"></script>
    <script src="https://unpkg.com/blockly@10.4.0/blocks_compressed.js"></script>
    <script src="https://unpkg.com/blockly@10.4.0/javascript_compressed.js"></script>
    <script src="https://unpkg.com/blockly@10.4.0/msg/zh-hant.js"></script>
    <link href="https://unpkg.com/blockly@10.4.0/blockly.min.css" rel="stylesheet" />
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
            max-width: 1600px;
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
            grid-template-columns: 1fr 600px;
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
            width:85%;
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
            padding: 10px 16px;
            margin: 8px 0;
            cursor: grab;
            user-select: none;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 14px;
            position: relative;
            min-height: 32px;
            display: flex;
            align-items: center;
            border: 2px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            font-family: "Google Sans", "Segoe UI", Arial, sans-serif;
        }

        /* Blockly 風格動作積木（Statement blocks） */
        .block-item.action {
            background: #a65ba6; /* Blockly 的動作積木顏色 */
            color: #ffffff;
            border-color: rgba(0, 0, 0, 0.25);
            border-radius: 4px;
            position: relative;
        }

        /* Blockly 風格的連接點 - 頂部凹槽 */
        .block-item.action::before {
            content: '';
            position: absolute;
            left: 8px;
            top: -6px;
            width: 20px;
            height: 12px;
            background: #a65ba6;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            z-index: 1;
        }

        /* Blockly 風格的連接點 - 底部凸起 */
        .block-item.action::after {
            content: '';
            position: absolute;
            right: 8px;
            bottom: -6px;
            width: 20px;
            height: 12px;
            background: #a65ba6;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-top: none;
            border-radius: 0 0 4px 4px;
            z-index: 1;
        }

        .block-item.action:hover {
            background: #8e4a8e;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Blockly 風格控制積木（C 形，可以嵌套） */
        .block-item.control {
            background: #ff9800; /* Blockly 的控制積木顏色 */
            color: #ffffff;
            border-color: rgba(0, 0, 0, 0.25);
            padding: 10px 16px;
            margin-bottom: 0;
            border-radius: 4px 4px 0 0;
            position: relative;
        }

        /* Blockly C 形積木的連接點 */
        .block-item.control::before {
            content: '';
            position: absolute;
            left: 8px;
            top: -6px;
            width: 20px;
            height: 12px;
            background: #ff9800;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            z-index: 1;
        }

        .block-item.control::after {
            content: '';
            position: absolute;
            right: 8px;
            bottom: -6px;
            width: 20px;
            height: 12px;
            background: #ff9800;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-top: none;
            border-radius: 0 0 4px 4px;
            z-index: 1;
        }

        .block-item.control:hover {
            background: #e68900;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            padding: 10px 16px;
            margin: 2px 0;
            cursor: move;
            position: relative;
            user-select: none;
            min-height: 32px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(0, 0, 0, 0.15);
            font-family: "Google Sans", "Segoe UI", Arial, sans-serif;
            font-weight: 500;
            font-size: 14px;
        }

        /* Blockly 風格已放置的動作積木 */
        .block-placed.action {
            background: #a65ba6;
            color: #ffffff;
            border-color: rgba(0, 0, 0, 0.25);
            margin-left: 0;
            border-radius: 4px;
            position: relative;
        }

        /* Blockly 風格的連接點 - 頂部凹槽 */
        .block-placed.action::before {
            content: '';
            position: absolute;
            left: 8px;
            top: -6px;
            width: 20px;
            height: 12px;
            background: #a65ba6;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            z-index: 1;
        }

        /* Blockly 風格的連接點 - 底部凸起 */
        .block-placed.action::after {
            content: '';
            position: absolute;
            right: 8px;
            bottom: -6px;
            width: 20px;
            height: 12px;
            background: #a65ba6;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-top: none;
            border-radius: 0 0 4px 4px;
            z-index: 1;
        }

        .block-placed.action:hover {
            background: #8e4a8e;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Blockly 風格已放置的控制積木 */
        .block-placed.control {
            background: #ff9800;
            color: #ffffff;
            border: 2px solid rgba(0, 0, 0, 0.25);
            padding: 10px 16px;
            flex-direction: column;
            align-items: stretch;
            margin-left: 0;
            border-radius: 4px 4px 0 0;
            position: relative;
        }

        /* Blockly C 形積木的連接點 */
        .block-placed.control::before {
            content: '';
            position: absolute;
            left: 8px;
            top: -6px;
            width: 20px;
            height: 12px;
            background: #ff9800;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            z-index: 1;
        }

        .block-placed.control::after {
            content: '';
            position: absolute;
            right: 8px;
            bottom: -6px;
            width: 20px;
            height: 12px;
            background: #ff9800;
            border: 2px solid rgba(0, 0, 0, 0.25);
            border-top: none;
            border-radius: 0 0 4px 4px;
            z-index: 1;
        }

        .block-placed.control:hover {
            background: #e68900;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

        /* 返回按鈕樣式 */
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            width: 30%;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* 調整困難度按鈕 */
        .btn-change-difficulty {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 20px;
        }

        .btn-change-difficulty:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* 困難度選擇按鈕樣式 */
        .difficulty-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .difficulty-btn {
            background: #ffffff;
            border: 3px solid #dee2e6;
            border-radius: 15px;
            padding: 25px 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 180px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .difficulty-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .difficulty-btn.easy {
            border-color: #4ade80;
        }

        .difficulty-btn.easy:hover {
            background: #f0fdf4;
            border-color: #22c55e;
        }

        .difficulty-btn.medium {
            border-color: #fbbf24;
        }

        .difficulty-btn.medium:hover {
            background: #fffbeb;
            border-color: #f59e0b;
        }

        .difficulty-btn.hard {
            border-color: #ef4444;
        }

        .difficulty-btn.hard:hover {
            background: #fef2f2;
            border-color: #dc2626;
        }

        .difficulty-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .difficulty-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .difficulty-desc {
            font-size: 14px;
            color: #6c757d;
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
    <!-- 困難度選擇介面 -->
    <?php if ($showDifficultySelection): ?>
    <div class="modal show" id="difficultyModal">
        <div class="modal-content">
            <div class="modal-title">🎮 選擇困難度</div>
            <div class="modal-message">請選擇遊戲困難度，系統將根據您選擇的困難度生成地圖</div>
            <div class="difficulty-buttons">
                <button class="difficulty-btn easy" onclick="selectDifficulty('easy')">
                    <div class="difficulty-icon">😊</div>
                    <div class="difficulty-name">簡單</div>
                    <div class="difficulty-desc">8x8 地圖，15% 障礙物</div>
                </button>
                <button class="difficulty-btn medium" onclick="selectDifficulty('medium')">
                    <div class="difficulty-icon">😐</div>
                    <div class="difficulty-name">中等</div>
                    <div class="difficulty-desc">12x12 地圖，25% 障礙物</div>
                </button>
                <button class="difficulty-btn hard" onclick="selectDifficulty('hard')">
                    <div class="difficulty-icon">😤</div>
                    <div class="difficulty-name">困難</div>
                    <div class="difficulty-desc">15x15 地圖，35% 障礙物</div>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="game-main" <?= $showDifficultySelection ? 'style="display:none;"' : '' ?>>
        <!-- 遊戲標題區域 -->
        <div class="game-header">
            <div style="display: flex; align-items: center; gap: 15px;width: 30%;">
                <button class="btn-back" onclick="window.location.href='game.php'" title="返回遊戲列表">
                    ← 返回
                </button>
                <div class="game-title">程式碼挑戰</div>
            </div>
            <div class="game-stats">
                <div class="stat-item">
                    <div class="stat-label">關卡</div>
                    <div class="stat-value" id="levelDisplay"><?= $currentLevel ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">步數</div>
                    <div class="stat-value" id="stepDisplay">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">困難度</div>
                    <div class="stat-value" id="difficultyDisplay"><?= isset($_SESSION['game_difficulty']) ? ucfirst($_SESSION['game_difficulty']) : '' ?></div>
                </div>
                <div class="stat-item">
                    <button class="btn-change-difficulty" onclick="showChangeDifficultyModal()" title="更改困難度">
                        ⚙️ 調整困難度
                    </button>
                </div>
            </div>
        </div>
        <div class="game-container">
            <!-- 地圖區域 -->
            <?php if (!$showDifficultySelection && $map): ?>
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
            <?php endif; ?>

            <!-- 積木區域 -->
            <div class="blocks-section">
                <div class="blocks-header">📦 積木工具箱</div>
                
                <!-- Blockly 工具箱 -->
                <xml id="toolbox" style="display: none;">
                    <category name="動作" colour="160">
                        <block type="move_up"></block>
                        <block type="move_down"></block>
                        <block type="move_left"></block>
                        <block type="move_right"></block>
                    </category>
                    <category name="控制" colour="120">
                        <block type="controls_repeat_ext">
                            <value name="TIMES">
                                <shadow type="math_number">
                                    <field name="NUM">2</field>
                                </shadow>
                            </value>
                        </block>
                    </category>
                </xml>

                <div class="blocks-header">🔧 程式區</div>
                <div id="blocklyDiv" style="height: 500px; width: 100%;"></div>

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
                <button class="modal-btn modal-btn-next" onclick="nextLevel()">➡️ 下一關</button>
                <button class="modal-btn modal-btn-restart" onclick="restartGame()">🔄 再來一局</button>
                <button class="modal-btn modal-btn-leave" onclick="leaveGame()">🚪 離開</button>
            </div>
        </div>
    </div>

    <!-- 調整困難度彈跳視窗 -->
    <div class="modal" id="changeDifficultyModal">
        <div class="modal-content">
            <div class="modal-title">⚙️ 調整困難度</div>
            <div class="modal-message">選擇新的困難度，系統將重新生成地圖並重置到第一關</div>
            <div class="difficulty-buttons">
                <button class="difficulty-btn easy" onclick="changeDifficulty('easy')">
                    <div class="difficulty-icon">😊</div>
                    <div class="difficulty-name">簡單</div>
                    <div class="difficulty-desc">8x8 地圖，15% 障礙物</div>
                </button>
                <button class="difficulty-btn medium" onclick="changeDifficulty('medium')">
                    <div class="difficulty-icon">😐</div>
                    <div class="difficulty-name">中等</div>
                    <div class="difficulty-desc">12x12 地圖，25% 障礙物</div>
                </button>
                <button class="difficulty-btn hard" onclick="changeDifficulty('hard')">
                    <div class="difficulty-icon">😤</div>
                    <div class="difficulty-name">困難</div>
                    <div class="difficulty-desc">15x15 地圖，35% 障礙物</div>
                </button>
            </div>
            <div class="modal-buttons" style="margin-top: 20px;">
                <button class="modal-btn modal-btn-leave" onclick="closeChangeDifficultyModal()">取消</button>
            </div>
        </div>
    </div>

    <?php include("share/footer.php"); ?>

    <script>
        // 地圖資料
        const mapData = <?= json_encode($map) ?>;
        const characterImage = '<?= htmlspecialchars($characterImage) ?>';
        const currentMapId = <?= json_encode($currentMapId ?? 0) ?>;
        const currentLevel = <?= json_encode($currentLevel ?? 0) ?>;
        const nextMapId = <?= isset($nextMapId) ? json_encode($nextMapId) : 'null' ?>;
        const difficulty = <?= isset($_SESSION['game_difficulty']) ? json_encode($_SESSION['game_difficulty']) : 'null' ?>;
        
        // 角色圖片對應表（根據方向）
        // 圖片路徑對應關係：
        // 1. pixilart-drawing.png - 預設
        // 2. 右轉.gif - 向右轉
        // 3. 右轉回來.gif - 從右邊轉回來
        // 4. 左轉.gif - 向左轉
        // 5. 左轉回來.gif - 從左邊轉回來
        // 6. 走路.gif - 正常走路
        // 7. 往右走.gif - 往右走
        // 8. 往左走.gif - 往左走
        // 9. 背面走路.gif - 往上走
        // 10. 轉到背面 - 轉到背面
        const characterImages = {
            'default': 'assets/images/character/pixilart-drawing.png',  // 預設/站立
            'right': 'assets/images/character/往右走.gif',              // 往右走
            'left': 'assets/images/character/往左走.gif',               // 往左走
            'up': 'assets/images/character/背面走路.gif',              // 往上走（背面）
            'down': 'assets/images/character/走路.gif'                  // 往下走（正常走路）
        };
        
        // 獲取角色圖片路徑（如果圖片不存在則使用預設）
        function getCharacterImagePath(direction) {
            const path = characterImages[direction] || characterImages['default'];
            // 路徑相對於 frontend 目錄
            return path;
        }
        
        // 遊戲狀態
        let characterPos = mapData ? { x: mapData.start.x, y: mapData.start.y } : { x: 0, y: 0 };
        let currentDirection = 'default'; // 當前角色方向
        let isRunning = false;
        let stepCount = 0;
        let workspace = null;
        
        // 初始化 Blockly
        function initBlockly() {
            if (!mapData || !mapData.map_data) {
                console.warn('地圖資料不存在，無法初始化 Blockly');
                return;
            }
            
            // 檢查 Blockly 是否已載入
            if (typeof Blockly === 'undefined') {
                console.error('Blockly 庫未載入，請檢查 CDN 連接');
                setTimeout(initBlockly, 500); // 重試
                return;
            }
            
            // 檢查工具箱元素是否存在
            const toolbox = document.getElementById('toolbox');
            if (!toolbox) {
                console.error('工具箱元素不存在');
                return;
            }
            
            try {
                // 定義自定義積木（在創建工作區之前）
                defineCustomBlocks();
                
                // 創建 Blockly 工作區
                workspace = Blockly.inject('blocklyDiv', {
                    toolbox: toolbox,
                    grid: {
                        spacing: 20,
                        length: 3,
                        colour: '#ccc',
                        snap: true
                    },
                    zoom: {
                        controls: true,
                        wheel: true,
                        startScale: 1.0,
                        maxScale: 3,
                        minScale: 0.3,
                        scaleSpeed: 1.2
                    },
                    trashcan: true,
                    media: 'https://unpkg.com/blockly@10.4.0/media/',
                    theme: Blockly.Themes.Classic,
                    collapse: false,
                    comments: false,
                    disable: false,
                    maxBlocks: Infinity,
                    readOnly: false,
                    scrollbars: true,
                    sounds: true,
                    toolboxPosition: 'start'
                });
                
                // 監聽工作區變化
                workspace.addChangeListener(function(event) {
                    if (event.type === Blockly.Events.BLOCK_CREATE ||
                        event.type === Blockly.Events.BLOCK_DELETE ||
                        event.type === Blockly.Events.BLOCK_CHANGE) {
                        // 積木變化時的處理（可選）
                    }
                });
                
                console.log('Blockly 工作區初始化成功');
            } catch (error) {
                console.error('初始化 Blockly 時發生錯誤:', error);
            }
        }
        
        // 定義自定義積木
        function defineCustomBlocks() {
            // 向上移動積木
            Blockly.Blocks['move_up'] = {
                init: function() {
                    this.appendDummyInput()
                        .appendField('向上移動')
                        .appendField(new Blockly.FieldNumber(1, 1, 10), 'STEPS')
                        .appendField('格');
                    this.setPreviousStatement(true, null);
                    this.setNextStatement(true, null);
                    this.setColour(160);
                    this.setTooltip('向上移動指定格數');
                }
            };
            
            // 向下移動積木
            Blockly.Blocks['move_down'] = {
                init: function() {
                    this.appendDummyInput()
                        .appendField('向下移動')
                        .appendField(new Blockly.FieldNumber(1, 1, 10), 'STEPS')
                        .appendField('格');
                    this.setPreviousStatement(true, null);
                    this.setNextStatement(true, null);
                    this.setColour(160);
                    this.setTooltip('向下移動指定格數');
                }
            };
            
            // 向左移動積木
            Blockly.Blocks['move_left'] = {
                init: function() {
                    this.appendDummyInput()
                        .appendField('向左移動')
                        .appendField(new Blockly.FieldNumber(1, 1, 10), 'STEPS')
                        .appendField('格');
                    this.setPreviousStatement(true, null);
                    this.setNextStatement(true, null);
                    this.setColour(160);
                    this.setTooltip('向左移動指定格數');
                }
            };
            
            // 向右移動積木
            Blockly.Blocks['move_right'] = {
                init: function() {
                    this.appendDummyInput()
                        .appendField('向右移動')
                        .appendField(new Blockly.FieldNumber(1, 1, 10), 'STEPS')
                        .appendField('格');
                    this.setPreviousStatement(true, null);
                    this.setNextStatement(true, null);
                    this.setColour(160);
                    this.setTooltip('向右移動指定格數');
                }
            };
            
            // 生成 JavaScript 代碼
            Blockly.JavaScript['move_up'] = function(block) {
                const steps = block.getFieldValue('STEPS') || 1;
                return `await moveUp(${steps});\n`;
            };
            
            Blockly.JavaScript['move_down'] = function(block) {
                const steps = block.getFieldValue('STEPS') || 1;
                return `await moveDown(${steps});\n`;
            };
            
            Blockly.JavaScript['move_left'] = function(block) {
                const steps = block.getFieldValue('STEPS') || 1;
                return `await moveLeft(${steps});\n`;
            };
            
            Blockly.JavaScript['move_right'] = function(block) {
                const steps = block.getFieldValue('STEPS') || 1;
                return `await moveRight(${steps});\n`;
            };
            
            // 重複執行積木（使用 Blockly 內建的 controls_repeat_ext）
            // 注意：我們需要覆蓋內建的生成器以支持異步
            if (Blockly.JavaScript['controls_repeat_ext']) {
                const originalRepeatExt = Blockly.JavaScript['controls_repeat_ext'];
                Blockly.JavaScript['controls_repeat_ext'] = function(block) {
                    const times = Blockly.JavaScript.valueToCode(block, 'TIMES', Blockly.JavaScript.ORDER_ASSIGNMENT) || '1';
                    const branch = Blockly.JavaScript.statementToCode(block, 'DO');
                    // 確保迴圈內的代碼是異步的
                    return `for (let i = 0; i < ${times}; i++) {\n${branch}}\n`;
                };
            }
        }
        
        // 更新角色方向圖片
        function updateCharacterDirection(direction) {
            currentDirection = direction;
            // 更新地圖顯示中的角色圖片
            const characterImg = document.querySelector('.character-img');
            if (characterImg) {
                const imgPath = getCharacterImagePath(direction);
                characterImg.src = imgPath;
            }
        }
        
        // 移動函數
        async function moveUp(steps) {
            updateCharacterDirection('up');
            for (let i = 0; i < steps; i++) {
                characterPos.y--;
                stepCount++;
                updateStepDisplay();
                await checkAndUpdate();
            }
        }
        
        async function moveDown(steps) {
            updateCharacterDirection('down');
            for (let i = 0; i < steps; i++) {
                characterPos.y++;
                stepCount++;
                updateStepDisplay();
                await checkAndUpdate();
            }
        }
        
        async function moveLeft(steps) {
            updateCharacterDirection('left');
            for (let i = 0; i < steps; i++) {
                characterPos.x--;
                stepCount++;
                updateStepDisplay();
                await checkAndUpdate();
            }
        }
        
        async function moveRight(steps) {
            updateCharacterDirection('right');
            for (let i = 0; i < steps; i++) {
                characterPos.x++;
                stepCount++;
                updateStepDisplay();
                await checkAndUpdate();
            }
        }
        
        // 檢查並更新地圖
        async function checkAndUpdate() {
            // 檢查是否撞牆或出界
            if (characterPos.x < 0 || characterPos.x >= mapData.width || 
                characterPos.y < 0 || characterPos.y >= mapData.height) {
                showResult('角色超出地圖範圍！', 'error');
                isRunning = false;
                document.getElementById('btnRun').disabled = false;
                throw new Error('角色超出地圖範圍');
            }
            
            const cellType = mapData.map_data[characterPos.y][characterPos.x];
            if (cellType === 'wall') {
                showResult('角色撞到牆壁！', 'error');
                isRunning = false;
                document.getElementById('btnRun').disabled = false;
                throw new Error('角色撞到牆壁');
            }
            
            // 更新地圖顯示
            initMap();
            await sleep(300);
        }
        
        // 更新關卡顯示
        function updateLevelDisplay() {
            const levelDisplay = document.getElementById('levelDisplay');
            if (levelDisplay) {
                // 使用 currentLevel（PHP 傳入的關卡數）或 currentMapId
                levelDisplay.textContent = currentLevel || currentMapId || 1;
            }
        }
        
        // 更新步數顯示
        function updateStepDisplay() {
            document.getElementById('stepDisplay').textContent = stepCount;
        }
        
        // 初始化時更新關卡顯示
        if (mapData && mapData.map_data) {
            updateLevelDisplay();
        }

        // 初始化地圖
        function initMap() {
            if (!mapData) return;
            
            const mapGrid = document.getElementById('mapGrid');
            if (!mapGrid) return;
            
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
                        // 使用當前方向的圖片，如果沒有則使用預設圖片
                        const imgPath = getCharacterImagePath(currentDirection);
                        img.src = imgPath;
                        img.className = 'character-img';
                        img.alt = '角色';
                        img.onerror = function() {
                            // 如果圖片載入失敗，使用原始圖片
                            console.warn('角色圖片載入失敗，使用預設圖片:', imgPath);
                            this.src = characterImage;
                        };
                        cell.appendChild(img);
                    }

                    mapGrid.appendChild(cell);
                }
            }
        }

        // 初始化 Blockly（當頁面載入時）
        function initializeBlocklyWhenReady() {
            if (!mapData || !mapData.map_data) {
                console.warn('地圖資料不存在，跳過 Blockly 初始化');
                return;
            }
            
            // 檢查 Blockly 是否已載入
            if (typeof Blockly === 'undefined') {
                console.log('等待 Blockly 庫載入...');
                setTimeout(initializeBlocklyWhenReady, 200);
                return;
            }
            
            // 檢查 DOM 是否準備好
            const blocklyDiv = document.getElementById('blocklyDiv');
            if (!blocklyDiv) {
                console.log('等待 blocklyDiv 元素...');
                setTimeout(initializeBlocklyWhenReady, 200);
                return;
            }
            
            // 初始化 Blockly
            initBlockly();
        }
        
        // 當頁面載入完成後初始化
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeBlocklyWhenReady);
        } else {
            // 使用 setTimeout 確保所有資源都已載入
            setTimeout(initializeBlocklyWhenReady, 300);
        }

        // 執行程式（使用 Blockly）
        document.getElementById('btnRun').addEventListener('click', async () => {
            if (isRunning || !workspace) return;
            
            // 檢查是否有積木
            const topBlocks = workspace.getTopBlocks(true);
            if (topBlocks.length === 0) {
                showResult('請先添加積木！', 'error');
                return;
            }

            isRunning = true;
            document.getElementById('btnRun').disabled = true;
            document.getElementById('resultMessage').className = 'result-message';
            
            // 重置角色位置和步數
            characterPos = { x: mapData.start.x, y: mapData.start.y };
            currentDirection = 'default'; // 重置方向為預設
            stepCount = 0;
            updateStepDisplay();
            initMap();
            
            try {
                // 生成 JavaScript 代碼
                const code = Blockly.JavaScript.workspaceToCode(workspace);
                console.log('生成的代碼:', code);
                
                if (!code || code.trim() === '') {
                    showResult('請先添加積木！', 'error');
                    return;
                }
                
                // 執行代碼（在異步函數中）
                // 將生成的代碼包裝在異步函數中執行
                const asyncFunction = new Function(
                    'moveUp', 
                    'moveDown', 
                    'moveLeft', 
                    'moveRight', 
                    'sleep',
                    `return (async function() { 
                        try {
                            ${code}
                        } catch (e) {
                            throw e;
                        }
                    })();`
                );
                
                await asyncFunction(moveUp, moveDown, moveLeft, moveRight, sleep);
                
                // 檢查是否到達終點
                if (characterPos.x === mapData.end.x && characterPos.y === mapData.end.y) {
                    // 顯示成功視窗
                    document.getElementById('successModal').classList.add('show');
                } else {
                    // 顯示失敗視窗
                    document.getElementById('failureModal').classList.add('show');
                }
            } catch (error) {
                console.error('執行錯誤:', error);
                if (!error.message.includes('超出') && !error.message.includes('撞到')) {
                    showResult('執行程式時發生錯誤！', 'error');
                }
            } finally {
                isRunning = false;
                document.getElementById('btnRun').disabled = false;
            }
        });

        // 重置（清除 Blockly 工作區）
        document.getElementById('btnReset').addEventListener('click', () => {
            if (workspace) {
                workspace.clear();
            }
            characterPos = mapData ? { x: mapData.start.x, y: mapData.start.y } : { x: 0, y: 0 };
            currentDirection = 'default'; // 重置方向為預設
            stepCount = 0;
            updateStepDisplay();
            initMap();
        });

        // 選擇困難度
        function selectDifficulty(selectedDifficulty) {
            window.location.href = 'game_code.php?difficulty=' + encodeURIComponent(selectedDifficulty) + '&reset=1';
        }

        // 顯示調整困難度視窗
        function showChangeDifficultyModal() {
            document.getElementById('changeDifficultyModal').classList.add('show');
        }

        // 關閉調整困難度視窗
        function closeChangeDifficultyModal() {
            document.getElementById('changeDifficultyModal').classList.remove('show');
        }

        // 更改困難度
        function changeDifficulty(newDifficulty) {
            window.location.href = 'game_code.php?difficulty=' + encodeURIComponent(newDifficulty) + '&reset=1';
        }
        
        // 重新開始遊戲 - 失敗時重置到第一關
        function restartGame() {
            // 重置到第一關
            if (difficulty) {
                window.location.href = 'game_code.php?difficulty=' + encodeURIComponent(difficulty) + '&reset=1';
            } else {
                window.location.href = 'game_code.php?reset=1';
            }
        }

        // 離開遊戲
        function leaveGame() {
            window.location.href = 'game.php';
        }

        // 下一關 - 自動生成新地圖（使用 Ollama）
        function nextLevel() {
            if (!difficulty) {
                console.error('困難度未設定');
                return;
            }
            
            // 關閉成功視窗
            document.getElementById('successModal').classList.remove('show');
            
            // 增加關卡數並重新載入頁面生成新地圖
            fetch('game_code_api.php?action=increment_level', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'difficulty=' + encodeURIComponent(difficulty),
                credentials: 'same-origin' // 確保發送 session cookie
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('關卡增加回應:', data);
                if (data.success) {
                    console.log('關卡已增加到:', data.level);
                    // 重新載入頁面，移除 reset 參數避免重置關卡數
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('reset');
                    currentUrl.searchParams.delete('difficulty'); // 也移除 difficulty，使用 session 中的值
                    window.location.href = currentUrl.toString();
                } else {
                    console.error('增加關卡失敗:', data.message);
                    alert('增加關卡失敗: ' + (data.message || '未知錯誤'));
                }
            })
            .catch(error => {
                console.error('請求失敗:', error);
                alert('無法連接到伺服器，請稍後再試');
            });
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
        if (mapData && mapData.map_data) {
            initMap();
        }
        
        // 關閉困難度選擇視窗（如果已選擇）
        const difficultyModal = document.getElementById('difficultyModal');
        if (difficultyModal && !<?= $showDifficultySelection ? 'true' : 'false' ?>) {
            difficultyModal.classList.remove('show');
        }
    </script>
</body>
</html>
