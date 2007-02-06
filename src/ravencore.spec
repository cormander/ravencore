%define rc_root /usr/local/ravencore

Summary: RavenCore Hosting Control Panel
Name: ravencore
Version: 0.3.2
Release: 1
Packager: Cormander
URL: http://www.ravencore.com/
Source0: %{name}-%{version}.tar.gz
License: GPL
Group: System Environment/Daemons
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-root

%description
A Free Hosting Control Panel for Linux intended to replace the need
for expensive software such as Ensim, CPanel & Plesk. It uses Apache,
Postfix, MySQL & other projects like AWStats and phpMyAdmin. The GUI
is written in PHP, and the backend in Perl & Bash.

RavenCore checks for installed components on your server, and gives
you options to control whatever that particular version of RavenCore
is able to interface with. These include, but are not limited to:

Postfix, Dovecot, Spam Assassin, ClamAV, Bind DNS, MySQL, vsftpd,
and much more.

%prep
%setup -q

%build

# build RavenCore

make build

%install
rm -rf $RPM_BUILD_ROOT

# Create directories

mkdir -p \
	$RPM_BUILD_ROOT/etc/cron.{hourly,daily} \
	$RPM_BUILD_ROOT/etc/init.d \
	$RPM_BUILD_ROOT/etc/logrotate.d

# Install RavenCore

make DESTDIR=$RPM_BUILD_ROOT RC_ROOT=%{rc_root} install

%post 
if [ -x /sbin/chkconfig ]; then

    /sbin/chkconfig --list ravencore &> /dev/null

    if [ $? -ne 0 ]; then

# not listed as a service, add it
        /sbin/chkconfig --add ravencore
# set ravencore to startup on boot
        /sbin/chkconfig --level 3 ravencore on
        /sbin/chkconfig --level 4 ravencore on
        /sbin/chkconfig --level 5 ravencore on

    fi

fi


%preun
if [ -x %{rc_root}/sbin/ravencore.init ]; then

	%{rc_root}/sbin/ravencore.init stop

	if [ -x /sbin/chkconfig ]; then
		/sbin/chkconfig --del ravencore
	fi

fi

if [ "$1" = "0" ] ; then # we are being completly uninstalled

# walk down the list of conf files we've changed since installation, and move them back

	for conf in `cat %{rc_root}/var/run/sys_orig_conf_files 2> /dev/null`; do

# each line is service:conf_file. we just want the conf file
		conf=$(echo $conf | awk -F : '{print $2}')
	
		[ -f $conf.sys_orig ] && mv -f $conf.sys_orig $conf

	done

fi

%check

%clean
rm -rf $RPM_BUILD_ROOT

%files
/etc/ravencore.conf
/etc/init.d/ravencore
/etc/logrotate.d/ravencore
/etc/cron.daily/ravencore
/etc/cron.hourly/ravencore

# hell, one day I was so bored I alphabatized each group of files here :P
%{rc_root}/LICENSE
%{rc_root}/README.install

%{rc_root}/conf.d/amavisd.conf
%{rc_root}/conf.d/amavisd.conf.debian
%{rc_root}/conf.d/base.conf
%{rc_root}/conf.d/dns.conf
%{rc_root}/conf.d/dns.conf.debian
%{rc_root}/conf.d/mail.conf
%{rc_root}/conf.d/mail.conf.debian
%{rc_root}/conf.d/mrtg.conf
%{rc_root}/conf.d/mrtg.conf.debian
%{rc_root}/conf.d/mysql.conf
%{rc_root}/conf.d/postgrey.conf
%{rc_root}/conf.d/postgrey.conf.debian
%{rc_root}/conf.d/web.conf

%{rc_root}/sbin/database_reconfig
%{rc_root}/sbin/data_query
%{rc_root}/sbin/db_install
%{rc_root}/sbin/process_logs
%{rc_root}/sbin/ravencore.cron
%{rc_root}/sbin/ravencore.init
%{rc_root}/sbin/ravencore.httpd
%{rc_root}/sbin/rcserver
%{rc_root}/sbin/run_cmd

%dir
%{rc_root}/docs
%{rc_root}/etc
%{rc_root}/httpdocs
%{rc_root}/var

%changelog
* Mon Feb 05 2007 cormander <admin@ravencore.com>
- version 0.3.2
- upgraded phpwebftp to 4.0 beta
- fixed checkconf_mrtg to add a return character to the script /etc/cron.d it writes so that it actually runs
- fixed service_running to check init script output for "running" before "stopped", to fix amavisd always been
  seen as stopped
- fixed rehash_mail to correctly populate the transport maps with mail relays for domains setup to relay mail
- fixed phpsysinfo by turning off safe_mode for just that Location in php.include
- fixed rehash_named function to actually work
- fixed the dependency check to make sure all files of a dependancy exist before marking the module enabled
- added list_system_daemons function that makes the "List all registered server daemons" link on the system
  services page possible
- added secure_chroot_dir to vsftpd.conf to be the VHOST_ROOT
- added rcserver.pid file and checks to TERM a running proccess if it exists on startup
- added a catch for the HUP signal to reload the database connection and variables, which allows for the
  connection to be inhereted by children, instead of a connection for each child process
- added a function so when ravencore reloads via HUP, the perl code and all internal .pm files are reloaded
- added find_in_path to rcfilefunctions.pm
- added code in rehash_mail for redirects so spam coming into the server doesn't get redirected
- added code in rehash_mail for redirects so the +badh delimiter is removed for emails redirected off-server
- added a new conf variable NAMED_CONF_FILE in conf.d/dns.conf
- added code to toggle debugging while the daemon is running, without having to completly restart
- updated the perl code, removing variables from $rc->{CONF} that aren't from the database, such as RC_ROOT,
  HTTPD, MYSQL_ADMIN_USER, etc (and putting them in $rc->{}) so $rc->{CONF} only contains database variables
- updated debug function to add server time and process pid to debugging output
- updated the build Makefile to build to completion without any errors on freebsd
- updated rcshadow.pm to build the bsd style passwd databases if $ostype is set to 'bsd'
- updated the debug statements to translate return characters into literal \n for cleaner debugging output
- updated the run_cmd passwd script with a better password prompt, it correctly handles DEL

* Sun Jan 28 2007 cormander <admin@ravencore.com>
- version 0.3.1
- fixed disp_chkconfig so that the "Startup Services" page now correctly loads
- fixed rehash_ftp to correctly set system user's home_dir if the database field is empty
- fixed the session user_data hash so that it's correctly populated when a non-admin user logs in
- fixed the tables style on the pages by adding class="listpad" to table, th, and td tags
- fixed the mrtg.php file to read fromm th socket since the rcadmin user no longer has read access to mrtg
- fixed the "Login Sessions" to read the socket instead of the empty table
- fixed the db_install script so it has the $MYSQL_ADMIN_PASS, it was missing since bash_functions was removed
- fixed the httpd.conf file for domains when cgi is active, to match \.pl instead of .pl
- fixed the "next if $waitedpid" call in rcserver to also check for the packed address returned by the "accept"
  call, which fixes a "broken pipe" error in ravencore on older versions of perl
- fixed rehash_httpd to properly include etc/vhosts.conf if it doesn't exist in the server's apache httpd.conf
- fixed php variable references in auth.php from \$var to &$var (perl uses \, php uses &)
- fixed "E_ALL & ~E_NOTICE" in php.include to the correct bit, since apache doesn't have those predefined vars
- changed display_errors from Off to On in php.include since error_reporting was fixed
- changed rcclient.php so that it only calls the class when session_start() is called, so that the object isn't
  created if a page without sessions is loaded
- removed the sessions table from the database
- added check in rcserver to call session_write if the client called session_read but not session_write
- added DBI perl module to the list of dynamically loaded modules, ravencore will now run without it; behaving
  like the database was offline (only admin can login, and only options on the system page are available)
- added checks for .ignore files in the conf.d, to force a module to not be included, even if it's installed

* Thu Jan 25 2007 cormander <admin@ravencore.com>
- version 0.3.0
- upgraded awstats to 6.6 final
- upgraded squirrelmail to 1.4.9a
- upgraded phpmyadmin to 2.9.2
- upgraded phpsysinfo to 2.5.2
- added Turkish and Italian language packs
- updated the Spanish language pack
- completly recoded most of the bash code into the new ravencore.pm and other perl modules in var/lib
- included the repack_ravencore script in the tarball so if a future release needs to rebuild an old version,
  it can do so without worrying about changes in it, because the one for that release can be used
- removed all bash scripts from bin/ and sbin/, except db_install and ravencore.init
- removed session.php and server.php, their coded is now in ravencore.pm
- renamed rcsock as rcserver, renamed rcsock.pm as rcclient.pm, renamed rcsock.php as rcclient.php
- completly rewrote auth.php and made several major changes to functions.php
- made several additions to how data is submitted/read from the socket interface for safety and simplicity
- the perl server now takes care of all server functions and commands, including authentication to login to
  the control panel itself, to provide a far more secure operating enviroment
- this version runs about 20x faster then the previous, due to the optimization the perl language provides
- used session_set_save_handler in php to have session data written to/read from the socket interface, to be
  stored in root read-only files to prevent session hyjacking
- added rcclient.pm to php auto_prepend so that anything making a call to session_start() will use the custom
  session functions on the socket interface
- enabled php safe_mode in the webserver that runs the PHP control panel interface
- changed directory permissions/ownership on all the directories, so that the rcadmin user can only view the
  php files, read/write to the socket interface, and store files in var/tmp/, but absolutly nothing else
- added a debugging link to the system page which gives an interface much like the sbin/run_cmd script
- removed webmail link from mail.php, as access to squirrelmail via ravencore broke (apache access still works)

* Sun Aug 13 2006 cormander <admin@ravencore.com>
- version 0.2.4
- upgraded squirrelmail to 1.4.8
- added checking and auto-configuration of the postfix "postgrey" policy server
- fixed a runtime bug with webstats.php
- updated code for rcshadow.pm, almost done with it

* Sun Aug 06 2006 cormander <admin@ravencore.com>
- version 0.2.3
- upgraded phpmyadmin to 2.8.2.1
- upgraded phpwebftp to 3.3b
- added the "passwd" command so that root can easilly set admin's password on the command line
- added the requirement to set the admin password after installation for security reasons
- added header_checks to postfix main.cf to reject obvious spam
- added several "my" statements in all of the perl scripts to keep variables from overwriting global ones
- changed references to "localhost" to 127.0.0.1 so servers with "localhost" not in /etc/hosts will work
- removed refrences to PAM authentication from dovecot 1.0 configuration file, as PAM isn't ever used there
- fixed data duplication bug in the rcsock.pm client for data_query calls
- fixed the "Startup Services" page in admin section, it wasn't showing anything on the page
- fixed the "System Services" page in admin section, it wasn't showing the status of any service
- fixed the bug with a user launching phpmyadmin and getting an error
- fixed the bug where mail redirects with mailboxes sent duplicate messages when amavisd is enabled

* Sun Jul 30 2006 cormander <admin@ravencore.com>
- version 0.2.2
- added a hardcoded requirement that perl scripts be run as root
- added a lot of error handling to rcsock, so users have a better idea whats going on when there are errors
- rcsock now uses the perl fork and setsid functions to break away from the terminal by itself
- removed the pwdchk.cron.hourly, as the initial login security method was changed
- removed the testsuid script in the ravencore bin, as it is no longer needed
- fixed the dutch language pack so that it is actually loaded when you select it
- fixed the error introducted by rcsock where database settings wouldn't get loaded into the bash scripts

* Sun Jul 23 2006 cormander <admin@ravencore.com>
- version 0.2.1
- added a module to work with mrtg, if it's installed on the system
- added a "fast" option to the init script, skips the checkconf step and just starts the ravencore websever
- added script "data_query" which replaces the mysql command in bash scripts for cleaner and faster execution
- added script "run_cmd" which will be called to run anything in $RC_ROOT/bin
- changed the name of dbsock to rcsock, so the name doesn't confuse people into thinking it only does sql
- changed the creation of rcsock to want the socket path on construct, instead of having $CONF a global array
- made the old wrapper program be removed if it still exists
- moved the bash_functions file to $RC_ROOT/var/lib and created a perl_functions.pm file in that directory
- fixed a bug in the user phpmyadmin login which made the page come up blank instead of load the application
- fixed a bug in the PHP rcsock where it would return nasty results on a concat command in the sql statement
- fixed a bug in the data_insert_id function where the socket wasn't returning it when it was supposed to
- fixed a bug with conf file creation when the file or directory doesn't exist in the first place
- fixed reference to the socket for PHP5, it won't connect unless unix:// is prepended to the socket path
- fixed the "[warn] NameVirtualHost *:443 has no VirtualHosts" warning
- fixed the bug where if the postconf command fails, checkconf.mail freezes

* Sun Jul 16 2006 cormander <admin@ravencore.com>
- version 0.2.0
- upgraded phpmyadmin to 2.8.2
- upgraded phpsysinfo to 2.5.2-rc3
- upgraded squirrelmail to 1.4.7
- added a new perl script called dbsock, which creates a socket and acts as the gobetween for php and mysql
- added to main.cf config to supress the "NIS lookups disabled" message in maillog
- added a warning to the dns page that you need at least one A and one NS record for your zonefile to exist
- added a login page to re-authenticate the admin user for phpMyAdmin
- changed permissions on the .shadow file so only root can see it, the new perl dbsock makes this possbile
- made first_valid_uid in dovecot.conf.in to fix imap login for the dovecot 1.0.x beta folks
- fixed the logout button so that it will destory the phpMyAdmin session data file, for security reasons
- fixed the first_valid_uid for dovecot 1.x configuration file to use the dynamic value of vmail's uid
- fixed the bug where "httpd" never showed as running, even if it was, on the services page
- fixed the errors with being unable to delete databases / database users
- fixed the debian installation bug where the "basename" command gives all sorts of errors
- fixed the permissions on the files in squirrelmail/data to be set to rcadmin:servgrp with 660 permissions
- fixed the vhosts.conf file so www is created for redirects, when it's supposed to
- redid a LOT of php code to call the new dbsock php object
- removed the adodb 3rd party program, the php dbsock object now takes care of all mysql calls
- removed the wrapper program, the perl dbsock code now takes care of all commands

* Sat Apr 15 2006 cormander <admin@ravencore.com>
- version 0.1.5
- upgraded adodb to 4.80
- upgraded phpmyadmin to 2.8.0.3
- upgraded phpsysinfo to 2.5.2-rc1
- removed static UID/GID of rcadmin and vmail so now the OS dynamically assigns them an unused system UID/GID
- .spec file now uses a Makefile instead, this also allows non-rpm users to do a "make install" of ravencore
- added ".local" support for awstats, just like the other 3rd party applications
- added support for ".ignore" files for the conf building, where you want to completly ommit a conf setting
- added checking on the /etc/sasldb2 file to make sure it isn't corrupt, to help keep SMTP auth working
- fixed awstats path in process_logs so webstats will now update properly as normal
- fixed the path to the awstats "icons", removed from the core conf files and put it in 3rdparty.include
- fixed the shutdown/reboot functions so you don't reboot the server again after the server comes back up
- fixed the init script so that ravencore still starts, even if not all database settings are met
- fixed the some php files to use <?php at the top instead of <? so all systems will parse them as php
- fixed the process of domain deletion to run rehash_logrotate there won't be errors from the hourly log cron

* Sat Feb 25 2006 cormander <admin@ravencore.com>
- version 0.1.4
- upgraded adodb to 4.72
- upgraded squirrelmail to 1.4.6
- added dutch, spanish, and hebrew language packs
- added rem_module.amavisd script for when you uninstall it, ravencore will automatically compensate
- added missing double quote at the end of the php open_basedir in the httpd.include files
- added squirrelmail vlogin plugin, and all server domains with mail enabled are automatically configured
- added configuration of webmail.* to point to squirrelmail, so every domain has webmail on apache port 80
- changed postfix main.cf's myhostname to be filled with the server env $HOSTNAME variable
- moved the content_filter for amavisd to the main.cf file, and set it to null on the 10025 smtp port
- moved 3rd party programs to var/apps directory, and added server Aliases for ravencore to point them there
- .local directories for the var/apps added, so you can run own version w/o them getting overwritten on upgrade
- .local files for service configuration are now picked up even when they don't have a base file that matches
- fixed the "relay to" catchall function, it didn't work for "untrusted" hosts
- fixed the "add database" page for users, it was giving an access denied error, only admin user could do it
- fixed the function in domains.php that allows the admin user to change which user the domain belongs to
- fixed webmail.php to select lcase so mail auto-login still works for email addresses entered in with some caps
- fixed add/update hosting for domains "alias" to update correctly at the time you click the add/update button
- fixed the session update idle time SQL query to look at the session_id field instead of the id field
- ravencore.httpd-2.2.conf copied from ravencore.httpd-2.0.conf since it seems to work just fine with apache 2.2
- webmail auto-login unsets squirrelmail session so that you won't have incorrect folders in the left frame
- postconf stderr output forced to /dev/null so there won't be any errors if main.cf has one before its rebuilt
- cut, cd, sort, tr, and uniq commands added to cmd_maps.base and made into variables in the scripts

* Tue Feb 21 2006 cormander <admin@ravencore.com>
- version 0.1.3
- added polish language translation
- language selection on login page is now sorted alphabetically
- logout function now kills all session variables, except the language selection
- database add/edit/remove functions fixed, they broke with the introduction of adodb in v0.1.1
- brackets removed from checkconf.amavisd so no MX lookup is done on localhost, to save some server load
- admin is now the only one able to edit a user's control panel username
- fixed bug where users were unable to add php/cgi/ssl hosting to a domain, even when they had permission to
- fixed bug where mailbox pop3/IMAP password was incorrectly being set when "spam folder" wasn't checked
- fixed bug on client view permissions page, unlimited values are now shown as unlimited
- fixed bug where you couldn't disable the login lock feature, it just kept refreshing to configuration page
- relay to host added as an option for catchall, so mail can get scanned locally and delivered remotly
- now a check to make sure the system group in postfix's setgid_group actually exists, SuSE had a problem here
- added ".local" support for ".in" configuration files. ex: amavisd.conf.in.local

* Mon Jan 16 2006 cormander <admin@ravencore.com>
- version 0.1.2
- fixed the server object to correct build the default settings page on installation
- fixed the session object to set a default to the lockout_time variable if it doesn't exist
- fixed the delete_hosting to remove the system user from the database table correctly
- corrected all the calls to rehash_httpd so it gives the domain name, and not a null entry
- removed the bad "Language" link from the admin page
- the "spam folder" link in mailbox edit now only shows if amavisd support is enabled on the server
- the "webmail" link on the list email page will now only show up if that email is an actual mailbox
- fixed the found() function in the session object so non-admin users can login correctly now

* Sun Jan 15 2006 cormander <admin@ravencore.com>
- version 0.1.1
- moved where session_destroy() was added in webmail's redirect.php to allow for the login page to remain working
- upgraded awstats to 6.5 final
- language is now correctly set from the login page when you first install ( when there is no default locale )
- added russian language pack
- converted PHP code to use the adodb database object for mysql queries
- started revision of PHP code to make object oriented, created domain, server, session, and user classes
- added ability to select on a per-mailbox basis whether spam is delivered into the INBOX or a "Spam" subfolder
- re-worked the auth.php code to allow for the admin user to still login, even if the connection to MySQL failed,
  to allow control over the functions which don't require a db connection, such as restart services
- did some changes to the amavisd.conf.in so that it has a less chance of having to be rebuilt on future upgrades

* Sun Jan 08 2006 cormander <admin@ravencore.com>
- version 0.1.0
- added output to the rebuild_conf_file to say what we're doing, if ran from the terminal
- made the init script unset the GOT_CONF if the installation is complete, so the checkconf scripts will run
- added support for dovecot 1.0 by dynamically loading the different conf file depending on the installed version
- the startup script now gives an important message the first time ravencore is started on install/upgrade
- added support for the use of proftpd as the ftp server, if vsftpd is not installed
- added support for $_[VAR_NAME] variables in built config files, so they can use the database settings
- added several more configuration directories to the etc/, to build more services' conf files
- changed how ravencore does spam filtering, by adding in amavisd-new support
- renamed the mailscan module as amavisd module
- updated phpMyAdmin to 2.7.0-pl2
- updated phpsysinfo to 2.5.1
- added JTA, a Java-based SSH client ( http://www.javassh.org/ ) to the admin section of ravencore
- redid the way the webmail auto-login works, by adding code to the redirect.php file on the rpmbuild
- added the ability for the | seperator in conf.d files for array type values, which give a dropdown selection
- added in language pack support, with norwegian translation bundeled in
- added the ability to add TXT DNS records, for SPF DNS records support
- php < 4.3 wasn't filling the $_ENV array, so a default was put in services.php so it works in php < 4.3

* Sun Dec 25 2005 cormander <admin@ravencore.com>
- version 0.0.6
- spamassassin and postfix's "on/off" status on services page now works on non-redhat distributions
- changed vsftpd's configuration parameter "anonymous_enable" and "userlist_enable" to be set to NO
- added "session.save_handler files" to php.include so login works with systems where it is set otherwise
- fixed the rehash_ftp script so that FTP users can login, and so the filemanager works
- added a "tmp" directory to each domain root, and the domain's httpd.include file now have upload_tmp_dir
  and session.save_path to use it, to keep privacy for php data per domain
- added more content to etc/services.dns so ravencore can control the DNS on debian based systems
- added pname.* files to etc/ to map service names to their process names so is_service_running works better
- "checkconf" option added to the init script, for when a full restart isn't needed
- php's function "stream_set_timeout" is only called if it exists, to support versions prior to php 4.3.0
- fixed the check for the pipe | character in ravencore's php function socket_cmd
- fixed the httpd-1.3 conf file to run non-ssl on port 8000 and SSL on port 8080
- changed the php _ENV variables to use the getenv function instead, to support apache-1.3.x
- pwdchk.cron.hourly is ran once an hour, to reset the password to a random string if it is still "ravencore"
- added "$myhostname" to main.cf's smtpd_sasl_local_domain for postfix version 1 support
- fixed the openssl verify on startup, some systems were not correctly sending output to /dev/stdout
- perl wrapper completly unsets the %ENV hash, and waits until just before execution to set uid to root
- added modular dependency checks with etc/dependencies.* files and etc/cmd_maps.* files as references
- symlinked the filemanager's tmp directory to the var/tmp directory in the ravencore root
- upgraded phpmyadmin to 2.7.0-pl1. added a patch the grab_globals.lib.php file to make it work with ravencore
- included the missing tarballs from webmail plugins, awstats, etc, that are needed for the src.rpm to rebuild on its own
- added the README.install file to the ravencore root directory, for those who didn't think to look for it on the website

* Sat Sep 10 2005 cormander <admin@ravencore.com>
- version 0.0.5
- removed the INBOX. prefix from the default folders for squirrelmail for better dovecot IMAP compatibility
- preconfigured some squirrelmail plugins: compatibility, timeout_user, sent_confirmation, and spamassassin
- patched the squirrelmail spamassassin plugin to work with the way ravencore does mailscanning
- for futher security, the wrapper checks the target file's UID and GID, they must be owned by root:rcadmin
- PATH variable is now set by bash_functions so we have it when ran in the wrapper's NULL enviroment
- fixed file and directory permissions of everything in the ravencore root to be somewhat more secure
- changed the redirect field to a textarea box on the add/edit mail page, to support multiple email redirects
- added the missing $MYSQL_ADMIN_HOST to the bash global variable $MYSQL_STR
- removed the ravencore.sql.in file, and made db_install simply load all the sql table files on install
- db_install now shows a dot for each table during the integrity check, as sort of a semi-progress bar
- added the "--restart" option to rehash_httpd, to do a full restart of apache, instead of just a reload
- "allow from all" added to vhosts.conf to override any system configuration to deny access to the vhost root
- checking added to the top of the rehash scripts to check if its corrisponding conf.d file is executable
- the runlevel of all the services in etc/services.* are now set to on, when ravencore is installed / upgraded
- checkconf script looks even deeper into apache conf files to find the user it runs as
- SSL for dovecot is now disabled by default in the conf file, you can optionally enable it if you need to
- some messages in the GUI are now passed with session varibles so you won't get a post warning if you refresh
- added support for multiple ways to deal with non-deliverable mail: send_to, bounce, delete_it, and alias_to
- checkconf now tries to make sure suid on the wrapper works, and if not, attempts to re-install it
- fixed a bug with awstats not being able to navigate and view the previous months of webstats
- removed the .in conf files, they are now instead built by the files in their directories in $RC_ROOT/etc
- the ravencore.httpd.pid file is now removed correctly when the control panel is stopped
- control panel now always runs normal http on port 8000, and opens up 8080 for SSL if it is enabled
- sudo is used ( if it exists ) to test the integrity of the wrapper when ravencore is started
- all the rehash scripts are now ran the first time you login to the panel after upgrade
- is_service_running does some more things to figure out if services are running even with a zero exit status
- enhancements made to the ravencore.init script to improve the startall / stopall commands

* Sun Aug 21 2005 cormander <admin@ravencore.com>
- version 0.0.4
- added the option for a wrapper in C code, that gets compiled if gcc exits. if not, we use the perl wrapper
- new script called "testsuid" which is used by the new C wrapper to print the results of the "whoami" command
- created a variable $HTTPD_INIT which is the basename of $HTTPD, and used with $INITD to restart apache
- wrapped <IfModule mod_ssl.c> around the SSL in vhost tags to prevent an apache startup failure
- edited the rehash_mail script to specify the -c flag on saslpasswd2 only if the user doesn't exist
- edited the mail_del script to remove the email user from the sasl database
- fixed the bug which cause the admin unable to assign the user "No one" ( empty uid ) to a domain
- rehash_named now gets executed when adding a domain with default dns records
- fixed the "Startup services" page to actually work
- automatically generate a simlink of the apache module for php if it is named mod_php, to point to libphp
- added in checking for .local files that will be used instead of the respective file in $RC_ROOT/etc if they
  exist, and upgrading ravencore won't overwite them so you can preserve your own configuration.
- db_install script now checks database integrity on upgrade, using the table .sql files in etc/sql_tables
  to look for any new columns that may have added to the tables since the previously installed version
- checkconf script now takes care of the creation of the control panel SSL key and cert files
- version now "cached" on startup, so an upgrade can be detected and taken care of when ravencore is restarted
- checkconf script checks to make sure that "/bin/false" is registered as a shell, and if not, adds it
- conf.d/web.conf DEFAULT_SHELL changed to "/bin/false"
- added the checking for "/usr/lib64" for the httpd_modules to take priority over "/usr/lib" if it exists
- you can now change what port ravencore runs on by editing port.conf, which won't be overwritten on upgrade
- added the GPL license header to each script
- fixed a bug with the log rotation, apache is reloaded afterwards now and awstats continues to update new info
- mailscan script now calls sendmail with the -t switch to fix not being able to cc: or bcc: recipients
- 

* Sun Aug 7 2005 cormander <admin@ravencore.com>
- version 0.0.3
- edited ravencore.init to detect compiled-in httpd modules, and add additional modules as needed on startup 
- edited startup process to include dynamic support to load either php4 or php5
- changed path to startup script from /etc/rc.d/init.d to /etc/init.d
- Bundeled in AWStats and changed all appropriate paths that reference it
- fixed the "delete" function for email addresses
- changed the "testsuid" function on the wrapper to run the "whoami" command so we are sure we are root
- added more data to the paths.httpd and paths.httpd_modules to support systems with alternate paths to apache
- checkconf scripts take care of install steps instead of the pre and post RPM scripts, for non-RPM distro support
- no longer assumes "apache" for the user that runs apache, but tries to figure it out based off the system
- improved the db_install script to work with mysql 4.x, and to echo it's progress to the terminal
- on installation, the control panel now won't start until it was able to gain access to mysqld
- finished the code where services that are not installed don't show up in the control panel
- fixed bug with the admin using phpmyadmin, where you'd only see a single database in some cases
- WEB_SERVER_GROUP, VMAIL_UID, VMAIL_GID are no longer variables that can be set with settings in the database
- servgrp's GID set to be defined as 149. Its modification is also now done "correctly" with usermod / groupmod
- fixed the ravencore.init script's "statusall", "stopall", and "startall" commands to better work
- the ravencore.init script now loads basic versions of the init functions library if it isn't able to find it
- added semi-dynamic path to sendmail in mailscan script, for systems that don't have /usr/sbin/sendmail.postfix
- locked down the vsftpd.conf file to chroot ftp users to their domain, and put it in the checkconf script
- upgraded the filemanager from webftp 2.8 to phpwebftp 3.0

* Sat Jul 30 2005 cormander <admin@ravencore.com>
- version 0.0.2
- Updated rehash_httpd to fix several bugs with log files and domain httpd.include generation
- Fixed edit_mail.php so you can have the same mailname in two different domains with the same password
- Bundeled in Squirrelmail and added a webmail auto-login feature
- Added a section for the admin user to login to "super user" mode in phpMyAdmin
- Fixed the code that checks to see if ravencore is outdated when you login, also made it a database setting
- Added basic support for domain ssl certificates, you can manully add them with a mysql insert statement
- Added basic email catchall support for mail delivered to non-existant email addresses

* Sat Jul 15 2005 cormander <admin@ravencore.com>
- version 0.0.1
- Initial build.
