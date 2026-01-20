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
?>

