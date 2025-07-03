<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <?php

    include("db.php");
    $帳號=mysqli_real_escape_string($link,$_POST["username"]);
    $姓名=mysqli_real_escape_string($link,$_POST["name"]);
    $電子郵件=mysqli_real_escape_string($link,$_POST["email"]);
    $身分=mysqli_real_escape_string($link,$_POST["role"]);
    $密碼 = mysqli_real_escape_string($link, $_POST["password"]);
    $確認密碼 = mysqli_real_escape_string($link, $_POST["confirm_password"]);

    if ($密碼 !== $確認密碼) {
        echo "密碼與確認密碼不一致，請重新輸入。";
        exit;
    }
    

    $sql="INSERT INTO `user` (`帳號`, `姓名`, `電子郵件`, `身分`,`密碼`) VALUES ('$帳號', '$姓名', '$電子郵件', '$身分' , '$密碼');";
    mysqli_query($link,$sql)  or die(mysqli_error($link));
    echo "成功輸入";
    ?>
</body>
</html>