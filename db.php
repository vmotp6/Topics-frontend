<?php
    $資料庫IP = "localhost";
    $資料庫帳號 = "root";
    $資料庫密碼 = "";
    $資料庫名稱 = "topics_good";
    $link = @new mysqli($資料庫IP, $資料庫帳號, $資料庫密碼, $資料庫名稱);

    if ($link->connect_error != "") {
        die("資料庫連線失敗");
    } else {
        $link->query("SET NAMES 'utf8'");
    }
?>