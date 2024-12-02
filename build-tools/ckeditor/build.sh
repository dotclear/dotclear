#!/bin/bash

# comment to build without warning
set -e

# Download ckbuilder.jar from http://download.cksource.com/CKBuilder/
# Current release is 2.3.2

# 1) clone ckbuilder from git@github.com:ckeditor/ckbuilder.git
# 2) patch src/lib/builder.js at line 120
#
#    diff --git i/src/lib/builder.js w/src/lib/builder.js
# index df1b059..e4d023c 100644
# --- i/src/lib/builder.js
# +++ w/src/lib/builder.js
# @@ -117,7 +117,7 @@ CKBuilder.builder = function( srcDir, dstDir ) {
#         *
#         * @type {java.io.File}
#         */
# -       var targetLocation = new File( dstDir, 'ckeditor' );
# +       var targetLocation = new File( dstDir, '' );^M
#
# 3) build ckbuilder
# $ cd dev/build && ./build_jar.sh
# 4) copy ckbuilder.jar (generated in bin directory) to build-tools/ckeditor/
# 5) clone ckeditor-dev git@github.com:ckeditor/ckeditor-dev.git
# 6) build ckeditor from dotclear root directory
# $ ./build-tools/ckeditor/build.sh 4.5.8 ../ckeditor-dev

PROGNAME=$(basename $0)

if [ ! $1 ] || [ ! $2 ];then
    echo "${PROGNAME} VERSION CKEDITOR_SOURCE"
else
    VERSION=$1
    SOURCE=$2
fi

java --add-exports java.desktop/sun.java2d=ALL-UNNAMED -jar ./build-tools/ckeditor/ckbuilder.jar \
     --build $SOURCE ./plugins/dcCKEditor/js/ckeditor \
     --build-config ./build-tools/ckeditor/build-config.js \
     --overwrite \
     --version="$VERSION" \
     --skip-omitted-in-build-config \
     --leave-js-unminified \
     --leave-css-unminified \
     --no-zip \
     --no-tar
