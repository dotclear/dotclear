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

class dcUrlHandlers extends urlHandler
{
	public $args;
	
	public function getURLFor($type,$value='') {
		$core =& $GLOBALS['core'];
		$url = $core->callBehavior("publicGetURLFor",$type,$value);
		if (!$url) {
			$url = $this->getBase($type);
			if ($value) {
				if ($url) {
					$url .= '/';
				}
				$url .= $value;
			}
		}
		return $url;
	}
	
	public function register($type,$url,$representation,$handler)
	{
		$core =& $GLOBALS['core'];
		$t = new ArrayObject(array($type,$url,$representation,$handler));
		$core->callBehavior("publicRegisterURL",$t);
		parent::register($t[0],$t[1],$t[2],$t[3]);
	}
	
	public static function p404()
	{
		throw new Exception ("Page not found",404);
	}
	
	public static function default404($args,$type,$e)
	{
		if ($e->getCode() != 404) {
			throw $e;
		}
		$_ctx =& $GLOBALS['_ctx'];
		$core = $GLOBALS['core'];
		
		header('Content-Type: text/html; charset=UTF-8');
		http::head(404,'Not Found');
		$core->url->type = '404';
		$_ctx->current_tpl = '404.html';
		$_ctx->content_type = 'text/html';
		
		echo $core->tpl->getData($_ctx->current_tpl);
		
		# --BEHAVIOR-- publicAfterDocument
		$core->callBehavior('publicAfterDocument',$core);
		exit;
	}
	
	protected static function getPageNumber(&$args)
	{
		if (preg_match('#(^|/)page/([0-9]+)$#',$args,$m)) {
			$n = (integer) $m[2];
			if ($n > 0) {
				$args = preg_replace('#(^|/)page/([0-9]+)$#','',$args);
				return $n;
			}
		}
		
		return false;
	}
	
	protected static function serveDocument($tpl,$content_type='text/html',$http_cache=true,$http_etag=true)
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		if ($_ctx->nb_entry_per_page === null) {
			$_ctx->nb_entry_per_page = $core->blog->settings->system->nb_post_per_page;
		}
		
		$tpl_file = $core->tpl->getFilePath($tpl);
		
		if (!$tpl_file) {
			throw new Exception('Unable to find template ');
		}
		
		$result = new ArrayObject;
		
		$_ctx->current_tpl = $tpl;
		$_ctx->content_type = $content_type;
		$_ctx->http_cache = $http_cache;
		$_ctx->http_etag = $http_etag;
		$core->callBehavior('urlHandlerBeforeGetData',$_ctx);
		
		if ($_ctx->http_cache) {
			$GLOBALS['mod_files'][] = $tpl_file;
			http::cache($GLOBALS['mod_files'],$GLOBALS['mod_ts']);
		}

		header('Content-Type: '.$_ctx->content_type.'; charset=UTF-8');
		$result['content'] = $core->tpl->getData($_ctx->current_tpl);
		$result['content_type'] = $_ctx->content_type;
		$result['tpl'] = $_ctx->current_tpl;
		$result['blogupddt'] = $core->blog->upddt;
		
		# --BEHAVIOR-- urlHandlerServeDocument
		$core->callBehavior('urlHandlerServeDocument',$result);
		
		if ($_ctx->http_cache && $_ctx->http_etag) {
			http::etag($result['content'],http::getSelfURI());
		}
		echo $result['content'];
	}
	
	public function getDocument()
	{
		$core =& $GLOBALS['core'];
		
		$type = $args = '';
		
		if ($this->mode == 'path_info')
		{
			$part = substr($_SERVER['PATH_INFO'],1);
		}
		else
		{
			$part = '';
			
			$qs = $this->parseQueryString();
			
			# Recreates some _GET and _REQUEST pairs
			if (!empty($qs))
			{
				foreach ($_GET as $k => $v) {
					if (isset($_REQUEST[$k])) {
						unset($_REQUEST[$k]);
					}
				}
				$_GET = $qs;
				$_REQUEST = array_merge($qs,$_REQUEST);
				
				list($k,$v) = each($qs);
				if ($v === null) {
					$part = $k;
					unset($_GET[$k]);
					unset($_REQUEST[$k]);
				}
			}
		}
		
		$_SERVER['URL_REQUEST_PART'] = $part;
		
		$this->getArgs($part,$type,$this->args);
		
		# --BEHAVIOR-- urlHandlerGetArgsDocument
		$core->callBehavior('urlHandlerGetArgsDocument',$this);
		
		if (!$type)
		{
			$this->type = 'default';
			$this->callDefaultHandler($this->args);
		}
		else
		{
			$this->type = $type;
			$this->callHandler($type,$this->args);
		}
	}
	
	public static function home($args)
	{
		$n = self::getPageNumber($args);
		
		if ($args && !$n)
		{
			# "Then specified URL went unrecognized by all URL handlers and 
			# defaults to the home page, but is not a page number.
			self::p404();
		}
		else
		{
			$core =& $GLOBALS['core'];
			
			if ($n) {
				$GLOBALS['_page_number'] = $n;
				$core->url->type = $n > 1 ? 'default-page' : 'default';
			}
			
			if (empty($_GET['q'])) {
				self::serveDocument('home.html');
				$core->blog->publishScheduledEntries();
			} else {
				self::search();
			}
		}
	}
	
	public static function search()
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		$core->url->type='search';
		
		$GLOBALS['_search'] = !empty($_GET['q']) ? rawurldecode($_GET['q']) : '';
		if ($GLOBALS['_search']) {
			$params = new ArrayObject(array('search' => $GLOBALS['_search']));
			$core->callBehavior('publicBeforeSearchCount',$params);
			$GLOBALS['_search_count'] = $core->blog->getPosts($params,true)->f(0);
		}
		
		self::serveDocument('search.html');
	}
	
	public static function lang($args)
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		$n = self::getPageNumber($args);
		$params = new ArrayObject(array(
			'lang' => $args));
		
		$core->callBehavior('publicLangBeforeGetLangs',$params,$args);
		
		$_ctx->langs = $core->blog->getLangs($params);
		
		if ($_ctx->langs->isEmpty()) {
			# The specified language does not exist.
			self::p404();
		}
		else
		{
			if ($n) {
				$GLOBALS['_page_number'] = $n;
			}
			$_ctx->cur_lang = $args;
			self::home(null);
		}
	}
	
	public static function category($args)
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		$n = self::getPageNumber($args);
		
		if ($args == '' && !$n) {
			# No category was specified.
			self::p404();
		}
		else
		{
			$params = new ArrayObject(array(
				'cat_url' => $args,
				'post_type' => 'post'));
			
			$core->callBehavior('publicCategoryBeforeGetCategories',$params,$args);
			
			$_ctx->categories = $core->blog->getCategories($params);
			
			if ($_ctx->categories->isEmpty()) {
				# The specified category does no exist.
				self::p404();
			}
			else
			{
				if ($n) {
					$GLOBALS['_page_number'] = $n;
				}
				self::serveDocument('category.html');
			}
		}
	}
	
	public static function archive($args)
	{
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		$year = $month = $cat_url = null;
		# Nothing or year and month
		if ($args == '')
		{
			self::serveDocument('archive.html');
		}
		elseif (preg_match('|^/([0-9]{4})/([0-9]{2})$|',$args,$m))
		{
			$params = new ArrayObject(array(
				'year' => $m[1],
				'month' => $m[2],
				'type' => 'month'));
			
			$core->callBehavior('publicArchiveBeforeGetDates',$params,$args);
			
			$_ctx->archives = $core->blog->getDates($params);
			
			if ($_ctx->archives->isEmpty()) {
				# There is no entries for the specified period.
				self::p404();
			}
			else
			{
				self::serveDocument('archive_month.html');
			}
		}
		else {
			# The specified URL is not a date.
			self::p404();
		}
	}
	
	public static function post($args)
	{
		if ($args == '') {
			# No entry was specified.
			self::p404();
		}
		else
		{
			$_ctx =& $GLOBALS['_ctx'];
			$core =& $GLOBALS['core'];
			
			$core->blog->withoutPassword(false);
			
			$params = new ArrayObject(array(
				'post_url' => $args));
			
			$core->callBehavior('publicPostBeforeGetPosts',$params,$args);

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
				# The specified entry does not exist.
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
				self::serveDocument('post.html');
			}
		}
	}
	
	public static function preview($args)
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
				self::post($post_url);
			}
		}
	}
	
	public static function feed($args)
	{
		$type = null;
		$comments = false;
		$cat_url = false;
		$post_id = null;
		$subtitle = '';
		
		$mime = 'application/xml';
		
		$_ctx =& $GLOBALS['_ctx'];
		$core =& $GLOBALS['core'];
		
		if (preg_match('!^([a-z]{2}(-[a-z]{2})?)/(.*)$!',$args,$m)) {
			$params = new ArrayObject(array('lang' => $m[1]));
			
			$args = $m[3];
			
			$core->callBehavior('publicFeedBeforeGetLangs',$params,$args);
			
			$_ctx->langs = $core->blog->getLangs($params);
			
			if ($_ctx->langs->isEmpty()) {
				# The specified language does not exist.
				self::p404();
				return;
			} else {
				$_ctx->cur_lang = $m[1];
			}
		}
		
		if (preg_match('#^rss2/xslt$#',$args,$m))
		{
			# RSS XSLT stylesheet
			self::serveDocument('rss2.xsl','text/xml');
			return;
		}
		elseif (preg_match('#^(atom|rss2)/comments/([0-9]+)$#',$args,$m))
		{
			# Post comments feed
			$type = $m[1];
			$comments = true;
			$post_id = (integer) $m[2];
		}
		elseif (preg_match('#^(?:category/(.+)/)?(atom|rss2)(/comments)?$#',$args,$m))
		{
			# All posts or comments feed
			$type = $m[2];
			$comments = !empty($m[3]);
			if (!empty($m[1])) {
				$cat_url = $m[1];
			}
		}
		else
		{
			# The specified Feed URL is malformed.
			self::p404();
			return;
		}
		
		if ($cat_url)
		{
			$params = new ArrayObject(array(
				'cat_url' => $cat_url,
				'post_type' => 'post'));
			
			$core->callBehavior('publicFeedBeforeGetCategories',$params,$args);
			
			$_ctx->categories = $core->blog->getCategories($params);
			
			if ($_ctx->categories->isEmpty()) {
				# The specified category does no exist.
				self::p404();
				return;
			}
			
			$subtitle = ' - '.$_ctx->categories->cat_title;
		}
		elseif ($post_id)
		{
			$params = new ArrayObject(array(
				'post_id' => $post_id,
				'post_type' => ''));
				
			$core->callBehavior('publicFeedBeforeGetPosts',$params,$args);
			
			$_ctx->posts = $core->blog->getPosts($params);
			
			if ($_ctx->posts->isEmpty()) {
				# The specified post does not exist.
				self::p404();
				return;
			}
			
			$subtitle = ' - '.$_ctx->posts->post_title;
		}
		
		$tpl = $type;
		if ($comments) {
			$tpl .= '-comments';
			$_ctx->nb_comment_per_page = $core->blog->settings->system->nb_comment_per_feed;
		} else {
			$_ctx->nb_entry_per_page = $core->blog->settings->system->nb_post_per_feed;
			$_ctx->short_feed_items = $core->blog->settings->system->short_feed_items;
		}
		$tpl .= '.xml';
		
		if ($type == 'atom') {
			$mime = 'application/atom+xml';
		}
		
		$_ctx->feed_subtitle = $subtitle;
		
		header('X-Robots-Tag: '.context::robotsPolicy($core->blog->settings->system->robots_policy,''));
		self::serveDocument($tpl,$mime);
		if (!$comments && !$cat_url) {
			$core->blog->publishScheduledEntries();
		}
	}
	
	public static function trackback($args)
	{
		if (!preg_match('/^[0-9]+$/',$args)) {
			# The specified trackback URL is not an number
			self::p404();
		} else {
			$tb = new dcTrackback($GLOBALS['core']);
			$tb->receive($args);
		}
	}
	
	public static function rsd($args)
	{
		$core =& $GLOBALS['core'];
		http::cache($GLOBALS['mod_files'],$GLOBALS['mod_ts']);
		
		header('Content-Type: text/xml; charset=UTF-8');
		echo
		'<?xml version="1.0" encoding="UTF-8"?>'."\n".
		'<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">'."\n".
		"<service>\n".
		"  <engineName>Dotclear</engineName>\n".
		"  <engineLink>http://www.dotclear.org/</engineLink>\n".
		'  <homePageLink>'.html::escapeHTML($core->blog->url)."</homePageLink>\n";
		
		if ($core->blog->settings->system->enable_xmlrpc)
		{
			$u = sprintf(DC_XMLRPC_URL,$core->blog->url,$core->blog->id);
			
			echo
			"  <apis>\n".
			'    <api name="WordPress" blogID="1" preferred="true" apiLink="'.$u.'"/>'."\n".
			'    <api name="Movable Type" blogID="1" preferred="false" apiLink="'.$u.'"/>'."\n".
			'    <api name="MetaWeblog" blogID="1" preferred="false" apiLink="'.$u.'"/>'."\n".
			'    <api name="Blogger" blogID="1" preferred="false" apiLink="'.$u.'"/>'."\n".
			"  </apis>\n";
		}
		
		echo
		"</service>\n".
		"</rsd>\n";
	}
	
	public static function xmlrpc($args)
	{
		$core =& $GLOBALS['core'];
		$blog_id = preg_replace('#^([^/]*).*#','$1',$args);
		$server = new dcXmlRpc($core,$blog_id);
		$server->serve();
	}
}
?>