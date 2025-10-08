<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>學校搜尋API測試</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Microsoft JhengHei', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .search-container {
            margin-bottom: 30px;
        }
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .search-input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover {
            background: #218838;
        }
        .results {
            margin-top: 20px;
        }
        .school-item {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .school-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        .school-location {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .stats {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-school"></i> 學校搜尋API測試</h1>
        
        <div class="stats">
            <h3>API功能說明</h3>
            <ul>
                <li>✅ 即時搜尋全台灣國民中學</li>
                <li>✅ 支援模糊匹配搜尋</li>
                <li>✅ 按城市篩選學校</li>
                <li>✅ 顯示學校名稱、城市、區域</li>
                <li>✅ 資料來源：台灣教育部開放資料</li>
            </ul>
            <p style="margin-top: 15px;">
                <a href="city_schools.php" class="btn" style="margin-right: 10px;">
                    <i class="fas fa-map-marker-alt"></i> 城市學校瀏覽
                </a>
                <a href="admin_school_data.php" class="btn btn-secondary">
                    <i class="fas fa-cogs"></i> 管理介面
                </a>
            </p>
        </div>
        
        <div class="search-container">
            <div style="margin-bottom: 15px;">
                <label for="citySelect" style="display: block; margin-bottom: 5px; font-weight: 600;">選擇城市：</label>
                <select id="citySelect" class="search-input" onchange="loadSchoolsByCity()">
                    <option value="">-- 請選擇城市 --</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="searchInput" style="display: block; margin-bottom: 5px; font-weight: 600;">搜尋學校：</label>
                <input type="text" id="searchInput" class="search-input" placeholder="請輸入學校名稱關鍵字，例如：中正、西松、永吉...">
                <button onclick="searchSchools()" class="btn" style="margin-top: 10px;">
                    <i class="fas fa-search"></i> 搜尋學校
                </button>
            </div>
        </div>
        
        <div id="results" class="results"></div>
    </div>
    
    <script>
        // 載入城市列表
        function loadCities() {
            fetch('api/school_data_api.php?action=cities')
                .then(response => response.json())
                .then(data => {
                    const citySelect = document.getElementById('citySelect');
                    citySelect.innerHTML = '<option value="">-- 請選擇城市 --</option>';
                    
                    data.cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        citySelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('載入城市列表失敗:', error);
                });
        }
        
        // 根據城市載入學校
        function loadSchoolsByCity() {
            const city = document.getElementById('citySelect').value;
            const resultsDiv = document.getElementById('results');
            
            if (!city) {
                resultsDiv.innerHTML = '';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> 載入中...</div>';
            
            fetch(`api/school_data_api.php?action=schools_by_city&city=${encodeURIComponent(city)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.schools && data.schools.length > 0) {
                        resultsDiv.innerHTML = `
                            <div class="stats">
                                <strong>${data.city} 的國民中學：</strong>共 ${data.total} 所
                            </div>
                            ${data.schools.map(school => `
                                <div class="school-item">
                                    <div class="school-name">
                                        <i class="fas fa-school"></i> ${school.name}
                                    </div>
                                    <div class="school-location">
                                        📍 ${school.city} ${school.district} | 類型：${school.type}
                                    </div>
                                </div>
                            `).join('')}
                        `;
                    } else {
                        resultsDiv.innerHTML = `<div class="error">${city} 沒有找到國民中學</div>`;
                    }
                })
                .catch(error => {
                    console.error('載入學校列表失敗:', error);
                    resultsDiv.innerHTML = '<div class="error">載入失敗，請稍後再試</div>';
                });
        }
        
        function searchSchools() {
            const keyword = document.getElementById('searchInput').value.trim();
            const resultsDiv = document.getElementById('results');
            
            if (keyword.length < 2) {
                resultsDiv.innerHTML = '<div class="error">請輸入至少2個字元</div>';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
            
            fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.schools && data.schools.length > 0) {
                        resultsDiv.innerHTML = `
                            <div class="stats">
                                <strong>搜尋結果：</strong>找到 ${data.total} 所學校
                            </div>
                            ${data.schools.map(school => `
                                <div class="school-item">
                                    <div class="school-name">
                                        <i class="fas fa-school"></i> ${school.name}
                                    </div>
                                    <div class="school-location">
                                        📍 ${school.city} ${school.district} | 類型：${school.type}
                                    </div>
                                </div>
                            `).join('')}
                        `;
                    } else {
                        resultsDiv.innerHTML = '<div class="error">找不到匹配的學校</div>';
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    resultsDiv.innerHTML = '<div class="error">搜尋失敗，請稍後再試</div>';
                });
        }
        
        // 按Enter鍵搜尋
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchSchools();
            }
        });
        
        // 測試不同關鍵字
        function testKeywords() {
            const testWords = ['中正', '中崙', '西松', '永吉', '板橋', '桃園', '基隆', '新竹', '台中', '高雄'];
            const randomWord = testWords[Math.floor(Math.random() * testWords.length)];
            document.getElementById('searchInput').value = randomWord;
            searchSchools();
        }
        
        // 頁面載入時初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 載入城市列表
            loadCities();
            
            // 檢查URL參數
            const urlParams = new URLSearchParams(window.location.search);
            const cityParam = urlParams.get('city');
            
            if (cityParam) {
                // 如果有城市參數，自動選擇該城市並載入學校
                setTimeout(() => {
                    document.getElementById('citySelect').value = cityParam;
                    loadSchoolsByCity();
                }, 500);
            } else {
                // 顯示說明
                document.getElementById('results').innerHTML = `
                    <div class="stats">
                        <h3>🎯 使用說明</h3>
                        <p><strong>方法1：按城市瀏覽</strong></p>
                        <p>選擇上方城市下拉選單，查看該城市的所有國民中學</p>
                        
                        <p><strong>方法2：關鍵字搜尋</strong></p>
                        <p>您可以嘗試搜尋以下關鍵字：</p>
                        <ul>
                            <li><strong>中正</strong> - 會找到中正國中等</li>
                            <li><strong>中崙</strong> - 會找到中崙國中</li>
                            <li><strong>西松</strong> - 會找到西松國中</li>
                            <li><strong>永吉</strong> - 會找到永吉國中</li>
                            <li><strong>板橋</strong> - 會找到板橋國中、海山國中等</li>
                            <li><strong>桃園</strong> - 會找到桃園國中、中壢國中等</li>
                            <li><strong>台中</strong> - 會找到台中一中、台中女中等</li>
                            <li><strong>高雄</strong> - 會找到高雄中學、高雄女中等</li>
                        </ul>
                        <button onclick="testKeywords()" class="btn" style="margin-top: 10px;">
                            <i class="fas fa-dice"></i> 隨機測試
                        </button>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>
