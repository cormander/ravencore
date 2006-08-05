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



# NOTE!! This code is not currently implemented



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

# TODO: when we rewrite the passwd / group /etc files, make sure the OLD one is copied and named passwd- , group-, etc
#       before it's removed

# TODO: if the user's name or uid is being changed, or they're being deleted, make sure crontab, vhost root, etc
#       reflect that change and search for and kill any processes running as that user, and force them to logout

# TODO: do the same checks as the pwck command:
#       * in each case, send an error to syslog
#       - the correct number of fields
#         if incorrect, dump the user to passwd.corrupted
#       - a unique user name
#         if not, keep the one with the smaller uid. if they're the same, just remove one and dump it to passwd.corrupted
#       - a valid user and group identifier
#         if its a neg uid, make it posotive. if that uid is in use, assign it the next available one
#         if the group doesn't exist, put the user in the group named the same as the user. if it doesn't exist, create it


package rcshadow;

# as we create the class, read in all of our system configuration files for user creation, and read in our
# passwd, shadow, group, and gshadow files. Then, do some auto-correction if possible

sub new
{

# inherit the classname from the above package statement, and get the directory to look in for stuff

    my ($class, $etc) = @_;

# TODO: do some sanity checks on $etc, it should only contain characters allowed in a directory name
#       this is for security reasons

# define our $self variable so we can bless it

    my $self = { etc => $etc };

# bind the class name to this object

    bless $self, $class;

# TODO: check for the existance of each of the system files we reference in this object, and if one doesn't exist,
#       then exit with error
#       make each file refrenced be in a configuration file somewhere so people can change the path if they so please

# look in the login.defs file so we can honor its values when adding a new user / group
    
    open FILE, $self->{etc} . "/login.defs";
    while (<FILE>)
    {
	
	chomp;
	
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
    close FILE;

# TODO: check to make sure all the valids from above, else exit with error

# check for and load our useradd defaults
    
    open FILE, $self->{etc} . '/default/useradd';
    while (<FILE>)
    {
	
	chomp;
	
	if( $_ =~ s/^INACTIVE=(.*)$/\1/) { $self->{INACTIVE} = $1; }
	if( $_ =~ s/^EXPIRE=(.*)$/\1/) { $self->{EXPIRE} = $1; }

    }
    close FILE;
    
# open up /etc/shells and get a list of valid shells so we can validate user's shells.
    
    my $bin_false = 0;
    
    open FILE, $self->{etc} . '/shells';
    @{$self->{shells}} = <FILE>;
    close FILE;
    
# remove newline characters, and check to see if /bin/false is a valid shell
    
    foreach (@{$self->{shells}})
    {
	
	chomp;
	
	$bin_false = 1 unless $_ ne "/bin/false";
	
    }
    
# if /bin/false is not a valid shell, then add it
    
    if( $bin_false == 0 )
    {
	
	push @{$self->{shells}}, "/bin/false";
	
	open FILE, '>> ' . $self->{etc} . '/shells';
	print FILE "/bin/false\n";
	close FILE;
	
# TODO: tell syslog that we added /bin/false to the list of shells
	
    }

    $self->{rebuild_shadows} = 0;


# passwd file
#       user name
#       password
#       numeric ID of user
#       numeric ID of the user's initial group
#       user's full name or description
#       home diretory
#       user's shell

    open PASSWD, $self->{etc} . '/passwd';
    
    while (<PASSWD>)
    {
	chomp;

# if $_ doesn't have the correct number of fields, we drop it

	if($self->num_fields($_) != 7)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($login,$passwd,$uid,$gid,$name,$home_dir,$shell) = split /:/;
	
# check if the user already exists in the $user hash here
	
	if($self->{user}{$login})
	{
	    
	    print "ERROR: Duplicate entry, " . $login . " already exists\n";
	    
	    $self->{rebuild_shadows} = 1;
	    
	    if($uid >= $self->{user}{$login}{'uid'})
	    {
#TODO: dump this NEW entry to passwd.corrupt
		
# skip to the next user to examine
		next;
	    }
	    else
	    {
# TODO: dump the previous entry to passwd.corrupt and proceed with the below
		
	    }
	    
	}
	
# add this user to an array by UID so we can map the uid to the name
	$self->userlist_map_add($uid,$login);
	
# set our user hash for this user
	$self->{user}{$login}{'passwd'} = $passwd;
	$self->{user}{$login}{'uid'} = $uid;
	$self->{user}{$login}{'gid'} = $gid;
	$self->{user}{$login}{'name'} = $name;
	$self->{user}{$login}{'home_dir'} = $home_dir;

# check to make sure this is a valid shell
	$self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $uid);
	
    }

    close PASSWD;
    
    
# group file
#       group name
#       group password
#       numeric ID of group
#       comma separted list of users in the group
    
    open GROUP, $self->{etc} . '/group';
    
    while (<GROUP>)
    {
	chomp;
	
	if($self->num_fields($_) != 4)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($name,$passwd,$gid,$user_list) = split /:/;
	
# TODO: check fi the gropu already exists in the $group hash here...
	
# add this group to an arry by GID so we can map the gid to the name
	$self->grouplist_map_add($gid,$name);
	
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
    
    open SHADOW, $self->{etc} . '/shadow';
    
    while(<SHADOW>)
    {
	chomp;

	if($self->num_fields($_) != 9)
	{
# TODO: dump this entry to passwd.corrupt
# skip to the next line
	    next;
	}

	my ($login,$passwd,$last_change,$min_change,$max_change,$warn,$inact,$expire,$reserved) = split /:/;
	
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
    
    close SHADOW;
    
    
# gshadow file
#       group name
#       group's encrypted password
#       ????
#       user list

    open GSHADOW, $self->{etc} . '/gshadow';
    
    while (<GSHADOW>)
    {
	chomp;
	
	my ($name,$passwd,$no_idea,$user_list) = split /:/;
	
	
	$self->{group}{$name}{'shadow_passwd'} = $passwd unless $self->{group}{$name}{'passwd'} ne "x";
# if there is no shadow_passwd, set to !!
	$self->{group}{$name}{'shadow_passwd'} = '!!' unless $self->{group}{$name}{'shadow_passwd'};
	
# the user_list is an array
# TODO: diff the group user_list and the gshadow user_list... and dump to syslog any differences, assume /etc/group 
#       is correct and sync /etc/gshadow with it.... dump gshadow to gshadow.corrupted
#    @{$self->{group}{$name}{'user_list'}} = split /,/, $user_list;
	
    }
    
    close GSHADOW;
    
#
# Now that we have all our users and groups in memory, we can do some additional checks on them
#    







# loop through the groups and do error checking, and auto-correct when appropriate
    
    foreach my $name (%{$self->{grouplist_map}})
    {
	
#
	
	if($self->{group}{$name}{'gid'} > $self->{GID_MAX}) { print "ERROR: exceeded GID MAX of " . $self->{GID_MAX} . "\n"; }
	
# make sure all these users are valid users
	
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
		$self->{rebuild_shadows} = 1;
	    }
# increment our spot in the array
	    $i++;
	    
	}

    } # end foreach my $name (%{$self->{grouplist_map}})

# loop through the users and do checking on them

    foreach my $login (%{$self->{userlist_map}})
    {
	
# check to see if this UID is below the MAX allowed UID
# TODO: attempt to reassign a valid UID... and if that is not possible, holy shit! there are a lot of users!!!
	
	if($self->{user}{$login}{'uid'} > $self->{UID_MAX})
	{
	    
	    print "ERROR: exceeded UID MAX of " . $self->{UID_MAX} . "\n";
	    
# look for the lowest available UID and automatically assign it to this user

	    $self->assign_uid($login);
	    
	}

# check to see if this is a valid GID here
	
	if( ! $self->{group}{$self->{grouplist_map}{$self->{user}{$login}{'gid'}}} )
	{
	    print "ERROR: invalid gid " . $self->{user}{$login}{'gid'} . "\n";
	    
# if not a valid gid, then check to see if a group named the same as this users exists, if so, change the gid to it
	    if( $self->{group}{$login} )
	    {
		$self->{user}{$login}{'gid'} = $self->{group}{$login}{'gid'}
		$self->{rebuild_shadows} = 1;
	    }
	    else
	    {
# TODO: finish this

# otherwise, add a group named this user and add this user to the group... and if we get an error adding the
# group... well shit, heh

	    }

	}
	
#    print "The user " . $login . " has a uid of " . $user{$login}{'uid'} . " and a gid of " . $user{$login}{'gid'} . " ( " . $group_name . " )\n";
	
    }




















# TODO: make sure that we have at least the root user

    if( ! $self->{user}{'root'} )
    {

#	$self->add_user(

    }

# return our object to the caller
    
    return $self;

}

# reassign a system user's UID, usually done if it is was bigger then UID_MAX

sub assign_uid
{
    my ($self, $login) = @_;

# start at UID_MIN, which is the start of "normal" system users
    my $uid = $self->{UID_MIN};
    
    $uid++ until $self->uniq_uid($uid);
	    
    if($uid <= $self->{UID_MAX})
    {
# kill the $old_uid in the userlist_map, if it exsists
	$self->userlist_map_delete($self->{user}{$login}{'uid'});

# assign the new uid
	$self->{user}{$login}{'uid'} = $uid;
	$self->userlist_map_add($uid,$login);

	$self->{rebuild_shadows} = 1;

# return the assigned uid
	
	return $uid
	    
    }
    else
    {
	print "ERROR: Unable to reassign uid for " . $login . ", valid UID range too small\n";
    }
    
}

# validate that the user's shell is valid, and if not (and they're not a system user or root),
# give them a default shell

sub confirm_shell
{

    my ($self, $shell, $uid) = @_;

    my $valid_shell = 0;

    foreach(@{$self->{shells}}) { $valid_shell = 1 unless $shell ne $_; }
	
# if not a valid shell, change it to "/bin/false" and flag for rebuild
# also, if the $uid is 0 or less then UID_MIN, don't edit the shell
    if($valid_shell == 0 && $uid != 0 and $uid >= $self->{UID_MIN})
    {
	$shell = "/bin/false";
	$self->{rebuild_shadows} = 1;
    }

    return $shell;
    
}

# count the number of fields in the given line of a passwd / shadow / etc file

sub num_fields
{
    my ($self, $str) = @_;

# we add one, because asdf:asdf:asdf is really three fields, even tho there are only two :'s
    return ($str =~ tr/://) + 1;
}

#

sub userlist_map_add
{
    my ($self, $uid, $login) = @_;

    $self->{userlist_map}{sprintf("%.7u",$uid)} = $login;

}

#

sub userlist_map_delete
{
    my ($self, $uid) = @_;

    delete $self->{userlist_map}{sprintf("%.7u",$uid)};
}

#

sub uniq_uid
{
    my ($self, $uid) = @_;

    return 1 unless $self->{userlist_map}{sprintf("%.7u",$uid)};
    return 0;
}

#

sub grouplist_map_add
{
    my ($self, $gid, $name) = @_;

    $self->{grouplist_map}{sprintf("%.7u",$gid)} = $name;

}

#

sub uniq_gid
{
    my ($self, $gid) = @_;
    
    return 1 unless $self->{grouplist_map}{sprintf("%.7u",$gid)};
    return 0;
}

#
# TODO: on any exit call, make sure the commit function is called

sub add_user
{

    my ($self,$login,$passwd,$home_dir,$shell,$uid,$gid) = @_;

# required field
    if( ! $login )
    {
	print "ERROR: Must specify the username.\n";

	exit(1);
    }

# duplicate username
    if( $self->{user}{$login} )
    {
	print "ERROR: username " . $login . " already exists.\n";

        exit(1);
    }

# security check, allow only alphanumeric characters
    if( $login !~ m/^[a-zA-Z0-9]$/ )
    {
        print "ERROR: username " . $login . " contains illegal characters.\n";

        exit(1);
    }

# assign a uid for this user, if none was given
# TODO: make a switch for adding a system user

    $uid = $self->assign_uid($login) unless $uid;

# add this uid / login to the mapping hash
    $self->userlist_map_add($uid,$login);
    
# TODO: assign a gid if not given

# set our user hash for this user
    $self->{user}{$login}{'passwd'} = 'x';
    $self->{user}{$login}{'uid'} = $uid;
    $self->{user}{$login}{'gid'} = $gid;
    $self->{user}{$login}{'home_dir'} = $home_dir;

# add the shell. if $shell is blank, the confirm_shell function will set the default
    $self->{user}{$login}{'shell'} = $self->confirm_shell($shell, $uid);
    
# if no $passwd was given, lock the account
    $self->{user}{$login}{'shadow_passwd'}
    $self->{user}{$login}{'shadow_passwd'} = '!!' unless $self->{user}{$login}{'shadow_passwd'};

	$self->{user}{$login}{'last_change'} = $last_change;
	$self->{user}{$login}{'min_change'} = ( $min_change ? $min_change : $self->{PASS_MIN_DAYS} );
	$self->{user}{$login}{'max_change'} = ( $max_change ? $max_change : $self->{PASS_MAX_DAYS} );
	$self->{user}{$login}{'warn'} = ( $warn ? $warn : $self->{PASS_WARN_AGE} );
	$self->{user}{$login}{'inact'} = ( $inact ? $inact : $self->{INACTIVE} );
	$self->{user}{$login}{'expire'} = ( $expire ? $expire : $self->{EXPIRE} );
	$self->{user}{$login}{'reserved'} = $reserved;
    
}


$s = new rcshadow('/etc');

print $s->{user}{root}{shell}. "\n";

exit(0);










    
# TODO: the create_user subroutine will by default start from $UID_MIN and work its way up the %userlist_map until it
#       finds a free $uid, and uses that
#       if the "system user" flag is set, it'll start from 1 and go up the %userlist_map until it finds a free $uid,
#       and uses it. if $UID_MIN is reached, die with an error saying that there are no more free system users

# TODO: likewise for the create_group

# TODO: the create_user subroutine calls create_group with the username as the group name, unless a $gid is given

# Set our flag of whether or not we need to rebuild the shadow files after we're done with error checking them












# if we had any errors above, our rebuild_shadows flag should be set to 1 so we know to re-write the files with corrections

if( $rebuild_shadows != 10 )
{

    open FILE, '> ' . $self->{etc} . '/passwd.test';

# sort

    foreach $key (sort(keys %userlist_map))
    {
	$login = $userlist_map{$key};

	print FILE $login . ":" . $user{$login}{'passwd'} . ":" . $user{$login}{'uid'} . ":" . $user{$login}{'gid'} . ":" . $user{$login}{'name'} . ":" . $user{$login}{'home_dir'} . ":" . $user{$login}{'shell'} . "\n";
    }
    close FILE;

}




