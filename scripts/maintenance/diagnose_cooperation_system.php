<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>康寧大學產學合作系統診斷報告</h1>\n";

// 1. 檢查資料庫連線
echo "<h2>1. 資料庫連線檢查</h2>\n";
$host = '100.79.58.120';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 資料庫連線成功<br>\n";
    
    // 檢查資料表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'cooperation_applications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ 資料表 'cooperation_applications' 存在<br>\n";
        
        // 檢查資料表結構
        $stmt = $pdo->query("DESCRIBE cooperation_applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>資料表欄位：</h3>\n";
        echo "<ul>\n";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']}: {$column['Type']} " . 
                 ($column['Null'] === 'NO' ? '(必填)' : '(選填)') . "</li>\n";
        }
        echo "</ul>\n";
        
        // 檢查資料筆數
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM cooperation_applications");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 目前申請表數量：{$count} 筆<br>\n";
        
    } else {
        echo "❌ 資料表 'cooperation_applications' 不存在<br>\n";
        echo "請執行 setup_cooperation_table.php 來建立資料表<br>\n";
    }
    
} catch(PDOException $e) {
    echo "❌ 資料庫連線失敗: " . $e->getMessage() . "<br>\n";
}

// 2. 檢查上傳目錄
echo "<h2>2. 檔案上傳目錄檢查</h2>\n";
$upload_dir = '../uploads/cooperation/';
if (file_exists($upload_dir)) {
    echo "✅ 上傳目錄存在：$upload_dir<br>\n";
    
    if (is_writable($upload_dir)) {
        echo "✅ 上傳目錄可寫入<br>\n";
    } else {
        echo "❌ 上傳目錄不可寫入<br>\n";
        echo "請設定目錄權限：chmod 755 $upload_dir<br>\n";
    }
    
    // 檢查目錄內容
    $files = scandir($upload_dir);
    $file_count = count($files) - 2; // 減去 . 和 ..
    echo "📁 目錄內檔案數量：{$file_count} 個<br>\n";
    
} else {
    echo "❌ 上傳目錄不存在：$upload_dir<br>\n";
    echo "請建立目錄：mkdir -p $upload_dir<br>\n";
}

// 3. 檢查PHP設定
echo "<h2>3. PHP設定檢查</h2>\n";
echo "📋 PHP版本：" . phpversion() . "<br>\n";
echo "📋 檔案上傳最大大小：" . ini_get('upload_max_filesize') . "<br>\n";
echo "📋 POST最大大小：" . ini_get('post_max_size') . "<br>\n";
echo "📋 最大執行時間：" . ini_get('max_execution_time') . " 秒<br>\n";

// 4. 檢查必要檔案
echo "<h2>4. 必要檔案檢查</h2>\n";
$required_files = [
    'cooperation_upload_api.php',
    'cooperation_list_api.php',
    'cooperation_detail_api.php',
    'cooperation_review_api.php',
    'cooperation_teacher_list_api.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file 存在<br>\n";
    } else {
        echo "❌ $file 不存在<br>\n";
    }
}

// 5. 檢查前端檔案
echo "<h2>5. 前端檔案檢查</h2>\n";
$frontend_files = [
    '../frontend/cooperation_upload.php',
    '../frontend/teacher_cooperation_status.php',
    '../frontend/admin_cooperation_review.php'
];

foreach ($frontend_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file 存在<br>\n";
    } else {
        echo "❌ $file 不存在<br>\n";
    }
}

// 6. 測試API端點
echo "<h2>6. API端點測試</h2>\n";
$api_url = 'http://localhost/backend/cooperation_upload_api.php';
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => 'test=1'
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response !== false) {
    echo "✅ API端點可存取<br>\n";
} else {
    echo "❌ API端點無法存取<br>\n";
    echo "請檢查伺服器設定和URL路徑<br>\n";
}

echo "<h2>7. 建議解決方案</h2>\n";
echo "<ol>\n";
echo "<li>如果資料表不存在，請執行：php setup_cooperation_table.php</li>\n";
echo "<li>如果上傳目錄不存在，請建立：mkdir -p ../uploads/cooperation/</li>\n";
echo "<li>如果權限問題，請設定：chmod 755 ../uploads/cooperation/</li>\n";
echo "<li>檢查資料庫連線設定是否正確</li>\n";
echo "<li>確認PHP錯誤日誌中的詳細錯誤訊息</li>\n";
echo "</ol>\n";

echo "<h2>8. 常見錯誤訊息對應</h2>\n";
echo "<ul>\n";
echo "<li><strong>「提交失敗，請稍後再試」</strong>：通常是資料庫連線或資料表結構問題</li>\n";
echo "<li><strong>「檔案上傳失敗」</strong>：檢查上傳目錄權限和磁碟空間</li>\n";
echo "<li><strong>「缺少必要欄位」</strong>：檢查表單是否完整填寫</li>\n";
echo "<li><strong>「資料庫錯誤」</strong>：檢查資料庫連線和資料表結構</li>\n";
echo "</ul>\n";
?>
