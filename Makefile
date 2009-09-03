
# RC_ROOT is where RavenCore is going to be installed
RC_ROOT=/usr/local/ravencore

# The RavenCore administrator user
ADMIN_USER=admin

# Changing this will break everything! You'll have to edit each of the shell scripts to
# reference the new conf file.... later on I'll build a tool to do automatically for you

ETC_RAVENCORE=/etc/ravencore.conf

# The current RavenCore version...

VERSION=0.4.0

# 3rd party program names and version numbers

PHPMYADMIN=phpMyAdmin-2.11.9.5-all-languages
PHPSYSINFO=phpsysinfo-2.5.4
PHPWEBFTP=phpwebftp40
AWSTATS=awstats-6.95
SQUIRRELMAIL=squirrelmail-1.4.20-RC2
YAA=yaa-0.3.1
PERL_CONFIG_ABSTRACT=Config-Abstract-0.16
PERL_NET_SERVER=Net-Server-0.97
PERL_PHP_SERIALIZATION=PHP-Serialization-0.33
PERL_SHA_PUREPERL=Digest-SHA-PurePerl-5.47
PERL_TEMPLATE_TOOLKIT=Template-Toolkit-2.22
JTA=jta26

# Squirrelmail plugins to install

SQUIRREL_PLUGIN_COMPAT=compatibility-2.0.14-1.0
SQUIRREL_PLUGIN_SENT_CONF=sent_confirmation-1.6-1.2
SQUIRREL_PLUGIN_TIMEOUT=timeout_user-1.1.1-0.5
SQUIRREL_PLUGIN_VLOGIN=vlogin-3.10.1-1.2.7
SQUIRREL_PLUGIN_CHANGE_PASS=chg_sasl_passwd-1.4.1-1.4
SQUIRREL_PLUGIN_SHOW_SSL=show_ssl_link-2.2-1.2.8
SQUIRREL_PLUGIN_SHOW_IP=show_user_and_ip-3.3-re-1.2.2
SQUIRREL_PLUGIN_LOGGER=squirrel_logger-2.3-1.2.7
SQUIRREL_PLUGIN_UNSAFE_IMG=unsafe_image_rules.0.8-1.4
SQUIRREL_PLUGIN_VIEW_HTML=view_as_html-3.8

# URL for each 3rd party program

URL_PHPMYADMIN=http://downloads.sourceforge.net/phpmyadmin/$(PHPMYADMIN).tar.bz2
URL_PHPSYSINFO=http://downloads.sourceforge.net/phpsysinfo/$(PHPSYSINFO).tar.gz
URL_PHPWEBFTP=http://www.phpwebftp.com/download/$(PHPWEBFTP).zip
URL_AWSTATS=http://awstats.sourceforge.net/files/$(AWSTATS).tar.gz
URL_SQUIRRELMAIL=http://downloads.sourceforge.net/squirrelmail/$(SQUIRRELMAIL).tar.bz2
URL_YAA=http://www.sourcefiles.org/Internet/Mail/Utilities/Autoresponders/$(YAA).tar.bz2
URL_PERL_CONFIG_ABSTRACT=http://search.cpan.org/CPAN/authors/id/A/AV/AVAJADI/$(PERL_CONFIG_ABSTRACT).tar.gz
URL_PERL_NET_SERVER=http://search.cpan.org/CPAN/authors/id/R/RH/RHANDOM/$(PERL_NET_SERVER).tar.gz
URL_PERL_PHP_SERIALIZATION=http://search.cpan.org/CPAN/authors/id/B/BO/BOBTFISH/$(PERL_PHP_SERIALIZATION).tar.gz
URL_PERL_SHA_PUREPERL=http://search.cpan.org/CPAN/authors/id/M/MS/MSHELOR/$(PERL_SHA_PUREPERL).tar.gz
URL_PERL_TEMPLATE_TOOLKIT=http://search.cpan.org/CPAN/authors/id/A/AB/ABW/$(PERL_TEMPLATE_TOOLKIT).tar.gz
URL_JTA=http://javassh.org/download/$(JTA).jar

URL_SQUIRREL_PLUGIN_COMPAT=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_COMPAT).tar.gz
URL_SQUIRREL_PLUGIN_SENT_CONF=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_SENT_CONF).tar.gz
URL_SQUIRREL_PLUGIN_TIMEOUT=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_TIMEOUT).tar.gz
URL_SQUIRREL_PLUGIN_VLOGIN=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_VLOGIN).tar.gz
URL_SQUIRREL_PLUGIN_CHANGE_PASS=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_CHANGE_PASS).tar.gz
URL_SQUIRREL_PLUGIN_SHOW_SSL=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_SHOW_SSL).tar.gz
URL_SQUIRREL_PLUGIN_SHOW_IP=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_SHOW_IP).tar.gz
URL_SQUIRREL_PLUGIN_LOGGER=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_LOGGER).tar.gz
URL_SQUIRREL_PLUGIN_UNSAFE_IMG=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_UNSAFE_IMG).tar.gz
URL_SQUIRREL_PLUGIN_VIEW_HTML=http://squirrelmail.org/plugins/$(SQUIRREL_PLUGIN_VIEW_HTML).tar.gz


.PHONY: all clean cleansrc distclean rpm release getsrc dobuild gplbuild build install uninstall
.SUFFIXES: .info

all:
	@echo "Usage:"
	@echo "       make build"
	@echo "          This does all the required commands to get everything into"
	@echo "          the right place and ready for the install, including the"
	@echo "          3rd party applications"
	@echo ""
	@echo "       make bare"
	@echo "          This tells all subsequent build processes to not include"
	@echo "          any 3rd party sources that are licensed under the GPL; must"
	@echo "          be used before the others, eg; make bare build"
	@echo ""
	@echo "       make clean"
	@echo "          Clean up after a build"
	@echo ""
	@echo "       make cleansrc"
	@echo "          Clean up just the 3rd party source tarballs"
	@echo ""
	@echo "       make distclean"
	@echo "          Clean up everything, even 3rd party source tarballs"
	@echo ""
	@echo "       make install"
	@echo "          Run this after \"make build\" to install the files. The"
	@echo "          default target directory is: /usr/local/ravencore"
	@echo "          You can change the default install dir via:"
	@echo "               make RC_ROOT=/new/target/directory install"
	@echo "          You can change the destination root dir via:"
	@echo "               make DESTDIR=/new/destination/directory install"
	@echo ""
	@echo "       make uninstall"
	@echo "          Remove all files \"make install\" creates"
	@echo ""
	@echo "       make getsrc"
	@echo "          Grab all the 3rd party source tarballs"
	@echo ""
	@echo "       make rpm"
	@echo "          Build an RPM package which you can install/upgrade"
	@echo ""
	@echo "       make release"
	@echo "          Build a release RPM package"
	@echo ""


clean:
	rm -rf `cat .gitignore | grep -v bare.info | grep -v '^src/'`


cleansrc:
	rm -rf `cat .gitignore | grep -v bare.info | grep '^src/'`


distclean:
	rm -rf `cat .gitignore`


rpm:
	./src/git2rpm.sh


release:
	DO_RELEASE=1 ./src/git2rpm.sh


bare:
	@touch bare.info


getsrc:

	# Download anything that we don't have
	@for target in \
		$(URL_YAA) $(URL_PERL_NET_SERVER) $(URL_PERL_PHP_SERIALIZATION) $(URL_PERL_SHA_PUREPERL) \
		$(URL_PERL_TEMPLATE_TOOLKIT) $(URL_PERL_CONFIG_ABSTRACT); do \
		./src/get3rdparty.sh $$target; \
	done

	# only download GPL 3rd party applications if the bare target is not specified
	@if [ ! -f bare.info ]; then \
		for target in \
			$(URL_JTA) $(URL_AWSTATS) $(URL_PHPSYSINFO) $(URL_PHPWEBFTP) $(URL_PHPMYADMIN) \
			$(URL_SQUIRRELMAIL) $(URL_SQUIRREL_PLUGIN_COMPAT) $(URL_SQUIRREL_PLUGIN_SENT_CONF) \
			$(URL_SQUIRREL_PLUGIN_TIMEOUT) $(URL_SQUIRREL_PLUGIN_VLOGIN) $(URL_SQUIRREL_PLUGIN_CHANGE_PASS) \
			$(URL_SQUIRREL_PLUGIN_SHOW_SSL) $(URL_SQUIRREL_PLUGIN_SHOW_IP) $(URL_SQUIRREL_PLUGIN_LOGGER) \
			$(URL_SQUIRREL_PLUGIN_UNSAFE_IMG) $(URL_SQUIRREL_PLUGIN_VIEW_HTML); do \
			./src/get3rdparty.sh $$target; \
		done \
	fi


dobuild: clean getsrc

	# make sure /bin/bash exists
	@if [ ! -f /bin/bash ] && [ -f /usr/local/bin/bash ]; then ln -s /usr/local/bin/bash /bin/bash; fi
	@if [ ! -f /bin/bash ]; then exit 1; fi

	# Make our target directories
	mkdir -p src/ravencore/server/var/apps src/ravencore/server/var/log src/ravencore/server/var/run src/ravencore/server/var/tmp

	# Tell us what version of RavenCore this is
	echo $(VERSION) > src/ravencore/server/etc/version

	# Touch and chmod the src/ravencore/server.httpd file
	touch src/ravencore/server/sbin/ravencore.httpd
	chmod 755 src/ravencore/server/sbin/ravencore.httpd

	# Config::Abstract install
	tar zxf src/3rdparty/$(PERL_CONFIG_ABSTRACT).tar.gz
	cd $(PERL_CONFIG_ABSTRACT) && perl Makefile.PL && make
	cp -a $(PERL_CONFIG_ABSTRACT)/blib/lib/Config src/ravencore/server/lib

	# Net::Server install
	tar zxf src/3rdparty/$(PERL_NET_SERVER).tar.gz
	cd $(PERL_NET_SERVER) && perl Makefile.PL && make
	cp -a $(PERL_NET_SERVER)/blib/lib/Net src/ravencore/server/lib

	# PHP::Serialization install
	tar zxf src/3rdparty/$(PERL_PHP_SERIALIZATION).tar.gz
	cd $(PERL_PHP_SERIALIZATION) && perl Makefile.PL && make
	cp -a $(PERL_PHP_SERIALIZATION)/blib/lib/PHP src/ravencore/server/lib

	# Digest::SHA::PurePerl install
	tar zxf src/3rdparty/$(PERL_SHA_PUREPERL).tar.gz
	cd $(PERL_SHA_PUREPERL) && perl Makefile.PL && make
	cp -a $(PERL_SHA_PUREPERL)/blib/lib/Digest src/ravencore/server/lib

	# Template::Toolkit install
	tar zxf src/3rdparty/$(PERL_TEMPLATE_TOOLKIT).tar.gz
	cd $(PERL_TEMPLATE_TOOLKIT) && perl Makefile.PL TT_XS_ENABLE=n TT_ACCEPT=y && make
	cp -a $(PERL_TEMPLATE_TOOLKIT)/blib/lib/Template src/ravencore/server/lib
	cp -a $(PERL_TEMPLATE_TOOLKIT)/blib/lib/Template.pm src/ravencore/server/lib

	# remove perl .exists files
	find src/ravencore/server/lib -type f | grep '\.exists$$' | xargs rm -rf

	@if [ ! -f bare.info ]; then \
		$(MAKE) gplbuild; \
	fi


gplbuild:

	# yaa install
	tar -C src/ravencore/server/var/apps -jxf src/3rdparty/$(YAA).tar.bz2; \
	mv src/ravencore/server/var/apps/$(YAA) src/ravencore/server/var/apps/yaa

	# awstats install
	tar -C src/ravencore/server/var/apps -zxf src/3rdparty/$(AWSTATS).tar.gz; \
	mv src/ravencore/server/var/apps/$(AWSTATS) src/ravencore/server/var/apps/awstats

	# phpsysinfo install
	tar -C src/ravencore/server/var/apps -zxf src/3rdparty/$(PHPSYSINFO).tar.gz

	# add ravencore auth to phpsyinfo's index page
	echo -e '<?php\n\nchdir("../../../httpdocs");\n\ninclude "auth.php";\n\nreq_admin();\n\nchdir("../var/apps/phpsysinfo");\n\n' > src/ravencore/server/var/apps/phpsysinfo/index.php.new

	# append index.php to the new one, removeing the first line: <?php
	cat src/ravencore/server/var/apps/phpsysinfo/index.php | sed '1d' >> src/ravencore/server/var/apps/phpsysinfo/index.php.new
	cp -f src/ravencore/server/var/apps/phpsysinfo/index.php.new src/ravencore/server/var/apps/phpsysinfo/index.php

	# move the conf file into place
	mv -f src/ravencore/server/var/apps/phpsysinfo/config.php.new src/ravencore/server/var/apps/phpsysinfo/config.php

	# phpmyadmin install
	tar -C src/ravencore/server/var/apps -jxf src/3rdparty/$(PHPMYADMIN).tar.bz2
	mv src/ravencore/server/var/apps/$(PHPMYADMIN) src/ravencore/server/var/apps/phpmyadmin

	# lang / user / pass / db are bassed off of a session set by phpmyadmin.php
	cat src/ravencore/server/var/apps/phpmyadmin/libraries/config.default.php | \
		sed "s/= 'config';/= 'http';/" > \
		src/ravencore/server/var/apps/phpmyadmin/config.inc.php

	# phpwebftp install
	unzip -qd src/ravencore/server/var/apps src/3rdparty/$(PHPWEBFTP).zip

	mv src/ravencore/server/var/apps/phpWebFTP src/ravencore/server/var/apps/phpwebftp

	echo -e '<?php\n\nchdir("../../../httpdocs");\ninclude("auth.php");\nchdir("../var/apps/phpwebftp");\n\n' > src/ravencore/server/var/apps/phpwebftp/config.inc.php.new

	# append to the new one, removeing the first line: <?php
	cat src/ravencore/server/var/apps/phpwebftp/config.inc.php | sed '1d' >> src/ravencore/server/var/apps/phpwebftp/config.inc.php.new
	mv -f src/ravencore/server/var/apps/phpwebftp/config.inc.php.new src/ravencore/server/var/apps/phpwebftp/config.inc.php
	rm -rf src/ravencore/server/var/apps/phpwebftp/CVS src/ravencore/server/var/apps/phpwebftp/*/CVS src/ravencore/server/var/apps/phpwebftp/*/*/CSV src/ravencore/server/var/apps/phpwebftp/tmp

	# link the tmp directory to our tmp
	ln -s ../../tmp src/ravencore/server/var/apps/phpwebftp/tmp

	# change the default language to english
	perl -pi -e 's|defaultLanguage = "nl"|defaultLanguage = "en"|g' src/ravencore/server/var/apps/phpwebftp/config.inc.php

	# change maxFileSize
	perl -pi -e 's|maxFileSize = 2000000|maxFileSize = 104857600|g' src/ravencore/server/var/apps/phpwebftp/config.inc.php

	# add the locale charset to the filemanager
	perl -pi -e "s|\</HEAD\>|<meta http-equiv=\"Content-Type\" content=\"text/html; charset='<?php print locale_getcharset(); ?>'\"></HEAD>|gi" src/ravencore/server/var/apps/phpwebftp/index.php

	# squirrelmail install
	tar -C src/ravencore/server/var/apps -jxf src/3rdparty/$(SQUIRRELMAIL).tar.bz2
	mv src/ravencore/server/var/apps/$(SQUIRRELMAIL) src/ravencore/server/var/apps/squirrelmail

	# rearrange docs
	mv src/ravencore/server/var/apps/squirrelmail/doc/ReleaseNotes src/ravencore/server/var/apps/squirrelmail/doc/ReleaseNotes.txt

	# webmail config
	cp -f src/3rdparty/webmail_config.php src/ravencore/server/var/apps/squirrelmail/config/config.php

	# get rid of the config_local.php file so we don't overwrite theirs
	rm -f src/ravencore/server/var/apps/squirrelmail/config/config_local.php

	# default webmail user prefs
	cp -f src/3rdparty/webmail_default_pref src/ravencore/server/var/apps/squirrelmail/data/default_pref

	# install squirrelmail plugins
	for plugin in \
			$(SQUIRREL_PLUGIN_COMPAT) \
			$(SQUIRREL_PLUGIN_SENT_CONF) \
			$(SQUIRREL_PLUGIN_TIMEOUT) \
			$(SQUIRREL_PLUGIN_VLOGIN) \
			$(SQUIRREL_PLUGIN_CHANGE_PASS) \
			$(SQUIRREL_PLUGIN_SHOW_SSL) \
			$(SQUIRREL_PLUGIN_SHOW_IP) \
			$(SQUIRREL_PLUGIN_LOGGER) \
			$(SQUIRREL_PLUGIN_UNSAFE_IMG) \
			$(SQUIRREL_PLUGIN_VIEW_HTML); do \
		tar -C src/ravencore/server/var/apps/squirrelmail/plugins -zxf src/3rdparty/$$plugin.tar.gz; \
	done

	# vlogin plugin configuration file
	cp src/ravencore/server/var/apps/squirrelmail/plugins/vlogin/data/config_default.php \
		src/ravencore/server/var/apps/squirrelmail/plugins/vlogin/data/config.php

	# sent_confirmation config file
	cp -f src/3rdparty/webmail_sc_config.php src/ravencore/server/var/apps/squirrelmail/plugins/sent_confirmation/config.php

	# change_pass script replacement, add patch, and config setup
	cp -f src/misc/webmail_chgsaslpasswd.pl src/ravencore/server/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd
	chmod +x src/ravencore/server/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd
	rm -f src/ravencore/server/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd.c
	cp -f src/3rdparty/webmail_pw_config.php src/ravencore/server/var/apps/squirrelmail/plugins/chg_sasl_passwd/config.php 
	patch -p1 -i src/3rdparty/webmail_pw_options.patch

	# various squirrel configuration files
	cp -f src/3rdparty/webmail_logger_config.php src/ravencore/server/var/apps/squirrelmail/plugins/squirrel_logger/config.php
	cp -f src/ravencore/server/var/apps/squirrelmail/plugins/show_user_and_ip/config.php.sample src/ravencore/server/var/apps/squirrelmail/plugins/show_user_and_ip/config.php
	cp -f src/ravencore/server/var/apps/squirrelmail/plugins/show_ssl_link/config.php.sample src/ravencore/server/var/apps/squirrelmail/plugins/show_ssl_link/config.php

	# install jta
	mkdir -p src/ravencore/server/var/apps/jta
	cp src/3rdparty/$(JTA).jar src/ravencore/server/var/apps/jta/jta.jar
	cp src/3rdparty/jta.config.php src/ravencore/server/var/apps/jta/config.php
	cp src/3rdparty/jta.index.php src/ravencore/server/var/apps/jta/index.php

	@touch build.info


build: dobuild

	# we're done
	@echo ""
	@echo "make build done"
	@echo ""
	@echo "run \"make install\" to install the RavenCore files"


install:

	# check to make sure the "make build" ran
	@if [ ! -f build.info ]; then \
		$(MAKE) build; \
	fi

	@echo "RavenCore root directory set to: $(RC_ROOT)"
	@echo "RavenCore etc conf file set to: $(ETC_RAVENCORE)"

	# Create the etc ravencore.conf file
	@echo "# RavenCore Root Directory" > $(DESTDIR)$(ETC_RAVENCORE)
	@echo -e "RC_ROOT=$(RC_ROOT)\n" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo "# RavenCore Administrator User" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo -e "ADMIN_USER=$(ADMIN_USER)\n" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo "# Debugging level" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo -e "DEBUG=0\n" >> $(DESTDIR)$(ETC_RAVENCORE)

	# Install all the files
	mkdir -p $(DESTDIR)$(RC_ROOT)

	cp -rp -f src/ravencore/server/* $(DESTDIR)$(RC_ROOT)
	cp -rp -f src/ravencore/common/* $(DESTDIR)$(RC_ROOT)

	# Install LICENSE, README, etc
	cp -a LICENSE README $(DESTDIR)$(RC_ROOT)
	cp -a GPL $(DESTDIR)$(RC_ROOT)/httpdocs

	# create symlinks
	rm -f $(DESTDIR)/etc/cron.hourly/ravencore $(DESTDIR)/etc/cron.daily/ravencore $(DESTDIR)/etc/init.d/ravencore

	@if [ -d $(DESTDIR)/etc/cron.hourly ]; then ln -s $(RC_ROOT)/sbin/ravencore.cron $(DESTDIR)/etc/cron.hourly/ravencore; fi
	@if [ -d $(DESTDIR)/etc/cron.daily ]; then ln -s $(RC_ROOT)/sbin/ravencore.cron $(DESTDIR)/etc/cron.daily/ravencore; fi
	@if [ -d $(DESTDIR)/etc/init.d ]; then ln -s $(RC_ROOT)/sbin/ravencore.init $(DESTDIR)/etc/init.d/ravencore; fi

	@if [ -d $(DESTDIR)/etc/profile.d ]; then ln -s $(RC_ROOT)/etc/bash-profile.sh $(DESTDIR)/etc/profile.d/ravencore.sh; fi

	# logrotation, only install if the directory exists
	@if [ -d $(DESTDIR)/etc/logrotate.d ]; then cat src/logrotate-ravencore | sed "s|\$$RC_ROOT|$(RC_ROOT)|" > $(DESTDIR)/etc/logrotate.d/ravencore; fi

	# we're done
	@echo "make install done. Start RavenCore with:"
	@if [ -f $(DESTDIR)/etc/init.d/ravencore ]; then \
		echo "     /etc/init.d/ravencore start"; else \
		echo "     $(RC_ROOT)/sbin/ravencore.init start"; fi


uninstall:

	@echo -n "Uninstalling..."
	@if [ -x $(DESTDIR)$(RC_ROOT)/sbin/restore_orig_conf.sh ]; then $(DESTDIR)$(RC_ROOT)/sbin/restore_orig_conf.sh; fi
	@rm -f $(DESTDIR)$(ETC_RAVENCORE) \
		$(DESTDIR)/etc/cron.hourly/ravencore \
		$(DESTDIR)/etc/cron.daily/ravencore \
		$(DESTDIR)/etc/profile.d/ravencore.sh \
		$(DESTDIR)/etc/logrotate.d/ravencore \
		$(DESTDIR)/etc/init.d/ravencore
	@rm -rf $(DESTDIR)$(RC_ROOT)
	@echo "done."

