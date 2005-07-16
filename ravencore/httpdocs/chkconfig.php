<?php

include "auth.php";

if($_GET[service] and $_GET[status]) {

  socket_cmd("chkconfig --level 3 $_GET[service] $_GET[status]");

  goto("$_SERVER[PHP_SELF]");

}

nav_top();

system("../bin/disp_chkconfig");

nav_bottom();

?>

