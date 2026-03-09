<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';

// 角色圖片路徑
$characterImage = 'http://localhost/game/走路.gif';

// 獲取程式相關題目
function getProgrammingQuestions($limit = 50) {
    try {
        $conn = getDatabaseConnection();
        
        $sql = "
            SELECT
                question,
                option_a,
                option_b,
                option_c,
                option_d,
                correct_option
            FROM game_questions
            WHERE is_active = 1
            AND (
                question LIKE '%程式%' OR
                question LIKE '%代碼%' OR
                question LIKE '%編程%' OR
                question LIKE '%變數%' OR
                question LIKE '%函數%' OR
                question LIKE '%陣列%' OR
                question LIKE '%迴圈%' OR
                question LIKE '%條件%' OR
                question LIKE '%JavaScript%' OR
                question LIKE '%Python%' OR
                question LIKE '%HTML%' OR
                question LIKE '%CSS%' OR
                question LIKE '%SQL%' OR
                question LIKE '%語法%' OR
                question LIKE '%演算法%'
            )
            ORDER BY RAND()
            LIMIT ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $questions[] = [
                'q' => $row['question'],
                'a' => strtoupper($row['correct_option']),
                'o' => [
                    'A' => $row['option_a'],
                    'B' => $row['option_b'],
                    'C' => $row['option_c'],
                    'D' => $row['option_d']
                ]
            ];
        }

        $stmt->close();
        $conn->close();
        
        return $questions;
    } catch (Exception $e) {
        error_log("獲取程式題目錯誤: " . $e->getMessage());
        return [];
    }
}

$programmingQuestions = getProgrammingQuestions(50);

// 如果沒有題目，使用示例程式題目
if (empty($programmingQuestions)) {
    $programmingQuestions = [
        ['q' => 'JavaScript 中，哪個關鍵字用於宣告變數？', 'a' => 'A', 'o' => ['A' => 'var, let, const', 'B' => 'variable', 'C' => 'int', 'D' => 'string']],
        ['q' => '下列哪個是正確的陣列宣告方式？', 'a' => 'B', 'o' => ['A' => 'array = (1,2,3)', 'B' => 'array = [1,2,3]', 'C' => 'array = {1,2,3}', 'D' => 'array = <1,2,3>']],
        ['q' => 'HTML 中，哪個標籤用於建立超連結？', 'a' => 'C', 'o' => ['A' => '<link>', 'B' => '<url>', 'C' => '<a>', 'D' => '<href>']],
        ['q' => 'CSS 中，哪個屬性用於設定文字顏色？', 'a' => 'A', 'o' => ['A' => 'color', 'B' => 'text-color', 'C' => 'font-color', 'D' => 'text']],
        ['q' => 'JavaScript 中，哪個方法用於將字串轉換為數字？', 'a' => 'B', 'o' => ['A' => 'toString()', 'B' => 'parseInt() 或 Number()', 'C' => 'toNumber()', 'D' => 'convert()']],
        ['q' => '下列哪個是正確的 if 語句語法？', 'a' => 'A', 'o' => ['A' => 'if (condition) { }', 'B' => 'if condition { }', 'C' => 'if [condition] { }', 'D' => 'if {condition}']],
        ['q' => 'SQL 中，哪個關鍵字用於選擇資料？', 'a' => 'C', 'o' => ['A' => 'GET', 'B' => 'FIND', 'C' => 'SELECT', 'D' => 'FETCH']],
        ['q' => 'JavaScript 中，哪個運算子用於比較值和型別？', 'a' => 'B', 'o' => ['A' => '==', 'B' => '===', 'C' => '=', 'D' => '===']],
        ['q' => 'HTML 中，哪個標籤用於建立無序列表？', 'a' => 'A', 'o' => ['A' => '<ul>', 'B' => '<ol>', 'C' => '<list>', 'D' => '<li>']],
        ['q' => 'CSS 中，哪個屬性用於設定元素的外邊距？', 'a' => 'C', 'o' => ['A' => 'padding', 'B' => 'border', 'C' => 'margin', 'D' => 'spacing']],
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Undertale 風格戰鬥遊戲 - 康寧大學</title>
	<style>
		* {
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: "Courier New", monospace;
			background: #ffffff;
			color: #000000;
			margin: 0;
			padding: 0;
			min-height: 100vh;
			image-rendering: pixelated;
			image-rendering: -moz-crisp-edges;
			image-rendering: crisp-edges;
		}

		.battle-container {
			width: 100%;
			min-height: calc(100vh - 120px);
			position: relative;
			background: #ffffff;
			display: flex;
			flex-direction: column;
		}

		/* 敵人區域 */
		.enemy-area {
			flex: 0 0 auto;
			display: flex;
			justify-content: center;
			align-items: center;
			position: relative;
			padding: 30px 50px;
			min-height: 300px;
            margin-top: 120px;
		}

		.enemy-sprite {
			max-width: 300px;
			max-height: 300px;
			image-rendering: pixelated;
			filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
		}

		.enemy-name {
			position: absolute;
			top: -10px;
			left: 50%;
			transform: translateX(-50%);
			font-size: 24px;
			color: #ff8800;
			font-weight: bold;
			text-shadow: 1px 1px 0 rgba(0,0,0,0.2);
		}

		/* 靈魂框區域 */
		.soul-box-area {
			flex: 0 0 auto;
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 20px 30px;
			position: relative;
			min-height: 320px;
		}

		.soul-box {
			width: 300px;
			height: 280px;
			border: 3px solid #000000;
			background: #ffffff;
			position: relative;
			overflow: hidden;
		}

		.soul {
			position: absolute;
			width: 16px;
			height: 16px;
			background: #ff0000;
			border-radius: 50% 50% 0 50%;
			transform: rotate(45deg);
			left: 82px;
			top: 82px;
			transition: none;
			box-shadow: 0 0 8px rgba(255,0,0,0.8);
			z-index: 10;
			image-rendering: pixelated;
		}

		/* 子彈攻擊 */
		.bullet {
			position: absolute;
			background: #ff0000;
			border-radius: 50%;
			z-index: 5;
			box-shadow: 0 0 8px rgba(255,0,0,0.8), 0 0 12px rgba(255,0,0,0.5);
			image-rendering: pixelated;
		}

		/* 程式碼文字攻擊 */
		.code-attack {
			position: absolute;
			color: #000000;
			font-family: "Courier New", monospace;
			font-weight: bold;
			font-size: 24px;
			z-index: 5;
			white-space: nowrap;
			pointer-events: none;
			image-rendering: pixelated;
		}

		/* 爆炸效果 */
		.explosion {
			position: absolute;
			width: 4px;
			height: 4px;
			background: #000000;
			border-radius: 50%;
			z-index: 6;
			pointer-events: none;
		}

		/* 光柱掃射 */
		.beam {
			position: absolute;
			background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 50%, rgba(255,255,255,0.9) 100%);
			width: 40px;
			height: 400px;
			transform-origin: center;
			z-index: 4;
			pointer-events: none;
			box-shadow: 0 0 20px rgba(255,255,255,0.8), 0 0 40px rgba(255,255,255,0.5);
		}

		/* UI 欄 */
		.ui-bar {
			flex: 0 0 auto;
			min-height: 120px;
			background: #ffffff;
			border-top: 3px solid #000000;
            border-bottom: 3px solid #000000;
			display: flex;
			flex-direction: column;
			padding: 10px 20px;
			font-family: "Courier New", monospace;
			color: #000000;
            margin-bottom: 20px;
		}

		.stats-row {
			display: flex;
			align-items: center;
			gap: 30px;
			margin-bottom: 8px;
			font-size: 18px;
			font-weight: bold;
		}

		.stat-item {
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.hp-bar-container {
			width: 200px;
			height: 20px;
			background: #000000;
			border: 2px solid #000000;
			position: relative;
			margin-left: 10px;
		}

		.hp-bar-fill {
			height: 100%;
			background: #ff0000;
			transition: width 0.3s;
			width: 100%;
		}

		.buttons-row {
			display: flex;
			gap: 15px;
			margin-top: 10px;
		}

		.action-button {
			flex: 1;
			padding: 12px;
			background: #ffffff;
			border: 3px solid #ff8800;
			color: #000000;
			font-family: "Courier New", monospace;
			font-size: 16px;
			font-weight: bold;
			cursor: pointer;
			text-align: center;
			position: relative;
			transition: all 0.2s;
		}

		.action-button:hover {
			background: #ff8800;
			color: #ffffff;
		}

		.action-button.selected {
			background: #ffff00;
			border-color: #ffff00;
		}

		#btnMercy::before {
			content: '❌';
			margin-right: 8px;
		}

		/* 對話框 */
		.dialog-box {
			position: absolute;
			top:72%;
			left: 60%;
			transform: translateY(-50%);
			width: 450px;
			background: #fff;
			border: 3px solid #667eea;
			border-radius: 15px;
			padding: 20px;
			color: #333;
			font-size: 18px;
			line-height: 1.8;
			display: none;
			z-index: 20;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
			cursor: pointer;
		}

		.dialog-box::after {
            content: '';
            position: absolute;
            left: -15px;
            width: 0;
            height: 0;
            border-top: 15px solid transparent;
            border-bottom: 15px solid transparent;
            border-left: 15px solid transparent;
		}

		.dialog-box.cream-dialog {
			border-color: #ffaa00;
			background: #fff8e1;
            top:230px;
            margin-left: 20px;
		}

		.dialog-box.cream-dialog::after {
			border-top-color: #ffaa00;
		}

		.dialog-box.cream-dialog strong {
			color: #ff8800;
		}

		.dialog-box.player-dialog {
			border-color: #0066cc;
			background: #e3f2fd;
		}

		.dialog-box.player-dialog::after {
			border-top-color: #0066cc;
		}

		.dialog-box.player-dialog strong {
			color: #0066cc;
		}

		.dialog-box.show {
			display: block;
			animation: dialogFadeIn 0.3s;
		}

		@keyframes dialogFadeIn {
			from {
				opacity: 0;
				transform: translateY(-50%) translateX(20px);
			}
			to {
				transform: translateY(-50%) translateX(0);
			}
		}

		.dialog-text {
			min-height: 60px;
			font-size: 16px;
			line-height: 1.8;
			text-align: left;
		}

		.dialog-text.typing {
			overflow: hidden;
		}

		.dialog-next {
			text-align: right;
			margin-top: 10px;
			font-size: 14px;
			color: #666666;
		}

		/* ACT 選單 */
		.act-menu {
			position: absolute;
			bottom: 140px;
			left: 50%;
			transform: translateX(-50%);
			width: 400px;
			background: #ffffff;
			border: 3px solid #000000;
			padding: 15px;
			color: #000000;
			display: none;
			z-index: 20;
			box-shadow: 0 4px 8px rgba(0,0,0,0.2);
		}

		.act-menu.show {
			display: block;
		}

			/* Help 按鈕（左上角） */
		.help-button {
			position: fixed;
			top: 130px;
			left: 12px;
			width: 36px;
			height: 36px;
			border-radius: 50%;
			border: 3px solid #000;
			font-weight: bold;
			font-size: 18px;          /* ★ 控制 ? 大小 */
			cursor: pointer;
			z-index: 200;
			box-shadow: 0 2px 6px rgba(0,0,0,0.15);
			display: flex;            /* ★ 關鍵 */
			align-items: center;      /* 垂直置中 */
			justify-content: center;  /* 水平置中 */
		}


			.help-button:focus { 
				outline: none; 
			}

			/* Help modal */
/* Help modal */
			.help-modal {
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				width: 420px;
				max-width: calc(100% - 32px);
				background: #ffffff;
				border: 3px solid #000;
				border-radius: 12px;          /* ★ 補圓角，解決裁切感 */
				padding: 20px 22px;           /* ★ 增加內距，避免貼邊 */
				z-index: 120;
				display: none;
				box-shadow: 0 10px 28px rgba(0,0,0,0.28);
				box-sizing: border-box;
				max-height: 75vh;
				overflow-y: auto;
			}

			/* 顯示 */
			.help-modal.show {
				display: block;
			}

			.help-modal.show {
				display: block;
			}

			.help-modal h3 {
				margin: 0 0 12px 0;
				font-size: 18px;
				font-weight: bold;
				border-bottom: 2px solid #000;
				padding-bottom: 6px;
			}

			.help-modal p { 
				margin: 6px 0; font-size: 14px; 
			}


			.help-modal .close-btn {
				position: absolute;   /* ★ 關鍵 */
				top: 8px;
				right: 8px;
				width: 32px;
				height: 32px;
				padding: 0;
				border: 2px solid #000;
				border-radius: 6px;
				font-size: 16px;
				font-weight: bold;
				cursor: pointer;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			/* 玩家回合時 Fight 按鈕樣式 */
			.action-button.player-turn {
				background: #ff8800;
				color: #ffffff;
				border-color: #ff8800;
				box-shadow: 0 6px 18px rgba(255,136,0,0.35);
				transform: translateY(-2px);
				animation: fightBlink 1s infinite;
			}

			@keyframes fightBlink {
				0% {
					box-shadow: 0 6px 18px rgba(255,136,0,0.25);
					transform: translateY(0);
				}
				50% {
					box-shadow: 0 10px 30px rgba(255,170,0,0.6);
					transform: translateY(-3px);
				}
				100% {
					box-shadow: 0 6px 18px rgba(255,136,0,0.25);
					transform: translateY(0);
				}
			}

		.act-option {
			padding: 10px;
			cursor: pointer;
			border: 2px solid transparent;
			margin-bottom: 5px;
			font-size: 16px;
		}

		.act-option:hover {
			background: #ffff00;
			border-color: #000000;
		}

		.act-option.selected {
			background: #ffff00;
			border-color: #000000;
		}

		/* 攻擊動畫 */
		.attack-animation {
			position: absolute;
			width: 100%;
			height: 100%;
			pointer-events: none;
			z-index: 15;
		}

		.damage-number {
			position: absolute;
			color: #ffff00;
			font-size: 32px;
			font-weight: bold;
			text-shadow: 2px 2px 0 #000;
			animation: damageFloat 1s ease-out forwards;
			pointer-events: none;
			z-index: 16;
		}

		@keyframes damageFloat {
			0% {
				opacity: 1;
				transform: translateY(0);
			}
			100% {
				opacity: 0;
				transform: translateY(-50px);
			}
		}

		/* 戰鬥結果 */
		.result-screen {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(200, 200, 200, 0.95);
			display: none;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			z-index: 100;
		}

		.result-screen.show {
			display: flex;
		}

		.result-character {
			width: 150px;
			height: 150px;
			margin-bottom: 20px;
			position: relative;
		}

		.result-character img {
			width: 100%;
			height: 100%;
			object-fit: contain;
		}

		.result-text {
			font-size: 24px;
			margin-bottom: 30px;
			text-align: center;
			color: #000000;
			line-height: 1.8;
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
		}

		.result-buttons {
			gap: 20px;
			margin-top: 20px;
		}

		.result-button {
			padding: 12px 24px;
			background: #ffffff;
			border: 2px solid #000000;
			border-radius: 8px;
			color: #000000;
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
			font-size: 16px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.3s;
		}

		.result-button:hover {
			background: #f0f0f0;
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0,0,0,0.2);
		}

		/* 題目系統 */
		.question-screen {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.85);
			display: none;
			justify-content: center;
			align-items: center;
			z-index: 200;
		}

		.question-screen.show {
			display: flex;
		}

		.question-box {
			background: #ffffff;
			border: 3px solid #000000;
			border-radius: 15px;
			padding: 30px;
			max-width: 600px;
			width: 90%;
			box-shadow: 0 8px 16px rgba(0,0,0,0.3);
		}

		.question-title {
			font-size: 20px;
			font-weight: bold;
			margin-bottom: 20px;
			color: #000000;
			text-align: center;
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
		}

		.question-text {
			font-size: 18px;
			margin-bottom: 25px;
			color: #333333;
			line-height: 1.6;
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
		}

		.question-options {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.question-option {
			padding: 15px 20px;
			background: #f8f9fa;
			border: 2px solid #000000;
			border-radius: 8px;
			cursor: pointer;
			font-size: 16px;
			color: #000000;
			transition: all 0.3s;
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
		}

		.question-option:hover {
			background: #ffff00;
			transform: translateX(5px);
		}

		.question-option.correct {
			background: #82e730ff;
			color: #ffffff;
			border-color: #82e730ff;
		}

		.question-option.wrong {
			background: #f44336;
			color: #ffffff;
			border-color: #c62828;
		}

		.tutorial-x {
	position: absolute;
	font-size: 24px;
	color: #000000ff;
	animation: floatX 1.5s infinite ease-in-out;
	pointer-events: none;
}

@keyframes floatX {
	0% { transform: translateY(0); }
	50% { transform: translateY(-8px); }
	100% { transform: translateY(0); }
}


		.question-option.disabled {
			cursor: not-allowed;
			opacity: 0.7;
		}

		/* 敵人受傷動畫 */
		@keyframes enemyHit {
			0%, 100% { transform: translateX(0); }
			25% { transform: translateX(-10px); }
			75% { transform: translateX(10px); }
		}

		.enemy-sprite.hit {
			animation: enemyHit 0.3s;
		}

		/* 靈魂受傷效果 */
		.soul.damaged {
			animation: soulFlicker 0.3s;
		}

		@keyframes soulFlicker {
			0%, 100% { opacity: 1; }
			50% { opacity: 0.3; }
		}
	</style>
</head>
<?php include("share/header.php"); ?>
<body>
	<div class="battle-container">
		<!-- 敵人區域 -->
		<div class="enemy-area">
			<div class="enemy-name" id="enemyName">敵人</div>
			<img src="<?= htmlspecialchars($characterImage) ?>" alt="敵人" class="enemy-sprite" id="enemySprite">
		</div>

		<!-- 靈魂框區域 -->
		<div class="soul-box-area">
			<div class="soul-box" id="soulBox">
				<div class="soul" id="soul"></div>
			</div>
		</div>

		<!-- 對話框 -->
		<div class="dialog-box" id="dialogBox">
			<div class="dialog-text" id="dialogText"></div>
			<div class="dialog-next">按任意鍵繼續...</div>
		</div>

		<!-- ACT 選單 -->
		<div class="act-menu" id="actMenu">
			<div class="act-option" data-act="check">檢查</div>
			<div class="act-option" data-act="talk">對話</div>
			<div class="act-option" data-act="flirt">調情</div>
		</div>

		<!-- UI 欄 -->
		<div class="ui-bar">
			<div class="stats-row">
				<div class="stat-item">
					<span>CHARA</span>
				</div>
				<div class="stat-item">
					<span>LV</span>
					<span id="playerLV">1</span>
				</div>
				<div class="stat-item">
					<span>HP</span>
					<div class="hp-bar-container">
						<div class="hp-bar-fill" id="playerHPBar"></div>
					</div>
					<span id="playerHP">20</span>
					<span>/</span>
					<span id="playerMaxHP">20</span>
				</div>
			</div>

			<!-- Help 按鈕 -->
			<button id="btnHelp" class="help-button" title="操作說明">?</button>

			<!-- Help Modal -->
			<div class="help-modal" id="helpModal" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
				<h3 id="helpTitle">操作說明</h3>
				<p>移動：使用 <strong>W A S D</strong> 或 <strong>方向鍵</strong> 移動靈魂。</p>
				<p>攻擊：按下 <strong>FIGHT</strong>（畫面下方）會出題，答對即可攻擊敵人。</p>
				<p>寬恕：按下 <strong>MERCY</strong> 嘗試寬恕敵人（需先 ACT）。</p>
				<p>當畫面上方的 <strong>FIGHT</strong> 按鈕為橘色時，代表現在是你的回合。</p>
				<button class="close-btn" id="helpClose">X</button>
			</div>
			<div class="buttons-row">
				<button class="action-button" id="btnFight">FIGHT</button>
				<button class="action-button" id="btnMercy">MERCY</button>
			</div>
		</div>

		<!-- 戰鬥結果畫面 -->
		<div class="result-screen" id="resultScreen">
			<div class="result-character">
				<img src="http://localhost/game/%e8%b5%b0%e8%b7%af.gif" alt="角色" onerror="this.style.display='none'">
			</div>
			<div class="result-text" id="resultText"></div>
			<div class="result-buttons">
				<button class="result-button" onclick="restartBattle()">再來一次</button>
				<button class="result-button" onclick="window.location.href='game.php'">返回</button>
			</div>
		</div>

		<!-- 題目畫面 -->
		<div class="question-screen" id="questionScreen">
			<div class="question-box">
				<div class="question-title">程式題目</div>
				<div class="question-text" id="questionText"></div>
				<div class="question-options" id="questionOptions"></div>
			</div>
		</div>
	</div>

	<script>
		// ==================== 遊戲狀態 ====================
let gameState = 'menu'; 
// menu, tutorial, playerTurn, enemyTurn, actMenu, dialog, ended

let playerHP = 20;
let playerMaxHP = 20;
let playerLV = 1;

let enemyHP = 20;
let enemyMaxHP = 10;
let enemyName = '奶油';

let bullets = [];
let bulletAttackActive = false;

// 全域儲存由 createBeamAttack 建立的 setInterval id，方便在結束攻擊時清理
let beamFireIntervals = [];
// 儲存狂暴等複合攻擊所建立的 timeout ids，方便結束時清理
let activeAttackTimeouts = [];

function scheduleAttack(fn, ms) {
	const id = setTimeout(fn, ms);
	activeAttackTimeouts.push(id);
	return id;
}

let soulPosition = { x: 142, y: 132 };

let turnCount = 0;
let furiousMode = false;
let furiousRounds = 0;

// 🔹 新手教學
let tutorialFinished = false;
let tutorialTrapTriggered = false;
let tutorialTrapActive = false;
let tutorialTimer = null;

// 🔹 控制與對話
let inputLocked = false;
let isDialogActive = false;

// 🔹 無敵幀
let invulnerable = false;
let invulnerableTime = 0;


		// 敵人類型
		const enemies = [
			{
				name: '奶油',
				maxHP: 10,
				attacks: [
					'codeAttackX',        // 回合 1：X 攻擊
					'codeAttackY',        // 回合 2：Y 攻擊
					'codeAttackBrackets', // 回合 3：{} 括號攻擊
					'codeAttackSymbols',  // 回合 4：符號攻擊
					'codeAttackHello',    // 回合 5：HELLO WORLD（爆炸）
					'codeAttackHelloNarrow', // 回合 6：HELLO WORLD 變框攻擊
					'beamAttack',         // 回合 7：光柱掃射
					'mixedCodeAttack1',   // 回合 8：混合程式碼攻擊 1
					'codeAttackFull',     // 回合 9：完整程式碼攻擊（爆炸）
					'codeAttackUltimate', // 回合 10+：終極程式碼攻擊（多重爆炸）
				],
				dialogs: {
					greeting: [
						'* 你好！我是奶油！👋',
						'* 讓我們一起學習程式設計吧！',
						'* 準備好迎接挑戰了嗎？'
					],
					attacking: [
						'* 看我的程式碼攻擊！',
						'* 這是 X 和 Y！',
						'* HELLO WORLD！',
						'* 你能躲過這些嗎？'
					],
					turn: [
						'* 輪到我了！',
						'* 接招吧！',
						'* 這次會更難喔！',
						'* 準備好了嗎？'
					],
					afterAttack: [
						'* 攻擊結束！輪到你了！',
						'* 怎麼樣？還撐得住嗎？',
						'* 你的回合到了！',
						'* 接下來換你攻擊了！',
						'* 這次攻擊怎麼樣？',
						'* 輪到你了！'
					]
				},
				actResponses: {
					check: '* 奶油 HP 30/30\n* 一個友善的角色',
					talk: '* 你試圖和奶油對話\n* 奶油很開心',
					flirt: '* 你對奶油調情\n* 奶油臉紅了'
				}
			}
		];

		let currentEnemy = enemies[0];

		// ==================== 初始化 ====================
		function initBattle() {
			enemyName = currentEnemy.name;
			enemyHP = currentEnemy.maxHP;
			enemyMaxHP = currentEnemy.maxHP;
			playerHP = playerMaxHP;
			turnCount = 0;
			canMercy = false;
			invulnerable = false;
			invulnerableTime = 0;
			
			// 重置靈魂位置（中心位置，考慮靈魂大小16px，靈魂框是300x280）
			soulPosition = { x: 142, y: 132 }; // (300 - 16) / 2 = 142, (280 - 16) / 2 = 132
			const soul = document.getElementById('soul');
			soul.style.left = soulPosition.x + 'px';
			soul.style.top = soulPosition.y + 'px';
			soul.style.opacity = '1';
			
			// 清除所有子彈
			bullets = [];
			bulletAttackActive = false;
			const soulBox = document.getElementById('soulBox');
			const existingBullets = soulBox.querySelectorAll('.bullet');
			existingBullets.forEach(bullet => bullet.remove());
			
			document.getElementById('enemyName').textContent = enemyName;
			updateUI();
			
			// 顯示奶油的問候對話（支援加速）
			const greetingDialogs = currentEnemy.dialogs.greeting;
			let dialogIndex = 0;
			
			const showNextGreeting = () => {
			if (dialogIndex >= greetingDialogs.length) {
				hideDialog();

				if (!tutorialFinished) {
					startTutorial();   // ← 教學入口
				} else {
					enemyTurn();
				}
				return;
			};
				
				showDialogWithCharacter(greetingDialogs[dialogIndex], 'cream', () => {
					dialogIndex++;
					if (dialogIndex < greetingDialogs.length) {
						// 自動顯示下一條，但用戶可以點擊加速
						setTimeout(showNextGreeting, 500);
					} else {
						hideDialog();
						// 若尚未完成教學，進入教學；否則敵人先攻擊
						if (!tutorialFinished) {
							startTutorial();
							return; // 等待 timeout 或其他安全機制再啟動回合
						} else {
							enemyTurn();
						}
					}
				});
			};
			
			showNextGreeting();
		}
		function startTutorial() {
	gameState = 'tutorial';

	const dialogs = [
		'* 等一下。',
		'* 在戰鬥前，我得先教你一件事。',
		'* 用 W A S D 移動你的靈魂。',
		'* 在框裡走走看吧。'
	];

	let i = 0;

	const next = () => {
		if (i >= dialogs.length) {
			hideDialog();
			startTutorialTrap();
			return;
		}

		// capture current index to avoid closure issues
		const idx = i;
		showDialogWithCharacter(dialogs[idx], 'cream', () => {
			// 當文字提到「在框裡」時，立即顯示 X（trap）並停留在該對話
			if (dialogs[idx].indexOf('在框裡') !== -1 || dialogs[idx].indexOf('走走') !== -1) {
				startTutorialTrap();
				// do not advance to next dialog here; trap flow continues
			} else {
				i = idx + 1;
				setTimeout(next, 400);
			}
		});
	};

	next();
}

function startTutorialTrap() {
	if (tutorialTrapActive) return;

	tutorialTrapActive = true;
	tutorialTrapTriggered = false;

	const soulBox = document.getElementById('soulBox');
	const trap = document.createElement('div');
	trap.id = 'tutorialX';
	trap.className = 'tutorial-x';
	trap.textContent = 'X';

	trap.style.left = '140px';
	trap.style.top = '100px';

	soulBox.appendChild(trap);

	// 觸發誘導對話（奶油引導玩家吃下 X）
	showDialogWithCharacter('* 看到這個 X 了嗎？吃掉它就能升級喔。', 'cream');

	// 5 秒後沒碰 → 成功（玩家沒上當）
	tutorialTimer = setTimeout(() => {
		if (!tutorialTrapTriggered) {
			trap.remove();
			tutorialSuccess();
		}
	}, 5000);

	// 碰撞檢查（靈魂與 trap 相撞）
	const check = setInterval(() => {
		if (checkSoulCollision(trap)) {
			tutorialTrapTriggered = true;
			clearTimeout(tutorialTimer);
			clearInterval(check);
			trap.remove();
			tutorialFail();
		}
	}, 50);
}

// 檢查靈魂與元素（如 tutorial X）是否碰撞（在 soulBox 同一坐標系）
function checkSoulCollision(el) {
	try {
		if (!el) return false;
		const soulW = 16, soulH = 16;
		// el 使用相對定位於 soulBox，使用 offsetLeft/Top
		const elLeft = el.offsetLeft || 0;
		const elTop = el.offsetTop || 0;
		const elW = el.offsetWidth || 20;
		const elH = el.offsetHeight || 20;

		const soulLeft = soulPosition.x;
		const soulTop = soulPosition.y;

		// 矩形相交檢查
		if (soulLeft < elLeft + elW && soulLeft + soulW > elLeft &&
			soulTop < elTop + elH && soulTop + soulH > elTop) {
			return true;
		}
		return false;
	} catch (e) {
		return false;
	}
}

function tutorialFail() {
	tutorialFinished = true;
	tutorialTrapActive = false;

	playerHP = Math.max(1, playerHP - 3);
	updateUI();

	// 嘲諷玩家並開始第一回合
	showDialogWithCharacter(
		'* 哈。學個程式之前，先學會不要亂吃東西好嗎？',
		'cream',
		() => {
			showDialogWithCharacter(
				'* 這就是你的判斷力嗎？',
				'cream',
				() => {
					showDialogWithCharacter(
						'* 誰跟你說 X 是好東西的？',
						'cream',
						() => {
							hideDialog();
							enemyTurn();
					}
				);
			}
		);
	}
)
}

function tutorialSuccess() {
	tutorialFinished = true;
	tutorialTrapActive = false;

	// 玩家沒碰到 X：表達可惜，然後開始第一回合
	showDialogWithCharacter('* 真可惜，沒有騙到你。', 'cream', () => {
		hideDialog();
		enemyTurn();
	});
}


		// ==================== UI 更新 ====================
		function updateUI() {
			document.getElementById('playerLV').textContent = playerLV;
			document.getElementById('playerHP').textContent = playerHP;
			document.getElementById('playerMaxHP').textContent = playerMaxHP;
			
			const hpPercent = (playerHP / playerMaxHP) * 100;
			document.getElementById('playerHPBar').style.width = hpPercent + '%';
			
			if (hpPercent < 30) {
				document.getElementById('playerHPBar').style.background = '#ff0000';
			} else if (hpPercent < 60) {
				document.getElementById('playerHPBar').style.background = '#ff8800';
			} else {
				document.getElementById('playerHPBar').style.background = '#00ff00';
			}
		}

		// ==================== 對話系統 ====================
		let currentDialogTyping = null;
		let isDialogTyping = false;
		let currentDialogCallback = null;
		let fullDialogText = '';

		// 暫停攻擊（清理 interval、移除子彈），在顯示對話時呼叫
		function pauseAttacks() {
			// 清除 beam intervals
			if (beamFireIntervals && beamFireIntervals.length > 0) {
				beamFireIntervals.forEach(id => clearInterval(id));
				beamFireIntervals = [];
			}

			// 移除所有子彈 DOM 與清空陣列
			const soulBox = document.getElementById('soulBox');
			if (soulBox) {
				const existing = soulBox.querySelectorAll('.bullet, .code-attack, .explosion');
				existing.forEach(e => e.remove());
			}
			bullets = [];
			bulletAttackActive = false;

			// 清除所有複合攻擊的 timeouts
			if (activeAttackTimeouts && activeAttackTimeouts.length > 0) {
				activeAttackTimeouts.forEach(tid => clearTimeout(tid));
				activeAttackTimeouts = [];
			}
		}

		function showDialog(text, callback) {
            // 顯示對話前暫停任何正在進行的攻擊
            pauseAttacks();
			const dialogBox = document.getElementById('dialogBox');
			const dialogText = document.getElementById('dialogText');
			dialogText.innerHTML = '';
			dialogBox.classList.remove('player-dialog', 'cream-dialog');
			dialogBox.classList.add('show');
			
			// 清除之前的打字動畫
			if (currentDialogTyping) {
				clearInterval(currentDialogTyping);
			}
			
			isDialogTyping = true;
			fullDialogText = text;
			let index = 0;
			
			currentDialogTyping = setInterval(() => {
				if (index < text.length) {
					dialogText.textContent = text.substring(0, index + 1);
					index++;
				} else {
					clearInterval(currentDialogTyping);
					currentDialogTyping = null;
					isDialogTyping = false;
					currentDialogCallback = callback;
				}
			}, 30);
			
			currentDialogCallback = callback;
		}

		function showDialogWithCharacter(text, character, callback) {
            // 顯示有角色的對話前暫停任何正在進行的攻擊
            pauseAttacks();
			const dialogBox = document.getElementById('dialogBox');
			const dialogText = document.getElementById('dialogText');
			
			// 移除之前的角色類別
			dialogBox.classList.remove('player-dialog', 'cream-dialog');
			
			let displayText = text;
			if (character === 'cream') {
				dialogBox.classList.add('cream-dialog');
				displayText = '<strong>奶油：</strong>' + text;
			} else if (character === 'player') {
				dialogBox.classList.add('player-dialog');
				displayText = '<strong>你：</strong>' + text;
			}
			
			dialogText.innerHTML = '';
			dialogBox.classList.add('show');
			
			// 清除之前的打字動畫
			if (currentDialogTyping) {
				clearInterval(currentDialogTyping);
			}
			
			isDialogTyping = true;
			fullDialogText = displayText;
			let index = 0;
			
			currentDialogTyping = setInterval(() => {
				if (index < displayText.length) {
					// 如果是HTML標籤，需要特殊處理
					if (displayText.substring(index).startsWith('<strong>')) {
						const tagEnd = displayText.indexOf('</strong>', index);
						if (tagEnd !== -1) {
							dialogText.innerHTML = displayText.substring(0, tagEnd + 9);
							index = tagEnd + 9;
						} else {
							dialogText.innerHTML += displayText[index];
							index++;
						}
					} else if (displayText.substring(index).startsWith('</strong>')) {
						dialogText.innerHTML = displayText.substring(0, index + 9);
						index += 9;
					} else {
						const textBefore = displayText.substring(0, index + 1);
						// 保留HTML標籤
						dialogText.innerHTML = textBefore;
						index++;
					}
				} else {
					clearInterval(currentDialogTyping);
					currentDialogTyping = null;
					isDialogTyping = false;
					currentDialogCallback = callback;
				}
			}, 30);
			
			currentDialogCallback = callback;
		}

		function hideDialog() {
			if (currentDialogTyping) {
				clearInterval(currentDialogTyping);
				currentDialogTyping = null;
			}
			isDialogTyping = false;
			currentDialogCallback = null;
			document.getElementById('dialogBox').classList.remove('show');
		}

		function skipDialog() {
			const dialogBox = document.getElementById('dialogBox');
			const dialogText = document.getElementById('dialogText');
			
			if (isDialogTyping && currentDialogTyping) {
				// 如果正在打字，直接完成
				clearInterval(currentDialogTyping);
				currentDialogTyping = null;
				isDialogTyping = false;
				dialogText.innerHTML = fullDialogText;
				
				// 如果有回調，設置它（但不立即執行，等用戶再次點擊）
				if (currentDialogCallback) {
					// 回調將在下次點擊時執行
				}
			} else if (currentDialogCallback) {
				// 如果打字完成，執行回調並隱藏
				const callback = currentDialogCallback;
				currentDialogCallback = null;
				hideDialog();
				if (callback) callback();
			} else {
				// 沒有回調，直接隱藏
				hideDialog();
			}
		}

		// 對話框點擊事件
		document.getElementById('dialogBox').addEventListener('click', (e) => {
			e.stopPropagation();
			skipDialog();
		});

		// ==================== 題目系統 ====================
		const programmingQuestions = <?php echo json_encode($programmingQuestions, JSON_UNESCAPED_UNICODE); ?>;
		let currentQuestionIndex = 0;
		let isAnswering = false;

		function showQuestion() {
			if (programmingQuestions.length === 0) {
				// 如果沒有題目，直接攻擊
				attackEnemy();
				return;
			}

			// 隨機選擇一題
			currentQuestionIndex = Math.floor(Math.random() * programmingQuestions.length);
			const question = programmingQuestions[currentQuestionIndex];
			
			gameState = 'question';
			isAnswering = false;
			
			const questionScreen = document.getElementById('questionScreen');
			const questionText = document.getElementById('questionText');
			const questionOptions = document.getElementById('questionOptions');
			
			questionText.textContent = question.q;
			questionOptions.innerHTML = '';
			
			// 創建選項按鈕
			Object.entries(question.o).forEach(([key, text]) => {
				const option = document.createElement('div');
				option.className = 'question-option';
				option.textContent = key + '. ' + text;
				option.onclick = () => answerQuestion(key);
				questionOptions.appendChild(option);
			});
			
			questionScreen.classList.add('show');
		}

		function answerQuestion(selectedKey) {
			if (isAnswering) return;
			isAnswering = true;
			
			const question = programmingQuestions[currentQuestionIndex];
			const correct = selectedKey === question.a;
			const options = document.querySelectorAll('.question-option');
			
			// 禁用所有選項
			options.forEach(option => {
				option.classList.add('disabled');
				option.onclick = null;
			});
			
			// 標記正確和錯誤答案
			options.forEach(option => {
				const key = option.textContent.trim().charAt(0);
				if (key === question.a) {
					option.classList.add('correct');
				} else if (key === selectedKey && !correct) {
					option.classList.add('wrong');
				}
			});
			
			// 延遲後執行攻擊或 miss
			setTimeout(() => {
				document.getElementById('questionScreen').classList.remove('show');
				if (correct) {
					// 答對：可以攻擊
					attackEnemy(true);
				} else {
					// 答錯：攻擊 miss
					attackEnemy(false);
				}
			}, 2000);
		}

		// ==================== 按鈕事件 ====================
		document.getElementById('btnFight').addEventListener('click', () => {
			if (gameState === 'playerTurn') {
				// 狂暴模式下，玩家不能攻擊
				if (furiousMode) {
					showDialogWithCharacter('* 奶油正在狂暴攻擊中，你無法行動！', 'player');
					return;
				}
				showQuestion();
			}
		});

		document.getElementById('btnMercy').addEventListener('click', () => {
			if (gameState === 'playerTurn') {
				tryMercy();
			}
		});

		// ==================== 戰鬥系統 ====================
		function attackEnemy(questionCorrect = null) {
			gameState = 'attacking';
			
			// 如果沒有傳入 questionCorrect，使用舊的隨機 miss 機制（向後兼容）
			let isMiss;
			if (questionCorrect !== null) {
				// 根據答題結果決定是否 miss
				isMiss = !questionCorrect;
			} else {
				// 計算miss機率：回合越高，miss機率越低，但基礎有10% miss
				const missChance = Math.max(5, 15 - turnCount * 1.5); // 從15%降到5%
				isMiss = Math.random() * 100 < missChance;
			}
			
			if (isMiss) {
				// 攻擊miss
				showDialogWithCharacter('* 攻擊落空了！', 'player', () => {
					hideDialog();
					enemyTurn();
				});
				
				const enemySprite = document.getElementById('enemySprite');
				enemySprite.classList.add('hit');
				setTimeout(() => {
					enemySprite.classList.remove('hit');
				}, 300);
			} else {
				// 攻擊成功，固定傷害1
				const damage = 1;
				
				// 顯示傷害數字
				showDamage(damage, 400, 200);
    
				// 敵人受傷動畫
				const enemySprite = document.getElementById('enemySprite');
				enemySprite.classList.add('hit');
				setTimeout(() => {
					enemySprite.classList.remove('hit');
				}, 300);
				
				enemyHP = Math.max(0, enemyHP - damage);
				
				setTimeout(() => {
					if (enemyHP <= 0) {
						victory();
					} else {
						// 檢查是否觸發狂暴模式（HP=1）
						if (enemyHP === 1 && !furiousMode) {
							furiousMode = true;
							furiousRounds = 0;
							showDialogWithCharacter('* ' + enemyName + ' HP 1/' + enemyMaxHP + '\n* 奶油進入了狂暴狀態！', 'cream', () => {
								hideDialog();
								// 直接進入敵人回合，玩家不能攻擊
								enemyTurn();
							});
						} else {
							showDialogWithCharacter('* ' + enemyName + ' HP ' + enemyHP + '/' + enemyMaxHP, 'cream', () => {
								hideDialog();
								enemyTurn();
							});
						}
					}
				}, 800);
			}
		}

		function showDamage(amount, x, y) {
			const damage = document.createElement('div');
			damage.className = 'damage-number';
			damage.textContent = '-' + amount;
			damage.style.left = x + 'px';
			damage.style.top = y + 'px';
			document.body.appendChild(damage);
			
			setTimeout(() => {
				damage.remove();
			}, 1000);
		}

		// ==================== ACT 系統 ====================
		function showActMenu() {
			gameState = 'actMenu';
			const actMenu = document.getElementById('actMenu');
			actMenu.classList.add('show');
			currentActOption = 0;
			updateActSelection();
		}

		function hideActMenu() {
			document.getElementById('actMenu').classList.remove('show');
		}

		function updateActSelection() {
			const options = document.querySelectorAll('.act-option');
			options.forEach((opt, index) => {
				if (index === currentActOption) {
					opt.classList.add('selected');
				} else {
					opt.classList.remove('selected');
				}
			});
		}

		// ACT 選項點擊
		document.querySelectorAll('.act-option').forEach((option, index) => {
			option.addEventListener('click', () => {
				currentActOption = index;
				selectActOption();
			});
		});

		// 鍵盤導航 ACT 選單和對話加速
		document.addEventListener('keydown', (e) => {
			// 對話框加速（優先級最高）
			if ((e.key === 'Enter' || e.key === ' ') && document.getElementById('dialogBox').classList.contains('show')) {
				e.preventDefault();
				skipDialog();
				return;
			}
			
			if (gameState === 'actMenu') {
				if (e.key === 'ArrowUp' && currentActOption > 0) {
					currentActOption--;
					updateActSelection();
				} else if (e.key === 'ArrowDown' && currentActOption < 2) {
					currentActOption++;
					updateActSelection();
				} else if (e.key === 'Enter' || e.key === ' ') {
					selectActOption();
				}
			}
		});

		function selectActOption() {
			const options = ['check', 'talk', 'flirt'];
			const selected = options[currentActOption];
			const response = currentEnemy.actResponses[selected];
			
			hideActMenu();
			showDialog(response, () => {
				hideDialog();
				enemyTurn();
			});
			
			// 執行 ACT 後可以寬恕
			canMercy = true;
		}

		// ==================== MERCY 系統 ====================
		let canMercy = false;

		function tryMercy() {
			// 需要先進行 ACT 才能使用 MERCY
			if (!canMercy) {
				showDialog('* ' + enemyName + ' 還沒有想要逃跑');
				return;
			}
			
			spare();
		}

		function spare() {
			gameState = 'ended';
			showDialog('* 你寬恕了 ' + enemyName, () => {
				victory(true);
			});
		}

		// ==================== 敵人回合 ====================
		function enemyTurn() {
			gameState = 'enemyTurn';
			turnCount++;
			
			// 狂暴模式處理
			if (furiousMode) {
				furiousRounds++;

				// 立即啟動對應的狂暴攻擊（不要先顯示對話），
				// 對話與回合控制會在 endBulletAttack() 中處理，確保所有子攻擊完成後再對話
				if (furiousRounds === 1) {
					createFuriousAttack1();
				} else if (furiousRounds === 2) {
					createFuriousAttack2();
				} else if (furiousRounds === 3) {
					createFuriousAttack3();
				}

				return; // 狂暴模式下直接返回，不進入玩家回合

			}
			
			// 正常模式：根據回合數選擇攻擊類型（難度遞增）
			let attackType;
			// 如果回合數在攻擊數組範圍內，使用對應的攻擊
			if (turnCount <= currentEnemy.attacks.length) {
				attackType = currentEnemy.attacks[turnCount - 1] || 'codeAttackX';
			} else {
				// 回合超過攻擊數組長度時，循環使用最後幾個高難度攻擊
				// 優先使用終極攻擊，也可以隨機選擇最後幾個
				const highDifficultyAttacks = currentEnemy.attacks.slice(-3); // 最後3個攻擊
				attackType = highDifficultyAttacks[Math.floor(Math.random() * highDifficultyAttacks.length)] || 'codeAttackUltimate';
			}
			
			// 顯示奶油攻擊前的對話
			const turnDialogs = currentEnemy.dialogs.turn || currentEnemy.dialogs.attacking || [];
			const dialogIndex = Math.min(turnCount - 1, turnDialogs.length - 1);
			const dialogText = turnDialogs[dialogIndex] || '* ' + enemyName + ' 攻擊！ (回合 ' + turnCount + ')';
			showDialogWithCharacter(dialogText, 'cream');
			
			setTimeout(() => {
				hideDialog();
				startBulletAttack(attackType);
			}, 2000);
		}

		// ==================== 子彈攻擊系統 ====================
		function startBulletAttack(type) {
			bulletAttackActive = true;
			bullets = [];
			
			// 重置靈魂到中心（每輪攻擊開始時）
			soulPosition = { x: 142, y: 132 };
			const soul = document.getElementById('soul');
			soul.style.left = soulPosition.x + 'px';
			soul.style.top = soulPosition.y + 'px';
			
			switch(type) {
				case 'codeAttackX':
					createCodeAttackX();
					break;
				case 'codeAttackY':
					createCodeAttackY();
					break;
				case 'codeAttackBrackets':
					createCodeAttackBrackets();
					break;
				case 'codeAttackSymbols':
					createCodeAttackSymbols();
					break;
				case 'codeAttackHello':
					createCodeAttackHello();
					break;
				case 'codeAttackHelloNarrow':
					createCodeAttackHelloNarrow();
					break;
				case 'beamAttack':
					createBeamAttack(); // 正常模式：1-2個指令
					break;
				case 'mixedCodeAttack1':
					createMixedCodeAttack1();
					break;
				case 'mixedCodeAttack2':
					createMixedCodeAttack2();
					break;
				case 'codeAttackXY':
					createCodeAttackXY();
					break;
				case 'codeAttackFull':
					createCodeAttackFull();
					break;
				case 'codeAttackUltimate':
					createCodeAttackUltimate();
					break;
				default:
					createCodeAttackX();
			}
		}

		function createSimpleBullets() {
			// 回合 1：簡單從上往下
			const count = 5 + Math.floor(turnCount / 2);
			const speed = 2 + (turnCount * 0.2);
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const bullet = createBullet(
						Math.random() * 180,
						-10,
						0,
						speed + Math.random() * 0.5
					);
					bullets.push(bullet);
				}, i * (300 - turnCount * 20));
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, count * (300 - turnCount * 20) + 3000);
		}

		function createWaveBullets() {
			// 回合 2：波浪攻擊
			const count = 10 + turnCount * 2;
			const speed = 2.5 + (turnCount * 0.15);
			const delay = Math.max(100, 200 - turnCount * 10);
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const x = (i / count) * 180;
					const bullet = createBullet(x, -10, 0, speed);
					bullets.push(bullet);
				}, i * delay);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, count * delay + 3500);
		}

		function createSpiralBullets() {
			// 回合 3：螺旋攻擊
			const centerX = 142; // 靈魂框中心 (300 / 2)
			const centerY = 132; // 靈魂框中心 (280 / 2)
			const count = 8 + turnCount;
			const speed = 2 + (turnCount * 0.2);
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const angle = (i / count) * Math.PI * 2;
					const bullet = createBullet(
						centerX,
						centerY,
						Math.cos(angle) * speed,
						Math.sin(angle) * speed
					);
					bullets.push(bullet);
				}, i * 120);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 4000);
		}

		function createCrossBullets() {
			// 回合 4：十字攻擊
			const centerX = 142; // 靈魂框中心 (300 / 2)
			const centerY = 132; // 靈魂框中心 (280 / 2)
			const speed = 2.5 + (turnCount * 0.2);
			const directions = [
				{ x: 0, y: -speed },   // 上
				{ x: 0, y: speed },    // 下
				{ x: -speed, y: 0 },   // 左
				{ x: speed, y: 0 }     // 右
			];
			
			directions.forEach((dir, i) => {
				setTimeout(() => {
					const bullet = createBullet(centerX, centerY, dir.x, dir.y);
					bullets.push(bullet);
				}, i * 200);
			});
			
			// 多波十字攻擊
			for (let wave = 1; wave < 3; wave++) {
				setTimeout(() => {
					directions.forEach((dir, i) => {
						const bullet = createBullet(centerX, centerY, dir.x * 1.2, dir.y * 1.2);
						bullets.push(bullet);
					});
				}, wave * 800);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 3500);
		}

		function createChaseBullets() {
			// 回合 5：追蹤子彈
			const count = 6 + turnCount;
			const speed = 2 + (turnCount * 0.15);
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					// 從邊緣發射，朝向靈魂位置
					const side = Math.floor(Math.random() * 4);
					let x, y, vx, vy;
					
					if (side === 0) { // 上
						x = Math.random() * 300;
						y = -10;
						vx = (Math.random() - 0.5) * speed;
						vy = speed;
					} else if (side === 1) { // 下
						x = Math.random() * 300;
						y = 290;
						vx = (Math.random() - 0.5) * speed;
						vy = -speed;
					} else if (side === 2) { // 左
						x = -10;
						y = Math.random() * 280;
						vx = speed;
						vy = (Math.random() - 0.5) * speed;
					} else { // 右
						x = 310;
						y = Math.random() * 280;
						vx = -speed;
						vy = (Math.random() - 0.5) * speed;
					}
					
					const bullet = createBullet(x, y, vx, vy);
					bullet.chase = true;
					bullet.speed = speed;
					bullets.push(bullet);
				}, i * 250);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 5000);
		}

		function createGridBullets() {
			// 回合 6：網格攻擊
			const rows = 3 + Math.floor(turnCount / 3);
			const cols = 4 + Math.floor(turnCount / 2);
			const speed = 2 + (turnCount * 0.15);
			
			// 從上往下
			for (let col = 0; col < cols; col++) {
				setTimeout(() => {
					for (let row = 0; row < rows; row++) {
						const x = (col / (cols - 1)) * 300;
						const bullet = createBullet(x, -10 - row * 30, 0, speed);
						bullets.push(bullet);
					}
				}, col * 150);
			}
			
			// 從左往右
			for (let row = 0; row < rows; row++) {
				setTimeout(() => {
					for (let col = 0; col < cols; col++) {
						const y = (row / (rows - 1)) * 280;
						const bullet = createBullet(-10 - col * 30, y, speed, 0);
						bullets.push(bullet);
					}
				}, row * 200);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 6000);
		}

		function createMixedAttack1() {
			// 回合 7+：混合攻擊 1（螺旋 + 追蹤）
			createSpiralBullets();
			
			setTimeout(() => {
				createChaseBullets();
			}, 2000);
			
			setTimeout(() => {
				endBulletAttack();
			}, 8000);
		}

		function createMixedAttack2() {
			// 回合 8+：混合攻擊 2（最難）
			// 十字 + 網格 + 追蹤
			createCrossBullets();
			
			setTimeout(() => {
				createGridBullets();
			}, 2000);
			
			setTimeout(() => {
				createChaseBullets();
			}, 4000);
			
			setTimeout(() => {
				endBulletAttack();
			}, 10000);
		}

		function createBullet(x, y, vx, vy) {
			const soulBox = document.getElementById('soulBox');
			const bullet = document.createElement('div');
			bullet.className = 'bullet';
			
			// 紅色醒目的圓形子彈
			bullet.style.width = '8px';
			bullet.style.height = '8px';
			bullet.style.left = x + 'px';
			bullet.style.top = y + 'px';
			bullet.style.background = '#ff0000';
			bullet.vx = vx;
			bullet.vy = vy;
			
			soulBox.appendChild(bullet);
			
			return bullet;
		}

		function endBulletAttack() {
			bulletAttackActive = false;

			// 清除所有 createBeamAttack 建立的 interval（避免在攻擊結束後仍繼續產生子彈）
			if (beamFireIntervals && beamFireIntervals.length > 0) {
				beamFireIntervals.forEach(id => clearInterval(id));
				beamFireIntervals = [];
			}

			// 移除尚未被清理的指令文字
			const soulBoxArea = document.querySelector('.soul-box-area');
			if (soulBoxArea) {
				const cmds = soulBoxArea.querySelectorAll('.beam-command');
				cmds.forEach(c => c.remove());
			}

			// 清除所有複合攻擊排程的 timeouts
			if (activeAttackTimeouts && activeAttackTimeouts.length > 0) {
				activeAttackTimeouts.forEach(tid => clearTimeout(tid));
				activeAttackTimeouts = [];
			}
			
			// 恢復靈魂框大小（如果被縮窄或變矮了）
			if (window.tempSoulBoxNarrow) {
				const soulBox = document.getElementById('soulBox');
				if (window.tempSoulBoxWidth) {
					// 恢復寬度
					soulBox.style.transition = 'width 0.5s, margin-left 0.5s';
					soulBox.style.width = '300px';
					soulBox.style.marginLeft = '0';
				}
				if (window.tempSoulBoxHeight) {
					// 恢復高度
					soulBox.style.transition = 'height 0.5s, margin-top 0.5s';
					soulBox.style.height = window.tempSoulBoxOriginalHeight + 'px';
					soulBox.style.marginTop = '0';
				}
				
				window.tempSoulBoxNarrow = false;
				window.tempSoulBoxWidth = null;
				window.tempSoulBoxHeight = null;
				window.tempSoulBoxOriginalHeight = null;
				
				soulPosition = { x: 142, y: 132 };
				const soul = document.getElementById('soul');
				soul.style.left = soulPosition.x + 'px';
				soul.style.top = soulPosition.y + 'px';
			}
			
			bullets.forEach(bullet => {
				if (bullet.parentNode) {
					bullet.remove();
				}
			});
			bullets = [];
			
			// 顯示攻擊結束後的對話
			// 狂暴模式下直接進入下一輪敵人回合
			if (furiousMode && furiousRounds < 3) {
				const afterAttackDialogs = [
					'* 攻擊還沒結束！',
					'* 繼續接招吧！',
					'* 還沒完呢！'
				];
				const dialogText = afterAttackDialogs[(furiousRounds - 1) % afterAttackDialogs.length] || '* 攻擊繼續！';
				showDialogWithCharacter(dialogText, 'cream', () => {
					hideDialog();
					// 直接進入下一輪敵人回合（跳過玩家回合）
					setTimeout(() => {
						enemyTurn();
					}, 500);
				});
			} else if (furiousMode && furiousRounds >= 3) {
				// 狂暴模式結束
				furiousMode = false;
				furiousRounds = 0;
				showDialogWithCharacter('* 奶油：可惡...體力耗盡了...', 'cream', () => {
					hideDialog();
					gameState = 'playerTurn';
				});
			} else {
				// 正常模式
				const afterAttackDialogs = currentEnemy.dialogs.afterAttack || [];
				let dialogText;
				if (afterAttackDialogs.length > 0) {
					// 根據回合數選擇不同的對話，或者隨機選擇
					const dialogIndex = Math.min(turnCount - 1, afterAttackDialogs.length - 1);
					dialogText = afterAttackDialogs[dialogIndex] || afterAttackDialogs[Math.floor(Math.random() * afterAttackDialogs.length)];
				} else {
					dialogText = '* 攻擊結束！輪到你了！';
				}
				
				showDialogWithCharacter(dialogText, 'cream', () => {
					hideDialog();
					gameState = 'playerTurn';
				});
			}
		}

		// ==================== 程式碼攻擊系統 ====================
		function createCodeAttack(text, x, y, vx, vy, size = 24, willExplode = false) {
			const soulBox = document.getElementById('soulBox');
			const codeAttack = document.createElement('div');
			codeAttack.className = 'code-attack';
			codeAttack.textContent = text;
			codeAttack.style.left = x + 'px';
			codeAttack.style.top = y + 'px';
			codeAttack.style.fontSize = size + 'px';
			codeAttack.style.color = '#000000'; // 明確設置為黑色
			codeAttack.style.backgroundColor = 'transparent'; // 確保背景透明
			codeAttack.vx = vx;
			codeAttack.vy = vy;
			codeAttack.width = text.length * size * 0.6; // 估算寬度
			codeAttack.height = size;
			codeAttack.willExplode = willExplode; // 標記是否會爆炸
			
			soulBox.appendChild(codeAttack);
			return codeAttack;
		}

		// 創建爆炸效果（濺射傷害）
		function createExplosion(x, y, sourceBullet) {
			const soulBox = document.getElementById('soulBox');
			const explosionCount = 6 + Math.floor(turnCount / 2); // 後期更多碎片
			const explosionSpeed = 1.5 + (turnCount * 0.1);
			
			for (let i = 0; i < explosionCount; i++) {
				setTimeout(() => {
					const angle = (i / explosionCount) * Math.PI * 2;
					const vx = Math.cos(angle) * explosionSpeed;
					const vy = Math.sin(angle) * explosionSpeed;
					
					const fragment = document.createElement('div');
					fragment.className = 'explosion';
					fragment.style.left = x + 'px';
					fragment.style.top = y + 'px';
					fragment.vx = vx;
					fragment.vy = vy;
					fragment.lifetime = 40; // 碎片存在時間
					
					soulBox.appendChild(fragment);
					bullets.push(fragment);
				}, i * 10);
			}
		}

		function createCodeAttackX() {
			// 回合 1：X 字攻擊
			const speed = 2 + (turnCount * 0.15);
			const count = 4 + turnCount;
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const x = (i / (count - 1)) * 260 + 20;
					const codeAttack = createCodeAttack('X', x, -30, 0, speed, 28);
					bullets.push(codeAttack);
				}, i * 400);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, count * 400 + 3000);
		}

		function createCodeAttackY() {
			// 回合 2：Y 字攻擊
			const speed = 2 + (turnCount * 0.15);
			const count = 5 + turnCount;
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const x = (i / (count - 1)) * 260 + 20;
					const codeAttack = createCodeAttack('Y', x, -30, 0, speed, 28);
					bullets.push(codeAttack);
				}, i * 350);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, count * 350 + 3500);
		}

		function createCodeAttackBrackets() {
			// 回合 3：{} 括號攻擊
			const speed = 2 + (turnCount * 0.15);
			const count = 6 + turnCount;
			const symbols = ['{', '}', '[', ']', '(', ')'];
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const symbol = symbols[i % symbols.length];
					const x = (i / (count - 1)) * 260 + 20;
					const codeAttack = createCodeAttack(symbol, x, -30, 0, speed, 28);
					bullets.push(codeAttack);
				}, i * 300);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, count * 300 + 3000);
		}

		function createCodeAttackSymbols() {
			// 回合 4：程式碼符號攻擊
			const speed = 2.2 + (turnCount * 0.15);
			const count = 8 + turnCount;
			const symbols = ['{', '}', '[', ']', '(', ')', '<', '>', '=', '+', '-', '*', '/', '%'];
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const symbol = symbols[Math.floor(Math.random() * symbols.length)];
					const side = Math.floor(Math.random() * 4);
					let x, y, vx, vy;
					
					if (side === 0) { // 上
						x = Math.random() * 260 + 20;
						y = -30;
						vx = (Math.random() - 0.5) * speed * 0.5;
						vy = speed;
					} else if (side === 1) { // 下
						x = Math.random() * 260 + 20;
						y = 310;
						vx = (Math.random() - 0.5) * speed * 0.5;
						vy = -speed;
					} else if (side === 2) { // 左
						x = -30;
						y = Math.random() * 260 + 20;
						vx = speed;
						vy = (Math.random() - 0.5) * speed * 0.5;
					} else { // 右
						x = 330;
						y = Math.random() * 260 + 20;
						vx = -speed;
						vy = (Math.random() - 0.5) * speed * 0.5;
					}
					
					const codeAttack = createCodeAttack(symbol, x, y, vx, vy, 26);
					bullets.push(codeAttack);
				}, i * 250);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 6000);
		}

		function createCodeAttackHello() {
			// 回合 5：HELLO WORLD 攻擊（會爆炸）
			const speed = 1.5 + (turnCount * 0.1);
			const texts = ['HELLO', 'WORLD'];
			
			texts.forEach((text, index) => {
				setTimeout(() => {
					// 從上方和下方同時發射，標記為會爆炸
					const codeAttack1 = createCodeAttack(text, 50, -40, 0, speed, 20, true);
					const codeAttack2 = createCodeAttack(text, 50, 320, 0, -speed, 20, true);
					bullets.push(codeAttack1, codeAttack2);
				}, index * 600);
			});
			
			setTimeout(() => {
				endBulletAttack();
			}, 4000);
		}

		function createCodeAttackHelloNarrow() {
			// 回合 6：HELLO WORLD 變框攻擊（變矮框，只能上下移動，文字波浪跳動）
			const soulBox = document.getElementById('soulBox');
			const originalHeight = 280;
			const narrowHeight = 120; // 變矮後的框高度
			const heightOffset = (originalHeight - narrowHeight) / 2;
			
			// 動畫變矮框
			soulBox.style.transition = 'height 0.5s, margin-top 0.5s';
			soulBox.style.height = narrowHeight + 'px';
			soulBox.style.marginTop = heightOffset + 'px';
			
			// 調整靈魂位置到框的中心
			setTimeout(() => {
				const newCenterY = narrowHeight / 2 - 8; // 框中心 - 靈魂高度的一半
				soulPosition.y = Math.max(3, Math.min(newCenterY, narrowHeight - 16 - 3));
				soulPosition.x = 142; // 保持水平居中
				const soul = document.getElementById('soul');
				soul.style.left = soulPosition.x + 'px';
				soul.style.top = soulPosition.y + 'px';
				
				window.tempSoulBoxNarrow = true;
				window.tempSoulBoxHeight = narrowHeight;
				window.tempSoulBoxOriginalHeight = originalHeight;
			}, 500);
			
			// 創建波浪跳動和跑馬燈效果的字母（等待框縮小完成後）
			setTimeout(() => {
				// 完整文字：H E L L O   W O R L D !（注意空格）
				const fullText = ['H', 'E', 'L', 'L', 'O', ' ', ' ', ' ', 'W', 'O', 'R', 'L', 'D', '!','H', 'E', 'L', 'L', 'O', ' ', ' ', ' ', 'W', 'O', 'R', 'L', 'D', '!'];
				const letterSpacing = 18; // 字母間距
				const boxWidth = 300;
				const waveAmplitude = 20; // 波浪幅度
				const waveSpeed = 0.08; // 波浪速度
				const scrollSpeed = 1.5; // 跑馬燈速度
				let waveTime = 0;
				let scrollTime = 0;
				
				// 上方字母基礎位置
				const topBaseY = 15;
				// 下方字母基礎位置
				const bottomBaseY = narrowHeight - 35;
				
				// 計算完整文字的總寬度（包括空格）
				const fullTextWidth = fullText.length * letterSpacing;
				
				// 攻擊持續時間（5.5秒）
				const attackDuration = 5500;
				
				// 計算需要多少組文字以覆蓋整個攻擊期間
				// 估計需要移動的距離：速度 * 時間（假設60fps，約330幀）
				const estimatedFrames = Math.ceil(attackDuration / 16.67); // 約330幀
				const estimatedScrollDistance = estimatedFrames * scrollSpeed; // 約495px
				// 需要足夠的組來覆蓋：移動距離 + 框寬度 + 一組文字寬度（預留緩衝）
				const numSets = Math.ceil((estimatedScrollDistance + boxWidth + fullTextWidth * 2) / fullTextWidth);
				
				const topTexts = [];
				const bottomTexts = [];
				
				// 創建連續的文字組（確保每組之間緊密連接，形成連續的跑馬燈）
				for (let setIndex = 0; setIndex < numSets; setIndex++) {
					fullText.forEach((letter, index) => {
						// 跳過空格，不創建元素
						if (letter === ' ') return;
						
						// 計算該字母在完整文字中的位置（用於波浪相位）
						const letterIndexInFull = index;
						// 計算該組的起始X位置
						// 第一組：-fullTextWidth 開始（在框外左側）
						// 第二組：0 開始（正好接續第一組的結尾）
						// 第三組：fullTextWidth 開始（接續第二組）
						const setStartX = -fullTextWidth + (setIndex * fullTextWidth);
						// 計算該字母在該組內的X位置（考慮前面所有字符的寬度，包括空格）
						let letterX = setStartX;
						// 累加前面所有字符（包括空格）的寬度
						for (let i = 0; i < index; i++) {
							letterX += letterSpacing;
						}
						
						// 創建上方字母
						const topCodeAttack = createCodeAttack(letter, letterX, topBaseY, 0, 0, 20, false);
						topCodeAttack.waveIndex = letterIndexInFull; // 使用完整索引以保持正確的波浪相位
						topCodeAttack.isTop = true;
						topCodeAttack.baseY = topBaseY;
						topCodeAttack.initialX = letterX;
						topCodeAttack.setIndex = setIndex;
						topCodeAttack.textContent = letter; // 確保文字內容正確
						topTexts.push(topCodeAttack);
						bullets.push(topCodeAttack);
						
						// 創建下方字母
						const bottomCodeAttack = createCodeAttack(letter, letterX, bottomBaseY, 0, 0, 20, false);
						bottomCodeAttack.waveIndex = letterIndexInFull; // 使用完整索引以保持正確的波浪相位
						bottomCodeAttack.isTop = false;
						bottomCodeAttack.baseY = bottomBaseY;
						bottomCodeAttack.initialX = letterX;
						bottomCodeAttack.setIndex = setIndex;
						bottomCodeAttack.textContent = letter; // 確保文字內容正確
						bottomTexts.push(bottomCodeAttack);
						bullets.push(bottomCodeAttack);
					});
				}
			
				// 波浪動畫 + 跑馬燈效果
				const animateWave = () => {
					waveTime += waveSpeed;
					scrollTime += scrollSpeed;
					
					// 更新上方字母位置（波浪向下跳動 + 左右跑動）
					topTexts.forEach((letter) => {
						// 確保文字可見性
						if (!letter.parentNode) return;
						
						// 確保顏色為黑色
						if (letter.style.color !== '#000000') {
							letter.style.color = '#000000';
						}
						
						// 每個字母有不同的相位，形成波浪傳播效果
						const phase = letter.waveIndex * 0.8; // 相位差，讓字母依次跳動
						const verticalOffset = Math.sin(waveTime + phase) * waveAmplitude;
						
						// 跑馬燈效果：字母從左向右移動
						let newX = letter.initialX + scrollTime;
						
						const newY = letter.baseY + verticalOffset;
						// 確保字母不會超出框頂部，也不會太接近靈魂區域
						const clampedY = Math.max(5, Math.min(newY, narrowHeight / 2 - 15));
						
						letter.style.left = newX + 'px';
						letter.style.top = clampedY + 'px';
						letter.style.display = 'block'; // 確保顯示
						letter.style.opacity = '1'; // 確保不透明
						letter.currentX = newX; // 保存當前X位置用於碰撞檢測
						
						// 隱藏移出視野的字母（左側）
						if (newX < -50) {
							letter.style.display = 'none';
						} else {
							letter.style.display = 'block';
						}
					});
					
					// 更新下方字母位置（波浪向上跳動 + 左右跑動）
					bottomTexts.forEach((letter) => {
						// 確保文字可見性
						if (!letter.parentNode) return;
						
						// 確保顏色為黑色
						if (letter.style.color !== '#000000') {
							letter.style.color = '#000000';
						}
						
						// 下方字母的波浪方向相反
						const phase = letter.waveIndex * 0.8;
						const verticalOffset = Math.sin(waveTime + phase + Math.PI) * waveAmplitude;
						
						// 跑馬燈效果：字母從左向右移動
						let newX = letter.initialX + scrollTime;
						
						const newY = letter.baseY + verticalOffset;
						// 確保字母不會超出框底部，也不會太接近靈魂區域
						const clampedY = Math.max(narrowHeight / 2 + 15, Math.min(newY, narrowHeight - 30));
						
						letter.style.left = newX + 'px';
						letter.style.top = clampedY + 'px';
						letter.style.display = 'block'; // 確保顯示
						letter.style.opacity = '1'; // 確保不透明
						letter.currentX = newX; // 保存當前X位置用於碰撞檢測
						
						// 隱藏移出視野的字母（左側）
						if (newX < -50) {
							letter.style.display = 'none';
						} else {
							letter.style.display = 'block';
						}
					});
				
					// 檢查碰撞（每個字母獨立檢測）
					topTexts.forEach(letter => {
						const letterY = parseFloat(letter.style.top) || letter.baseY;
						const letterHeight = 20;
						const letterWidth = 15; // 單個字母寬度
						const letterX = letter.currentX || parseFloat(letter.style.left) || 0;
						
						// 只檢測在框內的字母（跑馬燈中的字母）
						if (letterX >= -20 && letterX <= boxWidth + 20) {
							// 檢測靈魂與字母的矩形碰撞
							if (soulPosition.x < letterX + letterWidth && 
								soulPosition.x + 16 > letterX &&
								soulPosition.y < letterY + letterHeight + 3 && 
								soulPosition.y + 16 > letterY - 3) {
								if (!letter.hit) {
									letter.hit = true;
									// 重置 hit 標記，讓持續碰撞能持續造成傷害
									setTimeout(() => {
										letter.hit = false;
									}, 300);
									takeDamage(1);
								}
							}
						}
					});
					
					bottomTexts.forEach(letter => {
						const letterY = parseFloat(letter.style.top) || letter.baseY;
						const letterHeight = 20;
						const letterWidth = 15; // 單個字母寬度
						const letterX = letter.currentX || parseFloat(letter.style.left) || 0;
						
						// 只檢測在框內的字母（跑馬燈中的字母）
						if (letterX >= -20 && letterX <= boxWidth + 20) {
							// 檢測靈魂與字母的矩形碰撞
							if (soulPosition.x < letterX + letterWidth && 
								soulPosition.x + 16 > letterX &&
								soulPosition.y < letterY + letterHeight + 3 && 
								soulPosition.y + 16 > letterY - 3) {
								if (!letter.hit) {
									letter.hit = true;
									setTimeout(() => {
										letter.hit = false;
									}, 300);
									takeDamage(1);
								}
							}
						}
					});
					
					// 持續動畫直到攻擊結束
					if (bulletAttackActive) {
						requestAnimationFrame(animateWave);
					}
				};
				
				// 開始波浪動畫
				animateWave();
			}, 600);
			
			// 攻擊持續時間（5.5秒後結束）
			setTimeout(() => {
				// 停止動畫循環
				bulletAttackActive = false;
				
				// 移除所有文字
				if (typeof topTexts !== 'undefined') {
					topTexts.forEach(text => {
						if (text && text.parentNode) text.remove();
					});
				}
				if (typeof bottomTexts !== 'undefined') {
					bottomTexts.forEach(text => {
						if (text && text.parentNode) text.remove();
					});
				}
				
				// 結束攻擊（會恢復框大小並顯示對話，然後繼續下一回合）
				endBulletAttack();
			}, 5500);
		}


		function createBeamAttack(commandCount = null, suppressEnd = false) {
		// 回合 7：System.out.print 攻擊（從指令文字的 > 位置發射子彈）
		// commandCount: 指定指令數量（null時隨機2-3個，狂暴模式下至少6個）
			const soulBox = document.getElementById('soulBox');
			const soulBoxArea = document.querySelector('.soul-box-area');
			const boxWidth = 300;
			const boxHeight = 280;
			const centerX = 142; // 靈魂框中心X（相對於 soulBox）
			const centerY = 132; // 靈魂框中心Y（相對於 soulBox）
			
			// 獲取 soulBox 和 soulBoxArea 的實際位置
			// 使用 getBoundingClientRect 獲取絕對位置，然後計算相對位置
			const boxRect = soulBox.getBoundingClientRect();
			const areaRect = soulBoxArea.getBoundingClientRect();
			
			// 計算 soulBox 在 soulBoxArea 中的相對位置（考慮滾動）
			const boxOffsetX = boxRect.left - areaRect.left;
			const boxOffsetY = boxRect.top - areaRect.top;
			
			// 決定指令數量（預設改為2-3個；狂暴模式下更大量）
			if (commandCount === null) {
				commandCount = Math.floor(Math.random() * 2) + 2; // 默認：2或3個
			}
			if (furiousMode) {
				// 狂暴模式提升指令數量（至少6個）
				commandCount = Math.max(commandCount, 6);
			}
			const commands = [];
			
			// 指令文字
			const commandText = 'System.out.print("========|===>")';
			const fontSize = 18; // 稍微增大字體以確保清晰可見
			const charWidth = fontSize * 0.6; // 每個字符的寬度（調整係數）
			const textWidth = commandText.length * charWidth; // 完整文字寬度
			const textHeight = fontSize;
			
			// 計算 > 在文字中的位置（最後一個字符）
			const arrowCharIndex = commandText.length - 1; // > 是最後一個字符
			const arrowOffsetInText = arrowCharIndex * charWidth; // > 在文字中的偏移量（從文字左端到>右端的距離）
			
			// 可能的出現位置（只在左右兩側，相對於 soulBoxArea）
			// 需要確保 > 對準靈魂框，文字完全在框外
			const positions = [];
			
			// 確保文字在框外，使用更大的 margin（考慮 padding 和邊距）
			const outerMargin = 40; // 大幅增加距離，確保文字完全在框外
			
			// 左方：文字在框左側，> 應該指向框的左邊
			// 計算左側位置：框的 X - 文字寬度 - 外邊距
			const leftY = boxOffsetY + 40 + Math.random() * (boxHeight - 80); // 留更多邊距
			const leftX = boxOffsetX - textWidth - outerMargin; // 文字左端位置
			positions.push({
				side: 'left',
				x: leftX, // 確保文字完全在框外
				y: leftY,
				arrowX: boxOffsetX, // > 對準框左邊
				arrowY: leftY + textHeight / 2 // > 對準框內Y位置
			});
			
			// 右方：文字在框右側，> 應該指向框的右邊
			// 計算右側位置：框的 X + 框寬度 + 外邊距 - 箭頭位置偏移
			const rightY = boxOffsetY + 40 + Math.random() * (boxHeight - 80); // 留更多邊距
			const rightX = boxOffsetX + boxWidth + outerMargin - arrowOffsetInText; // 調整位置使 > 在框邊
			positions.push({
				side: 'right',
				x: rightX, // 確保文字完全在框外
				y: rightY,
				arrowX: boxOffsetX + boxWidth, // > 對準框右邊
				arrowY: rightY + textHeight / 2
			});
			
			// 如果指令數量超過4個，需要重複使用位置
			// 隨機選擇位置（允許重複以支持5個指令）
			for (let i = 0; i < commandCount; i++) {
				const availablePositions = positions.filter((p, idx) => {
					// 如果指令數量>4，允許重複使用位置
					if (commandCount > 4) return true;
					// 否則不重複使用
					return !commands.some(c => c.positionIndex === idx);
				});
				if (availablePositions.length === 0) break;
				
				const selectedPos = availablePositions[Math.floor(Math.random() * availablePositions.length)];
				const positionIndex = positions.indexOf(selectedPos);
				
				// 創建指令文字元素（添加到 soulBoxArea 而不是 soulBox，這樣才能在框外顯示）
				const commandElement = document.createElement('div');
				commandElement.textContent = commandText;
				commandElement.style.position = 'absolute';
				
				// 確保位置計算正確，文字完全在框外
				// 左側：x 必須小於 boxOffsetX（框的左邊）
				// 右側：x + textWidth 必須大於 boxOffsetX + boxWidth（框的右邊）
				let finalX = selectedPos.x;
				let finalY = selectedPos.y;
				
				// 驗證位置是否正確（調試用，確保文字在框外）
				if (selectedPos.side === 'left') {
					// 左側：文字右端應該在框左邊之外
					const textRightEdge = finalX + textWidth;
					if (textRightEdge >= boxOffsetX) {
						// 如果文字會進入框內，進一步向左移動
						finalX = boxOffsetX - textWidth - outerMargin - 10;
					}
				} else if (selectedPos.side === 'right') {
					// 右側：文字左端應該在框右邊之外
					if (finalX <= boxOffsetX + boxWidth) {
						// 如果文字會進入框內，進一步向右移動
						finalX = boxOffsetX + boxWidth + outerMargin + 10;
					}
				}
				
				commandElement.style.left = finalX + 'px';
				commandElement.style.top = finalY + 'px';
				commandElement.style.fontSize = fontSize + 'px';
				commandElement.style.color = '#000000';
				commandElement.style.fontFamily = '"Courier New", monospace';
				commandElement.style.fontWeight = 'bold';
				commandElement.style.whiteSpace = 'nowrap';
				commandElement.style.zIndex = '15';
				commandElement.style.pointerEvents = 'none';
				commandElement.style.backgroundColor = 'transparent';
				commandElement.className = 'beam-command';
				
				// 添加到 soulBoxArea 而不是 soulBox
				soulBoxArea.style.position = 'relative'; // 確保可以定位子元素
				soulBoxArea.appendChild(commandElement);
				
				// 使用預先計算好的 arrowX 和 arrowY（已經對準靈魂框邊界）
				// 轉換為相對於 soulBox 的座標（用於子彈發射）
				// 子彈從框邊界發射，所以需要稍微調整位置使其從框內開始
				let arrowX, arrowY;
				
				// 計算 > 在靈魂框中的絕對位置（相對於 soulBox）
				const arrowXAbsolute = selectedPos.arrowX - boxOffsetX;
				const arrowYAbsolute = selectedPos.arrowY - boxOffsetY;
				
				// 根據方向調整發射起點（確保子彈從框邊界開始）
				// 只處理左方和右方
				if (selectedPos.side === 'left') {
					// 從左方發射，子彈從框左邊開始，向右發射
					arrowX = 4; // 從框左邊稍內開始
					arrowY = Math.max(4, Math.min(boxHeight - 4, arrowYAbsolute));
				} else { // right
					// 從右方發射，子彈從框右邊開始，向左發射
					arrowX = boxWidth - 4; // 從框右邊稍內開始
					arrowY = Math.max(4, Math.min(boxHeight - 4, arrowYAbsolute));
				}
				
				// 計算朝向靈魂中心的方向
				const dx = centerX - arrowX;
				const dy = centerY - arrowY;
				const distance = Math.sqrt(dx * dx + dy * dy);
				
				// 避免除零錯誤
				if (distance === 0) {
					// 如果距離為0，使用默認方向
					const vx = selectedPos.side === 'left' ? 1 : (selectedPos.side === 'right' ? -1 : 0);
					const vy = selectedPos.side === 'top' ? 1 : (selectedPos.side === 'bottom' ? -1 : 0);
					commands.push({
						element: commandElement,
						arrowX: arrowX,
						arrowY: arrowY,
						vx: vx * (2.5 + turnCount * 0.15),
						vy: vy * (2.5 + turnCount * 0.15),
						positionIndex: positionIndex
					});
					continue;
				}
				
				const speed = 2.5 + (turnCount * 0.15);
				const vx = (dx / distance) * speed;
				const vy = (dy / distance) * speed;
				
				commands.push({
					element: commandElement,
					arrowX: arrowX,
					arrowY: arrowY,
					vx: vx,
					vy: vy,
					positionIndex: positionIndex
				});
			}
			
			// 機關槍式連發：每個指令會在短間隔內連續發射多發子彈
			// 再減少每個指令的子彈數量（減少5顆），但至少保留 1 顆
			const basePerCommand = furiousMode ? 8 : Math.max(2, 3 + Math.floor(turnCount / 4));
			const bulletsPerCommand = Math.max(1, basePerCommand - 3);
			let totalBulletsShot = 0;
			let remainingShots = commandCount * bulletsPerCommand;

			// 確保子彈更新循環正在運行
			if (!bulletAttackActive) {
				bulletAttackActive = true;
			}

			// 發射參數：狂暴模式更快、更猛烈，但保留較低頻率減少負載
			const intervalMs = furiousMode ? 100 : 220;
			const homingSpeed = furiousMode ? 3.2 : 2.2;

			// 對每個指令啟動一個快速連發計時器
			commands.forEach((cmd) => {
				if (!cmd || cmd.arrowX === undefined || cmd.arrowY === undefined) return;
				let fired = 0;
				const id = setInterval(() => {
					// 若命令元素被移除或達到發射數量，停止此間隔
					if (fired >= bulletsPerCommand) {
						clearInterval(id);
						return;
					}

					// 隨機化速度微調，讓子彈不會全部重疊
					const jitterX = (Math.random() - 0.5) * 0.3;
					const jitterY = (Math.random() - 0.5) * 0.3;
					// 建立子彈，設定為追蹤型（homing），初始速度較小以便後續校正方向
					const bullet = createBullet(cmd.arrowX, cmd.arrowY, (cmd.vx + jitterX) * 0.4, (cmd.vy + jitterY) * 0.4);
					if (bullet) {
						bullet.chase = true;
						bullet.speed = homingSpeed;
						// 保留較小初始速度矢量，讓 homing 能逐步調整方向
						bullet.vx = (cmd.vx + jitterX) * 0.4;
						bullet.vy = (cmd.vy + jitterY) * 0.4;
						bullets.push(bullet);
						totalBulletsShot++;
						remainingShots--;
					}

					fired++;

					// 當所有子彈發射完畢，清理指令文字（若非 suppressEnd，則結束攻擊）
					if (remainingShots <= 0) {
						// 等待短暫時間讓子彈進入畫面，再清理
						setTimeout(() => {
							commands.forEach(cmd2 => {
								if (cmd2.element && cmd2.element.parentNode) {
									cmd2.element.remove();
								}
							});
							if (!suppressEnd) {
								endBulletAttack();
							}
						}, furiousMode ? 1800 : 2600);
					}
				}, intervalMs);
				// 儲存在全域陣列以便外部也能清除（例如 endBulletAttack）
				beamFireIntervals.push(id);
			});

			// 如果沒有子彈（理論上不會發生），也要確保結束（除非被 suppress）
			if (remainingShots <= 0) {
				setTimeout(() => {
					commands.forEach(cmd => {
						if (cmd.element && cmd.element.parentNode) {
							cmd.element.remove();
						}
					});
					if (!suppressEnd) {
						endBulletAttack();
					}
				}, 2000);
			}
		}

		function createMixedCodeAttack1() {
			// 回合 4：混合程式碼攻擊 1 (X Y 混合)
			createCodeAttackX();
			setTimeout(() => {
				const speed = 2.5 + (turnCount * 0.15);
				const count = 3;
				for (let i = 0; i < count; i++) {
					setTimeout(() => {
						const codeAttack = createCodeAttack('Y', -30, 60 + i * 40, speed, 0, 28);
						bullets.push(codeAttack);
					}, i * 300);
				}
			}, 1500);
			
			setTimeout(() => {
				endBulletAttack();
			}, 6000);
		}

		function createMixedCodeAttack2() {
			// 回合 5：混合程式碼攻擊 2 (HELLO + X)
			const speed1 = 2;
			const speed2 = 2.5;
			
			setTimeout(() => {
				const codeAttack = createCodeAttack('HELLO', -50, 82, speed2, 0, 18);
				bullets.push(codeAttack);
			}, 0);
			
			for (let i = 0; i < 6; i++) {
				setTimeout(() => {
					const x = (i / 6) * 300;
					const codeAttack = createCodeAttack('X', x, -30, 0, speed1, 24);
					bullets.push(codeAttack);
				}, i * 250 + 800);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 5000);
		}

		function createCodeAttackXY() {
			// 回合 6：X Y 組合攻擊
			const speed = 2.5 + (turnCount * 0.2);
			const count = 8 + turnCount;
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const text = i % 2 === 0 ? 'X' : 'Y';
					const side = Math.floor(Math.random() * 4);
					let x, y, vx, vy;
					
					if (side === 0) { // 上
						x = Math.random() * 260 + 20;
						y = -30;
						vx = (Math.random() - 0.5) * speed;
						vy = speed;
					} else if (side === 1) { // 下
						x = Math.random() * 260 + 20;
						y = 310;
						vx = (Math.random() - 0.5) * speed;
						vy = -speed;
					} else if (side === 2) { // 左
						x = -30;
						y = Math.random() * 260 + 20;
						vx = speed;
						vy = (Math.random() - 0.5) * speed;
					} else { // 右
						x = 330;
						y = Math.random() * 260 + 20;
						vx = -speed;
						vy = (Math.random() - 0.5) * speed;
					}
					
					const codeAttack = createCodeAttack(text, x, y, vx, vy, 26);
					bullets.push(codeAttack);
				}, i * 200);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 6000);
		}

		function createCodeAttackFull() {
			// 回合 7：完整程式碼攻擊（包含爆炸）
			const texts = ['HELLO', 'WORLD', 'X', 'Y', '{', '}', '[', ']'];
			const speed = 2 + (turnCount * 0.15);
			const willExplode = turnCount >= 7; // 第7回合後會爆炸
			
			for (let i = 0; i < texts.length * 2; i++) {
				setTimeout(() => {
					const text = texts[i % texts.length];
					const size = text.length > 1 ? 18 : 24;
					const x = Math.random() * 240 + 30;
					const codeAttack = createCodeAttack(text, x, -40, 0, speed, size, willExplode);
					bullets.push(codeAttack);
				}, i * 300);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 5000);
		}

		function createCodeAttackUltimate() {
			// 回合 8+：終極程式碼攻擊（多重爆炸）
			const texts = ['HELLO', 'WORLD', 'X', 'Y', 'CODE', 'PHP', 'JS', '{', '}', '[', ']', '=', '+', '-'];
			const speed = 2.5 + (turnCount * 0.2);
			const count = 12 + turnCount * 2;
			
			for (let i = 0; i < count; i++) {
				setTimeout(() => {
					const text = texts[Math.floor(Math.random() * texts.length)];
					const size = text.length > 1 ? 16 : 22;
					const side = Math.floor(Math.random() * 4);
					let x, y, vx, vy;
					
					if (side === 0) { // 上
						x = Math.random() * 260 + 20;
						y = -40;
						vx = (Math.random() - 0.5) * speed * 0.8;
						vy = speed;
					} else if (side === 1) { // 下
						x = Math.random() * 260 + 20;
						y = 320;
						vx = (Math.random() - 0.5) * speed * 0.8;
						vy = -speed;
					} else if (side === 2) { // 左
						x = -40;
						y = Math.random() * 260 + 20;
						vx = speed;
						vy = (Math.random() - 0.5) * speed * 0.8;
					} else { // 右
						x = 340;
						y = Math.random() * 260 + 20;
						vx = -speed;
						vy = (Math.random() - 0.5) * speed * 0.8;
					}
					
					// 所有文字都會爆炸（終極難度）
					const codeAttack = createCodeAttack(text, x, y, vx, vy, size, true);
					bullets.push(codeAttack);
				}, i * 150);
			}
			
			setTimeout(() => {
				endBulletAttack();
			}, 7000);
		}

		// ==================== 狂暴模式攻擊 ====================
		function createFuriousAttack1() {
			// 第1回合：8個System.out.print + 終極攻擊混合
			bulletAttackActive = true;
			bullets = [];
			
			// 1. 創建8個System.out.print指令（每個發射更多子彈），但不要在這裡自動結束攻擊
			createBeamAttack(8, true);
			
			// 2. 延遲後添加終極程式碼攻擊（延長間隔）
			scheduleAttack(() => {
				const texts = ['HELLO', 'WORLD', 'X', 'Y', 'CODE', '{', '}', '[', ']'];
				const speed = 3.5; // 更高的速度
				const count = 15; // 更多子彈
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const text = texts[Math.floor(Math.random() * texts.length)];
						const size = text.length > 1 ? 18 : 24;
						const side = Math.floor(Math.random() * 4);
						let x, y, vx, vy;
						
						if (side === 0) { // 上
							x = Math.random() * 260 + 20;
							y = -40;
							vx = (Math.random() - 0.5) * speed * 0.8;
							vy = speed;
						} else if (side === 1) { // 下
							x = Math.random() * 260 + 20;
							y = 320;
							vx = (Math.random() - 0.5) * speed * 0.8;
							vy = -speed;
						} else if (side === 2) { // 左
							x = -40;
							y = Math.random() * 260 + 20;
							vx = speed;
							vy = (Math.random() - 0.5) * speed * 0.8;
						} else { // 右
							x = 340;
							y = Math.random() * 260 + 20;
							vx = -speed;
							vy = (Math.random() - 0.5) * speed * 0.8;
						}
						
						// 50%機率爆炸
						const willExplode = Math.random() > 0.5;
						const codeAttack = createCodeAttack(text, x, y, vx, vy, size, willExplode);
						bullets.push(codeAttack);
					}, i * 300); // 從120ms增加到300ms
				}
			}, 6000); // 從2000ms增加到6000ms
			
			// 3. 再添加追蹤子彈（延長間隔）
			scheduleAttack(() => {
				const centerX = 142;
				const centerY = 132;
				const count = 8;
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const angle = (i / count) * Math.PI * 2;
						const radius = 200;
						const x = centerX + Math.cos(angle) * radius;
						const y = centerY + Math.sin(angle) * radius;
						const speed = 2.8;
						const dx = centerX - x;
						const dy = centerY - y;
						const distance = Math.sqrt(dx * dx + dy * dy);
						const vx = (dx / distance) * speed;
						const vy = (dy / distance) * speed;
						
						const bullet = createBullet(x, y, vx, vy);
						bullet.chase = true;
						bullet.speed = speed;
						bullets.push(bullet);
					}, i * 200); // 從200ms增加到400ms
				}
			}, 3000); // 從3500ms增加到6000ms
			
			scheduleAttack(() => {
				endBulletAttack();
			}, 2000); // 從12000ms增加到30000ms（增加5秒）
		}

		function createFuriousAttack2() {
			// 第2回合：多種攻擊混合 + 追蹤子彈 
			bulletAttackActive = true;
			bullets = [];
			
			// 1. 同時發射多種程式碼攻擊（延長間隔）
			const texts1 = ['X', 'Y', '{', '}', '[', ']'];
			const speed1 = 3.0;
			const count1 = 12;
			
			for (let i = 0; i < count1; i++) {
				scheduleAttack(() => {
					const text = texts1[Math.floor(Math.random() * texts1.length)];
					const x = Math.random() * 260 + 20;
					const codeAttack = createCodeAttack(text, x, -40, 0, speed1, 26, false);
					bullets.push(codeAttack);
				}, i * 480); // 從100ms增加到480ms
			}
			
			// 2. 從左右兩側發射（延長間隔）
			scheduleAttack(() => {
				const texts2 = ['HELLO', 'WORLD', 'CODE'];
				const speed2 = 2.8;
				const count2 = 8;
				
				for (let i = 0; i < count2; i++) {
					scheduleAttack(() => {
						const text = texts2[Math.floor(Math.random() * texts2.length)];
						const y = Math.random() * 260 + 20;
						const fromLeft = Math.random() > 0.5;
						const x = fromLeft ? -50 : 350;
						const vx = fromLeft ? speed2 : -speed2;
						const vy = (Math.random() - 0.5) * speed2 * 0.6;
						const codeAttack = createCodeAttack(text, x, y, vx, vy, 20, true); // 會爆炸
						bullets.push(codeAttack);
					}, i * 550); // 從150ms增加到250ms
				}
			}, 4500); // 從1500ms增加到2500ms
			
			// 3. 追蹤子彈（更密集，延長間隔）
			scheduleAttack(() => {
				const centerX = 142;
				const centerY = 132;
				const count = 12;
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const angle = (i / count) * Math.PI * 2;
						const radius = 180;
						const x = centerX + Math.cos(angle) * radius;
						const y = centerY + Math.sin(angle) * radius;
						const speed = 3.2;
						const dx = centerX - x;
						const dy = centerY - y;
						const distance = Math.sqrt(dx * dx + dy * dy);
						const vx = (dx / distance) * speed;
						const vy = (dy / distance) * speed;
						
						const bullet = createBullet(x, y, vx, vy);
						bullet.chase = true;
						bullet.speed = speed;
						bullets.push(bullet);
					}, i * 550); // 從150ms增加到550ms
				}
			}, 8000); // 從3000ms增加到5000ms
			

			
			scheduleAttack(() => {
				endBulletAttack();
			}, 8000); // 從13000ms增加到18000ms（增加5秒）
		}

		function createFuriousAttack3() {
			// 第3回合：終極混合攻擊（10個System.out.print + 所有攻擊類型）
			bulletAttackActive = true;
			bullets = [];
			
			// 1. 創建10個System.out.print指令（最多數量），但不要在這裡自動結束攻擊
			createBeamAttack(10, true);
			
			// 2. 立即添加多方向程式碼攻擊
			scheduleAttack(() => {
				const texts = ['HELLO', 'WORLD', 'X', 'Y', 'CODE', 'PHP', 'JS', '{', '}', '[', ']', '=', '+', '-', '*', '/'];
				const speed = 3.8; // 最高速度
				const count = 24; // 增加子彈數量
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const text = texts[Math.floor(Math.random() * texts.length)];
						const size = text.length > 1 ? 16 : 22;
						const side = Math.floor(Math.random() * 4);
						let x, y, vx, vy;
						
						if (side === 0) { // 上
							x = Math.random() * 260 + 20;
							y = -40;
							vx = (Math.random() - 0.5) * speed * 0.95;
							vy = speed;
						} else if (side === 1) { // 下
							x = Math.random() * 260 + 20;
							y = 320;
							vx = (Math.random() - 0.5) * speed * 0.95;
							vy = -speed;
						} else if (side === 2) { // 左
							x = -40;
							y = Math.random() * 260 + 20;
							vx = speed;
							vy = (Math.random() - 0.5) * speed * 0.95;
						} else { // 右
							x = 340;
							y = Math.random() * 260 + 20;
							vx = -speed;
							vy = (Math.random() - 0.5) * speed * 0.95;
						}
						
						const willExplode = Math.random() > 0.25; // 提高爆炸率
						const codeAttack = createCodeAttack(text, x, y, vx, vy, size, willExplode);
						bullets.push(codeAttack);
					}, i * 160);
				}
			}, 1500);
			
			// 3. 密集追蹤子彈
			scheduleAttack(() => {
				const centerX = 142;
				const centerY = 132;
				const count = 16; // 更多追蹤子彈
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const angle = (i / count) * Math.PI * 2;
						const radius = 160;
						const x = centerX + Math.cos(angle) * radius;
						const y = centerY + Math.sin(angle) * radius;
						const speed = 3.5; // 更快的追蹤速度
						const dx = centerX - x;
						const dy = centerY - y;
						const distance = Math.sqrt(dx * dx + dy * dy);
						const vx = (dx / distance) * speed;
						const vy = (dy / distance) * speed;
						
						const bullet = createBullet(x, y, vx, vy);
						bullet.chase = true;
						bullet.speed = speed;
						bullets.push(bullet);
					}, i * 120);
				}
			}, 3000);
			
			// 4. 螺旋攻擊
			// 3b. 額外的終極混合（中期爆發）
			scheduleAttack(() => {
				const textsMid = ['ULTIMATE', 'CODE', 'HELLO', 'WORLD', 'PHP', 'JS'];
				const speedMid = 3.6;
				const countMid = 14;
				for (let i = 0; i < countMid; i++) {
					scheduleAttack(() => {
						const text = textsMid[Math.floor(Math.random() * textsMid.length)];
						const size = text.length > 1 ? 16 : 22;
						const side = Math.floor(Math.random() * 4);
						let x, y, vx, vy;
						if (side === 0) { x = Math.random() * 260 + 20; y = -40; vx = (Math.random() - 0.5) * speedMid; vy = speedMid; }
						else if (side === 1) { x = Math.random() * 260 + 20; y = 320; vx = (Math.random() - 0.5) * speedMid; vy = -speedMid; }
						else if (side === 2) { x = -40; y = Math.random() * 260 + 20; vx = speedMid; vy = (Math.random() - 0.5) * speedMid; }
						else { x = 340; y = Math.random() * 260 + 20; vx = -speedMid; vy = (Math.random() - 0.5) * speedMid; }
						bullets.push(createCodeAttack(text, x, y, vx, vy, size, Math.random() > 0.3));
					}, i * 140);
				}
			}, 9000);
			scheduleAttack(() => {
				const centerX = 142;
				const centerY = 132;
				const count = 36; // 增加螺旋數量
				const speed = 3.0;
				
				for (let i = 0; i < count; i++) {
					scheduleAttack(() => {
						const angle = (i / count) * Math.PI * 2;
						const bullet = createBullet(
							centerX,
							centerY,
							Math.cos(angle) * speed,
							Math.sin(angle) * speed
						);
						bullets.push(bullet);
					}, i * 140); // 延長內圈發射間隔
				}
			},16000); // 延後螺旋第一波

			// 4b. 第二波螺旋（較晚出現，變速與偏移角度）
			scheduleAttack(() => {
				const centerX = 142;
				const centerY = 132;
				const count2 = 36;
				const speed2 = 3.2;
				for (let i = 0; i < count2; i++) {
					scheduleAttack(() => {
						const angle = (i / count2) * Math.PI * 2 + Math.PI / count2; // 小偏移
						const bullet = createBullet(centerX, centerY, Math.cos(angle) * speed2, Math.sin(angle) * speed2);
						bullets.push(bullet);
					}, i * 140);
				}
			}, 24000);
			
			scheduleAttack(() => {
				endBulletAttack();
			}, 30000); // 縮短為 30000ms
		}

		// ==================== 靈魂移動 ====================
		let keys = {};
		const soulSpeed = 3;

		document.addEventListener('keydown', (e) => {
			const k = (e.key || '').length === 1 ? (e.key || '').toLowerCase() : e.key;
			keys[k] = true;
		});

		document.addEventListener('keyup', (e) => {
			const k = (e.key || '').length === 1 ? (e.key || '').toLowerCase() : e.key;
			keys[k] = false;
		});

		function updateSoul() {
			const soul = document.getElementById('soul');
			
			// 持續更新子彈（無論是否在攻擊）
			updateBullets();
			checkCollisions();
			
			// 在子彈攻擊、敵人回合或教學期間可以移動靈魂
			if (bulletAttackActive || gameState === 'enemyTurn' || gameState === 'tutorial' || tutorialTrapActive) {
				// 移動靈魂 - 使用方向鍵
				// 確保靈魂不超出外框（考慮靈魂大小16px，邊框3px）
				// 如果框被縮窄或變矮，使用調整後的尺寸
				let boxWidth = 300;
				let boxHeight = 280;
				if (window.tempSoulBoxNarrow) {
					if (window.tempSoulBoxWidth) {
						boxWidth = window.tempSoulBoxWidth;
					}
					if (window.tempSoulBoxHeight) {
						boxHeight = window.tempSoulBoxHeight;
					}
				}
				
				const minX = 3;
				const maxX = boxWidth - 3 - 16;
				const minY = 3;
				const maxY = boxHeight - 3 - 16;
				
				// 變矮模式下，靈魂可以上下左右移動（配合跑馬燈效果）
				// 不再鎖定 X 位置
				
				if (keys['ArrowUp'] || keys['w'] || keys['W']) {
					soulPosition.y = Math.max(minY, soulPosition.y - soulSpeed);
				}
				if (keys['ArrowDown'] || keys['s'] || keys['S']) {
					soulPosition.y = Math.min(maxY, soulPosition.y + soulSpeed);
				}
				if (keys['ArrowLeft'] || keys['a'] || keys['A']) {
					soulPosition.x = Math.max(minX, soulPosition.x - soulSpeed);
				}
				if (keys['ArrowRight'] || keys['d'] || keys['D']) {
					soulPosition.x = Math.min(maxX, soulPosition.x + soulSpeed);
				}
				
				// 更新靈魂位置（不使用 transition，直接設置以獲得流暢的移動）
				soul.style.left = soulPosition.x + 'px';
				soul.style.top = soulPosition.y + 'px';
			}
		}

		function updateBullets() {
			// 若沒有任何子彈也沒在攻擊，跳過更新；
			// 若還有子彈存在（即使 bulletAttackActive 為 false），仍需繼續更新以避免子彈停住
			if (!bulletAttackActive && bullets.length === 0) return;
			
			bullets.forEach((bullet, index) => {
				if (!bullet.parentNode) return;
				
				let left = parseFloat(bullet.style.left) || 0;
				let top = parseFloat(bullet.style.top) || 0;
				
				// 處理爆炸碎片（有生命週期）
				if (bullet.lifetime !== undefined) {
					bullet.lifetime--;
					if (bullet.lifetime <= 0) {
						bullet.remove();
						bullets.splice(index, 1);
						return;
					}
				}
				
				// 更新子彈位置
				// 支援追蹤子彈：若標記為 chase，逐步調整速度向靈魂靠攏
				if (bullet.chase) {
					// 設定目標為靈魂中心
					const targetX = soulPosition.x + 8; // 靈魂中心偏移
					const targetY = soulPosition.y + 8;
					const dxTo = targetX - left;
					const dyTo = targetY - top;
					const distTo = Math.sqrt(dxTo * dxTo + dyTo * dyTo) || 1;
					const desiredVx = (dxTo / distTo) * (bullet.speed || 2.2);
					const desiredVy = (dyTo / distTo) * (bullet.speed || 2.2);
					// 緩慢靠攏（避免瞬間轉向造成不自然運動）
					const homingStrength = 0.12; // 值越大追蹤越靈敏
					bullet.vx = (bullet.vx || 0) * (1 - homingStrength) + desiredVx * homingStrength;
					bullet.vy = (bullet.vy || 0) * (1 - homingStrength) + desiredVy * homingStrength;
				}
				if (bullet.vx !== undefined && bullet.vy !== undefined) {
					left += bullet.vx;
					top += bullet.vy;
					bullet.style.left = left + 'px';
					bullet.style.top = top + 'px';
				}
				
				// 使用 soulBox 的實際寬高作為邊界判斷，並提供適度的外擴 margin
				const soulBox = document.getElementById('soulBox');
				const boxWidth = soulBox ? soulBox.clientWidth : 300;
				const boxHeight = soulBox ? soulBox.clientHeight : 280;
				const margin = 60; // 允許子彈飛出框外一段距離再移除，避免被過早刪除
				// 程式碼文字可能比圓形子彈需要更大的容差
				const outLeft = -margin;
				const outRight = boxWidth + margin;
				const outTop = -margin;
				const outBottom = boxHeight + margin;
				if (left < outLeft || left > outRight || top < outTop || top > outBottom) {
					// 如果是會爆炸的文字且後期關卡，在邊界處也爆炸
					if (bullet.willExplode && turnCount >= 6 && bullet.textContent) {
						createExplosion(left, top, bullet);
					}
					bullet.remove();
					bullets.splice(index, 1);
				}
			});
		}

		function checkCollisions() {
			// 同 updateBullets：若還有子彈存在，即使 bulletAttackActive 為 false 也要檢查碰撞
			if (!bulletAttackActive && bullets.length === 0) return;
			
			const soulRect = {
				x: soulPosition.x,
				y: soulPosition.y,
				width: 16,
				height: 16
			};
			
			bullets.forEach((bullet, index) => {
				if (!bullet.parentNode) return;
				
				// 光柱特殊碰撞檢測
				if (bullet.className === 'beam') {
					const beamLeft = parseFloat(bullet.style.left) || 0;
					const beamTop = parseFloat(bullet.style.top) || 0;
					const beamWidth = 40;
					const beamHeight = 400;
					
					const beamRect = {
						x: beamLeft - beamWidth / 2,
						y: beamTop - beamHeight / 2,
						width: beamWidth,
						height: beamHeight
					};
					
					const soulCenterX = soulRect.x + soulRect.width / 2;
					const soulCenterY = soulRect.y + soulRect.height / 2;
					
					if (soulCenterX >= beamRect.x && soulCenterX <= beamRect.x + beamRect.width &&
						soulCenterY >= beamRect.y && soulCenterY <= beamRect.y + beamRect.height &&
						bullet.style.opacity !== '0' && !bullet.damaged) {
						bullet.damaged = true;
						takeDamage(1);
					}
					return;
				}
				
				const bulletLeft = parseFloat(bullet.style.left) || 0;
				const bulletTop = parseFloat(bullet.style.top) || 0;
				
				let bulletRect;
				if (bullet.textContent) {
					// 程式碼文字攻擊
					const fontSize = parseFloat(bullet.style.fontSize) || 24;
					const textWidth = bullet.textContent.length * fontSize * 0.6;
					bulletRect = {
						x: bulletLeft,
						y: bulletTop,
						width: textWidth,
						height: fontSize
					};
				} else if (bullet.className === 'explosion') {
					// 爆炸碎片
					const fragmentSize = 4;
					bulletRect = {
						x: bulletLeft,
						y: bulletTop,
						width: fragmentSize,
						height: fragmentSize
					};
				} else if (bullet.className === 'explosion') {
					// 爆炸碎片
					const fragmentSize = 4;
					bulletRect = {
						x: bulletLeft,
						y: bulletTop,
						width: fragmentSize,
						height: fragmentSize
					};
				} else {
					// 圓形子彈
					const bulletSize = 8;
					bulletRect = {
						x: bulletLeft,
						y: bulletTop,
						width: bulletSize,
						height: bulletSize
					};
				}
				
				if (checkRectCollision(soulRect, bulletRect)) {
					// 靈魂受傷（有短暫無敵時間）
					if (!bullet.hit) {
						bullet.hit = true;
						
						// 如果是程式碼文字，爆炸產生濺射傷害（後期關卡）
						if (bullet.textContent && turnCount >= 4) {
							createExplosion(bulletLeft, bulletTop, bullet);
						}
						
						bullet.remove();
						bullets.splice(index, 1);
						takeDamage(1);
					}
				}
			});
		}

		function checkRectCollision(rect1, rect2) {
			return rect1.x < rect2.x + rect2.width &&
				   rect1.x + rect1.width > rect2.x &&
				   rect1.y < rect2.y + rect2.height &&
				   rect1.y + rect1.height > rect2.y;
		}

		function takeDamage(amount) {
			if (invulnerable) return; // 無敵時間內不受傷
			
			// 傷害調低：基礎傷害 1，後面回合略微增加
			const baseDamage = 1;
			const adjustedDamage = baseDamage + Math.floor(turnCount / 5);
			playerHP = Math.max(0, playerHP - adjustedDamage);
			
			// 靈魂受傷動畫
			const soul = document.getElementById('soul');
			soul.classList.add('damaged');
			setTimeout(() => {
				soul.classList.remove('damaged');
			}, 300);
			
			// 顯示傷害數字
			const soulBox = document.getElementById('soulBox');
			const soulBoxRect = soulBox.getBoundingClientRect();
			showDamage(adjustedDamage, soulBoxRect.left + soulPosition.x + 8, soulBoxRect.top + soulPosition.y + 8);
			
			// 設置無敵時間（1秒）
			invulnerable = true;
			invulnerableTime = 60; // 60幀（約1秒，假設60fps）
			
			updateUI();
			
			if (playerHP <= 0) {
				gameOver();
			}
		}

		// ==================== 遊戲結束 ====================
		function victory(spared = false) {
			gameState = 'ended';
			const resultScreen = document.getElementById('resultScreen');
			const resultText = document.getElementById('resultText');
			
			if (spared) {
				resultText.textContent = '* 你寬恕了 ' + enemyName + '\n* 勝利！';
			} else {
				resultText.textContent = '* ' + enemyName + ' 被擊敗了\n* 勝利！';
			}
			
			resultScreen.classList.add('show');
		}

		function gameOver() {
			gameState = 'ended';
			const resultScreen = document.getElementById('resultScreen');
			const resultText = document.getElementById('resultText');
			
			resultText.innerHTML = '＊ 遊戲結束<br>＊ 你被擊敗了';
			resultScreen.classList.add('show');
		}

		function restartBattle() {
			// 重置所有狀態
			turnCount = 0;
			canMercy = false;
			bullets = [];
			bulletAttackActive = false;
			playerHP = playerMaxHP;
			enemyHP = currentEnemy.maxHP;
			soulPosition = { x: 142, y: 132 };
			furiousMode = false;
			furiousRounds = 0;
			
			// 清除所有子彈
			const soulBox = document.getElementById('soulBox');
			const existingBullets = soulBox.querySelectorAll('.bullet');
			existingBullets.forEach(bullet => bullet.remove());
			
			// 重置靈魂位置
			const soul = document.getElementById('soul');
			soul.style.left = soulPosition.x + 'px';
			soul.style.top = soulPosition.y + 'px';
			
			// 隱藏結果畫面
			document.getElementById('resultScreen').classList.remove('show');
			
			// 重新初始化
			initBattle();
		}

		// ==================== 遊戲循環 ====================
		// 更新回合 UI：Fight 按鈕在玩家回合時套用樣式
		function updateTurnUI() {
			const btnFightEl = document.getElementById('btnFight');
			if (!btnFightEl) return;
			if (gameState === 'playerTurn') {
				btnFightEl.classList.add('player-turn');
			} else {
				btnFightEl.classList.remove('player-turn');
			}
		}

		// Help modal 顯示與關閉
		function showHelp() {
			const modal = document.getElementById('helpModal');
			if (modal) modal.classList.add('show');
			// 防止 body 捲軸顯示/隱藏導致 layout 跳動
			document.body.style.overflow = 'hidden';
		}
		function hideHelp() {
			const modal = document.getElementById('helpModal');
			if (modal) modal.classList.remove('show');
			// 恢復 body 捲軸
			document.body.style.overflow = '';
		}

		// 綁定 Help 按鈕與關閉按鈕事件
		document.addEventListener('DOMContentLoaded', () => {
			const btnHelp = document.getElementById('btnHelp');
			const helpClose = document.getElementById('helpClose');
			if (btnHelp) btnHelp.addEventListener('click', (e) => { e.stopPropagation(); showHelp(); });
			if (helpClose) helpClose.addEventListener('click', (e) => { e.stopPropagation(); hideHelp(); });
			// 點擊 modal 以外區域也關閉
			document.addEventListener('click', (e) => {
				const modal = document.getElementById('helpModal');
				if (!modal) return;
				if (!modal.classList.contains('show')) return;
				if (!modal.contains(e.target) && e.target.id !== 'btnHelp') {
					hideHelp();
				}
			});
			// Esc 關閉
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape') hideHelp();
			});
		});

		function gameLoop() {
			updateSoul();
			// 更新回合提示 UI
			updateTurnUI();
			
			// 更新無敵時間
			if (invulnerable) {
				invulnerableTime--;
				if (invulnerableTime <= 0) {
					invulnerable = false;
					const soul = document.getElementById('soul');
					soul.style.opacity = '1';
				} else {
					// 無敵時間閃爍效果
					const soul = document.getElementById('soul');
					soul.style.opacity = (Math.floor(invulnerableTime / 5) % 2) ? '0.3' : '1';
				}
			}
			
			requestAnimationFrame(gameLoop);
		}

		// ==================== 啟動遊戲 ====================
		initBattle();
		gameLoop();
	</script>
<?php include("share/footer.php"); ?>
</body>
</html>
