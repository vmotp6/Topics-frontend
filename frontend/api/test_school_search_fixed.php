<?php
// 測試修復後的學校搜尋去重功能
header('Content-Type: text/html; charset=utf-8');

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('資料庫連接失敗: ' . $e->getMessage());
}

// 標準化學校名稱
function normalizeSchoolName($schoolName) {
    $prefixes = ['市立', '縣立', '國立', '私立', '台北市立', '新北市立', '桃園市立', '台中市立', '台南市立', '高雄市立'];
    
    $normalized = $schoolName;
    
    foreach ($prefixes as $prefix) {
        if (strpos($normalized, $prefix) === 0) {
            $normalized = substr($normalized, strlen($prefix));
            break;
        }
    }
    
    $normalized = trim($normalized);
    $normalized = str_replace('國民中學', '國中', $normalized);
    
    return $normalized;
}

// 標準化城市名稱
function normalizeCityName($cityName) {
    $cityMappings = [
        '臺北市' => '台北市',
        '臺中市' => '台中市',
        '臺南市' => '台南市',
        '臺東縣' => '台東縣'
    ];
    
    return $cityMappings[$cityName] ?? $cityName;
}

// 計算學校名稱的分數
function calculateNameScore($schoolName) {
    $score = 0;
    $score += (100 - strlen($schoolName));
    
    $prefixes = ['市立', '縣立', '國立', '私立', '台北市立', '新北市立', '桃園市立', '台中市立', '台南市立', '高雄市立'];
    foreach ($prefixes as $prefix) {
        if (strpos($schoolName, $prefix) === 0) {
            $score -= 20;
            break;
        }
    }
    
    if (strpos($schoolName, '國中') !== false && strpos($schoolName, '國民中學') === false) {
        $score += 10;
    }
    
    return $score;
}

// 從資料庫獲取國民中學資料
function fetchJuniorHighSchools($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT name, city, district, type, school_code, address, phone, website, data_source FROM school_data WHERE type = '國民中學' AND is_active = 1 ORDER BY city, district, name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("資料庫查詢失敗: " . $e->getMessage());
        return [];
    }
}

// 找到現有學校的索引
function findExistingSchoolIndex($matches, $uniqueKey) {
    foreach ($matches as $index => $school) {
        $normalized_name = normalizeSchoolName($school['name']);
        $normalized_city = normalizeCityName($school['city']);
        $school_unique_key = $normalized_city . '_' . $school['district'] . '_' . $normalized_name;
        if ($school_unique_key === $uniqueKey) {
            return $index;
        }
    }
    return -1;
}

// 選擇最佳的學校名稱顯示
function chooseBestSchoolName($currentName, $existingSchools, $normalizedName) {
    if (empty($existingSchools)) {
        return $currentName;
    }
    
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

echo "<!DOCTYPE html>
<html lang='zh-TW'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>學校搜尋去重功能測試（修復版）</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .result { background: #f0f8ff; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
        .debug { background: #fff3cd; font-size: 12px; }
    </style>
</head>
<body>
    <h1>學校搜尋去重功能測試（修復版）</h1>";

// 測試城市名稱標準化
echo "<div class='test-section'>
    <h2>城市名稱標準化測試</h2>";

$testCities = ['臺北市', '台北市', '臺中市', '台中市', '臺南市', '台南市', '高雄市'];
foreach ($testCities as $city) {
    $normalized = normalizeCityName($city);
    echo "<div class='result'>「{$city}」 → 「{$normalized}」</div>";
}

echo "</div>";

// 測試搜尋功能
echo "<div class='test-section'>
    <h2>搜尋功能測試（修復版）</h2>";

$keyword = '永吉';
echo "<h3>測試搜尋「{$keyword}」...</h3>";

$schools = fetchJuniorHighSchools($pdo);
$matches = [];
$seen_schools = [];

foreach ($schools as $school) {
    $match = false;
    if (stripos($school['name'], $keyword) !== false) {
        $match = true;
    }
    
    if ($match) {
        $normalized_name = normalizeSchoolName($school['name']);
        $normalized_city = normalizeCityName($school['city']);
        $unique_key = $normalized_city . '_' . $school['district'] . '_' . $normalized_name;
        
        if (!isset($seen_schools[$unique_key])) {
            $seen_schools[$unique_key] = true;
            $school['all_names'] = [$school['name']];
            $matches[] = $school;
        } else {
            $existing_index = findExistingSchoolIndex($matches, $unique_key);
            if ($existing_index !== -1) {
                if (!in_array($school['name'], $matches[$existing_index]['all_names'])) {
                    $matches[$existing_index]['all_names'][] = $school['name'];
                }
            }
        }
    }
}

echo "<div class='result success'>找到 " . count($matches) . " 個結果：</div>";

foreach ($matches as $school) {
    $displayName = $school['name'];
    $additionalInfo = '';
    
    if (isset($school['all_names']) && count($school['all_names']) > 1) {
        $additionalInfo = "<br><small>其他名稱: " . implode(', ', $school['all_names']) . "</small>";
    }
    
    echo "<div class='result'>
        <strong>{$displayName}</strong> - {$school['city']} {$school['district']}
        {$additionalInfo}
    </div>";
}

echo "<div class='debug'>
    <strong>調試信息：</strong><br>
    總學校數: " . count($schools) . "<br>
    唯一鍵數量: " . count($seen_schools) . "<br>
    唯一鍵: " . implode(', ', array_keys($seen_schools)) . "
</div>";

echo "</div>";

// 測試其他搜尋
echo "<div class='test-section'>
    <h2>測試其他常見重複案例</h2>";

$testKeywords = ['建國', '中山', '中正'];
foreach ($testKeywords as $testKeyword) {
    echo "<h3>搜尋「{$testKeyword}」:</h3>";
    
    $testMatches = [];
    $testSeen = [];
    
    foreach ($schools as $school) {
        $match = false;
        if (stripos($school['name'], $testKeyword) !== false) {
            $match = true;
        }
        
        if ($match) {
            $normalized_name = normalizeSchoolName($school['name']);
            $normalized_city = normalizeCityName($school['city']);
            $unique_key = $normalized_city . '_' . $school['district'] . '_' . $normalized_name;
            
            if (!isset($testSeen[$unique_key])) {
                $testSeen[$unique_key] = true;
                $school['all_names'] = [$school['name']];
                $testMatches[] = $school;
            } else {
                $existing_index = findExistingSchoolIndex($testMatches, $unique_key);
                if ($existing_index !== -1) {
                    if (!in_array($school['name'], $testMatches[$existing_index]['all_names'])) {
                        $testMatches[$existing_index]['all_names'][] = $school['name'];
                    }
                }
            }
        }
    }
    
    echo "<div class='result success'>找到 " . count($testMatches) . " 個結果：</div>";
    
    foreach ($testMatches as $school) {
        $displayName = $school['name'];
        $additionalInfo = '';
        
        if (isset($school['all_names']) && count($school['all_names']) > 1) {
            $additionalInfo = "<br><small>其他名稱: " . implode(', ', $school['all_names']) . "</small>";
        }
        
        echo "<div class='result'>
            <strong>{$displayName}</strong> - {$school['city']} {$school['district']}
            {$additionalInfo}
        </div>";
    }
}

echo "</div>";

echo "</body></html>";
?>








