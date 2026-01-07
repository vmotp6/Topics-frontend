<?php
// 載入 session 配置
require_once 'session_config.php';
// 載入資料庫配置 (為了去查 User ID)
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取 POST 數據
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (isset($data['username']) && isset($data['role'])) {
        try {
            // 1. 建立資料庫連線
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 2. 查詢使用者的 ID 和 Name
            $stmt = $pdo->prepare("SELECT id, name, role FROM user WHERE username = ?");
            $stmt->execute([$data['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 3. 設定完整的 Session
                $_SESSION['logged_in'] = $data['logged_in'] ?? true;
                $_SESSION['login_method'] = 'google'; // 標記為 Google 登入
                
                // 關鍵修正：設定 user_id
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['id'] = $user['id']; // 雙重保險，有些舊程式可能用 'id'
                
                $_SESSION['username'] = $data['username'];
                $_SESSION['role'] = $user['role']; // 優先使用資料庫裡的角色
                $_SESSION['name'] = $user['name']; // 設定顯示名稱 (讓 Header 顯示正確)

                // 為了後台相容性
                if (in_array($user['role'], ['ADM', 'STA', 'DI', 'IM', 'TEA', 'AS', '管理員', '行政人員', '主任', '資管科主任', '科助'])) {
                    $_SESSION['admin_logged_in'] = true;
                }

                error_log("Session 設定成功 (含ID): user_id=" . $user['id'] . ", username=" . $data['username']);
                echo json_encode(["success" => true, "message" => "Session set with ID"]);
            } else {
                // 如果資料庫找不到這個人 (可能是第一次登入還沒寫入 DB?)
                // 先設定基本資料，但 ID 可能會缺漏
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $data['username'];
                $_SESSION['role'] = $data['role'];
                // 嘗試設定一個臨時 ID 或保持為 0
                error_log("警告: Session 設定但資料庫查無此人: " . $data['username']);
                echo json_encode(["success" => true, "message" => "Session set (User not found in DB)"]);
            }
            exit;

        } catch (PDOException $e) {
            error_log("資料庫錯誤: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Database error"]);
            exit;
        }
    }
}

echo json_encode(["success" => false, "message" => "Invalid data"]);
?>