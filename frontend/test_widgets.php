<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>助手組件測試</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .page-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .page-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        .page-item a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .page-item a:hover {
            text-decoration: underline;
        }
        .widget-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 浮動助手組件測試頁面</h1>
        
        <div class="test-section">
            <h2>📊 測試狀態</h2>
            <div class="status success">
                ✅ 助手組件已成功添加到所有主要頁面
            </div>
            <div class="status info">
                ℹ️ 每個頁面都會顯示兩個浮動助手：聊天助手 (💬) 和 AI助手 (🤖)
            </div>
        </div>
        
        <div class="test-section">
            <h2>🎯 助手功能說明</h2>
            <div class="widget-info">
                <h3>💬 聊天助手 (右下角藍色按鈕)</h3>
                <ul>
                    <li>提供即時聊天功能</li>
                    <li>支援訊息記錄保存</li>
                    <li>可調整聊天框大小</li>
                    <li>需要登入才能使用</li>
                </ul>
                
                <h3>🤖 AI助手 (右下角橙色按鈕)</h3>
                <ul>
                    <li>提供科系推薦功能</li>
                    <li>智能回答招生相關問題</li>
                    <li>支援拖拽調整大小</li>
                    <li>需要登入才能使用完整功能</li>
                </ul>
            </div>
        </div>
        
        <div class="test-section">
            <h2>📄 已添加助手的頁面</h2>
            <div class="page-list">
                <div class="page-item">
                    <h4>🏠 主要頁面</h4>
                    <p><a href="index.php">首頁</a></p>
                    <p><a href="student.php">學生頁面</a></p>
                    <p><a href="teacher.php">老師頁面</a></p>
                </div>
                
                <div class="page-item">
                    <h4>📝 表單頁面</h4>
                    <p><a href="cooperation_upload.php">就讀意願登錄</a></p>
                    <p><a href="continued_admission.php">續招報名</a></p>
                    <p><a href="admission.php">五專入學說明會</a></p>
                    <p><a href="admission_recommend.php">推薦報名</a></p>
                </div>
                
                <div class="page-item">
                    <h4>👨‍💼 管理員頁面</h4>
                    <p><a href="admin_admission.php">招生管理</a></p>
                    <p><a href="admin_school_data.php">學校資料管理</a></p>
                    <p><a href="school_management.php">學校管理</a></p>
                </div>
                
                <div class="page-item">
                    <h4>📊 記錄頁面</h4>
                    <p><a href="records.php">活動紀錄填報</a></p>
                    <p><a href="my_records.php">我的記錄</a></p>
                    <p><a href="activity_records_management.php">活動記錄管理</a></p>
                </div>
                
                <div class="page-item">
                    <h4>🔧 其他頁面</h4>
                    <p><a href="QA.php">招生QA問答</a></p>
                    <p><a href="chat_settings.php">助手設置</a></p>
                    <p><a href="sitemap.php">網站地圖</a></p>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🧪 測試步驟</h2>
            <ol>
                <li>點擊上方任一頁面連結</li>
                <li>檢查右下角是否出現兩個浮動按鈕：💬 和 🤖</li>
                <li>點擊按鈕測試助手功能</li>
                <li>測試調整大小功能（拖拽邊緣）</li>
                <li>測試登入/登出狀態下的功能差異</li>
            </ol>
        </div>
        
        <div class="test-section">
            <h2>⚙️ 助手設置</h2>
            <p>您可以通過以下方式管理助手：</p>
            <ul>
                <li>點擊助手視窗右上角的 🚫 按鈕永久關閉</li>
                <li>點擊 🗑️ 按鈕清除對話記錄</li>
                <li>拖拽邊緣調整助手視窗大小</li>
                <li>訪問 <a href="chat_settings.php">助手設置頁面</a> 進行更多配置</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>📱 響應式支援</h2>
            <div class="status info">
                ℹ️ 助手組件支援響應式設計，在手機和平板上也能正常使用
            </div>
        </div>
    </div>
    
    <!-- 浮動助手組件 -->
    <?php include("share/chat_widget.php"); ?>
    <?php include("share/ai_widget.php"); ?>
</body>
</html>
