SHELL=/bin/sh

DIST=_dist
DC=$(DIST)/dotclear

default:
	@echo "make config or make dist"

config:
	mkdir -p ./$(DC)
	
	## Copy needed folders and files
	cp -pRf ./admin ./inc ./themes ./index.php ./CHANGELOG ./CREDITS ./LICENSE ./README ./$(DC)/
	
	## Locales directory
	mkdir -p ./$(DC)/locales
	cp -pRf ./locales/README ./locales/en ./locales/fr ./$(DC)/locales/
	
	## Create cache, db, plugins and public folders
	mkdir ./$(DC)/cache ./$(DC)/db ./$(DC)/plugins ./$(DC)/public
	cp inc/.htaccess ./$(DC)/cache/
	cp inc/.htaccess ./$(DC)/db/
	cp inc/.htaccess ./$(DC)/plugins/
	
	## Remove .svn folders
	find ./$(DIST)/ -type d -name '.svn' | xargs rm -rf

	## Remove .hg* files and folders
	find ./$(DIST)/ -type d -name '.hg*' | xargs rm -rf
	find ./$(DIST)/ -type f -name '.hg*' | xargs rm -rf
	
	## Remove config file if any
	rm -f ./$(DC)/inc/config.php
	
	## Copy built-in plugins
	cp -pRf \
	./plugins/aboutConfig \
	./plugins/akismet \
	./plugins/antispam \
	./plugins/attachments \
	./plugins/blogroll \
	./plugins/blowupConfig \
	./plugins/fairTrackbacks \
	./plugins/importExport \
	./plugins/maintenance \
	./plugins/tags \
	./plugins/pages \
	./plugins/pings \
	./plugins/simpleMenu \
	./plugins/themeEditor \
	./plugins/userPref \
	./plugins/widgets \
	./$(DC)/plugins/
	
	## Remove .svn folders
	find ./$(DIST)/ -type d -name '.svn' -print0 | xargs -0 rm -rf
	
	## Remove .hg* files and folders
	find ./$(DIST)/ -type d -name '.hg*' | xargs rm -rf
	find ./$(DIST)/ -type f -name '.hg*' | xargs rm -rf

	## "Compile" .po files
	./build-tools/make-l10n.php ./$(DC)/
	
	## Pack javascript files
	find $(DC)/admin/js/*.js -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/ie7/*.js -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/jquery/*.js -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/tool-man/*.js -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/plugins -name '*.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/themes/default/js/*.js -exec ./build-tools/min-js.php \{\} \;
	
	## Debug off
	perl -pi -e "s|^//\*== DC_DEBUG|/*== DC_DEBUG|sgi;" $(DC)/inc/prepend.php $(DC)/inc/prepend.php
	
	## Create digest
	cd $(DC) && ( \
		md5sum `find . -type f -not -path "./inc/digest" -not -path "./cache/*" -not -path "./db/*" -not -path ./CHANGELOG` \
		> inc/digests \
	)
	
	touch config-stamp

dist: config dist-tgz dist-zip dist-l10n

deb:
	cp ./README debian/README
	dpkg-buildpackage -rfakeroot

dist-tgz:
	[ -f config-stamp ]
	cd $(DIST) && tar cfz dotclear-$$(grep DC_VERSION dotclear/inc/prepend.php | cut -d"'" -f4).tar.gz ./dotclear

dist-zip:
	[ -f config-stamp ]
	cd $(DIST) && zip -r9 dotclear-$$(grep DC_VERSION dotclear/inc/prepend.php | cut -d"'" -f4).zip ./dotclear

dist-l10n:
	[ -f config-stamp ]
	
	rm -rf ./$(DIST)/l10n
	mkdir -p ./$(DIST)/l10n
	
	find ./locales/ -maxdepth 1 -mindepth 1 -type d -not -name '.svn' -not -name '_pot' -not -name 'en' \
	-exec cp -pRf \{\} ./$(DIST)/l10n/ \;
	
	find ./$(DIST)/l10n -type d -name '.svn' | xargs rm -rf
	./build-tools/make-l10n.php ./$(DIST)/l10n/
	
	cd ./$(DIST)/l10n && for i in *; do \
		zip -r9 "$$i-$$(grep DC_VERSION ../dotclear/inc/prepend.php | cut -d"'" -f4).zip" "$$i"; \
		rm -rf "$$i"; \
	done
	

clean:
	[ -f config-stamp ]
	rm -rf $(DIST)
	rm -f config-stamp build-stamp configure-stamp


## Modules (Themes and Plugins) ###############################################
pack-tool:
	[ "$(ipath)" != '' ]
	[ "$(iname)" != '' ]
	[ "$(iname)" != '' ]
	[ -d $(ipath)/$(iname) ]
	
	
