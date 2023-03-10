#!/bin/sh
# @package Dotclear
#
# @copyright Olivier Meunier & Association Dotclear
# @copyright GPL-2.0-only
#
# Usage (from dotclear root)
# po_update.sh <lang> [plugin-or-theme-path]

set -e

export LANG=C

XGETTEXT=xgettext
MSGMERGE=msgmerge
MSGCAT=msgcat

PLUGINS="
aboutConfig
akismet
antispam
attachments
blogroll
breadcrumb
buildtools
dcCKEditor
dcLegacyEditor
dcProxyV1
dcProxyV2
fairTrackbacks
importExport
maintenance
pages
pings
simpleMenu
tags
themeEditor
userPref
widgets
_fake_plugin
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
  -L PHP -k"__:1,2" -k"__:1" \
  --no-wrap \
  --foreign-user \
  --from-code=UTF-8 \
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

  $MSGMERGE --no-location --no-wrap --quiet -o $po_tmp $po_file $pot_file
  mv $po_tmp $po_file
}

if [ -z "$PO_MODULE" ]; then
  #
  # Create po template files
  #
  echo "Building main PO template..."
  find ./admin ./inc ./src -name '*.php' -not -regex '.*/inc/public/.*' -not -regex '.*/inc/libs/.*' -print | \
    extract_strings \
    --package-name="Dotclear 2" \
    -o locales/_pot/main.pot \
    -x locales/_pot/date.pot

  echo "- done"

  # - plugins.pot
  echo "Building plugins PO template..."
  for p in $PLUGINS; do
    if [ -d plugins/$p ]; then
      find ./plugins/$p -name '*.php' -print
    fi
  done | \
    extract_strings \
    --package-name="Dotclear 2" \
    -o locales/_pot/plugins.pot \
    -x locales/_pot/date.pot \
    -x locales/_pot/main.pot

  # - public.pot
  # Will use all default templates files and will be merged with _public.pot if necessary
  echo "Building public PO template..."
  echo '<?php' > ./__html_tpl_dummy.php
  find ./inc/public/default-templates -name '*.html' -exec grep -o '{{tpl:lang [^}]*}}' {} \; | \
    sed 's/{{tpl:lang \(.*\)}}$/__\("\1")/' | sort -u \
    >> ./__html_tpl_dummy.php
  sed -i.bak 's/\$/\\\$/g' ./__html_tpl_dummy.php
  rm -- ./__html_tpl_dummy.php.bak
  find . -name '__html_tpl_dummy.php' -print | \
    extract_strings \
    --package-name="Dotclear 2" \
    -o locales/_pot/templates.pot \
    -x locales/_pot/date.pot \
    -x locales/_pot/main.pot \
    -x locales/_pot/plugins.pot
  rm -f ./__html_tpl_dummy.php
  if [ -s locales/_pot/_public.pot ]; then
    $MSGCAT --use-first --no-wrap --sort-output locales/_pot/templates.pot locales/_pot/_public.pot > locales/_pot/public.pot
    rm -f locales/_pot/templates.pot
  else
    mv locales/_pot/templates.pot locales/_pot/public.pot
  fi

  echo "- done"

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
  echo "- done"

else
  #
  # Plugin (3rd party only) or Theme (standard or 3rd party) language update
  #

  if [ ! -d $PO_MODULE ]; then
    echo "Module $PO_MODULE does not exist"
    exit 1
  fi
  echo "Module $PO_MODULE language update"

  #
  # Building po template file
  #
  echo "Building PO template..."
  if [ ! -d $PO_MODULE/locales/_pot ]; then
    mkdir -p $PO_MODULE/locales/_pot
  fi

  # _config.php goes to admin.pot, should be loaded explicitely in _config.php:
  # l10n::set(__DIR__ . '/locales/' . dcCore::app()->lang . '/admin');

  if [ -f $PO_MODULE/_config.php ]; then
    echo "- Building admin PO template..."
    find $PO_MODULE -name '_config.php' -print | \
      extract_strings \
      --package-name="Dotclear 2 `basename $PO_MODULE` module" \
      -o $PO_MODULE/locales/_pot/admin.pot \
      -x locales/_pot/date.pot -x locales/_pot/main.pot -x locales/_pot/public.pot -x locales/_pot/plugins.pot
  else
    touch $PO_MODULE/locales/_pot/admin.pot
  fi

  if [ -f $PO_MODULE/src/Config.php ]; then
    echo "- Building admin PO template..."
    find $PO_MODULE/src -name 'Config.php' -print | \
      extract_strings \
      --package-name="Dotclear 2 `basename $PO_MODULE` module" \
      -o $PO_MODULE/locales/_pot/admin.pot \
      -x locales/_pot/date.pot -x locales/_pot/main.pot -x locales/_pot/public.pot -x locales/_pot/plugins.pot
  else
    touch $PO_MODULE/locales/_pot/admin.pot
  fi

  # All other files including templates

  echo "- Building main PO template..."
  echo '<?php' > $PO_MODULE/__html_tpl_dummy.php
  find $PO_MODULE -name '*.html' -exec grep -o '{{tpl:lang [^}]*}}' {} \; | sed 's/{{tpl:lang \(.*\)}}$/__\("\1")/' | sort -u \
    >> $PO_MODULE/__html_tpl_dummy.php
  sed -i.bak 's/\$/\\\$/g' $PO_MODULE/__html_tpl_dummy.php
  rm -- $PO_MODULE/__html_tpl_dummy.php.bak

  find $PO_MODULE -name '*.php' -not -regex '.*/_config.php' -print | \
    extract_strings \
    --package-name="Dotclear 2 `basename $PO_MODULE` module" \
    -o $PO_MODULE/locales/_pot/main.pot \
    -x $PO_MODULE/locales/_pot/admin.pot \
    -x locales/_pot/date.pot \
    -x locales/_pot/public.pot \
    -x locales/_pot/plugins.pot

  rm -f $PO_MODULE/__html_tpl_dummy.php
  if [ ! -s $PO_MODULE/locales/_pot/admin.pot ]; then
    # Remove admin.pot if is empty
    rm -f $PO_MODULE/locales/_pot/admin.pot
  fi
  if [ ! -s $PO_MODULE/locales/_pot/main.pot ]; then
    # Remove main.pot if is empty
    rm -f $PO_MODULE/locales/_pot/main.pot
  fi

  echo "- PO template built"

  #
  # Update locale/<lang>
  #
  echo "Update PO file..."
  if [ ! -d $PO_MODULE/locales/$PO_LANG ]; then
    mkdir -p $PO_MODULE/locales/$PO_LANG
  fi
  if [ -s $PO_MODULE/locales/_pot/main.pot ]; then
    echo "- Updating module <$PO_MODULE> main <$PO_LANG> po file... "
    update_po $PO_MODULE/locales/$PO_LANG/main.po $PO_MODULE/locales/_pot/main.pot
  fi
  if [ -s $PO_MODULE/locales/_pot/admin.pot ]; then
    echo "- Updating module <$PO_MODULE> admin <$PO_LANG> po file... "
    update_po $PO_MODULE/locales/$PO_LANG/admin.po $PO_MODULE/locales/_pot/admin.pot
  fi
fi
