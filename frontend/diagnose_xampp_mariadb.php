<?php
/**
 * XAMPP MariaDB 診斷工具
 * 專門用於診斷 XAMPP 中 MariaDB 無法啟動的問題
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP MariaDB 診斷工具</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #007bff;
        }
        .command {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .solution-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <h1>🔍 XAMPP MariaDB 診斷工具</h1>
    
    <?php
    $xampp_path = 'C:\\xampp';
    $mysql_path = $xampp_path . '\\mysql';
    $data_path = $mysql_path . '\\data';
    $bin_path = $mysql_path . '\\bin';
    $error_log_path = $data_path . '\\*.err';
    
    // 1. 檢查 XAMPP 路徑
    echo "<div class='section'>";
    echo "<h2>1. 檢查 XAMPP 安裝路徑</h2>";
    
    if (is_dir($xampp_path)) {
        echo "<p class='success'>✅ 找到 XAMPP 安裝目錄: $xampp_path</p>";
        
        if (is_dir($mysql_path)) {
            echo "<p class='success'>✅ 找到 MySQL/MariaDB 目錄: $mysql_path</p>";
        } else {
            echo "<p class='error'>❌ 未找到 MySQL/MariaDB 目錄: $mysql_path</p>";
        }
        
        if (is_dir($data_path)) {
            echo "<p class='success'>✅ 找到數據目錄: $data_path</p>";
        } else {
            echo "<p class='error'>❌ 未找到數據目錄: $data_path</p>";
        }
        
        if (is_dir($bin_path)) {
            echo "<p class='success'>✅ 找到 bin 目錄: $bin_path</p>";
        } else {
            echo "<p class='error'>❌ 未找到 bin 目錄: $bin_path</p>";
        }
    } else {
        echo "<p class='error'>❌ 未找到 XAMPP 安裝目錄: $xampp_path</p>";
        echo "<p class='info'>請確認您的 XAMPP 安裝路徑是否正確</p>";
    }
    echo "</div>";
    
    // 2. 檢查端口占用
    echo "<div class='section'>";
    echo "<h2>2. 檢查端口 3306 占用情況</h2>";
    
    $port_check = @fsockopen('localhost', 3306, $errno, $errstr, 2);
    if ($port_check) {
        echo "<p class='warning'>⚠️ 端口 3306 正在被使用</p>";
        echo "<p class='info'>這可能表示：</p>";
        echo "<ul>";
        echo "<li>MariaDB 已經在運行（這是好事！）</li>";
        echo "<li>其他程序占用了端口 3306</li>";
        echo "</ul>";
        fclose($port_check);
        
        // 檢查是否可以連接
        $conn = @mysqli_connect('localhost', 'root', '', '', 3306);
        if ($conn) {
            echo "<p class='success'>✅ 可以成功連接到 MariaDB！服務正在運行。</p>";
            mysqli_close($conn);
        } else {
            echo "<p class='error'>❌ 端口被占用但無法連接，可能是其他程序占用了端口</p>";
            echo "<p class='info'>請執行以下命令查看占用端口的程序：</p>";
            echo "<div class='command'>netstat -ano | findstr :3306</div>";
        }
    } else {
        echo "<p class='error'>❌ 端口 3306 沒有響應</p>";
        echo "<p class='info'>這表示 MariaDB 服務沒有運行</p>";
    }
    echo "</div>";
    
    // 3. 檢查錯誤日誌
    echo "<div class='section'>";
    echo "<h2>3. 檢查錯誤日誌</h2>";
    
    $error_logs = glob($data_path . '\\*.err');
    if (!empty($error_logs)) {
        echo "<p class='success'>✅ 找到錯誤日誌文件：</p>";
        foreach ($error_logs as $log_file) {
            echo "<p class='info'>📄 " . basename($log_file) . "</p>";
            
            // 讀取最後 20 行
            $lines = file($log_file);
            $last_lines = array_slice($lines, -20);
            
            echo "<details>";
            echo "<summary style='cursor: pointer; color: #007bff;'>查看最後 20 行日誌</summary>";
            echo "<pre>";
            echo htmlspecialchars(implode('', $last_lines));
            echo "</pre>";
            echo "</details>";
        }
    } else {
        echo "<p class='warning'>⚠️ 未找到錯誤日誌文件</p>";
        echo "<p class='info'>日誌文件應該在: $data_path\\*.err</p>";
    }
    echo "</div>";
    
    // 4. 檢查配置文件
    echo "<div class='section'>";
    echo "<h2>4. 檢查配置文件</h2>";
    
    $config_files = [
        $mysql_path . '\\bin\\my.ini',
        $mysql_path . '\\bin\\my.cnf',
        $xampp_path . '\\mysql\\my.ini',
    ];
    
    $config_found = false;
    foreach ($config_files as $config_file) {
        if (file_exists($config_file)) {
            $config_found = true;
            echo "<p class='success'>✅ 找到配置文件: $config_file</p>";
            
            $content = file_get_contents($config_file);
            
            // 檢查端口配置
            if (preg_match('/port\s*=\s*(\d+)/i', $content, $matches)) {
                echo "<p class='info'>📌 配置的端口: " . $matches[1] . "</p>";
            }
            
            // 檢查數據目錄配置
            if (preg_match('/datadir\s*=\s*["\']?([^"\'\n\r]+)/i', $content, $matches)) {
                echo "<p class='info'>📌 配置的數據目錄: " . trim($matches[1]) . "</p>";
            }
            
            break;
        }
    }
    
    if (!$config_found) {
        echo "<p class='warning'>⚠️ 未找到配置文件</p>";
        echo "<p class='info'>常見位置：</p>";
        echo "<ul>";
        foreach ($config_files as $config_file) {
            echo "<li>$config_file</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // 5. 檢查服務狀態
    echo "<div class='section'>";
    echo "<h2>5. 檢查 Windows 服務狀態</h2>";
    
    echo "<p class='info'>請手動檢查服務狀態：</p>";
    echo "<div class='solution-box'>";
    echo "<p><strong>步驟：</strong></p>";
    echo "<ol>";
    echo "<li>按 <kbd>Win + R</kbd>，輸入 <code>services.msc</code>，按 Enter</li>";
    echo "<li>找到以下服務之一：</li>";
    echo "<ul>";
    echo "<li><code>MySQL</code></li>";
    echo "<li><code>MySQL80</code></li>";
    echo "<li><code>MariaDB</code></li>";
    echo "<li><code>XAMPP MySQL</code></li>";
    echo "</ul>";
    echo "<li>查看服務狀態</li>";
    echo "<li>如果服務未運行，右鍵點擊選擇「啟動」</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    // 6. 提供解決方案
    echo "<div class='section'>";
    echo "<h2>6. 解決方案</h2>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 1: 通過 XAMPP 控制面板啟動</h3>";
    echo "<ol>";
    echo "<li>打開 XAMPP 控制面板</li>";
    echo "<li>找到 MySQL/MariaDB</li>";
    echo "<li>點擊「Start」按鈕</li>";
    echo "<li>如果啟動失敗，查看錯誤訊息</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 2: 通過命令提示字元啟動（管理員權限）</h3>";
    echo "<p>以管理員身份打開命令提示字元，執行：</p>";
    echo "<div class='command'>";
    echo "cd C:\\xampp\\mysql\\bin<br>";
    echo "mysqld --console<br>";
    echo "</div>";
    echo "<p class='info'>這會在前台運行 MySQL，您可以看到詳細的啟動訊息和錯誤</p>";
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 3: 檢查並修復端口衝突</h3>";
    echo "<p>如果端口 3306 被其他程序占用：</p>";
    echo "<div class='command'>";
    echo "# 查看占用端口的程序<br>";
    echo "netstat -ano | findstr :3306<br><br>";
    echo "# 查看程序詳情（將 PID 替換為上面顯示的數字）<br>";
    echo "tasklist | findstr [PID]<br><br>";
    echo "# 如果需要，可以終止程序（將 PID 替換為實際數字）<br>";
    echo "taskkill /PID [PID] /F<br>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 4: 檢查數據目錄權限</h3>";
    echo "<p>確保 XAMPP 服務有權限訪問數據目錄：</p>";
    echo "<ol>";
    echo "<li>右鍵點擊 <code>$data_path</code></li>";
    echo "<li>選擇「屬性」→「安全性」</li>";
    echo "<li>確保「SYSTEM」和「Administrators」有完全控制權限</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 5: 檢查磁碟空間</h3>";
    echo "<p>確保 C: 磁碟有足夠空間（至少 1GB 可用空間）</p>";
    $free_space = disk_free_space('C:');
    $free_space_gb = round($free_space / (1024 * 1024 * 1024), 2);
    echo "<p class='info'>當前 C: 磁碟可用空間: <strong>$free_space_gb GB</strong></p>";
    if ($free_space < 1024 * 1024 * 1024) {
        echo "<p class='error'>⚠️ 可用空間不足 1GB，可能影響 MySQL 啟動</p>";
    }
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 6: 重新安裝 MariaDB 服務（最後手段）</h3>";
    echo "<p class='warning'><strong>⚠️ 警告：這會移除現有的服務配置，但不會刪除數據</strong></p>";
    echo "<p>以管理員身份打開命令提示字元，執行：</p>";
    echo "<div class='command'>";
    echo "cd C:\\xampp\\mysql\\bin<br>";
    echo "# 移除服務<br>";
    echo "mysqld --remove MySQL<br><br>";
    echo "# 重新安裝服務<br>";
    echo "mysqld --install MySQL --defaults-file=\"C:\\xampp\\mysql\\bin\\my.ini\"<br><br>";
    echo "# 啟動服務<br>";
    echo "net start MySQL<br>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='solution-box'>";
    echo "<h3>方案 7: 檢查防火牆</h3>";
    echo "<p>確保 Windows 防火牆允許 MySQL 通過：</p>";
    echo "<ol>";
    echo "<li>打開「Windows Defender 防火牆」</li>";
    echo "<li>點擊「允許應用程式通過防火牆」</li>";
    echo "<li>找到 MySQL 或 MariaDB，確保已勾選</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    // 7. 快速修復命令
    echo "<div class='section'>";
    echo "<h2>7. 快速修復命令（複製到命令提示字元執行）</h2>";
    echo "<div class='command'>";
    echo "# 停止可能運行的 MySQL 進程<br>";
    echo "taskkill /F /IM mysqld.exe<br><br>";
    echo "# 檢查端口占用<br>";
    echo "netstat -ano | findstr :3306<br><br>";
    echo "# 通過 XAMPP 啟動（如果已安裝服務）<br>";
    echo "net start MySQL<br>";
    echo "</div>";
    echo "</div>";
    ?>
    
    <div class="section">
        <h2>📝 診斷完成</h2>
        <p class="info">如果以上方法都無法解決問題，請：</p>
        <ol>
            <li>查看完整的錯誤日誌（在數據目錄中的 .err 文件）</li>
            <li>記錄所有錯誤訊息</li>
            <li>檢查 Windows 事件檢視器（<code>eventvwr.msc</code>）中的應用程式日誌</li>
            <li>考慮重新安裝 XAMPP（<strong>記得先備份數據！</strong>）</li>
        </ol>
    </div>
</body>
</html>










