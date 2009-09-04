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

if ($_REQUEST['disable']) {
	$db->run("disable_module", Array(module => $_REQUEST['disable']));

	goto($_SERVER['PHP_SELF']);
}

if ($_REQUEST['enable']) {
	$db->run("enable_module", Array(module => $_REQUEST['enable']));

	goto($_SERVER['PHP_SELF']);
}

nav_top();

$info = $db->run('ravencore_info');

?>
<center>

<h3>RavenCore Info</h3>
<hr />

<?php

print _("Version") . ': ' . $info['version'] . '<br />';
if ($info['release'] != "1") print $info['release'] . '<br />';

?>

<p>

<table class="listpad">
<tr>
<th class="listpad">Module</th>
<th class="listpad">Installed</th>
<th class="listpad">Enabled</th>
<th class="listpad">&nbsp;</th>
</tr>
<?php

$installed = $status[modules_installed];

foreach (Array('web', 'mysql', 'mail', 'amavisd', 'postgrey', 'mrtg') as $service) {
	print '<tr><td class="listpad">' .$service . '</td>' .
		'<td class="listpad">' . ( in_array($service, $installed) ? "Yes" : "No" ) . '</td>' .
		'<td class="listpad">' . ( have_service($service) ? "Yes" : "No" ) . '</td>' .
		'<td class="listpad">' . ( in_array($service, $installed) ? ( have_service($service) ? '<a href="ravencore.php?disable=' . $service . '">disable</a>' : '<a href="ravencore.php?enable=' . $service . '">enable</a>' ) : "" ) . '</td>' .
		'</tr>';
}

?>
</table>

</center>

<?php

nav_bottom();

?>
