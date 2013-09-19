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

/**
* FieldsList - Compatibility class for hidden fields & entries[] fields
*
*/
class FieldsList {
	/** @var array list of hidden fields */
	protected $hidden;
	/** @var array list of selected entries */
	protected $entries;


   /**
     * Class constructor
	*/
	public function __construct() {
		$this->hidden=array();
		$this->entries =array();
	}

    /**
     * addHidden - adds a hidden field
     * 
     * @param string $name the field name.
     * @param mixed $value the field value.
     *
     * @access public
	 * @return the FieldsList instance, enabling to chain requests
     */	
	 public function addHidden($name,$value) {
		$this->hidden[] = form::hidden($name,$value);
		return $this;
	}

    /**
     * addEntry - adds a antry field
     * 
     * @param string $id the entry id.
     * @param mixed $title the entry title.
     *
     * @access public
	 * @return the FieldsList instance, enabling to chain requests
     */	
	 public function addEntry($id,$title) {
		$this->entries[$id]=$title;
		return $this;
	}

    /**
     * getHidden - returns the list of hidden fields, html encoded
     *
     * @access public
	 * @return the list of hidden fields, html encoded
     */
	 public function getHidden() {
		return join('',$this->hidden);
	}
	
    /**
     * getEntries - returns the list of entry fields, html encoded
     *
	 * @param boolean $hidden if set to true, returns entries as a list of hidden field
	 *                if set to false, returns html code displaying the list of entries
	 *                with a list of checkboxes to enable to select/deselect entries
     * @access public
	 * @return the list of entry fields, html encoded
     */
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
	
    /**
     * getEntriesQS - returns the list of entry fields as query string
     *
     * @access public
	 * @return the list of entry fields, html encoded
     */
	public function getEntriesQS() {
		$ret=array();
		foreach ($this->entries as $id=>$title) {
			$ret[] = 'entries[]='.$id;
		}
		return join('&',$ret);
	}
	
    /**
     * __toString - magic method. -- DEPRECATED here
	 *              This method is only used to preserve compatibility with plugins 
	 *				relying on previous versions of adminPostsActionsContent behavior, 
	 *
     * @access public
	 * @return the list of hidden fields and entries (as hidden fields too), html encoded
     */
	public function __toString() {
		return join('',$this->hidden).$this->getEntries(true);
	}
}

$fields = new FieldsList();
$posts_ids = array();

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
$redir_sel = $redir;

if (!empty($_POST['entries']))
{
	$entries = $_POST['entries'];
	
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
	while ($posts->fetch())	{
		$posts_ids[] = $posts->post_id;
		$fields->addEntry($posts->post_id,$posts->post_title);
	}
	// Redirection including selected entries
	$redir_sel = $redir.'&'.$fields->getEntriesQS();

} else {
	$posts = $core->con->select("SELECT blog_id FROM ".$core->prefix."blog WHERE false");;
}

/* Actions
-------------------------------------------------------- */
if (!empty($_POST['action']))
{
	$action = $_POST['action'];
} 
else
{
	$core->error->add(__('No action specified.'));
	dcPage::open(
		__('Entries'),'',dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			'<span class="page-title">'.__('Entries actions').'</span>' => ''
		))
	);
	
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	dcPage::close();
	exit;
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
		
		http::redirect($redir_sel.'&upd=1');
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
		
		http::redirect($redir_sel."&upd=1");
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
		
		http::redirect($redir."&del=1");
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
	
}
elseif ($action == 'category' && isset($_POST['new_cat_id']))
{
	$new_cat_id = $_POST['new_cat_id'];
	
	try
	{
		if (!empty($_POST['new_cat_title']) && $core->auth->check('categories', $core->blog->id))
		{
			$cur_cat = $core->con->openCursor($core->prefix.'category');
			$cur_cat->cat_title = $_POST['new_cat_title'];
			$cur_cat->cat_url = '';
			
			$parent_cat = !empty($_POST['new_cat_parent']) ? $_POST['new_cat_parent'] : '';
			
			# --BEHAVIOR-- adminBeforeCategoryCreate
			$core->callBehavior('adminBeforeCategoryCreate', $cur_cat);
			
			$new_cat_id = $core->blog->addCategory($cur_cat, (integer) $parent_cat);
			
			# --BEHAVIOR-- adminAfterCategoryCreate
			$core->callBehavior('adminAfterCategoryCreate', $cur_cat, $new_cat_id);
		}
		
		$core->blog->updPostsCategory($posts_ids, $new_cat_id);
		
		http::redirect($redir_sel."&upd=1");
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
		
		http::redirect($redir_sel."&upd=1");
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
		
		http::redirect($redir_sel."&upd=1");
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessages());
	}
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
			'<span class="page-title">'.__('Change category for this selection').'</span>' => ''
	));
	
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	# categories list
	# Getting categories
	$categories_combo = dcAdminCombos::getCategoriesCombo(
		$core->blog->getCategories(array('post_type'=>'post'))
	);
	
	echo
	'<form action="posts_actions.php" method="post">'.
	$fields->getEntries().
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
			'<span class="page-title">'.__('Change language for this selection').'</span>' => ''
	));
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	# lang list
	# Languages combo
	$rs = $core->blog->getLangs(array('order'=>'asc'));
	$lang_combo = dcAdminCombos::getLangsCombo($rs,true);
	
	echo
	'<form action="posts_actions.php" method="post">'.
	$fields->getEntries().
	
	'<p><label for="new_lang" class="classic">'.__('Entry language:').'</label> '.
	form::combo('new_lang',$lang_combo,'');
	
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
			'<span class="page-title">'.__('Change author for this selection').'</span>' => ''
	));
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	echo
	'<form action="posts_actions.php" method="post">'.
	$fields->getEntries().
	'<p><label for="new_auth_id" class="classic">'.__('New author (author ID):').'</label> '.
	form::field('new_auth_id',20,255);
	
	echo
	$fields->getHidden().
	$core->formNonce().
	form::hidden(array('action'),'author').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';
}

dcPage::close();
?>