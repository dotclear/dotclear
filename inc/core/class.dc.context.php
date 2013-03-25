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
 * @ingroup DC_CORE
 * @brief Template extension for public context
 *
 * This extends template environment with tools required in context.
 * Admin context and public context should extend this class.
 */
class dcContext extends Twig_Extension
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
			
			'version' 		=> DC_VERSION,
			'vendor_name' 	=> DC_VENDOR_NAME,
			
			'safe_mode' 	=> isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'],
			'debug_mode'	=> DC_DEBUG
		);
	}
	
	/**
	 * Prevent call crash from template on method that return this class
	 */
	public function __toString()
	{
		return '';
	}
	
	/**
	 * Test a global variable
	 *
	 * @param string $name Name of the variable to test
	 * @return boolean
	 */
	public function __isset($name)
	{
		return isset($this->globals[$name]);
	}
	
	/**
	 * Add a global variable
	 *
	 * @param string $name Name of the variable
	 * @param mixed $value Value of the variable
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
	 * Get a global variable
	 *
	 * @param string $name Name of the variable
	 * @return mixed Value of the variable or null
	 */
	public function __get($name)
	{
		return isset($this->globals[$name]) ? $this->globals[$name] : null;
	}
	
	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array(
			'trans' => new Twig_Filter_Function("__", array('is_safe' => array('html')))
		);
	}
	
	/**
	 * Returns a list of functions to add to the existing list.
	 * 
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'__' 		=> new Twig_Function_Function("__", array('is_safe' => array('html'))),
			'debug_info' => new Twig_Function_Method($this, 'getDebugInfo', array('is_safe' => array('html'))),
			'memorize' => new Twig_Function_Method($this, 'setMemory', array('is_safe' => array('html'))),
			'memorized' => new Twig_Function_Method($this, 'getMemory', array('is_safe' => array('html')))
		);
	}
	
	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * This merges overloaded variables with defined variables.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
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
		return 'dcContext';
	}
	
	
	/**
	 * Add an informational message
	 *
	 * @param string $message A message
	 * @return object self
	 */
	public function setSafeMode($safe_mode)
	{
		$this->protected_globals['safe_mode'] = (boolean) $safe_mode;
		return $this;
	}
	
	/**
	 * Add an informational message
	 *
	 * @param string $message A message
	 * @return object self
	 */
	public function addMessageStatic($message)
	{
		$this->protected_globals['messages']['static'][] = $message;
		return $this;
	}
	
	/**
	 * Add a list of informational messages
	 *
	 * @param string $message A title
	 * @param array $message A list of messages
	 * @return object self
	 */
	public function addMessagesList($title,$messages)
	{
		$this->protected_globals['messages']['lists'][$title] = $messages;
		return $this;
	}
	
	/**
	 * Set an important message
	 *
	 * @param string $message A message
	 * @return object self
	 */
	public function setAlert($message)
	{
		$this->protected_globals['messages']['alert'] = $message;
		return $this;
	}
	
	/**
	 * Add an error message
	 *
	 * @param string Error message
	 * @return object self
	 */
	public function addError($error)
	{
		$this->protected_globals['messages']['errors'][] = $error;
		return $this;
	}
	
	/**
	 * Check if there is an error message
	 * 
	 * @return boolean
	 */
	public function hasError()
	{
		return !empty($this->protected_globals['messages']['errors']);
	}
	
	/**
	 * Get an array of debug/dev infos
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
	 * Add a value in a namespace memory
	 *
	 * This help keep variable when recalling Twig macros
	 *
	 * @param string $ns A namespace
	 * @param string $str A value to memorize in this namespace
	 */
	public function setMemory($ns,$str)
	{
		if (!array_key_exists($ns,$this->memory) || !in_array($str,$this->memory[$ns])) {
			$this->memory[$ns][] = $str;
		}
	}
	
	/**
	 * Check if a value is previously memorized in a namespace
	 *
	 * @param string $ns A namespace
	 * @param string $str A value to search in this namespace
	 * @return array True if exists
	 */
	public function getMemory($ns,$str)
	{
		return array_key_exists($ns,$this->memory) && in_array($str,$this->memory[$ns]);
	}
}
?>