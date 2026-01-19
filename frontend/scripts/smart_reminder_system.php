<?php
/**
 * 智能提醒系統
 * 根據報名時間和活動時間智能決定何時發送提醒
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_functions.php';

// 設置時區
date_default_timezone_set('Asia/Taipei');

echo "=== 智能提醒系統 ===\n";
echo "執行時間：" . date('Y-m-d H:i:s') . "\n\n";

try {
    $conn = getDatabaseConnection();
    echo "✅ 資料庫連接成功\n\n";

    // 查詢所有未發送提醒的報名者
    $applications_sql = "SELECT a.id, a.email, a.student_name, a.parent_name, 
                                a.course_priority_1, a.course_priority_2, a.created_at,
                                s.session_name, s.session_date, s.session_type, s.location, s.online_link
                         FROM admission_applications a
                         INNER JOIN admission_sessions s ON a.session_id = s.id
                         WHERE a.reminder_sent = 0 
                         AND a.email IS NOT NULL AND a.email != ''
                         AND s.is_active = 1
                         ORDER BY s.session_date, a.created_at";
    
    $apps_stmt = $conn->prepare($applications_sql);
    $apps_stmt->execute();
    $apps_result = $apps_stmt->get_result();
    
    $total_sent = 0;
    $total_failed = 0;
    $total_skipped = 0;
    
    if ($apps_result->num_rows > 0) {
        echo "找到 " . $apps_result->num_rows . " 位未發送提醒的報名者：\n\n";
        
        while ($application = $apps_result->fetch_assoc()) {
            $student_name = $application['student_name'];
            $email = $application['email'];
            $session_name = $application['session_name'];
            $session_date = $application['session_date'];
            $created_at = $application['created_at'];
            
            echo "👤 {$student_name} ({$email})\n";
            echo "   場次：{$session_name}\n";
            echo "   活動日期：{$session_date}\n";
            echo "   報名時間：{$created_at}\n";
            
            // 計算時間差
            $today = new DateTime();
            $session_date_obj = new DateTime($session_date);
            $created_date_obj = new DateTime($created_at);
            
            $days_until_session = $today->diff($session_date_obj)->days;
            $days_since_registration = $created_date_obj->diff($today)->days;
            
            echo "   距離活動：{$days_until_session} 天\n";
            echo "   報名後經過：{$days_since_registration} 天\n";
            
            // 智能判斷是否應該發送提醒
            $should_send = false;
            $reason = "";
            
            if ($days_until_session < 0) {
                // 活動已過期
                $reason = "活動已過期";
                echo "   ❌ {$reason}\n";
            } elseif ($days_until_session == 0) {
                // 活動是今天
                $should_send = true;
                $reason = "活動是今天";
                echo "   ✅ {$reason} - 立即發送提醒\n";
            } elseif ($days_until_session == 1) {
                // 活動是明天
                $should_send = true;
                $reason = "活動是明天";
                echo "   ✅ {$reason} - 發送提醒\n";
            } elseif ($days_until_session <= 3 && $days_since_registration >= 1) {
                // 活動在3天內，且報名已超過1天
                $should_send = true;
                $reason = "活動在3天內且報名已超過1天";
                echo "   ✅ {$reason} - 發送提醒\n";
            } elseif ($days_until_session <= 7 && $days_since_registration >= 3) {
                // 活動在7天內，且報名已超過3天
                $should_send = true;
                $reason = "活動在7天內且報名已超過3天";
                echo "   ✅ {$reason} - 發送提醒\n";
            } else {
                $reason = "時間未到，暫不發送";
                echo "   ⏳ {$reason}\n";
            }
            
            if ($should_send) {
                echo "   📤 發送提醒郵件...\n";
                
                $session_type = $application['session_type'] ?? '實體';
                $location = $application['location'] ?? '';
                $online_link = $application['online_link'] ?? '';
                
                $email_sent = sendReminderEmail(
                    $application['email'],
                    $application['student_name'],
                    $application['parent_name'],
                    $session_name,
                    $session_date,
                    $session_type,
                    $location,
                    $online_link
                );
                
                if ($email_sent) {
                    // 更新資料庫
                    $update_sql = "UPDATE admission_applications 
                                   SET reminder_sent = 1, reminder_sent_at = NOW() 
                                   WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("i", $application['id']);
                    
                    if ($update_stmt->execute()) {
                        $total_sent++;
                        echo "   ✅ 發送成功並已更新資料庫\n";
                    } else {
                        $total_failed++;
                        echo "   ⚠️ 郵件發送成功但資料庫更新失敗\n";
                    }
                    $update_stmt->close();
                } else {
                    $total_failed++;
                    echo "   ❌ 郵件發送失敗\n";
                }
            } else {
                $total_skipped++;
            }
            
            echo "\n";
            sleep(1); // 避免發送過於頻繁
        }
    } else {
        echo "沒有找到需要發送提醒的報名者。\n";
    }
    
    $apps_stmt->close();
    $conn->close();
    
    echo "=== 執行結果統計 ===\n";
    echo "成功發送：{$total_sent} 封\n";
    echo "發送失敗：{$total_failed} 封\n";
    echo "暫不發送：{$total_skipped} 封\n";
    echo "\n智能提醒系統執行完畢！\n";
    
} catch (Exception $e) {
    echo "❌ 錯誤：" . $e->getMessage() . "\n";
    echo "錯誤詳情：文件 " . $e->getFile() . " 行號 " . $e->getLine() . "\n";
}
?>
