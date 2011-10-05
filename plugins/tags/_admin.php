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

$_menu['Blog']->addItem(__('Tags'),'plugin.php?p=tags&amp;m=tags','index.php?pf=tags/icon.png',
		preg_match('/plugin.php\?p=tags&m=tag(s|_posts)?(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('adminPostFormSidebar',array('tagsBehaviors','tagsField'));

$core->addBehavior('adminAfterPostCreate',array('tagsBehaviors','setTags'));
$core->addBehavior('adminAfterPostUpdate',array('tagsBehaviors','setTags'));

$core->addBehavior('adminPostHeaders',array('tagsBehaviors','postHeaders'));
$core->addBehavior('adminPostsActionsHeaders',array('tagsBehaviors','postsActionsHeaders'));

$core->addBehavior('adminPostsActionsCombo',array('tagsBehaviors','adminPostsActionsCombo'));
$core->addBehavior('adminPostsActions',array('tagsBehaviors','adminPostsActions'));
$core->addBehavior('adminPostsActionsContent',array('tagsBehaviors','adminPostsActionsContent'));

$core->addBehavior('adminPreferencesForm',array('tagsBehaviors','adminUserForm'));
// $core->addBehavior('adminUserForm',array('tagsBehaviors','adminUserForm'));

$core->addBehavior('adminBeforeUserCreate',array('tagsBehaviors','setTagListFormat'));
$core->addBehavior('adminBeforeUserUpdate',array('tagsBehaviors','setTagListFormat'));

$core->addBehavior('coreInitWikiPost',array('tagsBehaviors','coreInitWikiPost'));

$core->addBehavior('adminDashboardFavs',array('tagsBehaviors','dashboardFavs'));

# BEHAVIORS
class tagsBehaviors
{
	public static function dashboardFavs($core,$favs)
	{
		$favs['tags'] = new ArrayObject(array('tags','Tags','plugin.php?p=tags&amp;m=tags',
			'index.php?pf=tags/icon.png','index.php?pf=tags/icon-big.png',
			'usage,contentadmin',null,null));
	}

	public static function coreInitWikiPost($wiki2xhtml)
	{
		$wiki2xhtml->registerFunction('url:tag',array('tagsBehaviors','wiki2xhtmlTag'));
	}
	
	public static function wiki2xhtmlTag($url,$content)
	{
		$url = substr($url,4);
		if (strpos($content,'tag:') === 0) {
			$content = substr($content,4);
		}
		
		
		$tag_url = html::stripHostURL($GLOBALS['core']->blog->url.$GLOBALS['core']->url->getBase('tag'));
		$res['url'] = $tag_url.'/'.rawurlencode(dcMeta::sanitizeMetaID($url));
		$res['content'] = $content;
		
		return $res;
	}
	
	public static function tagsField($post)
	{
		$meta =& $GLOBALS['core']->meta;
		
		if (!empty($_POST['post_tags'])) {
			$value = $_POST['post_tags'];
		} else {
			$value = ($post) ? $meta->getMetaStr($post->post_meta,'tag') : '';
		}
		
		echo
		'<h3><label for="post_tags">'.__('Tags:').'</label></h3>'.
		'<div class="p" id="tags-edit">'.form::textarea('post_tags',20,3,$value,'maximal').'</div>';
	}
	
	public static function setTags($cur,$post_id)
	{
		$post_id = (integer) $post_id;
		
		if (isset($_POST['post_tags'])) {
			$tags = $_POST['post_tags'];
			$meta =& $GLOBALS['core']->meta;
			$meta->delPostMeta($post_id,'tag');
			
			foreach ($meta->splitMetaValues($tags) as $tag) {
				$meta->setPostMeta($post_id,'tag',$tag);
			}
		}
	}
	
	public static function postHeaders()
	{
		$tag_url = $GLOBALS['core']->blog->url.$GLOBALS['core']->url->getBase('tag');
		
		$opts = $GLOBALS['core']->auth->getOptions();
		$type = isset($opts['tag_list_format']) ? $opts['tag_list_format'] : 'more';
		
		return 
		'<script type="text/javascript" src="index.php?pf=tags/js/jquery.autocomplete.js"></script>'.
		'<script type="text/javascript" src="index.php?pf=tags/js/post.js"></script>'.
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"metaEditor.prototype.meta_url = 'plugin.php?p=tags&m=tag_posts&amp;tag=';\n".
		"metaEditor.prototype.meta_type = '".html::escapeJS($type)."';\n".
		"metaEditor.prototype.text_confirm_remove = '".html::escapeJS(__('Are you sure you want to remove this %s?'))."';\n".
		"metaEditor.prototype.text_add_meta = '".html::escapeJS(__('Add a %s to this entry'))."';\n".
		"metaEditor.prototype.text_choose = '".html::escapeJS(__('Choose from list'))."';\n".
		"metaEditor.prototype.text_all = '".html::escapeJS(__('all'))."';\n".
		"metaEditor.prototype.text_separation = '';\n".
		"jsToolBar.prototype.elements.tag.title = '".html::escapeJS(__('Tag'))."';\n".
		"jsToolBar.prototype.elements.tag.url = '".html::escapeJS($tag_url)."';\n".
		"dotclear.msg.tags_autocomplete = '".html::escapeJS(__('used in %e - frequency %p%'))."';\n".
		"dotclear.msg.entry = '".html::escapeJS(__('entry'))."';\n".
		"dotclear.msg.entries = '".html::escapeJS(__('entries'))."';\n".
		"\n//]]>\n".
		"</script>\n".
		'<link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />';
	}
	
	public static function postsActionsHeaders()
	{
		$tag_url = $GLOBALS['core']->blog->url.$GLOBALS['core']->url->getBase('tag');
		
		$opts = $GLOBALS['core']->auth->getOptions();
		$type = isset($opts['tag_list_format']) ? $opts['tag_list_format'] : 'more';
		
		return 
		'<script type="text/javascript" src="index.php?pf=tags/js/jquery.autocomplete.js"></script>'.
		'<script type="text/javascript" src="index.php?pf=tags/js/posts_actions.js"></script>'.
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"metaEditor.prototype.meta_url = 'plugin.php?p=tags&m=tag_posts&amp;tag=';\n".
		"metaEditor.prototype.meta_type = '".html::escapeJS($type)."';\n".
		"metaEditor.prototype.text_confirm_remove = '".html::escapeJS(__('Are you sure you want to remove this %s?'))."';\n".
		"metaEditor.prototype.text_add_meta = '".html::escapeJS(__('Add a %s to this entry'))."';\n".
		"metaEditor.prototype.text_choose = '".html::escapeJS(__('Choose from list'))."';\n".
		"metaEditor.prototype.text_all = '".html::escapeJS(__('all'))."';\n".
		"metaEditor.prototype.text_separation = '".html::escapeJS(__('Enter tags separated by coma'))."';\n".
		"dotclear.msg.tags_autocomplete = '".html::escapeJS(__('used in %e - frequency %p%'))."';\n".
		"dotclear.msg.entry = '".html::escapeJS(__('entry'))."';\n".
		"dotclear.msg.entries = '".html::escapeJS(__('entries'))."';\n".
		"\n//]]>\n".
		"</script>\n".
		'<link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />';
	}
	
	public static function adminPostsActionsCombo($args)
	{
		$args[0][__('Tags')] = array(__('Add tags') => 'tags');
		
		if ($GLOBALS['core']->auth->check('delete,contentadmin',$GLOBALS['core']->blog->id)) {
			$args[0][__('Tags')] = array_merge($args[0][__('Tags')],
				array(__('Remove tags') => 'tags_remove'));
		}
	}
	
	public static function adminPostsActions($core,$posts,$action,$redir)
	{
		if ($action == 'tags' && !empty($_POST['new_tags']))
		{
			try
			{
				$meta =& $GLOBALS['core']->meta;
				$tags = $meta->splitMetaValues($_POST['new_tags']);
				
				while ($posts->fetch())
				{
					# Get tags for post
					$post_meta = $meta->getMetadata(array(
						'meta_type' => 'tag',
						'post_id' => $posts->post_id));
					$pm = array();
					while ($post_meta->fetch()) {
						$pm[] = $post_meta->meta_id;
					}
					
					foreach ($tags as $t) {
						if (!in_array($t,$pm)) {
							$meta->setPostMeta($posts->post_id,'tag',$t);
						}
					}
				}
				
				http::redirect($redir);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		}
		elseif ($action == 'tags_remove' && !empty($_POST['meta_id']) && $core->auth->check('delete,contentadmin',$core->blog->id))
		{
			try
			{
				$meta =& $GLOBALS['core']->meta;
				while ($posts->fetch())
				{
					foreach ($_POST['meta_id'] as $v)
					{
						$meta->delPostMeta($posts->post_id,'tag',$v);
					}
				}
				
				http::redirect($redir);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		}
	}
	
	public static function adminPostsActionsContent($core,$action,$hidden_fields)
	{
		if ($action == 'tags')
		{
			echo
			'<h2 class="page-title">'.__('Add tags to entries').'</h2>'.
			'<form action="posts_actions.php" method="post">'.
			'<div><label for="new_tags" class="area">'.__('Tags to add:').'</label> '.
			form::textarea('new_tags',60,3).
			'</div>'.
			$hidden_fields.
			$core->formNonce().
			form::hidden(array('action'),'tags').
			'<p><input type="submit" value="'.__('Save').'" '.
			'name="save_tags" /></p>'.
			'</form>';
		}
		elseif ($action == 'tags_remove')
		{
			$meta =& $GLOBALS['core']->meta;
			$tags = array();
			
			foreach ($_POST['entries'] as $id) {
				$post_tags = $meta->getMetadata(array(
					'meta_type' => 'tag',
					'post_id' => (integer) $id))->toStatic()->rows();
				foreach ($post_tags as $v) {
					if (isset($tags[$v['meta_id']])) {
						$tags[$v['meta_id']]++;
					} else {
						$tags[$v['meta_id']] = 1;
					}
				}
			}
			
			echo '<h2 class="page-title">'.__('Remove selected tags from entries').'</h2>';
			
			if (empty($tags)) {
				echo '<p>'.__('No tags for selected entries').'</p>';
				return;
			}
			
			$posts_count = count($_POST['entries']);
			
			echo
			'<form action="posts_actions.php" method="post">'.
			'<fieldset><legend>'.__('Following tags have been found in selected entries:').'</legend>';
			
			foreach ($tags as $k => $n) {
				$label = '<label class="classic">%s %s</label>';
				if ($posts_count == $n) {
					$label = sprintf($label,'%s','<strong>%s</strong>');
				}
				echo '<p>'.sprintf($label,
						form::checkbox(array('meta_id[]'),html::escapeHTML($k)),
						html::escapeHTML($k)).
					'</p>';
			}
			
			echo
			'<p><input type="submit" value="'.__('ok').'" /></p>'.
			$hidden_fields.
			$core->formNonce().
			form::hidden(array('action'),'tags_remove').
			'</fieldset></form>';
		}
	}
	
	public static function adminUserForm($args)
	{
		if ($args instanceof dcCore) {
			$opts = $args->auth->getOptions();
		}
		elseif ($args instanceof record) {
			$opts = $args->options();
		}
		else {
			$opts = array();
		}
		
		$combo = array();
		$combo[__('short')] = 'more';
		$combo[__('extended')] = 'all';
		
		$value = array_key_exists('tag_list_format',$opts) ? $opts['tag_list_format'] : 'more';
		
		echo
		'<fieldset><legend>'.__('Tags').'</legend>'.
		'<p><label for="user_tag_list_format">'.__('Tags list format:').' '.
		form::combo('user_tag_list_format',$combo,$value).
		'</label></p></fieldset>';
	}
	
	public static function setTagListFormat($cur,$user_id = null)
	{
		if (!is_null($user_id)) {
			$cur->user_options['tag_list_format'] = $_POST['user_tag_list_format'];
		}
	}
}
?>