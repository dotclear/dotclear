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
* dcFormNode
*
* @uses     Twig_Node
*
*/
class dcFormNode extends Twig_Node
{
	public function __construct($name,Twig_NodeInterface $body,$lineno,$tag=null)
	{
		parent::__construct(array('body' => $body),array('name' => $name),$lineno,$tag);
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
			->subcompile($this->getAttribute('name'))
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
	public function parse(Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$name = $this->parser->getExpressionParser()->parseExpression();
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse(array($this,'decideBlockEnd'),true);
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		
		return new dcFormNode($name,$body,$token->getLine(),$this->getTag());
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
	
	public function isChoiceGroup($choice)
	{
		return is_array($choice);
	}
	
	public function isChoiceSelected($choice,$value)
	{
		return $choice == $value;
	}
	
	public function getTokenParsers()
	{
		return array(new dcFormTokenParser());
	}
	
	public function hasWidget($name) {
		return isset($this->blocks[$name]);
	}
	public function renderWidget($name,$attr) {
		if (!isset($this->blocks[$name]))
			return '';
		echo $this->template->renderBlock(
			$name,
			$attr,
			$this->blocks
		);
	}

	public function getCurrentForm() {
		return $this->currentForm;
	}

	public function renderField($name,$attributes=array(),$extra=array())
	{
		$field = $this->currentForm->$name;
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

	public function renderHiddenWidgets()
	{
		foreach ($this->currentForm->getHiddenFields() as $h) {
			$this->renderField($h->getName());
		}
	}

	public function getName()
	{
		return 'dc_form';
	}

	public function addForm(dcForm $form)
	{
		$this->forms[$form->getName()] = $form;
	}

	public function beginForm($name)
	{
		if (isset($this->forms[$name])) {
			$this->currentForm = $this->forms[$name];
			$this->currentForm->begin();
		}
		else {
			throw new Twig_Error_Runtime(sprintf(
				'Form "%s" does not exist',
				$name
			));
		}
	}
	
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
	protected $id;
	protected $name;
	protected $core;
	protected $action;
	protected $fields;
	protected $method;
	protected $submitfields;
	protected $hiddenfields;
	protected $errors;
	
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
     * @param mixed $nid either an array (name, id) or a string (name only, id will be set to null).
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
	
	public function getContext() {
		return array();
	}

    /**
     * Class constructor
     * 
     * @param mixed  $core   dotclear core
     * @param mixed  $name   form name
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
		if ($method == 'POST') {
			$this->addNonce();
		}
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
	
	public function getErrors()
	{
		return $this->errors;
	}
	
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
	
	public function removeField(dcField $f) {
		$n = $f->getName();
		if (isset($this->fields[$n])){
			unset($this->fields[$n]);
		}

	}
	public function renameField($field,$newname) {
		$oldname = $field->getName();
		if (isset($this->fields[$oldname])) {
			unset($this->fields[$oldname]);
			$field->setName($newname);
			$this->fields[$newname] = $field;
		}
	}
	public function begin()
	{
		echo sprintf(
			'<form%s method="%s" action="%s">',
			empty($this->id) ? '' : ' id="'.$this->id.'"',
			$this->method,
			$this->action
		);
	}
	
	public function end()
	{
		echo '</form>';
	}
	
	public function __isset($name)
	{
		return isset($this->fields[$name]);
	}
	
	public function __get($name)
	{
		return isset($this->fields[$name]) ?
			$this->fields[$name] : null;
	}

	public function __set($name,$value)
	{
		if (isset($this->fields[$name])) {
			$this->fields[$name]->setValue($value);
		}
	}

	public function isSubmitted()
	{
		$from = $this->method == 'POST' ? $_POST : $_GET;
	}

	protected function setupFields() {
		$from = $this->method == 'POST' ? $_POST : $_GET;
		foreach ($this->fields as $f) {
			$f->setup($from);
		}
	}

	protected function handleActions($submitted) {
		foreach ($submitted as $f) {
			$action = $f->getAction();
			if ($action != NULL) {
				$ret = call_user_func($action,$this);
			}
		}
	}

	protected function getSubmittedFields() {
		$s = array();
		foreach ($this->submitfields as $f) {
			if ($f->isDefined()) {
				$s[$f->getName()] = $f;
			}
		}
		return $s;
	}

	public function setup()
	{
		$this->setupFields();
		$submitted = $this->getSubmittedFields();
		$this->handleActions($submitted);
	}

	public function check()
	{
		foreach ($this->fields as $f) {
			try {
				$f->check();
			}
			catch (InvalidFieldException $e) {
				$this->errors[] = $e->getMessage();
			}
		}
	}
	
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
	protected $options;
	protected $name;
	protected $values;
	protected $id;
	protected $multiple;
	protected $defined;
	
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
	
	public function setValue($value,$offset=0) {
		$this->values[$offset] = $value;
	}

	public function setValues($values) {
		if (is_array($values)) {
			$this->values = $values;
		} elseif ($values !== NULL) {
			$this->values = array($values);
		}

	}

	public function getValues() {
		return $this->values;
	}

	public function getValue($offset=0) {
		if (isset($this->values[$offset])) {
			return $this->values[$offset];
		}
	}

	public function addValue($value) {
		$this->values[] = $value;
	}
	public function delValue($offset) {
		if (isset($this->values[$offset])) {
			array_splice($this->values,$offset,1);
		}
	}

	public function count() {
		return count($this->values);
	}

	public function __toString()
	{
		return join(',',$this->values);
	}
	
	abstract public function getWidgetBlock();
	
	public function getAttributes($options)
	{
		$offset = isset($options['offset']) ? $options['offset'] : 0;

		$attr = $this->options->getArrayCopy();
		if (isset($this->values[$offset])) {
			$attr['value'] = $this->values[$offset];
		} else {
			$attr['value'] = $this->getDefaultValue();
		}
		if ($offset==0) {
			$attr['id']=$this->id;
		}
		$attr['name'] = $this->name;
		if ($this->multiple) {
			$attr['name'] = $attr['name'].'[]';
		}
		return $attr;
	}
	
	public function getDefaultValue() {
		return '';
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name) {
		$this->setNID($name);
	}

	public function check()
	{
		if (!$this->defined && $this->options['mandatory']) {
			throw new InvalidFieldException(sprintf(
				'Field "%s" is mandatory',
				$this->attributes['label'])
			);
		}
	}
	
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

	public function setup($from)
	{
		$values = $this->parseValues($from);
		if (count($values)) {
			$this->setValues($values);
			$this->defined = true;
		}
	}
	
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
	public function getWidgetBlock()
	{
		return "field_checkbox";
	}

	public function getDefaultValue() {
		return 0;
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
	
	public function __construct($name,$value,$combo,$options=array())
	{
		$this->combo = $combo;
		parent::__construct($name,$value,$options);
	}
	
	public function getWidgetBlock()
	{
		return "field_combo";
	}

	public function getDefaultValue() {
		return current($this->combo);
	}

	public function parseValues($from) {
		$values = parent::parseValues($from);
		if (!is_array($values)) {
			$values = array($values);
		}
		foreach ($values as &$v) {
			if (!isset($this->combo[$v]))
			$v = $this->getDefaultValue();
		}
		return $values;
	}

	public function getAttributes($options) {
		$attr = parent::getAttributes($options);
		$attr['options'] = $this->combo;
		return $attr;
	}
}

?>