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


class dcFilterSetExtension extends Twig_Extension {
	private $filtersets;

	public function __construct($core)
	{
		$this->core = $core;
		$this->tpl = 'formfilter_layout.html.twig';
	}
	
	public function initRuntime(Twig_Environment $environment)
	{
		$this->core->tpl->getExtension('dc_form')->addTemplate($this->tpl);
	}
	
	
	public function getFunctions()
	{
		return array(
			'filterset' => new Twig_Function_Method(
				$this,
				'renderFilterSet',
				array(
					'is_safe' => array('html'),
					'needs_context' => true
		)));
	}

	

	public function renderFilterSet($context,$name,$attributes=array())
	{
		$context['filtersetname']=$name;
		echo $this->core->tpl->getExtension('dc_form')->renderBlock(
			'filterset',
			$context
		);
	}
	

	public function getName()
	{
		return 'dc_filterset';
	}

}


class dcFilterSet extends dcForm {
	protected $filters;		/// <b>array</b> lists of currently applied filters
	protected $form_prefix;		/// <b>string</b> displayed form prefix
	protected $action; 			/// <b>string</b> form action page
	protected $hideform;		/// <b>boolean</b> start form display hidden by default or not
	protected $name;			/// <b>string</b> filterset name
	protected $core;

	/**
	Inits dcFilterSet object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct($core,$name,$action,$form_prefix="f_") {
		$this->form_prefix=$form_prefix;
		$this->filters = new ArrayObject();
		$this->action = $action;
		$this->filtered = false;
		parent::__construct($core,$name,$action,'POST');
		$this->id = "filters";
	}

	public function setup() {
		$form_combo = array();
		$form_combo['-'] = '';
		foreach ($this->filters as $filter) {
			$filter->init();
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
		$submitted = $this->getSubmittedFields();
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
				echo $action;
				if ($count==1) {
					$action = $match[1];
					break;
				}
			}
		}
		if ($action !== false) {
			// Use case (1)
			if ($action != 'clear_filters' && $action != 'reset')  {
				$this->setupEditFilters($_POST);
				if ($action == 'add'){
					if (isset($_POST[$p.'add_filter'])
						&& isset($this->filters[$_POST[$p.'add_filter']])) {
					$this->filters[$_POST[$p.'add_filter']]->add();
					}
				} elseif (strpos($action,'del_') === 0) {
					$count = preg_match('#del_(.+)_([0-9]+)#',$action,$match);
					echo "action=".$action;
					if (($count == 1) && isset($this->filters[$match[1]])) {
						$this->filters[$match[1]]->remove($match[2]);
						echo "remove";
					}
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
				$this->setupAppliedFilters($out);
				if ($action == 'reset') {
					$this->setupEditFilters($out);
				}
			}
			$this->hideform=false;
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
			$this->setupEditFilters($get);
			$this->setupAppliedFilters($get);
		}
		$queryParams = $this->getListedFilters();
		$this->addField(
			new dcFieldHidden($this->form_prefix.'query',
				http_build_query($queryParams)));
		$this->core->tpl->addGlobal('filterset_'.$this->name,$this->getContext());
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
	protected function setupEditFilters ($form_data) {
		$this->hideform = true;
		foreach ($this->filters as $filter) {
			$filter->setupFields ($form_data);
		}
	}
	protected function setupAppliedFilters ($form_data) {
		foreach ($this->filters as $filter) {
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
		return $arr;
	}
	/**
	Adds a new filter to list
	
	@param	filter		<b>dcFilter</b>		the filter to add
	*/
	public function addFilter (dcFilter $filter) {
		$filter->setFormPrefix($this->form_prefix);
		$filter->setFilterSet($this);
		$this->filters[$filter->id] = $filter;
		return $this;
	}
	public function getContext() {
		$fcontext = new ArrayObject();
		foreach ($this->filters as $f) {
			if($f->isEnabled()) {
				$f->appendEditLines($fcontext);
			}
		}
		return array(
			'active_filters' => $fcontext, 
			'prefix'=>$this->form_prefix);
	}

	protected function getListedFilters() {
		$arr = new ArrayObject();
		foreach ($this->filters as $f) {
			if ($f->isEnabled())
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
		foreach ($this->filters as $filter) {
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
	protected $request_param;	///<b>string</b> resulting parameter array key
	protected $enabled;			///<b>string</b> true if filter is enabled
	protected $fields;			///<b>array</b> currently edited values
	protected $avalues;			///<b>array</b> currently applied values
	public $filter_id;			///<b>string</b> field id (global to the page)

	public function __construct ($id,$name,$desc,$request_param) {
		$this->id = $id;
		$this->name=$name;
		$this->desc = $desc;
		$this->request_param = $request_param;
		$this->enabled=false;
		$this->avalues = array();
		$this->filter_id = $this->id;
		$this->fields = array();
	}

	public function setupFields($data) {
		$val = $this->parseData($data);
		$pos=0;
		foreach($val['values'] as $v) {
			$this->addField($pos,$v);
			$pos++;
		}
		$this->enabled = ($pos> 0);
	}//	abstract public function setupFields($data);


	public function init() {
	}

	/**
	Extract values from data (data being an array, such as $_GET or $_POST)
	
	@param	$data	<b>array</b>	data to parse
	@return	<b>array</b>	field values
	
	*/
	protected function parseData($data) {
		$count=0;
		$arr = array();
		while (isset($data[$this->getFieldId($count)])) {
			$arr[] = $data[$this->getFieldId($count)];
			$count++;
		}
		return array('values' => $arr);
	}
	
	public function setupAppliedFilter($data) {
		$this->avalues = $this->parseData($data);
	}

	public function updateAppliedValues($arr) {
		foreach ($this->avalues['values'] as $k=>$v) {
			$arr[$this->getFieldID($k)]=$v;
		}
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
	Get a field id
	
	@param	pos		<b>integer</b>	position of field, in case of multiple field (0 if only 1 field set, default value)
	@return	<b>string</b> The field ID
	*/
	protected function getFieldId($pos=0) {
		if ($pos == 0) {
			return $this->filter_id;
		} else {
			return $this->filter_id.'_'.$pos;
		}
	}
	
	
	/**
	Tells whether the filter is enabled or not
	
	@return	<b>boolean</b> true if enabled, false otherwise
	*/
	public function isEnabled() {
		return $this->enabled;
	}
	
	protected abstract function addField($pos,$value=NULL);

	/**
	Adds the current filter to the list
	*/
	public function add() {
		$this->addField(count($this->fields));
		$this->enabled = true;
	}
	
	/**
	Removes a value from filter
	*/
	public function remove($pos) {
		if (isset($this->fields[$pos])) {
			$this->filterset->removeField($this->fields[$pos]);
			array_splice($this->fields,$pos,1);
			for ($cur=$pos; $cur<count($this->fields);$cur++) {
				$this->filterset->renameField($this->fields[$cur],$this->getFieldID($cur));
			}
			$this->enabled = (count($this->fields)!=0);
		}

	}

	abstract protected function appendSingleLine($ctx,$pos);

	public function appendEditLines($ctx) {
		foreach ($this->fields as $cur => $f) {
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
		for ($cur=0; $cur < count($this->fields); $cur++) {
			$arr[$this->getFieldId($cur)]=$this->fields[$cur]->getValue();
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

}


/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract filter class.

Handle combo filter on admin side. Can be single or multi-valued
*/
class comboFilter extends dcFilter {
	protected $options;
	protected $verb;
	protected $extra;
	
	public function __construct($id,$name,$desc,$request_param,$options,$extra=array()) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->options = $options;
		$this->extra = $extra;
	}

	public function init() {
		parent::init();
		$this->verb = new dcFieldCombo(
			$this->filter_id.'_v', 
			'is',
			array(
				'is'=>__('is'),
				'isnot'=>__('is not'))
		);
	}

	protected function parseData($data) {
		$val = parent::parseData($data);
		$val['verb'] = $this->verb->parseValue($data);
		return $val;
	}

	protected function addField($pos,$value=NULL) {
		if ($value === NULL) {
			$value = current($this->options);
		}
		$f = new dcFieldCombo(
			$this->getFieldID($pos),
			$value,
			$this->options);
		$this->filterset->addField($f);
		$this->fields[]=$f;
	}

	public function setupFields($data) {
		/*$val = $this->parseData($data);
		$this->verb->setup($data);
		$pos=0;
		foreach($val['values'] as $v) {
			$this->addField($pos,$v);
		}
		$this->enabled = (count($this->fields) != 0);*/
		parent::setupFields($data);
		$this->verb->setup($data);
		$this->filterset->addField($this->verb);
	}
	public function updateAppliedValues($arr) {
		parent::updateAppliedValues($arr);
		if ($this->enabled) {
			$arr[$this->verb->getName()] = $this->verb->getValue();
		}
	}
	public function add() {
		if (isset($this->extra['singleval']) && (count($this->fields) > 0))
			return;
		parent::add();
	}

	public function appendSingleLine($line,$pos) {
		$f = $this->fields[$pos];
		$line['ffield'] = $f->getName();

		if ($pos == 0) {
			$line['fwidget']='filter_combo';
			$line['fverb'] = $this->verb->getName();
			$line['desc']=$this->desc;
		} else {
			$line['fwidget']='filter_combo_cont';
		};
	}
	
	public function serialize($arr) {
		parent::serialize($arr);
		$arr[$this->filter_id.'_v']=$this->verb->getValue();
	}
	
	public function applyFilter($params) {
		$attr = $this->request_param;
		if ($this->avalues['verb'] != "is") {
			$params[$attr."_not"] = true;
		}
		if (isset($this->extra['singleval']))
			$params[$attr]=$this->avalues['values'][0];
		else
			$params[$attr]=$this->avalues['values'];
	}

}

class booleanFilter extends dcFilter {
	protected $options;
	protected $verb;
	
	public function __construct($id,$name,$desc,$request_param,$options) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->options = $options;
	}



	public function appendSingleLine($line,$pos) {
		$f = $this->fields[$pos];
		$line['ffield'] = $f->getName();

		if ($pos == 0) {
			$line['fwidget']='filter_boolean';
			$line['desc']=$this->desc;
		};
	}

	protected function addField($pos,$value=NULL) {
		if (count($this->fields)>0)
			return;
		if ($value === NULL) {
			$value = 1;
		}
		$f = new dcFieldCombo(
			$this->getFieldID($pos),
			$value,
			$this->options);
		$this->filterset->addField($f);
		$this->fields[]=$f;
	}

	public function applyFilter($params) {
		$params[$this->request_param]=$this->avalues['values'][0];
	}
	
}

class textFilter extends dcFilter {
	protected $options;
	protected $verb;
	
	public function __construct($id,$name,$desc,$request_param,$options) {
		parent::__construct($id,$name,$desc,$request_param);
		$this->options = $options;
	}

	public function setupFields($data) {
		$val = $this->parseData($data);
		foreach($val['values'] as $k=>$v) {
			$this->addField($k,$v);
		}
		$this->enabled = (count($this->fields) != 0);
	}

	public function appendSingleLine($line,$pos) {
		$f = $this->fields[$pos];
		$line['ffield'] = $f->getName();

		if ($pos == 0) {
			$line['fwidget']='filter_boolean';
			$line['desc']=$this->desc;
		};
	}

	protected function addField($pos,$value=NULL) {
		if (count($this->fields)>0)
			return;
		if ($value === NULL) {
			$value = 1;
		}
		$f = new dcFieldCombo(
			$this->getFieldID($pos),
			$value,
			$this->options);
		$this->filterset->addField($f);
		$this->fields[]=$f;
	}

	public function applyFilter($params) {
		$params[$this->request_param]=$this->avalues['values'][0];
	}
	
}

?>