<?php
require_once __DIR__ . '/config.php';
include __DIR__ . '/share/header.php';

date_default_timezone_set('Asia/Taipei');
$now = date('Y-m-d H:i:s');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$conn = getDatabaseConnection();

// 前台載入時：若存在「已到發布時間但尚未發布」的草稿，立即寫入 published_at 並更新前台公告狀態，這樣不依賴排程或造訪委員會頁也會發布
$draft_due = null;
$check_draft = $conn->prepare("
    SELECT id, publish_at FROM continued_admission_result_announcements
    WHERE scope = 'all' AND year = ? AND published_at IS NULL AND publish_at IS NOT NULL AND publish_at != '' AND publish_at <= ?
    LIMIT 1
");
if ($check_draft) {
    $check_draft->bind_param("is", $year, $now);
    $check_draft->execute();
    $res = $check_draft->get_result();
    $draft_due = $res ? $res->fetch_assoc() : null;
    $check_draft->close();
}
if ($draft_due) {
    $up = $conn->prepare("UPDATE continued_admission_result_announcements SET published_at = NOW(), updated_at = NOW() WHERE scope = 'all' AND year = ?");
    if ($up) {
        $up->bind_param("i", $year);
        $up->execute();
        $up->close();
    }
    $source = "continued_admission_{$year}";
    $up2 = $conn->prepare("UPDATE bulletin_board SET status_code = 'published', updated_at = NOW() WHERE source = ? AND type_code = 'result'");
    if ($up2) {
        $up2->bind_param("s", $source);
        $up2->execute();
        $up2->close();
    }
}

// 讀取已發布公告（published_at 有值，且 publish_at <= now 或未設定）
$announcement = null;
$stmt = $conn->prepare("
    SELECT *
    FROM continued_admission_result_announcements
    WHERE scope = 'all'
      AND year = ?
      AND published_at IS NOT NULL
      AND (publish_at IS NULL OR publish_at = '' OR publish_at <= ?)
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("is", $year, $now);
    $stmt->execute();
    $announcement = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

// 科系名稱
$deptNameMap = [];
$deptRes = $conn->query("SELECT code, name FROM departments WHERE code != 'AA' ORDER BY code");
if ($deptRes) {
    while ($r = $deptRes->fetch_assoc()) {
        $deptNameMap[$r['code']] = $r['name'];
    }
}

function statusLabel(string $status, ?int $rank): string {
    if ($status === 'approved' || $status === 'AP') return '正取' . ($rank ? " {$rank} 號" : '');
    if ($status === 'waitlist' || $status === 'AD') return '備取' . ($rank ? " {$rank} 號" : '');
    if ($status === 'rejected' || $status === 'RE') return '不錄取';
    return $status;
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>續招錄取名單（<?php echo (int)$year; ?>）</title>
  <style>
    body { background:#f0f2f5; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
    .card { background:#fff; border:1px solid #f0f0f0; border-radius: 12px; padding: 18px; margin-bottom: 16px; }
    .title { font-size: 22px; font-weight: 800; margin: 0 0 8px 0; }
    .muted { color:#8c8c8c; font-size: 14px; }
    .dept { margin-top: 18px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; text-align: left; }
    th { background: #fafafa; font-weight: 700; }
    .badge { display:inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; border:1px solid; }
    .ok { background:#f6ffed; color:#135200; border-color:#b7eb8f; }
    .wait { background:#fffbe6; color:#ad6800; border-color:#ffe58f; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="title">續招錄取名單（<?php echo (int)$year; ?>）</div>
      <?php if (!$announcement): ?>
        <div class="muted">目前尚未公告或尚未到公告時間。</div>
      <?php else: ?>
        <div class="muted">公告時間：<?php echo htmlspecialchars($announcement['publish_at'] ?? $announcement['published_at'] ?? ''); ?></div>
        <div style="margin-top:12px; line-height:1.8;">
          <?php echo nl2br(htmlspecialchars($announcement['content'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($announcement): ?>
      <?php foreach ($deptNameMap as $deptCode => $deptName): ?>
        <?php
          $apps = [];
          $stmt2 = $conn->prepare("
              SELECT apply_no, name, status, admission_rank
              FROM continued_admission
              WHERE assigned_department = ?
                AND LEFT(apply_no, 4) = ?
                AND status IN ('approved','AP','waitlist','AD')
              ORDER BY
                CASE
                  WHEN status IN ('approved','AP') THEN 1
                  WHEN status IN ('waitlist','AD') THEN 2
                  ELSE 9
                END,
                admission_rank ASC,
                id ASC
          ");
          if ($stmt2) {
              $stmt2->bind_param("si", $deptCode, $year);
              $stmt2->execute();
              $apps = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
              $stmt2->close();
          }
          if (empty($apps)) continue;
        ?>
        <div class="card dept">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div style="font-weight: 800; font-size: 18px;"><?php echo htmlspecialchars($deptName); ?></div>
            <div class="muted">共 <?php echo count($apps); ?> 人</div>
          </div>
          <div style="margin-top:12px; overflow-x:auto;">
            <table>
              <thead>
                <tr>
                  <th style="width: 140px;">結果</th>
                  <th style="width: 160px;">報名編號</th>
                  <th>姓名</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($apps as $a): 
                  $rank = isset($a['admission_rank']) ? (int)$a['admission_rank'] : null;
                  $label = statusLabel((string)$a['status'], $rank);
                  $isOk = (string)$a['status'] === 'approved' || (string)$a['status'] === 'AP';
                ?>
                <tr>
                  <td>
                    <span class="badge <?php echo $isOk ? 'ok' : 'wait'; ?>">
                      <?php echo htmlspecialchars($label); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($a['apply_no'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($a['name'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
<?php $conn->close(); ?>




