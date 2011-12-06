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
if (!defined('DC_CONTEXT_ADMIN')) { return; }
dcPage::check('pages,contentadmin');

/* Pager class
-------------------------------------------------------- */
class adminPageList extends adminPostList
{
	public function setColumns()
	{
		$this->addColumn('title',__('Title'),array('adminPostList','getTitle'),' class="maximal"',false);
		$this->addColumn('date',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('author',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('comment',__('Comments'),array('adminPostList','getComments'));
		$this->addColumn('trackback',__('Trackbacks'),array('adminPostList','getTrackbacks'));
		$this->addColumn('status',__('Status'),array('adminPostList','getStatus'));
	}
	
	protected function getDefaultCaption()
	{
		return __('Pages list');
	}
}

/* Getting pages
-------------------------------------------------------- */
$params = array(
	'post_type' => 'page'
);

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	$nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['order'] = 'post_position ASC, post_title ASC';

try {
	$pages = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list = new adminPageList($core,$pages,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id))
{
	$combo_action[__('publish')] = 'publish';
	$combo_action[__('unpublish')] = 'unpublish';
	$combo_action[__('schedule')] = 'schedule';
	$combo_action[__('mark as pending')] = 'pending';
}
if ($core->auth->check('admin',$core->blog->id)) {
	$combo_action[__('change author')] = 'author';
}
if ($core->auth->check('delete,contentadmin',$core->blog->id))
{
	$combo_action[__('delete')] = 'delete';
}

# --BEHAVIOR-- adminPagesActionsCombo
$core->callBehavior('adminPagesActionsCombo',array(&$combo_action));

/* Display
-------------------------------------------------------- */
?>
<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?")); ?>
  //]]>
  </script>
</head>

<body>
<?php
echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Pages').'</span></h2>'.
'<p class="top-add"><a class="button add" href="'.$p_url.'&amp;act=page">'.__('New page').'</a></p>';

if (!$core->error->flag())
{
	# Show pages
	$post_list->display($page,$nb_per_page,
	'<form action="posts_actions.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected pages action:').'</label> '.
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('post_type'),'page').
	form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
	$core->formNonce().
	'</div>'.
	'</form>');
}
dcPage::helpBlock('pages');
?>
</body>
</html>