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
# authentication functions
#

# authenticate a session / check if a session authenticated. check session ID, IP address, access times, username/
# password when given. return "true" on success, anything else is the error message. The error of "" simply means
# not authenticated, and no attempt to authenticate was made.

sub auth {
	my ($self, $query) = @_;

	my ($session_id,$ipaddress,$username,$password) = split / /, $query;

	# first off, $session_id must be alphanumeric from beginning to end
	return 'Invalid session ID.' unless $session_id =~ /^[a-zA-Z0-9]*$/;

	# validate IP address
	return 'Invalid IP address.' unless is_ip($ipaddress);

	# cache the current time
	my $now = time;

	# $ipaddress is a protective layer against someone else remotly (via the php pages) trying to steal someone else's
	# session. It is pretty useless past that point - if you (the hacker) happen to know their session ID, and have gained
	# access to arbitrarily write commands to the socket directly, you can spoof any IP address you want - and use the
	# netstat command (or likewise) to see what IP addresses are connecting to the ravencore webserver. Knowing the
	# ip address and session ID, you essentially have as much access as they do. This only applies, however, if the session
	# is active (the client is actually logged in at the time the hacker is present on the system). So even if an exploit
	# exists to gain socket access, the system isn't a sitting duck all the time - someone has to be logged in for you to
	# gain control over their account.

	$self->{session_id} = $session_id;

	# define our session file
	$self->{session_file} = $self->{RC_ROOT} . '/var/tmp/sessions/' . $self->{session_id};

	# default session timeout to 600 if we don't have the config value yet
	my $session_timeout = ( $self->{CONF}{SESSION_TIMEOUT} ? $self->{CONF}{SESSION_TIMEOUT} : 600 );

	# read our session data
	$self->session_read;

	# the "user" session variable will only exist if we have a valid session
	if ($self->{session}{user}) {
		# if ip address given is different then is recorded in the session file, delete it
		$self->session_dest("Session IP " . $self->{session}{ip} . " doesn't match client IP " . $ipaddress) if $self->{session}{ip} ne $ipaddress;
		# if accessed time is newer then right now, delete it
		$self->session_dest("Session last access time is NEWER then right now") if $self->{session}{accessed} > $now;
		# if accessed time is older then now + SESSION_TIMEOUT, delete it
		$self->session_dest("Session last access time is older then the SESSION_TIMEOUT variable of " . $session_timeout) if
		($now - $session_timeout) > $self->{session}{accessed};

		# if the session file no longer exists after the above checks - we're not authenticated
		return "Session has expired" unless -f $self->{session_file};

		if ( ! $self->is_admin($username) && ! $self->{db_connected} ) {
			return "Database error.";
		}

		# if we made it here, we're authenticated. Update the access time and access permissions
		$self->{session}{accessed} = $now;

		$self->set_privs;

		# authentication successful
		return 1;
	}

	# default lockout time to 300 if we don't have the config value yet
	my $lockout_time = ( $self->{CONF}{LOCKOUT_TIME} ? $self->{CONF}{LOCKOUT_TIME} : 300 );

	# default lockout count to 3 if we don't have the config value yet
	my $lockout_count = ( $self->{CONF}{LOCKOUT_COUNT} ? $self->{CONF}{LOCKOUT_COUNT} : 3 );

	# we get here if we had no previous session, attempt to authenticate
	# no username or password, and no session... return nothing
	if ( ! $username or ! $password ) {

		# give them a session, but it's unathenticated because there is no user
		$self->{session}{ip} = $ipaddress;
		$self->{session}{created} = $now;
		$self->{session}{accessed} = $now;

		return;
	}

	# the username/password is being posted if we get here

	# make sure our config is complete and our GPL has been checked, and if not, lock out normal users
	# We do this after the above if-statement because if we don't, we'll give an error when you first load
	# the login page w/o attempting authentication

	if ( ! $self->{config_complete} || ! $self->{gpl_check} ) {
		if ( ! $self->is_admin($username)) {
			return 'Login locked by the administrator.';
		}
	}

	# make sure we're not out-of-date (version wise).
	# NOTE: this should only be done when username/password is being posted for efficientcy, so don't move this
	# any higher then it is.
	if ($self->version_outdated) {
		# admin still gets to login... but with a warning that we're out of date
		if ($self->is_admin($username)) {
			# TODO: find out what happens to the session var if the admin actually failed to authenticate
			# set our session "status_mesg" variable for admin to see after they login
			$self->session_set_var('status_mesg', 'Warning: Your version of RavenCore is out of date. Please visit www.ravencore.com and download/install the latest version.');
		} else {
			# users can't login if outdated and we're configured to lock them out.... give a cryptic reason as to why
			return 'Login locked by the administrator.' if $self->{CONF}{LOCK_IF_OUTDATED} == 1;
		}
	}

	if ($self->{db_connected}) {

		# get the login failure count for this user
		my $sql = "select count(*) as count from login_failure
				where login = '" . $username . "' and
				( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + " . $lockout_time . " ) >
				( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";
		my $result = $self->{dbi}->prepare($sql);

		$result->execute();
		my @row = $result->fetchrow_array;
		$result->finish();

		# if locked out, issue error
		return 'Too many login failures, please try again later.' if $row[0] >= $lockout_count;

	}

	# this lets us keep the password as "ravencore" in the demo
	if ($self->{DEMO}) {
		$self->{initial_passwd} = 0;
	}

	# TODO: have a spot to specify why locked, and fields to determine when it'll unlock, and have the option for a perm-lock
	# things such as "account disabled"

	# if username and password but they are invalid, add to the error_count of username and issue error: "invalid username
	# or password"

	# admin user auth
	if ($self->is_admin($username)) {
		# check to see if the admin password has been set - if not, return false and tell them to set it
		return 'You have not set your admin password yet. Please run the following command as root:<p>' . $self->{RC_ROOT} . '/sbin/run_cmd passwd'
		if $self->{initial_passwd};

		# TODO: set the db_panic value in session_info, giving a logical reason why as to the admin user only sees the
		# system page... (no database connection, for example)

		return "Login failed." unless $self->auth_admin($password);
	} else {
		# user auth
		# no database connection, users can't login
		return "Sorry, there is a problem with the login system." unless $self->{db_connected};

		return "Login failed." unless $self->auth_user($username, $password);
	}

	# if we get here, we are authenticated. tie the session to this client and update the access time
	$self->{session}{ip} = $ipaddress;
	$self->{session}{created} = $now;
	$self->{session}{accessed} = $now;
	$self->{session}{user} = $username;

	$self->set_privs;

	return 1;

}

# tell us whether the given password is correct or not for admin access

sub auth_admin {
	my ($self, $passwd) = @_;

	if ($passwd eq $self->get_passwd) {
		# password is correct, return true
		# TODO: call an auth_success function here to record the login
		return 1;
	}

	# return false ( a call to auth_fauluire returns false, and records the attempt )
	return $self->auth_failure($self->{MYSQL_ADMIN_USER});

}

# record the failure to authenticate

sub auth_failure {
	my ($self, $username) = @_;

	if ($self->{db_connected}) {
		my $sql = "insert into login_failure set date = now(), login = '" . $username . "'";
		$self->{dbi}->do($sql);
	}

	return 0;

}

# Access for the "system" user, which is only accessable via the command line, because first a special
# file must be created to prove that this really is the root user running the command. This method is
# so that even if the "admin" user gets locked out ( by someone trying to hack in ) it doesn't
# lockout the system from doing stuff on the back-end. It also allows for the option of haveing stuff
# not even the admin can do, but the system can. Still we should be smart about it, and not allow too
# much access by even the system ( see cmd_privs_system )

sub auth_system {
	my ($self, $query) = @_;

	my ($session_id,$password) = split / /, $query;

	# validate the session_id
	return 'Invalid session ID.' unless $session_id =~ /^[a-zA-Z0-9]*$/;

	# special session filename for system access
	my $session_file = $self->{RC_ROOT} . '/var/tmp/sessions/SYSTEM_' . $session_id;

	# the password must be correct AND our special session file must exist prior to authenticaton, the
	# system client must create the file
	return unless $session_file;

	# TODO: actually use the session for session read/write, and remove it after we're done
	# for now, just remove the session file so it doesn't hang around forever
	file_delete($session_file);

	# check the password too, for good measure
	if ($password eq $self->get_passwd) {
		# system is authenticated. we're an admin user
		$self->{session}{user} = $self->{MYSQL_ADMIN_USER};

		# set privs with the system flag
		$self->set_privs(1);

		return 1;
	}

	return;

}

#

sub auth_user {
	my ($self, $username, $password) = @_;

	# query the database for this user
	# TODO: if/else statements for different user types
	#	   if a username contains an @ sign, attempt mail user auth
	#	   if a username contains a dot, attempt domain user auth
	#	   otherwise, attempt control panel user auth
	my $count;

	$count = $self->{dbi}->selectrow_array("select count(*) from users where binary(login) = ? and binary(passwd) = ? limit 1", undef, $username, $password);

	$self->debug("CP user auth result: $count");

	return 1 if $count eq "1";

	# try again for mail users
	$count = $self->{dbi}->selectrow_array("select count(*) from domains d inner join mail_users mu on mu.did = d.id where concat(mail_name,'\@',d.name) = ? and binary(mu.passwd) = ? limit 1", undef, $username, $password);

	$self->debug("email user auth result: $count");

	if ($count eq "1") {
		# email users get no gui, for now
		$self->{no_gui} = 1;
		return 1;
	}

	# we only get here if we failed auth.. call the failed auth function
	return $self->auth_failure($username);
}

#

sub set_privs {
	my ($self, $system) = @_;

	# tie this session with client privs by default
	@{$self->{cmd_privs}} = (@{$self->{cmd_privs}}, @{$self->{cmd_privs_client}});

	# if this user is admin, tie it with admin privs
	@{$self->{cmd_privs}} = (@{$self->{cmd_privs}}, @{$self->{cmd_privs_admin}}) if $self->is_admin;

	# if this user is a system user, tie it with system privs
	@{$self->{cmd_privs}} = (@{$self->{cmd_privs}}, @{$self->{cmd_privs_system}}) if $system;

	if($self->{db_connected}) {
		# fill in user_data
		my $sql = "select * from users where binary(login) = '" . $self->{session}{user} . "' limit 1";
		my $result = $self->{dbi}->prepare($sql);

		$result->execute();
		my $row = $result->fetchrow_hashref;
		$result->finish();

		# success if %row exists
		$self->{session}{user_data} = $row if $row;

	}

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

# is this an admin user?

sub is_admin {
	my ($self, $username) = @_;

	# if we're not given a username, assume the session as the user to check
	$username = $self->{session}{user} unless $username;

	return 1 if $username eq $self->{MYSQL_ADMIN_USER};
	return 0;
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

#

sub version_outdated {
	my ($self) = @_;

	# return 0 if unable to check
	unless( $self->{perl_modules}{Net::HTTP} ) {
		$self->debug("Unable to check ravencore version: Net::HTTP perl module not found");
		return 0;
	}

	# the last digit is the release number
	my $release = $self->{version};
	$release =~ s/.*\.(\d*)/$1/;

	# sub the last digit for an x, this is the variable used to find the correct remote version file
	my $v = $self->{version};
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
	$s->write_request(GET => "/updates/" . $v . '.txt', 'User-Agent' => "RavenCore/".$self->{version});
	$self->debug("http request sent to www.ravencore.com: GET /updates/$v.txt, User-Agent RavenCore/".$self->{version});

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
	if ($data gt $self->{version}) {
		$self->debug("RavenCore version is NOT up-to-date");
		return 1;
	}

	# return 0 when up to date
	$self->debug("RavenCore version is up-to-date, checked at www.ravencore.com (which said " . $data . ")");
	return 0;

}

#

sub unlock_user {
	my ($self, $user) = @_;

	my $sql = "delete from login_failure where login = '" . $user . "'";
	$self->{dbi}->do($sql);

	return;
}

