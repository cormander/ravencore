<?php

$lang = array();

// global
$lang['global_search'] = 'Search';
$lang['global_go'] = 'Go';
$lang['global_please_enter_search_value'] = 'Please enter a search value!';
$lang['global_show_all'] = 'Show All';
$lang['global_back'] = 'Back';
$lang['global_your_search_returned'] = 'Your search returned';
$lang['global_results'] = 'results.';
$lang['global_name'] = 'Name';
$lang['global_domains'] = 'Domains';
$lang['global_disc_space_usage'] = 'Space usage';
$lang['global_traffic_usage_current_month'] = 'Traffic usage (This month)';
$lang['global_traffic_usage'] = 'Traffic usage';
$lang['global_domain_usage'] = 'Domain usage';
$lang['global_totals'] = 'Totals';

// auth.php page
$lang['auth_welcome_and_thank_you'] = 'Welcome, and thank you for using RavenCore!';
$lang['auth_please_upgrade_config'] = 'You installed and/or upgraded some packages that require new configuration settings. Please take a moment to review these settings. We recomend that you keep the default values, but if you know what you are doing, you may adjust them to your liking.';
$lang['auth_test_suid_error'] = 'Your system is unable to set uid to root with the wrapper. This is required for ravencore to function. To correct this:<p>
Remove the file: <b>/usr/local/ravencore/sbin/wrapper</b><p>
Then do one of the following:<p>
* Install <b>gcc</b> and the package that includes <b>/usr/include/sys/types.h</b> and restart ravencore<br />
&nbsp;&nbsp;or<br />
* Install the <b>perl-suidperl</b> package and restart ravencore<br />
&nbsp;&nbsp;or<br />
* Copy the wrapper binary from another server with ravencore installed into ravencore\'s sbin/ on this server';

$lang['auth_no_php_mysql'] = 'Unable to call the mysql_connect function. Please install the php-mysql package or recompile PHP with mysql support, and restart the control panel.<p>If php-mysql is installed on the server, check to make sure that the mysql.so extention is getting loaded in your system\'s php.ini file';
$lang['auth_locked_outdated'] = "Login locked because control panel is outdated.";
$lang['auth_api_cmd_failed'] = 'API command failed. This server is configured as a master server.';
$lang['auth_locked_upgrading'] = "Control Panel is being upgraded. Login Locked.";
$lang['auth_no_php_session'] = 'The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled';
$lang['auth_no_database_cfg'] = 'You are missing the database configuration file: ' . $CONF[RC_ROOT] . '/database.cfg<p>Please run the following script as root:<p>' . $CONF[RC_ROOT] . '/sbin/database_reconfig';
$lang['auth_no_database_connect'] = 'Unable to get a database connection.';
$lang['auth_must_agree_gpl'] = 'You must agree to the GPL License to use RavenCore';
$lang['auth_please_agree_gpl'] = 'Please read the GPL License and select the "I agree" checkbox below';
$lang['auth_gpl_appear_below'] = 'The GPL License should appear in the frame below:';
$lang['auth_i_agree_gpl'] = 'I agree to these terms and conditions';
$lang['auth_login_locked'] = 'Login locked';
$lang['auth_login_failure'] ='Login failure';
$lang['auth_cp_userlock_outdated_settings'] = 'Control panel is locked for users, because your "lock if outdated" setting is active, and we appear to be outdated.';
$lang['auth_conf_file_configuration'] = 'configuration';

// login.php
$lang['login_please_login'] = 'Please Login';
$lang['login_username'] = 'Username';
$lang['login_password'] = 'Password';
$lang['login_language'] = 'Language';
$lang['login_option_default'] = 'Default';
$lang['login_your_login_is_secure'] = 'Your login is secure';
$lang['login_go_to_secure_login'] = 'Go to Secure Login';
$lang['login_login'] = 'Login';

// ad_db.php
$lang['add_db_adding_a_database_for'] = 'Adding a Database for';
$lang['add_db_add_database'] = 'Add Database';

// domains.php
$lang['domains_domains_for'] = 'Domains for';
$lang['domains_there_are_no_domains_setup'] = 'There are no domains setup';
$lang['domains_view_setup_information_for'] = 'View setup information for';
$lang['domains_you_are_at_domain_limit'] = 'You are at your limit for the number of domains you can have';
$lang['domains_add_a_domain_to_server'] = 'Add a domain to the server';
$lang['domains_add_a_domain'] = 'Add a Domain';
$lang['domains_domain_no_exist'] = 'Domain does not exist';
$lang['domains_domain_belongs_to'] = 'This domain belongs to';
$lang['domains_no_one'] = 'No One';
$lang['domains_change'] = 'Change';
$lang['domains_deletes_this_domain'] = 'Delete this domain off the server';
$lang['domains_sure_you_want_to_delete'] = 'Are you sure you wish to delete this domain';
$lang['domains_delete'] = 'delete';
$lang['domains_name'] = 'Name';
$lang['domains_created'] = 'Created';
$lang['domains_status'] = 'Status';
$lang['domains_on'] = 'ON';
$lang['domains_sure_turn_off_hosting'] = 'Are you sure you wish to turn off hosting for this domain';
$lang['domains_turn_off_hosting'] = 'Turn OFF hosting for this domain';
$lang['domains_off'] = 'OFF';
$lang['domains_turn_on_hosting'] = 'Turn ON hosting for this domain';
$lang['domains_physical'] = 'Physical Hosting';
$lang['domains_view_edit_physical'] = 'View/Edit Physical hosting for this domain';
$lang['domains_edit'] = 'edit';
$lang['domains_redirect'] = 'Redirect';
$lang['domains_view_edit_redirect'] = 'View/Edit where this domain redirects to';
$lang['domains_alias'] = 'Alias of';
$lang['domains_view_edit_alias'] = 'View/Edit what this domain is a server alias of';
$lang['domains_no_hosting'] = 'No Hosting';
$lang['domains_setup_hosting'] = 'Setup hosting for this domain';
$lang['domains_setup'] = 'setup';
$lang['domains_filemanager'] = 'File Manager';
$lang['domains_go_to_filemanager'] = 'Go to the File Manager for this domain';
$lang['domains_offline_filemanager'] = 'The file manager is currently offline';
$lang['domains_filemanager_currently_offline'] = 'The file manager is currently offline';
$lang['domains_filemanager_offline'] = '( offline )';
$lang['domains_log_manager'] = 'Log Manager';
$lang['domains_go_to_log_manager'] = 'Go to the Log Manager for this domain';
$lang['domains_error_docs'] = 'Error Documents';
$lang['domains_view_edit_ced'] = 'View/Edit Custom Error Documents for this domain';
$lang['domains_mail'] = 'Mail';
$lang['domains_view_edit_mail'] = 'View/Edit Mail for this domain';
$lang['domains_mail_off'] = '( off )';
$lang['domains_view_edit_domain_databases'] = 'View/Edit databases for this domain';
$lang['domains_databases'] = 'Databases';
$lang['domains_manage_dns'] = 'Manage DNS for this domain';
$lang['domains_dns_records'] = 'DNS Records';
$lang['domains_dns_off'] = '( off )';
$lang['domains_view_webstats'] = 'View Webstats for this domain';
$lang['domains_webstats'] = 'Webstats';

// functions.php
$lang['menu_users'] = 'Users';
$lang['menu_domains'] = 'Domains';
$lang['menu_mail'] = 'Mail';
$lang['menu_databases'] = 'Databases';
$lang['menu_dns'] = 'DNS';
$lang['menu_system'] = 'System';
$lang['menu_logout'] = 'Logout';
$lang['menu_list_control_panel_users'] = 'List control panel users';
$lang['menu_list_domains'] = 'List domains';
$lang['menu_list_email_addresses'] = 'List email addresses';
$lang['menu_list_databases'] = 'List databases';
$lang['menu_dns_for_domains_on_this_server'] = 'DNS for domains on this server';
$lang['menu_manage_system_settings'] = 'Manage system settings';
$lang['menu_view_all_server_log_files'] = 'View all server log files';
$lang['functions_unable_to_connect_db'] = 'Unable to connect to DB server! Attempting to restart mysql';
$lang['functions_this_server_does_not_have'] = 'This server does not have';
$lang['functions_installed_page_cannot_be_displayed'] = 'installed. Page cannot be displayed';

//users.php page
$lang['users_no_users_setup'] = 'There are no users setup';
$lang['users_view_data_for'] = 'View data for';
$lang['users_add_cp_user'] = 'Add a user to the control panel';
$lang['users_add_a_cp_user'] = 'Add a Control Panel user';
$lang['users_user_does_not_exist'] = 'User does not exist';
$lang['users_failed_login_lockout'] = 'This user is locked out due to failed login attempts';
$lang['users_unlock'] = 'Unlock';
$lang['users_company'] = 'Company';
$lang['users_created'] = 'Created';
$lang['users_contact_email'] = 'Contact email';
$lang['users_login_id'] = 'Login ID';
$lang['users_edit_account_info'] = 'Edit account info';
$lang['users_see_what_you_can_and_not_do'] = 'See what you can and can not do';
$lang['users_view_perms'] = 'View Permissions';
$lang['users_view_edit_perms'] = 'View/Edit Permissions';
$lang['users_options'] = 'Options';
$lang['users_you_have_no_domains_setup'] = 'You have no domains setup';
$lang['users_no_domains_setup'] = 'No domains setup';
$lang['users_for_which_domain'] = 'For which domain';
$lang['users_add_mysql_database'] = 'Add a MySQL database';
$lang['users_add_email_account'] = 'Add E-Mail Account';
$lang['users_list_domains'] = 'List Domains';
$lang['users_view_webstats'] = 'View Webstatistics';
$lang['users_add_a_domain'] = 'Add a Domain';
$lang['users_add_edit_dns'] = 'Add/Edit DNS records';
$lang['users_domain_limit_reached'] = 'You are at your limit for the number of domains you can have';
$lang['users_user_reached_domain_limit'] = 'This user is at his/her domain limit';
$lang['users_add_one_anyway'] = 'Add one anyway';
$lang['users_no_users_setup'] = 'There are no users setup';
$lang['users_view_user_data_for'] = 'View user data for';
$lang['users_list_all_your_domains'] = 'List all of your domain names';
$lang['users_add_a_domain_to_the_server'] = 'Add a domain to the server';


/*
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
*/
?>