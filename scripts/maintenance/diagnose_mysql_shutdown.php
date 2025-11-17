<?php
/**
 * XAMPP MySQL 意外關閉診斷工具
 * 診斷 MySQL shutdown unexpectedly 的問題
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL 意外關閉診斷工具</title>
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
            color: #d32f2f;
            border-bottom: 3px solid #d32f2f;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-left: 4px solid #d32f2f;
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
            white-space: pre-wrap;
            word-wrap: break-word;
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
            background: #d32f2f;
            color: white;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MySQL 意外關閉診斷工具</h1>
        <p>此工具將診斷 MySQL shutdown unexpectedly 錯誤的原因。</p>

        <?php
        // 查找 XAMPP 路徑
        $xampp_paths = ['C:/xampp', 'D:/xampp', 'E:/xampp'];
        $xampp_path = null;
        
        foreach ($xampp_paths as $path) {
            if (file_exists($path . '/mysql/bin/mysqld.exe')) {
                $xampp_path = $path;
                break;
            }
        }
        
        if (!$xampp_path) {
            echo "<div class='status error'>❌ 未找到 XAMPP 安裝路徑</div>";
            echo "<p>請確認 XAMPP 已正確安裝在 C:\\xampp、D:\\xampp 或 E:\\xampp</p>";
        } else {
            echo "<div class='status success'>✅ 找到 XAMPP 安裝: $xampp_path</div>";
        }
        
        // 1. 檢查 MySQL 進程
        echo "<h2>1. 檢查 MySQL 進程狀態</h2>";
        
        $mysql_running = false;
        $processes = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul');
            if (strpos($output, 'mysqld.exe') !== false) {
                $mysql_running = true;
                echo "<div class='status warning'>⚠️ 發現 MySQL 進程正在運行</div>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                echo "<div class='status info'>ℹ️ 沒有發現 MySQL 進程（可能已關閉）</div>";
            }
        }
        
        // 2. 檢查端口占用
        echo "<h2>2. 檢查端口 3306 占用情況</h2>";
        
        $port_3306 = @fsockopen('localhost', 3306, $errno, $errstr, 2);
        if ($port_3306) {
            echo "<div class='status success'>✅ 端口 3306 正在監聽</div>";
            fclose($port_3306);
            
            // 嘗試連接 MySQL
            try {
                $pdo = new PDO("mysql:host=localhost;port=3306", 'root', '');
                echo "<div class='status success'>✅ MySQL 連接成功</div>";
            } catch (PDOException $e) {
                echo "<div class='status warning'>⚠️ 端口開放但無法連接: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='status error'>❌ 端口 3306 無法連接</div>";
            echo "<div class='status info'>錯誤: $errstr ($errno)</div>";
        }
        
        // 3. 檢查錯誤日誌
        echo "<h2>3. 檢查 MySQL 錯誤日誌</h2>";
        
        if ($xampp_path) {
            $log_path = $xampp_path . '/mysql/data';
            // 檢查 .err 和 .log 文件
            $error_logs = array_merge(
                glob($log_path . '/*.err'),
                glob($log_path . '/mysql_error.log'),
                glob($log_path . '/*error*.log')
            );
            
            if (!empty($error_logs)) {
                // 找到最新的錯誤日誌
                $latest_log = '';
                $latest_time = 0;
                foreach ($error_logs as $log) {
                    $time = filemtime($log);
                    if ($time > $latest_time) {
                        $latest_time = $time;
                        $latest_log = $log;
                    }
                }
                
                echo "<div class='status success'>✅ 找到錯誤日誌: " . basename($latest_log) . "</div>";
                echo "<div class='status info'>最後修改時間: " . date('Y-m-d H:i:s', $latest_time) . "</div>";
                
                // 讀取最後 50 行
                $log_content = file($latest_log);
                $last_lines = array_slice($log_content, -50);
                
                echo "<h3>最近的錯誤訊息（最後 50 行）：</h3>";
                echo "<pre>" . htmlspecialchars(implode('', $last_lines)) . "</pre>";
                
                // 分析常見錯誤
                $log_text = implode('', $last_lines);
                $issues = [];
                
                if (stripos($log_text, 'port') !== false && stripos($log_text, 'already in use') !== false) {
                    $issues[] = "端口被占用";
                }
                if (stripos($log_text, 'datadir') !== false || stripos($log_text, 'data directory') !== false) {
                    $issues[] = "數據目錄問題";
                }
                if (stripos($log_text, 'permission') !== false || stripos($log_text, 'access denied') !== false) {
                    $issues[] = "權限問題";
                }
                if (stripos($log_text, 'disk') !== false || stripos($log_text, 'space') !== false) {
                    $issues[] = "磁碟空間不足";
                }
                if (stripos($log_text, 'corrupt') !== false || stripos($log_text, 'corrupted') !== false) {
                    $issues[] = "數據文件損壞";
                }
                
                if (!empty($issues)) {
                    echo "<div class='status warning'>⚠️ 檢測到的可能問題：</div>";
                    echo "<ul>";
                    foreach ($issues as $issue) {
                        echo "<li>$issue</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<div class='status warning'>⚠️ 未找到錯誤日誌文件</div>";
                echo "<p>日誌應該位於: $log_path</p>";
            }
        }
        
        // 4. 檢查配置文件
        echo "<h2>4. 檢查 MySQL 配置文件</h2>";
        
        if ($xampp_path) {
            $config_file = $xampp_path . '/mysql/bin/my.ini';
            if (file_exists($config_file)) {
                echo "<div class='status success'>✅ 找到配置文件: my.ini</div>";
                
                $config_content = file_get_contents($config_file);
                
                // 檢查端口配置
                if (preg_match('/port\s*=\s*(\d+)/i', $config_content, $matches)) {
                    echo "<div class='status info'>端口配置: " . $matches[1] . "</div>";
                }
                
                // 檢查數據目錄配置
                if (preg_match('/datadir\s*=\s*["\']?([^"\'\r\n]+)/i', $config_content, $matches)) {
                    $datadir = trim($matches[1]);
                    echo "<div class='status info'>數據目錄: $datadir</div>";
                    
                    if (file_exists($datadir)) {
                        echo "<div class='status success'>✅ 數據目錄存在</div>";
                        
                        // 檢查目錄權限（Windows 上主要是檢查是否可讀寫）
                        if (is_readable($datadir) && is_writable($datadir)) {
                            echo "<div class='status success'>✅ 數據目錄可讀寫</div>";
                        } else {
                            echo "<div class='status error'>❌ 數據目錄權限問題</div>";
                        }
                    } else {
                        echo "<div class='status error'>❌ 數據目錄不存在</div>";
                    }
                }
            } else {
                echo "<div class='status warning'>⚠️ 未找到配置文件 my.ini</div>";
            }
        }
        
        // 5. 檢查磁碟空間
        echo "<h2>5. 檢查磁碟空間</h2>";
        
        if ($xampp_path) {
            $drive = substr($xampp_path, 0, 2);
            $free_space = disk_free_space($drive);
            $total_space = disk_total_space($drive);
            
            if ($free_space !== false) {
                $free_gb = round($free_space / 1024 / 1024 / 1024, 2);
                $total_gb = round($total_space / 1024 / 1024 / 1024, 2);
                $used_percent = round((($total_space - $free_space) / $total_space) * 100, 2);
                
                echo "<div class='status info'>磁碟: $drive</div>";
                echo "<div class='status info'>總空間: $total_gb GB</div>";
                echo "<div class='status info'>可用空間: $free_gb GB</div>";
                echo "<div class='status info'>使用率: $used_percent%</div>";
                
                if ($free_space < 1024 * 1024 * 1024) { // 少於 1GB
                    echo "<div class='status error'>❌ 磁碟空間不足！MySQL 需要至少 1GB 可用空間</div>";
                } else {
                    echo "<div class='status success'>✅ 磁碟空間充足</div>";
                }
            }
        }
        
        // 6. 檢查常見問題
        echo "<h2>6. 常見問題檢查</h2>";
        
        $common_issues = [];
        
        // 檢查是否有其他 MySQL 服務運行
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $services = shell_exec('sc query type= service state= all | findstr /i mysql 2>nul');
            if ($services && stripos($services, 'RUNNING') !== false) {
                $common_issues[] = "發現其他 MySQL 服務正在運行，可能與 XAMPP MySQL 衝突";
            }
        }
        
        if (empty($common_issues)) {
            echo "<div class='status success'>✅ 未發現常見問題</div>";
        } else {
            echo "<div class='status warning'>⚠️ 發現以下問題：</div>";
            echo "<ul>";
            foreach ($common_issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
        }
        
        // 7. 提供解決方案
        echo "<h2>7. 解決方案建議</h2>";
        
        echo "<div class='solution'>";
        echo "<h3>立即嘗試的修復步驟：</h3>";
        echo "<ol>";
        echo "<li><strong>完全停止並重新啟動 MySQL</strong><br>";
        echo "在 XAMPP Control Panel 中：<br>";
        echo "1. 點擊 MySQL 的 Stop 按鈕<br>";
        echo "2. 等待 5 秒<br>";
        echo "3. 點擊 MySQL 的 Start 按鈕<br>";
        echo "4. 查看 Logs 按鈕中的錯誤訊息</li>";
        
        echo "<li><strong>檢查並停止占用端口的程序</strong><br>";
        echo "打開命令提示字元（以管理員身份），執行：<br>";
        echo "<div class='code-block'>netstat -ano | findstr :3306</div>";
        echo "找到占用端口的 PID，然後執行：<br>";
        echo "<div class='code-block'>taskkill /F /PID [PID號碼]</div></li>";
        
        echo "<li><strong>使用修復腳本</strong><br>";
        echo "以管理員身份運行：<br>";
        echo "<div class='code-block'>scripts\\maintenance\\fix_mysql_shutdown.bat</div></li>";
        
        echo "<li><strong>檢查錯誤日誌</strong><br>";
        if ($xampp_path) {
            echo "查看詳細錯誤：<br>";
            echo "<div class='code-block'>$xampp_path\\mysql\\data\\*.err</div>";
        }
        echo "在 XAMPP Control Panel 中點擊 MySQL 的 Logs 按鈕</li>";
        
        echo "<li><strong>檢查數據目錄</strong><br>";
        echo "確認數據目錄沒有損壞，必要時備份並重建</li>";
        
        echo "<li><strong>檢查 Windows 事件檢視器</strong><br>";
        echo "按 Win + R，輸入 eventvwr.msc，查看應用程式和系統日誌中的 MySQL 相關錯誤</li>";
        
        echo "<li><strong>重新安裝 MySQL 服務（最後手段）</strong><br>";
        echo "如果以上方法都無效，可能需要重新安裝 XAMPP 或 MySQL 組件</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div class='solution'>";
        echo "<h3>根據錯誤日誌的具體修復：</h3>";
        echo "<ul>";
        echo "<li><strong>端口被占用</strong>：停止占用端口的程序或修改 MySQL 端口</li>";
        echo "<li><strong>數據目錄問題</strong>：檢查目錄權限，確保 MySQL 有讀寫權限</li>";
        echo "<li><strong>權限問題</strong>：以管理員身份運行 XAMPP Control Panel</li>";
        echo "<li><strong>磁碟空間不足</strong>：清理磁碟空間，至少保留 1GB</li>";
        echo "<li><strong>數據文件損壞</strong>：備份數據，嘗試修復或重建數據庫</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<hr>";
        echo "<p style='text-align: center; color: #666;'>診斷完成時間: " . date('Y-m-d H:i:s') . "</p>";
        ?>
    </div>
</body>
</html>

