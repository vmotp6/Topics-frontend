<?php
/**
 * 修復損壞的資料表問題
 * 處理 schools_contacts_old 表的損壞問題
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連接配置
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修復損壞的資料表</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #27ae60;
            padding: 10px;
            background: #d5f4e6;
            border-left: 4px solid #27ae60;
            margin: 10px 0;
        }
        .warning {
            color: #f39c12;
            padding: 10px;
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
            padding: 10px;
            background: #fadbd8;
            border-left: 4px solid #e74c3c;
            margin: 10px 0;
        }
        .info {
            color: #3498db;
            padding: 10px;
            background: #ebf5fb;
            border-left: 4px solid #3498db;
            margin: 10px 0;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .solution {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 修復損壞的資料表</h1>

<?php
try {
    // 連接資料庫
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='success'>✅ 資料庫連接成功！</div>";
    
    $problem_table = 'schools_contacts_old';
    
    echo "<h2>問題診斷</h2>";
    echo "<div class='warning'>⚠️ 發現損壞的表：<strong>$problem_table</strong></div>";
    echo "<p>錯誤訊息顯示：</p>";
    echo "<ul>";
    echo "<li>無法開啟表空間檔案 <code>schools_contacts_old.ibd</code></li>";
    echo "<li>作業系統錯誤：71 和 203</li>";
    echo "<li>InnoDB 無法找到有效的表空間檔案</li>";
    echo "</ul>";
    
    // 檢查表是否存在
    echo "<h2>檢查表狀態</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE '$problem_table'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<div class='info'>ℹ️ 表 <code>$problem_table</code> 在資料庫中存在</div>";
        
        // 檢查表結構
        try {
            $stmt = $pdo->query("DESCRIBE `$problem_table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='info'>ℹ️ 表有 " . count($columns) . " 個欄位</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ 無法讀取表結構: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // 檢查檔案是否存在
        $data_dir = "C:\\xampp\\mysql\\data\\topics_good";
        $ibd_file = "$data_dir\\$problem_table.ibd";
        
        echo "<h2>檢查檔案</h2>";
        
        if (file_exists($ibd_file)) {
            $size = filesize($ibd_file);
            echo "<div class='info'>ℹ️ 找到檔案: <code>$ibd_file</code> (大小: " . number_format($size) . " bytes)</div>";
            
            if ($size == 0) {
                echo "<div class='error'>❌ 檔案大小為 0，檔案已損壞</div>";
            }
        } else {
            echo "<div class='error'>❌ 找不到檔案: <code>$ibd_file</code></div>";
        }
        
    } else {
        echo "<div class='info'>ℹ️ 表 <code>$problem_table</code> 在資料庫中不存在</div>";
        echo "<div class='success'>✅ 這表示表可能已經被刪除，但資料字典中仍有殘留記錄</div>";
    }
    
    // 提供解決方案
    echo "<h2>解決方案</h2>";
    
    if (!isset($_GET['execute'])) {
        echo "<div class='solution'>";
        echo "<h3>方案 1: 刪除損壞的表（推薦）</h3>";
        echo "<p>如果這個表是舊的備份表（從名稱 <code>_old</code> 可以看出），可以安全刪除：</p>";
        echo "<ol>";
        echo "<li>刪除表空間檔案</li>";
        echo "<li>從資料庫中移除表定義</li>";
        echo "<li>清理資料字典</li>";
        echo "</ol>";
        echo "<p><strong>注意：這會永久刪除表及其所有資料！</strong></p>";
        echo "</div>";
        
        echo "<div class='solution'>";
        echo "<h3>方案 2: 修復表</h3>";
        echo "<p>如果這個表包含重要資料，可以嘗試修復：</p>";
        echo "<ol>";
        echo "<li>刪除損壞的 .ibd 檔案</li>";
        echo "<li>使用 DISCARD TABLESPACE 和 IMPORT TABLESPACE</li>";
        echo "<li>或使用 REPAIR TABLE 命令</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?execute=delete' class='btn btn-danger'>🗑️ 刪除損壞的表</a> ";
        echo "<a href='?execute=repair' class='btn'>🔧 嘗試修復表</a>";
        echo "</p>";
        
    } else if ($_GET['execute'] === 'delete') {
        echo "<h2>執行刪除操作</h2>";
        
        try {
            // 先嘗試刪除表
            $pdo->exec("DROP TABLE IF EXISTS `$problem_table`");
            echo "<div class='success'>✅ 已從資料庫中刪除表定義</div>";
            
            // 刪除檔案（如果存在）
            $data_dir = "C:\\xampp\\mysql\\data\\topics_good";
            $ibd_file = "$data_dir\\$problem_table.ibd";
            $frm_file = "$data_dir\\$problem_table.frm";
            
            $deleted_files = [];
            if (file_exists($ibd_file)) {
                @unlink($ibd_file);
                $deleted_files[] = "ibd";
            }
            if (file_exists($frm_file)) {
                @unlink($frm_file);
                $deleted_files[] = "frm";
            }
            
            if (!empty($deleted_files)) {
                echo "<div class='success'>✅ 已刪除檔案: " . implode(", ", $deleted_files) . "</div>";
            }
            
            // 清理 InnoDB 資料字典（需要重啟 MySQL）
            echo "<div class='info'>ℹ️ 建議：重新啟動 MySQL 以完全清理資料字典</div>";
            
            echo "<div class='success'>✅ 刪除操作完成！</div>";
            echo "<p>請重新啟動 MySQL 服務，然後檢查是否還有錯誤。</p>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ 刪除失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>ℹ️ 這可能是因為表已經被標記為損壞。您需要手動刪除檔案。</div>";
        }
        
    } else if ($_GET['execute'] === 'repair') {
        echo "<h2>執行修復操作</h2>";
        
        try {
            // 先嘗試 DISCARD TABLESPACE
            echo "<p>步驟 1: 丟棄表空間...</p>";
            $pdo->exec("ALTER TABLE `$problem_table` DISCARD TABLESPACE");
            echo "<div class='success'>✅ 已丟棄表空間</div>";
            
            // 刪除損壞的 .ibd 檔案
            $data_dir = "C:\\xampp\\mysql\\data\\topics_good";
            $ibd_file = "$data_dir\\$problem_table.ibd";
            if (file_exists($ibd_file)) {
                @unlink($ibd_file);
                echo "<div class='success'>✅ 已刪除損壞的 .ibd 檔案</div>";
            }
            
            // 重新建立表空間
            echo "<p>步驟 2: 重新建立表空間...</p>";
            $pdo->exec("ALTER TABLE `$problem_table` IMPORT TABLESPACE");
            echo "<div class='success'>✅ 已重新建立表空間</div>";
            
            echo "<div class='success'>✅ 修復操作完成！</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ 修復失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>ℹ️ 如果修復失敗，建議刪除這個表（因為它是 _old 備份表）</div>";
        }
    }
    
    // 檢查是否還有其他問題
    echo "<h2>檢查其他問題</h2>";
    
    // 檢查 aria_log_control 問題
    $aria_log = "C:\\xampp\\mysql\\data\\aria_log_control";
    if (file_exists($aria_log)) {
        echo "<div class='info'>ℹ️ 找到 aria_log_control 檔案</div>";
        
        // 檢查檔案權限
        if (!is_readable($aria_log) || !is_writable($aria_log)) {
            echo "<div class='warning'>⚠️ aria_log_control 檔案可能有權限問題</div>";
        } else {
            echo "<div class='success'>✅ aria_log_control 檔案權限正常</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ 資料庫連接失敗: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>請確認 MySQL 服務正在運行。</p>";
}

echo "<p style='margin-top: 30px;'>";
echo "<a href='diagnose_mysql.php' class='btn'>🔍 返回診斷工具</a>";
echo "</p>";

?>

    </div>
</body>
</html>

