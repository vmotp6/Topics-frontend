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

// 標準化學校名稱，用於去重
function normalizeSchoolName($schoolName) {
    // 移除常見的前綴詞
    $prefixes = ['市立', '縣立', '國立', '私立', '台北市立', '新北市立', '桃園市立', '台中市立', '台南市立', '高雄市立'];
    
    $normalized = $schoolName;
    
    // 移除前綴詞
    foreach ($prefixes as $prefix) {
        if (strpos($normalized, $prefix) === 0) {
            $normalized = substr($normalized, strlen($prefix));
            break;
        }
    }
    
    // 移除多餘的空白
    $normalized = trim($normalized);
    
    // 統一「國中」和「國民中學」的寫法
    $normalized = str_replace('國民中學', '國中', $normalized);
    
    return $normalized;
}

// 標準化城市名稱
function normalizeCityName($cityName) {
    // 統一城市名稱的寫法
    $cityMappings = [
        '臺北市' => '台北市',
        '臺中市' => '台中市',
        '臺南市' => '台南市',
        '臺東縣' => '台東縣'
    ];
    
    return $cityMappings[$cityName] ?? $cityName;
}

// 選擇最佳的學校名稱顯示
function chooseBestSchoolName($currentName, $existingSchools, $normalizedName) {
    // 如果沒有現有學校，直接返回當前名稱
    if (empty($existingSchools)) {
        return $currentName;
    }
    
    // 優先順序：較短的名稱 > 不包含前綴的名稱 > 原始名稱
    $currentScore = calculateNameScore($currentName);
    $bestName = $currentName;
    $bestScore = $currentScore;
    
    foreach ($existingSchools as $school) {
        $score = calculateNameScore($school['name']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestName = $school['name'];
        }
    }
    
    return $bestName;
}

// 計算學校名稱的分數（分數越高越好）
function calculateNameScore($schoolName) {
    $score = 0;
    
    // 較短的名稱得分更高
    $score += (100 - strlen($schoolName));
    
    // 不包含前綴詞的名稱得分更高
    $prefixes = ['市立', '縣立', '國立', '私立', '台北市立', '新北市立', '桃園市立', '台中市立', '台南市立', '高雄市立'];
    foreach ($prefixes as $prefix) {
        if (strpos($schoolName, $prefix) === 0) {
            $score -= 20; // 有前綴詞扣分
            break;
        }
    }
    
    // 包含「國中」而不是「國民中學」的得分更高
    if (strpos($schoolName, '國中') !== false && strpos($schoolName, '國民中學') === false) {
        $score += 10;
    }
    
    return $score;
}

// 找到現有學校的索引
function findExistingSchoolIndex($matches, $uniqueKey) {
    foreach ($matches as $index => $school) {
        $normalized_name = normalizeSchoolName($school['name']);
        $normalized_city = normalizeCityName($school['city']);
        $school_unique_key = $normalized_city . '_' . $normalized_name; // 移除區域，只使用城市+學校名稱
        if ($school_unique_key === $uniqueKey) {
            return $index;
        }
    }
    return -1;
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
        $seen_schools = []; // 用於去重的陣列
        
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
                // 創建學校的唯一識別碼（使用城市+學校名稱的標準化版本，忽略區域差異）
                $normalized_name = normalizeSchoolName($school['name']);
                $normalized_city = normalizeCityName($school['city']);
                $unique_key = $normalized_city . '_' . $normalized_name; // 移除區域，只使用城市+學校名稱
                
                // 檢查是否已經存在相同的學校
                if (!isset($seen_schools[$unique_key])) {
                    $seen_schools[$unique_key] = true;
                    
                    // 選擇最佳的學校名稱顯示（優先選擇較短、較簡潔的名稱）
                    $school['display_name'] = $school['name'];
                    $school['all_names'] = [$school['name']]; // 記錄所有找到的名稱
                    
                    $matches[] = $school;
                } else {
                    // 如果找到重複的學校，整合資訊
                    $existing_index = findExistingSchoolIndex($matches, $unique_key);
                    if ($existing_index !== -1) {
                        // 將新名稱添加到所有名稱列表中
                        if (!in_array($school['name'], $matches[$existing_index]['all_names'])) {
                            $matches[$existing_index]['all_names'][] = $school['name'];
                        }
                        
                        // 選擇最佳名稱作為顯示名稱
                        $best_name = chooseBestSchoolName($school['name'], [$matches[$existing_index]], $normalized_name);
                        if ($best_name !== $matches[$existing_index]['name']) {
                            $matches[$existing_index]['name'] = $best_name;
                            $matches[$existing_index]['display_name'] = $best_name;
                        }
                        
                        // 合併其他可能有用的資訊
                        if (empty($matches[$existing_index]['address']) && !empty($school['address'])) {
                            $matches[$existing_index]['address'] = $school['address'];
                        }
                        if (empty($matches[$existing_index]['phone']) && !empty($school['phone'])) {
                            $matches[$existing_index]['phone'] = $school['phone'];
                        }
                        if (empty($matches[$existing_index]['website']) && !empty($school['website'])) {
                            $matches[$existing_index]['website'] = $school['website'];
                        }
                    }
                }
            }
        }
        
        // 限制結果數量
        $matches = array_slice($matches, 0, 20);
        
        echo json_encode([
            'schools' => $matches,
            'total' => count($matches),
            'keyword' => $keyword,
            'debug' => [
                'total_schools' => count($schools),
                'unique_keys' => array_keys($seen_schools),
                'deduplication_enabled' => true
            ]
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
