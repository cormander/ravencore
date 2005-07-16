<?php

include "auth.php";

req_admin();

//for now....
$user = $_REQUEST[user];

if($action == "add") {

  $sql = "insert into crontab set minute = '$_POST[minute]', hour = '$_POST[hour]', dayofm = '$_POST[dayofm]', month = '$_POST[month]', dayofw = '$_POST[dayofw]', cmd = '$_POST[cmd]', user = '$_POST[user]'";
  mysql_query($sql);

  socket_cmd("crontab_mng $_POST[user]");

  goto("$_SERVER[PHP_SELF]?user=$_POST[user]");

} else if($action == "delete") {

  $sql = "delete from crontab where id = '$_POST[del_val]' and user = '$_POST[user]'";
  mysql_query($sql);

  socket_cmd("crontab_mng $_POST[user]");

  goto("$_SERVER[PHP_SELF]?user=$_POST[user]");

}

nav_top();

$sql = "select distinct user from crontab order by user";
$result = mysql_query($sql);

$num = mysql_num_rows($result);

if (!$_POST[user] or $num == 0) {

  print "<a href=\"crontab.php?add=1\">Add a crontab</a><p>";
  
  $_POST[user] = "";

}

if($num == 0) print "There are no crontabs.<p>";
else { 

  print "<form name=f method=get>User: <select name=user onchange=\"document.f.submit()\"><option value=''>- - Choose a user - -</option>";

  while ( $row = mysql_fetch_array($result) ) {
    
    print "<option value=$row[user]";
    if($user == $row[user]) print " selected";
    print ">$row[user]</option>";

  }
  
  print "</select></form>";

}

print "<p>";

if($user) {

  $sql = "select * from crontab where user = '$user'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) {

    print "No crontab for user $user";

    exit;

  } else {

    print "<form method=post><table>";
    
    while ( $row = mysql_fetch_array($result) ) {
      
      print "<tr><td><input type=radio name=del_val value=$row[id]></td><td>$row[minute]</td><td>$row[hour]</td><td>$row[dayofm]</td><td>$row[month]</td><td>$row[dayofw]</td><td>$row[cmd]</td></tr>";
      
    }
    
    print "</table><input type=submit value=\"Delete Selected\"> <input type=hidden name=user value=\"$user\"><input type=hidden name=action value=delete></form>";

  }

}

if($add or $user) {

?><form method=post>

User: <?php

     if($user) print $user . "<input type=hidden name=user value=$user";
     else print "<input type=text name=user>"; 

?>

<p>
   Entry:
<input type="text" size=4 name=minute>
<input type="text" size=4 name=hour>
<input type="text" size=4 name=dayofm>
<input type="text" size=4 name=month>
<input type="text" size=4 name=dayofw>
<input type="text" size=30 name=cmd>

<p>

<input type=submit value="Add Crontab"> <input type="hidden" name=action value=add>

</form>

<?php

}

nav_bottom();

?>

