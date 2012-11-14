<?php

// common to public and admin sides @see inc/admin/prepend.php
class dcTwigPage extends Twig_Environment
{
	protected $core; // not in use by now
	protected $context = null;
	
	public function __construct($default_tpl_dir,$cache_dir,$context,$core)
	{
		$this->core =& $core;
		
		# Twig Loader
		$loader = new Twig_Loader_Filesystem($this->checkDefaultTemplateDir($default_tpl_dir));
		
		# Twig Environment options
		$options = array(
			'auto_reload' => true,
			'base_template_class' => 'Twig_Template',
			'cache' => $this->setCacheDir($cache_dir),
			'charset' => 'UTF-8',
			'debug' => DC_DEBUG,
			'optimizations' => -1
		);
		
		# Twig Environment
		parent::__construct($loader,$options);
		
		# Add context helper (public vs admin)
		if ($context instanceof Twig_ExtensionInterface) {
			$this->context = $context;
			$this->addExtension($context);
		}
		
		# Add form helper
		$this->addExtension(new dcFormExtension());
		
		$this->clearCacheFiles();
	}
	
	protected function checkDefaultTemplateDir($dir)
	{
		$dir = path::real($dir,false);
		
		return is_dir($dir) ? $dir : false;
	}
	
	protected function setCacheDir($dir)
	{
		try {
			$dir = path::real($dir,false);
			files::makeDir($dir,true);
			return $dir;
		}
		catch(Exception $e) { }
		
		return false;
	}
	
	public function getContext()
	{
		return $this->context;
	}
}
?>