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


class dcFilterSet extends dcForm {
	protected $filters;		/// <b>array</b> lists of currently applied filters
	protected $static_filters;
	protected $all_filters;
	protected $form_prefix;		/// <b>string</b> displayed form prefix
	protected $action; 			/// <b>string</b> form action page
	protected $hide_filterset;		/// <b>boolean</b> start form display hidden by default or not
	protected $name;			/// <b>string</b> filterset name
	protected $core;

	public static function __init__($env) {
		$env->getExtension('dc_form')->addTemplate('@forms/formfilter_layout.html.twig');
		$env->addFunction(
			new Twig_SimpleFunction(
				'filterset',
				'dcFilterSet::renderFilterSet',
				array(
					'is_safe' => array('html'),
					'needs_context' => true,
					'needs_environment' => true
		)));
	}
	
	public static function renderFilterSet($env,$context,$name,$attributes=array())
	{
		$context['filtersetname']=$name;
		echo $env->getExtension('dc_form')->renderWidget(
			'filterset',
			$context
		);
	}
	/**
	Inits dcFilterSet object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct($core,$name,$action,$form_prefix="f_") {
		$this->form_prefix=$form_prefix;
		$this->filters = new ArrayObject();
		$this->static_filters = new ArrayObject();
		$this->all_filters = new ArrayObject();
		$this->action = $action;
		$this->filtered = false;
		parent::__construct($core,$name,$action,'POST');
		$this->id = "filters";
	}

	public function setup() {
		$form_combo = array();
		$form_combo['-'] = '';
		foreach ($this->all_filters as $filter) {
			$filter->init();
		}
		foreach ($this->filters as $filter) {
			$form_combo[$filter->id]=$filter->name;
		}
		$p = $this->form_prefix;
		$this
			->addField (
				new dcFieldCombo ($p.'add_filter','',$form_combo,array(
				)))
			->addField (
				new  dcFieldSubmit($p.'add',__('Add this filter'),array(
				)))
			->addField (
				new dcFieldSubmit($p.'clear_filters',__('Delete all filters'),array(
				)))
			->addField (
				new dcFieldSubmit($p.'apply',__('Apply filters and display options'),array(
				)))
			->addField (
				new dcFieldSubmit($p.'reset',__('Reset'),array(
				)))
		;
		$this->setupFields();
		/* Use cases :
			(1) $_POST not empty for formfilter fields :
				* efilters are set from $_POST
				* lfilters are set from $_GET
				* keep filters div shown
			(2) $_POST empty :
				* both efilters and lfilters are set from $_GET
				* hide filter div
		*/
		$action = false;
		//$allowed_actions = array('clear_filters','add','del_.*','apply','reset');
		$allowed_actions = '#^(clear_filters|add|del_.*|apply|reset)$#';
		// Fetch each $_POST parameter to see whether filters are concerned.
		// Only 1 action at a time is allowed.

		foreach ($_POST as $k => $v) {
			if (strpos($k,$this->form_prefix)===0) {
				$tmp = substr($k,strlen($this->form_prefix));
				$count = preg_match($allowed_actions,$tmp,$match);
				if ($count==1) {
					$action = $match[1];
					break;
				}
			}
		}
		$this->hide_filterset = true;
		if ($action !== false) {
			// Use case (1)
			if ($action != 'clear_filters' && $action != 'reset')  {
				$this->setupEditFilters($this->all_filters,$_POST);
				if ($action == 'add'){
					$fname = $p.'add_filter';
					if (isset($_POST[$fname])
						&& isset($this->filters[$_POST[$fname]])) {
					$this->filters[$_POST[$fname]]->add();
					}
					$this->hide_filterset = false;
				} elseif (strpos($action,'del_') === 0) {
					$count = preg_match('#del_(.+)_([0-9]+)#',$action,$match);
					if (($count == 1) && isset($this->filters[$match[1]])) {
						$this->filters[$match[1]]->remove($match[2]);
					}
					$this->hide_filterset = false;
				} elseif ($action=="apply") {
					$data = $this->saveFilters();
					$query = http_build_query($data,'','&');
					if ($query != '') {
						$query = (strpos($this->action,'?') === false ? '?' : '&').$query;
					}
					http::redirect($this->action.$query);
					exit;
				}
			}
			if (isset($_POST[$p."query"])) {
				parse_str($_POST[$p."query"],$out);
				$this->setupAppliedFilters($this->all_filters,$out);
				if ($action == 'reset') {
					$this->setupEditFilters($this->all_filters,$out);
				} elseif ($action == 'clear_filters') {
					$this->setupEditFilters($this->static_filters,$out);
					foreach ($this->filters as $f) {
						$f->cleanup();
					}
				}
			}
			
		} else {
			// Use case (2)
			$load_from_settings = true;
			foreach($_GET as $k=>$v) {
				if (strpos($k,$this->form_prefix)===0) {
					$load_from_settings=false;
					break;
				}
			}
			$get = $_GET;
			if ($load_from_settings) {
				$get = new ArrayObject(array_merge($this->loadFilters(),$get));
			}
			$this->setupEditFilters($this->all_filters,$get);

			$this->setupAppliedFilters($this->all_filters,$get);
		}
		foreach ($this->static_filters as $f) {
			if (!$f->isEnabled()) {
				$f->add();
			}
		}
		$queryParams = $this->getAppliedFilters();
		$this->addField(
			new dcFieldHidden($this->form_prefix.'query',
				http_build_query($queryParams)));
		$this->core->tpl->addGlobal('filterset_'.$this->name,$this->getContext());
	}

	public function getURLParams() {
		return $this->getAppliedFilters();
	}

	/**
	Saves user filters to preferences
	*/
	protected function saveFilters() {
		$ser = array();
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		$data = new ArrayObject();
		$data= $this->serialize();
		$ws->put($this->name,serialize($data->getArrayCopy()),'string');
		return $data;
	}

	/**
	Loads user filters from preferences
	*/
	protected function loadFilters() {
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		$data = (!is_null($ws->{$this->name})) ? unserialize($ws->{$this->name}) : array();
		if (is_array($data))
			return $data;
		else
			return array();
	}

	/**
	Updates filters values according to form_data
	To be called before any call to display() or getForm()
	
	@param	form_data	<b>array</b>	form values (usually $_GET or $_POST)
	*/
	protected function setupEditFilters ($filters,$form_data) {
		foreach ($filters as $filter) {
			$filter->setupFields ($form_data);
		}
	}
	protected function setupAppliedFilters ($filters,$form_data) {
		foreach ($filters as $filter) {
			$filter->setupAppliedFilter ($form_data);
		}
	}
	/**
	Retrieves the filters values as parameters
	
	@param	filters	<b>array</b>	list of concerned filters
	
	@return	<b>array</b>	the array of parameters

	*/

	protected function serialize() {
		$arr = new ArrayObject();
		foreach ($this->filters as $f) {
			if ($f->isEnabled()) {
				$f->serialize($arr);
			}
		}
		foreach ($this->static_filters as $f) {
			$f->serialize($arr);
		}
		return $arr;
	}
	/**
	Adds a new filter to list
	
	@param	filter		<b>dcFilter</b>		the filter to add
	*/
	public function addFilter (dcFilter $filter) {
		$filter->setFormPrefix($this->form_prefix);
		$filter->setFilterSet($this);
		$this->all_filters[$filter->id] = $filter;
		if ($filter->isStatic()) {
			$this->static_filters[$filter->id] = $filter;
		} else {
			$this->filters[$filter->id] = $filter;
		}
		return $this;
	}

	public function getContext() {
		$fcontext = new ArrayObject();
		$sfcontext = new ArrayObject();
		foreach ($this->filters as $f) {
			if($f->isEnabled()) {
				$f->appendEditLines($fcontext);
			}
		}
		foreach ($this->static_filters as $f) {
			$f->appendEditLines ($sfcontext);
		}
		return array(
			'active_filters' => $fcontext, 
			'static_filters' => $sfcontext,
			'hide_filters'	 => $this->hide_filterset,
			'prefix'		 => $this->form_prefix);
	}

	protected function getAppliedFilters() {
		$arr = new ArrayObject();
		foreach ($this->all_filters as $f) {
			if ($f->isApplied())
				$f->updateAppliedValues($arr);
		}
		return $arr;
	}
	/**
	Applies fieldset and return resulting parameters for request
	
	@param	method	<b>string</b>		form method to use (default: "get")
	@param	method	<b>string</b>		form method to use (default: "get")
	
	*/
	public function applyFilters($params) {
		foreach ($this->all_filters as $filter) {
			if ($filter->isApplied()) {
				$filter->applyFilter($params);
				$this->filtered = true;
			}
		}
		return $this->filtered;
	}

	public function getDelName($field_id,$pos) {
		return $this->form_prefix.'del_'.$field_id.'_'.$pos;
	}

}



/**
* dcFilter - describes an abstract filter
*
*/
abstract class dcFilter  {
	public $filterset;			///<b>string</b> filterset parent
	public $id;					///<b>string</b> field id (local to fieldset)
	public $name;				///<b>string</b> filter name
	public $desc;				///<b>string</b> field description
	public $filter_id;			///<b>string</b> field id (global to the page)
	protected $request_param;	///<b>string</b> resulting parameter array key
	protected $field;			///<b>array</b> currently edited values
	protected $avalues;			///<b>array</b> currently applied values
	protected $static;
	protected $options;
	protected $multiple;

	public function __construct ($id,$name,$desc,$request_param,$options=array()) {
		$this->id  				= $id;
		$this->name 			=$name;
		$this->desc 			= $desc;
		$this->request_param	= $request_param;
		$this->avalues   		= array();
		$this->filter_id 		= $this->id;
		$this->static    		= false;
		$this->multiple    		= false;
		$this->options   		= $options;
		if (isset($options['static']) && $options['static']) {
			$this->static 		= true;
		}
		if (isset($options['multiple']) && $options['multiple']) {
			$this->multiple		= true;
		}
	}

	/**
	Extract values from data (data being an array, such as $_GET or $_POST)
	
	@param	$data	<b>array</b>	data to parse
	@return	<b>array</b>	field values
	
	*/
	protected function parseData($data) {
		$arr = $this->field->parseValues($data);
		return array('values' => $arr);
	}

	public function isStatic() {
		return $this->static;
	}

	public function setupFields($data) {
		$this->field->setup($data);
	}


	public function init() {
	}

	public function cleanup() {
		$this->field->setValues(array());
	}

	public function setupAppliedFilter($data) {
		$this->avalues = $this->parseData($data);
	}

	public function updateAppliedValues($arr) {
		$arr[$this->filter_id] = $this->avalues['values'];
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
		$this->filter_id = $prefix.$this->id;
	}

	/**
	Tells whether the filter is enabled or not
	
	@return	<b>boolean</b> true if enabled, false otherwise
	*/
	public function isEnabled() {
		return count($this->field) != 0;
	}
	
	protected abstract function addValue($value=NULL);

	/**
	Adds the current filter to the list
	*/
	public function add() {
		if (count($this->field) > 1 && !$this->multiple)
			return;
		$this->addValue();
	}
	
	/**
	Removes a value from filter
	*/
	public function remove($pos) {
		$values = $this->field->getValues();
		if (isset($values[$pos])) {
			$this->field->delValue($pos);
		}

	}

	abstract protected function appendSingleLine($ctx,$pos);

	public function appendEditLines($ctx) {
		foreach ($this->field->getValues() as $cur => $f) {
			$line = new ArrayObject();
			$line['lineclass'] = $this->id;
			$line['del_id'] = $this->filterset->getDelName($this->id,$cur);
			$del = new dcFieldSubmit(
				$this->filterset->getDelName($this->id,$cur),
				'-',
				array(
					'attr' => array(
						'title' => __('Delete the following filter')))
			);
			$this->filterset->addField($del);
			$this->appendSingleLine($line,$cur);
			$ctx[]=$line;
		}
	}

	public function serialize($arr) {
		if (count($this->fields) == 1) {
			$arr[$this->filter_id]=$this->field->getValue();
		} else {
			$arr[$this->filter_id]=$this->field->getValues();
		}
	}
	
	public function isApplied(){
		return (count($this->avalues['values']) != 0);
	}

	/**
	Convert filter values into a $param filter, used for the upcoming SQL request
	
	@param <b>ArrayObject</b> the parameters array to enrich

    @return boolean true if a filter has been applied, false otherwise 
    */
	public function applyFilter($params) {
		return false;
	}

	public function header() {
		return '';
	}

	public function getFields() {
		return $this->field;
	}

}



class dcFilterText extends dcFilter {

	public function init() {
		$this->field = new dcFieldText(
			$this->filter_id,
			NULL);
		$this->filterset->addField($this->field);
		$this->multiple = false;
	}


	public function appendSingleLine($line,$pos) {
		$line['ffield'] = $this->field->getName();
		if ($this->static) {
			$line['display_inline'] = true;
		}

		if ($pos == 0) {
			$line['fwidget']='filter_text';
			$line['desc']=$this->desc;
		};
	}

	protected function addValue($value=NULL) {
		if ($value === NULL) {
			$value = '';
		}
		$this->field->addValue($value);
	}

	public function applyFilter($params) {
		$params[$this->request_param]=$this->avalues['values'][0];
	}
	
}
/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Handle combo filter on admin side. Can be single or multi-valued
*/
class dcFilterCombo extends dcFilter {
	protected $combo;
	
	public function __construct($id,$name,$desc,$request_param,$combo,$options=array()) {
		parent::__construct($id,$name,$desc,$request_param,$options);
		$this->combo = $combo;
	}
	public function init() {
		$this->field = new dcFieldCombo(
			$this->filter_id,
			NULL,
			$this->combo);
		$this->filterset->addField($this->field);
	}

	protected function addValue($value=NULL) {
		if ($value === NULL) {
			$value = current($this->combo);
		}
		$this->field->addValue($value);
	}

	public function appendSingleLine($line,$pos) {
		if ($this->static) {
			$line['display_inline'] = true;
		}
		$line['ffield'] = $this->field->getName();
		$line['foffset'] = $pos;
		if ($pos == 0) {
			$line['fwidget']='filter_combo';
			$line['desc']=$this->desc;
		} else {
			$line['fwidget']='filter_combo_cont';
		};
	}
	
	public function applyFilter($params) {
		$attr = $this->request_param;
		if ($this->multiple)
			$params[$attr]=$this->avalues['values'];
		else
			$params[$attr]=$this->avalues['values'][0];
	}

}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Handle combo filter on admin side. Can be single or multi-valued
*/
class dcFilterRichCombo extends dcFilterCombo {
	protected $verb;

	public function init() {
		parent::init();
		$this->verb = new dcFieldCombo(
			$this->filter_id.'_v', 
			'is',
			array(
				'is'=>__('is'),
				'isnot'=>__('is not'))
		);
		$this->filterset->addField($this->verb);
	}

	protected function parseData($data) {
		$val = parent::parseData($data);
		$v = $this->verb->parseValues($data);
		if (isset($v[0]) && $v[0] === 'isnot')
			$val['verb'] = 'isnot';
		else
			$val['verb'] ='is';
		return $val;
	}

	public function setupFields($data) {
		parent::setupFields($data);
		$this->verb->setup($data);
	}

	public function updateAppliedValues($arr) {
		parent::updateAppliedValues($arr);
		$arr['verb'] = $this->verb->getValue();
	}

	public function appendSingleLine($line,$pos) {
		parent::appendSingleLine($line,$pos);
		if ($pos == 0) {
			$line['fverb'] = $this->verb->getName();
			$line['fwidget']='filter_richcombo';
		} 
	}
	
	public function serialize($arr) {
		parent::serialize($arr);
		$arr[$this->filter_id.'_v']=$this->verb->getValue();
	}
	
	public function applyFilter($params) {
		parent::applyFilter($params);
		$attr = $this->request_param;
		if ($this->avalues['verb'] != "is") {
			$params[$attr."_not"] = true;
		}
	}

}



dcFilterSet::__init__($GLOBALS['core']->tpl);
?>