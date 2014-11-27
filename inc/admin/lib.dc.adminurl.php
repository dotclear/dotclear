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
@nosubgrouping
@brief URL Handler for admin urls

*/
class dcAdminURL
{
	/** @var dcCore dcCore instance */
	protected $core;
	protected $urls;

	/**
	Inits dcAdminURL object

	@param	core		<b>dcCore</b>		Dotclear core reference
	*/
	public function __construct($core)
	{
		$this->core = $core;
		$this->urls = new ArrayObject();
	}

	/**
	 * Registers a new url
	 * @param  string $name   the url name
	 * @param  string $url    url value
	 * @param  array  $params query string params (optional)
	 */
	public function register($name,$url,$params=array())
	{
		$this->urls[$name] = array('url' => $url, 'qs' => $params);
	}

	/**
	 * Registers a new url as a copy of an existing one
	 * @param  string $name   url name
	 * @param  streing $orig   url to copy information from
	 * @param  array  $params extra parameters to add
	 * @param  string $newurl new url if different from the original
	 */
	public function registercopy($name,$orig,$params=array(),$newurl='')
	{
		if (!isset($this->urls[$orig])) {
			throw new exception ('Unknown URL handler for '.$orig);
		}
		$url = $this->urls[$orig];
		$url['qs'] = array_merge($url['qs'],$params);
		if ($newurl != '') {
			$url['url'] = $newurl;
		}
		$this->urls[$name] = $url;
	}

	/**
	 * retrieves a URL given its name, and optional parameters
	 *
	 * @param  string $name      URL Name
	 * @param  array  $params    query string parameters, given as an associative array
	 * @param  string $separator separator to use between QS parameters
	 * @return string            the forged url
	 */
	public function get($name,$params=array(),$separator='&amp;')
	{
		if (!isset($this->urls[$name])) {
			throw new exception ('Unknown URL handler for '.$name);
		}
		$url = $this->urls[$name];
		$p = array_merge($url['qs'],$params);
		$u = $url['url'];
		if (!empty($p)) {
			$u .= '?'.http_build_query($p,'',$separator);
		}
		return $u;
	}

	/**
	 * retrieves a URL (decoded — useful for echoing) given its name, and optional parameters
	 *
	 * @param  string $name      URL Name
	 * @param  array  $params    query string parameters, given as an associative array
	 * @param  string $separator separator to use between QS parameters
	 * @return string            the forged decoded url
	 */
	public function decode($name,$params=array(),$separator='&')
	{
		return urldecode($this->get($name,$params,$separator));
	}

	/**
	 * Returns $urls property content.
	 *
	 * @return  ArrayObject
	 */
	public function dumpUrls()
	{
		return $this->urls;
	}
}
