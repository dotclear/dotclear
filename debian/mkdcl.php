#!/usr/bin/env php
<?php

class debianChangelog
{
	public $f = 'debian/changelog';
	
	public function __construct()
	{
		if (!is_file($this->f)) {
			throw new Exception('No changelog file found');
		}
	}
	
	private function readLastRevision()
	{
		$f = file($this->f);
		$res = array();
		$done = false;
		
		foreach ($f as $v)
		{
			$v = rtrim($v,"\n");
			
			# First line of a change
			if (strpos($v,' ') !== 0 && trim($v) != '')
			{
				if ($done) {
					break;
				}
				
				$done = true;
				$res = $this->getPackageInfo($v,$res[$i]);
			}
			# Maintainer information
			elseif (strpos($v,' --') === 0)
			{
				$res['maintainer'] = $this->getMaintainerInfo($v);
			}
			# Changelog
			elseif (strpos($v,'  ') === 0)
			{
				$res['changelog'] .= $v."\n";
			}
		}
		
		return $res;
	}
	
	public function writeChangelog()
	{
		$ch = $this->readLastRevision();
		
		# Get debian revision
		$rev = 1;
		if (preg_match('/^(.*)-(\d+)$/',$ch['version'],$m)) {
			$ch['version'] = $m[1];
			$rev = $m[2];
		}
		$rev++;
		
		# Get SVN revision
		$svnrev = isset($ch['keywords']['svnrev']) ? (integer) $ch['keywords']['svnrev'] : 1;
		
		# Get current SVN revision
		$currev = svnInfo::getCurrentRevision();
		if ($currev <= $svnrev) {
			return;
		}
		
		$changelog = '';
		$changes = svnInfo::getChangeLog($svnrev+1,$currev);
		foreach ($changes as $k => $v)
		{
			$changelog .=
			'  * SVN Revision '.$k.' - '.$v['author'].
			', on '.date('r',strtotime($v['date']))."\n".
			'    '.trim(preg_replace('/\n/ms',"\n    ",$v['msg']))."\n\n";
			
		} 
		
		$res =
		$ch['package'].' ('.$ch['version'].'-'.$rev.') '.$ch['dist'].'; urgency='.$ch['keywords']['urgency'].
		' ; svnrev='.$currev.
		"\n\n".
		rtrim($changelog)."\n\n".
		' -- '.$ch['maintainer']['name'].' <'.$ch['maintainer']['email'].'>  '.date('r')."\n".
		"\n";
		
		$old_changelog = file_get_contents($this->f);
		$fp = fopen($this->f,'wb');
		fwrite($fp,$res.$old_changelog);
		fclose($fp);
	}
	
	private function getPackageInfo($l)
	{
		$res = array(
			'package' => '',
			'version' => '',
			'dist' => '',
			'keywords' => '',
			'changelog' => '',
			'maintainer' => array()
		);
		
		$l = explode(';',$l);
		
		# Info
		$info = array_shift($l);
		$res['package'] = strtok($info,' ');
		$res['version'] = strtok('()');
		$res['dist'] = trim(strtok(';'));
		
		# Keywords
		foreach ($l as $v) {
			$v = explode('=',$v);
			if (count($v) == 2) {
				$res['keywords'][trim($v[0])] = trim($v[1]);
			}
		}
		
		return $res;
	}
	
	private function getMaintainerInfo($l)
	{
		$res = array(
			'name' => '',
			'email' => '',
			'date' => ''
		);
		
		if (preg_match('/^ -- (.+?) <(.+?)>  (.+?)$/',$l,$m)) {
			$res['name'] = $m[1];
			$res['email'] = $m[2];
			$res['date'] = $m[3];
		}
		
		return $res;
	}
}

class svnInfo
{
	public static function getCurrentRevision()
	{
		$info = `LANG=C svn info --xml`;
		
		$x = @simplexml_load_string($info);
		if (!$x) {
			throw new Exception('Unable to get current SVN revision');
		}
		
		$rev = $x->entry->commit['revision'];
		
		if (!$rev) {
			throw new Exception('Last revision number is invalid');
		}
		
		return (integer) $rev;
	}
	
	public static function getChangeLog($fromrev,$torev)
	{
		$log = `LANG=C svn log --xml -r $fromrev:$torev`;
		
		$x = @simplexml_load_string($log);
		if (!$x) {
			throw new Exception('Unable to open SVN log');
		}
		
		$res = array();
		foreach ($x->logentry as $change)
		{
			$res[(integer) $change['revision']] = array(
				'author' => (string) $change->author,
				'date' => (string) $change->date,
				'msg' => trim((string) $change->msg)
			);
		}
		
		return $res;
	}
}

# Main
try
{
	$ch = new debianChangelog();
	$ch->writeChangelog();
}
catch (Exception $e)
{
	fwrite(STDERR,$e->getMessage()."\n");
	exit(1);
}
?>