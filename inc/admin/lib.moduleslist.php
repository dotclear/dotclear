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

/**
 * @ingroup DC_CORE
 * @brief Helper for admin list of modules.
 * @since 2.6

 * Provides an object to parse XML feed of modules from a repository.
 */
class adminModulesList
{
	public $core;		/**< @var	object	dcCore instance */
	public $modules;	/**< @var	object	dcModules instance */
	public $store;		/**< @var	object	dcStore instance */

	public static $allow_multi_install = false;		/**< @var	boolean	Work with multiple root directories */
	public static $distributed_modules = array();	/**< @var	array	List of modules distributed with Dotclear */

	protected $list_id = 'unknow';	/**< @var	string	Current list ID */
	protected $data = array();		/**< @var	array	Current modules */

	protected $config_module = '';	/**< @var	string	Module ID to configure */
	protected $config_file = '';	/**< @var	string	Module path to configure */
	protected $config_content = '';	/**< @var	string	Module configuration page content */

	protected $path = false;			/**< @var	string	Modules root directory */
	protected $path_writable = false;	/**< @var	boolean	Indicate if modules root directory is writable */
	protected $path_pattern = false;	/**< @var	string	Directory pattern to work on */

	protected $page_url = 'plugins.php';	/**< @var	string	Page URL */
	protected $page_qs = '?';				/**< @var	string	Page query string */
	protected $page_tab = '';				/**< @var	string	Page tab */
	protected $page_redir = '';				/**< @var	string	Page redirection */

	public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789'; /**< @var	string	Index list */
	protected $nav_list = array();		/**< @var	array	Index list with special index */
	protected $nav_special = 'other';	/**< @var	string	Text for other special index */

	protected $sort_field = 'sname';	/**< @var	string	Field used to sort modules */
	protected $sort_asc = true;			/**< @var	boolean	Sort order asc */

	/**
	 * Constructor.
	 *
	 * Note that this creates dcStore instance.
	 *
	 * @param	object	$modules		dcModules instance
	 * @param	string	$modules_root	Modules root directories
	 * @param	string	$xml_url		URL of modules feed from repository
	 */
	public function __construct(dcModules $modules, $modules_root, $xml_url)
	{
		$this->core = $modules->core;
		$this->modules = $modules;
		$this->store = new dcStore($modules, $xml_url);

		$this->setPath($modules_root);
		$this->setIndex(__('other'));
	}

	/**
	 * Begin a new list.
	 *
	 * @param	string	$id		New list ID
	 * @return	adminModulesList self instance
	 */
	public function setList($id)
	{
		$this->data = array();
		$this->page_tab = '';
		$this->list_id = $id;

		return $this;
	}

	/**
	 * Get list ID.
	 *
	 * @return	List ID
	 */
	public function getList()
	{
		return $this->list_id;
	}

	/// @name Modules root directory methods
	//@{
	/**
	 * Set path info.
	 *
	 * @param	string	$root		Modules root directories
	 * @return	adminModulesList self instance
	 */
	protected function setPath($root)
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

	/**
	 * Get modules root directory.
	 *
	 * @return	Path to work on
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Check if modules root directory is writable.
	 *
	 * @return	True if directory is writable
	 */
	public function isWritablePath()
	{
		return $this->path_writable;
	}

	/**
	 * Check if root directory of a module is deletable.
	 *
	 * @param	string	$root		Module root directory
	 * @return	True if directory is delatable
	 */
	public function isDeletablePath($root)
	{
		return $this->path_writable 
			&& (preg_match('!^'.$this->path_pattern.'!', $root) || defined('DC_DEV') && DC_DEV) 
			&& $this->core->auth->isSuperAdmin();
	}
	//@}

	/// @name Page methods
	//@{
	/**
	 * Set page base URL.
	 *
	 * @param	string	$url		Page base URL
	 * @return	adminModulesList self instance
	 */
	public function setURL($url)
	{
		$this->page_qs = strpos('?', $url) ? '&amp;' : '?';
		$this->page_url = $url;

		return $this;
	}

	/**
	 * Get page URL.
	 *
	 * @param	string|array	$queries	Additionnal query string
	 * @param	booleany	$with_tab		Add current tab to URL end
	 * @return	Clean page URL
	 */
	public function getURL($queries='', $with_tab=true)
	{
		return $this->page_url.
			(!empty($queries) ? $this->page_qs : '').
			(is_array($queries) ? http_build_query($queries) : $queries).
			($with_tab && !empty($this->page_tab) ? '#'.$this->page_tab : '');
	}

	/**
	 * Set page tab.
	 *
	 * @param	string	$tab		Page tab
	 * @return	adminModulesList self instance
	 */
	public function setTab($tab)
	{
		$this->page_tab = $tab;

		return $this;
	}

	/**
	 * Get page tab.
	 *
	 * @return	Page tab
	 */
	public function getTab()
	{
		return $this->page_tab;
	}

	/**
	 * Set page redirection.
	 *
	 * @param	string	$default		Default redirection
	 * @return	adminModulesList self instance
	 */
	public function setRedir($default='')
	{
		$this->page_redir = empty($_REQUEST['redir']) ? $default : $_REQUEST['redir'];

		return $this;
	}

	/**
	 * Get page redirection.
	 *
	 * @return	Page redirection
	 */
	public function getRedir()
	{
		return empty($this->page_redir) ? $this->getURL() : $this->page_redir;
	}
	//@}

	/// @name Search methods
	//@{
	/**
	 * Get search query.
	 *
	 * @return	Search query
	 */
	public function getSearch()
	{
		$query = !empty($_REQUEST['m_search']) ? trim($_REQUEST['m_search']) : null;
		return strlen($query) > 2 ? $query : null;
	}

	/**
	 * Display searh form.
	 *
	 * @return	adminModulesList self instance
	 */
	public function displaySearch()
	{
		$query = $this->getSearch();

		if (empty($this->data) && $query === null) {
			return $this;
		}

		echo 
		'<div class="modules-search">'.
		'<form action="'.$this->getURL().'" method="get">'.
		'<p><label for="m_search" class="classic">'.__('Search in repository:').'&nbsp;</label><br />'.
		form::field(array('m_search','m_search'), 30, 255, html::escapeHTML($query)).
		'<input type="submit" value="'.__('OK').'" /> ';

		if ($query) { 
			echo 
			' <a href="'.$this->getURL().'" class="button">'.__('Reset search').'</a>';
		}

		echo 
		'</p>'.
		'<p class="form-note">'.
		__('Search is allowed on multiple terms longer than 2 chars, terms must be separated by space.').
		'</p>'.
		'</form>';

		if ($query) {
			echo 
			'<p class="message">'.sprintf(
				__('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->data)), 
				count($this->data), html::escapeHTML($query)
				).
			'</p>';
		}
		echo '</div>';

		return $this;
	}
	//@}

	/// @name Navigation menu methods
	//@{
	/**
	 * Set navigation special index.
	 *
	 * @return	adminModulesList self instance
	 */
	public function setIndex($str)
	{
		$this->nav_special = (string) $str;
		$this->nav_list = array_merge(str_split(self::$nav_indexes), array($this->nav_special));

		return $this;
	}

	/**
	 * Get index from query.
	 *
	 * @return	Query index or default one
	 */
	public function getIndex()
	{
		return isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0];
	}

	/**
	 * Display navigation by index menu.
	 *
	 * @return	adminModulesList self instance
	 */
	public function displayIndex()
	{
		if (empty($this->data) || $this->getSearch() !== null) {
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
			if ($this->getIndex() == $char) {
				$buttons[] = '<li class="active" title="'.__('current selection').'"><strong> '.$char.' </strong></li>';
			}
			# Letter having modules
			elseif (!empty($indexes[$char])) {
				$title = sprintf(__('%d module', '%d modules', $indexes[$char]), $indexes[$char]);
				$buttons[] = '<li class="btn" title="'.$title.'"><a href="'.$this->getURL('m_nav='.$char).'" title="'.$title.'"> '.$char.' </a></li>';
			}
			# Letter without modules
			else {
				$buttons[] = '<li class="btn no-link" title="'.__('no module').'"> '.$char.' </li>';
			}
		}
		# Parse navigation menu
		echo '<div class="pager">'.__('Browse index:').' <ul class="index">'.implode('',$buttons).'</ul></div>';

		return $this;
	}
	//@}

	/// @name Sort methods
	//@{
	/**
	 * Set default sort field.
	 *
	 * @return	adminModulesList self instance
	 */
	public function setSort($field, $asc=true)
	{
		$this->sort_field = $field;
		$this->sort_asc = (boolean) $asc;

		return $this;
	}

	/**
	 * Get sort field from query.
	 *
	 * @return	Query sort field or default one
	 */
	public function getSort()
	{
		return !empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field;
	}

	/**
	 * Display sort field form.
	 *
	 * @note	This method is not implemented yet
	 * @return	adminModulesList self instance
	 */
	public function displaySort()
	{
		//

		return $this;
	}
	//@}

	/// @name Modules methods
	//@{
	/**
	 * Set modules and sanitize them.
	 *
	 * @return	adminModulesList self instance
	 */
	public function setModules($modules)
	{
		$this->data = array();
		if (!empty($modules) && is_array($modules)) {
			foreach($modules as $id => $module) {
				$this->data[$id] = self::sanitizeModule($id, $module);
			}
		}
		return $this;
	}

	/**
	 * Get modules currently set.
	 *
	 * @return	Array of modules
	 */
	public function getModules()
	{
		return $this->data;
	}

	/**
	 * Sanitize a module.
	 *
	 * This clean infos of a module by adding default keys 
	 * and clean some of them, sanitize module can safely 
	 * be used in lists.
	 *
	 * @return	Array of the module informations
	 */
	public static function sanitizeModule($id, $module)
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
				'sshot' 			=> '',
				'score'				=> 0,
				'type' 				=> null
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

	/**
	 * Check if a module is part of the distribution.
	 *
	 * @param	string	$id		Module root directory
	 * @return	True if module is part of the distribution
	 */
	public static function isDistributedModule($id)
	{
		$distributed_modules = self::$distributed_modules;

		return is_array($distributed_modules) && in_array($id, $distributed_modules);
	}

	/**
	 * Sort modules list by specific field.
	 *
	 * @param	string	$module		Array of modules
	 * @param	string	$field		Field to sort from
	 * @param	bollean	$asc		Sort asc if true, else decs
	 * @return	Array of sorted modules
	 */
	public static function sortModules($modules, $field, $asc=true)
	{
		$sorter = array();
		foreach($modules as $id => $module) {
			$sorter[$id] = isset($module[$field]) ? $module[$field] : $field;
		}
		array_multisort($sorter, $asc ? SORT_ASC : SORT_DESC, $modules);

		return $modules;
	}

	/**
	 * Display list of modules.
	 *
	 * @param	array	$cols		List of colones (module field) to display
	 * @param	array	$actions	List of predefined actions to show on form
	 * @param	boolean	$nav_limit	Limit list to previously selected index
	 * @return	adminModulesList self instance
	 */
	public function displayModules($cols=array('name', 'version', 'desc'), $actions=array(), $nav_limit=false)
	{
		echo 
		'<div class="table-outer">'.
		'<table id="'.html::escapeHTML($this->list_id).'" class="modules'.(in_array('expander', $cols) ? ' expandable' : '').'">'.
		'<caption class="hidden">'.html::escapeHTML(__('Modules list')).'</caption><tr>';

		if (in_array('name', $cols)) {
			echo 
			'<th class="first nowrap"'.(in_array('icon', $cols) ? ' colspan="2"' : '').'>'.__('Name').'</th>';
		}

		if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
			echo 
			'<th class="nowrap">'.__('Score').'</th>';
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

		$sort_field = $this->getSort();

		# Sort modules by $sort_field (default sname)
		$modules = $this->getSearch() === null ?
			self::sortModules($this->data, $sort_field, $this->sort_asc) :
			$this->data;

		$count = 0;
		foreach ($modules as $id => $module)
		{
			# Show only requested modules
			if ($nav_limit && $this->getSearch() === null) {
				$char = substr($module[$sort_field], 0, 1);
				if (!in_array($char, $this->nav_list)) {
					$char = $this->nav_special;
				}
				if ($this->getIndex() != $char) {
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
				'<a href="'.$this->getURL('module='.$id.'&amp;conf=1').'" title"'.sprintf(__('Configure module "%s"'), html::escapeHTML($module['name'])).'">'.html::escapeHTML($module['name']).'</a>' : 
				html::escapeHTML($module['name'])
			).'</td>';

			# Display score only for debug purpose
			if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
				echo 
				'<td class="module-version nowrap count"><span class="debug">'.$module['score'].'</span></td>';
			}

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

				'<form action="'.$this->getURL().'" method="post">'.
				'<div>'.
				$this->core->formNonce().
				form::hidden(array('module'), html::escapeHTML($id)).

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

		if(!$count && $this->getSearch() === null) {
			echo 
			'<p class="message">'.__('No module matches your search.').'</p>';
		}

		return $this;
	}

	/**
	 * Get action buttons to add to modules list.
	 *
	 * @param	string	$id			Module ID
	 * @param	array	$module		Module info
	 * @param	array	$actions	Actions keys
	 * @return	Array of actions buttons
	 */
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
				case 'delete': if ($this->isDeletablePath($module['root'])) {
					$dev = !preg_match('!^'.$this->path_pattern.'!', $module['root']) && defined('DC_DEV') && DC_DEV ? ' debug' : '';
					$submits[] = 
					'<input type="submit" class="delete '.$dev.'" name="delete" value="'.__('Delete').'" />';
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

				# Behavior
				case 'behavior':

					# --BEHAVIOR-- adminModulesListGetActions
					$tmp = $this->core->callBehavior('adminModulesListGetActions', $this, $id, $module);

					if (!empty($tmp)) {
						$submits[] = $tmp;
					}
				break;
			}
		}


		return $submits;
	}

	/**
	 * Execute POST action.
	 *
	 * @note	Set a notice on success through dcPage::addSuccessNotice
	 * @throw	Exception	Module not find or command failed
	 * @param	string	$prefix		Prefix used on behaviors
	 * @return	Null
	 */
	public function doActions($prefix)
	{
		if (empty($_POST) || !empty($_REQUEST['conf']) 
		|| !$this->core->auth->isSuperAdmin() || !$this->isWritablePath()) {
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

				dcPage::addSuccessNotice(__('Module has been successfully activated.'));
				http::redirect($this->getURL());
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

				dcPage::addSuccessNotice(__('Module has been successfully deactivated.'));
				http::redirect($this->getURL());
			}

			elseif (!empty($_POST['delete'])) {

				$disabled = $this->modules->getDisabledModules();
				if (!isset($disabled[$id])) {

					if (!$this->modules->moduleExists($id)) {
						throw new Exception(__('No such module.'));
					}

					$module = $this->modules->getModules($id);
					$module['id'] = $id;

					if (!$this->isDeletablePath($module['root'])) {
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

				dcPage::addSuccessNotice(__('Module has been successfully deleted.'));
				http::redirect($this->getURL());
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

				dcPage::addSuccessNotice($ret_code == 2 ?
					__('Module has been successfully updated.') :
					__('Module has been successfully installed.')
				);
				http::redirect($this->getURL());
			}

			elseif (!empty($_POST['update'])) {

				$updated = $this->store->get(true);
				if (!isset($updated[$id])) {
					throw new Exception(__('No such module.'));
				}

				if (!$this->modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$tab = count($updated) > 1 ? '' : '#'.$prefix;

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

				dcPage::addSuccessNotice(__('Module has been successfully updated.'));
				http::redirect($this->getURL().$tab);
			}
			else {

				# --BEHAVIOR-- adminModulesListDoActions
				$this->core->callBehavior('adminModulesListDoActions', $this, $id, $prefix);

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

			dcPage::addSuccessNotice($ret_code == 2 ?
				__('Module has been successfully updated.') :
				__('Module has been successfully installed.')
			);
			http::redirect($this->getURL().'#'.$prefix);
		}

		return null;
	}

	/**
	 * Display tab for manual installation.
	 *
	 * @return	adminModulesList self instance
	 */
	public function displayManualForm()
	{
		if (!$this->core->auth->isSuperAdmin() || !$this->isWritablePath()) {
			return null;
		}

		# 'Upload module' form
		echo
		'<form method="post" action="'.$this->getURL().'" id="uploadpkg" enctype="multipart/form-data" class="fieldset">'.
		'<h4>'.__('Upload a zip file').'</h4>'.
		'<p class="field"><label for="pkg_file" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Zip file path:').'</label> '.
		'<input type="file" name="pkg_file" id="pkg_file" /></p>'.
		'<p class="field"><label for="your_pwd1" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
		form::password(array('your_pwd','your_pwd1'),20,255).'</p>'.
		'<p><input type="submit" name="upload_pkg" value="'.__('Upload').'" />'.
		$this->core->formNonce().'</p>'.
		'</form>';

		# 'Fetch module' form
		echo
		'<form method="post" action="'.$this->getURL().'" id="fetchpkg" class="fieldset">'.
		'<h4>'.__('Download a zip file').'</h4>'.
		'<p class="field"><label for="pkg_url" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Zip file URL:').'</label> '.
		form::field(array('pkg_url','pkg_url'),40,255).'</p>'.
		'<p class="field"><label for="your_pwd2" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
		form::password(array('your_pwd','your_pwd2'),20,255).'</p>'.
		'<p><input type="submit" name="fetch_pkg" value="'.__('Download').'" />'.
		$this->core->formNonce().'</p>'.
		'</form>';

		return $this;
	}
	//@}

	/// @name Module configuration methods
	//@{
	/**
	 * Prepare module configuration.
	 *
	 * We need to get configuration content in three steps
	 * and out of this class to keep backward compatibility.
	 *
	 * if ($xxx->setConfiguration()) {
	 *	include $xxx->includeConfiguration();
	 * }
	 * $xxx->getConfiguration();
	 * ... [put here page headers and other stuff]
	 * $xxx->displayConfiguration();
	 *
	 * @param	string	$id		Module to work on or it gather through REQUEST
	 * @return	True if config set
	 */
	public function setConfiguration($id=null)
	{
		if (empty($_REQUEST['conf']) || empty($_REQUEST['module']) && !$id) {
			return false;
		}
		
		if (!empty($_REQUEST['module']) && empty($id)) {
			$id = $_REQUEST['module'];
		}

		if (!$this->modules->moduleExists($id)) {
			$this->core->error->add(__('Unknow module ID'));
			return false;
		}

		$module = $this->modules->getModules($id);
		$module = self::sanitizeModule($id, $module);
		$file = path::real($module['root'].'/_config.php');

		if (!file_exists($file)) {
			$this->core->error->add(__('This module has no configuration file.'));
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

	/**
	 * Get path of module configuration file.
	 *
	 * @note Required previously set file info
	 * @return Full path of config file or null
	 */
	public function includeConfiguration()
	{
		if (!$this->config_file) {
			return null;
		}
		$this->setRedir($this->getURL().'#plugins');

		ob_start();

		return $this->config_file;
	}

	/**
	 * Gather module configuration file content.
	 *
	 * @note Required previously file inclusion
	 * @return True if content has been captured
	 */
	public function getConfiguration()
	{
		if ($this->config_file) {
			$this->config_content = ob_get_contents();
		}

		ob_end_clean();

		return !empty($this->file_content);
	}

	/**
	 * Display module configuration form.
	 *
	 * @note Required previously gathered content
	 * @return	adminModulesList self instance
	 */
	public function displayConfiguration()
	{
		if ($this->config_file) {

			if (!$this->config_module['standalone_config']) {
				echo
				'<form id="module_config" action="'.$this->getURL('conf=1').'" method="post" enctype="multipart/form-data">'.
				'<h3>'.sprintf(__('Configure plugin "%s"'), html::escapeHTML($this->config_module['name'])).'</h3>'.
				'<p><a class="back" href="'.$this->getRedir().'">'.__('Back').'</a></p>';
			}

			echo $this->config_content;

			if (!$this->config_module['standalone_config']) {
				echo
				'<p class="clear"><input type="submit" name="save" value="'.__('Save').'" />'.
				form::hidden('module', $this->config_module['id']).
				form::hidden('redir', $this->getRedir()).
				$this->core->formNonce().'</p>'.
				'</form>';
			}
		}

		return $this;
	}
	//@}

	/**
	 * Helper to sanitize a string.
	 *
	 * Used for search or id.
	 *
	 * @param	string	$str		String to sanitize
	 * @return	Sanitized string
	 */
	public static function sanitizeString($str)
	{
		return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
	}
}

/**
 * @ingroup DC_CORE
 * @brief Helper to manage list of themes.
 * @since 2.6
 */
class adminThemesList extends adminModulesList
{
	protected $page_url = 'blog_theme.php';

	public function displayModules($cols=array('name', 'config', 'version', 'desc'), $actions=array(), $nav_limit=false)
	{
		echo 
		'<div id="'.html::escapeHTML($this->list_id).'" class="modules'.(in_array('expander', $cols) ? ' expandable' : '').' one-box">';

		$sort_field = $this->getSort();

		# Sort modules by id
		$modules = $this->getSearch() === null ?
			self::sortModules($this->data, $sort_field, $this->sort_asc) :
			$this->data;

		$res = '';
		$count = 0;
		foreach ($modules as $id => $module)
		{
			# Show only requested modules
			if ($nav_limit && $this->getSearch() === null) {
				$char = substr($module[$sort_field], 0, 1);
				if (!in_array($char, $this->nav_list)) {
					$char = $this->nav_special;
				}
				if ($this->getIndex() != $char) {
					continue;
				}
			}

			$current = $this->core->blog->settings->system->theme == $id && $this->modules->moduleExists($id);
			$distrib = self::isDistributedModule($id) ? ' dc-box' : '';

			$line = 
			'<div class="box '.($current ? 'medium current-theme' : 'theme').$distrib.'">';

			if (in_array('name', $cols) && !$current) {
				$line .= 
				'<h4 class="module-name">'.html::escapeHTML($module['name']).'</h4>';
			}

			# Display score only for debug purpose
			if (in_array('score', $cols) && $this->getSearch() !== null && defined('DC_DEBUG') && DC_DEBUG) {
				$line .= 
				'<p class="module-score debug">'.sprintf(__('Score: %s'), $module['score']).'</p>';
			}

			if (in_array('sshot', $cols)) {
				# Screenshot from url
				if (preg_match('#^http(s)?://#', $module['sshot'])) {
					$sshot = $module['sshot'];
				}
				# Screenshot from installed module
				elseif (file_exists($this->core->blog->themes_path.'/'.$id.'/screenshot.jpg')) {
					$sshot = $this->getURL('shot='.rawurlencode($id));
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
			'<div class="module-infos toggle-bloc">';

			if (in_array('name', $cols) && $current) {
				$line .= 
				'<h4 class="module-name">'.html::escapeHTML($module['name']).'</h4>';
			}

			$line .=
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

			if (in_array('current_version', $cols)) {
				$line .= 
				'<span class="module-current-version">'.sprintf(__('(current version %s)'),html::escapeHTML($module['current_version'])).'</span> ';
			}

			if (in_array('parent', $cols) && !empty($module['parent'])) {
				if ($this->modules->moduleExists($module['parent'])) {
					$line .= 
					'<span class="module-parent-ok">'.sprintf(__('(built on "%s")'),html::escapeHTML($module['parent'])).'</span> ';
				}
				else {
					$line .= 
					'<span class="module-parent-missing">'.sprintf(__('(requires "%s")'),html::escapeHTML($module['parent'])).'</span> ';
				}
			}

			$has_details = in_array('details', $cols) && !empty($module['details']);
			$has_support = in_array('support', $cols) && !empty($module['support']);
			if ($has_details || $has_support) {
				$line .=
				'<span class="mod-more">';

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
			
			# Plugins actions
			if ($current) {

				# _GET actions
				if (file_exists(path::real($this->core->blog->themes_path.'/'.$id).'/style.css')) {
					$theme_url = preg_match('#^http(s)?://#', $this->core->blog->settings->system->themes_url) ?
						http::concatURL($this->core->blog->settings->system->themes_url, '/'.$id) :
						http::concatURL($this->core->blog->url, $this->core->blog->settings->system->themes_url.'/'.$id);
					$line .= 
					'<p><a href="'.$theme_url.'/style.css">'.__('View stylesheet').'</a></p>';
				}

				$line .= '<div class="current-actions">';

				if (file_exists(path::real($this->core->blog->themes_path.'/'.$id).'/_config.php')) {
					$line .= 
					'<p><a href="'.$this->getURL('module='.$id.'&conf=1', false).'" class="button submit">'.__('Configure theme').'</a></p>';
				}

				# --BEHAVIOR-- adminCurrentThemeDetails
				$line .= 
				$this->core->callBehavior('adminCurrentThemeDetails', $this->core, $id, $module);

				$line .= '</div>';
			}

			# _POST actions
			if (!empty($actions)) {
				$line .=
				'<form action="'.$this->getURL().'" method="post" class="actions-buttons">'.
				'<p>'.
				$this->core->formNonce().
				form::hidden(array('module'), html::escapeHTML($id)).

				implode(' ', $this->getActions($id, $module, $actions)).
 
				'</p>'.
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

		if(!$count && $this->getSearch() === null) {
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
				$submits[] = 
				'<input type="submit" name="select" value="'.__('Use this one').'" />';
			}
		}

		return array_merge(
			$submits,
			parent::getActions($id, $module, $actions)
		);
	}

	public function doActions($prefix)
	{
		if (!empty($_POST) && empty($_REQUEST['conf']) && $this->isWritablePath()) {

			# Select theme to use on curent blog
			if (!empty($_POST['module']) && !empty($_POST['select'])) {
				$id = $_POST['module'];

				if (!$this->modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$this->core->blog->settings->addNamespace('system');
				$this->core->blog->settings->system->put('theme',$id);
				$this->core->blog->triggerBlog();

				dcPage::addSuccessNotice(__('Module has been successfully selected.'));
				http::redirect($this->getURL().'#themes');
			}
		}

		return parent::doActions($prefix);
	}
}
