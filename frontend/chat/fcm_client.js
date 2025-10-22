/**
 * Firebase Cloud Messaging 客戶端整合
 */

class FCMClient {
    constructor() {
        this.isSupported = 'Notification' in window && 'serviceWorker' in navigator;
        this.registration = null;
        this.currentToken = null;
    }
    
    /**
     * 初始化FCM
     */
    async initialize() {
        if (!this.isSupported) {
            console.log('此瀏覽器不支援推播通知');
            return false;
        }
        
        try {
            // 檢查通知權限狀態
            if (Notification.permission === 'denied') {
                console.log('通知權限已被拒絕，請在瀏覽器設定中啟用');
                this.showNotificationPermissionGuide();
                return false;
            }
            
            // 請求通知權限
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.log('用戶拒絕了通知權限');
                this.showNotificationPermissionGuide();
                return false;
            }
            
            // 註冊Service Worker
            try {
                this.registration = await navigator.serviceWorker.register('../firebase-messaging-sw.js');
                console.log('Service Worker註冊成功');
            } catch (error) {
                console.log('Service Worker註冊失敗，使用簡化模式:', error);
                // 如果Service Worker註冊失敗，繼續使用簡化的通知功能
            }
            
            // 獲取FCM token
            await this.getToken();
            
            return true;
            
        } catch (error) {
            console.error('FCM初始化失敗:', error);
            return false;
        }
    }
    
    /**
     * 獲取FCM token
     */
    async getToken() {
        try {
            // 這裡需要Firebase SDK
            // 暫時使用模擬token進行測試
            this.currentToken = 'test-token-' + Date.now();
            console.log('FCM Token:', this.currentToken);
            
            // 註冊token到服務器
            await this.registerToken();
            
            return this.currentToken;
            
        } catch (error) {
            console.error('獲取FCM token失敗:', error);
            return null;
        }
    }
    
    /**
     * 註冊token到服務器
     */
    async registerToken() {
        if (!this.currentToken) return;
        
        try {
        const response = await fetch('simple_fcm_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register_token',
                username: window.username || 'test_user',
                fcm_token: this.currentToken,
                device_type: 'web',
                device_info: JSON.stringify({
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language
                })
            })
        });
            
            const result = await response.json();
            if (result.success) {
                console.log('FCM token註冊成功');
            } else {
                console.error('FCM token註冊失敗:', result.error);
            }
            
        } catch (error) {
            console.error('註冊FCM token時發生錯誤:', error);
        }
    }
    
    /**
     * 顯示通知
     */
    showNotification(title, body, data = {}) {
        if (!this.isSupported || Notification.permission !== 'granted') {
            return;
        }
        
        const options = {
            body: body,
            icon: '../assets/icon-192x192.svg',
            badge: '../assets/badge-72x72.svg',
            tag: 'chat-notification',
            data: data,
            actions: [
                {
                    action: 'open',
                    title: '查看訊息'
                },
                {
                    action: 'close',
                    title: '關閉'
                }
            ]
        };
        
        const notification = new Notification(title, options);
        
        notification.onclick = function(event) {
            event.preventDefault();
            window.focus();
            
            if (data.chat_url) {
                window.open(data.chat_url, '_blank');
            }
            
            notification.close();
        };
        
        // 自動關閉通知
        setTimeout(() => {
            notification.close();
        }, 5000);
    }
    
    /**
     * 監聽推播訊息
     */
    setupMessageListener() {
        if (!this.registration) return;
        
        // 監聽推播事件
        this.registration.addEventListener('push', (event) => {
            if (event.data) {
                const data = event.data.json();
                this.showNotification(data.notification.title, data.notification.body, data.data);
            }
        });
        
        // 監聽通知點擊事件
        this.registration.addEventListener('notificationclick', (event) => {
            event.notification.close();
            
            if (event.action === 'open' || !event.action) {
                if (event.notification.data && event.notification.data.chat_url) {
                    event.waitUntil(
                        clients.openWindow(event.notification.data.chat_url)
                    );
                }
            }
        });
    }
    
    /**
     * 測試發送通知
     */
    async testNotification(toUser, title, body) {
        try {
            const response = await fetch('fcm_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'test_notification',
                    to_user: toUser,
                    title: title,
                    body: body,
                    data: {
                        type: 'test',
                        timestamp: Date.now()
                    }
                })
            });
            
            const result = await response.json();
            return result;
            
        } catch (error) {
            console.error('測試通知發送失敗:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * 顯示通知權限設定指南
     */
    showNotificationPermissionGuide() {
        const guideModal = document.createElement('div');
        guideModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        guideModal.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; margin: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
                <h3 style="margin: 0 0 20px 0; color: #333; text-align: center;">🔔 通知權限設定</h3>
                <div style="color: #666; line-height: 1.6;">
                    <p><strong>推播通知功能需要瀏覽器權限才能使用。</strong></p>
                    <p>請按照以下步驟啟用通知權限：</p>
                    <ol style="margin: 15px 0; padding-left: 20px;">
                        <li>點擊瀏覽器地址欄左側的鎖頭圖示 🔒</li>
                        <li>找到「通知」選項</li>
                        <li>選擇「允許」</li>
                        <li>重新整理頁面</li>
                    </ol>
                    <p style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <strong>⚠️ 注意：</strong>如果權限被多次拒絕，可能需要手動在瀏覽器設定中啟用。
                    </p>
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                            style="background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        我知道了
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(guideModal);
        
        // 點擊背景關閉
        guideModal.addEventListener('click', (e) => {
            if (e.target === guideModal) {
                guideModal.remove();
            }
        });
    }
}

// 全域FCM實例
window.fcmClient = new FCMClient();

// 頁面載入時初始化FCM
document.addEventListener('DOMContentLoaded', async () => {
    const initialized = await window.fcmClient.initialize();
    if (initialized) {
        window.fcmClient.setupMessageListener();
        console.log('FCM客戶端初始化完成');
    }
});

// 導出給其他模組使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FCMClient;
}


















