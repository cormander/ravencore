<?php

include "auth.php";

if(!$did or !$db or !$dbu) goto("users.php?uid=$uid");

$sql = "select name, login, passwd from data_bases d, data_base_users u where db_id = d.id and d.id = '$db' and u.id = '$dbu' and did = '$did'";
$result = mysql_query($sql);

$row = mysql_fetch_array($result);

$_SESSION['name'] = $row[name];
$_SESSION['login'] = $row[login];
$_SESSION['passwd'] = $row[passwd];

goto("phpmyadmin/");

?>