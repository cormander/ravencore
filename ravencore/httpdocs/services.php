<?php

include "auth.php";

req_admin();

$services = array();

// get contents of $RC_ROOT/etc/services, explode on return character, split on the :, and chop off the .conf, to fill the package / service array

if($action == "run") {

  // authenticate $_GET[service] as an allowed service

  // make sure $_GET[service_cmd] can only be start, stop, or restart

  socket_cmd("service $_GET[service] $_GET[service_cmd]");

  goto("$_SERVER[PHP_SELF]");

}

nav_top();

?>

<p>

<table>

<tr><th>Service</th><th>Running</th><th>Restart</th><th>Start</th><th>Stop</th></tr><?php

$services = get_all_services();

foreach ($services as $val) {

  print '<tr><td>' . $val . '</td><td>';

  $handle = popen("../sbin/wrapper is_service_running $val", "r");

  print fread($handle, 5);  

  pclose($handle);

  print '</td><td><a href="services.php?action=run&service=' . $val . '&service_cmd=restart">/\</a></td><td><a href="services.php?action=run&service=' . $val . '&service_cmd=start">+</a></td><td><a href="services.php?action=run&service=' . $val . '&service_cmd=stop">-</a></td>';
  
  print '</tr>';

}

?>

</table>

<?php

nav_bottom();

?>