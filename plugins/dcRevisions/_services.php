<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
#
# Copyright (c) 2010 Tomtom and contributors
# http://blog.zenstyle.fr/
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

class dcRevisionsRestMethods
{
	public static function getPatch()
	{
		global $core;
		
		$pid = isset($_GET['pid']) ? $_GET['pid'] : null;
		$rid = isset($_GET['rid']) ? $_GET['rid'] : null;
		
		if ($pid === null) {
			throw new Exception(__('No post ID'));
		}
		if ($rid === null) {
			throw new Exception(__('No revision ID'));
		}
		
		$p = $core->blog->getPosts(array('post_id' => $pid));
		$o = array(
			'post_excerpt_xhtml' => $p->post_excerpt_xhtml,
			'post_content_xhtml' => $p->post_content_xhtml
		);
		
		$n = $core->blog->revisions->getPatch($pid,$rid);
		unset($n['post_excerpt']);
		unset($n['post_content']);
		
		$rsp = new xmlTag('revision');
		
		foreach ($o as $k => $v) {
			$c = dcRevisionsRestMethods::getNode($v,$n[$k],$k);
			$rsp->insertNode($c);
		}
		
		return $rsp;
	}
	
	protected static function getNode($old,$new,$node_name)
	{
		if (!is_array($old)) {
			$old = preg_replace ('/ +/',' ',$old);
			$lo = explode("\n",trim($old));
		} else {
			$lo = $old;
		}

		if (!is_array($new)) {
			$new = preg_replace('/ +/',' ',$new);
			$ln = explode("\n", trim($new));
		} else {
			$ln = $new;
		}
		
		$n = new xmlTag($node_name);
		
		$size = max(count($lo),count($ln));
		$pad_length = strlen($size);

		$equ = array_intersect_assoc($lo,$ln);
		$ins = array_diff_assoc($ln,$lo);
		$del = array_diff_assoc($lo,$ln);

		for ($i = 0; $i < $size; $i++)
		{
			$line_number = str_pad($i + 1,$pad_length,'0',STR_PAD_LEFT);

			if (isset($del[$i])) {
				$ld = new xmlTag('line');
				$ld->insertAttr('old',(string) $line_number);
				$ld->insertAttr('new','');
				$ld->insertAttr('content',dcRevisionsRestMethods::getLine($del,$ins,$i,'delete'));
				$n->insertNode($ld);
			}
			if (isset($equ[$i])) {
				$li = new xmlTag('line');
				$li->insertAttr('old',(string) $line_number);
				$li->insertAttr('new',(string) $line_number);
				$li->insertAttr('content',$equ[$i]);
				$n->insertNode($li);
			}
			if (isset($ins[$i])) {
				$l = new xmlTag('line');
				$l->insertAttr('old','');
				$l->insertAttr('new',(string) $line_number);
				$l->insertAttr('content',dcRevisionsRestMethods::getLine($del,$ins,$i,'insert'));
				$n->insertNode($l);
			}
		}

		return $n;
	}
	
	protected static function getLine($del,$ins,$key,$mode)
	{
		
		$p_del = '<del>%s</del>';
		$p_ins = '<ins>%s</ins>';
		
		$str_del = isset($del[$key]) ? preg_replace('#<p>(.*)<\/p>#','$1',$del[$key]) : '';
		$ins_del = isset($ins[$key]) ? preg_replace('#<p>(.*)<\/p>#','$1',$ins[$key]) : '';

		switch ($mode) {
			case 'delete':
				$p = $p_del;
				$src = preg_replace('#(.)*#',"\$1\n",$str_del);
				$dist = preg_replace('#(.)*#',"\$1\n",$ins_del);
				break;
			case 'insert':
				$p = $p_ins;
				$src = preg_replace('#(.)*#',"\$1\n",$ins_del);
				$dist = preg_replace('#(.)*#',"\$1\n",$str_del);
				break;
		}
		
		if (array_key_exists($key,$del) && !array_key_exists($key,$ins)) {
			return sprintf($p_del,preg_replace('#<p>(.*)<\/p>#','$1',$del[$key]));
		}
		if (array_key_exists($key,$ins) && !array_key_exists($key,$del)) {
			return sprintf($p_ins,preg_replace('#<p>(.*)<\/p>#','$1',$ins[$key]));
		}
		
		$del = preg_split('//',preg_replace('#<p>(.*)<\/p>#','$1',$del[$key]),-1);
		$ins = preg_split('//',preg_replace('#<p>(.*)<\/p>#','$1',$ins[$key]),-1);
		
		switch ($mode) {
			case 'delete':
				$p = $p_del;
				$arr = array_diff_assoc($del,$ins);
				$res = implode('',$del);
				break;
			case 'insert':
				$p = $p_ins;
				$arr = array_diff_assoc($ins,$del);
				$res = implode('',$ins);
				break;
		}
		
		$word = '';
		$diff = array();
		
		foreach ($arr as $k => $c) {
			if (array_key_exists($k+1,$arr)) {
				$word .= $c;
			}
			else {
				array_push($diff,$word);
				$word = '';
			}
		}
		
		return preg_replace('#('.implode('|',$diff).')#',sprintf($p,'$1'),$res);
	}
}

?>