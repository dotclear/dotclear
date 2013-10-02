<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_ADMIN_CONTEXT')) { return; }

class adminModulesList
{
	public $core;
	public $modules;
	public $store;

	public static $allow_multi_install = false;
	public static $distributed_modules = array();

	protected $list_id = 'unknow';
	protected $data = array();

	protected $config_module = '';
	protected $config_file = '';
	protected $config_content = '';

	protected $path = false;
	protected $path_writable = false;
	protected $path_pattern = false;

	protected $page_url = 'plugins.php';
	protected $page_qs = '?';
	protected $page_tab = '';

	public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789';
	protected $nav_list = array();
	protected $nav_special = 'other';

	protected $sort_field = 'sname';
	protected $sort_asc = true;

	public function __construct(dcModules $modules, $modules_root, $xml_url)
	{
		$this->core = $modules->core;
		$this->modules = $modules;
		$this->store = new dcStore($modules, $xml_url);

		$this->setPathInfo($modules_root);
		$this->setNavSpecial(__('other'));
	}

	public function newList($list_id)
	{
		$this->data = array();
		$this->page_tab = '';
		$this->list_id = $list_id;

		return $this;
	}

	protected function setPathInfo($root)
	{
		$paths = explode(PATH_SEPARATOR, $root);
		$path = array_pop($paths);
		unset($paths);

		$this->path = $path;
		if (is_dir($path) && is_writeable($path)) {
			$this->path_writable = true;
			$this->path_pattern = preg_quote($path,'!');
		}

		return $this;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function isPathWritable()
	{
		return $this->path_writable;
	}

	public function isPathDeletable($root)
	{
		return $this->path_writable 
			&& preg_match('!^'.$this->path_pattern.'!', $root) 
			&& $this->core->auth->isSuperAdmin();
	}

	public function setPageURL($url)
	{
		$this->page_qs = strpos('?', $url) ? '&' : '?';
		$this->page_url = $url;

		return $this;
	}

	public function getPageURL($queries='', $with_tab=true)
	{
		return $this->page_url.
			(!empty($queries) ? $this->page_qs : '').
			(is_array($queries) ? http_build_query($queries) : $queries).
			($with_tab && !empty($this->page_tab) ? '#'.$this->page_tab : '');
	}

	public function setPageTab($tab)
	{
		$this->page_tab = $tab;

		return $this;
	}

	public function getPageTab()
	{
		return $this->page_tab;
	}

	public function getSearchQuery()
	{
		$query = !empty($_REQUEST['m_search']) ? trim($_REQUEST['m_search']) : null;
		return strlen($query) > 1 ? $query : null;
	}

	public function displaySearchForm()
	{
		$query = $this->getSearchQuery();

		if (empty($this->data) && $query === null) {
			return $this;
		}

		echo 
		'<form action="'.$this->getPageURL().'" method="get" class="fieldset">'.
		'<p><label for="m_search" class="classic">'.__('Search in repository:').'&nbsp;</label><br />'.
		form::field(array('m_search','m_search'), 30, 255, html::escapeHTML($query)).
		'<input type="submit" value="'.__('Search').'" /> ';
		if ($query) { echo ' <a href="'.$this->getPageURL().'" class="button">'.__('Reset search').'</a>'; }
		echo '</p>'.
		'</form>';

		if ($query) {
			echo 
			'<p class="message">'.sprintf(
				__('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->data)), 
				count($this->data), html::escapeHTML($query)
				).
			'</p>';
		}
		return $this;
	}

	public function setNavSpecial($str)
	{
		$this->nav_special = (string) $str;
		$this->nav_list = array_merge(str_split(self::$nav_indexes), array($this->nav_special));

		return $this;
	}

	public function getNavQuery()
	{
		return isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0];
	}

	public function displayNavMenu()
	{
		if (empty($this->data) || $this->getSearchQuery() !== null) {
			return $this;
		}

		# Fetch modules required field
		$indexes = array();
		foreach ($this->data as $id => $module) {
			if (!isset($module[$this->sort_field])) {
				continue;
			}
			$char = substr($module[$this->sort_field], 0, 1);
			if (!in_array($char, $this->nav_list)) {
				$char = $this->nav_special;
			}
			if (!isset($indexes[$char])) {
				$indexes[$char] = 0;
			}
			$indexes[$char]++;
		}

		$buttons = array();
		foreach($this->nav_list as $char) {
			# Selected letter
			if ($this->getNavQuery() == $char) {
				$buttons[] = '<li class="active" title="'.__('current selection').'"><strong> '.$char.' </strong></li>';
			}
			# Letter having modules
			elseif (!empty($indexes[$char])) {
				$title = sprintf(__('%d module', '%d modules', $indexes[$char]), $indexes[$char]);
				$buttons[] = '<li class="btn" title="'.$title.'"><a href="'.$this->getPageURL('m_nav='.$char).'" title="'.$title.'"> '.$char.' </a></li>';
			}
			# Letter without modules
			else {
				$buttons[] = '<li class="btn no-link" title="'.__('no module').'"> '.$char.' </li>';
			}
		}
		# Parse navigation menu
		echo '<div class="pager">'.__('Browse index:').' <ul>'.implode('',$buttons).'</ul></div>';

		return $this;
	}

	public function setSortField($field, $asc=true)
	{
		$this->sort_field = $field;
		$this->sort_asc = (boolean) $asc;

		return $this;
	}

	public function getSortQuery()
	{
		return !empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field;
	}

	public function displaySortForm()
	{
		//not yet implemented
	}

	/** @todo Use new mesasge system **/
	public function displayMessage($action)
	{
		switch($action) {
			case 'activate': 
				$str = __('Module successfully activated.'); break;
			case 'deactivate': 
				$str = __('Module successfully deactivated.'); break;
			case 'delete': 
				$str = __('Module successfully deleted.'); break;
			case 'install': 
				$str = __('Module successfully installed.'); break;
			case 'update': 
				$str = __('Module successfully updated.'); break;
			default:
				$str = ''; break;
		}
		if (!empty($str)) {
			dcPage::success($str);
		}
	}

	public function setModules($modules)
	{
		$this->data = array();
		if (!empty($modules) && is_array($modules)) {
			foreach($modules as $id => $module) {
				$this->data[$id] = self::parseModuleInfo($id, $module);
			}
		}
		return $this;
	}

	public function getModules()
	{
		return $this->data;
	}

	public static function parseModuleInfo($id, $module)
	{
		$label = empty($module['label']) ? $id : $module['label'];
		$name = __(empty($module['name']) ? $label : $module['name']);
		
		return array_merge(
			# Default values
			array(
				'desc' 				=> '',
				'author' 			=> '',
				'version' 			=> 0,
				'current_version' 	=> 0,
				'root' 				=> '',
				'root_writable' 	=> false,
				'permissions' 		=> null,
				'parent' 			=> null,
				'priority' 			=> 1000,
				'standalone_config' => false,
				'support' 			=> '',
				'section' 			=> '',
				'tags' 				=> '',
				'details' 			=> '',
				'sshot' 			=> ''
			),
			# Module's values
			$module,
			# Clean up values
			array(
				'id' 				=> $id,
				'sid' 				=> self::sanitizeString($id),
				'label' 			=> $label,
				'name' 				=> $name,
				'sname' 			=> self::sanitizeString($name)
			)
		);
	}

	public static function isDistributedModule($module)
	{
		$distributed_modules = self::$distributed_modules;

		return is_array($distributed_modules) && in_array($module, $distributed_modules);
	}

	public static function sortModules($modules, $field, $asc=true)
	{
		$sorter = array();
		foreach($modules as $id => $module) {
			$sorter[$id] = isset($module[$field]) ? $module[$field] : $field;
		}
		array_multisort($sorter, $asc ? SORT_ASC : SORT_DESC, $modules);

		return $modules;
	}

	public function displayModulesList($cols=array('name', 'config', 'version', 'desc'), $actions=array(), $nav_limit=false)
	{
		echo 
		'<div class="table-outer">'.
		'<table id="'.html::escapeHTML($this->list_id).'" class="modules'.(in_array('expander', $cols) ? ' expandable' : '').'">'.
		'<caption class="hidden">'.html::escapeHTML(__('Modules list')).'</caption><tr>';

		if (in_array('name', $cols)) {
			echo 
			'<th class="first nowrap"'.(in_array('icon', $cols) ? ' colspan="2"' : '').'>'.__('Name').'</th>';
		}

		if (in_array('version', $cols)) {
			echo 
			'<th class="nowrap count" scope="col">'.__('Version').'</th>';
		}

		if (in_array('current_version', $cols)) {
			echo 
			'<th class="nowrap count" scope="col">'.__('Current version').'</th>';
		}

		if (in_array('desc', $cols)) {
			echo 
			'<th class="nowrap" scope="col">'.__('Details').'</th>';
		}

		if (in_array('distrib', $cols)) {
			echo '<th'.(in_array('desc', $cols) ? '' : ' class="maximal"').'></th>';
		}

		if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
			echo 
			'<th class="minimal nowrap">'.__('Action').'</th>';
		}

		echo 
		'</tr>';

		$sort_field = $this->getSortQuery();

		# Sort modules by $sort_field (default sname)
		$modules = $this->getSearchQuery() === null ?
			self::sortModules($this->data, $sort_field, $this->sort_asc) :
			$this->data;

		$count = 0;
		foreach ($modules as $id => $module)
		{
			# Show only requested modules
			if ($nav_limit && $this->getSearchQuery() === null) {
				$char = substr($module[$sort_field], 0, 1);
				if (!in_array($char, $this->nav_list)) {
					$char = $this->nav_special;
				}
				if ($this->getNavQuery() != $char) {
					continue;
				}
			}

			echo 
			'<tr class="line" id="'.html::escapeHTML($this->list_id).'_m_'.html::escapeHTML($id).'">';

			if (in_array('icon', $cols)) {
				echo 
				'<td class="module-icon nowrap">'.sprintf(
					'<img alt="%1$s" title="%1$s" src="%2$s" />', 
					html::escapeHTML($id), file_exists($module['root'].'/icon.png') ? 'index.php?pf='.$id.'/icon.png' : 'images/module.png'
				).'</td>';
			}

			# Link to config file
			$config = in_array('config', $cols) && !empty($module['root']) && file_exists(path::real($module['root'].'/_config.php'));

			echo 
			'<td class="module-name nowrap" scope="row">'.($config ? 
				'<a href="'.$this->getPageURL('module='.$id.'&conf=1').'" title"'.sprintf(__('Configure module "%s"'), html::escapeHTML($module['name'])).'">'.html::escapeHTML($module['name']).'</a>' : 
				html::escapeHTML($module['name'])
			).'</td>';

			if (in_array('version', $cols)) {
				echo 
				'<td class="module-version nowrap count">'.html::escapeHTML($module['version']).'</td>';
			}

			if (in_array('current_version', $cols)) {
				echo 
				'<td class="module-current-version nowrap count">'.html::escapeHTML($module['current_version']).'</td>';
			}

			if (in_array('desc', $cols)) {
				echo 
				'<td class="module-desc maximal">'.html::escapeHTML($module['desc']).'</td>';
			}

			if (in_array('distrib', $cols)) {
				echo 
				'<td class="module-distrib">'.(self::isDistributedModule($id) ? 
					'<img src="images/dotclear_pw.png" alt="'.
					__('Module from official distribution').'" title="'.
					__('module from official distribution').'" />' 
				: '').'</td>';
			}

			if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
				$buttons = $this->getActions($id, $module, $actions);

				echo 
				'<td class="module-actions nowrap">'.

				'<form action="'.$this->getPageURL().'" method="post">'.
				'<div>'.
				$this->core->formNonce().
				form::hidden(array('module'), html::escapeHTML($id)).
				form::hidden(array('tab'), $this->page_tab).

				implode(' ', $buttons).

				'</div>'.
				'</form>'.

				'</td>';
			}

			echo 
			'</tr>';

			$count++;
		}
		echo 
		'</table></div>';

		if(!$count && $this->getSearchQuery() === null) {
			echo 
			'<p class="message">'.__('No module matches your search.').'</p>';
		}
	}

	protected function getActions($id, $module, $actions)
	{
		$submits = array();

		# Use loop to keep requested order
		foreach($actions as $action) {
			switch($action) {

				# Deactivate
				case 'activate': if ($module['root_writable']) {
					$submits[] = 
					'<input type="submit" name="activate" value="'.__('Activate').'" />';
				} break;

				# Activate
				case 'deactivate': if ($module['root_writable']) {
					$submits[] = 
					'<input type="submit" name="deactivate" value="'.__('Deactivate').'" class="reset" />';
				} break;

				# Delete
				case 'delete': if ($this->isPathDeletable($module['root'])) {
					$submits[] = 
					'<input type="submit" class="delete" name="delete" value="'.__('Delete').'" />';
				} break;

				# Install (from store)
				case 'install': if ($this->path_writable) {
					$submits[] = 
					'<input type="submit" name="install" value="'.__('Install').'" />';
				} break;

				# Update (from store)
				case 'update': if ($this->path_writable) {
					$submits[] = 
					'<input type="submit" name="update" value="'.__('Update').'" />';
				} break;
			}
		}

		return $submits;
	}

	public function doActions($prefix)
	{
		if (empty($_POST) || !empty($_REQUEST['conf']) 
		|| !$this->core->auth->isSuperAdmin() || !$this->isPathWritable()) {
			return null;
		}

		# List actions
		if (!empty($_POST['module'])) {

			$id = $_POST['module'];

			if (!empty($_POST['activate'])) {

				$enabled = $this->modules->getDisabledModules();
				if (!isset($enabled[$id])) {
					throw new Exception(__('No such module.'));
				}

				# --BEHAVIOR-- moduleBeforeActivate
				$this->core->callBehavior($prefix.'BeforeActivate', $id);

				$this->modules->activateModule($id);

				# --BEHAVIOR-- moduleAfterActivate
				$this->core->callBehavior($prefix.'AfterActivate', $id);

				http::redirect($this->getPageURL('msg=activate'));
			}

			elseif (!empty($_POST['deactivate'])) {

				if (!$this->modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$module = $this->modules->getModules($id);
				$module['id'] = $id;

				if (!$module['root_writable']) {
					throw new Exception(__('You don\'t have permissions to deactivate this module.'));
				}

				# --BEHAVIOR-- moduleBeforeDeactivate
				$this->core->callBehavior($prefix.'BeforeDeactivate', $module);

				$this->modules->deactivateModule($id);

				# --BEHAVIOR-- moduleAfterDeactivate
				$this->core->callBehavior($prefix.'AfterDeactivate', $module);

				http::redirect($this->getPageURL('msg=deactivate'));
			}

			elseif (!empty($_POST['delete'])) {

				$disabled = $this->modules->getDisabledModules();
				if (!isset($disabled[$id])) {

					if (!$this->modules->moduleExists($id)) {
						throw new Exception(__('No such module.'));
					}

					$module = $this->modules->getModules($id);
					$module['id'] = $id;

					if (!$this->isPathDeletable($module['root'])) {
						throw new Exception(__("You don't have permissions to delete this module."));
					}

					# --BEHAVIOR-- moduleBeforeDelete
					$this->core->callBehavior($prefix.'BeforeDelete', $module);

					$this->modules->deleteModule($id);

					# --BEHAVIOR-- moduleAfterDelete
					$this->core->callBehavior($prefix.'AfterDelete', $module);
				}
				else {
					$this->modules->deleteModule($id, true);
				}

				http::redirect($this->getPageURL('msg=delete'));
			}

			elseif (!empty($_POST['install'])) {

				$updated = $this->store->get();
				if (!isset($updated[$id])) {
					throw new Exception(__('No such module.'));
				}

				$module = $updated[$id];
				$module['id'] = $id;

				$dest = $this->getPath().'/'.basename($module['file']);

				# --BEHAVIOR-- moduleBeforeAdd
				$this->core->callBehavior($prefix.'BeforeAdd', $module);

				$ret_code = $this->store->process($module['file'], $dest);

				# --BEHAVIOR-- moduleAfterAdd
				$this->core->callBehavior($prefix.'AfterAdd', $module);

				http::redirect($this->getPageURL('msg='.($ret_code == 2 ? 'update' : 'install')));
			}

			elseif (!empty($_POST['update'])) {

				$updated = $store->get();
				if (!isset($updated[$id])) {
					throw new Exception(__('No such module.'));
				}

				if (!$this->modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$module = $updated[$id];
				$module['id'] = $id;
			
				if (!self::$allow_multi_install) {
					$dest = $module['root'].'/../'.basename($module['file']);
				}
				else {
					$dest = $this->getPath().'/'.basename($module['file']);
					if ($module['root'] != $dest) {
						@file_put_contents($module['root'].'/_disabled', '');
					}
				}

				# --BEHAVIOR-- moduleBeforeUpdate
				$this->core->callBehavior($prefix.'BeforeUpdate', $module);

				$this->store->process($module['file'], $dest);

				# --BEHAVIOR-- moduleAfterUpdate
				$this->core->callBehavior($prefix.'AfterUpdate', $module);

				http::redirect($this->getPageURL('msg=upadte'));
			}
		}
		# Manual actions
		elseif (!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file']) 
			|| !empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url']))
		{
			if (empty($_POST['your_pwd']) || !$this->core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY, $_POST['your_pwd']))) {
				throw new Exception(__('Password verification failed'));
			}

			if (!empty($_POST['upload_pkg'])) {
				files::uploadStatus($_FILES['pkg_file']);
				
				$dest = $this->getPath().'/'.$_FILES['pkg_file']['name'];
				if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
					throw new Exception(__('Unable to move uploaded file.'));
				}
			}
			else {
				$url = urldecode($_POST['pkg_url']);
				$dest = $this->getPath().'/'.basename($url);
				$this->store->download($url, $dest);
			}

			# --BEHAVIOR-- moduleBeforeAdd
			$this->core->callBehavior($prefix.'BeforeAdd', null);

			$ret_code = $this->store->install($dest);

			# --BEHAVIOR-- moduleAfterAdd
			$this->core->callBehavior($prefix.'AfterAdd', null);

			http::redirect($this->getPageURL('msg='.($ret_code == 2 ? 'update' : 'install')).'#'.$prefix);
		}

		return null;
	}

	public function displayManualForm()
	{
		if (!$this->core->auth->isSuperAdmin() || !$this->isPathWritable()) {
			return null;
		}

		# 'Upload module' form
		echo
		'<form method="post" action="'.$this->getPageURL().'" id="uploadpkg" enctype="multipart/form-data" class="fieldset">'.
		'<h4>'.__('Upload a zip file').'</h4>'.
		'<p class="field"><label for="pkg_file" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Zip file path:').'</label> '.
		'<input type="file" name="pkg_file" id="pkg_file" /></p>'.
		'<p class="field"><label for="your_pwd1" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
		form::password(array('your_pwd','your_pwd1'),20,255).'</p>'.
		'<p><input type="submit" name="upload_pkg" value="'.__('Upload').'" />'.
		form::hidden(array('tab'), $this->getPageTab()).
		$this->core->formNonce().'</p>'.
		'</form>';
		
		# 'Fetch module' form
		echo
		'<form method="post" action="'.$this->getPageURL().'" id="fetchpkg" class="fieldset">'.
		'<h4>'.__('Download a zip file').'</h4>'.
		'<p class="field"><label for="pkg_url" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Zip file URL:').'</label> '.
		form::field(array('pkg_url','pkg_url'),40,255).'</p>'.
		'<p class="field"><label for="your_pwd2" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
		form::password(array('your_pwd','your_pwd2'),20,255).'</p>'.
		'<p><input type="submit" name="fetch_pkg" value="'.__('Download').'" />'.
		form::hidden(array('tab'), $this->getPageTab()).
		$this->core->formNonce().'</p>'.
		'</form>';
	}

	/**
	 *
	 * We need to get configuration content in three steps
	 * and out of this class to keep backward compatibility.
	 *
	 * if ($xxx->setConfigurationFile()) {
	 *	include $xxx->getConfigurationFile();
	 * }
	 * $xxx->setConfigurationContent();
	 * ... [put here page headers and other stuff]
	 * $xxx->getConfigurationContent();
	 *
	 */
	public function setConfigurationFile($id=null)
	{
		if (empty($_REQUEST['conf']) || empty($_REQUEST['module']) && !$id) {
			return false;
		}
		
		if (!empty($_REQUEST['module']) && empty($id)) {
			$id = $_REQUEST['module'];
		}

		if (!$this->modules->moduleExists($id)) {
			$core->error->add(__('Unknow module ID'));
			return false;
		}

		$module = $this->modules->getModules($id);
		$module = self::parseModuleInfo($id, $module);
		$file = path::real($module['root'].'/_config.php');

		if (!file_exists($file)) {
			$core->error->add(__('This module has no configuration file.'));
			return false;
		}

		$this->config_module = $module;
		$this->config_file = $file;
		$this->config_content = '';

		if (!defined('DC_CONTEXT_MODULE')) {
			define('DC_CONTEXT_MODULE', true);
		}

		return true;
	}

	public function getConfigurationFile()
	{
		if (!$this->config_file) {
			return null;
		}

		ob_start();

		return $this->config_file;
	}

	public function setConfigurationContent()
	{
		if ($this->config_file) {
			$this->config_content = ob_get_contents();
		}

		ob_end_clean();

		return !empty($this->file_content);
	}

	public function getConfigurationContent()
	{
		if (!$this->config_file) {
			return null;
		}

		if (!$this->config_module['standalone_config']) {
			echo
			'<form id="module_config" action="'.$this->getPageURL('conf=1').'" method="post" enctype="multipart/form-data">'.
			'<h3>'.sprintf(__('Configure plugin "%s"'), html::escapeHTML($this->config_module['name'])).'</h3>'.
			'<p><a class="back" href="'.$this->getPageURL().'#plugins">'.__('Back').'</a></p>';
		}

		echo $this->config_content;

		if (!$this->config_module['standalone_config']) {
			echo
			'<p class="clear"><input type="submit" name="save" value="'.__('Save').'" />'.
			form::hidden('module', $this->config_module['id']).
			$this->core->formNonce().'</p>'.
			'</form>';
		}

		return true;
	}

	public static function sanitizeString($str)
	{
		return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
	}
}

class adminThemesList extends adminModulesList
{
	protected $page_url = 'blog_theme.php';

	public function displayModulesList($cols=array('name', 'config', 'version', 'desc'), $actions=array(), $nav_limit=false)
	{
		echo 
		'<div id="'.html::escapeHTML($this->list_id).'" class="modules'.(in_array('expander', $cols) ? ' expandable' : '').' one-box">';

		$sort_field = $this->getSortQuery();

		# Sort modules by id
		$modules = $this->getSearchQuery() === null ?
			self::sortModules($this->data, $sort_field, $this->sort_asc) :
			$this->data;

		$res = '';
		$count = 0;
		foreach ($modules as $id => $module)
		{
			# Show only requested modules
			if ($nav_limit && $this->getSearchQuery() === null) {
				$char = substr($module[$sort_field], 0, 1);
				if (!in_array($char, $this->nav_list)) {
					$char = $this->nav_special;
				}
				if ($this->getNavQuery() != $char) {
					continue;
				}
			}

			$current = $this->core->blog->settings->system->theme == $id;
			$distrib = self::isDistributedModule($id) ? ' dc-box' : '';

			$theme_url = preg_match('#^http(s)?://#', $this->core->blog->settings->system->themes_url) ?
				http::concatURL($this->core->blog->settings->system->themes_url, '/'.$id) :
				http::concatURL($this->core->blog->url, $this->core->blog->settings->system->themes_url.'/'.$id);

			$has_conf = file_exists(path::real($this->core->blog->themes_path.'/'.$id).'/_config.php');
			$has_css = file_exists(path::real($this->core->blog->themes_path.'/'.$id).'/style.css');
			$parent = $module['parent'];
			$has_parent = !empty($module['parent']);
			if ($has_parent) {
				$is_parent_present = $this->modules->moduleExists($parent);
			}

			$line = 
			'<div class="box '.($current ? 'medium current-theme' : 'small theme').$distrib.'">';

			if (in_array('name', $cols)) {
				$line .= 
				'<h4 class="module-name">'.html::escapeHTML($module['name']).'</h4>';
			}

			if (in_array('sshot', $cols)) {
				# Screenshot from url
				if (preg_match('#^http(s)?://#', $module['sshot'])) {
					$sshot = $module['sshot'];
				}
				# Screenshot from installed module
				elseif (file_exists($this->core->blog->themes_path.'/'.$id.'/screenshot.jpg')) {
					$sshot = $this->getPageURL('shot='.rawurlencode($id));
				}
				# Default screenshot
				else {
					$sshot = 'images/noscreenshot.png';
				}

				$line .= 
				'<div class="module-sshot"><img src="'.$sshot.'" alt="'.
				sprintf(__('%s screenshot.'), html::escapeHTML($module['name'])).'" /></div>';
			}

			$line .= 
			'<div class="module-infos toggle-bloc">'.
			'<p>';

			if (in_array('desc', $cols)) {
				$line .= 
				'<span class="module-desc">'.html::escapeHTML($module['desc']).'</span> ';
			}

			if (in_array('author', $cols)) {
				$line .= 
				'<span class="module-author">'.sprintf(__('by %s'),html::escapeHTML($module['author'])).'</span> ';
			}

			if (in_array('version', $cols)) {
				$line .= 
				'<span class="module-version">'.sprintf(__('version %s'),html::escapeHTML($module['version'])).'</span> ';
			}

			if (in_array('parent', $cols) && $has_parent) {
				if ($is_parent_present) {
					$line .= 
					'<span class="module-parent-ok">'.sprintf(__('(built on "%s")'),html::escapeHTML($parent)).'</span> ';
				}
				else {
					$line .= 
					'<span class="module-parent-missing">'.sprintf(__('(requires "%s")'),html::escapeHTML($parent)).'</span> ';
				}
			}

			if (in_array('version', $cols)) {
				$line .= 
				'<span class="module-version">'.sprintf(__('version %s'),html::escapeHTML($module['version'])).'</span> ';
			}

			$has_details = in_array('details', $cols) && !empty($module['details']);
			$has_support = in_array('support', $cols) && !empty($module['support']);
			if ($has_details || $has_support) {
				$line .=
				'<span class="mod-more">'.__('Help:').' ';

				if ($has_details) {
					$line .= 
					'<a class="module-details" href="'.$module['details'].'">'.__('Details').'</a>';
				}

				if ($has_support) {
					$line .= 
					' - <a class="module-support" href="'.$module['support'].'">'.__('Support').'</a>';
				}

				$line .=
				'</span>';
			}

			$line .= 
			'</p>'.
			'</div>';

			$line .= 
			'<div class="module-actions toggle-bloc">';
			
			# _GET actions

			if ($current && $has_css) {
				$line .= 
				'<p><a href="'.$theme_url.'/style.css" class="button">'.__('View stylesheet').'</a></p>';
			}
			if ($current && $has_conf) {
				$line .= 
				'<p><a href="'.$this->getPageURL('module='.$id.'&conf=1', false).'" class="button">'.__('Configure theme').'</a></p>';
			}

			# Plugins actions
			if ($current) {
				# --BEHAVIOR-- adminCurrentThemeDetails
				$line .= 
				$this->core->callBehavior('adminCurrentThemeDetails', $this->core, $id, $module);
			}

			# _POST actions
			if (!empty($actions)) {
				$line .=
				'<form action="'.$this->getPageURL().'" method="post">'.
				'<div>'.
				$this->core->formNonce().
				form::hidden(array('module'), html::escapeHTML($id)).
				form::hidden(array('tab'), $this->page_tab).

				implode(' ', $this->getActions($id, $module, $actions)).
 
				'</div>'.
				'</form>';
			}

			$line .= 
			'</div>';

			$line .=
			'</div>';

			$count++;

			$res = $current ? $line.$res : $res.$line;
		}
		echo 
		$res.
		'</div>';

		if(!$count && $this->getSearchQuery() === null) {
			echo 
			'<p class="message">'.__('No module matches your search.').'</p>';
		}
	}

	protected function getActions($id, $module, $actions)
	{
		$submits = array();

		$this->core->blog->settings->addNamespace('system');
		if ($id != $this->core->blog->settings->system->theme) {

			# Select theme to use on curent blog
			if (in_array('select', $actions) && $this->path_writable) {
				$submits[] = '<input type="submit" name="select" value="'.__('Choose').'" />';
			}
		}

		return array_merge(
			$submits,
			parent::getActions($id, $module, $actions)
		);
	}

	public function doActions($prefix)
	{
		if (!empty($_POST) && empty($_REQUEST['conf']) && $this->isPathWritable()) {

			# Select theme to use on curent blog
			if (!empty($_POST['module']) && !empty($_POST['select'])) {
				$id = $_POST['module'];

				if (!$this->modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$this->core->blog->settings->addNamespace('system');
				$this->core->blog->settings->system->put('theme',$id);
				$this->core->blog->triggerBlog();

				http::redirect($this->getPageURL('msg=select').'#themes');
			}
		}

		return parent::doActions($prefix);
	}
}
