<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */		"Pages",
	/* Description*/	"Serve entries as simple web pages",
	/* Author */		"Olivier Meunier",
	/* Version */		'1.1.1',
	array(
		'permissions' =>	'contentadmin,pages',
		'priority' =>	999
	)
);
?>