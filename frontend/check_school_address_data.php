<?php
/**
 * 檢查學校地址資料來源和欄位對應
 * 診斷工具：檢查 API 資料的實際欄位名稱
 */

// 載入 session 配置
require_once 'session_config.php';

// 載入資料庫配置
require_once 'config.php';

// 資料庫連接
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("資料庫連接失敗: " . $e->getMessage());
}

// API端點
$api_url = "https://data.nat.gov.tw/api/v1/datastore/ODRP001/6088";

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檢查學校地址資料</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1976d2;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            word-break: break-word;
        }
        th {
            background-color: #1976d2;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .field-name {
            font-weight: bold;
            color: #1976d2;
        }
        .sample-data {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 學校地址資料診斷工具</h1>
        
        <h2>1. 檢查 API 資料結構</h2>
        <?php
        // 獲取 API 資料
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $api_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($api_data && $http_code == 200) {
            $json_data = json_decode($api_data, true);
            
            if ($json_data && isset($json_data["result"]["records"])) {
                $records = $json_data["result"]["records"];
                $sample_record = null;
                
                // 找到一個國中的範例
                foreach ($records as $record) {
                    if (isset($record["學校名稱"]) && strpos($record["學校名稱"], "國中") !== false) {
                        $sample_record = $record;
                        break;
                    }
                }
                
                if ($sample_record) {
                    echo "<div class='alert alert-info'>";
                    echo "<strong>✅ 成功獲取 API 資料</strong><br>";
                    echo "找到 " . count($records) . " 筆記錄<br>";
                    echo "範例學校: " . htmlspecialchars($sample_record["學校名稱"] ?? "未知");
                    echo "</div>";
                    
                    echo "<h3>API 欄位名稱列表（範例記錄的所有欄位）：</h3>";
                    echo "<table>";
                    echo "<tr><th>欄位名稱</th><th>欄位值（範例）</th></tr>";
                    foreach ($sample_record as $key => $value) {
                        $display_value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                        $display_value = mb_substr($display_value, 0, 100);
                        echo "<tr>";
                        echo "<td class='field-name'>" . htmlspecialchars($key) . "</td>";
                        echo "<td class='sample-data'>" . htmlspecialchars($display_value) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // 檢查地址相關欄位
                    echo "<h3>地址相關欄位檢查：</h3>";
                    $address_fields = [];
                    foreach ($sample_record as $key => $value) {
                        if (stripos($key, "地址") !== false || 
                            stripos($key, "address") !== false ||
                            stripos($key, "路") !== false ||
                            stripos($key, "街") !== false) {
                            $address_fields[$key] = $value;
                        }
                    }
                    
                    if (!empty($address_fields)) {
                        echo "<div class='alert alert-info'>";
                        echo "<strong>找到可能的地址欄位：</strong><br>";
                        foreach ($address_fields as $key => $value) {
                            echo "<strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "<br>";
                        }
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-warning'>";
                        echo "⚠️ 未找到明顯的地址欄位，可能需要檢查其他欄位名稱";
                        echo "</div>";
                    }
                    
                    // 檢查目前程式碼使用的欄位
                    echo "<h3>目前程式碼使用的欄位對應：</h3>";
                    $current_mapping = [
                        "學校名稱" => $sample_record["學校名稱"] ?? "❌ 不存在",
                        "縣市名稱" => $sample_record["縣市名稱"] ?? "❌ 不存在",
                        "行政區" => $sample_record["行政區"] ?? "❌ 不存在",
                        "學校代碼" => $sample_record["學校代碼"] ?? "❌ 不存在",
                        "地址" => $sample_record["地址"] ?? "❌ 不存在",
                        "電話" => $sample_record["電話"] ?? "❌ 不存在",
                        "網址" => $sample_record["網址"] ?? "❌ 不存在"
                    ];
                    
                    echo "<table>";
                    echo "<tr><th>程式碼使用的欄位名稱</th><th>API 中的值</th><th>狀態</th></tr>";
                    foreach ($current_mapping as $field => $value) {
                        $status = ($value !== "❌ 不存在") ? "✅" : "❌";
                        echo "<tr>";
                        echo "<td class='field-name'>" . htmlspecialchars($field) . "</td>";
                        echo "<td>" . htmlspecialchars(is_string($value) ? mb_substr($value, 0, 100) : $value) . "</td>";
                        echo "<td>" . $status . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                } else {
                    echo "<div class='alert alert-warning'>";
                    echo "⚠️ 未找到國中學校的範例記錄";
                    echo "</div>";
                }
            } else {
                echo "<div class='alert alert-error'>";
                echo "❌ API 回應格式不符合預期";
                echo "</div>";
                echo "<pre>" . htmlspecialchars(substr($api_data, 0, 1000)) . "...</pre>";
            }
        } else {
            echo "<div class='alert alert-error'>";
            echo "❌ 無法獲取 API 資料 (HTTP: $http_code)";
            echo "</div>";
        }
        ?>
        
        <h2>2. 檢查資料庫中的地址資料</h2>
        <?php
        // 查詢資料庫中幾所學校的地址
        $stmt = $pdo->prepare("SELECT name, city, district, address, school_code, data_source 
                              FROM school_data 
                              WHERE name LIKE '%國中%' 
                                AND is_active = 1
                              ORDER BY city, district, name
                              LIMIT 20");
        $stmt->execute();
        $db_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($db_schools)) {
            echo "<div class='alert alert-info'>";
            echo "<strong>資料庫中的學校地址資料（前 20 筆）：</strong>";
            echo "</div>";
            
            echo "<table>";
            echo "<tr><th>學校名稱</th><th>縣市</th><th>區</th><th>地址</th><th>資料來源</th></tr>";
            foreach ($db_schools as $school) {
                $address_class = empty($school['address']) || 
                                 (strlen($school['address']) < 10) ? "alert-warning" : "";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($school['name']) . "</td>";
                echo "<td>" . htmlspecialchars($school['city']) . "</td>";
                echo "<td>" . htmlspecialchars($school['district']) . "</td>";
                echo "<td class='$address_class'>" . htmlspecialchars($school['address'] ?: "（無地址）") . "</td>";
                echo "<td>" . htmlspecialchars($school['data_source'] ?? "未知") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // 統計
            $empty_address_count = 0;
            $short_address_count = 0;
            foreach ($db_schools as $school) {
                if (empty($school['address'])) {
                    $empty_address_count++;
                } elseif (strlen($school['address']) < 10) {
                    $short_address_count++;
                }
            }
            
            echo "<div class='alert alert-warning'>";
            echo "<strong>統計：</strong><br>";
            echo "總共檢查: " . count($db_schools) . " 筆<br>";
            echo "無地址: $empty_address_count 筆<br>";
            echo "地址過短（&lt;10字）: $short_address_count 筆";
            echo "</div>";
        }
        ?>
        
        <h2>3. 檢查特定學校（南港國中）</h2>
        <?php
        $stmt = $pdo->prepare("SELECT name, city, district, address, school_code, data_source 
                              FROM school_data 
                              WHERE name LIKE '%南港國中%' 
                                AND is_active = 1");
        $stmt->execute();
        $nangang = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($nangang)) {
            echo "<table>";
            echo "<tr><th>學校名稱</th><th>縣市</th><th>區</th><th>地址</th><th>學校代碼</th><th>資料來源</th></tr>";
            foreach ($nangang as $school) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($school['name']) . "</td>";
                echo "<td>" . htmlspecialchars($school['city']) . "</td>";
                echo "<td>" . htmlspecialchars($school['district']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($school['address'] ?: "（無地址）") . "</strong></td>";
                echo "<td>" . htmlspecialchars($school['school_code']) . "</td>";
                echo "<td>" . htmlspecialchars($school['data_source'] ?? "未知") . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='alert alert-warning'>未找到南港國中的資料</div>";
        }
        ?>
    </div>
</body>
</html>


