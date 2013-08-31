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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

dcPage::check('usage,contentadmin');

$params = array(
	'post_type' => 'page'
);

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
		$this->entries=array();
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
				'<table class="pages-list"><tr>'.
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
$pages_ids = array();

if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
{
	$redir = $_POST['redir'];
}
else
{
	$redir ='plugin.php?p=pages';
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
	
	$pages = $core->blog->getPosts($params);
	while ($pages->fetch())	{
		$pages_ids[] = $pages->post_id;
		$fields->addEntry($pages->post_id,$pages->post_title);
	}
	// Redirection including selected entries
	$redir_sel = $redir.'&'.$fields->getEntriesQS();

} else {
	$pages = $core->con->select("SELECT blog_id FROM ".$core->prefix."blog WHERE false");;
}

/* Actions
-------------------------------------------------------- */
if (!empty($_POST['reorder']))
{
 	$action = 'reorder';
}
elseif (!empty($_POST['action']))
{
	$action = $_POST['action'];
} 
else
{
	$core->error->add(__('No action specified.'));
	dcPage::open(
		__('Pages'),'',dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Pages') => 'plugin.php?p=pages',
			'<span class="page-title">'.__('Entries actions').'</span>' => ''
		))
	);
	
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	dcPage::close();
	exit;
}

# --BEHAVIOR-- adminPagesActions
$core->callBehavior('adminPagesActions',$core,$pages,$action,$redir);

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
		$core->blog->updPostsStatus($pages_ids,$status);
		
		http::redirect($redir_sel.'&upd=1');
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
		foreach($pages_ids as $post_id)
		{
			# --BEHAVIOR-- adminBeforePostDelete
			$core->callBehavior('adminBeforePagesDelete',(integer) $post_id);
		}
		
		# --BEHAVIOR-- adminBeforePagesDelete
		$core->callBehavior('adminBeforePagesDelete',$pages_ids);
		
		$core->blog->delPosts($pages_ids);
		
		http::redirect($redir."&del=1");
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
	
}
elseif ($action == 'author' && isset($_POST['new_auth_id']) && $core->auth->check('admin',$core->blog->id))
{
	$new_user_id = $_POST['new_auth_id'];
	
	try
	{
		if ($core->getUser($new_user_id)->isEmpty()) {
			throw new Exception(__('This user does not exist'));
		}
		
		$cur = $core->con->openCursor($core->prefix.'post');
		$cur->user_id = $new_user_id;
		$cur->update('WHERE post_id '.$core->con->in($pages_ids));
		
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
elseif ( $action == 'reorder' && isset($_POST['order']))
{
	try {
		
		foreach($_POST['order'] as $post_id => $value) {
			
			if (!$core->auth->check('publish,contentadmin',$core->blog->id))
				throw new Exception(__('You are not allowed to change this entry status'));
			
			$strReq = "WHERE blog_id = '".$core->con->escape($core->blog->id)."' ".
					"AND post_id ".$core->con->in($post_id);
			
			#If user can only publish, we need to check the post's owner
			if (!$core->auth->check('contentadmin',$core->blog->id))
				$strReq .= "AND user_id = '".$core->con->escape($core->auth->userID())."' ";
			
			$cur = $core->con->openCursor($core->prefix.'post');
			
			$cur->post_position = (integer) $value-1;
			$cur->post_upddt = date('Y-m-d H:i:s');
			
			$cur->update($strReq);
			$core->blog->triggerBlog();
			
		}

		http::redirect($redir."&reo=1");
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
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
?>

<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <?php
  	echo '<script type="text/javascript">'."\n".
	"//<![CDATA[\n".
	'usersList = ['.$usersList.']'."\n".
	"\n//]]>\n".
	"</script>\n".
	dcPage::jsLoad('js/jquery/jquery.autocomplete.js').
	dcPage::jsLoad('index.php?pf=pages/list.js').
	dcPage::jsMetaEditor().
	# --BEHAVIOR-- adminBeforePostDelete
	$core->callBehavior('adminPagesActionsHeaders');
  ?>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?")); ?>
  //]]>
  </script>
</head>
<body>

<?php
if (!isset($action)) {
?>
</body>
</html>
<?php
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

# --BEHAVIOR-- adminPagesActionsContent
$core->callBehavior('adminPagesActionsContent',$core,$action,$fields);

if ($action == 'lang')
{
	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Entries') => 'posts.php',
			'<span class="page-title">'.__('Change language for entries').'</span>' => ''
	));
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

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
	
	'<p><label for="new_lang" class="classic">'.__('Entry lang:').'</label> '.
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
			__('Pages') => 'plugin.php?p=pages',
			'<span class="page-title">'.__('Change author for this selection').'</span>' => ''
	));
	echo '<p><a class="back" href="'.html::escapeURL($redir_sel).'">'.__('Back to entries list').'</a></p>';

	echo
	'<form action="plugin.php?p=pages&act=actions" method="post">'.
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
?>
</body>
</html>
