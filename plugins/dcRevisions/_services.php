<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
#
# Copyright (c) 2011 Tomtom and contributors
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
		
		$rsp = new xmlTag();
		
		foreach ($o as $k => $v) {
			$rsp->insertNode(self::buildNode($v,$n[$k],2,$k));
		}
		
		return $rsp;
	}
	
	public static function buildNode($src,$dst,$ctx,$root)
	{
		$udiff = diff::uniDiff($src,$dst,$ctx);
		$tdiff = new tidyDiff(htmlspecialchars($udiff),true);
		
		$rev = new xmlTag($root);
		
		foreach ($tdiff->getChunks() as $k => $chunk) {
			foreach ($chunk->getLines() as $line) {
				switch ($line->type) {
					case 'context':
						$node = new xmlTag('context');
						$node->oline = $line->lines[0];
						$node->nline = $line->lines[1];
						$node->insertNode($line->content);
						$rev->insertNode($node);
						break;
					case 'delete':
						$node = new xmlTag('delete');
						$node->oline = $line->lines[0];
						$c = str_replace(array('\0','\1'),array('<del>','</del>'),$line->content);
						$node->insertNode($c);
						$rev->insertNode($node);
						break;
					case 'insert':
						$node = new xmlTag('insert');
						$node->nline = $line->lines[1];
						$c = str_replace(array('\0','\1'),array('<ins>','</ins>'),$line->content);
						$node->insertNode($c);
						$rev->insertNode($node);
						break;
				}
			}
			if ($k < count($tdiff->getChunks()) - 1) {
				$node = new xmlTag('skip');
				$rev->insertNode($node);
			}
		}
		return $rev;
	}
}

?>