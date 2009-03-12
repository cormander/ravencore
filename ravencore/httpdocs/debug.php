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

$resp = "";

if($debug)
{
	$resp = $db->do_raw_query($debug);
}

nav_top();

?>
<h3>DEBUG PAGE</h3>

Enter in a raw query to the socket and press submit to see the output. <a href="/debug.php?debug=help">(Help)</a>

<br/>

<form method="GET">

<input type="text" size="40" name="debug" value="<?=stripslashes($_REQUEST['debug']);?>"> <input type="submit" value="Submit">

</form>

<pre>
<?php

if(is_array($resp)) {
	// print arrays with print_r
	print_r($resp);
} else if(ereg('^sql ', $debug)) {
	// treat the "sql" command like an sql statement
	$sql = ereg_replace('^sql ', '', $debug);

	$result = $db->data_query(stripslashes($sql));

	while( $row = $db->data_fetch_array($result) ) {
		print_r($row);
	}

} else if(ereg('^help', $debug)) {
  print '</pre><p>' . nl2br(htmlspecialchars($resp)) . '</p><pre>';
} else {
  print htmlspecialchars($resp);
}

?>
</pre>

<?php

nav_bottom();

?>
