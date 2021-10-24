#!/bin/sh
# @package Dotclear
#
# @copyright Olivier Meunier & Association Dotclear
# @copyright GPL-2.0-only
#
# Usage (from dotclear root)
# po_update_core_all.sh

if [ ! -d ./inc/core ]; then
  echo "You are not on Dotclear root directory"
  exit 1
fi

LANGS=$(cd locales && ls -d -- */ | sed -e "s/\///g" | grep -v "_pot")
for l in $LANGS; do
  ./build-tools/po_update.sh "$l"
done
