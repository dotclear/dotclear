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
			
			'safe_mode' 	=> isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode']
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
			'__' 		=> new Twig_Function_Function("__", array('is_safe' => array('html')))
			//,'page_menu' => new Twig_Function_Method($this, 'pageMenu', array('is_safe' => array('html')))
		);
	}
	
    /**
    Returns a list of global variables to add to the existing list.
	
	This merges overloaded variables with defined variables.
    
    @return array An array of global variables
    */
	public function getGlobals()
	{
		$this->getBlogs();
		$this->getCurrentBlog();
		$this->getCurrentUser();
		$this->getMenus();
		
		# Additional globals
		$p = path::info($_SERVER['REQUEST_URI']);
		$this->protected_globals['current_page'] = $p['base'];
		$this->protected_globals['blog_count'] = $this->core->auth->blog_count;
		
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
	
	public function setSafeMode($safe_mode)
	{
		$this->protected_globals['safe_mode'] = (boolean) $safe_mode;
		return $this;
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
	 * Get list of blogs
	 */
	protected function getBlogs()
	{
		$blog_id = '';
		
		# Blogs list
		$blogs = array();
		if ($this->core->auth->blog_count > 1 && $this->core->auth->blog_count < 20) {
			$blog_id = $this->core->blog->id;
			$rs_blogs = $this->core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			while ($rs_blogs->fetch()) {
				$blogs[$rs_blogs->blog_id] = $rs_blogs->blog_name.' - '.$rs_blogs->blog_url;
				$this->protected_globals['blogs'][$rs_blogs->blog_id] = array(
					'id' 	=> $rs_blogs->blog_id,
					'name' 	=> $rs_blogs->blog_name,
					'desc' 	=> $rs_blogs->blog_desc,
					'url' 	=> $rs_blogs->blog_url,
					'creadt'	=> $rs_blogs->blog_creadt,
					'upddt'	=> $rs_blogs->blog_upddt
				);
			}
		}
		
		# Switch blog form
		$form = new dcForm($this->core,'switchblog_menu','index.php');
		$form
			->addField(
				new dcFieldCombo('switchblog',$blog_id,$blogs,array(
				'label' => __('Blogs:'))))
			->addField(
				new dcFieldSubmit('switchblog_submit',__('ok'),array(
				'action' => 'switchblog')))
			->setup();
	}
	
	/**
	 * Get current blog information
	 */
	protected function getCurrentBlog()
	{
		$this->protected_globals['current_blog'] = $this->core->auth->blog_count ?
			array(
				'id' 	=> $this->core->blog->id,
				'name' 	=> $this->core->blog->name,
				'desc' 	=> $this->core->blog->desc,
				'url' 	=> $this->core->blog->url,
				'host' 	=> $this->core->blog->host,
				'creadt'	=> $this->core->blog->creadt,
				'upddt'	=> $this->core->blog->upddt
			) : array(
				'id' 	=> '',
				'name' 	=> '',
				'desc' 	=> '',
				'url' 	=> '',
				'host' 	=> '',
				'creadt'	=> '',
				'upddt'	=> ''
			);
	}
	
	/**
	 * Get current user information
	 */
	protected function getCurrentUser()
	{
		$this->protected_globals['current_user'] = $this->core->auth->userID() ?
			array(
				'id' 	=> $this->core->auth->userID(),
				'admin' 	=> $this->core->auth->getInfo('user_admin'),
				'name' 	=> $this->core->auth->getInfo('user_name'),
				'firstname' 	=> $this->core->auth->getInfo('user_firstname'),
				'displayname' 	=> $this->core->auth->getInfo('user_displayname'),
				'url' 	=> $this->core->auth->getInfo('user_url'),
				'blog' 	=> $this->core->auth->getInfo('user_default_blog'),
				'lang' 	=> $this->core->auth->getInfo('user_lang'),
				'tz' 	=> $this->core->auth->getInfo('user_tz'),
				'creadt' 	=> $this->core->auth->getInfo('user_creadt'),
				'cn' 	=> $this->core->auth->getInfo('user_cn')
			) :
			array(
				'id' 	=> '',
				'admin' 	=> '',
				'name' 	=> '',
				'firstname' 	=> '',
				'displayname' 	=> '',
				'url' 	=> '',
				'blog' 	=> '',
				'lang' 	=> 'en',
				'tz' 	=> '',
				'creadt' 	=> '',
				'cn' 	=> '',
			);
	}
	
	protected function getMenus()
	{
		global $_menu;
		
		$this->protected_globals['menus'] = array();
		
		if (!isset($_menu)) {
			return;
		}
		
		foreach($_menu as $m) {
			$this->protected_globals['menus'][] = array(
				'id' 		=> $m->getID(),
				'title' 		=> $m->getTitle(),
				'separator' 	=> $m->getSeparator(),
				'items' 		=> $m->getItems()
			);
		}
	}
}
?>