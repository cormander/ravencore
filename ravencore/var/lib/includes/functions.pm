
sub get_ip_addresses {
	my ($self) = @_;

	my $sth;
	my $ip_info = [];

	$sth = $self->{dbi}->prepare("select * from ip_addresses order by ip_address");

	$sth->execute;

	while (my $row = $sth->fetchrow_hashref) {
		push @{$ip_info}, $row;
	}

	$sth->finish;

	return $ip_info;
}

sub get_ip_info_by_ip {
	my ($self, $ip) = @_;

	my $sth;
	my $ip_info;

	$ip_info = $self->{dbi}->selectrow_hashref("select * from ip_addresses where ip_address = ?", undef, $ip);

	return undef unless $ip_info->{ip_address} eq $ip;

	return $ip_info;
}

sub get_domain_by_id {
	my ($self, $id) = @_;

	my $sth;
	my $dom;

	$dom = $self->{dbi}->selectrow_hashref("select * from domains where id = ?", undef, $id);

	return undef unless $dom->{id} eq $id;

	$dom->{sys_users} = $self->get_sys_users_by_domain_id($id);

	return $dom;
}

sub get_domains_by_ip {
	my ($self, $ip) = @_;

	my $sth;
	my $domains = [];

	$sth = $self->{dbi}->prepare("select d.* from domains d inner join domain_ips i on d.id = i.did where i.ip_address = ?");

	$sth->execute($ip);

	while (my $row = $sth->fetchrow_hashref) {
		push @{$domains}, $row;
	}

	$sth->finish;

	return $domains;
}

sub get_domains_with_no_ip {
	my ($self) = @_;

	my $sth;
	my $domains = [];

	$sth = $self->{dbi}->prepare("select id from domains where id not in (select did from domain_ips)");

	$sth->execute;

	while (my ($id) = $sth->fetchrow_array) {
		push @{$domains}, $self->get_domain_by_id($id);
	}

	$sth->finish;

	return $domains;
}

sub get_domains_where_webmail_true {
	my ($self) = @_;

	my $sth;
	my $domains = [];

	$sth = $self->{dbi}->prepare("select id from domains where webmail = 'yes'");

	$sth->execute;

	while (my ($id) = $sth->fetchrow_array) {
		push @{$domains}, $self->get_domain_by_id($id);
	}

	$sth->finish;

	return $domains;
}

sub get_sys_users_by_domain_id {
	my ($self, $did) = @_;

	my $sth;
	my $sys_users = [];

	$sth = $self->{dbi}->prepare("select * from sys_users where did = ?");

	$sth->execute($did);

	while (my $row = $sth->fetchrow_hashref) {
		push @{$sys_users}, $row;
	}

	$sth->finish;

	return $sys_users;
}

sub get_user_by_name_and_password {
	my ($self, $username, $password) = @_;

	my $sth;
	my $user;

	$user = $self->{dbi}->selectrow_hashref("select * from users where binary(login) = ? and binary(passwd) = ? limit 1", undef, $username, $password);

	return undef unless $user->{login} eq $username;

	return $user;
}

sub get_mail_user_by_name_and_password {
	my ($self, $username, $password) = @_;

	my $sth;
	my $user;

	$user = $self->{dbi}->selectrow_hashref("select mu.*, concat(mail_name,'\@',d.name) as email from domains d inner join mail_users mu on mu.did = d.id
		where concat(mail_name,'\@',d.name) = ? and binary(mu.passwd) = ? limit 1",
		undef, $username, $password);

	return undef unless $user->{email} eq $username;

	return $user;
}

sub get_login_failure_count_by_username {
	my ($self, $username) = @_;

	my $sth;
	my $lockout_time = ( $self->{CONF}{LOCKOUT_TIME} ? $self->{CONF}{LOCKOUT_TIME} : 300 );

	return ($self->{dbi}->selectrow_array("select count(*) from login_failure where login = ? and
			( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + ? ) >
			( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )", undef, $username, $lockout_time))[0];
}

sub hosting_ssl {
	my ($self) = @_;

	return ($self->{dbi}->selectrow_array("select count(*) as count from domains where host_ssl = 'true'"))[0];
}

