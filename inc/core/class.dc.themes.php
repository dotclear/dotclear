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
@brief Themes specific handler

Provides an specialized object to handle themes. An instance of this
class should be created when needed.

This class extends dcModules.
*/
class dcThemes extends dcModules
{	
	protected static $type = 'theme';

	/**
	This method registers a theme in modules list. You should use this to
	register a new theme.
	
	<var>$parent</var> is a optional value to indicate them inheritance.
	If <var>$parent</var> is null / not set, we simply fall back to 
	the standard behavior, by using 'default'.
	
	<var>$priority</var> is an integer. Modules are sorted by priority and name.
	Lowest priority comes first. This property is currently ignored when dealing
	with themes.
	
	@param	name			<b>string</b>		Module name
	@param	desc			<b>string</b>		Module description
	@param	author		<b>string</b>		Module author name
	@param	version		<b>string</b>		Module version
	@param	properties	<b>array</b>		extra properties 
	(currently available keys : parent, priority, standalone_config, type)
	*/
	public function registerModule($name,$desc,$author,$version,$properties = array())
	{
		# Fallback to legacy registerModule parameters
		if (!is_array($properties)) {
			$args = func_get_args();
			$properties = array();
			if (isset($args[4])) {
				$properties['parent']=$args[4];
			}
			if (isset($args[5])) {
				$properties['priority']= (integer)$args[5];
			}
		}
		# Themes specifics properties
		$properties = array_merge(
			array('parent' => null),
			$properties,
			array('permissions' => 'admin') // force themes perms
		);

		parent::registerModule($name, $desc, $author, $version, $properties);
	}	
	
	/**
	Loads namespace <var>$ns</var> specific file for module with ID
	<var>$id</var>
	Note : actually, only 'public' namespace is supported with themes.
	
	@param	id		<b>string</b>		Module ID
	@param	ns		<b>string</b>		Namespace name
	*/
	public function loadNsFile($id,$ns=null)
	{
		switch ($ns) {
			case 'public':
				$parent = $this->modules[$id]['parent'];
				if ($parent) {
					// This is not a real cascade - since we don't call loadNsFile -,
					// thus limiting inclusion process.
					// TODO : See if we have to change this.
					$this->loadModuleFile($this->modules[$parent]['root'].'/_public.php');
				}
				$this->loadModuleFile($this->modules[$id]['root'].'/_public.php');
				break;
		}
	}
}
