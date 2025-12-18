<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) &&
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>方塊射擊遊戲 - 程式設計挑戰 - 康寧大學</title>
	
	<!-- Blockly 庫 -->
	<script src="https://unpkg.com/blockly@10.4.0/blockly_compressed.js"></script>
	<script src="https://unpkg.com/blockly@10.4.0/blocks_compressed.js"></script>
	<script src="https://unpkg.com/blockly@10.4.0/javascript_compressed.js"></script>
	<script src="https://unpkg.com/blockly@10.4.0/msg/zh-hant.js"></script>
	<link href="https://unpkg.com/blockly@10.4.0/blockly.min.css" rel="stylesheet" />
	
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
			background: #f8f9fa;
			color: #2c3e50;
			padding-top: 100px;
			min-height: 100vh;
		}

        /* 返回按鈕樣式 */
.btn-back-fight {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
    width: 200px;
}

.btn-back-fight:hover {
    background: linear-gradient(135deg, #5568d3 0%, #6a3d8f 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
}

		.game-main {
			width: 100%;
			max-width: 1600px;
			margin: 0 auto;
			padding: 20px;
            margin-bottom: 40px;
		}

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
            margin-top: 10px;
		}

		.game-title {
			font-size: 32px;
			font-weight: bold;
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.game-stats {
			display: flex;
			gap: 20px;
		}

		.stat-item {
			background: rgba(255, 255, 255, 0.2);
			padding: 10px 20px;
			border-radius: 10px;
			backdrop-filter: blur(10px);
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
		}

		@media (max-width: 1024px) {
			.game-container {
				grid-template-columns: 1fr;
			}
		}

		/* 遊戲區域 */
		.game-section {
			background: #ffffff;
			border-radius: 10px;
			padding: 20px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
			position: relative;
		}

		.game-section-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
		}

		.section-title {
			font-size: 24px;
			font-weight: bold;
			color: #667eea;
		}

		#gameCanvas {
			background: #1a1a2e;
			border: 3px solid #dee2e6;
			border-radius: 10px;
			width: 100%;
			display: block;
		}

		.health-bar-container {
			position: absolute;
			top: 70px;
			left: 30px;
			width: 200px;
			height: 20px;
			background: rgba(255,0,0,0.3);
			border: 2px solid rgba(255,255,255,0.5);
			border-radius: 10px;
			overflow: hidden;
			z-index: 10;
		}

		.health-fill {
			height: 100%;
			background: linear-gradient(90deg, #ff0000, #ff6b6b);
			transition: width 0.3s;
			width: 100%;
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

		#blocklyDiv {
			height: 500px;
			width: 100%;
			border: 2px solid #dee2e6;
			border-radius: 8px;
		}

		.control-buttons {
			display: flex;
			gap: 10px;
			margin-top: 15px;
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
			font-family: inherit;
		}

		.btn-run {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
		}

		.btn-run:hover:not(:disabled) {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		}

		.btn-reset {
			background: #6c757d;
			color: white;
		}

		.btn-reset:hover:not(:disabled) {
			background: #5a6268;
		}

		.btn:disabled {
			opacity: 0.6;
			cursor: not-allowed;
		}

		.result-message {
			margin-top: 15px;
			padding: 10px;
			border-radius: 8px;
			text-align: center;
			font-weight: bold;
			display: none;
		}

		.result-message.success {
			background: #d4edda;
			color: #155724;
			display: block;
		}

		.result-message.error {
			background: #f8d7da;
			color: #721c24;
			display: block;
		}

		.modal {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0,0,0,0.7);
			z-index: 1000;
			justify-content: center;
			align-items: center;
		}

		.modal.show {
			display: flex;
		}

		.modal-content {
			background: white;
			padding: 40px;
			border-radius: 20px;
			text-align: center;
			max-width: 500px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.3);
		}

		.modal-title {
			font-size: 36px;
			margin-bottom: 20px;
			color: #667eea;
		}

		.modal-title.success {
			color: #28a745;
		}

		.modal-title.failure {
			color: #dc3545;
		}

		.modal-message {
			font-size: 18px;
			margin-bottom: 30px;
			line-height: 1.6;
		}

		.modal-buttons {
			display: flex;
			gap: 10px;
			justify-content: center;
		}

		.modal-btn {
			padding: 12px 24px;
			border: none;
			border-radius: 8px;
			font-size: 16px;
			font-weight: bold;
			cursor: pointer;
			transition: all 0.3s;
			font-family: inherit;
		}

		.modal-btn-next {
			background: #28a745;
			color: white;
		}

		.modal-btn-restart {
			background: #667eea;
			color: white;
		}

		.modal-btn-leave {
			background: #6c757d;
			color: white;
		}

		.modal-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.2);
		}
	</style>
</head>
<?php include("share/header.php"); ?>
<body>
	<div class="game-main">
        <!-- 返回按鈕 -->
        <div style=" margin: 0 auto; margin-top: 110px; text-align: right;">
            <button class="btn-back-fight" onclick="window.location.href='game.php'" title="返回遊戲列表">
                ← 返回遊戲列表
            </button>
        </div>
		<div class="game-header">
			<div class="game-title">🎮 方塊射擊遊戲 - 程式設計挑戰</div>
			<div class="game-stats">
				<div class="stat-item">
					<div class="stat-label">關卡</div>
					<div class="stat-value" id="levelDisplay">1</div>
				</div>
				<div class="stat-item">
					<div class="stat-label">擊殺</div>
					<div class="stat-value" id="killsDisplay">0</div>
				</div>
				<div class="stat-item">
					<div class="stat-label">分數</div>
					<div class="stat-value" id="scoreDisplay">0</div>
				</div>
			</div>
		</div>

		<div class="game-container">
			<!-- 遊戲區域 -->
			<div class="game-section">
				<div class="game-section-header">
					<div class="section-title">🎯 遊戲畫面</div>
					<div id="levelInfo" style="font-size: 14px; color: #667eea; font-weight: bold;"></div>
				</div>
				<div style="position: relative;">
					<!-- 關卡資訊顯示（載入時顯示在右側） -->
					<div id="levelInfoPanel" style="position: absolute; top: 50%; right: 1px; transform: translateY(-50%); width: 900px; height: 680px; background: rgba(255,255,255,0.95); z-index: 100; display: flex; flex-direction: column; padding: 25px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
						<h2 id="levelInfoTitle" style="color: #667eea; font-size: 22px; margin-bottom: 15px; font-weight: bold;">載入關卡中...</h2>
						<div id="levelInfoContent" style="color: #2c3e50; font-size: 40px;height: 500px; ">
							<div id="levelDescription" style="margin-bottom: 15px; color: #6c757d;"></div>
							<div id="winConditionInfo" style="background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 10px; border-left: 4px solid #667eea;">
								<div style="font-weight: bold; font-size: 30px; margin-bottom: 10px; color: #667eea;">🎯 獲勝條件</div>
								<div id="winConditionText" style="font-size: 20px; color: #2c3e50;"></div>
							</div>
						</div>
						<button id="startLevelBtn" class="btn btn-run" style="margin-top: 20px; width: 100%; display: none; font-size: 20px;" onclick="hideLevelInfo()">開始挑戰</button>
					</div>
					
					<div class="health-bar-container">
						<div class="health-fill" id="healthBar"></div>
					</div>
					<div id="timeDisplay" style="position: absolute; top: 70px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; z-index: 10; display: none;">
						時間: <span id="timeValue">0</span> 秒
					</div>
					<div id="targetProgress" style="position: absolute; bottom: 60px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; z-index: 10; display: none;">
						目標進度: <span id="progressValue">0</span>
					</div>
					<canvas id="gameCanvas" width="800" height="600"></canvas>
				</div>
			</div>

			<!-- 積木區域 -->
			<div class="blocks-section">
				<div class="blocks-header">🔧 程式設計區</div>
				
				<!-- Blockly 工具箱 -->
				<xml id="toolbox" style="display: none;">
					<category name="移動" colour="160">
						<block type="move_up"></block>
						<block type="move_down"></block>
						<block type="move_left"></block>
						<block type="move_right"></block>
						<block type="move_towards_target"></block>
						<block type="move_away_from_enemy"></block>
					</category>
					<category name="射擊" colour="230">
						<block type="shoot"></block>
						<block type="turn_to_enemy"></block>
						<block type="turn_to_target"></block>
						<block type="turn_angle">
							<value name="ANGLE">
								<shadow type="math_number">
									<field name="NUM">90</field>
								</shadow>
							</value>
						</block>
					</category>
					<category name="偵測" colour="210">
						<block type="enemy_detected"></block>
						<block type="enemy_count"></block>
						<block type="enemy_direction"></block>
						<block type="enemy_distance"></block>
						<block type="target_exists"></block>
						<block type="target_direction"></block>
						<block type="target_distance"></block>
						<block type="player_health"></block>
						<block type="player_x"></block>
						<block type="player_y"></block>
						<block type="distance_to_point">
							<value name="X">
								<shadow type="math_number">
									<field name="NUM">0</field>
								</shadow>
							</value>
							<value name="Y">
								<shadow type="math_number">
									<field name="NUM">0</field>
								</shadow>
							</value>
						</block>
					</category>
					<category name="控制" colour="120">
						<block type="controls_forever"></block>
						<block type="controls_repeat_ext">
							<value name="TIMES">
								<shadow type="math_number">
									<field name="NUM">10</field>
								</shadow>
							</value>
						</block>
						<block type="controls_if"></block>
						<block type="controls_whileUntil"></block>
					</category>
					<category name="邏輯" colour="210">
						<block type="logic_compare"></block>
						<block type="logic_operation"></block>
						<block type="logic_negate"></block>
						<block type="logic_boolean"></block>
					</category>
					<category name="數學" colour="230">
						<block type="math_number"></block>
						<block type="math_arithmetic"></block>
						<block type="math_random_int">
							<value name="FROM">
								<shadow type="math_number">
									<field name="NUM">1</field>
								</shadow>
							</value>
							<value name="TO">
								<shadow type="math_number">
									<field name="NUM">100</field>
								</shadow>
							</value>
						</block>
					</category>
				</xml>

				<div id="blocklyDiv"></div>

				<div class="control-buttons">
					<button class="btn btn-run" id="btnRun">▶️ 執行程式</button>
					<button class="btn btn-reset" id="btnReset">🔄 重置</button>
				</div>

				<div class="result-message" id="resultMessage"></div>
			</div>
		</div>
	</div>

	<!-- 成功視窗 -->
	<div class="modal" id="successModal">
		<div class="modal-content">
			<div class="modal-title success">🎉 關卡完成！</div>
			<div class="modal-message" id="successMessage">恭喜通過關卡！</div>
			<div class="modal-buttons">
				<button class="modal-btn modal-btn-next" onclick="nextLevel()">➡️ 下一關</button>
				<button class="modal-btn modal-btn-restart" onclick="restartGame()">🔄 再來一局</button>
				<button class="modal-btn modal-btn-leave" onclick="leaveGame()">🚪 離開</button>
			</div>
		</div>
	</div>

		<!-- 失敗視窗 -->
		<div class="modal" id="failureModal">
			<div class="modal-content">
				<div class="modal-title failure">❌ 遊戲結束</div>
				<div class="modal-message" id="failureMessage">你的角色被擊敗了！</div>
				<div class="modal-buttons">
					<button class="modal-btn modal-btn-restart" onclick="restartGame()">🔄 重新挑戰當前關卡</button>
					<button class="modal-btn modal-btn-restart" onclick="resetToFirstLevel()">🏠 回到第一關</button>
					<button class="modal-btn modal-btn-leave" onclick="leaveGame()">🚪 離開</button>
				</div>
			</div>
		</div>

	<script>
		// ==================== 遊戲變數 ====================
		const canvas = document.getElementById('gameCanvas');
		const ctx = canvas.getContext('2d');
		
		let gameState = 'idle'; // idle, running, paused, gameOver, levelComplete
		let level = 1;
		let score = 0;
		let kills = 0;
		let workspace = null;
		let codeRunner = null;
		let isCodeRunning = false;
		let currentLevelData = null;
		let targetPoint = null;
		let gameStartTime = 0;
		let surviveTimeRequired = 0;

		// ==================== 玩家類 ====================
		class Player {
			constructor() {
				this.x = canvas.width / 2;
				this.y = canvas.height / 2;
				this.size = 25;
				this.speed = 3;
				this.health = 100;
				this.maxHealth = 100;
				this.color = '#4a90e2';
				this.angle = 0; // 面向角度（弧度）
				this.lastShot = 0;
				this.shootCooldown = 200;
			}

			draw() {
				ctx.save();
				ctx.translate(this.x, this.y);
				ctx.rotate(this.angle);
				
				// 繪製玩家方塊
				ctx.fillStyle = this.color;
				ctx.fillRect(-this.size, -this.size, this.size * 2, this.size * 2);
				
				// 繪製邊框
				ctx.strokeStyle = '#fff';
				ctx.lineWidth = 2;
				ctx.strokeRect(-this.size, -this.size, this.size * 2, this.size * 2);
				
				// 繪製方向指示器
				ctx.fillStyle = '#ffd700';
				ctx.beginPath();
				ctx.arc(0, -this.size - 5, 5, 0, Math.PI * 2);
				ctx.fill();
				
				ctx.restore();
			}

			takeDamage(amount) {
				this.health -= amount;
				if (this.health < 0) this.health = 0;
				document.getElementById('healthBar').style.width = (this.health / this.maxHealth * 100) + '%';
				return this.health <= 0;
			}

			shoot() {
				const now = Date.now();
				if (now - this.lastShot < this.shootCooldown) return null;
				
				this.lastShot = now;
				return new Bullet(this.x, this.y, Math.cos(this.angle), Math.sin(this.angle), 'player');
			}
		}

		// ==================== 子彈類 ====================
		class Bullet {
			constructor(x, y, dx, dy, owner) {
				this.x = x;
				this.y = y;
				this.dx = dx;
				this.dy = dy;
				this.speed = 10;
				this.size = 6;
				this.owner = owner;
				this.color = owner === 'player' ? '#ffd700' : '#ff4444';
			}

			update() {
				this.x += this.dx * this.speed;
				this.y += this.dy * this.speed;
			}

			draw() {
				ctx.fillStyle = this.color;
				ctx.shadowBlur = 10;
				ctx.shadowColor = this.color;
				ctx.beginPath();
				ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
				ctx.fill();
				ctx.shadowBlur = 0;
			}

			isOutOfBounds() {
				return this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height;
			}
		}

		// ==================== 敵人類 ====================
		class Enemy {
			constructor(x, y, type = 'normal') {
				this.x = x;
				this.y = y;
				this.type = type;
				
				if (type === 'fast') {
					this.size = 20;
					this.speed = 1.5 + (level * 0.15);
					this.health = 1;
					this.maxHealth = 1;
					this.color = '#00ff88';
					this.score = 20;
				} else if (type === 'tank') {
					this.size = 35;
					this.speed = 0.8 + (level * 0.1);
					this.health = 3 + level;
					this.maxHealth = 3 + level;
					this.color = '#ff6b6b';
					this.score = 50;
				} else {
					this.size = 25;
					this.speed = 1.2 + (level * 0.12);
					this.health = 2;
					this.maxHealth = 2;
					this.color = '#ff4444';
					this.score = 30;
				}
				
				this.lastShot = 0;
				this.shootCooldown = 2000 - (level * 100);
				if (this.shootCooldown < 500) this.shootCooldown = 500;
			}

			update() {
				if (!player) return;
				const dx = player.x - this.x;
				const dy = player.y - this.y;
				const distance = Math.sqrt(dx * dx + dy * dy);
				
				if (distance > 0) {
					this.x += (dx / distance) * this.speed;
					this.y += (dy / distance) * this.speed;
				}
			}

			draw() {
				ctx.fillStyle = this.color;
				ctx.fillRect(this.x - this.size, this.y - this.size, this.size * 2, this.size * 2);
				
				ctx.strokeStyle = '#fff';
				ctx.lineWidth = 2;
				ctx.strokeRect(this.x - this.size, this.y - this.size, this.size * 2, this.size * 2);
				
				if (this.maxHealth > 1) {
					const barWidth = this.size * 2;
					const healthPercent = this.health / this.maxHealth;
					
					ctx.fillStyle = '#ff0000';
					ctx.fillRect(this.x - this.size, this.y - this.size - 8, barWidth, 4);
					ctx.fillStyle = '#00ff00';
					ctx.fillRect(this.x - this.size, this.y - this.size - 8, barWidth * healthPercent, 4);
				}
			}

			takeDamage(amount) {
				this.health -= amount;
				return this.health <= 0;
			}

			shoot() {
				if (this.type === 'fast') return null;
				
				const now = Date.now();
				if (now - this.lastShot < this.shootCooldown) return null;
				
				this.lastShot = now;
				const dx = player.x - this.x;
				const dy = player.y - this.y;
				const distance = Math.sqrt(dx * dx + dy * dy);
				
				if (distance === 0 || distance > 400) return null;
				
				return new Bullet(this.x, this.y, dx / distance, dy / distance, 'enemy');
			}
		}

		// ==================== 粒子效果 ====================
		class Particle {
			constructor(x, y, color) {
				this.x = x;
				this.y = y;
				this.vx = (Math.random() - 0.5) * 4;
				this.vy = (Math.random() - 0.5) * 4;
				this.life = 1;
				this.decay = 0.02;
				this.color = color;
				this.size = Math.random() * 3 + 2;
			}

			update() {
				this.x += this.vx;
				this.y += this.vy;
				this.life -= this.decay;
			}

			draw() {
				ctx.globalAlpha = this.life;
				ctx.fillStyle = this.color;
				ctx.beginPath();
				ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
				ctx.fill();
				ctx.globalAlpha = 1;
			}

			isDead() {
				return this.life <= 0;
			}
		}

		// ==================== 目標點類 ====================
		class TargetPoint {
			constructor(x, y, radius = 30) {
				this.x = x;
				this.y = y;
				this.radius = radius;
				this.color = '#00aaff';
				this.pulsePhase = 0;
			}

			draw() {
				ctx.save();
				
				// 脈衝動畫
				this.pulsePhase += 0.1;
				const pulseSize = this.radius + Math.sin(this.pulsePhase) * 5;
				
				// 外圈光暈
				const gradient = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, pulseSize);
				gradient.addColorStop(0, 'rgba(0, 170, 255, 0.8)');
				gradient.addColorStop(0.5, 'rgba(0, 170, 255, 0.4)');
				gradient.addColorStop(1, 'rgba(0, 170, 255, 0)');
				
				ctx.fillStyle = gradient;
				ctx.beginPath();
				ctx.arc(this.x, this.y, pulseSize, 0, Math.PI * 2);
				ctx.fill();
				
				// 目標點
				ctx.fillStyle = this.color;
				ctx.beginPath();
				ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
				ctx.fill();
				
				ctx.strokeStyle = '#fff';
				ctx.lineWidth = 3;
				ctx.stroke();
				
				// 標記
				ctx.fillStyle = '#fff';
				ctx.font = 'bold 20px Arial';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.fillText('⚑', this.x, this.y);
				
				ctx.restore();
			}

			checkCollision(player) {
				const dx = player.x - this.x;
				const dy = player.y - this.y;
				const distance = Math.sqrt(dx * dx + dy * dy);
				return distance < (this.radius + player.size);
			}
		}

		// ==================== 遊戲物件 ====================
		let player = new Player();
		let bullets = [];
		let enemies = [];
		let particles = [];

		// ==================== 載入關卡資料 ====================
		async function loadLevel(levelNumber) {
			try {
				const response = await fetch(`game_IM_api.php?action=get_level&level=${levelNumber}`);
				const data = await response.json();
				
				if (data.success) {
					currentLevelData = data.level;
					return true;
				} else {
					console.error('載入關卡失敗:', data.message);
					return false;
				}
			} catch (error) {
				console.error('載入關卡錯誤:', error);
				return false;
			}
		}

		// ==================== 生成敵人 ====================
		function spawnEnemies() {
			enemies = [];
			
			if (!currentLevelData || !currentLevelData.enemy_config) {
				// 預設生成（如果沒有關卡資料）
				const enemyCount = 5 + level * 2;
				for (let i = 0; i < enemyCount; i++) {
					spawnSingleEnemy('normal');
				}
				return;
			}
			
			const enemyConfig = currentLevelData.enemy_config;
			if (enemyConfig.enemies && Array.isArray(enemyConfig.enemies)) {
				enemyConfig.enemies.forEach(enemyGroup => {
					const count = enemyGroup.count || 0;
					const type = enemyGroup.type || 'normal';
					
					for (let i = 0; i < count; i++) {
						spawnSingleEnemy(type);
					}
				});
			}
		}

		function spawnSingleEnemy(type) {
			let x, y;
			let attempts = 0;
			
			do {
				const side = Math.floor(Math.random() * 4);
				if (side === 0) {
					x = Math.random() * canvas.width;
					y = -50;
				} else if (side === 1) {
					x = canvas.width + 50;
					y = Math.random() * canvas.height;
				} else if (side === 2) {
					x = Math.random() * canvas.width;
					y = canvas.height + 50;
				} else {
					x = -50;
					y = Math.random() * canvas.height;
				}
				attempts++;
			} while (Math.sqrt((x - player.x) ** 2 + (y - player.y) ** 2) < 200 && attempts < 10);
			
			enemies.push(new Enemy(x, y, type));
		}

		// ==================== 碰撞檢測 ====================
		function checkCollision(obj1, obj2) {
			const dx = obj1.x - obj2.x;
			const dy = obj1.y - obj2.y;
			const distance = Math.sqrt(dx * dx + dy * dy);
			return distance < (obj1.size || obj1.size) + (obj2.size || obj2.size);
		}

		// ==================== 尋找最近的敵人 ====================
		function findNearestEnemy() {
			if (enemies.length === 0) return null;
			
			let nearest = null;
			let minDist = Infinity;
			
			for (const enemy of enemies) {
				const dx = enemy.x - player.x;
				const dy = enemy.y - player.y;
				const dist = Math.sqrt(dx * dx + dy * dy);
				
				if (dist < minDist) {
					minDist = dist;
					nearest = enemy;
				}
			}
			
			return nearest;
		}

		// ==================== Blockly 初始化 ====================
		function initBlockly() {
			if (typeof Blockly === 'undefined') {
				setTimeout(initBlockly, 200);
				return;
			}
			
			defineCustomBlocks();
			
			const toolbox = document.getElementById('toolbox');
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
				theme: Blockly.Themes.Classic
			});
		}

		// ==================== 定義自定義積木 ====================
		function defineCustomBlocks() {
			// 向上移動
			Blockly.Blocks['move_up'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('向上移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('向上移動');
				}
			};
			
			Blockly.JavaScript['move_up'] = function(block) {
				return `await moveUp();\n`;
			};

			// 向下移動
			Blockly.Blocks['move_down'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('向下移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('向下移動');
				}
			};
			
			Blockly.JavaScript['move_down'] = function(block) {
				return `await moveDown();\n`;
			};

			// 向左移動
			Blockly.Blocks['move_left'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('向左移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('向左移動');
				}
			};
			
			Blockly.JavaScript['move_left'] = function(block) {
				return `await moveLeft();\n`;
			};

			// 向右移動
			Blockly.Blocks['move_right'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('向右移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('向右移動');
				}
			};
			
			Blockly.JavaScript['move_right'] = function(block) {
				return `await moveRight();\n`;
			};

			// 射擊
			Blockly.Blocks['shoot'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('射擊');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(230);
					this.setTooltip('向當前方向射擊');
				}
			};
			
			Blockly.JavaScript['shoot'] = function(block) {
				return `await shoot();\n`;
			};

			// 轉向敵人
			Blockly.Blocks['turn_to_enemy'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('轉向最近的敵人');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(230);
					this.setTooltip('轉向最近的敵人');
				}
			};
			
			Blockly.JavaScript['turn_to_enemy'] = function(block) {
				return `await turnToEnemy();\n`;
			};

			// 偵測敵人
			Blockly.Blocks['enemy_detected'] = {
				init: function() {
					this.setOutput(true, 'Boolean');
					this.appendDummyInput()
						.appendField('偵測到敵人？');
					this.setColour(210);
					this.setTooltip('檢查是否有敵人');
				}
			};
			
			Blockly.JavaScript['enemy_detected'] = function(block) {
				return ['enemyDetected()', Blockly.JavaScript.ORDER_NONE];
			};

			// 敵人方向
			Blockly.Blocks['enemy_direction'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('敵人方向（角度）');
					this.setColour(210);
					this.setTooltip('返回最近敵人的方向角度（0-360）');
				}
			};
			
			Blockly.JavaScript['enemy_direction'] = function(block) {
				return ['enemyDirection()', Blockly.JavaScript.ORDER_NONE];
			};

			// 敵人距離
			Blockly.Blocks['enemy_distance'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('敵人距離');
					this.setColour(210);
					this.setTooltip('返回最近敵人的距離');
				}
			};
			
			Blockly.JavaScript['enemy_distance'] = function(block) {
				return ['enemyDistance()', Blockly.JavaScript.ORDER_NONE];
			};

			// 玩家血量
			Blockly.Blocks['player_health'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('玩家血量');
					this.setColour(210);
					this.setTooltip('返回玩家當前血量（0-100）');
				}
			};
			
			Blockly.JavaScript['player_health'] = function(block) {
				return ['playerHealth()', Blockly.JavaScript.ORDER_NONE];
			};

			// 敵人數量
			Blockly.Blocks['enemy_count'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('敵人數量');
					this.setColour(210);
					this.setTooltip('返回當前敵人數量');
				}
			};
			
			Blockly.JavaScript['enemy_count'] = function(block) {
				return ['enemyCount()', Blockly.JavaScript.ORDER_NONE];
			};

			// 目標是否存在
			Blockly.Blocks['target_exists'] = {
				init: function() {
					this.setOutput(true, 'Boolean');
					this.appendDummyInput()
						.appendField('目標點存在？');
					this.setColour(210);
					this.setTooltip('檢查是否有目標點');
				}
			};
			
			Blockly.JavaScript['target_exists'] = function(block) {
				return ['targetExists()', Blockly.JavaScript.ORDER_NONE];
			};

			// 目標方向
			Blockly.Blocks['target_direction'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('目標方向（角度）');
					this.setColour(210);
					this.setTooltip('返回目標點的方向角度（0-360）');
				}
			};
			
			Blockly.JavaScript['target_direction'] = function(block) {
				return ['targetDirection()', Blockly.JavaScript.ORDER_NONE];
			};

			// 目標距離
			Blockly.Blocks['target_distance'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('目標距離');
					this.setColour(210);
					this.setTooltip('返回到目標點的距離');
				}
			};
			
			Blockly.JavaScript['target_distance'] = function(block) {
				return ['targetDistance()', Blockly.JavaScript.ORDER_NONE];
			};

			// 玩家X座標
			Blockly.Blocks['player_x'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('玩家X座標');
					this.setColour(210);
					this.setTooltip('返回玩家當前X座標');
				}
			};
			
			Blockly.JavaScript['player_x'] = function(block) {
				return ['playerX()', Blockly.JavaScript.ORDER_NONE];
			};

			// 玩家Y座標
			Blockly.Blocks['player_y'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendDummyInput()
						.appendField('玩家Y座標');
					this.setColour(210);
					this.setTooltip('返回玩家當前Y座標');
				}
			};
			
			Blockly.JavaScript['player_y'] = function(block) {
				return ['playerY()', Blockly.JavaScript.ORDER_NONE];
			};

			// 移動到目標
			Blockly.Blocks['move_towards_target'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('向目標移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('向目標點移動');
				}
			};
			
			Blockly.JavaScript['move_towards_target'] = function(block) {
				return `await moveTowardsTarget();\n`;
			};

			// 遠離敵人移動
			Blockly.Blocks['move_away_from_enemy'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('遠離敵人移動');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(160);
					this.setTooltip('遠離最近的敵人移動');
				}
			};
			
			Blockly.JavaScript['move_away_from_enemy'] = function(block) {
				return `await moveAwayFromEnemy();\n`;
			};

			// 轉向目標
			Blockly.Blocks['turn_to_target'] = {
				init: function() {
					this.appendDummyInput()
						.appendField('轉向目標');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(230);
					this.setTooltip('轉向目標點');
				}
			};
			
			Blockly.JavaScript['turn_to_target'] = function(block) {
				return `await turnToTarget();\n`;
			};

			// 轉向指定角度
			Blockly.Blocks['turn_angle'] = {
				init: function() {
					this.appendValueInput('ANGLE')
						.setCheck('Number')
						.appendField('轉向角度');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(230);
					this.setTooltip('轉向指定角度（0-360度）');
				}
			};
			
			Blockly.JavaScript['turn_angle'] = function(block) {
				const angle = Blockly.JavaScript.valueToCode(block, 'ANGLE', Blockly.JavaScript.ORDER_ATOMIC) || '0';
				return `await turnAngle(${angle});\n`;
			};

			// 計算到指定點的距離
			Blockly.Blocks['distance_to_point'] = {
				init: function() {
					this.setOutput(true, 'Number');
					this.appendValueInput('X')
						.setCheck('Number')
						.appendField('距離到點 (X:');
					this.appendValueInput('Y')
						.setCheck('Number')
						.appendField('Y:');
					this.appendDummyInput()
						.appendField(')');
					this.setColour(210);
					this.setTooltip('計算到指定座標點的距離');
				}
			};
			
			Blockly.JavaScript['distance_to_point'] = function(block) {
				const x = Blockly.JavaScript.valueToCode(block, 'X', Blockly.JavaScript.ORDER_ATOMIC) || '0';
				const y = Blockly.JavaScript.valueToCode(block, 'Y', Blockly.JavaScript.ORDER_ATOMIC) || '0';
				return [`distanceToPoint(${x}, ${y})`, Blockly.JavaScript.ORDER_NONE];
			};

			// 重複執行（支持無限循環）
			const originalRepeatExt = Blockly.JavaScript['controls_repeat_ext'];
			Blockly.JavaScript['controls_repeat_ext'] = function(block) {
				const times = Blockly.JavaScript.valueToCode(block, 'TIMES', Blockly.JavaScript.ORDER_ASSIGNMENT);
				const branch = Blockly.JavaScript.statementToCode(block, 'DO');
				
				// 如果次數是 Infinity 或非常大的數字，使用 while 循環
				if (!times || times === 'Infinity' || parseFloat(times) > 10000) {
					return `while (getGameState() === 'running') {\n${branch}await sleep(50);\n}\n`;
				}
				
				return `for (let i = 0; i < ${times}; i++) {\n${branch}await sleep(50);\n}\n`;
			};

			// 持續執行積木
			Blockly.Blocks['controls_forever'] = {
				init: function() {
					this.appendStatementInput('DO')
						.appendField('持續執行');
					this.setPreviousStatement(true, null);
					this.setNextStatement(true, null);
					this.setColour(120);
					this.setTooltip('持續執行區塊內的程式，直到遊戲結束');
				}
			};
			
			Blockly.JavaScript['controls_forever'] = function(block) {
				const branch = Blockly.JavaScript.statementToCode(block, 'DO');
				return `while (getGameState() === 'running') {\n${branch}await sleep(50);\n}\n`;
			};

			// 如果積木支持異步
			const originalIf = Blockly.JavaScript['controls_if'];
			Blockly.JavaScript['controls_if'] = function(block) {
				const n = 0;
				const argument0 = Blockly.JavaScript.valueToCode(block, 'IF' + n, Blockly.JavaScript.ORDER_NONE) || 'false';
				const branch0 = Blockly.JavaScript.statementToCode(block, 'DO' + n);
				
				let code = `if (${argument0}) {\n${branch0}}\n`;
				
				if (block.elseifCount_) {
					for (let i = 1; i <= block.elseifCount_; i++) {
						const argument = Blockly.JavaScript.valueToCode(block, 'IF' + i, Blockly.JavaScript.ORDER_NONE) || 'false';
						const branch = Blockly.JavaScript.statementToCode(block, 'DO' + i);
						code += `else if (${argument}) {\n${branch}}\n`;
					}
				}
				
				if (block.elseCount_) {
					const branch = Blockly.JavaScript.statementToCode(block, 'ELSE');
					code += `else {\n${branch}}\n`;
				}
				
				return code;
			};
		}

		// ==================== 遊戲函數（供 Blockly 調用） ====================
		function sleep(ms) {
			return new Promise(resolve => setTimeout(resolve, ms));
		}

		async function moveUp() {
			if (gameState !== 'running') return;
			player.y = Math.max(player.size, player.y - player.speed);
			player.angle = -Math.PI / 2;
			await sleep(50);
		}

		async function moveDown() {
			if (gameState !== 'running') return;
			player.y = Math.min(canvas.height - player.size, player.y + player.speed);
			player.angle = Math.PI / 2;
			await sleep(50);
		}

		async function moveLeft() {
			if (gameState !== 'running') return;
			player.x = Math.max(player.size, player.x - player.speed);
			player.angle = Math.PI;
			await sleep(50);
		}

		async function moveRight() {
			if (gameState !== 'running') return;
			player.x = Math.min(canvas.width - player.size, player.x + player.speed);
			player.angle = 0;
			await sleep(50);
		}

		async function shoot() {
			if (gameState !== 'running') return;
			const bullet = player.shoot();
			if (bullet) {
				bullets.push(bullet);
			}
			await sleep(50);
		}

		async function turnToEnemy() {
			if (gameState !== 'running') return;
			const nearest = findNearestEnemy();
			if (nearest) {
				const dx = nearest.x - player.x;
				const dy = nearest.y - player.y;
				player.angle = Math.atan2(dy, dx);
			}
			await sleep(50);
		}

		function enemyDetected() {
			return enemies.length > 0;
		}

		function enemyDirection() {
			const nearest = findNearestEnemy();
			if (!nearest) return 0;
			const dx = nearest.x - player.x;
			const dy = nearest.y - player.y;
			let angle = Math.atan2(dy, dx) * (180 / Math.PI);
			if (angle < 0) angle += 360;
			return angle;
		}

		function enemyDistance() {
			const nearest = findNearestEnemy();
			if (!nearest) return Infinity;
			const dx = nearest.x - player.x;
			const dy = nearest.y - player.y;
			return Math.sqrt(dx * dx + dy * dy);
		}

		function playerHealth() {
			return player.health;
		}

		function getGameState() {
			return gameState;
		}

		function enemyCount() {
			return enemies.length;
		}

		function targetExists() {
			return targetPoint !== null;
		}

		function targetDirection() {
			if (!targetPoint) return 0;
			const dx = targetPoint.x - player.x;
			const dy = targetPoint.y - player.y;
			let angle = Math.atan2(dy, dx) * (180 / Math.PI);
			if (angle < 0) angle += 360;
			return angle;
		}

		function targetDistance() {
			if (!targetPoint) return Infinity;
			const dx = targetPoint.x - player.x;
			const dy = targetPoint.y - player.y;
			return Math.sqrt(dx * dx + dy * dy);
		}

		function playerX() {
			return player.x;
		}

		function playerY() {
			return player.y;
		}

		async function moveTowardsTarget() {
			if (gameState !== 'running' || !targetPoint) return;
			const dx = targetPoint.x - player.x;
			const dy = targetPoint.y - player.y;
			const distance = Math.sqrt(dx * dx + dy * dy);
			
			if (distance > 0) {
				player.x += (dx / distance) * player.speed;
				player.y += (dy / distance) * player.speed;
				player.x = Math.max(player.size, Math.min(canvas.width - player.size, player.x));
				player.y = Math.max(player.size, Math.min(canvas.height - player.size, player.y));
				player.angle = Math.atan2(dy, dx);
			}
			await sleep(50);
		}

		async function moveAwayFromEnemy() {
			if (gameState !== 'running') return;
			const nearest = findNearestEnemy();
			if (nearest) {
				const dx = player.x - nearest.x;
				const dy = player.y - nearest.y;
				const distance = Math.sqrt(dx * dx + dy * dy);
				
				if (distance > 0) {
					player.x += (dx / distance) * player.speed;
					player.y += (dy / distance) * player.speed;
					player.x = Math.max(player.size, Math.min(canvas.width - player.size, player.x));
					player.y = Math.max(player.size, Math.min(canvas.height - player.size, player.y));
					player.angle = Math.atan2(-dy, -dx);
				}
			}
			await sleep(50);
		}

		async function turnToTarget() {
			if (gameState !== 'running' || !targetPoint) return;
			const dx = targetPoint.x - player.x;
			const dy = targetPoint.y - player.y;
			player.angle = Math.atan2(dy, dx);
			await sleep(50);
		}

		async function turnAngle(angleDegrees) {
			if (gameState !== 'running') return;
			player.angle = (angleDegrees * Math.PI) / 180;
			await sleep(50);
		}

		function distanceToPoint(x, y) {
			const dx = x - player.x;
			const dy = y - player.y;
			return Math.sqrt(dx * dx + dy * dy);
		}

		// ==================== 初始化關卡 ====================
		async function initLevel() {
			const loaded = await loadLevel(level);
			if (!loaded) {
				showResult('載入關卡失敗！', 'error');
				return false;
			}
			
			// 設置玩家起始位置
			if (currentLevelData.player_start_x !== undefined) {
				player.x = currentLevelData.player_start_x;
			}
			if (currentLevelData.player_start_y !== undefined) {
				player.y = currentLevelData.player_start_y;
			}
			if (currentLevelData.player_health !== undefined) {
				player.health = currentLevelData.player_health;
				player.maxHealth = currentLevelData.player_health;
			}
			
			// 設置目標點
			targetPoint = null;
			if (currentLevelData.win_condition && currentLevelData.win_condition.type === 'reach_target') {
				const cond = currentLevelData.win_condition;
				targetPoint = new TargetPoint(cond.target_x, cond.target_y, cond.target_radius || 30);
			} else if (currentLevelData.win_condition && currentLevelData.win_condition.type === 'hybrid') {
				// 混合任務可能有目標點
				const conditions = currentLevelData.win_condition.conditions || [];
				const targetCond = conditions.find(c => c.type === 'reach_target');
				if (targetCond) {
					targetPoint = new TargetPoint(targetCond.target_x, targetCond.target_y, targetCond.target_radius || 30);
				}
			}
			
			// 設置生存時間要求
			if (currentLevelData.win_condition && currentLevelData.win_condition.type === 'survive_time') {
				surviveTimeRequired = currentLevelData.win_condition.survive_seconds || 0;
			} else if (currentLevelData.win_condition && currentLevelData.win_condition.type === 'hybrid') {
				const conditions = currentLevelData.win_condition.conditions || [];
				const surviveCond = conditions.find(c => c.type === 'survive_time');
				if (surviveCond) {
					surviveTimeRequired = surviveCond.survive_seconds || 0;
				}
			} else {
				surviveTimeRequired = 0;
			}
			
			// 顯示關卡資訊
			if (currentLevelData.level_name) {
				document.getElementById('levelInfo').textContent = `${currentLevelData.level_name} - ${currentLevelData.level_description || ''}`;
			}
			
			// 顯示/隱藏UI元素
			document.getElementById('timeDisplay').style.display = surviveTimeRequired > 0 ? 'block' : 'none';
			
			// 顯示關卡資訊面板
			showLevelInfo();
			
			return true;
		}

		// ==================== 顯示關卡資訊 ====================
		function showLevelInfo() {
			if (!currentLevelData) return;
			
			const panel = document.getElementById('levelInfoPanel');
			const title = document.getElementById('levelInfoTitle');
			const description = document.getElementById('levelDescription');
			const winConditionText = document.getElementById('winConditionText');
			const startBtn = document.getElementById('startLevelBtn');
			
			// 設置標題
			title.textContent = `第 ${level} 關：${currentLevelData.level_name || '未知關卡'}`;
			
			// 設置描述
			if (currentLevelData.level_description) {
				description.textContent = currentLevelData.level_description;
				description.style.display = 'block';
			} else {
				description.style.display = 'none';
			}
			
			// 設置獲勝條件
			let conditionText = '';
			if (currentLevelData.win_condition) {
				const winCond = currentLevelData.win_condition;
				
				switch (winCond.type) {
					case 'eliminate_all':
						if (winCond.min_kills) {
							conditionText = `擊殺至少 ${winCond.min_kills} 個敵人`;
						} else {
							conditionText = '消滅所有敵人';
						}
						break;
						
					case 'reach_target':
						conditionText = `到達藍色目標點（位置：X=${winCond.target_x}, Y=${winCond.target_y}）`;
						break;
						
					case 'survive_time':
						conditionText = `在敵人攻擊下生存 ${winCond.survive_seconds} 秒`;
						break;
						
					case 'hybrid':
						const conditions = winCond.conditions || [];
						const conditionList = [];
						
						conditions.forEach(cond => {
							switch (cond.type) {
								case 'eliminate_all':
									if (cond.min_kills) {
										conditionList.push(`擊殺至少 ${cond.min_kills} 個敵人`);
									} else {
										conditionList.push('消滅所有敵人');
									}
									break;
								case 'min_kills':
									conditionList.push(`擊殺至少 ${cond.count} 個敵人`);
									break;
								case 'reach_target':
									conditionList.push(`到達藍色目標點`);
									break;
								case 'survive_time':
									conditionList.push(`生存 ${cond.survive_seconds} 秒`);
									break;
							}
						});
						
						conditionText = conditionList.join(' 且 ');
						break;
						
					default:
						conditionText = '消滅所有敵人';
				}
			} else {
				conditionText = '消滅所有敵人';
			}
			
			winConditionText.textContent = conditionText;
			
			// 顯示面板
			panel.style.display = 'flex';
			startBtn.style.display = 'block';
		}

		// ==================== 隱藏關卡資訊 ====================
		function hideLevelInfo() {
			document.getElementById('levelInfoPanel').style.display = 'none';
			// 立即繪製一次遊戲畫面，顯示玩家角色
			if (player) {
				drawGameFrame();
			}
		}
		
		// ==================== 繪製遊戲畫面（單次）====================
		function drawGameFrame() {
			// 使用全局的 canvas 和 ctx
			
			// 清除畫布
			ctx.fillStyle = '#1a1a2e';
			ctx.fillRect(0, 0, canvas.width, canvas.height);
			
			// 繪製網格
			ctx.strokeStyle = 'rgba(255,255,255,0.05)';
			ctx.lineWidth = 1;
			for (let i = 0; i < canvas.width; i += 50) {
				ctx.beginPath();
				ctx.moveTo(i, 0);
				ctx.lineTo(i, canvas.height);
				ctx.stroke();
			}
			for (let i = 0; i < canvas.height; i += 50) {
				ctx.beginPath();
				ctx.moveTo(0, i);
				ctx.lineTo(canvas.width, i);
				ctx.stroke();
			}
			
			// 繪製目標點（如果有）
			if (targetPoint) {
				targetPoint.draw();
			}
			
			// 繪製敵人（如果有）
			enemies.forEach(enemy => {
				enemy.draw();
			});
			
			// 繪製玩家
			if (player) {
				player.draw();
			}
		}

		// ==================== 執行程式 ====================
		async function runCode() {
			if (isCodeRunning || !workspace) return;
			
			const topBlocks = workspace.getTopBlocks(true);
			if (topBlocks.length === 0) {
				showResult('請先添加積木！', 'error');
				return;
			}

			// 確保關卡已載入
			if (!currentLevelData) {
				const levelLoaded = await initLevel();
				if (!levelLoaded) return;
			}

			// 重置遊戲
			player = new Player();
			bullets = [];
			particles = [];
			
			// 從關卡資料初始化玩家
			if (currentLevelData.player_start_x !== undefined) {
				player.x = currentLevelData.player_start_x;
			}
			if (currentLevelData.player_start_y !== undefined) {
				player.y = currentLevelData.player_start_y;
			}
			if (currentLevelData.player_health !== undefined) {
				player.health = currentLevelData.player_health;
				player.maxHealth = currentLevelData.player_health;
			}
			
			// 更新血條
			document.getElementById('healthBar').style.width = (player.health / player.maxHealth * 100) + '%';
			
			spawnEnemies();
			updateUI();
			
			// 隱藏關卡資訊面板，顯示遊戲畫面（會自動繪製玩家角色）
			hideLevelInfo();

			isCodeRunning = true;
			gameState = 'running';
			document.getElementById('btnRun').disabled = true;
			gameStartTime = Date.now();
			
			try {
				const code = Blockly.JavaScript.workspaceToCode(workspace);
				console.log('生成的代碼:', code);
				
				const asyncFunction = new Function(
					'moveUp', 'moveDown', 'moveLeft', 'moveRight',
					'shoot', 'turnToEnemy', 'turnToTarget', 'turnAngle',
					'enemyDetected', 'enemyCount', 'enemyDirection', 'enemyDistance', 
					'targetExists', 'targetDirection', 'targetDistance',
					'playerHealth', 'playerX', 'playerY', 'distanceToPoint',
					'moveTowardsTarget', 'moveAwayFromEnemy',
					'sleep', 'getGameState',
					`return (async function() { 
						try {
							${code}
						} catch (e) {
							console.error('程式執行錯誤:', e);
							throw e;
						}
					})();`
				);
				
				// 開始遊戲循環
				gameLoop();
				
				// 執行用戶代碼（在背景執行）
				codeRunner = asyncFunction(
					moveUp, moveDown, moveLeft, moveRight,
					shoot, turnToEnemy, turnToTarget, turnAngle,
					enemyDetected, enemyCount, enemyDirection, enemyDistance,
					targetExists, targetDirection, targetDistance,
					playerHealth, playerX, playerY, distanceToPoint,
					moveTowardsTarget, moveAwayFromEnemy,
					sleep, getGameState
				);
				
				await codeRunner;
				
			} catch (error) {
				console.error('執行錯誤:', error);
				showResult('執行程式時發生錯誤：' + error.message, 'error');
				gameState = 'idle';
				isCodeRunning = false;
				document.getElementById('btnRun').disabled = false;
			}
		}

		// ==================== 遊戲主循環 ====================
		function gameLoop() {
			if (gameState !== 'running') return;
			
			// 清除畫布
			ctx.fillStyle = '#1a1a2e';
			ctx.fillRect(0, 0, canvas.width, canvas.height);
			
			// 繪製網格
			ctx.strokeStyle = 'rgba(255,255,255,0.05)';
			ctx.lineWidth = 1;
			for (let i = 0; i < canvas.width; i += 50) {
				ctx.beginPath();
				ctx.moveTo(i, 0);
				ctx.lineTo(i, canvas.height);
				ctx.stroke();
			}
			for (let i = 0; i < canvas.height; i += 50) {
				ctx.beginPath();
				ctx.moveTo(0, i);
				ctx.lineTo(canvas.width, i);
				ctx.stroke();
			}
			
			// 更新子彈
			for (let i = bullets.length - 1; i >= 0; i--) {
				bullets[i].update();
				
				if (bullets[i].isOutOfBounds()) {
					bullets.splice(i, 1);
					continue;
				}
				
				bullets[i].draw();
				
				// 玩家子彈與敵人碰撞
				if (bullets[i].owner === 'player') {
					for (let j = enemies.length - 1; j >= 0; j--) {
						if (checkCollision(bullets[i], enemies[j])) {
							for (let k = 0; k < 5; k++) {
								particles.push(new Particle(enemies[j].x, enemies[j].y, enemies[j].color));
							}
							
							if (enemies[j].takeDamage(1)) {
								score += enemies[j].score;
								kills++;
								enemies.splice(j, 1);
								updateUI();
							}
							
							bullets.splice(i, 1);
							break;
						}
					}
				}
				// 敵人子彈與玩家碰撞
				else if (bullets[i].owner === 'enemy') {
					if (checkCollision(bullets[i], player)) {
						if (player.takeDamage(10)) {
							gameOver();
							return;
						}
						bullets.splice(i, 1);
					}
				}
			}
			
			// 更新敵人
			for (let i = enemies.length - 1; i >= 0; i--) {
				enemies[i].update();
				enemies[i].draw();
				
				const enemyBullet = enemies[i].shoot();
				if (enemyBullet) {
					bullets.push(enemyBullet);
				}
				
				// 敵人與玩家碰撞
				if (checkCollision(enemies[i], player)) {
					if (player.takeDamage(20)) {
						gameOver();
						return;
					}
					
					const dx = player.x - enemies[i].x;
					const dy = player.y - enemies[i].y;
					const distance = Math.sqrt(dx * dx + dy * dy);
					if (distance > 0) {
						player.x += (dx / distance) * 10;
						player.y += (dy / distance) * 10;
						player.x = Math.max(player.size, Math.min(canvas.width - player.size, player.x));
						player.y = Math.max(player.size, Math.min(canvas.height - player.size, player.y));
					}
					
					for (let k = 0; k < 8; k++) {
						particles.push(new Particle(enemies[i].x, enemies[i].y, '#ff0000'));
					}
				}
			}
			
			// 更新粒子
			for (let i = particles.length - 1; i >= 0; i--) {
				particles[i].update();
				if (particles[i].isDead()) {
					particles.splice(i, 1);
				} else {
					particles[i].draw();
				}
			}
			
			// 繪製目標點
			if (targetPoint) {
				targetPoint.draw();
			}
			
			// 繪製玩家
			player.draw();
			
			// 更新時間顯示
			if (surviveTimeRequired > 0) {
				const elapsed = (Date.now() - gameStartTime) / 1000;
				document.getElementById('timeValue').textContent = Math.floor(elapsed);
			}
			
			// 檢查勝利條件
			if (checkWinCondition()) {
				gameState = 'levelComplete';
				isCodeRunning = false;
				document.getElementById('btnRun').disabled = false;
				setTimeout(() => {
					let message = `恭喜通過第 ${level} 關！\n`;
					if (kills > 0) message += `擊殺了 ${kills} 個敵人，`;
					message += `得分 ${score}！`;
					document.getElementById('successMessage').textContent = message;
					document.getElementById('successModal').classList.add('show');
				}, 500);
				return;
			}
			
			requestAnimationFrame(gameLoop);
		}

		// ==================== 檢查勝利條件 ====================
		function checkWinCondition() {
			if (!currentLevelData || !currentLevelData.win_condition) {
				// 預設：消滅所有敵人
				return enemies.length === 0;
			}
			
			const winCond = currentLevelData.win_condition;
			
			switch (winCond.type) {
				case 'eliminate_all':
					// 消滅所有敵人
					if (winCond.min_kills) {
						return kills >= winCond.min_kills;
					}
					return enemies.length === 0;
					
				case 'reach_target':
					// 到達目標點
					if (targetPoint && targetPoint.checkCollision(player)) {
						return true;
					}
					return false;
					
				case 'survive_time':
					// 生存指定時間
					const elapsed = (Date.now() - gameStartTime) / 1000;
					return elapsed >= surviveTimeRequired;
					
				case 'hybrid':
					// 混合條件：需要滿足所有條件
					const conditions = winCond.conditions || [];
					for (const cond of conditions) {
						switch (cond.type) {
							case 'eliminate_all':
								if (enemies.length > 0 && !cond.min_kills) return false;
								if (cond.min_kills && kills < cond.min_kills) return false;
								break;
							case 'min_kills':
								if (kills < cond.count) return false;
								break;
							case 'reach_target':
								if (!targetPoint || !targetPoint.checkCollision(player)) return false;
								break;
							case 'survive_time':
								const elapsed = (Date.now() - gameStartTime) / 1000;
								if (elapsed < cond.survive_seconds) return false;
								break;
						}
					}
					return true;
					
				default:
					return enemies.length === 0;
			}
		}

		// ==================== UI 更新 ====================
		function updateUI() {
			document.getElementById('levelDisplay').textContent = level;
			document.getElementById('killsDisplay').textContent = kills;
			document.getElementById('scoreDisplay').textContent = score;
		}

		function showResult(message, type) {
			const resultMsg = document.getElementById('resultMessage');
			resultMsg.textContent = message;
			resultMsg.className = `result-message ${type}`;
		}

		function gameOver() {
			gameState = 'gameOver';
			isCodeRunning = false;
			document.getElementById('btnRun').disabled = false;
			document.getElementById('failureMessage').textContent = `你在第 ${level} 關被擊敗了！\n擊殺了 ${kills} 個敵人，得分 ${score}！\n\n可以重新挑戰當前關卡！`;
			
			// 確保停止遊戲循環
			if (codeRunner) {
				codeRunner = null;
			}
			
			// 顯示失敗視窗
			const failureModal = document.getElementById('failureModal');
			failureModal.classList.add('show');
			
			// 確保按鈕可以點擊（移除可能的禁用狀態）
			const buttons = failureModal.querySelectorAll('button');
			buttons.forEach(btn => {
				btn.disabled = false;
				btn.style.pointerEvents = 'auto';
			});
		}

		async function nextLevel() {
			level++;
			score = 0;
			kills = 0;
			document.getElementById('healthBar').style.width = '100%';
			document.getElementById('successModal').classList.remove('show');
			isCodeRunning = false;
			gameState = 'idle';
			document.getElementById('btnRun').disabled = false;
			
			// 載入新關卡
			await initLevel();
			updateUI();
		}

		function restartGame() {
			// 重新挑戰當前關卡，不回到第一關
			score = 0;
			kills = 0;
			bullets = [];
			particles = [];
			gameState = 'idle';
			isCodeRunning = false;
			document.getElementById('btnRun').disabled = false;
			document.getElementById('successModal').classList.remove('show');
			document.getElementById('failureModal').classList.remove('show');
			
			// 重新初始化玩家
			player = new Player();
			if (currentLevelData) {
				if (currentLevelData.player_start_x !== undefined) {
					player.x = currentLevelData.player_start_x;
				}
				if (currentLevelData.player_start_y !== undefined) {
					player.y = currentLevelData.player_start_y;
				}
				if (currentLevelData.player_health !== undefined) {
					player.health = currentLevelData.player_health;
					player.maxHealth = currentLevelData.player_health;
					document.getElementById('healthBar').style.width = '100%';
				}
			}
			
			// 重新生成敵人
			spawnEnemies();
			
			// 重新繪製遊戲畫面
			drawGameFrame();
			
			// 重新顯示關卡資訊
			showLevelInfo();
			updateUI();
		}
		
		async function resetToFirstLevel() {
			// 重置到第一關（可選功能）
			level = 1;
			score = 0;
			kills = 0;
			bullets = [];
			enemies = [];
			particles = [];
			gameState = 'idle';
			isCodeRunning = false;
			document.getElementById('btnRun').disabled = false;
			document.getElementById('successModal').classList.remove('show');
			document.getElementById('failureModal').classList.remove('show');
			
			// 重新載入第一關
			await initLevel();
			
			// 初始化玩家
			player = new Player();
			if (currentLevelData) {
				if (currentLevelData.player_start_x !== undefined) {
					player.x = currentLevelData.player_start_x;
				}
				if (currentLevelData.player_start_y !== undefined) {
					player.y = currentLevelData.player_start_y;
				}
				if (currentLevelData.player_health !== undefined) {
					player.health = currentLevelData.player_health;
					player.maxHealth = currentLevelData.player_health;
					document.getElementById('healthBar').style.width = '100%';
				}
			}
			
			// 生成敵人並繪製
			spawnEnemies();
			drawGameFrame();
			
			showLevelInfo();
			updateUI();
		}

		function leaveGame() {
			window.location.href = 'game.php';
		}

		// ==================== 事件監聽 ====================
		document.getElementById('btnRun').addEventListener('click', runCode);
		document.getElementById('btnReset').addEventListener('click', () => {
			if (workspace) {
				workspace.clear();
			}
			restartGame();
		});

		// ==================== 初始化 ====================
		async function initialize() {
			initBlockly();
			await initLevel();
			updateUI();
			
			// 初始化玩家並繪製一次
			if (currentLevelData) {
				if (currentLevelData.player_start_x !== undefined) {
					player.x = currentLevelData.player_start_x;
				}
				if (currentLevelData.player_start_y !== undefined) {
					player.y = currentLevelData.player_start_y;
				}
				if (currentLevelData.player_health !== undefined) {
					player.health = currentLevelData.player_health;
					player.maxHealth = currentLevelData.player_health;
					document.getElementById('healthBar').style.width = '100%';
				}
				
				// 生成敵人但先不繪製（等按下開始後才開始遊戲）
				spawnEnemies();
				
				// 繪製初始畫面
				drawGameFrame();
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initialize);
		} else {
			setTimeout(initialize, 300);
		}
	</script>
<?php include("share/footer.php"); ?>
</body>
</html>
