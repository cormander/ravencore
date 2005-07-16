<?php

include("auth.php");

// the config variable is the domain name
$domain_name = $_REQUEST[config];

$did = get_domain_id($domain_name);

// check to see if this user has this domain. If not, they can't view the webstats

if(!user_have_domain($uid,$did)) goto("users.php");

// null out the configdir in the query string so people can't hack it to look at other webstats

$_SERVER[QUERY_STRING] = preg_replace('/configdir=([\/-\w.]+)/', '', $_SERVER[QUERY_STRING]);

// put this domain's configdir into the query string so we're looking for the conf file in the right place

$_SERVER[QUERY_STRING] .= "&configdir=" . $CONF[VHOST_ROOT] . "/" . $domain_name . "/conf";

// run awstats in a simulated CGI enviroment, and output the results to the web
// fix the links too, by changeing references to "awstats.pl" to "webstats.php"

print shell_exec("export GATEWAY_INTERFACE=\"CGI/1.1\" QUERY_STRING=\"" . $_SERVER[QUERY_STRING] . "\"; " . $_ENV[AWSTATS_ROOT] . "/wwwroot/cgi-bin/awstats.pl | sed 's/awstats.pl/webstats.php/g' | sed '1d' | sed '1d' | sed '1d' | sed '1d'");

?>