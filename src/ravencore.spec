%define rc_root /usr/local/ravencore

%define reltag %{?_with_release: 1} %{?!_with_release: 0.%(perl -e 'print time()')}

Summary: RavenCore Hosting Control Panel
Name: ravencore
Version: 0.4.0
Release: %{reltag}
Packager: Corey Henderson <corman@cormander.com>
Vendor: RavenCore
URL: http://www.ravencore.com/
Source0: %{name}-%{version}.tar.gz
License: GPL
Group: System Environment/Daemons
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-root
# For now, don't preocess dependancies
AutoReq: no
AutoProv: no

%description
A Free Hosting Control Panel for Linux intended to replace the need
for expensive software such as Ensim, CPanel & Plesk. It uses Apache,
Postfix, MySQL & other projects like AWStats and phpMyAdmin. The GUI
is written in PHP, and the backend in Perl & Bash.

RavenCore checks for installed components on your server, and gives
you options to control whatever that particular version of RavenCore
is able to interface with. These include, but are not limited to:

Postfix, Dovecot, Spam Assassin, ClamAV, Bind DNS, MySQL, vsftpd,
and much more.

%prep
%setup -q

echo "Building %{name}-%{version}-%{release}"

%build

# build RavenCore

make %{?_with_bare:bare} build

%install
rm -rf $RPM_BUILD_ROOT

# Create directories

mkdir -p \
	$RPM_BUILD_ROOT/etc/cron.{hourly,daily} \
	$RPM_BUILD_ROOT/etc/init.d \
	$RPM_BUILD_ROOT/etc/logrotate.d \
	$RPM_BUILD_ROOT/etc/profile.d

# Install RavenCore

make DESTDIR=$RPM_BUILD_ROOT RC_ROOT=%{rc_root} install

%pre
if [ -L %{rc_root} ]; then
	echo "%{rc_root} is a symlink; not installing"
	exit 1
fi


%post 
if [ -x /sbin/chkconfig ]; then

    /sbin/chkconfig --list ravencore &> /dev/null

    if [ $? -ne 0 ]; then

# not listed as a service, add it
        /sbin/chkconfig --add ravencore
# set ravencore to startup on boot
        /sbin/chkconfig ravencore on

    fi

fi


%preun
if [ -x %{rc_root}/sbin/ravencore.init ]; then

	%{rc_root}/sbin/ravencore.init stop

	if [ -x /sbin/chkconfig ]; then
		/sbin/chkconfig --del ravencore
	fi

fi

if [ "$1" = "0" ] ; then # we are being completly uninstalled

	[ -x %{rc_root}/sbin/restore_orig_conf.sh ] && %{rc_root}/sbin/restore_orig_conf.sh

fi

%check

%clean
rm -rf $RPM_BUILD_ROOT

%files
/etc/ravencore.conf
/etc/init.d/ravencore
/etc/logrotate.d/ravencore
/etc/cron.daily/ravencore
/etc/cron.hourly/ravencore
/etc/profile.d/ravencore.sh

%{rc_root}/LICENSE
%{rc_root}/README

%{rc_root}/sbin/data_query
%{rc_root}/sbin/db_install
%{rc_root}/sbin/dbshell
%{rc_root}/sbin/decode_query
%{rc_root}/sbin/process_logs
%{rc_root}/sbin/ravencore.cron
%{rc_root}/sbin/ravencore.init
%{rc_root}/sbin/ravencore.httpd
%{rc_root}/sbin/rcserver
%{rc_root}/sbin/restore_orig_conf.sh
%{rc_root}/sbin/run_cmd

%dir
%{rc_root}/docs
%{rc_root}/etc
%{rc_root}/httpdocs
%{rc_root}/var
%{rc_root}/lib

%changelog
* Wed Mar 11 2009 Corey Henderson <corman@cormander.com>
- see http://github.com/cormander/ravencore for changelog.

* Sat Jul 15 2005 cormander <admin@ravencore.com>
- version 0.0.1
- Initial build.

