<?php
/**
 * 更新學校地址工具頁面
 * 用於更新特定學校的地址資料
 */

// 載入 session 配置
require_once 'session_config.php';

// 載入資料庫配置
require_once 'config.php';

// 檢查是否為 POST 請求（執行更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $school_name = $_POST['school_name'] ?? '';
        $new_address = $_POST['new_address'] ?? '';
        $city = $_POST['city'] ?? '';
        $district = $_POST['district'] ?? '';
        
        if (empty($school_name) || empty($new_address)) {
            $error = "請填寫學校名稱和新地址";
        } else {
            // 構建更新查詢
            $sql = "UPDATE school_data SET address = :address WHERE name LIKE :name AND is_active = 1";
            $params = [
                ':address' => $new_address,
                ':name' => "%{$school_name}%"
            ];
            
            if (!empty($city)) {
                $sql .= " AND city = :city";
                $params[':city'] = $city;
            }
            
            if (!empty($district)) {
                $sql .= " AND district = :district";
                $params[':district'] = $district;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                $success = "成功更新 {$affected} 筆記錄";
            } else {
                $error = "未找到符合條件的學校記錄";
            }
        }
    } catch (PDOException $e) {
        $error = "更新失敗: " . $e->getMessage();
    }
}

// 查詢南港國中的資料
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT name, city, district, address, school_code 
                          FROM school_data 
                          WHERE name LIKE '%南港國中%' 
                            AND is_active = 1
                          ORDER BY city, district");
    $stmt->execute();
    $nangang_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $nangang_schools = [];
    $query_error = "查詢失敗: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新學校地址</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1976d2;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 10px;
        }
        h2 {
            color: #424242;
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #424242;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            background-color: #1976d2;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #1565c0;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1976d2;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .quick-update {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .quick-update button {
            background-color: #ffc107;
            color: #000;
            margin-top: 10px;
        }
        .quick-update button:hover {
            background-color: #ffb300;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏫 更新學校地址工具</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($query_error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($query_error); ?>
            </div>
        <?php endif; ?>
        
        <h2>快速更新：南港國中</h2>
        <div class="quick-update">
            <p><strong>正確地址：</strong>115臺北市南港區向陽路21號</p>
            <form method="POST" onsubmit="return confirm('確定要更新南港國中的地址嗎？');">
                <input type="hidden" name="school_name" value="南港國中">
                <input type="hidden" name="city" value="臺北市">
                <input type="hidden" name="district" value="南港區">
                <input type="hidden" name="new_address" value="115臺北市南港區向陽路21號">
                <button type="submit" name="update_address">更新南港國中地址</button>
            </form>
        </div>
        
        <h2>手動更新</h2>
        <form method="POST" onsubmit="return confirm('確定要更新地址嗎？');">
            <div class="form-group">
                <label for="school_name">學校名稱：</label>
                <input type="text" id="school_name" name="school_name" placeholder="例如：南港國中" required>
            </div>
            <div class="form-group">
                <label for="city">縣市（選填，用於精確匹配）：</label>
                <input type="text" id="city" name="city" placeholder="例如：臺北市">
            </div>
            <div class="form-group">
                <label for="district">區/鄉鎮市（選填，用於精確匹配）：</label>
                <input type="text" id="district" name="district" placeholder="例如：南港區">
            </div>
            <div class="form-group">
                <label for="new_address">新地址：</label>
                <input type="text" id="new_address" name="new_address" placeholder="例如：115臺北市南港區向陽路21號" required>
            </div>
            <button type="submit" name="update_address">更新地址</button>
        </form>
        
        <h2>目前資料庫中的南港國中資料</h2>
        <?php if (!empty($nangang_schools)): ?>
            <table>
                <thead>
                    <tr>
                        <th>學校名稱</th>
                        <th>縣市</th>
                        <th>區/鄉鎮市</th>
                        <th>地址</th>
                        <th>學校代碼</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nangang_schools as $school): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($school['name']); ?></td>
                            <td><?php echo htmlspecialchars($school['city']); ?></td>
                            <td><?php echo htmlspecialchars($school['district']); ?></td>
                            <td><strong><?php echo htmlspecialchars($school['address']); ?></strong></td>
                            <td><?php echo htmlspecialchars($school['school_code']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>未找到南港國中的資料</p>
        <?php endif; ?>
    </div>
</body>
</html>


