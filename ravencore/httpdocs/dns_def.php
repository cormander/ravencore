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

req_service("dns");

req_admin();

if ($action == "delete")
{
    $sql = "delete from dns_def where id = '$_POST[delete]'";
    $db->Execute($sql);

    goto("$_SERVER[PHP_SELF]");
} 

nav_top();

$sql = "select * from dns_def order by type, name, target";
$result =& $db->Execute($sql);

$num = $result->RecordCount();

if ($num == 0) print __("No default DNS records setup for this server");
else
{
    print '<h3>' . __('Default DNS for domains setup on this server') . '</h3><form method=post><table><tr><td>&nbsp;</td><td>' . _('Record Name') . '</td><td>' . __('Record Type') . '</td><td>' . __('Record Target') . '</td></tr>';

    while ($row =& $result->FetchRow())
    {
        print '<tr><td><input type=radio name=delete value="' . $row[id] . '"></td><td>' . $row[name] . '</td><td>' . $row[type] . '</td><td>' . $row[target] . '</td></tr>';
    } 

    print '<tr><td colspan=4><input type=submit value="' . __('Delete Selected') . '"></tr>
<input type=hidden name=action value=delete>
<input type=hidden name=did value="' . $did . '">
</table></form>';
} 

print '<p><form method=post action="add_def_dns.php">
' . __('Add record') . ': <select name=type>
<option value=A>A</option>
<option value=NS>NS</option>
<option value=MX>MX</option>
<option value=SOA>SOA</option>
<option value=CNAME>CNAME</option>
<option value=PTR>PTR</option>
<option value=TXT>TXT</option>
</select> <input type=submit value="' . __('Add') . '">
<input type=hidden name=did value="' . $did . '">
</form>';

nav_bottom();

?>