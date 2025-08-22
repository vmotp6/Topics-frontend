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
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #fff; /* 移除漸層，純白背景 */
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: #333;
        padding-top: 100px; /* 避開固定在頂部的 header */
    }

    main { /* 讓主要內容可撐開高度，與 footer 相容 */
        flex: 1;
    }

    .coop-container {
        max-width: 1200px;
        margin: 40px auto;
        background: #fff;
        padding: 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    .h11 {
        color: #222;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.8em;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }

    input[type="text"], 
    input[type="email"], 
    input[type="tel"], 
    input[type="date"], 
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        transition: border 0.3s ease;
        box-sizing: border-box;
    }

    input:focus, textarea:focus, select:focus {
        border-color: #0056b3;
        outline: none;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    input[type="file"] {
        width: 100%;
        padding: 12px;
        border: 1px dashed #aaa;
        border-radius: 4px;
        background-color: #fafafa;
    }

    .submit-btn {
        background: #0056b3;
        color: white;
        padding: 14px 24px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s ease;
    }

    .submit-btn:hover {
        background: #004494;
    }

    .message {
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        font-size: 14px;
    }

    .message.success {
        background: #e6f4ea;
        color: #1e4620;
        border: 1px solid #9ccc9c;
    }

    .message.error {
        background: #fdecea;
        color: #611a15;
        border: 1px solid #f5c6cb;
    }

    .required {
        color: #e74c3c;
    }

    .form-row,
    .form-row-3,
    .form-row-2-1 {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .form-row .form-group,
    .form-row-3 .form-group,
    .form-row-2-1 .form-group {
        flex: 1;
    }

    .form-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .form-table th,
    .form-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .form-table th {
        background: #f7f7f7;
        font-weight: 600;
        width: 20%;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 5px 0;
    }

    .checkbox-group input {
        cursor: pointer;
    }

    .section-title {
        color: #333;
        border-bottom: 2px solid #0056b3;
        padding-bottom: 6px;
        margin: 25px 0 15px 0;
        font-size: 1.2em;
        font-weight: 600;
    }

    .sub-section {
        margin: 15px 0;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fafafa;
    }

    .sub-section h4 {
        margin: 0 0 10px 0;
        font-size: 1em;
        font-weight: 600;
        color: #333;
    }

    .attachment-info {
        color: #333;
        font-weight: 600;
        margin-bottom: 10px;
        padding: 8px;
        background: #f1f9ff;
        border-left: 3px solid #0056b3;
    }

    .attachment-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .attachment-list li {
        padding: 6px 10px;
        margin: 5px 0;
        background: #fafafa;
        border-left: 3px solid #0056b3;
        border-radius: 4px;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .form-row,
        .form-row-3,
        .form-row-2-1 {
            flex-direction: column;
        }
    }
    </style>

</head>
<body>
    <?php include("share/header.php"); ?>
    <main>
    <div class="coop-container">
        <h1 class="h11">康寧大學產學合作申請表</h1>
        
        <div id="message"></div>
        
        <form id="cooperationForm" enctype="multipart/form-data">
            <!-- 基本申請資訊 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="application_date">申請日期 <span class="required">*</span></label>
                    <input type="date" id="application_date" name="application_date" required>
                </div>
                <div class="form-group">
                    <label for="approval_number">核定編號</label>
                    <input type="text" id="approval_number" name="approval_number" placeholder="由行政人員填寫">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="department">申請系/科別 <span class="required">*</span></label>
                    <select id="department" name="department" required>
                        <option value="">請選擇系/科別</option>
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
                <div class="form-group">
                    <label for="principal_investigator">主持人 <span class="required">*</span></label>
                    <input type="text" id="principal_investigator" name="principal_investigator" required>
                </div>
            </div>

            <div class="form-group">
                <label>是否已詳閱本校產學合作辦法 <span class="required">*</span></label>
                <div class="checkbox-group">
                    <input type="radio" id="regulations_yes" name="regulations_read" value="yes" required>
                    <label for="regulations_yes">是</label>
                    <input type="radio" id="regulations_no" name="regulations_read" value="no" required>
                    <label for="regulations_no">否</label>
                </div>
            </div>

            <!-- 申請類別 -->
            <h3 class="section-title">申請類別</h3>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="category_research" name="application_categories[]" value="research">
                    <label for="category_research">研究發展及應用（專題研究、技術服務、專利申請等）</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="category_education" name="application_categories[]" value="education">
                    <label for="category_education">教育培訓合作（研習、實習、訓練等）</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="category_intellectual" name="application_categories[]" value="intellectual">
                    <label for="category_intellectual">智慧財產權運用事項</label>
                </div>
            </div>

            <!-- 計畫詳細資訊 -->
            <h3 class="section-title">計畫詳細資訊</h3>
            <table class="form-table">
                <tr>
                    <th>計畫金額</th>
                    <td>
                        $ <input type="number" id="project_amount" name="project_amount" class="amount-input" min="0" step="0.01" required>
                        <br><br>
                        行政管理費：<input type="number" id="admin_fee_percentage" name="admin_fee_percentage" class="percentage-input" value="10" readonly> %
                        <br><br>
                        成果歸屬：
                        <div class="checkbox-group">
                            <input type="checkbox" id="outcome_university" name="outcome_university">
                            <label for="outcome_university">本校</label>
                            <input type="number" id="university_percentage" name="university_percentage" class="percentage-input" min="0" max="100"> %
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="outcome_company" name="outcome_company">
                            <label for="outcome_company">廠商</label>
                            <input type="number" id="company_percentage" name="company_percentage" class="percentage-input" min="0" max="100"> %
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>合作廠商</th>
                    <td>
                        <input type="text" id="company_name" name="company_name" placeholder="廠商名稱" required>
                        <br><br>
                        負責人姓名：<input type="text" id="company_contact" name="company_contact" required>
                        <br><br>
                        電話：<input type="tel" id="company_phone" name="company_phone" required>
                    </td>
                </tr>
                <tr>
                    <th>計畫名稱</th>
                    <td>
                        <input type="text" id="project_title" name="project_title" required>
                    </td>
                </tr>
                <tr>
                    <th>預期成果</th>
                    <td>
                        <textarea id="expected_outcomes" name="expected_outcomes" rows="4" required></textarea>
                    </td>
                </tr>
                <tr>
                    <th>計畫期程</th>
                    <td>
                        <textarea id="project_timeline" name="project_timeline" rows="4" required></textarea>
                    </td>
                </tr>
            </table>

            <!-- 智慧財產權 -->
            <h3 class="section-title">智慧財產權</h3>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="radio" id="ip_yes" name="has_intellectual_property" value="yes" required>
                    <label for="ip_yes">有</label>
                    <input type="radio" id="ip_no" name="has_intellectual_property" value="no" required>
                    <label for="ip_no">沒有</label>
                </div>
            </div>

            <div id="ip_details" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th>類型</th>
                        <th>比例</th>
                        <th>專利</th>
                        <th>商標</th>
                        <th>著作權</th>
                        <th>營業秘密</th>
                    </tr>
                    <tr>
                        <td>本校(%)</td>
                        <td><input type="number" id="university_ip_percentage" name="university_ip_percentage" class="percentage-input" min="0" max="100"></td>
                        <td><input type="text" id="university_patent" name="university_patent"></td>
                        <td><input type="text" id="university_trademark" name="university_trademark"></td>
                        <td><input type="text" id="university_copyright" name="university_copyright"></td>
                        <td><input type="text" id="university_trade_secret" name="university_trade_secret"></td>
                    </tr>
                    <tr>
                        <td>廠商(%)</td>
                        <td><input type="number" id="company_ip_percentage" name="company_ip_percentage" class="percentage-input" min="0" max="100"></td>
                        <td><input type="text" id="company_patent" name="company_patent"></td>
                        <td><input type="text" id="company_trademark" name="company_trademark"></td>
                        <td><input type="text" id="company_copyright" name="company_copyright"></td>
                        <td><input type="text" id="company_trade_secret" name="company_trade_secret"></td>
                    </tr>
                    <tr>
                        <td>主持人(%)</td>
                        <td><input type="number" id="investigator_ip_percentage" name="investigator_ip_percentage" class="percentage-input" min="0" max="100"></td>
                        <td><input type="text" id="investigator_patent" name="investigator_patent"></td>
                        <td><input type="text" id="investigator_trademark" name="investigator_trademark"></td>
                        <td><input type="text" id="investigator_copyright" name="investigator_copyright"></td>
                        <td><input type="text" id="investigator_trade_secret" name="investigator_trade_secret"></td>
                    </tr>
                </table>
            </div>

            <!-- 其他問題 -->
            <h3 class="section-title">其他問題</h3>
            <div class="sub-section">
                <h4>未來是否有技術移轉</h4>
                <div class="checkbox-group">
                    <input type="radio" id="tech_transfer_yes" name="future_tech_transfer" value="yes">
                    <label for="tech_transfer_yes">是，技術移轉金$</label>
                    <input type="number" id="tech_transfer_amount" name="tech_transfer_amount" class="amount-input" min="0" step="0.01">
                    <input type="radio" id="tech_transfer_no" name="future_tech_transfer" value="no">
                    <label for="tech_transfer_no">否</label>
                </div>
            </div>

            <div class="sub-section">
                <h4>是否有衍生利益金</h4>
                <div class="checkbox-group">
                    <input type="radio" id="benefits_yes" name="has_derived_benefits" value="yes">
                    <label for="benefits_yes">是，利益金$</label>
                    <input type="number" id="benefits_amount" name="benefits_amount" class="amount-input" min="0" step="0.01">
                    <input type="radio" id="benefits_no" name="has_derived_benefits" value="no">
                    <label for="benefits_no">否</label>
                </div>
            </div>

            <div class="sub-section">
                <h4>場地使用相關</h4>
                <div class="checkbox-group">
                    <input type="checkbox" id="use_venue" name="use_university_venue">
                    <label for="use_venue">是否使用學校場地</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="venue_fees" name="venue_fees_in_proposal">
                    <label for="venue_fees">是否於計畫書編列場地相關費用</label>
                </div>
            </div>

            <div class="sub-section">
                <h4>其他</h4>
                <div class="checkbox-group">
                    <input type="checkbox" id="employ_disadvantaged" name="employ_disadvantaged_students">
                    <label for="employ_disadvantaged">是否聘用弱勢生</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="use_standard_contract" name="use_standard_contract">
                    <label for="use_standard_contract">是否使用公用版合約</label>
                </div>
            </div>

            <!-- 繳交附件 -->
            <h3 class="section-title">繳交附件</h3>
            <div class="form-group">
                <p class="attachment-info">以下附件為必繳文件，請務必上傳：</p>
                <ul class="attachment-list">
                    <li>產學合作合約書（含計畫內容、經費、期程等）</li>
                    <li>產學合作計畫書（含經費編列、人力規劃等）</li>
                </ul>
            </div>

            <!-- 檔案上傳 -->
            <h3 class="section-title">檔案上傳</h3>
            <div class="file-upload-section">
                <div class="form-group">
                    <label for="contract_file">產學合作合約書 (PDF格式) <span class="required">*</span></label>
                    <input type="file" id="contract_file" name="contract_file" accept=".pdf" required>
                </div>
                <div class="form-group">
                    <label for="proposal_file">產學合作計畫書 (PDF格式) <span class="required">*</span></label>
                    <input type="file" id="proposal_file" name="proposal_file" accept=".pdf" required>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                📤 提交申請
            </button>
        </form>
    </div>
    </main>

    <script>
        // 智慧財產權區塊顯示/隱藏
        document.getElementById('ip_yes').addEventListener('change', function() {
            document.getElementById('ip_details').style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('ip_no').addEventListener('change', function() {
            document.getElementById('ip_details').style.display = this.checked ? 'none' : 'block';
        });

        // 技術移轉金額輸入控制
        document.getElementById('tech_transfer_yes').addEventListener('change', function() {
            const amountInput = document.getElementById('tech_transfer_amount');
            amountInput.disabled = !this.checked;
            if (!this.checked) amountInput.value = '';
        });

        // 衍生利益金輸入控制
        document.getElementById('benefits_yes').addEventListener('change', function() {
            const amountInput = document.getElementById('benefits_amount');
            amountInput.disabled = !this.checked;
            if (!this.checked) amountInput.value = '';
        });

        document.getElementById('cooperationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            
            // 檢查申請類別是否至少選擇一項
            const categories = document.querySelectorAll('input[name="application_categories[]"]:checked');
            if (categories.length === 0) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '請至少選擇一項申請類別';
                return;
            }
            
            // 檢查檔案大小
            const contractFile = document.getElementById('contract_file').files[0];
            const proposalFile = document.getElementById('proposal_file').files[0];
            
            if (contractFile && contractFile.size > 10 * 1024 * 1024) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '合約書檔案大小不能超過10MB';
                return;
            }
            
            if (proposalFile && proposalFile.size > 10 * 1024 * 1024) {
                messageDiv.className = 'message error';
                messageDiv.textContent = '計畫書檔案大小不能超過10MB';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';
            
            const formData = new FormData(this);
            formData.append('teacher_username', '<?php echo $username; ?>');
            
            fetch('/backend/auto_fix_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.className = 'message success';
                    messageDiv.textContent = data.message;
                    document.getElementById('cooperationForm').reset();
                    document.getElementById('ip_details').style.display = 'none';
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

        // 設定申請日期預設為今天
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('application_date').value = today;
    </script>
    <?php include("share/footer.php"); ?>
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>

