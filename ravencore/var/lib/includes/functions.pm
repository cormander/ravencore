
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

	$dom->{sys_users} = $self->get_sys_users_by_domain_id({did => $id});

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

sub get_sys_users_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from sys_users where did = ?", [$ref->{did}]);
}

sub get_dns_recs_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from dns_rec where did = ? order by type, name, target", [$ref->{did}]);
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

sub get_mail_users {
	my ($self) = @_;

	return $self->select_ref_many("select *, m.id as mid, d.mail as mail_toggle from domains d inner join mail_users m on m.did = d.id");
}

sub get_mail_users_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select *, m.id as mid, d.mail as mail_toggle from domains d inner join mail_users m on m.did = d.id where d.id = ?", [$ref->{did}]);
}

sub get_login_failure_count_by_username {
	my ($self, $ref) = @_;

	my $lockout_time = ( $self->{CONF}{LOCKOUT_TIME} ? $self->{CONF}{LOCKOUT_TIME} : 300 );

	return $self->select_count("select count(*) from login_failure where login = ? and
			( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + ? ) >
			( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )",
		[$ref->{username}, $lockout_time]);
}

sub get_databases_by_domain_id {
	my ($self, $ref) = @_;

	return $self->select_ref_many("select * from data_bases where did = ?", [$ref->{did}]);
}

sub hosting_ssl {
	my ($self) = @_;

	return $self->select_count("select count(*) as count from domains where host_ssl = 'true'");
}

