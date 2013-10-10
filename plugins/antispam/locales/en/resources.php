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

if (!isset($__resources['help']['antispam']))
{
	$__resources['help']['antispam'] = dirname(__FILE__).'/help/help.html';
}
if (!isset($__resources['help']['antispam-filters']))
{
	$__resources['help']['antispam-filters'] = dirname(__FILE__).'/help/filters.html';
}
if (!isset($__resources['help']['ip-filter']))
{
	$__resources['help']['ip-filter'] = dirname(__FILE__).'/help/ip.html';
}
if (!isset($__resources['help']['iplookup-filter']))
{
	$__resources['help']['iplookup-filter'] = dirname(__FILE__).'/help/iplookup.html';
}
if (!isset($__resources['help']['words-filter']))
{
	$__resources['help']['words-filter'] = dirname(__FILE__).'/help/words.html';
}
if (!isset($__resources['help']['antispam_comments']))
{
	$__resources['help']['antispam_comments'] = dirname(__FILE__).'/help/comments.html';
}
