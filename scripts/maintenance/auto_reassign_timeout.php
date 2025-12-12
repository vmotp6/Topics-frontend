<?php

// 載入資料庫設定 (請根據您的實際路徑調整)
require_once __DIR__ . '/../../config.php'; 

// 設定時區
date_default_timezone_set('Asia/Taipei');

try {
    // 建立資料庫連線 (如果 config.php 沒有回傳連線物件，請自行建立 PDO)
    // 假設這裡使用與 submit_enrollment.php 相同的連線方式
    $host = 'localhost';
    $dbname = 'topics_good';
    $db_username = 'root';
    $db_password = ''; // 請填入您的密碼
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "開始檢查超時案件...\n";

    // 1. 找出符合條件的案件：
    //    - 已經分配 (assigned_department 不為空)
    //    - 分配時間超過 3 天 (assigned_at < NOW - 3 days)
    //    - 目前志願序小於 3 (還有下一個志願可以轉)
    //    - (選項) 狀態還不是「已結案」或「已聯繫」(根據您的業務邏輯，這裡假設只檢查尚未結案的)
    
    $sql = "SELECT * FROM enrollment_intention 
            WHERE assigned_department IS NOT NULL 
            AND assigned_at < DATE_SUB(NOW(), INTERVAL 3 DAY) 
            AND current_choice_order < 3
            -- AND status NOT IN ('已結案', '已聯繫') -- 如果您有狀態欄位，建議加上這行
            ";
    
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $enrollment_id = $row['id'];
        $next_order = $row['current_choice_order'] + 1;
        
        echo "處理案件 ID: $enrollment_id (目前志願: {$row['current_choice_order']}) -> 嘗試轉至志願 $next_order\n";

        // 2. 查詢下一個志願的科系代碼
        $choice_stmt = $pdo->prepare("SELECT department_code FROM enrollment_choices 
                                      WHERE enrollment_id = ? AND choice_order = ?");
        $choice_stmt->execute([$enrollment_id, $next_order]);
        $next_choice = $choice_stmt->fetch();

        if ($next_choice && !empty($next_choice['department_code'])) {
            $next_dept_code = $next_choice['department_code'];

            // 3. 查詢該新科系的主任
            $director_stmt = $pdo->prepare("SELECT user_id FROM director WHERE department = ? LIMIT 1");
            $director_stmt->execute([$next_dept_code]);
            $director = $director_stmt->fetch();

            if ($director) {
                $new_director_id = $director['user_id'];

                // 4. 更新分配資訊
                $update_sql = "UPDATE enrollment_intention 
                               SET assigned_department = :dept,
                                   assigned_teacher_id = :teacher,
                                   assigned_at = NOW(), -- 重置計時器
                                   current_choice_order = :order
                               WHERE id = :id";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    ':dept' => $next_dept_code,
                    ':teacher' => $new_director_id,
                    ':order' => $next_order,
                    ':id' => $enrollment_id
                ]);

                echo "  -> 成功轉單給 $next_dept_code (主任ID: $new_director_id)\n";
            } else {
                echo "  -> 失敗: 找不到科系 $next_dept_code 的主任資料\n";
            }
        } else {
            echo "  -> 失敗: 沒有設定第 $next_order 志願，或志願為空\n";
            // 可選：如果你希望沒有下一志願時就標記為「待人工處理」，可以在這裡更新狀態
        }
    }

    echo "檢查完成。\n";

} catch (Exception $e) {
    echo "發生錯誤: " . $e->getMessage() . "\n";
}