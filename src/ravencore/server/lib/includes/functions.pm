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

	return $self->select_ref_many("select * from domains where id not in (select did from domain_ips)");
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

	return $self->select_ref_single("select * from users where login = ? limit 1", [$ref->{username}]);
}

sub get_user_by_name_and_password {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select * from users where login = ? and passwd = ? limit 1", [$ref->{username}, $ref->{password}]);
}

sub get_mail_user_by_name_and_password {
	my ($self, $ref) = @_;

	return $self->select_ref_single("select mu.*, concat(mail_name,'\@',d.name) as email from domains d inner join mail_users mu on mu.did = d.id
		where concat(mail_name,'\@',d.name) = ? and mu.passwd = ? limit 1",
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

	return $self->select_count("select count(*) from login_failure where login = ? and date > ( strftime('%s','now') - ? )",
		[$ref->{username}, $lockout_time]);
}

sub get_databases {
	my ($self) = @_;

	return $self->select_ref_many("select b.*, d.name as domain_name from data_bases b inner join domains d on b.did = d.id");
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

	$self->xsql("delete from domain_ips where did = ?", [$did]);

	if ("ARRAY" eq ref($ip_addresses)) {
		foreach my $ip (@{$ip_addresses}) {
			$self->xsql("insert into domain_ips values (?,?)", [$did, $ip]);
		}
	}

	if ("edit" eq $action) {

		return undef unless $did;

		my $suser = $self->get_sys_user_by_domain_id({did => $did});

		# only do this if we got a passwd value that is different from the current passwd
		if ($sysuser_passwd ne $suser->{passwd} or $sysuser_shell ne $suser->{shell}) {
			# Make sure someone isn't trying to change the login shell w/o permission
			#if (!user_can_add($uid, "shell_user") and !is_admin() and $suser[shell] == $CONF[DEFAULT_LOGIN_SHELL]) $_POST[login_shell] = $CONF[DEFAULT_LOGIN_SHELL];

			$self->xsql("update sys_users set passwd = ?, shell = ? where id = ?", [$sysuser_passwd, $sysuser_shell, $suser->{id}]);

			$self->rehash_ftp;
		}

		#if (user_can_add($uid, "host_php") or is_admin() or $_POST[host_php] == "false") $sql .= ", host_php = '$_POST[php]'";
		#if (user_can_add($uid, "host_cgi") or is_admin() or $_POST[host_cgi] == "false") $sql .= ", host_cgi = '$_POST[cgi]'";
		#if (user_can_add($uid, "host_ssl") or is_admin() or $_POST[host_ssl] == "false") $sql .= ", host_ssl = '$_POST[ssl]'";

		my ($ra) = $self->xsql("update domains set redirect_url = ?, www = ?, host_dir = ?,
					host_php = ?, host_cgi = ?, host_ssl = ? where id = ?",
				[$redirect_url, $www, $host_dir, $host_php, $host_cgi, $host_ssl, $did]);

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

			my ($ra, $suid) = $self->xsql("insert into sys_users (login, passwd) values (?,?)", [$sysuser_login, $sysuser_passwd]);

			$self->xsql("update domains set suid = ? where id = ?", [$suid, $did]);

			# when the rehash_ftp is fixed, we want to run it with just the new username, rather then the --all switch
			$self->rehash_ftp;

		}

		#if (user_can_add($uid, "host_php") or is_admin() or $_POST[host_php] == "false") $sql .= ", host_php = '$_POST[php]'";
		#if (user_can_add($uid, "host_cgi") or is_admin() or $_POST[host_cgi] == "false") $sql .= ", host_cgi = '$_POST[cgi]'";
		#if (user_can_add($uid, "host_ssl") or is_admin() or $_POST[host_ssl] == "false") $sql .= ", host_ssl = '$_POST[ssl]'";

		my ($ra) = $self->xsql("update domains set redirect_url = ?, www = ?, host_dir = ?,
					host_php = ?, host_cgi = ?, host_ssl = ? where id = ?",
				[$redirect_url, $www, $host_dir, $host_php, $host_cgi, $host_ssl, $did]);

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
		$self->xsql("delete from sys_users where id = ?", [$suser->{id}]);

		$self->ftp_del({login => $suser->{login}});

		$self->xsql("update domains set host_type = 'none' where id = ?", [$did]);

		$self->domain_del({name => $dom->{name}});

		return 1;
	}

	return undef;
}

sub push_domain {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $hosting = 	$ref->{hosting};
	my $dns =	$ref->{dns};
	my $name =	$ref->{name};
	my $uid =	$ref->{uid};
	my $did =	$ref->{did};

	my $ra;

	if ("delete" eq $action) {

		# delete all the email
		$self->xsql("delete from mail_users where did = ?", [$did]);

		# delete all the DNS records
		$self->xsql("delete from dns_rec where did = ?", [$did]);

		# TODO: delete databases

		# delete the domain
		$self->xsql("delete from domains where id = ?", [$did]);

		# run the nessisary system calls
		$self->rehash_named;
		$self->rehash_mail;

	}
	elsif ("hosting" eq $action) {
		($ra) = $self->xsql("update domains set hosting = ? where id = ?", [$hosting, $did]);

		$self->rehash_httpd if 0 < $ra;
	}
	elsif ("change" eq $action) {
		# TODO: only an admin can do this

		$self->xsql("update domains set uid = ? where id = ?", [$uid, $did]);
	}
	elsif ("add" eq $action) {

		# TODO: If we're not an admin, and this user doens't have permissions to add another domain,
		# redirect them back to their main page
		# if (!user_can_add($uid, "domain") and !is_admin()) { return undef }

		# Check to see that the domain isn't already setup on the server
		my $domain = $self->get_domain_by_name({name => $name});

		if ($domain->{id}) {
			return $self->do_error(_("The domain $name is already setup on this server"));
		}
		elsif(!$name) {
			return $self->do_error(_("Please enter the domain name you wish to setup"));
		} else {
			# TODO: validate that $name is a valid name for a domain
			($ra, $did) = $self->xsql("insert into domains (name, uid, created) values(?,?,strftime('%s','now'))", [$name, $uid]);

			$self->rehash_mail;

			# Copy over server default DNS to this domain, if the option was checked
			if ($dns) {
				my $recs = $self->get_default_dns_recs;

				# handle SOA record
				foreach my $rec (@{$recs}) {
					next if "SOA" ne $rec->{type};

					$self->xsql("insert into parameters (type_id, param, value) values (?,?,?)", [$did, 'soa', $rec->{target}]);
					$self->xsql("update domains set soa = ? where id = ?", [$rec->{target}, $did]);
				}

				# handle everything else
				foreach my $rec (@{$recs}) {
					next if "SOA" eq $rec->{type};

					$self->xsql("insert into dns_rec (did, name, type, target) values (?,?,?,?)", [$did, $rec->{name}, $rec->{type}, $rec->{target}]);
				}

				$self->rehash_named;
			}
		}
	}

	return 1;
}

sub push_user {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $uid = 	$ref->{uid};
	my $login =	$ref->{login};
	my $passwd =	$ref->{passwd};
	my $confirm_passwd = $ref->{confirm_passwd};
	my $name =	$ref->{name};
	my $email =	$ref->{email};

	# TODO: verify $uid and $login against session if not admin

	if ("delete" eq $action) {
		return undef unless $self->is_admin();

		# delete this users domains
		my $domains = $self->get_domains_by_user_id({uid => $uid});

		foreach my $domain (@{$domains}) {
			$self->push_domain({
				action => "delete",
				did => $domain->{id},
			});
		}

		# remove this users permissions
		$self->xsql("delete from user_permissions where uid = ?", [$uid]);

		# get rid of the user
                $self->xsql("delete from users where id = ?", [$uid]);
	}
	elsif ("add" eq $action or "edit" eq $action) {
		# input sanity checks
		if (!$name) {
			return $self->do_error(_("You must enter a name for this user"));
		}
		elsif (!$name) {
			return $self->do_error(_("You must enter a name for this user"));
		}
		elsif ("add" eq $action and !$login) {
			return $self->do_error(_("You must enter a login for this user"));
		}
		elsif (!$passwd) {
			return $self->do_error(_("You must enter a password for this user"));
		}
		elsif ($confirm_passwd ne $passwd){
			return $self->do_error(_("Your passwords do not match"));
		}
# TODO: RavenCore::Common needs a valid_passwd function
#		elsif (!valid_passwd($passwd)) {
#			return $self->do_error(_("Your password must be atleast 5 characters long, and not a dictionary word."));
#		}
# TODO: need an is_email function
#		elsif (!is_email($email)) {
#			return $self->do_error(_("The email address entered is invalid"));
#		}
		elsif ($login eq $self->{ADMIN_USER}) {
			return $self->do_error(_("$login is not a valid user name"));
		}

		my $user = $self->get_user_by_name({username => $login});

		if ("add" eq $action) {
			return undef unless $self->is_admin();

			# The procedue to add a user. First check to see if the login provided is already in use
			return $self->do_error(_("The user login $login already exists")) if $user->{login};

			$self->xsql("insert into users (created, name, email, login, passwd) values (?,?,?,?,?)", ['now', $name, $email, $login, $passwd]);
		}
		elsif ("edit" eq $action) {
			$self->xsql("update users set name = ?, email = ?, passwd = ? where id = ?", [$name, $email, $passwd, $uid]);
		}
	}
	elsif ("unlock" eq $action) {
		return undef unless $self->is_admin();
	        $self->xsql("delete from login_failure where login = ?", [$login]);
	}

	return 1;
}

sub push_mail {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $did =	$ref->{did};
	my $mid =	$ref->{mid};
	my $mail =	$ref->{mail};
	my $catchall =	$ref->{catchall};	
	my $relay_host = $ref->{relay_host};
	my $alias_addr = $ref->{alias_addr};
	my $catchall_addr = $ref->{catchall_addr};
	my $bounce_message = $ref->{bounce_message};

	my $ra;

	if ("update" eq $action) {

		if ("send_to" eq $catchall) {
			return $self->do_error("Invalid email address for catchall") unless is_email($catchall_addr);
		}
		elsif ("relay" eq $catchall) {
			# Examples of allowable input for relay_host:
			#   ravencore.com
			#   ravencore.com:2525
			#   [ravencore.com]
			#   [ravencore.com]:2525
			#   192.168.1.115
			#   192.168.1.115:2525
			# A domain name in [ ] means force MX host lookup

			my $error = 1;

			$error = 0 if $relay_host =~ /\[?([a-zA-Z\d]+((\.||\-)[a-zA-Z\d]?)?)*[a-zA-Z\d]\.[a-zA-Z]+\]?(:\d*)?/;
			$error = 0 if is_ip($relay_host);

			return $self->do_error("Relay to must be a hostname or IP address") if $error;
		}

		($ra) = $self->xsql("update domains set catchall = ?, catchall_addr = ?, bounce_message = ?, relay_host = ?, alias_addr = ? where id = ?",
			[$catchall, $catchall_addr, $bounce_message, $relay_host, $alias_addr, $did]);

	}
	elsif ("delete" eq $action) {
		($ra) = $self->xsql("delete from mail_users where did = ? and id = ?", [$did, $mid]);
	}
	elsif ("toggle" eq $action) {
		($ra) = $self->xsql("update domains set mail = ? where id = ?", [$mail, $did]);
	}

	$self->rehash_mail if 0 < $ra;

	return 1;
}

sub push_mail_user {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $did =	$ref->{did};
	my $mid =	$ref->{mid};
	my $passwd =	$ref->{passwd};
	my $confirm_passwd = $ref->{confirm_passwd};
	my $redirect =	$ref->{redirect};
	my $redirect_addr = $ref->{redirect_addr};
	my $autoreply = $ref->{autoreply};
	my $autoreply_subject = $ref->{autoreply_subject};
	my $autoreply_body = $ref->{autoreply_body};
	my $mailbox = $ref->{mailbox};
	my $spam_folder = $ref->{spam_folder};

	my $mails = $self->get_mail_user_by_name_and_domain_id({name => $name, did => $did});

	if (0 != scalar($mails) and "add" eq $action) {
		return $self->do_error("That email address already exists");
	}

	# make sure that the passwords match
	return $self->do_error("Your passwords do not match") if $passwd ne $confirm_passwd;

	# make sure it's a valid password
	return $self->do_error("Invalid password") if !is_ok_password($passwd);

	# make sure the given mailname is valid
	return $self->do_error("Invalid mailname") if !is_username($name);

	# sanity check redirect
	if ($redirect) {
		return $self->do_error("You selected you wanted a redirect, but left the address blank") unless $redirect_addr;
	
		$redirect_addr =~ s/\w//g;

		map { return $self->do_error("The redirect list contains an invalid email address") if !is_email($_) } split /,/, $redirect_addr;
	}

	my $ra;

	if ("add" eq $action) {
		($ra, $mid) = $self->xsql("insert into mail_users (mail_name, did, passwd, spam_folder, mailbox, redirect, redirect_addr, autoreply, autoreply_subject, autoreply_body)" .
			" values (?,?,?,?,?,?,?,?,?,?)",
			[$name, $did, $passwd, $spam_folder, $mailbox, $redirect, $redirect_addr, $autoreply, $autoreply_subject, $autoreply_body]);
	} else {
		($ra) = $self->xsql("update mail_users set passwd = ?, mailbox = ?, spam_folder = ?, redirect = ?, redirect_addr = ?, autoreply = ?, autoreply_subject = ?, autoreply_body = ?" .
			" where id = ?",
			[$passwd, $mailbox, $spam_folder, $redirect, $redirect_addr, $autoreply, $autoreply_subject, $autoreply_body, $mid]);
	}

	$self->rehash_mail if $ra > 0;

	return 1;
}

sub push_dns_rec {
	my ($self, $ref) = @_;

	my $action =	$ref->{action};
	my $did =	$ref->{did};
	my $name =	$ref->{name};
	my $type =	$ref->{type};
	my $target =	$ref->{target};
	my $preference = $ref->{referenc};

	my $domain = $self->get_domain_by_id({id => $did});

	return $self->do_error("Domain with id %s does not exist", $did) unless scalar keys %{$domain};

	my $recs = $self->get_dns_recs_by_domain_id({did => $did});

	my $count = 0;

	foreach my $rec (@{$recs}) {
		if ($rec->{name} eq $name and $rec->{type} eq $type and $rec->{target} eq $target) {
			$count = 1;
		}
	}

	#
	# sanity checks on new record
	#

	if (0 != $count) {
		return $self->do_error("You already have a %s record for %s pointing to %s", $type, $name, $target);
	}

	if ($name eq $target and "MX" ne $type) {
		return $self->do_error("Your record name and target cannot be the same")
	}

	if (is_ip($target) and ("SOA" eq $type or "MX" eq $type or "CNAME" eq $type)) {
		return $self->do_error("A %s record can not point to an IP address");
	}

	if ($name =~ /\.$/) {
		return $self->do_error("You can not enter in a full domain as the record name");
	}

	if ("MX" eq $type and !$preference) {
		return $self->do_error("MX records must have a preference");
	}

	#
	# if we get here, we're good to create the record
	#

	# this domain is aliased as "@" in bind
	if ($domain->{name} eq $name) {
		$name = '@';
	}

	if ($domain->{name} eq $target) {
		$arget = '@';
	}

	# MX records have preference
	if ("MX" eq $type) {
		$type .= '-' . $preference;
	}

	my $ra;
	my $rid;

	# start of authority records are stored in the domains table
	if ("SOA" eq $type) {
		($ra) = $self->xsql("update domains set soa = ? where id = ?", [$target, $did]);
	} else {
		($ra, $rid) = $self->xsql("insert into dns_rec (did, name, type, target) values (?,?,?,?)", [$did, $name, $type, $target]);
	}

	$self->rehash_named if $ra > 0;

	return 1;
}
