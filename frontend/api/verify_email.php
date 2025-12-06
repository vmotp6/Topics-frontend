<?php
/**
 * Email 驗證碼驗證 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// 只接受 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只接受 POST 請求']);
    exit;
}

$action = $_POST['action'] ?? '';
$conn = getDatabaseConnection();

try {
    switch ($action) {
        case 'verify_code':
            // 驗證驗證碼
            $username = trim($_POST['username'] ?? '');
            $code = trim($_POST['code'] ?? '');
            
            if (empty($username) || empty($code)) {
                echo json_encode(['success' => false, 'message' => '請輸入帳號和驗證碼']);
                exit;
            }
            
            // 查詢用戶和驗證碼
            $stmt = $conn->prepare("
                SELECT u.id, u.email, u.email_verified, vc.code, vc.expires_at
                FROM user u
                LEFT JOIN email_verification_codes vc ON u.id = vc.user_id
                WHERE u.username = ?
                ORDER BY vc.created_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row) {
                echo json_encode(['success' => false, 'message' => '找不到該帳號']);
                exit;
            }
            
            if ($row['email_verified'] == 1) {
                echo json_encode(['success' => false, 'message' => '此帳號已經驗證過了']);
                exit;
            }
            
            if (!$row['code']) {
                echo json_encode(['success' => false, 'message' => '找不到驗證碼，請重新註冊或重新發送驗證碼']);
                exit;
            }
            
            // 檢查驗證碼是否過期
            $expires_at = strtotime($row['expires_at']);
            if (time() > $expires_at) {
                echo json_encode(['success' => false, 'message' => '驗證碼已過期，請重新發送']);
                exit;
            }
            
            // 驗證驗證碼
            if ($code !== $row['code']) {
                echo json_encode(['success' => false, 'message' => '驗證碼錯誤']);
                exit;
            }
            
            // 驗證成功，更新 email_verified
            $update_stmt = $conn->prepare("UPDATE user SET email_verified = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // 刪除已使用的驗證碼
            $delete_stmt = $conn->prepare("DELETE FROM email_verification_codes WHERE user_id = ?");
            $delete_stmt->bind_param("i", $row['id']);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Email 驗證成功！']);
            break;
            
        case 'resend_code':
            // 重新發送驗證碼
            $username = trim($_POST['username'] ?? '');
            
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => '請輸入帳號']);
                exit;
            }
            
            // 查詢用戶
            $stmt = $conn->prepare("SELECT id, email, email_verified FROM user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => '找不到該帳號']);
                exit;
            }
            
            if ($user['email_verified'] == 1) {
                echo json_encode(['success' => false, 'message' => '此帳號已經驗證過了']);
                exit;
            }
            
            // 生成新的驗證碼
            $verification_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1小時後過期
            
            // 刪除舊的驗證碼
            $delete_stmt = $conn->prepare("DELETE FROM email_verification_codes WHERE user_id = ?");
            $delete_stmt->bind_param("i", $user['id']);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // 插入新的驗證碼
            $insert_stmt = $conn->prepare("
                INSERT INTO email_verification_codes (user_id, code, expires_at) 
                VALUES (?, ?, ?)
            ");
            $insert_stmt->bind_param("iss", $user['id'], $verification_code, $expires_at);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            // 發送驗證碼郵件
            require_once '../includes/email_functions.php';
            $subject = "康寧大學招生平台 - Email 驗證碼";
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .code-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center; border: 2px solid #667eea; }
                    .code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 8px; }
                    .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📧 Email 驗證</h1>
                    </div>
                    <div class='content'>
                        <h2>親愛的用戶，您好！</h2>
                        <p>感謝您註冊康寧大學招生平台！</p>
                        <p>請使用以下驗證碼完成 Email 驗證：</p>
                        <div class='code-box'>
                            <div class='code'>{$verification_code}</div>
                        </div>
                        <p>此驗證碼將在 1 小時後過期。</p>
                        <p>如果您沒有註冊此帳號，請忽略此郵件。</p>
                        <div class='footer'>
                            <p>此郵件由系統自動發送，請勿直接回覆</p>
                            <p><strong>康寧大學招生組</strong></p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $email_sent = sendEmail($user['email'], $subject, $body);
            
            if ($email_sent) {
                echo json_encode(['success' => true, 'message' => '驗證碼已重新發送到您的 Email']);
            } else {
                // 如果發送失敗，刪除剛插入的驗證碼
                $delete_stmt = $conn->prepare("DELETE FROM email_verification_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $delete_stmt->bind_param("i", $user['id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                echo json_encode(['success' => false, 'message' => '發送驗證碼失敗，請稍後再試']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '無效的操作']);
            break;
    }
} catch (Exception $e) {
    error_log("Email 驗證錯誤: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '系統錯誤，請稍後再試']);
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>
