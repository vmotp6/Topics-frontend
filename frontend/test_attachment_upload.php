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
    <title>附件上傳測試</title>
    <?php include("share/header.php"); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .attachment-info {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: linear-gradient(135deg, #e8f4fd 0%, #d1ecf1 100%);
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }

        .attachment-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .attachment-list li {
            padding: 8px 15px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #667eea;
            color: #2c3e50;
            font-weight: 500;
        }

        .attachment-list li::before {
            content: '📎';
            margin-right: 8px;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .required {
            color: #e74c3c;
            font-weight: bold;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
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

        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="cooperation_upload.php" class="back-btn">← 返回申請表</a>
        
        <h1>📎 附件上傳測試</h1>
        
        <div id="message"></div>

        <form id="attachmentForm">
            <!-- 繳交附件說明 -->
            <div class="form-group">
                <div class="attachment-info">以下附件為必繳文件，請務必上傳：</div>
                <ul class="attachment-list">
                    <li>產學合作合約書（含計畫內容、經費、期程等）</li>
                    <li>產學合作計畫書（含經費編列、人力規劃等）</li>
                </ul>
            </div>

            <!-- 檔案上傳 -->
            <div class="form-group">
                <label for="contract_file">產學合作合約書 (PDF格式) <span class="required">*</span></label>
                <input type="file" id="contract_file" name="contract_file" accept=".pdf" required>
            </div>
            
            <div class="form-group">
                <label for="proposal_file">產學合作計畫書 (PDF格式) <span class="required">*</span></label>
                <input type="file" id="proposal_file" name="proposal_file" accept=".pdf" required>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                📤 測試上傳
            </button>
        </form>
    </div>

    <script>
        document.getElementById('attachmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 檢查檔案是否已選擇
            const contractFile = document.getElementById('contract_file').files[0];
            const proposalFile = document.getElementById('proposal_file').files[0];
            
            if (!contractFile) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請選擇產學合作合約書檔案';
                return;
            }
            
            if (!proposalFile) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請選擇產學合作計畫書檔案';
                return;
            }
            
            // 檢查檔案格式
            if (!contractFile.name.toLowerCase().endsWith('.pdf')) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '合約書必須為PDF格式';
                return;
            }
            
            if (!proposalFile.name.toLowerCase().endsWith('.pdf')) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '計畫書必須為PDF格式';
                return;
            }
            
            // 檢查檔案大小
            if (contractFile.size > 10 * 1024 * 1024) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '合約書檔案大小不能超過10MB';
                return;
            }
            
            if (proposalFile.size > 10 * 1024 * 1024) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '計畫書檔案大小不能超過10MB';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '上傳中...';
            
            const formData = new FormData(this);
            formData.append('teacher_username', '<?php echo $username; ?>');
            formData.append('test_mode', 'true');
            
            fetch('/backend/auto_fix_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = '✅ 附件上傳測試成功！檔案已正確處理。';
                } else {
                    messageDiv.className = 'message error';
                    messageDiv.textContent = '❌ 上傳失敗: ' + data.message;
                }
            })
            .catch(error => {
                messageDiv.className = 'message error';
                messageDiv.textContent = '❌ 上傳時發生錯誤';
                console.error('Error:', error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 測試上傳';
            });
        });
    </script>
</body>
</html>
