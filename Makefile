.PHONY: config-stamp

SHELL=/bin/sh
DIST=_dist
DC=$(DIST)/dotclear

default:
	@echo "make config or make dist"

config: clean config-stamp
	mkdir -p ./$(DC)

	## Copy needed folders and files
	cp -pRf ./admin ./inc ./index.php ./CHANGELOG ./CREDITS ./LICENSE ./README.md ./CONTRIBUTING.md ./$(DC)/

	## Locales directory
	mkdir -p ./$(DC)/locales
	cp -pRf ./locales/README ./locales/en ./locales/fr ./$(DC)/locales/

	## Remove tests directories and test stuff, idem for doxygen documentation
	rm -fr ./$(DC)/inc/libs/clearbricks/tests ./$(DC)/inc/libs/clearbricks/composer.* \
				 ./$(DC)/inc/libs/clearbricks/.atoum.* ./$(DC)/inc/libs/clearbricks/vendor  \
				 ./$(DC)/inc/libs/clearbricks/bin ./$(DC)/inc/libs/clearbricks/_dist        \
				 ./$(DC)/.atoum.* ./$(DC)/tests ./$(DC)/coverage                            \
				 ./$(DC)/features ./$(DC)/travis ./$(DC)/behat.yml.dist ./$(DC)/composer.*  \
				 ./$(DC)/doxygen ./$(DC)/clearbricks/doxygen

	## Create cache, var, db, plugins, themes and public folders
	mkdir ./$(DC)/cache ./$(DC)/var ./$(DC)/db ./$(DC)/plugins ./$(DC)/themes ./$(DC)/public
	cp -p inc/.htaccess ./$(DC)/cache/
	cp -p inc/.htaccess ./$(DC)/var/
	cp -p inc/.htaccess ./$(DC)/db/
	cp -p inc/.htaccess ./$(DC)/plugins/

	## Remove config file if any
	rm -f ./$(DC)/inc/config.php

	## Copy built-in themes
	cp -pRf \
	./themes/default \
	./themes/blueSilence \
	./themes/customCSS \
	./themes/ductile \
	./themes/berlin \
	./$(DC)/themes/

	## Copy built-in plugins based on DC_DISTRIB_PLUGINS constant
	cp -pRf $$(grep DC_DISTRIB_PLUGINS inc/prepend.php | \
		sed -e "s/.*, *'//" -e "s/'.*//" | \
		sed -e  's/\(^\|,\)/ .\/plugins\//g') \
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

	## Debug off
	perl -pi -e "s|^//\*== DC_DEBUG|/*== DC_DEBUG|sgi;" $(DC)/inc/prepend.php $(DC)/inc/prepend.php

	## Remove scm files and folders from DC and CB
	find ./$(DIST)/ -type d -name '.svn' | xargs -r rm -rf
	find ./$(DIST)/ -type d -name '.hg'  | xargs -r rm -rf
	find ./$(DIST)/ -type d -name '.git' | xargs -r rm -rf
	find ./$(DIST)/ -type f -name '.*ignore' | xargs -r rm -rf
	find ./$(DIST)/ -type f -name '.flow' | xargs -r rm -rf

	## Create digest
	cd $(DC) && ( \
		md5sum `find . -type f -not -path "./inc/digest" -not -path "./cache/*" -not -path "./var/*" -not -path "./db/*" -not -path ./CHANGELOG` \
		> inc/digests \
	)

	touch config-stamp

dist: config dist-tgz dist-zip dist-l10n

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
	rm -rf $(DIST) config-stamp


## Modules (Themes and Plugins) ###############################################
pack-tool:
	[ "$(ipath)" != '' ]
	[ "$(iname)" != '' ]
	[ "$(iname)" != '' ]
	[ -d $(ipath)/$(iname) ]


copy-plugins: clean
