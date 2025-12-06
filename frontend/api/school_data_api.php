<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 載入 session 配置
require_once '../session_config.php';

// 載入資料庫配置
require_once '../config.php';

// 資料庫連接（使用 config.php 中的配置）
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '資料庫連接失敗: ' . $e->getMessage()]);
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
    
    // 特殊處理：將「高中附設國中部」轉換為「國中」
    $normalized = str_replace('高中附設國中部', '國中', $normalized);
    $normalized = str_replace('附設國中部', '國中', $normalized);
    
    // 移除「附設」相關詞彙
    $normalized = str_replace('附設', '', $normalized);
    
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
    
    // 收集所有候選名稱
    $candidateNames = [$currentName];
    foreach ($existingSchools as $school) {
        $candidateNames[] = $school['name'];
    }
    
    // 選擇最佳名稱
    $bestName = $currentName;
    $bestScore = calculateNameScore($currentName);
    
    foreach ($candidateNames as $name) {
        $score = calculateNameScore($name);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestName = $name;
        }
    }
    
    return $bestName;
}

// 計算學校名稱的分數（分數越高越好）
function calculateNameScore($schoolName) {
    $score = 0;
    
    // 較短的名稱得分更高（基礎分數）
    $score += (100 - strlen($schoolName));
    
    // 不包含前綴詞的名稱得分更高
    $prefixes = ['市立', '縣立', '國立', '私立', '台北市立', '新北市立', '桃園市立', '台中市立', '台南市立', '高雄市立'];
    $hasPrefix = false;
    foreach ($prefixes as $prefix) {
        if (strpos($schoolName, $prefix) === 0) {
            $score -= 30; // 有前綴詞扣分
            $hasPrefix = true;
            break;
        }
    }
    
    // 如果沒有前綴詞，額外加分
    if (!$hasPrefix) {
        $score += 20;
    }
    
    // 包含「國中」而不是「國民中學」的得分更高
    if (strpos($schoolName, '國中') !== false && strpos($schoolName, '國民中學') === false) {
        $score += 15;
    }
    
    // 不包含「附設」的名稱得分更高
    if (strpos($schoolName, '附設') === false) {
        $score += 25;
    }
    
    // 不包含「高中」的名稱得分更高（因為我們要找國中）
    if (strpos($schoolName, '高中') === false) {
        $score += 20;
    }
    
    // 包含「國中」的額外加分
    if (strpos($schoolName, '國中') !== false) {
        $score += 10;
    }
    
    return $score;
}

// 找到現有學校的索引
function findExistingSchoolIndex($matches, $uniqueKey) {
    foreach ($matches as $index => $school) {
        $normalized_name = normalizeSchoolName($school['name']);
        $normalized_city = normalizeCityName($school['city']);
        
        // 進一步簡化學校名稱，移除「高中」等詞彙
        $simplified_name = str_replace(['高中', '附設', '部'], '', $normalized_name);
        $simplified_name = trim($simplified_name);
        
        $school_unique_key = $normalized_city . '_' . $simplified_name; // 使用簡化後的名稱
        if ($school_unique_key === $uniqueKey) {
            return $index;
        }
    }
    return -1;
}

// 從資料庫獲取國民中學資料
function fetchJuniorHighSchools($pdo) {
    try {
        // 根据实际表结构：school_code, name, city, district, address, phone, is_active
        // 表中没有 type 字段，需要通过名称过滤国中数据
        
        // 查询所有启用的学校（根据名称过滤国中）
        $query = "SELECT school_code, name, city, district, address, phone, is_active 
                  FROM school_data 
                  WHERE is_active = 1 
                  AND (name LIKE '%國中%' OR name LIKE '%國民中學%' OR name LIKE '%附設國中部%')
                  ORDER BY city, district, name";
        
        error_log("执行查询: " . $query);
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 为每条记录添加 type 字段（用于兼容性）
        foreach ($results as &$row) {
            $row['type'] = '國民中學';
            // 添加其他可能缺失的字段（设为null）
            if (!isset($row['school_code'])) $row['school_code'] = null;
            if (!isset($row['website'])) $row['website'] = null;
            if (!isset($row['data_source'])) $row['data_source'] = null;
        }
        
        error_log("fetchJuniorHighSchools: 找到 " . count($results) . " 条国民中学记录");
        
        return $results;
    } catch (PDOException $e) {
        error_log("獲取學校資料失敗: " . $e->getMessage());
        error_log("SQL错误信息: " . $e->getMessage());
        error_log("错误代码: " . $e->getCode());
        return [];
    }
}

// 處理不同的API請求
$action = $_GET['action'] ?? 'search';

switch ($action) {
    case 'search':
        $keyword = $_GET['keyword'] ?? '';
        $city = $_GET['city'] ?? '';
        
        // 调试信息
        error_log("API搜索请求: keyword=" . $keyword . ", city=" . $city);
        
        if (strlen($keyword) < 2) {
            echo json_encode(['schools' => [], 'message' => '請輸入至少2個字元']);
            exit;
        }
        
        $schools = fetchJuniorHighSchools($pdo);
        
        // 调试信息
        error_log("从数据库获取到 " . count($schools) . " 条学校记录");
        
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
            if (!empty($school['school_code']) && stripos($school['school_code'], $keyword) !== false) {
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
                    // 確保所有必需字段都存在
                    if (!isset($school['district'])) $school['district'] = '';
                    if (!isset($school['city'])) $school['city'] = '';
                    if (!isset($school['name'])) $school['name'] = '';
                    // 確保 school_code 存在
                    if (!isset($school['school_code']) || empty($school['school_code'])) {
                        error_log("警告: 學校沒有 school_code: " . $school['name']);
                        // 如果沒有 school_code，跳過這條記錄
                        continue;
                    }
                    
                    // 如果 address 為空，自動生成地址（使用 city + district）
                    if (empty($school['address']) && !empty($school['city']) && !empty($school['district'])) {
                        $school['address'] = $school['city'] . $school['district'];
                        error_log("自動生成地址: " . $school['name'] . " -> " . $school['address']);
                    } elseif (empty($school['address']) && !empty($school['city'])) {
                        $school['address'] = $school['city'];
                        error_log("自動生成地址（僅縣市）: " . $school['name'] . " -> " . $school['address']);
                    }
                
                // 使用 school_code 作為唯一識別碼，避免合併不同地址的相同名稱學校
                $unique_key = $school['school_code'];
                
                // 檢查是否已經存在相同的 school_code
                if (!isset($seen_schools[$unique_key])) {
                    $seen_schools[$unique_key] = true;
                    
                    // 選擇最佳的學校名稱顯示（優先選擇較短、較簡潔的名稱）
                    $school['display_name'] = $school['name'];
                    $school['all_names'] = [$school['name']]; // 記錄所有找到的名稱
                    
                    $matches[] = $school;
                } else {
                    // 如果找到相同的 school_code，只更新名稱資訊（不更新地址，因為地址應該是一致的）
                    $existing_index = -1;
                    foreach ($matches as $idx => $match) {
                        if ($match['school_code'] === $school['school_code']) {
                            $existing_index = $idx;
                            break;
                        }
                    }
                    
                    if ($existing_index !== -1) {
                        // 將新名稱添加到所有名稱列表中
                        if (!in_array($school['name'], $matches[$existing_index]['all_names'])) {
                            $matches[$existing_index]['all_names'][] = $school['name'];
                        }
                        
                        // 選擇最佳名稱作為顯示名稱
                        $all_names = $matches[$existing_index]['all_names'];
                        $best_name = $all_names[0]; // 預設使用第一個名稱
                        $best_score = calculateNameScore($best_name);
                        
                        // 從所有名稱中選擇分數最高的
                        foreach ($all_names as $name) {
                            $score = calculateNameScore($name);
                            if ($score > $best_score) {
                                $best_score = $score;
                                $best_name = $name;
                            }
                        }
                        
                        // 更新顯示名稱
                        $matches[$existing_index]['name'] = $best_name;
                        $matches[$existing_index]['display_name'] = $best_name;
                        
                        // 只有在現有記錄沒有地址時，才使用新記錄的地址（保持地址一致性）
                        if (empty($matches[$existing_index]['address']) && !empty($school['address'])) {
                            $matches[$existing_index]['address'] = $school['address'];
                        }
                        // 只有在現有記錄沒有電話時，才使用新記錄的電話
                        if (empty($matches[$existing_index]['phone']) && !empty($school['phone'])) {
                            $matches[$existing_index]['phone'] = $school['phone'];
                        }
                        // 只有在現有記錄沒有網站時，才使用新記錄的網站
                        if (empty($matches[$existing_index]['website']) && !empty($school['website'])) {
                            $matches[$existing_index]['website'] = $school['website'];
                        }
                    }
                }
            }
        }
        
        // 限制結果數量
        $matches = array_slice($matches, 0, 20);
        
        // 確保所有學校都有地址（如果為空，自動生成）
        foreach ($matches as &$match) {
            if (empty($match['address'])) {
                if (!empty($match['city']) && !empty($match['district'])) {
                    $match['address'] = $match['city'] . $match['district'];
                } elseif (!empty($match['city'])) {
                    $match['address'] = $match['city'];
                } else {
                    $match['address'] = '';
                }
            }
        }
        unset($match); // 釋放引用
        
        // 调试信息
        error_log("搜索匹配结果: 找到 " . count($matches) . " 条匹配记录");
        
        // 記錄每筆匹配記錄的詳細資訊（用於調試）
        foreach ($matches as $idx => $match) {
            error_log("匹配記錄 #" . ($idx + 1) . ": school_code=" . ($match['school_code'] ?? 'NULL') . 
                     ", name=" . ($match['name'] ?? 'NULL') . 
                     ", address=" . ($match['address'] ?? 'NULL'));
        }
        
        // 确保返回正确的JSON格式
        $response = [
            'schools' => $matches,
            'total' => count($matches),
            'keyword' => $keyword,
            'debug' => [
                'total_schools' => count($schools),
                'unique_keys' => array_keys($seen_schools),
                'deduplication_enabled' => true,
                'matches_detail' => array_map(function($m) {
                    return [
                        'school_code' => $m['school_code'] ?? null,
                        'name' => $m['name'] ?? null,
                        'address' => $m['address'] ?? null
                    ];
                }, $matches)
            ]
        ];
        
        // 如果启用调试模式，添加更多信息
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            $response['debug']['all_schools_count'] = count($schools);
            $response['debug']['matches_count'] = count($matches);
            $response['debug']['first_school'] = !empty($schools) ? $schools[0] : null;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
            // 根据实际表结构，没有 type 字段，通过名称过滤
            $stmt = $pdo->prepare("SELECT school_code, name, city, district, address, phone 
                                   FROM school_data 
                                   WHERE city = ? 
                                   AND is_active = 1 
                                   AND (name LIKE '%國中%' OR name LIKE '%國民中學%' OR name LIKE '%附設國中部%')
                                   ORDER BY district, name");
            $stmt->execute([$city]);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 添加 type 字段用于兼容性
            foreach ($schools as &$school) {
                $school['type'] = '國民中學';
            }
            
            echo json_encode([
                'schools' => $schools,
                'city' => $city,
                'total' => count($schools)
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
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
        
    case 'check_school':
        // 檢查特定學校是否存在
        $school_name = $_GET['school_name'] ?? '';
        if (empty($school_name)) {
            echo json_encode(['error' => '請提供學校名稱']);
            exit;
        }
        
        try {
            // 根据实际表结构查询
            $stmt = $pdo->prepare("SELECT school_code, name, city, district, address, phone 
                                   FROM school_data 
                                   WHERE name LIKE ? 
                                   AND is_active = 1 
                                   AND (name LIKE '%國中%' OR name LIKE '%國民中學%' OR name LIKE '%附設國中部%')");
            $stmt->execute(["%$school_name%"]);
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 添加 type 字段用于兼容性
            foreach ($schools as &$school) {
                $school['type'] = '國民中學';
                if (!isset($school['website'])) $school['website'] = null;
            }
            
            echo json_encode([
                'school_name' => $school_name,
                'found' => count($schools) > 0,
                'count' => count($schools),
                'schools' => $schools
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
        
    default:
        echo json_encode(['error' => '無效的操作']);
        break;
}
?>
