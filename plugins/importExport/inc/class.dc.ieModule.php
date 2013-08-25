<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of importExport, a plugin for DotClear2.
#
# Copyright (c) 2003-2012 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

abstract class dcIeModule
{
	public $type;
	public $id;
	public $name;
	public $description;
	
	protected $import_url;
	protected $export_url;
	protected $core;
	
	public function __construct($core)
	{
		$this->core =& $core;
		$this->setInfo();
		
		if (!in_array($this->type,array('import','export'))) {
			throw new Exception(sprintf('Unknow type for module %s',get_class($this)));
		}
		
		if (!$this->name) {
			$this->name = get_class($this);
		}
		
		$this->id = get_class($this);
		$this->url = sprintf('plugin.php?p=importExport&type=%s&module=%s',$this->type,$this->id);
	}
	
	public function init()
	{
	}
	
	abstract protected function setInfo();
	
	final public function getURL($escape=false)
	{
		return $escape ? html::escapeHTML($this->url) : $this->url;
	}
	
	abstract public function process($do);
	
	abstract public function gui();
	
	protected function progressBar($percent)
	{
		$percent = ceil($percent);
		if ($percent > 100) {
			$percent = 100;
		}
		return '<div class="ie-progress"><div style="width:'.$percent.'%">'.$percent.' %</div></div>';
	}
	
	protected function autoSubmit()
	{
		return form::hidden(array('autosubmit'),1);
	}
	
	protected function congratMessage()
	{
		return
		'<h3>'.__('Congratulation!').'</h3>'.
		'<p class="success">'.__('Your blog has been successfully imported. Welcome on Dotclear 2!').'</p>'.
		'<ul><li><strong><a href="post.php">'.__('Why don\'t you blog this now?').'</a></strong></li>'.
		'<li>'.__('or').' <a href="index.php">'.__('visit your dashboard').'</a></li></ul>';
	}
}
?>