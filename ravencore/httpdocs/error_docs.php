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

if (!$did) goto("users.php?uid=$uid");

$domain_name = $d->name();

if ($action == "add")
{ 
    // quick hack to get all the allowed status codes into an array
    $handle = popen("cat http_status_codes.php | awk '{print \$1}' | grep '^[[:digit:]]'", "r");

    while (!feof($handle)) $code_data .= fread($handle, 1024);

    pclose($handle);

    $http_codes = explode("\n", $code_data);

    if (!in_array($_POST[code], $http_codes)) alert(__("$_POST[code] is not a valid http code!"));
    else
    {
        $sql = "select count(*) as count from error_docs where did = '$did' and code = '$_POST[code]'";
        $result = $db->data_query($sql);

	$row = $db->data_fetch_array($result);

        if ($row[count] != 0) alert(__("You already have a $_POST[code] error document"));
        else
        {
            $sql = "insert into error_docs set did = '$did', code = '$_POST[code]', file = '$_POST[file]'";
            $db->data_query($sql);

            socket_cmd("rehash_httpd " . $d->name());

            goto("error_docs.php?did=$did");
        } 
    } 
} 
else if ($action == "delete")
{
    $sql = "delete from error_docs where did = '$did' and code = '$_POST[code]'";
    $db->data_query($sql);

    socket_cmd("rehash_httpd " . $d->name());

    goto("error_docs.php?did=$did");
} 

nav_top();

$sql = "select * from error_docs where did = '$did'";
$result = $db->data_query($sql);

$num = $db->data_num_rows();

if ($num == 0) print __('No custom error documents setup.');
else
{
    print '<form method=POST>';

    while ( $row = $db->data_fetch_array($result) )
    {
        print '<input type=radio name=code value=' . $row[code] . '> ' . $row[code] . ' - ' . $row[file] . '<br>';
    } 

    print '<input type=submit value=' . __('Delete') . '><input type=hidden name=action value=delete></form>';
} 

print '<p><form method=POST>
' . __('Add Custom Error Document') . ':
<br>
' . __('Code') . ': <input type=text size=2 name=code>
<br>
' . __('File') . ': <input type=text name=file>
<p>
<input type=submit value=' . __('Add') . '>
<input type=hidden name=action value=add>
<input type=hidden name=did value=' . $did . '>
</form>';

print '<p><a href="http_status_codes.php" target=_blank>' . __('List HTTP Status Codes') . '</a>';

nav_bottom();

?>