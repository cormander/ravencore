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
# This object is used to read in the system files that define users and groups (passwd, shadow, group, gshadow)
# and provide functions to add / edit / delete entries from them. It does file integrety checks, auto-correction
# when it can, and all the normal sanity checks the system useradd / usermod / etc commands would, with some
# additional features added.
#
# The purpose of this object is so that we don't have to figure out what the command names of all *nix systems
# are, what options they support, and how to call them. Instead we just get right down to business and edit the
# system files directly.
#
# This is coded as an object so that we can load in everything, store it in memory, do all of our changes, and 
# commit them at once. This way we don't have to open / read / check / write / close each file for each user we
# want to edit, so we can maintain optimal speed even on systems with thousands of system users.
#

# TODO: as-is, this object will die a horrible death if there are users or groups with the same uid/gid.. 
#       ( see the item_map_add function ). fix this

# TODO: if the user's name or uid is being changed, or they're being deleted, make sure crontab, vhost root, etc
#       reflect that change and search for and kill any processes running as that user, and force them to logout

# TODO: do the same checks as the pwck command:
#       * in each case, send an error to syslog
#       - a unique user name
#         if not, keep the one with the smaller uid. if they're the same, just remove one and dump it to passwd.corrupted
#       - a valid user and group identifier
#         if its a neg uid, make it posotive. if that uid is in use, assign it the next available one
#         if the group doesn't exist, put the user in the group named the same as the user. if it doesn't exist, create it

#
# NOTE -
#
# This module has a few limitions, nothing too bad I hope. For one, you can't have more than one user with the same
# UID, or more than one group with the same GID. If you do, you'll end up with only one user / group for each UID / GID,
# with the others simply gone. You might even have wierd things happen like a "mixed" user with the name from one and
# a shell or home_dir from the other (of the same UID).. I don't know, haven't tested that :)
#
# This supposedly works on BSD systems, though you might have trouble with the "class" section if you use it. Needs more
# testing.
#

package RavenCore::Shadow;

# TODO: finish implementing syslog logging of errors
use Sys::Syslog;
use RavenCore::Common;

# as we create the class, read in all of our system configuration files for user creation, and read in our
# passwd, shadow, group, and gshadow files. Then, do some auto-correction if possible

sub new {
	# inherit the classname from the above package statement
	my ($class, $ostype) = @_;

	# define our $self variable so we can bless it
	my $self = {
		shells => '/etc/shells',
		login_defs => '/etc/login.defs',
		useradd => '/etc/default/useradd',
		bin_false => '/bin/false',
		rebuild_shadows => 0,
	};

	# bind the class name to this object
	bless $self, $class;

	# define our user and group database files, and what they should contain
	# bsd and linux differ a little

	$self->{ostype} = $ostype;

	if($ostype eq 'bsd') {
		$self->{userdb}{passwd}{file} = '/etc/master.passwd';
		@{$self->{userdb}{passwd}{order}} = ('user','shadow_passwd','uid','gid','class','last_change','expire','gecos','home_dir','shell');

		$self->{groupdb}{group}{file} = '/etc/group';
		@{$self->{groupdb}{group}{order}} = ('name','passwd','gid','user_list');

		$self->{enc_char} = '*';
	} else {
		$self->{userdb}{passwd}{file} = '/etc/passwd';
		@{$self->{userdb}{passwd}{order}} = ('user','passwd','uid','gid','gecos','home_dir','shell');

		$self->{userdb}{shadow}{file} = '/etc/shadow';
		@{$self->{userdb}{shadow}{order}} = ('user','shadow_passwd','last_change','min_change','max_change','warn','inact','expire','reserved');

		$self->{groupdb}{group}{file} = '/etc/group';
		@{$self->{groupdb}{group}{order}} = ('name','passwd','gid','user_list');

		$self->{groupdb}{gshadow}{file} = '/etc/gshadow';
		@{$self->{groupdb}{gshadow}{order}} = ('name','passwd','no_idea','user_list');

		$self->{enc_char} = 'x';

	}

# set up logging, using the LOG_AUTHPRIV facility - security/authorization messages (private)
# when we commit a log statement, use one of the following:
# * LOG_EMERG - system is unusable
# * LOG_ALERT - action must be taken immediately
# * LOG_CRIT - critical conditions
# * LOG_ERR - error conditions
# * LOG_WARNING - warning conditions
# * LOG_NOTICE - normal, but significant, condition
# * LOG_INFO - informational message
# * LOG_DEBUG - debug-level message

	openlog('rcshadow','pid','LOG_AUTHPRIV');

	# TODO: check for the existance of each of the system files we reference in this object, and if one doesn't exist,
	#       then exit with error

	# set our login.defs defaults, just incase we don't find a login.defs file below

	$self->{UID_MIN} = 500;
	$self->{UID_MAX} = 90000;
	$self->{GID_MIN} = 500;
	$self->{GID_MAX} = 90000;
	$self->{PASS_MAX_DAYS} = 99999;
	$self->{PASS_MIN_DAYS} = 0;
	$self->{PASS_MIN_LEN} = 5;
	$self->{PASS_WARN_AGE} = 7;

	# look in the login.defs file so we can honor its values when adding a new user / group

	if( -f $self->{login_defs} ) {

		my @file = file_get_array($self->{login_defs});

		foreach (@file) {
			# these regular expressions return true if the match was successful, in which case, $1 is set to the content we want

			if ($_ =~ s/^UID_MIN\W*(\d*)$/$1/) { $self->{UID_MIN} = $1; }
			if ($_ =~ s/^UID_MAX\W*(\d*)$/$1/) { $self->{UID_MAX} = $1; }
			if ($_ =~ s/^GID_MIN\W*(\d*)$/$1/) { $self->{GID_MIN} = $1; }
			if ($_ =~ s/^GID_MAX\W*(\d*)$/$1/) { $self->{GID_MAX} = $1; }

			if ($_ =~ s/^PASS_MAX_DAYS\W*(\d*)$/$1/) { $self->{PASS_MAX_DAYS} = $1; }
			if ($_ =~ s/^PASS_MIN_DAYS\W*(\d*)$/$1/) { $self->{PASS_MIN_DAYS} = $1; }
			if ($_ =~ s/^PASS_MIN_LEN\W*(\d*)$/$1/) { $self->{PASS_MIN_LEN} = $1; }
			if ($_ =~ s/^PASS_WARN_AGE\W*(\d*)$/$1/) { $self->{PASS_WARN_AGE} = $1; }

		}

	}

	# check for and load our useradd defaults

	my @file = file_get_array($self->{useradd});

	foreach (@file) {
		if ( $_ =~ s/^EXPIRE=(.*)$/$1/) { $self->{EXPIRE} = $1; }
		# assume -1 is empty with the INACTIVE config
		if ( $_ =~ s/^INACTIVE=(.*)$/$1/) { $self->{INACTIVE} = $1 unless $1 == -1; }

	}

	# some systems don't have a useradd file.. but that's OK, we don't really NEED them.
	# open up /etc/shells and get a list of valid shells so we can validate user's shells.
	my $bin_false = 0;

	@{$self->{valid_shells}} = file_get_array($self->{shells});

	# remove newline characters, and check to see if /bin/false is a valid shell

	foreach (@{$self->{valid_shells}}) {
		$bin_false = 1 unless $_ ne $self->{bin_false};
	}

	# if /bin/false is not a valid shell, then add it

	if ($bin_false == 0) {
		push @{$self->{valid_shells}}, $self->{bin_false};

		file_append($self->{shells}, $self->{bin_false} . "\n");

		# tell syslog that we added /bin/false to the list of shells
		syslog('LOG_NOTICE',"Added " . $self->{bin_false} . " to " . $self->{shells});
	}

	# TODO: run sanity checks against all fields read in from the passwd and shadow files

	# get info from the userdb file(s) and store it in $self->{user}

	foreach my $userdb (keys %{$self->{userdb}}) {

		my @file = file_get_array($self->{userdb}{$userdb}{file});

		foreach (@file) {
			next if /^#/;

			my @args = split /:/;

			# username is always the first one
			my $login = $args[0];

			# count the number of elements in order
			my $c = @{$self->{userdb}{$userdb}{order}};
			my $i;

			# walk down the order
			for ($i = 0; $i < $c; $i++) {
				$self->{user}{$login}{$self->{userdb}{$userdb}{order}[$i]} = $args[$i];
			}

		}

	}

	# confirm the login shell and add every user to the item_maps
	foreach my $login (keys %{$self->{user}}) {
		# check to make sure this is a valid shell
		$self->{user}{$login}{'shell'} = $self->confirm_shell($self->{user}{$login}{'shell'}, $self->{user}{$login}{'uid'});
	 
		# add this user to an array by UID so we can map the uid to the name
		$self->item_map_add('user',$self->{user}{$login}{'uid'},$login);
	}

# group file
#       group name
#       group password
#       numeric ID of group
#       comma separted list of users in the group


	# get info from the groupdb file(s) and store it in $self->{group}
	foreach my $groupdb (keys %{$self->{groupdb}}) {

		my @file = file_get_array($self->{groupdb}{$groupdb}{file});

		foreach (@file) {
			next if /^#/;

			my @args = split /:/;

			# group is always the first one
			my $group = $args[0];

			# count the number of elements in order
			my $c = @{$self->{groupdb}{$groupdb}{order}};
			my $i;

			# walk down the order
			for ($i = 0; $i < $c; $i++) {
				if ('user_list' eq $self->{groupdb}{$groupdb}{order}[$i]) {
					@{$self->{group}{$group}{$self->{groupdb}{$groupdb}{order}[$i]}} = split /,/, $args[$i];
				} else {
					$self->{group}{$group}{$self->{groupdb}{$groupdb}{order}[$i]} = $args[$i];
				}
			}

		}

	}

	# add this group to an array by GID so we can map the gid to the name
	foreach my $group (keys %{$self->{group}}) {
		$self->item_map_add('group',$self->{group}{$group}{'gid'},$group);
	}

	#
	# Now that we have all our users and groups in memory, we can do some additional checks on them
	#    

	#
	# group checks: loop through the groups and do error checking, and auto-correct when appropriate
	#

	foreach my $name (%{$self->{groupitem_map}}) {
		# check to see if this GID is below the MAX allowed GID
		if ($self->{group}{$name}{'gid'} > $self->{GID_MAX}) {
			# look for the lowest available UID and automatically assign it to this user
			$self->assign_id('group',$name);
		}

		my $c = 0;

		# make sure each element in user_list is a valid username
		foreach my $user (@{$self->{group}{$name}{'user_list'}}) {
			if (!$self->valid_name($user)) {
				# remove it
				splice @{$self->{group}{$name}{'user_list'}}, $c;
			}
			$c++;
		}
	}

	#
	# user checks: loop through the users and do checking on them
	#

	foreach my $login (%{$self->{useritem_map}}) {

		# check to see if this UID is below the MAX allowed UID

		if ($self->{user}{$login}{'uid'} > $self->{UID_MAX}) {
			# look for the lowest available UID and automatically assign it to this user
			$self->assign_id('user',$login);
		}

		# check to see if this is a valid GID here

		if ( ! $self->item_exists('group',$self->item_map_name('group',$self->{user}{$login}{'gid'})) ) {

			# don't worry about printing an error out, for now
			#	    print "ERROR: invalid gid " . $self->{user}{$login}{'gid'} . ", group does not exist\n";

			# if not a valid gid, then check to see if a group named the same as this users exists, if so, change the gid to it
			if ($self->item_exists('group',$login)) {
				# TODO: log this appropriatly
				$self->{user}{$login}{'gid'} = $self->{group}{$login}{'gid'};
				$self->flag_rebuild;
			} else {

				# otherwise, a group was deleted manually.... I'd like to add a group named this user and add this user to the group...
				# but if we get an error adding the group (and we probably will) holy crap the system is having problems and this will
				# croak, so for now, do nothing
				# TODO: if $uid as a $gid is not in use, assign this new group that id
				#		$self->add_group($login);

			}

		}

	}

	# TODO: make sure that we have at least the root user
	if ( ! $self->item_exists('user','root') ) {

		# $self->add_user(

	}

	# return our object to the caller
	return $self;

}

# assign a system user or group ID, usually done if it is was bigger then ID_MAX

sub assign_id {
	my ($self, $obj, $name) = @_;

	my $ID;

	# dynamically decide what the id is depending on if this is a use ror a group
	$ID = 'uid' unless $obj ne 'user';
	$ID = 'gid' unless $obj ne 'group';

	# start at UID_MIN, which is the start of "normal" system users / groups
	my $id = $self->{uc($ID).'_MIN'};

	# until we find a uniq ID, increment it
	$id++ until $self->item_map_uniq_id($obj,$id);

	# once here, $id is uniq in context of $obj. check to make sure it is under the max
	if ($id <= $self->{uc($ID).'_MAX'}) {
		# kill the $old id in the item_map, if it exsists
		$self->item_map_delete($obj,$self->{$obj}{$name}{$ID});

		# assign the new id
		$self->{$obj}{$name}{$ID} = $id;
		$self->item_map_add($obj,$id,$name);

		# flag for shadow rebuilding
		$self->flag_rebuild;

		# return the assigned id
		return $id
	} else {
		# fatal error
		print "ERROR: Unable to assign " . $ID . " for " . $name . ", valid " . $ID . " range too small\n";
		exit(1);
	}

}

# validate that the user's shell is valid, and if not (and they're not a system user or root),
# give them a default shell

sub confirm_shell {

	my ($self, $shell, $uid) = @_;

	my $valid_shell = 0;

	foreach (@{$self->{valid_shells}}) { $valid_shell = 1 unless $shell ne $_; }

	# if not a valid shell, change it to "/bin/false" and flag for rebuild
	# also, if the $uid is 0 or less then UID_MIN, don't edit the shell
	if ($valid_shell == 0 && $uid != 0 and $uid >= $self->{UID_MIN}) {
		$shell = $self->{bin_false};
		$self->flag_rebuild;
	}

	return $shell;
}

# count the number of fields in the given line of a passwd / shadow / etc file

sub num_fields {
	my ($self, $str) = @_;

	# we add one, because asdf:asdf:asdf is really three fields, even tho there are only two :'s
	return ($str =~ tr/://) + 1;
}

# add an id / name combo to the maps. we padd the number with zeros so it'll sort correctly when commited

sub item_map_add {
	my ($self, $obj, $id, $name) = @_;

	#    print "obj is $obj: $name / $id\n";

	if ($self->{$obj . 'item_map'}{sprintf("U%.7u",$id)}) {

		#	print "obj is $obj\nid is $id\nresult is " . $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} . "\n";

		#	print "ERROR: $obj id $id already in use while attempting to assign it to $name\n";
		#	exit(1);
	}

	$self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} = $name;

}

# remove an id from a list map

sub item_map_delete {
	my ($self, $obj, $id) = @_;

	# only delete $id if it exists, because if it doesn't, this will delete root from the useritem_map
	delete $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} unless $id == 0;
}

# tell us if the given ID is uniq (whether or not it's in use)

sub item_map_uniq_id {
	my ($self, $obj, $id) = @_;

	return 1 unless $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)};
	return 0;
}

# return the name of the given ID in the list map

sub item_map_name {
	my ($self, $obj, $id) = @_;

	return $self->{($obj eq 'user' ? 'user' : 'group' ) . 'item_map'}{sprintf("U%.7u",$id)};
}

# the checks we do on adding a user or a group

sub add_item_checks {
	my ($self, $obj, $name) = @_;

	# the only required field is the username. the rest we can deal with ourself if we have to
	if ( ! $name ) {
		return 0;
	}

	# duplicate username
	if ( $self->item_exists($obj,$name) ) {
		return 0;
	}

	# security / sanity check
	if ( ! $self->valid_name($name) )
	{
		return 0;
	}

	# success, return 1
	return 1;
}

# check a given name (login name, group name, etc) and determine whether it's safe to use

sub valid_name {
	my ($self, $name) = @_;

	# normal POSIX systems accept just about any ascii character for a username, but things can get
	# weird and scary when you deal with useranmes like "<?php print foo ?>", so we want to restrict
	# users and groups down to just alphanumeric characters, with the exception of underscores, dots,
	# and dashes
	return 1 if $name =~ m/^[a-zA-Z0-9_\.\-]*$/;
	return 0;

}

# check if the given user or group exists

sub item_exists {
	my ($self, $obj, $name) = @_;

	# duplicate name
	return 0 unless defined $self->{$obj}{$name};
	return 1;
}

# randomly generate a salt str and return the $passwd as an encrypted string

sub crypt_passwd {
	my ($self,$passwd) = @_;

	# define our salt characters
	my $salt_chars='./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	my $salt = substr($salt_chars,rand(length($salt_chars)),1) . substr($salt_chars,rand(length($salt_chars)),1);

	# run the crypt function with a random salt
	return crypt($passwd,$salt);

}

# function to tell commit to rebuild the shadow files when called
# TODO: go through all the flag_rebuild calls and make sure they are submitting appropriate messages for syslog

sub flag_rebuild {
	my ($self, $msg) = @_;

	# flag the object to rebuild
	$self->{rebuild_shadows} = 1;
	# log it if we got a message
	syslog('LOG_INFO',$msg) if $msg;
}

#

sub add_user {
	my ($self,$login,$passwd,$home_dir,$shell,$uid,$gid) = @_;

	return if ! $self->add_item_checks('user',$login);

	# assign a uid for this user, if none was given
	# TODO: make a switch for adding a system user

	$uid = $self->assign_id('user',$login) unless $uid;

	# TODO: assign a gid if not given

	# TODO: by default, if no gid, look for a group named this user, and use that. if no group, add it
	#       and use that gid

	# TODO: if for some reason there was an error in the above creation... just assign the gid from /etc/default/useradd

	# set our user hash for this user

	$self->{user}{$login}{'user'} = $login;
	# by default, encrypt the password
	$self->{user}{$login}{'passwd'} = $self->{enc_char};
	$self->{user}{$login}{'uid'} = $uid;
	$self->{user}{$login}{'gid'} = $gid;
	$self->{user}{$login}{'home_dir'} = $home_dir;

	# add the shell. if $shell is blank, the confirm_shell function will set the default
	$self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $uid);

	# crypt the password, if given
	$self->{user}{$login}{'shadow_passwd'} = $self->crypt_passwd($passwd) if $passwd;

	# if no $passwd was given, lock the account
	$self->{user}{$login}{'shadow_passwd'} = '!!' unless $self->{user}{$login}{'shadow_passwd'};
 
	# set last password change to the # of days since epoch
	$self->{user}{$login}{'last_change'} = int(time / 60 / 60 / 24 );

	#
	$self->{user}{$login}{'min_change'} = $self->{PASS_MIN_DAYS};
	$self->{user}{$login}{'max_change'} = $self->{PASS_MAX_DAYS};
	$self->{user}{$login}{'warn'} = $self->{PASS_WARN_AGE};
	$self->{user}{$login}{'inact'} = $self->{INACTIVE};
	$self->{user}{$login}{'expire'} = $self->{EXPIRE};
	$self->{user}{$login}{'reserved'} = '';

	# flag the object to rebuild
	# TODO: add all relivant info here
	$self->flag_rebuild("new user: name=" . $login . ", uid=" . $uid);

	# return the uid of the created user
	return $uid;
}

# edit a system user
# TODO: make the username able to be changed - but it cant be changed to a name already in user

sub edit_user {
	my ($self,$login,$passwd,$home_dir,$shell,$uid,$gid) = @_;

	# TODO: make sure the user exists

	# TODO: code security checks for these incoming values
	# only edit items if new items exist for them
	$self->{user}{$login}{'uid'} = $uid unless ! $uid;
	$self->{user}{$login}{'gid'} = $gid unless ! $gid;
	$self->{user}{$login}{'home_dir'} = $home_dir unless ! $home_dir;
	$self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $self->{user}{$login}{'uid'}) unless ! $shell;

	# if we have a password,
	if ($passwd) {
		# TODO: check to see if this user is disabled before we try to compare the passwords, and if so, add !! to the
		#       the beginning of the password to keep it locked

		# check to see if the given password is different from the crypted password
		if ( crypt($passwd,substr($self->{user}{$login}{'shadow_passwd'},0,2)) ne $self->{user}{$login}{'shadow_passwd'} ) {
			# if so, set the new password and set last password change to the # of days since epoch
			$self->{user}{$login}{'shadow_passwd'} = $self->crypt_passwd($passwd);
			$self->{user}{$login}{'last_change'} = int(time / 60 / 60 / 24 );
			# flag the object to rebuild
			$self->flag_rebuild("change user `" . $login . "' password");
		}
		# else, do nothing

	}

	# flag the object to rebuild if we changed anything
	if ($home_dir or $shell or $uid or $gid) {
	# TODO: add relivant info here
		$self->flag_rebuild("change user `" . $login . "' ");
	}

}

#

sub delete_user {
	my ($self, $login) = @_;

	# check to make sure that this user isn't already gone...
	if ( ! $self->item_exists('user',$login) ) {
		print "ERROR: user " . $login . " does not exist\n";
		exit(1);
	}

	# TODO: if the gid's name is the same as the user, and has no user_list, delete the group too
	# remove the user from the item_map
	$self->item_map_delete('user',$self->{user}{$login}{'uid'});

	# remove the user from the object
	delete $self->{user}{$login};

	# flag for rebuild
	$self->flag_rebuild("delete user `" . $login . "'");

}

#

sub add_group {
	my ($self,$name,$gid,$user) = @_;

	return if ! $self->add_item_checks('group',$name);

	# assign this new group a gid if not given
	# TODO: make a switch for adding a system group
	$gid = $self->assign_id('group',$name) unless $gid;

	$self->{group}{$name}{'name'} = $name;
	$self->{group}{$name}{'gid'} = $gid;

	$self->{group}{$name}{'user_list'}[0] = $user;

	# flag the object to rebuild
	$self->flag_rebuild("new group: name=" . $name . ", gid=" . $gid);

	# return the uid of the created user
	return $gid;

}

# add a user to a group

sub group_user_add {
	my ($self,$name,$user) = @_;

	# check to make sure the user isn't already in the group
	return if $self->user_in_group($user, $name);

	push @{$self->{group}{$name}{'user_list'}}, $user;

	$self->flag_rebuild("new user added to group: name=" . $user . ", group=" . $name);

}

# delete a user from a group

sub group_user_delete {
	my ($self,$name,$user) = @_;

	# check to make sure the user is actually in the group
	return unless $self->user_in_group($user, $name);

	my $c = @{$self->{group}{$name}{'user_list'}};

	for (my $i = 0; $i < $c; $i++) {
		if ( $self->{group}{$name}{'user_list'}[$i] eq $user ) {
			$self->{group}{$name}{'user_list'}[$i] = '';
		}
	}

	$self->flag_rebuild("user removed from group: name=" . $user . ", group=" . $name);

}

# check to see if the given user is in the specified group

sub user_in_group {
	my ($self, $user, $name) = @_;

	# not the most elabroate solution, but it works.. try to match the user in the user_list string
	foreach my $guser (@{$self->{group}{$name}{'user_list'}}) {
		return 1 if $guser eq $user;
	}

	return 0;
}

#
# TODO: make/finish functions:
# edit_group - edit a group
# delete_group - delete a group
# user_is_disabled - return true/false on whether or not a user is disabled
# disable_user - disable a user
# enable_user - enable a user
#

# commit changes to the files, if any

sub commit {
	my ($self) = @_;

	# if we had any errors above, our rebuild_shadows flag should be set to 1 so we know to re-write the files with corrections

	if( $self->{rebuild_shadows} != 0 ) {

		# make sure that the user with uid 0 is root
		$self->{useritem_map}{sprintf("U%.7u",0)} = "root";

		foreach my $db ( ('userdb','groupdb') ) {

			my $item_map;
			my $item;

			if( $db eq "userdb" ) {
				$item_map = 'useritem_map';
				$item = 'user';
			}

			if( $db eq "groupdb" ) {
				$item_map = 'groupitem_map';
				$item = 'group';
			}

			# walk down the $db files and write data
			foreach my $itemdb (keys %{$self->{$db}}) {

				my $data;

				# sort our hash so we build the file in order of UID
				foreach my $key (sort(keys %{$self->{$item_map}})) {

					# get the name of this user
					my $user = $self->{$item_map}{$key};

					# if there isn't a $user, then skip the below... this should probably never happen
					next unless $user;

					# count the number of elements in order
					my $c = @{$self->{$db}{$itemdb}{order}};
					my $i;

					# walk down the order
					for ($i = 0; $i < $c; $i++) {

						if ( ref($self->{$item}{$user}{$self->{$db}{$itemdb}{order}[$i]}) eq "ARRAY" ) {
							$data .= join(',', @{$self->{$item}{$user}{$self->{$db}{$itemdb}{order}[$i]}}). ':';
							$data =~ s/,,/,/g;
							$data =~ s/:,/:/;
						} else {
							$data .= $self->{$item}{$user}{$self->{$db}{$itemdb}{order}[$i]}. ':';
						}
					}

					# chop the last : off
					chop $data;

					# return character at the end of this line
					$data .= "\n";
				}

				# save a copy of the files to passwd- , shadow- , etc before we overwrite them
				file_copy($self->{$db}{$itemdb}{file}, $self->{$db}{$itemdb}{file} . '-');

				# write the gathered data to the file
				file_write($self->{$db}{$itemdb}{file}, $data);

			}

		}

	}

	# BSD requires pwd_mkdb be ran afterwards
	if ($self->{ostype} eq 'bsd') {
		system ('pwd_mkdb ' . $self->{userdb}{passwd}{file});
	}

	# destroy ourself at end of commit
	undef $self;

}

1;

