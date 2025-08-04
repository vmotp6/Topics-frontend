
const chat = document.getElementById('chat');
const input = document.getElementById('input');

function appendMessage(sender, text) {
  const msg = document.createElement('div');
  msg.className = 'message ' + sender;
  msg.innerText = (sender === 'user' ? '使用者: ' : 'AI: ') + text;
  chat.appendChild(msg);
  chat.scrollTop = chat.scrollHeight;
}

function sendMessage() {
  const text = input.value.trim();
  if (!text) return;
  appendMessage('user', text);
  input.value = '';

  // 模擬 AI 回答
  let response = '';
  if (text.includes('想解決什麼問題')) {
    response = '請告訴我們您遇到的困難或希望改善的情況，我們將協助推薦適合的科系或資源。';
  } else if (text.includes('需要什麼技術') || text.includes('技術')) {
    response = '了解您的技術需求後，我們可以媒合具備相關技能的產學團隊。';
  } else if (text.includes('科系') || text.includes('合作')) {
    response = '根據您的需求，我們推薦資工系、電機系、設計系等可提供支援的科系進行合作。';
  } else {
    response = '感謝您的提問，我們會根據您的輸入提供適當的建議。';
  }
  setTimeout(() => appendMessage('bot', response), 500);
}
