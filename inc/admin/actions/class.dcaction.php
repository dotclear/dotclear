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

abstract class dcAction {
	protected $actionset;
		
	public function setActionSet($as) {
		$this->actionset = $as;
	}
	abstract public function onAction($post,$actionset,$post);
}

class dcSimpleAction extends dcAction {
	protected $callback;
	
	public function __construct($callback) {
		$this->callback = $callback;
	}
	
	public function onAction($core,$actionset,$post) {
		return call_user_func($this->callback,$core,$actionset,$post);
	}
}

class dcActionSet
{
	protected $uri;
	protected $core;
	protected $combo;
	protected $actions;
	protected $ids;
	protected $rs;
	protected $redir_args;
	protected $redirect_fields;
	protected $action;
	
	public function __construct($core,$uri) {
		$this->core = $core;
		$this->actions = new ArrayObject();
		$this->combo = array();
		$this->uri = $uri;
		$this->redir_args = array();
		$this->redirect_fields = array();
		$this->action = '';
	}
	
	public function addAction ($actions,$callback) {
		if (is_array($callback)) {
			$callback = new dcSimpleAction($callback);
		}
		foreach ($actions as $k => $a) {
			if (is_array($a)) {
				$values = array_values($a);
				if (!isset($this->combo[$k])) {
					$this->combo[$k]=array();
				}
				$this->combo[$k] = array_merge ($this->combo[$k],$a);
			} elseif ($a instanceof formSelectOption) {
				$values = $a->value;
				$this->combo[$k] = $a->value;
			} else {
				$values = $a;
				$this->combo[$k] = $a;
			}
			foreach ($values as $v) {
				$this->actions[$v]=$callback;
			}
		}
		$callback->setActionSet($this);
	}
	
	public function getCombo() {
		return $this->combo;
	}
	
	public function getIDs() {
		return $this->ids;
	}
	public function getIDsHidden() {
		$ret = '';
		foreach  ($this->ids as $id) {
			$ret .= form::hidden('entries[]',$id);
		}
		return $ret;
	}
	public function getHiddenFields($with_ids = false) {
		$ret = '';
		foreach ($this->redir_args as $k => $v) {
			$ret .= form::hidden(array($k),$v);
		}
		if ($with_ids) {
			$ret .= $this->getIDsHidden();
		}
		return $ret;
	}
	public function getRS() {
		return $this->rs;
	}
	
	public function setupRedir($from) {
		foreach ($this->redirect_fields as $p) {
			if (isset($from[$p])) {
				$redir_args[$p] = $from[$p];
			}
		}
	}
	public function getRedirection($params=array(),$with_selected_entries=false) {
		$redir_args = array_merge($params,$this->redir_args);
		if ($with_selected_entries) {
			$redir_args['entries'] = $this->ids;
		}
		return $this->uri.'?'.http_build_query($redir_args);
	}

	public function redirect($params=array(),$with_selected_entries=false) {
		http::redirect($this->getRedirection($params,$with_selected_entries));
	}	
	
	public function beginPage($head='') {
	}
	
	public function endPage() {
	}
	public function getAction() {
		return $this->action;
	}
	public function process() {
		$from = new ArrayObject($_POST);
		if (isset($from['redir']) && strpos($from['redir'],'://') === false) {
			$this->redir = html::escapeURL($_POST['redir']);
		} else {
			$this->setupRedir($from);
		}
		$this->fetchEntries($from);	
		if (isset($from['action'])) {
			$this->action = $from['action'];
			try {
				$performed=false;
				foreach ($this->actions as $k=>$v) {
					if ($from['action']==$k) {
						$performed = true;
						$v->onAction($this->core,$this,$from);
					}
				}
				if ($performed) {
					exit;
				}
			} catch (Exception $e) {
				$this->error($e);
			}
		}
	}
	
}

