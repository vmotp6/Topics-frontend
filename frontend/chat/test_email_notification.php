<?php
/**
 * 測試Gmail通知功能
 */

// 引入郵件通知服務
require_once '../../backend/services/email_notification.php';

echo "<h1>Gmail通知功能測試</h1>";

// 資料庫連接
$host = '100.79.58.120';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. 檢查系統配置</h2>";
    
    // 檢查環境變數
    $gmail_email = getenv('GMAIL_SENDER_EMAIL') ?: 'your-email@gmail.com';
    $gmail_password = getenv('GMAIL_APP_PASSWORD') ?: 'your-app-password';
    $gmail_name = getenv('GMAIL_SENDER_NAME') ?: '康寧大學聊天系統';
    $base_url = getenv('BASE_URL') ?: 'http://100.79.58.120';
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>配置項目</th><th>值</th><th>狀態</th></tr>";
    echo "<tr><td>Gmail發送者郵箱</td><td>" . htmlspecialchars($gmail_email) . "</td><td>" . ($gmail_email !== 'your-email@gmail.com' ? '✅ 已配置' : '❌ 未配置') . "</td></tr>";
    echo "<tr><td>Gmail應用程式密碼</td><td>" . (strlen($gmail_password) > 10 ? '已設置' : '未設置') . "</td><td>" . ($gmail_password !== 'your-app-password' ? '✅ 已配置' : '❌ 未配置') . "</td></tr>";
    echo "<tr><td>發送者名稱</td><td>" . htmlspecialchars($gmail_name) . "</td><td>✅ 已配置</td></tr>";
    echo "<tr><td>基礎URL</td><td>" . htmlspecialchars($base_url) . "</td><td>✅ 已配置</td></tr>";
    echo "</table>";
    
    echo "<h2>2. 檢查用戶資料</h2>";
    
    // 檢查用戶表是否有email欄位
    $stmt = $pdo->query("SHOW COLUMNS FROM user LIKE 'email'");
    $hasEmail = $stmt->rowCount() > 0;
    
    echo "<p>user表email欄位: " . ($hasEmail ? '✅ 存在' : '❌ 不存在') . "</p>";
    
    if (!$hasEmail) {
        echo "<p style='color: orange;'>⚠️ 需要添加email欄位到user表</p>";
        echo "<p><button onclick='addEmailColumn()'>添加email欄位</button></p>";
    }
    
    // 顯示現有用戶
    $stmt = $pdo->query("SELECT id, username, role FROM user ORDER BY role, username LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>現有用戶列表:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>用戶名</th><th>角色</th><th>Email</th></tr>";
    foreach ($users as $user) {
        $email = $hasEmail ? '未設置' : 'N/A';
        if ($hasEmail) {
            $stmt = $pdo->prepare("SELECT email FROM user WHERE id = ?");
            $stmt->execute([$user['id']]);
            $emailData = $stmt->fetch(PDO::FETCH_ASSOC);
            $email = $emailData['email'] ?: '未設置';
        }
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>" . htmlspecialchars($email) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. 測試郵件發送</h2>";
    
    // 測試郵件發送表單
    if (isset($_POST['test_email'])) {
        $test_email = $_POST['test_email'];
        $test_name = $_POST['test_name'] ?: '測試用戶';
        
        echo "<h3>發送測試郵件...</h3>";
        
        try {
            $emailService = new EmailNotificationService();
            $success = $emailService->sendPrivateMessageNotification(
                $test_email,
                $test_name,
                '系統測試',
                '這是一封測試郵件，用於驗證Gmail通知功能是否正常工作。',
                $base_url . '/frontend/chat/chat.php'
            );
            
            if ($success) {
                echo "<p style='color: green;'>✅ 測試郵件發送成功！請檢查您的郵箱。</p>";
            } else {
                echo "<p style='color: red;'>❌ 測試郵件發送失敗。請檢查配置和日誌。</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ 發送郵件時發生錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // 測試表單
    echo "<form method='POST' style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>發送測試郵件</h3>";
    echo "<p><label>測試郵箱地址: <input type='email' name='test_email' required style='width: 300px; padding: 8px;'></label></p>";
    echo "<p><label>接收者姓名: <input type='text' name='test_name' value='測試用戶' style='width: 200px; padding: 8px;'></label></p>";
    echo "<p><button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>發送測試郵件</button></p>";
    echo "</form>";
    
    echo "<h2>4. 檢查PHP郵件配置</h2>";
    
    // 檢查PHP郵件配置
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>配置項目</th><th>值</th><th>狀態</th></tr>";
    echo "<tr><td>mail()函數</td><td>" . (function_exists('mail') ? '可用' : '不可用') . "</td><td>" . (function_exists('mail') ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>SMTP</td><td>" . ini_get('SMTP') ?: '未設置' . "</td><td>" . (ini_get('SMTP') ? '✅' : '⚠️') . "</td></tr>";
    echo "<tr><td>smtp_port</td><td>" . ini_get('smtp_port') ?: '未設置' . "</td><td>" . (ini_get('smtp_port') ? '✅' : '⚠️') . "</td></tr>";
    echo "<tr><td>sendmail_from</td><td>" . ini_get('sendmail_from') ?: '未設置' . "</td><td>" . (ini_get('sendmail_from') ? '✅' : '⚠️') . "</td></tr>";
    echo "</table>";
    
    echo "<h2>5. 快速修復建議</h2>";
    
    if ($gmail_email === 'your-email@gmail.com' || $gmail_password === 'your-app-password') {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>⚠️ Gmail配置未完成</h4>";
        echo "<ol>";
        echo "<li>設置環境變數 GMAIL_SENDER_EMAIL 為您的Gmail地址</li>";
        echo "<li>設置環境變數 GMAIL_APP_PASSWORD 為您的Gmail應用程式密碼</li>";
        echo "<li>確保已啟用Gmail的兩步驟驗證</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    if (!$hasEmail) {
        echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>💡 添加email欄位</h4>";
        echo "<p>點擊上方「添加email欄位」按鈕，或手動執行以下SQL:</p>";
        echo "<code>ALTER TABLE user ADD COLUMN email VARCHAR(255);</code>";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<h1>❌ 資料庫連接失敗</h1>";
    echo "<p>錯誤: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<script>
function addEmailColumn() {
    if (confirm('確定要添加email欄位到user表嗎？')) {
        // 這裡可以添加AJAX請求來執行SQL
        alert('請手動執行以下SQL命令:\nALTER TABLE user ADD COLUMN email VARCHAR(255);');
    }
}
</script>

<style>
table {
    width: 100%;
    max-width: 800px;
}
th, td {
    padding: 8px;
    text-align: left;
}
th {
    background-color: #f2f2f2;
}
</style>









