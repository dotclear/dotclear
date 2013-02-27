<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
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
@brief Dotclear post record helpers.

This class adds new methods to database post results.
You can call them on every record comming from dcBlog::getPosts and similar
methods.

@warning You should not give the first argument (usualy $rs) of every described
function.
*/
class rsExtPost
{
	/**
	Returns whether post is editable.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function isEditable($rs)
	{
		# If user is admin or contentadmin, true
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}
		
		# No user id in result ? false
		if (!$rs->exists('user_id')) {
			return false;
		}
		
		# If user is usage and owner of the entrie
		if ($rs->core->auth->check('usage',$rs->core->blog->id)
		&& $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}
		
		return false;
	}
	
	/**
	Returns whether post is deletable
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function isDeletable($rs)
	{
		# If user is admin, or contentadmin, true
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}
		
		# No user id in result ? false
		if (!$rs->exists('user_id')) {
			return false;
		}
		
		# If user has delete rights and is owner of the entrie
		if ($rs->core->auth->check('delete',$rs->core->blog->id)
		&& $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}
		
		return false;
	}
	
	/**
	Returns whether post is the first one of its day.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function firstPostOfDay($rs)
	{
		if ($rs->isStart()) {
			return true;
		}
		
		$cdate = date('Ymd',strtotime($rs->post_dt));
		$rs->movePrev();
		$ndate = date('Ymd',strtotime($rs->post_dt));
		$rs->moveNext();
		return $ndate != $cdate;
	}
	
	/**
	Returns whether post is the last one of its day.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function lastPostOfDay($rs)
	{
		if ($rs->isEnd()) {
			return true;
		}
		
		$cdate = date('Ymd',strtotime($rs->post_dt));
		$rs->moveNext();
		$ndate = date('Ymd',strtotime($rs->post_dt));
		$rs->movePrev();
		return $ndate != $cdate;
	}
	
	/**
	Returns whether post has been updated since publication.

	@return <b>boolean</b>
	*/
	public static function isRepublished($rs)
	{
		return ($rs->getTS('upddt') + dt::getTimeOffset($rs->post_tz)) > $rs->getTS();
	}
	
	/**
	Returns full post URL.
	
	@param	rs	Invisible parameter
	@return	<b>string</b>
	*/
	public static function getURL($rs)
	{
		return $rs->core->blog->url.$rs->core->getPostPublicURL(
				$rs->post_type,html::sanitizeURL($rs->post_url)
			);
	}
	
	/**
	Returns whether post has an excerpt.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function isExtended($rs)
	{
		return $rs->post_excerpt_xhtml != '';
	}
	
	/**
	Returns post timestamp.
	
	@param	rs	Invisible parameter
	@param	type	<b>string</b>		(dt|upddt|creadt) defaults to post_dt
	@return	<b>integer</b>
	*/
	public static function getTS($rs,$type='')
	{
		if ($type == 'upddt') {
			return strtotime($rs->post_upddt);
		} elseif ($type == 'creadt') {
			return strtotime($rs->post_creadt);
		} else {
			return strtotime($rs->post_dt);
		}
	}
	
	/**
	Returns post date formating according to the ISO 8601 standard.
	
	@param	rs	Invisible parameter
	@param	type	<b>string</b>		(dt|upddt|creadt) defaults to post_dt
	@return	<b>string</b>
	*/
	public static function getISO8601Date($rs,$type='')
	{
		if ($type == 'upddt' || $type == 'creadt') {
			return dt::iso8601($rs->getTS($type)+dt::getTimeOffset($rs->post_tz),$rs->post_tz);
		} else {
			return dt::iso8601($rs->getTS(),$rs->post_tz);
		}
	}
	
	/**
	Returns post date formating according to RFC 822.
	
	@param	rs	Invisible parameter
	@param	type	<b>string</b>		(dt|upddt|creadt) defaults to post_dt
	@return	<b>string</b>
	*/
	public static function getRFC822Date($rs,$type='')
	{
		if ($type == 'upddt' || $type == 'creadt') {
			return dt::rfc822($rs->getTS($type)+dt::getTimeOffset($rs->post_tz),$rs->post_tz);
		} else {
			return dt::rfc822($rs->getTS($type),$rs->post_tz);
		}
	}
	
	/**
	Returns post date with <var>$format</var> as formatting pattern. If format
	is empty, uses <var>date_format</var> blog setting.
	
	@param	rs	Invisible parameter
	@param	format	<b>string</b>		Date format pattern
	@param	type	<b>string</b>		(dt|upddt|creadt) defaults to post_dt
	@return	<b>string</b>
	*/
	public static function getDate($rs,$format,$type='')
	{
		if (!$format) {
			$format = $rs->core->blog->settings->system->date_format;
		}
		
		if ($type == 'upddt') {
			return dt::dt2str($format,$rs->post_upddt,$rs->post_tz);
		} elseif ($type == 'creadt') {
			return dt::dt2str($format,$rs->post_creadt,$rs->post_tz);
		} else {
			return dt::dt2str($format,$rs->post_dt);
		}
	}
	
	/**
	Returns post time with <var>$format</var> as formatting pattern. If format
	is empty, uses <var>time_format</var> blog setting.
	
	@param	rs	Invisible parameter
	@param	format	<b>string</b>		Time format pattern
	@param	type	<b>string</b>		(dt|upddt|creadt) defaults to post_dt
	@return	<b>string</b>
	*/
	public static function getTime($rs,$format,$type='')
	{
		if (!$format) {
			$format = $rs->core->blog->settings->system->time_format;
		}
		
		if ($type == 'upddt') {
			return dt::dt2str($format,$rs->post_upddt,$rs->post_tz);
		} elseif ($type == 'creadt') {
			return dt::dt2str($format,$rs->post_creadt,$rs->post_tz);
		} else {
			return dt::dt2str($format,$rs->post_dt);
		}
	}
	
	/**
	Returns author common name using user_id, user_name, user_firstname and
	user_displayname fields.
	
	@param	rs	Invisible parameter
	@return	<b>string</b>
	*/
	public static function getAuthorCN($rs)
	{
		return dcUtils::getUserCN($rs->user_id, $rs->user_name,
		$rs->user_firstname, $rs->user_displayname);
	}
	
	/**
	Returns author common name with a link if he specified one in its
	preferences.
	
	@param	rs	Invisible parameter
	@return	<b>string</b>
	*/
	public static function getAuthorLink($rs)
	{
		$res = '%1$s';
		$url = $rs->user_url;
		if ($url) {
			$res = '<a href="%2$s">%1$s</a>';
		}
		
		return sprintf($res,html::escapeHTML($rs->getAuthorCN()),html::escapeHTML($url));
	}
	
	/**
	Returns author e-mail address. If <var>$encoded</var> is true, "@" sign is
	replaced by "%40" and "." by "%2e".
	
	@param	rs	Invisible parameter
	@param	encoded	<b>boolean</b>		Encode address.
	@return	<b>string</b>
	*/
	public static function getAuthorEmail($rs,$encoded=true)
	{
		if ($encoded) {
			return strtr($rs->user_email,array('@'=>'%40','.'=>'%2e'));
		}
		return $rs->user_email;
	}
	
	/**
	Returns post feed unique ID.
	
	@param	rs	Invisible parameter
	@return	<b>string</b>
	*/
	public static function getFeedID($rs)
	{
		return 'urn:md5:'.md5($rs->core->blog->uid.$rs->post_id);
		
		$url = parse_url($rs->core->blog->url);
		$date_part = date('Y-m-d',strtotime($rs->post_creadt));
		
		return 'tag:'.$url['host'].','.$date_part.':'.$rs->post_id;
	}
	
	/**
	Returns post content. If <var>$absolute_urls</var> is true, appends full
	blog URL to each relative post URLs.
	
	@param	rs	Invisible parameter
	@param	absolute_urls	<b>boolean</b>		With absolute URLs
	@return	<b>string</b>
	*/
	public static function getContent($rs,$absolute_urls=false)
	{
		if ($absolute_urls) {
			return html::absoluteURLs($rs->post_content_xhtml,$rs->getURL());
		} else {
			return $rs->post_content_xhtml;
		}
	}
	
	/**
	Returns post excerpt. If <var>$absolute_urls</var> is true, appends full
	blog URL to each relative post URLs.
	
	@param	rs	Invisible parameter
	@param	absolute_urls	<b>boolean</b>		With absolute URLs
	@return	<b>string</b>
	*/
	public static function getExcerpt($rs,$absolute_urls=false)
	{
		if ($absolute_urls) {
			return html::absoluteURLs($rs->post_excerpt_xhtml,$rs->getURL());
		} else {
			return $rs->post_excerpt_xhtml;
		}
	}
	
}

/**
@ingroup DC_CORE
@brief Dotclear dates record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from dcBlog::getDates.

@warning You should not give the first argument (usualy $rs) of every described
function.
*/
class rsExtDates
{
	/**
	@param	rs	Invisible parameter
	@return	<b>integer</b>		Date timestamp
	*/
	public static function ts($rs)
	{
		return strtotime($rs->dt);
	}
	
	/**
	@param	rs	Invisible parameter
	@return	<b>string</b>		Date year
	*/
	public static function year($rs)
	{
		return date('Y',strtotime($rs->dt));
	}
	
	/**
	@param	rs	Invisible parameter
	@return	<b>string</b>		Date month
	*/
	public static function month($rs)
	{
		return date('m',strtotime($rs->dt));
	}
	
	/**
	@param	rs	Invisible parameter
	@return	<b>integer</b>		Date day
	*/
	public static function day($rs)
	{
		return date('d',strtotime($rs->dt));
	}
	
	/**
	Returns date month archive full URL.
	
	@param	rs	Invisible parameter
	@param	core		<b>dcCore</b>		dcCore instance
	@return	<b>integer</b>
	*/
	public static function url($rs,$core)
	{
		$url = date('Y/m',strtotime($rs->dt));
		
		return $core->blog->url.$core->url->getURLFor('archive',$url);
	}
	
	/**
	Returns whether date is the first of year.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function yearHeader($rs)
	{
		if ($rs->isStart()) {
			return true;
		}
		
		$y = $rs->year();
		$rs->movePrev();
		$py = $rs->year();
		$rs->moveNext();
		
		return $y != $py;
	}
	
	/**
	Returns whether date is the last of year.
	
	@param	rs	Invisible parameter
	@return	<b>boolean</b>
	*/
	public static function yearFooter($rs)
	{
		if ($rs->isEnd()) {
			return true;
		}
		
		$y = $rs->year();
		if ($rs->moveNext()) {
			$ny = $rs->year();
			$rs->movePrev();
			return $y != $ny;
		}
		return false;
		
	}
}

/**
@ingroup DC_CORE
@brief Dotclear dates record helpers.

This class adds new methods to database dates results.
You can call them on every record comming from dcAuth::checkUser and
dcCore::getUsers.

@warning You should not give the first argument (usualy $rs) of every described
function.
*/
class rsExtUser
{
	/**
	Returns a user option.
	
	@param	rs	Invisible parameter
	@param	name		<b>string</b>		Option name
	@return	<b>string</b>
	*/
	public static function option($rs,$name)
	{
		$options = self::options($rs);
		
		if (isset($options[$name])) {
			return $options[$name];
		}
		return null;
	}
	
	/**
	Returns all user options.
	
	@param	rs	Invisible parameter
	@return	<b>array</b>
	*/
	public static function options($rs)
	{
		$options = @unserialize($rs->user_options);
		if (is_array($options)) {
			return $options;
		}
		return array();
	}
}
?>