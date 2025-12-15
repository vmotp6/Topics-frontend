<?php
/**
 * 康寧大學招生公告欄資料表設置腳本
 * 用於建立公告欄相關的資料表結構
 */

require_once '../../frontend/config.php';

echo "=== 康寧大學招生公告欄資料表設置 ===\n\n";

try {
    // 建立資料庫連接
    $conn = getDatabaseConnection();
    
    // 讀取 SQL 文件
    $sql_file = __DIR__ . '/../database/create_bulletin_board_table.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL 文件不存在：{$sql_file}");
    }
    
    $sql = file_get_contents($sql_file);
    
    // 使用 multi_query 執行多個 SQL 語句
    if ($conn->multi_query($sql)) {
        $success_count = 0;
        $error_count = 0;
        
        // 處理第一個結果
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
            
            if ($conn->more_results()) {
                $conn->next_result();
            }
            
            $success_count++;
        } while ($conn->more_results());
        
        echo "✅ SQL 腳本執行成功！\n\n";
    } else {
        throw new Exception("SQL 執行失敗：" . $conn->error);
    }
    
    // 檢查主要資料表結構
    $tables_to_check = ['bulletin_types', 'bulletin_statuses', 'bulletin_board', 'bulletin_views'];
    
    foreach ($tables_to_check as $table_name) {
        $result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
        if ($result && $result->num_rows > 0) {
            echo "📋 資料表 '{$table_name}' 結構：\n";
            $desc_result = $conn->query("DESCRIBE {$table_name}");
            echo str_pad("欄位名稱", 25) . str_pad("資料類型", 25) . str_pad("允許空值", 12) . str_pad("鍵值", 12) . "說明\n";
            echo str_repeat("-", 100) . "\n";
            
            while ($row = $desc_result->fetch_assoc()) {
                $comment = isset($row['Comment']) ? $row['Comment'] : '';
                echo str_pad($row['Field'], 25) . 
                     str_pad($row['Type'], 25) . 
                     str_pad($row['Null'], 12) . 
                     str_pad($row['Key'], 12) . 
                     $comment . "\n";
            }
            echo "\n";
        } else {
            echo "⚠️  資料表 '{$table_name}' 不存在\n\n";
        }
    }
    
    // 檢查外鍵約束
    echo "🔗 外鍵約束檢查：\n";
    $fk_result = $conn->query("
        SELECT 
            TABLE_NAME,
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME IN ('bulletin_board', 'bulletin_views')
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, CONSTRAINT_NAME
    ");
    
    if ($fk_result && $fk_result->num_rows > 0) {
        while ($row = $fk_result->fetch_assoc()) {
            echo "  ✅ {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
        }
    } else {
        echo "  ⚠️  未找到外鍵約束\n";
    }
    
    echo "\n✅ 資料表設置完成！\n";
    echo "📌 下一步：\n";
    echo "   1. 確認網頁 frontend/bulletin_board.php 可以正常運作\n";
    echo "   2. 可以開始在後台建立公告內容\n";
    echo "   3. 公告類型：exam（考試資訊）、interview（面試通知）、result（錄取結果）、general（一般公告）\n";
    echo "   4. 公告狀態：draft（草稿）、published（已發布）、archived（已歸檔）\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    exit(1);
}
?>
