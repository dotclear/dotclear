.PHONY: config-stamp

SHELL=/bin/sh
DIST=_dist
DC=$(DIST)/dotclear

default:
	@echo "make config or make dist"

config: clean config-stamp
	mkdir -p ./$(DC)

	## Copy needed folders and files
	cp -pRf ./admin ./inc ./src ./index.php ./CHANGELOG ./CREDITS ./LICENSE ./README.md ./CONTRIBUTING.md ./release.json ./$(DC)/

	## Remove unnecessary folder from admin style
	rm -rf ./$(DC)/admin/style/scss

	## Locales directory
	mkdir -p ./$(DC)/locales
	cp -pRf ./locales/README ./locales/en ./locales/fr ./$(DC)/locales/

	## Create cache, var, db, plugins, themes and public folders
	mkdir ./$(DC)/cache ./$(DC)/var ./$(DC)/db ./$(DC)/plugins ./$(DC)/themes ./$(DC)/public
	cp -p inc/.htaccess ./$(DC)/cache/
	cp -p inc/.htaccess ./$(DC)/var/
	cp -p inc/.htaccess ./$(DC)/db/
	cp -p inc/.htaccess ./$(DC)/plugins/

	## Remove config file if any
	rm -f ./$(DC)/inc/config.php

	## Copy built-in themes (same list that "distributed_themes" from release.json)
	cp -pRf \
	./themes/berlin \
	./themes/blowup \
	./themes/blueSilence \
	./themes/customCSS \
	./themes/ductile \
	./$(DC)/themes/

	## Remove unnecessary folder from berlin themes
	rm -rf ./$(DC)/themes/berlin/scss

	## Copy built-in plugins (same list that "distributed_plugins" from release.json)
	cp -pRf \
	./plugins/aboutConfig \
	./plugins/akismet \
	./plugins/antispam \
	./plugins/attachments \
	./plugins/blogroll \
	./plugins/breadcrumb \
	./plugins/dcCKEditor \
	./plugins/dcLegacyEditor \
	./plugins/dcProxyV1 \
	./plugins/dcProxyV2 \
	./plugins/fairTrackbacks \
	./plugins/importExport \
	./plugins/maintenance \
	./plugins/pages \
	./plugins/pings \
	./plugins/simpleMenu \
	./plugins/tags \
	./plugins/themeEditor \
	./plugins/Uninstaller \
	./plugins/userPref \
	./plugins/widgets \
	./$(DC)/plugins/

	## "Compile" .po files
	./build-tools/make-l10n.php ./$(DC)/

	## Pack javascript files
	find $(DC)/admin/js/*.js ! -name '*.min.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/codemirror ! -name '*.min.js' -name '*.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/jquery/*.js ! -name '*.min.js' ! -name 'jquery.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/admin/js/jsUpload/*.js ! -name '*.min.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/plugins -name '*.js' ! -name '*.min.js' ! -name 'jquery.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/themes -name '*.js' ! -name '*.min.js' ! -name 'jquery.js' -exec ./build-tools/min-js.php \{\} \;
	find $(DC)/inc/js -name '*.js' ! -name '*.min.js' ! -name 'jquery.js' -exec ./build-tools/min-js.php \{\} \;

	## Remove scm files and folders from DC and CB
	find ./$(DIST)/ -type d -name '.git' | xargs -r rm -rf
	find ./$(DIST)/ -type f -name '.*ignore' | xargs -r rm -rf
	find ./$(DIST)/ -type f -name '.flow' | xargs -r rm -rf

	## Create digest
	cd $(DC) && ( \
		find . -type f -not -path "./inc/digests" -not -path "./cache/*" -not -path "./var/*" -not -path "./db/*" -not -path ./CHANGELOG \
		| sort \
		| xargs md5sum > inc/digests \
	)

	touch config-stamp

dist: config dist-tgz dist-zip dist-l10n

dist-tgz:
	[ -f config-stamp ]
	cd $(DIST) && tar cfz dotclear-$$(grep release_version dotclear/release.json | cut -d'"' -f4).tar.gz ./dotclear

dist-zip:
	[ -f config-stamp ]
	cd $(DIST) && zip -r9 dotclear-$$(grep release_version dotclear/release.json | cut -d'"' -f4).zip ./dotclear

dist-l10n:
	[ -f config-stamp ]

	rm -rf ./$(DIST)/l10n
	mkdir -p ./$(DIST)/l10n

	find ./locales/ -maxdepth 1 -mindepth 1 -type d -not -name '.svn' -not -name '_pot' -not -name 'en' \
	-exec cp -pRf \{\} ./$(DIST)/l10n/ \;

	find ./$(DIST)/l10n -type d -name '.svn' | xargs rm -rf
	./build-tools/make-l10n.php ./$(DIST)/l10n/

	cd ./$(DIST)/l10n && for i in *; do \
		zip -r9 "$$i-$$(grep release_version ../dotclear/release.json | cut -d'"' -f4).zip" "$$i"; \
		rm -rf "$$i"; \
	done


clean:
	rm -rf $(DIST) config-stamp
