<?php

$lang = array();

// auth.php page
$lang['welcome_and_thank_you'] = 'Welcome, and thank you for using RavenCore!';
$lang['please_upgrade_config'] = 'You installed and/or upgraded some packages that require new configuration settings. Please take a moment to review these settings. We recomend that you keep the default values, but if you know what you are doing, you may adjust them to your liking.';
$lang['test_suid_error'] = 'Your system is unable to set uid to root with the wrapper. This is required for ravencore to function. To correct this, do one of the following:<p>
* Install <b>gcc</b> and the package that includes <b>/usr/include/sys/types.h</b> and restart ravencore<br />
&nbsp;&nbsp;or<br />
* Install the <b>perl-suidperl</b> package and restart ravencore<br />
&nbsp;&nbsp;or<br />
* Copy the wrapper binary from another server with ravencore installed into ravencore\'s sbin/ on this server';

$lang['no_php_mysql'] = 'Unable to call the mysql_connect function. Please install the php-mysql package or recompile PHP with mysql support, and restart the control panel.<p>If php-mysql is installed on the server, check to make sure that the mysql.so extention is getting loaded in your system\'s php.ini file';
$lang['locked_outdated'] = "Login locked because control panel is outdated.";
$lang['api_cmd_failed'] = 'API command failed. This server is configured as a master server.';
$lang['locked_upgrading'] = "Control Panel is being upgraded. Login Locked.";
$lang['no_php_session'] = 'The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled';
$lang['no_database_cfg'] = 'You are missing the database configuration file: ' . $CONF[RC_ROOT] . '/database.cfg<p>Please run the following script as root:<p>' . $CONF[RC_ROOT] . '/sbin/database_reconfig';
$lang['no_database_connect'] = 'Unable to get a database connection.';
$lang['must_agree_gpl'] = 'You must agree to the GPL License to use RavenCore';
$lang['please_agree_gpl'] = 'Please read the GPL License and select the "I agree" checkbox below';
$lang['gpl_appear_below'] = 'The GPL License should appear in the frame below:';
$lang['i_agree_gpl'] = 'I agree to these terms and conditions';

//users.php page
$lang['search'] = 'Search';
$lang['go'] = 'Go';
$lang['pease_enter_search_val'] = 'Please enter a search value!';

$lang['show_all'] = 'Show All';
$lang['no_users_setup'] = 'There are no users setup';
$lang['number_search_results'] = 'Number of search results';
$lang['space_usage'] = 'Space usage';
$lang['traffic_usage'] = 'Traffic usage';
$lang['view_data_for'] = 'View data for';
$lang['totals'] = 'Totals';
$lang['add_cp_user'] = 'Add a user to the control panel';
$lang['user_no_exist'] = 'User does not exist';
$lang['failed_login_lockout'] = 'This user is locked out due to failed login attempts';
$lang['unlock'] = 'Unlock';
$lang['comapny'] = 'Company';
$lang['name'] = 'Name';
$lang['created'] = 'Created';
$lang['contact_email'] = 'Contact email';
$lang['login_id'] = 'Login ID';

/*

$lang['edit_account_info'] = 'Edit account info';

$lang['see_what_can_and_no_do'] = 'See what you can and can not do';

$lang['view_edit_perms'] = 'View/Edit Permissions';

$lang['you_have_no_domains_setup'] = 'You have no domains setup';

$lang['no_domains_setup'] = 'No domains setup';

$lang['for_which_domain'] = 'For which domain?';

Back

Add a MySQL database

Add E-Mail Account

Add/Edit DNS records

List Domains

Add a domain

You are at your limit for the number of domains you can have

This user is at his/her domain limit


$lang[''] = '';

*/

?>