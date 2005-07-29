<?php

include "auth.php";

req_admin();

if($_GET[cmd]) {

  socket_cmd("system $_GET[cmd]");
  
  alert("The system will now $_GET[cmd]");
  
}

nav_top();

?>

<table>
<tr><th colspan="2">System</th></tr>
<tr>
<td width=300 valign=top>
<a href="services.php" onmouseover="show_help('Stop/Start system services such as httpd, mail, etc');" onmouseout="help_rst();">System Services</a>

<p>

<a href="sessions.php" onmouseover="show_help('View who is logged into the server, and where from');" onmouseout="help_rst();">Login Sessions</a>

<p>

<?php
/*
<a href="iptables.php" onmouseover="show_help('Manage the server firewall');" onmouseout="help_rst();">Firewall Configuration</a>

<p>

*/
?>
<a href="chkconfig.php" onmouseover="show_help('Services that automatically start when the server boots up');" onmouseout="help_rst();">Startup Services</a>

<p>

<?php

if(have_service("web")) {
  
  // commented out because it doesn't currently work
  //print '<a href="crontab.php" onmouseover="show_help(\'Manage Vixie Crontab for the server\');" onmouseout="help_rst();">Manage Crontab</a>';
  
}

?>

<p>

<?php

if(have_service("dns")) {

?>
<a href="dns_def.php" onmouseover="show_help('The DNS records that are setup for a domain by default when one is added to the server');" onmouseout="help_rst();">Default DNS</a>

<p>

<?php

   }

print '<a href="change_password.php" onmouseover="show_help(\'Change the admin password\');" onmouseout="help_rst();">Change Admin Password</a>';

?>

</td><td valign=top>

<p>
<a href="phpmyadmin_admin.php" target=_blank onmouseover="show_help('Load phpMyAdmin for all with MySQL admin user');" onmouseout="help_rst();">Admin MySQL Databases</a>
</p>

<a href="sysinfo" target=_blank onmouseover="show_help('View general system information');" onmouseout="help_rst();">System Info</a>

<p>

<a href="phpinfo.php" target=_blank onmouseover="show_help('View output from the phpinfo() function');" onmouseout="help_rst();">PHP Info</a>

</td>
</tr></table>

<p>
&nbsp;
<p>

<?php

print '<a href="system.php?cmd=reboot" onclick="return confirm(\'Are you sure you wish to reboot the system?\');" onmouseover="show_help(\'Reboot the server\');" onmouseout="help_rst();">Reboot Server</a>';

?>

<p>
&nbsp;
<p>

<?php

print '<a href="system.php?cmd=shutdown" onclick="return confirm(\'You are about to shutdown the system. There is no way to bring the server back online with this software. Are you sure you wish to shutdown the system?\');" onmouseover="show_help(\'Shutdown the server\');" onmouseout="help_rst();">Shutdown Server</a>';

nav_bottom();

?>