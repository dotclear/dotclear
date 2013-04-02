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
 * @brief Template environment.
 *
 * This class provides same Twig environment for both admin and public side. 
 * It adds methods for backwards compatibility with old template engine.
 *
 * default-templates path must be added from admin|public/prepend.php with:
 * $core->tpl->getLoader()->addPath('PATH_TO/default-templates');
 * Selected theme path must be added with:
 * $core->tpl->getLoader()->prependPath('PATH_TO/MY_THEME');
 */
class dcTemplate extends Twig_Environment
{
	protected $dc_blocks = array();
	protected $dc_values = array();
	
	private $core;
	
	/**
	 * Create template environment (Twig_Environment instance).
	 * 
	 * We keep same signature as the old dcTemplate class.
	 */
	public function __construct($cache_dir,$self_name,$core)
	{
		$this->core =& $core;
		
		# Check cache directory
		$cache_dir = path::real($cache_dir.'/twtpl',false);
		if (!is_dir($cache_dir)) {
			try {
				files::makeDir($cache_dir);
			} catch (Exception $e) {
				$cache_dir = false;
			}
		}
		
		# Create Twig environment
		parent::__construct(
			new Twig_Loader_Filesystem(dirname(__FILE__).'/../swf'),
			array(
				'auto_reload' => true,
				'autoescape' => false,
				'base_template_class' => 'Twig_Template',
				'cache' => $cache_dir, 
				'charset' => 'UTF-8',
				'debug' => DC_DEBUG,
				'optimizations' => -1,
				'strict_variables' => 0 //DC_DEBUG // Please fix undefined variables!
			)
		);
		
		# Add extensions
		$this->addExtension(new dcFormExtension($this));
		$this->addExtension(new dcTabExtension($this));
	}
	
	/// @name Old template engine compatibility methods
	//@{
	/**
	 * Old style to add a template block.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function addBlock($name,$callback)
	{
		if (!is_callable($callback)) {
			throw new Exception('No valid callback for '.$name);
		}
		
		$this->dc_blocks[$name] = $callback;
	}
	
	/**
	 * Old style to add a template value.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function addValue($name,$callback)
	{
		if (!is_callable($callback)) {
			throw new Exception('No valid callback for '.$name);
		}
		
		$this->dc_values[$name] = $callback;
	}
	
	/**
	 * Old style to check if a template block exists.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function blockExists($name)
	{
		return isset($this->dc_blocks[$name]);
	}
	
	
	/**
	 * Old style to check if a template value exists.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function valueExists($name)
	{
		return isset($this->dc_values[$name]);
	}
	
	
	/**
	 * Old style to check if a template tag exists.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function tagExists($name)
	{
		return $this->blockExists($name) || $this->valueExists($name);
	}
	
	
	/**
	 * Old style to get a template value callback.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function getValueCallback($name)
	{
		if ($this->valueExists($name))
		{
			return $this->dc_values[$name];
		}
		
		return false;
	}
	
	/**
	 * Old style to get a template block callback.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function getBlockCallback($name)
	{
		if ($this->blockExists($name))
		{
			return $this->dc_blocks[$name];
		}
		
		return false;
	}
	
	/**
	 * Old style to get list of template blocks.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function getBlocksList()
	{
		return array_keys($this->dc_blocks);
	}
	
	/**
	 * Old style to get list of template values.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function getValuesList()
	{
		return array_keys($this->dc_values);
	}
	
	/**
	 * Old style to add template path.
	 *
	 * Many plugins and themes use this method, so we keep it.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function setPath()
	{
		$path = array();
		
		foreach (func_get_args() as $v)
		{
			if (is_array($v)) {
				$path = array_merge($path,array_values($v));
			} else {
				$path[] = $v;
			}
		}
		
		foreach ($path as $k => $v)
		{
			if (($v = path::real($v)) === false) {
				unset($path[$k]);
			}
		}
		
		$path = array_unique($path);
		
		$this->getLoader()->setPaths($path);
	}
	
	/**
	 * Old style to get template path.
	 *
	 * Many plugins and themes use this method, so we keep it.
	 *
	 * @ignore
	 * @deprecated
	 */
	public function getPath()
	{
		return $this->getLoader()->getPaths();
	}
	//@}
}
?>