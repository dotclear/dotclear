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
if (!defined('DC_RC_PATH')) { return; }

$GLOBALS['core']->addBehavior('adminPostsActionSet',array('dcDefaultPostActions','adminPostsActionSet'));
$GLOBALS['core']->addBehavior('adminPostsActionSet',array('dcLegacyPosts','adminPostsActionSet'));

class dcPostsActionSet extends dcActionSet
{
	public function __construct($core,$uri) {
		parent::__construct($core,$uri);
		$this->redirect_fields = array('user_id','cat_id','status',
		'selected','month','lang','sortby','order','page','nb');
		$core->callBehavior('adminPostsActionSet',$core,$this);

	}

	public function beginPage($breadcrumb='',$head='') {
		dcPage::open(
			__('Entries'),
			dcPage::jsLoad('js/jquery/jquery.autocomplete.js').
			dcPage::jsLoad('js/_posts_actions.js').
			dcPage::jsMetaEditor().
			$head,
			$breadcrumb
		);	
		echo '<p><a class="back" href="'.$this->getRedirection(array(),true).'">'.__('Back to entries list').'</a></p>';
	}
	
	public function endPage() {
		dcPage::close();
	}
	
	public function error(Exception $e) {
		$this->core->erorr->add($e->getMessage());
		$this->beginPage(dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				__('Entries') => 'posts.php',
				'<span class="page-title">'.__('Entries actions').'</span>' => ''
			))
		);
		$this->endPage();
	}
	
	public function getCheckboxes() {
		$ret = 
			'<table class="posts-list"><tr>'.
			'<th colspan="2">'.__('Title').'</th>'.
			'</tr>';
		foreach ($this->entries as $id=>$title) {
			$ret .= 
				'<tr><td>'.
				form::checkbox(array('entries[]'),$id,true,'','').'</td>'.
				'<td>'.	$title.'</td></tr>';
		}
		$ret .= '</table>';
		return $ret;
	}
	
	
	public function fetchEntries($from) {
		if (!empty($from['entries']))
		{
			$entries = $from['entries'];
			
			foreach ($entries as $k => $v) {
				$entries[$k] = (integer) $v;
			}
			
			$params['sql'] = 'AND P.post_id IN('.implode(',',$entries).') ';
			
			if (!isset($from['full_content']) || empty($from['full_content'])) {
				$params['no_content'] = true;
			}
			
			if (isset($from['post_type'])) {
				$params['post_type'] = $from['post_type'];
			}
			
			$posts = $this->core->blog->getPosts($params);
			while ($posts->fetch())	{
				$this->ids[] = $posts->post_id;
				$this->entries[$posts->post_id] = $posts->post_title;
			}
			$this->rs = $posts;			
		} else {
			$this->rs = $this->core->con->select("SELECT blog_id FROM ".$this->core->prefix."blog WHERE false");;
		}
	}
}

class dcDefaultPostActions 
{
	public static function adminPostsActionSet($core, dcPostsActionSet $as) {
		if ($core->auth->check('publish,contentadmin',$core->blog->id)) {
			$as->addAction(
				array(__('Status') => array(
					__('Publish') => 'publish',
					__('Unpublish') => 'unpublish',
					__('Schedule') => 'schedule',
					__('Mark as pending') => 'pending'
				)),
				array('dcDefaultPostActions','doChangePostStatus')
			);
		}
		$as->addAction(
			array(__('Mark')=> array(
				__('Mark as selected') => 'selected',
				__('Mark as unselected') => 'unselected'
			)),
			array('dcDefaultPostActions','doUpdateSelectedPost')
		);
		$as->addAction(
			array(__('Change') => array(
				__('Change category') => 'category',
			)),
			array('dcDefaultPostActions','doChangePostCategory')
		);
		$as->addAction(
			array(__('Change') => array(
				__('Change language') => 'lang',
			)),
			array('dcDefaultPostActions','doChangePostLang')
		);
		if ($core->auth->check('admin',$core->blog->id))
		{
			$as->addAction(
				array(__('Change') => array(
					__('Change author') => 'author')),
				array('dcDefaultPostActions','doChangePostLang')
			);
		}
		if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
			$as->addAction(
				array(__('Delete') => array(
					__('Delete') => 'delete')),
				array('dcDefaultPostActions','doDeletePost')
			);
		}
	}

	public static function doChangePostStatus($core, dcPostsActionSet $as, $post) {
		switch ($as->getAction()) {
			case 'unpublish' : $status = 0; break;
			case 'schedule' : $status = -1; break;
			case 'pending' : $status = -2; break;
			default : $status = 1; break;
		}
		
		try
		{
			$posts_ids = $as->getIDs();
			if (empty($posts_ids)) {
				throw new Exception(__('No entry selected'));
			}
			$core->blog->updPostsStatus($posts_ids,$status);
			
			$as->redirect(array('upd' => 1),true);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}		
	}
	
	public static function doUpdateSelectedPost($core, dcPostsActionSet $as, $post) {
		try
		{
			$posts_ids = $as->getIDs();
			if (empty($posts_ids)) {
				throw new Exception(__('No entry selected'));
			}
			
			$core->blog->updPostsSelected($posts_ids,$action == 'selected');
			
			$as->redirect(array('upd' => 1),true);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	
	public static function doDeletePost($core, dcPostsActionSet $as, $post) {

		$posts_ids = $as->getIDs();
		if (empty($posts_ids)) {
			throw new Exception(__('No entry selected'));
		}
		// Backward compatibility
		foreach($posts_ids as $post_id)
		{
			# --BEHAVIOR-- adminBeforePostDelete
			$core->callBehavior('adminBeforePostDelete',(integer) $post_id);
		}
		
		# --BEHAVIOR-- adminBeforePostsDelete
		$core->callBehavior('adminBeforePostsDelete',$posts_ids);
		
		$core->blog->delPosts($posts_ids);
		
		$as->redirect(array('del',1),false);
	}

	public static function doChangePostCategory($core, dcPostsActionSet $as, $post) {
		if (isset($post['new_cat_id'])) {
			$posts_ids = $as->getIDs();
			if (empty($posts_ids)) {
				throw new Exception(__('No entry selected'));
			}
			$new_cat_id = $post['new_cat_id'];
			if (!empty($post['new_cat_title']) && $core->auth->check('categories', $core->blog->id))
			{
				$cur_cat = $core->con->openCursor($core->prefix.'category');
				$cur_cat->cat_title = $post['new_cat_title'];
				$cur_cat->cat_url = '';
				
				$parent_cat = !empty($post['new_cat_parent']) ? $post['new_cat_parent'] : '';
				
				# --BEHAVIOR-- adminBeforeCategoryCreate
				$core->callBehavior('adminBeforeCategoryCreate', $cur_cat);
				
				$new_cat_id = $core->blog->addCategory($cur_cat, (integer) $parent_cat);
				
				# --BEHAVIOR-- adminAfterCategoryCreate
				$core->callBehavior('adminAfterCategoryCreate', $cur_cat, $new_cat_id);
			}
			
			$core->blog->updPostsCategory($posts_ids, $new_cat_id);
			
			$as->redirect(array('upd'=>1),true);
		} else {
			$as->beginPage(
				dcPage::breadcrumb(
					array(
						html::escapeHTML($core->blog->name) => '',
						__('Entries') => 'posts.php',
						'<span class="page-title">'.__('Change category for entries').'</span>' => ''
			)));
			
			# categories list
			# Getting categories
			$categories_combo = dcAdminCombos::getCategoriesCombo(
				$core->blog->getCategories(array('post_type'=>'post'))
			);			
			echo
			'<form action="posts.php" method="post">'.
			$as->getCheckboxes().
			'<p><label for="new_cat_id" class="classic">'.__('Category:').'</label> '.
			form::combo('new_cat_id',$categories_combo,'');
			
			if ($core->auth->check('categories', $core->blog->id)) {
				echo 
				'<div>'.
				'<p id="new_cat">'.__('Create a new category for the post(s)').'</p>'.
				'<p><label for="new_cat_title">'.__('Title:').'</label> '.
				form::field('new_cat_title',30,255,'','').'</p>'.
				'<p><label for="new_cat_parent">'.__('Parent:').'</label> '.
				form::combo('new_cat_parent',$categories_combo,'','').
				'</p>'.
				'</div>';
			}
			
			echo
			$core->formNonce().
			form::hidden(array('action'),'category').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';
			$as->endPage();

		}
	
	}
	public static function doChangePostAuthor($core, dcPostsActionSet $as, $post) {
		if (isset($post['new_auth_id']) && $core->auth->check('admin',$core->blog->id)) {
			$new_user_id = $post['new_auth_id'];
			$posts_ids = $as->getIDs();
			if (empty($posts_ids)) {
				throw new Exception(__('No entry selected'));
			}
			
			try
			{
				if ($core->getUser($new_user_id)->isEmpty()) {
					throw new Exception(__('This user does not exist'));
				}
				
				$cur = $core->con->openCursor($core->prefix.'post');
				$cur->user_id = $new_user_id;
				$cur->update('WHERE post_id '.$core->con->in($posts_ids));
				
				$as->redirect(array('upd' => 1),true);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		} else {
			$usersList = '';
			if ($core->auth->check('admin',$core->blog->id)) {
				$params = array(
					'limit' => 100,
					'order' => 'nb_post DESC'
					);
				$rs = $core->getUsers($params);
				while ($rs->fetch())
				{
					$usersList .= ($usersList != '' ? ',' : '').'"'.$rs->user_id.'"';
				}
			}
			$as->beginPage(
				dcPage::breadcrumb(
					array(
						html::escapeHTML($core->blog->name) => '',
						__('Entries') => 'posts.php',
						'<span class="page-title">'.__('Change author for entries').'</span>' => ''
				)),
				'<script type="text/javascript">'."\n".
				"//<![CDATA[\n".
				'usersList = ['.$usersList.']'."\n".
				"\n//]]>\n".
				"</script>\n"
			);

			echo
			'<form action="posts_actions.php" method="post">'.
			$as->getCheckboxes().
			'<p><label for="new_auth_id" class="classic">'.__('New author (author ID):').'</label> '.
			form::field('new_auth_id',20,255);
			
			echo
				$core->formNonce().
				form::hidden(array('action'),'author').
				'<input type="submit" value="'.__('Save').'" /></p>'.
				'</form>';
			$as->endPage();
		}
	}
	public static function doChangePostLang($core, dcPostsActionSet $as, $post) {
		if (isset($post['new_lang'])) {
			$new_lang = $post['new_lang'];
			try
			{
				$cur = $core->con->openCursor($core->prefix.'post');
				$cur->post_lang = $new_lang;
				$cur->update('WHERE post_id '.$core->con->in($posts_ids));
				
				$as->redirect(array('upd' => 1),true);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessages());
			}
		} else {
			$as->beginPage(
				dcPage::breadcrumb(
					array(
						html::escapeHTML($core->blog->name) => '',
						__('Entries') => 'posts.php',
						'<span class="page-title">'.__('Change language for entries').'</span>' => ''
			)));
			# lang list
			# Languages combo
			$rs = $core->blog->getLangs(array('order'=>'asc'));
			$all_langs = l10n::getISOcodes(0,1);
			$lang_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1,1));
			while ($rs->fetch()) {
				if (isset($all_langs[$rs->post_lang])) {
					$lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;
					unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
				} else {
					$lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
				}
			}
			unset($all_langs);
			unset($rs);
			
			echo
			'<form action="posts_actions.php" method="post">'.
			$as->getCheckboxes().
			
			'<p><label for="new_lang" class="classic">'.__('Entry lang:').'</label> '.
			form::combo('new_lang',$lang_combo,'');
			
			echo
				$core->formNonce().
				form::hidden(array('action'),'lang').
				'<input type="submit" value="'.__('Save').'" /></p>'.
				'</form>';
		}
	}
}


class dcLegacyPosts
{
	public static function adminPostsActionSet($core, dcPostsActionSet $as) {
		$stub_actions = new ArrayObject();
		$core->callBehavior('adminPostsActionsCombo',array($stub_actions));
		if (!empty($stub_actions)) {
			$as->addAction($stub_actions,array('dcLegacyPosts','onActionLegacy'));
		}
	}
	
	public static function onActionLegacy($core, dcPostsActionSet $as, $post) {
		$core->callBehavior('adminPostsActions',$core,$as->getRS(),$as->getAction(),$as->getRedirection());
		$as->beginPage($core->callBehavior('adminPostsActionsHeaders'),'');
		$core->callBehavior('adminPostsActionsContent',$core,$as->getAction(),$as->getHiddenFields(true));
		$as->endPage();
	
	}
}