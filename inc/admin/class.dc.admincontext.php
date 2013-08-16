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


class dcProxy {
	protected $object;
	protected $attributes;
	protected $methods;
	protected $default;
	protected $denyfirst;

    /**
     * valuesToArray - converts a list of strings to an array having these strings as keys.
     * 
     * @param mixed $val the list to convert.
     * @access protected
     * @return mixed Value The resulting array
     */
	protected function valuesToArray($val) {
		$arr = array();
		foreach ($val as $k) {
			$arr[$k]=true;
		}
		return $arr;
	}

	protected function isAllowed ($name,$list) {
		if ($this->denyfirst) {
			return isset($list[$name]);
		} else {
			return !isset($list[$name]);
		}
	}

	public function __construct($object,$rights,$default='',$denyfirst=true) {
		$this->object = $object;
		$this->attributes = array();
		$this->methods = array();
		$this->denyfirst = $denyfirst;
		if (isset($rights['attr'])) {
			$this->attributes = $this->valuesToArray($rights['attr']);
		}
		if (isset($rights['methods'])) {
			$this->methods = $this->valuesToArray($rights['methods']);
		}
	}

	public function __get($name) {
		if ($this->isAllowed($name,$this->attributes)) {
			return $this->object->$name;
		} else {
			return $this->default;
		}
	}

	public function __call($name,$args) {
		if ($this->isAllowed($name,$this->methods) &&
			is_callable(array($this->object,$name))) {
			return call_user_func_array(array($this->object,$name),$args);
		} else {
			return $this->default;
		}

	}
}

class dcArrayProxy extends dcProxy implements ArrayAccess {
	public function offsetExists ($offset) {
		return (isset($this->value[$offset]));
	}
	public function offsetGet ($offset) {
		return new ProxyValue($this->object[$offset],$this->rights);
	}
	public function offsetSet ($offset ,$value ) {
		// Do nothing, we are read only
	}
	public function offsetUnset ($offset) {
		// Do nothing, we are read only
	}
}


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
	protected $memory = array();
	
	public function __construct($core)
	{
		$this->core = $core;
		
		# Globals editable via context
		$this->globals = array();
		
		# Globals not editable via context
		$this->protected_globals = array(
			'messages' => array(
				'static' => array(),
				'lists' => array(),
				'alert' => '',
				'errors' => array()
			),
			
			'page_title'	=> array(),
			'page_global'	=> false,
			
			'admin_url' 	=> DC_ADMIN_URL,
			'theme_url' 	=> '',
			'plugin_url' 	=> DC_ADMIN_URL.'index.php?pf=',
			
			'version' 		=> DC_VERSION,
			'vendor_name' 	=> DC_VENDOR_NAME,
			
			'safe_mode' 	=> isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'],
			'debug_mode'	=> DC_DEBUG
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
			'debug_info' => new Twig_Function_Method($this, 'getDebugInfo', array('is_safe' => array('html'))),
			'memorize' => new Twig_Function_Method($this, 'setMemory', array('is_safe' => array('html'))),
			'memorized' => new Twig_Function_Method($this, 'getMemory', array('is_safe' => array('html'))),
			'build_url' => new Twig_Function_Method($this,'buildUrl', array('is_safe' => array('html')))
		);
	}
	

    /**
     * Builds an url given a base, and parameters
     * 
     * @param mixed $url    the base url as string
     * @param mixed $params the parameters.
     *
     * @access public
     *
     * @return string the resulting url.
     */
	public function buildUrl($url,$params=array()) {
		if (is_array($url) && isset($url[0])) {
			$base = $url[0];
			if (isset($url[1]) && is_array($url[1])) {
				$p = array_merge($params,$url[1]);
			}
		} else {
			$base = $url;
			$p=$params;
		}
		if (empty($p)) {
			return $base;
		} else {
			return $base.'?'.http_build_query($p);
		}
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
		$this->protected_globals['rtl'] = l10n::getTextDirection(
			$this->protected_globals['current_user']['lang']) == 'rtl';
		$this->protected_globals['session'] = array(
			'id' => session_id(),
			'uid' => isset($_SESSION['sess_browser_uid']) ? $_SESSION['sess_browser_uid'] : '',
			'nonce' => $this->core->getNonce()
		);
		
		# Keep protected globals safe
		return array_merge($this->globals,$this->protected_globals);
	}
	
	/**
	Returns the name of the extension.
	
	@return string The extension name
	*/
	public function getName()
	{
		return 'AdminContext';
	}
	
	
	/**
	Add an informational message
	
	@param string $message A message
	@return object self
	*/
	public function setSafeMode($safe_mode)
	{
		$this->protected_globals['safe_mode'] = (boolean) $safe_mode;
		return $this;
	}
	
	/**
	Add an informational message
	
	@param string $message A message
	@return object self
	*/
	public function addMessageStatic($message)
	{
		$this->protected_globals['messages']['static'][] = $message;
		return $this;
	}
	
	/**
	Add a list of informational messages
	
	@param string $message A title
	@param array $message A list of messages
	@return object self
	*/
	public function addMessagesList($title,$messages)
	{
		$this->protected_globals['messages']['lists'][$title] = $messages;
		return $this;
	}
	
	/**
	Set an important message
	
	@param string $message A message
	@return object self
	*/
	public function setAlert($message)
	{
		$this->protected_globals['messages']['alert'] = $message;
		return $this;
	}
	
	/**
	Add an error message
	
	@param string Error message
	@return object self
	*/
	public function addError($error)
	{
		$this->protected_globals['messages']['errors'][] = $error;
		return $this;
	}
	
	/**
	Check if there is an error message
	
	@return boolean
	*/
	public function hasError()
	{
		return !empty($this->protected_globals['messages']['errors']);
	}
	
	/**
	Add a section to the breadcrumb
	
	$title can be: 
	a string for page title part or 
	TRUE to add blog name at the begining of title or
	NULL to empty/reset title
	
	@param mixed $title A title part
	@param boolean $url Link of the title part
	@return object self
	*/
	public function appendBreadCrumbItem($title,$url='',$class='')
	{
		$this->protected_globals['page_title'][] = array(
			'title' => $title,
			'link' => $url,
			'class' => $class
		);
	}
	
	/**
	Fill the page title
	
	$title can be: 
	a string for page title part or 
	TRUE to add blog name at the begining of title or
	NULL to empty/reset title
	
	@param mixed $title A title part
	@param boolean $url Link of the title part
	@return object self
	*/
	public function setBreadCrumb($breadcrumb, $with_home_link=true)
	{
		if ($with_home_link) {
			$this->appendBreadCrumbItem('<img src="style/dashboard.png" alt="" />','index.php','go_home');
		} else {
			$this->appendBreadCrumbItem('<img src="style/dashboard-alt.png" alt="" />'.$breadcrumb);
			return $this;
		}
		if (is_array($breadcrumb)) {
			foreach ($breadcrumb as $title => $bc) {
				$this->appendBreadCrumbItem($title,$bc);
			}
		} else {
			$this->appendBreadcrumbItem($breadcrumb);
		}
		return $this;
	}
	
	/**
	Check if a page title is set
	*/
	public function hasPageTitle()
	{
		return !empty($this->protected_globals['page_title']);
	}
	
	/**
	Get list of blogs
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
				$this->protected_globals['blogs'][$rs_blogs->blog_id] = 
				new dcArrayProxy($rs_blogs, array(
					'blog_id','blog_name','blog_desc','blog_url','blog_creadt','blog_upddt'));
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
		# Switch blog form
		$sform = new dcForm($this->core,'search-menu','search.php','GET');
		$sform
			->addField(
				new dcFieldText('q','',array(
				'maxlength'		=> 255,
				'label' => __('Search:'))))
			->addField(
				new dcFieldSubmit(array('ok'),__('OK'),array(
				)))
			->setup();
	}
	
	/**
	Get current blog information
	*/
	protected function getCurrentBlog()
	{
		$this->protected_globals['current_blog'] = $this->core->auth->blog_count ?
			new dcProxy($this->core->blog,array(
				'id','name','desc','url','host','creadt','upddt'
			)) : array(
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
	Get current user information
	*/
	protected function getCurrentUser()
	{
		$infos = array(
			'pwd','name','firstname','displayname',
			'email','url','default_blog','lang','tz',
			'post_status','creadt','upddt','cn'
		);
		
		$user = array(
			'id' => '',
			'super' => false,
			'lang' => 'en',
			'options' => $this->core->userDefaults(),
			'prefs' => array(),
			'rights' => array(
				'media' => false
			)
		);
		
		foreach($infos as $i) {
			$user[$i] = '';
		}
		
		if ($this->core->auth->userID()) {
		
			$user = array(
				'id' => $this->core->auth->userID(),
				'super' => $this->core->auth->isSuperAdmin(),
				'options' => $this->core->auth->getOptions(),
				'rights' => array(
					'media' => $this->core->auth->check('media,media_admin',$this->core->blog->id)
				)
			);
			
			foreach($infos as $i) {
				$user[$i] = $this->core->auth->getInfo('user_'.$i);
			}
			
			foreach($this->core->auth->user_prefs->dumpWorkspaces() as $ws => $prefs) {
				$user['prefs'][$ws] = $prefs->dumpPrefs();
			}
		}
		
		$this->protected_globals['current_user'] = $user;
	}
	
	/**
	Get sidebar menus
	*/
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
	
	/**
	Get an array of debug/dev infos
	*/
	public function getDebugInfo()
	{
		if (!DC_DEBUG) {
			return array();
		}
		
		$di = array(
			'global_vars' => implode(', ',array_keys($GLOBALS)),
			'memory' => array(
				'usage' => memory_get_usage(),
				'size' => files::size(memory_get_usage())
			),
			'xdebug' => array()
		);
		
		if (function_exists('xdebug_get_profiler_filename')) {
		
			$url = http::getSelfURI();
			$url .= strpos($url,'?') === false ? '?' : '&';
			$url .= 'XDEBUG_PROFILE';
			
			$di['xdebug'] = array(
				'elapse_time' => xdebug_time_index(),
				'profiler_file' => xdebug_get_profiler_filename(),
				'profiler_url' =>  $url
			);
			
			/* xdebug configuration:
			zend_extension = /.../xdebug.so
			xdebug.auto_trace = On
			xdebug.trace_format = 0
			xdebug.trace_options = 1
			xdebug.show_mem_delta = On
			xdebug.profiler_enable = 0
			xdebug.profiler_enable_trigger = 1
			xdebug.profiler_output_dir = /tmp
			xdebug.profiler_append = 0
			xdebug.profiler_output_name = timestamp
			*/
		}
		
		return $di;
	}
	
	/**
	Add a value in a namespace memory
	
	This help keep variable when recalling Twig macros
	
	@param string $ns A namespace
	@param string $str A value to memorize in this namespace
	*/
	public function setMemory($ns,$str)
	{
		if (!array_key_exists($ns,$this->memory) || !in_array($str,$this->memory[$ns])) {
			$this->memory[$ns][] = $str;
		}
	}
	
	/**
	Check if a value is previously memorized in a namespace
	
	@param string $ns A namespace
	@param string $str A value to search in this namespace
	@return array True if exists
	*/
	public function getMemory($ns,$str)
	{
		return array_key_exists($ns,$this->memory) && in_array($str,$this->memory[$ns]);
	}
}
?>