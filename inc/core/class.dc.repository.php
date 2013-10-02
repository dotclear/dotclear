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
@brief Repository modules XML feed reader

Provides an object to parse XML feed of modules from repository.
*/
class dcRepository
{
	/** @var	object	dcCore instance */
	public $core;
	/** @var	object	dcModules instance */
	public $modules;

	/** @var	string	User agent used to query repository */
	protected $user_agent = 'DotClear.org RepoBrowser/0.1';
	/** @var	string	XML feed URL */
	protected $xml_url;
	/** @var	array	Array of new/update modules from repository */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param	object	$modules		dcModules instance
	 * @param	string	$xml_url		XML feed URL
	 */
	public function __construct(dcModules $modules, $xml_url)
	{
		$this->core = $modules->core;
		$this->modules = $modules;
		$this->xml_url = $xml_url;
		$this->user_agent = sprintf('Dotclear/%s)', DC_VERSION);

		$this->check();
	}

	/**
	 * Check repository.
	 *
	 * @param	boolean	$force		Force query repository
	 * @return	boolean	True if get feed or cache
	 */
	public function check($force=false)
	{
		if (!$this->xml_url) {
			return false;
		}
		if (($parser = dcRepositoryReader::quickParse($this->xml_url, DC_TPL_CACHE, $force)) === false) {
			return false;
		}

		$raw_datas = $parser->getModules();

		uasort($raw_datas, array('self','sort'));

		$skipped = array_keys($this->modules->getDisabledModules());
		foreach ($skipped as $p_id) {
			if (isset($raw_datas[$p_id])) {
				unset($raw_datas[$p_id]);
			}
		}

		$updates = array();
		$current = $this->modules->getModules();
		foreach ($current as $p_id => $p_infos) {
			if (isset($raw_datas[$p_id])) {
				if (self::compare($raw_datas[$p_id]['version'],$p_infos['version'],'>')) {
					$updates[$p_id] = $raw_datas[$p_id];
					$updates[$p_id]['root'] = $p_infos['root'];
					$updates[$p_id]['root_writable'] = $p_infos['root_writable'];
					$updates[$p_id]['current_version'] = $p_infos['version'];
				}
				unset($raw_datas[$p_id]);
			}
		}

		$this->data = array(
			'new'	=> $raw_datas,
			'update'	=> $updates
		);

		return true;
	}

	/**
	 * Get a list of modules.
	 *
	 * @param	boolean	$update	True to get update modules, false for new ones
	 * @return	array	List of update/new modules
	 */
	public function get($update=false)
	{
		return $this->data[$update ? 'update' : 'new'];
	}

	/**
	 * Search a module.
	 *
	 * Search string is cleaned, split and compare to split:
	 * - module id and clean id,
	 * - module name, clean name,
	 * - module desccription.
	 *
	 * Every time a part of query is find on module,
	 * result accuracy grow. Result is sorted by acuracy.
	 *
	 * @param	string	$pattern	String to search
	 * @return	array	Match modules
	 */
	public function search($pattern)
	{
		$result = array();

		# Split query into small clean words
		$patterns = explode(' ', $pattern);
		array_walk($patterns, array('dcRepository','sanitize'));

		# For each modules
		foreach ($this->data['new'] as $id => $module) {

			# Split modules infos into small clean word
			$subjects = explode(' ', $id.' '.$module['name'].' '.$module['desc']);
			array_walk($subjects, array('dcRepository','sanitize'));

			# Check contents
			if (!($nb = preg_match_all('/('.implode('|', $patterns).')/', implode(' ', $subjects), $_))) {
				continue;
			}

			# Add module to result
			if (!isset($sorter[$id])) {
				$sorter[$id] = 0;
				$result[$id] = $module;
			}

			# Increment matches count
			$sorter[$id] += $nb;
			$result[$id]['accuracy'] = $sorter[$id];
		}
		# Sort response by matches count
		if (!empty($result)) {
			array_multisort($sorter, SORT_DESC, $result);
		}
		return $result;
	}

	/**
	 * Quick download and install module.
	 *
	 * @param	string	$url	Module package URL
	 * @param	string	$dest	Path to install module
	 * @return	integer		1 = installed, 2 = update
	 */
	public function process($url, $dest)
	{
		$this->download($url, $dest);
		return $this->install($dest);
	}

	/**
	 * Download a module.
	 *
	 * @param	string	$url	Module package URL
	 * @param	string	$dest	Path to put module package
	 */
	public function download($url, $dest)
	{
		try {
			$client = netHttp::initClient($url, $path);
			$client->setUserAgent(self::agent());
			$client->useGzip(false);
			$client->setPersistReferers(false);
			$client->setOutput($dest);
			$client->get($path);
			unset($client);
		}
		catch (Exception $e) {
			unset($client);
			throw new Exception(__('An error occurred while downloading the file.'));
		}
	}

	/**
	 * Install a previously downloaded module.
	 *
	 * @param	string	$path	Module package URL
	 * @param	string	$path	Path to module package
	 * @return	integer		1 = installed, 2 = update
	 */
	public function install($path)
	{
		return dcModules::installPackage($path, $this->modules);
	}

	/**
	 * User Agent String.
	 *
	 * @param	string	$str		User agent string
	 */
	public function agent($str)
	{
		$this->user_agent = $str;
	}

	/**
	 * Sanitize string.
	 *
	 * @param	string	$str		String to sanitize
	 * @param	null	$_		Unused	param
	 */
	public static function sanitize(&$str, $_)
	{
		$str = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $str));
	}

	/**
	 * Compare version.
	 *
	 * @param	string	$v1		Version
	 * @param	string	$v2		Version
	 * @param	string	$op		Comparison operator
	 * @return	boolean	True is comparison is true, dude!
	 */
	private static function compare($v1, $v2, $op)
	{
		return version_compare(
			preg_replace('!-r(\d+)$!', '-p$1', $v1), 
			preg_replace('!-r(\d+)$!', '-p$1', $v2), 
			$op
		);
	}

	/** 
	 * Sort modules list.
	 *
	 * @param	array	$a		A module
	 * @param	array	$b		A module
	 * @return	integer
	 */
	private static function sort($a,$b)
	{
		$c = strtolower($a['id']); 
		$d = strtolower($b['id']); 
		if ($c == $d) { 
			return 0; 
		} 
		return ($c < $d) ? -1 : 1; 
	}
}
