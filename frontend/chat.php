<?php
session_start();
$username = $_SESSION['username'] ?? '匿名';
$role = $_SESSION['role'] ?? '訪客';
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "請先登入後再使用聊天室。";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>聊天室</title>
  <style>
    #chat {
      width: 400px;
      height: 400px;
      border: 1px solid #ccc;
      overflow-y: scroll;
      padding: 10px;
      margin-bottom: 10px;
    }
    .teacher { color: blue; }
    .vendor { color: green; }
    .me { font-weight: bold; }
  </style>
</head>
<body>
  <h2>老師與廠商聊天室</h2>
  <div id="chat"></div>
  <input type="text" id="msg" placeholder="輸入訊息">
  <button onclick="sendMsg()">送出</button>

  <!-- Socket.IO -->
  <script src="https://cdn.socket.io/4.0.1/socket.io.min.js"></script>
  <script>
    const socket = io("http://localhost:5003");

    const username = "<?php echo $username; ?>";
    const role = "<?php echo $role; ?>";
    const chat = document.getElementById('chat');

    // 載入歷史紀錄
    fetch("http://localhost:5003/chat_history")
      .then(res => res.json())
      .then(messages => {
        messages.forEach(data => {
          appendMessage(data);
        });
      });

    // 接收新訊息
    socket.on('chat_message', data => {
      appendMessage(data);
    });

    // 顯示訊息
    function appendMessage(data) {
      const p = document.createElement('p');
      const isMe = (data.sender === username);
      let cssClass = '';
      if (data.role === '老師') cssClass = 'teacher';
      else if (data.role === '廠商') cssClass = 'vendor';
      if (isMe) cssClass += ' me';

      p.className = cssClass;
      p.textContent = `[${data.role}] ${data.sender}：${data.message}`;
      chat.appendChild(p);
      chat.scrollTop = chat.scrollHeight;
    }

    // 發送訊息
    function sendMsg() {
      const msgInput = document.getElementById('msg');
      const msg = msgInput.value.trim();
      if (msg) {
        const data = {
          username: username,
          role: role,
          message: msg
        };
        socket.emit('chat_message', data);
        msgInput.value = '';
      }
    }
  </script>
</body>
</html>
