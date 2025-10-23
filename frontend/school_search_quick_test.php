<?php
/**
 * 學校搜尋快速測試頁面
 */

echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>學校搜尋快速測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .search-box { margin: 20px 0; }
        .search-box input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; margin-bottom: 10px; }
        .search-btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .search-btn:hover { background: #0056b3; }
        .search-btn:disabled { background: #6c757d; cursor: not-allowed; }
        .results { margin-top: 20px; }
        .school-item { padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; background: white; }
        .school-name { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .school-info { color: #666; font-size: 14px; }
        .no-results { text-align: center; color: #e74c3c; padding: 20px; font-size: 16px; }
        .loading { text-align: center; color: #007bff; padding: 20px; }
        .test-buttons { margin: 20px 0; }
        .test-btn { background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #1e7e34; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🏫 學校搜尋快速測試</h1>";

echo "<div class='test-buttons'>";
echo "<button class='test-btn' onclick='testSearch(\"南港\")'>測試：南港</button>";
echo "<button class='test-btn' onclick='testSearch(\"中正\")'>測試：中正</button>";
echo "<button class='test-btn' onclick='testSearch(\"台北\")'>測試：台北</button>";
echo "<button class='test-btn' onclick='testSearch(\"高雄\")'>測試：高雄</button>";
echo "<button class='test-btn' onclick='testSearch(\"\")'>顯示全部</button>";
echo "</div>";

echo "<div class='search-box'>";
echo "<input type='text' id='searchInput' placeholder='請輸入學校名稱、縣市或區域關鍵字...'>";
echo "<button onclick='searchSchools()' class='search-btn' id='searchBtn'>搜尋學校</button>";
echo "</div>";

echo "<div id='results' class='results'></div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='cooperation_upload.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>返回就讀意願表單</a>";
echo "</div>";

echo "</div>";

echo "
<script>
function testSearch(keyword) {
    document.getElementById('searchInput').value = keyword;
    searchSchools();
}

function searchSchools() {
    const keyword = document.getElementById('searchInput').value.trim();
    const resultsDiv = document.getElementById('results');
    const searchBtn = document.getElementById('searchBtn');
    
    // 顯示載入中
    searchBtn.textContent = '搜尋中...';
    searchBtn.disabled = true;
    resultsDiv.innerHTML = '<div class=\"loading\">🔍 搜尋中，請稍候...</div>';
    
    // 構建搜尋URL
    let url = 'school_search_simple.php?';
    if (keyword) url += 'keyword=' + encodeURIComponent(keyword);
    
    // 發送搜尋請求
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.schools, keyword);
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

function displayResults(schools, keyword) {
    const resultsDiv = document.getElementById('results');
    
    if (schools.length === 0) {
        let message = '未找到符合條件的學校';
        if (keyword) message += '（關鍵字：' + keyword + '）';
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

// 頁面載入時顯示提示
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('results').innerHTML = `
        <div style=\"text-align: center; color: #666; padding: 20px;\">
            💡 提示：<br>
            • 點擊上方測試按鈕快速測試<br>
            • 輸入學校名稱關鍵字進行搜尋<br>
            • 支援部分關鍵字匹配<br>
            • 點擊「搜尋學校」開始搜尋
        </div>
    `;
});
</script>
</body>
</html>";
?>



















