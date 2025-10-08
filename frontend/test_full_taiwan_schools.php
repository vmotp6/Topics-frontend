<?php
/**
 * 全台國民中學搜尋測試頁面
 * 測試台灣教育部API整合後的搜尋功能
 */

// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 獲取統計資料
$stats = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學'");
    $stats['total'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC");
    $stats['cities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT data_source, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY data_source");
    $stats['sources'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total' => 0, 'cities' => [], 'sources' => []];
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全台國民中學搜尋測試</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007cba 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #007cba;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007cba;
            margin: 0;
        }
        
        .stat-label {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 0.9em;
        }
        
        .search-section {
            padding: 30px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 30px;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 3px rgba(0,124,186,0.1);
        }
        
        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .results-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            background: white;
        }
        
        .result-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .result-item:hover {
            background-color: #f8f9fa;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .school-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .school-info {
            color: #666;
            font-size: 0.9em;
        }
        
        .school-code {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .test-section {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .test-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .test-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .test-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .keyword-tag {
            background: #007cba;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .keyword-tag:hover {
            background: #0056b3;
        }
        
        .actions {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 5px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-school"></i> 全台國民中學搜尋測試</h1>
            <p>整合台灣教育部開放資料的完整搜尋系統</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">國民中學總數</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($stats['cities']); ?></div>
                <div class="stat-label">涵蓋縣市</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($stats['sources']); ?></div>
                <div class="stat-label">資料來源</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">覆蓋率</div>
            </div>
        </div>
        
        <div class="search-section">
            <h2><i class="fas fa-search"></i> 即時搜尋測試</h2>
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="輸入學校名稱、縣市、地址或代碼進行搜尋...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div id="searchResults" class="results-container" style="display: none;"></div>
        </div>
        
        <div class="test-section">
            <h2><i class="fas fa-flask"></i> 搜尋測試</h2>
            <p>點擊下方關鍵字測試不同類型的搜尋功能：</p>
            
            <div class="test-grid">
                <div class="test-card">
                    <div class="test-title">學校名稱搜尋</div>
                    <div class="test-keywords">
                        <span class="keyword-tag" onclick="testSearch('中正')">中正</span>
                        <span class="keyword-tag" onclick="testSearch('板橋')">板橋</span>
                        <span class="keyword-tag" onclick="testSearch('桃園')">桃園</span>
                        <span class="keyword-tag" onclick="testSearch('中崙')">中崙</span>
                        <span class="keyword-tag" onclick="testSearch('西松')">西松</span>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-title">縣市搜尋</div>
                    <div class="test-keywords">
                        <span class="keyword-tag" onclick="testSearch('台北市')">台北市</span>
                        <span class="keyword-tag" onclick="testSearch('新北市')">新北市</span>
                        <span class="keyword-tag" onclick="testSearch('桃園市')">桃園市</span>
                        <span class="keyword-tag" onclick="testSearch('台中市')">台中市</span>
                        <span class="keyword-tag" onclick="testSearch('高雄市')">高雄市</span>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-title">區域搜尋</div>
                    <div class="test-keywords">
                        <span class="keyword-tag" onclick="testSearch('中正區')">中正區</span>
                        <span class="keyword-tag" onclick="testSearch('板橋區')">板橋區</span>
                        <span class="keyword-tag" onclick="testSearch('松山區')">松山區</span>
                        <span class="keyword-tag" onclick="testSearch('信義區')">信義區</span>
                        <span class="keyword-tag" onclick="testSearch('桃園區')">桃園區</span>
                    </div>
                </div>
                
                <div class="test-card">
                    <div class="test-title">學校代碼搜尋</div>
                    <div class="test-keywords">
                        <span class="keyword-tag" onclick="testSearch('TP001')">TP001</span>
                        <span class="keyword-tag" onclick="testSearch('NT001')">NT001</span>
                        <span class="keyword-tag" onclick="testSearch('TY001')">TY001</span>
                        <span class="keyword-tag" onclick="testSearch('TC001')">TC001</span>
                        <span class="keyword-tag" onclick="testSearch('KS001')">KS001</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="cooperation_upload.php" class="btn btn-success">
                <i class="fas fa-graduation-cap"></i> 測試就讀意願登錄
            </a>
            <a href="integrate_government_api.php" class="btn">
                <i class="fas fa-sync-alt"></i> 更新學校資料
            </a>
            <a href="diagnose_school_search.php" class="btn btn-secondary">
                <i class="fas fa-stethoscope"></i> 診斷工具
            </a>
        </div>
    </div>

    <script>
        let searchTimeout;
        
        // 搜尋功能
        function performSearch(keyword) {
            const resultsDiv = document.getElementById('searchResults');
            
            if (!keyword || keyword.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }
            
            // 顯示載入中
            resultsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> 搜尋中...</div>';
            resultsDiv.style.display = 'block';
            
            // 發送搜尋請求
            fetch(`api/school_data_api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.schools && data.schools.length > 0) {
                        resultsDiv.innerHTML = data.schools.map(school => `
                            <div class="result-item" onclick="selectSchool('${school.name}', '${school.city}', '${school.district}')">
                                <div class="school-name">
                                    ${school.name}
                                    <span class="school-code">${school.school_code || 'N/A'}</span>
                                </div>
                                <div class="school-info">
                                    <i class="fas fa-map-marker-alt"></i> ${school.city} ${school.district}
                                    ${school.address ? `<br><i class="fas fa-home"></i> ${school.address}` : ''}
                                    ${school.phone ? `<br><i class="fas fa-phone"></i> ${school.phone}` : ''}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        resultsDiv.innerHTML = '<div class="no-results"><i class="fas fa-search"></i><br>找不到匹配的學校</div>';
                    }
                })
                .catch(error => {
                    console.error('搜尋錯誤:', error);
                    resultsDiv.innerHTML = '<div class="no-results"><i class="fas fa-exclamation-triangle"></i><br>搜尋失敗，請稍後再試</div>';
                });
        }
        
        // 測試搜尋
        function testSearch(keyword) {
            document.getElementById('searchInput').value = keyword;
            performSearch(keyword);
        }
        
        // 選擇學校
        function selectSchool(schoolName, city, district) {
            const fullSchoolName = `${schoolName} (${city}${district})`;
            alert(`已選擇：${fullSchoolName}`);
        }
        
        // 綁定搜尋事件
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value.trim());
            }, 300);
        });
        
        // 點擊其他地方隱藏搜尋結果
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-section')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    </script>
</body>
</html>
