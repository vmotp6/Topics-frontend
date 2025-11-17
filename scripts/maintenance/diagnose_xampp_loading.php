<?php
/**
 * XAMPP 載入問題診斷工具
 * 診斷為什麼 Apache 和 MySQL 顯示運行但頁面一直在載入
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP 載入問題診斷</title>
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
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-left: 4px solid #4CAF50;
            padding-left: 10px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
        .solution {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #2196F3;
        }
        .solution h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .solution ol {
            line-height: 2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #4CAF50;
            color: white;
        }
        table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 XAMPP 載入問題診斷工具</h1>
        <p>此工具將檢查 Apache 和 MySQL 的實際運行狀態，找出頁面一直載入的原因。</p>

        <?php
        echo "<h2>1. 檢查 Apache 服務狀態</h2>";
        
        // 檢查 Apache 端口（80, 443, 或其他）
        $apache_ports = [80, 443, 8080];
        $apache_running = false;
        $apache_port = null;
        
        foreach ($apache_ports as $port) {
            $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
            if ($connection) {
                $apache_running = true;
                $apache_port = $port;
                fclose($connection);
                break;
            }
        }
        
        if ($apache_running) {
            echo "<div class='status success'>✅ Apache 正在端口 $apache_port 上運行</div>";
        } else {
            echo "<div class='status error'>❌ Apache 無法在常見端口（80, 443, 8080）上連接</div>";
            echo "<div class='status warning'>⚠️ 這可能是頁面無法載入的主要原因！</div>";
        }
        
        // 檢查 HTTP 響應
        echo "<h2>2. 檢查 HTTP 響應</h2>";
        $test_url = "http://localhost";
        $ch = curl_init($test_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $response = @curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code > 0) {
            echo "<div class='status success'>✅ HTTP 響應正常，狀態碼: $http_code</div>";
        } else {
            echo "<div class='status error'>❌ 無法連接到 localhost</div>";
            if ($curl_error) {
                echo "<div class='status warning'>錯誤訊息: $curl_error</div>";
            }
        }
        
        echo "<h2>3. 檢查 MySQL 服務狀態</h2>";
        
        // 檢查 MySQL 連接
        $mysql_host = 'localhost';
        $mysql_port = 3306;
        $mysql_username = 'root';
        $mysql_password = '';
        
        $mysql_connection = @fsockopen($mysql_host, $mysql_port, $errno, $errstr, 2);
        if ($mysql_connection) {
            echo "<div class='status success'>✅ MySQL 端口 $mysql_port 正在監聽</div>";
            fclose($mysql_connection);
            
            // 嘗試實際連接
            try {
                $pdo = new PDO("mysql:host=$mysql_host;port=$mysql_port", $mysql_username, $mysql_password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='status success'>✅ MySQL 連接成功</div>";
                
                // 檢查資料庫
                $stmt = $pdo->query("SHOW DATABASES");
                $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<div class='status info'>📊 找到 " . count($databases) . " 個資料庫</div>";
                
            } catch (PDOException $e) {
                echo "<div class='status error'>❌ MySQL 連接失敗: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='status error'>❌ MySQL 端口 $mysql_port 無法連接</div>";
            echo "<div class='status warning'>錯誤: $errstr ($errno)</div>";
        }
        
        echo "<h2>4. 檢查端口占用情況</h2>";
        
        // 檢查常見端口
        $ports_to_check = [
            ['name' => 'HTTP (Apache)', 'port' => 80],
            ['name' => 'HTTPS (Apache)', 'port' => 443],
            ['name' => 'MySQL', 'port' => 3306],
            ['name' => 'Apache 替代端口', 'port' => 8080],
        ];
        
        echo "<table>";
        echo "<tr><th>服務</th><th>端口</th><th>狀態</th></tr>";
        
        foreach ($ports_to_check as $port_info) {
            $port = $port_info['port'];
            $name = $port_info['name'];
            $conn = @fsockopen('localhost', $port, $errno, $errstr, 1);
            
            if ($conn) {
                echo "<tr><td>$name</td><td>$port</td><td><span style='color:green'>✅ 正在使用</span></td></tr>";
                fclose($conn);
            } else {
                echo "<tr><td>$name</td><td>$port</td><td><span style='color:red'>❌ 未使用</span></td></tr>";
            }
        }
        
        echo "</table>";
        
        echo "<h2>5. 檢查 phpMyAdmin 配置</h2>";
        
        // 檢查 phpMyAdmin 是否存在
        $phpmyadmin_paths = [
            'C:/xampp/phpMyAdmin',
            'C:/xampp/htdocs/phpmyadmin',
            $_SERVER['DOCUMENT_ROOT'] . '/phpmyadmin',
            $_SERVER['DOCUMENT_ROOT'] . '/phpMyAdmin',
        ];
        
        $phpmyadmin_found = false;
        foreach ($phpmyadmin_paths as $path) {
            if (file_exists($path)) {
                echo "<div class='status success'>✅ 找到 phpMyAdmin: $path</div>";
                $phpmyadmin_found = true;
                
                // 檢查 config.inc.php
                $config_file = $path . '/config.inc.php';
                if (file_exists($config_file)) {
                    echo "<div class='status success'>✅ 找到配置文件: config.inc.php</div>";
                } else {
                    echo "<div class='status warning'>⚠️ 未找到 config.inc.php（可能使用 config.sample.inc.php）</div>";
                }
                break;
            }
        }
        
        if (!$phpmyadmin_found) {
            echo "<div class='status warning'>⚠️ 未找到 phpMyAdmin 安裝路徑</div>";
        }
        
        echo "<h2>6. 檢查系統資源</h2>";
        
        // 檢查記憶體
        if (function_exists('memory_get_usage')) {
            $memory_usage = memory_get_usage(true);
            $memory_limit = ini_get('memory_limit');
            echo "<div class='status info'>💾 PHP 記憶體使用: " . round($memory_usage / 1024 / 1024, 2) . " MB</div>";
            echo "<div class='status info'>💾 PHP 記憶體限制: $memory_limit</div>";
        }
        
        // 檢查執行時間
        $max_execution_time = ini_get('max_execution_time');
        echo "<div class='status info'>⏱️ PHP 最大執行時間: $max_execution_time 秒</div>";
        
        echo "<h2>7. 診斷結果與解決方案</h2>";
        
        $issues = [];
        $solutions = [];
        
        if (!$apache_running) {
            $issues[] = "Apache 無法在標準端口上連接";
            $solutions[] = [
                "title" => "Apache 無法連接",
                "steps" => [
                    "檢查 XAMPP Control Panel 中 Apache 的日誌（點擊 Logs 按鈕）",
                    "確認端口 80 沒有被其他程序占用（如 IIS、Skype 等）",
                    "嘗試修改 Apache 端口：編輯 httpd.conf，將 Listen 80 改為 Listen 8080",
                    "以管理員身份重新啟動 Apache",
                    "檢查 Windows 防火牆是否阻止了 Apache"
                ]
            ];
        }
        
        if ($http_code == 0 || !$response) {
            $issues[] = "無法獲取 HTTP 響應";
            $solutions[] = [
                "title" => "HTTP 無響應",
                "steps" => [
                    "確認 Apache 確實已啟動（不只是顯示運行）",
                    "檢查 Apache 錯誤日誌：C:\\xampp\\apache\\logs\\error.log",
                    "嘗試訪問 http://localhost:8080（如果修改了端口）",
                    "清除瀏覽器快取並重新載入",
                    "嘗試使用其他瀏覽器或無痕模式"
                ]
            ];
        }
        
        if (!$mysql_connection) {
            $issues[] = "MySQL 無法連接";
            $solutions[] = [
                "title" => "MySQL 連接問題",
                "steps" => [
                    "檢查 XAMPP Control Panel 中 MySQL 的日誌",
                    "確認端口 3306 沒有被其他 MySQL 實例占用",
                    "檢查 MySQL 錯誤日誌：C:\\xampp\\mysql\\data\\*.err",
                    "嘗試手動啟動 MySQL 服務：net start MySQL（以管理員身份）",
                    "如果問題持續，可能需要重新安裝 MySQL 服務"
                ]
            ];
        }
        
        if (empty($issues)) {
            echo "<div class='status success'>✅ 所有基本檢查都通過了！</div>";
            echo "<div class='solution'>";
            echo "<h3>如果頁面仍在載入，請嘗試：</h3>";
            echo "<ol>";
            echo "<li><strong>清除瀏覽器快取</strong>：按 Ctrl+Shift+Delete，清除快取和 Cookie</li>";
            echo "<li><strong>檢查瀏覽器控制台</strong>：按 F12，查看 Console 和 Network 標籤頁的錯誤訊息</li>";
            echo "<li><strong>嘗試直接訪問</strong>：http://localhost/phpmyadmin/ 或 http://localhost:8080/phpmyadmin/</li>";
            echo "<li><strong>檢查 phpMyAdmin 配置</strong>：確認 config.inc.php 中的 MySQL 連接設定正確</li>";
            echo "<li><strong>查看 Apache 錯誤日誌</strong>：C:\\xampp\\apache\\logs\\error.log</li>";
            echo "<li><strong>重新啟動服務</strong>：在 XAMPP Control Panel 中停止並重新啟動 Apache 和 MySQL</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='status error'>❌ 發現 " . count($issues) . " 個問題：</div>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
            
            foreach ($solutions as $solution) {
                echo "<div class='solution'>";
                echo "<h3>解決方案：" . $solution['title'] . "</h3>";
                echo "<ol>";
                foreach ($solution['steps'] as $step) {
                    echo "<li>$step</li>";
                }
                echo "</ol>";
                echo "</div>";
            }
        }
        
        echo "<h2>8. 快速修復建議</h2>";
        echo "<div class='solution'>";
        echo "<h3>立即嘗試的步驟：</h3>";
        echo "<ol>";
        echo "<li><strong>完全重新啟動服務</strong>：<br>";
        echo "在 XAMPP Control Panel 中：<br>";
        echo "1. 點擊 Apache 的 Stop 按鈕<br>";
        echo "2. 點擊 MySQL 的 Stop 按鈕<br>";
        echo "3. 等待 5 秒<br>";
        echo "4. 點擊 Apache 的 Start 按鈕<br>";
        echo "5. 點擊 MySQL 的 Start 按鈕<br>";
        echo "6. 等待服務完全啟動（查看日誌確認）</li>";
        echo "<li><strong>檢查端口衝突</strong>：<br>";
        echo "打開命令提示字元（以管理員身份），執行：<br>";
        echo "<pre>netstat -ano | findstr :80\nnetstat -ano | findstr :3306</pre>";
        echo "如果看到其他程序占用，需要停止它們或修改 XAMPP 端口</li>";
        echo "<li><strong>查看詳細日誌</strong>：<br>";
        echo "在 XAMPP Control Panel 中點擊 Apache 和 MySQL 的 Logs 按鈕，查看具體錯誤訊息</li>";
        echo "<li><strong>檢查防火牆</strong>：<br>";
        echo "確保 Windows 防火牆允許 Apache 和 MySQL 通過</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<hr>";
        echo "<p style='text-align: center; color: #666;'>診斷完成時間: " . date('Y-m-d H:i:s') . "</p>";
        ?>
    </div>
</body>
</html>

