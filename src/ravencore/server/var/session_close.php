<?php

// NOTE: this file is auto_appened to every PHP file ran in the ravencore webserver.
// Unless a script makes a call to exit;, it'll load this... which means no ravencore
// php script should, because they all either make a call to openfile() or nav_bottom(),
// both of which make a call to rc_exit ( which does session_write_close() and exit )

// PHP 5.0.5 does write and close after objects are destroyed, so this is our
// last chance to write session data before the $rcdb object goes kaboom
// basically, try to prevent phpmyadmin/phpwebftp/etc from breaking

session_write_close();

?>