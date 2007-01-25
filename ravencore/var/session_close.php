<?php
// PHP 5.0.5 does write and close after objects are destroyed, so this is our
// last chance to write session data before the $rcdb object goes kaboom
session_write_close();
?>