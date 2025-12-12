<?php
require_once 'session_config.php';
require_once 'config.php';

$conn = getDatabaseConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$video = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND published = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $video = $res->fetch_assoc();
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $video ? htmlspecialchars($video['title'], ENT_QUOTES) : '影片播放'; ?></title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'Noto Sans TC','Microsoft JhengHei',sans-serif;background:#f6f8fb;margin:0;padding:20px}
        .container{max-width:900px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 8px 30px rgba(38,53,78,0.06)}
        .video-box{background:#000;border-radius:6px;overflow:hidden}
        video{width:100%;height:auto;display:block}
        h1{font-size:20px;margin:14px 0}
        .meta{color:#666;margin-bottom:12px}
        .desc{white-space:pre-wrap;color:#333}
        a.back{display:inline-block;margin-bottom:12px;color:#2196f3;text-decoration:none}
    </style>
</head>
<body>
<?php include('share/header.php'); ?>
<div class="container">
    <a class="back" href="radio.php">← 返回影片列表</a>

    <?php if (!$video): ?>
        <div style="padding:40px;text-align:center;color:#777">找不到影片或尚未發布。</div>
    <?php else: ?>
        <div class="video-box">
            <video controls preload="metadata" poster="<?php echo htmlspecialchars($video['thumbnail_url'], ENT_QUOTES); ?>">
                <source src="<?php echo htmlspecialchars($video['video_url'], ENT_QUOTES); ?>" type="video/mp4">
                您的瀏覽器不支援影片播放。
            </video>
        </div>
        <h1><?php echo htmlspecialchars($video['title'], ENT_QUOTES); ?></h1>
        <div class="meta">分類：<?php echo htmlspecialchars($video['category'] ?? '未分類'); ?>　｜　長度：<?php echo htmlspecialchars($video['duration'] ?? ''); ?></div>
        <div class="desc"><?php echo nl2br(htmlspecialchars($video['description'] ?? '')); ?></div>
    <?php endif; ?>

</div>
<?php include('share/footer.php'); ?>
</body>
</html>