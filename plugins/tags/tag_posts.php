<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$tag = (!empty($_REQUEST['tag']) || $_REQUEST['tag'] == '0') ? $_REQUEST['tag'] : '';

$this_url = $p_url.'&amp;m=tag_posts&amp;tag='.rawurlencode($tag);


$page = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page =  30;

# Rename a tag
if (!empty($_POST['new_tag_id']) || $_POST['new_tag_id'] == '0')
{
	$new_id = dcMeta::sanitizeMetaID($_POST['new_tag_id']);
	try {
		if ($core->meta->updateMeta($tag,$new_id,'tag')) {
			http::redirect($p_url.'&m=tag_posts&tag='.$new_id.'&renamed=1');
		}
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Delete a tag
if (!empty($_POST['delete']) && $core->auth->check('publish,contentadmin',$core->blog->id))
{
	try {
		$core->meta->delMeta($tag,'tag');
		http::redirect($p_url.'&m=tags&del=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;

$params['meta_id'] = $tag;
$params['meta_type'] = 'tag';
$params['post_type'] = '';

# Get posts
try {
	$posts = $core->meta->getPostsByMeta($params);
	$counter = $core->meta->getPostsByMeta($params,true);
	$post_list = new adminPostList($core,$posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id))
{
	$combo_action[__('Status')] = array(
		__('Publish') => 'publish',
		__('Unpublish') => 'unpublish',
		__('Schedule') => 'schedule',
		__('Mark as pending') => 'pending'
	);
}
$combo_action[__('Mark')] = array(
	__('Mark as selected') => 'selected',
	__('Mark as unselected') => 'unselected'
);
$combo_action[__('Change')] = array(__('Change category') => 'category');
if ($core->auth->check('admin',$core->blog->id))
{
	$combo_action[__('Change')] = array_merge($combo_action[__('Change')],
		array(__('Change author') => 'author'));
}
if ($core->auth->check('delete,contentadmin',$core->blog->id))
{
	$combo_action[__('Delete')] = array(__('Delete') => 'delete');
}

# --BEHAVIOR-- adminPostsActionsCombo
$core->callBehavior('adminPostsActionsCombo',array(&$combo_action));

?>
<html>
<head>
  <title>Tags</title>
  <link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  dotclear.msg.confirm_tag_delete = '<?php echo html::escapeJS(sprintf(__('Are you sure you want to remove this %s?'),'tag')) ?>';
  $(function() {
    $('#tag_delete').submit(function() {
      return window.confirm(dotclear.msg.confirm_tag_delete);
    });
  });
  //]]>
  </script>
</head>
<body>

<h2><?php echo html::escapeHTML($core->blog->name); ?> &rsaquo;
<?php echo __('Edit tag'); ?></h2>

<?php
if (!empty($_GET['renamed'])) {
	echo '<p class="message">'.__('Tag has been successfully renamed').'</p>';
}

echo '<p><a href="'.$p_url.'&amp;m=tags">'.__('Back to tags list').'</a></p>';

if (!$core->error->flag())
{
	if (!$posts->isEmpty())
	{
		echo
		'<form action="'.$this_url.'" method="post">'.
		'<p><label class="classic">'.__('Rename this tag:').' '.
		form::field('new_tag_id',20,255,html::escapeHTML($tag)).
		'</label> <input type="submit" value="'.__('save').'" />'.
		$core->formNonce().'</p>'.
		'</form>';
	}
	
	# Show posts
	$post_list->display($page,$nb_per_page,
	'<form action="posts_actions.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right">'.__('Selected entries action:').' '.
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden('post_type','').
	form::hidden('redir',$p_url.'&amp;m=tag_posts&amp;tag='.
		str_replace('%','%%',rawurlencode($tag)).'&amp;page='.$page).
	$core->formNonce().
	'</div>'.
	'</form>');
	
	# Remove tag
	if (!$posts->isEmpty() && $core->auth->check('contentadmin',$core->blog->id)) {
		echo
		'<form id="tag_delete" action="'.$this_url.'" method="post">'.
		'<p><input type="submit" class="delete" name="delete" value="'.__('Delete this tag').'" />'.
		$core->formNonce().'</p>'.
		'</form>';
	}
}
?>
</body>
</html>
