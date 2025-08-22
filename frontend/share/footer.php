<!-- footer.php -->
<footer class="footer">
	<nav class="footer-nav" aria-label="Footer navigation">
		<a href="/Topics-frontend/frontend/index.php" class="footer-link">首頁</a>
		<a href="/Topics-frontend/frontend/QA.php" class="footer-link">認識產學合作</a>
		<a href="/Topics-frontend/frontend/AI.php" class="footer-link">AI產學合作</a>
		<a href="/Topics-frontend/frontend/chat_settings.php" class="footer-link">💬 聊天設置</a>
	</nav>
	<div class="footer-copy">
		<p>© 2025 康寧大學產學合作平台</p>
	</div>
</footer>

<style>
	/* 讓頁面內容不足時，footer 仍貼齊視窗底部 */
	html, body {
		height: 100%;
		margin: 0;
		padding: 0;
	}

	body {
		display: flex;
		flex-direction: column;
		min-height: 100vh;
	}

	/* 若頁面使用 <main>，讓其撐開空間 */
	main { flex: 1; }

	/* 與 company.php 一致的底色與樣式（縮小高度） */
	.footer {
		background: rgba(217, 229, 234, 0.95);
		backdrop-filter: blur(10px);
		box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1);
		padding: 18px 0; /* 原 30px 0，縮小高度 */
		margin-top: auto;
		position: relative;
		z-index: 100;
	}

	.footer-nav {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		gap: 10px 16px; /* 原 14px 24px */
		padding: 8px 8px 4px; /* 原 16px 12px 6px */
		font-size: 0.9rem; /* 稍微縮小字級 */
	}

	.footer-link {
		text-decoration: none;
		padding: 0 4px; /* 原 0 6px */
	}

	/* 分隔線樣式：沿用主題色 */
	.footer-link + .footer-link::before {
		content: "|";
		margin-right: 10px; /* 原 12px */
		opacity: .7;
	}

	.footer-copy {
		text-align: center;
		font-size: 11px; /* 原 12px */
		line-height: 1.5;
		padding: 4px 10px 10px; /* 原 6px 12px 16px */
	}

	@media (max-width: 768px) {
		.footer-copy { font-size: 10px; }
	}
</style>
