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

/**
@ingroup DC_CORE
@brief Trackbacks sender and server

Sends and receives trackbacks. Also handles trackbacks auto discovery.
*/
class dcTrackback
{
	public $core;		///< <b>dcCore</b> dcCore instance
	public $table;		///< <b>string</b> done pings table name
	
	/**
	Object constructor
	
	@param	core		<b>dcCore</b>		dcCore instance
	*/
	public function __construct($core)
	{
		$this->core =& $core;
		$this->con =& $this->core->con;
		$this->table = $this->core->prefix.'ping';
	}
	
	/// @name Send trackbacks
	//@{
	/**
	Get all pings sent for a given post.
	
	@param	post_id	<b>integer</b>		Post ID
	@return	<b>record</b>
	*/
	public function getPostPings($post_id)
	{
		$strReq = 'SELECT ping_url, ping_dt '.
				'FROM '.$this->table.' '.
				'WHERE post_id = '.(integer) $post_id;
		
		return $this->con->select($strReq);
	}
	
	/**
	Sends a ping to given <var>$url</var>.
	
	@param	url			<b>string</b>		URL to ping
	@param	post_id		<b>integer</b>		Post ID
	@param	post_title	<b>string</b>		Post title
	@param	post_excerpt	<b>string</b>		Post excerpt
	@param	post_url		<b>string</b>		Post URL
	*/
	public function ping($url,$post_id,$post_title,$post_excerpt,$post_url)
	{
		if ($this->core->blog === null) {
			return false;
		}
		
		$post_id = (integer) $post_id;
		
		# Check for previously done trackback
		$strReq = 'SELECT post_id, ping_url FROM '.$this->table.' '.
				'WHERE post_id = '.$post_id.' '.
				"AND ping_url = '".$this->con->escape($url)."' ";
		
		$rs = $this->con->select($strReq);
		
		if (!$rs->isEmpty()) {
			throw new Exception(sprintf(__('%s has still been pinged'),$url));
		}
		
		$ping_parts = explode('|',$url);
		
		# Let's walk by the trackback way
		if (count($ping_parts) < 2) {
			$data = array(
				'title' => $post_title,
				'excerpt' => $post_excerpt,
				'url' => $post_url,
				'blog_name' => trim(html::escapeHTML(html::clean($this->core->blog->name)))
				//,'__debug' => false
			);
			
			# Ping
			try
			{
				$http = self::initHttp($url,$path);
				$http->post($path,$data,'UTF-8');
				$res = $http->getContent();
			}
			catch (Exception $e)
			{
				throw new Exception(__('Unable to ping URL'));
			}
			
			$pattern =
			'|<response>.*<error>(.*)</error>(.*)'.
			'(<message>(.*)</message>(.*))?'.
			'</response>|msU';
			
			if (!preg_match($pattern,$res,$match))
			{
				throw new Exception(sprintf(__('%s is not a ping URL'),$url));
			}
			
			$ping_error = trim($match[1]);
			$ping_msg = (!empty($match[4])) ? $match[4] : '';
		}
		# Damnit ! Let's play pingback
		else {
			try {
				$xmlrpc = new xmlrpcClient($ping_parts[0]);
				$res = $xmlrpc->query('pingback.ping', $post_url, $ping_parts[1]);
				$ping_error = '0';
			}
			catch (xmlrpcException $e) {
				$ping_error = $e->getCode();
				$ping_msg = $e->getMessage();	
			}
			catch (Exception $e) {
				throw new Exception(__('Unable to ping URL'));
			}
		}
		
		if ($ping_error != '0') {
			throw new Exception(sprintf(__('%s, ping error:'),$url).' '.$ping_msg);
		} else {
			# Notify ping result in database
			$cur = $this->con->openCursor($this->table);
			$cur->post_id = $post_id;
			$cur->ping_url = $url;
			$cur->ping_dt = date('Y-m-d H:i:s');
			
			$cur->insert();
		}
	}
	//@}
	
	/// @name Receive trackbacks
	//@{
	/**
	Receives a trackback and insert it as a comment of given post.
	
	@param	post_id		<b>integer</b>		Post ID
	*/
	public function receive($post_id)
	{
		header('Content-Type: text/xml; charset=UTF-8');
		if (empty($_POST)) {
			http::head(405,'Method Not Allowed');
			echo
			'<?xml version="1.0" encoding="utf-8"?>'."\n".
			"<response>\n".
			"  <error>1</error>\n".
			"  <message>POST request needed</message>\n".
			"</response>";
			return;
		}
		
		$post_id = (integer) $post_id;
		
		$title = !empty($_POST['title']) ? $_POST['title'] : '';
		$excerpt = !empty($_POST['excerpt']) ? $_POST['excerpt'] : '';
		$url = !empty($_POST['url']) ? $_POST['url'] : '';
		$blog_name = !empty($_POST['blog_name']) ? $_POST['blog_name'] : '';
		$charset = '';
		$comment = '';
		
		$err = false;
		$msg = '';
		
		if ($this->core->blog === null)
		{
			$err = true;
			$msg = 'No blog.';
		}
		elseif ($url == '')
		{
			$err = true;
			$msg = 'URL parameter is required.';
		}
		elseif ($blog_name == '') {
			$err = true;
			$msg = 'Blog name is required.';
		}
		
		if (!$err)
		{
			$post = $this->core->blog->getPosts(array('post_id'=>$post_id,'post_type'=>''));
			
			if ($post->isEmpty())
			{
				$err = true;
				$msg = 'No such post.';
			}
			elseif (!$post->trackbacksActive())
			{
				$err = true;
				$msg = 'Trackbacks are not allowed for this post or weblog.';
			}
		}
		
		if (!$err)
		{
			$charset = self::getCharsetFromRequest();
			
			if (!$charset) {
				$charset = mb_detect_encoding($title.' '.$excerpt.' '.$blog_name,
				'UTF-8,ISO-8859-1,ISO-8859-2,ISO-8859-3,'.
				'ISO-8859-4,ISO-8859-5,ISO-8859-6,ISO-8859-7,ISO-8859-8,'.
				'ISO-8859-9,ISO-8859-10,ISO-8859-13,ISO-8859-14,ISO-8859-15');
			}
			
			if (strtolower($charset) != 'utf-8') {
				$title = iconv($charset,'UTF-8',$title);
				$excerpt = iconv($charset,'UTF-8',$excerpt);
				$blog_name = iconv($charset,'UTF-8',$blog_name);
			}
			
			$title = trim(html::clean($title));
			$title = html::decodeEntities($title);
			$title = html::escapeHTML($title);
			$title = text::cutString($title,60);
			
			$excerpt = trim(html::clean($excerpt));
			$excerpt = html::decodeEntities($excerpt);
			$excerpt = preg_replace('/\s+/ms',' ',$excerpt);
			$excerpt = text::cutString($excerpt,252); 
			$excerpt = html::escapeHTML($excerpt).'...';
			
			$blog_name = trim(html::clean($blog_name));
			$blog_name = html::decodeEntities($blog_name);
			$blog_name = html::escapeHTML($blog_name);
			$blog_name = text::cutString($blog_name,60);
			
			$url = trim(html::clean($url));
			
			if (!$blog_name) {
				$blog_name = 'Anonymous blog';
			}
			
			$comment =
			"<!-- TB -->\n".
			'<p><strong>'.($title ? $title : $blog_name)."</strong></p>\n".
			'<p>'.$excerpt.'</p>';
			
			$cur = $this->core->con->openCursor($this->core->prefix.'comment');
			$cur->comment_author = (string) $blog_name;
			$cur->comment_site = (string) $url;
			$cur->comment_content = (string) $comment;
			$cur->post_id = $post_id;
			$cur->comment_trackback = 1;
			$cur->comment_status = $this->core->blog->settings->system->trackbacks_pub ? 1 : -1;
			$cur->comment_ip = http::realIP();
			
			try
			{
				# --BEHAVIOR-- publicBeforeTrackbackCreate
				$this->core->callBehavior('publicBeforeTrackbackCreate',$cur);
				if ($cur->post_id) {
					$comment_id = $this->core->blog->addComment($cur);
					
					# --BEHAVIOR-- publicAfterTrackbackCreate
					$this->core->callBehavior('publicAfterTrackbackCreate',$cur,$comment_id);
				}
			}
			catch (Exception $e)
			{
				$err = 1;
				$msg = 'Something went wrong : '.$e->getMessage();
			}
		}
		
		
		$debug_trace =
		"  <debug>\n".
		'    <title>'.$title."</title>\n".
		'    <excerpt>'.$excerpt."</excerpt>\n".
		'    <url>'.$url."</url>\n".
		'    <blog_name>'.$blog_name."</blog_name>\n".
		'    <charset>'.$charset."</charset>\n".
		'    <comment>'.$comment."</comment>\n".
		"  </debug>\n";
		
		$resp =
		'<?xml version="1.0" encoding="utf-8"?>'."\n".
		"<response>\n".
		'  <error>'.(integer) $err."</error>\n";
		
		if ($msg) {
			$resp .= '  <message>'.$msg."</message>\n";
		}
		
		if (!empty($_POST['__debug'])) {
			$resp .= $debug_trace;
		}
		
		echo	$resp."</response>";
	}
	//@}

	/// @name Receive pingbacks
	//@{
	/**
	Receives a pingback and insert it as a comment of given post.
	
	@param	from_url		<b>string</b>		Source URL
	@param	to_url			<b>string</b>		Target URL
	*/
	public function receive_pb($from_url, $to_url)
	{
		$reg = '!^'.preg_quote($this->core->blog->url).'(.*)!';
		$type = $args = $next = '';
		
		# Are you dumb?
		if (!preg_match($reg, $to_url, $m)) {
			throw new Exception(__('Any chance you ping one of my contents? No? Really?'), 0);
		}
		
		# Does the targeted URL look like a registered post type? 
		$url_part = $m[1];
		$p_type = '';
		$post_types = $this->core->getPostTypes();
		foreach ($post_types as $k => $v) {
			$reg = '!^'.preg_quote(str_replace('%s', '', $v['public_url'])).'(.*)!';
			if (preg_match($reg, $url_part, $n)) {
				$p_type = $k;
				$post_url = $n[1];
				break;
			}
		}
		
		if (empty($p_type)) {
			throw new Exception(__('Sorry but you can not ping this type of content.'), 33);
		}

		# Time to see if we've got a winner...
		$params = array(
			'post_type' => $p_type,
			'post_url' => $post_url,
		);
		$posts = $this->core->blog->getPosts($params);
		
		# Missed! 
		if ($posts->isEmpty()) {
			throw new Exception(__('Oops. Kinda "not found" stuff. Please check the target URL twice.'), 33);
		}
		
		# Nice try. But, sorry, no.
		if (!$posts->trackbacksActive()) {
			throw new Exception(__('Sorry, dude. This entry does not accept pingback at the moment.'), 33);
		}

		# OK. We've found our champion. Time to check the remote part.
		try {
			$http = self::initHttp($from_url, $from_path);
			
			# First round : just to be sure the ping comes from an acceptable resource type.
			$http->setHeadersOnly(true);
			$http->get($from_path);
			$c_type = explode(';', $http->getHeader('content-type'));

			# Bad luck. Bye, bye...
			if (!in_array($c_type[0],array('text/html', 'application/xhtml+xml'))) {
				throw new Exception(__('Your source URL does not look like a supported content type. Sorry. Bye, bye!'), 0);
			}
			
			# Second round : let's go fetch and parse the remote content
			$http->setHeadersOnly(false);
			$http->get($from_path);
			$remote_content = $http->getContent();

			$charset = mb_detect_encoding($remote_content,
				'UTF-8,ISO-8859-1,ISO-8859-2,ISO-8859-3,'.
				'ISO-8859-4,ISO-8859-5,ISO-8859-6,ISO-8859-7,ISO-8859-8,'.
				'ISO-8859-9,ISO-8859-10,ISO-8859-13,ISO-8859-14,ISO-8859-15');

			if (strtolower($charset) != 'utf-8') {
				$remote_content = iconv($charset,'UTF-8',$remote_content);
			}
			
			# We want a title...
			if (!preg_match('!<title>([^<].*?)</title>!mis', $remote_content, $m)) {
				throw new Exception(__('Where\'s your title?'), 0);
			}
			$title = trim(html::clean($m[1]));
			$title = html::decodeEntities($title);
			$title = html::escapeHTML($title);
			$title = text::cutString($title,60);
			
			preg_match('!<body[^>]*?>(.*)?</body>!msi', $remote_content, $m);
			$source = $m[1];
			$source = preg_replace('![\r\n\s]+!ms',' ',$source);
			$source = preg_replace( "/<\/*(h\d|p|th|td|li|dt|dd|pre|caption|input|textarea|button)[^>]*>/", "\n\n", $source );
			$source = strip_tags($source, '<a>');
			$source = explode("\n\n",$source);
			
			$excerpt = '';
			foreach ($source as $line) {
				if (strpos($line, $to_url) !== false) {
					if (preg_match("!<a[^>]+?".$to_url."[^>]*>([^>]+?)</a>!", $line, $m)) {
						$excerpt = strip_tags($line);
						break;
					}
				}
			}
			if ($excerpt) {
				$excerpt = '(&#8230;) '.text::cutString(html::escapeHTML($excerpt),255).' (&#8230;)';
			}
			else {
				$excerpt = '(??)';
			}

			$comment =
			"<!-- TB -->\n".
			'<p><strong>'.$title."</strong></p>\n".
			'<p>'.$excerpt.'</p>';
			
			$cur = $this->core->con->openCursor($this->core->prefix.'comment');
			$cur->comment_author = 'Anonymous blog';
			$cur->comment_site = (string) $from_url;
			$cur->comment_content = (string) $comment;
			$cur->post_id = $posts->post_id;
			$cur->comment_trackback = 1;
			$cur->comment_status = $this->core->blog->settings->system->trackbacks_pub ? 1 : -1;
			$cur->comment_ip = http::realIP();
			
			# --BEHAVIOR-- publicBeforeTrackbackCreate
			$this->core->callBehavior('publicBeforeTrackbackCreate',$cur);
			if ($cur->post_id) {
				$comment_id = $this->core->blog->addComment($cur);
				
				# --BEHAVIOR-- publicAfterTrackbackCreate
				$this->core->callBehavior('publicAfterTrackbackCreate',$cur,$comment_id);
			}
		}
		catch (Exception $e) {
			throw new Exception(__('Sorry, an internal problem has occured.'), 0);
		}
		
		return __('Thanks, mate. It was a pleasure.');
	}
	//@}
	
	private static function initHttp($url,&$path)
	{
		$client = netHttp::initClient($url,$path);
		$client->setTimeout(5);
		$client->setUserAgent('Dotclear - http://www.dotclear.org/');
		$client->useGzip(false);
		$client->setPersistReferers(false);
		
		return $client;
	}
	
	private static function getCharsetFromRequest()
	{
		if (isset($_SERVER['CONTENT_TYPE']))
		{
			if (preg_match('|charset=([a-zA-Z0-9-]+)|',$_SERVER['CONTENT_TYPE'],$m)) {
				return $m[1];
			}
		}
		
		return null;
	}
	
	/// @name Trackbacks auto discovery
	//@{
	/**
	Returns an array containing all discovered trackbacks URLs in
	<var>$text</var>.
	
	@param	text		<b>string</b>		Input text
	@return	<b>array</b>
	*/
	public function discover($text)
	{
		$res = array();
		
		foreach ($this->getTextLinks($text) as $link)
		{
			if (($url = $this->getPingURL($link)) !== null) {
				$res[] = $url;
			}
		}
		
		return $res;
	}
	//@}
	
	private function getTextLinks($text)
	{
		$res = array();
		
		# href attribute on "a" tags
		if (preg_match_all('/<a ([^>]+)>/ms', $text, $match, PREG_SET_ORDER))
		{
			for ($i = 0; $i<count($match); $i++)
			{
				if (preg_match('/href="((https?:\/)?\/[^"]+)"/ms', $match[$i][1], $matches)) {
					$res[$matches[1]] = 1;
				}
			}
		}
		unset($match);
		
		# cite attributes on "blockquote" and "q" tags
		if (preg_match_all('/<(blockquote|q) ([^>]+)>/ms', $text, $match, PREG_SET_ORDER))
		{
			for ($i = 0; $i<count($match); $i++)
			{
				if (preg_match('/cite="((https?:\/)?\/[^"]+)"/ms', $match[$i][2], $matches)) {
					$res[$matches[1]] = 1;
				}
			}
		}
		
		return array_keys($res);
	}
	
	private function getPingURL($url)
	{
		if (strpos($url,'/') === 0) {
			$url = http::getHost().$url;
		}
		
		try
		{
			$http = self::initHttp($url,$path);
			$http->get($path);
			$page_content = $http->getContent();
			$pb_url = $http->getHeader('x-pingback');
		}
		catch (Exception $e)
		{
			return false;
		}
		
		# If we've got a X-Pingback header and it's a valid URL, it will be enough
		if ($pb_url && filter_var($pb_url,FILTER_VALIDATE_URL) && preg_match('!^https?:!',$pb_url)) {
			return $pb_url.'|'.$url;
		}
		
		# No X-Pingback header. A link rel=pingback, maybe ?
		$pattern_pingback = '!<link rel="pingback" href="(.*?)"( /)?>!msi';
		
		if (preg_match($pattern_pingback,$page_content,$m)) {
			$pb_url = $m[1];
			if (filter_var($pb_url,FILTER_VALIDATE_URL) && preg_match('!^https?:!',$pb_url)) {
				return $pb_url.'|'.$url;
			}
		}

		# No pingback ? OK, let's check for a trackback data chunk...
		$pattern_rdf =
		'/<rdf:RDF.*?>.*?'.
		'<rdf:Description\s+(.*?)\/>'.
		'.*?<\/rdf:RDF>'.
		'/msi';
		
		preg_match_all($pattern_rdf,$page_content,$rdf_all,PREG_SET_ORDER);
		
		$url_path = parse_url($url, PHP_URL_PATH);
		$sanitized_url = str_replace($url_path, html::sanitizeURL($url_path), $url);
		
		for ($i=0; $i<count($rdf_all); $i++)
		{
			$rdf = $rdf_all[$i][1];
			if (preg_match('/dc:identifier="'.preg_quote($url,'/').'"/msi',$rdf) ||
				preg_match('/dc:identifier="'.preg_quote($sanitized_url,'/').'"/msi',$rdf)) {
				if (preg_match('/trackback:ping="(.*?)"/msi',$rdf,$tb_link)) {
					return $tb_link[1];
				}
			}
		}
		
		return null;
	}
}
?>