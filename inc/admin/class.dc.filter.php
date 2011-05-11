<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of DotClear "MyPostTypes" plugin.
# Copyright (c) 2010 Bruno Hondelatte, and contributors. 
# Many, many thanks to Olivier Meunier and the Dotclear Team.
# All rights reserved.
#
# MyPostTypes plugin for DC2 is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# DotClear is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with DotClear; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****

class dcFilterSet {

	protected $filters;
	protected $form_prefix;
	protected $action;
	protected $hideform;
	protected $columns_form;
	
	public function __construct($action,$form_prefix="f_") {
		$this->form_prefix=$form_prefix;
		$this->filters = array();
		$this->action = $action;
	}

	public function addFilter (Filter $filter) {
		$filter->setFormPrefix($this->form_prefix);
		$this->filters[$filter->id] = $filter;
		return $this;
	}

	// Retrieves filter values from context
	public function setValues ($form_data) {
		$this->hideform = true;
		if (isset($form_data['clear_filters']))
			return;
		foreach ($this->filters as $filter) {
			$filter->setValues ($form_data);
			if ($filter->isEnabled())
				$this->hideform=false;
		}
		if (isset($form_data['add_filter']) && isset($this->filters[$form_data['add_filter']])) {
			$this->filters[$form_data['add_filter']]->add();
		}
	}
	
	public function setColumnsForm($html)
	{
		$this->columns_form = $html;
	}
	
	public function getFormFieldsAsHidden() {
		$ret='';
		foreach ($this->filters as $filter) {
			$ret.= $filter->getFormFieldAsHidden();
		}
		return $ret;
	}

	public function getForm($action,$extra_content,$method="get",$nb_cols=3) {
		$ret = '';
		/*if ($this->hideform) {
			$ret .= '<p><a id="filter-control" class="form-control" href="#">'.
			__('Filters').'</a></p>';
		}*/
		$ret .= '<p><img alt="" src="minus.png"/> <a href="#" id="toggle-filters">'.__('Toggle filters and display options').'</a></p>';
		$ret .=
			'<div class="two-cols">'.
			'<form id="filters" action="'.$this->action.'" method="get" id="filters-form">'.
			'<div class="col70">'.
			'<h3>'.__('Entries filters').'</h3>';
			
		$count=0;
		$form_combo=array();
		$form_combo['-']='';
		foreach ($this->filters as $filter) {
			if ($filter->isEnabled()) {
				$ret .= $filter->getFormLine();
			}
			$form_combo[$filter->desc]=$filter->id;
			$count++;
		}
		$ret .= 
			'<p class="clear"><input class="delete" type="submit" value="'.__('Delete all filters').'" name="clear_filters"></p>'.
			'<h3 class="margintop">'.__('Add a filter').'</h3>'.
			'<p id="available_filters">'.
			form::combo("add_filter",$form_combo).
			'<input type="submit" value=" + " title="'.__('Add this filter').'" name="apply">'.
			'</p>'.
			'</div>'.
			'<div class="col30">'.
			$this->columns_form.
			'</div>'.
			'<p class="clear margintop"><input type="submit" value="'.__('Apply filters and display options').'" name="apply"></p>'.

			'</form></div>';
		return $ret;
	}

	public function header() {
		return dcPage::jsLoad('js/filters.js');
	}
	public function display() {
		echo $this->getForm("#","");
	}

	public function applyFilters($params) {
		$filtered = false;
		foreach ($this->filters as $filter) {
			if ($filter->isEnabled()) {
				$filter->applyFilter($params);
				$filtered = true;
			}
		}
		return $filtered;
	}
	
}


abstract class Filter {
	public $id;
	public $desc;
	protected $request_param;
	protected $enabled;
	protected $values;
	public $field_id;
	
	
	public function __construct ($id,$desc,$request_param) {
		$this->id = $id;
		$this->desc = $desc;
		$this->request_param = $request_param;
		$this->enabled=false;
		$this->values = array();
		$this->field_id = $this->id;
	}
	
	protected function getFieldId($pos=0) {
		if ($pos == 0) {
			return $this->field_id;
		} else {
			return $this->field_id.'_'.$pos;
		}
	}
	
	public function isEnabled() {
		return $this->enabled;
	}
	
	public function add() {
		$this->enabled = true;
	}
	
	public function setFormPrefix($prefix) {
		$this->field_id = $prefix.$this->id;
	}
	
	public abstract function getType();
	
	public function getFormFields() {
		return '';
	}

	public function setValues($form_data) {
/*		if (isset($form_data['c_'.$this->field_id])) {
			$this->enabled = true;
		}*/
		$count=0;
		while (isset($form_data[$this->getFieldId($count)])) {
			if (!isset($form_data['del_'.$this->getFieldId($count)])) {
				$this->values[] = $form_data[$this->getFieldId($count)];
				$this->enabled = true;
			}
			$count++;
		}
	}
	
	public function getFormFieldAsHidden () {
		$ret='';
		for ($cur=0; $cur < count($this->values); $cur++) {
			$ret .= form::hidden($this->getFieldId($cur), $this->values[$cur]);
		}
	}
	public function getFormLine() {
		$ret="";
		for ($cur=0; $cur < count($this->values); $cur++) {
			$ret .= '<p id="'.$this->getFieldId($cur).'" class="line" title="'.$this->desc.'">'.
				$this->getFormFields($cur).
				'<input id="del_'.$this->getFieldId($cur).'" class="delete" '.
				'type="submit" title="Delete this filter" value=" - " name="del_'.$this->getFieldId($cur).'"/>'.
				'</p>';
		}
		return $ret;
	}
	
	public function applyFilter($params) {
	}
	
}

class comboFilter extends Filter {
	protected $options;
	protected $default;
	protected $no_value;
	protected $verb;
	protected $extra;
	
	public function __construct($id,$desc,$request_param,$options,$extra=array()) {
		parent::__construct($id,$desc,$request_param);
		$this->options = $options;
		$this->extra = $extra;
		$this->desc = $desc;
		$this->verb = "is";
		$this->values=array();
	}
	
	public function add() {
		parent::add();
		if (isset($this->extra['singleval']) && (count($this->values) > 0))
			return;
		$this->values[]=current($this->options);
	}
	
	public function getType() {
		return "combo";
	}
	
	public function setValues($form_data) {
		parent::setValues($form_data);
		if (isset($form_data[$this->field_id."_v"])) {
			$this->verb = $form_data[$this->field_id."_v"] == 'is' ? 'is' : 'isnot';
		}
	}

	public function getFormFieldAsHidden () {
		return parent::getFormFieldAsHidden().form::hidden($this->field_id."_v",$this->verb);
	}

	public function getFormFields($pos=0) {
		
		if ($pos == 0) {
			$desc = $this->desc.' : ';
			$labelclass="";
		} else {
			$desc = __('or');
			$labelclass = ' class="or"';
		};
		return '<label for="'.$this->getFieldId($pos).'"'.$labelclass.'>'.$desc.'</label>'.
			(($pos == 0) ?form::combo($this->field_id.'_v',array(__('is')=>'is',__('is not')=>'isnot'),$this->verb) : '').
			form::combo($this->getFieldId($pos),$this->options,$this->values[$pos]);
	}
	
	public function applyFilter($params) {
		if (isset($this->extra['singleval']))
			$params[$this->request_param]=$this->values[0];
		else
			$params[$this->request_param]=$this->values;
	}
}
?>
