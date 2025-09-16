<?php
/**
 * 康寧大學五專入學說明會郵件提醒腳本
 * 此腳本應該設置為定期執行（例如每天執行一次）
 * 檢查即將到來的活動並發送提醒郵件
 */

require_once '../../frontend/config.php';

// 郵件配置
$mail_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // 請更換為實際郵箱
    'smtp_password' => 'your-app-password',    // 請更換為應用程式密碼
    'from_email' => 'your-email@gmail.com',
    'from_name' => '康寧大學招生中心'
];

// 從資料庫動態取得活動場次
function getActiveSessions($conn) {
    $sessions = [];
    $query = "SELECT id, session_name, session_date, session_type FROM admission_sessions WHERE is_active = 1";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sessions[$row['id']] = [
                'name' => $row['session_name'],
                'date' => $row['session_date'],
                'type' => $row['session_type']
            ];
        }
    }
    
    return $sessions;
}

// 發送郵件函數
function sendReminderEmail($to_email, $student_name, $parent_name, $session_choice, $experience_course) {
    global $mail_config;
    
    $subject = "康寧大學五專入學說明會提醒 - {$session_choice}";
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
            .footer { background: #333; color: white; padding: 15px; text-align: center; }
            .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🎓 康寧大學五專入學說明會</h1>
            <p>選擇康寧 • 人生雙贏 • 未來罩您</p>
        </div>
        
        <div class='content'>
            <h2>親愛的 {$parent_name} 家長您好：</h2>
            
            <p>感謝您為 <strong>{$student_name}</strong> 同學報名康寧大學五專入學說明會！</p>
            
            <div class='info-box'>
                <h3>📅 活動提醒</h3>
                <p><strong>參加場次：</strong>{$session_choice}</p>
                <p><strong>體驗課程：</strong>{$experience_course}</p>
            </div>
            
            <div class='info-box'>
                <h3>📞 聯絡資訊</h3>
                <p><strong>招生諮詢電話：</strong>2632-1181*310 / 0916-051-882</p>
                <p><strong>LINE ID：</strong>@ukn_taipei</p>
                <p><strong>招生中心：</strong>高老師</p>
            </div>
            
            <div class='info-box'>
                <h3>🎯 五專優勢</h3>
                <ul>
                    <li>✅ 念五專有前途</li>
                    <li>✅ 升學就業兩相宜</li>
                    <li>✅ 五專前三年免學費</li>
                    <li>✅ 展翅計畫免學雜費保證就業</li>
                </ul>
            </div>
            
            <div class='info-box'>
                <h3>🏫 科系介紹</h3>
                <p>護理科 | 視光科 | 資訊管理科 | 應用外語科 | 嬰幼兒保育科 | 企業管理科 | 數位影視動畫科</p>
            </div>
            
            <p style='margin-top: 20px;'>
                如有任何問題，歡迎隨時與我們聯繫。期待在說明會上與您見面！
            </p>
        </div>
        
        <div class='footer'>
            <p>© 2025 康寧大學招生中心</p>
            <p>此郵件為系統自動發送，請勿直接回覆</p>
        </div>
    </body>
    </html>
    ";
    
    // 設置郵件標頭
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        "From: {$mail_config['from_name']} <{$mail_config['from_email']}>",
        'Reply-To: ' . $mail_config['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // 發送郵件
    return mail($to_email, $subject, $message, implode("\r\n", $headers));
}

// 主程序
function sendReminders() {
    try {
        // 建立資料庫連接
        $conn = getDatabaseConnection();
        
        // 取得活動場次
        $sessions = getActiveSessions($conn);
        
        // 取得當前時間
        $now = new DateTime();
        
        // 檢查每個活動場次
        foreach ($sessions as $session_id => $session_info) {
            $session_date = new DateTime($session_info['date']);
            
            // 計算距離活動的天數
            $interval = $now->diff($session_date);
            $days_until = $interval->days;
            
            // 如果活動在 1-3 天內，發送提醒郵件
            if ($days_until >= 1 && $days_until <= 3 && $interval->invert == 0) {
                echo "檢查活動：{$session_info['name']}，距離活動 {$days_until} 天\n";
                
                // 查詢尚未發送提醒郵件的報名者
                $sql = "SELECT a.*, s.session_name, s.session_type 
                        FROM admission_applications a 
                        JOIN admission_sessions s ON a.session_id = s.id 
                        WHERE a.session_id = ? 
                        AND a.email_sent = 0 
                        AND a.email IS NOT NULL 
                        AND a.email != ''";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $session_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $sent_count = 0;
                $failed_count = 0;
                
                while ($row = $result->fetch_assoc()) {
                    // 發送提醒郵件
                    $email_sent = sendReminderEmail(
                        $row['email'],
                        $row['student_name'],
                        $row['parent_name'],
                        $row['session_name'] . ($row['session_type'] === '線上' ? ' (線上)' : ''),
                        $row['experience_course']
                    );
                    
                    if ($email_sent) {
                        // 更新資料庫，標記已發送
                        $update_sql = "UPDATE admission_applications SET email_sent = 1 WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $row['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        $sent_count++;
                        echo "✅ 已發送提醒郵件給：{$row['email']} ({$row['student_name']})\n";
                    } else {
                        $failed_count++;
                        echo "❌ 發送失敗：{$row['email']} ({$row['student_name']})\n";
                    }
                    
                    // 避免發送過於頻繁，每封郵件間隔1秒
                    sleep(1);
                }
                
                $stmt->close();
                echo "場次 [{$session_info['name']}] 提醒郵件發送完成：成功 {$sent_count} 封，失敗 {$failed_count} 封\n\n";
            }
        }
        
        $conn->close();
        echo "✅ 郵件提醒檢查完成\n";
        
    } catch (Exception $e) {
        echo "❌ 錯誤：" . $e->getMessage() . "\n";
        
        // 記錄錯誤到文件
        $error_log = date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/email_reminder_errors.log', $error_log, FILE_APPEND);
    }
}

// 檢查是否從命令行執行
if (php_sapi_name() === 'cli') {
    echo "=== 康寧大學五專入學說明會郵件提醒系統 ===\n";
    echo "執行時間：" . date('Y-m-d H:i:s') . "\n\n";
    sendReminders();
} else {
    // 如果從網頁執行，需要管理員權限
    session_start();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        die('權限不足');
    }
    
    echo "<pre>";
    echo "=== 康寧大學五專入學說明會郵件提醒系統 ===\n";
    echo "執行時間：" . date('Y-m-d H:i:s') . "\n\n";
    sendReminders();
    echo "</pre>";
}
?>
