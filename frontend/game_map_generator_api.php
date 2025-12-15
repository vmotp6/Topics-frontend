<?php
/**
 * 地圖生成 API - 使用 Ollama 生成地圖
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 引入必要的文件
require_once __DIR__ . '/../backend/api/ollama/ollama_service.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'generate_map':
        generateMap();
        break;
    default:
        echo json_encode(['error' => '無效的操作']);
        break;
}

function generateMap() {
    $difficulty = $_POST['difficulty'] ?? $_GET['difficulty'] ?? 'easy';
    $level = isset($_POST['level']) ? (int)$_POST['level'] : (isset($_GET['level']) ? (int)$_GET['level'] : 1);
    
    // 根據困難度設定地圖參數
    $config = getDifficultyConfig($difficulty, $level);
    
    try {
        $ollama = new OllamaService('http://localhost:11434', 'qwen2.5:3b');
        
        // 構建提示詞（會在函數內生成終點位置並存儲到 $config）
        $prompt = buildMapGenerationPrompt($config);
        
        // 調用 Ollama 生成地圖描述
        $result = $ollama->askQuestion($prompt, '', 'qwen2.5:3b');
        
        if ($result['success']) {
            // 解析 AI 回應並生成地圖（使用配置中的終點位置）
            $map = parseAIResponseAndGenerateMap($result['answer'], $config);
            
            echo json_encode([
                'success' => true,
                'map' => $map,
                'level' => $level,
                'difficulty' => $difficulty
            ]);
        } else {
            // 如果 AI 失敗，使用演算法生成地圖（會使用配置中的終點位置）
            $map = generateMapAlgorithmically($config);
            
            echo json_encode([
                'success' => true,
                'map' => $map,
                'level' => $level,
                'difficulty' => $difficulty,
                'fallback' => true
            ]);
        }
    } catch (Exception $e) {
        // 如果 Ollama 服務不可用，使用演算法生成（會使用配置中的終點位置）
        $map = generateMapAlgorithmically($config);
        
        echo json_encode([
            'success' => true,
            'map' => $map,
            'level' => $level,
            'difficulty' => $difficulty,
            'fallback' => true,
            'error' => 'Ollama 服務不可用，使用演算法生成: ' . $e->getMessage()
        ]);
    }
}

function getDifficultyConfig($difficulty, $level) {
    $baseConfig = [
        'easy' => [
            'width' => 8,
            'height' => 8,
            'wall_ratio' => 0.15,  // 15% 的牆壁
            'min_path_length' => 8
        ],
        'medium' => [
            'width' => 12,
            'height' => 12,
            'wall_ratio' => 0.25,  // 25% 的牆壁
            'min_path_length' => 15
        ],
        'hard' => [
            'width' => 15,
            'height' => 15,
            'wall_ratio' => 0.35,  // 35% 的牆壁
            'min_path_length' => 20
        ]
    ];
    
    $config = $baseConfig[$difficulty] ?? $baseConfig['easy'];
    
    // 根據關卡數增加難度
    $levelMultiplier = 1 + ($level - 1) * 0.1; // 每關增加 10% 難度
    $config['width'] = (int)($config['width'] * $levelMultiplier);
    $config['height'] = (int)($config['height'] * $levelMultiplier);
    $config['wall_ratio'] = min(0.5, $config['wall_ratio'] * $levelMultiplier); // 最多 50% 牆壁
    $config['min_path_length'] = (int)($config['min_path_length'] * $levelMultiplier);
    
    return $config;
}

function buildMapGenerationPrompt(&$config) {
    $wallCount = (int)(($config['width'] * $config['height']) * $config['wall_ratio']);
    
    // 隨機生成終點位置（但不能是起點）
    $endX = rand(1, $config['width'] - 1);
    $endY = rand(1, $config['height'] - 1);
    // 確保終點不在起點
    if ($endX == 0 && $endY == 0) {
        $endX = rand(1, $config['width'] - 1);
        $endY = rand(1, $config['height'] - 1);
    }
    
    // 將終點位置存儲到配置中（使用引用傳遞）
    $config['end_x'] = $endX;
    $config['end_y'] = $endY;
    
    $prompt = "你是一個迷宮地圖生成器。請生成一個 {$config['width']}x{$config['height']} 的迷宮地圖。\n\n";
    $prompt .= "要求：\n";
    $prompt .= "1. 地圖大小：{$config['width']} 寬 x {$config['height']} 高\n";
    $prompt .= "2. 起點位置：(0, 0) - 左上角\n";
    $prompt .= "3. 終點位置：({$endX}, {$endY})\n";
    $prompt .= "4. 牆壁數量：約 {$wallCount} 個（約 " . round($config['wall_ratio'] * 100) . "%）\n";
    $prompt .= "5. 必須確保從起點 (0,0) 到終點 ({$endX},{$endY}) 至少有一條可行路徑\n";
    $prompt .= "6. 牆壁不能放在起點 (0,0) 或終點 ({$endX},{$endY})\n\n";
    $prompt .= "請以 JSON 格式回應，只返回 JSON 物件，格式如下：\n";
    $prompt .= "{\n";
    $prompt .= "  \"walls\": [[x1, y1], [x2, y2], [x3, y3], ...],\n";
    $prompt .= "  \"description\": \"簡短的地圖描述（10字以內）\"\n";
    $prompt .= "}\n\n";
    $prompt .= "注意：\n";
    $prompt .= "- walls 陣列中的座標 [x, y] 必須在 0 到 " . ($config['width'] - 1) . " (寬度) 和 0 到 " . ($config['height'] - 1) . " (高度) 範圍內\n";
    $prompt .= "- 不能包含 [0, 0] 或 [{$endX}, {$endY}]\n";
    $prompt .= "- 只返回 JSON，不要其他文字或說明\n";
    $prompt .= "- 確保路徑存在，牆壁分布要合理\n";
    
    return $prompt;
}

function parseAIResponseAndGenerateMap($aiResponse, $config) {
    // 嘗試從 AI 回應中提取 JSON
    $jsonStart = strpos($aiResponse, '{');
    $jsonEnd = strrpos($aiResponse, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonStr = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonStr, true);
        
        if ($data && isset($data['walls'])) {
            // 使用 AI 生成的牆壁位置，並使用配置中的終點位置
            return createMapFromWalls($data['walls'], $config, $data['description'] ?? 'AI 生成地圖');
        }
    }
    
    // 如果解析失敗，使用演算法生成
    return generateMapAlgorithmically($config);
}

function generateMapAlgorithmically($config) {
    $width = $config['width'];
    $height = $config['height'];
    $wallRatio = $config['wall_ratio'];
    
    // 獲取終點位置（如果已設定，否則使用右下角）
    $endX = isset($config['end_x']) ? $config['end_x'] : ($width - 1);
    $endY = isset($config['end_y']) ? $config['end_y'] : ($height - 1);
    
    // 初始化地圖
    $mapData = [];
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $mapData[$y][$x] = 'empty';
        }
    }
    
    // 計算需要的牆壁數量
    $totalCells = $width * $height;
    $wallCount = (int)($totalCells * $wallRatio);
    
    // 確保起點和終點是空的
    $mapData[0][0] = 'empty';
    $mapData[$endY][$endX] = 'empty';
    
    // 隨機放置牆壁，但確保有路徑
    $placedWalls = 0;
    $attempts = 0;
    $maxAttempts = $wallCount * 10;
    
    while ($placedWalls < $wallCount && $attempts < $maxAttempts) {
        $x = rand(0, $width - 1);
        $y = rand(0, $height - 1);
        
        // 跳過起點和終點
        if (($x == 0 && $y == 0) || ($x == $endX && $y == $endY)) {
            $attempts++;
            continue;
        }
        
        // 如果這個位置已經是牆壁，跳過
        if ($mapData[$y][$x] === 'wall') {
            $attempts++;
            continue;
        }
        
        // 暫時放置牆壁
        $mapData[$y][$x] = 'wall';
        
        // 檢查是否還有路徑（使用動態終點）
        if (hasPath($mapData, $width, $height, 0, 0, $endX, $endY)) {
            $placedWalls++;
        } else {
            // 如果沒有路徑，撤銷這個牆壁
            $mapData[$y][$x] = 'empty';
        }
        
        $attempts++;
    }
    
    $mapName = "關卡地圖 ({$width}x{$height})";
    
    return [
        'id' => 0,
        'name' => $mapName,
        'width' => $width,
        'height' => $height,
        'start' => ['x' => 0, 'y' => 0],
        'end' => ['x' => $endX, 'y' => $endY],
        'map_data' => $mapData
    ];
}

function createMapFromWalls($walls, $config, $description) {
    $width = $config['width'];
    $height = $config['height'];
    
    // 獲取終點位置（如果已設定，否則使用右下角）
    $endX = isset($config['end_x']) ? $config['end_x'] : ($width - 1);
    $endY = isset($config['end_y']) ? $config['end_y'] : ($height - 1);
    
    // 初始化地圖
    $mapData = [];
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $mapData[$y][$x] = 'empty';
        }
    }
    
    // 放置牆壁
    foreach ($walls as $wall) {
        if (isset($wall[0]) && isset($wall[1])) {
            $x = (int)$wall[0];
            $y = (int)$wall[1];
            
            // 確保在範圍內，且不是起點或終點
            if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
                if (!($x == 0 && $y == 0) && !($x == $endX && $y == $endY)) {
                    $mapData[$y][$x] = 'wall';
                }
            }
        }
    }
    
    // 驗證路徑，如果沒有路徑則使用演算法生成
    if (!hasPath($mapData, $width, $height, 0, 0, $endX, $endY)) {
        return generateMapAlgorithmically($config);
    }
    
    return [
        'id' => 0,
        'name' => $description ?: 'AI 生成地圖',
        'width' => $width,
        'height' => $height,
        'start' => ['x' => 0, 'y' => 0],
        'end' => ['x' => $endX, 'y' => $endY],
        'map_data' => $mapData
    ];
}

// 使用 BFS 檢查是否有從起點到終點的路徑（支援動態終點）
function hasPath($mapData, $width, $height, $startX = 0, $startY = 0, $endX = null, $endY = null) {
    // 如果沒有指定終點，使用預設的右下角
    if ($endX === null) {
        $endX = $width - 1;
    }
    if ($endY === null) {
        $endY = $height - 1;
    }
    
    $visited = [];
    $queue = [[$startX, $startY]];
    $visited[$startY][$startX] = true;
    
    $directions = [[0, 1], [1, 0], [0, -1], [-1, 0]]; // 下、右、上、左
    
    while (!empty($queue)) {
        [$x, $y] = array_shift($queue);
        
        // 到達終點
        if ($x == $endX && $y == $endY) {
            return true;
        }
        
        // 檢查四個方向
        foreach ($directions as [$dx, $dy]) {
            $nx = $x + $dx;
            $ny = $y + $dy;
            
            // 檢查邊界
            if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                continue;
            }
            
            // 檢查是否訪問過
            if (isset($visited[$ny][$nx])) {
                continue;
            }
            
            // 檢查是否是牆壁
            if ($mapData[$ny][$nx] === 'wall') {
                continue;
            }
            
            $visited[$ny][$nx] = true;
            $queue[] = [$nx, $ny];
        }
    }
    
    return false;
}
?>

