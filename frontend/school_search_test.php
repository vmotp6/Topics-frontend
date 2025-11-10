<?php
/**
 * 學校搜尋功能測試頁面
 */

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>台灣國中搜尋測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .search-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f8f9fa; }
        .search-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; margin-bottom: 10px; }
        .search-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .search-btn:hover { background: #0056b3; }
        .search-btn:disabled { background: #6c757d; cursor: not-allowed; }
        .results { margin-top: 20px; }
        .school-item { padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; background: white; }
        .school-name { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .school-info { color: #666; font-size: 14px; }
        .no-results { text-align: center; color: #e74c3c; padding: 20px; font-size: 16px; }
        .loading { text-align: center; color: #007bff; padding: 20px; }
        .stats { background: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .city-filter { margin-bottom: 15px; }
        .city-filter select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🏫 台灣國中搜尋系統測試</h1>";

echo "<div class='stats'>";
echo "<h3>📊 系統統計</h3>";
echo "<p><strong>資料庫包含：</strong>全台灣各縣市主要國中</p>";
echo "<p><strong>搜尋範圍：</strong>學校名稱、縣市、區域</p>";
echo "<p><strong>支援功能：</strong>關鍵字搜尋、縣市篩選、即時搜尋</p>";
echo "</div>";

echo "<div class='search-section'>";
echo "<h3>🔍 搜尋學校</h3>";

echo "<div class='city-filter'>";
echo "<label for='cityFilter'>縣市篩選：</label>";
echo "<select id='cityFilter'>";
echo "<option value=''>全部縣市</option>";
echo "<option value='台北市'>台北市</option>";
echo "<option value='新北市'>新北市</option>";
echo "<option value='桃園市'>桃園市</option>";
echo "<option value='台中市'>台中市</option>";
echo "<option value='台南市'>台南市</option>";
echo "<option value='高雄市'>高雄市</option>";
echo "<option value='基隆市'>基隆市</option>";
echo "<option value='新竹市'>新竹市</option>";
echo "<option value='嘉義市'>嘉義市</option>";
echo "<option value='新竹縣'>新竹縣</option>";
echo "<option value='苗栗縣'>苗栗縣</option>";
echo "<option value='彰化縣'>彰化縣</option>";
echo "<option value='南投縣'>南投縣</option>";
echo "<option value='雲林縣'>雲林縣</option>";
echo "<option value='嘉義縣'>嘉義縣</option>";
echo "<option value='屏東縣'>屏東縣</option>";
echo "<option value='宜蘭縣'>宜蘭縣</option>";
echo "<option value='花蓮縣'>花蓮縣</option>";
echo "<option value='台東縣'>台東縣</option>";
echo "<option value='澎湖縣'>澎湖縣</option>";
echo "<option value='金門縣'>金門縣</option>";
echo "<option value='連江縣'>連江縣</option>";
echo "</select>";
echo "</div>";

echo "<input type='text' id='searchInput' class='search-input' placeholder='請輸入學校名稱、縣市或區域關鍵字...'>";
echo "<button onclick='searchSchools()' class='search-btn' id='searchBtn'>搜尋學校</button>";

echo "<div id='results' class='results'></div>";
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='cooperation_upload.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>返回就讀意願表單</a>";
echo "</div>";

echo "</div>";

echo "
<script>
function searchSchools() {
    const keyword = document.getElementById('searchInput').value.trim();
    const city = document.getElementById('cityFilter').value;
    const resultsDiv = document.getElementById('results');
    const searchBtn = document.getElementById('searchBtn');
    
    // 顯示載入中
    searchBtn.textContent = '搜尋中...';
    searchBtn.disabled = true;
    resultsDiv.innerHTML = '<div class=\"loading\">🔍 搜尋中，請稍候...</div>';
    
    // 構建搜尋URL
    let url = 'school_search_api.php?';
    if (keyword) url += 'keyword=' + encodeURIComponent(keyword);
    if (city) url += (keyword ? '&' : '') + 'city=' + encodeURIComponent(city);
    
    // 發送搜尋請求
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.schools, keyword, city);
            } else {
                resultsDiv.innerHTML = '<div class=\"no-results\">❌ 搜尋失敗：' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('搜尋錯誤:', error);
            resultsDiv.innerHTML = '<div class=\"no-results\">❌ 搜尋時發生錯誤，請稍後再試</div>';
        })
        .finally(() => {
            searchBtn.textContent = '搜尋學校';
            searchBtn.disabled = false;
        });
}

function displayResults(schools, keyword, city) {
    const resultsDiv = document.getElementById('results');
    
    if (schools.length === 0) {
        let message = '未找到符合條件的學校';
        if (keyword) message += '（關鍵字：' + keyword + '）';
        if (city) message += '（縣市：' + city + '）';
        resultsDiv.innerHTML = '<div class=\"no-results\">🔍 ' + message + '</div>';
        return;
    }
    
    let html = '<h4>📋 搜尋結果（共 ' + schools.length + ' 所學校）：</h4>';
    
    schools.forEach(school => {
        html += `
            <div class=\"school-item\">
                <div class=\"school-name\">${school.name}</div>
                <div class=\"school-info\">
                    📍 ${school.city} ${school.district}<br>
                    🏠 ${school.address}
                </div>
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

// 支援Enter鍵搜尋
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchSchools();
    }
});

// 縣市篩選變更時自動搜尋
document.getElementById('cityFilter').addEventListener('change', function() {
    const keyword = document.getElementById('searchInput').value.trim();
    if (keyword) {
        searchSchools();
    }
});

// 頁面載入時顯示提示
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('results').innerHTML = `
        <div style=\"text-align: center; color: #666; padding: 20px;\">
            💡 提示：<br>
            • 輸入學校名稱關鍵字進行搜尋<br>
            • 可選擇特定縣市進行篩選<br>
            • 支援部分關鍵字匹配<br>
            • 點擊「搜尋學校」開始搜尋
        </div>
    `;
});
</script>
</body>
</html>";
?>






























