<?php
// 資料庫設定腳本
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$db_username = 'root';
$db_password = '';

try {
    // 先連接到 MySQL 伺服器（不指定資料庫）
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 創建資料庫（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS topics_good CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 選擇資料庫
    $pdo->exec("USE topics_good");
    
    // 創建資料表
    $sql = "CREATE TABLE IF NOT EXISTS enrollment_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        identity ENUM('學生', '家長') NOT NULL,
        gender ENUM('男', '女') NULL,
        phone1 VARCHAR(50) NOT NULL,
        phone2 VARCHAR(50) NULL,
        email VARCHAR(255) NULL,
        intention1 VARCHAR(255) DEFAULT '無特定',
        system1 VARCHAR(50) NULL,
        department1 VARCHAR(255) NULL,
        intention2 VARCHAR(255) DEFAULT '無特定',
        system2 VARCHAR(50) NULL,
        department2 VARCHAR(255) NULL,
        intention3 VARCHAR(255) DEFAULT '無特定',
        system3 VARCHAR(50) NULL,
        department3 VARCHAR(255) NULL,
        junior_high VARCHAR(255) NULL,
        current_grade VARCHAR(50) NULL,
        line_id VARCHAR(255) NULL,
        facebook VARCHAR(255) NULL,
        recommended_teacher VARCHAR(255) NULL,
        remarks TEXT NULL,
        status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
        admin_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => '資料庫和資料表創建成功'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤: ' . $e->getMessage()
    ]);
}
?>
