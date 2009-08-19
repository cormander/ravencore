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
# Below is a list of functions to get information. They all should really be
# self-explanitory by their name, and really only return one sql query.
#
# See database.pm for the select functions used here
#

sub get_ip_addresses {
	my ($self) = @_;

	return $self->select_ref_many("select * from ip_addresses order by ip_address");
}

sub get_ip_addresses_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from ip_addresses i inner join domain_ips di on di.ip_address = i.ip_address where di.did = ?", [$ref->{did}]);
}

sub get_ip_info_by_ip {
	my ($self, $ip) = @_;

	return $self->select_ref_single("select * from ip_addresses where ip_address = ?", [$ip]);
}

sub get_domain_by_id {
	my ($self, $ref) = @_;

	my $id = $ref->{id};

	my $dom = $self->select_ref_single("select * from domains where id = ?", [$id]);

	$dom->{sys_user} = $self->get_sys_user_by_domain_id({did => $id});

	return $dom;
}

sub get_domains_by_user_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from domains where uid = ?", [$ref->{uid}]);
}

sub get_domains {
	my ($self) = @_;

	return $self->select_ref_many("select * from domains");
}

sub get_domains_by_ip {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select d.* from domains d inner join domain_ips i on d.id = i.did where i.ip_address = ?", [$ref->{ip}]);
}

sub get_domains_with_no_ip {
	my ($self) = @_;

	return $self->select_ref_many("select id from domains where id not in (select did from domain_ips)");
}

sub get_sys_users {
	my ($self) = @_;

	return $self->select_ref_many("select * from sys_users su inner join domains d on d.suid = su.id");
}

sub get_sys_user_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from sys_users su inner join domains d on d.suid = su.id where d.id = ?", [$ref->{did}]);
}

sub get_sys_users_by_user_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from sys_users su inner join domains d on su.did = d.id where d.uid = ?", [$ref->{uid}]);
}

sub get_sys_user_by_name {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from sys_users where login = ?", [$ref->{name}]);
}

sub get_dns_recs_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from dns_rec where did = ? order by type, name, target", [$ref->{did}]);
}

sub get_dns_recs_by_user_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from dns_rec dr inner join domains d on d.id = dr.did where d.uid = ? order by type, name, target", [$ref->{uid}]);
}

sub get_default_dns_recs {
	my ($self) = @_;

	return $self->select_ref_many("select * from dns_def");
}

sub get_users {
	my ($self) = @_;

	return $self->select_ref_many("select * from users");
}

sub get_user_by_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from users where id = ? limit 1", [$ref->{id}]);
}

sub get_user_by_name {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from users where binary(login) = ? limit 1", [$ref->{username}]);
}

sub get_user_by_name_and_password {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from users where binary(login) = ? and binary(passwd) = ? limit 1", [$ref->{username}, $ref->{password}]);
}

sub get_mail_user_by_name_and_password {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select mu.*, concat(mail_name,'\@',d.name) as email from domains d inner join mail_users mu on mu.did = d.id
		where concat(mail_name,'\@',d.name) = ? and binary(mu.passwd) = ? limit 1",
		[$ref->{username}, $ref->{password}]);
}

sub get_mail_user_by_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select mu.*, concat(mail_name,'\@',d.name) as email from domains d inner join mail_users mu on mu.did = d.id
		where mu.id = ?", [$ref->{id}]);
}

sub get_mail_users {
	my ($self) = @_;

	return $self->select_ref_many("select *, m.id as mid, d.mail as mail_toggle from domains d inner join mail_users m on m.did = d.id");
}

sub get_mail_users_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select *, m.id as mid, d.mail as mail_toggle from domains d inner join mail_users m on m.did = d.id where d.id = ?", [$ref->{did}]);
}

sub get_mail_users_by_user_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select *, m.id as mid, d.mail as mail_toggle from domains d inner join mail_users m on m.did = d.id
		inner join users u on d.uid = u.id where u.id = ?", [$ref->{uid}]);
}

sub get_mail_user_by_name_and_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select mu.*, concat(mail_name,'\@',d.name) as email from domains d inner join mail_users mu on mu.did = d.id
		where mu.mail_name = ? and d.id = ?", [$ref->{name}, $ref->{did}]);
}

sub get_login_failure_count_by_username {
	my ($self, $ref) = @_;

	my $lockout_time = ( $self->{CONF}{LOCKOUT_TIME} ? $self->{CONF}{LOCKOUT_TIME} : 300 );

	return $self->select_count("select count(*) from login_failure where login = ? and
			( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + ? ) >
			( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )",
		[$ref->{username}, $lockout_time]);
}

sub get_databases {
	my ($self) = @_;

	return $self->select_ref_many("select b.*, d.name as domain_name from data_bases d inner join domains d on b.did = d.id");
}

sub get_database_by_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from data_bases where id = ?", [$ref->{id}]);
}

sub get_databases_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from data_bases where did = ?", [$ref->{did}]);
}

sub get_databases_by_user_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from data_bases b inner join domains d on b.did = d.id and uid = ?", [$ref->{uid}]);
}

sub get_database_user_by_id {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from data_base_users where id = ?", [$ref->{id}]);
}

sub get_database_user_by_name {
        my ($self, $ref) = @_;

	return $self->select_ref_single("select * from data_base_users where login = ?", [$ref->{name}]);
}

sub get_database_users_by_database_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from data_base_users where db_id = ?", [$ref->{id}]);
}

sub get_permission_by_user_id_and_perm {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from user_permissions where uid = ? and perm = ?", [$ref->{uid}, $ref->{perm}]);
}

sub hosting_ssl {
	my ($self) = @_;

	return $self->select_count("select count(*) as count from domains where host_ssl = 'true'");
}

#
# Below is a list of functions to push information to the database, do
# actions, etc. Think of "push" as the opposite of "get".
#

sub push_hosting {
	my ($self, $ref) = @_;

	my $action =		$ref->{action};
	my $ip_addresses =	$ref->{ip_addresses};
	my $sysuser_login =	$ref->{sysuser_login};
	my $sysuser_passwd =	$ref->{sysuser_passwd};
	my $sysuser_shell =	$ref->{sysuser_shell};
	my $host_type =		$ref->{host_type};
	my $redirect_url =	$ref->{redirect_url};
	my $www =		$ref->{www};
	my $host_php =		$ref->{host_php};
	my $host_cgi =		$ref->{host_cgi};
	my $host_ssl =		$ref->{host_ssl};
	my $host_dir =		$ref->{host_dir};
	my $did	=		$ref->{did};

	$self->{dbi}->do("delete from domain_ips where did = ?", undef, $did);

	if ("HASH" eq ref($ip_addresses)) {
		foreach my $key (keys %{$ip_addresses}) {
			my $val = $ip_addresses->{$key};
			$self->{dbi}->do("insert into domain_ips values (?,?)", undef, $did, $val);
		}
	}

	if ("edit" eq $action) {

		return undef unless $did;

		my $suser = $self->get_sys_user_by_domain_id({did => $did});

		# only do this if we got a passwd value that is different from the current passwd
		if ($sysuser_passwd ne $suser->{passwd} or $sysuser_shell ne $suser->{shell}) {
			# Make sure someone isn't trying to change the login shell w/o permission
			#if (!user_can_add($uid, "shell_user") and !is_admin() and $suser[shell] == $CONF[DEFAULT_LOGIN_SHELL]) $_POST[login_shell] = $CONF[DEFAULT_LOGIN_SHELL];

			$self->{dbi}->do("update sys_users set passwd = ?, shell = ? where id = ?", undef, $sysuser_passwd, $sysuser_shell, $suser->{id});

			$self->rehash_ftp;
		}

		#if (user_can_add($uid, "host_php") or is_admin() or $_POST[host_php] == "false") $sql .= ", host_php = '$_POST[php]'";
		#if (user_can_add($uid, "host_cgi") or is_admin() or $_POST[host_cgi] == "false") $sql .= ", host_cgi = '$_POST[cgi]'";
		#if (user_can_add($uid, "host_ssl") or is_admin() or $_POST[host_ssl] == "false") $sql .= ", host_ssl = '$_POST[ssl]'";

		my $ra = $self->{dbi}->do("update domains set redirect_url = ?, www = ?, host_dir = ?,
					host_php = ?, host_cgi = ?, host_ssl = ? where id = ?",
				undef, $redirect_url, $www, $host_dir, $host_php, $host_cgi, $host_ssl, $did);

		# only mess with the filesystem if we affected the db
		$self->rehash_httpd if 0 < $ra;

		return 1;
	}
	elsif ("add" eq $action) {

		if ("physical" eq $host_type) {

			# get sys_users setup in db
			my $suser = $self->get_sys_user_by_name({name => $sysuser_login});

			# open up our /etc/passwd file, and input only the usernames
			my @users = split /\n/, `cat /etc/passwd | awk -F : '{print \$1}'`;

			# make sure we don't already have the user setup in the database or in the system
			# this prevents people from creating their ftp user as root or something
			return $self->do_error("You cannot create a FTP user with the login $sysuser_login") if (in_array($sysuser_login, @users));

			$self->{dbi}->do("insert into sys_users set login = ?, passwd = ?", undef, $sysuser_login, $sysuser_passwd);

			my $suid = $self->{dbi}->{ q{mysql_insertid} };

			$self->{dbi}->do("update domains set suid = ? where id = ?", undef, $suid, $did);

			# when the rehash_ftp is fixed, we want to run it with just the new username, rather then the --all switch
			$self->rehash_ftp;

		}

		#if (user_can_add($uid, "host_php") or is_admin() or $_POST[host_php] == "false") $sql .= ", host_php = '$_POST[php]'";
		#if (user_can_add($uid, "host_cgi") or is_admin() or $_POST[host_cgi] == "false") $sql .= ", host_cgi = '$_POST[cgi]'";
		#if (user_can_add($uid, "host_ssl") or is_admin() or $_POST[host_ssl] == "false") $sql .= ", host_ssl = '$_POST[ssl]'";

		my $ra = $self->{dbi}->do("update domains set redirect_url = ?, www = ?, host_dir = ?,
					host_php = ?, host_cgi = ?, host_ssl = ? where id = ?",
				undef, $redirect_url, $www, $host_dir, $host_php, $host_cgi, $host_ssl, $did);

		# only mess with the filesystem if we affected the db
		if (0 < $ra) {
			$self->rehash_httpd;
			$self->rehash_logrotate;
		}

		return 1;
	}
	elsif ("delete" eq $action) {

		my $suser = $self->get_sys_user_by_domain_id({did => $did});
		my $dom = $self->get_domain_by_id({id => $did});

		# delete all the system users for this domain
		$self->{dbi}->do("delete from sys_users where id = ?", undef, $suser->{id});

		$self->ftp_del({login => $suser->{login}});

		$self->{dbi}->do("update domains set host_type = 'none' where id = ?", undef, $did);

		$self->domain_del({name => $dom->{name}});

		return 1;
	}

	return undef;
}

sub push_domain {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $hosting = 	$ref->{hosting};
	my $uid =	$ref->{uid};
	my $did =	$ref->{did};

	if ("delete" eq $action) {

		# delete all the email
		$self->{dbi}->do("delete from mail_users where did = ?", undef, $did);

		# delete all the DNS records
		$self->{dbi}->do("delete from dns_rec where did = ?", undef, $did);

		# TODO: delete databases

		# delete the domain
		$self->{dbi}->do("delete from domains where id = ?", undef, $did);

		# run the nessisary system calls
		$self->rehash_named;
		$self->rehash_mail;

	}
	elsif ("hosting" eq $action) {
		my $ra = $self->{dbi}->do("update domains set hosting = ? where id = ?", undef, $hosting, $did);

		$self->rehash_httpd if 0 < $ra;
	}
	elsif ("change" eq $action) {
		# TODO: only an admin can do this

		$self->{dbi}->do("update domains set uid = ? where id = ?", undef, $uid, $did);
	}

	return 1;
}

sub push_user {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $uid = 	$ref->{uid};
	my $login =	$ref->{login};

	# TODO: Only an admin can call this function

	if ("delete" eq $action) {
		# delete this users domains
		my $domains = $self->get_domains_by_user_id({uid => $uid});

		foreach my $domain (@{$domains}) {
			$self->push_domain({
				action => "delete",
				did => $domain->{id},
			});
		}

		# remove this users permissions
		$self->{dbi}->do("delete from user_permissions where uid = ?", undef, $uid);

		# get rid of the user
                $self->{dbi}->do("delete from users where id = ?", undef, $uid);
	}
	elsif ("unlock" eq $action) {
	        $self->{dbi}->do("delete from login_failure where login = ?", undef, $login);
	}

	return 1;
}

