[% IF 443 == port %]<IfModule mod_ssl.c>
[% END %]<VirtualHost [% ip_addr.ip_addr %]:[% port %]>
	ServerName   [% domain.name %]:[% port %]
[% FOREACH alias = aliases %]
	ServerAlias [% alias %]
[% END %]
	DocumentRoot [% domain.root %]/httpdocs
	CustomLog    [% domain.root %]/var/log/access_log combined
	ErrorLog     [% domain.root %]/var/log/error_log

[% IF domain.cgi %]	ScriptAlias  /cgi-bin/ [% domain.root %]/cgi-bin/
[% END %][% IF domain.webstats %]	ScriptAlias  /awstats/ /var/apps/awstats/wwwroot/cgi-bin/

	Alias /icon/ [% rc_root %]/var/apps/awstats/wwwroot/icon/

	<Directory [% rc_root %]/var/apps/awstats/wwwroot/cgi-bin/>
		DirectoryIndex awstats.pl
	</Directory>[% END %]

	<IfModule mod_ssl.c>[% IF 443 == port %]
		SSLEngine on
		SSLVerifyClient none

		SSLCertificateFile [% domain.root %]/conf/server.crt
		SSLCertificateKeyFile [% domain.root %]/conf/server.key[% ELSE %]
		SSLEngine off[% END %]
	</IfModule>

	<Directory [% domain.root %]/httpdocs>
		Options [% IF domain.dir_index %]+[% ELSE %]-[% END %]Indexes [% IF domain.cgi %]+Includes +ExecCGI[% END %]
		DirectoryIndex index.html index.htm [% IF domain.php %]index.php[% END %]
[% FOREACH version IN [ 4, 5 ] %]
		<IfModule mod_php[% version %].c>[% IF domain.php %]
			php_admin_flag engine on
			php_admin_value open_basedir "[% domain.root %]"
			php_admin_value upload_tmp_dir "[% domain.root %]/tmp"
			php_admin_value session.save_path "[% domain.root %]/tmp"
			php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f [% apache_user %]@[% domain.name %]"[% ELSE %]			
			php_admin_flag engine off[% END %]
		</IfModule>[% END %]
[% IF domain.cgi %]
		<IfModule mod_perl.c>
			<Files ~ (\\.pl)>
				SetHandler perl-script
				PerlHandler ModPerl::Registry
				allow from all
				PerlSendHeader On
			</Files>
		</IfModule>[% END %]
	</Directory>
</VirtualHost>
[% IF domain.webmail %]
<VirtualHost [% ip_addr.ip_addr %]:[% port %]>
	ServerName   webmail.[% domain.name %]:[% port %]
	DocumentRoot [% rc_root %]/var/apps/squirrelmail

	<IfModule mod_ssl.c>[% IF 443 == port %]
		SSLEngine on
		SSLVerifyClient none[% ELSE %]
		SSLEngine off[% END %]
	</IfModule>

	<Directory [% rc_root %]/var/apps/squirrelmail>
		Options -Indexes
[% FOREACH version IN [ 4, 5 ] %]
		<IfModule mod_php[% version %].c>
			php_admin_flag engine on
			php_admin_value open_basedir "[% rc_root %]/var/apps/squirrelmail"
			php_admin_value upload_tmp_dir "[% rc_root %]/var/tmp"
			php_admin_value session.save_path "[% rc_root %]/var/tmp"
		</IfModule>[% END %]
	</Directory>
</VirtualHost>[% IF 443 == port %]
</IfModule>[% END %][% END %]
