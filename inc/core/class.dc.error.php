<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

/**
@ingroup DC_CORE
@brief Error class

dcError is a very simple error class, with a stack. Call dcError::add to
add an error in stack. In administration area, errors are automatically
displayed.
*/
class dcError
{
	protected $errors = array();				///< <b>array</b>	Errors stack
	protected $flag = false;					///< <b>boolean</b>	True if stack is not empty
	protected $html_list = "<ul>\n%s</ul>\n";	///< <b>string</b>	HTML errors list pattern
	protected $html_item = "<li>%s</li>\n";	///< <b>string</b>	HTML error item pattern
	
	/**
	Object constructor.
	*/
	public function __construct()
	{
		$this->code = 0;
		$this->msg = '';
	}
	
	/**
	Object string representation. Returns errors stack.
	
	@return	<b>string</b>
	*/
	public function __toString()
	{
		$res = '';
		
		foreach ($this->errors as $msg)
		{
			$res .= $msg."\n";
		}
				
		return $res;
	}
	
	/**
	Adds an error to stack.
	
	@param	msg		<b>string</b>		Error message
	*/
	public function add($msg)
	{
		$this->flag = true;
		$this->errors[] = $msg;
	}
	
	/**
	Returns the value of <var>flag</var> property.
	
	@return	<b>boolean</b> True if errors stack is not empty
	*/
	public function flag()
	{
		return $this->flag;
	}
	
	/**
	Resets errors stack.
	*/
	public function reset()
	{
		$this->flag = false;
		$this->errors = array();
	}
	
	/**
	Returns <var>errors</var> property.
	
	@return	<b>array</b>
	*/
	public function getErrors()
	{
		return $this->errors;
	}
	
	/**
	Sets <var>list</var> and <var>item</var> properties.
	
	@param	list		<b>string</b>		HTML errors list pattern
	@param	item		<b>string</b>		HTML error item pattern
	*/
	public function setHTMLFormat($list,$item)
	{
		$this->html_list = $list;
		$this->html_item = $item;
	}
	
	/**
	Returns errors stack as HTML.
	
	@return	<b>string</b>
	*/
	public function toHTML()
	{
		$res = '';
		
		if ($this->flag)
		{
			foreach ($this->errors as $msg)
			{
				$res .= sprintf($this->html_item,$msg);
			}
			
			$res = sprintf($this->html_list,$res);
		}
		
		return $res;
	}
}
?>