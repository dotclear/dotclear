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
if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */			"Tags",
	/* Description*/		"Tags for posts",
	/* Author */			"Olivier Meunier",
	/* Version */			'1.4',
	array(
		'permissions' =>	'usage,contentadmin',
		'priority' =>		1001,	// Must be higher than dcLegacyEditor/dcCKEditor priority (ie 1000)
		'type'		=>		'plugin',
		'settings'	=>		array(
								'pref' => '#user-options.tags_prefs'
							)
	)
);
