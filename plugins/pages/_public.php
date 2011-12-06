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

# Localized string we find in template
__('Published on');
__('This page\'s comments feed');

require dirname(__FILE__).'/_widgets.php';

class urlPages extends dcUrlHandlers
{
	public static function pages($args)
	{
		if ($args == '') {
			# No page was specified.
			self::p404();
		}
		else
		{
			$_ctx =& $GLOBALS['_ctx'];
			$core =& $GLOBALS['core'];
			
			$core->blog->withoutPassword(false);
			
			$params = new ArrayObject(array(
				'post_type' => 'page',
				'post_url' => $args));
			
			$core->callBehavior('publicPagesBeforeGetPosts',$params,$args);
			
			$_ctx->posts = $core->blog->getPosts($params);
			
			$_ctx->comment_preview = new ArrayObject();
			$_ctx->comment_preview['content'] = '';
			$_ctx->comment_preview['rawcontent'] = '';
			$_ctx->comment_preview['name'] = '';
			$_ctx->comment_preview['mail'] = '';
			$_ctx->comment_preview['site'] = '';
			$_ctx->comment_preview['preview'] = false;
			$_ctx->comment_preview['remember'] = false;
			
			$core->blog->withoutPassword(true);
			
			
			if ($_ctx->posts->isEmpty())
			{
				# The specified page does not exist.
				self::p404();
			}
			else
			{
				$post_id = $_ctx->posts->post_id;
				$post_password = $_ctx->posts->post_password;
				
				# Password protected entry
				if ($post_password != '' && !$_ctx->preview)
				{
					# Get passwords cookie
					if (isset($_COOKIE['dc_passwd'])) {
						$pwd_cookie = unserialize($_COOKIE['dc_passwd']);
					} else {
						$pwd_cookie = array();
					}
					
					# Check for match
					if ((!empty($_POST['password']) && $_POST['password'] == $post_password)
					|| (isset($pwd_cookie[$post_id]) && $pwd_cookie[$post_id] == $post_password))
					{
						$pwd_cookie[$post_id] = $post_password;
						setcookie('dc_passwd',serialize($pwd_cookie),0,'/');
					}
					else
					{
						self::serveDocument('password-form.html','text/html',false);
						return;
					}
				}
				
				$post_comment =
					isset($_POST['c_name']) && isset($_POST['c_mail']) &&
					isset($_POST['c_site']) && isset($_POST['c_content']) &&
					$_ctx->posts->commentsActive();
				
				# Posting a comment
				if ($post_comment)
				{
					# Spam trap
					if (!empty($_POST['f_mail'])) {
						http::head(412,'Precondition Failed');
						header('Content-Type: text/plain');
						echo "So Long, and Thanks For All the Fish";
						# Exits immediately the application to preserve the server.
						exit;
					}
					
					$name = $_POST['c_name'];
					$mail = $_POST['c_mail'];
					$site = $_POST['c_site'];
					$content = $_POST['c_content'];
					$preview = !empty($_POST['preview']);
					
					if ($content != '')
					{
						if ($core->blog->settings->system->wiki_comments) {
							$core->initWikiComment();
						} else {
							$core->initWikiSimpleComment();
						}
						$content = $core->wikiTransform($content);
						$content = $core->HTMLfilter($content);
					}
					
					$_ctx->comment_preview['content'] = $content;
					$_ctx->comment_preview['rawcontent'] = $_POST['c_content'];
					$_ctx->comment_preview['name'] = $name;
					$_ctx->comment_preview['mail'] = $mail;
					$_ctx->comment_preview['site'] = $site;
					
					if ($preview)
					{
						# --BEHAVIOR-- publicBeforeCommentPreview
						$core->callBehavior('publicBeforeCommentPreview',$_ctx->comment_preview);
						
						$_ctx->comment_preview['preview'] = true;
					}
					else
					{
						# Post the comment
						$cur = $core->con->openCursor($core->prefix.'comment');
						$cur->comment_author = $name;
						$cur->comment_site = html::clean($site);
						$cur->comment_email = html::clean($mail);
						$cur->comment_content = $content;
						$cur->post_id = $_ctx->posts->post_id;
						$cur->comment_status = $core->blog->settings->system->comments_pub ? 1 : -1;
						$cur->comment_ip = http::realIP();
						
						$redir = $_ctx->posts->getURL();
						$redir .= $core->blog->settings->system->url_scan == 'query_string' ? '&' : '?';
						
						try
						{
							if (!text::isEmail($cur->comment_email)) {
								throw new Exception(__('You must provide a valid email address.'));
							}
							
							# --BEHAVIOR-- publicBeforeCommentCreate
							$core->callBehavior('publicBeforeCommentCreate',$cur);
							if ($cur->post_id) {					
								$comment_id = $core->blog->addComment($cur);
							
								# --BEHAVIOR-- publicAfterCommentCreate
								$core->callBehavior('publicAfterCommentCreate',$cur,$comment_id);
							}
							
							if ($cur->comment_status == 1) {
								$redir_arg = 'pub=1';
							} else {
								$redir_arg = 'pub=0';
							}
							
							header('Location: '.$redir.$redir_arg);
						}
						catch (Exception $e)
						{
							$_ctx->form_error = $e->getMessage();
							$_ctx->form_error;
						}
					}
				}
				
				# The entry
				$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
				self::serveDocument('page.html');
			}
		}
	}
	
	public static function pagespreview($args)
	{
		$core = $GLOBALS['core'];
		$_ctx = $GLOBALS['_ctx'];
		
		if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#',$args,$m)) {
			# The specified Preview URL is malformed.
			self::p404();
		}
		else
		{
			$user_id = $m[1];
			$user_key = $m[2];
			$post_url = $m[3];
			if (!$core->auth->checkUser($user_id,null,$user_key)) {
				# The user has no access to the entry.
				self::p404();
			}
			else
			{
				$_ctx->preview = true;
				self::pages($post_url);
			}
		}
	}
}

class tplPages
{
	# Widget function
	public static function pagesWidget($w)
	{
		global $core, $_ctx;
		
		if ($w->homeonly && $core->url->type != 'default') {
			return;
		}
		
		$params['post_type'] = 'page';
		$params['limit'] = abs((integer) $w->limit);
		$params['no_content'] = true;
		
		$sort = $w->sortby;
		if (!in_array($sort,array('post_title','post_position','post_dt'))) {
			$sort = 'post_title';
		}
		
		$order = $w->orderby;
		if ($order != 'asc') {
			$order = 'desc';
		}
		$params['order'] = $sort.' '.$order;
		
		$rs = $core->blog->getPosts($params);
		
		if ($rs->isEmpty()) {
			return;
		}
		
		$res =
		'<div class="pages">'.
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';
		
		while ($rs->fetch()) {
			$class = '';
			if (($core->url->type == 'pages' && $_ctx->posts instanceof record && $_ctx->posts->post_id == $rs->post_id)) {
				$class = ' class="page-current"';
			}
			$res .= '<li'.$class.'><a href="'.$rs->getURL().'">'.
			html::escapeHTML($rs->post_title).'</a></li>';
		}
		
		$res .= '</ul></div>';
		
		return $res;
	}
}
?>