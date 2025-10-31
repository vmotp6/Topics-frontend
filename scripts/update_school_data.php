<?php
/**
 * 學校資料更新腳本
 * 從台灣教育部開放資料平台獲取最新的學校資料
 * 建議設定為定時任務，每日或每週執行一次
 */

// 設定執行時間限制
set_time_limit(300); // 5分鐘

// 載入配置
require_once '../frontend/session_config.php';

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("資料庫連接失敗: " . $e->getMessage());
    exit(1);
}

// 台灣教育部開放資料API端點
$api_endpoints = [
    'junior_high' => 'https://data.gov.tw/dataset/12071', // 國民中學
    'senior_high' => 'https://data.gov.tw/dataset/12072', // 高級中學
    'elementary' => 'https://data.gov.tw/dataset/12070'   // 國民小學
];

// 從教育部API獲取資料的函數
function fetchDataFromEducationAPI($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'header' => [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
                'Cache-Control: no-cache'
            ]
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        return null;
    }
    
    return json_decode($data, true);
}

// 解析CSV資料的函數
function parseCSVData($csv_content) {
    $lines = explode("\n", $csv_content);
    $headers = str_getcsv(array_shift($lines));
    $data = [];
    
    foreach ($lines as $line) {
        if (trim($line)) {
            $row = str_getcsv($line);
            if (count($row) >= count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }
    }
    
    return $data;
}

// 更新學校資料的函數
function updateSchoolData($pdo, $schools, $type) {
    try {
        // 開始事務
        $pdo->beginTransaction();
        
        // 清空現有資料
        $stmt = $pdo->prepare("DELETE FROM school_data WHERE type = ?");
        $stmt->execute([$type]);
        
        // 準備插入語句
        $insert_stmt = $pdo->prepare("
            INSERT INTO school_data (
                name, city, district, type, school_code, 
                address, phone, website, principal, 
                student_count, teacher_count, established_year, 
                is_active, data_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $inserted_count = 0;
        
        foreach ($schools as $school) {
            $insert_stmt->execute([
                $school['name'] ?? '',
                $school['city'] ?? '',
                $school['district'] ?? '',
                $type,
                $school['school_code'] ?? null,
                $school['address'] ?? null,
                $school['phone'] ?? null,
                $school['website'] ?? null,
                $school['principal'] ?? null,
                $school['student_count'] ?? 0,
                $school['teacher_count'] ?? 0,
                $school['established_year'] ?? null,
                $school['is_active'] ?? 1,
                '教育部開放資料'
            ]);
            $inserted_count++;
        }
        
        // 提交事務
        $pdo->commit();
        
        return $inserted_count;
        
    } catch (Exception $e) {
        // 回滾事務
        $pdo->rollBack();
        throw $e;
    }
}

// 記錄日誌的函數
function logUpdate($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents('../logs/school_data_update.log', $log_message, FILE_APPEND | LOCK_EX);
    echo $log_message;
}

// 主執行邏輯
try {
    logUpdate("開始更新學校資料...");
    
    // 這裡使用模擬資料，實際使用時需要替換為真實的API調用
    $mock_junior_high_schools = [
        ['name' => '中正國中', 'city' => '台北市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'TP001', 'is_active' => 1],
        ['name' => '西松國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP002', 'is_active' => 1],
        ['name' => '永吉國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP003', 'is_active' => 1],
        ['name' => '信義國中', 'city' => '台北市', 'district' => '信義區', 'type' => '國民中學', 'school_code' => 'TP004', 'is_active' => 1],
        ['name' => '松山國中', 'city' => '台北市', 'district' => '松山區', 'type' => '國民中學', 'school_code' => 'TP005', 'is_active' => 1],
        ['name' => '板橋國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT001', 'is_active' => 1],
        ['name' => '海山國中', 'city' => '新北市', 'district' => '板橋區', 'type' => '國民中學', 'school_code' => 'NT002', 'is_active' => 1],
        ['name' => '新莊國中', 'city' => '新北市', 'district' => '新莊區', 'type' => '國民中學', 'school_code' => 'NT003', 'is_active' => 1],
        ['name' => '桃園國中', 'city' => '桃園市', 'district' => '桃園區', 'type' => '國民中學', 'school_code' => 'TY001', 'is_active' => 1],
        ['name' => '中壢國中', 'city' => '桃園市', 'district' => '中壢區', 'type' => '國民中學', 'school_code' => 'TY002', 'is_active' => 1],
        ['name' => '基隆國中', 'city' => '基隆市', 'district' => '中正區', 'type' => '國民中學', 'school_code' => 'KL001', 'is_active' => 1],
        ['name' => '新竹國中', 'city' => '新竹市', 'district' => '東區', 'type' => '國民中學', 'school_code' => 'HSC001', 'is_active' => 1],
        ['name' => '竹北國中', 'city' => '新竹縣', 'district' => '竹北市', 'type' => '國民中學', 'school_code' => 'HSH001', 'is_active' => 1]
    ];
    
    // 更新國民中學資料
    $count = updateSchoolData($pdo, $mock_junior_high_schools, '國民中學');
    logUpdate("國民中學資料更新完成，共更新 $count 筆資料");
    
    // 實際使用時，可以從真實的API獲取資料
    /*
    foreach ($api_endpoints as $type => $url) {
        $data = fetchDataFromEducationAPI($url);
        if ($data) {
            $count = updateSchoolData($pdo, $data, $type);
            logUpdate("$type 資料更新完成，共更新 $count 筆資料");
        } else {
            logUpdate("無法獲取 $type 資料");
        }
    }
    */
    
    logUpdate("學校資料更新完成！");
    
} catch (Exception $e) {
    logUpdate("更新失敗: " . $e->getMessage());
    exit(1);
}
?>
