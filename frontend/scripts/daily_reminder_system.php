<?php
/**
 * 康寧大學五專入學說明會 - 每日自動提醒郵件系統
 * 每天檢查是否有明天的活動，自動發送提醒郵件
 * 
 * 使用方法：
 * 1. 手動執行：php daily_reminder_system.php
 * 2. 定時任務：設置每天早上8點執行此腳本
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_functions.php';

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設定時區
date_default_timezone_set('Asia/Taipei');

/**
 * 解析民國年日期字串為西元年日期
 * 支援格式：115.09.20（六) 1900-2100
 */
function parseTaiwanDate($session_name) {
    $result = [
        'success' => false,
        'minguo_year' => null,
        'western_year' => null,
        'month' => null,
        'day' => null,
        'mysql_date' => null,
        'formatted_datetime' => null,
        'time_range' => null
    ];
    
    // 正則表達式匹配民國年日期
    if (preg_match('/(\d{3})\.(\d{2})\.(\d{2}).*?(\d{4})-(\d{4})/', $session_name, $matches)) {
        $result['minguo_year'] = intval($matches[1]);
        $result['month'] = intval($matches[2]);
        $result['day'] = intval($matches[3]);
        $result['time_range'] = $matches[4] . '-' . $matches[5];
        
        // 民國年轉西元年（民國年 + 1911）
        $result['western_year'] = $result['minguo_year'] + 1911;
        
        // MySQL 日期格式
        $result['mysql_date'] = sprintf("%d-%02d-%02d", $result['western_year'], $result['month'], $result['day']);
        
        // 格式化顯示時間
        $start_hour = substr($matches[4], 0, 2);
        $start_minute = substr($matches[4], 2, 2);
        $result['formatted_datetime'] = sprintf("%d年%02d月%02d日 %s:%s", 
            $result['western_year'], $result['month'], $result['day'], $start_hour, $start_minute);
        
        $result['success'] = true;
    }
    
    return $result;
}

/**
 * 判斷活動類型（線上/實體）
 */
function getSessionType($session_name) {
    return (strpos($session_name, '線上') !== false) ? '線上' : '實體';
}

/**
 * 記錄日誌
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}\n";
    
    // 輸出到控制台
    echo $log_message;
    
    // 寫入日誌文件
    $log_file = __DIR__ . '/reminder_system.log';
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * 主要執行函數
 */
function runDailyReminderSystem() {
    try {
        logMessage("=== 康寧大學提醒郵件系統啟動 ===");
        
        // 建立資料庫連接
        $conn = getDatabaseConnection();
        logMessage("資料庫連接成功");
        
        // 獲取今天和明天的日期
        $today = new DateTime();
        $tomorrow = new DateTime('+1 day');
        $today_date = $today->format('Y-m-d');
        $tomorrow_date = $tomorrow->format('Y-m-d');
        
        logMessage("今天日期：{$today_date}");
        logMessage("明天日期：{$tomorrow_date}");
        
        // 查找今天和明天的活動
        logMessage("開始搜索今天和明天的活動...");
        
        $activities_found = [];
        
        // 查詢今天和明天的場次
        $sessions_sql = "SELECT id, session_name, session_date, session_type FROM admission_sessions 
                         WHERE is_active = 1 AND session_date IN (?, ?)
                         ORDER BY session_date";
        $sessions_stmt = $conn->prepare($sessions_sql);
        $sessions_stmt->bind_param("ss", $today_date, $tomorrow_date);
        $sessions_stmt->execute();
        $sessions_result = $sessions_stmt->get_result();
        
        if ($sessions_result) {
            while ($row = $sessions_result->fetch_assoc()) {
                $session_name = $row['session_name'];
                $session_id = $row['id'];
                $session_date = $row['session_date'];
                $session_type = $row['session_type'];
                
                logMessage("檢查場次: {$session_name} (ID: {$session_id})");
                logMessage("  → 活動日期: {$session_date}");
                
                // 檢查是否為今天或明天的活動
                if ($session_date === $today_date || $session_date === $tomorrow_date) {
                    $day_type = ($session_date === $today_date) ? "今天" : "明天";
                    logMessage("  ✅ 發現{$day_type}的活動！");
                        $activities_found[] = [
                        'session_id' => $session_id,
                            'session_name' => $session_name,
                        'session_date' => $session_date,
                        'session_type' => $session_type
                        ];
                    } else {
                    logMessage("  ❌ 不是今天或明天的活動");
                }
            }
        }
        $sessions_stmt->close();
        
        logMessage("找到 " . count($activities_found) . " 個今天或明天的活動");
        
        if (count($activities_found) > 0) {
            $total_sent = 0;
            $total_failed = 0;
            $total_already_sent = 0;
            
            foreach ($activities_found as $activity) {
                $session_id = $activity['session_id'];
                $session_name = $activity['session_name'];
                $session_date = $activity['session_date'];
                $session_type = $activity['session_type'];
                
                logMessage("處理活動：{$session_name} (ID: {$session_id})");
                logMessage("  活動時間：{$session_date}");
                logMessage("  活動類型：{$session_type}");
                
                // 查詢該場次的報名者（使用session_id進行查詢）
                $applications_sql = "SELECT id, email, student_name, parent_name, 
                                            course_priority_1, course_priority_2, reminder_sent
                                     FROM admission_applications 
                                     WHERE session_id = ? 
                                     AND email IS NOT NULL AND email != ''";
                
                $apps_stmt = $conn->prepare($applications_sql);
                $apps_stmt->bind_param("i", $activity['session_id']);
                $apps_stmt->execute();
                $apps_result = $apps_stmt->get_result();
                
                $session_sent = 0;
                $session_failed = 0;
                $session_already_sent = 0;
                
                if ($apps_result->num_rows > 0) {
                    logMessage("  找到 " . $apps_result->num_rows . " 位報名者");
                    
                    while ($application = $apps_result->fetch_assoc()) {
                        // 檢查是否已發送
                        if ($application['reminder_sent'] == 1) {
                            $session_already_sent++;
                            logMessage("  ⏩ {$application['student_name']} 已發送過提醒");
                            continue;
                        }
                        
                        // 組合課程資訊
                        $course_info = [];
                        if (!empty($application['course_priority_1'])) {
                            $course_info[] = "第一選擇：" . $application['course_priority_1'];
                        }
                        if (!empty($application['course_priority_2'])) {
                            $course_info[] = "第二選擇：" . $application['course_priority_2'];
                        }
                        $course_text = !empty($course_info) ? implode('、', $course_info) : '未選擇體驗課程';
                        
                        // 發送提醒郵件
                        logMessage("  📤 發送提醒郵件給：{$application['student_name']} ({$application['email']})");
                        
                        $email_sent = sendReminderEmail(
                            $application['email'],
                            $application['student_name'],
                            $application['parent_name'],
                            $session_name,
                            $session_date
                        );
                        
                        if ($email_sent) {
                            // 更新資料庫
                            $update_sql = "UPDATE admission_applications 
                                           SET reminder_sent = 1, reminder_sent_at = NOW() 
                                           WHERE id = ?";
                            $update_stmt = $conn->prepare($update_sql);
                            $update_stmt->bind_param("i", $application['id']);
                            
                            if ($update_stmt->execute()) {
                                $session_sent++;
                                logMessage("  ✅ 提醒郵件發送成功並已更新資料庫");
                            } else {
                                $session_failed++;
                                logMessage("  ⚠️ 郵件發送成功但資料庫更新失敗", 'WARNING');
                            }
                            $update_stmt->close();
                        } else {
                            $session_failed++;
                            logMessage("  ❌ 提醒郵件發送失敗", 'ERROR');
                        }
                        
                        // 避免發送過於頻繁
                        sleep(1);
                    }
                } else {
                    logMessage("  該場次沒有報名者");
                }
                
                $apps_stmt->close();
                
                logMessage("  場次統計：成功 {$session_sent} 封，已發送 {$session_already_sent} 封，失敗 {$session_failed} 封");
                
                $total_sent += $session_sent;
                $total_failed += $session_failed;
                $total_already_sent += $session_already_sent;
            }
            
            // 總結報告
            logMessage("=== 執行結果統計 ===");
            logMessage("發現活動場次：" . count($activities_found) . " 個");
            logMessage("成功發送郵件：{$total_sent} 封");
            logMessage("已發送過的：{$total_already_sent} 封");
            logMessage("發送失敗郵件：{$total_failed} 封");
            
        } else {
            logMessage("明天沒有安排的活動場次");
        }
        
        $conn->close();
        logMessage("=== 提醒郵件系統執行完畢 ===");
        
    } catch (Exception $e) {
        logMessage("系統錯誤：" . $e->getMessage(), 'ERROR');
        logMessage("錯誤詳情：文件 " . $e->getFile() . " 行號 " . $e->getLine(), 'ERROR');
        
        // 記錄詳細錯誤到錯誤日誌
        $error_log = __DIR__ . '/reminder_error.log';
        $error_message = "[" . date('Y-m-d H:i:s') . "] 錯誤：" . $e->getMessage() . "\n";
        $error_message .= "文件：" . $e->getFile() . "\n";
        $error_message .= "行號：" . $e->getLine() . "\n";
        $error_message .= "堆疊：" . $e->getTraceAsString() . "\n\n";
        file_put_contents($error_log, $error_message, FILE_APPEND | LOCK_EX);
        
        return false;
    }
    
    return true;
}

// 執行主程序
if (php_sapi_name() === 'cli') {
    // 命令行模式
    echo "康寧大學五專入學說明會 - 每日提醒郵件系統\n";
    echo "執行時間：" . date('Y-m-d H:i:s') . "\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $success = runDailyReminderSystem();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "執行" . ($success ? "成功" : "失敗") . "！\n";
    
    exit($success ? 0 : 1);
} else {
    // Web模式 - 提供簡單的Web界面
    ?>
    <!DOCTYPE html>
    <html lang="zh-TW">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>每日提醒郵件系統 - 康寧大學</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { background: #667eea; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 10px 5px; }
            .btn:hover { background: #5a67d8; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .output { background: #000; color: #00ff00; padding: 20px; border-radius: 5px; white-space: pre-wrap; font-family: 'Courier New', monospace; margin: 20px 0; max-height: 400px; overflow-y: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🤖 每日提醒郵件系統</h1>
            <p>自動檢查明天的活動並發送提醒郵件</p>

            <div class="info">
                <h3>📋 系統功能</h3>
                <ul>
                    <li>🔍 自動檢查明天的活動（支援民國年轉換）</li>
                    <li>📧 發送活動提醒郵件給報名者</li>
                    <li>🗄️ 更新資料庫記錄 (reminder_sent = 1, reminder_sent_at)</li>
                    <li>⏩ 跳過已發送提醒的用戶</li>
                    <li>📊 提供詳細的執行報告</li>
                </ul>
            </div>

            <?php if (isset($_POST['run_system'])): ?>
                <div class="output"><?php
                    ob_start();
                    runDailyReminderSystem();
                    $output = ob_get_clean();
                    echo htmlspecialchars($output);
                ?></div>
            <?php endif; ?>

            <div style="text-align: center;">
                <form method="POST">
                    <button type="submit" name="run_system" class="btn">🚀 立即執行提醒檢查</button>
                </form>
                
                <a href="../admission.php" class="btn" style="background: #28a745;">📝 返回報名頁面</a>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h3>⚙️ 設定自動執行</h3>
                
                <h4>Windows 工作排程器：</h4>
                <ul>
                    <li>程式：C:\xampp\php\php.exe</li>
                    <li>引數：<?php echo __FILE__; ?></li>
                    <li>執行時間：每天 08:00</li>
                </ul>

                <h4>Linux Cron：</h4>
                <pre>0 8 * * * php <?php echo __FILE__; ?> >> /var/log/reminder.log 2>&1</pre>

                <h3>📝 日誌檔案</h3>
                <p>系統日誌：<?php echo __DIR__; ?>/reminder_system.log</p>
                <p>錯誤日誌：<?php echo __DIR__; ?>/reminder_error.log</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
