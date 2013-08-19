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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$params = array();

class FieldsList {
	protected $hidden;
	protected $entries;
	public function __construct() {
		$this->hidden=array();
		$this->entries =array();
	}
	public function addHidden($name,$value) {
		$this->hidden[] = form::hidden($name,$value);
		return $this;
	}
	public function addEntry($id,$title) {
		$this->entries[$id]=$title;
		return $this;
	}

	public function getHidden() {
		return join('',$this->hidden);
	}
	
	public function getEntries ($hidden=false) {
		$ret = '';
		if ($hidden) {
			foreach ($this->entries as $id=> $e) {
				$ret .= form::hidden('entries[]',$id);
			}
		} else {
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
		}
		return $ret;
	}
	
	public function __toString() {
		return join('',$this->hidden).$this->getEntries(true);
	}
}


function listEntries($titles) {
	$ret = 
		'<table class="posts-list"><tr>'.
		'<th colspan="2">'.__('Title').'</th>'.
		'</tr>';
	foreach ($titles as $id=>$title) {
		$ret .= 
			'<tr><td>'.
			form::checkbox(array('entries[]'),$id,true,'','').'</td>'.
			'<td>'.	$title.'</td></tr>';
	}
	$ret .= '</table>';
	return $ret;
}

/* Actions
-------------------------------------------------------- */
if (!empty($_POST['action']) && !empty($_POST['entries']))
{
	$entries = $_POST['entries'];
	$action = $_POST['action'];
	
	if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
	{
		$redir = $_POST['redir'];
	}
	else
	{
		$redir =
		'posts.php?user_id='.$_POST['user_id'].
		'&cat_id='.$_POST['cat_id'].
		'&status='.$_POST['status'].
		'&selected='.$_POST['selected'].
		'&month='.$_POST['month'].
		'&lang='.$_POST['lang'].
		'&sortby='.$_POST['sortby'].
		'&order='.$_POST['order'].
		'&page='.$_POST['page'].
		'&nb='.$_POST['nb'];
	}
	
	foreach ($entries as $k => $v) {
		$entries[$k] = (integer) $v;
	}
	
	$params['sql'] = 'AND P.post_id IN('.implode(',',$entries).') ';
	
	if (!isset($_POST['full_content']) || empty($_POST['full_content'])) {
		$params['no_content'] = true;
	}
	
	if (isset($_POST['post_type'])) {
		$params['post_type'] = $_POST['post_type'];
	}
	
	$posts = $core->blog->getPosts($params);
	
	$posts_ids = array();
	while ($posts->fetch())	{
		$posts_ids[] = $posts->post_id;
	}
	
	# --BEHAVIOR-- adminPostsActions
	$core->callBehavior('adminPostsActions',$core,$posts,$action,$redir);
	
	if (preg_match('/^(publish|unpublish|schedule|pending)$/',$action))
	{
		switch ($action) {
			case 'unpublish' : $status = 0; break;
			case 'schedule' : $status = -1; break;
			case 'pending' : $status = -2; break;
			default : $status = 1; break;
		}
		
		try
		{
			$core->blog->updPostsStatus($posts_ids,$status);
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'selected' || $action == 'unselected')
	{
		try
		{
			$core->blog->updPostsSelected($posts_ids,$action == 'selected');
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'delete')
	{
		try
		{
			// Backward compatibility
			foreach($posts_ids as $post_id)
			{
				# --BEHAVIOR-- adminBeforePostDelete
				$core->callBehavior('adminBeforePostDelete',(integer) $post_id);
			}
			
			# --BEHAVIOR-- adminBeforePostsDelete
			$core->callBehavior('adminBeforePostsDelete',$posts_ids);
			
			$core->blog->delPosts($posts_ids);
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
		
	}
	elseif ($action == 'category' && isset($_POST['new_cat_id']))
	{
		try
		{
			$core->blog->updPostsCategory($posts_ids,$_POST['new_cat_id']);
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'author' && isset($_POST['new_auth_id'])
	&& $core->auth->check('admin',$core->blog->id))
	{
		$new_user_id = $_POST['new_auth_id'];
		
		try
		{
			if ($core->getUser($new_user_id)->isEmpty()) {
				throw new Exception(__('This user does not exist'));
			}
			
			$cur = $core->con->openCursor($core->prefix.'post');
			$cur->user_id = $new_user_id;
			$cur->update('WHERE post_id '.$core->con->in($posts_ids));
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'lang' && isset($_POST['new_lang']))
	{
		$new_lang = $_POST['new_lang'];
		try
		{
			$cur = $core->con->openCursor($core->prefix.'post');
			$cur->post_lang = $new_lang;
			$cur->update('WHERE post_id '.$core->con->in($posts_ids));
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessages());
		}
	}
} else {
	if (empty($_POST['entries'])) {
		$core->error->add(__('At least one entry should be selected'));
	} else {
		$core->error->add(__('No action specified.'));
	}
	dcPage::open(
		__('Entries'),'',dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			'<span class="page-title">'.__('Entries actions').'</span>' => ''
		))
	);

	dcPage::close();
	exit;
}
/* DISPLAY
-------------------------------------------------------- */
// Get current users list
$usersList = '';
if ($action == 'author' && $core->auth->check('admin',$core->blog->id)) {
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
dcPage::open(
	__('Entries'),
	'<script type="text/javascript">'."\n".
	"//<![CDATA[\n".
	'usersList = ['.$usersList.']'."\n".
	"\n//]]>\n".
	"</script>\n".
	dcPage::jsLoad('js/jquery/jquery.autocomplete.js').
	dcPage::jsLoad('js/_posts_actions.js').
	dcPage::jsMetaEditor().
	# --BEHAVIOR-- adminBeforePostDelete
	$core->callBehavior('adminPostsActionsHeaders')
);

if (!isset($action)) {
	dcPage::close();
	exit;
}

$fields = new FieldsList();
while ($posts->fetch()) {
	$fields->addEntry($posts->post_id,$posts->post_title);
}

if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
{
	$fields->addHidden(array('redir'),html::escapeURL($_POST['redir']));
}
else
{
	$fields
		->addHidden(array('user_id'),$_POST['user_id'])
		->addHidden(array('cat_id'),$_POST['cat_id'])
		->addHidden(array('status'),$_POST['status'])
		->addHidden(array('selected'),$_POST['selected'])
		->addHidden(array('month'),$_POST['month'])
		->addHidden(array('lang'),$_POST['lang'])
		->addHidden(array('sortby'),$_POST['sortby'])
		->addHidden(array('order'),$_POST['order'])
		->addHidden(array('page'),$_POST['page'])
		->addHidden(array('nb'),$_POST['nb'])
	;
}

if (isset($_POST['post_type'])) {
	$fields->addHidden(array('post_type'),$_POST['post_type']);
}

# --BEHAVIOR-- adminPostsActionsContent
$core->callBehavior('adminPostsActionsContent',$core,$action,$fields);

if ($action == 'category')
{
	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			__('Change category for entries') => ''
	));

	# categories list
	# Getting categories
	$categories_combo = array('&nbsp;' => '');
	try {
		$categories = $core->blog->getCategories(array('post_type'=>'post'));
		while ($categories->fetch()) {
			$categories_combo[] = new formSelectOption(
				str_repeat('&nbsp;&nbsp;',$categories->level-1).
				($categories->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($categories->cat_title),
				$categories->cat_id
			);
		}
	} catch (Exception $e) { }
	
	echo
	'<form action="posts_actions.php" method="post">'.
	$fields->getEntries().
	'<p><label for="new_cat_id" class="classic">'.__('Category:').' '.
	form::combo('new_cat_id',$categories_combo,'').
	'</label> ';
	
	echo
	$fields->getHidden().
	$core->formNonce().
	form::hidden(array('action'),'category').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';
}
elseif ($action == 'lang')
{
	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			'<span class="page-title">'.__('Change language for entries').'</span>' => ''
	));
	
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
	$fields->getEntries().	
	'<p><label for="new_lang" class="classic">'.__('Entry lang:').' '.
	form::combo('new_lang',$lang_combo,'').
	'</label> ';
	
	echo
	$fields->getHidden().
	$core->formNonce().
	form::hidden(array('action'),'lang').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';

}
elseif ($action == 'author' && $core->auth->check('admin',$core->blog->id))
{
	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			'<span class="page-title">'.__('Change author for entries').'</span>' => ''
	));
	
	echo
	'<form action="posts_actions.php" method="post">'.
	$fields->getEntries().
	'<p><label for="new_auth_id" class="classic">'.__('Author ID:').' '.
	form::field('new_auth_id',20,255).
	'</label> ';
	
	echo
	$fields->getHidden().
	$core->formNonce().
	form::hidden(array('action'),'author').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';
}

echo '<p><a class="back" href="'.html::escapeURL($redir).'">'.__('back').'</a></p>';

dcPage::close();
?>