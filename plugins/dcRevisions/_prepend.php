<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRrevisions, a plugin for Dotclear.
#
# Copyright (c) 2011 Tomtom and contributors
# http://blog.zenstyle.fr/
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

$__autoload['dcRevisions'] = dirname(__FILE__).'/inc/class.dc.revisions.php';
$__autoload['dcRevisionsRestMethods'] = dirname(__FILE__).'/_services.php';
$__autoload['dcRevisionsBehaviors'] = dirname(__FILE__).'/inc/class.dc.revisions.behaviors.php';
$__autoload['dcRevisionsExtensions'] = dirname(__FILE__).'/inc/class.dc.revisions.extensions.php';
$__autoload['dcRevisionsList'] = dirname(__FILE__).'/inc/class.dc.revisions.list.php';

?>