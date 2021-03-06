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

use strict;
use warnings;

# if these don't exist, you've got one funky installation
use RavenCore::Common;
use RavenCore::Shadow;
use Config::Abstract::Ini;
use PHP::Serialization qw(serialize unserialize);
use Template;

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

use Carp;

$Carp::MaxArgLen = 0;

#
# RavenCore::Server warning handler -
#    Since in most cases the warnings get sent to syslog, and syslog doesn't handle
#    multi-line output very gracefully, convert the stacktrace to an array ref and
#    handle the conversion the same way Data::Dumper info gets squished onto one line

$SIG{__WARN__} = sub {
	my $str = Carp::longmess @_;

	# kill all prepended whitespace
	$str =~ s/^\s*//mg;

	# convert $str into an array ref; each line is an element in the array, and then
	# back into a string via Data::Dumper so we get a nice and pretty one-liner msg
	$str = Dumper([ split /\n/, $str ]);

	# kill all prepended whitespace, again
	$str =~ s/^\s*//mg;

	# replace newlines
	$str =~ s/\n/ /g;

	# replace \' with double quotes
	$str =~ s/\\'/"/g;

	# re-throw the warning
	warn $str;
};

# global vars
our $NUL = chr(0);
our $ETX = chr(3);
our $EOT = chr(4);

our $AUTOLOAD;

# combind this object with Net::Server, and load other nessisary functions from various files

sub new
{
	my ($class) = @_;

	my $self = {
		class => $class,
	};

	bless $self, $class;

	# read core configuration; these variables go directly into $self
	my $RC_ETC = '/etc/ravencore.conf';
	my %rcetc = $self->parse_conf_file($RC_ETC);

	foreach my $key (keys %rcetc) {
		$self->{$key} = $rcetc{$key};
	}

	# sanity check that we got RC_ROOT, RC_ROOT exists, and ADMIN_USER is defined
	$self->die_error(_('Variable %s is undefined! Please check the file: %s', 'RC_ROOT', $RC_ETC)) unless $self->{RC_ROOT};
	$self->die_error(_('The root directory %s does not exist!', $self->{RC_ROOT})) unless -d $self->{RC_ROOT};
	$self->die_error(_('Variable %s is undefined! Please check the file: %s', 'ADMIN_USER', $RC_ETC)) unless $self->{ADMIN_USER};

	# initialize some defaults if they were not set in the above conf file
	$self->{DEBUG} = 0 unless $self->{DEBUG};
	$self->{DEBUG_SHOW_PASSWORDS} = 0 unless $self->{DEBUG_SHOW_PASSWORDS};
	$self->{DEBUG_STRING_LENGTH} = 2500 unless $self->{DEBUG_STRING_LENGTH};
	$self->{DEMO} = 0 unless $self->{DEMO};

	#
	# load required modules that we need from our own lib; they may not nessisarily exist with a standard perl install
	#

	unshift @INC, $self->{RC_ROOT} . '/lib';

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
		'DBD::SQLite',
		'Locale::gettext',
	);

	foreach my $mod ( @internal_perl_mod_specs ) {

		eval "use " . $mod . ";";

		# if eval succeeded, record that we have the module in use
		if (!$@) {
			$self->{perl_modules}{$mod} = 1;
		}

	}

	#
	# load more internal functions from seperate files
	#

	my @pms = dir_list($self->{RC_ROOT} . '/lib/includes');

	foreach my $pm (@pms) {

		# only include this file if it ends in .pm
		# TODO: check file ownership/permissions, should be 0600 root:root
		next unless $pm =~ /\.pm$/;

		$self->debug("Including " . $self->{RC_ROOT} . '/lib/includes/' . $pm);
		do $self->{RC_ROOT} . '/lib/includes/' . $pm;

		# check for errors on the do statement, if there is a syntax error, it'll show up as a "Bad file
		# descriptor", but that's confusing and scary, so say something more optimistic
		$self->do_error(_("Warning: %s might not have completly loaded, it appears to have syntax errors", $pm)) if $!;

	}

	# initialize errors array
	@{$self->{errors}} = ();

	return $self;
}

# reload rcserver

sub reload {
	my ($self, $input) = @_;

	my $msg = $input->{message};

	# tell our parent process to reload (if we are not the parent process)
	if ($self->{server}{ppid} ne $$) {
		$self->log('2',$msg) if $msg; # always good to know why

		kill "HUP", $self->{server}{ppid};
	}
}

# reload ravencore.httpd

sub reload_webserver {
	my ($self) = @_;

	$self->debug("reloading control panel webserver");

	# argument makes it graceful
	$self->start_webserver(1);

	return
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

			$key =~ s/^([A-Z0-9_]*)=.*/$1/;
			$val =~ s/^([A-Z0-9_]*)=//;

			# remove starting and ending quotations
			$val =~ s/^("|')//;
			$val =~ s/('|")$//;

			$data{$key} = $val;

		}

	}

	return %data;
}

# bind to a high port and serve up the interface to RavenCore

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

# catch undefined function and dump a stacktrace

sub AUTOLOAD {
	my ($self) = @_;
	warn "WARNING: Caught undefined function via AUTOLOAD; $AUTOLOAD";
}

#

sub DESTROY {
	undef @_;
}

#
# functions on Net::Server startup
#

sub configure_hook {
	my ($self) = @_;

	# our running version
	chomp($self->{version} = file_get_contents($self->{RC_ROOT} . '/etc/version'));

	# if we have Locale::gettext, set our textdomain
	if ($INC{'Locale/gettext.pm'}) {
		textdomain("ravencore");
		bindtextdomain("ravencore", $self->{RC_ROOT} . '/var/locale');
		# TODO: verify LC_MESSAGES works in single quotes
		setlocale('LC_MESSAGES', $self->get_default_locale);
	}

	# toggle debugging
	$self->{DEBUG} = 1 if -f $self->{RC_ROOT} . '/var/run/debug';

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

	# check to see if the GPL has been accepted
	$self->{gpl_check} = 0;

	if ( -f $self->{RC_ROOT} . "/var/run/gpl_check" ) {
		$self->{gpl_check} = 1;
	} else {
		$self->debug("GPL agree file not found: " . $self->{RC_ROOT} . "/var/run/gpl_check");
	}

	# assume our configuration is complete, because if it actually isn't, we'll set this to zero
	# if no database connection, we're considered to have a "config_complete" because we won't
	# be able to find out, which is what we want.
	$self->{config_complete} = 1;

	# are we a complete install?
	$self->{install_complete} = 0;
	$self->{install_complete} = 1 if -f $self->{RC_ROOT} . '/var/run/install_complete'; 

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
			if( -f $file && !$self->{dist}) {
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
	$self->{server}{commandline} = [ 'perl', '-I' . $self->{RC_ROOT} . '/lib', $self->{RC_ROOT} . '/sbin/rcserver' ];
}

# post_configure_hook
# post_bind_hook

sub pre_loop_hook {
	my ($self) = @_;

	chmod 0666, $self->{server}{port}[0];
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
	my ($self, $msg, @args) = @_;

	if (0 != scalar @args) {
		$msg = sprintf($msg, @args);
	}

	# only log in debug mode
	if ($self->{DEBUG}) {
		# kill Data::Dumper's prepended whitespace
		$msg =~ s/^\s*//mg;

		# kill newlines
		$msg =~ s/\n/ /g;

		# replace password values with ******
		if (!$self->{DEBUG_SHOW_PASSWORDS}) {
			$msg =~ s/'(\w*?passwo?r?d\w*?)' => '(.*?)'(,?) /'$1' => '******'$3 /g;
		}

		# only let the debug message be so long
		if (length($msg) > $self->{DEBUG_STRING_LENGTH}) {
			$msg = substr($msg, 0, $self->{DEBUG_STRING_LENGTH});
		}

		# use Net::Server log() if it exists
		if ($self->{server}{log_file}) {
			$self->log(2, $msg);
		} else {
			print STDERR scalar localtime(), ": ", $msg, "\n";
		}
	}
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
	my ($func, $input) = split/ /, $query, 2;

	# /

	# remember that we did this $func.. we have a hash for easy lookup, and an array to remember to order
	# in which they were called
	$self->{client_request_hash}{$func} = 1;
	push @{$self->{client_request_array}}, $func;

	my $ok_to_do = 0;

	# check to see if it is OK for this client to run this function
	foreach (@{$self->{cmd_privs}}) {
		$ok_to_do = 1 if $_ eq $func;
	}

	# for now, all functions starting with get_ or push_ can be called
	$ok_to_do = 1 if $func =~ /^get_/;
	$ok_to_do = 1 if $func =~ /^push_/;

	$self->debug("received query: " . $query);

	if ($ok_to_do == 1) {
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
			my $data;

			# incoming hash ref is the one and only argument to each function called by the interface
			if ($input) {
				$data = unserialize(decode_base64($input));

				$self->debug(Dumper($data));
			}

			$ret = $self->$func($data);
		};

		# if there was an error caught, tell the client
		$self->do_error($@) if $@;

		$self->debug("End of function call " . $func);
		$self->debug(_('Sent data: %s', ( ref($ret) ? Dumper($ret) : ( defined $ret ? $ret : 'undef' ))));

		return $ret;
	}

	# will only get here if $func wasn't OK to run for this client
	$self->do_error(_('Access denied for query: %s', $query));

	return;
}

# serialize values in the same manner that PHP does session data

sub session_encode {
	my ($value) = @_;
	my $str = "";

	if ("HASH" eq ref($value)) {
		foreach my $key (keys %{$value}) {
			$str .= "$key|". serialize($$value{$key});
		}
	}
	elsif ("ARRAY" eq ref($value)) {
		for (my $key = 0; $key < scalar @{$value}; $key++ ) {
			$str .= "$key|". serialize($$value[$key]);
		}
	}

	return $str;
}

# the reverse of session_encode

sub session_decode {
	my ($string) = @_;

	my %res = $string =~ /([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\|([^\|]*[\;\}])/g;

	return \%res;
}

# simple error facility

sub do_error {
	my ($self, $msg) = @_;

	push @{$self->{errors}}, $msg;

	$self->debug(_('Sent error: %s', $msg));

	return undef;
}

#

sub die_error {
	my ($self, $msg) = @_;

	print STDERR $msg, "\n";

	exit(1);
}

1;
