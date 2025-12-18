-- =====================================================
-- 方塊射擊遊戲關卡資料表
-- 用途：儲存遊戲關卡的配置資料
-- 日期：2025
-- =====================================================

USE topics_good;

-- 創建關卡資料表
CREATE TABLE IF NOT EXISTS game_im_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL UNIQUE COMMENT '關卡編號',
    level_name VARCHAR(255) NOT NULL COMMENT '關卡名稱',
    level_description TEXT NULL COMMENT '關卡描述',
    level_type ENUM('eliminate_all', 'reach_target', 'survive_time', 'collect_items', 'hybrid') 
        NOT NULL DEFAULT 'eliminate_all' COMMENT '關卡類型：消滅所有敵人、到達目標、生存時間、收集物品、混合任務',
    win_condition JSON NOT NULL COMMENT '勝利條件JSON，根據類型不同而不同',
    enemy_config JSON NOT NULL COMMENT '敵人配置JSON，包含敵人種類、數量、位置等',
    map_config JSON NULL COMMENT '地圖配置JSON，包含障礙物、目標點、起點等',
    player_start_x INT DEFAULT 400 COMMENT '玩家起始X座標',
    player_start_y INT DEFAULT 300 COMMENT '玩家起始Y座標',
    player_health INT DEFAULT 100 COMMENT '玩家初始血量',
    is_active TINYINT(1) DEFAULT 1 COMMENT '是否啟用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_level_number (level_number),
    INDEX idx_is_active (is_active),
    INDEX idx_level_type (level_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='方塊射擊遊戲關卡資料表';

-- 插入預設關卡資料
-- 第1關：消滅所有敵人（教學關）
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    1,
    '新手訓練',
    '學習基本操作，消滅所有敵人',
    'eliminate_all',
    '{"type": "eliminate_all", "enemy_count": 0}',
    '{"enemies": [{"type": "normal", "count": 5, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第2關：消滅所有敵人
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    2,
    '敵人圍攻',
    '消滅所有來襲的敵人',
    'eliminate_all',
    '{"type": "eliminate_all", "enemy_count": 0}',
    '{"enemies": [{"type": "normal", "count": 8, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第3關：到達目標點
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    3,
    '突圍任務',
    '避開敵人，到達藍色目標點',
    'reach_target',
    '{"type": "reach_target", "target_x": 700, "target_y": 100, "target_radius": 30}',
    '{"enemies": [{"type": "normal", "count": 6, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    100, 300, 100
);

-- 第4關：消滅特定數量敵人
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    4,
    '快速擊殺',
    '擊殺至少10個敵人',
    'eliminate_all',
    '{"type": "eliminate_all", "min_kills": 10}',
    '{"enemies": [{"type": "normal", "count": 15, "spawn_pattern": "wave", "wave_count": 3}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第5關：生存時間
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    5,
    '生存挑戰',
    '在敵人的圍攻下生存30秒',
    'survive_time',
    '{"type": "survive_time", "survive_seconds": 30}',
    '{"enemies": [{"type": "fast", "count": 12, "spawn_pattern": "wave", "wave_count": 4}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第6關：到達目標 + 消滅部分敵人
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    6,
    '突圍戰鬥',
    '擊殺至少5個敵人並到達目標點',
    'hybrid',
    '{"type": "hybrid", "conditions": [{"type": "reach_target", "target_x": 700, "target_y": 500, "target_radius": 30}, {"type": "min_kills", "count": 5}]}',
    '{"enemies": [{"type": "normal", "count": 10, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    100, 100, 100
);

-- 第7關：坦克敵人挑戰
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    7,
    '重型挑戰',
    '消滅所有坦克敵人',
    'eliminate_all',
    '{"type": "eliminate_all", "enemy_count": 0}',
    '{"enemies": [{"type": "tank", "count": 5, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第8關：混合敵人群
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    8,
    '敵人大軍',
    '消滅混合敵人組合',
    'eliminate_all',
    '{"type": "eliminate_all", "enemy_count": 0}',
    '{"enemies": [{"type": "normal", "count": 8, "spawn_pattern": "random"}, {"type": "fast", "count": 6, "spawn_pattern": "random"}, {"type": "tank", "count": 3, "spawn_pattern": "random"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

-- 第9關：複雜突圍
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    9,
    '極限突圍',
    '在大量敵人包圍下到達目標',
    'reach_target',
    '{"type": "reach_target", "target_x": 50, "target_y": 50, "target_radius": 25}',
    '{"enemies": [{"type": "normal", "count": 10, "spawn_pattern": "wave"}, {"type": "fast", "count": 8, "spawn_pattern": "wave"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    750, 550, 100
);

-- 第10關：最終挑戰
INSERT INTO game_im_levels (
    level_number, level_name, level_description, level_type,
    win_condition, enemy_config, map_config,
    player_start_x, player_start_y, player_health
) VALUES (
    10,
    '最終決戰',
    '擊殺15個敵人並存活到最後',
    'hybrid',
    '{"type": "hybrid", "conditions": [{"type": "min_kills", "count": 15}, {"type": "survive_time", "survive_seconds": 45}]}',
    '{"enemies": [{"type": "normal", "count": 12, "spawn_pattern": "wave"}, {"type": "fast", "count": 10, "spawn_pattern": "wave"}, {"type": "tank", "count": 5, "spawn_pattern": "wave"}]}',
    '{"width": 800, "height": 600, "obstacles": []}',
    400, 300, 100
);

