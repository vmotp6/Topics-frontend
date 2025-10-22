// Firebase Service Worker for Push Notifications
// 用於處理推播通知的Service Worker

importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// Firebase配置
const firebaseConfig = {
  apiKey: "your-api-key",
  authDomain: "your-project.firebaseapp.com",
  projectId: "your-project-id",
  storageBucket: "your-project.appspot.com",
  messagingSenderId: "123456789",
  appId: "your-app-id"
};

// 初始化Firebase
firebase.initializeApp(firebaseConfig);

// 獲取Firebase Messaging實例
const messaging = firebase.messaging();

// 處理背景訊息
messaging.onBackgroundMessage(function(payload) {
  console.log('收到背景訊息:', payload);
  
  const notificationTitle = payload.notification?.title || '新訊息';
  const notificationOptions = {
    body: payload.notification?.body || '您有一條新訊息',
    icon: '/assets/icon-192x192.svg',
    badge: '/assets/badge-72x72.svg',
    tag: 'chat-notification',
    data: payload.data,
    actions: [
      {
        action: 'open',
        title: '查看訊息'
      },
      {
        action: 'dismiss',
        title: '關閉'
      }
    ],
    requireInteraction: false,
    silent: false
  };

  return self.registration.showNotification(notificationTitle, notificationOptions);
});

// 處理通知點擊事件
self.addEventListener('notificationclick', function(event) {
  console.log('通知被點擊:', event);
  
  event.notification.close();
  
  if (event.action === 'open') {
    // 打開聊天頁面
    event.waitUntil(
      clients.openWindow('/frontend/chat/chat.php')
    );
  }
});

// 處理通知關閉事件
self.addEventListener('notificationclose', function(event) {
  console.log('通知被關閉:', event);
});

// 處理推送事件
self.addEventListener('push', function(event) {
  console.log('收到推送事件:', event);
  
  if (event.data) {
    const data = event.data.json();
    console.log('推送數據:', data);
    
    const notificationTitle = data.title || '新訊息';
    const notificationOptions = {
      body: data.body || '您有一條新訊息',
      icon: '/assets/icon-192x192.svg',
      badge: '/assets/badge-72x72.svg',
      tag: 'chat-notification',
      data: data,
      actions: [
        {
          action: 'open',
          title: '查看訊息'
        },
        {
          action: 'dismiss',
          title: '關閉'
        }
      ],
      requireInteraction: false,
      silent: false
    };
    
    event.waitUntil(
      self.registration.showNotification(notificationTitle, notificationOptions)
    );
  }
});

console.log('Firebase Service Worker 已載入');
