<?php
// 簡單的郵件測試頁面
require_once 'config.php';
require_once 'config/email_notification_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>郵件測試 - 康寧大學</title>
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .result { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 郵件通知系統測試</h1>
            <p>康寧大學招生推薦系統</p>
        </div>

        <div class="test-section">
            <h3>🔧 系統狀態檢查</h3>
            <div class="result info">
                <strong>SMTP 設定:</strong><br>
                • 主機: <?php echo SMTP_HOST; ?><br>
                • 端口: <?php echo SMTP_PORT; ?><br>
                • 發件人: <?php echo SMTP_FROM_EMAIL; ?><br>
                • 加密: <?php echo SMTP_SECURE; ?><br>
                • 狀態: ✅ 已設定
            </div>
        </div>

        <div class="test-section">
            <h3>📧 測試郵件發送</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="test_email">測試郵件地址:</label>
                    <input type="email" id="test_email" name="test_email" value="vichuang2005@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label for="test_name">推薦人姓名:</label>
                    <input type="text" id="test_name" name="test_name" value="張小明" required>
                </div>
                
                <div class="form-group">
                    <label for="template_type">郵件模板:</label>
                    <select id="template_type" name="template_type" required>
                        <option value="recommendation_success">推薦申請提交成功通知</option>
                        <option value="approval_notification">審核通過通知</option>
                        <option value="enrollment_notification">入學確認通知</option>
                    </select>
                </div>
                
                <button type="submit" name="send_test" class="btn btn-success">📤 發送測試郵件</button>
            </form>
        </div>

        <?php
        if (isset($_POST['send_test'])) {
            $test_email = $_POST['test_email'];
            $test_name = $_POST['test_name'];
            $template_type = $_POST['template_type'];
            
            // 準備測試資料
            $test_data = [
                'student_name' => '尤世全',
                'recommender_name' => $test_name,
                'recommender_student_id' => 'A123456789',
                'recommender_department' => '資訊管理科',
                'student_school' => '中正國中',
                'student_grade' => '國三',
                'submission_time' => date('Y-m-d H:i:s'),
                'approval_time' => date('Y-m-d H:i:s'),
                'enrollment_time' => date('Y-m-d H:i:s')
            ];
            
            echo '<div class="test-section">';
            echo '<h3>📤 測試結果</h3>';
            
            try {
                $result = sendNotificationEmail($test_email, $test_name, $template_type, $test_data);
                
                if ($result) {
                    echo '<div class="result success">';
                    echo '✅ <strong>郵件發送成功！</strong><br>';
                    echo "收件人（推薦人）: {$test_email}<br>";
                    echo "推薦人姓名: {$test_name}<br>";
                    echo "被推薦學生: 尤世全<br>";
                    echo "模板: {$template_type}<br>";
                    echo "發送時間: " . date('Y-m-d H:i:s');
                    echo '</div>';
                } else {
                    echo '<div class="result error">';
                    echo '❌ <strong>郵件發送失敗！</strong><br>';
                    echo '請檢查SMTP設定和網路連線。';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="result error">';
                echo '❌ <strong>發送錯誤:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>

        <div class="test-section">
            <h3>📋 郵件模板預覽</h3>
            <div class="result info">
                <strong>可用的郵件模板:</strong><br>
                • <strong>推薦申請提交成功通知</strong> - 發送給推薦人，確認推薦申請已成功提交<br>
                • <strong>審核通過通知</strong> - 發送給推薦人，通知其推薦的學生申請已通過審核<br>
                • <strong>入學確認通知</strong> - 發送給推薦人，通知其推薦的學生已正式入學
            </div>
            
            <button onclick="window.open('test_email_notification.php', '_blank')" class="btn btn-warning">
                📄 查看完整模板預覽
            </button>
        </div>

        <div class="test-section">
            <h3>🔗 相關功能</h3>
            <a href="admission_recommend.php" class="btn">📊 推薦管理系統</a>
            <a href="test_email_notification.php" class="btn">📧 完整郵件測試</a>
            <a href="index.php" class="btn">🏠 返回首頁</a>
        </div>
    </div>
</body>
</html>
