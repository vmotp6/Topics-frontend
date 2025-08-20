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
    <title>產學合作申請表測試頁面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], 
        input[type="date"], 
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        .checkbox-group {
            margin: 10px 0;
        }
        .checkbox-group label {
            display: inline-block;
            margin-right: 15px;
            font-weight: normal;
        }
        .submit-btn {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .submit-btn:hover {
            background: #0056b3;
        }
        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 產學合作申請表測試頁面</h1>
        
        <div id="message"></div>
        
        <form id="testForm">
            <div class="form-group">
                <label>老師帳號 *</label>
                <input type="text" name="teacher_username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label>申請日期 *</label>
                <input type="date" name="application_date" required>
            </div>
            
            <div class="form-group">
                <label>科系 *</label>
                <input type="text" name="department" value="資訊工程系" required>
            </div>
            
            <div class="form-group">
                <label>主持人 *</label>
                <input type="text" name="principal_investigator" value="測試主持人" required>
            </div>
            
            <div class="form-group">
                <label>已讀規定 *</label>
                <select name="regulations_read" required>
                    <option value="yes">是</option>
                    <option value="no">否</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>申請類別 *</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="application_categories[]" value="技術合作" checked> 技術合作</label>
                    <label><input type="checkbox" name="application_categories[]" value="研究合作"> 研究合作</label>
                    <label><input type="checkbox" name="application_categories[]" value="人才培育"> 人才培育</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>計畫金額 *</label>
                <input type="number" name="project_amount" value="100000" required>
            </div>
            
            <div class="form-group">
                <label>公司名稱 *</label>
                <input type="text" name="company_name" value="測試公司" required>
            </div>
            
            <div class="form-group">
                <label>聯絡人 *</label>
                <input type="text" name="company_contact" value="測試聯絡人" required>
            </div>
            
            <div class="form-group">
                <label>聯絡電話 *</label>
                <input type="text" name="company_phone" value="0912345678" required>
            </div>
            
            <div class="form-group">
                <label>專案名稱 *</label>
                <input type="text" name="project_title" value="測試專案" required>
            </div>
            
            <div class="form-group">
                <label>預期成果 *</label>
                <textarea name="expected_outcomes" required>測試成果</textarea>
            </div>
            
            <div class="form-group">
                <label>專案時程 *</label>
                <textarea name="project_timeline" required>6個月</textarea>
            </div>
            
            <div class="form-group">
                <label>智慧財產權 *</label>
                <select name="has_intellectual_property" required>
                    <option value="no">否</option>
                    <option value="yes">是</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>合約書 (PDF) *</label>
                <input type="file" name="contract_file" accept=".pdf" required>
            </div>
            
            <div class="form-group">
                <label>計畫書 (PDF) *</label>
                <input type="file" name="proposal_file" accept=".pdf" required>
            </div>
            
            <button type="submit" class="submit-btn" id="submitBtn">📤 測試提交</button>
        </form>
        
        <div id="debugInfo" class="debug-info" style="display: none;"></div>
    </div>

    <script>
        // 設定申請日期預設為今天
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="application_date"]').value = today;
        
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            const debugInfo = document.getElementById('debugInfo');
            
            // 檢查申請類別
            const categories = document.querySelectorAll('input[name="application_categories[]"]:checked');
            if (categories.length === 0) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請至少選擇一項申請類別';
                return;
            }
            
            // 檢查檔案
            const contractFile = document.querySelector('input[name="contract_file"]').files[0];
            const proposalFile = document.querySelector('input[name="proposal_file"]').files[0];
            
            if (!contractFile || !proposalFile) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請上傳合約書和計畫書';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            messageDiv.textContent = '';
            debugInfo.style.display = 'none';
            
            const formData = new FormData(this);
            
            // 記錄請求資訊
            console.log('提交的資料:', {
                teacher_username: formData.get('teacher_username'),
                application_date: formData.get('application_date'),
                department: formData.get('department'),
                project_title: formData.get('project_title'),
                contract_file: contractFile.name,
                proposal_file: proposalFile.name
            });
            
            // 先嘗試測試API
            fetch('backend/test_cooperation_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        messageDiv.className = 'message success';
                        messageDiv.textContent = data.message;
                        document.getElementById('testForm').reset();
                        document.querySelector('input[name="application_date"]').value = today;
                    } else {
                        messageDiv.className = 'message error';
                        messageDiv.textContent = data.message;
                    }
                    
                    // 顯示調試資訊
                    debugInfo.style.display = 'block';
                    debugInfo.textContent = `API回應:\n${JSON.stringify(data, null, 2)}`;
                    
                } catch (e) {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '回應格式錯誤';
                    
                    debugInfo.style.display = 'block';
                    debugInfo.textContent = `原始回應:\n${text}`;
                }
            })
            .catch(error => {
                messageDiv.className = 'message error';
                messageDiv.textContent = '網路錯誤：' + error.message;
                console.error('Error:', error);
                
                debugInfo.style.display = 'block';
                debugInfo.textContent = `錯誤詳情:\n${error.toString()}`;
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 測試提交';
            });
        });
    </script>
</body>
</html>
