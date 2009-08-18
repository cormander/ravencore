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

