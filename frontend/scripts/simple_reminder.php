<?php
/**
 * 簡化的郵件提醒系統
 * 檢查 admission_sessions 表的 session_date，如果是明天就發送提醒
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_functions.php';

// 設定時區
date_default_timezone_set('Asia/Taipei');

echo "=== 簡化郵件提醒系統 ===\n";
echo "執行時間：" . date('Y-m-d H:i:s') . "\n\n";

try {
    // 建立資料庫連接
    $conn = getDatabaseConnection();
    echo "✅ 資料庫連接成功\n";
    
    // 獲取明天的日期
    $tomorrow = new DateTime('+1 day');
    $tomorrow_date = $tomorrow->format('Y-m-d');
    
    echo "明天日期：{$tomorrow_date}\n\n";
    
    // 查詢明天有活動的場次
    $sessions_sql = "SELECT id, session_name, session_date, session_type 
                     FROM admission_sessions 
                     WHERE session_date = ? AND is_active = 1";
    
    $sessions_stmt = $conn->prepare($sessions_sql);
    $sessions_stmt->bind_param("s", $tomorrow_date);
    $sessions_stmt->execute();
    $sessions_result = $sessions_stmt->get_result();
    
    if ($sessions_result->num_rows > 0) {
        echo "找到 " . $sessions_result->num_rows . " 個明天的活動\n\n";
        
        $total_sent = 0;
        $total_failed = 0;
        
        while ($session = $sessions_result->fetch_assoc()) {
            $session_id = $session['id'];
            $session_name = $session['session_name'];
            $session_date = $session['session_date'];
            $session_type = $session['session_type'];
            
            echo "處理場次：{$session_name} (ID: {$session_id})\n";
            
            // 查詢該場次的所有報名者（包含報名時間）
            $applications_sql = "SELECT id, email, student_name, parent_name, 
                                        course_priority_1, course_priority_2, reminder_sent,
                                        created_at, reminder_sent_at
                                 FROM admission_applications 
                                 WHERE session_id = ? 
                                 AND email IS NOT NULL AND email != ''";
            
            $apps_stmt = $conn->prepare($applications_sql);
            $apps_stmt->bind_param("i", $session_id);
            $apps_stmt->execute();
            $apps_result = $apps_stmt->get_result();
            
            if ($apps_result->num_rows > 0) {
                echo "  找到 " . $apps_result->num_rows . " 位報名者\n";
                
                while ($application = $apps_result->fetch_assoc()) {
                    $student_name = $application['student_name'];
                    $email = $application['email'];
                    $created_at = $application['created_at'];
                    $reminder_sent = $application['reminder_sent'];
                    $reminder_sent_at = $application['reminder_sent_at'];
                    
                    echo "  👤 {$student_name} ({$email})\n";
                    echo "    報名時間：{$created_at}\n";
                    
                    // 檢查是否已發送過提醒
                    if ($reminder_sent == 1) {
                        echo "    ⏩ 已發送過提醒 ({$reminder_sent_at})，跳過\n";
                        continue;
                    }
                    
                    // 計算報名時間到活動時間的天數
                    $created_date = new DateTime($created_at);
                    $session_date_obj = new DateTime($session_date);
                    $days_since_registration = $created_date->diff($session_date_obj)->days;
                    
                    echo "    距離活動：{$days_since_registration} 天\n";
                    
                    // 檢查是否應該發送提醒（活動前一天）
                    $should_send = false;
                    if ($days_since_registration >= 0) { // 活動前一天就發送提醒
                        echo "    ✅ 符合發送條件（活動前一天提醒）\n";
                        $should_send = true;
                    } else {
                        echo "    ⏳ 報名時間太近，暫不發送提醒\n";
                    }
                    
                    if (!$should_send) {
                        continue;
                    }
                    
                    echo "    📤 發送提醒郵件...\n";
                    
                    // 發送提醒郵件
                    $email_sent = sendReminderEmail(
                        $application['email'],
                        $application['student_name'],
                        $application['parent_name'],
                        $session_name,
                        $session_date
                    );
                    
                    if ($email_sent) {
                        // 更新資料庫，標記已發送
                        $update_sql = "UPDATE admission_applications 
                                       SET reminder_sent = 1, reminder_sent_at = NOW() 
                                       WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $application['id']);
                        
                        if ($update_stmt->execute()) {
                            $total_sent++;
                            echo "    ✅ 發送成功並已更新資料庫\n";
                        } else {
                            $total_failed++;
                            echo "    ⚠️ 郵件發送成功但資料庫更新失敗\n";
                        }
                        $update_stmt->close();
                    } else {
                        $total_failed++;
                        echo "    ❌ 郵件發送失敗\n";
                    }
                    
                    // 避免發送過於頻繁
                    sleep(1);
                }
            } else {
                echo "  沒有找到報名者\n";
            }
            
            $apps_stmt->close();
            echo "\n";
        }
        
        echo "=== 發送完成 ===\n";
        echo "總計：成功 {$total_sent} 封，失敗 {$total_failed} 封\n";
        
    } else {
        echo "沒有找到明天的活動\n";
    }
    
    $sessions_stmt->close();
    $conn->close();
    
    echo "✅ 提醒系統執行完成\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    echo "錯誤詳情：" . $e->getTraceAsString() . "\n";
}
?>
