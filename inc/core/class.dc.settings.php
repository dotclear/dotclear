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
@ingroup DC_CORE
@brief Blog settings handler

dcSettings provides blog settings management. This class instance exists as
dcBlog $settings property. You should create a new settings instance when
updating another blog settings.
*/
class dcSettings
{
	protected $con;		///< <b>connection</b> Database connection object
	protected $table;		///< <b>string</b> Settings table name
	protected $blog_id;		///< <b>string</b> Blog ID
	
	protected $namespaces = array();		///< <b>array</b> Associative namespaces array
	
	protected $ns;			///< <b>string</b> Current namespace
	
	/**
	Object constructor. Retrieves blog settings and puts them in $namespaces
	array. Local (blog) settings have a highest priority than global settings.
	
	@param	core		<b>dcCore</b>		dcCore object
	@param	blog_id	<b>string</b>		Blog ID
	*/
	public function __construct($core,$blog_id)
	{
		$this->con =& $core->con;
		$this->table = $core->prefix.'setting';
		$this->blog_id =& $blog_id;
		$this->loadSettings();
	}
	
	/**
	Retrieves all namespaces (and their settings) from database, with one query. 
	*/
	private function loadSettings()
	{
		$strReq = 'SELECT blog_id, setting_id, setting_value, '.
				'setting_type, setting_label, setting_ns '.
				'FROM '.$this->table.' '.
				"WHERE blog_id = '".$this->con->escape($this->blog_id)."' ".
				'OR blog_id IS NULL '.
				'ORDER BY setting_ns ASC, setting_id DESC';
		try {
			$rs = $this->con->select($strReq);
		} catch (Exception $e) {
			trigger_error(__('Unable to retrieve namespaces:').' '.$this->con->error(), E_USER_ERROR);
		}
		
		/* Prevent empty tables (install phase, for instance) */
		if ($rs->isEmpty()) {
			return;
		}
		
		do {
			$ns = trim($rs->f('setting_ns'));
			if (!$rs->isStart()) {
				// we have to go up 1 step, since namespaces construction performs a fetch()
				// at very first time
				$rs->movePrev();
			}
			$this->namespaces[$ns] = new dcNamespace($GLOBALS['core'], $this->blog_id, $ns,$rs);
		} while(!$rs->isStart());
	}
		
	
	/**
	Create a new namespace. If the namespace already exists, return it without modification.
	
	@param	ns	<b>string</b>		Namespace name
	@return	<b>dcNamespace</b>	The namespace created
	*/
	public function addNamespace($ns)
	{
		if (!array_key_exists($ns, $this->namespaces)) {
			$this->namespaces[$ns] = new dcNamespace($GLOBALS['core'], $this->blog_id, $ns);
		}
		return $this->namespaces[$ns];
	}
	
	/**
	Returns full namespace with all settings pertaining to it.
	
	@param	ns	<b>string</b>		Namespace name
	@return	<b>dcNamespace</b>
	*/
	public function get($ns)
	{
		return $this->namespaces[$ns];
	}
	
	/**
	Magic __get method.
	@copydoc ::get
	*/
	public function __get($n)
	{
		if (!array_key_exists($n, $this->namespaces)) {
			// For backward compatibility only: the developer tried to access
			// a setting directly, without passing via a namespace.
			$this->raiseDeprecated('old_style_get');
			return $this->getSetting($n);
		}
		return $this->get($n);
	}
	
	/**
	Magic __set method.
	@copydoc ::set
	*/
	public function __set($n,$v)
	{
		$this->set($n,$v);
	}
	
	/**
	Returns $namespaces property content.
	
	@return	<b>array</b>
	*/
	public function dumpNamespaces()
	{
		return $this->namespaces;
	}
	
	/**
	Raises a E_USER_NOTICE errror for deprecated functions. 
	This allows the developer to know he's been using deprecated functions.
	
	@param	name	<b>string</b>	Name of the deprecated function that was called.
	*/
	private function raiseDeprecated($name)
	{
		if (DC_DEBUG) {
			$trace = debug_backtrace();
			array_shift($trace);
			$grand = array_shift($trace);
			$msg = 'Deprecated function called. (';
			$msg .= 'dcSettings::'.$name . ' was called from '.$grand['file'].' ['.$grand['line'].'])';
			trigger_error($msg, E_USER_NOTICE);
		}
	}
	
	/**
	@deprecated Please set your settings via $core->blog->settings->{namespace}->{setting}
	
	Sets a setting in $settings property. This sets the setting for script
	execution time only and if setting exists.
	
	@param	n		<b>string</b>		Setting name
	@param	v		<b>mixed</b>		Setting value
	*/
	public function set($n,$v)
	{
		// For backward compatibility only: the developer tried to access
		// a setting directly, without passing via a namespace.
		$this->raiseDeprecated('old_style_set');
		
		if (!$this->ns) {
			throw new Exception(__('No namespace specified'));
		}
		
		if (isset($this->namespaces[$this->ns]->$n)) {
			$this->namespaces[$this->ns]->$n['value'] = $v;
		} else {
			$this->namespaces[$this->ns]->$n = array(
				'ns' => $this->ns,
				'value' => $v,
				'type' => gettype($n),
				'label' => '',
				'global' => false
			);
		}
	}
	
	/**
	@deprecated Please access your settings via $core->blog->settings->{namespace}->...
	
	Sets a working namespace. You should do this before accessing any setting.
	
	@param	ns		<b>string</b>		Namespace name
	*/
	public function setNamespace($ns)
	{
		$this->raiseDeprecated('setNamespace');
		if (preg_match('/^[a-zA-Z][a-zA-Z0-9]+$/',$ns)) {
			$this->ns = $ns;
		} else {
			throw new Exception(sprintf(__('Invalid setting namespace: %s'),$ns));
		}
	}
	
	/**
	@deprecated Please set your settings via $core->blog->settings->{namespace}->put()
	
	Creates or updates a setting.
	
	$type could be 'string', 'integer', 'float', 'boolean' or null. If $type is
	null and setting exists, it will keep current setting type.
	
	$value_change allow you to not change setting. Useful if you need to change
	a setting label or type and don't want to change its value.
	
	Don't forget to set namespace before calling this method.
	
	@param	id			<b>string</b>		Setting ID
	@param	value		<b>mixed</b>		Setting value
	@param	type			<b>string</b>		Setting type
	@param	label		<b>string</b>		Setting label
	@param	value_change	<b>boolean</b>		Change setting value or not
	@param	global		<b>boolean</b>		Setting is global
	*/
	public function put($id,$value,$type=null,$label=null,$value_change=true,$global=false)
	{
		$this->raiseDeprecated('put');
		if (!$this->ns) {
			throw new Exception(__('No namespace specified'));
		}
		if (!isset($this->namespaces[$this->ns])) {
			// Create namespace if needed
			$this->namespaces[$this->ns] = new dcNamespace($GLOBALS['core'], $this->blog_id, $this->ns);
		}
		$this->namespaces[$this->ns]->put($id, $value, $type, $label, $value_change, $global);
	}
	
	/**
	@deprecated Please get your settings via $core->blog->settings->{namespace}->{setting}
	
	Returns setting value if exists.
	
	@param	n		<b>string</b>		Setting name
	@return	<b>mixed</b>
	*/
	public function getSetting($n)
	{
		if ($this->namespaces['system']->get($n) != null) {
			// Give preference to system settings
			return $this->namespaces['system']->get($n);
		} else {
			// Parse all the namespaces
			foreach (array_keys($this->namespaces) as $id => $ns) {
				if ($this->namespaces[$ns]->get($n) != null) {
					// Return the first setting with matching name
					return $this->namespaces[$ns]->get($n);
				}
			}
		}
		
		return null;
	}
	
	/**
	@deprecated Please get your settings via $core->blog->settings->{namespace}->dumpSettings
	
	Returns all settings content.
	
	@return	<b>array</b>
	*/
	public function dumpSettings()
	{
		// For backward compatibility only: the developer tried to access
		// the settings directly, without passing via a namespace.
		$this->raiseDeprecated('dumpSettings');
		
		$settings = array();
		// Parse all the namespaces
		foreach (array_keys($this->namespaces) as $id => $ns) {
			$settings = array_merge($settings, $this->namespaces[$ns]->dumpSettings());
		}
		
		return $settings;
	}
	
	/**
	@deprecated Please get your settings via $core->blog->settings->{namespace}->dumpGlobalSettings
	
	Returns all global settings content.
	
	@return	<b>array</b>
	*/
	public function dumpGlobalSettings()
	{
		// For backward compatibility only: the developer tried to access
		// the settings directly, without passing via a namespace.
		$this->raiseDeprecated('dumpGlobalSettings');
		
		$settings = array();
		// Parse all the namespaces
		foreach (array_keys($this->namespaces) as $id => $ns) {
			$settings = array_merge($settings, $this->namespaces[$ns]->dumpGlobalSettings());
		}
		
		return $settings;
	}

	/**
	Returns a list of settings matching given criteria, for any blog.
	<b>$params</b> is an array taking the following
	optionnal parameters:
	
	- ns : retrieve setting from given namespace
	- id : retrieve only settings corresponding to the given id
	
	@param	params		<b>array</b>		Parameters
	@return	<b>record</b>	A record 
	*/
	public function getGlobalSettings($params=array())
	{
		$strReq = "SELECT * from ".$this->table." ";
		$where = array();
		if (!empty($params['ns'])) {
			$where[] = "setting_ns = '".$this->con->escape($params['ns'])."'";
		}
		if (!empty($params['id'])) {
			$where[] = "setting_id = '".$this->con->escape($params['id'])."'";
		}
		if (isset($params['blog_id'])) {
			if (!empty($params['blog_id'])) {
				$where[] = "blog_id = '".$this->con->escape($params['blog_id'])."'";
			} else {
				$where[] = "blog_id IS NULL";
			}
		}
		if (count($where) != 0) {
			$strReq .= " WHERE ".join(" AND ", $where);
		}
		$strReq .= " ORDER by blog_id";
		return $this->con->select($strReq);
	}

	/**
	Updates a setting from a given record
	
	@param	rs		<b>record</b>		the setting to update
	*/
	public function updateSetting($rs) 
	{
		$cur = $this->con->openCursor($this->table);
		$cur->setting_id = $rs->setting_id;
		$cur->setting_value = $rs->setting_value;
		$cur->setting_type = $rs->setting_type;
		$cur->setting_label = $rs->setting_label;
		$cur->blog_id = $rs->blog_id;
		$cur->setting_ns = $rs->setting_ns;
		if ($cur->blog_id == null) {
				$where = 'WHERE blog_id IS NULL ';
		} else {
			$where = "WHERE blog_id = '".$this->con->escape($cur->blog_id)."' ";
		}
		$cur->update($where."AND setting_id = '".$this->con->escape($cur->setting_id)."' AND setting_ns = '".$this->con->escape($cur->setting_ns)."' ");
	}
	
	/**
	Drops a setting from a given record
	
	@param	rs		<b>record</b>		the setting to drop
	@return	int		number of deleted records (0 if setting does not exist)
	*/
	public function dropSetting($rs) {
		$strReq = "DELETE FROM ".$this->table.' ';
		if ($rs->blog_id == null) {
			$strReq .= 'WHERE blog_id IS NULL ';
		} else {
			$strReq .= "WHERE blog_id = '".$this->con->escape($rs->blog_id)."' ";
		}
		$strReq .= "AND setting_id = '".$this->con->escape($rs->setting_id)."' AND setting_ns = '".$this->con->escape($rs->setting_ns)."' ";
		return $this->con->execute($strReq);
	}
}
?>