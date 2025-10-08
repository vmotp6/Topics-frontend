<?php
// 載入 session 配置
require_once 'session_config.php';

// 檢查登入狀態
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && isset($_SESSION['username']);

// 獲取頭像路徑
$avatar_src = './share/EIdROxGXsAE_LSs.jpg';
if ($isLoggedIn && isset($_SESSION['username'])) {
    try {
        $configPath = dirname(__DIR__) . '/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
            $conn = getDatabaseConnection();
        } else {
            $conn = null;
        }
        if ($conn) {
            $stmt = $conn->prepare("SELECT profile_picture FROM user WHERE username = ?");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['profile_picture'])) {
                    if (filter_var($row['profile_picture'], FILTER_VALIDATE_URL)) {
                        $avatar_src = $row['profile_picture'];
                    } else {
                        $avatar_src = './share/' . $row['profile_picture'];
                    }
                }
            }
            $conn->close();
        }
    } catch (Exception $e) {
        error_log("頭像載入錯誤: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>原始HTML測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { 
            background: #e9ecef; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            font-family: monospace; 
            font-size: 12px;
        }
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            border: 3px solid #007bff; 
            margin: 10px;
            object-fit: cover;
        }
        .navbar { 
            background: rgba(217, 229, 234, 0.95); 
            padding: 15px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <h1>🔧 原始HTML測試</h1>
    
    <div class="debug">
登入狀態: <?php echo $isLoggedIn ? 'true' : 'false'; ?>
用戶名: <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'null'; ?>
頭像路徑: <?php echo $avatar_src; ?>
    </div>
    
    <div>
        <h3>直接圖片測試</h3>
        <img src="<?php echo $avatar_src; ?>" alt="測試頭像" class="avatar">
    </div>
    
    <?php if ($isLoggedIn): ?>
    <div>
        <h3>完整導航列HTML</h3>
        <div class="navbar">
            <div style="font-weight: bold; color: #2c3e50;">康寧大學招生平台</div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="color: #2c3e50;">歡迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <div style="position: relative;">
                    <div style="cursor: pointer; display: flex; align-items: center; justify-content: center; position: relative; padding: 0; width: 50px; height: 50px; border-radius: 50%; transition: all 0.3s ease;">
                        <img src="<?php echo htmlspecialchars($avatar_src); ?>" 
                             alt="頭像" 
                             style="width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; background-color: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div>
        <h3>實際Header組件</h3>
        <div style="border: 2px solid #007bff; padding: 10px; border-radius: 8px;">
            <?php include 'share/header.php'; ?>
        </div>
    </div>
    <?php else: ?>
    <div>
        <h3>❌ 未登入</h3>
        <p>請先登入後再測試</p>
        <a href="test_google_login.php">🔐 Google 登入</a>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <a href="index.php">🏠 返回首頁</a>
    </div>

    <script>
        console.log('🔧 原始HTML測試頁面載入完成');
        console.log('登入狀態:', <?php echo $isLoggedIn ? 'true' : 'false'; ?>);
        console.log('頭像路徑:', '<?php echo $avatar_src; ?>');
        
        // 檢查所有圖片
        const images = document.querySelectorAll('img');
        console.log('找到 ' + images.length + ' 個圖片元素');
        
        images.forEach((img, index) => {
            console.log(`圖片 ${index + 1}:`, img.src);
            
            img.addEventListener('load', () => {
                console.log(`✅ 圖片 ${index + 1} 載入成功`);
            });
            
            img.addEventListener('error', () => {
                console.log(`❌ 圖片 ${index + 1} 載入失敗`);
            });
        });
    </script>
</body>
</html>
