
# RC_ROOT is where RavenCore is going to be installed

RC_ROOT=/usr/local/ravencore

# Changing this will break everything! You'll have to edit each of the shell scripts to
# reference the new conf file.... later on I'll build a tool to do automatically for you

ETC_RAVENCORE=/etc/ravencore.conf

# The current RavenCore version...

VERSION=0.3.5

# 3rd party program names and version numbers

PHPMYADMIN=phpMyAdmin-2.11.9.4-all-languages
PHPSYSINFO=phpsysinfo-2.5.4
PHPWEBFTP=phpwebftp40
AWSTATS=awstats-6.9
SQUIRRELMAIL=squirrelmail-1.4.17
YAA=yaa-0.3.1

URL_PHPMYADMIN=http://downloads.sourceforge.net/phpmyadmin/$(PHPMYADMIN).tar.bz2
URL_PHPSYSINFO=http://downloads.sourceforge.net/phpsysinfo/$(PHPSYSINFO).tar.gz
URL_AWSTATS=http://downloads.sourceforge.net/awstats/$(AWSTATS).tar.gz
URL_SQUIRRELMAIL=http://downloads.sourceforge.net/squirrelmail/$(SQUIRRELMAIL).tar.bz2

# Squirrelmail plugins to install

webmail_cp_plugin=compatibility-2.0.14-1.0
webmail_sc_plugin=sent_confirmation-1.6-1.2
webmail_tu_plugin=timeout_user-1.1.1-0.5
webmail_vl_plugin=vlogin-3.10.1-1.2.7
webmail_pw_plugin=chg_sasl_passwd-1.4.1-1.4


all:
	@echo "Usage:"
	@echo "       make build"
	@echo "          This does all the required commands to get everything into"
	@echo "          the right place and ready for the install, including the"
	@echo "          3rd party applications"
	@echo ""
	@echo "       make install"
	@echo "          Run this after \"make build\" to install the files. The"
	@echo "          default target directory is: /usr/local/ravencore"
	@echo "          You can change the default install dir via:"
	@echo "               make RC_ROOT=/new/target/directory install"
	@echo "          You can change the destination root dir via:"
	@echo "               make DESTDIR=/new/destination/directory install"
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


rpm:
	./git2rpm.sh


release:
	DO_RELEASE=1 ./git2rpm.sh


getsrc:

# Download anything that we don't have
	@if [ ! -f src/$(PHPMYADMIN).tar.bz2 ]; then wget -O src/$(PHPMYADMIN).tar.bz2 "$(URL_PHPMYADMIN)"; fi
	@if [ ! -f src/$(PHPSYSINFO).tar.gz ]; then wget -O src/$(PHPSYSINFO).tar.gz "$(URL_PHPSYSINFO)"; fi
	@if [ ! -f src/$(AWSTATS).tar.gz ]; then wget -O src/$(AWSTATS).tar.gz "$(URL_AWSTATS)"; fi
	@if [ ! -f src/$(SQUIRRELMAIL).tar.bz2 ]; then wget -O src/$(SQUIRRELMAIL).tar.bz2 "$(URL_SQUIRRELMAIL)"; fi

# check md5sums
	md5sum -c src/md5sum.list


build: getsrc

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

# TODO: build / install third party apps in a seperate .sh file so it can be coded to be more
#       modular
#	./src/build_3rd_party.sh

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
	./src/mk_phpmyadmin_config.sh

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

# hack the redirect.php file for ravencore auto-logins by appending the real redirect.php file
#	./src/mk_webmail_redirect.sh

# webmail config
	cp -f src/webmail_config.php ravencore/var/apps/squirrelmail/config/config.php

# get rid of the config_local.php file so we don't overwrite theirs
	rm -f ravencore/var/apps/squirrelmail/config/config_local.php

# default webmail user prefs
	cp -f src/webmail_default_pref ravencore/var/apps/squirrelmail/data/default_pref

# install squirrelmail plugins
	tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$(webmail_cp_plugin).tar.gz
	tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$(webmail_sc_plugin).tar.gz
	tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$(webmail_tu_plugin).tar.gz
	tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$(webmail_vl_plugin).tar.gz
	tar -C ravencore/var/apps/squirrelmail/plugins -zxf src/$(webmail_pw_plugin).tar.gz

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

# Install all the files
	mkdir -p $(DESTDIR)$(RC_ROOT)

	cp -rp -f ravencore/* $(DESTDIR)$(RC_ROOT)

# create symlinks
	rm -f $(DESTDIR)/etc/cron.hourly/ravencore $(DESTDIR)/etc/cron.daily/ravencore $(DESTDIR)/etc/init.d/ravencore

	@if [ -d $(DESTDIR)/etc/cron.hourly ]; then ln -s $(RC_ROOT)/sbin/ravencore.cron $(DESTDIR)/etc/cron.hourly/ravencore; fi
	@if [ -d $(DESTDIR)/etc/cron.daily ]; then ln -s $(RC_ROOT)/sbin/ravencore.cron $(DESTDIR)/etc/cron.daily/ravencore; fi
	@if [ -d $(DESTDIR)/etc/init.d ]; then ln -s $(RC_ROOT)/sbin/ravencore.init $(DESTDIR)/etc/init.d/ravencore; fi

# logrotation, only install if the directory exists
	@if [ -d $(DESTDIR)/etc/logrotate.d ]; then ./src/mk_logrotate.sh $(RC_ROOT) > $(DESTDIR)/etc/logrotate.d/ravencore; fi

# we're done
	@echo "make install done. Start RavenCore with:"
	@if [ -f $(DESTDIR)/etc/init.d/ravencore ]; then \
		echo "     /etc/init.d/ravencore start"; else \
		echo "     $(RC_ROOT)/sbin/ravencore.init start"; fi
