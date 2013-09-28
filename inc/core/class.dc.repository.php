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
		$this->modules  = $modules
	}

	protected function check($force=false)
	{
		if (!$this->xml_url) {
			return false;
		}
		if (($parser = dcModulesReader::quickParse($this->xml_url, DC_TPL_CACHE, $force)) === false) {
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

	public function search($search)
	{
		$result = array();

		foreach ($this->data['new'] as $module)
		{
			if ( preg_match('/'.$search.'/i',$module['id']) ||
				preg_match('/'.$search.'/i',$module['name']) ||
				preg_match('/'.$search.'/i',$module['desc']))
			{
				$result[] = $module;
			}
		}
		return $result;
	}

	public function process($url, $dest)
	{
		try {
			$client = netHttp::initClient($url, $path);
			$client->setUserAgent(self::agent());
			$client->useGzip(false);
			$client->setPersistReferers(false);
			$client->setOutput($dest);
			$client->get($path);
		}
		catch (Exception $e) {
			throw new Exception(__('An error occurred while downloading the file.'));
		}

		unset($client);
		$ret_code = dcModules::installPackage($dest, $this->modules);

		return $ret_code;
	}

	public static function agent()
	{
		return sprintf('Dotclear/%s)', DC_VERSION);
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
