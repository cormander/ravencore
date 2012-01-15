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

$showall = ( $_GET['showall'] ? 'showall=1&' : '' );

$services = array();
// get contents of $RC_ROOT/etc/services, explode on return character, split on the :, and chop off the .conf, to fill the package / service array
if ($action == "run") {
  // authenticate $_GET[service] as an allowed service
  // make sure $_GET[service_cmd] can only be start, stop, or restart
  $db->run("service", Array('service' => $_GET['service'], 'cmd' => $_GET['service_cmd']));

  if (!$_SESSION['status_mesg']) $_SESSION['status_mesg'] = $_GET['service_cmd'] . ' command sucessfull for ' . $_GET['service'];

  openfile($_SERVER[PHP_SELF] . ( $_GET['showall'] ? '?showall=1' : '' ) );

}

nav_top();

?>

<table class="listpad">

<tr>
<th class="listpad"><?php e_('Service')?></th>
<th class="listpad"><?php e_('Running')?></th>
<th class="listpad"><?php e_('Start')?></th>
<th class="listpad"><?php e_('Stop')?></th>
<th class="listpad"><?php e_('Restart')?></th>
</tr>
<?php

if($_GET['showall']) {
  $services = $db->run('list_system_daemons');
} else {
  $services = $status['services'];
}

//

foreach ($services as $val) {
	print '<tr><td class="listpad">' . $val . '</td><td class="listpad" align=center>';

	if ($db->run("service_running", Array('service' => $val))) {

			$running = '<img src="images/solidgr.gif" border=0>';
			$start = '<img src="images/start_grey.gif" border=0>';
			$stop = '<a href="services.php?' . $showall . 'action=run&service=' . $val . '&service_cmd=stop"><img src="images/stop.gif" border=0></a>';

	} else {

		$running = '<img src="images/solidrd.gif" border=0>';
		$start = '<a href="services.php?' . $showall . 'action=run&service=' . $val . '&service_cmd=start"><img src="images/start.gif" border=0></a>';
		$stop = '<img src="images/stop_grey.gif" border=0>';

	}

	print $running . '</td>
<td class="listpad">' . $start . '</td>
<td class="listpad">' . $stop . '</td>
<td class="listpad" align=center><a href="services.php?' . $showall . 'action=run&service=' . $val . '&service_cmd=restart"><img src="images/restart.gif" border=0></a></td></tr>';
}

?>

</table>

<?php

if(!$_GET['showall']) {

  print '<br/><a href="' . $_SERVER['PHP_SELF'] . '?showall=1">List all registered server daemons</a>';

}

nav_bottom();

?>
