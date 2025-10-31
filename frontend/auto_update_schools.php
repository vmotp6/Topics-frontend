<?php
/**
 * 台灣教育部國民中學資料自動更新腳本
 * 可設定為定時任務（cron job）定期執行
 * 
 * 使用方法：
 * 1. 手動執行：訪問此頁面
 * 2. 定時任務：php /path/to/auto_update_schools.php
 * 3. 每週更新：0 2 * * 1 php /path/to/auto_update_schools.php
 */

// 設定執行時間限制
set_time_limit(300); // 5分鐘

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
    if (php_sapi_name() === 'cli') {
        echo "資料庫連接失敗: " . $e->getMessage() . "\n";
    } else {
        die("資料庫連接失敗: " . $e->getMessage());
    }
    exit(1);
}

// 記錄更新日誌
function logUpdate($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    } else {
        echo "<p>$timestamp - $message</p>";
    }
    
    // 寫入日誌文件
    file_put_contents('school_update.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// 從教育部API獲取資料
function fetchDataFromEducationAPI($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'header' => [
                'Accept: text/plain, application/vnd.ms-excel, */*',
                'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
                'Cache-Control: no-cache'
            ]
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        return null;
    }
    
    return $data;
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
                'data_source' => '教育部統計處'
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

// 檢查是否需要更新
function shouldUpdate($pdo) {
    try {
        $stmt = $pdo->query("SELECT MAX(last_updated) as last_update FROM school_data WHERE type = '國民中學'");
        $result = $stmt->fetch();
        
        if (!$result['last_update']) {
            return true; // 沒有資料，需要更新
        }
        
        $lastUpdate = new DateTime($result['last_update']);
        $now = new DateTime();
        $daysDiff = $now->diff($lastUpdate)->days;
        
        // 如果超過7天沒有更新，則需要更新
        return $daysDiff >= 7;
        
    } catch (PDOException $e) {
        logUpdate("檢查更新狀態失敗: " . $e->getMessage());
        return true; // 出錯時強制更新
    }
}

// 主更新流程
function performUpdate($pdo) {
    logUpdate("開始更新學校資料...");
    
    // 教育部統計處的資料來源
    $api_url = 'http://stats.moe.gov.tw/files/school/104/j1_new.txt';
    
    try {
        // 獲取資料
        logUpdate("正在從教育部統計處獲取資料...");
        $txt_data = fetchDataFromEducationAPI($api_url);
        
        if ($txt_data) {
            logUpdate("成功獲取資料，大小: " . strlen($txt_data) . " bytes");
            
            // 解析資料
            $schools = parseSchoolDataTXT($txt_data);
            logUpdate("解析完成，找到 " . count($schools) . " 所學校");
            
            if (count($schools) > 0) {
                // 更新資料庫
                $inserted_count = updateSchoolData($pdo, $schools);
                logUpdate("✅ 成功更新 $inserted_count 所國民中學到資料庫");
                
                // 更新統計
                $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC LIMIT 5");
                $topCities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                logUpdate("前5大縣市:");
                foreach ($topCities as $city) {
                    logUpdate("  - {$city['city']}: {$city['count']} 所");
                }
                
                return true;
            } else {
                logUpdate("❌ 沒有找到有效的學校資料");
                return false;
            }
        } else {
            logUpdate("❌ 無法從教育部API獲取資料");
            return false;
        }
        
    } catch (Exception $e) {
        logUpdate("❌ 更新失敗: " . $e->getMessage());
        return false;
    }
}

// 檢查是否為命令行執行
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<h1>🔄 台灣教育部國民中學資料自動更新</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}p{margin:5px 0;}</style>";
}

// 檢查是否需要更新
if (shouldUpdate($pdo)) {
    logUpdate("需要更新學校資料");
    
    $success = performUpdate($pdo);
    
    if ($success) {
        logUpdate("🎉 學校資料更新完成！");
        
        if (!$isCLI) {
            echo "<hr>";
            echo "<h2>📊 更新結果</h2>";
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學'");
                $total = $stmt->fetch()['total'];
                echo "<p><strong>總學校數：</strong>$total 所</p>";
                
                $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM school_data WHERE type = '國民中學' GROUP BY city ORDER BY count DESC");
                $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>按縣市統計：</h3>";
                echo "<ul>";
                foreach ($cities as $city) {
                    echo "<li>{$city['city']}: {$city['count']} 所</li>";
                }
                echo "</ul>";
                
            } catch (PDOException $e) {
                echo "<p style='color:red;'>統計查詢失敗: " . $e->getMessage() . "</p>";
            }
            
            echo "<hr>";
            echo "<h2>🔗 相關連結</h2>";
            echo "<p><a href='cooperation_upload.php'>📝 測試就讀意願登錄</a></p>";
            echo "<p><a href='test_full_taiwan_schools.php'>🧪 測試搜尋功能</a></p>";
            echo "<p><a href='diagnose_school_search.php'>🔍 診斷工具</a></p>";
        }
    } else {
        logUpdate("❌ 學校資料更新失敗");
        
        if (!$isCLI) {
            echo "<p style='color:red;'>更新失敗，請檢查日誌文件 school_update.log</p>";
        }
    }
} else {
    logUpdate("學校資料已是最新，無需更新");
    
    if (!$isCLI) {
        echo "<p style='color:green;'>✅ 學校資料已是最新版本，無需更新</p>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM school_data WHERE type = '國民中學'");
            $total = $stmt->fetch()['total'];
            echo "<p><strong>目前資料庫中有 $total 所國民中學</strong></p>";
            
            $stmt = $pdo->query("SELECT MAX(last_updated) as last_update FROM school_data WHERE type = '國民中學'");
            $lastUpdate = $stmt->fetch()['last_update'];
            echo "<p><strong>最後更新時間：</strong>$lastUpdate</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color:red;'>查詢失敗: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
        echo "<h2>🔗 相關連結</h2>";
        echo "<p><a href='cooperation_upload.php'>📝 測試就讀意願登錄</a></p>";
        echo "<p><a href='test_full_taiwan_schools.php'>🧪 測試搜尋功能</a></p>";
        echo "<p><a href='diagnose_school_search.php'>🔍 診斷工具</a></p>";
    }
}

logUpdate("更新腳本執行完成");
?>
