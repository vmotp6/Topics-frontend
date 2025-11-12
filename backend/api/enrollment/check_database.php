<?php
// 檢查資料庫和資料表狀態
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$db_username = 'root';
$db_password = '';

try {
    // 連接到 MySQL 伺服器
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = [
        'success' => true,
        'database_status' => [],
        'tables' => [],
        'enrollment_data' => []
    ];
    
    // 檢查資料庫是否存在
    $stmt = $pdo->query("SHOW DATABASES LIKE 'topics_good'");
    if ($stmt->rowCount() > 0) {
        $result['database_status']['topics_good'] = '存在';
        
        // 連接到 topics_good 資料庫
        $pdo->exec("USE topics_good");
        
        // 列出所有資料表
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $result['tables'] = $tables;
        
        // 檢查 enrollment_applications 資料表
        if (in_array('enrollment_applications', $tables)) {
            $result['database_status']['enrollment_applications'] = '存在';
            
            // 檢查資料表結構
            $stmt = $pdo->query("DESCRIBE enrollment_applications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result['table_structure'] = $columns;
            
            // 檢查資料筆數
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollment_applications");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $result['data_count'] = $count['count'];
            
            // 如果有資料，顯示最新的幾筆
            if ($count['count'] > 0) {
                $stmt = $pdo->query("SELECT id, username, name, identity, phone1, email, created_at FROM enrollment_applications ORDER BY created_at DESC LIMIT 5");
                $recent_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result['recent_applications'] = $recent_data;
            }
        } else {
            $result['database_status']['enrollment_applications'] = '不存在';
        }
    } else {
        $result['database_status']['topics_good'] = '不存在';
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接錯誤: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
