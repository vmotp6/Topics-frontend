<?php
/**
 * 康寧大學五專入學說明會資料表設置腳本
 */

require_once '../../frontend/config.php';

echo "=== 康寧大學五專入學說明會資料表設置 ===\n\n";

try {
    // 建立資料庫連接
    $conn = getDatabaseConnection();
    
    // 讀取 SQL 文件
    $sql_file = __DIR__ . '/../database/create_admission_table.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL 文件不存在：{$sql_file}");
    }
    
    $sql = file_get_contents($sql_file);
    
    // 執行 SQL
    if ($conn->query($sql)) {
        echo "✅ admission_applications 資料表創建成功！\n\n";
        
        // 檢查表格結構
        $result = $conn->query("DESCRIBE admission_applications");
        echo "📋 資料表結構：\n";
        echo str_pad("欄位名稱", 20) . str_pad("資料類型", 20) . str_pad("允許空值", 10) . str_pad("鍵值", 10) . "說明\n";
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            echo str_pad($row['Field'], 20) . 
                 str_pad($row['Type'], 20) . 
                 str_pad($row['Null'], 10) . 
                 str_pad($row['Key'], 10) . 
                 "\n";
        }
        
        echo "\n✅ 資料表設置完成！\n";
        echo "📌 下一步：\n";
        echo "   1. 確認網頁 frontend/admission.php 可以正常運作\n";
        echo "   2. 設置定期執行 scripts/maintenance/send_admission_reminders.php\n";
        echo "   3. 配置郵件發送設定\n\n";
        
    } else {
        throw new Exception("SQL 執行失敗：" . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    exit(1);
}
?>
