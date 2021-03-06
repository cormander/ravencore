#
#                  RavenCore Hosting Control Panel
#                Copyright (C) 2005  Corey Henderson
#
#     This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#

#
# ravencore's misc backend functions
#

# return the default locale

sub get_default_locale {
	my ($self) = @_;

	return $self->{CONF}{DEFAULT_LOCALE} if $self->{CONF}{DEFAULT_LOCALE};

	# we get here if we have no default locale set (usually on first install)
	# get the locale from the enviroment
	my $lang = $ENV{LANG};

	# this is usually has some other data we don't want, like: en_US.UTF-8
	# we only want the part before the dot
	$lang =~ s/\..*//;

	return $lang;
}

# read a configuration file and set the $self->{CONF} hash

sub read_conf_file {
	my ($self, $file) = @_;

	# die here if $file doesn't exist... for obvious reasons
	$self->die_error("Unable to load config file: " . $file) if ! -f $file;

	my %data = $self->parse_conf_file($file);

	foreach my $key (keys %data) {
		$self->{CONF}{$key} = $data{$key};
	}

	$self->debug("Read configuration file: " . $file);

}

# set a conf variable

sub set_conf_var {
	my ($self, $input) = @_;

	my $key = $input->{key};
	my $val = $input->{val};

	# $key should always be uppercase
	$key = uc($key);

	# simple error checking
	return $self->do_error("set_conf_var a key") if ! $key;

	# check to see if it's already in the database
	my $sql = "select count(*) from settings where setting = " . $self->{dbi}->quote($key) . 
	" and value = " . $self->{dbi}->quote($val);
	my $result = $self->{dbi}->prepare($sql);

	$result->execute();
	my @count = $result->fetchrow_array;
	$result->finish();

	if ($count[0] > 0) {
		# if it's there, update it
		$self->xsql("update settings set value = ? where setting = ?", [$val, $key]);
	} else {
		# if it's not there, insert it
		$self->xsql("insert into settings (setting, value) values (?,?)", [$key, $val]);
	}

	# store it in this sessions's CONF
	$self->{CONF}{$key} = $val;

	return;
}

#

sub webstats {
	my ($self, $input) = @_;

	my $did = $input->{did};
	my $str = $input->{QUERY_STRING};

	my $dom = $self->get_domain_by_id({id => $did});

	my $domain_name = $dom->{name};

	# null out the configdir in the query string so people can't hack it to look at other webstats
	$str =~ s/configdir=([\/-\w.]+)//;

	$str =~ s/config=([\/-\w.]+)//;

	# put this domain's configdir into the query string so we're looking for the conf file in the right place
	$str .= "&configdir=" . $self->{CONF}{VHOST_ROOT} . "/" . $domain_name . "/conf";

	$str .= "&config=" . $domain_name . "&did=" . $did . "&Lang="; # TODO: fix: . $locales[$current_locale]['awstats'];

	# run awstats in a simulated CGI enviroment

	$ENV{GATEWAY_INTERFACE} = "CGI/1.1";
	$ENV{QUERY_STRING} = $str;

	my $cmd = $self->{RC_ROOT} . "/var/apps/awstats/wwwroot/cgi-bin/awstats.pl | sed 's/awstats.pl/webstats.php/g' | sed '1d' | sed '1d' | sed '1d' | sed '1d'";

	my $output = `$cmd 2> /dev/null`;

	$output =~ s|<input type="submit" value=" OK " class="aws_button" />|<input type="submit" value=" OK " class="aws_button" /><input type="hidden" name="did" value="$did"|;

	delete $ENV{GATEWAY_INTERFACE};
	delete $ENV{QUERY_STRING};

	return $output;
}

# return a hash, the keys are the module names and the values are the conf file to
# use for checks on said module

sub module_list {
	my ($self) = @_;

	my @modules, $mod;

	my @confs = dir_list($self->{RC_ROOT} . '/etc/modules/*/settings.ini');

	foreach my $conf (@confs) {
		$mod = $conf;
		$mod =~ s|.*/etc/modules/(\w*?)/settings.ini$|$1|;
		push @modules, $mod;
	}

	return @modules;
}

# just like module_list, but only returns modules that are installed

sub module_list_installed {
	my ($self) = @_;

	my @installed;

	my @modules = $self->module_list;

	foreach my $mod (@modules) {
		# if there is not an "installed" file for this module, skip it
		next unless -f $self->{RC_ROOT} . '/etc/modules/' . $mod . '/installed';

		push @installed, $mod;
	}

	return @installed;
}

# just like module_list_installed, but only returns modules that are enabled

sub module_list_enabled {
	my ($self) = @_;

	my @enabled;

	my @modules = $self->module_list_installed;

	foreach my $mod (@modules) {
		# if there is a "disable" file for this module, skip it
		next if -f $self->{RC_ROOT} . '/etc/modules/' . $mod . '/disabled';

		push @enabled, $mod;
	}

	return @enabled;
}

# disable a module

sub disable_module {
	my ($self, $ref) = @_;

	my $mod = $ref->{module};

	return $self->do_error("Illegal module name") if $mod !~ /^[a-z0-9_]*$/;
	return $self->do_error("Module does not exist") unless -d $self->{RC_ROOT} . '/etc/modules/' . $mod;

	file_touch($self->{RC_ROOT} . '/etc/modules/' . $mod . '/disabled');

	my $msg = "$mod has been disabled";

	$self->do_error($msg);

	$self->reload({message => $msg});
}

# enable a module

sub enable_module {
	my ($self, $ref) = @_;

	my $mod = $ref->{module};

	return $self->do_error("Illegal module name") if $mod !~ /^[a-z0-9_]*$/;
	return $self->do_error("Module does not exist") unless -d $self->{RC_ROOT} . '/etc/modules/' . $mod;

	file_delete($self->{RC_ROOT} . '/etc/modules/' . $mod . '/disabled');

	my $msg = "$mod has been enabled";

	$self->do_error($msg);

	$self->reload({message => $msg});
}

#

sub gpl_agree {
	my ($self) = @_;

	$self->{gpl_check} = 1;
	file_touch($self->{RC_ROOT} . '/var/run/gpl_check');

	$self->reload({ message => "GPL License has been agreed to" });

	sleep 2;
}

# this is called after an install/upgrade. rehash everything

sub complete_install {
	my ($self) = @_;

	return unless $self->{config_complete};

	$self->debug("Running post-installation process");

	$self->rehash_httpd;

	$self->rehash_ftp;

	$self->rehash_mail;

	$self->rehash_named;

	file_touch($self->{RC_ROOT} . '/var/run/install_complete');

	$self->{install_complete} = 1;

	$self->checkconf;

	$self->reload({ message => "Installation complete" });

	sleep 2;
}

# is the given service running?

sub service_running {
	my ($self, $input) = @_;

	my $service = $input->{service};

	# This script tries to determine if a service is running. It works just fine
	# on most systems, but still has trouble on some distributions which do not
	# set correct or expected exit codes when they run.

	my $init = $self->{INITD} . '/' . $service;

	# check to see if the init script exists
	return 0 unless -f $init;

	# send the "status" option to the init script, and force any error'd output to stdout
	my $stat = `$init status 2> /dev/stdout`;

	# save the exit status of the init script
	#	my $ret = $?;
	#	print $stat . "- $init - $ret - $!\n";

	# check to see if $stat says running... must be done before the stopped check, amavisd
	# also has milter which can be stopped even when amavisd is actually running
	if ($stat =~ /running/i or $stat =~ /enabled/i) {
		return 1;
	}

	# check to see if the init script said we're "stopped" or "not running" or etc...
	if ($stat =~ /stopped/i or $stat =~ / not /i or $stat =~ /dead/i or $stat =~ /disabled/i) {
		return 0;
	}

	# exit status of 0 means running OK ( if we didn't find "Usage" in the script output )
	#	if( $ret == 0 and $stat !~ m/Usage/ )
	#	{
	#	return 1;
	#	}

	return 1 if pidof($service);

	# search for different process names fro this service
	my @pnames = file_get_array($self->{RC_ROOT} . '/etc/pname.' . $service);

	foreach my $pname (@pnames) {
		return 1 if pidof($service);
	}

	# if we get here, no idea if it's running or not
	return $self->do_error("Unable to determine the status of " . $service);

}

# show messages in the mail queue, in a nice table HTML format

sub mailq {
	my ($self) = @_;

	# TODO: make this code count and keep track of each item in the queue, so we can implement "pagination" here,
	#	   so if there are several hundred things in the queue, only display 20 or so of them at once, with a list
	#	   of how many pages of mailq info there is. possibly implement a search function, only count / cache that
	#	   specific item if it matches search criteria

	# TODO: make a way to show the mail source, additional postfix queue information, and provide a way to re-deliver
	#	   and / or delete the message(s).

	# get in our mailq data. force errors to /dev/null
	# TODO: make this configurable
	my $data = `/usr/sbin/postqueue -p 2> /dev/null`;

	# make the end of the first line contain an additional return character
	$data =~ s|-$|\n|m;

	# get rid of spaceing.... down until we only have single spaces and double spaces
	while ($data =~ s|   |  |g) {}

		# turn the "double spaces" into close/open cell
		$data =~ s|  |</td><td>|g;

		# add row and cell starts to the beginnings of lines
		$data =~ s|^|<tr><td>|gm;

		# add cell and row ends to the ends of lines
		$data =~ s|$|</td></tr>|gm;

		# blank cells need to be killed and widened to the full length of the table
		$data =~ s|<td></td><td>|<td colspan="100%" align="right">|g;

		# the last line needs to be a full colspan
		$data =~ s|>--| colspan="100%" align="right">|;

		# put the "size" (in front of the date) in it's own cell and add "KB" to the end of it
		$data =~ s|</td><td>(\d*) |</td><td>$1 KB</td><td>|g;

		# turn the "date" bold
		$data =~ s|</td><td>(\d*) KB</td><td>([a-zA-Z0-9\ :]*)</td>|</td><td>$1 KB</td><td><b>$2</b></td>|g;

		# null out the dashes, but not all of them just yet
		while ($data =~ s|--|-|g) {}

		# this affects the header, turn "- -" into close/open cell
		$data =~ s|- -|</td><td>|g;

		# get rid of remaining dashes
		$data =~ s|-||g;

		# make links
		$data =~ s|([A-Z0-9]{11})|<a href="$1">$1</a>|g;

		# replace completly empty lines with a <hr> that spans the whole table
		$data =~ s|<tr><td></td></tr>|<tr><td colspan="100%"><hr></td></tr>|g;

		# print the result
		$data = '<table border="0" cellspacing=0 cellpadding=5>' . $data . '</table>';

		return $data;
}

#
# Rehash the httpd Configuration Files
#

sub rehash_httpd {
	my ($self, $input) = @_;

	return if $self->{DEMO};

	my @modules = $self->module_list_enabled;

	return unless in_array('web', @modules);

	return $self->do_error("VHOST_ROOT not defined") unless $self->{CONF}{VHOST_ROOT};

	my $restart_httpd;

	# Always make sure that the vhost root exists and is readable
	mkdir_p($self->{CONF}{VHOST_ROOT});

	# chown / chmod the vhost root with correct permissions
	file_chown('root:servgrp',$self->{CONF}{VHOST_ROOT});
	chmod 0755, $self->{CONF}{VHOST_ROOT};

	# make sure ip addresses are loaded into the db
	$self->ip_list;

	# set the vhosts file
	my $vhosts = $self->{RC_ROOT} . "/etc/vhosts.conf";

	# check to make sure we're included in the apache conf file
	my $output = file_get_contents($self->{httpd_config_file});
	my $include_vhosts = 'Include ' . $vhosts;

	# if not, append to it
	file_append($self->{httpd_config_file}, $include_vhosts . "\n") unless $output =~ m|^$include_vhosts$|m;

	#
	# Rebuild the vhosts.conf file
	#

	chomp(my $httpd_user = file_get_contents($self->{RC_ROOT} . '/var/run/httpd_user'));
	$httpd_user = ( $httpd_user ? $httpd_user : 'apache' );

	my $hosts = {
		apache_user => $httpd_user,
		rc_root => $self->{RC_ROOT},
		vhost_root => $self->{CONF}{VHOST_ROOT},
	};

	# walk down all our IP addresses and build the domains on them
	foreach my $ip (@{$self->get_ip_addresses}) {

		my $dom;
		my $ip_addr = $ip->{ip_address};

		# define the nested structure for our template
		my $ref = {
			ip_addr => $ip_addr,
			ports => {
				80 => [],
				443 => [],
			},
		};

		# build the IP's default domain first
		$dom = $self->get_domain_by_id({id => $ip->{default_did}});

		if ("on" eq $dom->{'hosting'}) {
			$self->make_virtual_host($dom);
			push @{$ref->{ports}{80}}, $self->domain_to_tt_ref($dom);
			push @{$ref->{ports}{443}}, $self->domain_to_tt_ref($dom) if "true" eq $dom->{host_ssl};
		}

		# build the rest of the domains on this IP
		foreach $dom (@{$self->get_domains_by_ip({ip => $ip_addr})}) {

			# skip the default domain since we already processed it
			next if $dom->{id} eq $ip->{default_did};

			if ("on" eq $dom->{'hosting'}) {
				$self->make_virtual_host($dom);
				push @{$ref->{ports}{80}}, $self->domain_to_tt_ref($dom);
				push @{$ref->{ports}{443}}, $self->domain_to_tt_ref($dom) if "true" eq $dom->{host_ssl};
			}
		}

		# if this is the IP address wildcards get setup on, add them
		if ($self->{CONF}{VHOST_DEFAULT_IP} eq $ip_addr) {

			foreach $dom (@{$self->get_domains_with_no_ip}) {
				next unless "on" eq $dom->{'hosting'};

				$self->make_virtual_host($dom);
				push @{$ref->{ports}{80}}, $self->domain_to_tt_ref($dom);
				push @{$ref->{ports}{443}}, $self->domain_to_tt_ref($dom) if "true" eq $dom->{host_ssl};
			}

		}

		push @{$hosts->{ip_addresses}}, $ref;
	}

	# template toolkit
	my $tt = Template->new({ INCLUDE_PATH => [ $self->{RC_ROOT}."/etc/tt2/httpd" ] });
	my $data;

	

	# process the template
	$tt->process('vhosts.tpl', $hosts, \$data);

	# write out the template
	file_write($vhosts, $data);

	# Test to be sure we can restart apache. Save the results to http_tmp
	my $tmp_file = $self->{RC_ROOT} . '/var/tmp/rehash_httpd.' . $$;

	$self->debug("Checking apache conf file syntax");

	my $ret = system($self->{HTTPD} . ' -t &> ' . $tmp_file);

	if($ret != 0) {
		# cat out what the error was
		my $error = file_get_contents($tmp_file) . "\n" . "Not restarting apache\n";

		file_delete($tmp_file);

		$self->do_error($error);

		return;
	}

	file_delete($tmp_file);

	# if we are running, reload or restart
	if($self->service_running("httpd")) {
		my $cmd;

		if( $restart_httpd == 1 ) { $cmd = 'restart' }
		else { $cmd = 'reload' }

		$self->debug($cmd . "ing apache");

		$self->service({ service => 'httpd', cmd => $cmd });
	} else {
		# otherwse, start apache
		$self->debug("Apache not running, starting it");

		$self->service({ service => 'httpd', cmd => 'start' });
	}
   
}

# convert the database values to things Template can understand

sub domain_to_tt_ref {
	my ($self, $dom) = @_;

	my $ref = {
		name => $dom->{name},
		root => $self->{CONF}{VHOST_ROOT} . '/' . $dom->{name},
		physical_hosting => ( "physical" eq $dom->{host_type} ? 1 : 0 ),
		redirect_url => $dom->{redirect_url},
		ssl => ( "true" eq $dom->{host_ssl} ? 1 : 0 ),
		php => ( "true" eq $dom->{host_php} ? 1 : 0 ),
		cgi => ( "true" eq $dom->{host_cgi} ? 1 : 0 ),
		dir_index => ( "true" eq $dom->{host_dir} ? 1 : 0 ),
		webmail => ( "yes" eq $dom->{webmail} ? 1 : 0 ),
		webstats => ( "yes" eq $dom->{webstats_url} ? 1 : 0 ),
	};

	return $ref;
}

# build all the directories

sub make_virtual_host {
	my ($self, $row) = @_;

	return unless $row->{'name'};

	# Make sure the proper directories exist and that they are set to the correct permissions
	my $domain_root = $self->{CONF}{VHOST_ROOT} . "/" . $row->{'name'};

	if ($row->{'host_type'} eq "physical") {
		mkdir_p(
			(
			$domain_root,
			$domain_root . "/var/log",
			$domain_root . "/var/awstats",
			$domain_root . "/httpdocs",
			$domain_root . "/conf",
			$domain_root . "/tmp",
			)
		);

		# cgi-bin if cgi
		mkdir_p($domain_root . "/cgi-bin") if "true" eq $row->{host_cgi};

		# make sure ssl keys exist if this is over ssl
		$self->ssl_genkey_pair($domain_root . "/conf") if "true" eq $row->{host_ssl};

		# chmod / chown the directories
		file_chown("root:servgrp", $domain_root);
		file_chown($row->{'login'} . ":servgrp", $domain_root . "/httpdocs", $domain_root . "/tmp");

		chmod 0750, $domain_root,
		$domain_root . "/httpdocs";

		chmod 0770, $domain_root . "/tmp";

		# If the awstats configuration file doesn't exist, build it
		if ( ! -f $domain_root . "/conf/awstats." . $domain . ".conf" ) {
			my $awstats_conf = file_get_contents($self->{RC_ROOT} . "/etc/awstats.model.conf.in");

			# Run the needed substitution statements to edit the config file appropriatly
			$awstats_conf =~ s|LogFile=".*"|LogFile="$domain_root/var/log/access_log.1"|;
			$awstats_conf =~ s|SiteDomain=".*"|SiteDomain="$domain"|;
			$awstats_conf =~ s|DirData=".*"|DirData="$domain_root/var/awstats"|;

			file_write($domain_root . "/conf/awstats." . $domain . ".conf", $awstats_conf);
		}

		# make sure a symlink to it exists in /etc/awstats
		if ($row->{'webstats_url'} eq "yes" and ! -l "/etc/awstats/" . $domain . ".conf") {
			mkdir_p("/etc/awstats");
			unlink ("/etc/awstats/awstats." . $domain . ".conf");
			system ("ln -s " . $domain_root . "/conf/awstats." . $domain . ".conf /etc/awstats/awstats." . $domain . ".conf");
		}

		# make sure it doesn't exist if webstats_url is no
		if ($row->{'webstats_url'} ne "yes") {
			unlink ("/etc/awstats/awstats." . $domain . ".conf");
		}
	}
}

#

sub ssl_genkey_pair {
	my ($self, $path) = @_;

	if (-x '/usr/bin/openssl') {
	# generate the key if non-existant

		if ( ! -f $path . '/server.key' ) {
			$self->do_error("Creating SSL key file in $path....");
			system('openssl genrsa -rand /proc/apm:/proc/cpuinfo:/proc/dma:/proc/filesystems:/proc/interrupts:/proc/ioports:/proc/pci:/proc/rtc:/proc/uptime 1024 > ' . $path . '/server.key 2> /dev/null');
		}

		# generate the cert if non-existant

		if ( ! -f $path . '/server.crt' ) {

			$self->do_error("Creating SSL cert file in $path....");

			open SSL, '| openssl req -new -key ' . $path . '/server.key -x509 -days 365 -out ' . $path . '/server.crt 2>/dev/null';

			print SSL "--\n";
			print SSL "SomeState\n";
			print SSL "SomeCity\n";
			print SSL "SomeOrganization\n";
			print SSL "SomeOrganizationalUnit\n";
			print SSL $ENV{HOSTNAME} . "\n";
			print SSL 'root@' . $ENV{HOSTNAME} . "\n";

			close SSL;

		}

	}

}

# A function to print out the server aliases for a domain

sub server_alias {
	my ($self, $domain, $www) = @_;

	my $data;
	my $sql;
	my $result;

	$data = "\tServerAlias  " . $domain . "\n";

	# $self->debug($domain . " has server alias " . $domain);

	if ($www eq "true") {
		$data .=  "\tServerAlias  www." . $domain . "\n";
		# $self->debug($domain . " has server alias www." . $domain );
	}

	# Find domains aliased to this domain, without this they will just show the default...
	$sql = "SELECT name, www FROM domains WHERE redirect_url = '" . $domain . "'" ;
	$result = $self->{dbi}->prepare($sql);
	$result->execute;

	while ($row = $result->fetchrow_hashref) {
		$data .= $self->server_alias($row->{'name'}, $row->{'www'});
	}

	return $data;
}

#

sub rehash_mail {
	my ($self, $input) = @_;

	return if $self->{DEMO};

	# check to make sure that this server is a mail server
	my @modules = $self->module_list_enabled;

	return unless in_array('mail', @modules);

	#
	return unless $self->{CONF}{VMAIL_ROOT};
	return unless $self->{CONF}{VMAIL_USER};
	return unless $self->{CONF}{SASL2_DB};

	#
	mkdir_p($self->{CONF}{VMAIL_ROOT}) unless -d $self->{CONF}{VMAIL_ROOT};

	#
	my $vtransportmap;
	my $vmaildomains;
	my $relay_domains;
	my $vmailbox;
	my $valiasmap;
	my $login_maps;

	my $output;
	my $mail;
	my $dom;

	# The system user mail will run as
	my $VMAIL_UID = getpwnam($self->{CONF}{VMAIL_USER});
	my $VMAIL_GID = getgrnam($self->{CONF}{VMAIL_USER});

	# check to make sure that the /etc/sasldb2 file isn't corrupt

	if (-f $self->{CONF}{SASL2_DB} && file_get_contents($self->{CONF}{SASL2_DB}) eq "") {
		file_delete($self->{CONF}{SASL2_DB});
	}

	# cache a list of current sasl users
	# TODO: check for errors here
	my @sasl_users = `sasldblistusers2 2> /dev/null`;
	chomp @sasl_users;

	# clear yaa's info so we can rebuild it
	my $slh;

	if ($self->{perl_modules}{DBD::SQLite}) {
		$slh = DBI->connect('dbi:SQLite:dbname='.$self->{RC_ROOT}."/var/apps/yaa/data/autoresponder_data","","");
		$slh->do("delete from autoresponder_data");
	}

	# individual mail addresses
	my $dovecot_passwd;

	foreach $mail (@{$self->get_mail_users}) {

		next unless "on" eq $mail->{mail_toggle};

		my $dname = lc $mail->{name};
		my $mname = lc $mail->{mail_name};
		my $email_addr = $mname . '@' . $dname;
		my $domain_root = $self->{CONF}{VMAIL_ROOT} . "/" . $dname;

		$self->debug("Rebuilding configuration for " . $email_addr);

		mkdir_p($domain_root . "/" . $mname);

		file_chown($self->{CONF}{VMAIL_USER} . ':' . $self->{CONF}{VMAIL_USER}, $domain_root, $domain_root . "/" . $mname);

		# chech for the imap .subscriptions
		my $subscriptions = $domain_root . "/" . $mname . "/.subscriptions";

		#
		if ( ! -f $subscriptions ) {

			#
			my @dirs = ('.Sent', '.Trash', '.Drafts', '.');
			my $append;

			push @dirs, '.Spam' unless "true" ne $mail->{spam_folder};

			foreach my $dir ( @dirs ) {

				#
				mkdir_p(
					$domain_root . "/" . $mname . "/" . $dir . "/cur",
					$domain_root . "/" . $mname . "/" . $dir . "/new",
					$domain_root . "/" . $mname . "/" . $dir . "/tmp"
				);

				#
				file_chown_r($self->{CONF}{VMAIL_USER} . ':' . $self->{CONF}{VMAIL_USER}, $domain_root . "/" . $mname . "/" . $dir);
				chmod 0700, $domain_root . "/" . $mname . "/" . $dir;

				# store the variable so we don't open / write / close the file inside this loop
				$append .= $dir . "\n" unless $dir eq ".";

			}

			# append the cache'd dirs here
			file_append($subscriptions, $append);

			file_chown($VMAIL_USER.':'.$VMAIL_USER, $subscriptions);
			chmod 0600, $subscriptions;

		}

		# put this email in the virtual mailbox and transport tables
		$vtransportmap .= $email_addr . "\t\tvirtual:\n";
		$vmailbox .= $email_addr . "\t\t" . $dname . "/" . $mname . "/\n";

		if ("true" eq $mail->{spam_folder}) {
			$vmailbox .= $mname . '+spam@' . $dname . "\t\t" . $dname . "/" . $mname . "/.Spam/\n";
		}

		# check to see if this email should have any redirects

		# first, unset the alias_map variable
		my $alias_map = "";

		# if this is a local mailbox, tell the alias_map to deliver locally
		$alias_map = $email_addr unless "true" ne $mail->{'mailbox'};

		# if autoreply is set
		if ( 1 == $mail->{autoreply} and $slh) {
			$self->debug("$email_addr is setup to autorespond via yaa");
			$slh->do("insert into autoresponder_data values (1, ?, ?, ?, ?, ?, ?)", undef, $mail->{autoreply_body}, $mail->{autoreply_subject}, '', '', $email_addr, $dname);

			$alias_map = $mname . '@yaa-autoreply.' . $dname unless $alias_map;
			$alias_map .= ',' . $mname . '@yaa-autoreply.' . $dname if $alias_map;
		}

		# if redirects
		if ($mail->{redirect_addr}) {
			# if $alias_map is emtpy, we don't start with a comma
			if ($alias_map eq "") {
				$alias_map = $mail->{redirect_addr};
			} else {
				$alias_map .= "," . $mail->{redirect_addr};
			}
		}

		# only put this entry in valiasmap if the variable exists
		$valiasmap .= $email_addr . "\t\t" . $alias_map . "\n" unless $alias_map eq "";

		# put this email in login_maps
		$login_maps .= $email_addr . "\t\t" . $email_addr . "\n";

		# smtp SASL authentication

		# check to see if users exists
		my $user_exists = 0;

		foreach (@sasl_users) { $user_exists = 1 unless $_ ne $email_addr; }

		my $sasl_action;

		if ($user_exists == 0) { 
			# user does not exist, create it with -c
			$sasl_action = "-c";
		} else {
			# make sure we unset it, so a previous -c won't carry over
			$sasl_action = "";
		}

		# set sasl passwd
		my $cmd = "echo " . $mail->{passwd} . " | saslpasswd2 " . $sasl_action . " -p -u " . $dname . " " . $mname;
		$output .= `$cmd 2>&1`;

		# build a random 2 letter "salt" string for use in the perl crypt fucntion in the docevat passwd-file below
		my $salt_str;

		for (my $i = 0; $i < 2; $i++) { $salt_str .= pack("C",int(rand(26))+65); }

		# dovecot passwd-file authentication

		$dovecot_passwd .= $email_addr . ":" . crypt($mail->{passwd},$salt_str) . ":" . $VMAIL_UID . ":" . $VMAIL_GID . "::" . $domain_root . "/" . $mname . ":/bin/false\n";

	}

	# close DBD::SQLite handle if it's open
	$slh->disconnect if $slh;

	#
	# stop spam from going off-server via a redirect
	#
	my @redir_emails;

	foreach $mail (@{$self->get_mail_users}) {

		next unless "true" eq $mail->{redirect};

		# each email is seperated by a comma
		my @email_list = split /,/, lc $mail->{redirect_addr};

		foreach my $email (@email_list) {
			# a single email can show up many times, only add it to the list if it isn't there
			push @redir_emails, $email unless in_array($email, @redir_emails);
		}

	}

	# add the redir_emails to the $valiasmap
	foreach my $email (@redir_emails) {

		#
		my $spam = $email;
		my $badh = $email;

		$spam =~ s/@/+spam@/;
		$badh =~ s/@/+badh@/;

		# send spam to /dev/null
		$valiasmap .= $spam . "\t\tdevnull\n";

		# send +badh w/o the +badh
		$valiasmap .= $badh . "\t\t" . $email . "\n";

	}

	# handle catchalls. we concat semicolons here because the relay_host might have a : character in it
	foreach $dom (@{$self->get_domains}) {

		next unless "on" eq $dom->{mail};

		my $name = lc $dom->{name};
		my $catchall = lc $dom->{catchall};

		if ("send_to" eq $catchall or "true" eq $catchall) {
			$valiasmap .= '@' . $name . "\t\t" . $dom->{catchall_addr} . "\n";
			$vtransportmap .= $name . "\t\tvirtual:\n";
		}
		elsif ("bounce" eq $catchall) {
			$vtransportmap .= $name . "\t\terror:" . $dom->{bounce_message} . "\n";
		}
		elsif ("delete_it" eq $catchall) {
			$valiasmap .= '@' . $name . "\t\tdevnull\n";
			$vtransportmap .= $name . "\t\tvirtual:\n";
		}
		elsif ("alias_to" eq $catchall) {
			$valiasmap .= '@' . $name . "\t\t" . '@' . $dom->{alias_addr} . "\n";
			$vtransportmap .= $name . "\t\tvirtual:\n";
		}
		elsif ("relay" eq $catchall) {
			# relay transport addition by spectro - slightly modified by cormander
			$vtransportmap .= $name . "\t\trelay:";

			# only add the [ ] around the relay_host if we want to force an MX lookup
			$vtransportmap .= $dom->{relay_host} . "\n";
		}

		# yaa-autoreply subdomains map to yaa
		$vtransportmap .= 'yaa-autoreply.' . $name . "\t\tyaa\n";

	}

	#
	# build the virtual domains table
	#

	my $data = "
<?php

  // Do not edit this file manually, it'll get overwritten by RavenCore the next time
  // an email account is updated.

  // Global Variables, don't touch these unless you want to break the plugin
  //
  global \$notPartOfDomainName, \$numberOfDotSections, \$useSessionBased,
		 \$putHostNameOnFrontOfUsername, \$checkByExcludeList,
		 \$at, \$dot, \$dontUseHostName, \$perUserSettingsFile,
		 \$smHostIsDomainThatUserLoggedInWith, \$virtualDomains,
		 \$sendmailVirtualUserTable, \$virtualDomainDataDir,
		 \$allVirtualDomainsAreUnderOneHost, \$vlogin_debug, \$removeFromFront,
		 \$chopOffDotSectionsFromRight, \$chopOffDotSectionsFromLeft,
		 \$translateHostnameTable, \$pathToQmail, \$atConversion,
		 \$removeDomainIfGiven, \$alwaysAddHostName, \$reverseDotSectionOrder,
		 \$replacements, \$usernameReplacements, \$forceLowercase,
		 \$securePort, \$useDomainFromVirtDomainsArray,
		 \$usernameDomainIsHost, \$stripDomainFromUserSubstitution,
		 \$serviceLevelBackend, \$internalServiceLevelFile,
		 \$vlogin_dsn, \$sqlServiceLevelQuery;

  \$virtualDomains = array(

";

	#

	# domains that have a catchall of relay are handeled in the vtransportmaps. Any email for the domain to 
	# go locally will be directed straight to virtual: , while the rest will go remote because it isn't in
	# the vmaildomains. If it was, it'll attempt to deliver those mails locally too, and give a reject message.

	foreach $dom (@{$self->get_domains}) {

		next unless "on" eq $dom->{mail};

		my $name = lc $dom->{name};
		my $catchall = lc $dom->{catchall};

		if ("relay" ne $catchall) {
			$vmaildomains .= $name . "\t\tplaceholder\n";
		}
		# build the list of domains this server is allowed to relay for
		else {
			$relay_domains .= $name . "\t\tplaceholder\n";
		}

		$data .= "\t\t'" . $name . "' => array('org_name' => '" . $name . "'),\n";

	}

	#
	$data .= "

  );

  \$vlogin_debug = 0;

?>

";

	#
	my $sq_vlogin_file = $self->{RC_ROOT} . "/var/apps/squirrelmail/plugins/vlogin/data/domains/ravencore.vlogin.config.php";

	file_write($sq_vlogin_file, $data);

	file_chown("rcadmin:servgrp", $self->{RC_ROOT} . "/var/apps/squirrelmail/data");
	file_chmod_r(660, $self->{RC_ROOT} . "/var/apps/squirrelmail/data");
	chmod 0771, $self->{RC_ROOT} . "/var/apps/squirrelmail/data";

	#
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/vmaildomains", $vmaildomains);
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/vmailbox", $vmailbox);
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/vtransportmap", $vtransportmap);
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/login_maps", $login_maps);
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/valiasmap", $valiasmap);
	file_write($self->{CONF}{VMAIL_CONF_DIR} . "/relay_domains", $relay_domains);
	file_write("/etc/dovecot-passwd", $dovecot_passwd);

	file_chown("dovecot:dovecot", "/etc/dovecot-passwd");
	chmod 0400, "/etc/dovecot-passwd";

	#
	my $cmd = "postmap " . $self->{CONF}{VMAIL_CONF_DIR} . "/{vmaildomains,vmailbox,vtransportmap,login_maps,valiasmap,relay_domains}";
	$output .= `$cmd 2>&1`;

	print $output;

}

#

sub rehash_ftp {
	my ($self, $input) = @_;

	return if $self->{DEMO};

	# check to make sure that this server is a webserver
	my @modules = $self->module_list_enabled;

	return unless in_array('web', @modules);

	return unless $self->{db_connected};

	my $shadow = new RavenCore::Shadow($self->{ostype});

	foreach my $suser (@{$self->get_sys_users}) {

		my $shell = ($suser->{shell} ? $suser->{shell} : $self->{CONF}{DEFAULT_LOGIN_SHELL});
		my $home_dir = ($suser->{home_dir} ? $suser->{home_dir} : $self->{CONF}{VHOST_ROOT}. '/' . $suser->{name});
		my $login = $suser->{login};
		my $passwd = $suser->{passwd};

		next if "root" eq $login;

		# ask if the user exists
		if ($shadow->item_exists('user', $login)) {
			$shadow->edit_user($login,$passwd,$home_dir,$shell,'',$shadow->{group}{'servgrp'}{'gid'});
		} else {
			$shadow->add_user($login,$passwd,$home_dir,$shell,'',$shadow->{group}{'servgrp'}{'gid'});
		}

	}

	$shadow->commit();

}

#

sub mail_del {
	my ($self, $input) = @_;

	my ($mail_name, $domain) = split /@/, $input->{email_addr};

	# remove the user from the sasl database
	system("saslpasswd2 -d -p -u " . $domain . " " . $mail_name);
}

#

sub service {
	my ($self, $input) = @_;

	if ($self->{DEMO}) {
		$self->do_error("Can't stop or start services in the demo.");
		return;
	}

	my $service = $input->{service};
	my $cmd = $input->{cmd};

	my $ret;

	# TODO: check $service and $cmd for hacking attempts, as they are passed directly into the system() function

	# TODO: check to see if this service is in our allowed services to mess with
	# $_cat $RC_ROOT/etc/services.* | $_grep $1 &> /dev/null
	#	if [ $? -ne 0 ]; then
	# not in list, exit with error
	#	$_echo "Service $1 not in list of services"
	#	exit 1
	# fi

	# check to see the init script for this service exists
	if (-x $self->{INITD} . '/' . $service) {
		# call the system service
		if ($self->{term}) {
			$ret = system($self->{INITD} . '/' . $service . ' ' . $cmd);
		} else {
			$ret = system($self->{INITD} . '/' . $service . ' ' . $cmd . ' &> /dev/null');
		}

		# check to see if we suceeded. If we try to stop a stopped service, we're going to fail, so we explicity ignore
		# failures on the "stop" command
		if ($cmd ne "stop" and $ret != 0) {
			$self->do_error("Service " . $service . " failed to " . $cmd);
		}

	} else {
		$self->do_error("Unable to " . $cmd . " " . $service);
	}

}

#

sub rehash_logrotate {
	my ($self, $input) = @_;

	return if $self->{DEMO};

	my @modules = $self->module_list_enabled;

	return unless in_array('web', @modules);

	#
	my $dir = $self->{CONF}{VHOST_ROOT};
	my $stuff = `ls -1 $dir/*/var/log/*_log 2> /dev/null`;
	my @logs = split /\n/, $stuff;

	# last line is always blank
	pop @logs;

	my $data;

	#
	foreach my $log (@logs) {

		my $domain_name = $log;
		my $logb = basename($log);
		$domain_name =~ s|/var/log/$logb||;

		$data .= "
$log {
	create 644 root root
	daily
	nocompress
	rotate 1
	nomail
	postrotate
	$self->{RC_ROOT}/sbin/process_logs $logb $domain_name
	endscript
}
";

	}
 
	# write the file
	file_write($self->{RC_ROOT} . '/etc/logrotate.conf', $data);

}

#

sub rehash_named {
	my ($self, $input) = @_;

	return if $self->{DEMO};

	my @modules = $self->module_list_enabled;

	return unless in_array('dns', @modules);

	return unless $self->{db_connected};

	# Some checks
	return unless $self->{CONF}{NAMED_ROOT};
	return unless $self->{CONF}{NAMED_CONF_FILE};

	# if the directory doesn't exist, exit
	return $self->do_error("The directory " . $self->{CONF}{NAMED_ROOT} . " does not exist") unless -d $self->{CONF}{NAMED_ROOT};

	# search for the checkzone command
	my $checkzone = find_in_path('named-checkzone');
	$checkzone = find_in_path('checkzone') unless $checkzone;

	return $self->do_error("Unable to find the bind server checkzone binary") unless $checkzone;

	my @domain_list;
	my $dom;

	foreach $dom (@{$self->get_domains}) {
		next unless $dom->{soa};
		push @domain_list, $dom->{name};
	}

	# TODO: foreach domain, do basic checks to make sure it doesn't contain any funny characters
	my $num;
	my $data;

	# read in our num file, if it exists
	$num = file_get_contents($self->{CONF}{NAMED_ROOT} . '/num') if -f $self->{CONF}{NAMED_ROOT} . '/num';

	# If our num file doesn't exist, give it a value of 10
	$num = 10 unless $num;

	# just incase there is a return character, get rid of it
	chomp $num;

	# Increment the value
	$num++;

	# If the value is greater then 99, set it back to 10
	$num = 10 if $num > 99;

	# Store our new value in the file we got it from
	file_write($self->{CONF}{NAMED_ROOT} . '/num', $num);

	# Loop through our domain list

	foreach $dom (@domain_list) {

		my $dname = $dom->{name};

		# TODO: make this a perl command
		my $date_str = `date +%Y%m%d`;
		chomp $date_str;

		$date_str .= $num;

		$data = qq~\$TTL	300

\@	IN	SOA	$dom->{soa} admin (
		$date_str	; Serial
		10800	; Refresh
		3600	; Retry
		604800	; Expire
		86400 )	; Minimum

~;

		# Loop through the records for this domain
		foreach my $rec (@{$self->get_dns_recs_by_domain_id({did => $dom->{id}})}) {
			# This may be an MX record. We seperate the MX token and the preference with a - symbol, so replace this with a space
			$rec->{type} =~ s/-/ /;
			$data .= $rec->{name} . "\t\tIN " . $rec->{type} . "\t" . $rec->{target} . "\n";
		}

		# Check to make sure this domain has enough DNS entries to be safely put into the configuration
		my $tmp_file = $self->{RC_ROOT} . '/var/tmp/' . $dname . '.' . $$;

		# write to and check the tmp file
		file_write($tmp_file, $data);
		my $ret = system($checkzone . " -q " . $dname . " " . $tmp_file);
		file_delete($tmp_file);

		# if all goes well, load the file
		if ($ret == 0) {
			$self->debug("Loading zone " . $dname);
			file_write($self->{CONF}{NAMED_ROOT} . '/' . $dname, $data);

		} else {
			# bad zone file, don't write it
			$self->debug("Bad zone file for " . $dname);
		}

	}

	$self->debug("Rebuilding named.conf file");

	# Static stuff that will always be in the named.conf file
	# TODO: read a template file rather then in-line static code
	$data = qq~

    options {
        directory "$self->{CONF}{NAMED_ROOT}";
        allow-transfer {
	    127.0.0.1;
        };
        auth-nxdomain no;
        forward only;

        forwarders {
	    127.0.0.1;
        };

    };

    zone "." {
        type master;
        file "default";
    };

~;

	# Loop through the domains on the server
	# TODO: this needs to be a select statement, as @domain_list may not be all domains once the $query is parsed
	foreach my $domain (@domain_list) {

		$data .= qq~
    zone "$domain" {
        type master;
        file "$domain";
    };

~;

	}

	# write the named.conf file
	file_write($self->{CONF}{NAMED_CONF_FILE}, $data) if $self->{CONF}{NAMED_CONF_FILE};

	# Restart named
	$self->debug("Restarting named");

	# find the right bind init script
	my $restarted = 0;

	# TODO: use a predefined array which is made on startup, along with other TODO arrays for service names and such
	# TODO: make the service function use the static name for the service, and use the above mentioned array for lookup
	# of the name of it on the current system
	foreach my $init (('bind','bind9','named')) {

		if (-f $self->{INITD} . '/' . $init) {
			# tell us we restarted
			$restarted = 1;
			# restart named
			$self->service({ 'service' => $init, cmd => 'restart' });
			# end the loop
			last;
		}
	}

	# if we didn't restart, submit an error
	$self->do_error("Unable to find named init script") unless $restarted == 1;

}

#

sub system {
	my ($self, $input) = @_;

	if ($self->{DEMO}){
		$self->do_error("You can't reboot or shutdown the demo server!");
		return;
	}

	my $cmd = $input->{cmd};

	my $s;

	# only allow "reboot" and "shutdown" calls
	$s = '-r' if $cmd eq "reboot";
	$s = '-h' if $cmd eq "shutdown";

	# if $s is not defined yet, error out
	if ( ! $s ) {
		$self->do_error("Invalid system call");
		return;
	}

	system ("shutdown " . $s . " now");
}

# a wrapper for chkconfig

sub chkconfig {
	my ($self, $input) = @_;

	if($self->{DEMO}) {
		$self->do_error("You can't change services in the demo server!");
		return;
	}

	# TODO: figure out what other systems like debian use for this, and handle it accordingly
	# TODO: do checking on stuff in @args, it's passed into the system() function

	$self->do_error("Unable to execute chkconfig command") unless find_in_path('chkconfig');

	system ("chkconfig --level " . $input->{level} . " " . $input->{service} . " " . $input->{status});

}

#

sub list_system_daemons {
	my ($self) = @_;

	my @daemons;

	$self->do_error("Unable to execute chkconfig command") unless find_in_path('chkconfig');

	# TODO: figure out what the debian equiv is and call that when {dist} is set to debian
	open PROG, "chkconfig --list | grep '^[[:alpha:]]'|";

	while (<PROG>) {
		chomp;

		# split on whitespace (spaces, tabs, etc)
		my @arg = split;

		my $daemon = shift @arg;

		push @daemons, $daemon;

		@{$self->{daemons}{$daemon}} = @arg;

	}

	close PROG;

	return \@daemons;
}

#

sub disp_chkconfig {
	my ($self, $input) = @_;

	my $data;

	my $runlevel = $input->{runlevel};

	# if no runlevel is given
	if ( ! $runlevel ) {
		# get our default runlevel
		# TODO: verify that this works
		$runlevel=`grep ':initdefault:' /etc/inittab | sed 's/:/ /g' | awk '{print \$2}'`;
		# chomp $runlevel;
	}

	$data  = "<h2>Services set to start on boot ( runlevel $runlevel ";

	# TODO: allow the change of the runlevel
	#<select name=runlevel>"
	#for i in 1 2 3 4 5; do
	#	echo -n "<option value=$i"
	#	[ $runlevel -eq $i ] && echo -n " selected"
	#	echo ">$i</option>"
	#done
	#echo "</select>

	$data .= " )</h2>\n";

	$data .= "<table cellpadding=0 cellspacing=0 border=1><tr>\n";

	my $daemons = $self->list_system_daemons;

	#
	foreach my $daemon (@{$daemons}) {

		$data .= "<tr><td width=200>" . $daemon . "</td>";

		# is this thing going to be on or off?
		my $status = $self->{daemons}{$daemon}[$runlevel];
		$status =~ s/.*://;

		my $new_status;
		my $running;
		my $start;
		my $stop;

		#
		if ($status eq "on") {
			$new_status = "off";
			$running = '<img src="images/solidgr.gif" border=0>';
			$start = '<img src="images/start_grey.gif" border=0>';
			$stop = '<a href="chkconfig.php?service=' . $daemon . '&status=' . $new_status . '"><img src="images/stop.gif" border=0></a>';
		} else {
			$new_status = "on";
			$running = '<img src="images/solidrd.gif" border=0>';
			$start = '<a href="chkconfig.php?service=' . $daemon . '&status=' . $new_status . '"><img src="images/start.gif" border=0></a>';
			$stop = '<img src="images/stop_grey.gif" border=0>';
		}

		#
		$data .= '<td>' . $running . '</td><td>' . $start . '</td><td>' . $stop . '</td></tr>' . "\n";

	}

	$data .= "</table>";

	return $data;

}

#

sub ravencore_info {
	my ($self) = @_;

	my $release;

	chomp(my $rpm_output = `rpm -q ravencore`);

	if ($rpm_output =~ /not installed/) {
		# just assume this isn't a snapshot, since we have no way of knowing
		$release = 1;
	} else {
		($release = $rpm_output) =~ s/^ravencore\-\d+\.\d+\.\d+\-//;
		if ($release ne "1") {
			$release =~ s/^0\.//;
			$release = "Snapshot built on " . scalar(localtime($release));
		}
	}

	my $info = {
		version => $self->{version},
		release => $release,
	};

	return $info;
}

#

sub mrtg {
	my ($self, $input) = @_;

	my $image = $input->{image};

	my $data;

	if ($input->{html}) {

		my @files = dir_list($self->{RC_ROOT} . '/var/log/mrtg');

		foreach my $file (@files) {
			next unless $file =~ /\.html/;
			$data .= file_get_contents($self->{RC_ROOT} . '/var/log/mrtg/' . $file);
			$data =~ s|SRC="([a-zA-Z0-9_\-\.]*)"|SRC="?img=$1"|ig;
		}

	} else {
		$data = file_get_contents($self->{RC_ROOT} . '/var/log/mrtg/' . basename($image));
	}

	return $data;
}

#

sub ftp_del {
	my ($self, $input) = @_;

	my $username = $input->{login};

	# TODO: use RavenCore::Shadow here instead
	# check to make sure this is a legitimate ID being deleted
	if ($username =~ /^[a-zA-Z0-9-_\.]+$/) {
		my $ret = `/usr/bin/id -u $username`;
		if ($ret ge 500) {
			system ("/usr/sbin/userdel " . $username);
			return 1;
		} else {
			system ("echo ftp_del error: id was less than 500: " . $username . " -UID- " . $ret);
			return 0;
		}
	} else {
		return 0;
	}
}

#

sub domain_del
{
	my ($self, $domain) = @_;

	# remove the domain from the apache directory
	if (length($domain) gt 0) {
		system ("/bin/rm -Rf --directory " . $self->{CONF}{VHOST_ROOT} . "/" . $domain);
		$self->rehash_httpd;
	} else {
		return 0;
	}
}

#

sub ip_update {
	my ($self, $input) = @_;

	my $ip = $input->{ip};
	my $uid = $input->{uid};
	my $did = $input->{did};

	return $self->do_error("Not an IP address: $ip") unless is_ip($ip);

	if ($uid eq "NULL") {
		$self->{dbi}->do("update ip_addresses set uid = NULL, default_did = ? where ip_address = ?", undef, $did, $ip);
	} else {
		$self->{dbi}->do("update ip_addresses set uid = ?, default_did = ? where ip_address = ?", undef, $uid, $did, $ip);
	}

	$self->rehash_httpd;

	$self->do_error("IP address updated.");

	return 0;
}

sub ip_delete {
	my ($self, $input) = @_;

	my $ip = $input->{ip};

	return $self->do_error("Not an IP address: $ip") unless is_ip($ip);

	$self->{dbi}->do("delete from ip_addresses where ip_address = ?", undef, $ip);
	$self->{dbi}->do("delete from domain_ips where ip_address = ?", undef, $ip);

	$self->rehash_httpd;

	$self->do_error("IP address deleted.");

	return 0;
}

sub ip_list {
	my ($self) = @_;

	return "This page doesn't work without a database connection." unless $self->{db_connected};

	my $ips = {};
	my $db_ips = {};

	foreach my $ip (@{$self->get_ip_addresses}) {
		$db_ips->{$ip->{ip_address}} = $ip;
	}

	open IF, "ifconfig |";
	while (<IF>) {
		chomp;

		my $ip = $_;
		$ip =~ s/.*addr:((\d{1,3}\.){3}\d{1,3}).*/$1/;

		next unless is_ip($ip);
		next if $ip =~ /^127./;

		# not in the database?
		if ( ! $db_ips->{$ip} ) {
			$self->xsql("insert into ip_addresses (ip_address, active) values (?,?)", [$ip, "true"]);
			$db_ips->{$ip} = { active => "true" };
		} elsif ( $db_ips->{$ip}{active} ne "true" ) {
			$self->xsql("update ip_addresses set active = ? where ip_address = ?", ["true", $ip]);
			$db_ips->{$ip}{active} = "true";
		}

		$ips->{$ip} = $db_ips->{$ip};
	}
	close IF;

	# look for IPs in db that aren't in $ips
	foreach my $ip (keys %{$db_ips}) {

		if ( ! defined($ips->{$ip}) ) {
			$self->xsql("update ip_addresses set active = ? where ip_address = ?", ["false", $ip]);
			$ips->{$ip} = $db_ips->{$ip};
			$ips->{$ip}{active} = "false";
		}
	}

	# convert undef to NULL string
	foreach my $ip (keys %{$ips}) {
		if ( ! defined($ips->{$ip}{uid}) ) {
			$ips->{$ip}{uid} = "NULL";
		}
	}

	return $ips;
}

# call ip_list and return a hash where each IP is the key and the value

sub ip_list_just_ips {
	my ($self) = @_;

	my $ret = {};

	my $ips = $self->ip_list;

	foreach my $ip (keys %{$ips}) {
		$ret->{$ip} = $ip;
	}

	return $ret;
}

# very handy for debugging

sub dump_vars {
	my ($self) = @_;
	return Dumper($self);
}

