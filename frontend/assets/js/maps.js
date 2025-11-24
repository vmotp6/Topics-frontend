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
        this.isMotorcycle = false;
        this.campusLocation = {
            'name': '康寧大學台北校區',
            'address': '台北市內湖區康寧路三段75巷137號',
            'lat': 25.07575358359577,
            'lng': 121.60949282881778,
            'description': '康寧大學台北校區'
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
            infoWindow.open(this.map, marker);
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
        const closePanelBtn = document.getElementById('close-panel-btn');
        if (closePanelBtn) {
            closePanelBtn.addEventListener('click', () => {
                this.toggleSidePanel();
            });
        }

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
            const [googleRestaurants, recommendedRestaurants] = await Promise.all([
                this.fetchGoogleRestaurants(),
                this.fetchRecommendedRestaurants()
            ]);
            
            // 合併餐廳列表，優先顯示推薦餐廳，去除重複
            const mergedRestaurants = this.mergeRestaurants(googleRestaurants, recommendedRestaurants);
            
            // 獲取每個餐廳的詳細信息（包括外送評價）
            await this.fetchRestaurantDetails(mergedRestaurants);
            
            // 顯示餐廳列表
            this.displayRestaurants(mergedRestaurants);
            this.addRestaurantMarkers(mergedRestaurants);
            
        } catch (error) {
            console.error('搜尋餐廳失敗:', error);
            restaurantsContent.innerHTML = '<p class="error-text">搜尋餐廳時發生錯誤，請稍後再試。</p>';
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
            const response = await fetch('api/get_recommended_restaurants.php');
            const data = await response.json();
            
            if (data.success && data.restaurants) {
                console.log('獲取到推薦餐廳:', data.restaurants.length, '間');
                return data.restaurants;
            }
            return [];
        } catch (error) {
            console.error('獲取推薦餐廳失敗:', error);
            return [];
        }
    }
    
    // 合併餐廳列表，去除重複
    mergeRestaurants(googleRestaurants, recommendedRestaurants) {
        const merged = [];
        const seenNames = new Set();
        const seenPlaces = new Set();
        
        // 優先添加推薦餐廳
        recommendedRestaurants.forEach(restaurant => {
            const name = restaurant.name.toLowerCase().trim();
            const placeId = restaurant.place_id;
            
            if (!seenNames.has(name) && !seenPlaces.has(placeId)) {
                seenNames.add(name);
                seenPlaces.add(placeId);
                merged.push(restaurant);
            }
        });
        
        // 添加 Google Maps 餐廳（排除已存在的）
        googleRestaurants.forEach(restaurant => {
            const name = restaurant.name.toLowerCase().trim();
            const placeId = restaurant.place_id;
            
            if (!seenNames.has(name) && !seenPlaces.has(placeId)) {
                seenNames.add(name);
                seenPlaces.add(placeId);
                merged.push(restaurant);
            }
        });
        
        return merged;
    }

    async fetchRestaurantDetails(restaurants) {
        // 為每個餐廳獲取詳細信息
        const detailPromises = restaurants.map((restaurant, index) => {
            return new Promise((resolve) => {
                if (!restaurant.place_id) {
                    resolve();
                    return;
                }
                
                const detailRequest = {
                    placeId: restaurant.place_id,
                    fields: ['name', 'rating', 'user_ratings_total', 'price_level', 'types', 'opening_hours', 'formatted_address', 'vicinity', 'reviews']
                };
                
                this.placesService.getDetails(detailRequest, (place, status) => {
                    if (status === google.maps.places.PlacesServiceStatus.OK && place) {
                        // 合併詳細信息到餐廳對象
                        this.restaurants[index] = {
                            ...this.restaurants[index],
                            ...place,
                            // 檢查是否有外送服務
                            hasDelivery: place.types && place.types.includes('meal_delivery'),
                            // 獲取外送相關評價
                            deliveryRating: this.getDeliveryRating(place)
                        };
                    }
                    resolve();
                });
            });
        });
        
        await Promise.all(detailPromises);
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
        this.restaurants = restaurants;
        
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
            const hasDelivery = restaurant.hasDelivery || (restaurant.types && restaurant.types.includes('meal_delivery'));
            const deliveryRating = restaurant.deliveryRating;
            
            const isRecommended = restaurant.is_recommended || false;
            
            html += `
                <div class="restaurant-item" data-restaurant-index="${index}" ${isRecommended ? 'data-recommended="true"' : ''}>
                    <div class="restaurant-header">
                        <h5>${restaurant.name || '未命名餐廳'}</h5>
                        <div class="restaurant-badges">
                            ${isRecommended ? '<span class="recommended-badge" style="background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-right: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
                            ${isOpen ? '<span class="open-badge">營業中</span>' : '<span class="closed-badge">已打烊</span>'}
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
            const isRecommended = restaurant.is_recommended || false;
            const marker = new google.maps.Marker({
                position: restaurant.geometry.location,
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
                            ${isRecommended ? '<span style="background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: 600; margin-left: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
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
                infoWindow.open(this.map, marker);
                // 如果是推薦餐廳，直接顯示詳情；否則選擇餐廳
                if (restaurant.is_recommended && restaurant.recommendation_id) {
                    this.showRestaurantDetails(restaurant, index);
                } else {
                    this.selectRestaurant(index);
                }
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
        
        // 移動地圖到餐廳位置（使用平滑動畫）
        if (restaurant.geometry && restaurant.geometry.location) {
            const location = restaurant.geometry.location;
            // 處理 LatLng 對象或包含 lat/lng 的對象
            if (location.lat && location.lng) {
                this.map.panTo(location);
            } else if (typeof location.lat === 'function') {
                // Google Maps LatLng 對象
                this.map.panTo(location);
            }
            this.map.setZoom(17);
        }
        
        // 在側邊面板顯示餐廳詳情和評論
        this.showRestaurantDetails(restaurant, index);
        
        // 打開該餐廳的資訊視窗
        if (this.restaurantMarkers && this.restaurantMarkers[index]) {
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
            infoWindow.open(this.map, this.restaurantMarkers[index]);
        }
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
        const isOpen = restaurant.opening_hours && restaurant.opening_hours.open_now;
        const hasDelivery = restaurant.hasDelivery || (restaurant.types && restaurant.types.includes('meal_delivery'));
        const deliveryRating = restaurant.deliveryRating;
        
        let html = `
            <div class="restaurant-detail">
                <button onclick="campusMap.showNearbyRestaurants()" class="back-button" style="margin-bottom: 16px; background: #f8f9fa; border: 1px solid #e1e5e9; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #666;">
                    <i class="fas fa-arrow-left"></i> 返回餐廳列表
                </button>
                
                <div class="restaurant-detail-header">
                    <h3>${restaurant.name || '未命名餐廳'}</h3>
                    <div class="restaurant-badges">
                        ${restaurant.is_recommended ? '<span class="recommended-badge" style="background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-right: 6px;"><i class="fas fa-star"></i> 推薦</span>' : ''}
                        ${isOpen ? '<span class="open-badge">營業中</span>' : '<span class="closed-badge">已打烊</span>'}
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
                                    style="padding: 8px 16px; background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">
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
                                                ${review.source === 'senior' ? '<span style="background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: 600;"><i class="fas fa-user-graduate"></i> 學長姐</span>' : ''}
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
        
        // 預設終點為校園地址（先設置，再初始化自動完成）
        const destinationInput = document.getElementById('directions-destination');
        if (destinationInput && !destinationInput.value) {
            destinationInput.value = this.campusLocation.address;
            console.log('設置終點預設值:', this.campusLocation.address);
        }
        
        // 初始化自動完成
        this.initDirectionsAutocomplete();
        
        // 如果起點已經有值，立即觸發路線規劃
        const originInput = document.getElementById('directions-origin');
        if (originInput && originInput.value.trim()) {
            console.log('起點已有值，立即觸發路線規劃');
            setTimeout(() => {
                this.updateDirectionsIfReady();
            }, 200);
        } else {
            console.log('等待用戶選擇起點');
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
        
        // 輸入框變化時更新路線（使用 blur 事件，當用戶完成輸入後觸發）
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
            // 也監聽輸入變化（延遲觸發，避免頻繁請求）
            originInput.addEventListener('input', () => {
                clearTimeout(this.directionsUpdateTimeout);
                this.directionsUpdateTimeout = setTimeout(() => {
                    this.updateDirectionsIfReady();
                }, 800);
            });
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
            // 也監聽輸入變化（延遲觸發，避免頻繁請求）
            destinationInput.addEventListener('input', () => {
                clearTimeout(this.directionsUpdateTimeout);
                this.directionsUpdateTimeout = setTimeout(() => {
                    this.updateDirectionsIfReady();
                }, 800);
            });
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
        
        // 清除輸入框
        const originInput = document.getElementById('directions-origin');
        const destinationInput = document.getElementById('directions-destination');
        if (originInput) originInput.value = '';
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
                                infoWindow.open(this.map, turnMarker);
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
    
    /**
     * 從 URL 參數顯示餐廳位置
     */
    showRestaurantFromURL(restaurantName, lat, lng, address) {
        if (!this.map) {
            console.error('地圖未初始化');
            return;
        }
        
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
        
        // 創建信息窗口
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px; min-width: 200px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333;">${restaurantName}</h3>
                    ${address ? `<p style="margin: 0; font-size: 13px; color: #666;"><i class="fas fa-map-marker-alt"></i> ${address}</p>` : ''}
                    <button onclick="campusMap.startDirectionsToDestination('${address || restaurantName}')" 
                            style="margin-top: 10px; padding: 8px 16px; background: #ff6b35; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-route"></i> 規劃路線
                    </button>
                </div>
            `
        });
        
        // 點擊標記顯示信息窗口
        restaurantMarker.addListener('click', () => {
            infoWindow.open(this.map, restaurantMarker);
        });
        
        // 移動地圖到餐廳位置
        this.map.setCenter({ lat: lat, lng: lng });
        this.map.setZoom(17);
        
        // 自動打開信息窗口
        setTimeout(() => {
            infoWindow.open(this.map, restaurantMarker);
        }, 500);
        
        console.log('餐廳位置已標示:', restaurantName);
    }
}

// 全域變數，供 HTML 中的 onclick 事件使用
let campusMap;
window.campusMap = null; // 確保可以在全域訪問

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
            window.campusMap = campusMap; // 設置到 window 物件上
            console.log('CampusMap 實例創建成功');
            
            // 檢查 URL 參數，如果有餐廳信息，則在地圖上標示
            const urlParams = new URLSearchParams(window.location.search);
            const restaurantName = urlParams.get('restaurant');
            const restaurantLat = urlParams.get('lat');
            const restaurantLng = urlParams.get('lng');
            const restaurantAddress = urlParams.get('address');
            
            if (restaurantName && restaurantLat && restaurantLng) {
                // 延遲一下確保地圖已完全初始化
                setTimeout(() => {
                    campusMap.showRestaurantFromURL(restaurantName, parseFloat(restaurantLat), parseFloat(restaurantLng), restaurantAddress);
                }, 1000);
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
