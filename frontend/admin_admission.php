<?php
session_start();
require_once 'config.php';

// 處理Google登入回調
if (isset($_GET['google_login']) && $_GET['google_login'] === 'success') {
    if (isset($_GET['username']) && isset($_GET['role'])) {
        // 設定Session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_GET['username'];
        $_SESSION['role'] = $_GET['role'];
        $_SESSION['login_method'] = 'google';
        
        // 重定向到相應頁面（避免URL參數顯示）
        $redirect_url = 'index.php';
        if ($_GET['role'] === '管理員') {
            $redirect_url = 'admin_admission.php';
        } elseif ($_GET['role'] === '老師') {
            $redirect_url = 'teacher.php';
        }
        
        header("Location: $redirect_url");
        exit();
    }
}

// 檢查管理員權限
if (!isset($_SESSION['role']) || $_SESSION['role'] !== '管理員') {
    header('Location: index.php');
    exit();
}

// 建立資料庫連接
$conn = getDatabaseConnection();

$message = "";
$messageType = "";

// 處理表單提交
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_session':
                // 新增場次
                $sql = "INSERT INTO admission_sessions (session_name, session_date, session_type, max_participants, description) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $max_participants = !empty($_POST['max_participants']) ? $_POST['max_participants'] : null;
                $stmt->bind_param("sssis", 
                    $_POST['session_name'],
                    $_POST['session_date'],
                    $_POST['session_type'],
                    $max_participants,
                    $_POST['description']
                );
                
                if ($stmt->execute()) {
                    $message = "場次新增成功！";
                    $messageType = "success";
                } else {
                    $message = "場次新增失敗：" . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
                break;
                
            case 'toggle_session':
                // 啟用/停用場次
                $sql = "UPDATE admission_sessions SET is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $is_active = $_POST['is_active'] === '1' ? 0 : 1;
                $stmt->bind_param("ii", $is_active, $_POST['session_id']);
                
                if ($stmt->execute()) {
                    $message = $is_active ? "場次已啟用" : "場次已停用";
                    $messageType = "success";
                } else {
                    $message = "操作失敗：" . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
                break;
                
            case 'update_session':
                // 更新場次
                $sql = "UPDATE admission_sessions SET session_name = ?, session_date = ?, session_type = ?, max_participants = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $max_participants = !empty($_POST['max_participants']) ? $_POST['max_participants'] : null;
                $stmt->bind_param("sssisi", 
                    $_POST['session_name'],
                    $_POST['session_date'],
                    $_POST['session_type'],
                    $max_participants,
                    $_POST['description'],
                    $_POST['session_id']
                );
                
                if ($stmt->execute()) {
                    $message = "場次更新成功！";
                    $messageType = "success";
                } else {
                    $message = "場次更新失敗：" . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
                break;
                
            case 'delete_session':
                // 檢查是否有報名資料
                $check_sql = "SELECT COUNT(*) as count FROM admission_applications WHERE session_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $_POST['session_id']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $check_row = $check_result->fetch_assoc();
                $check_stmt->close();
                
                if ($check_row['count'] > 0) {
                    $message = "無法刪除：此場次已有 {$check_row['count']} 筆報名資料";
                    $messageType = "error";
                } else {
                    // 刪除場次
                    $sql = "DELETE FROM admission_sessions WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_POST['session_id']);
                    
                    if ($stmt->execute()) {
                        $message = "場次刪除成功！";
                        $messageType = "success";
                    } else {
                        $message = "場次刪除失敗：" . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// 取得所有場次
$sessions_query = "SELECT s.*, COUNT(a.id) as registration_count 
                   FROM admission_sessions s 
                   LEFT JOIN admission_applications a ON s.id = a.session_id 
                   GROUP BY s.id 
                   ORDER BY s.session_date";
$sessions_result = $conn->query($sessions_query);
$sessions = [];
if ($sessions_result) {
    while ($row = $sessions_result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入學說明會場次管理 - 康寧大學</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/csp/admin_admission.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> 入學說明會場次管理</h1>
            <p>康寧大學五專招生系統</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 新增場次 -->
        <div class="card">
            <h2><i class="fas fa-plus"></i> 新增場次</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_session">
                <div class="form-row">
                    <div class="form-group">
                        <label>場次名稱 *</label>
                        <input type="text" name="session_name" required placeholder="例：114.05.29（四) 1900-2030">
                    </div>
                    <div class="form-group">
                        <label>場次日期時間 *</label>
                        <input type="datetime-local" name="session_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>場次類型 *</label>
                        <select name="session_type" required>
                            <option value="實體">實體</option>
                            <option value="線上">線上</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>最大參與人數 (選填)</label>
                        <input type="number" name="max_participants" min="1" placeholder="不限制請留空">
                    </div>
                </div>
                <div class="form-group">
                    <label>場次描述 (選填)</label>
                    <textarea name="description" rows="3" placeholder="場次相關說明"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新增場次
                </button>
            </form>
        </div>

        <!-- 場次列表 -->
        <div class="card">
            <h2><i class="fas fa-list"></i> 場次列表</h2>
            
            <?php if (empty($sessions)): ?>
                <p>目前沒有任何場次。</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>場次名稱</th>
                            <th>日期時間</th>
                            <th>類型</th>
                            <th>報名人數</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo date('Y/m/d H:i', strtotime($session['session_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $session['session_type'] === '線上' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $session['session_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $session['registration_count']; ?>
                                    <?php if ($session['max_participants']): ?>
                                        / <?php echo $session['max_participants']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $session['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $session['is_active'] ? '啟用' : '停用'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <!-- 啟用/停用 -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_session">
                                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $session['is_active']; ?>">
                                            <button type="submit" class="btn <?php echo $session['is_active'] ? 'btn-warning' : 'btn-success'; ?>" onclick="return confirm('確定要<?php echo $session['is_active'] ? '停用' : '啟用'; ?>此場次嗎？')">
                                                <i class="fas fa-power-off"></i>
                                                <?php echo $session['is_active'] ? '停用' : '啟用'; ?>
                                            </button>
                                        </form>
                                        
                                        <!-- 編輯 -->
                                        <button type="button" class="btn btn-primary" onclick="editSession(<?php echo htmlspecialchars(json_encode($session)); ?>)">
                                            <i class="fas fa-edit"></i> 編輯
                                        </button>
                                        
                                        <!-- 刪除 -->
                                        <?php if ($session['registration_count'] == 0): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_session">
                                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除此場次嗎？此操作無法復原！')">
                                                    <i class="fas fa-trash"></i> 刪除
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> 返回管理後台
            </a>
        </div>
    </div>

    <!-- 編輯場次模態框 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-edit"></i> 編輯場次</h2>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="update_session">
                <input type="hidden" name="session_id" id="edit_session_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>場次名稱 *</label>
                        <input type="text" name="session_name" id="edit_session_name" required>
                    </div>
                    <div class="form-group">
                        <label>場次日期時間 *</label>
                        <input type="datetime-local" name="session_date" id="edit_session_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>場次類型 *</label>
                        <select name="session_type" id="edit_session_type" required>
                            <option value="實體">實體</option>
                            <option value="線上">線上</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>最大參與人數 (選填)</label>
                        <input type="number" name="max_participants" id="edit_max_participants" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>場次描述 (選填)</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-warning" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 儲存變更
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editSession(session) {
            document.getElementById('edit_session_id').value = session.id;
            document.getElementById('edit_session_name').value = session.session_name;
            
            // 轉換日期格式
            const date = new Date(session.session_date);
            const formattedDate = date.getFullYear() + '-' + 
                                  String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(date.getDate()).padStart(2, '0') + 'T' + 
                                  String(date.getHours()).padStart(2, '0') + ':' + 
                                  String(date.getMinutes()).padStart(2, '0');
            
            document.getElementById('edit_session_date').value = formattedDate;
            document.getElementById('edit_session_type').value = session.session_type;
            document.getElementById('edit_max_participants').value = session.max_participants || '';
            document.getElementById('edit_description').value = session.description || '';
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // 點擊模態框外部關閉
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // 關閉按鈕
        document.querySelector('.close').onclick = function() {
            closeModal();
        }
    </script>
</body>
</html>
