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
 * @brief Template extension for admin context.
 * 
 * This extends template environment with tools required in admin context.
 */
class dcAdminContext extends dcContext
{
	public function __construct($core)
	{
		parent::__construct($core);
		
		$this->protected_globals = array_merge($this->protected_globals,array(
			'page_title'	=> array(),
			'page_global'	=> false,
			
			'admin_url' 	=> DC_ADMIN_URL,
			'theme_url' 	=> DC_ADMIN_URL.'index.php?tf=',
			'plugin_url' 	=> DC_ADMIN_URL.'index.php?pf=',
		));
	}
	
	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * This merges overloaded variables with defined variables.
	 * 
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		$this->getBlogs();
		$this->getCurrentBlog();
		$this->getCurrentUser();
		$this->getMenus();
		
		# Additional globals
		$p = path::info($_SERVER['REQUEST_URI']);
		$this->protected_globals['current_page'] = $p['base'];
		$this->protected_globals['blog_count'] = $this->core->auth->blog_count;
		$this->protected_globals['rtl'] = l10n::getTextDirection(
			$this->protected_globals['current_user']['lang']) == 'rtl';
		$this->protected_globals['session'] = array(
			'id' => session_id(),
			'uid' => isset($_SESSION['sess_browser_uid']) ? $_SESSION['sess_browser_uid'] : '',
			'nonce' => $this->core->getNonce()
		);
		
		# Keep protected globals safe
		return array_merge($this->globals,$this->protected_globals);
	}
	
	/**
	 * Fill the page title.
	 *
	 * $title can be: 
	 * - a string for page title part or 
	 * - TRUE to add blog name at the begining of title or
	 * - NULL to empty/reset title
	 *
	 * @param mixed $title A title part
	 * @param boolean $url Link of the title part
	 * @return object self
	 */
	public function fillPageTitle($title,$url='')
	{
		if (is_bool($title)) {
			$this->protected_globals['page_global'] = $title;
		}
		elseif (null === $title) {
			$this->protected_globals['page_global'] = false;
			$this->protected_globals['page_title'] = array();
		}
		else {
			$this->protected_globals['page_title'][] = array(
				'title' => $title,
				'link' => $url
			);
		}
		return $this;
	}
	
	/**
	 * Check if a page title is set
	 */
	public function hasPageTitle()
	{
		return !empty($this->protected_globals['page_title']);
	}
	
	/**
	 * Get list of blogs
	 */
	protected function getBlogs()
	{
		$blog_id = '';
		
		# Blogs list
		$blogs = array();
		if ($this->core->auth->blog_count > 1 && $this->core->auth->blog_count < 20) {
			$blog_id = $this->core->blog->id;
			$rs_blogs = $this->core->getBlogs(array('order'=>'LOWER(blog_name)','limit'=>20));
			while ($rs_blogs->fetch()) {
				$blogs[$rs_blogs->blog_id] = $rs_blogs->blog_name.' - '.$rs_blogs->blog_url;
				$this->protected_globals['blogs'][$rs_blogs->blog_id] = array(
					'id' 	=> $rs_blogs->blog_id,
					'name' 	=> $rs_blogs->blog_name,
					'desc' 	=> $rs_blogs->blog_desc,
					'url' 	=> $rs_blogs->blog_url,
					'creadt'	=> $rs_blogs->blog_creadt,
					'upddt'	=> $rs_blogs->blog_upddt
				);
			}
		}
		
		# Switch blog form
		$form = new dcForm($this->core,'switchblog_menu','index.php');
		$form
			->addField(
				new dcFieldCombo('switchblog',$blog_id,$blogs,array(
				'label' => __('Blogs:'))))
			->addField(
				new dcFieldSubmit('switchblog_submit',__('ok'),array(
				'action' => 'switchblog')))
			->setup();
	}
	
	/**
	 * Get current blog information
	 */
	protected function getCurrentBlog()
	{
		$this->protected_globals['current_blog'] = $this->core->auth->blog_count ?
			array(
				'id' 	=> $this->core->blog->id,
				'name' 	=> $this->core->blog->name,
				'desc' 	=> $this->core->blog->desc,
				'url' 	=> $this->core->blog->url,
				'host' 	=> $this->core->blog->host,
				'creadt'	=> $this->core->blog->creadt,
				'upddt'	=> $this->core->blog->upddt
			) : array(
				'id' 	=> '',
				'name' 	=> '',
				'desc' 	=> '',
				'url' 	=> '',
				'host' 	=> '',
				'creadt'	=> '',
				'upddt'	=> ''
			);
	}
	
	/**
	 * Get current user information
	 */
	protected function getCurrentUser()
	{
		$infos = array(
			'pwd','name','firstname','displayname',
			'email','url','default_blog','lang','tz',
			'post_status','creadt','upddt','cn'
		);
		
		$user = array(
			'id' => '',
			'super' => false,
			'lang' => 'en',
			'options' => $this->core->userDefaults(),
			'prefs' => array(),
			'rights' => array(
				'media' => false
			)
		);
		
		foreach($infos as $i) {
			$user[$i] = '';
		}
		
		if ($this->core->auth->userID()) {
		
			$user = array(
				'id' => $this->core->auth->userID(),
				'super' => $this->core->auth->isSuperAdmin(),
				'options' => $this->core->auth->getOptions(),
				'rights' => array(
					'media' => $this->core->auth->check('media,media_admin',$this->core->blog->id)
				)
			);
			
			foreach($infos as $i) {
				$user[$i] = $this->core->auth->getInfo('user_'.$i);
			}
			
			foreach($this->core->auth->user_prefs->dumpWorkspaces() as $ws => $prefs) {
				$user['prefs'][$ws] = $prefs->dumpPrefs();
			}
		}
		
		$this->protected_globals['current_user'] = $user;
	}
	
	/**
	 * Get sidebar menus
	 */
	protected function getMenus()
	{
		global $_menu;
		
		$this->protected_globals['menus'] = array();
		
		if (!isset($_menu)) {
			return;
		}
		
		foreach($_menu as $m) {
			$this->protected_globals['menus'][] = array(
				'id' 		=> $m->getID(),
				'title' 		=> $m->getTitle(),
				'separator' 	=> $m->getSeparator(),
				'items' 		=> $m->getItems()
			);
		}
	}
}
?>