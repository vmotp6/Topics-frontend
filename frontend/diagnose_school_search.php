<?php
/**
 * 學校搜尋功能診斷和修復腳本
 * 檢查並修復 cooperation_upload.php 中的學校搜尋問題
 */

// 載入 session 配置
require_once 'session_config.php';

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

echo "<h1>🔍 學校搜尋功能診斷</h1>";

// 步驟1：檢查資料表
echo "<h2>步驟1：檢查資料表</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'school_data'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ school_data 表存在</p>";
        
        // 檢查資料數量
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data");
        $totalCount = $stmt->fetch()['count'];
        echo "<p>總資料數量：$totalCount</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_data WHERE type = '國民中學'");
        $juniorCount = $stmt->fetch()['count'];
        echo "<p>國民中學數量：$juniorCount</p>";
        
        if ($juniorCount == 0) {
            echo "<p style='color: red;'>❌ 沒有國民中學資料！</p>";
            echo "<p><a href='quick_init_schools.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>點此初始化學校資料</a></p>";
        } else {
            echo "<p style='color: green;'>✅ 有國民中學資料</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ school_data 表不存在！</p>";
        echo "<p><a href='create_school_table.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>點此創建資料表</a></p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>檢查資料表失敗: " . $e->getMessage() . "</p>";
}

// 步驟2：測試API
echo "<h2>步驟2：測試API</h2>";
if ($juniorCount > 0) {
    echo "<p>測試API端點：<code>api/school_data_api.php?action=search&keyword=中正</code></p>";
    
    // 模擬API請求
    $_GET['action'] = 'search';
    $_GET['keyword'] = '中正';
    
    ob_start();
    include 'api/school_data_api.php';
    $apiResponse = ob_get_clean();
    
    echo "<h3>API回應：</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($apiResponse);
    echo "</pre>";
    
    $response = json_decode($apiResponse, true);
    if ($response && isset($response['schools'])) {
        echo "<p style='color: green;'>✅ API正常運作，找到 " . count($response['schools']) . " 筆結果</p>";
    } else {
        echo "<p style='color: red;'>❌ API回應異常</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ 跳過API測試（沒有資料）</p>";
}

// 步驟3：測試前端搜尋
echo "<h2>步驟3：前端搜尋測試</h2>";
echo "<div id='searchTest' style='border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
echo "<h3>即時搜尋測試</h3>";
echo "<input type='text' id='testSearch' placeholder='輸入學校名稱測試...' style='width: 300px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>";
echo "<div id='testResults' style='margin-top: 10px;'></div>";
echo "</div>";

// JavaScript測試
echo "<script>";
echo "
document.getElementById('testSearch').addEventListener('input', function() {
    const keyword = this.value.trim();
    const resultsDiv = document.getElementById('testResults');
    
    if (keyword.length < 2) {
        resultsDiv.innerHTML = '';
        return;
    }
    
    resultsDiv.innerHTML = '<p>搜尋中...</p>';
    
    fetch('api/school_data_api.php?action=search&keyword=' + encodeURIComponent(keyword))
        .then(response => response.json())
        .then(data => {
            if (data.schools && data.schools.length > 0) {
                resultsDiv.innerHTML = '<h4>搜尋結果：</h4><ul>' + 
                    data.schools.map(school => 
                        '<li>' + school.name + ' (' + school.city + ' ' + school.district + ')</li>'
                    ).join('') + 
                    '</ul>';
            } else {
                resultsDiv.innerHTML = '<p style=\"color: red;\">找不到匹配的學校</p>';
            }
        })
        .catch(error => {
            resultsDiv.innerHTML = '<p style=\"color: red;\">搜尋失敗: ' + error.message + '</p>';
        });
});
";
echo "</script>";

// 步驟4：修復建議
echo "<h2>步驟4：修復建議</h2>";
echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 5px; border-left: 4px solid #007cba;'>";

if ($juniorCount == 0) {
    echo "<h3>🔧 需要修復的問題：</h3>";
    echo "<ol>";
    echo "<li><strong>缺少學校資料</strong> - 資料庫中沒有國民中學資料</li>";
    echo "<li><strong>解決方案</strong> - 執行學校資料初始化</li>";
    echo "</ol>";
    
    echo "<h3>📋 修復步驟：</h3>";
    echo "<ol>";
    echo "<li>點擊上方「初始化學校資料」按鈕</li>";
    echo "<li>等待資料插入完成</li>";
    echo "<li>重新測試搜尋功能</li>";
    echo "</ol>";
} else {
    echo "<h3>✅ 系統狀態正常</h3>";
    echo "<p>學校資料已存在，搜尋功能應該正常運作。</p>";
    echo "<p>如果仍有問題，請檢查：</p>";
    echo "<ul>";
    echo "<li>瀏覽器控制台是否有JavaScript錯誤</li>";
    echo "<li>網路請求是否成功</li>";
    echo "<li>API回應是否正確</li>";
    echo "</ul>";
}

echo "</div>";

// 步驟5：快速修復按鈕
echo "<h2>步驟5：快速修復</h2>";
echo "<div style='text-align: center; margin: 20px 0;'>";

if ($juniorCount == 0) {
    echo "<a href='quick_init_schools.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 10px; display: inline-block;'>🚀 初始化學校資料</a>";
}

echo "<a href='cooperation_upload.php' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 10px; display: inline-block;'>📝 測試就讀意願登錄</a>";
echo "<a href='test_school_api.php' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin: 10px; display: inline-block;'>🧪 測試API功能</a>";

echo "</div>";

echo "<hr>";
echo "<h2>📊 系統資訊</h2>";
echo "<ul>";
echo "<li>PHP版本：" . phpversion() . "</li>";
echo "<li>資料庫：" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
echo "<li>當前時間：" . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";
?>
