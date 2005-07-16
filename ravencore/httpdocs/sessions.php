<?php

include "auth.php";

req_admin();

if($action == "delete") {

  $sql = "delete from sessions where id = '$_POST[session]' and session_id != '$session_id'";
  mysql_query($sql);

  goto($_SERVER[PHP_SELF]);

}

nav_top();

$sql = "select * from sessions";
$result = mysql_query($sql);

print '<form method=post><table width=600><tr><th width=20%>Login</th><th width=20%>IP Address</th><th width=20%>Session Time</th><th width=20%>Idle Time</th><th width=20%>Delete</th></tr>';

while( $row = mysql_fetch_array($result) ) {

  $sql = "select ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) ) - ( ( to_days(created) * 24 * 60 * 60 ) + time_to_sec(created) ) as total, ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) ) - ( ( to_days(idle) * 24 * 60 * 60 ) + time_to_sec(idle) ) as idle from sessions where id = '$row[id]'";
  $result_session = mysql_query($sql);

  $row_session = mysql_fetch_array($result_session);

  print '<tr><td>';

  if($row[session_id] == $session_id) print '<b>' . $row[login] . '</b>';
  else print $row[login];  
  
  //if the session is longer then an hour, make the date_disp contain the H
  if($row_session[total] > 3600) $date_disp = "H:i:s";
  else $date_disp = "i:s";

  print '</td><td>' . $row[location] . '</td><td>' . date($date_disp, mktime(0, 0, $row_session[total], 1, 1, 1)) . '</td><td>' . date('i:s', mktime(0, 0, $row_session[idle], 1, 1, 1)) . '</td><td><input type=radio name=session value="' . $row[id] . '"';

  if($row[session_id] == $session_id) print ' disabled';

  print '></td></tr>';

}

print '<tr><td colspan=4></td><td><input type=hidden name=action value=delete><input type=submit value="Remove"></td></tr></table></form>';

nav_bottom();

?>