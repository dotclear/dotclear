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
if (!defined('DC_RC_PATH')) { return; }

/**
* dcFormNode Twig Node for Form handling
*
* @uses     Twig_Node
*
*/
class dcFormNode extends Twig_Node
{
	public function __construct($name,Twig_NodeInterface $body,$attr,$lineno,$tag=null)
	{
		parent::__construct(array('body' => $body),array('name' => $name, 'attr' => $attr),$lineno,$tag);
	}
	
	/**
	* Compiles the node to PHP.
	*
	* @param Twig_Compiler A Twig_Compiler instance
	*/
	public function compile(Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this);
		$compiler
			->write("\$context['dc_form']->beginForm(")
			->subcompile($this->getAttribute('name'));
		if ($this->getAttribute('attr') !== null) {
			$compiler
				->write(',')
				->subcompile($this->getAttribute('attr'));
		}
		$compiler
			->write(");\n");
		$compiler
			->subcompile($this->getNode('body'))
			->write("\$context['dc_form']->renderHiddenWidgets();\n")
			->write("\$context['dc_form']->endForm();\n")
		;
	}
}

/**
 * Template form token parser
 */
class dcFormTokenParser extends Twig_TokenParser
{
    /**
     * parse - parses form tag
     * General syntax is :
     *  {% form 'formname' %}
     *  ... {{ form_field (...)}}
     *  {% endform %}
     * Specific attributes can be passed to the form, enabling to set
     * attributes to the form template :
     * {% form 'formname' with {'id':'myform'} %}
     * @param mixed \Twig_Token Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function parse(Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$name = $this->parser->getExpressionParser()->parseExpression();
		$attr = null;
		/* parse optional context */
		if ($stream->test(Twig_Token::NAME_TYPE, 'with')) {
			$stream->next();
			$attr = $this->parser->getExpressionParser()->parseExpression();
		}
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse(array($this,'decideBlockEnd'),true);
		$stream->expect(Twig_Token::BLOCK_END_TYPE);

		return new dcFormNode($name,$body,$attr,$token->getLine(),$this->getTag());
	}

	public function decideBlockEnd(Twig_Token $token)
	{
		return $token->test('endform');
	}

	public function getTag()
	{
		return 'form';
	}
}

/**
 * Template form extension
 */
class dcFormExtension extends Twig_Extension
{
	protected $template;
	protected $tpl;
	protected $core;
	protected $twig;
	protected $forms;
	protected $currentForm;
	protected $blocks;

	public function __construct($core)
	{
		$this->core = $core;
		$this->tpl = array('@forms/form_layout.html.twig');
		$this->forms = array();
		$this->blocks = array();
		$this->currentForm = null;
	}

	public function initRuntime(Twig_Environment $environment)
	{
		$this->twig = $environment;
		$this->twig->getLoader()->addPath(dirname(__FILE__).'/default-templates/forms','forms');
		foreach ($this->tpl as $tpl) {
			$this->template = $this->twig->loadTemplate($tpl);
			$this->blocks = array_merge($this->blocks,$this->template->getBlocks());
		}
	}

	public function addTemplate($tpl) {
		$this->tpl[]=$tpl;
		if (isset($this->twig)) {
			$this->template = $this->twig->loadTemplate($tpl);
			$this->blocks = array_merge($this->blocks,$this->template->getBlocks());
		}
	}

	public function getGlobals()
	{
		return array('dc_form' => $this);
	}

	public function getFunctions()
	{
		return array(
			new Twig_SimpleFunction(
				'widget',
				array($this,'renderWidget'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'haswidget',
				array($this,'hasWidget'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'form_field',
				array($this,'renderField'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'form_hidden',
				array($this,'renderHiddenWidgets'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'form_field_attr',
				array($this,'getFieldAttributes'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'_form_is_choice_group',
				array($this,'isChoiceGroup'),
				array('is_safe' => array('html'))
			),
			new Twig_SimpleFunction(
				'_form_is_choice_selected',
				array($this,'isChoiceSelected'),
				array('is_safe' => array('html'))
			)
		);
	}

    /**
     * isChoiceGroup - binding for twig function "_form_is_choice_group"
     * 					returns whether a choice is a group or not
     * @param mixed $choice the choice.
     *
     * @access public
     *
     * @return boolean true is choice is a group (optgroup).
     */
	public function isChoiceGroup($choice)
	{
		return is_array($choice);
	}

    /**
     * isChoiceSelected - binding for twig function "_form_is_choice_selected"
     * 					returns whether current choice matches a value or not
     * @param mixed $choice the choixe.
     * @param mixed $value  the value to check matching.
     *
     * @access public
     *
     * @return boolean if choice is matching the value.
     */
	public function isChoiceSelected($choice,$value)
	{
		return $choice == $value;
	}

    /**
     * getTokenParsers returns token parsers
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function getTokenParsers()
	{
		return array(new dcFormTokenParser());
	}

    /**
     * hasWidget - binding for twig "haswidget" function
     * 	returns whether a widget is defined or not
     * 
     * @param mixed $name the widget name.
     *
     * @access public
     *
     * @return boolean true if the widget exists.
     */
	public function hasWidget($name) {
		return isset($this->blocks[$name]);
	}

    /**
     * renderWidget - binding for 'widget' twig function
     * behaves exactly like "block" function, except that a context
     * can be passed to the function
     * 
     * @param mixed $name the widget (block) name to render.
     * @param mixed $attr Description the context for this block.
     *
     *
     * @return mixed Value.
     */
	public function renderWidget($name,$attr) {
		if (!isset($this->blocks[$name]))
			return '';
		echo $this->template->renderBlock(
			$name,
			$attr,
			$this->blocks
		);
	}

    /**
     * getCurrentForm - returns current form if called within a {% form %} tag
     *
     * @access public
     *
     * @return string the current form.
     */
	public function getCurrentForm() {
		return $this->currentForm;
	}

    /**
     * renderField - binding for 'form_field' twig function; renders a field
     *
     * @param mixed $name       field name as defined on php side.
     * @param array $attributes html attributes for field (ex : class, ...).
     * @param array $extra      extra attributes that may be template specific.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function renderField($name,$attributes=array(),$extra=array())
	{
		$field = $this->currentForm->getField($name);
		if ($field) {
			$attr = $field->getAttributes($attributes);
			if (isset($attr['attr'])) {
				$attr['attr'] = array_merge($attr['attr'],$attributes);
			} else {
				$attr['attr'] = $attributes;
			}
			$this->renderWidget(
			$field->getWidgetBlock(),
				array_merge(
					$attr,
					$extra
				)
			);
		}
	}
	
    /**
     * getFieldAttributes - binding for 'form_field_attr' twig function; returns all field attributes
     *
     * @param mixed $name       field name as defined on php side.
     * @param mixed $name       the attribute name, null to grab all attributes as an array
     *
     * @access public
     *
     * @return array the field attributes
     */	
	public function getFieldAttributes($name,$attr=null)
	{
		$field = $this->currentForm->getField($name);
		if ($field) {
			$a = $field->getAttributes();
			if ($attr !== null) {
				if (isset($a[$attr])) {
					return $a[$attr];
				} else {
					return null;
				}
			} else {
				return $field->getAttributes();
			}
		} else {
			return array();
		}
	}

    /**
     * renderHiddenWidgets -- renders all form hidden fields
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function renderHiddenWidgets()
	{	
		if ($this->currentForm->areHiddenfieldsDisplayed()) {
			return;
		}
		foreach ($this->currentForm->getHiddenFields() as $h) {
			$this->renderField($h->getName());
		}
		$this->currentForm->setHiddenfieldsDisplayed();
	}

	public function getName()
	{
		return 'dc_form';
	}

    /**
     * addForm -- registers a new form
     *
     * @param mixed \dcForm Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function addForm(dcForm $form)
	{
		$this->forms[$form->getName()] = $form;
	}

    /**
     * beginForm -- displays form beginning
     * 
     * @param mixed $name form name.
     * @param array $attr extra attributes.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function beginForm($name,$attr=array())
	{
		if (isset($this->forms[$name])) {
			$this->currentForm = $this->forms[$name];
			$this->currentForm->begin($attr);
		}
		else {
			throw new Twig_Error_Runtime(sprintf(
				'Form "%s" does not exist',
				$name
			));
		}
	}

    /**
     * endForm -- displays form ending
     * 
     * @access public
     *
     * @return mixed Value.
     */
	public function endForm()
	{
		$this->currentForm->end();
		$this->currentForm = null;
	}
}

/**
 * Template form exception
 */
class InvalidFieldException extends Exception {
}

/**
* dcForm - Template form
*
*/
class dcForm
{
	/** @var string form id */
	protected $id;
	/** @var string form name */
	protected $name;
	/** @var dcCore dcCore instance */
	protected $core;
	/** @var string form action */
	protected $action;
	/** @var array(dcField) list of form fields */
	protected $fields;
	/** @var string form method (GET/POST) */
	protected $method;
	/** @var array(dcField) list of submit fields */
	protected $submitfields;
	/** @var array(dcField) list of hidden fields */
	protected $hiddenfields;
	/** @var array(dcField) list of form errors */
	protected $errors;
	/** @var array() list of form properties */
	protected $properties;
	
	protected $hiddendisplayed;
	
    /**
     * Class constructor
     * 
     * @param mixed  $core   dotclear core
     * @param mixed  $name   form name - can be an array (name,id)
     * @param mixed  $action form action
     * @param string $method form method ('GET' or 'POST')
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function __construct($core,$name,$action,$method='POST')
	{
		$this->core = $core;
		$this->setNID($name);
		$this->method = $method;
		$this->action = $action;
		$this->fields = array();
		$this->core->tpl->getExtension('dc_form')->addForm($this);
		$this->submitfields = array();
		$this->hiddenfields = array();
		$this->errors = array();
		$this->properties = array();
		if ($method == 'POST') {
			$this->addNonce();
		}
		$this->hiddendisplayed = false;
	}

	/**
     * areHiddenFieldsDisplayed - tells whether hidden fields have been rendered or not
     * 
     * @return boolean true if hidden fields have already been displayed, false otherwise
     * @access public
     */
	public function areHiddenFieldsDisplayed() {
		return $this->hiddendisplayed;
	}
	
	/**
     * setHiddenFieldsDisplayed - sets whether hidden fields have been rendered or not
     * 
     * @param boolean true (default) if hidden fields are to be set as displayed, false otherwise
     * @access public
     */
	public function setHiddenFieldsDisplayed($value=true) {
		$this->hiddendisplayed = $value;
	}
	
    /**
     * setProperty - sets form property
     * 
     * @param string $name the property name.
     * @param mixed $value the property value.
     *
     * @access public
     */
	public function setProperty($prop,$value) {
		$this->properties[$prop]=$value;
	}
	
    /**
     * getProperty - gets form property
     * 
     * @param string $name the property name.
	 *
     * @return mixed the property value, null if no property found.
     * @access public
     */	
	public function getProperty($prop) {
		if (isset($this->properties[$prop])) {
			return $this->properties[$prop];
		} else {
			return null;
		}
	}
    /**
     * addTemplate - Adds a template file to enrich form fields
     * 
     * @param string $t the template file.
     *
     * @access public
     */
	public function addTemplate($t) {
		$this->core->tpl->getExtension('dc_form')->addTemplate($t);
	}

    /**
     * addNonce -- adds dc nonce to form fields
     *
     * @access protected
     *
     * @return nothing
     */
	protected function addNonce()
	{
		$this->addField(
			new dcFieldHidden(array('xd_check'),
			$this->core->getNonce())
		);
	}

    /**
     * Defines Name & ID from field
     *
     * @param mixed $nid either an array (name, id) or a string
     *                   (name only, id will be set to null).
     *
     * @access protected
     *
     * @return nothing.
     */
	protected function setNID($nid)
	{
		if (is_array($nid)) {
			$this->name = $nid[0];
			$this->id = !empty($nid[1]) ? $nid[1] : null;
		}
		else {
			$this->id = null;
			$this->name = $nid;
		}
	}

    /**
     * getContext - returns form context (to fill-in twig context for instance),
     * 				if any
     *
     * @access public
     *
     * @return array the form context.
     */
	public function getContext() {
		return array();
	}


    /**
     * Returns form name
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function getName()
	{
		return $this->name;
	}

    /**
     * getErrors - returns form errors
     *
     * @access public
     *
     * @return array the list of errors.
     */
	public function getErrors()
	{
		return $this->errors;
	}

    /**
     * addField - adds a new field to form
     *
     * @param mixed \dcField the field to add.
     *
     * @access public
     *
     * @return dcForm the form instance (therefore addField can be chained)
     */
	public function addField(dcField $f)
	{
		if ($f instanceof dcFieldAction) {
			$this->submitfields[$f->getName()] = $f;
		}
		if ($f instanceof dcFieldHidden) {
			$this->hiddenfields[$f->getName()] = $f;
		}
		$this->fields[$f->getName()] = $f;

		return $this;
	}

    /**
     * getField - retrieves a field form form
     *
     * @param string the field name
     *
     * @access public
     *
     * @return dcForm the requested field
     */	
	 public function getField($name) {
		if (isset($this->fields[$name])) {
			return $this->fields[$name];
		} else {
			return null;
		}
	}
	
    /**
     * removeField - removes a field
     *
     * @param mixed \dcField the field to remove.
     *
     * @access public
     *
     * @return dcForm the form instance (therefore addField can be chained)
     */
	public function removeField(dcField $f) {
		$n = $f->getName();
		if (isset($this->fields[$n])){
			unset($this->fields[$n]);
		}
		return $this;
	}


    /**
     * renameField - renames a field
     *
     * @param mixed $field   the field to rename.
     * @param mixed $newname new field name
     *
     * @access public
     *
     *
     * @return dcForm the form instance (therefore addField can be chained)
     */
	public function renameField($field,$newname) {
		$oldname = $field->getName();
		if (isset($this->fields[$oldname])) {
			unset($this->fields[$oldname]);
			$field->setName($newname);
			$this->fields[$newname] = $field;
		}
		return $this;
	}

    /**
     * begin - begins a form. Should be not be called directly, it is handled
     * 			by the Twig Form extension.
     *
     * @param array $attr form extra attributes, if any.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function begin($attr=array())
	{
		$attr['method'] = $this->method;
		$attr['action'] = $this->action;
		if (!empty($this->id)) {
			$attr['id'] = $this->id;
		}
		$this->core->tpl->getExtension('dc_form')->renderWidget(
			'beginform',
			$attr);
	}

    /**
     * end - ends a form. Should be not be called directly, it is handled
     * 			by the Twig Form extension.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function end($attr=array())
	{
		$this->core->tpl->getExtension('dc_form')->renderWidget(
			'endform',$attr);
	}

    /**
     * __isset - magic method isset, checks whether a field exists
     * 				example : if (isset($form->field1))
     *
     * @param mixed $name field name to check.
     *
     * @access public
     *
     * @return boolean true if the field exists.
     */
	public function __isset($name)
	{
		return isset($this->fields[$name]);
	}

    /**
     * __get -- magic method, retrieves a field from a form
     * 			example : $f = $form->field1
     *
     * @param mixed $name Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function __get($name)
	{
		if (isset($this->fields[$name])) {
			$f = $this->fields[$name];
			if ($f->isMultiple()) {
				return $f->getValues();
			} else {
				return $f->getValue();
			}
		} else {
			return $this->getProperty($name);
		}
	}

    /**
     * __set -- magic method, sets a value for a given form field
     * 			example : $form->field1 = 'my value';
     *
     * @param mixed $name  the field name.
     * @param mixed $value the field value.
     *
     * @access public
     */
	public function __set($name,$value)
	{
		if (isset($this->fields[$name])) {
			$f = $this->fields[$name];
			if ($f->isMultiple()) {
				$this->fields[$name]->setValues($value);
			} else {
				$this->fields[$name]->setValue($value);
			}
		} else {
			$this->setProperty($name,$value);
		}
	}

    /**
     * setupFields - initializes form & fields from $_GET or $_POST
     * 
     * @access protected
     */
	protected function setupFields() {
		$from = $this->method == 'POST' ? $_POST : $_GET;
		if (!empty($from)) {
			foreach ($this->fields as $f) {
				$f->setup($from);
			}
		}
	}

    /**
     * handleActions - handle appropriate actions, according to submitted fields
     * 
     * @param mixed $submitted the fields that have been submitted.
     *
     * @access protected
     */
	protected function handleActions($submitted) {
		$hasActions = false;
		foreach ($submitted as $f) {
			$action = $f->getAction();
			if ($action != NULL && is_callable($action)) {
				$hasActions = true;
				$ret = call_user_func($action,$this);
			}
		}
	}

    /**
     * getSubmittedFields - retrieves fields that have been submitted, if any
     *
     * @access protected
     *
     * @return array the list of submitted fields.
     */
	protected function getSubmittedFields() {
		$s = array();
		foreach ($this->submitfields as $f) {
			if ($f->isDefined()) {
				$s[$f->getName()] = $f;
			}
		}
		return $s;
	}

    /**
     * isSubmitted - returns whether form has been submitted or not
     *
     * @access public
     *
     * @return boolean true if the form has been submitted.
     */	
	public function isSubmitted() {
		foreach ($this->submitfields as $f) {
			if ($f->isDefined()) {
				return true;
			}
		}
		return false;		
	}
	
    /**
     * setup - sets up the form, given the parameters given to the page
     * 			should be called after fields have been defined.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function setup()
	{
		$this->setupFields();
		$submitted = $this->getSubmittedFields();
		$this->handleActions($submitted);
	}

    /**
     * check - checks if the form is valid, errors are filled in, in case of
     * 			incorrect fields
     *
     * @access public
     */
	public function check(dcAdminContext $ctx)
	{
		$valid = true;
		foreach ($this->fields as $f) {
			try {
				$f->check();
			}
			catch (InvalidFieldException $e) {
				$valid = false;
				$ctx->addError($e->getMessage());
			}
		}
		if (!$valid) {
			throw new InvalidFieldException ("Some fields are missing");
		}
	}

	public function getFieldIDs() {
		return array_keys($this->fields);
	}
	
    /**
     * getHiddenFields - returns the list of hidden fields
     *
     * @access public
     *
     * @return array the list of hidden fields.
     */
	public function getHiddenFields()
	{
		return $this->hiddenfields;
	}
}

/**
 * Template form field
 */
abstract class dcField implements Countable
{
	/** @var string field options */
	protected $options;
	/** @var string field name */
	protected $name;
	/** @var string field values */
	protected $values;
	/** @var string field id */
	protected $id;
	/** @var boolean true if field can contain multiple values */
	protected $multiple;
	/** @var boolean true if the field has been defined */
	protected $defined;

    /**
     * __construct - constructor
     *
     * @param string $name   Name or array(name,id) for field.
     * @param array $values  field values.
     * @param array $options options
     *
     * Currently globally available options are :
     *  * multiple : true/false. Enable multiple values for field
     * @access public
     *
     * @return mixed Value.
     */
	public function __construct($name,$values,$options=array())
	{
		$this->setNID($name);
		$this->options = new ArrayObject($options);
		if ($values === NULL){
			$values = array();
		}
		$this->setValues($values);
		$this->defined = false;
		$this->multiple = (isset($options['multiple']) && $options['multiple']);

	}

    /**
     * defines whether a field is multiple or not
     *
     * @param boolean true if the field is multiple
	 *
     * @access public
     */
	public function setMultiple($m=true) {
		$this->multiple = $m;
	}
	
	    /**
     * Returns whether can have multiple values or not
     *
     * @return boolean true if the field has multiple values
	 *
     * @access public
     */
	public function isMultiple($m=true) {
		return $this->multiple;
	}

    /**
     * setNID - defines fiels name & id
     *
     * @param mixed $nid field name (string) or an array containing  name (1st)
     *                   and id (2nd field).
     *
     * @access protected
     */
	protected function setNID($nid)
	{
		if (is_array($nid)) {
			$this->name = $nid[0];
			$this->id = !empty($nid[1]) ? $nid[1] : null;
		}
		else {
			$this->id = $this->name = $nid;
		}
	}

    /**
     * setValue - sets field value
     *
     * @param mixed $value  field value.
     * @param int   $offset value offset to define (default 0).
     *
     * @access public
     */
	public function setValue($value,$offset=0) {
		$this->values[$offset] = $value;
	}

    /**
     * setValues - set field values
     *
     * @param mixed $values the array of values. If not an array, the parameter
     *                      will be converted to an array containing the value
     *
     * @access public
     */
	public function setValues($values) {
		if (is_array($values)) {
			$this->values = $values;
		} elseif ($values !== NULL) {
			$this->values = array($values);
		}

	}

    /**
     * getValues - return field values
     *
     * @access public
     *
     * @return mixed the array of values.
     */
	public function getValues() {
		return $this->values;
	}

    /**
     * getValue - retrieves a field value
     *
     * @param int $offset value offset (by default 1st value is returned).
     *
     * @access public
     *
     * @return mixed the field value.
     */
	public function getValue($offset=0) {
		if (isset($this->values[$offset])) {
			return $this->values[$offset];
		} else {
			return NULL;
		}
	}

    /**
     * addValue -- Adds a value to the field values.
     * 
     * @param mixed $value Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function addValue($value) {
		$this->values[] = $value;
	}
	public function delValue($offset) {
		if (isset($this->values[$offset])) {
			array_splice($this->values,$offset,1);
		}
	}

    /**
     * count -- returns the number of field values
     * 
     * @access public
     *
     * @return integer the number of values.
     */
	public function count() {
		return count($this->values);
	}

	public function __toString()
	{
		return join(',',$this->values);
	}
	
	abstract public function getWidgetBlock();
	
	public function isEmpty() {
		return (count($this->values) == 0) || empty($this->values[0]);
	}

    /**
     * getAttributes - retrieve field attributes that will be given to the
     * 				twig widget
     *
     * @param array $options extra options given to the widget
     *
     * @access public
     *
     * @return array the attributes.
     */
	public function getAttributes($options=array())
	{
		$offset = 0;
		$attr = $this->options->getArrayCopy();
		if (isset($options['offset'])) {
			$offset = $options['offset'];
			unset($attr['offset']);
		}
		
		$attr['value'] = $this->getValue($offset);
		if ($attr['value'] == NULL) {
			$attr['value']= $this->getDefaultValue();
		}
		if ($offset==0 && !empty($this->id)) {
			$attr['id']=$this->id;
		}
		$attr['name'] = $this->name;
		if ($this->multiple) {
			$attr['name'] = $attr['name'].'[]';
		}
		return $attr;
	}

    /**
     * getDefaultValue - returns field default value
     * 
     * @access public
     *
     * @return mixed the field default value.
     */
	public function getDefaultValue() {
		return '';
	}

    /**
     * getName - returns field name
     * 
     * @access public
     *
     * @return string the field name.
     */
	public function getName()
	{
		return $this->name;
	}

    /**
     * setName - defines field name and/or field id
     * 
     * @param mixed $name the field name or an array containing name and id.
     *
     * @access public
     */
	public function setName($name) {
		$this->setNID($name);
	}

    /**
     * check - checks whether the field is valid or not - raises an exception 
     * 			if not valid
     * @access public
     */
	public function check()
	{
		if (isset($this->options ['required']) && $this->options['required']) {
			if (!$this->defined || $this->isEmpty()) {
				throw new InvalidFieldException(sprintf(
					'Field "%s" is mandatory',
					$this->options['label'])
				);
			}
		}
	}

    /**
     * parseValues - parses field value from context (GET or POST)
     * 				and returns parsed value(s)
     * 				NOTE : the field is not updated with this method
     * 				use setup() to also update the field.
     * @param mixed $from the context (usually $_GET or $_POST).
     *
     * @access public
     *
     * @return array the list of values (empty array if no value).
     */
	public function parseValues($from) {
		if (isset($from[$this->name])) {
			$n = $from[$this->name];
			if (!is_array($n)) {
				$n = array($n);
			}
			return $n;
		}
		return array();
	}

    /**
     * setup - sets up the field from conetxt (GET or $POST)
     *
     * @param mixed $from the context (usually $_GET or $_POST).
     *
     * @access public
     *
     * @return mixed Value.
     */
	public function setup($from)
	{
		$values = $this->parseValues($from);
		if (count($values)) {
			$this->setValues($values);
			$this->defined = true;
		}
	}

    /**
     * isDefined - returns whether the field is defined or not
     * 				(a field is defined if some values are set after setup())
     * @access public
     *
     * @return mixed Value.
     */
	public function isDefined()
	{
		return $this->defined;
	}
}


/**
 * Template form field of type "password"
 */
class dcFieldPassword extends dcField
{
	public function getWidgetBlock()
	{
		return "field_password";
	}
}

/**
 * Template form field of type "text"
 */
class dcFieldText extends dcField
{
	public function getWidgetBlock()
	{
	return "field_text";
	}
}

/**
 * Template form field of type "textarea"
 */
class dcFieldTextArea extends dcField
{
	public function getWidgetBlock()
	{
		return "field_textarea";
	}
}

/**
 * Template form field of type "hidden"
 */
class dcFieldHidden extends dcField
{
	public function getWidgetBlock()
	{
		return "field_hidden";
	}
}

/**
 * Template form field of type "checkbox"
 */
class dcFieldCheckbox extends dcField
{
	//protected $checked;
	
	public function __construct($name,$values,$options=array())
	{
		$val = array();
		if (!is_array($values) && $values !== null) {
			$values = array("1" => !empty($values));
		}		
		parent::__construct($name,$values,$options);
	}

    /**
     * setValue - sets field value
     *
     * @param mixed $value  field value.
     * @param int   $offset value offset to define (default 0).
     *
     * @access public
     */
	public function setValue($value,$offset=0) {
		$keys = array_keys($this->values);
		if (isset($keys[$offset])) {
			$this->values[$keys[$offset]] = $value;
		}
	}
	
	public function addValue ($value,$checked=false) {
		$this->values[$value] = $checked;
	}

	public function getValue($offset=0) {
		$keys = array_keys($this->values);
		if (isset($keys[$offset])) {
			return $this->values[$keys[$offset]];
		} else {
			return false;
		}
	}
	
	public function getAttributes($options=array())
	{
		$offset = 0;
		if (isset($options['offset'])) {
			$offset = $options['offset'];
		}
		$a = parent::getAttributes($options);
		$keys = array_keys($this->values);
		if (isset( $keys[$offset])) {
			$val = $keys[$offset];
			$a['value'] = $val;
			if (isset($this->values[$val]) && $this->values[$val]) {
				$a['checked']='checked';
			}
		}
		
		return $a;
	}

	public function parseValues($from) {
		$val = parent::parseValues($from);
		$arr = $this->values;
		foreach ($arr as $k=>&$v) {
			$v=false;
		}
		foreach ($val as $v) {
			if (isset($arr[$v])) {
				$arr[$v]=true;
			}
		}
		return $arr;
	}


	public function getWidgetBlock()
	{
		return "field_checkbox";
	}

	public function getDefaultValue() {
		return false;
	}
}

/**
 * Template form action
 */
abstract class dcFieldAction extends dcField
{
	protected $action;

	public function __construct($name,$values,$options=array())
	{
		parent::__construct($name,$values,$options);

		if (isset($options['action'])) {
			$this->action = $options['action'];
		} else {
			$this->action = NULL;
		}
	}

	public function getAction()
	{
		return $this->action;
	}
}

/**
 * Template form field of type "submit"
 */
class dcFieldSubmit extends dcFieldAction
{
	public function getWidgetBlock()
	{
		return "field_submit";
	}
}

/**
 * Template form field of type "combo"
 */
class dcFieldCombo extends dcField
{
	protected $combo;
	protected $combo_values;

	public function __construct($name,$value,$combo,$options=array())
	{
		$this->combo = $combo;
		$this->combo_values = $combo;
		foreach ($combo as $k=>$v) {
			if (is_array($v)) {
				unset($this->combo_values[$k]);
				$this->combo_values = array_merge($v,$this->combo_values);
			}
		}
		parent::__construct($name,$value,$options);
	}

	public function getWidgetBlock()
	{
		return "field_combo";
	}

	public function getDefaultValue() {
		return current(array_keys($this->combo_values));
	}

	public function parseValues($from) {
		$values = parent::parseValues($from);
		if (!is_array($values)) {
			$values = array($values);
		}
		foreach ($values as &$v) {
			if (!isset($this->combo_values[$v])) {
				$v = $this->getDefaultValue();
			}
		}
		return $values;
	}

	public function getTextForValue($value) {
		if (isset($this->combo_values[$value])) {
			return $this->combo_values[$value];
		} else {
			return false;
		}
	}
	public function getAttributes($options=array()) {
		$attr = parent::getAttributes($options);
		$attr['options'] = $this->combo;
		return $attr;
	}
}

?>