<?php
// 載入 session 配置
require_once 'session_config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>網站導覽 - 康寧大學招生平台</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft JhengHei', 'Segoe UI', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            padding-top: 100px;
            color: #2c3e50;
            line-height: 1.6;
        }

        .sitemap-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .sitemap-header {
            text-align: center;
            margin-bottom: 50px;
            background: #f8f9fa;
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .sitemap-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .sitemap-header h1 {
            font-size: 2.8rem;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sitemap-header p {
            font-size: 1.3rem;
            color: #7f8c8d;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
            font-weight: 400;
        }

        .sitemap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .sitemap-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .sitemap-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .sitemap-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
        }

        .section-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
            color: white;
            font-size: 1.6rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .page-list {
            list-style: none;
        }

        .page-item {
            margin-bottom: 18px;
            padding: 18px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .page-item:hover {
            background: #e9ecef;
            border-left-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .page-link {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-link:hover {
            color: #667eea;
        }

        .page-description {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin-top: 8px;
            line-height: 1.6;
            font-weight: 400;
        }

        .page-icon {
            color: #667eea;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .page-link:hover .page-icon {
            transform: translateX(3px);
            color: #764ba2;
        }

        .back-to-home {
            text-align: center;
            margin-top: 40px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(90deg, #7ac9c7 0%, #956dbd 100%);
            color: white;
            padding: 18px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            body {
                padding-top: 80px;
            }

            .sitemap-container {
                padding: 20px 15px;
            }

            .sitemap-header {
                padding: 30px 20px;
            }

            .sitemap-header h1 {
                font-size: 2rem;
            }

            .sitemap-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .sitemap-section {
                padding: 20px;
            }

            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .section-icon {
                margin-right: 0;
            }
        }
    </style>
</head>

<?php include("share/header.php"); ?>

<body>
    <div class="sitemap-container">
        <!-- 頁面標題 -->
        <div class="sitemap-header">
            <h1><i class="fas fa-info-circle"></i> 網站功能介紹</h1>
            <p>歡迎來到康寧大學招生平台，以下是本網站各項功能的詳細介紹，幫助您了解每個頁面能做什麼</p>
        </div>

        <!-- 主要功能區域 -->
        <div class="sitemap-grid">
            <!-- 首頁與基本資訊 -->
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="section-title">首頁與基本資訊</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('index.php'); ?>" class="page-link">
                            <div>
                                <div>首頁</div>
                                <div class="page-description">平台首頁提供最新消息、重要公告和活動資訊。透過輪播圖片展示重要活動，並提供快速導航功能，讓您輕鬆了解康寧大學招生平台的各項服務與特色。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('QA.php'); ?>" class="page-link">
                            <div>
                                <div>招生QA問答</div>
                                <div class="page-description">提供完整的招生常見問題解答系統，您可以快速搜尋特定問題的答案，了解詳細的報名流程與注意事項，獲取所有招生相關的重要資訊，讓報名過程更加順利。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 招生相關服務 -->
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="section-title">招生相關服務</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('admission.php'); ?>" class="page-link">
                            <div>
                                <div>五專入學說明會</div>
                                <div class="page-description">專門為五專入學舉辦的說明會報名系統，讓您深入了解五專課程的特色與優勢。您可以查看說明會的詳細時間與地點，獲取完整的入學相關資訊與報名流程，為您的升學之路做好準備。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('admission_recommend.php'); ?>" class="page-link">
                            <div>
                                <div>推薦報名</div>
                                <div class="page-description">提供推薦入學的專屬報名管道，讓有推薦人的學生能夠透過推薦方式報名入學。系統支援填寫推薦人資訊與學生詳細資料，上傳相關證明文件，並享受推薦報名的特殊優惠與服務。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('continued_admission.php'); ?>" class="page-link">
                            <div>
                                <div>續招報名</div>
                                <div class="page-description">針對續招期間設計的報名系統，讓錯過初次招生的學生仍有機會入學。您可以查看剩餘的招生名額，填寫續招專用報名表單，把握最後的入學機會，完成您的升學夢想。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 智能助手與聊天 -->
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="section-title">智能助手與聊天</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('chat_settings.php'); ?>" class="page-link">
                            <div>
                                <div>🤖 助手設置</div>
                                <div class="page-description">AI智能助手的個人化設定中心，讓您自訂助手的回應風格與專業領域。您可以調整對話偏好設定，管理各項功能開關，打造專屬於您的智能助手體驗。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('AI.php'); ?>" class="page-link">
                            <div>
                                <div>科系推薦系統</div>
                                <div class="page-description">智能科系推薦系統，根據學生的興趣、能力和未來規劃，提供個人化的科系推薦建議。系統會分析各科系特色，幫助學生找到最適合的升學方向。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['username'])): ?>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('chat/chat.php'); ?>" class="page-link">
                            <div>
                                <div>私訊聊天室</div>
                                <div class="page-description">與AI智能助手進行一對一的即時對話，您可以詢問任何招生相關問題，獲得個人化的專業建議。系統會保存您的聊天歷史記錄，讓您隨時回顧之前的對話內容。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- 教師專區 -->
            <?php if (isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] === '老師'): ?>
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="section-title">教師專區</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('records.php'); ?>" class="page-link">
                            <div>
                                <div>活動紀錄填報表單</div>
                                <div class="page-description">專為教師設計的活動紀錄管理系統，讓您輕鬆記錄參與的教學活動與招生活動。系統支援上傳相關證明文件，並提供完整的歷史填報記錄查詢功能，協助您管理教學活動資料。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('teacher_profile.php'); ?>" class="page-link">
                            <div>
                                <div>個人資料</div>
                                <div class="page-description">教師專屬的個人資料管理中心，提供完整的個人基本資料查看與編輯功能。您可以更新聯絡資訊、專業領域，並管理各項帳戶設定，確保資料的準確性與即時性。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('my_records.php'); ?>" class="page-link">
                            <div>
                                <div>我的活動紀錄</div>
                                <div class="page-description">查看和管理您已填報的活動紀錄，提供完整的紀錄查詢、編輯和統計功能。您可以追蹤活動填報狀態，查看歷史紀錄，並進行必要的資料更新。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('activity_records_management.php'); ?>" class="page-link">
                            <div>
                                <div>活動紀錄管理</div>
                                <div class="page-description">進階的活動紀錄管理系統，提供更詳細的紀錄管理功能。支援批量操作、資料匯出、統計分析等進階功能，協助教師更有效率地管理活動紀錄。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- 管理員專區 -->
            <?php if (isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] === '管理員'): ?>
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="section-title">管理員專區</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('admin_recommendations.php'); ?>" class="page-link">
                            <div>
                                <div>推薦管理</div>
                                <div class="page-description">管理員專用的推薦報名管理系統，提供完整的推薦申請審核流程。您可以查看所有推薦報名申請，審核推薦資料，管理推薦流程狀態，並匯出詳細的推薦報名統計報表。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('school_management.php'); ?>" class="page-link">
                            <div>
                                <div>學校資料管理</div>
                                <div class="page-description">管理員專用的學校資料管理系統，提供完整的學校資訊維護功能。您可以新增、編輯、刪除學校資料，管理學校基本資訊，確保資料庫中學校資料的準確性和完整性。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- 其他實用功能 -->
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="section-title">其他實用功能</div>
                </div>
                <ul class="page-list">
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('check_admission_status.php'); ?>" class="page-link">
                            <div>
                                <div>入學狀態查詢</div>
                                <div class="page-description">提供入學申請狀態查詢功能，讓學生和家長能夠即時了解報名進度。您可以輸入相關資訊查詢申請狀態，獲取最新的審核結果和後續流程資訊。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('cooperation_upload.php'); ?>" class="page-link">
                            <div>
                                <div>就讀意願申請表</div>
                                <div class="page-description">提供學生填寫就讀意願與基本資料的專區。完成填寫後可送出表單，以表達對校系的申請意向，並作為後續審核與聯繫的重要依據。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- 用戶管理 -->
            <div class="sitemap-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="section-title">用戶管理</div>
                </div>
                <ul class="page-list">
                    <?php if (isset($_SESSION['username'])): ?>
                    <li class="page-item">
                        <a href="<?php echo getCorrectPath('logout.php'); ?>" class="page-link">
                            <div>
                                <div>登出</div>
                                <div class="page-description">提供安全的帳戶登出功能，確保您的個人資料安全。系統會完全清除您的登入狀態，保護您的隱私與帳戶安全，讓您安心使用平台服務。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item">
                        <a href="#" id="openLoginBtn" class="page-link">
                            <div>
                                <div>登入</div>
                                <div class="page-description">提供多種登入方式，包括傳統帳號密碼登入與Google帳號快速登入。登入後即可存取個人專屬功能，查看個人化內容，享受完整的平台服務體驗。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a href="#" id="openModalBtn" class="page-link">
                            <div>
                                <div>註冊</div>
                                <div class="page-description">新用戶註冊系統，支援多種身分角色選擇，包括老師、學生、行政人員等。提供簡潔的註冊流程，填寫基本資料後即可開始使用平台各項服務，享受個人化的使用體驗。</div>
                            </div>
                            <i class="fas fa-arrow-right page-icon"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- 返回首頁按鈕 -->
        <div class="back-to-home">
            <a href="<?php echo getCorrectPath('index.php'); ?>" class="back-btn">
                <i class="fas fa-home"></i>
                返回首頁
            </a>
        </div>
    </div>

    <script>
        // 處理登入和註冊按鈕點擊事件
        document.addEventListener('DOMContentLoaded', function() {
            const openLoginBtn = document.getElementById('openLoginBtn');
            const openModalBtn = document.getElementById('openModalBtn');
            
            if (openLoginBtn) {
                openLoginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // 觸發header中的登入按鈕
                    const headerLoginBtn = document.getElementById('openLoginBtn');
                    if (headerLoginBtn) {
                        headerLoginBtn.click();
                    }
                });
            }
            
            if (openModalBtn) {
                openModalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // 觸發header中的註冊按鈕
                    const headerRegisterBtn = document.getElementById('openModalBtn');
                    if (headerRegisterBtn) {
                        headerRegisterBtn.click();
                    }
                });
            }
        });
    </script>

<?php include("share/footer.php"); ?>

<!-- 浮動助手組件 -->
<?php include("share/chat_widget.php"); ?>
<?php include("share/ai_widget.php"); ?>
</body>
</html>
