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
// for now....
$user = $_REQUEST[user];

if ($action == "add")
{
    $sql = "insert into crontab set minute = '$_POST[minute]', hour = '$_POST[hour]', dayofm = '$_POST[dayofm]', month = '$_POST[month]', dayofw = '$_POST[dayofw]', cmd = '$_POST[cmd]', user = '$_POST[user]'";
    $db->data_query($sql);

    socket_cmd("crontab_mng $_POST[user]");

    goto("$_SERVER[PHP_SELF]?user=$_POST[user]");
} 
else if ($action == "delete")
{
    $sql = "delete from crontab where id = '$_POST[del_val]' and user = '$_POST[user]'";
    $db->data_query($sql);

    socket_cmd("crontab_mng $_POST[user]");

    goto("$_SERVER[PHP_SELF]?user=$_POST[user]");
} 

nav_top();

$sql = "select distinct user from crontab order by user";
$result = $db->data_query($sql);

$num = $db->data_num_rows();

if (!$_POST[user] or $num == 0)
{
    print "<a href=\"crontab.php?add=1\">" . __("Add a crontab") . "</a><p>";

    $_POST[user] = "";
} 

if ($num == 0) print __("There are no crontabs.") . "<p>";
else
{
    print "<form name=f method=get>" . __("User") . ": <select name=user onchange=\"document.f.submit()\"><option value=''>- - " . __("Choose a user") . " - -</option>";

    while ( $row = $db->data_fetch_array($result) )
    {
        print "<option value=$row[user]";
        if ($user == $row[user]) print " selected";
        print ">$row[user]</option>";
    } 

    print "</select></form>";
} 

print "<p>";

if ($user)
{
    $sql = "select * from crontab where user = '$user'";
    $result = $db->data_query($sql);

    $num = $db->data_num_rows();

    if ($num == 0)
    {
        print __("No crontab for user $user");

        rc_exit;
    } 
    else
    {
        print "<form method=post><table>";

        while ( $row = $db->data_fetch_array($result) )
        {
            print "<tr><td><input type=radio name=del_val value=$row[id]></td><td>$row[minute]</td><td>$row[hour]</td><td>$row[dayofm]</td><td>$row[month]</td><td>$row[dayofw]</td><td>$row[cmd]</td></tr>";
        } 

        print "</table><input type=submit value=\"" . __("Delete Selected") . "\"> <input type=hidden name=user value=\"$user\"><input type=hidden name=action value=delete></form>";
    } 
} 

if ($add or $user)
{

    ?><form method=post>

User: <?php

    if ($user) print $user . "<input type=hidden name=user value=$user";
    else print "<input type=text name=user>";

    ?>

<p>
   <?php e_('Entry')?>:
<input type="text" size=4 name=minute>
<input type="text" size=4 name=hour>
<input type="text" size=4 name=dayofm>
<input type="text" size=4 name=month>
<input type="text" size=4 name=dayofw>
<input type="text" size=30 name=cmd>

<p>

<input type=submit value="<?php e_('Add Crontab')?>"> <input type="hidden" name=action value=add>

</form>

<?php

} 

nav_bottom();

?>

