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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear FilterSet class.

Dotclear FilterSet handles filters and columns when displaying items lists.
*/
class dcFilterSet {

	protected $filters;			/// <b>array</b> lists of defined filters
	protected $form_prefix;		/// <b>string</b> displayed form prefix
	protected $action; 			/// <b>string</b> form action page
	protected $hideform;		/// <b>boolean</b> start form display hidden by default or not
	protected $columns_form;	/// <b>string</b> columns form
	protected $name;			/// <b>string</b> fieldset name
	/**
	Inits dcFilterSet object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct($name,$action,$form_prefix="f_") {
		$this->name = $name;
		$this->form_prefix=$form_prefix;
		$this->filters = array();
		$this->action = $action;
	}

	/**
	Adds a new filter to list
	
	@param	filter		<b>dcFilter</b>		the filter to add
	*/
	public function addFilter (Filter $filter) {
		$filter->setFormPrefix($this->form_prefix);
		$this->filters[$filter->id] = $filter;
		return $this;
	}
	
	/**
	Saves user filters to preferences
	*/
	protected function saveFilters() {
		$ser = array();
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		foreach($this->filters as $filter) {
			$ser[$filter->id]=$filter->serialize();
		}
		$ws->put($this->name,serialize($ser),'string');
	}
	
	/**
	Loads user filters from preferences
	*/
	protected function loadFilters() {
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		
		$settings = !is_null($ws->{$this->name}) ? unserialize($ws->{$this->name}) : array();
		foreach($settings as $k => $v) {
			$this->filters[$k]->unserialize($v);
		}
	}
	
	/**
	Updates filters values according to form_data
	To be called before any call to display() or getForm()
	
	@param	form_data	<b>array</b>	form values (usually $_GET or $_POST)
	*/
	public function setValues ($form_data) {
		$this->hideform = true;
		if (isset($form_data['clear_filters'])) {
			$this->saveFilters();
			return;
		}
		if (!isset($form_data['apply'])) {
			$this->loadFilters();
		}
		foreach ($this->filters as $filter) {
			$filter->setValues ($form_data);
			if ($filter->isEnabled()) {
				$this->hideform=false;
			}
		}
		if (isset($form_data['apply'])) {
			if (trim($form_data['apply']) == '+'
				&& isset($form_data['add_filter']) 
				&& isset($this->filters[$form_data['add_filter']])) {
				$this->filters[$form_data['add_filter']]->add();
				$this->hideform=false;
			}
		}
		$this->saveFilters();
	}
	
	/**
	Defines additional form in layout (right column)
	
	@param	html	<b>string</b>		the code to add
	*/
	public function setColumnsForm($html)
	{
		$this->columns_form = $html;
	}
	
	/**
	Returns form fields as hidden fields
	
	@return	<b>string</b>	the corresponding html code
	*/
	public function getFormFieldsAsHidden() {
		$ret='';
		foreach ($this->filters as $filter) {
			$ret.= $filter->getFormFieldAsHidden();
		}
		return $ret;
	}

	/**
	Retrieves filterset generated form
	
	@param	method	<b>string</b>		form method to use (default: "get")
	*/
	public function getForm($method="get") {
		$ret = '';
		
		if ($this->hideform) {
			$formclass = ' class="hidden"';
		} else {
			$formclass='';
		}
		$ret .= '<p><img alt="" src="images/minus.png" /> <a href="#" id="toggle-filters">'.__('Toggle filters and display options').'</a></p>';
		$ret .=
			'<div class="two-cols">'.
			'<form id="filters" action="'.$this->action.'" method="'.$method.'" id="filters-form"'.$formclass.'>'.
			'<div class="col70">'.
			'<h3>'.__('Entries filters').'</h3>';
			
		$count=0;
		$form_combo=array();
		$form_combo['-']='';
		if (count($this->filters)) {
			$ret .= '<ul>';
			foreach ($this->filters as $filter) {
				if ($filter->isEnabled()) {
					$ret .= $filter->getFormLine();
				}
				$form_combo[$filter->desc]=$filter->id;
				$count++;
			}
			$ret .= '</ul>';
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
	
	/**
	Displays required fieldset http header
	To be called in page header, of course.
	*/
	public function header() {
		return dcPage::jsLoad('js/filters.js');
	}
	
	
	/**
	Displays the fieldset
	*/
	public function display() {
		echo $this->getForm();
	}

	/**
	Applies fieldset and return resulting parameters for request
	
	@param	method	<b>string</b>		form method to use (default: "get")
	@param	method	<b>string</b>		form method to use (default: "get")
	
	*/
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


/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Dotclear Filter handles administration filters for each list
A filter fills in a parameter array, as defined in dcBlog class
*/
abstract class Filter {
	public $id;					///< <b>string</b> field id (local to fieldset)
	public $desc;				///< <b>string</b> field description
	protected $request_param;	///< <b>string</b> resulting parameter array key
	protected $enabled;			///< <b>string</b> true if filter is enabled
	protected $values;			///< <b>array</b> possible filter values
	public $field_id;			///< <b>string</b> field id (global to the page)
	
	/**
	Inits Filter object
	
	@param	id		<b>string</b>	field id
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct ($id,$desc,$request_param) {
		$this->id = $id;
		$this->desc = $desc;
		$this->request_param = $request_param;
		$this->enabled=false;
		$this->values = array();
		$this->field_id = $this->id;
	}
	
	/**
	Get a field id
	
	@param	pos		<b>integer</b>	position of field, in case of multiple field (0 if only 1 field set, default value)
	@return	<b>string</b> The field ID
	*/
	protected function getFieldId($pos=0) {
		if ($pos == 0) {
			return $this->field_id;
		} else {
			return $this->field_id.'_'.$pos;
		}
	}
	
	/**
	Tells whether the filter is enabled or not
	
	@return	<b>boolean</b> true if enabled, false otherwise
	*/
	public function isEnabled() {
		return $this->enabled;
	}
	
	/**
	Adds the current filter to the list
	*/
	public function add() {
		// By default here, only 1 value allowed. Simply enable the filter
		$this->enabled = true;
	}
	
	/**
	Defines form prefix for filter
	
	@param	prefix		<b>string</b>	the form prefix
	*/
	public function setFormPrefix($prefix) {
		$this->field_id = $prefix.$this->id;
	}
	
	
	/**
	Returns HTML code for form field
	
	@param	pos		<b>integer</b>	position of the field to display (in case of multiple values)
	@return <b>string</b> the html code
	*/
	public function getFormFields($pos=0) {
		return '';
	}
	
	/**
	Returns filter values il a serialized way (array)
	
	@return		<b>array</b>	serialized data
	*/
	public function serialize() {
		return array(
			'values' => $this->values,
			'enabled' => $this->enabled
		);
	}
	
	/**
	Defines filter values from serialized data (array)
	To be used in conjunction with serialize method
	
	@param	$data	<b>array</b>	serialized data to retrieve
	*/
	public function unserialize ($data) {
		$this->values = $data['values'];
		$this->enabled = $data['enabled'];
	}
	
	/**
	Set filter values from form_data (usually $_GET)	
	@param	$form_data	<b>array</b>	form data
	*/
	public function setValues($form_data) {
		$count=0;
		while (isset($form_data[$this->getFieldId($count)])) {
			if (!isset($form_data['del_'.$this->getFieldId($count)])) {
				$this->values[$count] = $form_data[$this->getFieldId($count)];
			} elseif (isset($this->values[$count])) {
				unset($this->values[$count]);
			}
			$count++;

		}
		$this->values = array_values($this->values);
		$this->enabled = (count($this->values)!=0);
	}
	
		/**
	Returns form fields as hidden fields
	
	@return	<b>string</b>	the corresponding html code
	*/	
	public function getFormFieldAsHidden () {
		$ret='';
		for ($cur=0; $cur < count($this->values); $cur++) {
			$ret .= form::hidden($this->getFieldId($cur), $this->values[$cur]);
		}
	}
	/**
	Returns HTML code for the hole filter lines
	
	@return <b>string</b> the html code
	*/
	
	public function getFormLine() {
		$ret="";
		for ($cur=0; $cur < count($this->values); $cur++) {
			$ret .= '<li id="'.$this->getFieldId($cur).'" class="line" title="'.$this->desc.'">'.
				$this->getFormFields($cur).
				'<input id="del_'.$this->getFieldId($cur).'" class="delete" '.
				'type="submit" title="Delete the following filter : " value=" - " name="del_'.$this->getFieldId($cur).'"/>'.
				'</li>';
		}
		return $ret;
	}
	
	/**
	Convert filter values into a $param filter, used for the upcoming SQL request
	
	@param <b>ArrayObject</b> the parameters array to enrich
	*/
	public function applyFilter($params) {
	}
	
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Handle combo filter on admin side. Can be single or multi-valued
*/
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

	public function serialize() {
		$data = parent::serialize();
		$data['verb'] = $this->verb;
		return $data;
	}
	
	public function unserialize ($data) {
		parent::unserialize($data);
		$this->verb = $data['verb'];
	}
	
	public function setValues($form_data) {
		parent::setValues($form_data);
		if (isset($form_data[$this->field_id."_v"])) {
			$this->verb = ($form_data[$this->field_id."_v"] == 'is') ? 'is' : 'isnot';
		}
	}

	public function getFormFieldAsHidden () {
		return parent::getFormFieldAsHidden().form::hidden($this->field_id."_v",$this->verb);
	}

	public function getFormFields($pos=0) {
		
		if ($pos == 0) {
			$desc = $this->desc.' : ';
			$labelclass="filter-title";
		} else {
			$desc = __('or');
			$labelclass = 'or';
		};
		return '<span class="'.$labelclass.'">'.$desc.'</span>'.
			(($pos == 0) 
				?form::combo($this->field_id.'_v',
					array(__('is')=>'is',__('is not')=>'isnot'),$this->verb,'','',
					false,'title="'.sprintf(__('%s is or is not'),$this->desc).'"') 
				:'').
			form::combo($this->getFieldId($pos),$this->options,$this->values[$pos],
				'','',false,'title="'.__('Choose an option').'"');
	}
	
	public function applyFilter($params) {
		$attr = $this->request_param;
		if ($this->verb != "is") {
			$params[$attr."_not"] = true;
		}
		if (isset($this->extra['singleval']))
			$params[$attr]=$this->values[0];
		else
			$params[$attr]=$this->values;
	}
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Handle boolean filter on admin side.
*/
class booleanFilter extends Filter {
	protected $options;
	
	public function __construct($id,$desc,$request_param,$options,$extra=array()) {
		parent::__construct($id,$desc,$request_param);
		$this->options = $options;
		$this->values=array();
	}
	
	
	public function getType() {
		return "boolean";
	}
	public function add() {
		parent::add();
		$this->values[]=$options[0];
	}

	public function getFormFields($pos=0) {
		return '<span class="'.$labelclass.'">'.$this->desc.'</span>'.
			form::combo($this->getFieldId($pos),$this->options,$this->values[$pos],
				'','',false,'title="'.__('Choose an option').'"');
	}
	
	public function applyFilter($params) {
		$params[$this->request_param]=$this->values[0];
	}
}

?>
