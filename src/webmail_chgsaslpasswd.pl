#!/usr/bin/perl 

BEGIN {
	unshift @INC, '/usr/local/ravencore/var/lib';
};

use RavenCore::Client;

my $rc = new RavenCore::Client;

my $user = $ARGV[0];
my $old_pass = $ARGV[1];
my $new_pass = $ARGV[2];

my $resp = $rc->auth('', '127.0.0.1', $user, $old_pass);

if ($resp ne "1") {
	print "Login Failed.\n";
	exit;
}

my $result = $rc->data_query("select mu.id from domains d inner join mail_users mu on mu.did = d.id where concat(mail_name,'\@',name) = '$user'");

if ($result->{0}{id}) {
	$rc->data_query("update mail_users set passwd = '" . $new_pass . "' where id = " . $result->{0}{id});
	$rc->do_raw_query("rehash_mail");
}

