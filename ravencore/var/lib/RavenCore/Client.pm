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
# The method (coded in perl) to connect to the ravencore socket and submit queries and read data
#

package RavenCore::Client;

use RavenCore;
use Serialize;

use IO::Socket::UNIX;
use MIME::Base64;

our $EOT = chr(4);

# connect to the ravencore socket

sub new {
	my ($class, $RC_ROOT) = @_;

	my $self = {
		RC_ROOT => ( $RC_ROOT ? $RC_ROOT : '/usr/local/ravencore' ),
	};

	bless $self, $class;

	# try 3 times to connect, then bail
	my $connect_retry = 0;

	while ($connect_retry < 3) {
		$self->{socket} = IO::Socket::UNIX->new($self->{RC_ROOT} . '/var/rc.sock');
		last if $self->{socket};
		$connect_retry++;
		sleep(1);
	}

	die "Unable to connect to RavenCore socket" unless $self->{socket};

	return $self;
}

#

sub auth_system {
	my ($self) = @_;

	# generate a random session_id
	my $session_id = gen_random_id(32);

	# define our authentication file that will be looked for when we run auth_system
	# TODO: repackage the file_ functions in ravencore.pm to their own module and use the file_touch here
	system('touch ' . $self->{RC_ROOT} . '/var/tmp/sessions/SYSTEM_' . $session_id);

	# normal auth would look something like this
	#    my $resp = $self->do_raw_query('auth ' . $session_id . ' ' . $ipaddress . ' ' . $username . ' ' . $password);
	# TODO: create a user-level shell API using this method

	return $self->do_raw_query('auth_system ' . $session_id . ' ' . $self->get_passwd);
}

# authenticate as a user

sub auth {
	my ($self, $session_id, $ipaddress, $username, $password) = @_;

	unless ($session_id) {
		$session_id = gen_random_id(32);
	}

	return $self->do_raw_query('auth ' . $session_id . ' ' . $ipaddress . ' ' . $username . ' ' . $password);
}

#

sub get_passwd {
	my ($self) = @_;

	my $password = file_get_contents($self->{RC_ROOT} . '/.shadow');
	chomp $password;

	return $password;
}

# submit a query to the socket

sub do_raw_query {
	my ($self, $query, $serial) = @_;

	my $c;
	my $data;

	# write to the socket
	print {$self->{socket}} $query . ( $serial ? ' -- ' . encode_base64(serialize($serial)) : '' ) . $EOT;

	# read the reply a byte at a time from the socket, until we get an EOT
	while ( read($self->{socket}, $c, 1) ) {
		last if $c eq $EOT;
		$data .= $c;
	}

	my $output = unserialize(decode_base64($data));

	# previous errors get overwritten
	$self->{errors} = $data->{stderr};

	return $output->{stdout};
}

#

sub do_error {
	my ($self, $msg) = @_;

	$msg =~ s/^$self->{NAK}//;

	print STDERR $msg . "\n";
}

# call do_error and exit

sub die_error {
	my ($self, $msg) = @_;

	$self->do_error($msg);

	exit(1);
}

# mysql query equiv
  
sub data_query {
	my ($self, $sql) = @_;

	my $data = $self->do_raw_query('sql ' . $sql);
	$self->{num_rows} = $data->{rows_affected};
	$self->{insert_id} = $data->{insert_id};

	return $data->{rows};
}

#

sub service_running {
	my ($self, $service) = @_;
	return $self->do_raw_query('service_running ' . $service);
}

# a function to change the current database in use

sub use_database {
	my ($self, $database) = @_;
	return $self->do_raw_query('use ' . $database);
}

# a function to change the admin password. returns true on success, false on failure
# this only checks if the $old password is correct. it's up to the code that calls this to verify
# things like password strength, length, etc.

sub passwd {
	my ($self, $old, $new) = @_;
	return $self->do_raw_query('passwd ' . $old . ' ' . $new);
}

1;
