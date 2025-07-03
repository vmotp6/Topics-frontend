<?php
session_start();
?>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <?php
    include("db.php");
    $帳號 = mysqli_real_escape_string($link, $_POST["username"]);
    $密碼 = mysqli_real_escape_string($link, $_POST["password"]);
    $sql = "SELECT * FROM `user` WHERE `帳號` = '$帳號' AND `密碼` = '$密碼'";
    $結果 = mysqli_query($link, $sql)  or die(mysqli_error($link));
    if (mysqli_num_rows($結果) > 0) {
        $row = mysqli_fetch_assoc($結果);
        $_SESSION["username"] = $row["帳號"];
        $_SESSION["password"] = $row["密碼"];
        echo "<span'>登入成功</span>";
    } else {
        echo "<span style='color:red;'>登入失敗</span>";
    }
    ?>
</body>
</html>