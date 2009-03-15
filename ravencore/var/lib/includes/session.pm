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
# Session functions, these tie into the PHP session calls
#

# tell the client the current status of this session and a few selection server variables

sub session_status {
	my ($self) = @_;

	# hash to return
	my %data;

	# do we have a database connection?
	$data{db_panic} = 1;
	$data{db_panic} = 0 if $self->{db_connected};

	# has the GPL been accepted?
	$data{gpl_check} = $self->{gpl_check};

	# is our config complete?
	$data{config_complete} = $self->{config_complete};

	# is our installation complete?
	$data{install_complete} = $self->{install_complete};

	# are we an admin?
	$data{is_admin} = $self->is_admin;

	# enabled modules
	my %modules = $self->module_list_enabled;

	@{$data{services}} = ();

	# we don't want the conf files, just the names of the modules, so replace them
	foreach my $mod (%modules) {

		$modules{$mod} = $mod;

		# also add services
		my @services = file_get_array($self->{RC_ROOT} . '/etc/services.' . $mod );

		foreach (@services) {
			if (-f $self->{INITD} . '/' . $_ && ! in_array($_, @{$data{services}})) {
				# push @{$self->{services}}, $_;
				push @{$data{services}}, $_;
			}
		}
	}

	# remember, have to pass things as a reference to the client
	$data{modules_enabled} = \%modules;

	# configuration
	$data{CONF} = \%{$self->{CONF}};

	# uninit configuration
	$data{UNINIT_CONF} = \%{$self->{UNINIT_CONF}};

	# user data from this session
	$data{user_data} = \%{$self->{session}{user_data}};

	return \%data;

}

# store a variable that will later be created as PHP session variable

sub session_set_var {
	my ($self, $var, $data) = @_;

	# the session_set_vars hash is written and deleted during session_read and session_write
	if (ref($data) eq "HASH") {
		%{$self->{session_set_vars}{$var}} = $data;
	} elsif (ref($data) eq "ARRAY") {
		@{$self->{session_set_vars}{$var}} = $data;
	} else {
		$self->{session_set_vars}{$var} = $data;
	}
}

# get all stored variables that are destined as a PHP session variable, returned as a serialize'd string

sub session_get_vars
{
	my ($self) = @_;

	my $data;

	# serialize and prepend our session_set_vars to the given session data to write
	# TODO: call session_encode from serialize.pm ... but for some reason it's giving an undefined function error
	foreach my $key (keys %{$self->{session_set_vars}}) {
		$data .= $key . '|' . serialize($self->{session_set_vars}{$key});
	}

	# undefine our hash when we're done, we don't want the data written twice by accident
	%{$self->{session_set_vars}} = ();

	return $data;
}

# read in session data. return true if there is session data, false otherwise

sub session_read {
	my ($self) = @_;
	my $c;

	# if we already have data - don't read again.
	return $self->{session}{data} if defined $self->{session}{data};

	# see if the session file exists
	if (-f $self->{session_file}) {

		# stat the session_file
		my @sess_stat = stat($self->{session_file});
		my $mode = sprintf "%04o", $sess_stat[2] & 07777;
		my $owner = $sess_stat[4];

		# if file exists, check that it's owned by root and set to 0600, and if not, remove it
		if ($mode ne '0600' or $owner != 0) {
			# kill the session file
			$self->session_dest("Incorrect permissions or ownership on session file");

			return "";
		}

		# read the session file data...
		# in format: IP Address, created datetime, last accessed datetime, username, sess_data, separated by :
		my ($ip,$created,$accessed,$user,$sess_data) = $self->session_read_file($self->{session_file});

		# cache our current time
		my $now = time;

		# append perl made session vars to $sess_data
		$sess_data .= $self->session_get_vars;

		# save our session data
		$self->{session}{ip} = $ip;
		$self->{session}{created} = $created;
		$self->{session}{accessed} = $accessed;
		$self->{session}{user} = $user;
		$self->{session}{data} = $sess_data;

		return $self->{session}{data};

	}

	return;

}

#

sub clear_stale_sessions {
	my ($self) = @_;

	opendir DH, $self->{RC_ROOT} . '/var/tmp/sessions/';
	while (my $file = readdir(DH)) {
		next if $file =~ /^\./;
		my $session_file = $self->{RC_ROOT} . '/var/tmp/sessions/' . $file;

		my ($ip,$created,$accessed,$user,$sess_data) = $self->session_read_file($session_file);

		# think a day is long enough to keep a stale session around?
		if ((time - $accessed) > (60*60*24)) {
			$self->debug("Clearing session file $file");
			file_delete($session_file);
		}
	}
	closedir DH;

}

#

sub session_read_file {
	my ($self, $file) = @_;

	# read the session file data
	my $data = file_get_contents($file);

	# kill the return character - if any
	chomp $data;

	# in format: IP Address, created datetime, last accessed datetime, username, sess_data, separated by :
	return split /:/, $data, 5;
}

# write the given data to the session file, along with our other session information

sub session_write {
	my ($self, $data) = @_;

	# if no session data is in memory, don't write a file
	return 1 unless %{$self->{session}};

	# append perl set session vars to our session data for writting
	$data .= $self->session_get_vars;

	# write our session file
	file_write(
		$self->{session_file},
		$self->{session}{ip} . ':' .
			$self->{session}{created} . ':' .
			$self->{session}{accessed} . ':' .
			$self->{session}{user} . ':' .
			$data
	);

	# set root read only file permissions on the session file
	chown 0, 0, $self->{session_file};
	chmod 0600, $self->{session_file};

	# TODO: return the number of bytes written.. at least that's what the PHP docs say this is
	# supposed to do... for now, just return something
	return 1;
}

# remove a session file and all data

sub session_dest {
	my ($self, $msg) = @_;

	# submit debugging information
	# TODO: make this go to syslog instead, after we have a good syslog function in this object
	$self->debug('Session ID ' . $self->{session_id} . ' destroyed because: ' . ( $msg ? $msg : 'Unknown reason' ));

	# remove the session from memory, so we don't rewrite the file when PHP calls the session_write function
	undef $self->{session};

	# remove the session file
	unlink $self->{session_file};
}

# remove a given session

sub session_remove {
	my ($self, $session_id) = @_;

	# since we're removing a file, even tho only an admin can do this, check to make sure it's actually a session ID
	# and not something funky
	return $self->do_error('Invalid session ID.') unless $session_id =~ /^[a-zA-Z0-9]*$/;

	$self->debug('Session ID ' . $session_id . ' destroyed because: Deleted by admin');

	unlink $self->{RC_ROOT} . '/var/tmp/sessions/' . $session_id;
}

# list sessions

sub session_list {
	my ($self) = @_;
   
	# for each session, we need to return: Login - IP Address - Session Time - Idle Time
	my %session_list;

	my @sessions = dir_list($self->{RC_ROOT} . '/var/tmp/sessions');

	foreach my $session_id (@sessions) {
		# don't list system sessions... or anything not a normal session for that matter
		next unless $session_id =~ /^[a-zA-Z0-9]*$/;

		my ($ip,$created,$accessed,$user,$sess_data) = $self->session_read_file($self->{RC_ROOT} . '/var/tmp/sessions/' . $session_id);

		# if there isn't user data for this session, then it's a non-ravencore php session, ignore it
		next unless $user;

		$session_list{$session_id}{ip} = $ip;
		$session_list{$session_id}{created} = $created;
		$session_list{$session_id}{accessed} = $accessed;
		$session_list{$session_id}{user} = $user;

	}

	return \%session_list;
}

