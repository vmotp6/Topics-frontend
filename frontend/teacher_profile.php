<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <?php include("share/header.php"); ?>
    <title>老師個人資料</title>
    <link rel="stylesheet" href="assets/csp/QA.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 120px auto 40px;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-title {
            text-align: center;
            color: #003366;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #003366;
            font-weight: 600;
            font-size: 16px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }

        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
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

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .back-btn:hover {
            color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <h1 class="profile-title">個人資料設定</h1>
        
        <form id="profileForm">
            <div class="form-group">
                <label for="department">科系</label>
                <select id="department" name="department" required>
                    <option value="" disabled selected>請選擇科系</option>
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
                    <option value="心理學系">心理學系</option>
                    <option value="政治學系">政治學系</option>
                    <option value="法律學系">法律學系</option>
                    <option value="教育學系">教育學系</option>
                    <option value="體育學系">體育學系</option>
                    <option value="藝術學系">藝術學系</option>
                    <option value="音樂學系">音樂學系</option>
                    <option value="戲劇學系">戲劇學系</option>
                    <option value="傳播學系">傳播學系</option>
                    <option value="新聞學系">新聞學系</option>
                    <option value="廣告學系">廣告學系</option>
                    <option value="公共關係學系">公共關係學系</option>
                    <option value="圖書資訊學系">圖書資訊學系</option>
                    <option value="資訊管理學系">資訊管理學系</option>
                    <option value="其他">其他</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="phone">電話</label>
                <input type="tel" id="phone" name="phone" placeholder="請輸入電話號碼" required>
            </div>
            
            <button type="submit" class="submit-btn">保存資料</button>
        </form>
        
        <div id="message"></div>
        
        <a href="teacher.php" class="back-btn">← 返回老師頁面</a>
    </div>

    <script>
        // 頁面載入時檢查是否已有個人資料
        window.addEventListener('load', function() {
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            if (username) {
                fetch(`http://localhost:5000/teacher/profile/${username}`)
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('尚未填寫個人資料');
                        }
                    })
                    .then(data => {
                        // 如果已有資料，填入表單
                        document.getElementById('department').value = data.department;
                        document.getElementById('phone').value = data.phone;
                    })
                    .catch(error => {
                        console.log('尚未填寫個人資料或發生錯誤');
                    });
            }
        });

        // 表單提交
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>';
            const department = document.getElementById('department').value;
            const phone = document.getElementById('phone').value;
            
            const formData = new FormData();
            formData.append('username', username);
            formData.append('department', department);
            formData.append('phone', phone);
            
            fetch('http://localhost:5000/teacher/profile', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.json().then(data => {
                    const messageDiv = document.getElementById('message');
                    if (response.ok) {
                        messageDiv.className = 'message success';
                        messageDiv.textContent = data.message;
                    } else {
                        messageDiv.className = 'message error';
                        messageDiv.textContent = data.message;
                    }
                });
            })
            .catch(error => {
                const messageDiv = document.getElementById('message');
                messageDiv.className = 'message error';
                messageDiv.textContent = '保存失敗，請稍後再試。';
            });
        });
    </script>
    
    <?php include("share/ai_widget.php"); ?>
</body>

</html> 