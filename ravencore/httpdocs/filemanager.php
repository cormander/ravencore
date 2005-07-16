<?php

include "auth.php";

if(!$did) goto("users.php");

$sql = "select login, passwd from sys_users s, domains d where d.id = '$did' and d.suid = s.id";
$result = mysql_query($sql);

$row = mysql_fetch_array($result);

$_SESSION['user'] = $row[login];
$_SESSION['password'] = $row[passwd];

$_SESSION['server'] = "localhost";
$_SESSION['language'] = "english";
$_SESSION['port'] = 21;

goto("filemanager/");

?>