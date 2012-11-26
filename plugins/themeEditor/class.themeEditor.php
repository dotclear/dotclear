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

class dcThemeEditor
{
	protected $core;
	
	protected $default_theme;
	protected $user_theme;
	protected $parent_theme;
	
	protected $default_tpl = array();
	public $tpl = array();
	public $css = array();
	public $js  = array();
	public $po  = array();
	
	public function __construct($core)
	{
		$this->core =& $core;
		$this->default_theme = path::real($this->core->blog->themes_path.'/default');
		$this->user_theme = path::real($this->core->blog->themes_path.'/'.$this->core->blog->settings->system->theme);
		if (null !== $this->core->themes) {
			$parent_theme = $this->core->themes->moduleInfo($this->core->blog->settings->system->theme,'parent');
			if ($parent_theme) {
				$this->parent_theme = path::real($this->core->blog->themes_path.'/'.$parent_theme);
			}
		}
		$this->findTemplates();
		$this->findStyles();
		$this->findScripts();
		$this->findLocales();
	}
	
	public function filesList($type,$item='%1$s')
	{
		$files = $this->getFilesFromType($type);
		
		if (empty($files)) {
			return '<p>'.__('No file').'</p>';
		}
		
		$list = '';
		foreach ($files as $k => $v)
		{
			if (strpos($v,$this->user_theme) === 0) {
				$li = sprintf('<li class="default-file">%s</li>',$item);
			} elseif ($this->parent_theme && strpos($v,$this->parent_theme) === 0) {
				$li = sprintf('<li class="parent-file">%s</li>',$item);
			} else {
				$li = sprintf('<li>%s</li>',$item);
			}
			$list .= sprintf($li,$k,html::escapeHTML($k));
		}
		
		return sprintf('<ul>%s</ul>',$list);
	}
	
	public function getFileContent($type,$f)
	{
		$files = $this->getFilesFromType($type);
		
		if (!isset($files[$f])) {
			throw new Exception(__('File does not exist.'));
		}
		
		$F = $files[$f];
		if (!is_readable($F)) {
			throw new Exception(sprintf(__('File %s is not readable'),$f));
		}
		
		return array(
			'c' => file_get_contents($F),
			'w' => $this->getDestinationFile($type,$f) !== false,
			'type' => $type,
			'f' => $f
		);
	}
	
	public function writeFile($type,$f,$content)
	{
		$files = $this->getFilesFromType($type);
		
		if (!isset($files[$f])) {
			throw new Exception(__('File does not exist.'));
		}
		
		try
		{
			$dest = $this->getDestinationFile($type,$f);
			
			if ($dest == false) {
				throw new Exception();
			}
			
			if ($type == 'tpl' && !is_dir(dirname($dest))) {
				files::makeDir(dirname($dest));
			}
			
			if ($type == 'po' && !is_dir(dirname($dest))) {
				files::makeDir(dirname($dest));
			}
			
			$fp = @fopen($dest,'wb');
			if (!$fp) {
				throw new Exception('tocatch');
			}
			
			$content = preg_replace('/(\r?\n)/m',"\n",$content);
			$content = preg_replace('/\r/m',"\n",$content);
			
			fwrite($fp,$content);
			fclose($fp);
			
			# Updating inner files list
			$this->updateFileInList($type,$f,$dest);
		}
		catch (Exception $e)
		{
			throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'),$f));
		}
	}
	
	protected function getDestinationFile($type,$f)
	{
		if ($type == 'tpl') {
			$dest = $this->user_theme.'/tpl/'.$f;
		} elseif ($type == 'po') {
			$dest = $this->user_theme.'/locales/'.$f;
		} else {
			$dest = $this->user_theme.'/'.$f;
		}
		
		if (file_exists($dest) && is_writable($dest)) {
			return $dest;
		}
		
		if ($type == 'tpl' && !is_dir(dirname($dest))) {
			if (is_writable($this->user_theme)) {
				return $dest;
			}
		}

		if ($type == 'po' && !is_dir(dirname($dest))) {
			if (is_writable($this->user_theme)) {
				return $dest;
			}
		}

		if (is_writable(dirname($dest))) {
			return $dest;
		}
		
		return false;
	}
	
	protected function getFilesFromType($type)
	{
		switch ($type)
		{
			case 'tpl':
				return $this->tpl;
			case 'css':
				return $this->css;
			case 'js':
				return $this->js;
			case 'po':
				return $this->po;
			default:
				return array();
		}
	}
	
	protected function updateFileInList($type,$f,$file)
	{
		switch ($type)
		{
			case 'tpl':
				$list =& $this->tpl;
				break;
			case 'css':
				$list =& $this->css;
				break;
			case 'js':
				$list =& $this->js;
				break;
			case 'po':
				$list =& $this->po;
				break;
			default:
				return;
		}
		
		$list[$f] = $file;
	}
	
	protected function findTemplates()
	{
		# First, we look in template paths
		$this->default_tpl = $this->getFilesInDir($this->default_theme.'/tpl');
		
		$this->tpl = array_merge(
			$this->default_tpl,
			$this->getFilesInDir($this->parent_theme.'/tpl'),
			$this->getFilesInDir($this->user_theme.'/tpl')
			);
		$this->tpl = array_merge($this->getFilesInDir(DC_ROOT.'/inc/public/default-templates'),$this->tpl);
		
		# Then we look in 'default-templates' plugins directory
		$plugins = $this->core->plugins->getModules();
		foreach ($plugins as $p) {
			$this->tpl = array_merge($this->getFilesInDir($p['root'].'/default-templates'),$this->tpl);
		}
		
		uksort($this->tpl,array($this,'sortFilesHelper'));
	}
	
	protected function findStyles()
	{
		$this->css = $this->getFilesInDir($this->user_theme,'css');
		$this->css= array_merge($this->css,$this->getFilesInDir($this->user_theme.'/style','css','style/'));
	}
	
	protected function findScripts()
	{
		$this->js = $this->getFilesInDir($this->user_theme,'js');
		$this->js = array_merge($this->js,$this->getFilesInDir($this->user_theme.'/js','js','js/'));
	}
	
	protected function findLocales()
	{
		$langs = l10n::getISOcodes(1,1);
		foreach ($langs as $k => $v) {
			if ($this->parent_theme) {
				$this->po = array_merge($this->po,$this->getFilesInDir($this->parent_theme.'/locales/'.$v,'po',$v.'/'));
			}
			$this->po = array_merge($this->po,$this->getFilesInDir($this->user_theme.'/locales/'.$v,'po',$v.'/'));
		}
	}
	
	protected function getFilesInDir($dir,$ext=null,$prefix='')
	{
		$dir = path::real($dir);
		if (!$dir || !is_dir($dir) || !is_readable($dir)) {
			return array();
		}
		
		$d = dir($dir);
		$res = array();
		while (($f = $d->read()) !== false)
		{
			if (is_file($dir.'/'.$f) && !preg_match('/^\./',$f) && (!$ext || preg_match('/\.'.preg_quote($ext).'$/i',$f))) {
				$res[$prefix.$f] = $dir.'/'.$f;
			}
		}
		
		return $res;
	}
	
	protected function sortFilesHelper($a,$b)
	{
		if ($a == $b) {
			return 0;
		}
		
		$ext_a = files::getExtension($a);
		$ext_b = files::getExtension($b);
		
		return strcmp($ext_a.'.'.$a,$ext_b.'.'.$b);
	}
}
?>