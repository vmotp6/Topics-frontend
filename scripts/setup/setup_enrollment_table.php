<?php
// 資料庫連線設定
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "正在建立康寧大學就讀意願登錄資料表...\n";
    
    // 先檢查並刪除舊表（如果存在）
    $pdo->exec("DROP TABLE IF EXISTS enrollment_applications");
    echo "✅ 舊表已清理\n";
    
    // 創建新的就讀意願登錄資料表
    $sql = "CREATE TABLE enrollment_applications (
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
        remarks TEXT NULL,
        status ENUM('pending', 'contacted', 'enrolled') DEFAULT 'pending',
        admin_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        INDEX idx_identity (identity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ 就讀意願登錄資料表建立成功！\n";
    
    // 插入測試資料
    $test_data = [
        [
            'username' => 'test_student1',
            'name' => '張小明',
            'identity' => '學生',
            'gender' => '男',
            'phone1' => '0912345678',
            'email' => 'test1@example.com',
            'intention1' => '資訊管理科',
            'system1' => '五專',
            'department1' => '資訊管理科',
            'junior_high' => '中正國中',
            'current_grade' => '國三',
            'status' => 'pending'
        ],
        [
            'username' => 'test_parent1',
            'name' => '李媽媽',
            'identity' => '家長',
            'gender' => '女',
            'phone1' => '0923456789',
            'email' => 'test2@example.com',
            'intention1' => '企業管理科',
            'system1' => '五專',
            'department1' => '企業管理科',
            'junior_high' => '建國國中',
            'current_grade' => '國二',
            'status' => 'contacted'
        ],
        [
            'username' => 'test_student2',
            'name' => '王小華',
            'identity' => '學生',
            'gender' => '女',
            'phone1' => '0934567890',
            'email' => 'test3@example.com',
            'intention1' => '應用外語科',
            'system1' => '五專',
            'department1' => '應用外語科',
            'junior_high' => '復興國中',
            'current_grade' => '國三',
            'status' => 'enrolled'
        ]
    ];
    
    $insert_sql = "INSERT INTO enrollment_applications (
        username, name, identity, gender, phone1, email, 
        intention1, system1, department1, 
        junior_high, current_grade, status
    ) VALUES (
        :username, :name, :identity, :gender, :phone1, :email,
        :intention1, :system1, :department1,
        :junior_high, :current_grade, :status
    )";
    
    $stmt = $pdo->prepare($insert_sql);
    
    foreach ($test_data as $data) {
        $stmt->execute($data);
    }
    
    echo "✅ 測試資料插入成功！\n";
    
    // 檢查資料表是否建立成功
    $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 資料表確認存在\n";
        
        // 顯示資料表結構
        $stmt = $pdo->query("DESCRIBE enrollment_applications");
        echo "\n📋 資料表結構：\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']} ({$row['Null']})\n";
        }
        
        // 顯示記錄數量
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM enrollment_applications");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n📊 總記錄數：{$count}\n";
        
        // 顯示各狀態的統計
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM enrollment_applications GROUP BY status");
        echo "\n📈 狀態統計：\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status_text = [
                'pending' => '待聯絡',
                'contacted' => '已聯絡',
                'enrolled' => '已入學'
            ];
            echo "- {$status_text[$row['status']]}: {$row['count']} 筆\n";
        }
        
    } else {
        echo "❌ 資料表建立失敗\n";
    }
    
} catch (PDOException $e) {
    echo "❌ 資料庫錯誤: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 系統錯誤: " . $e->getMessage() . "\n";
}

echo "\n🎉 就讀意願登錄資料表設定完成！\n";
?>
