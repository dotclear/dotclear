<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

if (!isset($__resources['help']['tags']))
{
	$__resources['help']['tags'] = dirname(__FILE__).'/help/tags.html';
}
if (!isset($__resources['help']['tag_posts']))
{
	$__resources['help']['tag_posts'] = dirname(__FILE__).'/help/tag_posts.html';
}
if (!isset($__resources['help']['tag_post']))
{
	$__resources['help']['tag_post'] = dirname(__FILE__).'/help/tag_post.html';
}

?>