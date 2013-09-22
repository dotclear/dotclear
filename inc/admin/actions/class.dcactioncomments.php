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

class dcCommentsActionsPage extends dcActionsPage
{
	public function __construct($core,$uri,$redirect_args=array()) {
		parent::__construct($core,$uri,$redirect_args);
		$this->redirect_fields = array('type','author','status',
			'sortby','ip','order','page','nb');
		$this->field_entries = 'comments';
		$this->title_cb = __('Comments');
		$this->loadDefaults();
		$core->callBehavior('adminCommentsActionsPage',$core,$this);
	}

	protected function loadDefaults() {
		// We could have added a behavior here, but we want default action
		// to be setup first
		dcDefaultCommentActions::adminCommentsActionsPage($this->core,$this);
	}
	
	public function beginPage($breadcrumb='',$head='') {
		dcPage::open(
			__('Comments'),
			
			dcPage::jsLoad('js/_comments_actions.js').
			$head,
			$breadcrumb
		);	
		echo '<p><a class="back" href="'.$this->getRedirection(array(),true).'">'.__('Back to entries list').'</a></p>';
	}
	
	public function endPage() {
		dcPage::close();
	}

	public function error(Exception $e) {
		$this->core->error->add($e->getMessage());
		$this->beginPage(dcPage::breadcrumb(
			array(
				html::escapeHTML($this->core->blog->name) => '',
				__('Comments') => 'comments.php',
				'<span class="page-title">'.__('Comments actions').'</span>' => ''
			))
		);
		$this->endPage();
	}
	
	protected function fetchEntries($from) {
		if (!empty($from['comments'])) {
			$comments = $from['comments'];
			
			foreach ($comments as $k => $v) {
				$comments[$k] = (integer) $v;
			}
			
			$params['sql'] = 'AND C.comment_id IN('.implode(',',$comments).') ';
			
			if (!isset($from['full_content']) || empty($from['full_content'])) {
				$params['no_content'] = true;
			}
			
			$co = $this->core->blog->getComments($params);
			while ($co->fetch())	{
				$this->entries[$co->comment_id] = array(
					'title' => $co->post_title,
					'author' => $co->comment_author
				);
			}
			$this->rs = $co;
		}
	}
}

class dcDefaultCommentActions 
{
	public static function adminCommentsActionsPage($core, dcCommentsActionsPage $ap) {
		if ($core->auth->check('publish,contentadmin',$core->blog->id))
		{
			$action = array('dcDefaultCommentActions','doChangeCommentStatus');
			$ap->addAction (array(__('Publish') => 'publish'), $action);
			$ap->addAction (array(__('Publish') => 'publish'), $action);
			$ap->addAction (array(__('Unpublish') => 'unpublish'), $action);
			$ap->addAction (array(__('Mark as pending') => 'pending'), $action);
			$ap->addAction(array(__('Mark as junk') => 'junk'), $action);
		}
	
		if ($core->auth->check('delete,contentadmin',$core->blog->id))
		{
			$ap->addAction(array(__('Delete') => 'delete'),
				array('dcDefaultCommentActions','doDeleteComment'));
		}
	}

	public static function doChangeCommentStatus($core, dcCommentsActionsPage $ap, $post) {
		$action = $ap->getAction();
		$co_ids = $ap->getIDs();
		if (empty($co_ids)) {
			throw new Exception(__('No comment selected'));
		}
		switch ($action) {
			case 'unpublish' : $status = 0; break;
			case 'pending' : $status = -1; break;
			case 'junk' : $status = -2; break;
			default : $status = 1; break;
		}
		
		$core->blog->updCommentsStatus($co_ids,$status);
		$ap->redirect(array('upd'=>1),true);
	}

	public static function doDeleteComment($core, dcCommentsActionsPage $ap, $post) {
		$co_ids = $ap->getIDs();
		if (empty($co_ids)) {
			throw new Exception(__('No comment selected'));
		}
		// Backward compatibility
		foreach($co_ids as $comment_id)
		{
			# --BEHAVIOR-- adminBeforeCommentDelete
			$core->callBehavior('adminBeforeCommentDelete',$comment_id);				
		}
		
		# --BEHAVIOR-- adminBeforeCommentsDelete
		$core->callBehavior('adminBeforeCommentsDelete',$co_ids);
		
		$core->blog->delComments($co_ids);
		$ap->redirect(array('del'=>1), false);
	}
}
