<?php
session_start();

// 引入資料庫設定
require_once '../config.php';

// 建立資料庫連接
$conn = getDatabaseConnection();

if (!$conn) {
    die(json_encode(['error' => '資料庫連接失敗']));
}

// 獲取請求的統計類型
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'status':
            $data = getStatusStats($conn);
            break;
        case 'department':
            $data = getDepartmentStats($conn);
            break;
        case 'grade':
            $data = getGradeStats($conn);
            break;
        case 'school':
            $data = getSchoolStats($conn);
            break;
        case 'student_grade':
            $data = getStudentGradeStats($conn);
            break;
        case 'monthly':
            $data = getMonthlyStats($conn);
            break;
        case 'interest':
            $data = getInterestStats($conn);
            break;
        default:
            $data = ['error' => '無效的統計類型'];
    }
    
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    if ($conn) {
        $conn->close();
    }
}

// 推薦狀態統計
function getStatusStats($conn) {
    $sql = "SELECT status, COUNT(*) as count FROM admission_recommendations GROUP BY status ORDER BY count DESC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status_text = [
                'pending' => '待處理',
                'contacted' => '已聯繫',
                'registered' => '已報名',
                'rejected' => '已拒絕'
            ];
            
            $stats[] = [
                'name' => $status_text[$row['status']] ?? $row['status'],
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 推薦人科系統計
function getDepartmentStats($conn) {
    $sql = "SELECT recommender_department, COUNT(*) as count FROM admission_recommendations GROUP BY recommender_department ORDER BY count DESC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['recommender_department'] ?: '未填寫',
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 推薦人年級統計
function getGradeStats($conn) {
    $sql = "SELECT recommender_grade, COUNT(*) as count FROM admission_recommendations GROUP BY recommender_grade ORDER BY count DESC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['recommender_grade'] ?: '未填寫',
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 被推薦學校統計
function getSchoolStats($conn) {
    $sql = "SELECT student_school, COUNT(*) as count FROM admission_recommendations GROUP BY student_school ORDER BY count DESC LIMIT 10";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['student_school'] ?: '未填寫',
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 被推薦學生年級統計
function getStudentGradeStats($conn) {
    $sql = "SELECT student_grade, COUNT(*) as count FROM admission_recommendations WHERE student_grade IS NOT NULL AND student_grade != '' GROUP BY student_grade ORDER BY count DESC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['student_grade'],
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 月度趨勢統計
function getMonthlyStats($conn) {
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM admission_recommendations 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
            ORDER BY month ASC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['month'],
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}

// 學生興趣領域科系統計
function getInterestStats($conn) {
    $sql = "SELECT student_interest, COUNT(*) as count 
            FROM admission_recommendations 
            WHERE student_interest IS NOT NULL AND student_interest != '' 
            GROUP BY student_interest 
            ORDER BY count DESC";
    $result = $conn->query($sql);
    
    $stats = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = [
                'name' => $row['student_interest'] ?: '未填寫',
                'value' => (int)$row['count']
            ];
        }
    }
    
    return $stats;
}
?>
