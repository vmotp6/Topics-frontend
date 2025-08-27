<?php
session_start();

// 檢查是否已登入且為老師身份
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== '老師' && $_SESSION['role'] !== 'teacher')) {
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
    <title>就讀意願登錄管理</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f5f5;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        color: #333;
        padding-top: 100px;
    }

    .container {
        max-width: 1200px;
        margin: 40px auto;
        background: #fff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .h11 {
        color: #222;
        text-align: center;
        margin-bottom: 30px;
        font-size: 2em;
        font-weight: 600;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 1.2em;
    }

    .stat-card .number {
        font-size: 2em;
        font-weight: bold;
    }

    .filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .filter-group {
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 10px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-btn {
        background: #0056b3;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        margin-right: 10px;
    }

    .filter-btn:hover {
        background: #004494;
    }

    .applications-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .applications-table th,
    .applications-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .applications-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .applications-table tr:hover {
        background: #f8f9fa;
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
        background: #fff3cd;
        color: #856404;
    }

    .status-contacted {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-enrolled {
        background: #d4edda;
        color: #155724;
    }

    .action-btn {
        background: #0056b3;
        color: white;
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-right: 5px;
    }

    .action-btn:hover {
        background: #004494;
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
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border-radius: 8px;
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
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .detail-section h4 {
        margin: 0 0 10px 0;
        color: #333;
        border-bottom: 2px solid #0056b3;
        padding-bottom: 5px;
    }

    .detail-row {
        display: flex;
        margin-bottom: 8px;
    }

    .detail-label {
        font-weight: 600;
        min-width: 120px;
        color: #666;
    }

    .detail-value {
        flex: 1;
        color: #333;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #666;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
            margin: 20px;
        }
        
        .stats-container {
            grid-template-columns: 1fr;
        }
        
        .filter-group {
            display: block;
            margin-bottom: 15px;
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
    <?php include("share/header.php"); ?>
    
    <div class="container">
        <h1 class="h11">就讀意願登錄管理</h1>
        
        <!-- 統計資訊 -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>總申請數</h3>
                <div class="number" id="totalCount">0</div>
            </div>
            <div class="stat-card">
                <h3>待聯絡</h3>
                <div class="number" id="pendingCount">0</div>
            </div>
            <div class="stat-card">
                <h3>已聯絡</h3>
                <div class="number" id="contactedCount">0</div>
            </div>
            <div class="stat-card">
                <h3>已入學</h3>
                <div class="number" id="enrolledCount">0</div>
            </div>
        </div>

        <!-- 篩選器 -->
        <div class="filters">
            <div class="filter-group">
                <label>狀態:</label>
                <select id="statusFilter">
                    <option value="">全部</option>
                    <option value="pending">待聯絡</option>
                    <option value="contacted">已聯絡</option>
                    <option value="enrolled">已入學</option>
                </select>
            </div>
            <div class="filter-group">
                <label>身分別:</label>
                <select id="identityFilter">
                    <option value="">全部</option>
                    <option value="學生">學生</option>
                    <option value="家長">家長</option>
                </select>
            </div>
            <div class="filter-group">
                <label>申請日期:</label>
                <input type="date" id="dateFilter">
            </div>
            <button class="filter-btn" onclick="loadApplications()">🔍 篩選</button>
            <button class="filter-btn" onclick="resetFilters()">🔄 重置</button>
        </div>

        <!-- 申請表列表 -->
        <div id="applicationsList">
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>申請編號</th>
                        <th>姓名</th>
                        <th>身分別</th>
                        <th>聯絡電話</th>
                        <th>就讀意願一</th>
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
            const statusFilter = document.getElementById('statusFilter').value;
            const identityFilter = document.getElementById('identityFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            const params = new URLSearchParams();
            if (statusFilter) params.append('status', statusFilter);
            if (identityFilter) params.append('identity', identityFilter);
            if (dateFilter) params.append('date', dateFilter);
            
            fetch(`../backend/enrollment_list_api.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('applicationsTableBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="no-data">暫無申請資料</td></tr>';
                        return;
                    }

                    // 更新統計資訊
                    updateStats(data);

                    data.forEach(app => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${app.id}</td>
                            <td>${app.name}</td>
                            <td>${app.identity}</td>
                            <td>${app.phone1}</td>
                            <td>${app.intention1}</td>
                            <td>${formatDate(app.created_at)}</td>
                            <td><span class="status-badge status-${app.status}">${getStatusText(app.status)}</span></td>
                            <td>
                                <button class="action-btn" onclick="viewDetail(${app.id})">查看</button>
                                <button class="action-btn" onclick="updateStatus(${app.id})">更新狀態</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('applicationsTableBody').innerHTML = 
                        '<tr><td colspan="8" class="no-data">載入資料時發生錯誤</td></tr>';
                });
        }

        // 更新統計資訊
        function updateStats(data) {
            const stats = {
                total: data.length,
                pending: data.filter(app => app.status === 'pending').length,
                contacted: data.filter(app => app.status === 'contacted').length,
                enrolled: data.filter(app => app.status === 'enrolled').length
            };

            document.getElementById('totalCount').textContent = stats.total;
            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('contactedCount').textContent = stats.contacted;
            document.getElementById('enrolledCount').textContent = stats.enrolled;
        }

        // 重置篩選器
        function resetFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('identityFilter').value = '';
            document.getElementById('dateFilter').value = '';
            loadApplications();
        }

        // 查看詳細資料
        function viewDetail(id) {
            fetch(`../backend/enrollment_detail_api.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const app = data.application;
                        document.getElementById('applicationDetail').innerHTML = `
                            <h2>申請編號: ${app.id}</h2>
                            
                            <div class="detail-section">
                                <h4>個人基本資料</h4>
                                <div class="detail-row">
                                    <span class="detail-label">姓名:</span>
                                    <span class="detail-value">${app.name}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">身分別:</span>
                                    <span class="detail-value">${app.identity}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">性別:</span>
                                    <span class="detail-value">${app.gender || '未填寫'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">聯絡電話1:</span>
                                    <span class="detail-value">${app.phone1}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">聯絡電話2:</span>
                                    <span class="detail-value">${app.phone2 || '未填寫'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">電子郵件:</span>
                                    <span class="detail-value">${app.email || '未填寫'}</span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>就讀意願</h4>
                                <div class="detail-row">
                                    <span class="detail-label">意願一:</span>
                                    <span class="detail-value">${app.intention1} - ${app.system1 || ''} - ${app.department1 || ''}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">意願二:</span>
                                    <span class="detail-value">${app.intention2} - ${app.system2 || ''} - ${app.department2 || ''}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">意願三:</span>
                                    <span class="detail-value">${app.intention3} - ${app.system3 || ''} - ${app.department3 || ''}</span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>國中資訊</h4>
                                <div class="detail-row">
                                    <span class="detail-label">就讀國中:</span>
                                    <span class="detail-value">${app.junior_high || '未填寫'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">目前年級:</span>
                                    <span class="detail-value">${app.current_grade || '未填寫'}</span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>社群媒體</h4>
                                <div class="detail-row">
                                    <span class="detail-label">LineID:</span>
                                    <span class="detail-value">${app.line_id || '未填寫'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Facebook:</span>
                                    <span class="detail-value">${app.facebook || '未填寫'}</span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>備註</h4>
                                <div class="detail-value">${app.remarks || '無備註'}</div>
                            </div>

                            <div class="detail-section">
                                <h4>申請資訊</h4>
                                <div class="detail-row">
                                    <span class="detail-label">申請日期:</span>
                                    <span class="detail-value">${formatDate(app.created_at)}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">狀態:</span>
                                    <span class="detail-value">
                                        <span class="status-badge status-${app.status}">${getStatusText(app.status)}</span>
                                    </span>
                                </div>
                            </div>
                        `;
                        document.getElementById('detailModal').style.display = 'block';
                    } else {
                        alert('載入詳細資料失敗');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('載入詳細資料時發生錯誤');
                });
        }

        // 更新狀態
        function updateStatus(id) {
            const newStatus = prompt('請選擇新狀態 (pending/contacted/enrolled):');
            if (newStatus && ['pending', 'contacted', 'enrolled'].includes(newStatus)) {
                fetch('../backend/enrollment_update_status_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('狀態更新成功');
                        loadApplications();
                    } else {
                        alert('狀態更新失敗: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('更新狀態時發生錯誤');
                });
            }
        }

        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-TW');
        }

        // 取得狀態文字
        function getStatusText(status) {
            const statusMap = {
                'pending': '待聯絡',
                'contacted': '已聯絡',
                'enrolled': '已入學'
            };
            return statusMap[status] || status;
        }

        // 關閉模態框
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('detailModal').style.display = 'none';
        });

        // 點擊模態框外部關閉
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // 頁面載入時載入資料
        document.addEventListener('DOMContentLoaded', function() {
            loadApplications();
        });
    </script>
    <?php include("share/footer.php"); ?>
</body>
</html>
