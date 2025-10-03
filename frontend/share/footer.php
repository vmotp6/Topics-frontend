<!-- footer.php -->
<footer class="footer">
    <nav class="footer-nav" aria-label="Footer navigation">
        <a href="/Topics-frontend/frontend/index.php" class="footer-link">首頁</a>
        <a href="/Topics-frontend/frontend/QA.php" class="footer-link">認識招生平台</a>
        <a href="/Topics-frontend/frontend/AI.php" class="footer-link">AI招生平台</a>
        <a href="/Topics-frontend/frontend/chat_settings.php" class="footer-link">💬 聊天設置</a>
    </nav>
    <div class="footer-copy">
        <p>© 2025 康寧大學招生平台</p>
    </div>
</footer>

<style>
    /* 只在非推薦報名頁面應用 flexbox 佈局 */
    body:not(.recommend-page-wrapper) {
        height: 100%;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* 撐開內容，footer 才能吸底 */
    main {
        flex: 1;
    }

    .footer {
        background: rgba(217, 229, 234, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1);
        padding: 18px 0;
        margin-top: auto;   /* 這個才是吸底關鍵 */
        position: relative; /* ❌ 不要再用 fixed */
        width: 100%;
        z-index: 100;
    }

    /* 推薦報名頁面的 footer 樣式 */
    .recommend-page-wrapper + .footer {
        margin-top: 0;
        position: relative;
    }

    /* 確保推薦報名頁面的 footer 正確顯示 */
    .recommend-page-wrapper {
        position: relative;
        min-height: 100vh;
    }

    .recommend-page-wrapper .footer {
        margin-top: 0;
        position: relative;
    }

    .footer-nav {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px 16px;
        padding: 8px 8px 4px;
        font-size: 0.9rem;
    }

    .footer-link {
        text-decoration: none;
        padding: 0 4px;
    }

    .footer-link + .footer-link::before {
        content: "|";
        margin-right: 10px;
        opacity: .7;
    }

    .footer-copy {
        text-align: center;
        font-size: 11px;
        line-height: 1.5;
        padding: 4px 10px 10px;
    }

    @media (max-width: 768px) {
        .footer-copy {
            font-size: 10px;
        }
    }
</style>
