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


/**
* dcFilterSet -- filter handling object
*
* @uses     dcForm
*
*/
class dcFilterSet extends dcForm {
	/** @var array list of variable filters */
	protected $filters;
	/** @var array list of static filters */
	protected $static_filters;
	/** @var array list of all filters (union of the 2 previous props) */
	protected $all_filters;
	/** @var string prefix to be used for all fields */
	protected $form_prefix;
	/** @var string action to perform upon form submission */
	protected $action;
	/** @var boolean start form display hidden by default or not */
	protected $hide_filterset;
	/** @var string filterset name */
	protected $name;
	/** @var dcCore dotclear core object */
	protected $core;
	/** @var boolean true if content is filtered */
	protected $filtered;

    /**
     * __init__ - class static initialiser (called at the very bottom of this
     * 				page)
     *
     * @param mixed $env the twig environment.
     *
     * @access public
     * @static
     *
     */
	public static function __init__($env) {
		// filterset widgets are defined in a separate file
		$env->getExtension('dc_form')->addTemplate(
			'@forms/formfilter_layout.html.twig'
		);

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

    /**
     * __construct -- constructor
     *
     * @param dcCore  $core       dotclear core instance.
     * @param string  $name       filterset name.
     * @param string  $action     form action.
     * @param string $form_prefix form prefix.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function __construct($core,$name,$action,$form_prefix="f_"){
		$this->form_prefix=$form_prefix;
		$this->filters = new ArrayObject();
		$this->static_filters = new ArrayObject();
		$this->all_filters = new ArrayObject();
		$this->action = $action;
		$this->filtered = false;
		parent::__construct($core,$name,$action,'POST');
		//$this->id = "filters";
	}


    /**
     * renderFilterSet -- binding to twig function "filterset"
     * 						renders a filterset given its name & context
     * @param mixed $env        Twig environment (passed by Twig template).
     * @param mixed $context    Context (passed by Twig template).
     * @param mixed $name       filterset name.
     * @param array $attributes filterset attributes.
     *
     * @access public
     * @static
     *
     * @return mixed Value.
     */
	public static function renderFilterSet($env,$context,$name,
										   $attributes=array())	{
		$context['filtersetname']=$name;
		echo $env->getExtension('dc_form')->renderWidget(
			'filterset',
			$context
		);
	}


    /**
     * setup - sets up the form filter from http context
     *
     * @access public
     *
     * @return mixed Value.
     */
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
				new dcFieldCombo ($p.'add_filter','',$form_combo,
					array()))
			->addField (
				new  dcFieldSubmit($p.'add',__('Add this filter'),
					array()))
			->addField (
				new dcFieldSubmit($p.'clear_filters',__('Delete all filters'),
					array()))
			->addField (
				new dcFieldSubmit($p.'apply',
					__('Apply filters and display options'),
					array()))
			->addField (
				new dcFieldSubmit($p.'reset',__('Reset'),
					array()))
		;
		$this->setupFields();
		/*  Since we have specific handling for actions
		    (for instance "del_*" fields), we do not take advantage of
		    submitfields features, actions are performed manually here.

			Use cases :
			(1) $_POST not empty for formfilter fields :
				* filters are set from $_POST
				* applied filters values are set from $_GET
				* keep filters div shown
			(2) $_POST empty :
				* both filters fields & applied values are set from $_GET
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
				// initialize fields from $_POST
				$this->setupEditFilters($this->all_filters,$_POST);
				if ($action == 'add'){
					// Add a new filter
					$fname = $p.'add_filter';
					if (isset($_POST[$fname])
						&& isset($this->filters[$_POST[$fname]])) {
						$this->filters[$_POST[$fname]]->add();
					}
					$this->hide_filterset = false;
				} elseif (strpos($action,'del_') === 0) {
					// Remove a filter
					$count = preg_match('#del_(.+)_([0-9]+)#',$action,$match);
					if (($count == 1) && isset($this->filters[$match[1]])) {
						$this->filters[$match[1]]->remove($match[2]);
					}
					$this->hide_filterset = false;
				} elseif ($action=="apply") {
					// Apply all filters
					// ==> store filter to preferences and redirect to
					//     page with filter as $_GET attributes
					$data = $this->saveFilters();
					$query = http_build_query($data,'','&');
					if ($query != '') {
						$query = (strpos($this->action,'?') === false ? '?' : '&').$query;
					}
					http::redirect($this->action.$query);
					exit;
				}
			}
			// Form as been submitted with POST method, retrieve
			// applied filter values from "query" field
			if (isset($_POST[$p."query"])) {
				parse_str($_POST[$p."query"],$out);
				$this->setupAppliedValues($this->all_filters,$out);
				if ($action == 'reset') {
					// RESET action => set fields from query field
					$this->setupEditFilters($this->all_filters,$out);
				} elseif ($action == 'clear_filters') {
					$this->setupEditFilters($this->static_filters,$out);
					foreach ($this->filters as $f) {
						$f->cleanup();
					}
				}
			}
		} else {
			// Use case (2) : GET method; set both filters and applied values
			// from GET args or from settings if no GET args.
			$load_from_settings = true;
			foreach($_GET as $k=>$v) {
				if (strpos($k,$this->form_prefix)===0) {
					$load_from_settings=false;
					break;
				}
			}
			$get = $_GET;
			if ($load_from_settings) {
				$get = array_merge($this->loadFilters(),$get);
			}
			$this->setupEditFilters($this->all_filters,$get);

			$this->setupAppliedValues($this->all_filters,$get);
		}
		foreach ($this->static_filters as $f) {
			if (!$f->isEnabled()) {
				$f->add();
			}
		}
		$queryParams = $this->getAppliedFilterValues();
		$this->addField(
			new dcFieldHidden($this->form_prefix.'query',
				http_build_query($queryParams)));
		// form context is set through a global twig variable
		$this->core->tpl->addGlobal(
			'filterset_'.$this->name,
			$this->getContext());
	}

	public function getURLParams() {
		return $this->getAppliedFilterValues()->getArrayCopy();
	}

    /**
     * saveFilters - save user defined filters into preferences
     *
     * @access protected
     */
	protected function saveFilters() {
		$ser = array();
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		$data= $this->serialize();
		$ws->put($this->name,serialize($data->getArrayCopy()),'string');
		return $data;
	}

    /**
     * loadFilters - load user filters from preferences
     *
     * @access protected
     *
     * @return mixed Value.
     */
	protected function loadFilters() {
		$ws = $GLOBALS['core']->auth->user_prefs->addWorkspace('filters');
		$data = (!is_null($ws->{$this->name}))
			? unserialize($ws->{$this->name})
			: array();
		if (is_array($data))
			return $data;
		else
			return array();
	}

    /**
     * setupEditFilters - Updates filters fields according to form_data
     * 					To be called before any call to display() or getForm()
     *
     * @param array $filters   list of filters to update
     * @param array $form_data form values (usually $_GET or $_POST)
     *
     * @access protected
     *
     * @return mixed Value.
     */
	protected function setupEditFilters ($filters,$form_data) {
		foreach ($filters as $filter) {
			$filter->setupFields ($form_data);
		}
	}

    /**
     * setupAppliedValues - Updates filters applied values according to
     * 						form_data
     *
     * @param array $filters   list of filters to update
     * @param array $form_data form values (usually $_GET or $_POST)
     *
     * @access protected
     *
     * @return mixed Value.
     */
	protected function setupAppliedValues ($filters,$form_data) {
		foreach ($filters as $filter) {
			$filter->setupAppliedValue ($form_data);
		}
	}

    /**
     * serialize - retrieves filter applied values in a serialized form
     *
     * @access protected
     *
     * @return ArrayObject serialized data.
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
     * addFilter - registers a new filter in filterset
     *
     * @param mixed \dcFilter the filter.
     *
     * @access public
     *
     * @return dcFilterSet the filterset (enabling to chain addFilter).
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

    /**
     * getContext - retrieves current filterset context
     * 				(to be given to twig template)
     *
     * @access public
     *
     * @return array the context
     */
	public function getContext() {
		$fcontext = new ArrayObject();
		$sfcontext = new ArrayObject();
		$afcontext = new ArrayObject();
		foreach ($this->filters as $f) {
			if($f->isEnabled()) {
				$f->appendFilterContext($fcontext);
			}
		}
		foreach ($this->static_filters as $f) {
			$f->appendFilterContext($sfcontext);
		}
		foreach ($this->filters as $f) {
			if ($f->isApplied()) {
				$afcontext[] = $f->getAppliedFilterText();
			}
		}
		return array(
			'active_filters' => $fcontext,
			'static_filters' => $sfcontext,
			'applied_filters' => $afcontext,
			'hide_filters'	 => $this->hide_filterset,
			'prefix'		 => $this->form_prefix);
	}

    /**
     * getAppliedFilterValues - retrieves the list of applied filter values
     *
     * @access protected
     *
     * @return ArrayObject the list of applied values
     */
	protected function getAppliedFilterValues() {
		$arr = new ArrayObject();
		foreach ($this->all_filters as $f) {
			if ($f->isApplied())
				$f->updateAppliedValues($arr);
		}
		return $arr;
	}

    /**
     * applyFilters -- applies filterset
     *
     * @param mixed $params the parameters to update.
     *
     * @access public
     *
     * @return mixed true if at least 1 filter has been applied, false otherwise
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

    /**
     * buildFieldName -- builds a field name given a verb, an id and a position
     * 					takes the form prefix into consideration
     * @param mixed $verb     the verb to use (ex  : "del")
     * @param mixed $field_id the field id
     * @param mixed $pos      the field position
     *
     * @access public
     *
     * @return mixed the field name
     */
	public function buildFieldName($verb,$field_id,$pos) {
		return $this->form_prefix.$verb.'_'.$field_id.'_'.$pos;
	}

}


/**
* dcFilter -- base filter class
*
*  A filter can be edited while being applied with other values
*  that enables to keep the list of applied filters (to display a list of items)
*  while modifing the filter itself.
*  Therefore it contains :
*   * a Field that tracks the currently edited filter
*   * an applied values that tracks the currently applied filter
*
*/
abstract class dcFilter  {
	/** @var dcFilterSet filterset parent */
	public $filterset;
	/** @var string filter id */
	public $id;
	/** @var string filter name */
	public $name;
	/** @var string filter description */
	public $desc;
	/** @var string filter id (including form prefix) */
	public $filter_id;
	/** @var string resulting parameter array key */
	protected $request_param;
	/** @var dcField edited field */
	protected $field;
	/** @var array currently applied values */
	protected $avalues;
	/** @var boolean true if field is static */
	protected $static;
	/** @var array generic field options */
	protected $options;
	/** @var boolean true if field can have multiple values */
	protected $multiple;

    /**
     * __construct -- filter constructor
     *
     * @param mixed $id            filter id.
     * @param mixed $name          filter name.
     * @param mixed $desc          filter description.
     * @param mixed $request_param request parameter (see dcBlog::getPosts for
     *                             instance).
     * @param array $options       filter options.
     *
     * @access public
     *
     * @return mixed Value.
     */
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
     * parseData - Extract values from data (data being an array, such as $_GET 
     * 				or $_POST) ; does not update any field, only return parsed
     * 				data
     *
     * @param mixed $data input data.
     *
     * @access protected
     *
     * @return array an array containing parsed data.
     */
	protected function parseData($data) {
		$arr = $this->field->parseValues($data);
		return array('values' => $arr);
	}

    /**
     * isStatic -- returns whether the filter is static or not
     *
     * @access public
     *
     * @return boolean true if the filter is static.
     */
	public function isStatic() {
		return $this->static;
	}

    /**
     * setupFields -- sets up filter fielt from $_GET or $_POST
     *
     * @param mixed $data input data (either $_GET or $_POST).
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function setupFields($data) {
		$this->field->setup($data);
	}


    /**
     * init -- initializes filter (called after setup)
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function init() {
	}

    /**
     * cleanup - resets filter field to its default value
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function cleanup() {
		$this->field->setValues(array());
	}

    /**
     * setupAppliedValue -- defines field applied values from data
     *
     * @param mixed $data data to retrieve values from ($_GET or $_POST).
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function setupAppliedValue($data) {
		$this->avalues = $this->parseData($data);
	}

	public function updateAppliedValues($arr) {
		$arr[$this->filter_id] = $this->avalues['values'];
	}

    /**
     * setFilterSet -- sets filterSet for filter
     *
     * @param mixed \dcFilterset Description.
     *
     * @access public
     *
     */
	public function setFilterSet(dcFilterset $fs) {
		$this->filterset = $fs;
	}

    /**
     * setFormPrefix -- sets filter form prefix
     * 
     * @param string $prefix the form prefix.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function setFormPrefix($prefix) {
		$this->filter_id = $prefix.$this->id;
	}

    /**
     * isEnabled -- Tells whether the filter is enabled or not (ie field has
     * 				at least 1 value defined)
     *
     * @access public
     *
     * @return mixed true if the filter is enabled.
     */
	public function isEnabled() {
		return count($this->field) != 0;
	}

	protected abstract function addValue($value=NULL);
	protected abstract function getAppliedFilterText();
	
    /**
     * add -- adds a value for the filter
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function add() {
		if (count($this->field) > 1 && !$this->multiple)
			return;
		$this->addValue();
	}

    /**
     * remove -- Removes a value from filter
     *
     * @param mixed $pos value position.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function remove($pos) {
		$values = $this->field->getValues();
		if (isset($values[$pos])) {
			$this->field->delValue($pos);
		}

	}

	abstract protected function appendContextLine($ctx,$pos);

    /**
     * appendFilterContext -- appends current filter context to the given 
     * 					context.
     * 	A filter context consists in a list of array elements, one for
     * 	each field line displayed.
     * 	If a field has multiple values, there will be as many lines as values
	 *
     *  The twig template then iterates through the array to display
     *  each and every line
     * @param mixed $ctx the context to enrich
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function appendFilterContext($ctx) {
		foreach ($this->field->getValues() as $cur => $f) {
			/*
			* each line of context has the following properties :
			*  * lineclass : <tr> class to use
			*  * 'del_id' : delete input field name to delete current value
			*  * other field-type specific values that are set from 
			*  		appendContextLine method
			 */
			$line = new ArrayObject();
			$line['lineclass'] = $this->id;
			$line['del_id'] = $this->filterset->buildFieldName('del',$this->id,$cur);
			// Create the delete field for this line
			$del = new dcFieldSubmit(
				$this->filterset->buildFieldName('del',$this->id,$cur),
				'-',
				array(
					'attr' => array(
						'title' => __('Delete the following filter')))
			);
			$this->filterset->addField($del);
			$this->appendContextLine($line,$cur);
			$ctx[]=$line;
		}
	}

    /**
     * serialize - serializes field value into given array
     * 
     * @param mixed $arr the context to update.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function serialize($arr) {
		if (count($this->fields) == 1) {
			$arr[$this->filter_id]=$this->field->getValue();
		} else {
			$arr[$this->filter_id]=$this->field->getValues();
		}
	}

    /**
     * isApplied -- returns true when the filter is applied
     * 	(ie. has at least 1 applied value)
     * @access public
     *
     * @return mixed Value.
     */
	public function isApplied(){
		return (count($this->avalues['values']) != 0);
	}

    /**
     * applyFilter -- Converts filter values into a $param filter, used for the
     * 				 upcoming SQL request
     *
     * @param mixed $params Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function applyFilter($params) {
		return false;
	}

    /**
     * header -- tbd
     * 
     * @access public
     *
     * @return mixed Value.
     */
	public function header() {
		return '';
	}

    /**
     * getFields -- returns filter field(s)
     * 
     * @access public
     *
     * @return dcField the filter field.
     */
	public function getFields() {
		return $this->field;
	}
}

/**
* dcFilterText - basic single field text filter
*
* @uses     dcFilter
*
*/
class dcFilterText extends dcFilter {

    /**
     * @see dcFilter::init()
     */
	public function init() {
		$this->field = new dcFieldText(
			$this->filter_id,
			NULL);
		$this->filterset->addField($this->field);
		$this->multiple = false;
	}


    /**
     * @see dcFilter::appendContextLine()
     */
	public function appendContextLine($line,$pos) {
		/*
		Extra data provided by this filter :
		* ffield : field name
		* display_inline : true if the field is static
		* fwidget : name of the widget (filter_text)
		* desc : filter description
		 */
		$line['ffield'] = $this->field->getName();
		if ($this->static) {
			$line['display_inline'] = true;
		}

		if ($pos == 0) {
			$line['fwidget']='filter_text';
			$line['desc']=$this->desc;
		};
	}

    /**
     * @see dcFilter::addValue()
     */
    protected function addValue($value=NULL) {
		if ($value === NULL) {
			$value = '';
		}
		$this->field->addValue($value);
	}

    /**
     * @see dcFilter::applyFilter()
     */
	public function applyFilter($params) {
		$params[$this->request_param]=$this->avalues['values'][0];
	}
	
	public function getAppliedFilterText() {
		if ($this->isApplied()) {
			return sprintf(__('%s contains : "%s"'),$this->desc,$this->avalues['values'][0]);
		}
	}
}

/**
* dcFilterCombo -- combo filter
*
* Enables to filter through a list of values, can be used to check
* if a value is in a list of items
*
* @uses     dcFilter
*
*/
class dcFilterCombo extends dcFilter {
	/** @var combo the list of possible values in combo */
	protected $combo;
	
    /**
     * @see dcFilter::__construct()
     */
	public function __construct($id,$name,$desc,$request_param,$combo,
								$options=array()) {
		parent::__construct($id,$name,$desc,$request_param,$options);
		$this->combo = $combo;
	}

    /**
     * @see dcFilter::init()
     */
	public function init() {
		$this->field = new dcFieldCombo(
			$this->filter_id,
			NULL,
			$this->combo,array(
				'multiple' => $this->multiple));
		$this->filterset->addField($this->field);
	}

    /**
     * @see dcFilter::addValue()
     */
	protected function addValue($value=NULL) {
		if ($value === NULL) {
			$value = current(array_keys($this->combo));
		}
		$this->field->addValue($value);
	}

    /**
     * @see dcFilter::init()
     */
	public function appendContextLine($line,$pos) {
		/*
		Extra data provided by this filter :
		* ffield : field name
		* display_inline : true if the field is static
		* fwidget : name of the widget (filter_combo or filter_combo_cont)
		* foffset : field value offset
		* desc : filter description
		Only the 1st item contains description.
		 */
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

    /**
     * @see dcFilter::applyFilter()
     */
	public function applyFilter($params) {
		$attr = $this->request_param;
		if ($this->multiple)
			$params[$attr]=$this->avalues['values'];
		else
			$params[$attr]=$this->avalues['values'][0];
	}
	public function getAppliedFilterText() {
		if ($this->isApplied()) {
			if (count($this->avalues['values'])) {
				return sprintf(__("%s is %s"),$this->desc,join(__(' OR '), $this->avalues['values']));
			} else {
				return sprintf(__('%s is %s'),$this->desc,$this->avalues['values'][0]);
			}
		}
	}
}

/**
* dcFilterRichCombo -- rich combo filter
*
* Same as dcFilterCombo, with the possibility to exclude a list of values
*
* @uses     dcFilter
*
*/
class dcFilterRichCombo extends dcFilterCombo {
	/** @var verb verb field ('is' or 'is not') */
	protected $verb;

    /**
     * @see dcFilter::init()
     */
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

    /**
     * @see dcFilter::parseData()
     */
	protected function parseData($data) {
		$val = parent::parseData($data);
		$v = $this->verb->parseValues($data);
		if (isset($v[0]) && $v[0] === 'isnot')
			$val['verb'] = 'isnot';
		else
			$val['verb'] ='is';
		return $val;
	}

    /**
     * @see dcFilter::setupFields()
     */
	public function setupFields($data) {
		parent::setupFields($data);
		$this->verb->setup($data);
	}

    /**
     * @see dcFilter::updateAppliedValues()
     */
	public function updateAppliedValues($arr) {
		parent::updateAppliedValues($arr);
		$arr[$this->verb->getName()] = $this->verb->getValue();
	}

    /**
     * @see dcFilter::appendContextLine()
     */
	public function appendContextLine($line,$pos) {
		parent::appendContextLine($line,$pos);
		if ($pos == 0) {
			$line['fverb'] = $this->verb->getName();
			$line['fwidget']='filter_richcombo';
		}
	}

    /**
     * @see dcFilter::serialize()
     */
	public function serialize($arr) {
		parent::serialize($arr);
		$arr[$this->filter_id.'_v']=$this->verb->getValue();
	}

    /**
     * @see dcFilter::applyFilter()
     */
	public function applyFilter($params) {
		parent::applyFilter($params);
		$attr = $this->request_param;
		if ($this->avalues['verb'] != "is") {
			$params[$attr."_not"] = true;
		}
	}
	public function getAppliedFilterText() {
		if ($this->isApplied()) {
			if ($this->avalues['verb'] == "is") {
				$txt = __("%s is %s");
				$or = __(' or ');
			} else {
				$txt = __("%s is not %s");
				$or = __(' nor ');
			}
			$texts = array();
			foreach ($this->avalues['values'] as $v) {
				$texts[] = $this->field->getTextForValue($v);
			}
			if (count($texts)>=1) {
				return sprintf($txt,$this->desc,join($or, $texts));
			} else {
				return sprintf($txt,$this->desc,$texts);
			}
		}
	}

}

// Static initializer
dcFilterSet::__init__($GLOBALS['core']->tpl);
?>