
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
AWSTATS=awstats-6.9
SQUIRRELMAIL=squirrelmail-1.4.20-RC2
YAA=yaa-0.3.1
PERL_NET_SERVER=Net-Server-0.97
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
URL_AWSTATS=http://downloads.sourceforge.net/awstats/$(AWSTATS).tar.gz
URL_SQUIRRELMAIL=http://downloads.sourceforge.net/squirrelmail/$(SQUIRRELMAIL).tar.bz2
URL_YAA=http://www.sourcefiles.org/Internet/Mail/Utilities/Autoresponders/$(YAA).tar.bz2
URL_PERL_NET_SERVER=http://search.cpan.org/CPAN/authors/id/R/RH/RHANDOM/$(PERL_NET_SERVER).tar.gz
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


all:
	@echo "Usage:"
	@echo "       make build"
	@echo "          This does all the required commands to get everything into"
	@echo "          the right place and ready for the install, including the"
	@echo "          3rd party applications"
	@echo ""
	@echo "       make clean"
	@echo "          Clean up after a build"
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
	rm -rf `cat .gitignore | grep -v '^src/'`


distclean:
	rm -rf `cat .gitignore`

rpm:
	./scripts/git2rpm.sh


release:
	DO_RELEASE=1 ./scripts/git2rpm.sh


getsrc:

# Download anything that we don't have
	@./scripts/get3rdparty.sh $(URL_PHPMYADMIN)
	@./scripts/get3rdparty.sh $(URL_PHPSYSINFO)
	@./scripts/get3rdparty.sh $(URL_PHPWEBFTP)
	@./scripts/get3rdparty.sh $(URL_AWSTATS)
	@./scripts/get3rdparty.sh $(URL_SQUIRRELMAIL)
	@./scripts/get3rdparty.sh $(URL_YAA)
	@./scripts/get3rdparty.sh $(URL_PERL_NET_SERVER)
	@./scripts/get3rdparty.sh $(URL_JTA)

	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_COMPAT)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_SENT_CONF)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_TIMEOUT)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_VLOGIN)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_CHANGE_PASS)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_SHOW_SSL)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_SHOW_IP)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_LOGGER)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_UNSAFE_IMG)
	@./scripts/get3rdparty.sh $(URL_SQUIRREL_PLUGIN_VIEW_HTML)


build: clean getsrc

# make sure /bin/bash exists
	@if [ ! -f /bin/bash ] && [ -f /usr/local/bin/bash ]; then ln -s /usr/local/bin/bash /bin/bash; fi
	@if [ ! -f /bin/bash ]; then exit 1; fi

# Make our target directories
	mkdir -p ravencore/var/apps ravencore/var/log ravencore/var/run ravencore/var/tmp

# Tell us what version of RavenCore this is
	echo $(VERSION) > ravencore/etc/version

# Touch and chmod the ravencore.httpd file
	touch ravencore/sbin/ravencore.httpd
	chmod 755 ravencore/sbin/ravencore.httpd

# Net::Server install
	tar zxf src/$(PERL_NET_SERVER).tar.gz
	cd $(PERL_NET_SERVER) && perl Makefile.PL && make
	cp -rp $(PERL_NET_SERVER)/blib/lib/Net ravencore/var/lib

# yaa install
	tar -C ravencore/var/apps -jxf src/$(YAA).tar.bz2; \
	mv ravencore/var/apps/$(YAA) ravencore/var/apps/yaa

# awstats install
	tar -C ravencore/var/apps -zxf src/$(AWSTATS).tar.gz; \
	mv ravencore/var/apps/$(AWSTATS) ravencore/var/apps/awstats

# phpsysinfo install
	tar -C ravencore/var/apps -zxf src/$(PHPSYSINFO).tar.gz

# add ravencore auth to phpsyinfo's index page
	echo -e '<?php\n\nchdir("../../../httpdocs");\n\ninclude "auth.php";\n\nreq_admin();\n\nchdir("../var/apps/phpsysinfo");\n\n' > ravencore/var/apps/phpsysinfo/index.php.new

# append index.php to the new one, removeing the first line: <?php
	cat ravencore/var/apps/phpsysinfo/index.php | sed '1d' >> ravencore/var/apps/phpsysinfo/index.php.new
	cp -f ravencore/var/apps/phpsysinfo/index.php.new ravencore/var/apps/phpsysinfo/index.php

# move the conf file into place
	mv -f ravencore/var/apps/phpsysinfo/config.php.new ravencore/var/apps/phpsysinfo/config.php

# phpmyadmin install
	tar -C ravencore/var/apps -jxf src/$(PHPMYADMIN).tar.bz2
	mv ravencore/var/apps/$(PHPMYADMIN) ravencore/var/apps/phpmyadmin

# lang / user / pass / db are bassed off of a session set by phpmyadmin.php
	cat ravencore/var/apps/phpmyadmin/libraries/config.default.php | \
		sed "s/= 'config';/= 'http';/" > \
		ravencore/var/apps/phpmyadmin/config.inc.php

# phpwebftp install
	unzip -qd ravencore/var/apps src/$(PHPWEBFTP).zip

	mv ravencore/var/apps/phpWebFTP ravencore/var/apps/phpwebftp

	echo -e '<?php\n\nchdir("../../../httpdocs");\ninclude("auth.php");\nchdir("../var/apps/phpwebftp");\n\n' > ravencore/var/apps/phpwebftp/config.inc.php.new

# append to the new one, removeing the first line: <?php
	cat ravencore/var/apps/phpwebftp/config.inc.php | sed '1d' >> ravencore/var/apps/phpwebftp/config.inc.php.new
	mv -f ravencore/var/apps/phpwebftp/config.inc.php.new ravencore/var/apps/phpwebftp/config.inc.php
	rm -rf ravencore/var/apps/phpwebftp/CVS ravencore/var/apps/phpwebftp/*/CVS ravencore/var/apps/phpwebftp/*/*/CSV ravencore/var/apps/phpwebftp/tmp

# link the tmp directory to our tmp
	ln -s ../../tmp ravencore/var/apps/phpwebftp/tmp

# change the default language to english
	perl -pi -e 's|defaultLanguage = "nl"|defaultLanguage = "en"|g' ravencore/var/apps/phpwebftp/config.inc.php

# change maxFileSize
	perl -pi -e 's|maxFileSize = 2000000|maxFileSize = 104857600|g' ravencore/var/apps/phpwebftp/config.inc.php

# apply some patches, fix delete / rename bugs and remove the loggoff buttons
#	patch -p0 ravencore/var/apps/phpwebftp/index.php < src/filemanager_index.patch
#	patch -p0 ravencore/var/apps/phpwebftp/include/script.js < src/filemanager_inc_js.patch

# add the locale charset to the filemanager
	perl -pi -e "s|\</HEAD\>|<meta http-equiv=\"Content-Type\" content=\"text/html; charset='<?php print locale_getcharset(); ?>'\"></HEAD>|gi" ravencore/var/apps/phpwebftp/index.php

# squirrelmail install
	tar -C ravencore/var/apps -jxf src/$(SQUIRRELMAIL).tar.bz2
	mv ravencore/var/apps/$(SQUIRRELMAIL) ravencore/var/apps/squirrelmail

# rearrange docs
	mv ravencore/var/apps/squirrelmail/doc/ReleaseNotes ravencore/var/apps/squirrelmail/doc/ReleaseNotes.txt

# hack the redirect.php file for ravencore auto-logins by appending the real redirect.php file
#	./src/mk_webmail_redirect.sh

# webmail config
	cp -f src/webmail_config.php ravencore/var/apps/squirrelmail/config/config.php

# get rid of the config_local.php file so we don't overwrite theirs
	rm -f ravencore/var/apps/squirrelmail/config/config_local.php

# default webmail user prefs
	cp -f src/webmail_default_pref ravencore/var/apps/squirrelmail/data/default_pref

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
		tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$$plugin.tar.gz; \
	done

# vlogin plugin configuration file
	cp ravencore/var/apps/squirrelmail/plugins/vlogin/data/config_default.php \
		ravencore/var/apps/squirrelmail/plugins/vlogin/data/config.php

# sent_confirmation config file
	cp -f src/webmail_sc_config.php ravencore/var/apps/squirrelmail/plugins/sent_confirmation/config.php

# change_pass script replacement, add patch, and config setup
	cp -f src/webmail_chgsaslpasswd.pl ravencore/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd
	chmod +x ravencore/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd
	rm -f ravencore/var/apps/squirrelmail/plugins/chg_sasl_passwd/chgsaslpasswd.c
	cp -f src/webmail_pw_config.php ravencore/var/apps/squirrelmail/plugins/chg_sasl_passwd/config.php 
	patch -p1 -i src/webmail_pw_options.patch

# various squirrel configuration files
	cp -f src/webmail_logger_config.php ravencore/var/apps/squirrelmail/plugins/squirrel_logger/config.php
	cp -f ravencore/var/apps/squirrelmail/plugins/show_user_and_ip/config.php.sample ravencore/var/apps/squirrelmail/plugins/show_user_and_ip/config.php
	cp -f ravencore/var/apps/squirrelmail/plugins/show_ssl_link/config.php.sample ravencore/var/apps/squirrelmail/plugins/show_ssl_link/config.php

# install jta
	mkdir -p ravencore/var/apps/jta
	cp src/$(JTA).jar ravencore/var/apps/jta/jta.jar
	cp src/jta.config.php ravencore/var/apps/jta/config.php
	cp src/jta.index.php ravencore/var/apps/jta/index.php

# we're done
	@echo ""
	@echo "make build done"
	@echo ""
	@echo "run \"make install\" to install the RavenCore files"

install:

# check to make sure the "make build" ran
	@if [ ! -f ravencore/LICENSE ]; then \
		echo "You need to run \"make build\" before you install"; \
		exit 1; \
	fi

	@echo "RavenCore root directory set to: $(RC_ROOT)"
	@echo "RavenCore etc conf file set to: $(ETC_RAVENCORE)"

# Create the etc ravencore.conf file
	@echo "# RavenCore Root Directory" > $(DESTDIR)$(ETC_RAVENCORE)
	@echo -e "RC_ROOT=$(RC_ROOT)\n" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo "# RavenCore Administrator User" >> $(DESTDIR)$(ETC_RAVENCORE)
	@echo -e "ADMIN_USER=$(ADMIN_USER)\n" >> $(DESTDIR)$(ETC_RAVENCORE)

# Install all the files
	mkdir -p $(DESTDIR)$(RC_ROOT)

	cp -rp -f ravencore/* $(DESTDIR)$(RC_ROOT)

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

