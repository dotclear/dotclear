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
* @nosubgrouping
* @brief Authentication and user credentials management
* 
* dcAuth is a class used to handle everything related to user authentication
* and credentials. Object is provided by dcCore $auth property.
*/
class dcAuth
{
	/** @var dcCore dcCore instance */
	protected $core;
	/** @var connection Database connection object */
	protected $con;
	
	/** @var string User table name */
	protected $user_table;
	/** @var string Perm table name */
	protected $perm_table;
	
	/** @var string Current user ID */
	protected $user_id;
	/** @var array Array with user information */
	protected $user_info = array();
	/** @var array Array with user options */
	protected $user_options = array();
	/** @var boolean User must change his password after login */
	protected $user_change_pwd;
	/** @var boolean User is super admin */
	protected $user_admin;
	/** @var array Permissions for each blog */
	protected $permissions = array();
	/** @var boolean User can change its password */
	protected $allow_pass_change = true;
	/** @var array List of blogs on which the user has permissions */
	protected $blogs = array();
	/** @var integer Count of user blogs */
	public $blog_count = null;
	
	/** @var array Permission types */
	protected $perm_types;
	
	/** @var dcPrefs dcPrefs object */
	public $user_prefs;
	
	/**
	* Class constructor. Takes dcCore object as single argument.
	* 
	* @param dcCore	$core		dcCore object
	*/
	public function __construct($core)
	{
		$this->core =& $core;
		$this->con =& $core->con;
		$this->blog_table = $core->prefix.'blog';
		$this->user_table = $core->prefix.'user';
		$this->perm_table = $core->prefix.'permissions';
		
		$this->perm_types = array(
			'admin' => __('administrator'),
			'usage' => __('manage their own entries and comments'),
			'publish' => __('publish entries and comments'),
			'delete' => __('delete entries and comments'),
			'contentadmin' => __('manage all entries and comments'),
			'categories' => __('manage categories'),
			'media' => __('manage their own media items'),
			'media_admin' => __('manage all media items')
		);
	}
	
	/// @name Credentials and user permissions
	//@{
	/**
	* Checks if user exists and can log in. <var>$pwd</var> argument is optionnal
	* while you may need to check user without password. This method will create
	* credentials and populate all needed object properties.
	* 
	* @param string	$user_id		User ID
	* @param string	$pwd			User password
	* @param string	$user_key		User key check
	* @param boolean	$check_blog	checks if user is associated to a blog or not.
	* @return boolean
	*/
	public function checkUser($user_id, $pwd=null, $user_key=null, $check_blog=true)
	{
		# Check user and password
		$strReq = 'SELECT user_id, user_super, user_pwd, user_change_pwd, '.
				'user_name, user_firstname, user_displayname, user_email, '.
				'user_url, user_default_blog, user_options, '.
				'user_lang, user_tz, user_post_status, user_creadt, user_upddt '.
				'FROM '.$this->con->escapeSystem($this->user_table).' '.
				"WHERE user_id = '".$this->con->escape($user_id)."' ";
		
		try {
			$rs = $this->con->select($strReq);
		} catch (Exception $e) {
			$err = $e->getMessage();
			return false;
		}		
		
		if ($rs->isEmpty()) {
			return false;
		}
		
		$rs->extend('rsExtUser');
		
		if ($pwd != '')
		{
			if (crypt::hmac(DC_MASTER_KEY,$pwd) != $rs->user_pwd) {
				sleep(rand(2,5));
				return false;
			}
		}
		elseif ($user_key != '')
		{
			if (http::browserUID(DC_MASTER_KEY.$rs->user_id.$rs->user_pwd) != $user_key) {
				return false;
			}
		}
		
		$this->user_id = $rs->user_id;
		$this->user_change_pwd = (boolean) $rs->user_change_pwd;
		$this->user_admin = (boolean) $rs->user_super;
		
		$this->user_info['user_pwd'] = $rs->user_pwd;
		$this->user_info['user_name'] = $rs->user_name;
		$this->user_info['user_firstname'] = $rs->user_firstname;
		$this->user_info['user_displayname'] = $rs->user_displayname;
		$this->user_info['user_email'] = $rs->user_email;
		$this->user_info['user_url'] = $rs->user_url;
		$this->user_info['user_default_blog'] = $rs->user_default_blog;
		$this->user_info['user_lang'] = $rs->user_lang;
		$this->user_info['user_tz'] = $rs->user_tz;
		$this->user_info['user_post_status'] = $rs->user_post_status;
		$this->user_info['user_creadt'] = $rs->user_creadt;
		$this->user_info['user_upddt'] = $rs->user_upddt;
		
		$this->user_info['user_cn'] = dcUtils::getUserCN($rs->user_id, $rs->user_name,
		$rs->user_firstname, $rs->user_displayname);
		
		$this->user_options = array_merge($this->core->userDefaults(),$rs->options());
		
		$this->user_prefs = new dcPrefs($this->core,$this->user_id);
		
		# Get permissions on blogs
		if ($check_blog && ($this->findUserBlog() === false)) {
			return false;
		}
		return true;
	}
	
	/**
	* This method only check current user password.
	* 
	* @param string	$pwd			User password
	* @return boolean
	*/
	public function checkPassword($pwd)
	{
		if (!empty($this->user_info['user_pwd'])) {
			return $pwd == $this->user_info['user_pwd'];
		}
		
		return false;
	}
	
	/**
	* This method checks if user session cookie exists
	* 
	* @return boolean
	*/
	public function sessionExists()
	{
		return isset($_COOKIE[DC_SESSION_NAME]);
	}
	
	/**
	* This method checks user session validity.
	* 
	* @return boolean
	*/
	public function checkSession($uid=null)
	{
		$this->core->session->start();
		
		# If session does not exist, logout.
		if (!isset($_SESSION['sess_user_id'])) {
			$this->core->session->destroy();
			return false;
		}
		
		# Check here for user and IP address
		$this->checkUser($_SESSION['sess_user_id']);
		$uid = $uid ? $uid : http::browserUID(DC_MASTER_KEY);
		
		$user_can_log = $this->userID() !== null && $uid == $_SESSION['sess_browser_uid'];
		
		if (!$user_can_log) {
			$this->core->session->destroy();
			return false;
		}
		
		return true;
	}
	
	/**
	* Checks if user must change his password in order to login.
	*
	* @return boolean
	*/
	public function mustChangePassword()
	{
		return $this->user_change_pwd;
	}
	
	/**
	* Checks if user is super admin
	* 
	* @return boolean
	*/
	public function isSuperAdmin()
	{
		return $this->user_admin;
	}
	
	/**
	* Checks if user has permissions given in <var>$permissions</var> for blog
	* <var>$blog_id</var>. <var>$permissions</var> is a coma separated list of
	* permissions.
	* 
	* @param string	$permissions	Permissions list
	* @param string	$blog_id		Blog ID
	* @return boolean
	*/
	public function check($permissions,$blog_id)
	{
		if ($this->user_admin) {
			return true;
		}
		
		$p = explode(',',$permissions);
		$b = $this->getPermissions($blog_id);
		
		if ($b != false)
		{
			if (isset($b['admin'])) {
				return true;
			}
			
			foreach ($p as $v)
			{
				if (isset($b[$v])) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	* Returns true if user is allowed to change its password.
	* 
	* @return	boolean
	*/
	public function allowPassChange()
	{
		return $this->allow_pass_change;
	}
	//@}
	
	/// @name User code handlers
	//@{
	public function getUserCode()
	{
		$code =
		pack('a32',$this->userID()).
		pack('H*',crypt::hmac(DC_MASTER_KEY,$this->getInfo('user_pwd')));
		return bin2hex($code);
	}
	
	public function checkUserCode($code)
	{
		$code = @pack('H*',$code);
		
		$user_id = trim(@pack('a32',substr($code,0,32)));
		$pwd = @unpack('H40hex',substr($code,32,40));
		
		if ($user_id === false || $pwd === false) {
			return false;
		}
		
		$pwd = $pwd['hex'];
		
		$strReq = 'SELECT user_id, user_pwd '.
				'FROM '.$this->user_table.' '.
				"WHERE user_id = '".$this->con->escape($user_id)."' ";
		
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) {
			return false;
		}
		
		if (crypt::hmac(DC_MASTER_KEY,$rs->user_pwd) != $pwd) {
			return false;
		}
		
		return $rs->user_id;
	}
	//@}
	
	
	/// @name Sudo
	//@{
	/**
	* Calls $f function with super admin rights.
	* Returns the function result.
	* 
	* @param callback	$f			Callback function
	* @return mixed
	*/
	public function sudo($f)
	{
		if (!is_callable($f)) {
			throw new Exception($f.' function doest not exist');
		}
		
		$args = func_get_args();
		array_shift($args);
		
		if ($this->user_admin) {
			$res = call_user_func_array($f,$args);
		} else {
			$this->user_admin = true;
			try {
				$res = call_user_func_array($f,$args);
				$this->user_admin = false;
			} catch (Exception $e) {
				$this->user_admin = false;
				throw $e;
			}
		}
		
		return $res;
	}
	//@}
	
	/// @name User information and options
	//@{
	/**
	* Returns user permissions for a blog as an array which looks like:
	* 
	*  - [blog_id]
	*    - [permission] => true
	*    - ...
	* 
	* @param string	$blog_id		Blog ID
	* @return array
	*/
	public function getPermissions($blog_id)
	{
		if (isset($this->blogs[$blog_id])) {
			return $this->blogs[$blog_id];
		}
		
		if ($this->blog_count === null) {
			$this->blog_count = $this->core->getBlogs(array(),true)->f(0);
		}
		
		if ($this->user_admin) {
			$strReq = 'SELECT blog_id '.
				'from '.$this->blog_table.' '.
				"WHERE blog_id = '".$this->con->escape($blog_id)."' ";
			$rs = $this->con->select($strReq);
			
			$this->blogs[$blog_id] = $rs->isEmpty() ? false : array('admin' => true);
			
			return $this->blogs[$blog_id];
		}
		
		$strReq = 'SELECT permissions '.
				'FROM '.$this->perm_table.' '.
				"WHERE user_id = '".$this->con->escape($this->user_id)."' ".
				"AND blog_id = '".$this->con->escape($blog_id)."' ".
				"AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') ";
		$rs = $this->con->select($strReq);
		
		$this->blogs[$blog_id] = $rs->isEmpty() ? false : $this->parsePermissions($rs->permissions);
		
		return $this->blogs[$blog_id];
	}
	
	public function findUserBlog($blog_id=null)
	{
		if ($blog_id && $this->getPermissions($blog_id) !== false)
		{
			return $blog_id;
		}
		else
		{
			if ($this->user_admin)
			{
				$strReq = 'SELECT blog_id '.
						'FROM '.$this->blog_table.' '.
						'ORDER BY blog_id ASC '.
						$this->con->limit(1);
			}
			else
			{
				$strReq = 'SELECT blog_id '.
						'FROM '.$this->perm_table.' '.
						"WHERE user_id = '".$this->con->escape($this->user_id)."' ".
						"AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') ".
						'ORDER BY blog_id ASC '.
						$this->con->limit(1);
			}
			
			$rs = $this->con->select($strReq);
			if (!$rs->isEmpty()) {
				return $rs->blog_id;
			}
		}
		
		return false;
	}
	
	/**
	* Returns current user ID
	* 
	* @return string
	*/
	public function userID()
	{
		return $this->user_id;
	}
	
	/**
	* Returns information about a user .
	* 
	* @param string	$n			Information name
	* @return string
	*/
	public function getInfo($n)
	{
		if (isset($this->user_info[$n])) {
			return $this->user_info[$n];
		}
		
		return null;
	}
	
	/**
	* Returns a specific user option
	* 
	* @param string	$n			Option name
	* @return string
	*/
	public function getOption($n)
	{
		if (isset($this->user_options[$n])) {
			return $this->user_options[$n];
		}
		return null;
	}
	
	/**
	* Returns all user options in an associative array.
	* 
	* @return array
	*/
	public function getOptions()
	{
		return $this->user_options;
	}
	//@}
	
	/// @name Permissions
	//@{
	/**
	* Returns an array with permissions parsed from the string <var>$level</var>
	* 
	* @param string	$level		Permissions string
	* @return array
	*/
	public function parsePermissions($level)
	{
		$level = preg_replace('/^\|/','',$level);
		$level = preg_replace('/\|$/','',$level);
		
		$res = array();
		foreach (explode('|',$level) as $v) {
			$res[$v] = true;
		}
		return $res;
	}
	
	/**
	* Returns <var>perm_types</var> property content.
	* 
	* @return array
	*/
	public function getPermissionsTypes()
	{
		return $this->perm_types;
	}
	
	/**
	* Adds a new permission type.
	* 
	* @param string	$name		Permission name
	* @param string	$title		Permission title
	*/
	public function setPermissionType($name,$title)
	{
		$this->perm_types[$name] = $title;
	}
	//@}
	
	/// @name Password recovery
	//@{
	/**
	* Add a recover key to a specific user identified by its email and
	* password.
	* 
	* @param string	$user_id		User ID
	* @param string	$user_email	User Email
	* @return string
	*/
	public function setRecoverKey($user_id,$user_email)
	{
		$strReq = 'SELECT user_id '.
				'FROM '.$this->user_table.' '.
				"WHERE user_id = '".$this->con->escape($user_id)."' ".
				"AND user_email = '".$this->con->escape($user_email)."' ";
		
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) {
			throw new Exception(__('That user does not exist in the database.'));
		}
		
		$key = md5(uniqid());
		
		$cur = $this->con->openCursor($this->user_table);
		$cur->user_recover_key = $key;
		
		$cur->update("WHERE user_id = '".$this->con->escape($user_id)."'");
		
		return $key;
	}
	
	/**
	* Creates a new user password using recovery key. Returns an array:
	* 
	* - user_email
	* - user_id
	* - new_pass
	* 
	* @param string	$recover_key	Recovery key
	* @return array
	*/
	public function recoverUserPassword($recover_key)
	{
		$strReq = 'SELECT user_id, user_email '.
				'FROM '.$this->user_table.' '.
				"WHERE user_recover_key = '".$this->con->escape($recover_key)."' ";
		
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) {
			throw new Exception(__('That key does not exist in the database.'));
		}
		
		$new_pass = crypt::createPassword();
		
		$cur = $this->con->openCursor($this->user_table);
		$cur->user_pwd = crypt::hmac(DC_MASTER_KEY,$new_pass);
		$cur->user_recover_key = null;
		
		$cur->update("WHERE user_recover_key = '".$this->con->escape($recover_key)."'");
		
		return array('user_email' => $rs->user_email, 'user_id' => $rs->user_id, 'new_pass' => $new_pass);
	}
	//@}
	
	/** @name User management callbacks
	This 3 functions only matter if you extend this class and use
	DC_AUTH_CLASS constant.
	These are called after core user management functions.
	Could be useful if you need to add/update/remove stuff in your
	LDAP directory	or other third party authentication database.
	*/
	//@{
	
	/**
	* Called after core->addUser
	* @see dcCore::addUser
	* @param cursor	$cur			User cursor
	*/
	public function afterAddUser($cur) {}
	
	/**
	* Called after core->updUser
	* @see dcCore::updUser
	* @param string	$id			User ID
	* @param cursor	$cur			User cursor
	*/
	public function afterUpdUser($id,$cur) {}
	
	/**
	* Called after core->delUser
	* @see dcCore::delUser
	* @param string	$id			User ID
	*/
	public function afterDelUser($id) {}
	//@}
}
?>