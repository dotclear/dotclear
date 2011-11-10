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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear FilterSet class.

Dotclear FilterSet handles filters and columns when displaying items lists.
*/
class dcFilterSet {

	protected $lfilters;			/// <b>array</b> lists of defined filters
	protected $efilters;			/// <b>array</b> lists of defined filters
	protected $form_prefix;		/// <b>string</b> displayed form prefix
	protected $action; 			/// <b>string</b> form action page
	protected $hideform;		/// <b>boolean</b> start form display hidden by default or not
	protected $extra_data;	/// <b>string</b> columns form
	protected $name;			/// <b>string</b> fieldset name
	
	/**
	Inits dcFilterSet object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct($name,$action,$form_prefix="f_") {
		$this->name = $name;
		$this->form_prefix=$form_prefix;
		$this->lfilters = new ArrayObject();
		$this->efilters = new ArrayObject();
		$this->action = $action;
		$this->extra_data = '';
	}
	
	/**
	Adds a new filter to list
	
	@param	filter		<b>dcFilter</b>		the filter to add
	*/
	public function addFilter (Filter $filter) {
		$filter->setFormPrefix($this->form_prefix);
		$filter->setFilterSet($this);
		$this->efilters[$filter->id] = $filter;
		$this->lfilters[$filter->id] = clone $filter;
		return $this;
	}
	
	/**
	Saves user filters to preferences
	*/
	protected function saveFilters() {
		$ser = array();
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		foreach($this->lfilters as $filter) {
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
			$this->lfilters[$k]->unserialize($v);
		}
	}
	
	/**
	Updates filters values according to form_data
	To be called before any call to display() or getForm()
	
	@param	form_data	<b>array</b>	form values (usually $_GET or $_POST)
	*/
	protected function initializeFromData ($filters, $form_data) {
		$this->hideform = true;
		foreach ($filters as $filter) {
			$filter->initializeFromData ($form_data);
		}
	}
	
	/**
	Defines additional form in layout (right column)
	
	@param	html	<b>string</b>		the code to add
	*/
	public function setExtraData($html)
	{
		$this->extra_data = $html;
	}
	
	/**
	Returns form fields as hidden fields
	
	@return	<b>string</b>	the corresponding html code
	*/
	public function getFormFieldsAsHidden() {
		$ret='';
		foreach ($this->lfilters as $filter) {
			$ret.= $filter->getFormFieldAsHidden();
		}
		return $ret;
	}

	/**
	Sets up filterset from $get and $post parameters
	
	*/
	public function setup($get,$post) {
		/* Use cases :
			(1) $post not empty for formfilter fields :
				* efilters are set from $post
				* lfilters are set from $get
				* keep filters div shown
			(2) $post empty : 
				* both efilters and lfilters are set from $get
				* hide filter div
		*/
		$action = false;
		$allowed_actions = array('clear_filters','add','del_','apply','reset');
		// Fetch each $post parameter to see whether filters are concerned.
		// Only 1 action at a time is allowed.
		foreach ($post as $k => $v) {
			if (strpos($k,$this->form_prefix)===0) {
				$tmp = substr($k,strlen($this->form_prefix));
				foreach ($allowed_actions as $a) {
					if (strpos($tmp,$a)===0) {
						$action = $tmp;
						break;
					}
				}
			}
		}
		if ($action !== false) {
			// Use case (1)
			if ($action != 'clear_filters' && $action != 'reset')  {
				$this->initializeFromData($this->efilters,$post);
				if ($action == 'add'){
					if (isset($post['add_filter']) 
						&& isset($this->efilters[$post['add_filter']])) {
						echo 'addadd';
					$this->efilters[$post['add_filter']]->add();
					}
				} elseif (strpos($action,'del_') === 0) {

					$count = preg_match('#del_(.+)_([0-9]+)#',$action,$match);
					if (($count == 1) && isset($this->efilters[$match[1]])) {
						$this->efilters[$match[1]]->remove($match[2]);
					}
				} elseif ($action=="apply") {
					http::redirect($this->action.'?'.http_build_query($this->getFiltersAsParams($this->efilters),'','&'));
					exit;
				}
			}
			if (isset($post[$this->form_prefix."query"])) {
				parse_str($post[$this->form_prefix."query"],$out);
				$this->initializeFromData($this->lfilters,$out);
				if ($action == 'reset') {
					$this->initializeFromData($this->efilters,$out);
				}
			}
			$this->hideform=false;
		} else {
			$this->initializeFromData($this->efilters, $get);
			$this->initializeFromData($this->lfilters, $get);
		}
	}
	/**
	Retrieves filterset generated form
	
	@param	method	<b>string</b>		form method to use (default: "get")
	*/
	public function getForm() {
		$ret = '';
		
		if ($this->hideform) {
			$formclass = ' class="hidden"';
		} else {
			$formclass='';
		}
		$ret .= '<p><img alt="" src="images/minus.png" /> '.
			'<a href="#" id="toggle-filters">'.
			__('Toggle filters and display options').
			'</a></p>';
		
		$ret .=
			'<div class="two-cols">'.
			'<form id="filters" action="'.$this->action.'" method="post"'.$formclass.'>'.
			'<div class="col70">'.
			'<h3>'.__('Entries filters').'</h3>'.
			'<table summary="'.__('Query filters').'" id="tfilters">'.
			'<tbody>';
		$count=0;
		$form_combo=array();
		$form_combo['-']='';
		if (count($this->efilters)) {
			foreach ($this->efilters as $filter) {
				if ($filter->isEnabled()) {
					$ret .= $filter->getFormLine();
				}
				$form_combo[$filter->name]=$filter->id;
				$count++;
			}
		}
		$ret .= '</tbody></table>'.
			'<p class="clear"><input class="delete" type="submit" value="'.__('Delete all filters').'" name="'.
			$this->form_prefix.'clear_filters" />'.
			'&nbsp;<input  type="submit" value="'.__('Reset').'" name="'.
			$this->form_prefix.'reset" /></p>'.
			'<h3 class="margintop">'.__('Add a filter').'</h3>'.
			'<p id="available_filters">'.
			form::combo("add_filter",$form_combo).
			'<input type="submit" value=" + " title="'.__('Add this filter').'" name="'.$this->form_prefix.'add" />'.
			'</p>'.
			'</div>';
		if ($this->extra_data != '') {
			$ret .=
				'<div class="col30">'.
				$this->extra_data.
				'</div>';
		}
		$ret .=
			'<p class="clear margintop">'.
			'<input type="submit" value="'.__('Apply filters and display options').
			'" name="'.$this->form_prefix.'apply" /></p>'.
			form::hidden($this->form_prefix."query",http_build_query($this->getFiltersAsParams($this->lfilters))).
			$GLOBALS['core']->formNonce().
			'</form>'.
			'</div>';
		return $ret;
	}
	/**
	Retrieves the filters values as parameters
	
	@param	filters	<b>array</b>	list of concerned filters
	
	@return	<b>array</b>	the array of parameters

	*/

	protected function getFiltersAsParams($filters) {
		$arr = new ArrayObject();
		foreach ($filters as $f) {
			if ($f->isEnabled())
				$f->fillQS($arr);
		}
		return $arr;
	}
	
	protected function displayFilters($filters) {
		$ret = '<ul>';
		foreach ($filters as $f) {
			if ($f->isEnabled())
			$ret .= '<li>'.$f->getAsText().'</li>'."\n";
		}
		$ret .= '</ul>';
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
		foreach ($this->lfilters as $filter) {
			if ($filter->isEnabled()) {
				$filter->applyFilter($params);
				$filtered = true;
			}
		}
		return $filtered;
	}
	
	public function getDelName($field_id,$pos) {
		return $this->form_prefix.'del_'.$field_id.'_'.$pos;
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
	public $filterset;			///<b>string</b> filterset parent
	public $id;					///<b>string</b> field id (local to fieldset)
	public $name;				///<b>string</b> filter name
	public $desc;				///<b>string</b> field description
	protected $request_param;	///<b>string</b> resulting parameter array key
	protected $enabled;			///<b>string</b> true if filter is enabled
	protected $values;			///<b>array</b> possible filter values
	public $field_id;			///<b>string</b> field id (global to the page)
	
	/**
	Inits Filter object
	
	@param	id		<b>string</b>	field id
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct ($id,$name,$desc,$request_param) {
		$this->id = $id;
		$this->name=$name;
		$this->desc = $desc;
		$this->request_param = $request_param;
		$this->enabled=false;
		$this->values = array();
		$this->field_id = $this->id;
	}
	
	/**
	Defines the filterset containing this filter
	
	@param	prefix		<b>dcFilterset</b>	the filterset
	*/
	public function setFilterSet($fs) {
		$this->filterset = $fs;
	}
	
	/**
	Defines form prefix for filter
	
	@param	prefix		<b>string</b>	the form prefix
	*/
	public function setFormPrefix($prefix) {
		$this->field_id = $prefix.$this->id;
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
	Removes a value from filter
	*/
	public function remove($pos) {
		if (isset($this->values[$pos])) {
			array_splice($this->values,$pos,1);
			$this->enabled = (count($this->values)!=0);
		}
	}
	
	/**
	Returns HTML code for form field
	
	@param	pos		<b>integer</b>	position of the field to display 
									(in case of multiple values)
	@return <b>string</b> the html code
	*/
	abstract protected function getFormFields($pos=0);
	
	/**
	Extract values from data (data being an array, such as $_GET or $_POST)
	
	@param	$data	<b>array</b>	data to parse
	@return	<b>array</b>	field values
	
	*/
	protected function getValuesFromData($data) {
		$count=0;
		$arr = array();
		while (isset($data[$this->getFieldId($count)])) {
			$arr[$count] = $data[$this->getFieldId($count)];
			$count++;
		}
		return $arr;
	}
	
	public function initializeFromData($form_data) {
		$this->values = $this->getValuesFromData($form_data);
		$this->enabled = (count($this->values)!=0);
	}
	
	/**
	Returns HTML code for the hole filter lines
	
	@return <b>string</b> the html code
	*/
	
	public function getFormLine() {
		$ret='';
		for ($cur=0; $cur < count($this->values); $cur++) {
			$ret .= '<tr class="'.$this->id.'">';
			$del_id = $this->filterset->getDelName($this->id,$cur);
			$ret .= '<td><input id="'.$del_id.'" class="delete" '.
					'type="submit" title="Delete the following filter : " value=" - " name="'.$del_id.'"/></td>'.
					$this->getFormFields($cur);
			$ret .= '</tr>';
		}
		return $ret;
	}
	
	public function fillQS($arr) {
		for ($cur=0; $cur < count($this->values); $cur++) {
			$arr[$this->getFieldId($cur)]=$this->values[$cur];
		}
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
	Convert filter values into a $param filter, used for the upcoming SQL request
	
	@param <b>ArrayObject</b> the parameters array to enrich
	*/
	public function applyFilter($params) {
	}
	
	public function setValues($value) {
		$this->values = $value;
	}
	
	public function getValue() {
		return $this->values;
	}

	public abstract function getAsText();

	
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
	
	public function __construct($id,$name,$desc,$request_param,$options,$extra=array()) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->options = $options;
		$this->extra = $extra;
		$this->verb = "is";
		$this->values=array();
	}
	
	protected function getValuesFromData($data) {
		$val = parent::getValuesFromData($data);
		if (isset($data[$this->field_id.'_v'])) {
			$verb = $data[$this->field_id.'_v'];
		} else {
			$verb = "is";
		}
		$arr = array(
			'values' => $val,
			'verb' => $verb
		);
		return $arr;
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
	
	public function initializeFromData($form_data) {
		$arr = $this->getValuesFromData($form_data);
		$this->values = $arr['values'];
		$this->verb = $arr['verb'];
		$this->enabled = (count($this->values) != 0);
	}

	public function getFormFields($pos=0) {
		if ($pos == 0) {
			$ret = '<td id="'.$this->getFieldId($cur).'" title="'.$this->desc.'" class="filter-title">'.
				''.$this->desc.' : </td>'.
				'<td>'.
				form::combo($this->field_id.'_v',
					array(__('is')=>'is',__('is not')=>'isnot'),$this->verb,'','',
					false,'title="'.sprintf(__('%s is or is not'),$this->desc).'"').
				'</td>';
		} else {
			$ret = '<td id="'.$this->getFieldId($cur).'" title="or" colspan="2" class="or">'.
				__('or').' : </td>';
		};
		$ret .= '<td>'.form::combo($this->getFieldId($pos),$this->options,$this->values[$pos],
			'','',false,'title="'.__('Choose an option').'"').'</td>';
		return $ret;
	}

	public function getFormFieldAsHidden () {
		return parent::getFormFieldAsHidden().form::hidden($this->field_id."_v",$this->verb);
	}
	
	public function fillQS($arr) {
		parent::fillQS($arr);
		
		$arr[$this->field_id.'_v']=$this->verb;
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
	
	public function getValues() {
		return array_merge($this->values,array($this->field_id.'_v',$this->verb));
	}
	
	public function getAsText() {
		return sprintf("%s %s %s",$this->desc,$this->verb,join(',',$this->values));
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
	
	public function __construct($id,$name,$desc,$request_param,$options) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->options = $options;
		$this->values=array();
	}
	
	
	public function getType() {
		return "boolean";
	}
	public function add() {
		parent::add();
		$this->values=current($this->options);
	}

	public function getFormFields($pos=0) {
		return '<td><span class="'.$labelclass.'">'.$this->desc.'</span></td><td>'.
			form::combo($this->getFieldId($pos),$this->options,$this->values[$pos],
				'','',false,'title="'.__('Choose an option').'"').'</td>';
	}
	
	public function applyFilter($params) {
		$params[$this->request_param]=$this->values[0];
	}
	
	public function getAsText() {
		return sprintf("%s %s",$this->desc,$this->values[0]);
	}
}


class textFilter extends Filter {
	protected $size;
	protected $max;
	
	public function __construct($id,$name,$desc,$request_param,$size,$max) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->size = $size;
		$this->max = $max;
		$this->values=array();
	}
	
	
	public function getType() {
		return "text";
	}
	public function add() {
		parent::add();
		$this->values[]='';
	}

	public function getFormFields($pos=0) {
		return '<span class="'.$labelclass.'">'.$this->desc.'</span>'.
			form::field($this->getFieldId($pos),$this->size,$this->max,html::escapeHTML($this->values[0]));
	}
	
	public function applyFilter($params) {
		$params[$this->request_param]=$this->values[0];
	}
	
	public function setValues($value) {
		parent::setValues(array($value));
	}

	public function getAsText() {
		return sprintf("%s %s",$this->desc,$this->values[0]);
	}
	
}
?>