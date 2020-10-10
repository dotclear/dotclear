#!/bin/sh

# Usage: build-module.sh [theme]
# The local directory of module must be a git repo and version will be extracted from its tags
# The module installable archive will be created inside the current directory (module root)
#
# Examples:
# (Assuming that the current path is the dotclear local repo root)
#
# Plugin module, inside plugins folder:
# $ cd plugins/my-plugin
# $ ../../build-tools/build-module.sh
#
# Theme module, inside themes folder:
# $ cd themes/my-theme
# $ ../../build-tools/build-module.sh theme

set -e

# Module type
MOD_TYPE="plugin"

# Default module version
DEF_VERSION="0.1"

# Path for JS minifier (1st argument is the js file to minify)
DIRECTORY=$(dirname "$0")
MIN_JS="$DIRECTORY/min-js.php"

# Check optionnal 1st parameter (module type)
if [ "$1" = "theme" ]; then
  MOD_TYPE="theme"
fi

# Find module name
MOD_NAME=$(basename "$PWD")
if [ "$MOD_NAME" = "themes" ] || [ "$MOD_NAME" = "plugins" ] || [ -d ./plugins ] ; then
  echo "Launch this command inside the module folder!"
  exit 1
fi

# Copy all files to tmp dir
if [ -d "$MOD_NAME" ]; then
  rm -rf ./"$MOD_NAME"
fi
mkdir ./"$MOD_NAME"
rsync --exclude-from="$DIRECTORY/build-module-rsync-exclude.txt" --exclude="$MOD_NAME" -a . ./"$MOD_NAME"

# Pack Javascript files
if [ -f "$MIN_JS" ]; then
  find ./"$MOD_NAME" -name '*.js' -exec "$MIN_JS" \{\} \;
fi

# Find last version (if any)
CUR_VERSION=$(git tag -l | sort -r -V | grep -E "[0-9]" | head -n 1)
if [ -z "$CUR_VERSION" ]; then
  CUR_VERSION=$DEF_VERSION
fi

# Make installable archive
if [ -f $MOD_TYPE-"$MOD_NAME"-$CUR_VERSION.zip ]; then
  rm $MOD_TYPE-"$MOD_NAME"-$CUR_VERSION.zip
fi
zip -q -r $MOD_TYPE-"$MOD_NAME"-$CUR_VERSION.zip ./"$MOD_NAME"/

# Cleanup
rm -rf ./"$MOD_NAME"

# Final output
echo "$MOD_TYPE-$MOD_NAME-$CUR_VERSION.zip ready!"
