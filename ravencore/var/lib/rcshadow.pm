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
# package rcshadow
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

package rcshadow;

# TODO: finish implementing syslog logging of errors
use Sys::Syslog;

use rcfilefunctions;

# as we create the class, read in all of our system configuration files for user creation, and read in our
# passwd, shadow, group, and gshadow files. Then, do some auto-correction if possible

sub new
{
# inherit the classname from the above package statement
    my ($class) = @_;

# define our $self variable so we can bless it
    my $self = {
	passwd => '/etc/passwd',
	shadow => '/etc/shadow',
	group => '/etc/group',
	gshadow => '/etc/gshadow',
	shells => '/etc/shells',
	login_defs => '/etc/login.defs',
	useradd => '/etc/default/useradd',
	bin_false => '/bin/false',
	rebuild_shadows => 0,
    };
    
# bind the class name to this object
    bless $self, $class;

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

# look in the login.defs file so we can honor its values when adding a new user / group
    
    my @file = file_get_array($self->{login_defs});

    foreach(@file)
    {
	
# these regular expressions return true if the match was successful, in which case, $1 is set to the content we want
	
	if($_ =~ s/^UID_MIN\W*(\d*)$/\1/) { $self->{UID_MIN} = $1; }
	if($_ =~ s/^UID_MAX\W*(\d*)$/\1/) { $self->{UID_MAX} = $1; }
	if($_ =~ s/^GID_MIN\W*(\d*)$/\1/) { $self->{GID_MIN} = $1; }
	if($_ =~ s/^GID_MAX\W*(\d*)$/\1/) { $self->{GID_MAX} = $1; }
	
	if($_ =~ s/^PASS_MAX_DAYS\W*(\d*)$/\1/) { $self->{PASS_MAX_DAYS} = $1; }
	if($_ =~ s/^PASS_MIN_DAYS\W*(\d*)$/\1/) { $self->{PASS_MIN_DAYS} = $1; }
	if($_ =~ s/^PASS_MIN_LEN\W*(\d*)$/\1/) { $self->{PASS_MIN_LEN} = $1; }
	if($_ =~ s/^PASS_WARN_AGE\W*(\d*)$/\1/) { $self->{PASS_WARN_AGE} = $1; }
	
    }

# check to make sure we got all the variables from above

# PASS_MIN_LEN isn't used so don't include it below

    foreach ( ('UID_MIN', 'UID_MAX', 'GID_MIN', 'GID_MAX', 'PASS_MAX_DAYS', 'PASS_MIN_DAYS', 'PASS_WARN_AGE' ) )
    {

# if missing, exit with error
	if( ! defined $self->{$_} )
	{
	    print "ERROR: Unable to load variable " . $_ . " from " . $self->{login_defs} . "\n";
	    exit(1);
	}
    }

# check for and load our useradd defaults
    
    my @file = file_get_array($self->{useradd});

    foreach(@file)
    {
	if( $_ =~ s/^EXPIRE=(.*)$/\1/) { $self->{EXPIRE} = $1; }
# assume -1 is empty with the INACTIVE config
	if( $_ =~ s/^INACTIVE=(.*)$/\1/) { $self->{INACTIVE} = $1 unless $1 == -1; }
	
    }

# some systems don't have a useradd file.. but that's OK, we don't really NEED them.
    
# open up /etc/shells and get a list of valid shells so we can validate user's shells.
    my $bin_false = 0;
    
    @{$self->{valid_shells}} = file_get_array($self->{shells});
    
# remove newline characters, and check to see if /bin/false is a valid shell
    
    foreach (@{$self->{valid_shells}})
    {
	$bin_false = 1 unless $_ ne $self->{bin_false};
    }
    
# if /bin/false is not a valid shell, then add it
    
    if( $bin_false == 0 )
    {	
	push @{$self->{valid_shells}}, $self->{bin_false};

	file_append($self->{shells}, $self->{bin_false} . "\n");
	
# tell syslog that we added /bin/false to the list of shells
	syslog('LOG_NOTICE',"Added " . $self->{bin_false} . " to " . $self->{shells});
    }

# TODO: run sanity checks against all fields read in from the passwd and shadow files

# passwd file
#       user name
#       password
#       numeric ID of user
#       numeric ID of the user's initial group
#       user's full name or description
#       home diretory
#       user's shell

    my @file = file_get_array($self->{passwd});
    
    foreach(@file)
    {

# if $_ doesn't have the correct number of fields, we drop it

	if($self->num_fields($_) != 7)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($login,$passwd,$uid,$gid,$name,$home_dir,$shell) = split /:/;
	
# check if the user already exists in the $user hash here
	
#	if( $self->item_exists('user',$login) )
#	{
	    
#	    print "ERROR: Duplicate entry, user " . $login . " already exists\n";
	    
#	    $self->flag_rebuild;
	    
#	    if($uid >= $self->{user}{$login}{'uid'})
#	    {
# TODO: dump this NEW entry to passwd.corrupt
		
# skip to the next user to examine
#		next;
#	    }
#	    else
#	    {
# TODO: dump the previous entry to passwd.corrupt and proceed with the below
		
#	    }
	    
#	}
	
# add this user to an array by UID so we can map the uid to the name
	$self->item_map_add('user',$uid,$login);
	
# set our user hash for this user
	$self->{user}{$login}{'passwd'} = $passwd;
	$self->{user}{$login}{'uid'} = $uid;
	$self->{user}{$login}{'gid'} = $gid;
	$self->{user}{$login}{'name'} = $name;
	$self->{user}{$login}{'home_dir'} = $home_dir;

# check to make sure this is a valid shell
	$self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $uid);
	
    }

# group file
#       group name
#       group password
#       numeric ID of group
#       comma separted list of users in the group

    my @file = file_get_array($self->{group});
    
    my $num_lines = @file;

    foreach(@file)
    {
	
	if($self->num_fields($_) != 4)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($name,$passwd,$gid,$user_list) = split /:/;

# check if the group already exists
	
#	if( $self->item_exists('group',$name) )
#	{
	    
#	    print "ERROR: Duplicate entry, group " . $name . " already exists\n";
	    
#	    $self->flag_rebuild;
	    
#	    if($gid >= $self->{group}{$name}{'gid'})
#	    {
# TODO: dump this NEW entry to group.corrupt
		
# skip to the next group to examine
#		next;
#	    }
#	    else
#	    {
# TODO: dump the previous entry to group.corrupt and proceed with the below
		
#	    }
	    
#	}
	
# add this group to an arry by GID so we can map the gid to the name
	$self->item_map_add('group',$gid,$name);
	
	$self->{group}{$name}{'gid'} = $gid;
	$self->{group}{$name}{'passwd'} = $passwd;
# the user_list is an array
	@{$self->{group}{$name}{'user_list'}} = split /,/, $user_list;
	
    }

# shadow file
#       user name
#       user's encrypted password
#       days since Jan 1, 1970 password was last changed.
#       days before which password may not be changed.
#       days after which password must be changed.
#       days before password is to expire that user is warned of pending password expiration.
#       days after password expires that account is considered inactive and disabled.
#       days since Jan 1, 1970 when account will be disabled.
#       reserved for future use.

    my @file = file_get_array($self->{shadow});
    
    foreach(@file)
    {
	if($self->num_fields($_) != 9)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($login,$passwd,$last_change,$min_change,$max_change,$warn,$inact,$expire,$reserved) = split /:/;

# TODO: make sure this user exists... if not, dump this entry to shadow.corrupt
	
# set the shadow_passwd 
	$self->{user}{$login}{'shadow_passwd'} = $passwd unless $self->{user}{$login}{'passwd'} ne "x";
# if there is no shadow_passwd, set to !!
	$self->{user}{$login}{'shadow_passwd'} = '!!' unless $self->{user}{$login}{'shadow_passwd'};
	
	$self->{user}{$login}{'last_change'} = $last_change;
	$self->{user}{$login}{'min_change'} = ( $min_change ? $min_change : $self->{PASS_MIN_DAYS} );
	$self->{user}{$login}{'max_change'} = ( $max_change ? $max_change : $self->{PASS_MAX_DAYS} );
	$self->{user}{$login}{'warn'} = ( $warn ? $warn : $self->{PASS_WARN_AGE} );
	$self->{user}{$login}{'inact'} = ( $inact ? $inact : $self->{INACTIVE} );
	$self->{user}{$login}{'expire'} = ( $expire ? $expire : $self->{EXPIRE} );
	$self->{user}{$login}{'reserved'} = $reserved;
	
    }
    
# gshadow file
#       group name
#       group's encrypted password
#       ????
#       user list

    my @file = file_get_array($self->{gshadow});
    
    foreach(@file)
    {
	
	my ($name,$passwd,$no_idea,$user_list) = split /:/;

	$self->{group}{$name}{'shadow_passwd'} = $passwd unless $self->{group}{$name}{'passwd'} ne "x";
# if there is no shadow_passwd, oh well!
# TODO: figure out what we're supposed to do here
	
# the user_list is an array
# TODO: diff the group user_list and the gshadow user_list... and dump to syslog any differences, assume /etc/group 
#       is correct and sync /etc/gshadow with it.... dump gshadow to gshadow.corrupted
#    @{$self->{group}{$name}{'user_list'}} = split /,/, $user_list;
	
    }
    
#
# Now that we have all our users and groups in memory, we can do some additional checks on them
#    

#
# group checks: loop through the groups and do error checking, and auto-correct when appropriate
#

    foreach my $name (%{$self->{groupitem_map}})
    {
#
	
# check to see if this GID is below the MAX allowed GID
	if($self->{group}{$name}{'gid'} > $self->{GID_MAX})
	{
	    
	    print "ERROR: exceeded GID MAX of " . $self->{GID_MAX} . "\n";
	    
# look for the lowest available UID and automatically assign it to this user

	    $self->assign_id('group',$name);
	    
	}

# make sure all these users are valid users
	$self->confirm_group_users($name);

    } # end foreach my $name (%{$self->{groupitem_map}})

#
# user checks: loop through the users and do checking on them
#

    foreach my $login (%{$self->{useritem_map}})
    {
	
# check to see if this UID is below the MAX allowed UID
	
	if($self->{user}{$login}{'uid'} > $self->{UID_MAX})
	{
	    
	    print "ERROR: exceeded UID MAX of " . $self->{UID_MAX} . "\n";
	    
# look for the lowest available UID and automatically assign it to this user

	    $self->assign_id('user',$login);
	    
	}

# check to see if this is a valid GID here

	if( ! $self->item_exists('group',$self->item_map_name('group',$self->{user}{$login}{'gid'})) )
	{

# don't worry about printing an error out, for now
#	    print "ERROR: invalid gid " . $self->{user}{$login}{'gid'} . ", group does not exist\n";
	    
# if not a valid gid, then check to see if a group named the same as this users exists, if so, change the gid to it
	    if( $self->item_exists('group',$login) )
	    {
# TODO: log this appropriatly
		$self->{user}{$login}{'gid'} = $self->{group}{$login}{'gid'};
		$self->flag_rebuild;
	    }
	    else
	    {

# otherwise, a group was deleted manually.... I'd like to add a group named this user and add this user to the group...
# but if we get an error adding the group (and we probably will) holy crap the system is having problems and this will
# croak, so for now, do nothing
# TODO: if $uid as a $gid is not in use, assign this new group that id
#		$self->add_group($login);

	    }

	}
	
    } # end foreach my $login (%{$self->{useritem_map}})

# TODO: make sure that we have at least the root user
    if( ! $self->item_exists('user','root') )
    {

#	$self->add_user(

    }

# return our object to the caller
    return $self;

} # end sub new

# assign a system user or group ID, usually done if it is was bigger then ID_MAX

sub assign_id
{
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
    if($id <= $self->{uc($ID).'_MAX'})
    {
# kill the $old id in the item_map, if it exsists
	$self->item_map_delete($obj,$self->{$obj}{$name}{$ID});

# assign the new id
	$self->{$obj}{$name}{$ID} = $id;
	$self->item_map_add($obj,$id,$name);

# flag for shadow rebuilding
	$self->flag_rebuild;

# return the assigned id
	return $id
	    
    }
    else
    {
# fatal error
	print "ERROR: Unable to assign " . $ID . " for " . $name . ", valid " . $ID . " range too small\n";
	exit(1);
    }
    
} # end sub assign_id

# validate that the user's shell is valid, and if not (and they're not a system user or root),
# give them a default shell

sub confirm_shell
{

    my ($self, $shell, $uid) = @_;

    my $valid_shell = 0;

    foreach(@{$self->{valid_shells}}) { $valid_shell = 1 unless $shell ne $_; }
	
# if not a valid shell, change it to "/bin/false" and flag for rebuild
# also, if the $uid is 0 or less then UID_MIN, don't edit the shell
    if($valid_shell == 0 && $uid != 0 and $uid >= $self->{UID_MIN})
    {
	$shell = $self->{bin_false};
	$self->flag_rebuild;
    }

    return $shell;
    
} # end sub confirm_shell

# make sure all the users in a group are valid users	
sub confirm_group_users
{
    my ($self, $name) = @_;
    
    my $i = 0;
    
    foreach (@{$self->{group}{$name}{'user_list'}})
    {
# if the user doesn't exist, remove it from the group
	if( ! $self->{user}{$_} )
	{
	    print "ERROR: group " . $name . " contains a non-existant user: " . $_ . ", removing it\n";
# kill this entry from the group and shift all the elements down		
	    splice @{$self->{group}{$name}{'user_list'}}, $i, 1;
# flag for a rebuild
	    $self->flag_rebuild;
	}
# increment our spot in the array
	$i++;
	
    }
    
} # end sub confirm_group_users

# count the number of fields in the given line of a passwd / shadow / etc file

sub num_fields
{
    my ($self, $str) = @_;

# we add one, because asdf:asdf:asdf is really three fields, even tho there are only two :'s
    return ($str =~ tr/://) + 1;
} # end num_fields

# add an id / name combo to the maps. we padd the number with zeros so it'll sort correctly when commited

sub item_map_add
{
    my ($self, $obj, $id, $name) = @_;

#    print "obj is $obj: $name / $id\n";

    if( $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} )
    {

#	print "obj is $obj\nid is $id\nresult is " . $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} . "\n";

#	print "ERROR: $obj id $id already in use while attempting to assign it to $name\n";
#	exit(1);
    }

    $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} = $name;

} # end sub item_map_add

# remove an id from a list map

sub item_map_delete
{
    my ($self, $obj, $id) = @_;

# only delete $id if it exists, because if it doesn't, this will delete root from the useritem_map
    delete $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)} unless $id == 0;
} # end sub item_map_delete

# tell us if the given ID is uniq (whether or not it's in use)

sub item_map_uniq_id
{
    my ($self, $obj, $id) = @_;

    return 1 unless $self->{$obj . 'item_map'}{sprintf("U%.7u",$id)};
    return 0;
} # end item_map_uniq_id

# return the name of the given ID in the list map

sub item_map_name
{
    my ($self, $obj, $id) = @_;

    return $self->{($obj eq 'user' ? 'user' : 'group' ) . 'item_map'}{sprintf("U%.7u",$id)};
} # end item_map_name

#

# the checks we do on adding a user or a group

sub add_item_checks
{
    my ($self, $obj, $name) = @_;

# the only required field is the username. the rest we can deal with ourself if we have to
    if( ! $name )
    {
	print "ERROR: Must specify the " . $obj . " name.\n";

	exit(1);
    }

# duplicate username
    if( $self->item_exists($obj,$name) )
    {
	print "ERROR: " . $obj . " " . $name . " already exists.\n";

        exit(1);
    }

# security / sanity check
    if( ! $self->valid_name($name) )
    {
        print "ERROR: " . $obj . " " . $name . " contains illegal characters.\n";
	
        exit(1);
    }

} # end sub add_item_checks

# check a given name (login name, group name, etc) and determine whether it's safe to use

sub valid_name
{

    my ($self, $name) = @_;

# normal POSIX systems accept just about any ascii character for a username, but things can get
# weird and scary when you deal with useranmes like "<?php print foo ?>", so we want to restrict
# users and groups down to just alphanumeric characters, with the exception of underscores, dots,
# and dashes
    return 1 if $name =~ m/^[a-zA-Z0-9_\.\-]*$/;
    return 0;

} # end sub valid_name

# check if the given user or group exists

sub item_exists
{
    my ($self, $obj, $name) = @_;

    # duplicate name
    return 0 unless defined $self->{$obj}{$name};
    return 1;
} # end sub item_exists

# randomly generate a salt str and return the $passwd as an encrypted string

sub crypt_passwd
{
    my ($self,$passwd) = @_;

# define our salt characters
    my $salt_chars='./abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    my $salt = substr($salt_chars,rand(length($salt_chars)),1) . substr($salt_chars,rand(length($salt_chars)),1);

# run the crypt function with a random salt
    return crypt($passwd,$salt);

} # end sub crypt_passwd

# function to tell commit to rebuild the shadow files when called
# TODO: go through all the flag_rebuild calls and make sure they are submitting appropriate messages for syslog

sub flag_rebuild
{
    my ($self, $msg) = @_;

# flag the object to rebuild
    $self->{rebuild_shadows} = 1;
# log it if we got a message
    syslog('LOG_INFO',$msg) if $msg;
} # end sub flag_rebuild

#

sub add_user
{
    my ($self,$login,$passwd,$home_dir,$shell,$uid,$gid) = @_;
    
    $self->add_item_checks('user',$login);

# assign a uid for this user, if none was given
# TODO: make a switch for adding a system user

    $uid = $self->assign_id('user',$login) unless $uid;

# TODO: assign a gid if not given

# TODO: by default, if no gid, look for a group named this user, and use that. if no group, add it
#       and use that gid

# TODO: if for some reason there was an error in the above creation... just assign the gid from /etc/default/useradd

# set our user hash for this user

# by default, encrypt the password
    $self->{user}{$login}{'passwd'} = 'x';
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
    
} # end sub add_user

# edit a system user
# TODO: make the username able to be changed - but it cant be changed to a name already in user

sub edit_user
{
    my ($self,$login,$passwd,$home_dir,$shell,$uid,$gid) = @_;

# TODO: make sure the user exists

# TODO: code security checks for these incoming values
# only edit items if new items exist for them
    $self->{user}{$login}{'uid'} = $uid unless ! $uid;
    $self->{user}{$login}{'gid'} = $gid unless ! $gid;
    $self->{user}{$login}{'home_dir'} = $home_dir unless ! $home_dir;
    $self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $self->{user}{$login}{'uid'}) unless ! $shell;

# if we have a password,
    if( $passwd )
    {
# TODO: check to see if this user is disabled before we try to compare the passwords, and if so, add !! to the
#       the beginning of the password to keep it locked

# check to see if the given password is different from the crypted password
	if( crypt($passwd,substr($self->{user}{$login}{'shadow_passwd'},0,2)) ne $self->{user}{$login}{'shadow_passwd'} )
	{
# if so, set the new password and set last password change to the # of days since epoch
	    $self->{user}{$login}{'shadow_passwd'} = $self->crypt_passwd($passwd);
	    $self->{user}{$login}{'last_change'} = int(time / 60 / 60 / 24 );
# flag the object to rebuild
	    $self->flag_rebuild("change user `" . $login . "' password");
	}
# else, do nothing

    }

# flag the object to rebuild if we changed anything
    if( $home_dir or $shell or $uid or $gid )
    {
# TODO: add relivant info here
	$self->flag_rebuild("change user `" . $login . "' ");
    }

} # end sub edit_user

#

sub delete_user
{
    my ($self, $login) = @_;

# check to make sure that this user isn't already gone...
    if( ! $self->item_exists('user',$login) )
    {
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

} # end sub delete_user

#

sub add_group
{
    my ($self,$name,$gid,@user_list) = @_;

    $self->add_item_checks('group',$name);

# assign this new group a gid if not given
# TODO: make a switch for adding a system group
    $gid = $self->assign_id('group',$name) unless $gid;

    $self->{group}{$name}{'gid'} = $gid;

# the user_list is an array
    @{$self->{group}{$name}{'user_list'}} = @user_list;
# make sure all the users in this group exist
    $self->confirm_group_users($name);

# flag the object to rebuild
    $self->flag_rebuild("new group: name=" . $name . ", gid=" . $gid);

# return the uid of the created user
    return $gid;

} # end sub add_group

#

sub group_user_add
{
    my ($self,$name,$user) = @_;

    push @{$self->{group}{$name}{'user_list'}}, $user;

    $self->flag_rebuild("new user added to group: name=" . $user . ", group=" . $name);

} # end sub group_user_add

#
# TODO: make/finish functions:
# edit_group - edit a group
# group_user_del - remove a user from a group
# delete_group - delete a group
# user_is_disabled - return true/false on whether or not a user is disabled
# disable_user - disable a user
# enable_user - enable a user
#

# commit changes to the files, if any

sub commit
{
    my ($self) = @_;

# note to self..... "my $passwd_file, $shadow_file, $group_file, $gshadow_file;" only declairs $passwd_file as local,
# the rest are then considered global... and wierd things start happening to the group/shadow/gshadow files.... so
# explicity declair them all as local variables individually
    my $passwd_file;
    my $shadow_file;
    my $group_file;
    my $gshadow_file;

# if we had any errors above, our rebuild_shadows flag should be set to 1 so we know to re-write the files with corrections

    if( $self->{rebuild_shadows} != 0 )
    {

# TODO: save a copy of the file to passwd- , shadow- , etc before we overwrite them

# build our passwd and shadow files
	
# sort our hash so we build the file in order of UID
	foreach my $key (sort(keys %{$self->{useritem_map}}))
	{
	    
# get the name of this user
	    my $login = $self->{useritem_map}{$key};

# if there isn't a $login, then skip the below... this should probably never happen
	    next unless $login;

# append to the passwd file
	    $passwd_file .= $login . ":" .
		$self->{user}{$login}{'passwd'} . ":" .
		$self->{user}{$login}{'uid'} . ":" .
		$self->{user}{$login}{'gid'} . ":" .
		$self->{user}{$login}{'name'} . ":" .
		$self->{user}{$login}{'home_dir'} . ":" .
		$self->{user}{$login}{'shell'} . "\n";
	    
# append to the shadow file
	    $shadow_file .= $login . ":" .
		$self->{user}{$login}{'shadow_passwd'} . ":" .
		$self->{user}{$login}{'last_change'} . ":" .
		$self->{user}{$login}{'min_change'} . ":" .
		$self->{user}{$login}{'max_change'} . ":" .
		$self->{user}{$login}{'warn'} . ":" .
		$self->{user}{$login}{'inact'} . ":" .
		$self->{user}{$login}{'expire'} . ":" .
		$self->{user}{$login}{'reserved'} . "\n";

	}

# build our group and gshadow files

# sort our hash so we order on GID
	foreach my $key (sort(keys %{$self->{groupitem_map}}))
	{

# get the group name
	    my $name = $self->{groupitem_map}{$key};

# next if no group name, this should never happen
	    next unless $name;

# local variable $user_list will be comma seperated of all the users
	    my $user_list;

# loop through the array	    
	    while ( my $usr = shift @{$self->{group}{$name}{'user_list'}} )
	    {
# if there are more users in the array, append a comma to the end. otherwise, don't so we don't end with a comma
		$user_list .= $usr . ( @{$self->{group}{$name}{'user_list'}} ? ',' : '' );
	    }

# append to the group file
	    $group_file .= $name . ":" .
		$self->{group}{$name}{'passwd'} . ":" .
		$self->{group}{$name}{'gid'} . ":" .
		$user_list . "\n";

# append to the gshadow file
	    $gshadow_file .= $name . ":" .
		$self->{group}{$name}{'shadow_passwd'} . ":" .
		$self->{group}{$name}{'no_idea'} . ":" .
		$user_list . "\n";

	}
# backup the files
# TODO: instead of copy blindly, get the contents, write the new, and only write a backup if they differ
	file_copy($self->{passwd}, $self->{passwd} . '-');
	file_copy($self->{shadow}, $self->{shadow} . '-');
	file_copy($self->{group},$self->{group} . '-' );
	file_copy($self->{gshadow}, $self->{gshadow} . '-');

# write the files
	file_write($self->{passwd}, $passwd_file);
	file_write($self->{shadow}, $shadow_file);
	file_write($self->{group}, $group_file);
	file_write($self->{gshadow}, $gshadow_file);

    } # end if( $self->{rebuild_shadows} != 0 )
    
# destroy ourself at end of commit
    undef $self;

} # end sub commit

1; # end package rcshadow
