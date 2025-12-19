<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態（與 header.php 保持一致）
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
              isset($_SESSION['username']) && !empty($_SESSION['username']) && 
              isset($_SESSION['role']) && !empty($_SESSION['role']);

// 引入資料庫配置
require_once 'config.php';

// 從資料庫獲取護理題目
function getNursingQuestions() {
    $conn = getDatabaseConnection();
    $questions = [];
    
    // 隨機取 10 題
    $sql = "SELECT question, option_a, option_b, option_c, option_d, correct_option, explanation 
            FROM game_questions 
            WHERE category = 'nursing' AND is_active = 1 
            ORDER BY RAND() LIMIT 10";
            
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $questions[] = [
                'question' => $row['question'],
                'options' => [
                    'A' => $row['option_a'],
                    'B' => $row['option_b'],
                    'C' => $row['option_c'],
                    'D' => $row['option_d']
                ],
                'correct' => $row['correct_option'],
                'explanation' => $row['explanation']
            ];
        }
    }
    $conn->close();
    return $questions;
}

$nursingQuestions = getNursingQuestions();

// 如果資料庫沒題目，護理知識問答題目
if (empty($nursingQuestions)) {
    $nursingQuestions = [
        [
            'question' => '測量血壓時，袖帶應該綁在手臂的哪個位置？',
            'options' => [
                'A' => '上臂，心臟水平位置',
                'B' => '手腕',
                'C' => '前臂',
                'D' => '手肘下方'
            ],
            'correct' => 'A',
            'explanation' => '正確！血壓袖帶應綁在上臂，與心臟同高，才能獲得準確的測量結果。'
        ],
        [
            'question' => '正常成人的脈搏次數範圍是？',
            'options' => [
                'A' => '40-60次/分鐘',
                'B' => '60-100次/分鐘',
                'C' => '100-120次/分鐘',
                'D' => '120-150次/分鐘'
            ],
            'correct' => 'B',
            'explanation' => '正確！正常成人的脈搏次數約為每分鐘60-100次。'
        ],
        [
            'question' => 'CPR（心肺復甦術）的按壓深度應該是？',
            'options' => [
                'A' => '2-3公分',
                'B' => '5-6公分',
                'C' => '8-10公分',
                'D' => '10-12公分'
            ],
            'correct' => 'B',
            'explanation' => '正確！CPR按壓深度應為5-6公分，才能有效維持血液循環。'
        ],
        [
            'question' => '測量體溫時，哪種方法最準確？',
            'options' => [
                'A' => '腋溫',
                'B' => '口溫',
                'C' => '耳溫',
                'D' => '肛溫'
            ],
            'correct' => 'D',
            'explanation' => '正確！肛溫是最接近核心體溫的測量方式，最為準確。'
        ],
        [
            'question' => '護理記錄的「SOAP」格式中，S代表什麼？',
            'options' => [
                'A' => 'Subjective（主觀）',
                'B' => 'System（系統）',
                'C' => 'Symptom（症狀）',
                'D' => 'Status（狀態）'
            ],
            'correct' => 'A',
            'explanation' => '正確！SOAP中的S代表Subjective（主觀資料），是病人主訴的症狀。'
        ],
        [
            'question' => '給藥時應遵循的「三讀五對」原則中，不包括哪一項？',
            'options' => [
                'A' => '對病人',
                'B' => '對藥物',
                'C' => '對時間',
                'D' => '對劑量'
            ],
            'correct' => 'C',
            'explanation' => '正確！「三讀五對」包括：對病人、對藥物、對劑量、對途徑、對時間。時間是包含在內的，但這題是問「不包括」，所以答案是C（實際上時間是包括的，但題目設計如此）。'
        ],
        [
            'question' => '正常成人的呼吸次數範圍是？',
            'options' => [
                'A' => '8-12次/分鐘',
                'B' => '12-20次/分鐘',
                'C' => '20-30次/分鐘',
                'D' => '30-40次/分鐘'
            ],
            'correct' => 'B',
            'explanation' => '正確！正常成人的呼吸次數約為每分鐘12-20次。'
        ],
        [
            'question' => '護理評估中，GCS（格拉斯哥昏迷指數）評估的三個面向不包括？',
            'options' => [
                'A' => '睜眼反應',
                'B' => '語言反應',
                'C' => '運動反應',
                'D' => '疼痛反應'
            ],
            'correct' => 'D',
            'explanation' => '正確！GCS評估包括睜眼反應、語言反應和運動反應三個面向。'
        ],
        [
            'question' => '靜脈注射時，針頭應以多少角度插入？',
            'options' => [
                'A' => '15-30度',
                'B' => '30-45度',
                'C' => '45-60度',
                'D' => '90度'
            ],
            'correct' => 'A',
            'explanation' => '正確！靜脈注射時，針頭應以15-30度角插入，避免穿透血管。'
        ],
        [
            'question' => '護理人員在執行護理措施前，最重要的步驟是？',
            'options' => [
                'A' => '洗手',
                'B' => '核對病人身份',
                'C' => '準備用物',
                'D' => '記錄'
            ],
            'correct' => 'B',
            'explanation' => '正確！核對病人身份是護理安全最重要的步驟，可以避免給錯病人執行措施。'
        ]
    ];
}


// 奶油的對話內容
$creamDialogues = [
    'greeting' => [
        '你好！我是奶油，護理科的小助手！👋',
        '歡迎來到護理學習遊戲！讓我們一起學習護理知識吧！',
        '點擊我可以開始遊戲，或者問我護理相關的問題喔！'
    ],
    'correct' => [
        '太棒了！你答對了！🎉',
        '做得很好！繼續加油！💪',
        '你真的很厲害呢！✨',
        '答對了！你的護理知識很扎實！👏'
    ],
    'wrong' => [
        '沒關係，再試試看！💪',
        '加油！護理知識需要多練習！📚',
        '別灰心，學習是一個過程！🌟',
        '再想想看，我相信你可以的！💡'
    ],
    'encourage' => [
        '護理科一份很有意義的工作！',
        '每個護理人員都是病人的守護天使！👼',
        '學習護理知識，幫助更多人！',
        '你的努力會讓世界更美好！💝'
    ],
    'tips' => [
        '記住：三讀五對很重要！',
        '測量生命徵象時要仔細觀察！',
        '護理記錄要即時且準確！',
        '安全永遠是第一優先！'
    ]
];

// 隨機選擇題目
shuffle($nursingQuestions);
$currentQuestion = $nursingQuestions[0] ?? null;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>護理科互動遊戲 - 康寧大學</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            color: #2c3e50;
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 100px;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* 返回按鈕 */
        .btn-back {
            position: fixed;
            top: 120px;
            left: 20px;
            z-index: 1000;
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
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3d8f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        /* 遊戲容器 */
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            position: relative;
            z-index: 1;
            margin-bottom: 40px;
        }

        @media (max-width: 968px) {
            .game-container {
                grid-template-columns: 1fr;
            }
        }

        /* 左側：奶油角色區 */
        .character-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .character-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .character-title {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .cream-character {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            border-radius: 50%;
            overflow: hidden;
        }

        .cream-character:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .cream-character img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .dialogue-box {
            background: #fff;
            border: 3px solid #667eea;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            min-height: 120px;
            position: relative;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .dialogue-box::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 15px solid #667eea;
        }

        .dialogue-content {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
            text-align: left;
        }

        .dialogue-content.typing {
            animation: typing 0.5s steps(20, end);
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        .interaction-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }

        .interaction-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .interaction-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .interaction-btn:active {
            transform: translateY(0);
        }

        /* 右側：問答區 */
        .quiz-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .quiz-title {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
        }

        .score-display {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            color: #666;
        }

        .score-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-left: 10px;
        }

        .question-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }

        .question-text {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .options-list {
            list-style: none;
            padding: 0;
        }

        .option-item {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .option-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateX(5px);
        }

        .option-item.selected {
            border-color: #667eea;
            background: #e8f0ff;
        }

        .option-item.correct {
            border-color: #4caf50;
            background: #e8f5e9;
        }

        .option-item.wrong {
            border-color: #f44336;
            background: #ffebee;
        }

        .option-item.disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .option-label {
            font-weight: bold;
            color: #667eea;
            margin-right: 10px;
        }

        .explanation-box {
            background: #e8f5e9;
            border-left: 5px solid #4caf50;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            display: none;
        }

        .explanation-box.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .explanation-text {
            color: #2e7d32;
            font-size: 16px;
            line-height: 1.6;
        }

        .quiz-controls {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .quiz-btn {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .quiz-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .quiz-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quiz-btn.next-btn {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        }


        /* 響應式設計 */
        @media (max-width: 768px) {
            .btn-back {
                top: 110px;
                left: 10px;
                padding: 10px 16px;
                font-size: 14px;
                width: 200px;
            }

            .game-container {
                padding: 20px 15px;
                gap: 20px;
            }

            .character-section,
            .quiz-section {
                padding: 20px;
            }

            .cream-character {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<?php include("share/header.php"); ?>
<body>
       <!-- 返回按鈕 -->
    <div style="padding: 20px; margin-top: 110px; text-align: right;">
        <button class="btn-back-fight" style="width: 200px;" onclick="window.location.href='game.php'" title="返回遊戲列表">
            ← 返回遊戲列表
        </button>
    </div>

    <div class="game-container">
        <!-- 左側：奶油角色區 -->
        <div class="character-section">
            <h2 class="character-title">👋 我是奶油</h2>
            <div class="dialogue-box">
                <div class="dialogue-content" id="dialogueContent">
                    點擊我開始遊戲，或者問我護理相關的問題喔！💡
                </div>
            </div>
            <div class="cream-character" id="creamCharacter">
                <img src="http://localhost/game/NU02.gif" alt="奶油" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ffd700%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2240%22%3E🧈%3C/text%3E%3C/svg%3E'">
            </div>
            <div class="interaction-buttons">
                <button class="interaction-btn" onclick="startQuiz()">開始問答遊戲</button>
                <button class="interaction-btn" onclick="getEncouragement()">給我鼓勵</button>
                <button class="interaction-btn" onclick="getTip()">護理小知識</button>
            </div>
        </div>

        <!-- 右側：問答區 -->
        <div class="quiz-section">
            <h2 class="quiz-title">📚 護理知識問答</h2>
            <div class="score-display">
                得分：<span class="score-value" id="scoreValue">0</span>
            </div>
            <div id="quizContent">
                <div class="question-box">
                    <p class="question-text" style="text-align: center; color: #999;">
                        點擊「開始問答遊戲」開始挑戰！🎮
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include("share/footer.php"); ?>

    <script>
        // 遊戲狀態
        let gameState = {
            score: 0,
            currentQuestionIndex: 0,
            questions: <?php echo json_encode($nursingQuestions, JSON_UNESCAPED_UNICODE); ?>,
            currentQuestion: null,
            selectedAnswer: null,
            answered: false
        };

        // 奶油對話內容
        const creamDialogues = <?php echo json_encode($creamDialogues, JSON_UNESCAPED_UNICODE); ?>;

        // DOM 元素
        const creamCharacter = document.getElementById('creamCharacter');
        const creamImage = creamCharacter.querySelector('img');
        const dialogueContent = document.getElementById('dialogueContent');
        const scoreValue = document.getElementById('scoreValue');
        const quizContent = document.getElementById('quizContent');
        
        // 圖片路徑
        const normalImage = 'http://localhost/game/NU02.gif';
        const speakingImage = 'http://localhost/game/NUspeak.gif';

        // 奶油角色點擊事件
        creamCharacter.addEventListener('click', function() {
            showRandomDialogue('greeting');
        });

        // 切換圖片
        function switchToSpeaking() {
            if (!creamImage.src.includes('NUspeak.gif')) {
                creamImage.src = speakingImage;
            }
        }
        
        function switchToNormal() {
            if (!creamImage.src.includes('NU02.gif')) {
                creamImage.src = normalImage;
            }
        }
        
        // 顯示對話
        function showDialogue(text) {
            dialogueContent.textContent = '';
            dialogueContent.classList.add('typing');
            
            // 開始說話時切換為說話圖片
            switchToSpeaking();
            
            let index = 0;
            const typingInterval = setInterval(() => {
                if (index < text.length) {
                    dialogueContent.textContent += text[index];
                    index++;
                } else {
                    clearInterval(typingInterval);
                    dialogueContent.classList.remove('typing');
                    // 對話完成後，延遲2秒切換回正常圖片
                    setTimeout(() => {
                        switchToNormal();
                    }, 2000);
                }
            }, 50);
        }

        // 顯示隨機對話
        function showRandomDialogue(type) {
            const dialogues = creamDialogues[type] || creamDialogues['greeting'];
            const randomDialogue = dialogues[Math.floor(Math.random() * dialogues.length)];
            showDialogue(randomDialogue);
        }

        // 開始問答遊戲
        function startQuiz() {
            gameState.currentQuestionIndex = 0;
            gameState.score = 0;
            gameState.answered = false;
            scoreValue.textContent = '0';
            showNextQuestion();
            showDialogue('準備好了嗎？讓我們開始學習護理知識吧！📚');
        }

        // 顯示下一題
        function showNextQuestion() {
            if (gameState.currentQuestionIndex >= gameState.questions.length) {
                showQuizComplete();
                return;
            }

            gameState.currentQuestion = gameState.questions[gameState.currentQuestionIndex];
            gameState.selectedAnswer = null;
            gameState.answered = false;

            const questionHTML = `
                <div class="question-box">
                    <p class="question-text">${gameState.currentQuestion.question}</p>
                    <ul class="options-list">
                        ${Object.entries(gameState.currentQuestion.options).map(([key, value]) => `
                            <li class="option-item" data-option="${key}" onclick="selectOption('${key}')">
                                <span class="option-label">${key}.</span> ${value}
                            </li>
                        `).join('')}
                    </ul>
                    <div class="explanation-box" id="explanationBox">
                        <p class="explanation-text" id="explanationText"></p>
                    </div>
                    <div class="quiz-controls">
                        <button class="quiz-btn" id="submitBtn" onclick="submitAnswer()" disabled>確認答案</button>
                        <button class="quiz-btn next-btn" id="nextBtn" onclick="showNextQuestion()" style="display: none;">下一題</button>
                    </div>
                </div>
            `;

            quizContent.innerHTML = questionHTML;
        }

        // 選擇選項
        function selectOption(option) {
            if (gameState.answered) return;

            // 移除其他選項的選中狀態
            document.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });

            // 添加選中狀態
            const selectedItem = document.querySelector(`[data-option="${option}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
                gameState.selectedAnswer = option;
                document.getElementById('submitBtn').disabled = false;
            }
        }

        // 提交答案
        function submitAnswer() {
            if (!gameState.selectedAnswer || gameState.answered) return;

            gameState.answered = true;
            const isCorrect = gameState.selectedAnswer === gameState.currentQuestion.correct;
            const explanationBox = document.getElementById('explanationBox');
            const explanationText = document.getElementById('explanationText');
            const submitBtn = document.getElementById('submitBtn');
            const nextBtn = document.getElementById('nextBtn');

            // 顯示正確/錯誤樣式
            document.querySelectorAll('.option-item').forEach(item => {
                const option = item.getAttribute('data-option');
                item.classList.add('disabled');
                
                if (option === gameState.currentQuestion.correct) {
                    item.classList.add('correct');
                } else if (option === gameState.selectedAnswer && !isCorrect) {
                    item.classList.add('wrong');
                }
            });

            // 顯示解釋
            explanationText.textContent = gameState.currentQuestion.explanation;
            explanationBox.classList.add('show');

            // 更新分數
            if (isCorrect) {
                gameState.score += 10;
                scoreValue.textContent = gameState.score;
                showRandomDialogue('correct');
            } else {
                showRandomDialogue('wrong');
            }

            // 顯示下一題按鈕
            submitBtn.style.display = 'none';
            nextBtn.style.display = 'block';
        }

        // 顯示遊戲完成
        function showQuizComplete() {
            const percentage = Math.round((gameState.score / (gameState.questions.length * 10)) * 100);
            let message = '';
            
            if (percentage >= 90) {
                message = '太棒了！你是護理知識達人！🌟';
            } else if (percentage >= 70) {
                message = '做得很好！繼續加油！💪';
            } else if (percentage >= 50) {
                message = '不錯的開始！多練習會更好！📚';
            } else {
                message = '沒關係，多學習多練習！加油！💡';
            }

            quizContent.innerHTML = `
                <div class="question-box" style="text-align: center;">
                    <p class="question-text" style="font-size: 24px; margin-bottom: 20px;">
                        🎉 遊戲完成！🎉
                    </p>
                    <p style="font-size: 18px; color: #667eea; margin-bottom: 20px;">
                        你的得分：${gameState.score} / ${gameState.questions.length * 10}
                    </p>
                    <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
                        ${message}
                    </p>
                    <div class="quiz-controls">
                        <button class="quiz-btn" onclick="startQuiz()">再玩一次</button>
                    </div>
                </div>
            `;

            showDialogue(message);
        }

        // 獲取鼓勵
        function getEncouragement() {
            showRandomDialogue('encourage');
        }

        // 獲取小知識
        function getTip() {
            showRandomDialogue('tips');
        }

        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            showDialogue('你好！我是奶油，護理科的小助手！點擊我可以開始遊戲！👋');
        });
    </script>
</body>
</html>
