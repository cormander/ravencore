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

if ($_GET['cmd']) {
  $db->do_raw_query("system " . $_GET['cmd']);

  alert(__("The system will now $_GET[cmd]"));

}

nav_top();

?>

<table class="listpad">
<tr><th colspan="2" class="listpad"><?php e_('System')?></th></tr>
<tr>
<td width=300 valign=top class="listpad">

<p>
<a href="ravencore.php" onmouseover="show_help('<?php e_('Information about your RavenCore installation')?>');" onmouseout="help_rst();"><?php e_('RavenCore Info')?></a>
</p>

<a href="services.php" onmouseover="show_help('<?php e_('Stop/Start system services such as httpd, mail, etc')?>');" onmouseout="help_rst();"><?php e_('System Services')?></a>

<p>

<a href="chkconfig.php" onmouseover="show_help('<?php e_('Services that automatically start when the server boots up')?>');" onmouseout="help_rst();"><?php e_('Startup Services')?></a>

<p>

<a href="ip_addresses.php" onmouseover="show_help('<?php e_('Manage IP addresses')?>');" onmouseout="help_rst();"><?php e_('IP Addresses')?></a>

<p>

<a href="sessions.php" onmouseover="show_help('<?php e_('View who is logged into the server, and where from')?>');" onmouseout="help_rst();"><?php e_('Login Sessions')?></a>

<?php

print '<p>';

if (have_service("web")) {
  // commented out because it doesn't currently work
  // print '<a href="crontab.php" onmouseover="show_help(\'Manage Vixie Crontab for the server\');" onmouseout="help_rst();">Manage Crontab</a>';
  // print '<p>';
}

if ( have_service("dns") and ! $status['db_panic'] ) {

    ?>
<a href="dns_def.php" onmouseover="show_help('<?php e_('The DNS records that are setup for a domain by default when one is added to the server')?>');" onmouseout="help_rst();"><?php e_('Default DNS')?></a>

<p>

<?php

}

print '<a href="change_password.php" onmouseover="show_help(\'' . __('Change the admin password') . '\');" onmouseout="help_rst();">' . __('Change Admin Password') . '</a>';

?>

<p>

<?php

print '<a href="/debug.php">Debugging</a>';

?>

</td><td valign=top class="listpad">

<p>
<a href="phpmyadmin_admin.php" target=_blank onmouseover="show_help('<?php e_('Load phpMyAdmin for all with MySQL admin user')?>');" onmouseout="help_rst();"><?php e_('Admin MySQL Databases')?></a>
</p>

<p>
<a href="jta/" target=_blank onmouseover="show_help('<?php e_('SSH Terminal to your server via a Java(TM) Applet');
?>');" onmouseout="help_rst();"><?php e_('SSH Terminal');
?></a>
</p>

<p>
<a href="sysinfo/index.php?lng=<?php print $locales[$current_locale]['sysinfo'];
?>" target="_blank" onmouseover="show_help('<?php e_('View general system information')?>');" onmouseout="help_rst();"><?php e_('System Info')?></a>
</p>

<p>
<a href="phpinfo.php" target=_blank onmouseover="show_help('<?php e_('View output from the phpinfo() function')?>');" onmouseout="help_rst();"><?php e_('PHP Info')?></a>
</p>

<?php

if( have_service("mrtg") ) {
?>
<p>
<a href="mrtg.php" target=_blank onmouseover="show_help('<?php e_('MRTG')?>');" onmouseout="help_rst();"><?php e_('MRTG')?></a>
</p>
<?php
}
?>

<hr>

<a href="mail_queue.php"><?php e_('View Mail Queue')?></a>

</td>
</tr></table>

<p>

<p>

<?php

print '<a href="system.php?cmd=reboot" onclick="return confirm(\'' . __('Are you sure you wish to reboot the system?') . '\');" onmouseover="show_help(\'' . __('Reboot the server') . '\');" onmouseout="help_rst();">' . __('Reboot Server') . '</a>';

?>

<p>

<p>

<?php

print '<a href="system.php?cmd=shutdown" onclick="return confirm(\'' . __('You are about to shutdown the system. There is no way to bring the server back online with this software. Are you sure you wish to shutdown the system?') . '\');" onmouseover="show_help(\'' . __('Shutdown the server') . '\');" onmouseout="help_rst();">' . __('Shutdown Server') . '</a>';

nav_bottom();

?>
