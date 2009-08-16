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
# ravencore's database functions
#

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

			if($self->admin_passwd =~ m/^ravencore$/i) { $self->{initial_passwd} = 1 }
			else { $self->{initial_passwd} = 0 }

			$self->get_db_conf;

			return;
		}

		# if we get here, we have lost database connection...
		$self->{db_connected} = 0;

	}

	# read in our database password. currently we have to do this each time we connect, because the password
	# might have changed since we first started, and a child process can't change a variable and have the
	# parent process know about it

	my $passwd = $self->admin_passwd;

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

# read in our settings from the database

sub get_db_conf {
	my ($self) = @_;

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

# do a database SQL statement

sub sql {
	my ($self, $input) = @_;

	my $data = {
		rows_affected => undef,
		insert_id => undef,
		rows => [],
	};

	my $query = $input->{query};

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

