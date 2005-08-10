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

$domain_name = get_domain_name($did);

//We can't decend a directory, or else we would provide read access to almost the entire server!

if(ereg("\.\.", $_GET[log_file])) die("Illegal argument");

$log_location = "$CONF[VHOST_ROOT]/$domain_name/logs/$_GET[log_file]";

$handle = fopen("$log_location","r") or die("Unable to open $log_location");

$size = filesize($log_location);

@ob_end_clean(); 
@ini_set('zlib.output_compression', 'Off'); 
header("Pragma: public"); 
header("Last-Modified: " . gmdate('D, d M Y H:i:s')  . " GMT"); 
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
header("Cache-Control: pre-check=0, post-check=0, max-age=0"); // HTTP/1.1 
header("Content-Transfer-Encoding: none"); 
header("Content-Type: application/text");
header("Content-Disposition: attachment; filename=\"" . $_GET[log_file] . ".txt\""); 
header("Content-Length: $size");

while( !feof($handle) ) print fread($handle, 1024);

fclose($handle);

?>
