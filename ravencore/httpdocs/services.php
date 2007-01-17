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
// get contents of $RC_ROOT/etc/services, explode on return character, split on the :, and chop off the .conf, to fill the package / service array
if ($action == "run")
{
  // authenticate $_GET[service] as an allowed service
  // make sure $_GET[service_cmd] can only be start, stop, or restart
  $db->do_raw_query("service " . $_GET['service'] . " " . $_GET['service_cmd']);
  
  if (!$_SESSION['status_mesg']) $_SESSION['status_mesg'] = $_GET['service_cmd'] . ' command sucessfull for ' . $_GET['service'];
  
  goto($_SERVER[PHP_SELF]);

} 

nav_top();

?>

<table>

<tr>
<th><?php e_('Service')?></th>
<th><?php e_('Running')?></th>
<th><?php e_('Start')?></th>
<th><?php e_('Stop')?></th>
<th><?php e_('Restart')?></th>
</tr>
<?php

$services = $status['services'];

foreach ($services as $val)
{
    print '<tr><td>' . $val . '</td><td align=center>';

    if( $db->do_raw_query("service_running " . $val) )
      {

            $running = '<img src="images/solidgr.gif" border=0>';
            $start = '<img src="images/start_grey.gif" border=0>';
            $stop = '<a href="services.php?action=run&service=' . $val . '&service_cmd=stop"><img src="images/stop.gif" border=0></a>';

      }
    else
      {
	
	$running = '<img src="images/solidrd.gif" border=0>';
	$start = '<a href="services.php?action=run&service=' . $val . '&service_cmd=start"><img src="images/start.gif" border=0></a>';
	$stop = '<img src="images/stop_grey.gif" border=0>';
	
    } 

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
