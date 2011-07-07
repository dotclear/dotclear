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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear generic list class.
@deprecated Please use class adminItemslist to create admin lists

Dotclear generic list handles every admin list.
*/
class adminGenericList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	
	/**
	@deprecated Please use class adminItemslist to create admin lists
	
	@param	core		<b>dcCore</b>		dcCore object
	@param	rs		<b>recordSet</b>	Items recordSet to display
	@param	rs_count	<b>int</b>		Total items number
	*/
	public function __construct($core,$rs,$rs_count)
	{
		// For backward compatibility only: the developer tried to create
		// a list with the old constructor.
		ob_start($this->raiseDeprecated(get_class($this)));
		
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->html_prev = __('&#171;prev.');
		$this->html_next = __('next&#187;');
	}
	
	/**
	Raises a E_USER_NOTICE error for deprecated classes. 
	This allows the developer to know he's been using deprecated classes.
	
	@param	name	<b>string</b>	Name of the deprecated classes that was called.
	*/
	private function raiseDeprecated($name)
	{
		if (DC_DEBUG) {
			$trace = debug_backtrace();
			array_shift($trace);
			$grand = array_shift($trace);
			$msg = 'Deprecated class called. (';
			$msg .= $name.' was called from '.$grand['file'].' ['.$grand['line'].'])';
			trigger_error($msg, E_USER_NOTICE);
		}
	}
}

?>