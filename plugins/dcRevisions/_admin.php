<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
# 
# Copyright (c) 2010 Tomtom and contributors
# http://blog.zenstyle.fr/
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminPostForm',array('dcRevisionsBehaviors','adminPostForm'));
$core->addBehavior('adminPostHeaders',array('dcRevisionsBehaviors','adminPostHeaders'));
$core->addBehavior('adminBeforePostUpdate',array('dcRevisionsBehaviors','adminBeforeUpdate'));
$core->addBehavior('adminBeforePageUpdate',array('dcRevisionsBehaviors','adminBeforeUpdate'));

$core->rest->addFunction('getPatch',array('dcRevisionsRestMethods','getPatch'));

$core->blog->revisions = new dcRevisions($core);

if (isset($_GET['id']) && isset($_GET['patch']) && preg_match('/post.php\?id=[0-9]+(.*)$/',$_SERVER['REQUEST_URI'])) {
	$core->blog->revisions->setPatch($_GET['id'],$_GET['patch']);
}

?>