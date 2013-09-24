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

$tag = (!empty($_REQUEST['tag']) || $_REQUEST['tag'] == '0') ? $_REQUEST['tag'] : '';

$this_url = $p_url.'&amp;m=tag_posts&amp;tag='.rawurlencode($tag);


$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
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

$posts_actions_page = new dcPostsActionsPage($core,'plugin.php',array('p'=>'tags', 'm'=>'tag_posts', 'tag'=> $tag));

if ($posts_actions_page->process()) {
	return;
}

?>
<html>
<head>
  <title><?php echo __('Tags'); ?></title>
  <link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  dotclear.msg.confirm_tag_delete = '<?php echo html::escapeJS(sprintf(__('Are you sure you want to remove tag: “%s”?'),html::escapeHTML($tag))) ?>';
  $(function() {
    $('#tag_delete').submit(function() {
      return window.confirm(dotclear.msg.confirm_tag_delete);
    });
  });
  //]]>
  </script>
</head>
<body>

<?php
echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		__('Tags') => $p_url.'&amp;m=tags',
		'<span class="page-title">'.__('Tag').' &ldquo;'.html::escapeHTML($tag).'&rdquo;'.'</span>' => ''
	));
?>

<?php
if (!empty($_GET['renamed'])) {
	dcPage::success(__('Tag has been successfully renamed'));
}

echo '<p><a class="back" href="'.$p_url.'&amp;m=tags">'.__('Back to tags list').'</a></p>';

if (!$core->error->flag())
{
	if (!$posts->isEmpty())
	{
		echo
		'<div class="fieldset">'.
		'<form action="'.$this_url.'" method="post">'.
		'<h3>'.__('Actions').'</h3>'.
		'<p><label for="new_tag_id">'.__('Edit tag name:').'</label>'.
		form::field('new_tag_id',20,255,html::escapeHTML($tag)).
		'<input type="submit" value="'.__('Rename').'" />'.
		$core->formNonce().
		'</p></form>';
		# Remove tag
		if (!$posts->isEmpty() && $core->auth->check('contentadmin',$core->blog->id)) {
			echo
			'<form id="tag_delete" action="'.$this_url.'" method="post">'.
			'<p>'.__('Delete this tag:').' '.
			'<input type="submit" class="delete" name="delete" value="'.__('Delete').'" />'.
			$core->formNonce().
			'</p></form>';
		}
		echo '</div>';
	}
	
	# Show posts
	echo '<h3>'.sprintf(__('List of entries with the tag “%s”'),html::escapeHTML($tag)).'</h3>';
	$post_list->display($page,$nb_per_page,
	'<form action="plugin.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected entries action:').'</label> '.
	form::combo('action',$posts_actions_page->getCombo()).
	'<input type="submit" value="'.__('OK').'" /></p>'.
	form::hidden('post_type','').
	form::hidden('p','tags').
	form::hidden('m','tag_posts').
	form::hidden('tag',$tag).
	$core->formNonce().
	'</div>'.
	'</form>');
}
?>
</body>
</html>