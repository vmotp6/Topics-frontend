<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);

  if (isset($data['username']) && isset($data['role'])) {
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];
    echo json_encode(["success" => true]);
    exit;
  }
}

echo json_encode(["success" => false]);
?>
