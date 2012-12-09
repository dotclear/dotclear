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
@brief Template extension for admin context

This extends template environment with tools required in admin context.
*/
class dcAdminContext extends Twig_Extension
{
	protected $core;
	protected $globals = array();
	protected $protected_globals = array();
	
	public function __construct($core)
	{
		$this->core = $core;
		
		# Globals editable via context
		$this->globals = array();
		
		# Globals not editable via context
		$this->protected_globals = array(
			'page_message'	=> '',
			'page_errors'	=> array(),
			'page_title'	=> '',
			
			'admin_url' 	=> DC_ADMIN_URL,
			'theme_url' 	=> DC_ADMIN_URL.'index.php?tf=',
			
			'version' 		=> DC_VERSION,
			'vendor_name' 	=> DC_VENDOR_NAME,
			
			# Blogs list (not available yet)
			'blogs' => array(),
			
			# Current blog (not available yet and never available in auth.php)
			'blog' => array(
				'id' 	=> '',
				'host' 	=> '',
				'url' 	=> '',
				'name' 	=> ''
			)
		);
	}
	
	/**
	Prevent call crash from template on method that return this class
	*/
	public function __toString()
	{
		return '';
	}
	
	/**
	Test a global variable
	
	@param string $name Name of the variable to test
	@return boolean
	*/
	public function __isset($name)
	{
		return isset($this->globals[$name]);
	}
	
	/**
	Add a global variable
	
    @param string $name Name of the variable
    @param mixed $value Value of the variable
	*/
	public function __set($name,$value)
	{
/*
		# Overload protect
		if ($value === null && isset($this->globals[$name])) {
			unset($this->globals[$name]);
		}
		elseif (!isset($this->globals[$name])) {
			throw new Exception('Modification of overloaded globals has no effect');
		}
//*/
		$this->globals[$name] = $value;
	}
	
	/**
	Get a global variable
	
	@param string $name Name of the variable
    @return mixed Value of the variable or null
	*/
	public function __get($name)
	{
		return isset($this->globals[$name]) ? $this->globals[$name] : null;
	}
	
    /**
    Returns a list of filters to add to the existing list.
    
	@return array An array of filters
    */
	public function getFilters()
	{
		return array(
			'trans' => new Twig_Filter_Function("__", array('is_safe' => array('html')))
		);
	}
	
    /**
    Returns a list of functions to add to the existing list.
    
    @return array An array of functions
    */
	public function getFunctions()
	{
		return array(
			'__' 		=> new Twig_Function_Function("__", array('is_safe' => array('html'))),
			'page_menu' => new Twig_Function_Method($this, 'pageMenu', array('is_safe' => array('html')))
		);
	}
	
    /**
    Returns a list of global variables to add to the existing list.
	
	This merges overloaded variables with defined variables.
    
    @return array An array of global variables
    */
	public function getGlobals()
	{
		# Blogs list
		if ($this->core->auth->blog_count > 1 && $this->core->auth->blog_count < 20) {
			$rs_blogs = $core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			while ($rs_blogs->fetch()) {
				$this->protected_globals['blogs'][html::escapeHTML($rs_blogs->blog_name.' - '.$rs_blogs->blog_url)] = $rs_blogs->blog_id;
			}
		}
		# Current blog
		if ($this->core->auth->blog_count) {
			$this->protected_globals['blog'] = array(
				'id' 	=> $this->core->blog->id,
				'host' 	=> $this->core->blog->host,
				'url' 	=> $this->core->blog->url,
				'name' 	=> $this->core->blog->name
			);
		}
		# Keep protected globals safe
		return array_merge($this->globals,$this->protected_globals);
	}
	
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
	public function getName()
	{
		return 'AdminContext';
	}
	
	/**
	Set information message
	
	@param string $message A message
	@return object self
	*/
	public function setMessage($message)
	{
		$this->protected_globals['page_message'] = $message;
		return $this;
	}

	/**
	Add an error message
	
	@param string Error message
	@return object self
	*/
	public function addError($error)
	{
		$this->protected_globals['page_errors'][] = $error;
		return $this;
	}
	
	/**
	Check if there is an error message
	
	@return boolean
	*/
	public function hasError()
	{
		return !empty($this->protected_globals['page_errors']);
	}
	
	/**
	Add page title
	*/
	public function setPageTitle($title)
	{
		$this->protected_globals['page_title'] = $title;
	}
	
	/**
	pageMenu
	*/
	public function pageMenu()
	{
		$menu =& $GLOBALS['_menu'];
		foreach ($menu as $k => $v) {
			echo $menu[$k]->draw();
		}
	}
}
?>