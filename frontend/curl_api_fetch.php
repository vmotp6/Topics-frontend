<?php
/**
 * 使用cURL從教育部API獲取資料的替代方案
 * 解決file_get_contents無法訪問的問題
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

echo "<h1>🔄 使用cURL獲取教育部資料</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// 使用cURL獲取資料的函數
function fetchDataWithCurl($url) {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'cURL不可用'];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/plain, application/vnd.ms-excel, */*',
        'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
        'Cache-Control: no-cache'
    ]);
    
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'success' => $data !== false && $http_code == 200,
        'data' => $data,
        'http_code' => $http_code,
        'error' => $error,
        'info' => $info
    ];
}

// 解析TXT格式的學校資料
function parseSchoolDataTXT($txt_content) {
    $lines = explode("\n", $txt_content);
    $schools = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strlen($line) < 10) continue;
        
        // 解析TXT格式的資料
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $school = [
                'school_code' => trim($parts[0]),
                'name' => trim($parts[1]),
                'city' => trim($parts[2]),
                'district' => trim($parts[3]),
                'address' => isset($parts[4]) ? trim($parts[4]) : '',
                'phone' => isset($parts[5]) ? trim($parts[5]) : '',
                'website' => isset($parts[6]) ? trim($parts[6]) : '',
                'type' => '國民中學',
                'is_active' => 1,
                'data_source' => '教育部統計處(cURL)'
            ];
            
            // 只處理國民中學
            if (strpos($school['name'], '國中') !== false || 
                strpos($school['name'], '國民中學') !== false ||
                strpos($school['name'], '中學') !== false) {
                $schools[] = $school;
            }
        }
    }
    
    return $schools;
}

// 更新學校資料到資料庫
function updateSchoolData($pdo, $schools) {
    try {
        $pdo->beginTransaction();
        
        // 清空現有資料
        $pdo->exec("DELETE FROM school_data WHERE type = '國民中學'");
        
        // 準備插入語句
        $stmt = $pdo->prepare("
            INSERT INTO school_data (
                name, city, district, type, school_code, 
                address, phone, website, is_active, data_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inserted_count = 0;
        foreach ($schools as $school) {
            $stmt->execute([
                $school['name'],
                $school['city'],
                $school['district'],
                $school['type'],
                $school['school_code'],
                $school['address'],
                $school['phone'],
                $school['website'],
                $school['is_active'],
                $school['data_source']
            ]);
            $inserted_count++;
        }
        
        $pdo->commit();
        return $inserted_count;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// 記錄更新日誌
function logUpdate($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "<p><strong>[$timestamp]</strong> $message</p>";
}

echo "<h2>🔍 檢查cURL可用性</h2>";
if (function_exists('curl_init')) {
    echo "<p class='success'>✅ cURL可用</p>";
} else {
    echo "<p class='error'>❌ cURL不可用，無法使用此方法</p>";
    echo "<p>請使用 <a href='create_school_table_fix.php'>備用資料方案</a></p>";
    exit;
}

echo "<h2>📡 嘗試使用cURL獲取資料</h2>";

// 教育部統計處的資料來源
$api_url = 'http://stats.moe.gov.tw/files/school/104/j1_new.txt';

logUpdate("正在使用cURL從教育部統計處獲取資料...");
logUpdate("目標URL: $api_url");

$result = fetchDataWithCurl($api_url);

if ($result['success']) {
    logUpdate("✅ cURL成功獲取資料");
    logUpdate("資料大小: " . strlen($result['data']) . " bytes");
    logUpdate("HTTP狀態碼: " . $result['http_code']);
    logUpdate("響應時間: " . round($result['info']['total_time'], 2) . " 秒");
    
    // 顯示前幾行資料
    $lines = explode("\n", $result['data']);
    echo "<h3>📄 資料預覽 (前5行)</h3>";
    echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;max-height:200px;overflow-y:auto;'>";
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
    // 解析資料
    logUpdate("正在解析資料...");
    $schools = parseSchoolDataTXT($result['data']);
    logUpdate("解析完成，找到 " . count($schools) . " 所學校");
    
    if (count($schools) > 0) {
        // 更新資料庫
        logUpdate("正在更新資料庫...");
        $inserted_count = updateSchoolData($pdo, $schools);
        logUpdate("✅ 成功更新 $inserted_count 所國民中學到資料庫");
        
        // 顯示統計資訊
        echo "<h3>📊 更新統計</h3>";
        
        // 按縣市統計
        $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC LIMIT 10");
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>前10大縣市：</h4>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;'>";
        foreach ($cities as $city) {
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #007cba;'>";
            echo "<strong>{$city['city']}</strong><br>";
            echo "<span style='color: #666;'>{$city['count']} 所學校</span>";
            echo "</div>";
        }
        echo "</div>";
        
        // 測試搜尋功能
        echo "<h3>🧪 搜尋功能測試</h3>";
        $testKeywords = ['中正', '板橋', '桃園', '中崙', '西松'];
        foreach ($testKeywords as $keyword) {
            $stmt = $pdo->prepare("SELECT name, city, district FROM school_data WHERE type = '國民中學' AND name LIKE ? LIMIT 3");
            $stmt->execute(["%$keyword%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                echo "<p class='success'>✅ 搜尋「$keyword」: 找到 " . count($results) . " 筆結果</p>";
                foreach ($results as $result) {
                    echo "<p style='margin-left: 20px;'>- {$result['name']} ({$result['city']} {$result['district']})</p>";
                }
            } else {
                echo "<p class='error'>❌ 搜尋「$keyword」: 沒有結果</p>";
            }
        }
        
    } else {
        logUpdate("❌ 沒有找到有效的學校資料");
    }
    
} else {
    logUpdate("❌ cURL獲取資料失敗");
    logUpdate("HTTP狀態碼: " . $result['http_code']);
    logUpdate("錯誤信息: " . $result['error']);
    
    echo "<h3>🔧 故障排除建議</h3>";
    echo "<ul>";
    echo "<li>檢查網路連接</li>";
    echo "<li>檢查防火牆設定</li>";
    echo "<li>聯繫主機商檢查網路限制</li>";
    echo "<li>嘗試使用備用資料方案</li>";
    echo "</ul>";
    
    echo "<p><a href='create_school_table_fix.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 使用備用資料方案</a></p>";
}

echo "<hr>";
echo "<h2>🎉 完成！</h2>";
echo "<div style='background:#d4edda;padding:20px;border-radius:8px;border-left:4px solid #28a745;'>";
echo "<h3 style='color:#155724;margin:0 0 15px 0;'>✅ 學校資料更新完成！</h3>";
echo "<p style='margin:0 0 10px 0;'>現在您可以：</p>";
echo "<ul style='margin:0;'>";
echo "<li>在 cooperation_upload.php 中使用學校搜尋功能</li>";
echo "<li>搜尋全台各縣市的國民中學</li>";
echo "<li>享受即時搜尋和自動完成功能</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align:center;margin:30px 0;'>";
echo "<a href='cooperation_upload.php' style='background:#007cba;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>📝 測試就讀意願登錄</a>";
echo "<a href='test_full_taiwan_schools.php' style='background:#6c757d;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>🧪 測試搜尋功能</a>";
echo "<a href='diagnose_api_connection.php' style='background:#17a2b8;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:16px;margin:10px;display:inline-block;'>🔍 API診斷工具</a>";
echo "</div>";
?>
