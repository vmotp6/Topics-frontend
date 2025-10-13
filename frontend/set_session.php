<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);

  if (isset($data['username']) && isset($data['role'])) {
    // 確保登入狀態也被設定
    $_SESSION['logged_in'] = $data['logged_in'] ?? true;
    $_SESSION['login_method'] = 'normal'; // 或其他您定義的登入方式
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];
    echo json_encode(["success" => true]);
    exit;
  }
}

echo json_encode(["success" => false]);
?>
