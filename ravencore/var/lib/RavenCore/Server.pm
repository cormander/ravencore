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
# provides a method for access to most of ravencore's backend functions.
#

package RavenCore::Server;

# if these don't exist, you've got one funky installation
use RavenCore;
use RavenCore::Shadow;
use Serialize;

# these modules should come with perl, and it's OK to die here if they don't exist
use File::Basename;
use Data::Dumper;
use MIME::Base64;

# likewise; these are needed by Net::Server
use IO::Handle;
use IO::Select;
use IO::Socket;
use IO::Socket::UNIX;
use POSIX;
use Socket qw(SOCK_DGRAM);
use Sys::Syslog;

# global vars
our $ETX = chr(3);
our $EOT = chr(4);

=pod
*** NOTES ***
Here are my general notes for things to keep in mind when coding in this module:

* if you edit anything in this file, you must restart ravencore for it to take effect! This may be a little
bit inconvienent, but it's a big part of what makes ravencore a lot faster then it used to be

* a function is either internal or external:
  1) a function is external if it exists in any of the "cmd_privs" hashes, otherwise it is internal
  2) an external function shouldn't be called by an internal function, with a few exceptions
  3) internal functions are can't be called externally, for security reasons

* an external function should only return an intiger, double, string, or hash. Not an array, because it'll be
interpreted as a hash on the other end anyway, which makes building an array pretty useless

* if an external function returns a hash, return it as a reference, eg: return \%{$self->{CONF}}. Otherwise,
you'll get a funky string instead of the hash data when it passes through the serialize function

* anything that causes the rcserver process to stop running, other then a TERM signal, is considered a crash
and should be bugreported / fixed so that it will be prevented

* anything that causes a connected client to wait indefinatly for input from the server is considered a major
bug, and should be bugreported / fixed

* this is code is accessed by both the parent and child processes of the rcserver script. Keep in mind that a
change in a variable in a child process won't affect the parent process

* we want as much configuration as possible to take place on startup, and as little as possible to happen when
a client connects, to optimize runtime and code flow.

* it would be ideal to be to the point where the PHP does no SQL statements, instead it calls functions from
this code to get all the data it needs. That way we can get rid of the 'sql' external command, which will 
greatly reduce the risk of sql injection

=cut

# combind this object with Net::Server, and load other nessisary functions from various files

sub new
{
	my ($class) = @_;

	my $self = { DEMO => 0 };

	bless $self, $class;

	# TODO: ugly ugly ugly!!!
	my $RC_ETC = ( $ENV{'RC_ETC'} ? $ENV{'RC_ETC'} : '/etc/ravencore.conf' );

	my %rcetc = $self->parse_conf_file($RC_ETC);

	foreach my $key (keys %rcetc) {
		$self->{$key} = $rcetc{$key};
	}

	#
	# load required modules that we need from our own lib; they may not nessisarily exist with a standard perl install
	#

	unshift @INC, $self->{RC_ROOT} . '/var/lib';

	# import the Net::Server module included with RavenCore, and make RavenCore::Server inherit it
	require base;
	import base qw(Net::Server::Fork);

	#
	# load "optional" perl modules; most of them are required for RavenCore to be useful, but we can at least start the interface
	# without them
	#

	my @internal_perl_mod_specs = (
		'Net::HTTP',
		'Time::HiRes',
		'DBI',
		'DBD::mysql',
		'Locale::gettext',
	);

	foreach my $mod ( @internal_perl_mod_specs ) {

		eval "use " . $mod . ";";

		if (!$@) {
			$self->{perl_modules}{$mod} = 1;
		}

	}

	#
	# load more internal functions from seperate files
	#

	my @pms = dir_list($self->{RC_ROOT} . '/var/lib/includes');

	foreach my $pm (@pms) {

		# only include this file if it ends in .pm
		# TODO: check file ownership/permissions, should be 0600 root:root
		next unless $pm =~ /\.pm$/;

		$self->debug("Including " . $self->{RC_ROOT} . '/var/lib/includes/' . $pm);
		do $self->{RC_ROOT} . '/var/lib/includes/' . $pm;

		# check for errors on the do statement, if there is a syntax error, it'll show up as a "Bad file
		# descriptor", but that's confusing and scary, so say something more optimistic
		$self->do_error(_("Warning: %s might not have completly loaded, it appears to have syntax errors", $pm)) if $!;

	}

	$self->die_error(_('Variable %s is undefined! Please check the file: %s', 'RC_ROOT', $RC_ETC)) unless $self->{RC_ROOT};
	$self->die_error(_('The root directory %s does not exist!', $self->{RC_ROOT})) unless -d $self->{RC_ROOT};

	$self->{class} = $_[0];

	@{$self->{errors}} = ();

	return $self;
}


# function to tell the client some basic information on what they can do

sub help {
	my ($self, $query) = @_;

	my $data;

	if ($query eq "") {
		$data = "RavenCore server help. You have permission to run the following commands:\n\n";

		foreach my $cmd ( @{$self->{cmd_privs}} ) {
			$data .= $cmd . "\n";
		}

		$data .= "\nFor more information about a command, run: help <command>\n";
	} else {

		$data = file_get_contents($self->{RC_ROOT} . '/docs/commands/' . basename($query));

		if ($data eq "") {
			$data = "Sorry, there is currently no help for that command.";
		}

	}

	return $data;
}

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

# ... you guessed it, connect to the database!!!

sub database_connect {
	my ($self) = @_;

	$self->{db_connected} = 0;

	if ( ! $self->{perl_modules}{DBI} ) {
		$self->debug("DBI not loaded");
		return;
	}

	if ( ! $self->{perl_modules}{DBD::mysql} ) {
		$self->debug("DBD::mysql not loaded");
		return;
	}

	# test if we have the dbi object, and if so, ping it to see if it's still alive
	if ($self->{dbi}) {
		$self->debug("Pinging inherited database connection");

		# see if our connection is still active
		my $ret;

		# $self->{dbi} may exist, but it might not be a blessed reference, so eval the call to ping
		eval {
			local $SIG{DIE} = '';
			$ret = $self->{dbi}->ping;
		};

		# if so, return
		if ($ret) {
			$self->{db_connected} = 1;

			if($self->get_passwd =~ m/^ravencore$/i) { $self->{initial_passwd} = 1 }
			else { $self->{initial_passwd} = 0 }

			return;
		}

		# if we get here, we have lost database connection...
		$self->{db_connected} = 0;

	}

	# read in our database password. currently we have to do this each time we connect, because the password
	# might have changed since we first started, and a child process can't change a variable and have the
	# parent process know about it

	my $passwd = $self->get_passwd;

	# connect to the database

	$self->{dbi} = DBI->connect(
		'DBI:mysql:database='.$self->{MYSQL_ADMIN_DB}.
		';host='.$self->{MYSQL_ADMIN_HOST}.
		';port='.$self->{MYSQL_ADMIN_PORT},
		$self->{MYSQL_ADMIN_USER}, $passwd, {RaiseError => 0,PrintError => 0}
	);

	# set internal variable of whether or not database is actually connected
	$self->{db_connected} = 1 if $self->{dbi}{Active};

	$self->debug("Connected to database.") if $self->{db_connected};

	$self->debug("Unable to get a database connection: " . DBI->errstr) unless $self->{db_connected};

	# cache our "initial_passwd" response, telling us if we need to set our admin password or not
	if ($passwd =~ m/^ravencore$/i) { $self->{initial_passwd} = 1 }
	else { $self->{initial_passwd} = 0 }

	$self->get_db_conf;

}

# reload rcserver

sub reload {
	my ($self, $msg) = @_;

	# tell our parent process to reload (if we are not the parent process)
	if ($self->{server}{ppid} ne $$) {
		$self->log('2',$msg) if $msg; # always good to know why

		kill "HUP", $self->{server}{ppid};
	}

	return;
}

# reload ravencore.httpd

sub reload_webserver {
	my ($self) = @_;

	$self->debug("reloading control panel webserver");

	# argument makes it graceful
	$self->start_webserver(1);

	return
}

# a function use to read the password out of the .shadow file

sub get_passwd {
	my ($self) = @_;

	# since the .shadow file is read a lot and almost never written to, skip locking. it isn't going to be
	# devistating if we're reading it while it's written to once in a very blue moon
	my $passwd = file_get_contents($self->{RC_ROOT} . "/.shadow", 1);
	chomp $passwd;

	return $passwd;
}

#
# Configuration reading/writting functions (the functions for writting are TODO)
#

sub parse_conf_file {
	my ($self, $file) = @_;

	my %data;
	my @conf_array = file_get_array($file, 1);

	foreach (@conf_array) {
		if(/^[A-Z0-9_]*=/) {
			my $key = my $val = $_;

			$key =~ s/^([A-Z0-9_]*)=.*/\1/;
			$val =~ s/^([A-Z0-9_]*)=//;

			# remove starting and ending quotations
			$val =~ s/^("|')//;
			$val =~ s/('|")$//;

			$data{$key} = $val;

		}

	}

	return %data;
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
	my ($self, $query) = @_;

	my ($key, $val) = split / /, $query;

	# $key should always be uppercase
	$key = uc($key);

	# simple error checking
	return $self->do_error("set_conf_var requires 2 arguments, a key and a value") if $key eq "";
	return $self->do_error("set_conf_var requires 2 arguments, a key and a value") if $val eq "";

	# check to see if it's already in the database
	my $sql = "select count(*) from settings where setting = " . $self->{dbi}->quote($key) . 
	" and value = " . $self->{dbi}->quote($val);
	my $result = $self->{dbi}->prepare($sql);

	$result->execute();
	my @count = $result->fetchrow_array;
	$result->finish();

	if ($count[0] > 0) {
		# if it's there, update it
		$sql = "update settings set value = " . $self->{dbi}->quote($val) .
			" where setting = " . $self->{dbi}->quote($key);
	} else {
		# if it's not there, insert it
		$sql = "insert into settings set setting = " . $self->{dbi}->quote($key) . 
			", value = " . $self->{dbi}->quote($val);
	}

	# execute the query
	$self->{dbi}->do($sql);

	# store it in this sessions's CONF
	$self->{CONF}{$key} = $val;

	return;
}

# read in our settings from the database

sub get_db_conf {
	my ($self) = @_;

	# assume our configuration is complete, because if it actually isn't, we'll set this to zero
	# if no database connection, we're considered to have a "config_complete" because we won't
	# be able to find out, which is what we want.
	$self->{config_complete} = 1;

	# check to see if the GPL has been accepted
	$self->{gpl_check} = 0;

	if ( -f $self->{RC_ROOT} . "/var/run/gpl_check" ) {
		$self->{gpl_check} = 1;
	} else {
		$self->debug("GPL agree file not found: " . $self->{RC_ROOT} . "/var/run/gpl_check");
	}

	# are we a complete install?
	$self->{install_complete} = 0;
	$self->{install_complete} = 1 if -f $self->{RC_ROOT} . '/var/run/install_complete'; 

	# no database connection - don't read the settings
	return unless $self->{db_connected};

	# remove the CONF hash so we don't inherit any previous variables
	delete $self->{CONF};

	$self->debug("Reading database settings, checking configuration");

	# do an sql query to get all the configuration variables
	my $result = $self->{dbi}->prepare("select * from settings");

	$result->execute;

	while (my $row = $result->fetchrow_hashref ) {
		#
		my $key = $row->{'setting'};

		# build our CONF hash
		$self->{CONF}{$key} = $row->{'value'};
	}

	$result->finish;

	# check to make sure we have a value for each configuration variable of each enabled module
	delete $self->{UNINIT_CONF};

	# walk down the list of our enabled modules
	my %modules = $self->module_list_enabled;

	foreach my $dep_file (values %modules) {

		# parse the conf file to get all the variable names needed for this module
		my %data = $self->parse_conf_file($dep_file);

		foreach my $key (keys %data) {

			# if we are missing this variable
			if ( ! exists $self->{CONF}{$key} ) {
				# our configuration is not complete
				$self->{config_complete} = 0;
				# cache this in a list of uninitilized keys, with their default values
				$self->{UNINIT_CONF}{$key} = $data{$key};
				# TODO: this should only show up on a higher debugging level
				$self->debug("Missing CONF var: " . $key);
			}

		}

	}

	$self->debug("Configuration is not complete") if ! $self->{config_complete};

}

#

sub get_domain_name {
	my ($self, $did) = @_;

	return unless $self->{db_connected};

	my $sql = "select name from domains where id = " . $self->{dbi}->quote($did);
	my $result = $self->{dbi}->prepare($sql);

	$result->execute();
	my @row = $result->fetchrow_array;
	$result->finish();

	return $row[0];
}

#

sub webstats {
	my ($self, $query) = @_;

	my ($did, $str) = split / /, $query, 2;

	my $domain_name = $self->get_domain_name($did);

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
# use for checks on said module (eg: base.conf vs. base.conf.debian )

sub module_list {
	my ($self) = @_;

	my %modules, $mod;

	my @confs = dir_list($self->{RC_ROOT} . '/conf.d/*.conf');

	foreach my $conf (@confs)
	{
		# since we did a wildcard look, we get the full path of the filename.... get just the basename
		$conf = basename($conf);

		# strip the .conf off of the name for the module name
		$mod = $conf;
		$mod =~ s/\.conf//;

		# if a ".dist" file exists for this conf file, use it instead ( like .debian or .gentoo )
		if ($self->{dist} && -f $self->{RC_ROOT} . '/conf.d/' . $conf . '.' . $self->{dist}) {
			$conf .= '.' . $self->{dist};
		}

		# value is the full path to the referenced file
		$modules{$mod} = $self->{RC_ROOT} . '/conf.d/' . $conf;

	}

	return %modules;
}

# just like module_list, but only returns modules that are enabled

sub module_list_enabled {
	my ($self) = @_;

	my %enabled;

	my %modules = $self->module_list;

	foreach my $mod (keys %modules) {

		# if there is a .ignore for this module, skip it
		next if -f $self->{RC_ROOT} . '/conf.d/' . $mod . '.conf.ignore';

		# if the executable bit is set on the file, it's an enabled module
		if (-x $modules{$mod}) {
			$enabled{$mod} = $modules{$mod};
		}

	}

	return %enabled;
}

# function that is called if things have gone horribly wrong. Print all the appropriate
# closing bytes to the client ( NAK for error, CAN for fatal error, and EOT so the client
# knows we're done reporting ), and exit out with an error.

sub fatal_error {
	my ($self, $errstr) = @_;

	if ($self->{CLIENT}) {
		print {$self->{CLIENT}} $self->{NAK} . $errstr . $self->{ETB} . $self->{CAN} . $self->{EOT};
		close $self->{CLIENT};
	} else {
		print STDERR $errstr . "\n";
	}

	# if $noexit is set, then don't exit. otherwise, default behavior is to exit
	# TODO: FIX ME

	POSIX::_exit(1) unless $self->{noexit} == 1;

}

#

sub gpl_agree {
	my ($self) = @_;

	$self->{gpl_check} = 1;
	file_touch($self->{RC_ROOT} . '/var/run/gpl_check');

	$self->reload("GPL License has been agreed to");

	sleep 2;
}

# this is called after an install/upgrade. rehash everything

sub complete_install {
	my ($self) = @_;

	$self->debug("Running post-installation process");

	$self->rehash_httpd("--all");

	$self->rehash_ftp("--all");

	$self->rehash_mail("--all");

	$self->rehash_named("--all --rebuild-conf");

	file_touch($self->{RC_ROOT} . '/var/run/install_complete');

	$self->{install_complete} = 1;

	$self->checkconf;

	$self->reload("Installation complete");

	sleep 2;
}

# is this an admin user?

sub is_admin {
	my ($self, $username) = @_;

	# if we're not given a username, assume the session as the user to check
	$username = $self->{session}{user} unless $username;

	return 1 if $username eq $self->{MYSQL_ADMIN_USER};
	return 0;
}

# is the given service running?

sub service_running {
	my ($self, $service) = @_;

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

# change the admin password

sub passwd {
	my ($self, $query) = @_;

	if ($self->{DEMO}) {
		$self->do_error("Can't change the password in the demo!");
		return;
	}

	# parse out the info from the $query
	my ($old, $new) = split / /,$query;

	my $error = 0;

	if (length($new) < 5) {
		$self->do_error("Your password must be at least 5 characters long.");
		$error = 1;
	}

	if ($new !~ /\d/) {
		$self->do_error("Your password must contain at least one digit.");
		$error = 1;
	}

	if ($new !~ /[a-zA-Z]/) {
		$self->do_error("Your password must contain at least one alphabetical character.");
		$error = 1;
	}

	if ( ! $self->verify_passwd($old) ) {
	$self->do_error("Old password incorrect.");
	$error = 1;
	}

	# TODO: do a dictionary lookup on groups of letters, translating hackerscript as well (1 = i or l, 4 = a, etc)

	if ($error == 0) {

		if ($self->{db_connected}) {
			$self->{dbi}->do("SET PASSWORD FOR '" . $self->{MYSQL_ADMIN_USER} . "'\@'" . $self->{MYSQL_ADMIN_HOST} . "' = PASSWORD('" . $new . "')");

			if ($self->{dbi}->errstr) {
				$error = 1;
				$self->do_error("Unable to execute \"SET PASSWORD\" database query: " . $self->{dbi}->errstr);
			}

		}

		if ($error == 0) {
			# the password change was successful. commit the password to the .shadow file and return true
			my $shadow_file = $self->{RC_ROOT} . "/.shadow";

			chmod 0600, $shadow_file;
			file_write($shadow_file, $new . "\n");
			chmod 0400, $shadow_file;

			$self->debug("Password change successful.");

			$self->reload("Password change");

			# TODO:
			# if( ! $self->{db_connected} ) {
			#	   ... check to see if this new password actually connects us to the database
			#	   ... if not, issue: do_error("Warning: unable to sync new password to mysql server");
			# }

			return 1;
		}

	} else {
		# failed
		$self->debug("Password change NOT successful.");
		return;
	}

}

#

sub verify_passwd {
	my ($self, $passwd) = @_;

	return 1 if $passwd eq $self->get_passwd;
	return 0;
}

# do a database SQL statement

sub sql {
	my ($self, $query) = @_;
	my $data = {
		rows_affected => undef,
		insert_id => undef,
		rows => [],
	};

	return unless $self->{db_connected};

	# do the sql query, but the kind of query depends on what we do.
	# if it's NOT a "select" or a "show" query (do a case in-sensative match) then we simply "do"
	# the query and get the $rows_affected and $insert_id data
	if ($query !~ m/^select/i and $query !~ m/^show/i) {

		# make sure the connection didn't go away on us
		$self->database_connect;

		# submit the query to the database
		$data->{rows_affected} = $self->{dbi}->do($query);
		$self->debug("Did SQL query: $query");
		$self->debug("Rows affected: ". $data->{rows_affected});

		if ( ! $self->{dbi}->errstr ) {
			$data->{insert_id} = $self->{dbi}->{ q{mysql_insertid} };
		} else {
			$self->do_error("DBI Error: " . $self->{dbi}->errstr);
		}

	} else {

		# on "select" queries, we're a little more involved... need to return the data we got :)
		my $result = $self->{dbi}->prepare($query);

		$result->execute;

		#
		if ( ! $self->{dbi}->errstr ) {
			# fetch the data into a hash. That way, we have both the column name and the value
			while (my $hash_ref = $result->fetchrow_hashref) {

				push @{$data->{rows}}, $hash_ref;

			}

			#
			$result->finish();
		}

	}

	return $data;

}

#

sub version_outdated {
	my ($self) = @_;

	# return 0 if unable to check
	unless( $self->{perl_modules}{Net::HTTP} ) {
		$self->debug("Unable to check ravencore version: Net::HTTP perl module not found");
		return 0;
	}

	# our running version
	my $version = file_get_contents($self->{RC_ROOT} . '/etc/version');
	chomp($version);

	# the last digit is the release number
	my $release = $version;
	$release =~ s/.*\.(\d*)/$1/;

	# sub the last digit for an x, this is the variable used to find the correct remote version file
	my $v = $version;
	$v =~ s/\.\d*$/.x/;

	my $s;

	# eval this because we want to timeout if the http connection takes too long
	eval {
		# trap the die and alarm signals, so nothing gets sent to the client if the next few lines fail
		local $SIG{__DIE__};
		local $SIG{ALRM} = sub { die "alarm\n" };

		# set a timeout of 5 seconds to the connecting to ravencore.com
		alarm(5);

		# open the http connection
		$s = Net::HTTP->new(Host => "www.ravencore.com");
		alarm(0);
	};

	# if $@ exists, there was a problem, just return 0
	if ($@) {
		$self->debug("Unable to check ravencore version: $@");
		return 0;
	}

	# request the version file for this series
	$s->write_request(GET => "/updates/" . $v . '.txt', 'User-Agent' => "RavenCore/".$version);
	$self->debug("http request sent to www.ravencore.com: GET /updates/$v.txt, User-Agent RavenCore/$version");

	# read the response headers
	my($code, $mess, %h) = $s->read_response_headers or return 0;

	my $data;
	my $buf;

	# get all http body data
	while ($s->read_entity_body($buf, 1024)) {
		$data .= $buf;
	}

	# remove all extra whitespace from $data
	$data =~ s/^\s+//;
	$data =~ s/\s+$//;

	# $data should now contain just a digit - the release number. check it against our current $release
	if ($data > $release) {
		$self->debug("RavenCore version is NOT up-to-date");
		return 1;
	}

	# return 0 when up to date
	$self->debug("RavenCore version is up-to-date, checked at www.ravencore.com");
	return 0;

}

#
# Rehash the httpd Configuration Files
#

sub rehash_httpd
{
	my ($self, $query) = @_;

	return if $self->{DEMO};

	my %modules = $self->module_list_enabled;

	return unless exists $modules{web};

	if( ! $self->{CONF}{VHOST_ROOT} )
	{
	$self->do_error("VHOST_ROOT not defined");
	return;
	}

	my $restart_httpd;

	# Always make sure that the vhost root exists and is readable
	mkdir_p($self->{CONF}{VHOST_ROOT});

	# chown / chmod the vhost root with correct permissions
	file_chown('root:servgrp',$self->{CONF}{VHOST_ROOT});
	chmod 0755, $self->{CONF}{VHOST_ROOT};

	# make sure ip addresses are loaded into the db
	$self->ip_list;

	my $sql;
	my $data = "";
	my $vhost_data = "";

	# set the vhosts file
	my $vhosts = $self->{RC_ROOT} . "/etc/vhosts.conf";

	# make sure that no other apache configuration denies access to the domains
	$data .= "<Directory " . $self->{CONF}{VHOST_ROOT} . ">\n";
	$data .= "Order allow,deny\n";
	$data .= "Allow from all\n";
	$data .= "</Directory>\n";

	# check to make sure we're included in the apache conf file
	my $output = file_get_contents($self->{httpd_config_file});
	my $include_vhosts = 'Include ' . $vhosts;

	# if not, append to it
	file_append($self->{httpd_config_file}, $include_vhosts . "\n") unless $output =~ m|^$include_vhosts$|m;

	#
	# Rebuild the vhosts.conf file
	#
	$domain_include_file = {};

	$self->debug("Begin IP address loop");

	# walk down all our IP addresses and build the domains on them
	$sql = "select * from ip_addresses order by ip_address";
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	while (my $row = $result->fetchrow_hashref) {
		my $ip_in_use = 0;

		# build the IP's default domain first
		my $dom = $self->{dbi}->selectrow_hashref("select d.*, u.login from domains d, sys_users u where d.suid = u.id and d.id = ? and hosting = 'on'", undef, $row->{default_did});

		$vhost_data .= $self->make_virtual_host($row->{ip_address}, $dom);

		if ($dom->{'name'}) {
			$ip_in_use = 1;
		}

		# build the rest of the domains on this IP
		$sql = "select d.*, u.login from domains d inner join domain_ips i on d.id = i.did, sys_users u where d.suid = u.id and i.ip_address = ? and d.id != ? and hosting = 'on'";
		my $result_dom = $self->{dbi}->prepare($sql);

		$result_dom->execute($row->{ip_address}, $row->{default_did});

		while ($dom = $result_dom->fetchrow_hashref ) {
			$domain_include_file->{$dom->{'name'}} .= $self->make_virtual_host($row->{ip_address}, $dom);
			$ip_in_use = 1;
		}

		$result_dom->finish;

		if ($ip_in_use == 1) {
			$data .= "NameVirtualHost " . $row->{ip_address} . ":80\n";
		}

	}

	$result->finish;

	$self->debug("End IP address loop");

	# look for domains that don't have an IP, and build them here
	$sql = "select d.*, u.login from domains d, sys_users u where d.suid = u.id and d.id not in (select did from domain_ips) and hosting = 'on'";
	$result = $self->{dbi}->prepare($sql);

	$result->execute;

	my $wildcard_virtualhost = 0;

	while( my $dom = $result->fetchrow_hashref ) {
		$wildcard_virtualhost = 1;
		$self->debug("Wildcard domain " . $row->{'name'});
		$domain_include_file->{$dom->{'name'}} .= $self->make_virtual_host('*', $dom, 0);
	}

	$result->finish;

	foreach my $domain_name (keys %{$domain_include_file}) {
		$vhost_data .= "Include " . $self->{CONF}{VHOST_ROOT} . "/" . $domain_name . "/conf/httpd.include\n";
		file_write($self->{CONF}{VHOST_ROOT} . "/" . $domain_name . "/conf/httpd.include", $domain_include_file->{$domain_name} );
	}

	if ($wildcard_virtualhost == 1) {
		$data .= "NameVirtualHost *:80\n";

		# if we have no ssl domains, don't echo the 443 virtualhost
		my $sql = "select count(*) as count from domains where host_ssl = 'true'";
		my $result = $self->{dbi}->prepare($sql);

		$result->execute;

		my $row = $result->fetchrow_hashref;

		if ($row->{'count'} != 0) { $data .= "NameVirtualHost *:443\n" }
	}

	$data .= "\n\n" . $vhost_data;

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

		$self->service('httpd ' . $cmd);
	} else {
		# otherwse, start apache
		$self->debug("Apache not running, starting it");

		$self->service('httpd start');
	}
   
}

# build all the directories, and return the resulting config file

sub make_virtual_host {
	my ($self, $ip, $row) = @_;

	return unless $row->{'name'};

	my $data = "";

	$self->debug("make_virtual_host for $ip " . $row->{'name'});

	# Make sure the proper directories exist and that they are set to the correct permissions

	my $domain_root = $self->{CONF}{VHOST_ROOT} . "/" . $row->{'name'};

	$self->debug("Building conf file for " . $row->{'name'});

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

		# chmod / chown the directories
		file_chown("root:servgrp", $domain_root);
		file_chown($row->{'login'} . ":servgrp", $domain_root . "/httpdocs", $domain_root . "/tmp");

		chmod 0750, $domain_root,
		$domain_root . "/httpdocs";

		chmod 0770, $domain_root . "/tmp";

		$data .= $self->make_virtual_host_content($ip, 0, $row);
		if ($row->{'host_ssl'} eq "true") {
			$data .= $self->make_virtual_host_content($ip, 1, $row);
		}

	} elsif ($row->{'host_type'} eq "redirect") {

		$data .= "<VirtualHost $ip:80>\n";
		$data .= "\tServerName\t" . $row->{'name'} . "\n";

		$data .= $self->server_alias($row->{'name'}, $row->{'www'});

		$data .= "\tRedirectPermanent / \"" . $row->{'redirect_url'} . "\"\n";
		$data .= "</VirtualHost>\n\n";
	}

	if ($row->{'webmail'} eq "yes") {
		my $squirrelmail = $self->{RC_ROOT} . "/var/apps/squirrelmail";
		my $save_path = $self->{RC_ROOT} . "/var/tmp";
		my $domain_name = $row->{'name'};

		$data .= qq~
<VirtualHost $ip:80>
		ServerName webmail.$domain_name
		DocumentRoot $squirrelmail
		<IfModule mod_ssl.c>
				SSLEngine off
		</IfModule>
		<Directory $squirrelmail>
		Options -Indexes
		<IfModule sapi_apache2.c>
				php_admin_flag engine on
				php_admin_value open_basedir "$squirrelmail"
				php_admin_value upload_tmp_dir "$save_path"
				php_admin_value session.save_path "$save_path"
		</IfModule>
		</Directory>
</VirtualHost>

~;

	}

	$self->debug("End building domain config file for " . $row->{'name'});

	return $data;

}

#

sub make_virtual_host_content {
	my ($self, $ip, $ssl, $row) = @_;

	$domain = $row->{'name'};
	$www = $row->{'www'};
	$host_dir = $row->{'host_dir'};
	$host_cgi = $row->{'host_cgi'};
	$host_php = $row->{'host_php'};

	my $data;
	my $port;

	# If this tag is ssl, the port is 443
	if ($ssl == 1) {
		$port = 443;
		$self->debug($domain . " is setup for ssl");
		$data = "<IfModule mod_ssl.c>\n";
	} else {
		$port = 80;
	}

	#
	$data .= "<VirtualHost " . $ip . ":" . $port . ">\n";
	$data .= "\tServerName   " . $domain . ":" . $port . "\n";

	# Does this domain have any aliases for it?
	$data .= $self->server_alias($domain, $www);

	# Define the directory's document root
	my $domain_root = $self->{CONF}{VHOST_ROOT} . "/" . $domain;

	$data .= "\tDocumentRoot " . $domain_root . "/httpdocs\n";

	# Make sure that the access and error log files exist
	my $domain_log_root = $domain_root . "/var/log";

	#
	my $ssl_log = ( $ssl == 1 ? "ssl_" : "" );

	file_touch($domain_log_root . "/" . $ssl_log . "access_log");
	$data .= "\tCustomLog  " . $domain_log_root . "/" . $ssl_log . "access_log combined\n";

	file_touch($domain_log_root . "/" . $ssl_log . "error_log");
	$data .= "\tErrorLog   " . $domain_log_root . "/" . $ssl_log . "error_log\n";

	#
	if ($host_cgi eq "true") {
		mkdir_p($domain_root . "/cgi-bin");
		chmod 0750, $domain_root . "/cgi-bin";

		# TODO: Fix me
		#	file_chown($row->{'login'} . ":servgrp", $domain_root . "/cgi-bin");

		$data .= "\tScriptAlias  /cgi-bin/ " . $domain_root . "/cgi-bin/\n";
	}

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

	if ($ssl == 1) {

		$data .= "\tSSLEngine on\n";
		$data .= "\tSSLVerifyClient none\n";

		$self->ssl_genkey_pair($domain_root . "/conf");

		$data .= "\tSSLCertificateFile " . $domain_root . "/conf/server.crt\n";
		$data .= "\tSSLCertificateKeyFile " . $domain_root . "/conf/server.key\n";

	} else {
		# no ssl, so make sure it's disabled
		$data .= "\t<IfModule mod_ssl.c>\n";
		$data .= "\t\tSSLEngine off\n";
		$data .= "\t</IfModule>\n";
	}

	# Begin the setup fir the virtual directory of this domain's web root
	$data .= "\t<Directory " . $domain_root . "/httpdocs>\n";

	# Add directory listing if set
	if ($host_dir eq "true") { $data .= "\tOptions +Indexes\n" }
	else { $data .= "\tOptions -Indexes\n" }

	# Setup php if appropriate
	# TODO: make sure this works with apache 1.x too
	if ($host_php eq "true") {
		#
		$data .= "\t<IfModule sapi_apache2.c>\n";
		$data .= "\t\tphp_admin_flag engine on\n";
		$data .= "\t\tphp_admin_value open_basedir \"" . $domain_root . "\"\n";
		$data .= "\t\tphp_admin_value upload_tmp_dir \"" . $domain_root . "/tmp\"\n";
		$data .= "\t\tphp_admin_value session.save_path \"" . $domain_root . "/tmp\"\n";
		$data .= "\t</IfModule>\n";
	} else {
		# Else, explicitly disable php
		$data .= "\t<IfModule sapi_apache2.c>\n";
		$data .= "\t\tphp_admin_flag engine off\n";
		$data .= "\t</IfModule>\n";
	}

	# Setup cgi if appropriate
	if ($host_cgi eq "true") {
		$data .= "\t\tOptions +Includes +ExecCGI\n";
		$data .= "\t<IfModule mod_perl.c>\n";
		$data .= "\t<Files ~ (\\.pl)>\n";
		$data .= "\t\tSetHandler perl-script\n";
		$data .= "\t\tPerlHandler ModPerl::Registry\n";
		$data .= "\t\tallow from all\n";
		$data .= "\t\tPerlSendHeader On\n";
		$data .= "\t</Files>\n";
		$data .= "\t</IfModule>\n";
	}

	# We're done with the virtual directory setup
	$data .= "\t</Directory>\n";

	# Setup this domains error documents
	# TODO: finish this
	#	$sql = "select * from error_docs where did = '$did'";

	# If a vhost.conf file exists for this domain, include it
	# TODO: do this for vhost_ssl.conf as well
	if ( -f $domain_root . "/conf/vhost.conf" ) {
		$data .= "\tInclude " . $domain_root . "/conf/vhost.conf\n";

		$self->debug($domain . " has a vhost.conf file");
	}

	if ($row->{'webstats_url'} eq "yes") {
		$data .= qq~
	Alias /icon/  $self->{RC_ROOT}/var/apps/awstats/wwwroot/icon/

	ScriptAlias /awstats/ $self->{RC_ROOT}/var/apps/awstats/wwwroot/cgi-bin/

	<Directory $self->{RC_ROOT}/var/apps/awstats/wwwroot/cgi-bin/>
		DirectoryIndex awstats.pl
	</Directory>

~;
	}

	# End our virtual host tag
	$data .= "</VirtualHost>\n\n";

	if ($ssl == 1) { $data .= "</IfModule>\n" }

	return $data;
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
	my ($self) = @_;

	return if $self->{DEMO};

	# check to make sure that this server is a mail server
	my %modules = $self->module_list_enabled;

	return unless exists $modules{mail};

	#
	return $self->debug("VMAIL_ROOT not defined") if $self->{CONF}{VMAIL_ROOT} eq "";

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

	# The system user mail will run as
	my $VMAIL_USER = "vmail";

	# The system uid and gid of the mail user
	my $VMAIL_UID = getpwnam($VMAIL_USER);
	my $VMAIL_GID = getgrnam($VMAIL_USER);

	# check to make sure that the /etc/sasldb2 file isn't corrupt

	if (-f "/etc/sasldb2" && file_get_contents("/etc/sasldb2") eq "") {
		file_delete("/etc/sasldb2");
	}

	# cache a list of current sasl users
	# TODO: check for errors here
	my @sasl_users = `sasldblistusers2 2> /dev/null`;
	chomp @sasl_users;

	# individual mail addresses
	my $sql = "select *, m.id as mid from domains d inner join mail_users m on m.did = d.id where d.mail = 'on' order by name";
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	my $dovecot_passwd;

#
	while (my $row = $result->fetchrow_hashref) {

		my $email_addr = $row->{'mail_name'} . '@' . $row->{'name'};

		$self->debug("Rebuilding configuration for " . $email_addr);
		my $domain_root = $self->{CONF}{VMAIL_ROOT} . "/" . $row->{'name'};

		mkdir_p( $domain_root . "/" . $row->{'mail_name'} );

		file_chown($VMAIL_USER.':'.$VMAIL_USER, $domain_root, $domain_root . "/" . $row->{'name'});

		# chech for the imap .subscriptions
		my $subscriptions = $domain_root . "/" . $row->{'mail_name'} . "/.subscriptions";

		#
		if ( ! -f $subscriptions ) {

			#
			my @dirs = ('.Sent', '.Trash', '.Drafts', '.');

			my $append;

			push @dirs, '.Spam' unless $row->{'spam_folder'} ne "true";

			foreach my $dir ( @dirs ) {

				#
				mkdir_p(
					$domain_root . "/" . $row->{'mail_name'} . "/" . $dir . "/cur",
					$domain_root . "/" . $row->{'mail_name'} . "/" . $dir . "/new",
					$domain_root . "/" . $row->{'mail_name'} . "/" . $dir . "/tmp"
				);

				#
				file_chown_r($VMAIL_USER.':'.$VMAIL_USER, $domain_root . "/" . $row->{'mail_name'} . "/" . $dir);
				chmod 0700, $domain_root . "/" . $row->{'mail_name'} . "/" . $dir;

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
		$vmailbox .= $email_addr . "\t\t" . $row->{'name'} . "/" . $row->{'mail_name'} . "/\n";

		if ($row->{'spam_folder'} eq "true") {
			$vmailbox .= $row->{'mail_name'} . '+spam@' . $row->{'name'} . "\t\t" . $row->{'name'} . "/" . $row->{'mail_name' } . "/.Spam/\n";
		}

		# check to see if this email should have any redirects

		# first, unset the alias_map variable
		my $alias_map = "";

		# if this is a local mailbox, tell the alias_map to deliver locally
		$alias_map = $email_addr unless $row->{'mailbox'} ne "true";

		# now loop for redirects
		my $sql = "select redirect_addr from mail_users where redirect = 'true' and id = '" . $row->{'mid'} . "'";
		my $result_redir = $self->{dbi}->prepare($sql);

		$result_redir->execute;

		while (my $row_redir = $result_redir->fetchrow_hashref) {

			# if $alias_map is emtpy, we don't start with a comma
			if ($alias_map eq "") {
				$alias_map = $row_redir->{'redirect_addr'};
			} else {
				$alias_map .= "," . $row_redir->{'redirect_addr'};
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
		my $cmd = "echo " . $row->{'passwd'} . " | saslpasswd2 " . $sasl_action . " -p -u " . $row->{'name'} . " " . $row->{'mail_name'};
		$output .= `$cmd 2>&1`;

		# build a random 2 letter "salt" string for use in the perl crypt fucntion in the docevat passwd-file below
		my $salt_str;

		for (my $i = 0; $i < 2; $i++) { $salt_str .= pack("C",int(rand(26))+65); }

		# dovecot passwd-file authentication

		$dovecot_passwd .= $email_addr . ":" . crypt($row->{'passwd'},$salt_str) . ":" . $VMAIL_UID . ":" . $VMAIL_GID . "::" . $domain_root . "/" . $row->{'mail_name'} . ":/bin/false\n";

	}

	$result->finish;

	#
	# stop spam from going off-server via a redirect
	#
	my @redir_emails;

	my $sql = "select lcase(redirect_addr) as redirect_addr from mail_users where redirect = 'true'";
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	#
	while (my $row = $result->fetchrow_hashref) {

		# each email is seperated by a comma
		my @email_list = split /,/, $row->{'redirect_addr'};

		foreach my $email (@email_list) {
			# a single email can show up many times, only add it to the list if it isn't there
			push @redir_emails, $email unless in_array($email, @redir_emails);
		}

	}

	$result->finish;

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
	my $sql = "select * from domains where mail = 'on' order by name";
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	#
	while ( my $row = $result->fetchrow_hashref )
	{

		if ($row->{'catchall'} eq "send_to" or $row->{'catchall'} eq "true") {
			$valiasmap .= '@' . $row->{'name'} . "\t\t" . $row->{'catchall_addr'} . "\n";
			$vtransportmap .= $row->{'name'} . "\t\tvirtual:\n";
		} elsif ($row->{'catchall'} eq "bounce" ) {
			$vtransportmap .= $row->{'name'} . "\t\terror:" . $row->{'bounce_message'} . "\n";
		} elsif ($row->{'catchall'} eq "delete_it") {
			$valiasmap .= '@' . $row->{'name'} . "\t\tdevnull\n";
			$vtransportmap .= $row->{'name'} . "\t\tvirtual:\n";
		} elsif ($row->{'catchall'} eq "alias_to") {
			$valiasmap .= '@' . $row->{'name'} . "\t\t" . '@' . $row->{'alias_addr'} . "\n";
			$vtransportmap .= $row->{'name'} . "\t\tvirtual:\n";
		} elsif ($row->{'catchall'} eq "relay") {
			# relay transport addition by spectro - slightly modified by cormander
			$vtransportmap .= $row->{'name'} . "\t\trelay:";

			# only add the [ ] around the relay_host if we want to force an MX lookup
			$vtransportmap .= $row->{'relay_host'} . "\n";
		}

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

	my $sql = "select * from domains where mail = 'on'";
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	#
	while (my $row = $result->fetchrow_hashref) {

		if($row->{'catchall'} ne "relay") { $vmaildomains .= $row->{'name'} . "\t\tplaceholder\n"; }
		# build the list of domains this server is allowed to relay for
		else { $relay_domains .= $row->{'name'} . "\t\tplaceholder\n"; }

		$data .= "\t\t'" . $row->{'name'} . "' => array('org_name' => '" . $row->{'name'} . "'),\n";

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
	my ($self, $query) = @_;

	return if $self->{DEMO};

# check to make sure that this server is a webserver
	my %modules = $self->module_list_enabled;

	return unless exists $modules{web};

	return unless $self->{db_connected};

# TODO: We must have one argument to run
#	my $c = "";
#
#	if( $c == 0 ) {
#
#	$self->do_error("Usage: rehash_ftp paramater\n" .
#			"\t--all	  Rebuild all ftp users on the server\n" .
#			"\tftpuser	Add/Modify this ftp user\n");
#	return;
#	}

	# create our shadow object
	my $shadow = new RavenCore::Shadow($self->{ostype});

	my $sql;

# TODO: fix
#	if($ARGV[0] eq "--all")
#	{
		$sql = "select s.login, s.passwd, s.shell, s.home_dir, d.name from sys_users s, domains d where suid = s.id";
#	}
#	else
#	{
#
#	$sql = "select * from sys_users where login = '" . $ARGV[0] . "'";
#	}

	#
	my $result = $self->{dbi}->prepare($sql);

	$result->execute;

	while (my $row = $result->fetchrow_hashref) {

		# Just in case we didn't get a shell value, use the default
		$row->{'shell'} = $self->{CONF}{DEFAULT_LOGIN_SHELL} unless $row->{'shell'};

		# if we don't have a home_dir, set it to default to VHOST_ROOT/domain
		$row->{'home_dir'} = $self->{CONF}{VHOST_ROOT}. '/' . $row->{'name'} unless $row->{'home_dir'};

		# ask if the user exists
		if ($shadow->item_exists('user', $row->{'login'})) {
			# if so, edit it
			# $login,$passwd,$home_dir,$shell,$uid,$gid
			$shadow->edit_user($row->{'login'},$row->{'passwd'},$row->{'home_dir'},$row->{'shell'},'',$shadow->{group}{'servgrp'}{'gid'});
		} else {
			# else, add it
			$shadow->add_user($row->{'login'},$row->{'passwd'},$row->{'home_dir'},$row->{'shell'},'',$shadow->{group}{'servgrp'}{'gid'});
		}

	} # end while( $row = $result->fetchrow_hashref )

	# commit our changes, if any
	$shadow->commit();

}

#

sub mail_del {
	my ($self, $email_addr) = @_;

	my ($mail_name, $domain) = split /@/, $email_addr;

	# remove the user from the sasl database
	system("saslpasswd2 -d -p -u " . $domain . " " . $mail_name);
}

#

sub service {
	my ($self, $query) = @_;

	if ($self->{DEMO}) {
		$self->do_error("Can't stop or start services in the demo.");
		return;
	}

	my ($service, $cmd) = split / /, $query;

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
	my ($self) = @_;

	return if $self->{DEMO};

	my %modules = $self->module_list_enabled;

	return unless exists $modules{web};

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
 
	# TODO: finish the below when custom log rotation is put back into the interface
   
#	my $sql = "select * from domains where logrotate = 'on'";
#	my @result = $self->{dbi}->prepare($sql);

#	while( my $row = $result->fetchrow_hashref )
#	{
#	$data .= $self->{CONF}{VHOST_ROOT} . '/' . $row->{name} . '/var/log/*.processed {' . "\n";
#	$data .= "\tcreate 644 root root\n"
#	case $log_when_rotate in
#	NULL);;
#"");;
#*)echo_e "\t$log_when_rotate";;
#	esac

#	case $log_mail_addr in
#	NULL)mail=nomail;;
#"");;
#*)echo_e "\tmail $log_mail_addr";;
#	esac

#	case $log_rotate_num in
#	NULL);;
#*)echo_e "\trotate $log_rotate_num";;
#	esac

#	case $log_rotate_size in
#	NULL);;
#*)echo_e "\tsize $log_rotate_size$log_rotate_size_ext";;
#	esac

#	case $log_compress in
#	yes)compress=compress;;
#no)compress=nocompress;;
#	esac

#	echo_e "\t$compress\n\n}"

	# write the file
	file_write($self->{RC_ROOT} . '/etc/logrotate.conf', $data);

}

#

sub rehash_named {
	my ($self, $query) = @_;

	return if $self->{DEMO};

	my %modules = $self->module_list_enabled;

	return unless exists $modules{dns};

	return unless $self->{db_connected};

	# Some checks
	return $self->do_error("NAMED_ROOT not defined") unless $self->{CONF}{NAMED_ROOT};
	return $self->do_error("NAMED_CONF_FILE not defined") unless $self->{CONF}{NAMED_CONF_FILE};

	# if the directory doesn't exist, exit
	return $self->do_error("The directory " . $self->{CONF}{NAMED_ROOT} . " does not exist") unless -d $self->{CONF}{NAMED_ROOT};

	# search for the checkzone command
	my $checkzone = find_in_path('named-checkzone');
	$checkzone = find_in_path('checkzone') unless $checkzone;

	return $self->do_error("Unable to find the bind server checkzone binary") unless $checkzone;

	# TODO: parse $query
	my $rebuild_conf = 1;
	my $all = 1;

	my @domain_list;

	# if given the "all" switch, set @domain_list to all domains
	if ($all == 1) {
		my $sql = "select distinct d.name from domains d, dns_rec r where r.did = d.id and d.soa is not null";

		my $result = $self->{dbi}->prepare($sql);

		$result->execute;

		while (my $row = $result->fetchrow_hashref) {
			unshift @domain_list, $row->{name};
		}

		$result->finish;
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

	foreach my $domain (@domain_list) {

		my $sql = "select * from domains where name = '" . $domain . "'";
		my $result = $self->{dbi}->prepare($sql);

		$result->execute;

		my $row = $result->fetchrow_hashref;

		$result->finish;

		# TODO: make this a perl command
		my $date_str = `date +%Y%m%d`;
		chomp $date_str;

		$date_str .= $num;

		$data = qq~\$TTL	300

\@	IN	SOA	$row->{soa} admin (
		$date_str	; Serial
		10800	; Refresh
		3600	; Retry
		604800	; Expire
		86400 )	; Minimum

~;

		# Loop through the records for this domain

		my $sql = "select * from dns_rec where did = '" . $row->{id} . "' order by type, name, target";
		my $result = $self->{dbi}->prepare($sql);

		$result->execute;

		while (my $row = $result->fetchrow_hashref) {
			# This may be an MX record. We seperate the MX token and the preference with a - symbol, so replace this with a space
			$row->{type} =~ s/-/ /;
			$data .= $row->{name} . "\t\tIN " . $row->{type} . "\t" . $row->{target} . "\n";
		}

		$result->finish;

		# Check to make sure this domain has enough DNS entries to be safely put into the configuration
		my $tmp_file = $self->{RC_ROOT} . '/var/tmp/' . $row->{name} . '.' . $$;

		# write to and check the tmp file
		file_write($tmp_file, $data);
		my $ret = system($checkzone . " -q " . $row->{name} . " " . $tmp_file);
		file_delete($tmp_file);

		# if all goes well, load the file
		if ($ret == 0) {
			$self->debug("Loading zone " . $row->{name});

			# If the zone file didn't already exists, we need to add the domain zone to named.conf. flag rebuild
			if ( ! -f $self->{CONF}{NAMED_ROOT} . '/' . $row->{name} ) {
				$rebuild_conf = 1;
				$self->debug($row->{name} . " is not in named.conf, flagging for a rebuild");
			}

			# Make the zone file
			file_write($self->{CONF}{NAMED_ROOT} . '/' . $row->{name}, $data);

		} else {
			# bad zone file, don't write it
			$self->debug("Bad zone file for " . $row->{name});
		}

	}

	# if we're told to rebuild to conf file, do so
	if ($rebuild_conf == 1) {
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

	}

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
			$self->service($init . ' restart');
			# end the loop
			last;
		}
	}

	# if we didn't restart, submit an error
	$self->do_error("Unable to find named init script") unless $restarted == 1;

}

#

sub system {
	my ($self, $cmd) = @_;

	if ($self->{DEMO}){
		$self->do_error("You can't reboot or shutdown the demo server!");
		return;
	}

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
	my ($self, @args) = @_;

	if($self->{DEMO}) {
		$self->do_error("You can't change services in the demo server!");
		return;
	}

	# TODO: figure out what other systems like debian use for this, and handle it accordingly
	# TODO: do checking on stuff in @args, it's passed into the system() function

	$self->do_error("Unable to execute chkconfig command") unless find_in_path('chkconfig');

	system ("chkconfig @args");

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
	my ($self, $runlevel) = @_;

	my $data;

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

sub start_webserver {
	my ($self, $graceful) = @_;

	unless ($graceful) {

		# check to make sure it aint already running
		if (my $tmp = file_get_contents($self->{RC_ROOT} . '/var/run/ravencore.httpd.pid')) {
			$self->do_error("ravencore.httpd pid file exists, sending TERM signal before startup");
			kill "TERM", $tmp;
			sleep 1;
		}

		# also check pidof
		if (my @pids = pidof('ravencore.httpd')) {
			$self->do_error("ravencore.httpd already appears to be running, sending TERM signal before startup");
			kill "TERM", @pids;
			sleep 1;
		}

	}

	# generate ssl cert for panel
	$self->ssl_genkey_pair($self->{RC_ROOT} . '/etc/');

	# check httpd version series. The result of this command will look like: 1.3 , 2.0 , etc
	my $str = ' -v | head -n 1 | sed \'s|.*Apache/||\' | sed \'s/^\([[:digit:]]\.[[:digit:]]*\)\..*$/\1/\'';
	my $httpd_v = `$self->{HTTPD} $str`;

	chomp $httpd_v;

	# check to make sure the conf file exists for this version of apache
	if ( ! -f $self->{RC_ROOT} . '/etc/ravencore.httpd-' . $httpd_v . '.conf' ) {
		$self->die_error("Unable to load ravencore's httpd conf file: $self->{RC_ROOT}/etc/ravencore.httpd-$httpd_v.conf");
	}

	# make sure the docroot.conf file is populated
	file_write($self->{RC_ROOT} . '/etc/docroot.conf', "DocumentRoot " . $self->{RC_ROOT} . "/httpdocs\n");

	# make sure the port.conf file exists. if not, create it
	if ( ! -f $self->{RC_ROOT} . '/etc/port.conf' ) {
		file_write($self->{RC_ROOT} . '/etc/port.conf', "Listen 8000\n");
	}

	# check to make sure our httpd_modules symlink is correct

	if ( ! -l $self->{RC_ROOT} . '/etc/httpd_modules' ) {

		# make sure that the httpd_modules.path directory exists
		if (-d $self->{HTTPD_MODULES}) {
			# re-create the symlink
			system('ln -s ' . $self->{HTTPD_MODULES} . ' ' . $self->{RC_ROOT} . '/etc/httpd_modules');
		} else {
			# can't find the httpd modules directory. exit with error
			$self->die_error("Unable to determine the path to the httpd modules");
		}
	}

	# set our runtime options
	# conf file is different depending on the httpd version
	my $OPTIONS = " -d " . $self->{RC_ROOT} . " -f etc/ravencore.httpd-$httpd_v.conf";

	# check for compiled-in modules, and include the ones that are not, that we need to function
	# get the list from the apache binary
	my $compiled_modules = `$self->{HTTPD} -l`;

	foreach my $module ( (
			'log_config',
			'setenvif',
			'mime',
			'negotiation',
			'dir',
			'actions',
			'alias',
			'include',
			) ) {

		# if this module is not compiled in to the binary
		if ($compiled_modules !~ /$module/) {
			$OPTIONS .= ' -D' . $module;
		}
	}

# check to see if the php and ssl apache modules live in a strange place.
# this will, in some cases, pick up the real file in the expected place.. but that's ok, linking a file
# to itself won't cause anything bad to happen, and we just force the error'd output to /dev/null
#	foreach my $so ( ('php', 'ssl') )
#	{
#		my $lib_so = $($_ls "$HTTPD_MODULES"*/*$so*.so 2> /dev/null)
#
# if so, link it to our httpd_modules
#
#	if [ -n "$lib_so" ]; then
#
# force error output to /dev/null , because in some cases we're trying to link the file to itself
#			$_ln -s $lib_so $RC_ROOT/etc/httpd_modules 2> /dev/null
#
#		fi
#
#	done

# only load PHP if we have it installed. That way, if we can't find PHP, we will successfully start up
# without php configured at all, and our no_php.html will show up as the default index page of the control
# panel, thus giving a more user-friendly (and less depressing) error message then a startup failure.

# first check to see if we have a funky name for the php module, mod_php ( it's normally libphp )
# we look through 4 and 5 because those are the supported versions
#	for num in 4 5; do

# basically, if mod_php4.so exist but there isn't a libphp4.so, create a symlink
#		[ -f $RC_ROOT/etc/httpd_modules/mod_php$num.so ] && [ ! -f $RC_ROOT/etc/httpd_modules/libphp$num.so ] && \
#			$_ln -s $RC_ROOT/etc/httpd_modules/mod_php$num.so $RC_ROOT/etc/httpd_modules/libphp$num.so
#	done

# php5 listed first, because on a test system with both php5 and php4 libs installed, mysql only worked with
# php5 loaded

	if (-f $self->{RC_ROOT} . '/etc/httpd_modules/libphp5.so') {
		$OPTIONS .= ' -DPHP5';
	} elsif (-f $self->{RC_ROOT} . '/etc/httpd_modules/libphp4.so') {
		$OPTIONS .= ' -DPHP4';
	}

	# figure out what our ssl shared object file is ( usually mod_ssl.so or libssl.so )
	my $ssl_file = `ls $self->{RC_ROOT}/etc/httpd_modules/*ssl.so 2> /dev/null | head -n1`;
	chomp $ssl_file;

	# if we have ssl installed, enable it when we start the control panel
	if (-f $ssl_file && $self->{RC_ROOT} . '/etc/server.crt' && $self->{RC_ROOT} . '/etc/server.key') {

		$OPTIONS .= ' -DSSL';

		# if running in ssl, be sure we have the port_ssl.conf

		if ( ! -f $self->{RC_ROOT} . '/etc/port_ssl.conf' ) {
			file_write($self->{RC_ROOT} . '/etc/port_ssl.conf', "Listen 8080\n");
		}

	}

# check to see if we want to use .local directories for var/apps
#	for i in $(ls $RC_ROOT/var/apps | grep -v awstats); do
#	if [ -d $RC_ROOT/var/apps/$i.local ]; then
#
# the app in all uppercase
#	OPTIONS="$OPTIONS -DLOCAL_"$(echo $i | perl -e 'print uc(<STDIN>);')
#
#	fi
#
#	done

	# start the apache webserver daemon
	my $ret = system($self->{RC_ROOT} . '/sbin/ravencore.httpd ' . ( $graceful ? ' -k graceful ' : '' ) . $OPTIONS);

	# check the exit status
	if ($ret != 0) {
		exit(1);
	}

	# wait a split second and check to see if the webserver actually started
	system ("sleep .5");

	if ( ! pidof('ravencore.httpd') ) {
		exit(1);
	}

}

#

sub unlock_user {
	my ($self, $user) = @_;

	my $sql = "delete from login_failure where login = '" . $user . "'";
	$self->{dbi}->do($sql);

	return;
}

#

sub mrtg {
	my ($self, $query) = @_;

	my ($type, $image) = split / /, $query;

	my $data;

	if ($type eq "html") {

		my @files = dir_list($self->{RC_ROOT} . '/var/log/mrtg');

		foreach my $file (@files) {
			next unless $file =~ /\.html/;
			$data .= file_get_contents($self->{RC_ROOT} . '/var/log/mrtg/' . $file);
			$data =~ s|SRC="([a-zA-Z0-9_\-\.]*)"|SRC="?img=$1"|ig;
		}

	} elsif ($type eq "image") {
		$data = file_get_contents($self->{RC_ROOT} . '/var/log/mrtg/' . basename($image));
	}

	return $data;
}

#

sub ftp_del {
	my ($self, $username) = @_;

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
		$self->rehash_httpd("--all");
	} else {
		return 0;
	}
}

#

sub ip_update {
	my ($self, $str) = @_;

	my ($ip, $uid, $did) = split / /, $str;

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

sub ip_list {
	my ($self) = @_;

	return "This page doesn't work without a database connection." unless $self->{db_connected};

	my $ips = {};
	my $db_ips = {};

	my $sql = "select * from ip_addresses";
	my $sth = $self->{dbi}->prepare($sql);

	$sth->execute;

	while (my $row = $sth->fetchrow_hashref) {
		$db_ips->{$row->{ip_address}} = $row;
	}

	$sth->finish;

	open IF, "ifconfig |";
	while (<IF>) {
		chomp;

		my $ip = $_;
		$ip =~ s/.*addr:((\d{1,3}\.){3}\d{1,3}).*/$1/;

		next unless is_ip($ip);
		next if $ip =~ /^127./;

		# not in the database?
		if ( ! $db_ips->{$ip} ) {
			$self->{dbi}->do("insert into ip_addresses set ip_address = ?, active = ?", undef, $ip, "true");
			$db_ips->{$ip} = { active => "true" };
		} elsif ( $db_ips->{$ip}{active} ne "true" ) {
			$self->{dbi}->do("update ip_addresses set active = ? where ip_address = ?", undef, "true", $ip);
			$db_ips->{$ip}{active} = "true";
		}

		$ips->{$ip} = $db_ips->{$ip};
	}
	close IF;

	# look for IPs in db that aren't in $ips
	foreach my $ip (keys %{$db_ips}) {

		if ( ! defined($ips->{$ip}) ) {
			$self->{dbi}->do("update ip_addresses set active = ? where ip_address = ?", undef, "false", $ip);
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

#

sub AUTOLOAD {
	my ($self) = @_;
	$self->debug("WARNING: Caught undefined function via AUTOLOAD");
}

#
# functions on Net::Server startup
#

sub configure_hook {
	my ($self) = @_;

	# if we have Locale::gettext, set our textdomain
	if ($INC{'Locale/gettext.pm'}) {
		textdomain("ravencore");
		bindtextdomain("ravencore", $self->{RC_ROOT} . '/var/locale');
		# TODO: verify LC_MESSAGES works in single quotes
		setlocale('LC_MESSAGES', $self->get_default_locale);
	}

	# toggle debugging
	$self->{debug_flag} = 1 if -f $self->{RC_ROOT} . '/var/run/debug';

	# cmd_privs_unauth: commands anybody can run. very few commands, and not able to do much
	# cmd_privs_client: commands a non-admin user can run
	# cmd_privs_admin: admin-only commands. reboot server, change password, etc.
	# cmd_privs_system: system-only commands

	# list of all functions
	@{$self->{'cmds'}} = ();

	foreach my $privs ( ('unauth', 'client', 'admin', 'system') ) {
		@{$self->{'cmd_privs_'.$privs}} = file_get_array($self->{RC_ROOT} . '/etc/cmd_privs_' . $privs);
		@{$self->{'cmds'}} = (@{$self->{'cmds'}}, @{$self->{'cmd_privs_'.$privs}});
	}

	# default to unauth privs
	@{$self->{cmd_privs}} = @{$self->{cmd_privs_unauth}};

	# TODO: $ENV{PATH}
	my @dist_map = file_get_array($self->{RC_ROOT} . '/etc/dist.map');

	# the etc/dist.map file is one distribution per line, first word is the name, and each string after it
	# seperated by a space is a file that is uniq to the distribution (that shouldn't exist on others).
	# it's a very basic way to tell what system this is, but it seems to work quite well

	foreach my $dist (@dist_map) {
		my @arr = split / /,$dist;

		# the first one is the dist name, we maybe are this one
		my $maybe_this = shift(@arr);

		# don't bother checking this if it's a # or a blank
		next unless $maybe_this;
		next if $maybe_this eq '#';

		# walk down the rest of the array.. they are files to check for
		foreach my $file (@arr) {
		# if this file exists, we're this dist... don't check again once the {dist} is set
			if( -f $file && !$self->{dist})
			{
				$self->{dist} = $maybe_this;
			}

		}

	}

	my $ostype = `uname`;
	chomp $ostype;

	if($ostype =~ /linux/i) {
		$self->{ostype} = 'linux';
	} elsif($ostype =~ /bsd/i) {
		$self->{ostype} = 'bsd';
	}

	# initialize some variables
	@{$self->{errors}} = ();

	# TODO: search for these rather then just define them
	$self->{HTTPD} = '/usr/sbin/httpd';
	$self->{INITD} = '/etc/init.d';

	# a hack; replace commandline with our correct startup procedure, so we can survive a sig HUP
	# Net::Server's default is $0, which is changed to 'rcserver', this changes the internal var back to the full path
	$self->{server}{commandline} = [ 'perl', '-I' . $self->{RC_ROOT} . '/var/lib', $self->{RC_ROOT} . '/sbin/rcserver' ];
}

# post_configure_hook
# post_bind_hook

sub pre_loop_hook {
	my ($self) = @_;

	chmod 0660, $self->{server}{port}[0];
	# TODO: use perl function instead of syscall
	CORE::system('chgrp rcadmin ' . $self->{server}{port}[0]);
}

#
# runtime functions before client connection
#

# pre_accept_hook
# post_accept_hook

# redirect STDERR before we fork

sub pre_fork_hook  {
	my ($self) = @_;

	# reopen STDERR to syslog so syntax and program errors are sent somewhere other than /dev/null
	# TODO: this is ugly, and may not work everywhere
	close STDERR;
	*STDERR = IO::Socket::UNIX->new(Type => SOCK_DGRAM, Peer => "/dev/log") or die $@;
}

#
# handle client connection
#

sub process_request {
	my ($self) = @_;

	$self->database_connect;

	$self->debug('Got client connection');

	# read commands until disconnect or quit
	while (my $query = $self->data_read) {
		# proccess the requested query and return the results to the client
		$self->data_write($self->run_query($query));

		last if $self->{quit}; # manual exit of session
	}

	# if session_read was called but seesion_write wasn't, call session_write
	if ($self->{run_ran}{session_read} && ! $self->{run_ran}{session_write}) {
		$self->debug(_('%s was ran, but %s was not; running %s', "session_read", "session_write", "session_write"));
		$self->session_write({'data' => $self->{session}{data}});
	}

	$self->debug('Closing client connection');

	return 1;
}

#
# shutdown cleaup
#

# pre_server_close_hook

#
# Net::Server functions to completly redefine
#

# nullify the damn function call that removes the entire %ENV hash on HUP.. it breaks shit. What the hell are they thinking?

sub hup_delete_env_keys { return; }

# contrary to usual daemon operations, we don't want a server INT or HUP to stop children from what they're doing...
# TODO: this may cause problems with shutdowns in certian situations, do some testing

sub close_children { return; }

#
# internal (non-Net::Server) functions which aren't called by the client
#

# simple debugging facility
# TODO: accept various debug verboseness

sub debug {
	my ($self, $msg) = @_;

	# only log in debug mode
	$self->log(2, $msg) if $self->{debug_flag};
}

# receive a chunk of data followed by a binary "end of transmission" character,

sub data_read {
	my ($self) = @_;

	my $data;
	my $c;

	# read each character one by one until EOT is reached
	while (read($self->{server}{client}, $c, 1)) {
		last if $c eq $EOT;
		$data .= $c;
	}

	# no data; either interuppted system call or the client disconnected
	return undef unless $data;

	# return the decoded data
	return $data;
}

# write a chunk of base64 encoded data followed by the binary EOT bit
# the encoded string is serialized, RavenCore::Client->run will decoded it properly

sub data_write {
	my ($self, $stdout) = @_;

	my $data = {
		stdout => $stdout,
		stderr => $self->{errors},
	};

	# write data the chunck
	print {$self->{server}{client}} encode_base64(serialize($data)) . $EOT;

	# errors are cleared after they're written
	@{$self->{errors}} = ();

}

# this is the server side function that is executed when RavenCore::Client->run() is called

sub run_query {
	my ($self, $query) = @_;

	# split the string
	my @args = split/ /, $query;

	# /
	# the first argument is the command (function) name to be called
	my $func = shift @args;

	# remember that we did this $func.. we have a hash for easy lookup, and an array to remember to order
	# in which they were called
	$self->{client_request_hash}{$func} = 1;
	push @{$self->{client_request_array}}, $func;

	my $ok_to_do = 0;

	# check to see if it is OK for this client to run this function
	foreach (@{$self->{cmd_privs}}) {
		$ok_to_do = 1 if $_ eq $func;
	}

	$self->debug("received query: " . $query);

	#
	# TODO: implement query logging. part of the whole reason why everything talks to the socket for data
	# queries and such, is so that there is a central point of logging... set different logging verbose levels,
	# so we'll know whather to report just insert/update/delete queries, just report commands, or report
	# ALL queries, directed to a logfile somewhere
	#

	if ($ok_to_do == 1) {
		# remove $func from query
		$query =~ s/^$func ?//;

		my $ret;

		# recieve any returned data from the client into this variable. Note that if we want to pass a hash or an
		# array, we need to return them as a reference, ie: return \%{self->{CONF}};
		# otherwise, you'll get a very wierd string

		# running a $func that doesn't exist shouldn't ever happen (unless a non-existant function is put in the
		# cmd_privs ), so just in case, do an eval on it to catch an "object method" error and prevent the child
		# process from crashing

		eval
		{
			local $SIG{__DIE__};
			$ret = $self->$func($query);
		};

		# if there was an error caught, tell the client
		$self->do_error($@) if $@;

		$self->debug("End of function call " . $func);
		$self->debug(_('Sent data: %s', ( ref($ret) ? Dumper($ret) : $ret )));

		return $ret;
	}

	# will only get here if $func wasn't OK to run for this client
	$self->do_error(_('Access denied for query: %s', $query));

	return;
}

# simple error facility

sub do_error {
	my ($self, $msg) = @_;

	push @{$self->{errors}}, $msg;

	$self->debug(_('Sent error: %s', $msg));
}

#

sub die_error {
	my ($self, $msg) = @_;

	print STDERR $msg, "\n";

	exit(1);
}

# very handy for debugging

sub dump_vars {
	my ($self) = @_;
	return Dumper($self);
}

1;