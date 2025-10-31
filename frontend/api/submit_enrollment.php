<?php
// 載入 session 配置
require_once '../session_config.php';

// 載入 Gmail 郵件配置
require_once '../../backend/config/email_config.php';

// 設定回應為 JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * 發送 Gmail 通知
 */
function sendEnrollmentNotification($data) {
    $config = getEmailConfig();
    
    // 檢查是否啟用郵件通知
    if (!$config['enable_notifications']) {
        return false;
    }
    
    // 檢查是否有填寫電子郵件
    if (empty($data['email'])) {
        return false;
    }
    
    // 設定收件人（發送給提交表單的人）
    $to_email = $data['email']; // 使用表單中填寫的電子郵件
    $to_name = $data['name']; // 使用表單中填寫的姓名
    
    // 郵件主題
    $subject = '【康寧大學】就讀意願登錄確認通知';
    
    // 郵件內容
    $message = "
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .field { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #667eea; }
            .label { font-weight: bold; color: #667eea; }
            .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>🎓 康寧大學就讀意願登錄確認</h2>
        </div>
        
        <div class='content'>
            <p>親愛的 {$data['name']}，您好！</p>
            <p>感謝您對康寧大學的關注！我們已收到您的就讀意願登錄，詳細資訊如下：</p>
            
            <div class='field'>
                <span class='label'>姓名：</span>{$data['name']}
            </div>
            
            <div class='field'>
                <span class='label'>身分別：</span>{$data['identity']}
            </div>
            
            <div class='field'>
                <span class='label'>性別：</span>{$data['gender']}
            </div>
            
            <div class='field'>
                <span class='label'>聯絡電話1：</span>{$data['phone1']}
            </div>
            
            <div class='field'>
                <span class='label'>聯絡電話2：</span>{$data['phone2']}
            </div>
            
            <div class='field'>
                <span class='label'>電子郵件：</span>{$data['email']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願一：</span>{$data['intention1']} - {$data['system1']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願二：</span>{$data['intention2']} - {$data['system2']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀意願三：</span>{$data['intention3']} - {$data['system3']}
            </div>
            
            <div class='field'>
                <span class='label'>就讀或畢業國中：</span>{$data['junior_high']}
            </div>
            
            <div class='field'>
                <span class='label'>目前年級：</span>{$data['current_grade']}
            </div>
            
            <div class='field'>
                <span class='label'>LineID：</span>{$data['line_id']}
            </div>
            
            <div class='field'>
                <span class='label'>Facebook：</span>{$data['facebook']}
            </div>
            
            <div class='field'>
                <span class='label'>推薦老師：</span>{$data['recommended_teacher']}
            </div>
            
            <div class='field'>
                <span class='label'>備註：</span>{$data['remarks']}
            </div>
            
            <div class='field'>
                <span class='label'>提交時間：</span>" . date('Y-m-d H:i:s') . "
            </div>
            
            <div style='margin-top: 30px; padding: 20px; background: #e8f4fd; border-radius: 8px; border-left: 4px solid #667eea;'>
                <h3 style='color: #667eea; margin-top: 0;'>📞 後續聯絡</h3>
                <p>我們的招生組將在收到您的資料後，儘快與您聯絡，請保持電話暢通。</p>
                <p>如有任何疑問，歡迎隨時與我們聯繫：</p>
                <ul>
                    <li>📧 招生組信箱：admissions@knu.edu.tw</li>
                    <li>📞 招生組電話：(02) 2632-1181</li>
                    <li>🌐 官方網站：https://www.knu.edu.tw</li>
                </ul>
            </div>
        </div>
        
        <div class='footer'>
            <p>此郵件由康寧大學招生平台自動發送，請勿直接回覆。</p>
            <p>如有疑問，請透過上述聯絡方式與我們聯繫。</p>
        </div>
    </body>
    </html>
    ";
    
    // 郵件標頭
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $config['sender_name'] . ' <' . $config['sender_email'] . '>',
        'Reply-To: ' . $config['sender_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // 發送郵件
    return mail($to_email, $subject, $message, implode("\r\n", $headers));
}

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '資料庫連接失敗'
    ]);
    exit;
}

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '請使用 POST 方法提交'
    ]);
    exit;
}

// 獲取表單數據
$name = $_POST['name'] ?? '';
$identity = $_POST['identity'] ?? '';
$gender = $_POST['gender'] ?? '';
$phone1 = $_POST['phone1'] ?? '';
$phone2 = $_POST['phone2'] ?? '';
$email = $_POST['email'] ?? '';
$intention1 = $_POST['intention1'] ?? '';
$intention2 = $_POST['intention2'] ?? '';
$intention3 = $_POST['intention3'] ?? '';
$system1 = $_POST['system1'] ?? '';
$system2 = $_POST['system2'] ?? '';
$system3 = $_POST['system3'] ?? '';
$junior_high = $_POST['junior_high'] ?? '';
$current_grade = $_POST['current_grade'] ?? '';
$line_id = $_POST['line_id'] ?? '';
$facebook = $_POST['facebook'] ?? '';
$recommended_teacher = $_POST['recommended_teacher'] ?? '';
$remarks = $_POST['remarks'] ?? '';
$captcha = $_POST['captcha'] ?? '';

// 基本驗證
if (empty($name) || empty($identity) || empty($phone1)) {
    echo json_encode([
        'success' => false,
        'message' => '請填寫必填欄位'
    ]);
    exit;
}

// 驗證碼檢查（這裡可以加入更複雜的驗證碼驗證邏輯）
if (empty($captcha)) {
    echo json_encode([
        'success' => false,
        'message' => '請輸入驗證碼'
    ]);
    exit;
}

try {
    // 插入資料到資料庫
    $sql = "INSERT INTO enrollment_intention (
        name, identity, gender, phone1, phone2, email,
        intention1, intention2, intention3,
        system1, system2, system3,
        junior_high, current_grade,
        line_id, facebook, recommended_teacher, remarks,
        captcha, created_at
    ) VALUES (
        :name, :identity, :gender, :phone1, :phone2, :email,
        :intention1, :intention2, :intention3,
        :system1, :system2, :system3,
        :junior_high, :current_grade,
        :line_id, :facebook, :recommended_teacher, :remarks,
        :captcha, NOW()
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':identity', $identity);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':phone1', $phone1);
    $stmt->bindParam(':phone2', $phone2);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':intention1', $intention1);
    $stmt->bindParam(':intention2', $intention2);
    $stmt->bindParam(':intention3', $intention3);
    $stmt->bindParam(':system1', $system1);
    $stmt->bindParam(':system2', $system2);
    $stmt->bindParam(':system3', $system3);
    $stmt->bindParam(':junior_high', $junior_high);
    $stmt->bindParam(':current_grade', $current_grade);
    $stmt->bindParam(':line_id', $line_id);
    $stmt->bindParam(':facebook', $facebook);
    $stmt->bindParam(':recommended_teacher', $recommended_teacher);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':captcha', $captcha);

    if ($stmt->execute()) {
        // 準備郵件資料
        $emailData = [
            'name' => $name,
            'identity' => $identity,
            'gender' => $gender,
            'phone1' => $phone1,
            'phone2' => $phone2,
            'email' => $email,
            'intention1' => $intention1,
            'intention2' => $intention2,
            'intention3' => $intention3,
            'system1' => $system1,
            'system2' => $system2,
            'system3' => $system3,
            'junior_high' => $junior_high,
            'current_grade' => $current_grade,
            'line_id' => $line_id,
            'facebook' => $facebook,
            'recommended_teacher' => $recommended_teacher,
            'remarks' => $remarks
        ];
        
        // 發送 Gmail 通知
        $emailSent = sendEnrollmentNotification($emailData);
        
        $message = '就讀意願提交成功！我們將儘快與您聯絡。';
        if ($emailSent && !empty($email)) {
            $message .= ' 確認通知已發送至您的電子郵件信箱。';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '提交失敗，請稍後再試'
        ]);
    }

} catch (PDOException $e) {
    // 記錄錯誤
    error_log("就讀意願提交錯誤: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => '系統錯誤，請稍後再試'
    ]);
}
?>
