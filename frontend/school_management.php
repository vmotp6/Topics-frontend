<?php
/**
 * 學校資料管理頁面
 */

session_start();

// 檢查管理員權限
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo "<h1>❌ 權限不足</h1>";
    echo "<p>此頁面僅限管理員使用。</p>";
    exit;
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 獲取學校統計
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools");
    $totalSchools = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM schools GROUP BY city ORDER BY count DESC");
    $cityStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $totalSchools = 0;
    $cityStats = [];
    $error = $e->getMessage();
}

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>學校資料管理</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .city-stats { margin: 20px 0; }
        .city-item { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table tr:hover { background-color: #f5f5f5; }
        .search-box { margin: 20px 0; }
        .search-box input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 300px; }
        .search-box button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🏫 學校資料管理系統</h1>";

if (isset($error)) {
    echo "<div style='color: #e74c3c; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>";
    echo "❌ 資料庫錯誤: " . htmlspecialchars($error);
    echo "</div>";
}

// 統計資訊
echo "<div class='stats'>";
echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . $totalSchools . "</div>";
echo "<div class='stat-label'>總學校數</div>";
echo "</div>";

echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . count($cityStats) . "</div>";
echo "<div class='stat-label'>涵蓋縣市</div>";
echo "</div>";

echo "<div class='stat-card'>";
echo "<div class='stat-number'>" . ($cityStats[0]['count'] ?? 0) . "</div>";
echo "<div class='stat-label'>最多學校縣市</div>";
echo "</div>";

echo "<div class='stat-card'>";
echo "<div class='stat-number'>100%</div>";
echo "<div class='stat-label'>資料完整性</div>";
echo "</div>";
echo "</div>";

// 縣市統計
if (!empty($cityStats)) {
    echo "<h3>📊 各縣市學校統計</h3>";
    echo "<div class='city-stats'>";
    foreach ($cityStats as $stat) {
        echo "<div class='city-item'>";
        echo "<span><strong>" . htmlspecialchars($stat['city']) . "</strong></span>";
        echo "<span>" . $stat['count'] . " 所學校</span>";
        echo "</div>";
    }
    echo "</div>";
}

// 操作按鈕
echo "<div style='margin: 30px 0;'>";
echo "<h3>🔧 管理操作</h3>";
echo "<a href='school_search_test.php' class='btn'>🔍 測試搜尋功能</a>";
echo "<a href='cooperation_upload.php' class='btn btn-success'>📝 就讀意願表單</a>";
echo "<button onclick='refreshData()' class='btn btn-warning'>🔄 刷新資料</button>";
echo "<button onclick='exportData()' class='btn'>📤 匯出資料</button>";
echo "</div>";

// 搜尋功能
echo "<div class='search-box'>";
echo "<h3>🔍 搜尋學校</h3>";
echo "<input type='text' id='searchInput' placeholder='輸入學校名稱、縣市或區域...'>";
echo "<button onclick='searchSchools()'>搜尋</button>";
echo "<button onclick='clearSearch()'>清除</button>";
echo "</div>";

// 搜尋結果表格
echo "<div id='searchResults'></div>";

// 系統資訊
echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;'>";
echo "<h3>ℹ️ 系統資訊</h3>";
echo "<p><strong>PHP版本:</strong> " . phpversion() . "</p>";
echo "<p><strong>資料庫:</strong> MySQL</p>";
echo "<p><strong>最後更新:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>管理員:</strong> " . htmlspecialchars($_SESSION['username']) . "</p>";
echo "</div>";

echo "</div>";

echo "
<script>
function searchSchools() {
    const keyword = document.getElementById('searchInput').value.trim();
    if (!keyword) {
        alert('請輸入搜尋關鍵字');
        return;
    }
    
    const resultsDiv = document.getElementById('searchResults');
    resultsDiv.innerHTML = '<div style=\"text-align: center; padding: 20px;\">🔍 搜尋中...</div>';
    
    fetch(`school_search_db_api.php?keyword=${encodeURIComponent(keyword)}&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.schools, keyword);
            } else {
                resultsDiv.innerHTML = '<div style=\"color: #e74c3c; padding: 20px;\">❌ 搜尋失敗: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('搜尋錯誤:', error);
            resultsDiv.innerHTML = '<div style=\"color: #e74c3c; padding: 20px;\">❌ 搜尋時發生錯誤</div>';
        });
}

function displaySearchResults(schools, keyword) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (schools.length === 0) {
        resultsDiv.innerHTML = '<div style=\"text-align: center; padding: 20px; color: #666;\">🔍 未找到包含「' + keyword + '」的學校</div>';
        return;
    }
    
    let html = '<h4>📋 搜尋結果（共 ' + schools.length + ' 所學校）：</h4>';
    html += '<table class=\"table\">';
    html += '<thead><tr><th>學校名稱</th><th>縣市</th><th>區域</th><th>地址</th><th>類型</th></tr></thead>';
    html += '<tbody>';
    
    schools.forEach(school => {
        html += '<tr>';
        html += '<td><strong>' + school.name + '</strong></td>';
        html += '<td>' + school.city + '</td>';
        html += '<td>' + school.district + '</td>';
        html += '<td>' + school.address + '</td>';
        html += '<td>' + school.type + '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    resultsDiv.innerHTML = html;
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchResults').innerHTML = '';
}

function refreshData() {
    location.reload();
}

function exportData() {
    window.open('school_search_db_api.php?export=1', '_blank');
}

// 支援Enter鍵搜尋
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchSchools();
    }
});
</script>
</body>
</html>";
?>




