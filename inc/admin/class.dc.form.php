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
 * Template form node
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
			->addDebugInfo($this)
			->write("\$context['dc_form']->beginForm('".
				$this->getAttribute('name')."');\n")
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
		$name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
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
	protected $blocks;
	protected $forms;
	protected $currentForm;
	
	public function __construct($core)
	{
		$this->core = $core;
		$this->tpl = 'form_layout.html.twig';
		$this->forms = array();
		$this->currentForm = null;
	}
	
	public function initRuntime(Twig_Environment $environment)
	{
		$this->twig = $environment;
		$this->template = $this->twig->loadTemplate($this->tpl);
		$this->blocks = $this->template->getBlocks();
	}
	
	public function getGlobals()
	{
		return array('dc_form' => $this);
	}
	
	public function getFunctions()
	{
		return array(
			'form_field' => new Twig_Function_Method(
				$this,
				'renderWidget',
				array('is_safe' => array('html'))
			),
			'_form_is_choice_group' => new Twig_Function_Method(
				$this,
				'isChoiceGroup',
				array('is_safe' => array('html'))
			),
			'_form_is_choice_selected' => new Twig_Function_Method(
				$this,
				'isChoiceSelected',
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
	
	public function renderWidget($name,$attributes=array())
	{
		$field = $this->currentForm->$name;
		if ($field) {
			echo $this->template->renderBlock(
				$field->getWidgetBlock(),
				array_merge(
					$field->getAttributes(),
					array('attr' => $attributes)
				),
				$this->blocks
			);
		}
	}

	public function renderHiddenWidgets()
	{
		foreach ($this->currentForm->getHiddenFields() as $h) {
			$this->renderWidget($h->getName());
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
 * Template form
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
	
	private function addNonce()
	{
		$this->addField(
			new dcFieldHidden(array('xd_check'),
			$this->core->getNonce())
		);
	}
	
	protected function getNID($nid)
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
	
	public function __construct($core,$name,$action,$method='POST')
	{
		$this->core = $core;
		$this->getNID($name);
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
			$this->fields[$name]->setAttribute('value',$value);
		}
	}
	
	public function isSubmitted()
	{
		$from = $this->method == 'POST' ? $_POST : $_GET;
		echo "form fields :\n";
	}
	
	public function setup()
	{
		$from = $this->method == 'POST' ? $_POST : $_GET;
		foreach ($this->fields as $f) {
			$f->setup($from);
		}
		foreach ($this->submitfields as $f) {
			if ($f->isDefined()) {
				$ret = call_user_func($f->getAction(),$this);
				return;
			}
		}
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
abstract class dcField
{
	protected $attributes;
	protected $name;
	protected $value;
	protected $id;
	protected $defined;
	
	protected function getNID($nid)
	{
		if (is_array($nid)) {
			$this->name = $nid[0];
			$this->id = !empty($nid[1]) ? $nid[1] : null;
		}
		else {
			$this->id = $this->name = $nid;
		}
	}
	
	public function __construct($name,$value,$attributes=array())
	{
		$this->getNID($name);
		$this->attributes = $attributes;
		$this->value = $value;
		$this->attributes['name'] = $this->name;
		$this->attributes['id'] = $this->id;
		$this->attributes['value'] = $this->value;
		$this->defined = false;
	}
	
	public function __toString()
	{
		return (string) $this->value;
	}
	
	abstract public function getWidgetBlock();
	
	public function setAttribute($name,$value)
	{
		$this->attributes[$name] = $value;
	}
	
	public function getAttributes()
	{
		return $this->attributes;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function check()
	{
		if (!$this->defined && $this->attributes['defined']) {
			throw new InvalidFieldException(sprintf(
				'Field "%s" is mandatory',
				$this->attributes['label'])
			);
		}
	}
	
	public function setup($from)
	{
		if (isset($from[$this->id])) {
			$this->value = $from[$this->id];
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
}

/**
 * Template form action
 */
abstract class dcFieldAction extends dcField
{
	protected $action;
	
	public function __construct($name,$value,$attributes=array())
	{
		parent::__construct($name,$value,$attributes);
		
		if (isset($attributes['action'])) {
			$this->action = $attributes['action'];
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
	protected $options;
	
	public function __construct($name,$value,$options,$attributes=array())
	{
		$this->options = $options;
		parent::__construct($name,$value,$attributes);
		$this->attributes['options']=$options;
	}
	
	public function getWidgetBlock()
	{
		return "field_combo";
	}

}
?>