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
				if (preg_match('/href="(http:\/\/[^"]+)"/ms', $match[$i][1], $matches)) {
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
				if (preg_match('/cite="(http:\/\/[^"]+)"/ms', $match[$i][2], $matches)) {
					$res[$matches[1]] = 1;
				}
			}
		}
		
		return array_keys($res);
	}
	
	private function getPingURL($url)
	{
		try
		{
			$http = self::initHttp($url,$path);
			$http->get($path);
			$page_content = $http->getContent();
		}
		catch (Exception $e)
		{
			return false;
		}
		
		$pattern_rdf =
		'/<rdf:RDF.*?>.*?'.
		'<rdf:Description\s+(.*?)\/>'.
		'.*?<\/rdf:RDF>'.
		'/msi';
		
		preg_match_all($pattern_rdf,$page_content,$rdf_all,PREG_SET_ORDER);
		
		for ($i=0; $i<count($rdf_all); $i++)
		{
			$rdf = $rdf_all[$i][1];
			
			if (preg_match('/dc:identifier="'.preg_quote($url,'/').'"/msi',$rdf)) {
				if (preg_match('/trackback:ping="(.*?)"/msi',$rdf,$tb_link)) {
					return $tb_link[1];
				}
			}
		}
		
		return null;
	}
}
?>