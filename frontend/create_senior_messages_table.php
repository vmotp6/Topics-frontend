<?php
/**
 * 創建學長姐留言表
 */

// 資料庫連接
$host = 'localhost';
$dbname = 'topics_good';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 創建學長姐留言表</h1>";
    
    // 創建資料表
    $sql = "CREATE TABLE IF NOT EXISTS senior_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL COMMENT '留言標題',
        content TEXT NOT NULL COMMENT '留言內容',
        author_name VARCHAR(100) NOT NULL COMMENT '學長姐姓名',
        author_email VARCHAR(255) NOT NULL COMMENT '學長姐Email',
        author_department VARCHAR(100) DEFAULT NULL COMMENT '學長姐科系',
        author_grade VARCHAR(50) DEFAULT NULL COMMENT '學長姐年級',
        author_contact VARCHAR(100) DEFAULT NULL COMMENT '聯絡方式',
        message_type ENUM('經驗分享', '學習建議', '生活指南', '就業資訊', '其他') DEFAULT '經驗分享' COMMENT '留言類型',
        is_published BOOLEAN DEFAULT TRUE COMMENT '是否發布',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '創建時間',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
        view_count INT DEFAULT 0 COMMENT '瀏覽次數',
        like_count INT DEFAULT 0 COMMENT '點讚次數',
        author_grade_year INT DEFAULT NULL COMMENT '入學年份（用於權限控制）'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='學長姐留言表'";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ 資料表 'senior_messages' 創建成功</p>";
    
    // 插入範例資料
    $insert_sql = "INSERT INTO senior_messages (title, content, author_name, author_email, author_department, author_grade, author_contact, message_type, author_grade_year) VALUES 
        ('歡迎來到康寧大學！', '各位學弟妹大家好！我是資訊管理系三年級的學長。大學生活真的很精彩，建議大家要好好把握時間，多參加社團活動，也要認真學習專業知識。有任何問題都可以找我聊聊！', '張小明', 'zhangxiaoming@stu.ukn.edu.tw', '資訊管理系', '三年級', 'line: xiaoming123', '經驗分享', 109),
        ('選課經驗分享', '學弟妹們，選課真的很重要！建議大家要提前了解各科系的課程內容，多聽學長姐的建議。有些通識課程很有趣，可以拓展視野。記住，不要只選好過的課，要選對自己有用的課！', '李小華', 'lihua@stu.ukn.edu.tw', '商務管理系', '四年級', 'email: lihua@example.com', '學習建議', 108),
        ('宿舍生活小貼士', '宿舍生活是大學很重要的一部分！建議大家要和室友好好相處，互相尊重。宿舍的公共區域要保持整潔，晚上不要太吵鬧。如果有問題可以找宿舍管理員或學長姐幫忙。', '王大偉', 'wangdawei@stu.ukn.edu.tw', '護理系', '二年級', 'phone: 0912-345-678', '生活指南', 110),
        ('實習經驗分享', '實習是連接學校和職場的重要橋樑。建議大家要主動爭取實習機會，多學習實務經驗。實習期間要認真學習，多問問題，建立良好的人際關係。這些經驗對未來就業很有幫助！', '陳小美', 'chenxiaomei@stu.ukn.edu.tw', '幼兒保育系', '五年級', 'line: chenmei456', '就業資訊', 107),
        ('社團活動推薦', '大學除了學習，社團活動也很重要！我參加了攝影社和志工社，學到很多課本以外的東西。建議大家可以選擇1-2個自己感興趣的社團參加，但要平衡時間，不要影響學業。', '林小強', 'linxiaoqiang@stu.ukn.edu.tw', '餐飲管理系', '三年級', 'email: linqiang@example.com', '經驗分享', 109)";
    
    $pdo->exec($insert_sql);
    echo "<p style='color: green;'>✅ 範例資料插入成功</p>";
    
    // 檢查資料數量
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM senior_messages");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>總留言數：{$count['total']}</p>";
    
    echo "<p><a href='senior_messages.php'>前往學長姐留言板</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}
?>
