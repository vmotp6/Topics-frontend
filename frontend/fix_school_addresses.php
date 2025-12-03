<?php
/**
 * 修復學校地址資料
 * 從 API 重新獲取並更新地址
 */

require_once 'session_config.php';
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'check';
$update_all = isset($_GET['update_all']) && $_GET['update_all'] == '1';

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

$api_schools = [];
if ($api_data && $http_code == 200) {
    $json_data = json_decode($api_data, true);
    if ($json_data && isset($json_data["result"]["records"])) {
        foreach ($json_data["result"]["records"] as $record) {
            if (isset($record["學校名稱"]) && strpos($record["學校名稱"], "國中") !== false) {
                // 嘗試多種可能的地址欄位
                $address = "";
                $possible_fields = ["地址", "學校地址", "address", "完整地址"];
                foreach ($possible_fields as $field) {
                    if (isset($record[$field]) && !empty(trim($record[$field]))) {
                        $address = trim($record[$field]);
                        break;
                    }
                }
                
                // 如果找不到，嘗試尋找包含地址特徵的欄位
                if (empty($address)) {
                    foreach ($record as $key => $value) {
                        if (is_string($value) && 
                            (strpos($value, "路") !== false || 
                             strpos($value, "街") !== false || 
                             strpos($value, "號") !== false) &&
                            strlen($value) > 10) {
                            $address = trim($value);
                            break;
                        }
                    }
                }
                
                $api_schools[] = [
                    "name" => $record["學校名稱"],
                    "city" => $record["縣市名稱"] ?? "",
                    "district" => $record["行政區"] ?? "",
                    "school_code" => $record["學校代碼"] ?? "",
                    "address" => $address,
                    "phone" => $record["電話"] ?? "",
                    "website" => $record["網址"] ?? ""
                ];
            }
        }
    }
}

// 執行更新
if ($action == 'update' && $update_all) {
    $pdo->beginTransaction();
    $updated = 0;
    $not_found = 0;
    
    try {
        foreach ($api_schools as $api_school) {
            if (empty($api_school['address'])) continue;
            
            // 根據學校名稱和縣市更新
            $stmt = $pdo->prepare("UPDATE school_data 
                                  SET address = :address 
                                  WHERE name = :name 
                                    AND city = :city 
                                    AND is_active = 1");
            $stmt->execute([
                ':address' => $api_school['address'],
                ':name' => $api_school['name'],
                ':city' => $api_school['city']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updated++;
            } else {
                $not_found++;
            }
        }
        
        $pdo->commit();
        $success_message = "成功更新 $updated 筆記錄，未找到 $not_found 筆";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "更新失敗: " . $e->getMessage();
    }
}

// 查詢資料庫中的學校
$stmt = $pdo->prepare("SELECT name, city, district, address, school_code 
                      FROM school_data 
                      WHERE name LIKE '%國中%' 
                        AND is_active = 1
                      ORDER BY city, district, name
                      LIMIT 50");
$stmt->execute();
$db_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 比對 API 和資料庫的地址
$comparison = [];
foreach ($db_schools as $db_school) {
    $api_match = null;
    foreach ($api_schools as $api_school) {
        if ($api_school['name'] == $db_school['name'] && 
            $api_school['city'] == $db_school['city']) {
            $api_match = $api_school;
            break;
        }
    }
    
    if ($api_match) {
        $comparison[] = [
            'name' => $db_school['name'],
            'city' => $db_school['city'],
            'district' => $db_school['district'],
            'db_address' => $db_school['address'],
            'api_address' => $api_match['address'],
            'match' => ($db_school['address'] == $api_match['address']),
            'different' => ($db_school['address'] != $api_match['address'] && !empty($api_match['address']))
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>修復學校地址</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; margin-bottom: 20px; }
        h1, h2 { color: #1976d2; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #1976d2; color: white; }
        tr.different { background: #fff3cd; }
        tr.match { background: #d4edda; }
        .btn { background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #1565c0; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .address-db { color: #dc3545; font-weight: bold; }
        .address-api { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 修復學校地址資料</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <strong>API 資料狀態：</strong><br>
            <?php if ($http_code == 200): ?>
                ✅ 成功獲取 API 資料，找到 <?php echo count($api_schools); ?> 所國中
            <?php else: ?>
                ❌ 無法獲取 API 資料 (HTTP: <?php echo $http_code; ?>)
            <?php endif; ?>
        </div>
        
        <h2>地址比對結果（前 50 筆）</h2>
        <p>
            <a href="?action=update&update_all=1" class="btn btn-danger" 
               onclick="return confirm('確定要更新所有地址嗎？這將用 API 的地址覆蓋資料庫中的地址。');">
                🔄 更新所有地址
            </a>
            <a href="?" class="btn">🔄 重新檢查</a>
        </p>
        
        <table>
            <tr>
                <th>學校名稱</th>
                <th>縣市</th>
                <th>區</th>
                <th>資料庫地址</th>
                <th>API 地址</th>
                <th>狀態</th>
            </tr>
            <?php 
            $different_count = 0;
            $match_count = 0;
            foreach ($comparison as $comp): 
                if ($comp['different']) $different_count++;
                if ($comp['match']) $match_count++;
            ?>
            <tr class="<?php echo $comp['different'] ? 'different' : ($comp['match'] ? 'match' : ''); ?>">
                <td><?php echo htmlspecialchars($comp['name']); ?></td>
                <td><?php echo htmlspecialchars($comp['city']); ?></td>
                <td><?php echo htmlspecialchars($comp['district']); ?></td>
                <td class="address-db"><?php echo htmlspecialchars($comp['db_address'] ?: '（無）'); ?></td>
                <td class="address-api"><?php echo htmlspecialchars($comp['api_address'] ?: '（無）'); ?></td>
                <td>
                    <?php if ($comp['different']): ?>
                        <span style="color: #dc3545;">⚠️ 不一致</span>
                    <?php elseif ($comp['match']): ?>
                        <span style="color: #28a745;">✅ 一致</span>
                    <?php else: ?>
                        <span style="color: #6c757d;">➖ 無資料</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="alert alert-info">
            <strong>統計：</strong><br>
            總共比對: <?php echo count($comparison); ?> 筆<br>
            地址一致: <span style="color: #28a745;"><?php echo $match_count; ?></span> 筆<br>
            地址不一致: <span style="color: #dc3545;"><?php echo $different_count; ?></span> 筆
        </div>
        
        <h2>API 資料範例（前 5 筆）</h2>
        <table>
            <tr><th>學校名稱</th><th>縣市</th><th>區</th><th>地址</th><th>學校代碼</th></tr>
            <?php foreach (array_slice($api_schools, 0, 5) as $school): ?>
            <tr>
                <td><?php echo htmlspecialchars($school['name']); ?></td>
                <td><?php echo htmlspecialchars($school['city']); ?></td>
                <td><?php echo htmlspecialchars($school['district']); ?></td>
                <td><strong><?php echo htmlspecialchars($school['address'] ?: '（無）'); ?></strong></td>
                <td><?php echo htmlspecialchars($school['school_code']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>


