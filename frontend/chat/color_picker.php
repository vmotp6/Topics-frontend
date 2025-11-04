<?php
session_start();

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'] ?? 'test_user';
$role = $_SESSION['role'] ?? '學生';

// 處理配色方案選擇
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['color_scheme'])) {
    $_SESSION['chat_color_scheme'] = $_POST['color_scheme'];
    $successMessage = "配色方案已更新！";
}

$currentScheme = $_SESSION['chat_color_scheme'] ?? 'white';
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>聊天室配色方案</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #007bff;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .color-schemes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .color-scheme {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .color-scheme:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .color-scheme.selected {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .color-scheme.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        .scheme-preview {
            display: flex;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .scheme-sidebar {
            width: 30%;
        }
        
        .scheme-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .scheme-header {
            height: 25%;
        }
        
        .scheme-messages {
            flex: 1;
        }
        
        .scheme-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .scheme-description {
            font-size: 14px;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 聊天室配色方案</h1>
            <p>選擇您喜歡的聊天室配色方案</p>
        </div>
        
        <div class="content">
            <?php if (isset($successMessage)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="colorForm">
                <div class="color-schemes">
                    
                    <!-- 方案1: 簡潔白色 -->
                    <div class="color-scheme <?php echo $currentScheme === 'white' ? 'selected' : ''; ?>" 
                         data-scheme="white" onclick="selectScheme('white')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f8f9fa;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #ffffff; border-bottom: 1px solid #e9ecef;"></div>
                                <div class="scheme-messages" style="background: #ffffff;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">簡潔白色</div>
                        <div class="scheme-description">經典的白色主題，簡潔清爽，適合長時間使用</div>
                    </div>
                    
                    <!-- 方案2: 溫暖米色 -->
                    <div class="color-scheme <?php echo $currentScheme === 'warm' ? 'selected' : ''; ?>" 
                         data-scheme="warm" onclick="selectScheme('warm')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f5f5f0;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #fefefe; border-bottom: 1px solid #e8e6e0;"></div>
                                <div class="scheme-messages" style="background: #fefefe;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">溫暖米色</div>
                        <div class="scheme-description">溫和的米色調，給人溫暖舒適的感覺</div>
                    </div>
                    
                    <!-- 方案3: 清新薄荷 -->
                    <div class="color-scheme <?php echo $currentScheme === 'mint' ? 'selected' : ''; ?>" 
                         data-scheme="mint" onclick="selectScheme('mint')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f0f8f6;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #f8fffe; border-bottom: 1px solid #e0f2f0;"></div>
                                <div class="scheme-messages" style="background: #f8fffe;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">清新薄荷</div>
                        <div class="scheme-description">清新的薄荷綠，讓人感到放鬆和舒適</div>
                    </div>
                    
                    <!-- 方案4: 柔和粉色 -->
                    <div class="color-scheme <?php echo $currentScheme === 'pink' ? 'selected' : ''; ?>" 
                         data-scheme="pink" onclick="selectScheme('pink')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f9f0f0;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #fef7f7; border-bottom: 1px solid #f0e0e0;"></div>
                                <div class="scheme-messages" style="background: #fef7f7;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">柔和粉色</div>
                        <div class="scheme-description">溫柔的粉色調，溫馨浪漫的氛圍</div>
                    </div>
                    
                    <!-- 方案5: 優雅灰色 -->
                    <div class="color-scheme <?php echo $currentScheme === 'gray' ? 'selected' : ''; ?>" 
                         data-scheme="gray" onclick="selectScheme('gray')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f5f5f5;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #fafafa; border-bottom: 1px solid #e0e0e0;"></div>
                                <div class="scheme-messages" style="background: #fafafa;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">優雅灰色</div>
                        <div class="scheme-description">低調的灰色調，專業且優雅</div>
                    </div>
                    
                    <!-- 方案6: 清新藍色 -->
                    <div class="color-scheme <?php echo $currentScheme === 'blue' ? 'selected' : ''; ?>" 
                         data-scheme="blue" onclick="selectScheme('blue')">
                        <div class="scheme-preview">
                            <div class="scheme-sidebar" style="background: #f0f6ff;"></div>
                            <div class="scheme-main">
                                <div class="scheme-header" style="background: #f8fbff; border-bottom: 1px solid #e3f2fd;"></div>
                                <div class="scheme-messages" style="background: #f8fbff;"></div>
                            </div>
                        </div>
                        <div class="scheme-name">清新藍色</div>
                        <div class="scheme-description">清新的藍色調，清爽且專業</div>
                    </div>
                    
                </div>
                
                <input type="hidden" name="color_scheme" id="selectedScheme" value="<?php echo $currentScheme; ?>">
                
                <div class="actions">
                    <button type="submit" class="btn">💾 保存配色方案</button>
                    <a href="chat.php" class="btn btn-secondary">← 返回聊天室</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function selectScheme(scheme) {
            // 移除所有選中狀態
            document.querySelectorAll('.color-scheme').forEach(el => {
                el.classList.remove('selected');
            });
            
            // 添加選中狀態
            document.querySelector(`[data-scheme="${scheme}"]`).classList.add('selected');
            
            // 更新隱藏輸入框
            document.getElementById('selectedScheme').value = scheme;
        }
    </script>
</body>
</html>

























