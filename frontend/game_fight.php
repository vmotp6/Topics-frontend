<?php
require_once 'session_config.php';
require_once 'config.php';

function getGameQuestions($limit = 10) {
    try {
        $conn = getDatabaseConnection();
        
        $sql = "
        SELECT question, option_a, option_b, option_c, option_d, correct_option
        FROM game_questions
        WHERE is_active = 1 AND category = 'fight'
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
                'a' => strtoupper($row['correct_option']), // A/B/C/D
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
        
        // 如果沒有足夠的題目，返回空陣列
        return $questions;
    } catch (Exception $e) {
        error_log("獲取遊戲題目錯誤: " . $e->getMessage());
        return [];
    }
}

$questions = getGameQuestions(10);
// 如果沒有題目，使用示例題目
if (empty($questions)) {
    $questions = [
        ['q' => '康寧大學位於哪個縣市？', 'a' => 'A', 'o' => ['A' => '台北市', 'B' => '新北市', 'C' => '桃園市', 'D' => '新竹市']],
        ['q' => '五專學制通常需要幾年？', 'a' => 'C', 'o' => ['A' => '3年', 'B' => '4年', 'C' => '5年', 'D' => '6年']],
        ['q' => '以下哪個不是康寧大學的主要科系？', 'a' => 'D', 'o' => ['A' => '護理', 'B' => '資訊', 'C' => '商管', 'D' => '醫學']],
    ];
    // 補足到10題
    while (count($questions) < 10) {
        $questions[] = $questions[count($questions) % 3];
    }
}

// GIF 路徑處理
$gif_path = 'http://localhost/game/fight01.gif';
// 如果本地文件存在，也可以使用相對路徑
if (file_exists('C:/Topics/game/fight01.gif')) {
    // 使用絕對 URL 路徑
    $gif_path = 'http://localhost/game/fight01.gif';
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
	<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>招生格鬥遊戲 - 康寧大學</title>

<style>
* {
    box-sizing: border-box;
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

body {
    background: #f8f9fa;
    color: #2c3e50;
    font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
    margin: 0;
    padding: 0;
    padding-top: 100px; /* 為 header 預留空間 */
    min-height: 100vh;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}

/* 確保 main 標籤撐開內容 */
.game-main {
    flex: 1;
    width: 100%;
}

@media (max-width: 768px) {
    body {
        padding-top: 120px;
    }
}

@media (max-width: 480px) {
    body {
        padding-top: 130px;
    }
}

@media (max-width: 480px) {
    body {
        padding-top: 130px;
    }
}

/* ====== 血量顯示區 ====== */
.hp-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    background: #ffffff;
    border-bottom: 3px solid #667eea;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border-radius: 10px 10px 0 0;
}

@media (max-width: 768px) {
    .hp-container {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
    }
    
    .hp-info {
        width: 100% !important;
    }
}

.hp-info {
    width: 45%;
}

.hp-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 18px;
    font-weight: bold;
}

.hp-number {
    font-size: 24px;
    color: #667eea;
    font-weight: bold;
}

.hp-bar-container {
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    overflow: hidden;
    border: 2px solid #dee2e6;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.hp-bar {
    height: 100%;
    background: #63c51b;
    width: 100%;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.hp-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shine 2s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.hp-bar.danger {
    background: linear-gradient(90deg, #e74c3c 0%, #c0392b 100%);
}

/* ====== 對戰區域 ====== */
.fight-area-wrapper {
    margin: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.fight-area {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    min-height: 350px;
    padding: 30px 80px;
    position: relative;
    background: #ffffff;
}

@media (max-width: 768px) {
    .fight-area-wrapper {
        margin: 10px;
    }
    
    .hp-container {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
    }
    
    .hp-info {
        width: 100% !important;
    }
    
    .fight-area {
        padding: 20px;
        flex-direction: column;
        align-items: center;
        gap: 30px;
        min-height: auto;
    }
    
    .character {
        width: 100%;
        max-width: 280px;
    }
}

/* ====== 角色容器 ====== */
.character {
    width: 280px;
    text-align: center;
    position: relative;
    z-index: 2;
}

.character-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
}

.character img {
    width: 100%;
    max-width: 250px;
    height: auto;
    image-rendering: auto;
    border-radius: 10px;
    background: #f8f9fa;
    padding: 10px;
}

.player-character {
    width: 200px;
    height: 300px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    border: 3px solid #ffffff;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    position: relative;
}

.player-character::before {
    content: '👤';
    font-size: 80px;
}

/* ====== 題目區域 ====== */
.quiz-box {
    background: #ffffff;
    padding: 30px;
    margin: 0 20px 20px 20px;
    border-top: 4px solid #667eea;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 60px;
}

@media (max-width: 768px) {
    .quiz-box {
        margin: 0 10px 20px 10px;
        padding: 20px;
    }
}

.quiz-box h2 {
    margin: 0 0 20px 0;
    font-size: 22px;
    color: #2c3e50;
    line-height: 1.6;
    font-weight: 600;
}

.options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.options button {
    padding: 15px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    color: #2c3e50;
    font-size: 16px;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s;
    text-align: left;
    position: relative;
    overflow: hidden;
}

.options button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.options button:hover {
    background: #e9ecef;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.options button:hover::before {
    width: 300px;
    height: 300px;
}

.options button:active {
    transform: translateY(0);
}

.options button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ====== 攻擊文字動畫 ====== */
.attack-text {
    position: fixed;
    font-size: 36px;
    font-weight: bold;
    z-index: 1000;
    pointer-events: none;
    text-shadow: 3px 3px 6px rgba(0,0,0,0.8);
}

.attack-text.user-attack {
    color: #4ade80;
    animation: flyToEnemy 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.attack-text.enemy-attack {
    color: #ff6b6b;
    animation: flyToUser 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes flyToEnemy {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-400px, -50px) scale(1.3);
        opacity: 1;
    }
    100% {
        transform: translate(-600px, -100px) scale(0.5);
        opacity: 0;
    }
}

@keyframes flyToUser {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(400px, -50px) scale(1.3);
        opacity: 1;
    }
    100% {
        transform: translate(600px, -100px) scale(0.5);
        opacity: 0;
    }
}

/* ====== 受擊動畫 ====== */
.character.hit {
    animation: hitShake 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes hitShake {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    10% { transform: translate(-15px, -10px) rotate(-5deg); }
    20% { transform: translate(15px, 10px) rotate(5deg); }
    30% { transform: translate(-10px, -5px) rotate(-3deg); }
    40% { transform: translate(10px, 5px) rotate(3deg); }
    50% { transform: translate(-5px, -2px) rotate(-1deg); }
    60% { transform: translate(5px, 2px) rotate(1deg); }
    70% { transform: translate(-3px, -1px); }
    80% { transform: translate(3px, 1px); }
    90% { transform: translate(-1px, 0); }
}

.character.attack {
    animation: attackMove 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes attackMove {
    0%, 100% { transform: translateX(0); }
    30% { transform: translateX(30px); }
    60% { transform: translateX(0); }
}

/* ====== 傷害數字 ====== */
.damage-number {
    position: fixed;
    font-size: 48px;
    font-weight: bold;
    color: #ff4444;
    z-index: 1001;
    pointer-events: none;
    text-shadow: 0 0 10px rgba(255, 68, 68, 0.8);
    animation: damageFloat 1s ease-out forwards;
}

@keyframes damageFloat {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    50% {
        transform: translateY(-80px) scale(1.5);
        opacity: 1;
    }
    100% {
        transform: translateY(-120px) scale(0.8);
        opacity: 0;
    }
}

/* ====== 結果畫面 ====== */
.result {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.95);
    display: none;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    z-index: 10000;
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.result-content {
    text-align: center;
    animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes scaleIn {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.result-title {
    font-size: 72px;
    font-weight: bold;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(255,255,255,0.5);
}

.result-title.victory {
    color: #4ade80;
    animation: victoryPulse 1s infinite;
}

.result-title.defeat {
    color: #ff6b6b;
    animation: defeatPulse 1s infinite;
}

.result-title.draw {
    color: #fbbf24;
}

@keyframes victoryPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes defeatPulse {
    0%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.05) rotate(-2deg); }
    75% { transform: scale(1.05) rotate(2deg); }
}

.result-stats {
    font-size: 24px;
    margin: 20px 0;
    color: #ccc;
}

.result-button {
    margin-top: 30px;
    padding: 15px 40px;
    font-size: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: #fff;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.result-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

.result-button:active {
    transform: translateY(0);
}

/* ====== 答錯時的錯誤答案顯示 ====== */
.wrong-answer-display {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 48px;
    font-weight: bold;
    color: #ff6b6b;
    z-index: 999;
    pointer-events: none;
    text-shadow: 0 0 20px rgba(255, 107, 107, 0.8);
    animation: wrongAnswerShow 1.2s ease-out forwards;
}

@keyframes wrongAnswerShow {
    0% {
        transform: translate(-50%, -50%) scale(0.5);
        opacity: 0;
    }
    20% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 1;
    }
    50% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
    }
}

/* ====== 答對時的正確答案顯示 ====== */
.correct-answer-display {
    position: fixed;
    top: 50%;
    right: 30%;
    font-size: 48px;
    font-weight: bold;
    color: #4ade80;
    z-index: 999;
    pointer-events: none;
    text-shadow: 0 0 20px rgba(74, 222, 128, 0.8);
    animation: correctAnswerShow 0.8s ease-out forwards;
}

@keyframes correctAnswerShow {
    0% {
        transform: translate(0, 0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-600px, -100px) scale(0.5);
        opacity: 0;
    }
}

/* ====== 答題按鈕正確/錯誤狀態 ====== */
.options button.correct {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    border-color: #16a34a;
    animation: correctPulse 0.5s;
}

.options button.wrong {
    background: linear-gradient(135deg, #ff6b6b 0%, #ef4444 100%);
    border-color: #dc2626;
    animation: wrongPulse 0.5s;
}

@keyframes correctPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes wrongPulse {
    0%, 100% { transform: scale(1); }
    25% { transform: scale(0.95) translateX(-5px); }
    75% { transform: scale(0.95) translateX(5px); }
}

/* ====== 開場加載畫面 ====== */
.loading-screen {
    position: fixed;
    inset: 0;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 10001;
    animation: fadeOut 0.5s ease-out 3.5s forwards;
    overflow: hidden;
}

.loading-screen::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
        radial-gradient(circle at 20% 30%, rgba(102, 126, 234, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(118, 75, 162, 0.08) 0%, transparent 50%);
    animation: backgroundRotate 20s linear infinite;
    pointer-events: none;
}

.loading-screen::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(102, 126, 234, 0.03) 35px, rgba(102, 126, 234, 0.03) 70px);
    pointer-events: none;
}

@keyframes backgroundRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-screen.hidden {
    display: none;
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; visibility: hidden; }
}

.vs-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 60px;
    margin-bottom: 50px;
    width: 100%;
    max-width: 900px;
}

.character-vs-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0;
    position: relative;
    z-index: 2;
}

.character-vs-wrapper.enemy {
    animation: slideInFromLeft 0.8s ease-out 0.3s forwards;
}

.character-vs-wrapper.enemy::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    animation: pulseGlow 2s ease-in-out infinite;
    z-index: -1;
}

.character-vs-wrapper.user {
    animation: slideInFromRight 0.8s ease-out 0.3s forwards;
}

.character-vs-wrapper.user::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(118, 75, 162, 0.2) 0%, transparent 70%);
    border-radius: 50%;
    animation: pulseGlow 2s ease-in-out infinite 0.5s;
    z-index: -1;
}

@keyframes pulseGlow {
    0%, 100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.3;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0.5;
    }
}

.character-vs-name {
    font-size: 48px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    animation: nameFloat 3s ease-in-out infinite;
}

.character-vs-wrapper.user .character-vs-name {
    animation: nameFloat 3s ease-in-out infinite 0.5s;
}

@keyframes nameFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.character-vs-image {
    width: 200px;
    height: 200px;
    object-fit: contain;
    border-radius: 15px;
    background: #f8f9fa;
    padding: 15px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    animation: imageBounce 2s ease-in-out infinite;
    border: 3px solid rgba(102, 126, 234, 0.2);
}

.character-vs-wrapper.enemy .character-vs-image {
    animation: imageBounce 2s ease-in-out infinite;
}

.character-vs-wrapper.user .character-vs-image {
    animation: imageBounce 2s ease-in-out infinite 0.5s;
}

@keyframes imageBounce {
    0%, 100% { 
        transform: translateY(0) scale(1);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    50% { 
        transform: translateY(-10px) scale(1.05);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
    }
}

.character-vs-image.user-image {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 100px;
    color: #ffffff;
}

.vs-text {
    font-size: 72px;
    font-weight: bold;
    color: #667eea;
    opacity: 0;
    animation: scaleIn 0.6s ease-out 1.2s forwards;
    text-align: center;
    position: relative;
    z-index: 2;
    text-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.vs-text::before,
.vs-text::after {
    content: '⚔️';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 40px;
    opacity: 0;
    animation: weaponSpin 1s ease-out 1.5s forwards;
}

.vs-text::before {
    left: -60px;
}

.vs-text::after {
    right: -60px;
}

@keyframes weaponSpin {
    0% {
        opacity: 0;
        transform: translateY(-50%) rotate(0deg) scale(0);
    }
    100% {
        opacity: 1;
        transform: translateY(-50%) rotate(360deg) scale(1);
    }
}

@keyframes slideInFromLeft {
    from {
        opacity: 0;
        transform: translateX(-150px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(150px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.fight-text {
    font-size: 96px;
    font-weight: bold;
    color: #ff4444;
    opacity: 0;
    animation: fightPulse 1s ease-out 2s forwards;
    letter-spacing: 15px;
    margin-top: 30px;
    position: relative;
    z-index: 2;
    text-shadow: 
        0 0 20px rgba(255, 68, 68, 0.6),
        0 0 40px rgba(255, 68, 68, 0.4),
        0 4px 10px rgba(0, 0, 0, 0.2);
}

.fight-text::before {
    content: 'FIGHT!';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    color: rgba(255, 68, 68, 0.3);
    z-index: -1;
    animation: fightGlow 1.5s ease-in-out infinite;
}

@keyframes fightGlow {
    0%, 100% {
        transform: scale(1) translate(0, 0);
        opacity: 0.3;
    }
    50% {
        transform: scale(1.1) translate(2px, 2px);
        opacity: 0.5;
    }
}

@keyframes fightPulse {
    0% {
        opacity: 0;
        transform: scale(0.3) rotate(-10deg);
        filter: blur(10px);
    }
    50% {
        opacity: 1;
        transform: scale(1.15) rotate(5deg);
        filter: blur(0px);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
        filter: blur(0px);
    }
}

.loading-bar {
    width: 400px;
    height: 12px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
    margin-top: 50px;
    opacity: 0;
    animation: fadeIn 0.5s ease-out 0.5s forwards;
    border: 2px solid #dee2e6;
    position: relative;
    z-index: 2;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.loading-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    width: 0%;
    animation: loadingProgress 2s ease-out 0.5s forwards;
    border-radius: 6px;
    position: relative;
    overflow: hidden;
}

.loading-bar-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: loadingShine 1.5s ease-in-out infinite;
}

@keyframes loadingShine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes loadingProgress {
    from { width: 0%; }
    to { width: 100%; }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* 裝飾性粒子效果 */
.loading-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.particle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: rgba(102, 126, 234, 0.4);
    border-radius: 50%;
    animation: particleFloat 8s ease-in-out infinite;
}

.particle:nth-child(1) { left: 10%; animation-delay: 0s; }
.particle:nth-child(2) { left: 20%; animation-delay: 1s; }
.particle:nth-child(3) { left: 30%; animation-delay: 2s; }
.particle:nth-child(4) { left: 40%; animation-delay: 3s; }
.particle:nth-child(5) { left: 50%; animation-delay: 4s; }
.particle:nth-child(6) { left: 60%; animation-delay: 5s; }
.particle:nth-child(7) { left: 70%; animation-delay: 6s; }
.particle:nth-child(8) { left: 80%; animation-delay: 7s; }
.particle:nth-child(9) { left: 90%; animation-delay: 8s; }

@keyframes particleFloat {
    0% {
        bottom: -10px;
        opacity: 0;
        transform: translateX(0);
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        bottom: 110%;
        opacity: 0;
        transform: translateX(20px);
    }
}

@media (max-width: 768px) {
    .vs-container {
        flex-direction: column;
        gap: 30px;
    }
    
    .vs-text {
        font-size: 48px;
        transform: rotate(90deg);
    }
    
    .vs-text::before,
    .vs-text::after {
        display: none;
    }
    
    .character-vs-name {
        font-size: 36px;
    }
    
    .character-vs-image {
        width: 150px;
        height: 150px;
    }
    
    .fight-text {
        font-size: 64px;
        letter-spacing: 10px;
    }
    
    .loading-bar {
        width: 300px;
    }
}

</style>


<body>

<?php include("share/header.php"); ?>

<!-- ====== 開場加載畫面 ====== -->
<div class="loading-screen" id="loadingScreen">
    <div class="loading-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="vs-container">
        <div class="character-vs-wrapper enemy">
            <div class="character-vs-name">奶油</div>
            <img src="<?= htmlspecialchars($gif_path) ?>" alt="奶油" class="character-vs-image" id="loadingEnemyGif">
        </div>
        <div class="vs-text">VS</div>
        <div class="character-vs-wrapper user">
            <div class="character-vs-name">使用者</div>
            <div class="character-vs-image user-image">👤</div>
        </div>
    </div>
    <div class="fight-text">FIGHT!</div>
    <div class="loading-bar">
        <div class="loading-bar-fill"></div>
    </div>
</div>

<div class="game-main">
    <!-- 返回按鈕 -->
    <div style="padding: 20px; margin: 0 auto; margin-top: 110px; text-align: right;">
        <button class="btn-back-fight" onclick="window.location.href='game.php'" title="返回遊戲列表">
            ← 返回遊戲列表
        </button>
    </div>
<!-- ====== 對戰區域（包含血量顯示） ====== -->
<div class="fight-area-wrapper">
    <!-- ====== 血量顯示 ====== -->
    <div class="hp-container">
        <div class="hp-info">
            <div class="hp-label">
                <span>奶油</span>
                <span class="hp-number" id="hpEnemyNumber">10</span>
            </div>
            <div class="hp-bar-container">
                <div id="hpEnemy" class="hp-bar"></div>
            </div>
        </div>
        <div class="hp-info">
            <div class="hp-label">
                <span>👤 使用者</span>
                <span class="hp-number" id="hpUserNumber">10</span>
            </div>
            <div class="hp-bar-container">
                <div id="hpUser" class="hp-bar"></div>
            </div>
        </div>
    </div>

    <!-- ====== 對戰畫面 ====== -->
    <div class="fight-area">
    <div class="character" id="enemy">
        <div class="character-name">奶油</div>
        <img src="<?= htmlspecialchars($gif_path) ?>" alt="奶油" id="enemyGif">
    </div>

    <div class="character" id="user">
        <div class="character-name">👤 使用者</div>
        <div class="player-character"></div>
    </div>
    </div>
</div>

<!-- ====== 題目區 ====== -->
<div class="quiz-box">
    <h2 id="question"></h2>
    <div class="options" id="options"></div>
</div>

<!-- ====== 結果畫面 ====== -->
<div class="result" id="result">
    <div class="result-content">
        <div class="result-title" id="resultTitle"></div>
        <div class="result-stats" id="resultStats"></div>
        <button class="result-button" onclick="location.reload()">再來一局</button>
    </div>
</div>
</div>

	<?php include("share/footer.php"); ?>

<script>
const questions = <?= json_encode($questions) ?>;
let index = 0;
let hpUser = 10;
let hpEnemy = 10;
let isAnswering = false; // 防止重複點擊
let correctAnswers = 0; // 答對題數

// DOM 元素
const question = document.getElementById("question");
const options = document.getElementById("options");
const hpUserBar = document.getElementById("hpUser");
const hpEnemyBar = document.getElementById("hpEnemy");
const hpUserNumber = document.getElementById("hpUserNumber");
const hpEnemyNumber = document.getElementById("hpEnemyNumber");
const enemy = document.getElementById("enemy");
const user = document.getElementById("user");
const result = document.getElementById("result");
const resultTitle = document.getElementById("resultTitle");
const resultStats = document.getElementById("resultStats");

function updateHP(){
    hpUser = Math.max(hpUser, 0);
    hpEnemy = Math.max(hpEnemy, 0);
    
    // 更新血條寬度
    hpUserBar.style.width = hpUser * 10 + "%";
    hpEnemyBar.style.width = hpEnemy * 10 + "%";
    
    // 更新血量數字
    hpUserNumber.textContent = hpUser;
    hpEnemyNumber.textContent = hpEnemy;
    
    // 檢查危險狀態（血量低於3）
    if (hpUser <= 3) {
        hpUserBar.classList.add("danger");
    } else {
        hpUserBar.classList.remove("danger");
    }
    
    if (hpEnemy <= 3) {
        hpEnemyBar.classList.add("danger");
    } else {
        hpEnemyBar.classList.remove("danger");
    }
}

function showQuestion(){

    if (index >= questions.length) {
        endGame();
        return;
    }

    if(hpUser <= 0 || hpEnemy <= 0){
        endGame();
        return;
    }

    isAnswering = false;
    const q = questions[index];

    question.innerText = `(${index+1}/${questions.length}) ${q.q}`;
    options.innerHTML = "";
    
    Object.entries(q.o).forEach(([key, text])=>{
        const btn = document.createElement("button");
        btn.innerText = `${key}. ${text}`;
        btn.onclick = () => answer(key, text);
        options.appendChild(btn);
    });
}

function answer(selectedKey, text){
    // 防止重複點擊
    if (isAnswering) return;
    isAnswering = true;
    
    const correct = selectedKey === questions[index].a;
    const correctAnswer = questions[index].o[questions[index].a];
    
    // 禁用所有按鈕
    const allButtons = options.querySelectorAll("button");
    allButtons.forEach(btn => {
        btn.disabled = true;
        // 標記正確答案
        if (btn.textContent.trim().startsWith(questions[index].a + ".")) {
            btn.classList.add("correct");
        }
        // 標記錯誤答案（如果選錯了）
        if (!correct && btn.textContent.trim().startsWith(selectedKey + ".")) {
            btn.classList.add("wrong");
        }
    });
    
    if(correct){
        // 答對：使用者攻擊奶油
        showUserAttack(text, correctAnswer);
    } else {
        // 答錯：先顯示錯誤答案，然後奶油攻擊
        showWrongAnswer(text);
        setTimeout(() => {
            showEnemyAttack(text);
        }, 400);
    }
}

function showUserAttack(answerText, correctAnswer) {
    // 答對，增加答對題數
    correctAnswers++;
    
    // 創建攻擊文字（從使用者位置飛向奶油）
    const attackText = document.createElement("div");
    attackText.className = "attack-text user-attack";
    attackText.textContent = answerText;
    
    // 設置起始位置（使用者位置）
    const userRect = user.getBoundingClientRect();
    attackText.style.left = (userRect.left + userRect.width / 2) + "px";
    attackText.style.top = (userRect.top + userRect.height / 2) + "px";
    
    document.body.appendChild(attackText);
    
    // 奶油受擊動畫
    setTimeout(() => {
        enemy.classList.add("hit");
    }, 200);
    
    // 顯示傷害數字
    setTimeout(() => {
        showDamageNumber(enemy, "-1");
    }, 300);
    
    // 扣血
    hpEnemy--;
    updateHP();
    
    // 清理動畫
    setTimeout(() => {
        attackText.remove();
        enemy.classList.remove("hit");
        nextQuestion();
    }, 1000);
}

function showWrongAnswer(wrongText) {
    // 顯示錯誤答案在畫面中央
    const wrongDisplay = document.createElement("div");
    wrongDisplay.className = "wrong-answer-display";
    wrongDisplay.textContent = wrongText;
    document.body.appendChild(wrongDisplay);
    
    setTimeout(() => {
        wrongDisplay.remove();
    }, 1200);
}

function showEnemyAttack(answerText) {
    // 創建攻擊文字（從奶油位置飛向使用者）
    const attackText = document.createElement("div");
    attackText.className = "attack-text enemy-attack";
    attackText.textContent = answerText;
    
    // 設置起始位置（奶油位置）
    const enemyRect = enemy.getBoundingClientRect();
    attackText.style.left = (enemyRect.left + enemyRect.width / 2) + "px";
    attackText.style.top = (enemyRect.top + enemyRect.height / 2) + "px";
    
    document.body.appendChild(attackText);
    
    // 奶油攻擊動畫
    enemy.classList.add("attack");
    
    // 使用者受擊動畫（在攻擊文字到達時觸發）
    setTimeout(() => {
        user.classList.add("hit");
        // 顯示傷害數字
        showDamageNumber(user, "-1");
        // 扣血
        hpUser--;
        updateHP();
    }, 400);
    
    // 清理動畫
    setTimeout(() => {
        attackText.remove();
        enemy.classList.remove("attack");
        user.classList.remove("hit");
        nextQuestion();
    }, 1200);
}

function showDamageNumber(target, damage) {
    const damageNum = document.createElement("div");
    damageNum.className = "damage-number";
    damageNum.textContent = damage;
    
    const rect = target.getBoundingClientRect();
    damageNum.style.left = (rect.left + rect.width / 2) + "px";
    damageNum.style.top = (rect.top + rect.height / 2) + "px";
    
    document.body.appendChild(damageNum);
    
    setTimeout(() => {
        damageNum.remove();
    }, 800);
}

function nextQuestion() {
    index++;
    showQuestion();
}

function endGame() {
    // 禁用所有按鈕
    const allButtons = options.querySelectorAll("button");
    allButtons.forEach(btn => btn.disabled = true);
    
    // 顯示結果畫面
    result.style.display = "flex";
    
    // 判斷勝負
    if (hpUser > hpEnemy) {
        resultTitle.textContent = "🎉 勝利！";
        resultTitle.className = "result-title victory";
    } else if (hpUser < hpEnemy) {
        resultTitle.textContent = "💀 失敗";
        resultTitle.className = "result-title defeat";
    } else {
        resultTitle.textContent = "🤝 平手";
        resultTitle.className = "result-title draw";
    }
    
    // 顯示統計資訊
    const totalQuestions = Math.min(index, questions.length);
    resultStats.textContent = `最終血量：你 ${hpUser} / 奶油 ${hpEnemy} | 答對 ${correctAnswers} 題，共 ${totalQuestions} 題`;
    
    // 重置游標
    document.body.style.cursor = "default";
}

// 初始化遊戲
const loadingScreen = document.getElementById("loadingScreen");

// 等待加載畫面動畫完成後再開始遊戲
setTimeout(() => {
    loadingScreen.classList.add("hidden");
    updateHP();
    showQuestion();
}, 4000); // 3.5秒動畫 + 0.5秒緩衝
</script>

</body>
</html>
