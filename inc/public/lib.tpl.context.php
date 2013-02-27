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

class context
{
	public $stack = array();
	
	public function __set($name,$var)
	{
		if ($var === null) {
			$this->pop($name);
		} else {
			$this->stack[$name][] =& $var;
			if ($var instanceof record) {
				$this->stack['cur_loop'][] =& $var;
			}
		}
	}
	
	public function __get($name)
	{
		if (!isset($this->stack[$name])) {
			return null;
		}
		
		$n = count($this->stack[$name]);
		if ($n > 0) {
			return $this->stack[$name][($n-1)];
		}
		
		return null;
	}
	
	public function exists($name)
	{
		return isset($this->stack[$name][0]);
	}
	
	public function pop($name)
	{
		if (isset($this->stack[$name])) {
			$v = array_pop($this->stack[$name]);
			if ($v instanceof record) {
				array_pop($this->stack['cur_loop']);
			}
			unset($v);
		}
	}
	
	# Loop position tests
	public function loopPosition($start,$length=null,$even=null)
	{
		if (!$this->cur_loop) {
			return false;
		}
		
		$index = $this->cur_loop->index();
		$size = $this->cur_loop->count();
		
		$test = false;
		if ($start >= 0)
		{
			$test = $index >= $start;
			if ($length !== null) {
				if ($length >= 0) {
					$test = $test && $index < $start + $length;
				} else {
					$test = $test && $index < $size + $length;
				}
			}
		}
		else
		{
			$test = $index >= $size + $start;
			if ($length !== null) {
				if ($length >= 0) {
					$test = $test && $index < $size + $start + $length;
				} else {
					$test = $test && $index < $size + $length;
				}
			}
		}
		
		if ($even !== null) {
			$test = $test && $index%2 == $even;
		}
		
		return $test;
	}
	
	
	# Static methods
	public static function global_filter($str,
	$encode_xml, $remove_html, $cut_string, $lower_case, $upper_case ,$tag='')
	{
		$args = func_get_args();
		array_pop($args);
		$args[0] =& $str;
		
		# --BEHAVIOR-- publicBeforeContentFilter
		$res = $GLOBALS['core']->callBehavior('publicBeforeContentFilter',$GLOBALS['core'],$tag,$args);
		
		if ($remove_html) {
			$str = self::remove_html($str);
			$str = preg_replace('/\s+/',' ',$str);
		}
		
		if ($encode_xml) {
			$str = self::encode_xml($str);
		}
		
		if ($cut_string) {
			$str = self::cut_string($str,(integer) $cut_string);
		}
		
		if ($lower_case) {
			$str = self::lower_case($str);
		} elseif ($upper_case) {
			if ($upper_case == 2) {
				$str = self::capitalize($str);
			} else {
				$str = self::upper_case($str);
			}
		}
		
		# --BEHAVIOR-- publicAfterContentFilter
		$res = $GLOBALS['core']->callBehavior('publicAfterContentFilter',$GLOBALS['core'],$tag,$args);
		
		return $str;
	}
	
	
	public static function cut_string($str,$l)
	{
		return text::cutString($str,$l);
	}
	
	public static function encode_xml($str)
	{
		return html::escapeHTML($str);
	}
	
	public static function remove_html($str)
	{
		return html::decodeEntities(html::clean($str));
	}
	
	public static function lower_case($str)
	{
		return mb_strtolower($str);
	}
	
	public static function upper_case($str)
	{
		return mb_strtoupper($str);
	}

	public static function capitalize($str)
	{
		if ($str != '') {
		   $str[0] = mb_strtoupper($str[0]);
		}
		return $str;
	}
	
	# Static methods for pagination
	public static function PaginationNbPages()
	{
		global $_ctx;
		
		if ($_ctx->pagination === null) {
			return false;
		}
		
		$nb_posts = $_ctx->pagination->f(0);
		$nb_per_page = $_ctx->post_params['limit'][1];
		
		$nb_pages = ceil($nb_posts/$nb_per_page);
		
		return $nb_pages;
	}
	
	public static function PaginationPosition($offset=0)
	{
		if (isset($GLOBALS['_page_number'])) {
			$p = $GLOBALS['_page_number'];
		} else {
			$p = 1;
		}
		
		$p = $p+$offset;
		
		$n = self::PaginationNbPages();
		if (!$n) {
			return $p;
		}
		
		if ($p > $n || $p <= 0) {
			return 1;
		} else {
			return $p;
		}
	}
	
	public static function PaginationStart()
	{
		if (isset($GLOBALS['_page_number'])) {
			return self::PaginationPosition() == 1;
		}
		
		return true;
	}
	
	public static function PaginationEnd()
	{
		if (isset($GLOBALS['_page_number'])) {
			return self::PaginationPosition() == self::PaginationNbPages();
		}
		
		return false;
	}
	
	public static function PaginationURL($offset=0)
	{
		$args = $_SERVER['URL_REQUEST_PART'];
		
		$n = self::PaginationPosition($offset);
		
		$args = preg_replace('#(^|/)page/([0-9]+)$#','',$args);
		
		$url = $GLOBALS['core']->blog->url.$args;
		
		if ($n > 1) {
			$url = preg_replace('#/$#','',$url);
			$url .= '/page/'.$n;
		}
		
		# If search param
		if (!empty($_GET['q'])) {
			$s = strpos($url,'?') !== false ? '&amp;' : '?';
			$url .= $s.'q='.rawurlencode($_GET['q']);
		}
		return $url;
	}
	
	# Robots policy
	public static function robotsPolicy($base,$over)
	{
		$pol = array('INDEX' => 'INDEX','FOLLOW' => 'FOLLOW', 'ARCHIVE' => 'ARCHIVE');
		$base = array_flip(preg_split('/\s*,\s*/',$base));
		$over = array_flip(preg_split('/\s*,\s*/',$over));
		
		foreach ($pol as $k => &$v)
		{
			if (isset($base[$k]) || isset($base['NO'.$k])) {
				$v = isset($base['NO'.$k]) ? 'NO'.$k : $k;
			}
			if (isset($over[$k]) || isset($over['NO'.$k])) {
				$v = isset($over['NO'.$k]) ? 'NO'.$k : $k;
			}
		}
		
		if ($pol['ARCHIVE'] == 'ARCHIVE') {
			unset($pol['ARCHIVE']);
		}
		
		return implode(', ',$pol);
	}
	
	# Smilies static methods
	public static function getSmilies($blog)
	{
		$path = array();
		if (isset($GLOBALS['__theme'])) {
			$path[] = $GLOBALS['__theme'];
		}
		$path[] = 'default';
		$definition = $blog->themes_path.'/%s/smilies/smilies.txt';
		$base_url = $blog->settings->system->themes_url.'/%s/smilies/';
		
		$res = array();
		
		foreach ($path as $t)
		{
			if (file_exists(sprintf($definition,$t))) {
				$base_url = sprintf($base_url,$t);
				return self::smiliesDefinition(sprintf($definition,$t),$base_url);
			}
		}
		return false;
	}
	
	public static function smiliesDefinition($f,$url)
	{
		$def = file($f);
			
		$res = array();
		foreach($def as $v)
		{
			$v = trim($v);
			if (preg_match('|^([^\t]*)[\t]+(.*)$|',$v,$matches))
			{
				$r = '/(\A|[\s]+|>)('.preg_quote($matches[1],'/').')([\s]+|[<]|\Z)/ms';
				$s = '$1<img src="'.$url.$matches[2].'" '.
				'alt="$2" class="smiley" />$3';
				$res[$r] = $s;
			}
		}
		
		return $res;
	}
	
	public static function addSmilies($str)
	{
		if (!isset($GLOBALS['__smilies']) || !is_array($GLOBALS['__smilies'])) {
			return $str;
		}

		# Process part adapted from SmartyPants engine (J. Gruber et al.) :

		$tokens = self::tokenizeHTML($str);
		$result = '';
		$in_pre = 0;  # Keep track of when we're inside <pre> or <code> tags.

		foreach ($tokens as $cur_token) {
			if ($cur_token[0] == "tag") {
				# Don't mess with quotes inside tags.
				$result .= $cur_token[1];
				if (preg_match('@<(/?)(?:pre|code|kbd|script|math)[\s>]@', $cur_token[1], $matches)) {
					$in_pre = isset($matches[1]) && $matches[1] == '/' ? 0 : 1;
				}
			} else {
				$t = $cur_token[1];
				if (!$in_pre) {
					$t = preg_replace(array_keys($GLOBALS['__smilies']),array_values($GLOBALS['__smilies']),$t);
				}
				$result .= $t;
			}
		}

		return $result;
	}

	private static function tokenizeHTML($str)
	{
		# Function from SmartyPants engine (J. Gruber et al.)
		#
		#   Parameter:  String containing HTML markup.
		#   Returns:    An array of the tokens comprising the input
		#               string. Each token is either a tag (possibly with nested,
		#               tags contained therein, such as <a href="<MTFoo>">, or a
		#               run of text between tags. Each element of the array is a
		#               two-element array; the first is either 'tag' or 'text';
		#               the second is the actual value.
		#
		#
		#   Regular expression derived from the _tokenize() subroutine in 
		#   Brad Choate's MTRegex plugin.
		#   <http://www.bradchoate.com/past/mtregex.php>
		#
		$index = 0;
		$tokens = array();

		$match = '(?s:<!(?:--.*?--\s*)+>)|'.	# comment
				 '(?s:<\?.*?\?>)|'.				# processing instruction
												# regular tags
				 '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)'; 

		$parts = preg_split("{($match)}", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parts as $part) {
			if (++$index % 2 && $part != '') 
				$tokens[] = array('text', $part);
			else
				$tokens[] = array('tag', $part);
		}
		return $tokens;
	}

}
?>