/**
 * 康寧大學校園地圖 JavaScript
 * 整合 Google Maps API 功能 - Google Maps 風格版本
 */

class CampusMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.restaurantMarkers = [];
        this.restaurantMarkerMap = new Map(); // 使用 Map 來存儲餐廳到標記的映射
        this.directionsService = null;
        this.directionsRenderer = null;
        this.placesService = null;
        this.autocompleteService = null;
        this.geocoder = null;
        this.currentMode = 'driving';
        this.isMotorcycle = false;
        this.currentInfoWindow = null; // 保存當前打開的 InfoWindow
        this.campusLocation = {
            'name': '康寧大學台北校區',
            'address': '台北市內湖區康寧路三段75巷137號',
            'lat': 25.07575358359577,
            'lng': 121.60949282881778,
            'description': '康寧大學台北校區'
        };
        // 第一教學大樓 (C棟) 位置
        this.firstTeachingBuildingLocation = {
            'lat': 25.076132980674792,
            'lng': 121.61012050007541,
            'name': '第一教學大樓 (C棟)'
        };
        // 第二教學大樓 (E棟) 位置
        this.secondTeachingBuildingLocation = {
            'lat': 25.075602118954585,
            'lng': 121.61005748369553,
            'name': '第二教學大樓 (E棟)'
        };
        // 行政大樓 (A棟) 位置
        this.administrativeBuildingLocation = {
            'lat': 25.075479266366344,
            'lng': 121.60968101654407,
            'name': '行政大樓 (A棟)'
        };
        // 野聲館 位置
        this.yeshengHallLocation = {
            'lat': 25.076506287137924,
            'lng': 121.61032460516375,
            'name': '野聲館'
        };
        // 慈暉樓 位置
        this.cihuiBuildingLocation = {
            'lat': 25.076098148747832,
            'lng': 121.60976402349822,
            'name': '慈暉樓'
        };
        this.sidePanelVisible = true;
        this.restaurants = [];
        this.originAutocomplete = null;
        this.destinationAutocomplete = null;
        this.directionsUpdateTimeout = null;
        this.routeMarkers = [];
        
        this.init();
    }

    async init() {
        try {
            console.log('CampusMap init() 開始執行');
            // 初始化地圖
            this.initMap();
            
            // 延遲設置事件監聽器，確保 DOM 完全準備好
            setTimeout(() => {
                console.log('準備設置事件監聽器，readyState:', document.readyState);
                this.setupEventListeners();
            }, 100);
            
            // 初始化側邊面板
            this.initSidePanel();
            
            // 隱藏載入畫面
            this.hideLoading();
            
        } catch (error) {
            console.error('地圖初始化失敗:', error);
            this.showError('地圖載入失敗，請重新整理頁面');
        }
    }

    initMap() {
        // 檢查是否有 API Key
        if (!GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY.trim() === '') {
            console.error('Google Maps API Key 未設定');
            this.showError('Google Maps API Key 未設定，請聯繫管理員');
            const loading = document.getElementById('map-loading');
            const staticMap = document.getElementById('static-map');
            if (loading) loading.style.display = 'none';
            if (staticMap) staticMap.style.display = 'flex';
            return;
        }

        // 檢查地圖容器是否存在
        const mapElement = document.getElementById('map');
        if (!mapElement) {
            console.error('找不到地圖容器 #map');
            this.showError('找不到地圖容器');
            return;
        }

        console.log('開始初始化地圖，位置:', this.campusLocation);

        try {
            // 創建地圖，使用更現代的樣式，中心設為第一教學大樓位置
            this.map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: this.firstTeachingBuildingLocation.lat, lng: this.firstTeachingBuildingLocation.lng },
                zoom: 18,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                streetViewControl: true,
                mapTypeControl: true,
                fullscreenControl: true,
                zoomControl: true,
                scaleControl: true,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'on' }]
                    }
                ],
                disableDefaultUI: false
            });
            console.log('地圖創建成功！');
        } catch (error) {
            console.error('創建地圖時發生錯誤:', error);
            this.showError('創建地圖失敗: ' + error.message);
            const loading = document.getElementById('map-loading');
            const staticMap = document.getElementById('static-map');
            if (loading) loading.style.display = 'none';
            if (staticMap) staticMap.style.display = 'flex';
            return;
        }

        // 初始化服務
        this.directionsService = new google.maps.DirectionsService();
        this.directionsRenderer = new google.maps.DirectionsRenderer({
            draggable: false,
            suppressMarkers: true, // 使用自定義標記，不顯示默認標記
            map: this.map,
            preserveViewport: false, // 允許自動調整視圖
            polylineOptions: {
                strokeColor: '#4285f4',
                strokeWeight: 5,
                strokeOpacity: 0.8,
                zIndex: 1
            }
        });
        
        console.log('DirectionsRenderer 已初始化，地圖:', this.map);

        // 初始化 Places 服務
        this.placesService = new google.maps.places.PlacesService(this.map);
        this.autocompleteService = new google.maps.places.AutocompleteService();
        this.geocoder = new google.maps.Geocoder();

        // 添加校園標記
        this.addCampusMarker();
        
        // 添加第一教學大樓 (C棟) 標記
        this.addFirstTeachingBuildingMarker();
        
        // 添加第二教學大樓 (E棟) 標記
        this.addSecondTeachingBuildingMarker();
        
        // 添加行政大樓 (A棟) 標記
        this.addAdministrativeBuildingMarker();
        
        // 添加野聲館 標記
        this.addYeshengHallMarker();
        
        // 添加慈暉樓 標記
        this.addCihuiBuildingMarker();
        
        console.log('地圖初始化完成');
    }

    addCampusMarker() {
        const marker = new google.maps.Marker({
            position: { lat: this.campusLocation.lat, lng: this.campusLocation.lng },
            map: this.map,
            title: this.campusLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="50" height="50" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="25" cy="25" r="22" fill="#e74c3c" stroke="white" stroke-width="3"/>
                        <text x="25" y="32" text-anchor="middle" fill="white" font-size="16" font-weight="bold">校</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(50, 50),
                anchor: new google.maps.Point(25, 50)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px;">${this.campusLocation.name}</h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> ${this.campusLocation.address}
                    </p>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-phone" style="color: #667eea;"></i> (02) 2632-1181
                    </p>
                    <div style="margin-top: 12px;">
                        <button onclick="campusMap.startDirectionsToDestination('${this.campusLocation.address}')" 
                                style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 13px; width: 100%;">
                            <i class="fas fa-route"></i> 規劃路線
                        </button>
                    </div>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }

    addFirstTeachingBuildingMarker() {
        const buildingLocation = this.firstTeachingBuildingLocation;

        // 使用 Google Maps 風格的紅色標記（標準水滴形狀）
        const marker = new google.maps.Marker({
            position: { lat: buildingLocation.lat, lng: buildingLocation.lng },
            map: this.map,
            title: buildingLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0 C7.163 0, 0 7.163, 0 16 C0 24.837, 16 40, 16 40 C16 40, 32 24.837, 32 16 C32 7.163, 24.837 0, 16 0 Z" fill="#EA4335" stroke="white" stroke-width="1.5"/>
                        <circle cx="16" cy="16" r="6" fill="white"/>
                        <text x="16" y="20" text-anchor="middle" fill="#EA4335" font-size="11" font-weight="bold" font-family="Arial, sans-serif">C</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 40),
                anchor: new google.maps.Point(16, 40)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #EA4335; margin-right: 8px;"></i>${buildingLocation.name}
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> 座標：${buildingLocation.lat}, ${buildingLocation.lng}
                    </p>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }

    addSecondTeachingBuildingMarker() {
        const buildingLocation = this.secondTeachingBuildingLocation;

        // 使用 Google Maps 風格的紅色標記（標準水滴形狀）
        const marker = new google.maps.Marker({
            position: { lat: buildingLocation.lat, lng: buildingLocation.lng },
            map: this.map,
            title: buildingLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0 C7.163 0, 0 7.163, 0 16 C0 24.837, 16 40, 16 40 C16 40, 32 24.837, 32 16 C32 7.163, 24.837 0, 16 0 Z" fill="#EA4335" stroke="white" stroke-width="1.5"/>
                        <circle cx="16" cy="16" r="6" fill="white"/>
                        <text x="16" y="20" text-anchor="middle" fill="#EA4335" font-size="11" font-weight="bold" font-family="Arial, sans-serif">E</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 40),
                anchor: new google.maps.Point(16, 40)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #EA4335; margin-right: 8px;"></i>${buildingLocation.name}
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> 座標：${buildingLocation.lat}, ${buildingLocation.lng}
                    </p>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }

    addAdministrativeBuildingMarker() {
        const buildingLocation = this.administrativeBuildingLocation;

        // 使用 Google Maps 風格的紅色標記（標準水滴形狀）
        const marker = new google.maps.Marker({
            position: { lat: buildingLocation.lat, lng: buildingLocation.lng },
            map: this.map,
            title: buildingLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0 C7.163 0, 0 7.163, 0 16 C0 24.837, 16 40, 16 40 C16 40, 32 24.837, 32 16 C32 7.163, 24.837 0, 16 0 Z" fill="#EA4335" stroke="white" stroke-width="1.5"/>
                        <circle cx="16" cy="16" r="6" fill="white"/>
                        <text x="16" y="20" text-anchor="middle" fill="#EA4335" font-size="11" font-weight="bold" font-family="Arial, sans-serif">A</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 40),
                anchor: new google.maps.Point(16, 40)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #EA4335; margin-right: 8px;"></i>${buildingLocation.name}
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> 座標：${buildingLocation.lat}, ${buildingLocation.lng}
                    </p>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }

    addYeshengHallMarker() {
        const buildingLocation = this.yeshengHallLocation;

        // 使用 Google Maps 風格的紅色標記（標準水滴形狀）
        const marker = new google.maps.Marker({
            position: { lat: buildingLocation.lat, lng: buildingLocation.lng },
            map: this.map,
            title: buildingLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0 C7.163 0, 0 7.163, 0 16 C0 24.837, 16 40, 16 40 C16 40, 32 24.837, 32 16 C32 7.163, 24.837 0, 16 0 Z" fill="#EA4335" stroke="white" stroke-width="1.5"/>
                        <circle cx="16" cy="16" r="6" fill="white"/>
                        <text x="16" y="20" text-anchor="middle" fill="#EA4335" font-size="10" font-weight="bold" font-family="Arial, sans-serif">野</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 40),
                anchor: new google.maps.Point(16, 40)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #EA4335; margin-right: 8px;"></i>${buildingLocation.name}
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> 座標：${buildingLocation.lat}, ${buildingLocation.lng}
                    </p>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }

    addCihuiBuildingMarker() {
        const buildingLocation = this.cihuiBuildingLocation;

        // 使用 Google Maps 風格的紅色標記（標準水滴形狀）
        const marker = new google.maps.Marker({
            position: { lat: buildingLocation.lat, lng: buildingLocation.lng },
            map: this.map,
            title: buildingLocation.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 0 C7.163 0, 0 7.163, 0 16 C0 24.837, 16 40, 16 40 C16 40, 32 24.837, 32 16 C32 7.163, 24.837 0, 16 0 Z" fill="#EA4335" stroke="white" stroke-width="1.5"/>
                        <circle cx="16" cy="16" r="6" fill="white"/>
                        <text x="16" y="20" text-anchor="middle" fill="#EA4335" font-size="10" font-weight="bold" font-family="Arial, sans-serif">慈</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 40),
                anchor: new google.maps.Point(16, 40)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 15px; max-width: 280px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-building" style="color: #EA4335; margin-right: 8px;"></i>${buildingLocation.name}
                    </h3>
                    <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                        <i class="fas fa-map-marker-alt" style="color: #667eea;"></i> 座標：${buildingLocation.lat}, ${buildingLocation.lng}
                    </p>
                </div>
            `
        });

        marker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        });

        this.markers.push(marker);
    }


    setupEventListeners() {
        console.log('setupEventListeners 被調用');
        console.log('檢查按鈕是否存在:', {
            'show-restaurants-btn': !!document.getElementById('show-restaurants-btn'),
            'show-campus-info-btn': !!document.getElementById('show-campus-info-btn'),
            'get-directions-btn': !!document.getElementById('get-directions-btn'),
            'show-campus-map-btn': !!document.getElementById('show-campus-map-btn')
        });

        // 顯示附近餐廳按鈕
        const showRestaurantsBtn = document.getElementById('show-restaurants-btn');
        if (showRestaurantsBtn) {
            showRestaurantsBtn.addEventListener('click', () => {
                this.showNearbyRestaurants();
            });
        }

        // 顯示校園資訊按鈕
        const showCampusInfoBtn = document.getElementById('show-campus-info-btn');
        if (showCampusInfoBtn) {
            showCampusInfoBtn.addEventListener('click', () => {
                this.showCampusInfo();
                this.updateActiveButton(showCampusInfoBtn);
            });
        }

        // 規劃路線按鈕
        const getDirectionsBtn = document.getElementById('get-directions-btn');
        if (getDirectionsBtn) {
            getDirectionsBtn.addEventListener('click', () => {
                this.promptForDirections();
                this.updateActiveButton(getDirectionsBtn);
            });
        }
        
        // 更新餐廳按鈕狀態
        if (showRestaurantsBtn) {
            showRestaurantsBtn.addEventListener('click', () => {
                this.updateActiveButton(showRestaurantsBtn);
            });
        }


        // 關閉側邊面板按鈕
        // 校園平面圖按鈕
        const showCampusMapBtn = document.getElementById('show-campus-map-btn');
        if (showCampusMapBtn) {
            console.log('校園平面圖按鈕找到，設置事件監聽器');
            showCampusMapBtn.addEventListener('click', (e) => {
                console.log('校園平面圖按鈕被點擊');
                e.preventDefault();
                e.stopPropagation();
                this.showCampusMap();
                this.updateActiveButton(showCampusMapBtn);
            });
        } else {
            console.warn('校園平面圖按鈕未找到！');
        }

        // 關閉校園平面圖模態框
        const closeCampusMapModal = document.getElementById('close-campus-map-modal');
        if (closeCampusMapModal) {
            closeCampusMapModal.addEventListener('click', () => {
                this.closeCampusMapModal();
            });
        }

        // 點擊模態框背景關閉
        const campusMapModal = document.getElementById('campus-map-modal');
        if (campusMapModal) {
            const modalOverlay = campusMapModal.querySelector('.modal-overlay');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', () => {
                    this.closeCampusMapModal();
                });
            }
        }

        // ESC 鍵關閉模態框
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeCampusMapModal();
            }
        });
    }


    async showNearbyRestaurants() {
        // 顯示側邊面板
        this.showSidePanel();
        
        // 切換到餐廳列表
        document.getElementById('campus-info').style.display = 'none';
        document.getElementById('directions-info').style.display = 'none';
        const restaurantsList = document.getElementById('restaurants-list');
        restaurantsList.style.display = 'block';
        
        // 更新面板標題
        document.getElementById('panel-title').innerHTML = '<i class="fas fa-utensils"></i> 附近餐廳';
        
        // 顯示載入中
        const restaurantsContent = document.getElementById('restaurants-content');
        restaurantsContent.innerHTML = '<p class="loading-text">正在搜尋附近餐廳...</p>';

        // 清除之前的餐廳標記
        this.clearRestaurantMarkers();

        try {
            // 同時獲取 Google Maps 餐廳和推薦餐廳
            console.log('🍽️ 開始獲取餐廳數據...');
            const [googleRestaurants, recommendedRestaurants] = await Promise.all([
                this.fetchGoogleRestaurants(),
                this.fetchRecommendedRestaurants()
            ]);
            
            console.log('📍 Google Maps 餐廳數量:', googleRestaurants.length);
            console.log('⭐ 推薦餐廳數量:', recommendedRestaurants.length);
            
            if (recommendedRestaurants.length > 0) {
                console.log('⭐ 推薦餐廳列表:', recommendedRestaurants.map(r => r.name));
            } else {
                console.warn('⚠️ 沒有找到推薦餐廳，請確認：');
                console.warn('  1. 是否有在留言板發布「推薦餐廳」類型的留言');
                console.warn('  2. 留言是否包含餐廳名稱或標題');
                console.warn('  3. 可以打開瀏覽器開發者工具查看 API 響應');
            }
            
            // 合併餐廳列表，優先顯示推薦餐廳，去除重複
            const mergedRestaurants = this.mergeRestaurants(googleRestaurants, recommendedRestaurants);
            console.log('✅ 合併後餐廳數量:', mergedRestaurants.length);
            console.log('⭐ 合併後推薦餐廳數量:', mergedRestaurants.filter(r => r.is_recommended).length);
            
            // 處理需要地理編碼的推薦餐廳
            await this.geocodeRecommendedRestaurants(mergedRestaurants);
            
            // 獲取每個餐廳的詳細信息（包括外送評價）
            await this.fetchRestaurantDetails(mergedRestaurants);
            
            // 顯示餐廳列表
            this.displayRestaurants(mergedRestaurants);
            this.addRestaurantMarkers(mergedRestaurants);
            
        } catch (error) {
            console.error('❌ 搜尋餐廳失敗:', error);
            console.error('錯誤詳情:', error.stack);
            restaurantsContent.innerHTML = '<p class="error-text">搜尋餐廳時發生錯誤，請稍後再試。請打開瀏覽器控制台查看詳細錯誤信息。</p>';
        }
    }
    
    // 獲取 Google Maps 餐廳
    fetchGoogleRestaurants() {
        return new Promise((resolve) => {
            const request = {
                location: new google.maps.LatLng(this.campusLocation.lat, this.campusLocation.lng),
                radius: 2000, // 2公里範圍
                type: 'restaurant'
            };

            this.placesService.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK && results) {
                    resolve(results);
                } else {
                    resolve([]);
                }
            });
        });
    }
    
    // 獲取推薦餐廳
    async fetchRecommendedRestaurants() {
        try {
            // 使用相對路徑，確保從 frontend 目錄正確訪問
            const apiUrl = 'api/get_recommended_restaurants.php';
            console.log('開始獲取推薦餐廳，API URL:', apiUrl);
            
            const response = await fetch(apiUrl);
            console.log('API 響應狀態:', response.status, response.statusText);
            
            if (!response.ok) {
                console.error('API 響應錯誤:', response.status, response.statusText);
                return [];
            }
            
            const data = await response.json();
            console.log('API 返回數據:', data);
            console.log('API 返回數據類型:', typeof data);
            console.log('data.success:', data.success);
            console.log('data.restaurants:', data.restaurants);
            console.log('data.restaurants 類型:', Array.isArray(data.restaurants));
            
            if (data.success && Array.isArray(data.restaurants)) {
                console.log('✓ 獲取到推薦餐廳:', data.restaurants.length, '間');
                if (data.restaurants.length > 0) {
                    console.log('推薦餐廳列表:', data.restaurants);
                } else {
                    console.warn('推薦餐廳列表為空');
                }
                return data.restaurants;
            } else {
                console.warn('API 返回失敗或沒有餐廳:', data);
                console.warn('錯誤詳情:', data.error || '未知錯誤');
                // 即使 success 為 false，也嘗試返回 restaurants 陣列（如果存在）
                if (Array.isArray(data.restaurants)) {
                    console.log('雖然 success 為 false，但返回 restaurants 陣列:', data.restaurants.length, '間');
                    return data.restaurants;
                }
                return [];
            }
        } catch (error) {
            console.error('✗ 獲取推薦餐廳失敗:', error);
            console.error('錯誤詳情:', error.message, error.stack);
            return [];
        }
    }
    
    // 合併餐廳列表，去除重複
    mergeRestaurants(googleRestaurants, recommendedRestaurants) {
        const merged = [];
        const seenNames = new Set();
        const seenPlaces = new Set();
        
        console.log('開始合併餐廳列表...');
        console.log('推薦餐廳:', recommendedRestaurants);
        console.log('Google Maps 餐廳:', googleRestaurants);
        
        // 優先添加推薦餐廳（即使沒有座標也要添加，稍後會進行地理編碼）
        recommendedRestaurants.forEach(restaurant => {
            const name = restaurant.name ? restaurant.name.toLowerCase().trim() : '';
            const placeId = restaurant.place_id || '';
            
            // 推薦餐廳優先添加，即使名稱或 place_id 重複也要添加（因為是推薦的）
            if (name) {
                if (!seenNames.has(name) && !seenPlaces.has(placeId)) {
                    seenNames.add(name);
                    if (placeId) seenPlaces.add(placeId);
                    merged.push(restaurant);
                    console.log('添加推薦餐廳:', restaurant.name);
                } else {
                    // 即使重複，如果是推薦餐廳也優先添加（替換掉 Google Maps 的）
                    const existingIndex = merged.findIndex(r => 
                        (r.name && r.name.toLowerCase().trim() === name) || 
                        (r.place_id && r.place_id === placeId)
                    );
                    if (existingIndex >= 0 && !merged[existingIndex].is_recommended) {
                        console.log('用推薦餐廳替換 Google Maps 餐廳:', restaurant.name);
                        merged[existingIndex] = restaurant;
                    } else if (existingIndex < 0) {
                        merged.push(restaurant);
                        console.log('添加推薦餐廳（即使重複）:', restaurant.name);
                    }
                }
            }
        });
        
        // 添加 Google Maps 餐廳（排除已存在的）
        googleRestaurants.forEach(restaurant => {
            const name = restaurant.name ? restaurant.name.toLowerCase().trim() : '';
            const placeId = restaurant.place_id || '';
            
            if (name && !seenNames.has(name) && !seenPlaces.has(placeId)) {
                seenNames.add(name);
                if (placeId) seenPlaces.add(placeId);
                merged.push(restaurant);
            }
        });
        
        console.log('合併完成，總共:', merged.length, '間餐廳');
        console.log('推薦餐廳數量:', merged.filter(r => r.is_recommended).length);
        
        return merged;
    }

    // 為需要地理編碼的推薦餐廳進行地理編碼
    async geocodeRecommendedRestaurants(restaurants) {
        if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
        }
        
        const geocodePromises = restaurants.map((restaurant, index) => {
            return new Promise((resolve) => {
                // 如果是推薦餐廳且需要地理編碼
                if (restaurant.is_recommended && restaurant.needs_geocoding && restaurant.formatted_address) {
                    console.log('為推薦餐廳進行地理編碼:', restaurant.name, restaurant.formatted_address);
                    this.geocoder.geocode({ address: restaurant.formatted_address }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            const location = results[0].geometry.location;
                            // 正確更新座標（確保是數字格式，不是 LatLng 對象）
                            restaurant.geometry.location = {
                                lat: location.lat(),
                                lng: location.lng()
                            };
                            restaurant.needs_geocoding = false;
                            console.log('✓ 地理編碼成功:', restaurant.name, location.lat(), location.lng());
                        } else {
                            console.warn('✗ 地理編碼失敗:', restaurant.name, status);
                            // 地理編碼失敗時，標記為無效座標，稍後不會在地圖上顯示標記
                            restaurant.geometry.location = { lat: 0, lng: 0 };
                        }
                        resolve();
                    });
                } else {
                    // 如果不需要地理編碼，確保座標格式正確
                    if (restaurant.geometry && restaurant.geometry.location) {
                        const loc = restaurant.geometry.location;
                        // 如果是 Google Maps LatLng 對象，轉換為普通對象
                        if (typeof loc.lat === 'function') {
                            restaurant.geometry.location = {
                                lat: loc.lat(),
                                lng: loc.lng()
                            };
                        }
                    }
                    resolve();
                }
            });
        });
        
        await Promise.all(geocodePromises);
    }

    async fetchRestaurantDetails(restaurants) {
        // 為每個餐廳獲取詳細信息
        const detailPromises = restaurants.map((restaurant, index) => {
            return new Promise((resolve) => {
                const currentRestaurant = restaurants[index];
                let placeId = restaurant.place_id;
                
                // 如果是推薦餐廳且 place_id 是假 ID，嘗試通過名稱和地址搜尋
                if (restaurant.is_recommended && placeId && placeId.startsWith('recommended_')) {
                    // 嘗試通過名稱和地址搜尋 Google Places
                    const searchQuery = restaurant.name + ' ' + (restaurant.formatted_address || restaurant.vicinity || '');
                    console.log('🔍 嘗試搜尋推薦餐廳的 place_id:', restaurant.name, searchQuery);
                    
                    // 使用 TextSearch 方法搜尋餐廳
                    const textSearchRequest = {
                        query: searchQuery,
                        type: 'restaurant',
                        location: restaurant.geometry?.location ? 
                            new google.maps.LatLng(
                                typeof restaurant.geometry.location.lat === 'function' ? 
                                    restaurant.geometry.location.lat() : 
                                    restaurant.geometry.location.lat,
                                typeof restaurant.geometry.location.lng === 'function' ? 
                                    restaurant.geometry.location.lng() : 
                                    restaurant.geometry.location.lng
                            ) : 
                            new google.maps.LatLng(this.campusLocation.lat, this.campusLocation.lng),
                        radius: 5000 // 5公里範圍
                    };
                    
                    if (this.placesService && this.placesService.textSearch) {
                        this.placesService.textSearch(textSearchRequest, (results, status) => {
                            if (status === google.maps.places.PlacesServiceStatus.OK && results && results.length > 0) {
                                // 找到匹配的餐廳，使用第一個結果的 place_id
                                placeId = results[0].place_id;
                                console.log('✓ 找到推薦餐廳的 place_id:', placeId, results[0].name);
                                // 繼續獲取詳細資訊
                                this.fetchPlaceDetails(placeId, currentRestaurant, restaurants, index, resolve);
                            } else {
                                console.warn('✗ 無法找到推薦餐廳的 place_id:', restaurant.name, status);
                                resolve();
                            }
                        });
                    } else {
                        console.warn('PlacesService.textSearch 方法不可用');
                        resolve();
                    }
                    return; // 已經處理，不需要繼續
                }
                
                // 如果有有效的 place_id，直接獲取詳細資訊
                if (placeId && !placeId.startsWith('recommended_')) {
                    this.fetchPlaceDetails(placeId, currentRestaurant, restaurants, index, resolve);
                } else {
                    resolve();
                }
            });
        });
        
        await Promise.all(detailPromises);
    }
    
    // 獲取 Google Place 的詳細資訊
    fetchPlaceDetails(placeId, currentRestaurant, restaurants, index, resolve) {
        const detailRequest = {
            placeId: placeId,
            fields: ['name', 'rating', 'user_ratings_total', 'price_level', 'types', 'opening_hours', 'formatted_address', 'vicinity', 'reviews']
        };
        
        this.placesService.getDetails(detailRequest, (place, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && place) {
                // 合併詳細信息到餐廳對象（保留原有的推薦餐廳資訊）
                restaurants[index] = {
                    ...currentRestaurant,
                    ...place,
                    // 更新 place_id（如果通過搜尋找到新的）
                    place_id: placeId,
                    // 保留推薦餐廳的特殊標記
                    is_recommended: currentRestaurant.is_recommended || false,
                    recommendation_id: currentRestaurant.recommendation_id,
                    recommendation_title: currentRestaurant.recommendation_title,
                    recommendation_content: currentRestaurant.recommendation_content,
                    recommendation_author: currentRestaurant.recommendation_author,
                    recommendation_created_at: currentRestaurant.recommendation_created_at,
                    recommendation_view_count: currentRestaurant.recommendation_view_count,
                    recommendation_like_count: currentRestaurant.recommendation_like_count,
                    // 檢查是否有外送服務
                    hasDelivery: place.types && place.types.includes('meal_delivery'),
                    // 獲取外送相關評價
                    deliveryRating: this.getDeliveryRating(place)
                };
                console.log('✓ 成功獲取推薦餐廳營業時間:', currentRestaurant.name, place.opening_hours?.open_now);
            } else {
                console.warn('獲取餐廳詳細資訊失敗:', currentRestaurant.name, status);
            }
            resolve();
        });
    }
    
    getDeliveryRating(place) {
        // 檢查是否有外送服務
        const hasDelivery = place.types && place.types.includes('meal_delivery');
        if (!hasDelivery) return null;
        
        // 如果有評價，使用餐廳的總體評價作為外送評價的參考
        // 實際應用中，可以整合第三方外送平台的 API
        if (place.rating && place.user_ratings_total) {
            return {
                rating: place.rating,
                totalReviews: place.user_ratings_total,
                // 外送評價通常略低於店內評價，這裡做一個簡單的調整
                deliveryRating: Math.max(0, place.rating - 0.2).toFixed(1)
            };
        }
        
        return null;
    }

    displayRestaurants(restaurants) {
        // 保存餐廳列表到實例變數，以便點擊時使用
        // 對餐廳列表進行排序，推薦餐廳優先顯示
        const sortedRestaurants = [...restaurants].sort((a, b) => {
            // 推薦餐廳排在前面
            if (a.is_recommended && !b.is_recommended) return -1;
            if (!a.is_recommended && b.is_recommended) return 1;
            // 如果都是推薦餐廳或都不是，按評分排序（評分高的在前）
            const ratingA = a.rating || 0;
            const ratingB = b.rating || 0;
            return ratingB - ratingA;
        });
        
        this.restaurants = sortedRestaurants;
        
        const restaurantsContent = document.getElementById('restaurants-content');
        const restaurantsCount = document.getElementById('restaurants-count');
        
        if (restaurantsCount) {
            restaurantsCount.textContent = `(${sortedRestaurants.length} 間)`;
        }

        if (sortedRestaurants.length === 0) {
            restaurantsContent.innerHTML = '<p class="error-text">附近沒有找到餐廳。</p>';
            return;
        }

        let html = '<div class="restaurants-items">';
        
        sortedRestaurants.forEach((restaurant, index) => {
            const rating = restaurant.rating || 0;
            const priceLevel = restaurant.price_level || 0;
            const priceSymbols = '$'.repeat(priceLevel);
            
            // 判斷營業狀態：只有當有 opening_hours 資訊時才顯示
            const hasOpeningHours = restaurant.opening_hours && restaurant.opening_hours.open_now !== undefined;
            const isOpen = hasOpeningHours ? restaurant.opening_hours.open_now : null; // null 表示未知
            
            const hasDelivery = restaurant.hasDelivery || (restaurant.types && restaurant.types.includes('meal_delivery'));
            const deliveryRating = restaurant.deliveryRating;
            
            const isRecommended = restaurant.is_recommended || false;
            
            // 構建營業狀態標籤
            let openingStatusBadge = '';
            if (isOpen === true) {
                openingStatusBadge = '<span class="open-badge">營業中</span>';
            } else if (isOpen === false) {
                openingStatusBadge = '<span class="closed-badge">已打烊</span>';
            }
            // 如果 isOpen === null，不顯示營業狀態標籤（因為未知）
            
            html += `
                <div class="restaurant-item" data-restaurant-index="${index}" ${isRecommended ? 'data-recommended="true"' : ''}>
                    <div class="restaurant-header">
                        <h5>${restaurant.name || '未命名餐廳'}</h5>
                        <div class="restaurant-badges">
                            ${isRecommended ? '<span class="recommended-badge" style="background: #667eea; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-right: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
                            ${openingStatusBadge}
                            ${hasDelivery ? '<span class="delivery-badge"><i class="fas fa-motorcycle"></i> 外送</span>' : ''}
                        </div>
                    </div>
                    <div class="restaurant-rating">
                        <div class="rating-group">
                            <span class="stars">${this.getStarRating(rating)}</span>
                            <span class="rating-text">${rating.toFixed(1)}</span>
                            ${restaurant.user_ratings_total ? `<span class="review-count">(${restaurant.user_ratings_total})</span>` : ''}
                        </div>
                        ${priceSymbols ? `<span class="price-level">${priceSymbols}</span>` : ''}
                    </div>
                    ${hasDelivery && deliveryRating ? `
                        <div class="delivery-rating">
                            <i class="fas fa-motorcycle"></i>
                            <span class="delivery-label">外送評價：</span>
                            <span class="delivery-stars">${this.getStarRating(parseFloat(deliveryRating.deliveryRating))}</span>
                            <span class="delivery-rating-text">${deliveryRating.deliveryRating}</span>
                        </div>
                    ` : ''}
                    <p class="restaurant-address">
                        <i class="fas fa-map-marker-alt"></i> ${restaurant.vicinity || restaurant.formatted_address || '地址未知'}
                    </p>
                    ${restaurant.types ? `
                        <div class="restaurant-types">
                            ${restaurant.types.slice(0, 3).map(type => 
                                `<span class="type-tag">${this.translateType(type)}</span>`
                            ).join('')}
                        </div>
                    ` : ''}
                    <div class="restaurant-actions" style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                        ${restaurant.place_id ? `
                            <button class="view-on-google-btn" onclick="event.stopPropagation(); window.open('https://www.google.com/maps/place/?q=place_id:${restaurant.place_id}', '_blank');" style="flex: 1; padding: 8px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fab fa-google"></i> 在 Google Maps 查看
                            </button>
                        ` : restaurant.geometry && restaurant.geometry.location ? `
                            <button class="view-on-google-btn" onclick="event.stopPropagation(); window.open('https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(restaurant.name)}+${encodeURIComponent(restaurant.vicinity || restaurant.formatted_address || '')}', '_blank');" style="flex: 1; padding: 8px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                                <i class="fab fa-google"></i> 在 Google Maps 查看
                            </button>
                        ` : ''}
                        ${restaurant.place_id ? `
                            <button class="view-reviews-btn" onclick="event.stopPropagation(); if(window.campusMap) window.campusMap.loadGoogleReviews('${restaurant.place_id}', ${index});" style="flex: 1; padding: 8px 12px; background: var(--hover-bg, #f7f9fa); border: 1px solid var(--border-color, #e1e8ed); border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease; color: var(--text-color, #000);">
                                <i class="fas fa-comments"></i> 查看評論
                            </button>
                        ` : ''}
                    </div>
                    ${restaurant.place_id ? `
                        <div id="reviews-${index}" class="google-reviews-container" style="display: none; margin-top: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e8ed; max-height: 700px; overflow-y: auto;">
                            <div class="reviews-loading" style="text-align: center; padding: 20px; color: #666;">
                                <i class="fas fa-spinner fa-spin"></i> 載入評論中...
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        });

        html += '</div>';
        restaurantsContent.innerHTML = html;
        
        // 添加點擊事件監聽器（使用事件委派）
        const restaurantItems = restaurantsContent.querySelectorAll('.restaurant-item');
        restaurantItems.forEach((item, index) => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const restaurantIndex = parseInt(item.dataset.restaurantIndex);
                if (!isNaN(restaurantIndex)) {
                    this.selectRestaurant(restaurantIndex);
                }
            });
        });
    }

    getStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '★'.repeat(fullStars);
        if (hasHalfStar) stars += '☆';
        return stars;
    }

    translateType(type) {
        const typeMap = {
            'restaurant': '餐廳',
            'food': '美食',
            'cafe': '咖啡廳',
            'meal_takeaway': '外帶',
            'meal_delivery': '外送',
            'bakery': '麵包店',
            'bar': '酒吧',
            'meal_delivery': '外送'
        };
        return typeMap[type] || type;
    }

    addRestaurantMarkers(restaurants) {
        restaurants.forEach((restaurant, index) => {
            // 檢查餐廳是否有有效的座標
            const location = restaurant.geometry?.location;
            if (!location) {
                // 如果是推薦餐廳，仍然顯示在列表中，只是不顯示地圖標記
                if (restaurant.is_recommended) {
                    console.log('推薦餐廳無座標，將顯示在列表中但不顯示地圖標記:', restaurant.name);
                } else {
                    console.warn('餐廳沒有座標，跳過標記:', restaurant.name);
                }
                return;
            }
            
            // 檢查座標是否有效（不是 0,0）
            const lat = typeof location.lat === 'function' ? location.lat() : location.lat;
            const lng = typeof location.lng === 'function' ? location.lng() : location.lng;
            
            // 檢查座標是否為 null（表示沒有座標）
            if (lat === null || lng === null) {
                if (restaurant.is_recommended) {
                    console.log('推薦餐廳座標為 null，將顯示在列表中但不顯示地圖標記:', restaurant.name);
                } else {
                    console.warn('餐廳座標為 null，跳過標記:', restaurant.name);
                }
                return;
            }
            
            // 檢查座標是否有效（不是 0,0 且不是 NaN）
            if ((lat === 0 && lng === 0) || !lat || !lng || isNaN(lat) || isNaN(lng)) {
                console.warn('餐廳座標無效，跳過標記:', restaurant.name, '座標:', lat, lng);
                return;
            }
            
            // 檢查座標是否在校園位置（可能是預設值，應該跳過）
            const campusLat = 25.076132980674792;
            const campusLng = 121.61012050007541;
            const distance = Math.sqrt(Math.pow(lat - campusLat, 2) + Math.pow(lng - campusLng, 2));
            // 如果座標非常接近校園位置（距離小於 0.0001 度，約 11 米），且是推薦餐廳但沒有地址，可能是預設值
            if (restaurant.is_recommended && distance < 0.0001 && !restaurant.formatted_address) {
                console.warn('推薦餐廳座標可能是預設值（校園位置），跳過標記:', restaurant.name);
                return;
            }
            
            const isRecommended = restaurant.is_recommended || false;
            // 確保 position 是正確的格式（Google Maps 可以接受 {lat, lng} 對象）
            const markerPosition = { lat: lat, lng: lng };
            
            const marker = new google.maps.Marker({
                position: markerPosition,
                map: this.map,
                title: restaurant.name,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="35" height="35" viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="17.5" cy="17.5" r="15" fill="${isRecommended ? '#ff6b35' : '#27ae60'}" stroke="white" stroke-width="2"/>
                            <text x="17.5" y="22" text-anchor="middle" fill="white" font-size="14" font-weight="bold">${isRecommended ? '⭐' : '餐'}</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(35, 35),
                    anchor: new google.maps.Point(17.5, 35)
                },
                zIndex: isRecommended ? 1000 : 100
            });
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 12px; max-width: 250px;">
                        <h4 style="margin: 0 0 8px 0; color: #333; font-size: 16px;">
                            ${restaurant.name || '未命名餐廳'}
                            ${isRecommended ? '<span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: 600; margin-left: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
                        </h4>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                            <i class="fas fa-map-marker-alt" style="color: #27ae60;"></i> ${restaurant.vicinity || restaurant.formatted_address || '地址未知'}
                        </p>
                        ${restaurant.rating ? `
                            <p style="margin: 0; color: #666; font-size: 13px;">
                                <i class="fas fa-star" style="color: #f39c12;"></i> ${restaurant.rating.toFixed(1)}
                            </p>
                        ` : ''}
                        ${isRecommended && restaurant.recommendation_id ? `
                            <button onclick="window.location.href='restaurant_reviews.php?message_id=${restaurant.recommendation_id}&restaurant=${encodeURIComponent(restaurant.name)}'" 
                                    style="margin-top: 8px; padding: 6px 12px; background: #ff6b35; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-comments"></i> 查看推薦
                            </button>
                        ` : ''}
                    </div>
                `
            });

            marker.addListener('click', () => {
                // 關閉之前打開的 InfoWindow
                if (this.currentInfoWindow) {
                    this.currentInfoWindow.close();
                }
                // 打開新的 InfoWindow 並保存引用
                infoWindow.open(this.map, marker);
                this.currentInfoWindow = infoWindow;
                // 如果是推薦餐廳，直接顯示詳情；否則選擇餐廳
                // 使用餐廳在列表中的實際索引
                const actualIndex = this.restaurants.findIndex(r => {
                    if (restaurant.is_recommended && restaurant.recommendation_id) {
                        return r.is_recommended && r.recommendation_id === restaurant.recommendation_id;
                    } else {
                        return r.place_id === restaurant.place_id;
                    }
                });
                if (actualIndex >= 0) {
                    if (restaurant.is_recommended && restaurant.recommendation_id) {
                        this.showRestaurantDetails(restaurant, actualIndex);
                    } else {
                        this.selectRestaurant(actualIndex);
                    }
                }
            });

            this.restaurantMarkers.push(marker);
            // 使用餐廳的唯一標識符作為 key 存儲標記映射
            const markerKey = restaurant.is_recommended && restaurant.recommendation_id 
                ? `recommended_${restaurant.recommendation_id}` 
                : restaurant.place_id || `restaurant_${index}`;
            this.restaurantMarkerMap.set(markerKey, marker);
        });
    }

    clearRestaurantMarkers() {
        this.restaurantMarkers.forEach(marker => marker.setMap(null));
        this.restaurantMarkers = [];
        this.restaurantMarkerMap.clear();
    }

    selectRestaurant(index) {
        if (!this.restaurants || !this.restaurants[index]) return;
        
        const restaurant = this.restaurants[index];
        
        // 移動地圖到餐廳位置（使用平滑動畫）
        if (restaurant.geometry && restaurant.geometry.location) {
            const location = restaurant.geometry.location;
            let lat, lng;
            
            // 處理不同的座標格式
            if (typeof location.lat === 'function') {
                // Google Maps LatLng 對象
                lat = location.lat();
                lng = location.lng();
            } else {
                // 普通對象 {lat, lng}
                lat = location.lat;
                lng = location.lng;
            }
            
            // 檢查座標是否為 null（表示沒有座標）
            if (lat === null || lng === null) {
                console.warn('餐廳座標為 null，無法移動地圖:', restaurant.name);
                // 如果有地址，嘗試進行地理編碼
                if (restaurant.formatted_address || restaurant.vicinity) {
                    const address = restaurant.formatted_address || restaurant.vicinity;
                    console.log('嘗試使用地址進行地理編碼:', address);
                    this.geocodeAndMoveToRestaurant(restaurant, address);
                }
                return;
            }
            
            // 確保座標有效（不是 0,0 且不是 NaN）
            if (lat && lng && !isNaN(lat) && !isNaN(lng) && (lat !== 0 || lng !== 0)) {
                // 檢查座標是否在校園位置（可能是預設值）
                const campusLat = 25.076132980674792;
                const campusLng = 121.61012050007541;
                const distance = Math.sqrt(Math.pow(lat - campusLat, 2) + Math.pow(lng - campusLng, 2));
                
                // 如果座標非常接近校園位置（距離小於 0.0001 度，約 11 米），且是推薦餐廳，可能是預設值
                if (restaurant.is_recommended && distance < 0.0001 && (restaurant.formatted_address || restaurant.vicinity)) {
                    console.warn('推薦餐廳座標可能是預設值（校園位置），嘗試使用地址進行地理編碼:', restaurant.name);
                    const address = restaurant.formatted_address || restaurant.vicinity;
                    this.geocodeAndMoveToRestaurant(restaurant, address);
                    return;
                }
                
                console.log('移動地圖到餐廳位置:', restaurant.name, lat, lng);
                this.map.panTo({ lat: lat, lng: lng });
                this.map.setZoom(17);
            } else {
                console.warn('餐廳座標無效，無法移動地圖:', restaurant.name, lat, lng);
                // 如果有地址，嘗試進行地理編碼
                if (restaurant.formatted_address || restaurant.vicinity) {
                    const address = restaurant.formatted_address || restaurant.vicinity;
                    console.log('嘗試使用地址進行地理編碼:', address);
                    this.geocodeAndMoveToRestaurant(restaurant, address);
                }
            }
        } else {
            // 如果沒有座標但有地址，嘗試進行地理編碼
            if (restaurant.formatted_address || restaurant.vicinity) {
                const address = restaurant.formatted_address || restaurant.vicinity;
                console.log('餐廳沒有座標，嘗試使用地址進行地理編碼:', address);
                this.geocodeAndMoveToRestaurant(restaurant, address);
            }
        }
        
        // 在側邊面板顯示餐廳詳情和評論
        this.showRestaurantDetails(restaurant, index);
        
        // 打開該餐廳的資訊視窗（使用 Map 查找標記，而不是索引）
        const markerKey = restaurant.is_recommended && restaurant.recommendation_id 
            ? `recommended_${restaurant.recommendation_id}` 
            : restaurant.place_id || `restaurant_${index}`;
        const marker = this.restaurantMarkerMap.get(markerKey);
        
        if (marker) {
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 15px; max-width: 280px;">
                        <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px;">${restaurant.name || '未命名餐廳'}</h3>
                        <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                            <i class="fas fa-map-marker-alt" style="color: #27ae60;"></i> ${restaurant.vicinity || restaurant.formatted_address || '地址未知'}
                        </p>
                        ${restaurant.rating ? `
                            <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                                <i class="fas fa-star" style="color: #f39c12;"></i> ${restaurant.rating.toFixed(1)} / 5.0
                            </p>
                        ` : ''}
                        <div style="margin-top: 12px;">
                            <button onclick="campusMap.startDirectionsToDestination('${restaurant.vicinity || restaurant.name}')" 
                                    style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 13px; width: 100%;">
                                <i class="fas fa-route"></i> 規劃路線
                            </button>
                        </div>
                    </div>
                `
            });
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, marker);
            this.currentInfoWindow = infoWindow;
        } else {
            console.warn('未找到對應的標記，餐廳:', restaurant.name, 'key:', markerKey);
        }
    }
    
    // 地理編碼並移動到餐廳位置，同時更新標記位置
    geocodeAndMoveToRestaurant(restaurant, address) {
        if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
        }
        
        console.log('開始地理編碼餐廳:', restaurant.name, '地址:', address);
        
        this.geocoder.geocode({ address: address }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;
                const lat = location.lat();
                const lng = location.lng();
                
                console.log('地理編碼成功:', restaurant.name, '座標:', lat, lng);
                
                // 更新餐廳座標
                if (restaurant.geometry) {
                    restaurant.geometry.location = { lat: lat, lng: lng };
                } else {
                    restaurant.geometry = { location: { lat: lat, lng: lng } };
                }
                
                // 移動地圖到正確位置
                this.map.panTo({ lat: lat, lng: lng });
                this.map.setZoom(17);
                
                // 找到對應的標記並更新位置（使用 Map 查找）
                const markerKey = restaurant.is_recommended && restaurant.recommendation_id 
                    ? `recommended_${restaurant.recommendation_id}` 
                    : restaurant.place_id || `restaurant_${restaurant.name}`;
                const marker = this.restaurantMarkerMap.get(markerKey);
                
                if (marker) {
                    marker.setPosition({ lat: lat, lng: lng });
                    console.log('✓ 已更新標記位置:', restaurant.name, 'key:', markerKey);
                } else {
                    console.warn('未找到對應的標記，餐廳:', restaurant.name, 'key:', markerKey);
                    // 如果找不到標記，可能需要重新創建標記
                    // 但這通常不應該發生，因為標記應該已經存在
                }
            } else {
                console.error('✗ 地理編碼失敗:', restaurant.name, '狀態:', status);
            }
        });
    }
    
    async showRestaurantDetails(restaurant, index) {
        // 顯示側邊面板
        this.showSidePanel();
        
        // 切換到餐廳列表（確保面板可見）
        document.getElementById('campus-info').style.display = 'none';
        document.getElementById('directions-info').style.display = 'none';
        const restaurantsList = document.getElementById('restaurants-list');
        restaurantsList.style.display = 'block';
        
        // 更新面板標題
        document.getElementById('panel-title').innerHTML = `<i class="fas fa-utensils"></i> ${restaurant.name || '餐廳詳情'}`;
        
        // 顯示餐廳詳情和評論
        const restaurantsContent = document.getElementById('restaurants-content');
        const rating = restaurant.rating || 0;
        const priceLevel = restaurant.price_level || 0;
        const priceSymbols = '$'.repeat(priceLevel);
        
        // 判斷營業狀態：只有當有 opening_hours 資訊時才顯示
        const hasOpeningHours = restaurant.opening_hours && restaurant.opening_hours.open_now !== undefined;
        const isOpen = hasOpeningHours ? restaurant.opening_hours.open_now : null; // null 表示未知
        
        const hasDelivery = restaurant.hasDelivery || (restaurant.types && restaurant.types.includes('meal_delivery'));
        const deliveryRating = restaurant.deliveryRating;
        
        // 構建營業狀態標籤
        let openingStatusBadge = '';
        if (isOpen === true) {
            openingStatusBadge = '<span class="open-badge">營業中</span>';
        } else if (isOpen === false) {
            openingStatusBadge = '<span class="closed-badge">已打烊</span>';
        }
        // 如果 isOpen === null，不顯示營業狀態標籤（因為未知）
        
        let html = `
            <div class="restaurant-detail">
                <button onclick="campusMap.showNearbyRestaurants()" class="back-button" style="margin-bottom: 16px; background: #f8f9fa; border: 1px solid #e1e5e9; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #666;">
                    <i class="fas fa-arrow-left"></i> 返回餐廳列表
                </button>
                
                <div class="restaurant-detail-header">
                    <h3>${restaurant.name || '未命名餐廳'}</h3>
                    <div class="restaurant-badges">
                        ${restaurant.is_recommended ? '<span class="recommended-badge" style="background: #667eea; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-right: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
                        ${openingStatusBadge}
                        ${hasDelivery ? '<span class="delivery-badge"><i class="fas fa-motorcycle"></i> 外送</span>' : ''}
                    </div>
                </div>
                
                ${restaurant.is_recommended && restaurant.recommendation_title ? `
                    <div style="background: linear-gradient(90deg, rgba(122, 201, 199, 0.1), rgba(149, 109, 189, 0.05)); border: 1px solid rgba(149, 109, 189, 0.3); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <i class="fas fa-user-circle" style="color: #ff6b35; font-size: 18px;"></i>
                            <strong style="color: var(--text-color, #333);">${restaurant.recommendation_author || '學長姐'}</strong>
                            <span style="color: var(--secondary-text, #666); font-size: 12px;">推薦</span>
                        </div>
                        <h4 style="margin: 0 0 8px 0; color: var(--text-color, #333); font-size: 15px; font-weight: 600;">${restaurant.recommendation_title}</h4>
                        <p style="margin: 0 0 12px 0; color: var(--secondary-text, #666); font-size: 13px; line-height: 1.5;">
                            ${restaurant.recommendation_content ? restaurant.recommendation_content.substring(0, 150) + (restaurant.recommendation_content.length > 150 ? '...' : '') : ''}
                        </p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button onclick="window.location.href='restaurant_reviews.php?message_id=${restaurant.recommendation_id}&restaurant=${encodeURIComponent(restaurant.name)}'" 
                                    style="padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                <i class="fas fa-comments"></i> 查看完整推薦與評價
                            </button>
                            <button onclick="campusMap.startDirectionsToDestination('${restaurant.vicinity || restaurant.formatted_address || restaurant.name}')" 
                                    style="padding: 8px 16px; background: #27ae60; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
                                <i class="fas fa-route"></i> 規劃路線
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                <div class="restaurant-detail-rating">
                    <div class="rating-group">
                        <span class="stars">${this.getStarRating(rating)}</span>
                        <span class="rating-text">${rating.toFixed(1)}</span>
                        ${restaurant.user_ratings_total ? `<span class="review-count">(${restaurant.user_ratings_total} 則評價)</span>` : ''}
                    </div>
                    ${priceSymbols ? `<span class="price-level">${priceSymbols}</span>` : ''}
                </div>
                
                ${hasDelivery && deliveryRating ? `
                    <div class="delivery-rating">
                        <i class="fas fa-motorcycle"></i>
                        <span class="delivery-label">外送評價：</span>
                        <span class="delivery-stars">${this.getStarRating(parseFloat(deliveryRating.deliveryRating))}</span>
                        <span class="delivery-rating-text">${deliveryRating.deliveryRating}</span>
                    </div>
                ` : ''}
                
                <p class="restaurant-address">
                    <i class="fas fa-map-marker-alt"></i> ${restaurant.vicinity || restaurant.formatted_address || '地址未知'}
                </p>
                
                ${restaurant.types ? `
                    <div class="restaurant-types">
                        ${restaurant.types.slice(0, 5).map(type => 
                            `<span class="type-tag">${this.translateType(type)}</span>`
                        ).join('')}
                    </div>
                ` : ''}
                
                <div style="margin-top: 20px;">
                    <button onclick="campusMap.startDirectionsToDestination('${restaurant.vicinity || restaurant.name}')" 
                            style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; width: 100%; margin-bottom: 20px;">
                        <i class="fas fa-route"></i> 規劃路線
                    </button>
                </div>
                
                <div id="restaurant-reviews-section" style="margin-top: 20px;">
                    <p style="color: #999; text-align: center; padding: 20px;">
                        <i class="fas fa-spinner fa-spin"></i> 載入評論中...
                    </p>
                </div>
            </div>
        `;
        
        restaurantsContent.innerHTML = html;
        
        // 異步獲取整合的評論
        await this.loadRestaurantReviews(restaurant, hasDelivery);
    }
    
    async loadRestaurantReviews(restaurant, hasDelivery) {
        const reviewsSection = document.getElementById('restaurant-reviews-section');
        if (!reviewsSection) return;
        
        try {
            // 獲取學長姐留言板的評論
            const params = new URLSearchParams();
            if (restaurant.recommendation_id) {
                params.append('message_id', restaurant.recommendation_id);
            }
            if (restaurant.place_id) {
                params.append('place_id', restaurant.place_id);
            }
            if (restaurant.name) {
                params.append('restaurant_name', restaurant.name);
            }
            
            const response = await fetch(`api/get_restaurant_reviews.php?${params.toString()}`);
            const data = await response.json();
            
            // 合併 Google 評論和學長姐留言板評論
            const googleReviews = (restaurant.reviews || []).map(review => ({
                source: 'google',
                author_name: review.author_name,
                rating: review.rating,
                text: review.text,
                time: review.time,
                isDelivery: false
            }));
            
            const seniorReviews = (data.reviews || []).map(review => ({
                ...review,
                source: 'senior'
            }));
            
            // 合併所有評論，優先顯示學長姐留言板的評論
            const allReviews = [...seniorReviews, ...googleReviews];
            
            // 按時間排序（最新的在前）
            allReviews.sort((a, b) => (b.time || 0) - (a.time || 0));
            
            // 顯示評論
            if (allReviews.length > 0) {
                const seniorCount = seniorReviews.length;
                const googleCount = googleReviews.length;
                
                reviewsSection.innerHTML = `
                    <div class="restaurant-reviews">
                        <h4 style="margin: 20px 0 12px 0; color: #333; font-size: 16px; font-weight: 600;">
                            <i class="fas fa-comments"></i> 顧客評論 (${allReviews.length})
                            ${seniorCount > 0 ? `<span style="font-size: 12px; color: #ff6b35; font-weight: normal; margin-left: 8px;">學長姐 ${seniorCount} 則</span>` : ''}
                            ${googleCount > 0 ? `<span style="font-size: 12px; color: #4285f4; font-weight: normal; margin-left: 4px;">Google ${googleCount} 則</span>` : ''}
                            ${hasDelivery ? '<span style="font-size: 13px; color: #ff6b35; font-weight: normal; margin-left: 8px;"><i class="fas fa-motorcycle"></i> 優先顯示外送評論</span>' : ''}
                        </h4>
                        <div class="reviews-list">
                            ${this.getDeliveryReviews(allReviews, hasDelivery).slice(0, 10).map(review => `
                                <div class="review-item ${review.isDelivery ? 'delivery-review' : ''} ${review.source === 'senior' ? 'senior-review' : ''}">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <strong>${review.author_name || '匿名用戶'}</strong>
                                                ${review.source === 'senior' ? '<span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: 600;"><i class="fas fa-user-graduate"></i> 學長姐</span>' : ''}
                                                ${review.isDelivery ? '<span class="delivery-review-badge"><i class="fas fa-motorcycle"></i> 外送</span>' : ''}
                                            </div>
                                            <span class="review-time">${this.formatReviewTime(review.time)}</span>
                                        </div>
                                        <div class="review-rating">
                                            ${this.getStarRating(review.rating || 0)}
                                            <span class="review-rating-text">${review.rating || 0}</span>
                                        </div>
                                    </div>
                                    <p class="review-text">${review.text || '無評論內容'}</p>
                                    ${review.title ? `<p style="margin-top: 8px; font-weight: 600; color: #ff6b35; font-size: 13px;">${review.title}</p>` : ''}
                                    ${review.review_id && review.source === 'senior' ? `
                                        <button onclick="window.location.href='restaurant_reviews.php?message_id=${review.message_id || restaurant.recommendation_id}&restaurant=${encodeURIComponent(restaurant.name)}'" 
                                                style="margin-top: 8px; padding: 4px 8px; background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 6px; cursor: pointer; font-size: 11px; color: #666;">
                                            查看完整評價
                                        </button>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            } else {
                reviewsSection.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">暫無評論</p>';
            }
        } catch (error) {
            console.error('載入評論失敗:', error);
            reviewsSection.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">載入評論時發生錯誤</p>';
        }
    }
    
    getDeliveryReviews(reviews, hasDelivery) {
        if (!hasDelivery || !reviews || reviews.length === 0) {
            return reviews;
        }
        
        // 外送相關關鍵字
        const deliveryKeywords = [
            '外送', '外賣', 'delivery', '外帶', 'takeout', 'take away',
            'uber eats', 'foodpanda', 'foodpanda', 'ubereats',
            '熊貓', '熊貓外送', 'foodpanda', 'deliveroo',
            '送餐', '配送', '外送員', '外送服務', '外送速度',
            '外送時間', '外送品質', '外送包裝', '外送態度'
        ];
        
        // 分類評論：外送評論和一般評論
        const deliveryReviews = [];
        const regularReviews = [];
        
        reviews.forEach(review => {
            const reviewText = (review.text || '').toLowerCase();
            const isDeliveryReview = deliveryKeywords.some(keyword => 
                reviewText.includes(keyword.toLowerCase())
            );
            
            if (isDeliveryReview) {
                deliveryReviews.push({ ...review, isDelivery: true });
            } else {
                regularReviews.push({ ...review, isDelivery: false });
            }
        });
        
        // 優先顯示外送評論，然後是一般評論
        return [...deliveryReviews, ...regularReviews];
    }
    
    formatReviewTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        
        if (days === 0) return '今天';
        if (days === 1) return '昨天';
        if (days < 7) return `${days} 天前`;
        if (days < 30) return `${Math.floor(days / 7)} 週前`;
        if (days < 365) return `${Math.floor(days / 30)} 個月前`;
        return `${Math.floor(days / 365)} 年前`;
    }

    showCampusInfo() {
        // 顯示側邊面板
        this.showSidePanel();
        
        // 切換到校園資訊
        document.getElementById('restaurants-list').style.display = 'none';
        document.getElementById('directions-info').style.display = 'none';
        document.getElementById('campus-info').style.display = 'block';
        
        // 更新面板標題
        document.getElementById('panel-title').innerHTML = '<i class="fas fa-info-circle"></i> 校園資訊';
        
        // 移動地圖到校園位置
        this.map.setCenter({ lat: this.campusLocation.lat, lng: this.campusLocation.lng });
        this.map.setZoom(16);
    }

    focusOnBuilding(buildingType) {
        let location = null;
        
        switch(buildingType) {
            case 'firstTeachingBuilding':
                location = this.firstTeachingBuildingLocation;
                break;
            case 'secondTeachingBuilding':
                location = this.secondTeachingBuildingLocation;
                break;
            case 'administrativeBuilding':
                location = this.administrativeBuildingLocation;
                break;
            case 'yeshengHall':
                location = this.yeshengHallLocation;
                break;
            case 'cihuiBuilding':
                location = this.cihuiBuildingLocation;
                break;
            default:
                console.error('未知的建築物類型:', buildingType);
                return;
        }
        
        if (location) {
            // 移動地圖到建築物位置
            this.map.setCenter({ lat: location.lat, lng: location.lng });
            this.map.setZoom(19);
            
            // 找到對應的標記點並打開信息窗口
            const marker = this.markers.find(m => {
                if (m.getPosition) {
                    const pos = m.getPosition();
                    return pos && 
                           Math.abs(pos.lat() - location.lat) < 0.0001 && 
                           Math.abs(pos.lng() - location.lng) < 0.0001;
                }
                return false;
            });
            
            if (marker) {
                // 觸發標記點的點擊事件來顯示信息窗口
                google.maps.event.trigger(marker, 'click');
            }
        }
    }

    showCampusMap() {
        // 顯示校園平面圖模態框
        console.log('showCampusMap 方法被調用');
        const modal = document.getElementById('campus-map-modal');
        if (modal) {
            console.log('模態框元素找到，添加 visible 類');
            modal.classList.add('visible');
            document.body.style.overflow = 'hidden'; // 防止背景滾動
        } else {
            console.error('模態框元素未找到！');
        }
    }

    closeCampusMapModal() {
        // 關閉校園平面圖模態框
        const modal = document.getElementById('campus-map-modal');
        if (modal) {
            modal.classList.remove('visible');
            document.body.style.overflow = ''; // 恢復背景滾動
        }
    }

    promptForDirections() {
        // 顯示側邊面板
        this.showSidePanel();
        
        // 切換到路線規劃
        document.getElementById('campus-info').style.display = 'none';
        document.getElementById('restaurants-list').style.display = 'none';
        const directionsInfo = document.getElementById('directions-info');
        directionsInfo.style.display = 'block';
        
        // 更新面板標題
        document.getElementById('panel-title').innerHTML = '<i class="fas fa-route"></i> 路線規劃';
        
        // 預設起點為指定地址
        const defaultOrigin = '114臺北市內湖區康寧路三段75巷137號';
        const originInput = document.getElementById('directions-origin');
        if (originInput && !originInput.value.trim()) {
            originInput.value = defaultOrigin;
            console.log('設置起點預設值:', defaultOrigin);
        }
        
        // 預設終點為校園地址（先設置，再初始化自動完成）
        const destinationInput = document.getElementById('directions-destination');
        if (destinationInput && !destinationInput.value) {
            destinationInput.value = this.campusLocation.address;
            console.log('設置終點預設值:', this.campusLocation.address);
        }
        
        // 初始化自動完成
        this.initDirectionsAutocomplete();
        
        // 如果起點和終點都有值，立即觸發路線規劃
        if (originInput && originInput.value.trim() && destinationInput && destinationInput.value.trim()) {
            console.log('起點和終點都有值，立即觸發路線規劃');
            setTimeout(() => {
                this.updateDirectionsIfReady();
            }, 200);
        } else {
            console.log('等待用戶選擇起點或終點');
        }
    }
    
    initDirectionsAutocomplete() {
        // 起點自動完成
        const originInput = document.getElementById('directions-origin');
        if (originInput && !this.originAutocomplete) {
            this.originAutocomplete = new google.maps.places.Autocomplete(originInput, {
                types: ['geocode', 'establishment'],
                componentRestrictions: { country: 'tw' }
            });
            
            this.originAutocomplete.addListener('place_changed', () => {
                const place = this.originAutocomplete.getPlace();
                console.log('起點選擇:', place);
                if (place.geometry) {
                    // 更新輸入框的值為完整地址
                    const address = place.formatted_address || place.name;
                    originInput.value = address;
                    console.log('起點已設置:', address);
                    
                    // 確保終點有值（如果沒有，設置為校園地址）
                    const destinationInput = document.getElementById('directions-destination');
                    if (destinationInput && !destinationInput.value.trim()) {
                        destinationInput.value = this.campusLocation.address;
                        console.log('終點預設值已設置:', this.campusLocation.address);
                    }
                    
                    // 觸發路線更新（延遲一點確保 DOM 更新完成）
                    setTimeout(() => {
                        console.log('觸發路線規劃（起點選擇後）');
                        this.updateDirectionsIfReady();
                    }, 300);
                }
            });
        }
        
        // 終點自動完成
        const destinationInput = document.getElementById('directions-destination');
        if (destinationInput && !this.destinationAutocomplete) {
            this.destinationAutocomplete = new google.maps.places.Autocomplete(destinationInput, {
                types: ['geocode', 'establishment'],
                componentRestrictions: { country: 'tw' }
            });
            
            this.destinationAutocomplete.addListener('place_changed', () => {
                const place = this.destinationAutocomplete.getPlace();
                console.log('終點選擇:', place);
                if (place.geometry) {
                    // 更新輸入框的值為完整地址
                    const address = place.formatted_address || place.name;
                    destinationInput.value = address;
                    console.log('終點已設置:', address);
                    // 觸發路線更新
                    setTimeout(() => {
                        console.log('觸發路線規劃（終點選擇後）');
                        this.updateDirectionsIfReady();
                    }, 300);
                }
            });
        }
        
        // 設置事件監聽器
        this.setupDirectionsEventListeners();
    }
    
    setupDirectionsEventListeners() {
        // 使用當前位置按鈕
        const useLocationBtn = document.getElementById('use-current-location-btn');
        if (useLocationBtn) {
            useLocationBtn.addEventListener('click', async () => {
                const location = await this.getCurrentLocation();
                if (location) {
                    // 反向地理編碼獲取地址
                    this.geocoder.geocode({ location: location }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            const originInput = document.getElementById('directions-origin');
                            if (originInput) {
                                originInput.value = results[0].formatted_address;
                                this.updateDirectionsIfReady();
                            }
                        }
                    });
                } else {
                    const originInput = document.getElementById('directions-origin');
                    if (originInput) {
                        originInput.placeholder = '無法獲取位置，請手動輸入地址';
                        originInput.style.borderColor = '#ea4335';
                        setTimeout(() => {
                            originInput.style.borderColor = '';
                            originInput.placeholder = '選擇起點（或使用我的位置）';
                        }, 3000);
                    }
                }
            });
        }
        
        // 交換起終點按鈕
        const swapBtn = document.getElementById('swap-locations-btn');
        if (swapBtn) {
            swapBtn.addEventListener('click', () => {
                const originInput = document.getElementById('directions-origin');
                const destinationInput = document.getElementById('directions-destination');
                if (originInput && destinationInput) {
                    const temp = originInput.value;
                    originInput.value = destinationInput.value;
                    destinationInput.value = temp;
                    this.updateDirectionsIfReady();
                }
            });
        }
        
        // 清除路線按鈕
        const clearBtn = document.getElementById('clear-directions-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearDirections();
            });
        }
        
        // 交通方式按鈕
        const travelModeBtns = document.querySelectorAll('.travel-mode-btn');
        travelModeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // 移除所有 active 類
                travelModeBtns.forEach(b => b.classList.remove('active'));
                // 添加 active 類到當前按鈕
                btn.classList.add('active');
                // 更新交通方式
                this.currentMode = btn.dataset.mode.toLowerCase();
                // 檢查是否為摩托車模式
                this.isMotorcycle = btn.dataset.vehicle === 'motorcycle';
                console.log('交通方式已更改:', this.currentMode, '摩托車:', this.isMotorcycle);
                this.updateDirectionsIfReady();
            });
        });
        
        // 輸入框變化時更新路線（只使用 blur 事件，當用戶完成輸入後觸發）
        // 移除 input 事件監聽器，避免在用戶輸入過程中自動規劃路線失敗
        const originInput = document.getElementById('directions-origin');
        const destinationInput = document.getElementById('directions-destination');
        if (originInput) {
            // 當輸入框失去焦點時觸發（用戶完成輸入）
            originInput.addEventListener('blur', () => {
                if (originInput.value.trim()) {
                    setTimeout(() => {
                        this.updateDirectionsIfReady();
                    }, 300);
                }
            });
            // 移除 input 事件監聽器，避免在輸入過程中自動規劃路線
        }
        if (destinationInput) {
            // 當輸入框失去焦點時觸發（用戶完成輸入）
            destinationInput.addEventListener('blur', () => {
                if (destinationInput.value.trim()) {
                    setTimeout(() => {
                        this.updateDirectionsIfReady();
                    }, 300);
                }
            });
            // 移除 input 事件監聽器，避免在輸入過程中自動規劃路線
        }
    }
    
    updateDirectionsIfReady() {
        const originInput = document.getElementById('directions-origin');
        const destinationInput = document.getElementById('directions-destination');
        
        if (!originInput || !destinationInput) {
            console.log('updateDirectionsIfReady: 輸入框不存在');
            return;
        }
        
        let origin = originInput.value.trim();
        let destination = destinationInput.value.trim();
        
        // 如果起點為空，設置為預設地址
        const defaultOrigin = '114臺北市內湖區康寧路三段75巷137號';
        if (!origin) {
            origin = defaultOrigin;
            originInput.value = origin;
            console.log('起點為空，已設置為預設地址:', origin);
        }
        
        // 如果終點為空，設置為校園地址
        if (!destination) {
            destination = this.campusLocation.address;
            destinationInput.value = destination;
            console.log('終點為空，已設置為校園地址:', destination);
        }
        
        console.log('updateDirectionsIfReady:', { origin, destination, originLength: origin.length, destinationLength: destination.length });
        
        if (origin && destination) {
            console.log('✓ 起點和終點都有值，觸發路線規劃');
            this.getDirections(destination, origin);
        } else {
            console.log('✗ 起點或終點為空，無法規劃路線', { 
                hasOrigin: !!origin, 
                hasDestination: !!destination,
                origin: origin || '(空)',
                destination: destination || '(空)'
            });
        }
    }
    
    clearDirections() {
        // 清除路線渲染
        if (this.directionsRenderer) {
            this.directionsRenderer.setDirections({ routes: [] });
        }
        
        // 清除自定義標記
        this.clearRouteMarkers();
        
        // 清除輸入框（但保留預設起點）
        const originInput = document.getElementById('directions-origin');
        const destinationInput = document.getElementById('directions-destination');
        const defaultOrigin = '114臺北市內湖區康寧路三段75巷137號';
        if (originInput) originInput.value = defaultOrigin; // 保留預設起點
        if (destinationInput) destinationInput.value = '';
        
        // 清除顯示內容
        const routesList = document.getElementById('routes-list');
        const directionsContent = document.getElementById('directions-content');
        if (routesList) routesList.innerHTML = '';
        if (directionsContent) directionsContent.innerHTML = '';
    }
    
    startDirectionsToDestination(destination) {
        // 顯示路線規劃界面
        this.promptForDirections();
        
        // 更新「規劃路線」按鈕為 active 狀態
        const getDirectionsBtn = document.getElementById('get-directions-btn');
        if (getDirectionsBtn) {
            this.updateActiveButton(getDirectionsBtn);
        }
        
        // 設置終點地址
        const destinationInput = document.getElementById('directions-destination');
        if (destinationInput) {
            destinationInput.value = destination;
            // 觸發自動完成更新（如果有的話）
            if (this.destinationAutocomplete) {
                setTimeout(() => {
                    this.updateDirectionsIfReady();
                }, 100);
            } else {
                setTimeout(() => {
                    this.updateDirectionsIfReady();
                }, 300);
            }
        }
    }

    async getDirections(destination, origin = null) {
        if (!destination) {
            console.log('getDirections: 終點為空');
            return;
        }

        try {
            // 確定起點
            let originValue = origin;
            if (!originValue) {
                const userLocation = await this.getCurrentLocation();
                originValue = userLocation ? `${userLocation.lat},${userLocation.lng}` : '台北車站';
            }

            console.log('getDirections 請求:', { origin: originValue, destination, mode: this.currentMode });

            const request = {
                origin: originValue,
                destination: destination,
                travelMode: google.maps.TravelMode[this.currentMode.toUpperCase()],
                language: 'zh-TW',
                provideRouteAlternatives: true, // 提供多條路線選項
                // 摩托車模式：避免高速公路（台灣摩托車不能上高速公路）
                avoidHighways: this.isMotorcycle,
                avoidTolls: false
            };
            
            if (this.isMotorcycle) {
                console.log('使用摩托車模式，避免高速公路');
            }

            this.directionsService.route(request, (result, status) => {
                console.log('路線規劃結果:', { status, routesCount: result?.routes?.length || 0 });
                
                if (status === 'OK') {
                    // 清除舊的標記
                    this.clearRouteMarkers();
                    
                    // 顯示多條路線選項
                    this.displayRoutesList(result);
                    
                    // 顯示第一條路線的詳細步驟
                    if (result.routes && result.routes.length > 0) {
                        // 創建只包含第一條路線的結果對象
                        const firstRouteResult = {
                            routes: [result.routes[0]],
                            request: result.request
                        };
                        
                        console.log('準備顯示路線，結果對象:', firstRouteResult);
                        console.log('DirectionsRenderer 狀態:', {
                            map: this.directionsRenderer.getMap(),
                            directions: this.directionsRenderer.getDirections()
                        });
                        
                        // 確保 directionsRenderer 連接到地圖
                        if (!this.directionsRenderer.getMap()) {
                            this.directionsRenderer.setMap(this.map);
                            console.log('重新設置 directionsRenderer 的地圖');
                        }
                        
                        // 先設置路線渲染，確保路線顯示（只顯示第一條）
                        this.directionsRenderer.setDirections(firstRouteResult);
                        console.log('路線已設置到 directionsRenderer');
                        
                        // 等待路線渲染完成後再添加標記
                        setTimeout(() => {
                            // 檢查路線是否已渲染
                            const renderedDirections = this.directionsRenderer.getDirections();
                            console.log('路線渲染後檢查:', {
                                hasDirections: !!renderedDirections,
                                routesCount: renderedDirections?.routes?.length || 0
                            });
                            
                            // 添加自定義標記
                            this.addRouteMarkers(result.routes[0]);
                            
                            // 自動調整地圖視圖以顯示完整路線
                            this.fitRouteBounds(result.routes[0]);
                        }, 200);
                        
                        // 顯示詳細步驟
                        this.displayDirections(result, 0);
                        
                        console.log('路線顯示完成');
                    } else {
                        console.log('沒有找到路線');
                        this.showError('找不到路線');
                    }
                } else {
                    console.error('路線規劃失敗:', status);
                    this.showError('路線規劃失敗：' + status);
                    const routesList = document.getElementById('routes-list');
                    const directionsContent = document.getElementById('directions-content');
                    if (routesList) routesList.innerHTML = '<p class="error-text">無法規劃路線，請檢查起終點是否正確</p>';
                    if (directionsContent) directionsContent.innerHTML = '';
                }
            });
        } catch (error) {
            console.error('路線規劃失敗:', error);
            this.showError('路線規劃失敗，請稍後再試');
        }
    }
    
    displayRoutesList(result) {
        const routesList = document.getElementById('routes-list');
        console.log('displayRoutesList 被調用:', { routesList: !!routesList, routes: result?.routes?.length || 0 });
        
        if (!routesList) {
            console.error('routes-list 元素不存在');
            return;
        }
        
        if (!result.routes) {
            console.error('result.routes 不存在');
            return;
        }
        
        if (result.routes.length === 0) {
            console.log('沒有路線');
            routesList.innerHTML = '<p class="error-text">找不到路線</p>';
            return;
        }
        
        console.log('開始渲染路線列表，共', result.routes.length, '條路線');
        
        let html = '<div class="routes-options">';
        
        result.routes.forEach((route, index) => {
            const leg = route.legs[0];
            const distance = leg.distance.text;
            const duration = leg.duration.text;
            const isFastest = index === 0; // 第一條通常是最快的
            
            html += `
                <div class="route-option ${isFastest ? 'fastest' : ''}" data-route-index="${index}">
                    <div class="route-header">
                        <div class="route-info">
                            <span class="route-number">路線 ${index + 1}</span>
                            ${isFastest ? '<span class="route-badge">最快</span>' : ''}
                        </div>
                        <div class="route-stats">
                            <span class="route-duration"><i class="fas fa-clock"></i> ${duration}</span>
                            <span class="route-distance"><i class="fas fa-route"></i> ${distance}</span>
                        </div>
                    </div>
                    ${route.summary ? `<div class="route-summary">${route.summary}</div>` : ''}
                </div>
            `;
        });
        
        html += '</div>';
        routesList.innerHTML = html;
        console.log('路線列表已渲染到 DOM');
        
        // 添加路線選項點擊事件
        const routeOptions = routesList.querySelectorAll('.route-option');
        routeOptions.forEach((option, index) => {
            option.addEventListener('click', () => {
                console.log('點擊路線選項:', index);
                
                // 移除所有選中狀態
                routeOptions.forEach(opt => opt.classList.remove('selected'));
                // 添加選中狀態
                option.classList.add('selected');
                
                // 創建只包含選中路線的新結果對象
                const selectedRoute = result.routes[index];
                if (selectedRoute) {
                    // 創建只包含一條路線的結果對象
                    const singleRouteResult = {
                        routes: [selectedRoute],
                        request: result.request
                    };
                    
                    console.log('顯示路線:', index, selectedRoute);
                    
                    // 清除舊的標記（在設置路線之前）
                    this.clearRouteMarkers();
                    
                    // 確保 directionsRenderer 連接到地圖
                    if (!this.directionsRenderer.getMap()) {
                        this.directionsRenderer.setMap(this.map);
                        console.log('重新設置 directionsRenderer 的地圖');
                    }
                    
                    // 顯示該路線（只顯示選中的那一條）
                    this.directionsRenderer.setDirections(singleRouteResult);
                    console.log('路線已設置到 directionsRenderer，路線索引:', index);
                    
                    // 等待路線渲染完成後再添加標記
                    setTimeout(() => {
                        // 檢查路線是否已渲染
                        const renderedDirections = this.directionsRenderer.getDirections();
                        console.log('路線渲染後檢查:', {
                            hasDirections: !!renderedDirections,
                            routesCount: renderedDirections?.routes?.length || 0
                        });
                        
                        // 添加自定義標記
                        this.addRouteMarkers(selectedRoute);
                        
                        // 調整地圖視圖
                        this.fitRouteBounds(selectedRoute);
                    }, 200);
                    
                    // 顯示詳細步驟
                    this.displayDirections(result, index);
                } else {
                    console.error('路線不存在:', index);
                }
            });
        });
        
        // 預設選中第一條路線
        if (routeOptions.length > 0) {
            routeOptions[0].classList.add('selected');
        }
    }

    getCurrentLocation() {
        return new Promise((resolve) => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        });
                    },
                    () => {
                        resolve(null);
                    }
                );
            } else {
                resolve(null);
            }
        });
    }

    displayDirections(result, routeIndex = 0) {
        const directionsContent = document.getElementById('directions-content');
        console.log('displayDirections 被調用:', { 
            directionsContent: !!directionsContent, 
            routes: result?.routes?.length || 0, 
            routeIndex 
        });
        
        if (!directionsContent) {
            console.error('directions-content 元素不存在');
            return;
        }
        
        if (!result.routes || !result.routes[routeIndex]) {
            console.error('路線數據不存在:', { routes: result?.routes?.length || 0, routeIndex });
            return;
        }

        const route = result.routes[routeIndex];
        const leg = route.legs[0];
        console.log('開始渲染路線詳細步驟，共', leg.steps.length, '個步驟');
        
        let html = `
            <div class="directions-summary-card">
                <div class="summary-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <div class="summary-label">預估時間</div>
                        <div class="summary-value">${leg.duration.text}</div>
                    </div>
                </div>
                <div class="summary-item">
                    <i class="fas fa-route"></i>
                    <div>
                        <div class="summary-label">距離</div>
                        <div class="summary-value">${leg.distance.text}</div>
                    </div>
                </div>
            </div>
            <div class="directions-addresses">
                <div class="address-item start-address">
                    <div class="address-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="address-text">
                        <div class="address-label">起點</div>
                        <div class="address-value">${leg.start_address}</div>
                    </div>
                </div>
                <div class="address-item end-address">
                    <div class="address-icon"><i class="fas fa-flag-checkered"></i></div>
                    <div class="address-text">
                        <div class="address-label">終點</div>
                        <div class="address-value">${leg.end_address}</div>
                    </div>
                </div>
            </div>
            <div class="directions-steps">
        `;

        leg.steps.forEach((step, index) => {
            // 提取轉向圖標和類型
            const maneuver = step.maneuver || '';
            const instructions = step.instructions.replace(/<[^>]*>/g, '');
            
            // 更智能的轉向圖標判斷
            let stepIcon = 'fas fa-arrow-right';
            let turnType = '轉向';
            
            const lowerManeuver = maneuver.toLowerCase();
            const lowerInstructions = instructions.toLowerCase();
            
            if (lowerManeuver.includes('left') || lowerInstructions.includes('左轉') || lowerInstructions.includes('左彎')) {
                stepIcon = 'fas fa-arrow-left';
                turnType = '左轉';
            } else if (lowerManeuver.includes('right') || lowerInstructions.includes('右轉') || lowerInstructions.includes('右彎')) {
                stepIcon = 'fas fa-arrow-right';
                turnType = '右轉';
            } else if (lowerManeuver.includes('straight') || lowerInstructions.includes('直行') || lowerInstructions.includes('繼續')) {
                stepIcon = 'fas fa-arrow-up';
                turnType = '直行';
            } else if (lowerManeuver.includes('uturn') || lowerInstructions.includes('迴轉') || lowerInstructions.includes('掉頭')) {
                stepIcon = 'fas fa-undo';
                turnType = '迴轉';
            } else if (lowerInstructions.includes('上') || lowerInstructions.includes('進入')) {
                stepIcon = 'fas fa-arrow-up';
                turnType = '進入';
            } else if (lowerInstructions.includes('下') || lowerInstructions.includes('離開')) {
                stepIcon = 'fas fa-arrow-down';
                turnType = '離開';
            }
            
            // 提取道路名稱（如果有的話）
            const roadMatch = instructions.match(/([^，,。.]+(?:路|街|道|巷|弄|段))/);
            const roadName = roadMatch ? roadMatch[1] : '';
            
            html += `
                <div class="direction-step" data-step-index="${index}">
                    <div class="step-number">${index + 1}</div>
                    <div class="step-icon">
                        <i class="${stepIcon}"></i>
                    </div>
                    <div class="step-content">
                        <div class="step-header">
                            <span class="step-turn-type">${turnType}</span>
                            ${roadName ? `<span class="step-road-name">${roadName}</span>` : ''}
                        </div>
                        <div class="step-instruction">${instructions}</div>
                        <div class="step-details">
                            <span class="step-distance"><i class="fas fa-route"></i> ${step.distance.text}</span>
                            <span class="step-duration"><i class="fas fa-clock"></i> ${step.duration.text}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        directionsContent.innerHTML = html;
        console.log('路線詳細步驟已渲染到 DOM，共', leg.steps.length, '個步驟');
        
        // 添加步驟點擊事件，點擊時高亮地圖上的路段
        const steps = directionsContent.querySelectorAll('.direction-step');
        console.log('找到', steps.length, '個步驟元素');
        steps.forEach((stepEl, index) => {
            stepEl.addEventListener('click', () => {
                // 移除所有高亮
                steps.forEach(s => s.classList.remove('active'));
                // 添加高亮
                stepEl.classList.add('active');
            });
        });
    }

    clearRouteMarkers() {
        // 清除所有路線標記
        if (this.routeMarkers && this.routeMarkers.length > 0) {
            this.routeMarkers.forEach(marker => {
                if (marker) {
                    marker.setMap(null);
                }
            });
            this.routeMarkers = [];
            console.log('路線標記已清除');
        }
    }
    
    addRouteMarkers(route) {
        if (!route || !route.legs || route.legs.length === 0) {
            console.log('路線數據無效，無法添加標記');
            return;
        }
        
        if (!this.map) {
            console.error('地圖未初始化，無法添加標記');
            return;
        }
        
        // 清除舊的標記
        this.clearRouteMarkers();
        
        const leg = route.legs[0];
        let markersAdded = 0;
        
        // 添加起點標記
        if (leg.start_location) {
            try {
                const startMarker = new google.maps.Marker({
                    position: leg.start_location,
                    map: this.map,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="20" cy="20" r="18" fill="#34a853" stroke="white" stroke-width="2"/>
                                <text x="20" y="28" font-size="16" font-weight="bold" fill="white" text-anchor="middle">起</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(40, 40),
                        anchor: new google.maps.Point(20, 20)
                    },
                    title: '起點: ' + leg.start_address,
                    zIndex: google.maps.Marker.MAX_ZINDEX + 10,
                    optimized: false
                });
                
                // 確保標記已添加到地圖
                if (startMarker.getMap() === this.map) {
                    this.routeMarkers.push(startMarker);
                    markersAdded++;
                    console.log('起點標記已添加:', leg.start_location, '標記對象:', startMarker);
                } else {
                    console.error('起點標記未正確添加到地圖');
                }
            } catch (error) {
                console.error('添加起點標記失敗:', error);
            }
        }
        
        // 添加終點標記
        if (leg.end_location) {
            try {
                const endMarker = new google.maps.Marker({
                    position: leg.end_location,
                    map: this.map,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="20" cy="20" r="18" fill="#ea4335" stroke="white" stroke-width="2"/>
                                <text x="20" y="28" font-size="16" font-weight="bold" fill="white" text-anchor="middle">終</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(40, 40),
                        anchor: new google.maps.Point(20, 20)
                    },
                    title: '終點: ' + leg.end_address,
                    zIndex: google.maps.Marker.MAX_ZINDEX + 10,
                    optimized: false
                });
                
                // 確保標記已添加到地圖
                if (endMarker.getMap() === this.map) {
                    this.routeMarkers.push(endMarker);
                    markersAdded++;
                    console.log('終點標記已添加:', leg.end_location, '標記對象:', endMarker);
                } else {
                    console.error('終點標記未正確添加到地圖');
                }
            } catch (error) {
                console.error('添加終點標記失敗:', error);
            }
        }
        
        // 添加重要轉向點的標記
        if (leg.steps) {
            leg.steps.forEach((step, index) => {
                if (this.isImportantTurn(step.maneuver, step.instructions) && step.end_location) {
                    try {
                        const turnInfo = this.getTurnInfo(step.maneuver, step.instructions);
                        const turnMarker = new google.maps.Marker({
                            position: step.end_location,
                            map: this.map,
                            icon: {
                                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(this.createTurnIconSVG(turnInfo)),
                                scaledSize: new google.maps.Size(30, 30),
                                anchor: new google.maps.Point(15, 15)
                            },
                            title: step.instructions.replace(/<[^>]*>/g, ''),
                            zIndex: google.maps.Marker.MAX_ZINDEX + 5,
                            optimized: false
                        });
                        
                        // 確保標記已添加到地圖
                        if (turnMarker.getMap() === this.map) {
                            // 添加信息窗口
                            const infoWindow = new google.maps.InfoWindow({
                                content: `
                                    <div style="padding: 8px;">
                                        <strong>${turnInfo.turnText}</strong><br>
                                        ${step.instructions.replace(/<[^>]*>/g, '')}<br>
                                        <small>距離: ${step.distance.text} | 時間: ${step.duration.text}</small>
                                    </div>
                                `
                            });
                            
                            turnMarker.addListener('click', () => {
                                // 關閉之前打開的 InfoWindow
                                if (this.currentInfoWindow) {
                                    this.currentInfoWindow.close();
                                }
                                // 打開新的 InfoWindow 並保存引用
                                infoWindow.open(this.map, turnMarker);
                                this.currentInfoWindow = infoWindow;
                            });
                            
                            this.routeMarkers.push(turnMarker);
                            markersAdded++;
                        } else {
                            console.error('轉向標記未正確添加到地圖');
                        }
                    } catch (error) {
                        console.error('添加轉向標記失敗:', error, step);
                    }
                }
            });
        }
        
        console.log('已添加', markersAdded, '個路線標記，總共', this.routeMarkers.length, '個標記');
    }
    
    isImportantTurn(maneuver, instructions) {
        if (!maneuver && !instructions) return false;
        
        const lowerManeuver = (maneuver || '').toLowerCase();
        const lowerInstructions = (instructions || '').toLowerCase();
        
        // 過濾掉直行和繼續的步驟
        if (lowerManeuver.includes('straight') || 
            lowerInstructions.includes('直行') || 
            lowerInstructions.includes('繼續') ||
            lowerInstructions.includes('繼續直行')) {
            return false;
        }
        
        // 只標記重要的轉向
        return lowerManeuver.includes('turn') || 
               lowerManeuver.includes('left') || 
               lowerManeuver.includes('right') ||
               lowerInstructions.includes('轉') ||
               lowerInstructions.includes('彎');
    }
    
    getTurnInfo(maneuver, instructions) {
        const lowerManeuver = (maneuver || '').toLowerCase();
        const lowerInstructions = (instructions || '').toLowerCase();
        
        let turnText = '轉向';
        let icon = 'fas fa-arrow-right';
        let color = '#4285f4';
        let direction = 'right';
        
        if (lowerManeuver.includes('left') || lowerInstructions.includes('左轉') || lowerInstructions.includes('左彎')) {
            turnText = '左轉';
            icon = 'fas fa-arrow-left';
            color = '#ea4335';
            direction = 'left';
        } else if (lowerManeuver.includes('right') || lowerInstructions.includes('右轉') || lowerInstructions.includes('右彎')) {
            turnText = '右轉';
            icon = 'fas fa-arrow-right';
            color = '#34a853';
            direction = 'right';
        } else if (lowerManeuver.includes('uturn') || lowerInstructions.includes('迴轉') || lowerInstructions.includes('掉頭')) {
            turnText = '迴轉';
            icon = 'fas fa-undo';
            color = '#fbbc04';
            direction = 'uturn';
        }
        
        return { turnText, icon, color, direction };
    }
    
    createTurnIconSVG(turnInfo) {
        const { color, direction } = turnInfo;
        let path = '';
        
        if (direction === 'left') {
            path = 'M 20 10 L 10 20 L 20 30 L 20 25 L 15 20 L 20 15 Z';
        } else if (direction === 'right') {
            path = 'M 20 10 L 30 20 L 20 30 L 20 25 L 25 20 L 20 15 Z';
        } else if (direction === 'uturn') {
            path = 'M 20 10 Q 10 10 10 20 Q 10 30 20 30 L 20 25 Q 15 25 15 20 Q 15 15 20 15 Z';
        } else {
            path = 'M 20 10 L 30 20 L 20 30 L 20 25 L 25 20 L 20 15 Z';
        }
        
        return `
            <svg width="30" height="30" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="20" r="18" fill="${color}" stroke="white" stroke-width="2"/>
                <path d="${path}" fill="white"/>
            </svg>
        `;
    }
    
    fitRouteBounds(route) {
        if (!route || !route.bounds) return;
        
        const bounds = new google.maps.LatLngBounds();
        
        // 添加起點和終點
        if (route.legs && route.legs.length > 0) {
            const leg = route.legs[0];
            if (leg.start_location) bounds.extend(leg.start_location);
            if (leg.end_location) bounds.extend(leg.end_location);
            
            // 添加所有步驟的點
            if (leg.steps) {
                leg.steps.forEach(step => {
                    if (step.path) {
                        step.path.forEach(point => bounds.extend(point));
                    }
                });
            }
        }
        
        // 調整地圖視圖
        if (!bounds.isEmpty()) {
            this.map.fitBounds(bounds, {
                padding: { top: 50, right: 50, bottom: 50, left: 50 }
            });
        }
    }

    showSidePanel() {
        const sidePanel = document.getElementById('side-panel');
        const floatingControls = document.querySelector('.floating-controls');
        if (sidePanel) {
            sidePanel.classList.add('visible');
            this.sidePanelVisible = true;
            // 調整浮動按鈕位置，避免與側邊面板重疊
            if (floatingControls && window.innerWidth > 1024) {
                // 根據屏幕寬度設置不同的位置
                if (window.innerWidth >= 1920) {
                    // 大屏幕：側邊面板寬度 500px，加上間距
                    floatingControls.style.right = '520px';
                } else {
                    // 桌面：側邊面板寬度 450px，加上間距
                    floatingControls.style.right = '480px';
                }
            }
        }
    }

    // 初始化時顯示側邊面板
    initSidePanel() {
        const sidePanel = document.getElementById('side-panel');
        const floatingControls = document.querySelector('.floating-controls');
        if (sidePanel) {
            // 延遲一下確保樣式已載入，然後顯示側邊面板
            setTimeout(() => {
                sidePanel.classList.add('visible');
                this.sidePanelVisible = true;
                // 調整浮動按鈕位置，避免與側邊面板重疊
                if (floatingControls && window.innerWidth > 1024) {
                    // 根據屏幕寬度設置不同的位置
                    if (window.innerWidth >= 1920) {
                        floatingControls.style.right = '520px';
                    } else {
                        floatingControls.style.right = '480px';
                    }
                }
            }, 300);
        }
    }

    toggleSidePanel() {
        const sidePanel = document.getElementById('side-panel');
        const floatingControls = document.querySelector('.floating-controls');
        if (sidePanel) {
            const isVisible = sidePanel.classList.contains('visible');
            sidePanel.classList.toggle('visible');
            this.sidePanelVisible = !isVisible;
            // 調整浮動按鈕位置
            if (floatingControls && window.innerWidth > 1024) {
                if (!isVisible) {
                    // 顯示面板時，根據屏幕寬度設置不同的位置
                    if (window.innerWidth >= 1920) {
                        floatingControls.style.right = '520px';
                    } else {
                        floatingControls.style.right = '480px';
                    }
                } else {
                    // 隱藏面板時，恢復默認位置
                    floatingControls.style.right = '60px';
                }
            }
        }
    }

    hideLoading() {
        const loading = document.getElementById('map-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    updateActiveButton(activeBtn) {
        // 移除所有按鈕的 active 類
        document.querySelectorAll('.floating-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        // 添加 active 類到當前按鈕
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }


    showError(message) {
        console.error(message);
        alert(message);
    }
    
    /**
     * 從 URL 參數顯示餐廳位置
     */
    showRestaurantFromURL(restaurantName, lat, lng, address) {
        console.log('showRestaurantFromURL 被調用:', { restaurantName, lat, lng, address, hasMap: !!this.map });
        
        if (!this.map) {
            console.error('地圖未初始化');
            return;
        }
        
        // 如果有座標，直接使用；如果沒有，使用地址或餐廳名稱進行地理編碼
        if (lat && lng && lat !== 'null' && lng !== 'null') {
            console.log('使用座標顯示餐廳:', { lat, lng });
            this._showRestaurantMarker(restaurantName, parseFloat(lat), parseFloat(lng), address);
        } else if (address && address.trim() !== '') {
            console.log('使用地址進行地理編碼:', address);
            // 使用地址進行地理編碼
            if (!this.geocoder) {
                this.geocoder = new google.maps.Geocoder();
            }
            
            this.geocoder.geocode({ address: address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const location = results[0].geometry.location;
                    this._showRestaurantMarker(restaurantName, location.lat(), location.lng(), address);
                } else {
                    console.error('地理編碼失敗:', status);
                    // 如果地址編碼失敗，嘗試使用餐廳名稱
                    this._geocodeByRestaurantName(restaurantName, address);
                }
            });
        } else if (restaurantName && restaurantName.trim() !== '') {
            // 如果沒有地址但有餐廳名稱，使用餐廳名稱進行地理編碼
            console.log('使用餐廳名稱進行地理編碼:', restaurantName);
            this._geocodeByRestaurantName(restaurantName, '');
        } else {
            console.error('缺少餐廳位置信息（座標、地址或餐廳名稱）');
            alert('缺少餐廳位置信息，無法在地圖上顯示');
        }
    }
    
    /**
     * 使用餐廳名稱進行地理編碼（內部方法）
     */
    _geocodeByRestaurantName(restaurantName, fallbackAddress) {
        if (!this.geocoder) {
            this.geocoder = new google.maps.Geocoder();
        }
        
        // 嘗試使用餐廳名稱加上「台北市」來搜尋
        const searchQuery = restaurantName + ' 台北市';
        
        this.geocoder.geocode({ address: searchQuery }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;
                const foundAddress = results[0].formatted_address || fallbackAddress;
                this._showRestaurantMarker(restaurantName, location.lat(), location.lng(), foundAddress);
            } else {
                console.error('使用餐廳名稱地理編碼失敗:', status);
                alert('無法找到餐廳位置，請確認餐廳名稱是否正確');
            }
        });
    }
    
    /**
     * 顯示餐廳標記（內部方法）
     */
    _showRestaurantMarker(restaurantName, lat, lng, address) {
        console.log('=== _showRestaurantMarker 被調用 ===', { restaurantName, lat, lng, address });
        
        // 創建餐廳標記
        const restaurantMarker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: this.map,
            title: restaurantName,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="20" r="18" fill="#ff6b35" stroke="white" stroke-width="2"/>
                        <text x="20" y="28" font-size="20" text-anchor="middle" fill="white">🍽️</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(40, 40),
                anchor: new google.maps.Point(20, 20)
            },
            zIndex: 1000
        });
        
        // 確定目的地（優先使用座標，其次地址，最後使用名稱）
        const destination = `${lat},${lng}`; // 使用座標格式，更精確
        
        // 創建信息窗口
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px; min-width: 200px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333;">${restaurantName}</h3>
                    ${address ? `<p style="margin: 0; font-size: 13px; color: #666;"><i class="fas fa-map-marker-alt"></i> ${address}</p>` : ''}
                    <button onclick="campusMap.startDirectionsToDestination('${destination}')" 
                            style="margin-top: 10px; padding: 8px 16px; background: #ff6b35; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-route"></i> 規劃路線
                    </button>
                </div>
            `
        });
        
        // 點擊標記顯示信息窗口
        restaurantMarker.addListener('click', () => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, restaurantMarker);
            this.currentInfoWindow = infoWindow;
        });
        
        // 移動地圖到餐廳位置
        this.map.setCenter({ lat: lat, lng: lng });
        this.map.setZoom(17);
        
        // 自動打開信息窗口
        setTimeout(() => {
            // 關閉之前打開的 InfoWindow
            if (this.currentInfoWindow) {
                this.currentInfoWindow.close();
            }
            // 打開新的 InfoWindow 並保存引用
            infoWindow.open(this.map, restaurantMarker);
            this.currentInfoWindow = infoWindow;
        }, 500);
        
        // 自動規劃路線（延遲一下確保地圖和界面已完全載入）
        console.log('設置自動規劃路線定時器，2秒後執行，目的地:', destination);
        setTimeout(() => {
            console.log('=== 開始自動規劃路線 ===');
            console.log('目的地:', destination);
            console.log('當前 this:', this);
            
            // 先顯示路線規劃界面
            console.log('調用 promptForDirections...');
            this.promptForDirections();
            
            // 等待界面元素準備好
            const waitForElements = () => {
                const destinationInput = document.getElementById('directions-destination');
                const originInput = document.getElementById('directions-origin');
                
                if (!destinationInput || !originInput) {
                    console.log('等待界面元素準備好...');
                    setTimeout(waitForElements, 100);
                    return;
                }
                
                console.log('界面元素已準備好，開始設置路線');
                
                // 設置終點
                destinationInput.value = destination;
                console.log('已設置終點:', destination);
                
                // 直接調用 getDirections，它會自動獲取當前位置作為起點
                // 不需要等待設置起點輸入框，因為 getDirections 會自動處理
                setTimeout(() => {
                    console.log('直接調用 getDirections 規劃路線...');
                    this.getDirections(destination).then(() => {
                        console.log('✓ 路線規劃完成');
                    }).catch((error) => {
                        console.error('✗ 路線規劃失敗:', error);
                    });
                }, 800);
            };
            
            waitForElements();
        }, 2000);
        
        console.log('餐廳位置已標示:', restaurantName);
    }

    // 載入 Google Maps 評論
    loadGoogleReviews(placeId, restaurantIndex) {
        if (!this.placesService || !placeId) {
            console.error('無法載入評論：PlacesService 未初始化或缺少 place_id');
            return;
        }

        const reviewsContainer = document.getElementById(`reviews-${restaurantIndex}`);
        if (!reviewsContainer) {
            console.error('找不到評論容器');
            return;
        }

        // 顯示容器和載入狀態
        reviewsContainer.style.display = 'block';
        reviewsContainer.innerHTML = '<div class="reviews-loading" style="text-align: center; padding: 20px; color: #666;"><i class="fas fa-spinner fa-spin"></i> 載入評論中...</div>';

        // 使用 getDetails 獲取評論
        const request = {
            placeId: placeId,
            fields: ['reviews', 'rating', 'user_ratings_total', 'name']
        };

        this.placesService.getDetails(request, (place, status) => {
            if (status === google.maps.places.PlacesServiceStatus.OK && place && place.reviews) {
                const reviews = place.reviews.slice(0, 5); // 只顯示前5則評論
                
                if (reviews.length === 0) {
                    reviewsContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">暫無評論</p>';
                    return;
                }

                let reviewsHtml = `
                    <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e1e8ed;">
                        <h6 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #333;">
                            <i class="fas fa-star" style="color: #f39c12;"></i> Google Maps 評論
                        </h6>
                        <p style="margin: 0; font-size: 12px; color: #666;">
                            總評分：${place.rating ? place.rating.toFixed(1) : 'N/A'} / 5.0 
                            ${place.user_ratings_total ? `(${place.user_ratings_total} 則評論)` : ''}
                        </p>
                    </div>
                    <div style="max-height: 600px; overflow-y: auto; padding-right: 8px;">
                `;

                reviews.forEach((review) => {
                    const rating = review.rating || 0;
                    const stars = '★'.repeat(Math.floor(rating)) + '☆'.repeat(5 - Math.floor(rating));
                    const timeAgo = review.time ? this.formatTimeAgo(review.time) : '';
                    
                    reviewsHtml += `
                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f0f0f0;">
                            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; margin-right: 10px; flex-shrink: 0;">
                                    ${review.author_name ? review.author_name.charAt(0) : '?'}
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 13px; color: #333; margin-bottom: 4px;">
                                        ${review.author_name || '匿名用戶'}
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #666;">
                                        <span style="color: #f39c12;">${stars}</span>
                                        <span>${rating.toFixed(1)}</span>
                                        ${timeAgo ? `<span>• ${timeAgo}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            ${review.text ? `
                                <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #555; word-wrap: break-word;">
                                    ${review.text.length > 200 ? review.text.substring(0, 200) + '...' : review.text}
                                </p>
                            ` : ''}
                        </div>
                    `;
                });

                reviewsHtml += `
                    </div>
                    <div style="margin-top: 12px; text-align: center;">
                        <a href="https://www.google.com/maps/place/?q=place_id:${placeId}" target="_blank" style="display: inline-block; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                            <i class="fab fa-google"></i> 在 Google Maps 查看所有評論
                        </a>
                    </div>
                `;

                reviewsContainer.innerHTML = reviewsHtml;
            } else {
                reviewsContainer.innerHTML = `
                    <p style="text-align: center; color: #666; padding: 20px;">
                        ${status === google.maps.places.PlacesServiceStatus.ZERO_RESULTS ? '找不到評論' : '載入評論失敗，請稍後再試'}
                    </p>
                `;
            }
        });
    }

    // 格式化時間（將時間戳轉換為相對時間）
    formatTimeAgo(time) {
        if (!time) return '';
        
        const now = new Date();
        const reviewTime = new Date(time * 1000); // Google Places API 返回的是秒級時間戳
        const diffInSeconds = Math.floor((now - reviewTime) / 1000);
        
        if (diffInSeconds < 60) return '剛剛';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} 分鐘前`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} 小時前`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)} 天前`;
        if (diffInSeconds < 31536000) return `${Math.floor(diffInSeconds / 2592000)} 個月前`;
        return `${Math.floor(diffInSeconds / 31536000)} 年前`;
    }
}

// 全域變數，供 HTML 中的 onclick 事件使用
let campusMap;
window.campusMap = null; // 確保可以在全域訪問
let mapInitialized = false; // 標記地圖是否已初始化

// 全域初始化函數，供 Google Maps API 回調使用
function initMap() {
    // 防止重複初始化
    if (mapInitialized) {
        console.log('地圖已經初始化，跳過重複初始化');
        return;
    }
    
    console.log('initMap 被調用');
    console.log('google 物件:', typeof google !== 'undefined' ? '存在' : '不存在');
    console.log('google.maps:', typeof google !== 'undefined' && typeof google.maps !== 'undefined' ? '存在' : '不存在');
    console.log('GOOGLE_MAPS_API_KEY:', GOOGLE_MAPS_API_KEY ? (GOOGLE_MAPS_API_KEY.substring(0, 10) + '...') : '未設定');
    
    if (typeof google !== 'undefined' && google.maps) {
        // 檢查 API Key
        if (!GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY.trim() === '') {
            console.error('Google Maps API Key 未設定');
            showMapError('Google Maps API Key 未設定，請聯繫管理員');
            return;
        }
        console.log('開始創建 CampusMap 實例');
        try {
            campusMap = new CampusMap();
            window.campusMap = campusMap; // 設置到 window 物件上
            mapInitialized = true; // 標記地圖已初始化
            console.log('CampusMap 實例創建成功');
            
            // 檢查 URL 參數，如果有餐廳信息，則在地圖上標示
            const urlParams = new URLSearchParams(window.location.search);
            let restaurantName = urlParams.get('restaurant');
            const restaurantLat = urlParams.get('lat');
            const restaurantLng = urlParams.get('lng');
            let restaurantAddress = urlParams.get('address');
            
            // 如果餐廳名稱看起來像數字（可能是標題），嘗試從地址或其他地方獲取
            // 但通常推薦餐廳時，標題就是餐廳名稱，所以直接使用
            if (restaurantName && /^\d+$/.test(restaurantName.trim())) {
                console.warn('餐廳名稱看起來像數字，可能是標題:', restaurantName);
                // 如果地址不為空，可以嘗試使用地址
                if (restaurantAddress && restaurantAddress.trim() !== '') {
                    console.log('使用地址作為餐廳名稱:', restaurantAddress);
                    restaurantName = restaurantAddress;
                }
            }
            
            console.log('URL 參數檢查:', { 
                restaurantName, 
                restaurantLat, 
                restaurantLng, 
                restaurantAddress,
                hasRestaurant: !!(restaurantName && restaurantName.trim() !== '')
            });
            
            // 只要有餐廳名稱就可以顯示（可以通過名稱、地址或座標來定位）
            if (restaurantName && restaurantName.trim() !== '') {
                console.log('發現餐廳參數，將在 1 秒後顯示餐廳位置');
                // 延遲一下確保地圖已完全初始化
                setTimeout(() => {
                    console.log('調用 showRestaurantFromURL:', { restaurantName, restaurantLat, restaurantLng, restaurantAddress });
                    campusMap.showRestaurantFromURL(restaurantName, restaurantLat, restaurantLng, restaurantAddress);
                }, 1000);
            } else {
                console.log('未發現餐廳參數，跳過餐廳顯示');
            }
        } catch (error) {
            console.error('創建 CampusMap 實例時發生錯誤:', error);
            showMapError('地圖初始化失敗: ' + error.message);
        }
    } else {
        console.error('Google Maps API 未載入');
        showMapError('Google Maps API 未載入');
    }
}

// 當頁面載入完成時檢查地圖狀態
document.addEventListener('DOMContentLoaded', () => {
    // 如果地圖已經初始化，不需要再次初始化
    if (mapInitialized) {
        console.log('地圖已經初始化，跳過 DOMContentLoaded 中的初始化');
        return;
    }
    
    // 如果 Google Maps API 已經載入，直接初始化
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    } else {
        // 等待 Google Maps API 載入
        setTimeout(() => {
            // 再次檢查是否已經初始化（可能在等待期間已經通過 callback 初始化了）
            if (mapInitialized) {
                console.log('地圖已在等待期間初始化，跳過');
                return;
            }
            if (typeof google !== 'undefined' && google.maps) {
                initMap();
            } else {
                showMapError('Google Maps API 載入失敗，請檢查網路連線或 API Key 設定');
            }
        }, 3000);
    }
});

// 顯示地圖錯誤
function showMapError(message) {
    const mapLoading = document.getElementById('map-loading');
    const staticMap = document.getElementById('static-map');
    
    if (mapLoading) {
        mapLoading.style.display = 'none';
    }
    
    if (staticMap) {
        staticMap.style.display = 'flex';
    }
    
    console.error(message);
}
