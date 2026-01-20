<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';

// 遊戲列表
$games = [
    [
        'id' => 'fight',
        'title' => '格鬥問答遊戲',
        'description' => '與奶油進行問答對戰！答對題目可以攻擊對手，答錯則會被攻擊。看看你能答對幾題！',
        'icon' => 'http://localhost/game/fight01.gif',
        'link' => 'game_fight.php',
        'color' => '#667eea',
        'difficulty' => '中等',
        'players' => '單人',
        'duration' => '5-10 分鐘'
    ],
    [
        'id' => 'code',
        'title' => '程式碼挑戰',
        'description' => '測試你的程式設計能力！透過解題來提升技能，適合想要學習程式設計的同學。',
        'icon' => 'http://localhost/game/turn.gif',
        'link' => 'game_code.php',
        'color' => '#f093fb',
        'difficulty' => '困難',
        'players' => '單人',
        'duration' => '10-20 分鐘'
    ],
    [
        'id' => 'nu',
        'title' => '護理科互動遊戲',
        'description' => '與奶油一起學習護理知識！透過問答遊戲提升護理技能，適合護理科的同學。',
        'icon' => 'http://localhost/game/NU02.gif',
        'link' => 'game_NU.php',
        'color' => '#4facfe',
        'difficulty' => '中等',
        'players' => '單人',
        'duration' => '5-15 分鐘'
    ],
    [
        'id' => 'im',
        'title' => '方塊射擊遊戲',
        'description' => '透過 Blockly 積木程式設計控制角色射擊敵人！訓練邏輯思維與程式能力，適合所有想學習程式設計的同學。',
        'icon' => 'http://localhost/game/pixilart-drawing.png',
        'link' => 'game_IM.php',
        'color' => '#764ba2',
        'difficulty' => '中等',
        'players' => '單人',
        'duration' => '10-30 分鐘'
    ],
    [
        'id' => 'undertale',
        'title' => 'Undertale 風格戰鬥',
        'description' => '經典回合制戰鬥遊戲！選擇戰鬥、行動、物品或寬恕，控制紅色靈魂躲避攻擊。展現你的選擇與策略！',
        'icon' => 'http://localhost/game/走路.gif',
        'link' => 'game_undertale.php',
        'color' => '#ff0000',
        'difficulty' => '中等',
        'players' => '單人',
        'duration' => '5-15 分鐘'
    ]
];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學招生遊戲中心</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #ffffff;
            color: #2c3e50;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 100px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        .game-main {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            margin-top: 100px;
            position: relative;
            z-index: 1;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
            z-index: 2;
        }

        .page-header h1 {
            font-size: 56px;
            color: #2c3e50;
            margin: 0 0 20px 0;
            font-weight: bold;
            position: relative;
            display: inline-block;
        }

        .page-header h1::after {
            content: '✨';
            position: absolute;
            top: -10px;
            right: -40px;
            font-size: 30px;
            animation: sparkle 2s ease-in-out infinite;
        }

        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.3) rotate(180deg); opacity: 0.7; }
        }

        .page-header p {
            font-size: 20px;
            color: #6c757d;
            margin: 0;
            font-weight: 500;
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .game-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .game-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-hover));
            transform: scaleX(0);
            transition: transform 0.4s ease;
            z-index: 1;
        }

        .game-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--card-color) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }

        .game-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border-color: var(--card-color);
        }

        .game-card:hover::before {
            transform: scaleX(1);
        }

        .game-card:hover::after {
            opacity: 0.1;
        }

        .game-icon {
            text-align: center;
            margin-bottom: 25px;
            display: block;
            position: relative;
            z-index: 2;
        }

        .game-icon img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            padding: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }

        .game-card:hover .game-icon img {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .game-title {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 15px 0;
            text-align: center;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .game-card:hover .game-title {
            transform: scale(1.05);
        }

        .game-description {
            font-size: 16px;
            color: #495057;
            line-height: 1.8;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .game-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 2;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }

        .play-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-hover));
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
            position: relative;
            z-index: 2;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .play-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .play-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .play-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .play-button:active {
            transform: translateY(0);
        }

        /* 遊戲卡片顏色變數 */
        .game-card[data-game="fight"] {
            --card-color: #667eea;
            --card-color-hover: #764ba2;
        }

        .game-card[data-game="code"] {
            --card-color: #f093fb;
            --card-color-hover: #4facfe;
        }

        .game-card[data-game="nu"] {
            --card-color: #4facfe;
            --card-color-hover: #00f2fe;
        }

        .game-card[data-game="im"] {
            --card-color: #764ba2;
            --card-color-hover: #667eea;
        }

        .game-card[data-game="undertale"] {
            --card-color: #ff0000;
            --card-color-hover: #cc0000;
        }

        /* 特色標籤 */
        .game-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 3;
            box-shadow: 0 2px 10px rgba(255, 107, 107, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* 裝飾性元素 */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
            border-radius: 50%;
        }

        .shape:nth-child(1) {
            width: 100px;
            height: 100px;
            background: #1e3a8a;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            background: #1e40af;
            top: 60%;
            right: 10%;
            animation-delay: 3s;
        }

        .shape:nth-child(3) {
            width: 80px;
            height: 80px;
            background: #1e3a8a;
            bottom: 20%;
            left: 20%;
            animation-delay: 6s;
        }

        .shape:nth-child(4) {
            width: 90px;
            height: 90px;
            background: #2563eb;
            top: 30%;
            right: 30%;
            animation-delay: 9s;
        }

        .shape:nth-child(5) {
            width: 110px;
            height: 110px;
            background: #1e40af;
            bottom: 40%;
            right: 20%;
            animation-delay: 12s;
        }

        .shape:nth-child(6) {
            width: 70px;
            height: 70px;
            background: #1e3a8a;
            top: 70%;
            left: 50%;
            animation-delay: 15s;
        }

        .shape:nth-child(7) {
            width: 95px;
            height: 95px;
            background: #2563eb;
            top: 20%;
            left: 70%;
            animation-delay: 18s;
        }

        .shape:nth-child(8) {
            width: 85px;
            height: 85px;
            background: #1e40af;
            bottom: 10%;
            left: 60%;
            animation-delay: 21s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 36px;
            }

            .games-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .game-card {
                padding: 25px;
            }

            .game-main {
                padding: 20px 15px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding-top: 120px;
            }

            .page-header h1 {
                font-size: 28px;
            }

            .page-header p {
                font-size: 16px;
            }
        }

        /* 動畫效果 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .game-card {
            animation: fadeInUp 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            opacity: 0;
        }

        .game-card:nth-child(1) {
            animation-delay: 0.2s;
        }

        .game-card:nth-child(2) {
            animation-delay: 0.4s;
        }

        .game-card:nth-child(3) {
            animation-delay: 0.6s;
        }

        .game-card:nth-child(4) {
            animation-delay: 0.8s;
        }

        .game-card:nth-child(5) {
            animation-delay: 1.0s;
        }

        .game-card.visible {
            opacity: 1;
        }

        /* 統計數據樣式增強 */
        .info-value {
            position: relative;
        }

        .info-value::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--card-color);
            transition: width 0.3s ease;
        }

        .game-card:hover .info-value::after {
            width: 100%;
        }
    </style>
</head>
<?php include("share/header.php"); ?>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="game-main">
        <div class="page-header">
            <h1>🎮 康寧大學招生遊戲中心</h1>
            <p>選擇一個遊戲開始挑戰吧！透過遊戲學習，讓招生資訊更有趣 ✨</p>
        </div>

        <div class="games-grid">
            <?php foreach ($games as $index => $game): ?>
            <div class="game-card" data-game="<?= htmlspecialchars($game['id']) ?>" onclick="window.location.href='<?= htmlspecialchars($game['link']) ?><?= $game['id'] === 'code' ? '?from_game=1' : '' ?>'">
                <?php if ($index === 0): ?>
                <div class="game-badge">🔥 熱門</div>
                <?php endif; ?>
                <span class="game-icon">
                    <img src="<?= htmlspecialchars($game['icon']) ?>" alt="<?= htmlspecialchars($game['title']) ?>">
                </span>
                <h2 class="game-title"><?= htmlspecialchars($game['title']) ?></h2>
                <p class="game-description"><?= htmlspecialchars($game['description']) ?></p>
                
                <div class="game-info">
                    <div class="info-item">
                        <div class="info-label">難度</div>
                        <div class="info-value"><?= htmlspecialchars($game['difficulty']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">模式</div>
                        <div class="info-value"><?= htmlspecialchars($game['players']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">時間</div>
                        <div class="info-value"><?= htmlspecialchars($game['duration']) ?></div>
                    </div>
                </div>

                <a href="<?= htmlspecialchars($game['link']) ?><?= $game['id'] === 'code' ? '?from_game=1' : '' ?>" class="play-button" onclick="event.stopPropagation(); return true;">
                    開始遊戲 →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include("share/footer.php"); ?>

    <script>
        // 添加可見性動畫
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.game-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('visible');
                }, index * 100);
            });
        });

        // 鍵盤導航支持
        document.addEventListener('keydown', function(e) {
            const cards = document.querySelectorAll('.game-card');
            const currentIndex = Array.from(cards).findIndex(card => card === document.activeElement);
            
            if (e.key === 'ArrowRight' && currentIndex < cards.length - 1) {
                cards[currentIndex + 1].focus();
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                cards[currentIndex - 1].focus();
            } else if (e.key === 'Enter' && document.activeElement.classList.contains('game-card')) {
                document.activeElement.click();
            }
        });

        // 為卡片添加 tabindex 以支持鍵盤導航
        document.querySelectorAll('.game-card').forEach(card => {
            card.setAttribute('tabindex', '0');
        });
    </script>
</body>
</html>
