
<!DOCTYPE html>
<?php include 'header.php'; ?>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>招生公告欄</title>
  <style>
    :root {
      --bg: #f9fafb;
      --panel: #ffffff;
      --border: #d1d5db;
      --text: #111827;
      --muted: #6b7280;
      --accent: #2563eb; /* 藍色強調 */
      --danger: #dc2626;
      --radius: 10px;
      --shadow: rgba(0,0,0,0.1);
    }

    body {
      margin: 0;
      font-family: "Microsoft JhengHei", Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    header {
      background: var(--accent);
      color: #fff;
      padding: 16px;
      text-align: center;
      font-size: 20px;
      font-weight: bold;
    }

    .page-hero {
      max-width: 1000px;
      margin: 24px auto;
      padding: 0 16px;
    }
    .page-hero h1 {
      margin: 0 0 8px;
      font-size: 28px;
    }
    .page-hero p {
      margin: 0;
      color: var(--muted);
    }

    .controls {
      max-width: 1000px;
      margin: 0 auto 16px;
      padding: 0 16px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .select, .search {
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      background: var(--panel);
    }
    .search { flex: 1; }

    .grid {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 16px 48px;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 16px;
    }

    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 4px 10px var(--shadow);
      display: flex;
      flex-direction: column;
    }
    .card-body {
      padding: 16px;
      display: grid;
      gap: 8px;
    }
    .title {
      font-weight: bold;
      font-size: 18px;
    }
    .desc {
      color: var(--muted);
      font-size: 14px;
    }
    .meta {
      font-size: 13px;
      color: var(--muted);
    }
    .card-footer {
      margin-top: auto;
      padding: 12px 16px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn {
      padding: 6px 12px;
      border-radius: var(--radius);
      border: 1px solid var(--accent);
      background: var(--accent);
      color: #fff;
      cursor: pointer;
    }
    .btn:hover { opacity: 0.9; }
  </style>
</head>
<body>

  <header>某某大學招生公告欄</header>

  <section class="page-hero">
    <h1>最新公告</h1>
    <p>提供招生考試、報名日期、面試資訊與重要通知。</p>
  </section>

  <section class="controls">
    <select class="select" id="filter-type">
      <option value="all">所有公告</option>
      <option value="exam">考試資訊</option>
      <option value="interview">面試通知</option>
      <option value="result">錄取結果</option>
      <option value="general">一般公告</option>
    </select>
    <input class="search" id="search" type="search" placeholder="搜尋公告標題或內容…" />
  </section>

  <main class="grid" id="grid">
    <!-- 公告卡片範例 -->
    <article class="card" data-type="exam">
      <div class="card-body">
        <div class="title">112 學年度入學考試報名開始</div>
        <div class="desc">報名日期：2025/01/10 至 2025/02/15，請至招生系統完成線上報名。</div>
        <div class="meta">公告日期：2024/12/20</div>
      </div>
      <div class="card-footer">
        <button class="btn">詳細資訊</button>
        <span class="meta">類型：考試資訊</span>
      </div>
    </article>

    <article class="card" data-type="interview">
      <div class="card-body">
        <div class="title">研究所面試通知</div>
        <div class="desc">面試日期：2025/03/05，地點：行政大樓 3 樓會議室。</div>
        <div class="meta">公告日期：2025/02/20</div>
      </div>
      <div class="card-footer">
        <button class="btn">詳細資訊</button>
        <span class="meta">類型：面試通知</span>
      </div>
    </article>
  </main>

  <script>
    const grid = document.getElementById('grid');
    const search = document.getElementById('search');
    const filterType = document.getElementById('filter-type');

    function applyFilters() {
      const q = (search.value || '').toLowerCase().trim();
      const type = filterType.value;

      grid.querySelectorAll('.card').forEach(card => {
        const title = card.querySelector('.title')?.textContent.toLowerCase() || '';
        const desc = card.querySelector('.desc')?.textContent.toLowerCase() || '';
        const matchText = !q || title.includes(q) || desc.includes(q);
        const matchType = type === 'all' || card.dataset.type === type;

        card.style.display = (matchText && matchType) ? '' : 'none';
      });
    }

    search.addEventListener('input', applyFilters);
    filterType.addEventListener('change', applyFilters);
    applyFilters();
  </script>
</body>
</html>