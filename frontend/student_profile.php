<?php
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/config.php';

// 檢查是否為學生且已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== '學生') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDatabaseConnection();

    // 更新姓名
    if (isset($_POST['update_name'])) {
        $name = trim($_POST['name']);
        if (empty($name)) {
            $message = '姓名不能為空。';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE user SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $message = '姓名更新成功！';
                $message_type = 'success';
            } else {
                $message = '姓名更新失敗，請稍後再試。';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }

    // 更新密碼
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = '所有密碼欄位都必須填寫。';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = '新密碼與確認密碼不相符。';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($current_password, $user['password'])) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hashed_password, $user_id);
                if ($stmt->execute()) {
                    $message = '密碼更新成功！';
                    $message_type = 'success';
                } else {
                    $message = '密碼更新失敗，請稍後再試。';
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $message = '目前的密碼不正確。';
                $message_type = 'error';
            }
        }
    }
    $conn->close();
}

// 取得使用者資料
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT username, name, email FROM user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$page_title = '個人資料';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - 康寧大學招生平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body.custom-spacing {
            padding: 0;
            background: linear-gradient(135deg, #e0e7ff, #f8fafc);
            font-family: 'Segoe UI', sans-serif;
        }

        main {
            max-width: 900px;
            margin: 150px auto 40px;
            padding: 30px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            text-align: center;
            color: #4f46e5;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        .profile-section {
            margin-bottom: 35px;
            padding: 25px;
            border: none;
            border-radius: 12px;
            background: #f9fafb;
            box-shadow: inset 0 0 0 1px #e5e7eb;
        }

        .profile-section h2 {
            font-size: 1.25rem;
            margin-bottom: 20px;
            color: #374151;
            padding-bottom: 8px;
            border-bottom: 3px solid #4f46e5;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
        }

        .form-group input[readonly] {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }

        .btn {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            transform: translateY(-1px);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            animation: fadeIn 0.4s ease;
        }

        .message.success {
            background-color: #dcfce7;
            color: #065f46;
        }

        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="custom-spacing">

<?php include __DIR__ . '/share/header.php'; ?>

<main>
    <h1><i class="fas fa-user-circle"></i> <?php echo $page_title; ?></h1>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="profile-section">
        <h2>基本資料</h2>
        <form method="POST">
            <div class="form-group">
                <label for="username">帳號</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="name">姓名</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
            </div>
            <button type="submit" name="update_name" class="btn"><i class="fas fa-save"></i> 更新姓名</button>
        </form>
    </div>

    <div class="profile-section">
        <h2>修改密碼</h2>
        <form method="POST">
            <div class="form-group">
                <label for="current_password">目前的密碼</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">新密碼</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">確認新密碼</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" name="update_password" class="btn"><i class="fas fa-key"></i> 更新密碼</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/share/footer.php'; ?>

</body>
</html>
