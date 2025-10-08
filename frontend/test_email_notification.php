<?php
// 測試郵件通知功能
require_once 'session_config.php';
require_once 'config/email_notification_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>郵件通知測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid #dee2e6;
        }
        .success { border-color: #28a745; background: #d4edda; }
        .error { border-color: #dc3545; background: #f8d7da; }
        .info { border-color: #17a2b8; background: #d1ecf1; }
        .test-form { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .btn { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>📧 郵件通知測試</h1>
    
    <div class="test-section">
        <h3>📊 系統狀態</h3>
        <div class="info">
            <p><strong>SMTP 設定:</strong></p>
            <p>Host: <?php echo SMTP_HOST; ?></p>
            <p>Port: <?php echo SMTP_PORT; ?></p>
            <p>Username: <?php echo SMTP_USERNAME; ?></p>
            <p>From: <?php echo SMTP_FROM_EMAIL; ?></p>
        </div>
    </div>
    
    <?php
    $test_result = '';
    $test_type = '';
    
    if ($_POST && isset($_POST['test_email'])) {
        $test_email = $_POST['test_email'];
        $test_name = $_POST['test_name'];
        $notification_type = $_POST['notification_type'];
        
        // 準備測試數據
        $test_data = [
            'student_name' => $test_name,
            'recommender_name' => '測試推薦人',
            'recommender_student_id' => 'TEST001',
            'recommender_department' => '資訊管理科',
            'student_school' => '測試國中',
            'student_grade' => '國三',
            'submission_time' => date('Y-m-d H:i:s'),
            'approval_time' => date('Y-m-d H:i:s'),
            'enrollment_time' => date('Y-m-d H:i:s')
        ];
        
        $template_name = $notification_type === 'approval' ? 'approval_notification' : 
                        ($notification_type === 'enrollment' ? 'enrollment_notification' : 'recommendation_success');
        
        $email_sent = sendNotificationEmail($test_email, $test_name, $template_name, $test_data);
        
        if ($email_sent) {
            $test_result = "✅ 測試郵件發送成功！請檢查 {$test_email} 的收件匣。";
            $test_type = 'success';
        } else {
            $test_result = "❌ 測試郵件發送失敗，請檢查SMTP設定和網路連線。";
            $test_type = 'error';
        }
    }
    ?>
    
    <?php if ($test_result): ?>
    <div class="test-section <?php echo $test_type; ?>">
        <h3>測試結果</h3>
        <p><?php echo $test_result; ?></p>
    </div>
    <?php endif; ?>
    
    <div class="test-section">
        <h3>🧪 發送測試郵件</h3>
        <div class="test-form">
            <form method="POST">
                <div class="form-group">
                    <label for="test_email">測試郵件地址:</label>
                    <input type="email" id="test_email" name="test_email" 
                           value="<?php echo $_POST['test_email'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="test_name">測試姓名:</label>
                    <input type="text" id="test_name" name="test_name" 
                           value="<?php echo $_POST['test_name'] ?? '測試學生'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="notification_type">通知類型:</label>
                    <select id="notification_type" name="notification_type">
                        <option value="recommendation_success" <?php echo ($_POST['notification_type'] ?? '') === 'recommendation_success' ? 'selected' : ''; ?>>
                            推薦申請提交成功通知
                        </option>
                        <option value="approval" <?php echo ($_POST['notification_type'] ?? '') === 'approval' ? 'selected' : ''; ?>>
                            審核通過通知
                        </option>
                        <option value="enrollment" <?php echo ($_POST['notification_type'] ?? '') === 'enrollment' ? 'selected' : ''; ?>>
                            入學確認通知
                        </option>
                    </select>
                </div>
                
                <button type="submit" name="test_email" class="btn btn-success">
                    📧 發送測試郵件
                </button>
            </form>
        </div>
    </div>
    
    <div class="test-section">
        <h3>📋 郵件模板預覽</h3>
        <div class="info">
            <p><strong>可用的郵件模板:</strong></p>
            <ul>
                <li><strong>推薦申請提交成功通知</strong> - 發送給推薦人，確認推薦申請已成功提交</li>
                <li><strong>審核通過通知</strong> - 發送給推薦人，通知其推薦的學生申請已通過審核</li>
                <li><strong>入學確認通知</strong> - 發送給推薦人，通知其推薦的學生已正式入學</li>
            </ul>
        </div>
    </div>
    
    <div class="test-section">
        <h3>🔧 設定說明</h3>
        <div class="info">
            <p><strong>要使用郵件通知功能，請確保:</strong></p>
            <ol>
                <li>SMTP 設定正確（在 config.php 中）</li>
                <li>Gmail 應用程式密碼已設定</li>
                <li>網路連線正常</li>
                <li>收件人郵件地址有效</li>
            </ol>
        </div>
    </div>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="admission_recommend.php" class="btn">返回推薦報名頁面</a>
        <a href="index.php" class="btn">返回首頁</a>
    </div>
</body>
</html>
