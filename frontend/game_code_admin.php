<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態和管理員權限
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

if (!$isLoggedIn || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getDatabaseConnection();
        
        if ($_POST['action'] === 'save') {
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
            
            $name = $_POST['name'] ?? '新地圖';
            $width = (int)($_POST['width'] ?? 10);
            $height = (int)($_POST['height'] ?? 10);
            $startX = (int)($_POST['start_x'] ?? 0);
            $startY = (int)($_POST['start_y'] ?? 0);
            $endX = (int)($_POST['end_x'] ?? 9);
            $endY = (int)($_POST['end_y'] ?? 9);
            $mapData = json_decode($_POST['map_data'] ?? '[]', true);
            $mapId = isset($_POST['map_id']) ? (int)$_POST['map_id'] : 0;
            
            $mapDataJson = json_encode($mapData);
            
            if ($mapId > 0) {
                // 更新
                $sql = "UPDATE game_code_maps SET name = ?, width = ?, height = ?, start_x = ?, start_y = ?, end_x = ?, end_y = ?, map_data = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siiiiiisi", $name, $width, $height, $startX, $startY, $endX, $endY, $mapDataJson, $mapId);
            } else {
                // 新增
                $sql = "INSERT INTO game_code_maps (name, width, height, start_x, start_y, end_x, end_y, map_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siiiiiis", $name, $width, $height, $startX, $startY, $endX, $endY, $mapDataJson);
            }
            
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            echo json_encode(['success' => true, 'message' => '地圖保存成功！']);
            exit;
        }
    }
}

// 獲取地圖列表
function getMapList() {
    try {
        $conn = getDatabaseConnection();
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
        
        $result = $conn->query("SELECT id, name, width, height, is_active FROM game_code_maps ORDER BY id DESC");
        $maps = [];
        while ($row = $result->fetch_assoc()) {
            $maps[] = $row;
        }
        $conn->close();
        return $maps;
    } catch (Exception $e) {
        return [];
    }
}

$maps = getMapList();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>地圖設計 - 程式碼挑戰</title>
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
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            margin: 0;
            color: #667eea;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        .maps-list {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .map-item {
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .map-item:hover {
            background: #e9ecef;
        }

        .map-item.active {
            background: #667eea;
            color: #fff;
        }

        .editor-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .editor-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .map-editor {
            margin-top: 20px;
        }

        .editor-grid {
            display: grid;
            gap: 2px;
            background: #dee2e6;
            padding: 2px;
            border-radius: 5px;
            margin: 20px auto;
        }

        .editor-cell {
            background: #ffffff;
            aspect-ratio: 1;
            cursor: pointer;
            border: 1px solid #e9ecef;
            position: relative;
        }

        .editor-cell.wall {
            background: #6c757d;
        }

        .editor-cell.start {
            background: #4ade80;
        }

        .editor-cell.end {
            background: #ff6b6b;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: #fff;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #4ade80;
            color: #fff;
        }

        .btn-success:hover {
            background: #22c55e;
        }

        .btn-danger {
            background: #ff6b6b;
            color: #fff;
        }

        .btn-danger:hover {
            background: #ef4444;
        }
    </style>
</head>
<?php include("share/header.php"); ?>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🗺️ 地圖設計管理</h1>
        </div>

        <div class="admin-content">
            <div class="maps-list">
                <h3>地圖列表</h3>
                <?php foreach ($maps as $map): ?>
                <div class="map-item" onclick="loadMap(<?= $map['id'] ?>)">
                    <strong><?= htmlspecialchars($map['name']) ?></strong><br>
                    <small><?= $map['width'] ?>x<?= $map['height'] ?></small>
                </div>
                <?php endforeach; ?>
                <div class="map-item" onclick="newMap()" style="background: #667eea; color: #fff; text-align: center;">
                    + 新增地圖
                </div>
            </div>

            <div class="editor-section">
                <form id="mapForm">
                    <input type="hidden" id="map_id" name="map_id" value="0">
                    
                    <div class="form-group">
                        <label>地圖名稱</label>
                        <input type="text" id="map_name" name="name" value="新地圖" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>寬度</label>
                            <input type="number" id="map_width" name="width" value="10" min="5" max="20" required>
                        </div>
                        <div class="form-group">
                            <label>高度</label>
                            <input type="number" id="map_height" name="height" value="10" min="5" max="20" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>起點 X</label>
                            <input type="number" id="start_x" name="start_x" value="0" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>起點 Y</label>
                            <input type="number" id="start_y" name="start_y" value="0" min="0" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>終點 X</label>
                            <input type="number" id="end_x" name="end_x" value="9" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>終點 Y</label>
                            <input type="number" id="end_y" name="end_y" value="9" min="0" required>
                        </div>
                    </div>

                    <div class="editor-controls">
                        <button type="button" class="btn btn-primary" onclick="setEditMode('wall')">牆壁</button>
                        <button type="button" class="btn btn-success" onclick="setEditMode('start')">起點</button>
                        <button type="button" class="btn btn-danger" onclick="setEditMode('end')">終點</button>
                        <button type="button" class="btn" onclick="setEditMode('empty')">清除</button>
                    </div>

                    <div class="map-editor">
                        <div class="editor-grid" id="editorGrid"></div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">💾 保存地圖</button>
                        <button type="button" class="btn btn-primary" onclick="generateMap()">🔄 重新生成</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentEditMode = 'wall';
        let mapData = [];
        let mapWidth = 10;
        let mapHeight = 10;

        function setEditMode(mode) {
            currentEditMode = mode;
            document.querySelectorAll('.editor-controls .btn').forEach(btn => {
                btn.style.opacity = '1';
            });
            event.target.style.opacity = '0.7';
        }

        function generateMap() {
            mapWidth = parseInt(document.getElementById('map_width').value);
            mapHeight = parseInt(document.getElementById('map_height').value);
            
            mapData = [];
            for (let y = 0; y < mapHeight; y++) {
                mapData[y] = [];
                for (let x = 0; x < mapWidth; x++) {
                    mapData[y][x] = 'empty';
                }
            }
            
            renderEditor();
        }

        function renderEditor() {
            const grid = document.getElementById('editorGrid');
            grid.style.gridTemplateColumns = `repeat(${mapWidth}, 1fr)`;
            grid.innerHTML = '';

            for (let y = 0; y < mapHeight; y++) {
                for (let x = 0; x < mapWidth; x++) {
                    const cell = document.createElement('div');
                    cell.className = 'editor-cell';
                    cell.dataset.x = x;
                    cell.dataset.y = y;
                    
                    const cellType = mapData[y][x];
                    if (cellType === 'wall') cell.classList.add('wall');
                    if (cellType === 'start') cell.classList.add('start');
                    if (cellType === 'end') cell.classList.add('end');
                    
                    cell.onclick = () => {
                        if (currentEditMode === 'start') {
                            // 清除舊起點
                            for (let i = 0; i < mapHeight; i++) {
                                for (let j = 0; j < mapWidth; j++) {
                                    if (mapData[i][j] === 'start') {
                                        mapData[i][j] = 'empty';
                                    }
                                }
                            }
                            mapData[y][x] = 'start';
                            document.getElementById('start_x').value = x;
                            document.getElementById('start_y').value = y;
                        } else if (currentEditMode === 'end') {
                            // 清除舊終點
                            for (let i = 0; i < mapHeight; i++) {
                                for (let j = 0; j < mapWidth; j++) {
                                    if (mapData[i][j] === 'end') {
                                        mapData[i][j] = 'empty';
                                    }
                                }
                            }
                            mapData[y][x] = 'end';
                            document.getElementById('end_x').value = x;
                            document.getElementById('end_y').value = y;
                        } else {
                            mapData[y][x] = currentEditMode;
                        }
                        renderEditor();
                    };
                    
                    grid.appendChild(cell);
                }
            }
        }

        function loadMap(mapId) {
            fetch('game_code_api.php?action=get_map&id=' + mapId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('map_id').value = data.map.id;
                        document.getElementById('map_name').value = data.map.name;
                        document.getElementById('map_width').value = data.map.width;
                        document.getElementById('map_height').value = data.map.height;
                        document.getElementById('start_x').value = data.map.start_x;
                        document.getElementById('start_y').value = data.map.start_y;
                        document.getElementById('end_x').value = data.map.end_x;
                        document.getElementById('end_y').value = data.map.end_y;
                        mapData = data.map.map_data;
                        mapWidth = data.map.width;
                        mapHeight = data.map.height;
                        renderEditor();
                    }
                });
        }

        function newMap() {
            document.getElementById('map_id').value = '0';
            document.getElementById('map_name').value = '新地圖';
            document.getElementById('map_width').value = '10';
            document.getElementById('map_height').value = '10';
            document.getElementById('start_x').value = '0';
            document.getElementById('start_y').value = '0';
            document.getElementById('end_x').value = '9';
            document.getElementById('end_y').value = '9';
            generateMap();
        }

        document.getElementById('mapForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('map_id', document.getElementById('map_id').value);
            formData.append('name', document.getElementById('map_name').value);
            formData.append('width', document.getElementById('map_width').value);
            formData.append('height', document.getElementById('map_height').value);
            formData.append('start_x', document.getElementById('start_x').value);
            formData.append('start_y', document.getElementById('start_y').value);
            formData.append('end_x', document.getElementById('end_x').value);
            formData.append('end_y', document.getElementById('end_y').value);
            formData.append('map_data', JSON.stringify(mapData));
            
            fetch('game_code_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('地圖保存成功！');
                    location.reload();
                }
            });
        });

        // 初始化
        generateMap();
    </script>
</body>
</html>

