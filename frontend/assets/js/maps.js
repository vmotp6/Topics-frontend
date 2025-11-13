/**
 * 康寧大學校園地圖 JavaScript
 * 整合 Google Maps API 功能 - Google Maps 風格版本
 */

class CampusMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.restaurantMarkers = [];
        this.directionsService = null;
        this.directionsRenderer = null;
        this.placesService = null;
        this.autocompleteService = null;
        this.geocoder = null;
        this.currentMode = 'driving';
        this.campusLocation = {
            'name': '康寧大學台北校區',
            'address': '台北市內湖區康寧路三段75巷137號',
            'lat': 25.07575358359577,
            'lng': 121.60949282881778,
            'description': '康寧大學台北校區'
        };
        this.sidePanelVisible = true;
        this.restaurants = [];
        
        this.init();
    }

    async init() {
        try {
            // 初始化地圖
            this.initMap();
            
            // 設定事件監聽器
            this.setupEventListeners();
            
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
            // 創建地圖，使用更現代的樣式
            this.map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: this.campusLocation.lat, lng: this.campusLocation.lng },
                zoom: 16,
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
            suppressMarkers: false,
            map: this.map
        });

        // 初始化 Places 服務
        this.placesService = new google.maps.places.PlacesService(this.map);
        this.autocompleteService = new google.maps.places.AutocompleteService();
        this.geocoder = new google.maps.Geocoder();

        // 添加校園標記
        this.addCampusMarker();
        
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
                        <button onclick="campusMap.getDirections('${this.campusLocation.address}')" 
                                style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 13px; width: 100%;">
                            <i class="fas fa-route"></i> 規劃路線
                        </button>
                    </div>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        this.markers.push(marker);
    }


    setupEventListeners() {

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
        const closePanelBtn = document.getElementById('close-panel-btn');
        if (closePanelBtn) {
            closePanelBtn.addEventListener('click', () => {
                this.toggleSidePanel();
            });
        }
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
            // 使用 Places API 搜尋附近餐廳
            const request = {
                location: new google.maps.LatLng(this.campusLocation.lat, this.campusLocation.lng),
                radius: 2000, // 2公里範圍
                type: 'restaurant'
            };

            this.placesService.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK && results) {
                    this.restaurants = results;
                    this.displayRestaurants(results);
                    this.addRestaurantMarkers(results);
                } else {
                    restaurantsContent.innerHTML = '<p class="error-text">找不到附近餐廳，請稍後再試。</p>';
                }
            });
        } catch (error) {
            console.error('搜尋餐廳失敗:', error);
            restaurantsContent.innerHTML = '<p class="error-text">搜尋餐廳時發生錯誤，請稍後再試。</p>';
        }
    }

    displayRestaurants(restaurants) {
        const restaurantsContent = document.getElementById('restaurants-content');
        const restaurantsCount = document.getElementById('restaurants-count');
        
        if (restaurantsCount) {
            restaurantsCount.textContent = `(${restaurants.length} 間)`;
        }

        if (restaurants.length === 0) {
            restaurantsContent.innerHTML = '<p class="error-text">附近沒有找到餐廳。</p>';
            return;
        }

        let html = '<div class="restaurants-items">';
        
        restaurants.forEach((restaurant, index) => {
            const rating = restaurant.rating || 0;
            const priceLevel = restaurant.price_level || 0;
            const priceSymbols = '$'.repeat(priceLevel);
            const isOpen = restaurant.opening_hours && restaurant.opening_hours.open_now;
            
            html += `
                <div class="restaurant-item" onclick="campusMap.selectRestaurant(${index})">
                    <div class="restaurant-header">
                        <h5>${restaurant.name || '未命名餐廳'}</h5>
                        ${isOpen ? '<span class="open-badge">營業中</span>' : '<span class="closed-badge">已打烊</span>'}
                    </div>
                    <div class="restaurant-rating">
                        <span class="stars">${this.getStarRating(rating)}</span>
                        <span class="rating-text">${rating.toFixed(1)}</span>
                        ${priceSymbols ? `<span class="price-level">${priceSymbols}</span>` : ''}
                    </div>
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
                </div>
            `;
        });

        html += '</div>';
        restaurantsContent.innerHTML = html;
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
            const marker = new google.maps.Marker({
                position: restaurant.geometry.location,
                map: this.map,
                title: restaurant.name,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="35" height="35" viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="17.5" cy="17.5" r="15" fill="#27ae60" stroke="white" stroke-width="2"/>
                            <text x="17.5" y="22" text-anchor="middle" fill="white" font-size="14" font-weight="bold">餐</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(35, 35),
                    anchor: new google.maps.Point(17.5, 35)
                }
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 12px; max-width: 250px;">
                        <h4 style="margin: 0 0 8px 0; color: #333; font-size: 16px;">${restaurant.name || '未命名餐廳'}</h4>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                            <i class="fas fa-map-marker-alt" style="color: #27ae60;"></i> ${restaurant.vicinity || '地址未知'}
                        </p>
                        ${restaurant.rating ? `
                            <p style="margin: 0; color: #666; font-size: 13px;">
                                <i class="fas fa-star" style="color: #f39c12;"></i> ${restaurant.rating.toFixed(1)}
                            </p>
                        ` : ''}
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
                this.selectRestaurant(index);
            });

            this.restaurantMarkers.push(marker);
        });
    }

    clearRestaurantMarkers() {
        this.restaurantMarkers.forEach(marker => marker.setMap(null));
        this.restaurantMarkers = [];
    }

    selectRestaurant(index) {
        if (!this.restaurants || !this.restaurants[index]) return;
        
        const restaurant = this.restaurants[index];
        
        // 移動地圖到餐廳位置
        this.map.setCenter(restaurant.geometry.location);
        this.map.setZoom(17);
        
        // 打開該餐廳的資訊視窗
        if (this.restaurantMarkers[index]) {
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
                            <button onclick="campusMap.getDirections('${restaurant.vicinity || restaurant.name}')" 
                                    style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; font-size: 13px; width: 100%;">
                                <i class="fas fa-route"></i> 規劃路線
                            </button>
                        </div>
                    </div>
                `
            });
            infoWindow.open(this.map, this.restaurantMarkers[index]);
        }
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

    promptForDirections() {
        const destination = prompt('請輸入目的地地址：', this.campusLocation.address);
        if (destination) {
            this.getDirections(destination);
        }
    }

    async getDirections(destination) {
        if (!destination) return;

        try {
            // 獲取用戶當前位置
            const userLocation = await this.getCurrentLocation();
            const origin = userLocation ? `${userLocation.lat},${userLocation.lng}` : '台北車站';

            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode[this.currentMode.toUpperCase()],
                language: 'zh-TW'
            };

            this.directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    this.directionsRenderer.setDirections(result);
                    this.displayDirections(result);
                } else {
                    this.showError('路線規劃失敗：' + status);
                }
            });
        } catch (error) {
            console.error('路線規劃失敗:', error);
            this.showError('路線規劃失敗，請稍後再試');
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

    displayDirections(result) {
        // 顯示側邊面板
        this.showSidePanel();
        
        // 切換到路線規劃
        document.getElementById('campus-info').style.display = 'none';
        document.getElementById('restaurants-list').style.display = 'none';
        const directionsInfo = document.getElementById('directions-info');
        directionsInfo.style.display = 'block';
        
        // 更新面板標題
        document.getElementById('panel-title').innerHTML = '<i class="fas fa-route"></i> 路線規劃';
        
        const directionsContent = document.getElementById('directions-content');
        if (!directionsContent) return;

        const route = result.routes[0];
        const leg = route.legs[0];
        
        let html = `
            <div class="directions-summary">
                <p><strong><i class="fas fa-route"></i> 距離：</strong>${leg.distance.text}</p>
                <p><strong><i class="fas fa-clock"></i> 預估時間：</strong>${leg.duration.text}</p>
                <p><strong><i class="fas fa-map-marker-alt"></i> 起點：</strong>${leg.start_address}</p>
                <p><strong><i class="fas fa-flag-checkered"></i> 終點：</strong>${leg.end_address}</p>
            </div>
            <div class="directions-steps">
        `;

        leg.steps.forEach((step, index) => {
            html += `
                <div class="direction-step">
                    <div class="step-number">${index + 1}</div>
                    <div class="step-content">
                        <p>${step.instructions.replace(/<[^>]*>/g, '')}</p>
                        <small><i class="fas fa-route"></i> ${step.distance.text} - <i class="fas fa-clock"></i> ${step.duration.text}</small>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        directionsContent.innerHTML = html;
    }

    showSidePanel() {
        const sidePanel = document.getElementById('side-panel');
        const floatingControls = document.querySelector('.floating-controls');
        if (sidePanel) {
            sidePanel.classList.add('visible');
            this.sidePanelVisible = true;
            // 調整浮動按鈕位置，避免與側邊面板重疊
            if (floatingControls && window.innerWidth > 1024) {
                floatingControls.style.right = '470px';
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
                    floatingControls.style.right = '470px';
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
                    floatingControls.style.right = '470px';
                } else {
                    floatingControls.style.right = '20px';
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
}

// 全域變數，供 HTML 中的 onclick 事件使用
let campusMap;

// 全域初始化函數，供 Google Maps API 回調使用
function initMap() {
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
            console.log('CampusMap 實例創建成功');
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
    // 如果 Google Maps API 已經載入，直接初始化
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    } else {
        // 等待 Google Maps API 載入
        setTimeout(() => {
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
