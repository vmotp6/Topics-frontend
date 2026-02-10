<?php
/**
 * 續招報名分配通知郵件功能
 */

// 載入郵件發送功能
require_once __DIR__ . '/email_functions.php';

/**
 * 發送續招報名分配通知給主任
 * 注意：郵件內容不會顯示志願順序資訊，以保護學生隱私
 * 
 * @param PDO $pdo 資料庫連接
 * @param string $department_code 科系代碼
 * @param int $choice_order 志願順序（1, 2, 3）- 僅用於內部處理，不會在郵件中顯示
 * @param array $student_data 學生資料
 * @param int $application_id 報名ID
 * @return bool 發送是否成功
 */
function sendContinuedAdmissionDirectorNotification($pdo, $department_code, $choice_order, $student_data, $application_id) {
    try {
        // 步驟1: 驗證科系代碼是否存在
        $dept_check_stmt = $pdo->prepare("SELECT code, name FROM departments WHERE code = ? LIMIT 1");
        $dept_check_stmt->execute([$department_code]);
        $dept_info = $dept_check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dept_info) {
            error_log("無法發送續招報名主任通知: 科系代碼 '$department_code' 不存在於 departments 表中");
            return false;
        }
        
        // 步驟2: 根據科系代碼查找主任的 user_id
        $director_id_stmt = $pdo->prepare("SELECT user_id, department FROM director WHERE department = ? LIMIT 1");
        $director_id_stmt->execute([$department_code]);
        $director_row = $director_id_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$director_row || empty($director_row['user_id'])) {
            error_log("無法發送續招報名主任通知: 科系 '$department_code' 在 director 表中找不到主任");
            return false;
        }
        
        $director_user_id = (int)$director_row['user_id'];
        
        // 步驟3: 根據 user_id 查找主任的 email 和其他資訊
        $user_stmt = $pdo->prepare("SELECT id, name, email, username FROM user WHERE id = ? LIMIT 1");
        $user_stmt->execute([$director_user_id]);
        $director = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$director || empty($director['email'])) {
            error_log("無法發送續招報名主任通知: 主任 user_id $director_user_id 沒有設置 email");
            return false;
        }
        
        $director_name = $director['name'] ?? $director['username'] ?? '主任';
        $director_email = $director['email'];
        $department_name = $dept_info['name'] ?? $department_code;
        $student_name = $student_data['name'] ?? '學生';
        $apply_no = $student_data['apply_no'] ?? '';
        
        // 構建郵件內容（不顯示志願順序）
        $subject = "【康寧大學續招報名】新的報名申請 - {$student_name}";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #1890ff 0%, #096dd9 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #1890ff; }
                .button { display: inline-block; background: #1890ff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 新的續招報名申請</h1>
                    <p>請立即上系統審核</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$director_name}</strong> 主任，您好！</p>
                    
                    <div class='info-box'>
                        <h3>📌 報名資訊</h3>
                        <p><strong>學生姓名：</strong>{$student_name}</p>
                        <p><strong>報名編號：</strong>{$apply_no}</p>
                        <p><strong>分配科系：</strong>{$department_name}</p>
                        <p><strong>提交時間：</strong>" . date('Y-m-d H:i:s') . "</p>
                    </div>
                    
                    <p>此學生已選擇 <strong>{$department_name}</strong>，系統已自動分配給您進行審核。</p>
                    
                    <p>⚠️ <strong>請立即執行以下動作：</strong></p>
                    <ol>
                        <li>立即登入後台系統查看完整的報名資料</li>
                        <li>審核學生的報名資格</li>
                        <li>根據審核結果進行錄取或拒絕</li>
                    </ol>
                    
                    <p style='text-align: center;'>
                        <a href='http://localhost/Topics-backend/frontend/continued_admission_detail.php?id={$application_id}' class='button'>立即前往審核</a>
                    </p>
                    
                    <div class='footer'>
                        <p>此郵件由康寧大學續招報名系統自動發送。</p>
                        <p>如有疑問，請聯繫招生中心。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
        新的續招報名申請

        親愛的 {$director_name} 主任，您好！

        報名資訊：
        - 學生姓名：{$student_name}
        - 報名編號：{$apply_no}
        - 分配科系：{$department_name}
        - 提交時間：" . date('Y-m-d H:i:s') . "

        此學生已選擇 {$department_name}，系統已自動分配給您進行審核。

        請立即前往後台系統審核：http://localhost/Topics-backend/frontend/continued_admission_detail.php?id={$application_id}

        此郵件由康寧大學續招報名系統自動發送。
        ";
        
        error_log("準備發送續招報名主任通知郵件: 主任={$director_name} ({$director_email}), 科系={$department_name} ({$department_code}), 學生={$student_name}");
        $result = sendEmail($director_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("✅ 已成功發送續招報名主任通知郵件給: {$director_name} ({$director_email})");
        } else {
            error_log("❌ 發送續招報名主任通知郵件失敗: {$director_name} ({$director_email})");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("發送續招報名主任通知郵件時發生錯誤: " . $e->getMessage());
        return false;
    }
}

/**
 * 發送續招報名分配通知給老師（主任分配給老師後）
 * 
 * @param mysqli $conn 資料庫連接
 * @param int $teacher_id 老師的 user_id
 * @param array $student_data 學生資料（包含 name, apply_no 等）
 * @param int $application_id 報名ID
 * @return bool 發送是否成功
 */
function sendContinuedAdmissionTeacherNotification($conn, $teacher_id, $student_data, $application_id) {
    try {
        // 查詢老師資訊
        $teacher_stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.username, d.name AS department_name
            FROM user u
            LEFT JOIN teacher t ON u.id = t.user_id
            LEFT JOIN departments d ON t.department = d.code
            WHERE u.id = ?
            LIMIT 1
        ");
        $teacher_stmt->bind_param("i", $teacher_id);
        $teacher_stmt->execute();
        $teacher_result = $teacher_stmt->get_result();
        $teacher = $teacher_result->fetch_assoc();
        
        if (!$teacher || empty($teacher['email'])) {
            error_log("無法發送續招報名老師通知: 找不到老師 (user_id: $teacher_id) 或老師沒有郵箱");
            return false;
        }
        
        $teacher_name = $teacher['name'] ?? $teacher['username'] ?? '老師';
        $teacher_email = $teacher['email'];
        $department_name = $teacher['department_name'] ?? '未知科系';
        $student_name = $student_data['name'] ?? '學生';
        $apply_no = $student_data['apply_no'] ?? '';
        
        // 構建郵件內容
        $subject = "【康寧大學續招報名】評審分配通知 - 請立即上系統評分";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #1890ff 0%, #096dd9 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .alert-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .urgent-box { background: #f8d7da; border: 3px solid #dc3545; padding: 25px; margin: 20px 0; border-radius: 8px; text-align: center; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #1890ff; }
                .button { display: inline-block; background: #1890ff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 續招報名評審分配通知</h1>
                    <p>請立即上系統評分</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$teacher_name}</strong> 老師，您好！</p>
                    
                    <div class='urgent-box'>
                        <h2 style='margin-top: 0; color: #dc3545; font-size: 24px;'>🚨 請立即上系統評分</h2>
                        <p style='font-size: 18px; font-weight: bold; color: #dc3545; margin: 15px 0;'>
                            主任已將續招報名分配給您進行評審
                        </p>
                        <p style='font-size: 16px; color: #721c24; margin: 10px 0;'>
                            ⚠️ 請立即登入系統查看報名資料並進行評分！
                        </p>
                    </div>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #0c5460;'>📌 重要提醒</h3>
                        <p style='font-size: 16px; font-weight: bold; color: #0c5460;'>
                            收到評審分配後，請立即上系統進行評分，不要延遲處理。
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #1890ff;'>📝 報名資訊</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555; width: 120px;'>學生姓名：</td>
                                <td style='padding: 8px 0; color: #333;'>{$student_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>報名編號：</td>
                                <td style='padding: 8px 0; color: #333;'>{$apply_no}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>分配科系：</td>
                                <td style='padding: 8px 0; color: #333;'>{$department_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>分配時間：</td>
                                <td style='padding: 8px 0; color: #333;'>" . date('Y-m-d H:i:s') . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #1890ff;'>📋 請立即執行以下動作</h3>
                        <ol style='padding-left: 20px; font-size: 15px;'>
                            <li style='margin-bottom: 10px;'><strong>立即登入系統</strong>查看完整的報名資料</li>
                            <li style='margin-bottom: 10px;'><strong>立即上系統進行評分</strong>，填寫評分表</li>
                            <li style='margin-bottom: 10px;'>審核學生的報名資格和相關資料</li>
                            <li style='margin-bottom: 10px;'>根據評分標準進行評分</li>
                            <li>如有問題，請與主任聯繫</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/Topics-backend/frontend/continued_admission_detail.php?id={$application_id}&action=score' class='button' style='font-size: 16px; padding: 15px 30px;'>
                            🔗 立即前往系統評分 →
                        </a>
                    </div>
                    
                    <div class='footer'>
                        <p>此郵件由康寧大學續招報名系統自動發送，請勿直接回覆。</p>
                        <p>如有疑問，請聯繫主任或招生中心。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
【重要】續招報名評審分配通知 - 請立即上系統評分

親愛的 {$teacher_name} 老師，您好！

🚨 請立即上系統評分！

主任已將續招報名分配給您進行評審，請立即登入系統查看報名資料並進行評分！

報名資訊：
- 學生姓名：{$student_name}
- 報名編號：{$apply_no}
- 分配科系：{$department_name}
- 分配時間：" . date('Y-m-d H:i:s') . "

⚠️ 請立即執行以下動作：
1. 立即登入系統查看完整的報名資料
2. 立即上系統進行評分，填寫評分表
3. 審核學生的報名資格和相關資料
4. 根據評分標準進行評分
5. 如有問題，請與主任聯繫

請立即前往系統評分：http://localhost/Topics-backend/frontend/continued_admission_detail.php?id={$application_id}&action=score

此郵件由康寧大學續招報名系統自動發送。
        ";
        
        error_log("準備發送續招報名老師通知郵件: 老師={$teacher_name} ({$teacher_email}), 學生={$student_name}, 報名ID={$application_id}");
        $result = sendEmail($teacher_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("✅ 已成功發送續招報名老師通知郵件給: {$teacher_name} ({$teacher_email})");
        } else {
            error_log("❌ 發送續招報名老師通知郵件失敗: {$teacher_name} ({$teacher_email})");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("發送續招報名老師通知郵件時發生錯誤: " . $e->getMessage());
        return false;
    }
}

/**
 * 發送評分截止提醒郵件給未評分的老師
 * 
 * @param mysqli $conn 資料庫連接
 * @param int $teacher_id 老師的 user_id
 * @param array $pending_applications 待評分的報名列表
 * @param string $review_end 審查截止時間
 * @return bool 發送是否成功
 */
function sendScoreDeadlineReminder($conn, $teacher_id, $pending_applications, $review_end) {
    try {
        // 查詢老師資訊
        $teacher_stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.username, d.name AS department_name
            FROM user u
            LEFT JOIN teacher t ON u.id = t.user_id
            LEFT JOIN departments d ON t.department = d.code
            WHERE u.id = ?
            LIMIT 1
        ");
        $teacher_stmt->bind_param("i", $teacher_id);
        $teacher_stmt->execute();
        $teacher_result = $teacher_stmt->get_result();
        $teacher = $teacher_result->fetch_assoc();
        
        if (!$teacher || empty($teacher['email'])) {
            error_log("無法發送評分截止提醒: 找不到老師 (user_id: $teacher_id) 或老師沒有郵箱");
            return false;
        }
        
        $teacher_name = $teacher['name'] ?? $teacher['username'] ?? '老師';
        $teacher_email = $teacher['email'];
        $department_name = $teacher['department_name'] ?? '未知科系';
        
        // 構建待評分學生列表
        $pending_list = '';
        foreach ($pending_applications as $app) {
            $pending_list .= "<li style='margin-bottom: 8px;'>";
            $pending_list .= "<strong>" . htmlspecialchars($app['name']) . "</strong> ";
            $pending_list .= "（報名編號：" . htmlspecialchars($app['apply_no']) . "）";
            $pending_list .= " <a href='http://localhost/Topics-backend/frontend/continued_admission_detail.php?id={$app['id']}&action=score&slot={$app['slot']}' style='color: #1890ff; text-decoration: none; margin-left: 8px;'>[前往評分]</a>";
            $pending_list .= "</li>";
        }
        
        // 構建郵件內容
        $subject = "【康寧大學續招報名】評分截止提醒 - 請盡快完成評分";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #faad14 0%, #ff8c00 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .urgent-box { background: #fff1f0; border: 3px solid #f5222d; padding: 25px; margin: 20px 0; border-radius: 8px; text-align: center; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #faad14; }
                .button { display: inline-block; background: #faad14; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ 評分截止提醒</h1>
                    <p>請盡快完成評分</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$teacher_name}</strong> 老師，您好！</p>
                    
                    <div class='urgent-box'>
                        <h2 style='margin-top: 0; color: #f5222d; font-size: 24px;'>🚨 重要提醒</h2>
                        <p style='font-size: 18px; font-weight: bold; color: #f5222d; margin: 15px 0;'>
                            評分截止時間：<strong>" . date('Y-m-d H:i', strtotime($review_end)) . "</strong>
                        </p>
                        <p style='font-size: 16px; color: #721c24; margin: 10px 0;'>
                            ⚠️ 距離截止時間還有 <strong>1天</strong>，請盡快完成評分！
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #faad14;'>📋 待評分學生列表（共 " . count($pending_applications) . " 位）</h3>
                        <ul style='padding-left: 20px; margin: 0;'>
                            {$pending_list}
                        </ul>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #faad14;'>📋 請立即執行以下動作</h3>
                        <ol style='padding-left: 20px; font-size: 15px;'>
                            <li style='margin-bottom: 10px;'><strong>立即登入系統</strong>查看待評分的學生資料</li>
                            <li style='margin-bottom: 10px;'><strong>立即完成評分</strong>，避免超過截止時間</li>
                            <li style='margin-bottom: 10px;'>如有問題，請與主任聯繫</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/Topics-backend/frontend/continued_admission_list.php' class='button' style='font-size: 16px; padding: 15px 30px;'>
                            🔗 立即前往系統評分 →
                        </a>
                    </div>
                    
                    <div class='footer'>
                        <p>此郵件由康寧大學續招報名系統自動發送，請勿直接回覆。</p>
                        <p>如有疑問，請聯繫主任或招生中心。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
【重要】評分截止提醒 - 請盡快完成評分

親愛的 {$teacher_name} 老師，您好！

🚨 重要提醒

評分截止時間：" . date('Y-m-d H:i', strtotime($review_end)) . "
⚠️ 距離截止時間還有 1天，請盡快完成評分！

待評分學生列表（共 " . count($pending_applications) . " 位）：
" . implode("\n", array_map(function($app) {
    return "- " . $app['name'] . "（報名編號：" . $app['apply_no'] . "）";
}, $pending_applications)) . "

請立即前往系統評分：http://localhost/Topics-backend/frontend/continued_admission_list.php

此郵件由康寧大學續招報名系統自動發送。
        ";
        
        error_log("準備發送評分截止提醒郵件: 老師={$teacher_name} ({$teacher_email}), 待評分數量=" . count($pending_applications));
        $result = sendEmail($teacher_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("✅ 已成功發送評分截止提醒郵件給: {$teacher_name} ({$teacher_email})");
        } else {
            error_log("❌ 發送評分截止提醒郵件失敗: {$teacher_name} ({$teacher_email})");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("發送評分截止提醒郵件時發生錯誤: " . $e->getMessage());
        return false;
    }
}

/**
 * 發送評分截止提醒給招生中心：列出尚未評分完成的科系，提醒催繳
 *
 * @param mysqli $conn 資料庫連接
 * @param array $departments_pending 尚未評分完成的科系列表 [ ['department_code' => '...', 'department_name' => '...', 'pending_count' => N, 'deadline' => '...'], ... ]
 * @param string $deadline_display 截止時間顯示用
 * @return int 成功發送數量
 */
function sendScoreDeadlineReminderToAdmissionCenter($conn, $departments_pending, $deadline_display) {
    if (empty($departments_pending)) {
        return 0;
    }
    try {
        // 查詢招生中心人員（ADM、STA）且有 email 的帳號
        $r1 = 'ADM';
        $r2 = 'STA';
        $r3 = '管理員';
        $r4 = '行政人員';
        $sql = "SELECT id, name, email, username FROM user WHERE role IN (?, ?, ?, ?) AND email IS NOT NULL AND TRIM(email) != '' AND status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $r1, $r2, $r3, $r4);
        $stmt->execute();
        $result = $stmt->get_result();
        $recipients = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['email'])) {
                $recipients[] = ['name' => $row['name'] ?? $row['username'] ?? '招生中心', 'email' => trim($row['email'])];
            }
        }
        $stmt->close();
        if (empty($recipients)) {
            error_log("無法發送招生中心提醒: 找不到具備 email 的招生中心人員（ADM/STA）");
            return 0;
        }
        // 構建科系列表
        $dept_list = '';
        foreach ($departments_pending as $d) {
            $name = htmlspecialchars($d['department_name'] ?? $d['department_code'] ?? '');
            $count = (int)($d['pending_count'] ?? 0);
            $dept_list .= "<li style='margin-bottom: 8px;'><strong>{$name}</strong>：尚有 <strong>{$count}</strong> 筆待評分</li>";
        }
        $subject = "【康寧大學續招報名】評分催繳提醒 - 以下科系尚未評分完成";
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #faad14 0%, #ff8c00 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .urgent-box { background: #fff7e6; border: 2px solid #faad14; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #faad14; }
                .button { display: inline-block; background: #faad14; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 評分催繳提醒</h1>
                    <p>以下科系尚未評分完成，請協助催繳</p>
                </div>
                <div class='content'>
                    <p>招生中心您好，</p>
                    <div class='urgent-box'>
                        <p style='margin: 0 0 10px 0;'><strong>評分截止時間：</strong>{$deadline_display}</p>
                        <p style='margin: 0;'>以下科系尚有老師未完成評分，請提醒各科系主任／老師盡快完成評分。</p>
                    </div>
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #faad14;'>尚未評分完成的科系</h3>
                        <ul style='padding-left: 20px; margin: 0;'>
                            {$dept_list}
                        </ul>
                    </div>
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/Topics-backend/frontend/continued_admission_list.php' class='button'>前往續招名單</a>
                    </div>
                    <div class='footer'>
                        <p>此郵件由康寧大學續招報名系統自動發送。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        $dept_text = '';
        foreach ($departments_pending as $d) {
            $name = $d['department_name'] ?? $d['department_code'] ?? '';
            $count = (int)($d['pending_count'] ?? 0);
            $dept_text .= "- {$name}：尚有 {$count} 筆待評分\n";
        }
        $altBody = "評分催繳提醒\n\n評分截止時間：{$deadline_display}\n\n以下科系尚未評分完成，請協助催繳：\n\n{$dept_text}\n請前往續招名單：http://localhost/Topics-backend/frontend/continued_admission_list.php\n\n此郵件由康寧大學續招報名系統自動發送。";
        $sent = 0;
        foreach ($recipients as $r) {
            $res = sendEmail($r['email'], $subject, $body, $altBody);
            if ($res) {
                $sent++;
                error_log("✅ 已發送評分催繳提醒給招生中心: {$r['name']} ({$r['email']})");
            } else {
                error_log("❌ 發送評分催繳提醒失敗: {$r['email']}");
            }
        }
        return $sent;
    } catch (Exception $e) {
        error_log("發送招生中心評分催繳提醒時發生錯誤: " . $e->getMessage());
        return 0;
    }
}
?>

