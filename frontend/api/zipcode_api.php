<?php
/**
 * 郵遞區號查詢 API
 * 簡化版本：只查詢縣市和鄉鎮，不包含路名和路段
 * 優先使用本地資料庫查詢，如果資料庫沒有資料則使用備用資料
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接設定
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

// 根據郵遞區號查詢縣市鄉鎮
$zipcode = isset($_GET['zipcode']) ? trim($_GET['zipcode']) : '';
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : $zipcode;

if (empty($zipcode) || !preg_match('/^\d{3,6}$/', $zipcode)) {
    echo json_encode([
        'success' => false,
        'message' => '請輸入有效的郵遞區號（3-6位數字）'
    ]);
    exit;
}

// 優先從資料庫查詢
$result = searchZipCodeByCode($zipcode);

if ($result) {
    echo json_encode([
        'success' => true,
        'data' => $result,
        'source' => 'database'
    ]);
} else {
    // 如果資料庫沒有資料，使用備用資料
    $fallbackData = getZipCodeFallback($zipcode);
    if ($fallbackData) {
        echo json_encode([
            'success' => true,
            'data' => $fallbackData,
            'source' => 'fallback'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '找不到對應的郵遞區號資料'
        ]);
    }
}

/**
 * 從資料庫根據郵遞區號查詢縣市鄉鎮
 * 簡化版本：只返回縣市和鄉鎮，不包含路名和路段
 * 支援3碼和6碼郵遞區號，6碼郵遞區號使用前3碼查詢（區域碼）
 */
function searchZipCodeByCode($zipcode) {
    global $host, $dbname, $db_username, $db_password;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 如果是6碼郵遞區號，使用前3碼查詢（區域碼）
        if (strlen($zipcode) == 6) {
            $zipcode = substr($zipcode, 0, 3);
        }
        
        // 只查詢縣市和鄉鎮，不查詢路名和路段
        $sql = "SELECT DISTINCT city, district 
                FROM zipcode_data 
                WHERE zipcode = :zipcode 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':zipcode' => $zipcode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'city' => $result['city'],
                'district' => $result['district']
            ];
        }
    } catch (PDOException $e) {
        error_log('郵遞區號查詢資料庫錯誤: ' . $e->getMessage());
    }
    
    return null;
}


/**
 * 備用郵遞區號資料（主要區域）
 * 當資料庫沒有資料時使用
 */
function getZipCodeFallback($zipcode) {
    $zipCodeMap = [
        '100' => ['city' => '台北市', 'district' => '中正區'],
        '103' => ['city' => '台北市', 'district' => '大同區'],
        '104' => ['city' => '台北市', 'district' => '中山區'],
        '105' => ['city' => '台北市', 'district' => '松山區'],
        '106' => ['city' => '台北市', 'district' => '大安區'],
        '108' => ['city' => '台北市', 'district' => '萬華區'],
        '110' => ['city' => '台北市', 'district' => '信義區'],
        '111' => ['city' => '台北市', 'district' => '士林區'],
        '112' => ['city' => '台北市', 'district' => '北投區'],
        '114' => ['city' => '台北市', 'district' => '內湖區'],
        '115' => ['city' => '台北市', 'district' => '南港區'],
        '116' => ['city' => '台北市', 'district' => '文山區'],
        '220' => ['city' => '新北市', 'district' => '板橋區'],
        '221' => ['city' => '新北市', 'district' => '汐止區'],
        '231' => ['city' => '新北市', 'district' => '新店區'],
        '234' => ['city' => '新北市', 'district' => '永和區'],
        '235' => ['city' => '新北市', 'district' => '中和區'],
        '241' => ['city' => '新北市', 'district' => '三重區'],
        '242' => ['city' => '新北市', 'district' => '新莊區'],
        '300' => ['city' => '新竹市', 'district' => '東區'],
        '330' => ['city' => '桃園市', 'district' => '桃園區'],
        '400' => ['city' => '台中市', 'district' => '中區'],
        '700' => ['city' => '台南市', 'district' => '中西區'],
        '800' => ['city' => '高雄市', 'district' => '新興區'],
    ];
    
    // 先嘗試精確匹配
    if (isset($zipCodeMap[$zipcode])) {
        return $zipCodeMap[$zipcode];
    }
    
    // 如果是5碼，嘗試前3碼匹配
    if (strlen($zipcode) == 5) {
        $prefix = substr($zipcode, 0, 3);
        if (isset($zipCodeMap[$prefix])) {
            return $zipCodeMap[$prefix];
        }
    }
    
    // 前3位匹配（通用）
    if (strlen($zipcode) >= 3) {
        $prefix = substr($zipcode, 0, 3);
        foreach ($zipCodeMap as $code => $data) {
            if ($code === $prefix) {
                return $data;
            }
        }
    }
    
    return null;
}
?>
