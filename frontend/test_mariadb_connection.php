<?php
/**
 * 測試 MariaDB 連接
 * 用於驗證 MariaDB 是否正常運行
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MariaDB 連接測試</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .result-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 5px;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
        }
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
            padding: 20px;
            border-radius: 5px;
        }
        h1 { color: #333; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .detail {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <h1>🔌 MariaDB 連接測試</h1>
    
    <?php
    // 測試連接
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $port = 3306;
    
    echo "<div class='result-box'>";
    echo "<h2>測試連接參數</h2>";
    echo "<p><strong>主機:</strong> $host</p>";
    echo "<p><strong>端口:</strong> $port</p>";
    echo "<p><strong>用戶名:</strong> $username</p>";
    echo "<p><strong>密碼:</strong> " . (empty($password) ? "(空)" : "***") . "</p>";
    echo "</div>";
    
    // 方法 1: 使用 mysqli
    echo "<div class='result-box'>";
    echo "<h2>方法 1: 使用 mysqli 連接</h2>";
    
    $conn = @mysqli_connect($host, $username, $password, '', $port);
    
    if ($conn) {
        echo "<div class='success'>";
        echo "<h3>✅ 連接成功！</h3>";
        echo "<p>MariaDB 服務正在正常運行</p>";
        
        // 獲取版本信息
        $version = mysqli_get_server_info($conn);
        echo "<div class='detail'>";
        echo "<p><strong>MariaDB 版本:</strong> $version</p>";
        
        // 獲取當前時間
        $result = mysqli_query($conn, "SELECT NOW() as current_time, VERSION() as version");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<p><strong>伺服器時間:</strong> " . $row['current_time'] . "</p>";
            echo "<p><strong>完整版本:</strong> " . $row['version'] . "</p>";
        }
        
        // 列出資料庫
        $result = mysqli_query($conn, "SHOW DATABASES");
        if ($result) {
            echo "<p><strong>可用資料庫:</strong></p>";
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<li>" . htmlspecialchars($row['Database']) . "</li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
        echo "</div>";
        
        mysqli_close($conn);
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ 連接失敗</h3>";
        echo "<p>錯誤訊息: " . mysqli_connect_error() . "</p>";
        echo "<p>錯誤代碼: " . mysqli_connect_errno() . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // 方法 2: 使用 PDO
    echo "<div class='result-box'>";
    echo "<h2>方法 2: 使用 PDO 連接</h2>";
    
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='success'>";
        echo "<h3>✅ PDO 連接成功！</h3>";
        
        // 獲取版本
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p><strong>MariaDB 版本:</strong> $version</p>";
        
        // 獲取資料庫列表
        $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>可用資料庫數量:</strong> " . count($databases) . "</p>";
        
        echo "</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<h3>❌ PDO 連接失敗</h3>";
        echo "<p>錯誤訊息: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // 方法 3: 檢查端口
    echo "<div class='result-box'>";
    echo "<h2>方法 3: 檢查端口 3306</h2>";
    
    $socket = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($socket) {
        echo "<div class='success'>";
        echo "<h3>✅ 端口 3306 正在監聽</h3>";
        echo "<p>這表示 MariaDB 服務正在運行</p>";
        fclose($socket);
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ 端口 3306 沒有響應</h3>";
        echo "<p>錯誤: $errstr (代碼: $errno)</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // 總結
    echo "<div class='result-box'>";
    echo "<h2>📊 測試總結</h2>";
    
    $all_success = isset($conn) && $conn && isset($pdo) && $socket;
    
    if ($all_success) {
        echo "<div class='success'>";
        echo "<h3>✅ MariaDB 運行正常！</h3>";
        echo "<p>所有測試都通過，您的 MariaDB 服務正在正常運行。</p>";
        echo "<p>如果 XAMPP 控制面板顯示未運行，可能是顯示問題，實際服務已經啟動。</p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<h3>⚠️ 部分測試失敗</h3>";
        echo "<p>請檢查：</p>";
        echo "<ul>";
        echo "<li>MariaDB 服務是否真的在運行</li>";
        echo "<li>端口 3306 是否被其他程序占用</li>";
        echo "<li>用戶名和密碼是否正確</li>";
        echo "</ul>";
        echo "</div>";
    }
    echo "</div>";
    
    // 提供下一步建議
    echo "<div class='result-box'>";
    echo "<h2>💡 下一步建議</h2>";
    echo "<div class='info'>";
    echo "<p><strong>如果連接成功：</strong></p>";
    echo "<ul>";
    echo "<li>✅ 您的 MariaDB 已經正常運行</li>";
    echo "<li>✅ 可以正常使用資料庫功能</li>";
    echo "<li>✅ 如果 XAMPP 控制面板顯示異常，可以忽略（服務實際已啟動）</li>";
    echo "</ul>";
    echo "<p><strong>如果連接失敗：</strong></p>";
    echo "<ul>";
    echo "<li>檢查 XAMPP 控制面板中的 MySQL 狀態</li>";
    echo "<li>嘗試在控制面板中停止後重新啟動</li>";
    echo "<li>查看錯誤日誌: <code>C:\\xampp\\mysql\\data\\*.err</code></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    ?>
</body>
</html>





