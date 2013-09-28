<?php

class adminModulesList
{
	public $core;
	public $modules;

	protected $page_url = '';
	protected $page_qs = '?';
	protected $page_tab = '';

	public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789';
	protected $nav_list = array();
	protected $nav_special = 'other';

	protected $sort_field = 'sname';
	protected $sort_asc = true;

	public function __construct($core, $modules, $page_url='', $page_tab='')
	{
		$this->core = $core;
		$this->setModules($modules);
		$this->setPageURL($page_url);
		$this->setPageTab($page_tab);
		$this->setNavSpecial(__('other'));

		$this->init();
	}

	protected function init()
	{
		return null;
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
			($with_tab && !empty($this->page_tab) || !empty($queries) ? $this->page_qs : '').
			(is_array($queries) ? http_build_query($queries) : $queries).
			($with_tab && !empty($this->page_tab) && !empty($queries) ? '&' : '').
			($with_tab && !empty($this->page_tab) ? /*'tab='$this->page_tab.*/'#'.$this->page_tab : '');
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
		if (empty($this->modules)) {
			return $this;
		}

		$query = $this->getSearchQuery();

		echo 
		'<div class="box">'.
		'<form action="'.$this->getPageURL().'" method="get">'.
		'<p><label for="m_search" class="classic">'.__('Search:').'&nbsp;</label><br />'.
		form::field(array('m_search'), 30, 255, html::escapeHTML($query)).
		'<input type="submit" value="'.__('Search').'" /> '.
		'</p>';

		if ($query) {
			echo 
			'<p class="info">'.sprintf(
				__('Found %d result for search "%s".', 'Found %d results for search "%s".', count($this->modules)), 
				count($this->modules), html::escapeHTML($query)
			).' <a href="'.$this->getPageURL().'">'.__('Reset search').'</a>'.
			'</p>';
		}

		echo 
		'</form>'.
		'</div>';

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
		if (empty($this->modules) || $this->getSearchQuery() !== null) {
			return $this;
		}

		# Fetch modules required field
		$indexes = array();
		foreach ($this->modules as $id => $module) {
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
		echo '<div class="pager">'.__('Index:').' <ul>'.implode('',$buttons).'</ul></div>';

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

	public function setModules($modules)
	{
		$this->modules = array();
		foreach($modules as $id => $module) {
			$this->modules[$id] = $this->setModuleInfo($id, $module);
		}

		return $this;
	}

	public function getModules()
	{
		return $this->modules;
	}

	protected function setModuleInfo($id, $module)
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
				'standalone_config' => false
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
		return in_array($module, array(
			'aboutConfig',
			'akismet',
			'antispam',
			'attachments',
			'blogroll',
			'blowupConfig',
			'daInstaller',
			'fairTrackbacks',
			'importExport',
			'maintenance',
			'pages',
			'pings',
			'simpleMenu',
			'tags',
			'themeEditor',
			'userPref',
			'widgets'
		));
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
		'<table class="modules"><caption class="hidden">'.html::escapeHTML(__('Modules list')).'</caption><tr>';

		if (in_array('name', $cols)) {
			echo 
			'<th class="first nowrap">'.__('Name').'</th>';
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

		if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
			echo 
			'<th class="minimal nowrap">'.__('Action').'</th>';
		}

		echo 
		'</tr>';

		$sort_field = $this->getSortQuery();

		# Sort modules by id
		$modules = $this->getSearchQuery() === null ?
			self::sortModules($this->modules, $sort_field, $this->sort_asc) :
			$this->modules;

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
			'<tr class="line" id="'.$this->getPageTab().'_p_'.html::escapeHTML($id).'">';

			# Link to config file
			$config = in_array('config', $cols) && !empty($module['root']) && file_exists(path::real($module['root'].'/_config.php'));

			echo 
			'<td class="nowrap" scope="row">'.($config ? 
				'<a href="'.$this->getPageURL('module='.$id.'&conf=1').'">'.html::escapeHTML(__($module['name'])).'</a>' : 
				html::escapeHTML(__($module['name']))
			).'</td>';

			if (in_array('version', $cols)) {
				echo 
				'<td class="nowrap count">'.html::escapeHTML($module['version']).'</td>';
			}

			if (in_array('current_version', $cols)) {
				echo 
				'<td class="nowrap count">'.html::escapeHTML($module['current_version']).'</td>';
			}

			if (in_array('desc', $cols)) {
				echo 
				'<td class="maximal">'.html::escapeHTML(__($module['desc'])).'</td>';
			}

			if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
				echo 
				'<td class="nowrap">';

				$this->displayInlineActions($id, $module, $actions);

				echo
				'</td>';
			}

			echo 
			'</tr>';

			$count++;
		}
		echo 
		'</table></div>';

		if(!$count) {
			echo 
			'<p>'.__('There is no module.').'</p>';
		}
	}

	protected function displayInlineActions($id, $module, $actions)
	{
		$submits = array();

		# Activate
		if (in_array('deactivate', $actions) && $module['root_writable']) {
			$submits[] = '<input type="submit" name="deactivate" value="'.__('Deactivate').'" />';
		}

		# Deactivate
		if (in_array('activate', $actions) && $module['root_writable']) {
			$submits[] = '<input type="submit" name="activate" value="'.__('Activate').'" />';
		}
/*
		# Delete
		if (in_array('delete', $actions) && $this->isPathWritable() && preg_match('!^'.$this->path_pattern.'!', $module['root'])) {
			$submits[] = '<input type="submit" class="delete" name="delete" value="'.__('Delete').'" />';
		}

		# Install (form repository)
		if (in_array('install', $actions) && $this->isPathWritable()) {
			$submits[] = '<input type="submit" name="install" value="'.__('Install').'" />';
		}

		# Update (from repository)
		if (in_array('update', $actions) && $this->isPathWritable()) {
			$submits[] = '<input type="submit" name="update" value="'.__('Update').'" />';
		}
*/
		# Parse form
		if (!empty($submits)) {
			echo 
			'<form action="'.$this->getPageURL().'" method="post">'.
			'<div>'.
			$this->core->formNonce().
			form::hidden(array('module'), html::escapeHTML($id)).
			form::hidden(array('tab'), $this->getPageTab()).
			implode(' ', $submits).
			'</div>'.
			'</form>';
		}
	}

	public static function sanitizeString($str)
	{
		return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
	}
}

class adminThemesList extends adminModulesList
{

}
