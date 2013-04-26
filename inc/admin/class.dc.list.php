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

class dcItemList extends dcForm {

	protected $columns;
	protected $entries;
	protected $filterset;
	protected $fetcher;
	protected $selection;
	protected $current_page;
	protected $nb_items;
	protected $nb_items_per_page;
	protected $nb_pages;
	protected $page;


	public static function __init__($env) {
		$env->getExtension('dc_form')->addTemplate('@forms/lists_layout.html.twig');
		$env->addFunction(
			new Twig_SimpleFunction(
				'listitems',
				'dcItemList::renderList',
				array(
					'is_safe' => array('html'),
					'needs_context' => true,
					'needs_environment' => true
		)));
	}

	public static function renderList($env,$context,$name,$attributes=array())
	{
		$context['listname']=$name;
		echo $env->getExtension('dc_form')->renderWidget(
			'entrieslist',
			$context
		);
	}
	/**
	Inits dcItemList object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	form_prefix	<b>string</b>		form prefix to use for parameters
	*/
	public function __construct($core,$name,$filterset,$fetcher,$action,$form_prefix="f_") {
		parent::__construct($core,$name,$action,'POST');
		$this->entries = array();
		$this->columns = array();
		$this->selection = new dcFieldCheckbox('entries',NULL,array('multiple' => true));
		$this->addField($this->selection);
		$this->filterset = $filterset;
		$this->fetcher = $fetcher;
	}

	public function setup() {
		$this
			->addField(new dcFieldCombo('action','',array(), array(
				'label' => __('Selected entries action:'))))
			->addField(new dcFieldSubmit('ok',__('ok'), array()));
		$columns_combo = array();
		foreach ($this->columns as $c) {
			$columns_combo[$c->getID()] = $c->getName();
		}
		$this->filterset->addFilter(new dcFilterCombo(
			'sortby',
			__('Sort By'), 
			__('Sort by'), 'sortby', $columns_combo,array('singleval'=> true,'static' => true)));
		$order_combo = array('asc' => __('Ascending'),'desc' => __('Descending'));
		$this->filterset->addFilter(new dcFilterCombo(
			'order',
			__('Order'), 
			__('Order'), 'orderby', $order_combo,array('singleval'=> true, 'static' => true)));
		$limit = new dcFilterText(
			'limit',
			__('Limit'), __('Limit'), 'limit',array('singleval'=> true,'static' =>true));
		$this->filterset->addFilter($limit);
		$this->filterset->setup();
		parent::setup();
		$this->nb_items_per_page = $limit->getFields()->getValue();
		if ($this->nb_items_per_page == 0)
			$this->nb_items_per_page = 10;
		$this->fetchEntries();

	}

	protected function fetchEntries() {
		$params = new ArrayObject();
		$this->filterset->applyFilters($params);
		$this->nb_items = $this->fetcher->getEntriesCount($params);
		$this->nb_pages = round($this->nb_items / $this->nb_items_per_page) + 1;
		if (isset($_GET['page'])) {
			$this->page = (int)$_GET['page'];
		} else {
			$this->page = 1;
		}
		if ($this->page > $this->nb_pages) {
			$this->page = $this->nb_pages;
		}
		$offset = $this->nb_items_per_page*($this->page-1);
		$entries = $this->fetcher->getEntries($params,$offset,$this->nb_items_per_page);
		$this->setEntries($entries);

	}

	public function setEntries($entries) {
		$this->entries = $entries;
		$this->core->tpl->addGlobal('list_'.$this->name,$this->getContext());
		foreach ($this->entries as $e) {
			$this->selection->addValue($e->post_id);
		}
	}

	public function getContext() {
		$ccontext = new ArrayObject();
		foreach ($this->columns as $c) {
			$c->appendEditLines($ccontext);
		}
		$page = $this->page;
		$nb_pages = $this->nb_pages;
		$nb_pages_around = 2;
		$pages = array(1);
		if ($page>$nb_pages_around+2) {
			$pages[]='...';
		}
		for ($p=max(2,$page-$nb_pages_around); 
			$p<=min($page+$nb_pages_around,$nb_pages-1); $p++) {
			$pages[]=$p;
		}
		if ($page<$nb_pages-$nb_pages_around-1) {
			$pages[]='...';
		}
		$pages[] = $nb_pages;


		return array(
			'url' => array('',$this->filterset->getURLParams()),
			'cols' => $ccontext,
			'entries' => $this->entries,
			'nb_entries' => $this->nb_items,
			'page' => $page,
			'pages_links' => $pages);
	}

	public function addColumn($c) {
		$this->columns[] = $c;
		$c->setForm($this);
		return $this;
	}

	public function setFilterSet($fs) {
		$this->filterset = $fs;
	}

}

class dcColumn {
	protected $form;
	protected $id;
	protected $name;
	protected $sortable;
	protected $col_id;

	public function __construct($id, $name, $col_id,$attributes=array()) {
		$this->id = $id;
		$this->name = $name;
		$this->col_id = $col_id;
	}

	public function getName() {
		return $this->name;
	}

	public function getID() {
		return $this->id;
	}

	public function getColID(){
		return $this->col_id;
	}

	public function setForm($f){
		$this->form = $f;
	}

	public function appendEditLines($line) {
		$line[] = array (
			'name' => $this->name,
			'col_id' => $this->col_id,
			'widget' => 'col_'.$this->id);
	}

}

abstract class dcListFetcher {
	protected $core;
	public function __construct($core) {
		$this->core = $core;
	}

	abstract function getEntries($params,$offset,$limit);
	abstract function getEntriesCount($params);
}

dcItemList::__init__($GLOBALS['core']->tpl);

?>