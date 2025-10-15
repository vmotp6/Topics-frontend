<?php
// 測試學校搜尋去重功能

// 載入必要的函數（不執行搜尋邏輯）
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
    echo "<p style='color: red;'>資料庫連接失敗: " . $e->getMessage() . "</p>";
    exit;
}

// 複製必要的函數
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

echo "<h2>學校搜尋去重功能測試</h2>";

// 測試標準化函數
echo "<h3>1. 學校名稱標準化測試</h3>";
$testNames = [
    '永吉國中',
    '市立永吉國中',
    '台北市立永吉國中',
    '永吉國民中學',
    '市立永吉國民中學'
];

foreach ($testNames as $name) {
    $normalized = normalizeSchoolName($name);
    echo "<p><strong>{$name}</strong> → <em>{$normalized}</em></p>";
}

// 測試名稱評分
echo "<h3>2. 學校名稱評分測試</h3>";
foreach ($testNames as $name) {
    $score = calculateNameScore($name);
    echo "<p><strong>{$name}</strong> → 分數: <em>{$score}</em></p>";
}

// 測試搜尋功能
echo "<h3>3. 搜尋功能測試</h3>";
echo "<p>測試搜尋「永吉」...</p>";

// 直接執行搜尋邏輯
$keyword = '永吉';
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
        $unique_key = $school['city'] . '_' . $school['district'] . '_' . $normalized_name;
        
        if (!isset($seen_schools[$unique_key])) {
            $seen_schools[$unique_key] = true;
            $school['all_names'] = [$school['name']];
            $matches[] = $school;
        } else {
            // 整合重複的學校
            $existing_index = -1;
            foreach ($matches as $index => $existing_school) {
                $existing_normalized = normalizeSchoolName($existing_school['name']);
                $existing_key = $existing_school['city'] . '_' . $existing_school['district'] . '_' . $existing_normalized;
                if ($existing_key === $unique_key) {
                    $existing_index = $index;
                    break;
                }
            }
            
            if ($existing_index !== -1) {
                if (!in_array($school['name'], $matches[$existing_index]['all_names'])) {
                    $matches[$existing_index]['all_names'][] = $school['name'];
                }
            }
        }
    }
}

echo "<p>找到 " . count($matches) . " 個結果：</p>";
echo "<ul>";
foreach ($matches as $school) {
    echo "<li><strong>{$school['name']}</strong> - {$school['city']} {$school['district']}";
    if (isset($school['all_names']) && count($school['all_names']) > 1) {
        echo "<br><em style='color: #667eea; font-size: 0.9em;'>其他名稱: " . implode(', ', $school['all_names']) . "</em>";
    }
    echo "</li>";
}
echo "</ul>";

echo "<p><strong>調試信息：</strong></p>";
echo "<ul>";
echo "<li>總學校數: " . count($schools) . "</li>";
echo "<li>唯一鍵數量: " . count($seen_schools) . "</li>";
echo "<li>唯一鍵: " . implode(', ', array_keys($seen_schools)) . "</li>";
echo "</ul>";

echo "<h3>4. 測試其他常見重複案例</h3>";
$testKeywords = ['建國', '成功', '中山'];

foreach ($testKeywords as $keyword) {
    echo "<h4>搜尋「{$keyword}」：</h4>";
    
    $matches = [];
    $seen_schools = [];
    
    foreach ($schools as $school) {
        $match = false;
        
        if (stripos($school['name'], $keyword) !== false) {
            $match = true;
        }
        
        if ($match) {
            $normalized_name = normalizeSchoolName($school['name']);
            $unique_key = $school['city'] . '_' . $school['district'] . '_' . $normalized_name;
            
            if (!isset($seen_schools[$unique_key])) {
                $seen_schools[$unique_key] = true;
                $matches[] = $school;
            }
        }
    }
    
    echo "<p>找到 " . count($matches) . " 個結果：</p>";
    echo "<ul>";
    foreach ($matches as $school) {
        echo "<li><strong>{$school['name']}</strong> - {$school['city']} {$school['district']}</li>";
    }
    echo "</ul>";
}
?>

<style>
body { font-family: 'Microsoft JhengHei', Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #667eea; }
p { margin: 5px 0; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
