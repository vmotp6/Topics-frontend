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
<body class="custom-spacing">
    <?php include 'share/header.php'; ?>
    
    <div class="map-page-container">
        <div class="maps-container">

            <!-- 浮動控制按鈕 -->
            <div class="floating-controls">
                <button id="show-restaurants-btn" class="floating-btn" title="附近餐廳">
                    <i class="fas fa-utensils"></i>
                    <span>附近餐廳</span>
                </button>
                <button id="show-campus-info-btn" class="floating-btn active" title="校園資訊">
                    <i class="fas fa-info-circle"></i>
                    <span>校園資訊</span>
                </button>
                <button id="get-directions-btn" class="floating-btn" title="規劃路線">
                    <i class="fas fa-route"></i>
                    <span>規劃路線</span>
                </button>
            </div>

            <!-- 地圖容器 - 全寬顯示，類似 Google Maps -->
            <div class="map-container">
                <div id="map" class="google-map"></div>
                <div id="map-loading" class="map-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>載入地圖中...</p>
                </div>
                <!-- 備用靜態地圖 -->
                <div id="static-map" class="static-map" style="display: none;">
                    <div class="static-map-content">
                        <h3><i class="fas fa-map-marker-alt"></i> 康寧大學台北校區</h3>
                        <p><strong>地址：</strong>台北市內湖區康寧路三段75巷137號</p>
                        <p><strong>座標：</strong>25.07575358359577, 121.60949282881778</p>
                        <div class="static-map-image">
                            <img src="https://maps.googleapis.com/maps/api/staticmap?center=25.07575358359577,121.60949282881778&zoom=15&size=600x400&markers=color:red%7C25.07575358359577,121.60949282881778&key=<?php 
                                // 確保載入 config.php
                                if (!defined('GOOGLE_MAPS_API_KEY')) {
                                    require_once __DIR__ . '/config.php';
                                }
                                $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
                                echo htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');
                            ?>" 
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

            <!-- 側邊資訊面板 - 可收合 -->
            <div id="side-panel" class="side-panel">
                <div class="panel-header">
                    <h3 id="panel-title"><i class="fas fa-info-circle"></i> 校園資訊</h3>
                    <button id="close-panel-btn" class="close-panel-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="panel-content">
                    <!-- 校園資訊 -->
                    <div id="campus-info" class="campus-info">
                        <div class="campus-card">
                            <h4><i class="fas fa-university"></i> 康寧大學台北校區</h4>
                            <p><i class="fas fa-map-marker-alt"></i> 台北市內湖區康寧路三段75巷137號</p>
                            <p><i class="fas fa-phone"></i> (02) 2632-1181</p>
                            <p><i class="fas fa-globe"></i> <a href="https://www.ukn.edu.tw" target="_blank">www.ukn.edu.tw</a></p>
                            <div class="campus-features">
                                <span class="feature-tag"><i class="fas fa-book"></i> 圖書館</span>
                                <span class="feature-tag"><i class="fas fa-dumbbell"></i> 體育館</span>
                                <span class="feature-tag"><i class="fas fa-utensils"></i> 餐廳</span>
                                <span class="feature-tag"><i class="fas fa-car"></i> 停車場</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 附近餐廳列表 -->
                    <div id="restaurants-list" class="restaurants-list" style="display: none;">
                        <div class="restaurants-header">
                            <h4><i class="fas fa-utensils"></i> 附近餐廳</h4>
                            <span id="restaurants-count" class="restaurants-count"></span>
                        </div>
                        <div id="restaurants-content" class="restaurants-content">
                            <p class="loading-text">正在搜尋附近餐廳...</p>
                        </div>
                    </div>
                    
                    <!-- 路線規劃 -->
                    <div id="directions-info" class="directions-info" style="display: none;">
                        <h4><i class="fas fa-route"></i> 路線規劃</h4>
                        <div id="directions-content"></div>
                    </div>
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
        // 從後端獲取 Google Maps API Key
        const GOOGLE_MAPS_API_KEY = '<?php 
            // 確保載入 config.php
            if (!defined('GOOGLE_MAPS_API_KEY')) {
                require_once __DIR__ . '/config.php';
            }
            $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
            if (empty($apiKey) && isset($_GET['key'])) {
                $apiKey = $_GET['key'];
            }
            echo htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');
        ?>';
        
        // 調試輸出
        console.log('Google Maps API Key 已載入，長度:', GOOGLE_MAPS_API_KEY ? GOOGLE_MAPS_API_KEY.length : 0);
        
        // 如果沒有 API Key，顯示提示訊息
        if (!GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY.trim() === '') {
            console.warn('Google Maps API Key 未設定，地圖功能可能無法正常使用');
            console.log('請在網址加上 ?key=你的API_KEY 來測試地圖功能');
            // 顯示靜態地圖
            document.addEventListener('DOMContentLoaded', function() {
                const loadingElement = document.getElementById('map-loading');
                const staticMapElement = document.getElementById('static-map');
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                if (staticMapElement) {
                    staticMapElement.style.display = 'flex';
                }
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php 
        // 確保載入 config.php
        if (!defined('GOOGLE_MAPS_API_KEY')) {
            require_once __DIR__ . '/config.php';
        }
        $apiKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
        if (empty($apiKey) && isset($_GET['key'])) {
            $apiKey = $_GET['key'];
        }
        echo htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');
    ?>&libraries=places&language=zh-TW&region=TW&callback=initMap" async defer></script>
    <script src="assets/js/maps.js"></script>
</body>
</html>
