<?php
/**
 * 直接檢查資料庫和 API 的地址資料
 */

require_once 'session_config.php';
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// 查詢幾所學校的地址
$stmt = $pdo->prepare("SELECT name, city, district, address, school_code, HEX(address) as address_hex
                      FROM school_data 
                      WHERE name LIKE '%國中%' 
                        AND is_active = 1
                      ORDER BY city, district, name
                      LIMIT 10");
$stmt->execute();
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取 API 資料
$api_url = "https://data.nat.gov.tw/api/v1/datastore/ODRP001/6088";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$api_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>地址資料檢查</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #1976d2; color: white; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>學校地址資料檢查</h1>
    
    <h2>資料庫中的地址資料（前 10 筆）</h2>
    <table>
        <tr>
            <th>學校名稱</th>
            <th>縣市</th>
            <th>區</th>
            <th>地址（原始）</th>
            <th>地址長度</th>
            <th>學校代碼</th>
        </tr>
        <?php foreach ($schools as $school): ?>
        <tr>
            <td><?php echo htmlspecialchars($school['name']); ?></td>
            <td><?php echo htmlspecialchars($school['city']); ?></td>
            <td><?php echo htmlspecialchars($school['district']); ?></td>
            <td><strong><?php echo htmlspecialchars($school['address'] ?: '（無）'); ?></strong></td>
            <td><?php echo strlen($school['address'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($school['school_code']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>API 資料檢查</h2>
    <?php if ($api_data && $http_code == 200): 
        $json_data = json_decode($api_data, true);
        if ($json_data && isset($json_data["result"]["records"])):
            // 找到南港國中
            $nangang = null;
            foreach ($json_data["result"]["records"] as $record) {
                if (isset($record["學校名稱"]) && strpos($record["學校名稱"], "南港") !== false && strpos($record["學校名稱"], "國中") !== false) {
                    $nangang = $record;
                    break;
                }
            }
            
            if ($nangang):
    ?>
        <h3>API 中的南港國中資料：</h3>
        <table>
            <tr><th>欄位名稱</th><th>值</th></tr>
            <?php foreach ($nangang as $key => $value): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                <td><?php echo htmlspecialchars(is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h3>地址欄位對應檢查：</h3>
        <ul>
            <li>使用 "地址" 欄位: <?php echo isset($nangang["地址"]) ? '<span class="success">✅ ' . htmlspecialchars($nangang["地址"]) . '</span>' : '<span class="error">❌ 不存在</span>'; ?></li>
            <li>使用 "學校地址" 欄位: <?php echo isset($nangang["學校地址"]) ? '<span class="success">✅ ' . htmlspecialchars($nangang["學校地址"]) . '</span>' : '<span class="error">❌ 不存在</span>'; ?></li>
            <li>使用 "address" 欄位: <?php echo isset($nangang["address"]) ? '<span class="success">✅ ' . htmlspecialchars($nangang["address"]) . '</span>' : '<span class="error">❌ 不存在</span>'; ?></li>
        </ul>
        
        <?php 
        // 檢查所有可能包含地址的欄位
        $address_candidates = [];
        foreach ($nangang as $key => $value) {
            if (is_string($value) && 
                (strpos($value, "路") !== false || 
                 strpos($value, "街") !== false || 
                 strpos($value, "號") !== false) &&
                strlen($value) > 10) {
                $address_candidates[$key] = $value;
            }
        }
        
        if (!empty($address_candidates)):
        ?>
        <h3>可能的地址欄位（包含路/街/號且長度>10）：</h3>
        <ul>
            <?php foreach ($address_candidates as $key => $value): ?>
            <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        
    <?php else: ?>
        <p class="error">❌ 在 API 資料中未找到南港國中</p>
    <?php endif; ?>
    
    <?php else: ?>
        <p class="error">❌ API 資料格式不符合預期</p>
        <pre><?php echo htmlspecialchars(substr($api_data, 0, 500)); ?>...</pre>
    <?php endif; ?>
    <?php else: ?>
        <p class="error">❌ 無法獲取 API 資料 (HTTP: <?php echo $http_code; ?>)</p>
    <?php endif; ?>
    
    <h2>資料庫中的南港國中</h2>
    <?php
    $stmt = $pdo->prepare("SELECT name, city, district, address, school_code 
                          FROM school_data 
                          WHERE name LIKE '%南港國中%' 
                            AND is_active = 1");
    $stmt->execute();
    $db_nangang = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($db_nangang):
    ?>
    <table>
        <tr><th>學校名稱</th><th>縣市</th><th>區</th><th>地址</th><th>學校代碼</th></tr>
        <?php foreach ($db_nangang as $school): ?>
        <tr>
            <td><?php echo htmlspecialchars($school['name']); ?></td>
            <td><?php echo htmlspecialchars($school['city']); ?></td>
            <td><?php echo htmlspecialchars($school['district']); ?></td>
            <td><strong class="error"><?php echo htmlspecialchars($school['address'] ?: '（無）'); ?></strong></td>
            <td><?php echo htmlspecialchars($school['school_code']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>資料庫中未找到南港國中</p>
    <?php endif; ?>
</body>
</html>


