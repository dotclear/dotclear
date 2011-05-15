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

class dcIeModule
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
		
		$this->id = get_class($this);
		if (!$this->type) {
			throw new Exception('No type for module'.$this->id);
		}
		
		$this->url = 'plugin.php?p=importExport&t='.$this->type.'&f='.$this->id;
		
		if (!$this->name) {
			$this->name = get_class($this);
		}
	}
	
	public function init()
	{
	}
	
	protected function setInfo()
	{
	}
	
	final public function getURL($escape=false)
	{
		if ($escape) {
			return html::escapeHTML($this->url);
		}
		return $this->url;
	}
	
	public function process($do)
	{
	}
	
	public function gui()
	{
	}
	
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
		'<p>'.__('Your blog has been successfully imported. Welcome on Dotclear 2!').'</p>'.
		'<ul><li><strong><a href="post.php">'.__('Why don\'t you blog this now?').'</a></strong></li>'.
		'<li>'.__('or').' <a href="index.php">'.__('visit your dashboard').'</a></li></ul>';
	}
}
?>