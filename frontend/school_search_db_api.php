<?php
/**
 * 國中學校搜尋API (資料庫版本)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理OPTIONS請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查schools表是否存在，如果不存在則創建
    $stmt = $pdo->query("SHOW TABLES LIKE 'schools'");
    if ($stmt->rowCount() == 0) {
        // 創建schools表
        $sql = "CREATE TABLE schools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL COMMENT '學校名稱',
            city VARCHAR(50) NOT NULL COMMENT '縣市',
            district VARCHAR(50) NOT NULL COMMENT '區域',
            address VARCHAR(500) NOT NULL COMMENT '地址',
            phone VARCHAR(50) DEFAULT NULL COMMENT '電話',
            website VARCHAR(255) DEFAULT NULL COMMENT '網站',
            type ENUM('公立', '私立') DEFAULT '公立' COMMENT '學校類型',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_city (city),
            INDEX idx_district (district),
            INDEX idx_city_district (city, district)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='國中學校資料表'";
        
        $pdo->exec($sql);
        
        // 插入基本資料
        $insertSql = "INSERT INTO schools (name, city, district, address, type) VALUES 
            ('台北市立中正國中', '台北市', '中正區', '台北市中正區重慶南路一段139號', '公立'),
            ('台北市立建國中學', '台北市', '中正區', '台北市中正區南海路56號', '公立'),
            ('台北市立金華國中', '台北市', '大安區', '台北市大安區新生南路二段32號', '公立'),
            ('新北市立板橋國中', '新北市', '板橋區', '新北市板橋區文化路一段188號', '公立'),
            ('新北市立新莊國中', '新北市', '新莊區', '新北市新莊區中正路211號', '公立'),
            ('桃園市立桃園國中', '桃園市', '桃園區', '桃園市桃園區中正路107號', '公立'),
            ('台中市立台中國中', '台中市', '中區', '台中市中區三民路二段46號', '公立'),
            ('台南市立台南國中', '台南市', '中西區', '台南市中西區民族路二段87號', '公立'),
            ('高雄市立高雄國中', '高雄市', '新興區', '高雄市新興區中正三路32號', '公立'),
            ('基隆市立基隆國中', '基隆市', '中正區', '基隆市中正區中正路115號', '公立')";
        
        $pdo->exec($insertSql);
    }
    
    // 搜尋功能
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $keyword = $_GET['keyword'] ?? '';
        $city = $_GET['city'] ?? '';
        $limit = min(intval($_GET['limit'] ?? 50), 100); // 限制最多100筆
        
        // 構建SQL查詢
        $sql = "SELECT * FROM schools WHERE 1=1";
        $params = [];
        
        // 關鍵字搜尋
        if (!empty($keyword)) {
            $sql .= " AND (name LIKE :keyword OR city LIKE :keyword OR district LIKE :keyword)";
            $params[':keyword'] = "%{$keyword}%";
        }
        
        // 縣市篩選
        if (!empty($city)) {
            $sql .= " AND city = :city";
            $params[':city'] = $city;
        }
        
        $sql .= " ORDER BY city, district, name LIMIT " . intval($limit);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 獲取總數
        $countSql = "SELECT COUNT(*) as total FROM schools WHERE 1=1";
        $countParams = [];
        
        if (!empty($keyword)) {
            $countSql .= " AND (name LIKE :keyword OR city LIKE :keyword OR district LIKE :keyword)";
            $countParams[':keyword'] = "%{$keyword}%";
        }
        
        if (!empty($city)) {
            $countSql .= " AND city = :city";
            $countParams[':city'] = $city;
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'count' => count($schools),
            'total' => $total,
            'schools' => $schools,
            'search_params' => [
                'keyword' => $keyword,
                'city' => $city,
                'limit' => $limit
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '只支援GET請求'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ]);
}
?>
