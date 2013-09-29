<?php

class dcRepository
{
	public $core;
	public $modules;

	protected $xml_url;
	protected $data;

	public function __construct(dcModules $modules, $xml_url)
	{
		$this->core = $modules->core;
		$this->modules = $modules;
		$this->xml_url = $xml_url;
	}

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

	public function get($update=false)
	{
		return $this->data[$update ? 'update' : 'new'];
	}

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
			if (!($nb = preg_match_all('/('.implode('|', $patterns).')/', implode(' ', $subjects)))) {
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

	public function process($url, $dest)
	{
		$this->download($url, $dest);
		return $this->install($dest);
	}

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

	public function install($path)
	{
		return dcModules::installPackage($path, $this->modules);
	}

	public static function agent()
	{
		return sprintf('Dotclear/%s)', DC_VERSION);
	}

	public static function sanitize(&$str, $_)
	{
		$str = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $str));
	}

	private static function compare($v1,$v2,$op)
	{
		$v1 = preg_replace('!-r(\d+)$!','-p$1',$v1);
		$v2 = preg_replace('!-r(\d+)$!','-p$1',$v2);
		return version_compare($v1,$v2,$op);
	}

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
