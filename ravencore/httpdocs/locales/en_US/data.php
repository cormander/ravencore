<?

// the $trans array key is first two letters as ISO 639 language code, underscore,
// and the last two letters ISO 3166 country code.
// ex: en_US  ru_RU  nb_NO  etc

$trans[''] = array(

	'Name' => '',
	'Add Database' => '',
	'Invalid password. Must only contain letters and numbers, must be atleast 5 characters, and not a dictionary word' => '',
	'Adding a user for database' => '',
	'Login' => '',
	'Password' => '',
	'Add User' => '',
	'Your record name and target cannot be the same.' => '',
	'You cannot enter in a full domain as the record name.' => '',
	'You already have a default SOA record set' => '',
	'Default Start of Authority' => '',
	'Record Name' => '',
	'Target IP' => '',
	'Nameserver' => '',
	'Mail for the domain' => '',
	'MX Preference' => '',
	'Mail Server' => '',
	'Alias name' => '',
	'Target name' => '',
	'Reverse pointer records are not yet available' => '',
	'Invalid DNS record type' => '',
	'Add Record' => '',
	'Start of Authority for' => '',
	'Mail for' => '',
	'must not be an IP!' => '',
	'The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled.' => '',
	'You are missing the database configuration file:' => '',
	'/database.cfg<p>Please run the following script as root:<p>' => '',
	'/sbin/database_reconfig' => '',
	'' => '',
	'Your system is unable to set uid to root with the wrapper. This is required for ravencore to function. To correct this:<p>' => '',
	'the file: <b>/usr/local/ravencore/sbin/wrapper</b><p>' => '',
	'do one of the following:<p>' => '',
	'Install <b>gcc</b> and the package that includes <b>/usr/include/sys/types.h</b> and restart ravencore<br />\n' => '',
	'/>\n' => '',
	'Install the <b>perl-suidperl</b> package and restart ravencore<br />\n' => '',
	'/>\n' => '',
	'Copy the wrapper binary from another server with ravencore installed into ravencore\'s sbin/ on this server' => '',
	'' => '',
	'to call the mysql_connect function. \n' => '',
	'\t\t\tPlease install the php-mysql package or recompile PHP with mysql support, and restart the control panel.<p>\n' => '',
	'php-mysql is installed on the server, check to make sure that the mysql.so extention is getting loaded in your system\'s php.ini file' => '',
	'Unable to get a database connection.' => '',
	'Login locked.' => '',
	'Login failure.' => '',
	'Control panel is locked for users, because your \"lock if outdated\" setting is active, and we appear to be outdated.' => '',
	'Login locked because control panel is outdated.' => '',
	'API command failed. This server is configured as a master server.' => '',
	'You must agree to the GPL License to use RavenCore.' => '',
	'Please read the GPL License and select the \"I agree\" checkbox below' => '',
	'The GPL License should appear in the frame below' => '',
	'I agree to these terms and conditions.' => '',
	'Welcome, and thank you for using RavenCore!' => '',
	'' => '',
	'installed and/or upgraded some packages that require new configuration settings. \n' => '',
	'take a moment to review these settings. We recomend that you keep the default values, \n' => '',
	'if you know what you are doing, you may adjust them to your liking.\n' => '',
	'' => '',
	'configuration' => '',
	'Submit' => '',
	'Control Panel is being upgraded. Login Locked.' => '',
	'The password is incorrect!' => '',
	'The new password must be greater than 4 characters and not a dictionary word' => '',
	'Cannot select MySQL database' => '',
	'Cannot change database password' => '',
	'Unable to flush database privileges' => '',
	'Cannot open .shadow file' => '',
	'Your passwords are not the same!' => '',
	'Please change the password for' => '',
	'Changing' => '',
	'password!' => '',
	'Old Password' => '',
	'New Password' => '',
	'Confirm New' => '',
	'Change Password' => '',
	'Add a crontab' => '',
	'There are no crontabs.' => '',
	'User' => '',
	'Choose a user' => '',
	'Delete Selected' => '',
	'Entry' => '',
	'Add Crontab' => '',
	'Unable to use mysql database' => '',
	'That database does not exist' => '',
	'Add a Database' => '',
	'No databases setup' => '',
	'Databases for' => '',
	'Are you sure you wish to delete this database?' => '',
	'delete' => '',
	'Users for the' => '',
	'database' => '',
	'Add a database user' => '',
	'No users for this database' => '',
	'Delete' => '',
	'Are you sure you wish to delete this database user?' => '',
	'Note: You may only manage one database user at a time with the phpmyadmin' => '',
	'Search' => '',
	'Please enter in a search value!' => '',
	'Show All' => '',
	'There are no databases setup' => '',
	'Your search returned' => '',
	'results' => '',
	'Domain' => '',
	'Database' => '',
	'No DNS records setup on the server' => '',
	'The following domains are setup for DNS' => '',
	'Records' => '',
	'No SOA record setup for this domain' => '',
	'Add SOA record' => '',
	'DNS for' => '',
	'Start of Authority for' => '',
	'is' => '',
	'No DNS records setup for this domain' => '',
	'Record Type' => '',
	'Record Target' => '',
	'Add record' => '',
	'Add' => '',
	'No default DNS records setup for this server' => '',
	'Default DNS for domains setup on this server' => '',
	'Domains for' => '',
	'There are no domains setup' => '',
	'Add a Domain' => '',
	'Go' => '',
	'Please enter a search value!' => '',
	'Space usage' => '',
	'Traffic usage' => '',
	'View setup information for' => '',
	'Totals' => '',
	'You are at your limit for the number of domains you can have' => '',
	'Add a domain to the server' => '',
	'Domain does not exist' => '',
	'This domain belongs to' => '',
	'No One' => '',
	'Change' => '',
	'Info for' => '',
	'Delete this domain off the server' => '',
	'Are you sure you wish to delete this domain' => '',
	'Created' => '',
	'Status' => '',
	'ON' => '',
	'Are you sure you wish to turn off hosting for this domain' => '',
	'Turn OFF hosting for this domain' => '',
	'OFF' => '',
	'Turn ON hosting for this domain' => '',
	'Physical Hosting' => '',
	'View/Edit Physical hosting for this domain' => '',
	'edit' => '',
	'Redirect' => '',
	'View/Edit where this domain redirects to' => '',
	'Alias of' => '',
	'View/Edit what this domain is a server alias of' => '',
	'No Hosting' => '',
	'Setup hosting for this domain' => '',
	'Go to the File Manager for this domain' => '',
	'The file manager is currently offline' => '',
	'File Manager' => '',
	'View/Edit Custom Error Documents for this domain' => '',
	'Error Documents' => '',
	'View/Edit Mail for this domain' => '',
	'Mail' => '',
	'( off )' => '',
	'View/Edit databases for this domain' => '',
	'Databases' => '',
	'Manage DNS for this domain' => '',
	'DNS Records' => '',
	'View Webstats for this domain' => '',
	'Webstats' => '',
	'Domain Usage' => '',
	'Disk space usage' => '',
	'This month\'s bandwidth' => '',
	'Illegal argument' => '',
	'Please enter the domain name you wish to setup' => '',
	'Invalid domain name. Please re-enter the domain name without the www.' => '',
	'Invalid domain name. May only contain letters, numbers, dashes and dots. Must not start or end with a dash or a dot, and a dash and a dot cannot be next to each other' => '',
	'Control Panel User' => '',
	'Select One' => '',
	'Add domain' => '',
	'Add Domain' => '',
	'Proceed to hosting setup' => '',
	'Add default DNS to this domain' => '',
	'That email address already exists' => '',
	'Your passwords do not match' => '',
	'You selected you wanted a redirect, but left the address blank' => '',
	'Invalid password. Must only contain letters and numbers.' => '',
	'The redirect list contains an invalid email address.' => '',
	'Invalid mailname. It may only contain letters, number, dashes, dots, and underscores. Must both start and end with either a letter or number.' => '',
	'Mail is disabled for' => '',
	'. You can not add an email address for it.' => '',
	'Edit' => '',
	'mail' => '',
	'Mail Name' => '',
	'Confirm' => '',
	'Mailbox' => '',
	'Mail will not be stored on the server if you disable this option. Are you sure you wish to do this?' => '',
	'List email addresses here, seperate each with a comma and a space' => '',
	'Add Mail' => '',
	'Update' => '',
	'You must enter a name for this user' => '',
	'You must enter a password for this user' => '',
	'Your password must be atleast 5 characters long, and not a dictionary word.' => '',
	'The email address entered is invalid' => '',
	'info' => '',
	'Full Name' => '',
	'Email Address' => '',
	'Edit Info' => '',
	'Proceed to Permissions Setup' => '',
	'Required fields' => '',
	'Are you sure you wish to delete this user?' => '',
	'No custom error documents setup.' => '',
	'Add Custom Error Document' => '',
	'Code' => '',
	'File' => '',
	'List HTTP Status Codes' => '',
	'This server does not have' => '',
	'installed. Page cannot be displayed.' => '',
	'Unable to connect to DB server! Attempting to restart mysql' => '',
	'Restart command completed. Please refresh the page.' => '',
	'If the problem persists, contact the system administrator' => '',
	'You are not authorized to view this page' => '',
	'List control panel users' => '',
	'Users' => '',
	'List domains' => '',
	'Domains' => '',
	'List email addresses' => '',
	'List databases' => '',
	'DNS for domains on this server' => '',
	'DNS' => '',
	'Manage system settings' => '',
	'System' => '',
	'Goto main server index page' => '',
	'Main Menu' => '',
	'List your domains' => '',
	'My Domains' => '',
	'List all your email accounts' => '',
	'My email accounts' => '',
	'Logout' => '',
	'Are you sure you wish to logout?' => '',
	'Are you sure you wish to delete hosting for this domain?' => '',
	'delete hosting' => '',
	'www prefix' => '',
	'Yes' => '',
	'No' => '',
	'FTP Username' => '',
	'FTP Password' => '',
	'Shell' => '',
	'SSL Support' => '',
	'If you disable ssl support, you will not be able to enable it again.\\rAre you sure you wish to do this?' => '',
	'PHP Support' => '',
	'If you disable php support, you will not be able to enable it again.\\rAre you sure you wish to do this?' => '',
	'CGI Support' => '',
	'If you disable cgi support, you will not be able to enable it again.\\rAre you sure you wish to do this?' => '',
	'Directory indexing' => '',
	'This domain is an alias of' => '',
	'Host on this server' => '',
	'Redirect to another domain' => '',
	'Show contents of another site on this server' => '',
	'Continue' => '',
	'Are you sure you wish to delete this log file?' => '',
	'Log files for' => '',
	'Manage' => '',
	'Go to log rotation manager for' => '',
	'Log Rotation' => '',
	'Log Name' => '',
	'Compression' => '',
	'File Size' => '',
	'Download the' => '',
	'Custom log rotation for' => '',
	'is' => '',
	'Are you sure you wish to turn off the custom log rotation for' => '',
	'Turn OFF log rotation for' => '',
	'Turn ON log rotation for' => '',
	'You must choose how many log files you wish to keep!' => '',
	'You must make a rotation selection: filesize, date, or both' => '',
	'Keep' => '',
	'log files' => '',
	'Rotate by' => '',
	'Filesize' => '',
	'Date' => '',
	'Daily' => '',
	'Weekly' => '',
	'Monthly' => '',
	'Email about-to-expire files to' => '',
	'Compress log files' => '',
	'No domains setup, so there are no Log files' => '',
	'Please Login' => '',
	'Username' => '',
	'Language' => '',
	'English' => '',
	'Your login is secure' => '',
	'Go to Secure Login' => '',
	'Goto' => '',
	'Turn ON mail for' => '',
	'Turn OFF mail for' => '',
	'Are you sure you wish to disable mail for this domain?' => '',
	'Mail sent to email accounts not set up for this domain ( catchall address )' => '',
	'Send to' => '',
	'Bounce with' => '',
	'Delete it' => '',
	'Forwoard to that user' => '',
	'You need at least two domains in the account with mail turned on to be able to alias mail' => '',
	'No mail for this domain.' => '',
	'Mail for this domain' => '',
	'Webmail' => '',
	'Webmail is currently offline' => '',
	'offline' => '',
	'If you delete this email, you may not be able to add it again.\\rAre you sure you wish to do this?' => '',
	'Are you sure you wish to delete this email?' => '',
	'This user is only allowed to create' => '',
	'email accounts. Are you sure you want to add another?' => '',
	'Add an email account' => '',
	'You have no domains setup.' => '',
	'Create a new email account' => '',
	'Add an email address' => '',
	'There are no mail users setup' => '',
	'Email Addresses' => '',
	'Service' => '',
	'Running' => '',
	'Start' => '',
	'Stop' => '',
	'Restart' => '',
	'IP Address' => '',
	'Session Time' => '',
	'Idle Time' => '',
	'Remove' => '',
	'Stop/Start system services such as httpd, mail, etc' => '',
	'System Services' => '',
	'View who is logged into the server, and where from' => '',
	'Login Sessions' => '',
	'Services that automatically start when the server boots up' => '',
	'Startup Services' => '',
	'The DNS records that are setup for a domain by default when one is added to the server' => '',
	'Default DNS' => '',
	'Change the admin password' => '',
	'Change Admin Password' => '',
	'Load phpMyAdmin for all with MySQL admin user' => '',
	'Admin MySQL Databases' => '',
	'View general system information' => '',
	'System Info' => '',
	'View output from the phpinfo() function' => '',
	'PHP Info' => '',
	'View Mail Queue' => '',
	'Are you sure you wish to reboot the system?' => '',
	'Reboot the server' => '',
	'Reboot Server' => '',
	'You are about to shutdown the system. There is no way to bring the server back online with this software. Are you sure you wish to shutdown the system?' => '',
	'Shutdown the server' => '',
	'Shutdown Server' => '',
	'This user can' => '',
	'Create' => '',
	'Note: A negative limit mean unlimited' => '',
	'You can\'t add domains' => '',
	'You can\'t add databases' => '',
	'You can\'t add cron jobs' => '',
	'You can\'t add email addresses' => '',
	'You can\'t add DNS records' => '',
	'You can\'t add cgi to hosting on any domains' => '',
	'You can\'t add php to hosting on any domains' => '',
	'You can\'t add ssl to hosting on any domains' => '',
	'You can\'t add shell users' => '',
	'There are no users setup' => '',
	'View user data for' => '',
	'Add a user to the control panel' => '',
	'Add a Control Panel user' => '',
	'User does not exist' => '',
	'This user is locked out due to failed login attempts' => '',
	'Unlock' => '',
	'Company' => '',
	'Contact email' => '',
	'Login ID' => '',
	'Edit account info' => '',
	'See what you can and can not do' => '',
	'View/Edit Permissions' => '',
	'View Permissions' => '',
	'Options' => '',
	'You have no domains setup' => '',
	'No domains setup' => '',
	'For which domain' => '',
	'Back' => '',
	'Add a MySQL database' => '',
	'Add E-Mail Account' => '',
	'Add/Edit DNS records' => '',
	'View Webstatistics' => '',
	'List all of your domain names' => '',
	'List Domains' => '',
	'This user is at his/her domain limit' => '',
	'Add one anyway' => '',
	'Domain usage' => '',
	'Traffic usage (This month)' => '',
	'is not setup for physical hosting. Webstats are not available' => '',
	'OK' => ''

	);

?>
