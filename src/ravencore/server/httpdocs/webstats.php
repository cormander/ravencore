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

include("auth.php");

$domain = $db->run("get_domain_by_id", Array( id => $did));

// if this domain isn't setup for "physical" hosting, there are no webstats
if ("physical" != $domain[host_type]) {
    nav_top();

    print $domain[name] . ' ' . __('is not setup for physical hosting. Webstats are not available');

    nav_bottom();

}

// runtime hang fix
// TODO: issue disconnect function
//fclose($db->sock);

print $db->run('webstats', Array('did' => $did, 'QUERY_STRING' => $_SERVER['QUERY_STRING']));

?>
