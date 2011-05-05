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
@brief User prefs handler

dcPrefs provides user preferences management. This class instance exists as
dcAuth $prefs property. You should create a new prefs instance when
updating another user prefs.
*/
class dcPrefs
{
	protected $con;		///< <b>connection</b> Database connection object
	protected $table;		///< <b>string</b> Prefs table name
	protected $user_id;		///< <b>string</b> User ID
	
	protected $workspaces = array();		///< <b>array</b> Associative workspaces array
	
	protected $ws;			///< <b>string</b> Current workspace
	
	/**
	Object constructor. Retrieves user prefs and puts them in $workspaces
	array. Local (user) prefs have a highest priority than global prefs.
	
	@param	core		<b>dcCore</b>		dcCore object
	@param	user_id	<b>string</b>		User ID
	*/
	public function __construct($core,$user_id)
	{
		$this->con =& $core->con;
		$this->table = $core->prefix.'pref';
		$this->user_id =& $user_id;
		try {$this->loadPrefs();} catch (Exception $e) {
			if (version_compare($core->getVersion('core'),'2.3','>')) {
				trigger_error(__('Unable to retrieve workspaces:').' '.$this->con->error(), E_USER_ERROR);
			}
		}
	}
	
	/**
	Retrieves all workspaces (and their prefs) from database, with one query. 
	*/
	private function loadPrefs()
	{
		$strReq = 'SELECT user_id, pref_id, pref_value, '.
				'pref_type, pref_label, pref_ws '.
				'FROM '.$this->table.' '.
				"WHERE user_id = '".$this->con->escape($this->user_id)."' ".
				'OR user_id IS NULL '.
				'ORDER BY pref_ws ASC, pref_id ASC';
		try {
			$rs = $this->con->select($strReq);
		} catch (Exception $e) {
			throw $e;
		}
		
		/* Prevent empty tables (install phase, for instance) */
		if ($rs->isEmpty()) {
			return;
		}
		
		do {
			$ws = trim($rs->f('pref_ws'));
			if (!$rs->isStart()) {
				// we have to go up 1 step, since workspaces construction performs a fetch()
				// at very first time
				$rs->movePrev();
			}
			$this->workspaces[$ws] = new dcWorkspace($GLOBALS['core'], $this->user_id, $ws,$rs);
		} while(!$rs->isStart());
	}
		
	
	/**
	Create a new workspace. If the workspace already exists, return it without modification.
	
	@param	ws	<b>string</b>		Workspace name
	@return	<b>dcWorkspace</b>	The workspace created
	*/
	public function addWorkspace($ws)
	{
		if (!array_key_exists($ws, $this->workspaces)) {
			$this->workspaces[$ws] = new dcWorkspace($GLOBALS['core'], $this->user_id, $ws);
		}
		return $this->workspaces[$ws];
	}
	
	/**
	Returns full workspace with all prefs pertaining to it.
	
	@param	ws	<b>string</b>		Workspace name
	@return	<b>dcWorkspace</b>
	*/
	public function get($ws)
	{
		if (array_key_exists($ws, $this->workspaces)) {
			return $this->workspaces[$ws];
		}
		
		return null;
	}
	
	/**
	Magic __get method.
	@copydoc ::get
	*/
	public function __get($n)
	{
		return $this->get($n);
	}
	
	/**
	Returns $workspaces property content.
	
	@return	<b>array</b>
	*/
	public function dumpWorkspaces()
	{
		return $this->workspaces;
	}
	
}
?>