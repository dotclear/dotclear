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
Template tab node
*/
class dcTabNode extends Twig_Node
{
	public function __construct($name,Twig_NodeInterface $params=null,Twig_NodeInterface $body,$lineno,$tag=null)
	{
		parent::__construct(array('body' => $body),array('name' => $name,'params' => $params),$lineno,$tag);
	}
	
	public function compile(Twig_Compiler $compiler)
	{
		$n = $this->getAttribute('name');
		
		$compiler
			->addDebugInfo($this)
			->write("\$context['dc_tabs']->beginTab('".$n."'");
		
		/* if tab is created from template, compile args */
		if ($this->getAttribute('params')) {
                foreach ($this->getAttribute('params') as $value) {
 				$compiler
					->raw(", ")
					->subcompile($value);
                }
		}
		
		$compiler
			->write(");\n")
			
			/* if tab is not defined, body is compiled but not display */
			->write("if (\$context['dc_tabs']->isTab('".$n."')) {\n")
			->subcompile($this->getNode('body'))
			->write("}\n")
			
			->write("\$context['dc_tabs']->endTab('".$n."');\n")
			;
	}
}

/**
Template tab token parser
*/
class dcTabTokenParser extends Twig_TokenParser
{
	public function parse(Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		
		$values = null;
		$name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
		
		/* create tab from template */
		if ($stream->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
            $stream->next();
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();
            $stream->next();
		}
		
		$stream->expect(Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse(array($this,'decideBlockEnd'),true);
		$stream->expect(Twig_Token::BLOCK_END_TYPE);

		return new dcTabNode($name, $values, $body, $lineno, $this->getTag());
	}
	
	public function decideBlockEnd(Twig_Token $token)
	{
		return $token->test('endtab');
	}
	
	public function getTag()
	{
		return 'tab';
	}
}

/**
Template tab extension
*/
class dcTabExtension extends Twig_Extension
{
	public static $uri = 'tab';
	
	protected $tpl;
	protected $core;
	
	protected $tabs = array();
	protected $selected_tab = ''; // This is tab id, not tab name !
	
	public function __construct($core)
	{
		$this->core = $core;
		$this->tpl = 'tab_layout.html.twig';
		$this->selected_tab = empty($_REQUEST[self::$uri]) ? '' : $_REQUEST[self::$uri];
	}
	
	public function initRuntime(Twig_Environment $environment)
	{
		$this->twig = $environment;
		$this->template = $this->twig->loadTemplate($this->tpl);
		$this->blocks = $this->template->getBlocks();
	}
	
	public function getName()
	{
		return 'dc_tabs';
	}
	
	public function getTokenParsers()
	{
		return array(new dcTabTokenParser());
	}
	
	public function getGlobals()
	{
		return array('dc_tabs' => $this);
	}
	
	public function getFunctions()
	{
		return array(
			'init_tabs' => new Twig_Function_Method(
				$this,
				'initTabs',
				array('is_safe' => array('html'))
			),
			'faketab' => new Twig_Function_Method(
				$this,
				'fakeTab',
				array('is_safe' => array('html'))
			)
		);
	}
	
	/**
	Display page head required by tabs
	*/
	public function initTabs($default_tab=null)
	{
		if (null === $default_tab) {
			$default_tab =  $this->selected_tab;
		}
		
		echo $this->template->renderBlock(
			'page_tab_head',
			array(
				'default_tab' => $default_tab,
				'theme_url' => DC_ADMIN_URL.'index.php?tf='
			),
			$this->blocks
		);
	}
	
	/**
	Display begin of a tab block
	
	This can also create a new tab from template
	*/
	public function beginTab()
	{
		$case = func_num_args();
		$args = func_get_args();
		
		switch($case) {
		
		# No tab name ?!
		case 0:
			throw new Exception('Something went wrong while compile tab.');
			break;
		# Normal usage
		case 1:
			break;
		# Create a new tab from template
		case 3:
			$this->setTab(new dcTab($args[0],$args[1],$args[2]));
			break;
		# Wrong argument count
		default:
			throw new Exception('Wrong parameters count for tab.');
			break;
		
		};
		
		$tab = $this->isTab($args[0]) ? $this->tabs[$args[0]] : false;
		$this->renderTab('page_tab_begin',$tab);
	}
	
	/**
	Display end of a tab block
	*/
	public function endTab($name)
	{
		$tab = $this->isTab($name) ? $this->tabs[$name] : false;
		$this->renderTab('page_tab_end',$tab);
	}
	
	/**
	Display a fake tab block
	*/
	public function fakeTab()
	{
		$case = func_num_args();
		$args = func_get_args();
		
		switch($case) {
		
		# No tab name ?!
		case 0:
			throw new Exception('Something went wrong while compile tab.');
			break;
		# Normal usage
		case 1:
			break;
		# Create a new fake tab from template
		case 4:
			$this->setTab(new dcFakeTab($args[0],$args[1],$args[2],$args[3]));
			break;
		# Wrong argument count
		default:
			throw new Exception('Wrong parameters count for tab.');
			break;
		
		};
		
		$tab = $this->isFake($args[0]) ? $this->tabs[$args[0]] : false;
		$this->renderTab('page_tab_fake',$tab);
	}
	
	/**
	Display block
	*/
	private function renderTab($block,$tab)
	{
		if ($tab) {
			echo $this->template->renderBlock(
				$block,
				$tab->getAttributes(),
				$this->blocks
			);
		}
	}
	
	/**
	Check if a tab is previously set
	
	@param string $name A tab name
	@return boolean
	*/
	public function isTab($name)
	{
		return array_key_exists($name,$this->tabs);
	}
	
	
	/**
	Check if a tab is previously set and it is a fake tab
	
	@param string $name A tab name
	@return boolean
	*/
	public function isFake($name)
	{
		return array_key_exists($name,$this->tabs) 
			&& $this->tabs[$name] instanceOf dcFakeTab;
	}
	
	
	/**
	Mark a tab as selected
	
	@param string $name A tab name
	*/
	public function selectTab($name)
	{
		if (!$name) {
			$this->selected_tab = '';
		}
		elseif ($this->isTab($name) && !$this->isFake($name)) {
			$this->selected_tab =  $this->getTab($name)->getId();
		}
	}
	
	/**
	Get a page tab
	
	@param string $name A tab name
	@return object dcTab instance
	*/
	public function getTab($name)
	{
		return $this->isTab($name) ? $this->tabs[$name] : null;
	}
	
	/**
	Set (prepapre) a page tab
	
	@param object $tab dcTab instance
	@return object self
	*/
	public function setTab($tab)
	{
		if ($tab instanceOf dcTab || $tab instanceOf dcFakeTab) {
			$this->tabs[$tab->getName()] = $tab;
		}
		return $this;
	}
	
	/**
	Check if a tab name is well formed
	
	Tab name must look like Twig_Token::NAME_TYPE
	
	@param string $name A string to check
	@param boolean $verbose Throw exception or not
	@return string The string or false
	*/
	public static function checkTabName($name,$verbose=true)
	{
		if (!preg_match('/[a-zA-Z0-9_]{2,}/',$name)) {
			if ($verbose) {
				throw new Exception(__('Wrong tab name'));
			}
			return false;
		}
		return $name;
	}
}

/**
Tab
*/
class dcTab
{
	protected $attributes = array();
	
	public function __construct($name,$id,$title)
	{
		$this->attributes = array(
			'name' => dcTabExtension::checkTabName($name),
			'id' => $id,
			'title' => $title
		);
	}
	
	public function getAttributes()
	{
		return $this->attributes;
	}
	
	public function getName()
	{
		return $this->attributes['name'];
	}
	
	public function getId()
	{
		return $this->attributes['id'];
	}
	
	public function getTitle()
	{
		return $this->attributes['title'];
	}
}

/**
Fake tab
*/
class dcFakeTab extends dcTab
{
	public function __construct($name,$id,$title,$url)
	{
		parent::__construct($name,$id,$title);
		$this->attributes['url'] = $url;
	}
	
	public function getURL()
	{
		return $this->attributes['url'];
	}
}
?>