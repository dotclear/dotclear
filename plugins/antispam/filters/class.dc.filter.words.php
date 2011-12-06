<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

class dcFilterWords extends dcSpamFilter
{
	public $has_gui = true;
	public $name = 'Bad Words';

	private $style_list = 'height: 200px; overflow: auto; margin-bottom: 1em; ';
	private $style_p = 'margin: 1px 0 0 0; padding: 0.2em 0.5em; ';
	private $style_global = 'background: #ccff99; ';

	private $con;
	private $table;

	public function __construct($core)
	{
		parent::__construct($core);
		$this->con =& $core->con;
		$this->table = $core->prefix.'spamrule';
	}

	protected function setInfo()
	{
		$this->description = __('Words Blacklist');
	}

	public function getStatusMessage($status,$comment_id)
	{
		return sprintf(__('Filtered by %1$s with word %2$s.'),$this->guiLink(),'<em>'.$status.'</em>');
	}

	public function isSpam($type,$author,$email,$site,$ip,$content,$post_id,&$status)
	{
		$str = $author.' '.$email.' '.$site.' '.$content;

		$rs = $this->getRules();

		while ($rs->fetch())
		{
			$word = $rs->rule_content;

			if (substr($word,0,1) == '/' && substr($word,-1,1) == '/') {
				$reg = substr(substr($word,1),0,-1);
			} else {
				$reg = preg_quote($word, '/');
				$reg = '(^|\s+|>|<)'.$reg.'(>|<|\s+|\.|$)';
			}

			if (preg_match('/'.$reg.'/msiu',$str)) {
				$status = $word;
				return true;
			}
		}
	}

	public function gui($url)
	{
		$core =& $this->core;

		# Create list
		if (!empty($_POST['createlist']))
		{
			try {
				$this->defaultWordsList();
				http::redirect($url.'&list=1');
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		# Adding a word
		if (!empty($_POST['swa']))
		{
			$globalsw = !empty($_POST['globalsw']) && $core->auth->isSuperAdmin();

			try {
				$this->addRule($_POST['swa'],$globalsw);
				http::redirect($url.'&added=1');
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		# Removing spamwords
		if (!empty($_POST['swd']) && is_array($_POST['swd']))
		{
			try {
				$this->removeRule($_POST['swd']);
				http::redirect($url.'&removed=1');
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		/* DISPLAY
		---------------------------------------------- */
		$res = '';

		if (!empty($_GET['list'])) {
			$res .= '<p class="message">'.__('Words have been successfully added.').'</p>';
		}
		if (!empty($_GET['added'])) {
			$res .= '<p class="message">'.__('Word has been successfully added.').'</p>';
		}
		if (!empty($_GET['removed'])) {
			$res .= '<p class="message">'.__('Words have been successfully removed.').'</p>';
		}

		$res .=
		'<form action="'.html::escapeURL($url).'" method="post" class="fieldset">'.
		'<p><label class="classic" for="swa">'.__('Add a word ').' '.form::field('swa',20,128).'</label>';

		if ($core->auth->isSuperAdmin()) {
			$res .= '<label class="classic" for="globalsw">'.form::checkbox('globalsw',1).' '.
			__('Global word').'</label> ';
		}

		$res .=
		$core->formNonce().
		'</p>'.
		'<p><input type="submit" value="'.__('Add').'"/></p>'.
		'</form>';

		$rs = $this->getRules();
		if ($rs->isEmpty())
		{
			$res .= '<p><strong>'.__('No word in list.').'</strong></p>';
		}
		else
		{
			$res .=
			'<form action="'.html::escapeURL($url).'" method="post" class="fieldset">'.
			'<h3>' . __('List of bad words') . '</h3>'.
			'<div style="'.$this->style_list.'">';

			while ($rs->fetch())
			{
				$disabled_word = false;
				$p_style = $this->style_p;
				if (!$rs->blog_id) {
					$disabled_word = !$core->auth->isSuperAdmin();
					$p_style .= $this->style_global;
				}

				$res .=
				'<p style="'.$p_style.'"><label class="classic" for="word-'.$rs->rule_id.'">'.
				form::checkbox(array('swd[]', 'word-'.$rs->rule_id),$rs->rule_id,false,'','',$disabled_word).' '.
				html::escapeHTML($rs->rule_content).
				'</label></p>';
			}

			$res .=
			'</div>'.
			'<p>'.form::hidden(array('spamwords'),1).
			$core->formNonce().
			'<input class="submit delete" type="submit" value="' . __('Delete selected words') . '"/></p>'.
			'</form>';
		}

		if ($core->auth->isSuperAdmin())
		{
			$res .=
			'<form action="'.html::escapeURL($url).'" method="post">'.
			'<p><input type="submit" value="'.__('Create default wordlist').'" />'.
			form::hidden(array('spamwords'),1).
			form::hidden(array('createlist'),1).
			$core->formNonce().'</p>'.
			'</form>';
		}

		return $res;
	}

	private function getRules()
	{
		$strReq = 'SELECT rule_id, blog_id, rule_content '.
				'FROM '.$this->table.' '.
				"WHERE rule_type = 'word' ".
				"AND ( blog_id = '".$this->con->escape($this->core->blog->id)."' ".
				"OR blog_id IS NULL ) ".
				'ORDER BY blog_id ASC, rule_content ASC ';

		return $this->con->select($strReq);
	}

	private function addRule($content,$general=false)
	{
		$strReq = 'SELECT rule_id FROM '.$this->table.' '.
				"WHERE rule_type = 'word' ".
				"AND rule_content = '".$this->con->escape($content)."' ";
		$rs = $this->con->select($strReq);

		if (!$rs->isEmpty()) {
			throw new Exception(__('This word exists'));
		}

		$rs = $this->con->select('SELECT MAX(rule_id) FROM '.$this->table);
		$id = (integer) $rs->f(0) + 1;

		$cur = $this->con->openCursor($this->table);
		$cur->rule_id = $id;
		$cur->rule_type = 'word';
		$cur->rule_content = (string) $content;

		if ($general && $this->core->auth->isSuperAdmin()) {
			$cur->blog_id = null;
		} else {
			$cur->blog_id = $this->core->blog->id;
		}

		$cur->insert();
	}

	private function removeRule($ids)
	{
		$strReq = 'DELETE FROM '.$this->table.' ';

		if (is_array($ids)) {
			foreach ($ids as &$v) {
				$v = (integer) $v;
			}
			$strReq .= 'WHERE rule_id IN ('.implode(',',$ids).') ';
		} else {
			$ids = (integer) $ids;
			$strReq .= 'WHERE rule_id = '.$ids.' ';
		}

		if (!$this->core->auth->isSuperAdmin()) {
			$strReq .= "AND blog_id = '".$this->con->escape($this->core->blog->id)."' ";
		}

		$this->con->execute($strReq);
	}

	public function defaultWordsList()
	{
		$words = array(
			'/-credit(\s+|$)/',
			'/-digest(\s+|$)/',
			'/-loan(\s+|$)/',
			'/-online(\s+|$)/',
			'4u',
			'adipex',
			'advicer',
			'ambien',
			'baccarat',
			'baccarrat',
			'blackjack',
			'bllogspot',
			'bolobomb',
			'booker',
			'byob',
			'car-rental-e-site',
			'car-rentals-e-site',
			'carisoprodol',
			'cash',
			'casino',
			'casinos',
			'chatroom',
			'cialis',
			'craps',
			'credit-card',
			'credit-report-4u',
			'cwas',
			'cyclen',
			'cyclobenzaprine',
			'dating-e-site',
			'day-trading',
			'debt',
			'digest-',
			'discount',
			'discreetordering',
			'duty-free',
			'dutyfree',
			'estate',
			'favourits',
			'fioricet',
			'flowers-leading-site',
			'freenet',
			'freenet-shopping',
			'gambling',
			'gamias',
			'health-insurancedeals-4u',
			'holdem',
			'holdempoker',
			'holdemsoftware',
			'holdemtexasturbowilson',
			'hotel-dealse-site',
			'hotele-site',
			'hotelse-site',
			'incest',
			'insurance-quotesdeals-4u',
			'insurancedeals-4u',
			'jrcreations',
			'levitra',
			'macinstruct',
			'mortgage',
			'online-gambling',
			'onlinegambling-4u',
			'ottawavalleyag',
			'ownsthis',
			'palm-texas-holdem-game',
			'paxil',
			'pharmacy',
			'phentermine',
			'pills',
			'poker',
			'poker-chip',
			'poze',
			'prescription',
			'rarehomes',
			'refund',
			'rental-car-e-site',
			'roulette',
			'shemale',
			'slot',
			'slot-machine',
			'soma',
			'taboo',
			'tamiflu',
			'texas-holdem',
			'thorcarlson',
			'top-e-site',
			'top-site',
			'tramadol',
			'trim-spa',
			'ultram',
			'v1h',
			'vacuum',
			'valeofglamorganconservatives',
			'viagra',
			'vicodin',
			'vioxx',
			'xanax',
			'zolus'
		);

		foreach ($words as $w) {
			try {
				$this->addRule($w,true);
			} catch (Exception $e) {}
		}
	}
}
?>