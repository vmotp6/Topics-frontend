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
    <title>我的產學合作案申請狀態</title>
    <?php include("share/header.php"); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
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

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.approved {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.rejected {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
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

        .btn-view:hover {
            background-color: #138496;
        }

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

        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
        }

        .new-application-btn {
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .new-application-btn:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            .stats-section {
                grid-template-columns: 1fr;
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
        <h1>📋 我的產學合作案申請狀態</h1>
        
        <a href="cooperation_upload.php" class="new-application-btn">📝 新增申請</a>
        
        <!-- 統計資訊 -->
        <div class="stats-section">
            <div class="stat-card pending">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">待審核</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number" id="approvedCount">0</div>
                <div class="stat-label">已通過</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-number" id="rejectedCount">0</div>
                <div class="stat-label">已拒絕</div>
            </div>
        </div>

        <!-- 申請表列表 -->
        <div id="applicationsList">
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>申請編號</th>
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
        // 載入申請表列表
        function loadApplications() {
            fetch(`backend/cooperation_teacher_list_api.php?teacher_username=<?php echo $username; ?>`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('applicationsTableBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="no-data">暫無申請資料</td></tr>';
                        return;
                    }

                    // 更新統計資訊
                    const stats = {
                        pending: 0,
                        approved: 0,
                        rejected: 0
                    };

                    data.forEach(app => {
                        stats[app.status]++;
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>#${app.id}</td>
                            <td>${app.project_title}</td>
                            <td>${app.company_name}</td>
                            <td>NT$ ${parseFloat(app.budget_amount).toLocaleString()}</td>
                            <td>${new Date(app.created_at).toLocaleDateString('zh-TW')}</td>
                            <td><span class="status-badge status-${app.status}">${getStatusText(app.status)}</span></td>
                            <td>
                                <button class="action-btn btn-view" onclick="viewDetail(${app.id})">查看</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // 更新統計數字
                    document.getElementById('pendingCount').textContent = stats.pending;
                    document.getElementById('approvedCount').textContent = stats.approved;
                    document.getElementById('rejectedCount').textContent = stats.rejected;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('applicationsTableBody').innerHTML = 
                        '<tr><td colspan="7" class="no-data">載入資料時發生錯誤</td></tr>';
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
            fetch(`backend/cooperation_detail_api.php?id=${applicationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const app = data.application;
                        document.getElementById('applicationDetail').innerHTML = `
                            <h2>申請表詳細資料 #${app.id}</h2>
                            
                            <div class="detail-section">
                                <h3>👨‍🏫 申請人資訊</h3>
                                <div class="detail-row">
                                    <div class="detail-label">申請人姓名:</div>
                                    <div class="detail-value">${app.teacher_name}</div>
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
                                    <div class="detail-label">專案描述:</div>
                                    <div class="detail-value">${app.project_description}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">專案期間:</div>
                                    <div class="detail-value">${app.project_start_date} ~ ${app.project_end_date}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">預算金額:</div>
                                    <div class="detail-value">NT$ ${parseFloat(app.budget_amount).toLocaleString()}</div>
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
                                    <div class="detail-label">聯絡信箱:</div>
                                    <div class="detail-value">${app.company_email}</div>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h3>📎 申請表檔案</h3>
                                <div class="detail-row">
                                    <div class="detail-label">檔案:</div>
                                    <div class="detail-value">
                                        <a href="${app.application_file_path}" target="_blank" style="color: #3498db; text-decoration: none;">
                                            📄 查看申請表檔案
                                        </a>
                                    </div>
                                </div>
                            </div>

                            ${app.status !== 'pending' ? `
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
                            ` : `
                                <div class="detail-section">
                                    <h3>⏳ 審核狀態</h3>
                                    <div class="detail-row">
                                        <div class="detail-label">目前狀態:</div>
                                        <div class="detail-value">
                                            <span class="status-badge status-pending">待審核</span>
                                            <br><small style="color: #7f8c8d;">您的申請已送交行政人員審核，請耐心等待結果</small>
                                        </div>
                                    </div>
                                </div>
                            `}
                        `;
                        
                        document.getElementById('detailModal').style.display = 'block';
                    } else {
                        alert('載入詳細資料失敗: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('載入詳細資料時發生錯誤');
                });
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
