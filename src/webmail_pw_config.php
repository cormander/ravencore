<?php
global $chg_sasl_passwd_MinPWLen, $chg_sasl_passwd_MaxPWLen,
       $chg_sasl_passwd_barredlist, $chg_sasl_passwd_vutfile,
       $chg_sasl_passwd_display, $chgsaslpasswd_cmd;;

// MAKE AJUSTMENTS FROM HERE DOWN

// location of chgsaslpasswd
$GLOBALS['chgsaslpasswd_cmd'] = SM_PATH . "plugins/chg_sasl_passwd/chgsaslpasswd";

// require passwords to be a min length
$GLOBALS['chg_sasl_passwd_MinPWLen'] = 5;

// don't allow passwords to be longer than
$GLOBALS['chg_sasl_passwd_MaxPWLen'] = 16;

// users who are not allowed to use this plugin
$chg_sasl_passwd_barredlist = Array();
/*
'apache',
'cyrus',
'root');
*/

// if using sendmails virtusertable, then set this to point to the file
// $chg_sasl_passwd_vutfile = '/etc/mail/virtusertable';

// decide which text to display to the user for the following phrase:
// Change Email Password for xyz
// set the following var to
// 0 for $usernamae
// 1 for $email_address as set in the user prefs
// 2 for the entry found in the sendmail virtualusertable file defined above
$chg_sasl_passwd_display = 0;

?>
