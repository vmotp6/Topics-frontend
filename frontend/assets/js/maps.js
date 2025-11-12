/**
 * 康寧大學校園地圖 JavaScript
 * 整合 Google Maps API 功能
 */

class CampusMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.directionsService = null;
        this.directionsRenderer = null;
        this.placesService = null;
        this.currentMode = 'driving';
        this.campusLocations = {};
        
        this.init();
    }

    async init() {
        try {
            // 載入校園位置資料
            await this.loadCampusLocations();
            
            // 初始化地圖
            this.initMap();
            
            // 設定事件監聽器
            this.setupEventListeners();
            
            // 隱藏載入畫面
            this.hideLoading();
            
        } catch (error) {
            console.error('地圖初始化失敗:', error);
            this.showError('地圖載入失敗，請重新整理頁面');
        }
    }

    async loadCampusLocations() {
        try {
            const response = await fetch('/api/maps/campus-locations');
            const data = await response.json();
            
            if (data.success) {
                this.campusLocations = data.locations;
                this.populateCampusSelect();
            } else {
                throw new Error(data.error || '無法載入校園位置資料');
            }
        } catch (error) {
            console.error('載入校園位置失敗:', error);
            // 使用預設位置資料
            this.campusLocations = {
                'main_campus': {
                    'name': '康寧大學校本部',
                    'address': '台北市內湖區康寧路三段75巷137號',
                    'lat': 25.07575358359577,
                    'lng': 121.60949282881778,
                    'description': '康寧大學主要校區'
                },
                'tamsui_campus': {
                    'name': '康寧大學淡水校區',
                    'address': '新北市淡水區學府路36號',
                    'lat': 25.1753,
                    'lng': 121.4505,
                    'description': '康寧大學淡水校區'
                }
            };
            this.populateCampusSelect();
        }
    }

    populateCampusSelect() {
        const select = document.getElementById('campus-select');
        if (!select) return;

        // 清空現有選項（保留"所有校區"選項）
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // 添加校區選項
        Object.keys(this.campusLocations).forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = this.campusLocations[key].name;
            select.appendChild(option);
        });
    }

    initMap() {
        // 檢查是否有 API Key
        if (!GOOGLE_MAPS_API_KEY) {
            console.error('Google Maps API Key 未設定');
            this.showMapError('Google Maps API Key 未設定，請聯繫管理員');
            return;
        }

        // 預設中心點（康寧大學校本部）
        const defaultLocation = this.campusLocations.main_campus || {
            lat: 25.07575358359577,
            lng: 121.60949282881778
        };

        this.map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: defaultLocation.lat, lng: defaultLocation.lng },
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            streetViewControl: true, // 啟用街景控制
            mapTypeControl: true,
            fullscreenControl: true,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });

        // 初始化街景
        this.streetView = new google.maps.StreetViewPanorama(
            document.getElementById('map'), {
                position: { lat: defaultLocation.lat, lng: defaultLocation.lng },
                pov: { heading: 0, pitch: 0 },
                visible: false
            }
        );

        // 初始化服務
        this.directionsService = new google.maps.DirectionsService();
        this.directionsRenderer = new google.maps.DirectionsRenderer({
            draggable: true,
            suppressMarkers: false
        });
        this.directionsRenderer.setMap(this.map);

        // 初始化 Places 服務
        this.placesService = new google.maps.places.PlacesService(this.map);

        // 添加校園標記
        this.addCampusMarkers();

        // 添加街景切換功能
        this.addStreetViewToggle();
    }

    addStreetViewToggle() {
        // 添加街景切換按鈕
        const streetViewBtn = document.createElement('button');
        streetViewBtn.innerHTML = '<i class="fas fa-street-view"></i> 切換街景';
        streetViewBtn.className = 'btn btn-primary';
        streetViewBtn.style.position = 'absolute';
        streetViewBtn.style.top = '10px';
        streetViewBtn.style.right = '10px';
        streetViewBtn.style.zIndex = '1000';
        streetViewBtn.onclick = () => this.toggleStreetView();
        
        document.querySelector('.map-container').appendChild(streetViewBtn);
    }

    toggleStreetView() {
        if (this.streetView.getVisible()) {
            this.streetView.setVisible(false);
            this.map.setVisible(true);
        } else {
            this.streetView.setVisible(true);
            this.map.setVisible(false);
        }
    }

    addCampusMarkers() {
        Object.keys(this.campusLocations).forEach(key => {
            const location = this.campusLocations[key];
            
            const marker = new google.maps.Marker({
                position: { lat: location.lat, lng: location.lng },
                map: this.map,
                title: location.name,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="20" cy="20" r="18" fill="#e74c3c" stroke="white" stroke-width="2"/>
                            <text x="20" y="26" text-anchor="middle" fill="white" font-size="12" font-weight="bold">校</text>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(40, 40)
                }
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 10px; max-width: 250px;">
                        <h3 style="margin: 0 0 8px 0; color: #333;">${location.name}</h3>
                        <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;">
                            <i class="fas fa-map-marker-alt"></i> ${location.address}
                        </p>
                        <p style="margin: 0; color: #666; font-size: 14px;">${location.description}</p>
                        <div style="margin-top: 10px;">
                            <button onclick="campusMap.getDirections('${location.address}')" 
                                    style="background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                規劃路線
                            </button>
                        </div>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });

            this.markers.push(marker);
        });
    }

    setupEventListeners() {
        // 校區選擇
        const campusSelect = document.getElementById('campus-select');
        if (campusSelect) {
            campusSelect.addEventListener('change', (e) => {
                this.onCampusSelect(e.target.value);
            });
        }

        // 搜尋功能
        const searchBtn = document.getElementById('search-btn');
        const searchInput = document.getElementById('search-input');
        
        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', () => {
                this.searchLocation(searchInput.value);
            });

            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchLocation(searchInput.value);
                }
            });
        }

        // 交通方式選擇
        const transportBtns = document.querySelectorAll('.transport-btn');
        transportBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTransportMode(e.target.dataset.mode);
            });
        });
    }

    onCampusSelect(campusKey) {
        if (campusKey === 'all') {
            // 顯示所有校區
            this.showAllCampuses();
        } else if (this.campusLocations[campusKey]) {
            // 聚焦到特定校區
            const location = this.campusLocations[campusKey];
            this.map.setCenter({ lat: location.lat, lng: location.lng });
            this.map.setZoom(16);
            
            // 更新校園資訊面板
            this.updateCampusInfo(location);
        }
    }

    showAllCampuses() {
        if (this.markers.length === 0) return;

        const bounds = new google.maps.LatLngBounds();
        this.markers.forEach(marker => {
            bounds.extend(marker.getPosition());
        });
        
        this.map.fitBounds(bounds);
        this.map.setZoom(Math.min(this.map.getZoom(), 12));
    }

    updateCampusInfo(location) {
        const campusInfo = document.getElementById('campus-info');
        if (!campusInfo) return;

        campusInfo.innerHTML = `
            <div class="campus-card">
                <h4>${location.name}</h4>
                <p><i class="fas fa-map-marker-alt"></i> ${location.address}</p>
                <p><i class="fas fa-phone"></i> (02) 2632-1181</p>
                <p><i class="fas fa-globe"></i> <a href="https://www.ukn.edu.tw" target="_blank">www.ukn.edu.tw</a></p>
                <div class="campus-features">
                    <span class="feature-tag">圖書館</span>
                    <span class="feature-tag">體育館</span>
                    <span class="feature-tag">餐廳</span>
                    <span class="feature-tag">停車場</span>
                </div>
            </div>
        `;
    }

    async searchLocation(query) {
        if (!query.trim()) return;

        try {
            const response = await fetch('/api/maps/geocode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ address: query })
            });

            const data = await response.json();

            if (data.success) {
                const location = data.location;
                
                // 移動地圖到搜尋位置
                this.map.setCenter({ lat: location.lat, lng: location.lng });
                this.map.setZoom(16);

                // 添加搜尋結果標記
                this.addSearchMarker(location, data.formatted_address);
                
                // 顯示搜尋結果資訊
                this.showSearchResult(data.formatted_address);
            } else {
                this.showError('找不到該地址，請嘗試其他關鍵字');
            }
        } catch (error) {
            console.error('搜尋失敗:', error);
            this.showError('搜尋失敗，請稍後再試');
        }
    }

    addSearchMarker(location, address) {
        // 移除之前的搜尋標記
        this.clearSearchMarkers();

        const marker = new google.maps.Marker({
            position: { lat: location.lat, lng: location.lng },
            map: this.map,
            title: address,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="15" cy="15" r="12" fill="#667eea" stroke="white" stroke-width="2"/>
                        <text x="15" y="19" text-anchor="middle" fill="white" font-size="10" font-weight="bold">搜</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(30, 30)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px; max-width: 250px;">
                    <h3 style="margin: 0 0 8px 0; color: #333;">搜尋結果</h3>
                    <p style="margin: 0; color: #666; font-size: 14px;">${address}</p>
                    <div style="margin-top: 10px;">
                        <button onclick="campusMap.getDirections('${address}')" 
                                style="background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                            規劃路線
                        </button>
                    </div>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        this.searchMarkers = this.searchMarkers || [];
        this.searchMarkers.push(marker);
    }

    clearSearchMarkers() {
        if (this.searchMarkers) {
            this.searchMarkers.forEach(marker => marker.setMap(null));
            this.searchMarkers = [];
        }
    }

    showSearchResult(address) {
        const directionsInfo = document.getElementById('directions-info');
        const directionsContent = document.getElementById('directions-content');
        
        if (!directionsInfo || !directionsContent) return;

        directionsInfo.style.display = 'block';
        directionsContent.innerHTML = `
            <div class="search-result">
                <p><strong>搜尋結果：</strong>${address}</p>
                <button class="btn btn-primary" onclick="campusMap.getDirections('${address}')">
                    <i class="fas fa-route"></i> 規劃路線
                </button>
            </div>
        `;
    }

    setTransportMode(mode) {
        this.currentMode = mode;
        
        // 更新按鈕狀態
        document.querySelectorAll('.transport-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-mode="${mode}"]`).classList.add('active');
    }

    async getDirections(destination) {
        if (!destination) return;

        try {
            // 獲取用戶當前位置
            const userLocation = await this.getCurrentLocation();
            const origin = userLocation ? `${userLocation.lat},${userLocation.lng}` : '台北車站';

            const response = await fetch('/api/maps/directions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    origin: origin,
                    destination: destination,
                    mode: this.currentMode
                })
            });

            const data = await response.json();

            if (data.success) {
                this.displayDirections(data.directions);
            } else {
                this.showError('路線規劃失敗：' + data.error);
            }
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

    displayDirections(directions) {
        const directionsInfo = document.getElementById('directions-info');
        const directionsContent = document.getElementById('directions-content');
        
        if (!directionsInfo || !directionsContent) return;

        directionsInfo.style.display = 'block';
        
        let html = `
            <div class="directions-summary">
                <p><strong>距離：</strong>${directions.distance}</p>
                <p><strong>預估時間：</strong>${directions.duration}</p>
                <p><strong>起點：</strong>${directions.start_address}</p>
                <p><strong>終點：</strong>${directions.end_address}</p>
            </div>
            <div class="directions-steps">
        `;

        directions.steps.forEach((step, index) => {
            html += `
                <div class="direction-step">
                    <strong>步驟 ${index + 1}：</strong>
                    <p>${step.instruction.replace(/<[^>]*>/g, '')}</p>
                    <small>${step.distance} - ${step.duration}</small>
                </div>
            `;
        });

        html += '</div>';
        directionsContent.innerHTML = html;
    }

    showFacilityOnMap(facilityType) {
        const facilityLocations = {
            'library': { lat: 25.0759, lng: 121.6096, name: '圖書館' },
            'gym': { lat: 25.0756, lng: 121.6093, name: '體育館' },
            'restaurant': { lat: 25.0758, lng: 121.6095, name: '學生餐廳' },
            'parking': { lat: 25.0754, lng: 121.6091, name: '停車場' }
        };

        const location = facilityLocations[facilityType];
        if (!location) return;

        // 移動地圖到設施位置
        this.map.setCenter({ lat: location.lat, lng: location.lng });
        this.map.setZoom(18);

        // 添加設施標記
        const marker = new google.maps.Marker({
            position: { lat: location.lat, lng: location.lng },
            map: this.map,
            title: location.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="35" height="35" viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="17.5" cy="17.5" r="15" fill="#27ae60" stroke="white" stroke-width="2"/>
                        <text x="17.5" y="22" text-anchor="middle" fill="white" font-size="10" font-weight="bold">設</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(35, 35)
            }
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px; max-width: 200px;">
                    <h3 style="margin: 0 0 8px 0; color: #333;">${location.name}</h3>
                    <p style="margin: 0; color: #666; font-size: 14px;">康寧大學校園設施</p>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(this.map, marker);
        });

        // 3秒後自動關閉資訊視窗
        setTimeout(() => {
            infoWindow.open(this.map, marker);
        }, 500);
    }

    hideLoading() {
        const loading = document.getElementById('map-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showError(message) {
        // 可以在這裡添加錯誤提示的 UI
        console.error(message);
        alert(message);
    }
}

// 全域變數，供 HTML 中的 onclick 事件使用
let campusMap;

// 全域初始化函數，供 Google Maps API 回調使用
function initMap() {
    if (typeof google !== 'undefined' && google.maps) {
        // 檢查 API Key
        if (!GOOGLE_MAPS_API_KEY) {
            console.error('Google Maps API Key 未設定');
            showMapError('Google Maps API Key 未設定，請聯繫管理員');
            return;
        }
        campusMap = new CampusMap();
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
