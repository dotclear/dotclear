#!/bin/sh
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

# Usage (from l10n-plugins root)
# po_update.sh <dotclear-root> [<lang>]

export LANG=C

XGETTEXT=xgettext
MSGMERGE=msgmerge

PLUGINS="
aboutConfig
akismet
antispam
blogroll
blowupConfig
fairTrackbacks
importExport
maintenance
pages
pings
tags
themeEditor
widgets
userPref
"

PO_LANG=$1
PO_MODULE=$2

if [ ! -d ./inc/core ]; then
	echo "You are not on Dotclear root directory"
	exit 1
fi

if [ ! -d locales/_pot ]; then
	echo "Template not found."
	exit 1
fi

extract_strings()
{
	$XGETTEXT \
	-f- \
	--sort-by-file \
	-L PHP -k__ \
	--no-wrap \
	--foreign-user \
	"$@"
}

extract_html_strings()
{
	tee -
	
	$XGETTEXT \
	- \
	--sort-by-file \
	-L PHP -k__ \
	--no-wrap \
	--foreign-user \
	"$@"
}
update_po()
{
	po_file=$1
	pot_file=$2
	po_dir=`dirname $1`
	po_tmp=$po_dir/tmp.po~
	
	if [ ! -d $po_dir ]; then
		mkdir $po_dir
	fi
	
	if [ ! -f $po_file ]; then
		cp $pot_file $po_file
		perl -pi -e "s|; charset=CHARSET|; charset=UTF-8|sgi;" $po_file $po_file
	fi
	
	$MSGMERGE --no-location --no-wrap -o $po_tmp $po_file $pot_file
	mv $po_tmp $po_file
}

if [ -z "$PO_MODULE" ]; then
	#
	# Create po template files
	#
	echo "Building main PO template..."
	find ./admin ./inc -name '*.php' -not -regex '.*/inc/public/.*' -print | \
		extract_strings \
		--package-name="Dotclear 2" \
		-o locales/_pot/main.pot \
		-x locales/_pot/date.pot
	
	echo "DONE"
	
	# plugins.pot
	echo "Building plugins PO template..."
	for p in $PLUGINS; do
		find ./plugins/$p -name '*.php' -print
	done | \
		extract_strings \
		--package-name="Dotclear 2" \
		-o locales/_pot/plugins.pot \
		-x locales/_pot/date.pot \
		-x locales/_pot/main.pot
	
	echo "DONE"
	
	#
	# Update locales/<lang> if needed
	#
	if [ -z "$PO_LANG" ]; then
		exit 0;
	fi
	
	# Init locale if not present
	if [ ! -d locales/$PO_LANG ]; then
		mkdir -p locales/$PO_LANG/help
		
		# Base help files
		for i in locales/en/help/*.html; do
			cp $i locales/$PO_LANG/help/core_`basename $i`
		done
		for i in $PLUGINS; do
			if [ -f plugins/$i/help.html ]; then
				cp plugins/$i/help.html locales/$PO_LANG/help/$i.html
			fi
		done
	fi
	
	# update main.po
	echo "Updating <$PO_LANG> po files..."
	update_po ./locales/$PO_LANG/main.po ./locales/_pot/main.pot
	update_po ./locales/$PO_LANG/plugins.po ./locales/_pot/plugins.pot
	update_po ./locales/$PO_LANG/public.po ./locales/_pot/public.pot
	update_po ./locales/$PO_LANG/date.po ./locales/_pot/date.pot
else
	#
	# Plugin language update
	#
	
	if [ ! -d $PO_MODULE ]; then
		echo "Module $PO_MODULE does not exist"
		exit 1
	fi
	echo "Module $PO_MODULE language update"
	
	
	#
	# Building po template file
	#
	if [ ! -d $PO_MODULE/locales/_pot ]; then
		mkdir -p $PO_MODULE/locales/_pot
	fi
	echo "Building main PO template..."
	echo '<?php' >$PO_MODULE/__html_tpl_dummy.php 
	find $PO_MODULE -name '*.html' -exec grep -o '{{tpl:lang [^}]*}}' {} \; | sed 's/{{tpl:lang \(.*\)}}$/__\("\1")/' | sort -u \
		>> $PO_MODULE/__html_tpl_dummy.php
	sed -i 's/\$/\\\$/g' $PO_MODULE/__html_tpl_dummy.php 
	
	find $PO_MODULE -name '*.php' -print | \
		extract_strings \
		--package-name="Dotclear 2 `basename $PO_MODULE` module" \
		-o $PO_MODULE/locales/_pot/main.pot \
		-x locales/_pot/date.pot -x locales/_pot/main.pot -x locales/_pot/public.pot -x locales/_pot/plugins.pot
	
	rm -f $PO_MODULE/__html_tpl_dummy.php
	
	echo "DONE"
	
	#
	# Update locale/<lang>
	#
	if [ ! -d $PO_MODULE/locales/$PO_LANG ]; then
		mkdir -p $PO_MODULE/locales/$PO_LANG
	fi
	echo "Updating module <$PO_MODULE> main <$PO_LANG> po file... "
	update_po $PO_MODULE/locales/$PO_LANG/main.po $PO_MODULE/locales/_pot/main.pot
fi
