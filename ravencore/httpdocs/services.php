<?php

include "auth.php";

req_admin();

$services = array();

if($_SESSION['status_mesg']) {

  $status = $_SESSION['status_mesg'];
  $_SESSION['status_mesg'] = '';

}

// get contents of $RC_ROOT/etc/services, explode on return character, split on the :, and chop off the .conf, to fill the package / service array

if($action == "run") {

  // authenticate $_GET[service] as an allowed service

  // make sure $_GET[service_cmd] can only be start, stop, or restart

  socket_cmd("service $_GET[service] $_GET[service_cmd]");

  $_SESSION['status_mesg'] = $_GET[service_cmd] . ' command sucessfull for ' . $_GET[service];

  goto("$_SERVER[PHP_SELF]");

}

nav_top();

print '<p>' . ($status ? $status : '&nbsp;') . '</p>';

?>

<table>

<tr><th>Service</th><th>Running</th><th>Start</th><th>Stop</th><th>Restart</th></tr>
<?php

$services = get_all_services();

foreach ($services as $val) {

  print '<tr><td>' . $val . '</td><td align=center>';

  $handle = popen("../sbin/wrapper is_service_running $val", "r");

  switch (trim(fread($handle, 5))) {

  case 'Yes':

    $running = '<img src="images/solidgr.gif" border=0>';
    $start = '<img src="images/start_grey.gif" border=0>';
    $stop = '<a href="services.php?action=run&service=' . $val . '&service_cmd=stop"><img src="images/stop.gif" border=0></a>';

    break;

  case 'No':

    $running = '<img src="images/solidrd.gif" border=0>';
    $start = '<a href="services.php?action=run&service=' . $val . '&service_cmd=start"><img src="images/start.gif" border=0></a>';
    $stop = '<img src="images/stop_grey.gif" border=0>';

    break;

  }

  pclose($handle);

  print $running . '</td>
<td>' . $start . '</td>
<td>' . $stop . '</td>
<td align=center><a href="services.php?action=run&service=' . $val . '&service_cmd=restart"><img src="images/restart.gif" border=0></a></td></tr>';

}

?>

</table>

<?php

nav_bottom();

?>