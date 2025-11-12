<?php
/**
 * MySQL 狀態檢查和診斷腳本
 * 用於診斷 MySQL 無法啟動的問題
 */

echo "========================================\n";
echo "MySQL 狀態診斷工具\n";
echo "========================================\n\n";

// 檢查 MySQL 服務狀態（Windows）
function checkMySQLService() {
    echo "1. 檢查 MySQL 服務狀態...\n";
    
    // 嘗試連接 MySQL
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $port = 3306;
    
    $conn = @mysqli_connect($host, $username, $password, '', $port);
    
    if ($conn) {
        echo "   ✅ MySQL 服務正在運行\n";
        echo "   ✅ 可以成功連接到 MySQL\n";
        mysqli_close($conn);
        return true;
    } else {
        echo "   ❌ 無法連接到 MySQL\n";
        echo "   錯誤訊息: " . mysqli_connect_error() . "\n";
        return false;
    }
}

// 檢查端口是否被占用
function checkPort($port = 3306) {
    echo "\n2. 檢查端口 $port 是否被占用...\n";
    
    $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
    
    if ($connection) {
        echo "   ✅ 端口 $port 正在監聽（MySQL 可能正在運行）\n";
        fclose($connection);
        return true;
    } else {
        echo "   ⚠️  端口 $port 沒有響應\n";
        echo "   這可能表示 MySQL 服務沒有啟動\n";
        return false;
    }
}

// 檢查常見的 MySQL 配置
function checkMySQLConfig() {
    echo "\n3. 檢查 MySQL 配置...\n";
    
    $commonPaths = [
        'C:\\Program Files\\MySQL\\MySQL Server 8.0\\my.ini',
        'C:\\Program Files\\MySQL\\MySQL Server 5.7\\my.ini',
        'C:\\xampp\\mysql\\bin\\my.ini',
        'C:\\wamp64\\bin\\mysql\\mysql8.0.xx\\my.ini',
        'C:\\ProgramData\\MySQL\\MySQL Server 8.0\\my.ini',
        'C:\\ProgramData\\MySQL\\MySQL Server 5.7\\my.ini',
    ];
    
    $found = false;
    foreach ($commonPaths as $path) {
        if (file_exists($path)) {
            echo "   ✅ 找到 MySQL 配置文件: $path\n";
            $found = true;
            
            // 讀取配置文件檢查端口
            $content = file_get_contents($path);
            if (preg_match('/port\s*=\s*(\d+)/i', $content, $matches)) {
                echo "   ✅ 配置的端口: " . $matches[1] . "\n";
            }
            break;
        }
    }
    
    if (!$found) {
        echo "   ⚠️  未找到常見的 MySQL 配置文件\n";
        echo "   請手動檢查 MySQL 安裝路徑\n";
    }
}

// 提供修復建議
function provideSolutions() {
    echo "\n========================================\n";
    echo "修復建議\n";
    echo "========================================\n\n";
    
    echo "如果 MySQL 無法啟動，請嘗試以下方法：\n\n";
    
    echo "方法 1: 使用服務管理器啟動 MySQL\n";
    echo "   1. 按 Win + R，輸入 services.msc\n";
    echo "   2. 找到 MySQL 服務（可能是 'MySQL' 或 'MySQL80'）\n";
    echo "   3. 右鍵點擊，選擇「啟動」\n\n";
    
    echo "方法 2: 使用命令提示字元（以管理員身份運行）\n";
    echo "   啟動服務: net start MySQL\n";
    echo "   或: net start MySQL80\n";
    echo "   停止服務: net stop MySQL\n";
    echo "   或: net stop MySQL80\n\n";
    
    echo "方法 3: 檢查 MySQL 錯誤日誌\n";
    echo "   常見日誌位置：\n";
    echo "   - C:\\ProgramData\\MySQL\\MySQL Server 8.0\\Data\\*.err\n";
    echo "   - C:\\xampp\\mysql\\data\\*.err\n";
    echo "   - C:\\wamp64\\logs\\mysql*.log\n\n";
    
    echo "方法 4: 檢查端口衝突\n";
    echo "   1. 打開命令提示字元\n";
    echo "   2. 執行: netstat -ano | findstr :3306\n";
    echo "   3. 如果端口被占用，檢查是哪個程序\n\n";
    
    echo "方法 5: 重新安裝 MySQL 服務\n";
    echo "   1. 以管理員身份打開命令提示字元\n";
    echo "   2. 切換到 MySQL bin 目錄\n";
    echo "   3. 執行: mysqld --remove\n";
    echo "   4. 執行: mysqld --install\n";
    echo "   5. 執行: net start MySQL\n\n";
    
    echo "方法 6: 檢查磁碟空間\n";
    echo "   MySQL 需要足夠的磁碟空間才能啟動\n\n";
    
    echo "方法 7: 檢查防火牆設定\n";
    echo "   確保 Windows 防火牆允許 MySQL 通過\n\n";
}

// 執行診斷
echo "開始診斷...\n\n";

$mysqlRunning = checkMySQLService();
checkPort(3306);
checkMySQLConfig();

if (!$mysqlRunning) {
    provideSolutions();
} else {
    echo "\n✅ MySQL 運行正常！\n";
}

echo "\n========================================\n";
echo "診斷完成\n";
echo "========================================\n";


