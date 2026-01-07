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
        // 查詢該科系的主任資訊
        $director_stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.username, d.name AS department_name
            FROM director dir
            INNER JOIN user u ON dir.user_id = u.id
            INNER JOIN departments d ON dir.department = d.code
            WHERE dir.department = ?
            LIMIT 1
        ");
        $director_stmt->execute([$department_code]);
        $director = $director_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$director || empty($director['email'])) {
            error_log("無法發送主任通知: 科系 $department_code 找不到主任或主任沒有郵箱");
            return false;
        }
        
        $director_name = $director['name'] ?? $director['username'] ?? '主任';
        $director_email = $director['email'];
        $department_name = $director['department_name'] ?? $department_code;
        $student_name = $student_data['name'] ?? '學生';
        
        // 構建郵件內容
        $subject = "【康寧大學】新的就讀意願表單 - 請盡快聯絡";
        
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
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 新的就讀意願表單</h1>
                    <p>請盡快與學生聯絡</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$director_name}</strong> 主任，您好！</p>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #856404;'>⚠️ 重要通知</h3>
                        <p style='font-size: 16px; font-weight: bold; color: #856404;'>
                            系統已自動將新的就讀意願表單分配給您，請盡快與學生聯絡！
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
                        <h3 style='margin-top: 0; color: #667eea;'>📋 後續動作</h3>
                        <ol style='padding-left: 20px;'>
                            <li>請登入後台系統查看完整的學生資料</li>
                            <li>盡快與學生或家長聯絡，了解就讀意願</li>
                            <li>可選擇自行聯絡或分配給科系老師</li>
                            <li>記錄每次聯絡的內容和結果</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . (defined('BACKEND_BASE_URL') ? BACKEND_BASE_URL : 'http://127.0.0.1/Topics-backend/frontend') . "/enrollment_list.php' class='button'>
                            前往後台查看 →
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
新的就讀意願表單通知

親愛的 {$director_name} 主任，您好！

系統已自動將新的就讀意願表單分配給您，請盡快與學生聯絡！

學生基本資料：
- 學生姓名：{$student_name}
- 分配科系：{$department_name}
- 聯絡電話：" . ($student_data['phone1'] ?? '未提供') . "
- 電子郵件：" . ($student_data['email'] ?? '未提供') . "
- 提交時間：" . date('Y-m-d H:i:s') . "

後續動作：
1. 請登入後台系統查看完整的學生資料
2. 盡快與學生或家長聯絡，了解就讀意願
3. 可選擇自行聯絡或分配給科系老師
4. 記錄每次聯絡的內容和結果

請前往後台查看：http://127.0.0.1/Topics-backend/frontend/enrollment_list.php

此郵件由康寧大學招生平台自動發送。
        ";
        
        $result = sendEmail($director_email, $subject, $body, $altBody);
        
        if ($result) {
            error_log("已成功發送主任通知郵件給: {$director_name} ({$director_email})");
        } else {
            error_log("發送主任通知郵件失敗: {$director_name} ({$director_email})");
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
        $subject = "【康寧大學】學生分配通知 - 請盡快聯絡";
        
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
                .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>👨‍🏫 學生分配通知</h1>
                    <p>您收到新的學生分配</p>
                </div>
                <div class='content'>
                    <p>親愛的 <strong>{$teacher_name}</strong> 老師，您好！</p>
                    
                    <div class='alert-box'>
                        <h3 style='margin-top: 0; color: #0c5460;'>📌 分配通知</h3>
                        <p style='font-size: 16px; font-weight: bold; color: #0c5460;'>
                            主任已將學生分配給您，請盡快與學生聯絡！
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
                        <h3 style='margin-top: 0; color: #667eea;'>📋 後續動作</h3>
                        <ol style='padding-left: 20px;'>
                            <li>請登入系統查看完整的學生資料</li>
                            <li>盡快與學生或家長聯絡，了解就讀意願</li>
                            <li>記錄每次聯絡的內容和結果</li>
                            <li>如有問題，請與主任聯繫</li>
                        </ol>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='" . (defined('BACKEND_BASE_URL') ? BACKEND_BASE_URL : 'http://127.0.0.1/Topics-backend/frontend') . "/enrollment_list.php' class='button'>
                            前往後台查看 →
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
學生分配通知

親愛的 {$teacher_name} 老師，您好！

主任已將學生分配給您，請盡快與學生聯絡！

學生基本資料：
- 學生姓名：{$student_name}
- 所屬科系：{$department_name}
- 聯絡電話：" . ($student_data['phone1'] ?? '未提供') . "
- 電子郵件：" . ($student_data['email'] ?? '未提供') . "
- 分配時間：" . date('Y-m-d H:i:s') . "

後續動作：
1. 請登入系統查看完整的學生資料
2. 盡快與學生或家長聯絡，了解就讀意願
3. 記錄每次聯絡的內容和結果
4. 如有問題，請與主任聯繫

請前往後台查看：http://127.0.0.1/Topics-backend/frontend/enrollment_list.php

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

