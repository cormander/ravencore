<?php

include "auth.php";

req_admin();

$_SESSION['login'] = $CONF[MYSQL_ADMIN_USER];
$_SESSION['passwd'] = $CONF[MYSQL_ADMIN_PASS];

goto("phpmyadmin/");

?>