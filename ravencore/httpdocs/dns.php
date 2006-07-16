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

if ($action == "delete")
{
    if ($_POST['type'] == "SOA")
	{
		$sql = "update domains set soa = NULL where id = '$did'";
	}
    else 
	{
		$sql = "delete from dns_rec where did = '$did' and id = '$_POST[delete]'";
	}

    $db->data_query($sql);

    socket_cmd("rehash_named --rebuild-conf --all");

    goto("dns.php?did=$did");
} 

if (!$did)
{
    req_admin();

    nav_top();

    $sql = "select * from domains where soa is not null and soa != ''";
    $result = $db->data_query($sql);

    $num = $db->data_num_rows();

    if ($num == 0) print __('No DNS records setup on the server');
    else
    {
        print __('The following domains are setup for DNS') . '<p>
<table><tr><th>' . __('Domain') . '</th><th>' . __('Records') . '</th></tr>';

        while ( $row = $db->data_fetch_array($result) )
        {
            print '<tr><td><a href="dns.php?did=' . $row['id'] . '">' . $row['name'] . '</a></td>';

            $sql = "select count(*) as count from dns_rec where did = '$row[id]'";
            $result_rec = $db->data_query($sql);

	    $row = $db->data_fetch_array($result_rec);

            print '<td align=center>' . $row['count'] . '</td></tr>';
        } 

        print '</table>';
    } 
} 
else
{
    nav_top();

    $sql = "select * from domains where id = '$did' and soa is not null";
    $result = $db->data_query($sql);

    $num = $db->data_num_rows();

    if ($num == 0) print '<form method=post action=add_dns.php name=main>
' . __('No SOA record setup for this domain') . ' - <a href="javascript:document.main.submit();">' . __('Add SOA record') . '</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
</form>';
    else
    {
      $row = $db->data_fetch_array($result);

        $domain_name = $row[name];

        print __('DNS for') . ' <a href="domains.php?did=' . $did . '">' . $domain_name . '</a><p>';

        print '<form method=post name=del>
' . __('Start of Authority for ') . $domain_name . __(' is ') . $row[soa] . ' - <a href="javascript:document.del.submit();">' . __('delete') . '</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
<input type=hidden name=action value=delete>
</form>
<p>';

        $sql = "select * from dns_rec where did = '$did' order by type, name, target";
        $result = $db->data_query($sql);

        $num = $db->data_num_rows();

        if ($num == 0) print __("No DNS records setup for this domain");
        else
        {

	  // check to see if at least an A and a NS record exist.. if not, print a warning
	  $sql = "select distinct type from dns_rec where did = '$did' and ( type = 'A' or type = 'NS' )";
	  $result_type = $db->data_query($sql);
	  $num = $db->data_num_rows();

	  // need to have both or else...
	  if($num != 2) print '<font color="red"><b>' . __("You need at least one A record and one NS record for your zone file to be created") . '</b></font>';

            print '<form method=post>';

            print '<table><tr><th>&nbsp;</th><th>' . __('Record Name') . '</th><th>' . __('Record Type') . '</th><th>' . __('Record Target') . '</th></tr>';

            while ( $row = $db->data_fetch_array($result) )
            {
                print '<tr><td><input type=radio name=delete value="' . $row[id] . '"></td><td>' . $row[name] . '</td><td>' . $row[type] . '</td><td>' . $row[target] . '</td></tr>';
            } 

            print '<tr><td colspan=4><input type=submit value="' . __('Delete Selected') . '"></tr>';

            print '<input type=hidden name=action value=delete>
<input type=hidden name=did value="' . $did . '">
</table></form>';
        } 

        if (user_can_add($uid, "dns_rec") or is_admin()) print '<p><form method=post action=add_dns.php>
' . __('Add record') . ': <select name=type>
<option value=A>A</option>
<option value=NS>NS</option>
<option value=MX>MX</option>
<option value=CNAME>CNAME</option>
<option value=PTR>PTR</option>
<option value=TXT>TXT</option>
</select> <input type=submit value=' . __('Add') . '>
<input type=hidden name=did value="' . $did . '">
</form>';
    } 
} 

nav_bottom();

?>