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

req_service("web");

clearstatcache();

if($action == "delete") {

  //make sure we don't decend a directory, or else we can delete any file on the server
  //also make sure we don't delete a non processed log file
  if(ereg("\.\.", $_POST[log_file]) or !ereg("\.processed", $_POST[log_file])) alert("Unable to delete log file");
  else {

    delete_log($did, $_POST[log_file]);
    
    goto("log_manager.php?did=$did");

  }

} else if($action == "compress" or $action == "decompress") {

  if(ereg("\.\.", $_GET[log_file])) alert("Unable to $action log file");
  else {

    socket_cmd("log_compress --$action $domain_name $_GET[log_file]");
    
    goto("log_manager.php?did=$did");
    
  }

} else if($action == "toggle") {

  update_parameter($did, 'log_rotate', $_POST[toggle]);

  socket_cmd("rehash_logrotate");

  goto("log_manager.php?did=$did");

} else if($action == "update") {

  if(!$_POST[filesize]) {

    $_POST[log_rotate_size] = "NULL";
    $_POST[log_rotate_size_ext] = "";

  }

  else $_POST[log_rotate_size] = "'" . $_POST[log_rotate_size] . "'";

  if(!$_POST[when]) $_POST[log_when_rotate] = "";

  if(!$_POST[log_compress]) $_POST[log_compress] = "no";

  update_parameter($did, 'log_rotate_num', $_POST[log_rotate_num]);
  update_parameter($did, 'log_mail_addr', $_POST[log_mail_addr]);
  update_parameter($did, 'log_when_rotate', $_POST[log_when_rotate]);
  update_parameter($did, 'log_rotate_size', $_POST[log_rotate_size]);
  update_parameter($did, 'log_compress', $_POST[log_compress]);
  update_parameter($did, 'log_rotate_size_ext', $_POST[log_rotate_size_ext]);

  socket_cmd("rehash_logrotate");

  goto("log_manager.php?did=$did");

}

if(!$did) req_admin();
  
nav_top();

$sql = "select * from domains where host_type = 'physical'";
if($did) $sql .= " and id = '$did'";
$sql .= " order by name";
$result = mysql_query($sql);

$num = mysql_num_rows($result);

if($num != 0) {
  
  while( $row = mysql_fetch_array($result) ) {
    
    print '<form method="post" onsubmit="return confirm(\'Are you sure you wish to delete this log file?\');">Log files for <a href="domains.php?did=' . $row[id] . '" onmouseover="show_help(\'Manage ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a>';

    if(!$did) print '- <a href="log_manager.php?did=' . $row[id] . '" onmouseover="show_help(\'Go to log rotation manager for ' . $row[name] . '\');" onmouseout="help_rst();">Log Rotation</a>';

    print '<p><table><tr><th width=175>Log Name</th>
<th width=100>Compression</th>
<th width=100>File Size</th>
<th>&nbsp;</th>
</tr>';
    
    $log_location = "$CONF[VHOST_ROOT]/$row[name]/logs";

    $handle = popen("/bin/ls -1 $log_location","r") or die("Unable to ls $log_location");

    $log_data = "";

    while( !feof($handle) ) $log_data .= fread($handle, 1024);
    
    pclose($handle);
    
    $log_files = explode("\n",$log_data);
    
    foreach($log_files as $log) {
      
      if($log != "") {

	if($size = filesize("$log_location/$log")) {

	  $size = readable_size($size);

	}
	
	print '<tr><td>';

	if($size) print '<a href="download_log.php?did=' . $row[id] . '&log_file=' . $log . '" onmouseover="show_help(\'Download the ' . $row[name] . ' ' . $log . '\');" onmouseout="help_rst();">';

	print $log;

	if($size) print '</a>';

	print '</td><td>';

	if($size and ereg(".gz$", $log)) print '<a href="log_manager.php?did=' . $did . '&log_file=' . $log . '&action=decompress">-</a>';
	else if($size) print '<a href="log_manager.php?did=' . $did . '&log_file=' . $log . '&action=compress">+</a>';
	else print '&nbsp;';

	print '</td><td>' . $size . '</td>
<td><input type=radio name=log_file ';
	if(ereg("\.processed", $log)) print 'value=' . $log;
	else print 'disabled';
	print '></td></tr>';
	
      }
      
    }

    print '<tr><td align=right colspan=3><input type=submit value="Delete"></td></tr></table>
<input type=hidden name=action value=delete>
<input type=hidden name=did value=' . $row[id] . '>
</form>
';
    
  }

  if($did) {

    //the while statment kills the row data, get it again
    
    $sql = "select * from domains where id = '$did'";
    $result = mysql_query($sql);
    
    $row = mysql_fetch_array($result);
    
    print '<p>
<form method=post name=status>
Custom log rotation for ' . $row[name] . ' is ';
    
    if($row[logrotate] == "on") print 'ON <a href="javascript:document.status.submit();" onclick="return confirm(\'Are you sure you wish to turn off the custom log rotation for ' . $row[name] . '?\');" onmouseover="show_help(\'Turn OFF log rotation for ' . $row[name] . '\');" onmouseout="help_rst();">*</a> <input type=hidden name=toggle value=off>';
    else print ' OFF <a href="javascript:document.status.submit();" onmouseover="show_help(\'Turn ON log rotation for ' . $row[name] . '\');" onmouseout="help_rst();">*</a> <input type=hidden name=toggle value=on>';
    
    print '<input type=hidden name=action value=toggle>
<input type=hidden name=did value = ' . $did . '>
</form>';
    
    if($row[logrotate] == "on") {
    
      print '<script type="text/javascript">
<!--
function validate_form(f) {

if(!f.log_rotate_num.value) {

alert("You must choose how many log files you wish to keep!");

return false;

}

if(!f.filesize.checked && !f.when.checked) {

alert("You must make a rotation selection: filesize, date, or both");

return false;

}

return true;

}
-->
</script>
<form method=post name=logrotate onsubmit="return validate_form(this);">
Keep <input type=text size=2 name=log_rotate_num value=' . $row[log_rotate_num] . '> log files<p>
Rotate by -
<br>
<table><tr><td rowspan=2 valign=middle>
<input type=checkbox name=filesize value=true';
      if($row[log_rotate_size]) print ' checked';
      print '> Filesize: <input type=text size=4 name=log_rotate_size value=' . $row[log_rotate_size] . '>
</td><td>K <input type=radio name=log_rotate_size_ext value=k';
      if($row[log_rotate_size_ext] == "k") print ' checked';
      print '></td></tr>
<tr><td>MB <input type=radio name=log_rotate_size_ext value=M';
      if($row[log_rotate_size_ext] == "M") print ' checked';
      print '></tr></tr>
</table>
<br>
<table><tr><td rowspan=3 valign=top>
<input type=checkbox name=when value=true';
      if($row[log_when_rotate]) print ' checked';
      print '> Date:</td><td><input type=radio name=log_when_rotate value=daily';
      if($row[log_when_rotate] == "daily") print ' checked';
      print '> Daily</td></tr>
<tr><td><input type=radio name=log_when_rotate value=weekly';
      if($row[log_when_rotate] == "weekly") print ' checked';
      print '> Weekly</td></tr>
<tr><td><input type=radio name=log_when_rotate value=monthly';
      if($row[log_when_rotate] == "monthly") print ' checked';
      print '> Monthly</td></tr>
</table>
<br>
Email about-to-expire files to: <input type=text size=30 name=log_mail_addr value="' . $row[log_mail_addr] . '">
<br>
<input type=checkbox name=log_compress value=yes';
      if($row[log_compress] == "yes") print ' checked';
      print '> Compress log files
<p>
<input type=submit value=Update><input type=hidden name=action value=update>
</form>
';
      
    }
    
  }
  
} else {

  print "No domains setup, so there are no Log files";

}
 
nav_bottom();

?>