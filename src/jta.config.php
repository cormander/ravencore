<?php
// configuration file for javassh
// This is a php file so we can dynamically write the server's IP address,
// and protect this config file, so it can be used by the admin user only

chdir("../../../httpdocs");

include "auth.php";

// only an admin should be able to load this

req_admin();

// Tell the browser this is a text file
header("Content-type: text/plain");

?>
plugins         =       Status,Socket,SSH,Terminal
layout.Status           =       South
layout.Terminal         =       Center
Socket.host             =       <?php

// print this server's IP, followed by a return character

print $_SERVER["SERVER_ADDR"] . "\n";

?>
Socket.port             =       22
Terminal.foreground     =       #000000
Terminal.background     =       #ffffff
Terminal.id             =       vt100
Terminal.resize         =       font

