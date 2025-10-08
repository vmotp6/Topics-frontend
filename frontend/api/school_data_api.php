<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 載入 session 配置
require_once '../session_config.php';

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '資料庫連接失敗']);
    exit;
}

// 台灣教育部開放資料API端點
$education_apis = [
    'junior_high_txt' => 'http://stats.moe.gov.tw/files/school/104/j1_new.txt',
    'junior_high_xls' => 'http://stats.moe.gov.tw/files/school/104/j1_new.xls',
    'school_basic_info' => 'https://data.gov.tw/dataset/12071',
    'school_statistics' => 'https://data.gov.tw/dataset/12072'
];

// 獲取學校資料的函數
function getSchoolDataFromAPI($api_url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $data = @file_get_contents($api_url, false, $context);
    if ($data === false) {
        return null;
    }
    
    return json_decode($data, true);
}

// 從資料庫獲取國民中學資料
function fetchJuniorHighSchools($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT name, city, district, type, school_code, address, phone, website, data_source FROM school_data WHERE type = '國民中學' AND is_active = 1 ORDER BY city, district, name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("獲取學校資料失敗: " . $e->getMessage());
        return [];
    }
}

// 處理不同的API請求
$action = $_GET['action'] ?? 'search';

switch ($action) {
    case 'search':
        $keyword = $_GET['keyword'] ?? '';
        $city = $_GET['city'] ?? '';
        
        if (strlen($keyword) < 2) {
            echo json_encode(['schools' => [], 'message' => '請輸入至少2個字元']);
            exit;
        }
        
        $schools = fetchJuniorHighSchools($pdo);
        $matches = [];
        
        foreach ($schools as $school) {
            // 如果指定了城市，先過濾城市
            if ($city && $school['city'] !== $city) {
                continue;
            }
            
            // 多種搜尋方式
            $match = false;
            
            // 1. 學校名稱模糊匹配
            if (stripos($school['name'], $keyword) !== false) {
                $match = true;
            }
            
            // 2. 學校代碼匹配
            if (stripos($school['school_code'], $keyword) !== false) {
                $match = true;
            }
            
            // 3. 地址模糊匹配
            if (isset($school['address']) && stripos($school['address'], $keyword) !== false) {
                $match = true;
            }
            
            // 4. 縣市區匹配
            if (stripos($school['city'], $keyword) !== false || 
                stripos($school['district'], $keyword) !== false) {
                $match = true;
            }
            
            if ($match) {
                $matches[] = $school;
            }
        }
        
        // 限制結果數量
        $matches = array_slice($matches, 0, 20);
        
        echo json_encode([
            'schools' => $matches,
            'total' => count($matches),
            'keyword' => $keyword
        ]);
        break;
        
    case 'cities':
        $schools = fetchJuniorHighSchools($pdo);
        $cities = array_unique(array_column($schools, 'city'));
        sort($cities);
        
        echo json_encode(['cities' => $cities]);
        break;
        
    case 'schools_by_city':
        $city = $_GET['city'] ?? '';
        if (empty($city)) {
            echo json_encode(['schools' => [], 'message' => '請指定城市']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT name, city, district, type, school_code FROM school_data WHERE city = ? AND type = '國民中學' AND is_active = 1 ORDER BY district, name");
            $stmt->execute([$city]);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'schools' => $schools,
                'city' => $city,
                'total' => count($schools)
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        // 執行更新腳本
        try {
            $output = shell_exec('php ../scripts/fetch_real_school_data.php 2>&1');
            echo json_encode([
                'success' => true,
                'message' => '學校資料更新成功',
                'output' => $output
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '更新失敗: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode(['error' => '無效的操作']);
        break;
}
?>
