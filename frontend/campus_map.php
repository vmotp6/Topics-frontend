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
                <button id="show-campus-info-btn" class="floating-btn active" title="校園資訊">
                    <i class="fas fa-info-circle"></i>
                    <span>校園資訊</span>
                </button>
                <button id="show-campus-map-btn" class="floating-btn" title="校園平面圖" onclick="console.log('按鈕 onclick 被觸發'); if(window.campusMap){console.log('調用 showCampusMap'); window.campusMap.showCampusMap();} else {console.error('CampusMap 實例不存在，window.campusMap:', window.campusMap);}">
                    <i class="fas fa-map"></i>
                    <span>校園平面圖</span>
                </button>
                <button id="show-restaurants-btn" class="floating-btn" title="附近餐廳">
                    <i class="fas fa-utensils"></i>
                    <span>附近餐廳</span>
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
                        <p><strong>座標：</strong>25.076132980674792, 121.61012050007541</p>
                        <div class="static-map-image">
                            <img src="https://maps.googleapis.com/maps/api/staticmap?center=25.076132980674792,121.61012050007541&zoom=16&size=600x400&markers=color:red%7Clabel:C%7C25.076132980674792,121.61012050007541&key=<?php 
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
                        
                        <!-- 校園建築物列表 -->
                        <div class="buildings-section">
                            <h4 style="margin: 24px 0 16px 0; color: #333; font-size: 1.1rem; font-weight: 600;">
                                <i class="fas fa-building" style="color: #667eea; margin-right: 8px;"></i>校園建築物
                            </h4>
                            <div class="buildings-list">
                                <div class="building-item" onclick="if(window.campusMap) window.campusMap.focusOnBuilding('firstTeachingBuilding')">
                                    <div class="building-icon" style="background: #EA4335;">
                                        <span style="color: white; font-weight: bold;">C</span>
                                    </div>
                                    <div class="building-info">
                                        <div class="building-name">第一教學大樓 (C棟)</div>
                                        <div class="building-coords">25.076132980674792, 121.61012050007541</div>
                                    </div>
                                    <i class="fas fa-chevron-right building-arrow"></i>
                                </div>
                                
                                <div class="building-item" onclick="if(window.campusMap) window.campusMap.focusOnBuilding('secondTeachingBuilding')">
                                    <div class="building-icon" style="background: #EA4335;">
                                        <span style="color: white; font-weight: bold;">E</span>
                                    </div>
                                    <div class="building-info">
                                        <div class="building-name">第二教學大樓 (E棟)</div>
                                        <div class="building-coords">25.075602118954585, 121.61005748369553</div>
                                    </div>
                                    <i class="fas fa-chevron-right building-arrow"></i>
                                </div>
                                
                                <div class="building-item" onclick="if(window.campusMap) window.campusMap.focusOnBuilding('administrativeBuilding')">
                                    <div class="building-icon" style="background: #EA4335;">
                                        <span style="color: white; font-weight: bold;">A</span>
                                    </div>
                                    <div class="building-info">
                                        <div class="building-name">行政大樓 (A棟)</div>
                                        <div class="building-coords">25.075479266366344, 121.60968101654407</div>
                                    </div>
                                    <i class="fas fa-chevron-right building-arrow"></i>
                                </div>
                                
                                <div class="building-item" onclick="if(window.campusMap) window.campusMap.focusOnBuilding('yeshengHall')">
                                    <div class="building-icon" style="background: #EA4335;">
                                        <span style="color: white; font-weight: bold; font-size: 0.9rem;">野</span>
                                    </div>
                                    <div class="building-info">
                                        <div class="building-name">野聲館</div>
                                        <div class="building-coords">25.076506287137924, 121.61032460516375</div>
                                    </div>
                                    <i class="fas fa-chevron-right building-arrow"></i>
                                </div>
                                
                                <div class="building-item" onclick="if(window.campusMap) window.campusMap.focusOnBuilding('cihuiBuilding')">
                                    <div class="building-icon" style="background: #EA4335;">
                                        <span style="color: white; font-weight: bold; font-size: 0.9rem;">慈</span>
                                    </div>
                                    <div class="building-info">
                                        <div class="building-name">慈暉樓</div>
                                        <div class="building-coords">25.076098148747832, 121.60976402349822</div>
                                    </div>
                                    <i class="fas fa-chevron-right building-arrow"></i>
                                </div>
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
                        <div class="directions-header">
                            <h4><i class="fas fa-route"></i> 路線規劃</h4>
                            <button id="clear-directions-btn" class="clear-directions-btn" title="清除路線">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- 起終點輸入框 -->
                        <div class="directions-inputs">
                            <div class="direction-input-group">
                                <div class="input-icon start-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <input type="text" 
                                       id="directions-origin" 
                                       class="direction-input" 
                                       placeholder="選擇起點（或使用我的位置）"
                                       autocomplete="off">
                                <button class="use-location-btn" id="use-current-location-btn" title="使用我的位置">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                            </div>
                            <div class="direction-input-group">
                                <div class="input-icon end-icon">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <input type="text" 
                                       id="directions-destination" 
                                       class="direction-input" 
                                       placeholder="選擇終點"
                                       autocomplete="off">
                                <button class="swap-locations-btn" id="swap-locations-btn" title="交換起終點">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 交通方式選擇 -->
                        <div class="travel-modes">
                            <button class="travel-mode-btn active" data-mode="DRIVING" title="開車">
                                <i class="fas fa-car"></i>
                                <span>開車</span>
                            </button>
                            <button class="travel-mode-btn" data-mode="DRIVING" data-vehicle="motorcycle" title="摩托車">
                                <i class="fas fa-motorcycle"></i>
                                <span>摩托車</span>
                            </button>
                            <button class="travel-mode-btn" data-mode="WALKING" title="步行">
                                <i class="fas fa-walking"></i>
                                <span>步行</span>
                            </button>
                            <button class="travel-mode-btn" data-mode="TRANSIT" title="大眾運輸">
                                <i class="fas fa-bus"></i>
                                <span>大眾運輸</span>
                            </button>
                            <button class="travel-mode-btn" data-mode="BICYCLING" title="騎自行車">
                                <i class="fas fa-bicycle"></i>
                                <span>自行車</span>
                            </button>
                        </div>
                        
                        <!-- 路線選項列表 -->
                        <div id="routes-list" class="routes-list"></div>
                        
                        <!-- 路線詳細步驟 -->
                        <div id="directions-content" class="directions-content"></div>
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

    <!-- 校園平面圖模態框 -->
    <div id="campus-map-modal" class="campus-map-modal">
        <div class="modal-overlay"></div>
        <img id="campus-map-image" src="assets/images/campus_map.png" alt="校園平面圖" 
             onerror="console.error('圖片載入失敗:', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';" />
        <div id="image-error-message" style="display: none; position: relative; z-index: 10001; background: white; padding: 40px; border-radius: 8px; text-align: center; max-width: 500px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #f39c12; margin-bottom: 20px;"></i>
            <h3>圖片載入失敗</h3>
            <p>請確認圖片文件已放置在：<br><code>frontend/assets/images/campus_map.png</code></p>
            <p style="color: #666; font-size: 0.9rem; margin-top: 10px;">或將圖片路徑更新為正確的位置</p>
        </div>
        <button id="close-campus-map-modal" class="close-modal-btn">
            <i class="fas fa-times"></i>
        </button>
    </div>

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
    <script>
        // 確保在 DOM 載入後檢查按鈕
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('show-campus-map-btn');
            console.log('DOM 載入完成，檢查校園平面圖按鈕:', btn ? '找到' : '未找到');
            
            // 如果 CampusMap 實例已創建，確保事件監聽器已設置
            if (typeof campusMap !== 'undefined' && campusMap) {
                console.log('CampusMap 實例存在');
            } else {
                console.warn('CampusMap 實例尚未創建');
            }
            
            // 檢查 URL 參數，如果包含 show_nearby=true，自動顯示附近餐廳
            const urlParams = new URLSearchParams(window.location.search);
            const showNearby = urlParams.get('show_nearby');
            const restaurantLat = urlParams.get('lat');
            const restaurantLng = urlParams.get('lng');
            const restaurantName = urlParams.get('restaurant');
            
            if (showNearby === 'true') {
                // 等待地圖初始化完成後再觸發
                const checkAndShowRestaurants = setInterval(function() {
                    if (typeof campusMap !== 'undefined' && campusMap && campusMap.map) {
                        clearInterval(checkAndShowRestaurants);
                        
                        // 如果有餐廳座標，先將地圖中心移動到該位置
                        if (restaurantLat && restaurantLng) {
                            const lat = parseFloat(restaurantLat);
                            const lng = parseFloat(restaurantLng);
                            if (!isNaN(lat) && !isNaN(lng)) {
                                campusMap.map.setCenter({ lat: lat, lng: lng });
                                campusMap.map.setZoom(15);
                                
                                // 添加餐廳標記
                                if (restaurantName) {
                                    new google.maps.Marker({
                                        position: { lat: lat, lng: lng },
                                        map: campusMap.map,
                                        title: restaurantName,
                                        icon: {
                                            url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                                        }
                                    });
                                }
                            }
                        }
                        
                        // 觸發顯示附近餐廳
                        const showRestaurantsBtn = document.getElementById('show-restaurants-btn');
                        if (showRestaurantsBtn) {
                            // 延遲一點時間確保地圖已完全載入
                            setTimeout(function() {
                                showRestaurantsBtn.click();
                            }, 500);
                        }
                    }
                }, 100);
                
                // 設置超時，避免無限等待
                setTimeout(function() {
                    clearInterval(checkAndShowRestaurants);
                }, 10000);
            }
        });
    </script>
</body>
</html>
