<?php
/*
                 RavenCore Hosting Control Panel
               Copyright (C) 2005  Corey Henderson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

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