<?php
require_once 'session_config.php';
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>康寧大學校園地圖 - 招生平台</title>
    <link rel="stylesheet" href="assets/css/maps.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'share/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-map-marked-alt"></i> 康寧大學校園地圖</h1>
            <p>探索康寧大學各校區位置，了解校園環境與周邊設施</p>
        </div>

        <div class="maps-container">
            <!-- 地圖控制面板 -->
            <div class="maps-controls">
                <div class="control-group">
                    <label for="campus-select">選擇校區：</label>
                    <select id="campus-select" class="form-control">
                        <option value="all">所有校區</option>
                        <option value="main_campus">校本部</option>
                        <option value="tamsui_campus">淡水校區</option>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="search-input">搜尋地址：</label>
                    <div class="search-container">
                        <input type="text" id="search-input" class="form-control" placeholder="輸入地址或地點...">
                        <button id="search-btn" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="control-group">
                    <label>交通方式：</label>
                    <div class="transport-modes">
                        <button class="transport-btn active" data-mode="driving">
                            <i class="fas fa-car"></i> 開車
                        </button>
                        <button class="transport-btn" data-mode="transit">
                            <i class="fas fa-bus"></i> 大眾運輸
                        </button>
                        <button class="transport-btn" data-mode="walking">
                            <i class="fas fa-walking"></i> 步行
                        </button>
                    </div>
                </div>
            </div>

            <!-- 地圖容器 -->
            <div class="map-container">
                <div id="map" class="google-map"></div>
                <div id="map-loading" class="map-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>載入地圖中...</p>
                </div>
                <!-- 備用靜態地圖 -->
                <div id="static-map" class="static-map" style="display: none;">
                    <div class="static-map-content">
                        <h3><i class="fas fa-map-marker-alt"></i> 康寧大學校本部</h3>
                        <p><strong>地址：</strong>台北市內湖區康寧路三段75巷137號</p>
                        <p><strong>座標：</strong>25.07575358359577, 121.60949282881778</p>
                        <div class="static-map-image">
                            <img src="https://maps.googleapis.com/maps/api/staticmap?center=25.07575358359577,121.60949282881778&zoom=15&size=600x400&markers=color:red%7C25.07575358359577,121.60949282881778&key=<?php echo getenv("GOOGLE_MAPS_API_KEY") ?: ""; ?>" 
                                 alt="康寧大學地圖" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="map-placeholder" style="display: none;">
                                <i class="fas fa-map-marked-alt" style="font-size: 3rem; color: #667eea; margin-bottom: 15px;"></i>
                                <p>地圖暫時無法顯示</p>
                                <p>請使用上方搜尋功能或聯繫我們</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 地圖資訊面板 -->
            <div class="map-info-panel">
                <div class="info-header">
                    <h3><i class="fas fa-info-circle"></i> 校園資訊</h3>
                </div>
                <div id="campus-info" class="campus-info">
                    <div class="campus-card">
                        <h4>康寧大學校本部</h4>
                        <p><i class="fas fa-map-marker-alt"></i> 台北市內湖區康寧路三段75巷137號</p>
                        <p><i class="fas fa-phone"></i> (02) 2632-1181</p>
                        <p><i class="fas fa-globe"></i> <a href="https://www.ukn.edu.tw" target="_blank">www.ukn.edu.tw</a></p>
                        <div class="campus-features">
                            <span class="feature-tag">圖書館</span>
                            <span class="feature-tag">體育館</span>
                            <span class="feature-tag">餐廳</span>
                            <span class="feature-tag">停車場</span>
                        </div>
                    </div>
                </div>
                
                <div id="directions-info" class="directions-info" style="display: none;">
                    <h4><i class="fas fa-route"></i> 路線規劃</h4>
                    <div id="directions-content"></div>
                </div>
            </div>
        </div>

        <!-- 校園設施列表 -->
        <div class="campus-facilities">
            <h2><i class="fas fa-building"></i> 校園設施</h2>
            <div class="facilities-grid">
                <div class="facility-card">
                    <div class="facility-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>圖書館</h3>
                    <p>提供豐富的學術資源和安靜的學習環境</p>
                    <button class="btn btn-outline-primary" onclick="showFacilityOnMap('library')">
                        在地圖上顯示
                    </button>
                </div>
                
                <div class="facility-card">
                    <div class="facility-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>體育館</h3>
                    <p>完善的運動設施，包含健身房、籃球場</p>
                    <button class="btn btn-outline-primary" onclick="showFacilityOnMap('gym')">
                        在地圖上顯示
                    </button>
                </div>
                
                <div class="facility-card">
                    <div class="facility-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>學生餐廳</h3>
                    <p>提供多樣化的餐飲選擇</p>
                    <button class="btn btn-outline-primary" onclick="showFacilityOnMap('restaurant')">
                        在地圖上顯示
                    </button>
                </div>
                
                <div class="facility-card">
                    <div class="facility-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>停車場</h3>
                    <p>充足的停車空間，方便使用</p>
                    <button class="btn btn-outline-primary" onclick="showFacilityOnMap('parking')">
                        在地圖上顯示
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'share/footer.php'; ?>

    <!-- Google Maps API -->
    <script>
        // 從後端獲取 Google Maps API Key，或從 URL 參數獲取
        const GOOGLE_MAPS_API_KEY = '<?php 
            $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : (getenv("GOOGLE_MAPS_API_KEY") ?: "");
            if (empty($apiKey) && isset($_GET['key'])) {
                $apiKey = $_GET['key'];
            }
            echo $apiKey;
        ?>';
        
        // 調試輸出
        console.log('API Key 長度:', GOOGLE_MAPS_API_KEY.length);
        console.log('API Key 前5個字元:', GOOGLE_MAPS_API_KEY.substring(0, 5));
        
        // 如果沒有 API Key，顯示提示訊息
        if (!GOOGLE_MAPS_API_KEY) {
            console.warn('Google Maps API Key 未設定，地圖功能可能無法正常使用');
            console.log('請在網址加上 ?key=你的API_KEY 來測試地圖功能');
            // 顯示靜態地圖
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('map-loading').style.display = 'none';
                document.getElementById('static-map').style.display = 'flex';
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php 
        $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : (getenv("GOOGLE_MAPS_API_KEY") ?: "");
        if (empty($apiKey) && isset($_GET['key'])) {
            $apiKey = $_GET['key'];
        }
        echo $apiKey;
    ?>&libraries=places&language=zh-TW&region=TW&callback=initMap" async defer></script>
    <script src="assets/js/maps.js"></script>
</body>
</html>
