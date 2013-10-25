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

/**
@ingroup DC_CORE
@brief Plugins specific handler
@since 2.6

An instance of this class is provided by dcCore $plugins property 
and used for plugins.

This class extends dcModules.
*/
class dcPlugins extends dcModules
{	
	protected static $type = 'plugin';

	/**
	This method registers a plugin in modules list. You should use this to
	register a new plugin.
	
	<var>$priority</var> is an integer. Modules are sorted by priority and name.
	Lowest priority comes first. This property is currently ignored when dealing
	with themes.
	
	@param	name			<b>string</b>		Module name
	@param	desc			<b>string</b>		Module description
	@param	author		<b>string</b>		Module author name
	@param	version		<b>string</b>		Module version
	@param	properties	<b>array</b>		extra properties (currently available keys : permissions, priority, standalone_config, type)
	*/
	public function registerModule($name,$desc,$author,$version,$properties = array())
	{
		# Fallback to legacy registerModule parameters
		if (!is_array($properties)) {
			$args = func_get_args();
			$properties = array();
			if (isset($args[4])) {
				$properties['permissions']=$args[4];
			}
			if (isset($args[5])) {
				$properties['priority']= (integer)$args[5];
			}
		}

		parent::registerModule($name, $desc, $author, $version, $properties);
	}
}
