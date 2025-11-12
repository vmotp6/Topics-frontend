<?php
/**
 * 檢查 XAMPP 路徑和 MySQL 數據目錄
 */

echo "<h1>XAMPP 路徑檢查</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .path { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
    .success { color: green; }
    .warning { color: orange; }
    .error { color: red; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

// 可能的 XAMPP 安裝路徑
$possiblePaths = [
    'C:\\xampp\\',
    'C:\\Program Files\\xampp\\',
    'D:\\xampp\\',
    'E:\\xampp\\'
];

echo "<h2>1. 檢查 XAMPP 安裝路徑</h2>";
$foundPaths = [];

foreach ($possiblePaths as $path) {
    if (is_dir($path)) {
        $foundPaths[] = $path;
        echo "<div class='path success'>✅ 找到: <strong>$path</strong></div>";
    }
}

if (empty($foundPaths)) {
    echo "<div class='path error'>❌ 未找到常見的 XAMPP 安裝路徑</div>";
}

// 檢查 MySQL 數據目錄
echo "<h2>2. 檢查 MySQL 數據目錄</h2>";

foreach ($foundPaths as $xamppPath) {
    $mysqlDataPath = $xamppPath . 'mysql\\data\\';
    
    if (is_dir($mysqlDataPath)) {
        echo "<div class='path success'>✅ MySQL 數據目錄: <strong>$mysqlDataPath</strong></div>";
        
        // 列出數據庫
        echo "<h3>數據庫列表：</h3>";
        $databases = [];
        
        if ($handle = opendir($mysqlDataPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && is_dir($mysqlDataPath . $entry)) {
                    // 排除系統文件夾
                    if (!in_array($entry, ['performance_schema', 'mysql', 'information_schema', 'sys'])) {
                        $databases[] = $entry;
                    }
                }
            }
            closedir($handle);
        }
        
        if (!empty($databases)) {
            echo "<table>";
            echo "<tr><th>資料庫名稱</th><th>路徑</th><th>狀態</th></tr>";
            foreach ($databases as $db) {
                $dbPath = $mysqlDataPath . $db;
                $exists = is_dir($dbPath) ? '✅ 存在' : '❌ 不存在';
                echo "<tr>";
                echo "<td><strong>$db</strong></td>";
                echo "<td>$dbPath</td>";
                echo "<td>$exists</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // 特別檢查 topics_good 資料庫
        $topicsGoodPath = $mysqlDataPath . 'topics_good\\';
        if (is_dir($topicsGoodPath)) {
            echo "<h3>📊 topics_good 資料庫詳細信息：</h3>";
            echo "<div class='path'>路徑: <strong>$topicsGoodPath</strong></div>";
            
            // 列出表文件
            $tables = [];
            if ($handle = opendir($topicsGoodPath)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != ".." && 
                        (pathinfo($entry, PATHINFO_EXTENSION) == 'frm' || 
                         pathinfo($entry, PATHINFO_EXTENSION) == 'ibd')) {
                        $tableName = pathinfo($entry, PATHINFO_FILENAME);
                        if (!isset($tables[$tableName])) {
                            $tables[$tableName] = [];
                        }
                        $tables[$tableName][] = $entry;
                    }
                }
                closedir($handle);
            }
            
            if (!empty($tables)) {
                echo "<p>找到 " . count($tables) . " 個資料表：</p>";
                echo "<table>";
                echo "<tr><th>表名</th><th>文件數</th></tr>";
                foreach ($tables as $tableName => $files) {
                    echo "<tr><td>$tableName</td><td>" . count($files) . "</td></tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<div class='path warning'>⚠️ topics_good 資料庫目錄不存在</div>";
        }
        
        break; // 只處理第一個找到的路徑
    }
}

// 檢查 PHP 配置中的 MySQL 數據目錄
echo "<h2>3. 從 MySQL 配置檢查數據目錄</h2>";

// 嘗試連接 MySQL 並查詢數據目錄
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查詢數據目錄
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'datadir'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $datadir = $result['Value'];
        // 將正斜杠轉換為反斜杠（Windows）
        $datadir = str_replace('/', '\\', $datadir);
        
        echo "<div class='path success'>✅ MySQL 實際數據目錄: <strong>$datadir</strong></div>";
        
        // 檢查 topics_good
        $topicsGoodPath = $datadir . 'topics_good\\';
        if (is_dir($topicsGoodPath)) {
            echo "<div class='path success'>✅ topics_good 資料庫路徑: <strong>$topicsGoodPath</strong></div>";
        } else {
            echo "<div class='path warning'>⚠️ topics_good 資料庫目錄不存在於此路徑</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='path error'>❌ 無法連接 MySQL: " . $e->getMessage() . "</div>";
}

// 提供總結
echo "<h2>4. 總結</h2>";
echo "<div class='path'>";
echo "<p><strong>最常用的路徑：</strong></p>";
echo "<ul>";
echo "<li>XAMPP 安裝目錄: <code>C:\\xampp\\</code></li>";
echo "<li>MySQL 數據目錄: <code>C:\\xampp\\mysql\\data\\</code></li>";
echo "<li>topics_good 資料庫: <code>C:\\xampp\\mysql\\data\\topics_good\\</code></li>";
echo "</ul>";
echo "</div>";

?>

