<?php
// 調試永吉國中的詳細資料
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

echo "<!DOCTYPE html>
<html lang='zh-TW'>
<head>
    <meta charset='UTF-8'>
    <title>永吉國中調試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .school { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .debug { background: #f0f8ff; padding: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>永吉國中詳細資料調試</h1>";

// 查詢所有包含「永吉」的學校
$stmt = $pdo->prepare("SELECT * FROM school_data WHERE name LIKE '%永吉%' AND type = '國民中學'");
$stmt->execute();
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>資料庫中的永吉相關學校：</h2>";
foreach ($schools as $school) {
    echo "<div class='school'>";
    echo "<strong>學校名稱：</strong>" . htmlspecialchars($school['name']) . "<br>";
    echo "<strong>城市：</strong>" . htmlspecialchars($school['city']) . "<br>";
    echo "<strong>區域：</strong>" . htmlspecialchars($school['district']) . "<br>";
    echo "<strong>類型：</strong>" . htmlspecialchars($school['type']) . "<br>";
    echo "<strong>學校代碼：</strong>" . htmlspecialchars($school['school_code']) . "<br>";
    echo "<strong>地址：</strong>" . htmlspecialchars($school['address']) . "<br>";
    echo "<strong>電話：</strong>" . htmlspecialchars($school['phone']) . "<br>";
    echo "<strong>網站：</strong>" . htmlspecialchars($school['website']) . "<br>";
    echo "<strong>資料來源：</strong>" . htmlspecialchars($school['data_source']) . "<br>";
    echo "</div>";
}

// 標準化函數
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

function normalizeCityName($cityName) {
    $cityMappings = [
        '臺北市' => '台北市',
        '臺中市' => '台中市',
        '臺南市' => '台南市',
        '臺東縣' => '台東縣'
    ];
    
    return $cityMappings[$cityName] ?? $cityName;
}

echo "<h2>標準化測試：</h2>";
foreach ($schools as $school) {
    $normalized_name = normalizeSchoolName($school['name']);
    $normalized_city = normalizeCityName($school['city']);
    $unique_key = $normalized_city . '_' . $school['district'] . '_' . $normalized_name;
    
    echo "<div class='debug'>";
    echo "<strong>原始名稱：</strong>" . htmlspecialchars($school['name']) . "<br>";
    echo "<strong>標準化名稱：</strong>" . htmlspecialchars($normalized_name) . "<br>";
    echo "<strong>原始城市：</strong>" . htmlspecialchars($school['city']) . "<br>";
    echo "<strong>標準化城市：</strong>" . htmlspecialchars($normalized_city) . "<br>";
    echo "<strong>區域：</strong>" . htmlspecialchars($school['district']) . "<br>";
    echo "<strong>唯一鍵：</strong>" . htmlspecialchars($unique_key) . "<br>";
    echo "</div>";
}

echo "</body></html>";
?>







