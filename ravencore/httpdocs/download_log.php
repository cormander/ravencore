<?php

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
