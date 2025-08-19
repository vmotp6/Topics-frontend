<?php
session_start();

// 檢查是否已登入且為老師身份
if (!isset($_SESSION['username']) || $_SESSION['role'] !== '老師') {
    header('Location: one.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>產學合作案申請表上傳</title>
    <?php include("share/header.php"); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="text"], 
        input[type="email"], 
        input[type="tel"], 
        input[type="date"], 
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #3498db;
            border-radius: 6px;
            background-color: #f8f9fa;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #3498db;
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #2980b9;
        }

        .submit-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .required {
            color: #e74c3c;
        }

        .form-row {
            display: flex;
            gap: 25px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-row-3 {
            display: flex;
            gap: 20px;
        }

        .form-row-3 .form-group {
            flex: 1;
        }

        .form-row-2-1 {
            display: flex;
            gap: 20px;
        }

        .form-row-2-1 .form-group:nth-child(1),
        .form-row-2-1 .form-group:nth-child(2) {
            flex: 1;
        }

        .form-row-2-1 .form-group:nth-child(3) {
            flex: 0.5;
        }

        @media (max-width: 768px) {
            .form-row,
            .form-row-3,
            .form-row-2-1 {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 產學合作案申請表上傳</h1>
        
        <div id="message"></div>
        
        <form id="cooperationForm" enctype="multipart/form-data">
            <!-- 老師基本資訊 -->
            <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">👨‍🏫 申請人資訊</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="teacher_name">申請人姓名 <span class="required">*</span></label>
                    <input type="text" id="teacher_name" name="teacher_name" required>
                </div>
                <div class="form-group">
                    <label for="department">所屬科系 <span class="required">*</span></label>
                    <select id="department" name="department" required>
                        <option value="">請選擇科系</option>
                        <option value="資訊工程學系">資訊工程學系</option>
                        <option value="電機工程學系">電機工程學系</option>
                        <option value="機械工程學系">機械工程學系</option>
                        <option value="化學工程學系">化學工程學系</option>
                        <option value="土木工程學系">土木工程學系</option>
                        <option value="工業工程學系">工業工程學系</option>
                        <option value="材料科學與工程學系">材料科學與工程學系</option>
                        <option value="生物科技學系">生物科技學系</option>
                        <option value="應用化學系">應用化學系</option>
                        <option value="應用數學系">應用數學系</option>
                        <option value="物理學系">物理學系</option>
                        <option value="企業管理學系">企業管理學系</option>
                        <option value="會計學系">會計學系</option>
                        <option value="財務金融學系">財務金融學系</option>
                        <option value="國際企業學系">國際企業學系</option>
                        <option value="經濟學系">經濟學系</option>
                        <option value="統計學系">統計學系</option>
                        <option value="外國語文學系">外國語文學系</option>
                        <option value="中國文學系">中國文學系</option>
                        <option value="歷史學系">歷史學系</option>
                        <option value="哲學系">哲學系</option>
                        <option value="社會學系">社會學系</option>
                    </select>
                </div>
            </div>

            <!-- 專案資訊 -->
            <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px;">📊 專案資訊</h3>
            
            <div class="form-group">
                <label for="project_title">專案名稱 <span class="required">*</span></label>
                <input type="text" id="project_title" name="project_title" required>
            </div>

            <div class="form-group">
                <label for="project_description">專案描述 <span class="required">*</span></label>
                <textarea id="project_description" name="project_description" placeholder="請詳細描述專案內容、目標、預期效益等..." required></textarea>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="project_start_date">專案開始日期 <span class="required">*</span></label>
                    <input type="date" id="project_start_date" name="project_start_date" required>
                </div>
                <div class="form-group">
                    <label for="project_end_date">專案結束日期 <span class="required">*</span></label>
                    <input type="date" id="project_end_date" name="project_end_date" required>
                </div>
                <div class="form-group">
                    <label for="budget_amount">預算金額 (新台幣) <span class="required">*</span></label>
                    <input type="number" id="budget_amount" name="budget_amount" min="0" step="0.01" placeholder="請輸入預算金額" required>
                </div>
            </div>

            <div class="form-group">
                <label for="expected_outcomes">預期成果 <span class="required">*</span></label>
                <textarea id="expected_outcomes" name="expected_outcomes" placeholder="請描述專案預期達成的成果、產出等..." required></textarea>
            </div>

            <!-- 合作企業資訊 -->
            <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px;">🏢 合作企業資訊</h3>
            
            <div class="form-group">
                <label for="company_name">企業名稱 <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" required>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label for="company_contact">聯絡人姓名 <span class="required">*</span></label>
                    <input type="text" id="company_contact" name="company_contact" required>
                </div>
                <div class="form-group">
                    <label for="company_phone">聯絡電話 <span class="required">*</span></label>
                    <input type="tel" id="company_phone" name="company_phone" required>
                </div>
                <div class="form-group">
                    <label for="company_email">聯絡信箱 <span class="required">*</span></label>
                    <input type="email" id="company_email" name="company_email" required>
                </div>
            </div>

            <!-- 檔案上傳 -->
            <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px;">📎 申請表檔案</h3>
            
            <div class="form-group">
                <label for="application_file">申請表檔案 (PDF格式) <span class="required">*</span></label>
                <input type="file" id="application_file" name="application_file" accept=".pdf" required>
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">請上傳PDF格式的申請表檔案，檔案大小不超過10MB</small>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                📤 提交申請
            </button>
        </form>
    </div>

    <script>
        document.getElementById('cooperationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 檢查日期邏輯
            const startDate = new Date(document.getElementById('project_start_date').value);
            const endDate = new Date(document.getElementById('project_end_date').value);
            
            if (endDate <= startDate) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '專案結束日期必須晚於開始日期';
                return;
            }
            
            // 檢查檔案大小
            const fileInput = document.getElementById('application_file');
            const file = fileInput.files[0];
            if (file && file.size > 10 * 1024 * 1024) { // 10MB
                messageDiv.className = 'message error';
                messageDiv.textContent = '檔案大小不能超過10MB';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            const formData = new FormData(this);
            formData.append('teacher_username', '<?php echo $username; ?>');
            
            fetch('backend/cooperation_upload_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    document.getElementById('cooperationForm').reset();
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = data.message;
                }
            })
            .catch(error => {
                messageDiv.className = 'message error';
                messageDiv.textContent = '提交失敗，請稍後再試';
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 提交申請';
            });
        });

        // 設定最小日期為今天
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('project_start_date').min = today;
        document.getElementById('project_end_date').min = today;
    </script>

    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
