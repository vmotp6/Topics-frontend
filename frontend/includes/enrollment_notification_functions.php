<?php
/**
 * 就讀意願分配通知郵件功能
 */

// 載入郵件發送功能
require_once __DIR__ . '/email_functions.php';

/**
 * 發送給主任的通知郵件（自動分配）
 * 
 * @param PDO $pdo 資料庫連接
 * @param string $department_code 科系代碼
 * @param array $student_data 學生資料
 * @return bool 發送是否成功
 */
function sendDirectorAssignmentNotification($pdo, $department_code, $student_data) {
    try {
        // 步驟1: 先驗證科系代碼是否存在
        $dept_check_stmt = $pdo->prepare("SELECT code, name FROM departments WHERE code = ? LIMIT 1");
        $dept_check_stmt->execute([$department_code]);
        $dept_info = $dept_check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dept_info) {
            error_log("無法發送主任通知: 科系代碼 '$department_code' 不存在於 departments 表中");
            return false;
        }
        
        error_log("步驟1完成: 找到科系 '$department_code' (名稱: " . ($dept_info['name'] ?? 'N/A') . ")");
        
        // 步驟2: 根據科系代碼查找主任的 user_id
        $director_id_stmt = $pdo->prepare("SELECT user_id, department FROM director WHERE department = ? LIMIT 1");
        $director_id_stmt->execute([$department_code]);
        $director_row = $director_id_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$director_row || empty($director_row['user_id'])) {
            error_log("無法發送主任通知: 科系 '$department_code' 在 director 表中找不到主任 (user_id)");
            // 嘗試查詢所有該科系的主任記錄以便調試
            $all_directors_stmt = $pdo->prepare("SELECT user_id, department FROM director WHERE department = ?");
            $all_directors_stmt->execute([$department_code]);
            $all_directors = $all_directors_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("調試: director 表中科系 '$department_code' 的所有記錄: " . json_encode($all_directors, JSON_UNESCAPED_UNICODE));
            return false;
        }
        
        $director_user_id = (int)$director_row['user_id'];
        error_log("步驟2完成: 找到主任 user_id = $director_user_id (科系: " . ($director_row['department'] ?? 'N/A') . ")");
        
        // 步驟3: 根據 user_id 查找主任的 email 和其他資訊
        $user_stmt = $pdo->prepare("SELECT id, name, email, username FROM user WHERE id = ? LIMIT 1");
        $user_stmt->execute([$director_user_id]);
        $director = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$director) {
            error_log("無法發送主任通知: user_id $director_user_id 在 user 表中不存在");
            return false;
        }
        
        if (empty($director['email'])) {
            error_log("無法發送主任通知: 主任 user_id $director_user_id (姓名: " . ($director['name'] ?? $director['username'] ?? 'N/A') . ") 沒有設置 email");
            return false;
        }
        
        error_log("步驟3完成: 找到主任 email = " . $director['email'] . " (姓名: " . ($director['name'] ?? $director['username'] ?? 'N/A') . ")");
        
        $director_name = $director['name'] ?? $director['username'] ?? '主任';
        $director_email = $director['email'];
        $department_name = $dept_info['name'] ?? $department_code;
        $student_name = $student_data['name'] ?? '學生';
        
        error_log("準備發送主任通知郵件: 主任={$director_name} ({$director_email}), 科系={$department_name} ({$department_code}), 學生={$student_name}");
        
        // 構建郵件內容
        $subject = "【康寧大學】新的就讀意願表單 - 請立即上系統聯絡";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .urgent-box { background: #f8d7da; border: 3px solid #dc3545; padding: 25px; margin: 20px 0; border-radius: 8px; text-align: center; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 新的就讀意願表單</h1>
                    <p>請立即上系統聯絡</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$director_name}</strong> 主任，您好！</p>
                    
                    <div class='urgent-box'>
                        <h2 style='margin-top: 0; color: #dc3545; font-size: 24px;'>🚨 請立即上系統聯絡</h2>
                        <p style='font-size: 18px; font-weight: bold; color: #dc3545; margin: 15px 0;'>
                            系統已自動將新的就讀意願表單分配給您
                        </p>
                        <p style='font-size: 16px; color: #721c24; margin: 10px 0;'>
                            ⚠️ 請立即登入系統查看學生資料並進行聯絡！
                        </p>
                    </div>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #856404;'>📌 重要提醒</h3>
                        <p style='font-size: 16px; font-weight: bold; color: #856404;'>
                            收到學生資料後，請立即上系統進行聯絡，不要延遲處理。
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #667eea;'>📝 學生基本資料</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555; width: 120px;'>學生姓名：</td>
                                <td style='padding: 8px 0; color: #333;'>{$student_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>分配科系：</td>
                                <td style='padding: 8px 0; color: #333;'>{$department_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>聯絡電話：</td>
                                <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars($student_data['phone1'] ?? '未提供') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>電子郵件：</td>
                                <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars($student_data['email'] ?? '未提供') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>提交時間：</td>
                                <td style='padding: 8px 0; color: #333;'>" . date('Y-m-d H:i:s') . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #667eea;'>📋 請立即執行以下動作</h3>
                        <ol style='padding-left: 20px; font-size: 15px;'>
                            <li style='margin-bottom: 10px;'><strong>立即登入後台系統</strong>查看完整的學生資料</li>
                            <li style='margin-bottom: 10px;'><strong>立即上系統進行聯絡</strong>，記錄聯絡內容</li>
                            <li style='margin-bottom: 10px;'>與學生或家長聯絡，了解就讀意願</li>
                            <li style='margin-bottom: 10px;'>可選擇自行聯絡或分配給科系老師</li>
                            <li>記錄每次聯絡的內容和結果</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . (defined('BACKEND_BASE_URL') ? BACKEND_BASE_URL : 'http://127.0.0.1/Topics-backend/frontend') . "/enrollment_list.php' class='button' style='font-size: 16px; padding: 15px 30px;'>
                            🔗 立即前往後台系統聯絡 →
                        </a>
                    </div>
                    
                    <div class='footer'>
                        <p>此郵件由康寧大學招生平台自動發送，請勿直接回覆。</p>
                        <p>如有疑問，請聯繫招生組。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
【重要】新的就讀意願表單通知 - 請立即上系統聯絡

親愛的 {$director_name} 主任，您好！

🚨 請立即上系統聯絡！

系統已自動將新的就讀意願表單分配給您，請立即登入系統查看學生資料並進行聯絡！

學生基本資料：
- 學生姓名：{$student_name}
- 分配科系：{$department_name}
- 聯絡電話：" . ($student_data['phone1'] ?? '未提供') . "
- 電子郵件：" . ($student_data['email'] ?? '未提供') . "
- 提交時間：" . date('Y-m-d H:i:s') . "

⚠️ 請立即執行以下動作：
1. 立即登入後台系統查看完整的學生資料
2. 立即上系統進行聯絡，記錄聯絡內容
3. 與學生或家長聯絡，了解就讀意願
4. 可選擇自行聯絡或分配給科系老師
5. 記錄每次聯絡的內容和結果

請立即前往後台系統：http://127.0.0.1/Topics-backend/frontend/enrollment_list.php

此郵件由康寧大學招生平台自動發送。
        ";
        
        error_log("準備調用 sendEmail: 收件人={$director_email}, 主題={$subject}");
        $result = sendEmail($director_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("✅ 已成功發送主任通知郵件給: {$director_name} ({$director_email})");
        } else {
            error_log("❌ 發送主任通知郵件失敗: {$director_name} ({$director_email})");
            error_log("請檢查: 1) SMTP配置是否正確 2) 主任email是否有效 3) 郵件服務器是否可訪問");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("發送主任通知郵件時發生錯誤: " . $e->getMessage());
        return false;
    }
}

/**
 * 發送給老師的通知郵件（手動分配）
 * 
 * @param mysqli $conn 資料庫連接
 * @param int $teacher_id 老師的 user_id
 * @param array $student_data 學生資料
 * @return bool 發送是否成功
 */
function sendTeacherAssignmentNotification($conn, $teacher_id, $student_data) {
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
            error_log("無法發送老師通知: 找不到老師 (user_id: $teacher_id) 或老師沒有郵箱");
            return false;
        }
        
        $teacher_name = $teacher['name'] ?? $teacher['username'] ?? '老師';
        $teacher_email = $teacher['email'];
        $department_name = $teacher['department_name'] ?? '未知科系';
        $student_name = $student_data['name'] ?? '學生';
        
        // 構建郵件內容
        $subject = "【康寧大學】學生分配通知 - 請立即上系統聯絡";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Microsoft JhengHei', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .alert-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .urgent-box { background: #f8d7da; border: 3px solid #dc3545; padding: 25px; margin: 20px 0; border-radius: 8px; text-align: center; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>👨‍🏫 學生分配通知</h1>
                    <p>請立即上系統聯絡</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$teacher_name}</strong> 老師，您好！</p>
                    
                    <div class='urgent-box'>
                        <h2 style='margin-top: 0; color: #dc3545; font-size: 24px;'>🚨 請立即上系統聯絡</h2>
                        <p style='font-size: 18px; font-weight: bold; color: #dc3545; margin: 15px 0;'>
                            主任已將學生分配給您
                        </p>
                        <p style='font-size: 16px; color: #721c24; margin: 10px 0;'>
                            ⚠️ 請立即登入系統查看學生資料並進行聯絡！
                        </p>
                    </div>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #0c5460;'>📌 重要提醒</h3>
                        <p style='font-size: 16px; font-weight: bold; color: #0c5460;'>
                            收到學生分配後，請立即上系統進行聯絡，不要延遲處理。
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #667eea;'>📝 學生基本資料</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555; width: 120px;'>學生姓名：</td>
                                <td style='padding: 8px 0; color: #333;'>{$student_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>所屬科系：</td>
                                <td style='padding: 8px 0; color: #333;'>{$department_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>聯絡電話：</td>
                                <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars($student_data['phone1'] ?? '未提供') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>電子郵件：</td>
                                <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars($student_data['email'] ?? '未提供') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; font-weight: bold; color: #555;'>分配時間：</td>
                                <td style='padding: 8px 0; color: #333;'>" . date('Y-m-d H:i:s') . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='margin-top: 0; color: #667eea;'>📋 請立即執行以下動作</h3>
                        <ol style='padding-left: 20px; font-size: 15px;'>
                            <li style='margin-bottom: 10px;'><strong>立即登入系統</strong>查看完整的學生資料</li>
                            <li style='margin-bottom: 10px;'><strong>立即上系統進行聯絡</strong>，記錄聯絡內容</li>
                            <li style='margin-bottom: 10px;'>與學生或家長聯絡，了解就讀意願</li>
                            <li style='margin-bottom: 10px;'>記錄每次聯絡的內容和結果</li>
                            <li>如有問題，請與主任聯繫</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . (defined('BACKEND_BASE_URL') ? BACKEND_BASE_URL : 'http://127.0.0.1/Topics-backend/frontend') . "/enrollment_list.php' class='button' style='font-size: 16px; padding: 15px 30px;'>
                            🔗 立即前往系統聯絡 →
                        </a>
                    </div>
                    
                    <div class='footer'>
                        <p>此郵件由康寧大學招生平台自動發送，請勿直接回覆。</p>
                        <p>如有疑問，請聯繫主任或招生組。</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $altBody = "
【重要】學生分配通知 - 請立即上系統聯絡

親愛的 {$teacher_name} 老師，您好！

🚨 請立即上系統聯絡！

主任已將學生分配給您，請立即登入系統查看學生資料並進行聯絡！

學生基本資料：
- 學生姓名：{$student_name}
- 所屬科系：{$department_name}
- 聯絡電話：" . ($student_data['phone1'] ?? '未提供') . "
- 電子郵件：" . ($student_data['email'] ?? '未提供') . "
- 分配時間：" . date('Y-m-d H:i:s') . "

⚠️ 請立即執行以下動作：
1. 立即登入系統查看完整的學生資料
2. 立即上系統進行聯絡，記錄聯絡內容
3. 與學生或家長聯絡，了解就讀意願
4. 記錄每次聯絡的內容和結果
5. 如有問題，請與主任聯繫

請立即前往系統：http://127.0.0.1/Topics-backend/frontend/enrollment_list.php

此郵件由康寧大學招生平台自動發送。
        ";
        
        $result = sendEmail($teacher_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("已成功發送老師通知郵件給: {$teacher_name} ({$teacher_email})");
        } else {
            error_log("發送老師通知郵件失敗: {$teacher_name} ({$teacher_email})");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("發送老師通知郵件時發生錯誤: " . $e->getMessage());
        return false;
    }
}
?>

