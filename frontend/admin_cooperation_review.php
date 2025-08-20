<?php
session_start();

// 檢查是否已登入且為行政人員身份
if (!isset($_SESSION['username']) || $_SESSION['role'] !== '學校行政人員') {
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
    <title>產學合作案審核管理</title>
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
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
        }

        .filter-section {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        select, input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-btn:hover {
            background: #2980b9;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .applications-table th,
        .applications-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .applications-table th {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }

        .applications-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-view:hover { background-color: #138496; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject:hover { background-color: #c82333; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #34495e;
        }

        .detail-value {
            flex: 1;
            color: #2c3e50;
        }

        .review-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }

        .review-form textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }

        .review-buttons {
            margin-top: 15px;
            text-align: right;
        }

        .review-buttons button {
            margin-left: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .applications-table {
                font-size: 12px;
            }
            
            .applications-table th,
            .applications-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>📋 產學合作案審核管理</h1>
            <a href="admin.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 14px; transition: background-color 0.3s ease;">
                ← 返回主頁面
            </a>
        </div>
        
        <!-- 篩選區域 -->
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-group">
                    <label>狀態篩選:</label>
                    <select id="statusFilter">
                        <option value="">全部狀態</option>
                        <option value="pending">待審核</option>
                        <option value="approved">已通過</option>
                        <option value="rejected">已拒絕</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>科系篩選:</label>
                    <select id="departmentFilter">
                        <option value="">全部科系</option>
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
                <div class="filter-group">
                    <label>申請日期:</label>
                    <input type="date" id="dateFilter">
                </div>
                <button class="filter-btn" onclick="loadApplications()">🔍 篩選</button>
                <button class="filter-btn" onclick="resetFilters()">🔄 重置</button>
            </div>
        </div>

        <!-- 申請表列表 -->
        <div id="applicationsList">
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>申請編號</th>
                        <th>申請人</th>
                        <th>科系</th>
                        <th>專案名稱</th>
                        <th>合作企業</th>
                        <th>預算金額</th>
                        <th>申請日期</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="applicationsTableBody">
                    <!-- 動態載入資料 -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 詳細資料模態框 -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="applicationDetail">
                <!-- 動態載入詳細資料 -->
            </div>
        </div>
    </div>

    <script>
        let currentApplicationId = null;

        // 載入申請表列表
        function loadApplications() {
            const statusFilter = document.getElementById('statusFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;

            fetch(`/backend/cooperation_list_api.php?status=${statusFilter}&department=${departmentFilter}&date=${dateFilter}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('applicationsTableBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="no-data">暫無申請資料</td></tr>';
                        return;
                    }

                    data.forEach(app => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>#${app.id}</td>
                            <td>${app.teacher_username}</td>
                            <td>${app.department}</td>
                            <td>${app.project_title}</td>
                            <td>${app.company_name}</td>
                            <td>NT$ ${parseFloat(app.budget_amount).toLocaleString()}</td>
                            <td>${new Date(app.created_at).toLocaleDateString('zh-TW')}</td>
                            <td><span class="status-badge status-${app.status}">${getStatusText(app.status)}</span></td>
                            <td>
                                <button class="action-btn btn-view" onclick="viewDetail(${app.id})">查看</button>
                                ${app.status === 'pending' ? `
                                    <button class="action-btn btn-approve" onclick="submitReview(${app.id}, 'approved')">通過</button>
                                    <button class="action-btn btn-reject" onclick="submitReview(${app.id}, 'rejected')">拒絕</button>
                                ` : ''}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('applicationsTableBody').innerHTML = 
                        '<tr><td colspan="9" class="no-data">載入資料時發生錯誤</td></tr>';
                });
        }

        // 取得狀態文字
        function getStatusText(status) {
            const statusMap = {
                'pending': '待審核',
                'approved': '已通過',
                'rejected': '已拒絕'
            };
            return statusMap[status] || status;
        }

        // 查看詳細資料
        function viewDetail(applicationId) {
            fetch(`/backend/cooperation_detail_api.php?id=${applicationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const app = data.application;
                        document.getElementById('applicationDetail').innerHTML = `
                            <h2>申請表詳細資料 #${app.id}</h2>
                            
                            <div class="detail-section">
                                <h3>👨‍🏫 申請人資訊</h3>
                                <div class="detail-row">
                                    <div class="detail-label">申請人帳號:</div>
                                    <div class="detail-value">${app.teacher_username}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">所屬科系:</div>
                                    <div class="detail-value">${app.department}</div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3>📊 專案資訊</h3>
                                <div class="detail-row">
                                    <div class="detail-label">專案名稱:</div>
                                    <div class="detail-value">${app.project_title}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">專案時程:</div>
                                    <div class="detail-value">${app.project_timeline}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">專案金額:</div>
                                    <div class="detail-value">NT$ ${parseFloat(app.project_amount).toLocaleString()}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">預期成果:</div>
                                    <div class="detail-value">${app.expected_outcomes}</div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3>🏢 合作企業資訊</h3>
                                <div class="detail-row">
                                    <div class="detail-label">企業名稱:</div>
                                    <div class="detail-value">${app.company_name}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">聯絡人:</div>
                                    <div class="detail-value">${app.company_contact}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">聯絡電話:</div>
                                    <div class="detail-value">${app.company_phone}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">聯絡電話:</div>
                                    <div class="detail-value">${app.company_phone}</div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3>📎 申請表檔案</h3>
                                <div class="detail-row">
                                    <div class="detail-label">合約書:</div>
                                    <div class="detail-value">
                                        <a href="${app.contract_file_path}" target="_blank" style="color: #3498db; text-decoration: none;">
                                            📄 查看合約書
                                        </a>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">計畫書:</div>
                                    <div class="detail-value">
                                        <a href="${app.proposal_file_path}" target="_blank" style="color: #3498db; text-decoration: none;">
                                            📄 查看計畫書
                                        </a>
                                    </div>
                                </div>
                            </div>

                            ${app.status === 'pending' ? `
                                <div class="review-form">
                                    <h3>📝 審核意見</h3>
                                    <textarea id="reviewComment" placeholder="請輸入審核意見（選填）"></textarea>
                                    <div class="review-buttons">
                                        <button class="action-btn btn-approve" onclick="submitDetailReview(${app.id}, 'approved')">✅ 通過申請</button>
                                        <button class="action-btn btn-reject" onclick="submitDetailReview(${app.id}, 'rejected')">❌ 拒絕申請</button>
                                    </div>
                                </div>
                            ` : `
                                <div class="detail-section">
                                    <h3>📝 審核結果</h3>
                                    <div class="detail-row">
                                        <div class="detail-label">審核狀態:</div>
                                        <div class="detail-value">
                                            <span class="status-badge status-${app.status}">${getStatusText(app.status)}</span>
                                        </div>
                                    </div>
                                    ${app.admin_comment ? `
                                        <div class="detail-row">
                                            <div class="detail-label">審核意見:</div>
                                            <div class="detail-value">${app.admin_comment}</div>
                                        </div>
                                    ` : ''}
                                    ${app.review_date ? `
                                        <div class="detail-row">
                                            <div class="detail-label">審核日期:</div>
                                            <div class="detail-value">${new Date(app.review_date).toLocaleString('zh-TW')}</div>
                                        </div>
                                    ` : ''}
                                </div>
                            `}
                        `;
                        
                        document.getElementById('detailModal').style.display = 'block';
                        currentApplicationId = applicationId;
                    } else {
                        alert('載入詳細資料失敗: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('載入詳細資料時發生錯誤');
                });
        }

        // 提交審核結果（從表格直接審核）
        function submitReview(applicationId, status) {
            if (confirm(`確定要${status === 'approved' ? '通過' : '拒絕'}這個申請嗎？`)) {
                fetch('/backend/cooperation_review_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: applicationId,
                        status: status,
                        comment: '',
                        admin_username: '<?php echo $username; ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('審核完成！');
                        loadApplications(); // 重新載入列表
                    } else {
                        alert('審核失敗: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('提交審核時發生錯誤');
                });
            }
        }

        // 提交詳細審核結果（從詳細資料模態框）
        function submitDetailReview(applicationId, status) {
            const comment = document.getElementById('reviewComment')?.value || '';
            
            fetch('/backend/cooperation_review_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    status: status,
                    comment: comment,
                    admin_username: '<?php echo $username; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('審核完成！');
                    document.getElementById('detailModal').style.display = 'none';
                    loadApplications(); // 重新載入列表
                } else {
                    alert('審核失敗: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('提交審核時發生錯誤');
            });
        }

        // 重置篩選條件
        function resetFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            document.getElementById('dateFilter').value = '';
            loadApplications();
        }

        // 關閉模態框
        document.querySelector('.close').onclick = function() {
            document.getElementById('detailModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // 頁面載入時執行
        document.addEventListener('DOMContentLoaded', function() {
            loadApplications();
        });
    </script>

    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
