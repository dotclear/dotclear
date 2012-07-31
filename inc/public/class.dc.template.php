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

class dcTemplate extends template
{
	private $core;
	private $current_tag;
	
	protected $unknown_value_handler = null;
	protected $unknown_block_handler = null;
	
	function __construct($cache_dir,$self_name,$core)
	{
		parent::__construct($cache_dir,$self_name);
		
		$this->remove_php = !$core->blog->settings->system->tpl_allow_php;
		$this->use_cache = $core->blog->settings->system->tpl_use_cache;
		
		$this->tag_block = '<tpl:(\w+)(?:(\s+.*?)>|>)((?:[^<]|<(?!/?tpl:\1)|(?R))*)</tpl:\1>';
		$this->tag_value = '{{tpl:(\w+)(\s(.*?))?}}';
		
		$this->core =& $core;
		
		# l10n
		$this->addValue('lang',array($this,'l10n'));
		
		# Loops test tags
		$this->addBlock('LoopPosition',array($this,'LoopPosition'));
		
		# Archives
		$this->addBlock('Archives',array($this,'Archives'));
		$this->addBlock('ArchivesHeader',array($this,'ArchivesHeader'));
		$this->addBlock('ArchivesFooter',array($this,'ArchivesFooter'));
		$this->addBlock('ArchivesYearHeader',array($this,'ArchivesYearHeader'));
		$this->addBlock('ArchivesYearFooter',array($this,'ArchivesYearFooter'));
		$this->addValue('ArchiveDate',array($this,'ArchiveDate'));
		$this->addBlock('ArchiveNext',array($this,'ArchiveNext'));
		$this->addBlock('ArchivePrevious',array($this,'ArchivePrevious'));
		$this->addValue('ArchiveEntriesCount',array($this,'ArchiveEntriesCount'));
		$this->addValue('ArchiveURL',array($this,'ArchiveURL'));
		
		# Blog
		$this->addValue('BlogArchiveURL',array($this,'BlogArchiveURL'));
		$this->addValue('BlogCopyrightNotice',array($this,'BlogCopyrightNotice'));
		$this->addValue('BlogDescription',array($this,'BlogDescription'));
		$this->addValue('BlogEditor',array($this,'BlogEditor'));
		$this->addValue('BlogFeedID',array($this,'BlogFeedID'));
		$this->addValue('BlogFeedURL',array($this,'BlogFeedURL'));
		$this->addValue('BlogRSDURL',array($this,'BlogRSDURL'));
		$this->addValue('BlogName',array($this,'BlogName'));
		$this->addValue('BlogLanguage',array($this,'BlogLanguage'));
		$this->addValue('BlogThemeURL',array($this,'BlogThemeURL'));
		$this->addValue('BlogUpdateDate',array($this,'BlogUpdateDate'));
		$this->addValue('BlogID',array($this,'BlogID'));
		$this->addValue('BlogURL',array($this,'BlogURL'));
		$this->addValue('BlogPublicURL',array($this,'BlogPublicURL'));
		$this->addValue('BlogQmarkURL',array($this,'BlogQmarkURL'));
		$this->addValue('BlogMetaRobots',array($this,'BlogMetaRobots'));
		
		# Entries
		$this->addBlock('DateFooter',array($this,'DateFooter'));
		$this->addBlock('DateHeader',array($this,'DateHeader'));
		$this->addBlock('Entries',array($this,'Entries'));
		$this->addBlock('EntriesFooter',array($this,'EntriesFooter'));
		$this->addBlock('EntriesHeader',array($this,'EntriesHeader'));
		$this->addValue('EntryExcerpt',array($this,'EntryExcerpt'));
		$this->addValue('EntryAuthorCommonName',array($this,'EntryAuthorCommonName'));
		$this->addValue('EntryAuthorDisplayName',array($this,'EntryAuthorDisplayName'));
		$this->addValue('EntryAuthorEmail',array($this,'EntryAuthorEmail'));
		$this->addValue('EntryAuthorID',array($this,'EntryAuthorID'));
		$this->addValue('EntryAuthorLink',array($this,'EntryAuthorLink'));
		$this->addValue('EntryAuthorURL',array($this,'EntryAuthorURL'));
		$this->addValue('EntryBasename',array($this,'EntryBasename'));
		$this->addValue('EntryContent',array($this,'EntryContent'));
		$this->addValue('EntryDate',array($this,'EntryDate'));
		$this->addValue('EntryFeedID',array($this,'EntryFeedID'));
		$this->addValue('EntryFirstImage',array($this,'EntryFirstImage'));
		$this->addValue('EntryID',array($this,'EntryID'));
		$this->addBlock('EntryIf',array($this,'EntryIf'));
		$this->addValue('EntryIfFirst',array($this,'EntryIfFirst'));
		$this->addValue('EntryIfOdd',array($this,'EntryIfOdd'));
		$this->addValue('EntryIfSelected',array($this,'EntryIfSelected'));
		$this->addValue('EntryLang',array($this,'EntryLang'));
		$this->addBlock('EntryNext',array($this,'EntryNext'));
		$this->addBlock('EntryPrevious',array($this,'EntryPrevious'));
		$this->addValue('EntryTitle',array($this,'EntryTitle'));
		$this->addValue('EntryTime',array($this,'EntryTime'));
		$this->addValue('EntryURL',array($this,'EntryURL'));
		
		# Languages
		$this->addBlock('Languages',array($this,'Languages'));
		$this->addBlock('LanguagesHeader',array($this,'LanguagesHeader'));
		$this->addBlock('LanguagesFooter',array($this,'LanguagesFooter'));
		$this->addValue('LanguageCode',array($this,'LanguageCode'));
		$this->addBlock('LanguageIfCurrent',array($this,'LanguageIfCurrent'));
		$this->addValue('LanguageURL',array($this,'LanguageURL'));
		
		# Pagination
		$this->addBlock('Pagination',array($this,'Pagination'));
		$this->addValue('PaginationCounter',array($this,'PaginationCounter'));
		$this->addValue('PaginationCurrent',array($this,'PaginationCurrent'));
		$this->addBlock('PaginationIf',array($this,'PaginationIf'));
		$this->addValue('PaginationURL',array($this,'PaginationURL'));
		
		# System
		$this->addValue('SysBehavior',array($this,'SysBehavior'));
		$this->addBlock('SysIf',array($this,'SysIf'));
		$this->addBlock('SysIfFormError',array($this,'SysIfFormError'));
		$this->addValue('SysFeedSubtitle',array($this,'SysFeedSubtitle'));
		$this->addValue('SysFormError',array($this,'SysFormError'));
		$this->addValue('SysPoweredBy',array($this,'SysPoweredBy'));
		$this->addValue('SysSearchString',array($this,'SysSearchString'));
		$this->addValue('SysSelfURI',array($this,'SysSelfURI'));
	}
	
	public function getData($________)
	{
		# --BEHAVIOR-- tplBeforeData
		if ($this->core->hasBehavior('tplBeforeData'))
		{
			self::$_r = $this->core->callBehavior('tplBeforeData',$this->core);
			if (self::$_r) {
				return self::$_r;
			}
		}
		
		parent::getData($________);
		
		# --BEHAVIOR-- tplAfterData
		if ($this->core->hasBehavior('tplAfterData')) {
			$this->core->callBehavior('tplAfterData',$this->core,self::$_r);
		}
		
		return self::$_r;
	}
	
	protected function compileFile($file)
	{
		$fc = file_get_contents($file);
		
		$this->compile_stack[] = $file;
		
		# Remove every PHP tags
		if ($this->remove_php)
		{
			$fc = preg_replace('/<\?(?=php|=|\s).*?\?>/ms','',$fc);
		}
		
		# Transform what could be considered as PHP short tags
		$fc = preg_replace('/(<\?(?!php|=|\s))(.*?)(\?>)/ms',
		'<?php echo "$1"; ?>$2<?php echo "$3"; ?>',$fc);
		
		# Remove template comments <!-- #... -->
		$fc = preg_replace('/(^\s*)?<!-- #(.*?)-->/ms','',$fc);
		
		# Lexer part : split file into small pieces
		# each array entry will be either a tag or plain text
		$blocks = preg_split(
			'#(<tpl:\w+[^>]*>)|(</tpl:\w+>)|({{tpl:\w+[^}]*}})#msu',$fc,-1,
			PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
		# Next : build semantic tree from tokens.
		$rootNode = new tplNode();
		$node = $rootNode;
		$errors = array();
		foreach ($blocks as $id => $block) {
			$isblock = preg_match('#<tpl:(\w+)(?:(\s+.*?)>|>)|</tpl:(\w+)>|{{tpl:(\w+)(\s(.*?))?}}#ms',$block,$match);
			if ($isblock == 1) {
				if (substr($match[0],1,1) == '/') {
					// Closing tag, check if it matches current opened node
					$tag = $match[3];
					if (($node instanceof tplNodeBlock) && $node->getTag() == $tag) {
						$node->setClosing();
						$node = $node->getParent();
					} else {
						// Closing tag does not match opening tag
						// Search if it closes a parent tag
						$search = $node;
						while($search->getTag() != 'ROOT' && $search->getTag() != $tag) {
							$search = $search->getParent();
						}
						if ($search->getTag() == $tag) {
							$errors[] = sprintf(
								__('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
								html::escapeHTML($node->getTag()));
							$search->setClosing();
							$node = $search->getParent();
						} else {
							$errors[]=sprintf(
								__('Unexpected closing tag </tpl:%s> found.'),
								$tag);;
						}
					}
				} elseif (substr($match[0],0,1) == '{') {
					// Value tag
					$tag = $match[4];
					$str_attr = '';
					$attr = array();
					if (isset($match[6])) {
						$str_attr = $match[6];
						$attr = $this->getAttrs($match[6]);
					}
					$node->addChild(new tplNodeValue($tag,$attr,$str_attr));
				} else {
					// Opening tag, create new node and dive into it
					$tag = $match[1];
					$newnode = new tplNodeBlock($tag,isset($match[2])?$this->getAttrs($match[2]):array());
					$node->addChild($newnode);
					$node = $newnode;
				}
			} else {
				// Simple text
				$node->addChild(new tplNodeText($block));
			}
		}
		
		if (($node instanceof tplNodeBlock) && !$node->isClosed()) {
			$errors[] = sprintf(
				__('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
				html::escapeHTML($node->getTag()));
		}
		
		$err = "";
		if (count($errors) > 0) {
			$err = "\n\n<!-- \n".
				__('WARNING: the following errors have been found while parsing template file :').
				"\n * ".
				join("\n * ",$errors).
				"\n -->\n";
		}
		
		return $rootNode->compile($this).$err;
	}
    
	public function compileBlockNode($tag,$attr,$content)
	{
		$this->current_tag = $tag;
		$attr = new ArrayObject($attr);
		# --BEHAVIOR-- templateBeforeBlock
		$res = $this->core->callBehavior('templateBeforeBlock',$this->core,$this->current_tag,$attr);
		
		# --BEHAVIOR-- templateInsideBlock
		$this->core->callBehavior('templateInsideBlock',$this->core,$this->current_tag,$attr,array(&$content));
		
		if (isset($this->blocks[$this->current_tag])) {
			$res .= call_user_func($this->blocks[$this->current_tag],$attr,$content);
		} elseif ($this->unknown_block_handler != null) {
			$res .= call_user_func($this->unknown_block_handler,$this->current_tag,$attr,$content);
		}
		
		# --BEHAVIOR-- templateAfterBlock
		$res .= $this->core->callBehavior('templateAfterBlock',$this->core,$this->current_tag,$attr);
		
		return $res;
	}
	
	public function compileValueNode($tag,$attr,$str_attr)
	{
		$this->current_tag = $tag;
		
		$attr = new ArrayObject($attr);
		# --BEHAVIOR-- templateBeforeValue
		$res = $this->core->callBehavior('templateBeforeValue',$this->core,$this->current_tag,$attr);
		
		if (isset($this->values[$this->current_tag])) {
			$res .= call_user_func($this->values[$this->current_tag],$attr,ltrim($str_attr));
		} elseif ($this->unknown_value_handler != null) {
			$res .= call_user_func($this->unknown_value_handler,$this->current_tag,$attr,$str_attr);
		}
        
		# --BEHAVIOR-- templateAfterValue
		$res .= $this->core->callBehavior('templateAfterValue',$this->core,$this->current_tag,$attr);
		
		return $res;
	}
    
	public function setUnknownValueHandler($callback)
	{
		if (is_callable($callback)) {
			$this->unknown_value_handler = $callback;
		}
	}
    
	public function setUnknownBlockHandler($callback)
	{
		if (is_callable($callback)) {
			$this->unknown_block_handler = $callback;
		}
	}
    
	public function getFilters($attr)
	{
		$p[0] = '0';	# encode_xml
		$p[1] = '0';	# remove_html
		$p[2] = '0';	# cut_string
		$p[3] = '0';	# lower_case
		$p[4] = '0';	# upper_case
		
		$p[0] = (integer) (!empty($attr['encode_xml']) || !empty($attr['encode_html']));
		$p[1] = (integer) !empty($attr['remove_html']);
		
		if (!empty($attr['cut_string']) && (integer) $attr['cut_string'] > 0) {
			$p[2] = (integer) $attr['cut_string'];
		}
		
		$p[3] = (integer) !empty($attr['lower_case']);
		$p[4] = (integer) !empty($attr['upper_case']);
		
		return "context::global_filter(%s,".implode(",",$p).",'".addslashes($this->current_tag)."')";
	}
	
	public static function getOperator($op)
	{
		switch (strtolower($op))
		{
			case 'or':
			case '||':
				return '||';
			case 'and':
			case '&&':
			default:
				return '&&';
		}
	}
	
	public function getSortByStr($attr,$table = null)
	{
		$res = array();
		
		$default_order = 'desc';
		
		$default_alias = array(
			'post' => array(
				'title' => 'post_title',
				'selected' => 'post_selected',
				'author' => 'user_id',
				'date' => 'post_dt',
				'id' => 'post_id',
			)
		);
		
		$alias = new ArrayObject();
		
		# --BEHAVIOR-- templateCustomSortByAlias
		$this->core->callBehavior('templateCustomSortByAlias',$alias);
		
		$alias = $alias->getArrayCopy();
		
		if (is_array($alias)) {
			foreach ($alias as $k => $v) {
				if (!is_array($v)) {
					$alias[$k] = array();
				}
				if (!is_array($v)) {
					$default_alias[$k] = array();
				}
				$default_alias[$k] = array_merge($default_alias[$k],$alias[$k]);
			}
		}
		
		if (!array_key_exists($table,$default_alias)) {
			return implode(', ',$res);
		}
		
		if (isset($attr['order']) && preg_match('/^(desc|asc)$/i',$attr['order'])) {
			$default_order = $attr['order'];
		}
		if (isset($attr['sortby'])) {
			$sorts = explode(',',$attr['sortby']);
			foreach ($sorts as $k => $sort) {
				$order = $default_order;
				if (preg_match('/([a-z]*)\s*\?(desc|asc)$/i',$sort,$matches)) {
					$sort = $matches[1];
					$order = $matches[2];
				}
				if (array_key_exists($sort,$default_alias[$table])) {
					array_push($res,$default_alias[$table][$sort].' '.$order);
				}
			}
		}
		
		if (count($res) === 0) {
			array_push($res,$default_alias[$table]['date'].' '.$default_order);
		}
		
		return implode(', ',$res);
	}
	
	public function getAge($attr)
	{
		if (isset($attr['age']) && preg_match('/^(\-[0-9]+|last).*$/i',$attr['age'])) {
			if (($ts = strtotime($attr['age'])) !== false) {
				return dt::str('%Y-%m-%d %H:%m:%S',$ts);
			}
		}
		return '';
	}
	
	/* TEMPLATE FUNCTIONS
	------------------------------------------------------- */
	
	public function l10n($attr,$str_attr)
	{
		# Normalize content
		$str_attr = preg_replace('/\s+/x',' ',$str_attr);
		
		return "<?php echo __('".str_replace("'","\\'",$str_attr)."'); ?>";
	}
	
	public function LoopPosition($attr,$content)
	{
		$start = isset($attr['start']) ? (integer) $attr['start'] : '0';
		$length = isset($attr['length']) ? (integer) $attr['length'] : 'null';
		$even = isset($attr['even']) ? (integer) (boolean) $attr['even'] : 'null';
		
		if ($start > 0) {
			$start--;
		}
		
		return
		'<?php if ($_ctx->loopPosition('.$start.','.$length.','.$even.')) : ?>'.
		$content.
		"<?php endif; ?>";
	}
	
	
	/* Archives ------------------------------------------- */
	/*dtd
	<!ELEMENT tpl:Archives - - -- Archives dates loop -->
	<!ATTLIST tpl:Archives
	type		(day|month|year)	#IMPLIED	-- Get days, months or years, default to month --
	no_context (1|0)			#IMPLIED  -- Override context information
	order	(asc|desc)		#IMPLIED  -- Sort asc or desc --
	post_type	CDATA			#IMPLIED  -- Get dates of given type of entries, default to post --
	post_lang	CDATA		#IMPLIED  -- Filter on the given language
	>
	*/
	public function Archives($attr,$content)
	{
		$p = "if (!isset(\$params)) \$params = array();\n";
		$p .= "\$params['type'] = 'month';\n";
		if (isset($attr['type'])) {
			$p .= "\$params['type'] = '".addslashes($attr['type'])."';\n";
		}
		
		if (isset($attr['post_type'])) {
			$p .= "\$params['post_type'] = '".addslashes($attr['post_type'])."';\n";
		}
        
		if (isset($attr['post_lang'])) {
			$p .= "\$params['post_lang'] = '".addslashes($attr['post_lang'])."';\n";
		}
		
		$order = 'desc';
		if (isset($attr['order']) && preg_match('/^(desc|asc)$/i',$attr['order'])) {
			$p .= "\$params['order'] = '".$attr['order']."';\n ";
		}
		
		$res = "<?php\n";
		$res .= $p;
		$res .= $this->core->callBehavior("templatePrepareParams", 
			array("tag" => "Archives","method" => "blog::getDates"), 
			$attr,$content);
		$res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);'."\n";
		$res .= "?>\n";
		
		$res .=
		'<?php while ($_ctx->archives->fetch()) : ?>'.$content.'<?php endwhile; $_ctx->archives = null; ?>';
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:ArchivesHeader - - -- First archives result container -->
	*/
	public function ArchivesHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->archives->isStart()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:ArchivesFooter - - -- Last archives result container -->
	*/
	public function ArchivesFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->archives->isEnd()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:ArchivesYearHeader - - -- First result of year in archives container -->
	*/
	public function ArchivesYearHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->archives->yearHeader()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:ArchivesYearFooter - - -- Last result of year in archives container -->
	*/
	public function ArchivesYearFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->archives->yearFooter()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:ArchiveDate - O -- Archive result date -->
	<!ATTLIST tpl:ArchiveDate
	format	CDATA	#IMPLIED  -- Date format (Default %B %Y) --
	>
	*/
	public function ArchiveDate($attr)
	{
		$format = '%B %Y';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"dt::dt2str('".$format."',\$_ctx->archives->dt)").'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:ArchiveEntriesCount - O -- Current archive result number of entries -->
	*/
	public function ArchiveEntriesCount($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->archives->nb_post').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:ArchiveNext - - -- Next archive result container -->
	<!ATTLIST tpl:ArchiveNext
	type		(day|month|year)	#IMPLIED	-- Get days, months or years, default to month --
	post_type	CDATA			#IMPLIED  -- Get dates of given type of entries, default to post --
	post_lang	CDATA		#IMPLIED  -- Filter on the given language
	>
	*/
	public function ArchiveNext($attr,$content)
	{
		$p = "if (!isset(\$params)) \$params = array();\n";
		$p .= "\$params['type'] = 'month';\n";
		if (isset($attr['type'])) {
			$p .= "\$params['type'] = '".addslashes($attr['type'])."';\n";
		}
		
		if (isset($attr['post_type'])) {
			$p .= "\$params['post_type'] = '".addslashes($attr['post_type'])."';\n";
		}
		
		if (isset($attr['post_lang'])) {
			$p .= "\$params['post_lang'] = '".addslashes($attr['post_lang'])."';\n";
		}
        
		$p .= "\$params['next'] = \$_ctx->archives->dt;";
		
		$res = "<?php\n";
		$res .= $p;
		$res .= $this->core->callBehavior("templatePrepareParams",
			array("tag" => "ArchiveNext","method" => "blog::getDates"), 
			$attr, $content);
		$res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);'."\n";
		$res .= "?>\n";
		
		$res .=
		'<?php while ($_ctx->archives->fetch()) : ?>'.$content.'<?php endwhile; $_ctx->archives = null; ?>';
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:ArchivePrevious - - -- Previous archive result container -->
	<!ATTLIST tpl:ArchivePrevious
	type		(day|month|year)	#IMPLIED	-- Get days, months or years, default to month --
	post_type	CDATA			#IMPLIED  -- Get dates of given type of entries, default to post --
	post_lang	CDATA		#IMPLIED  -- Filter on the given language
	>
	*/
	public function ArchivePrevious($attr,$content)
	{
		$p = 'if (!isset($params)) $params = array();';
		$p .= "\$params['type'] = 'month';\n";
		if (isset($attr['type'])) {
			$p .= "\$params['type'] = '".addslashes($attr['type'])."';\n";
		}
		
		if (isset($attr['post_type'])) {
			$p .= "\$params['post_type'] = '".addslashes($attr['post_type'])."';\n";
		}
        
		if (isset($attr['post_lang'])) {
			$p .= "\$params['post_lang'] = '".addslashes($attr['post_lang'])."';\n";
		}
        
		$p .= "\$params['previous'] = \$_ctx->archives->dt;";
		
		$res = "<?php\n";
		$res .= $this->core->callBehavior("templatePrepareParams",
			array("tag" => "ArchivePrevious","method" => "blog::getDates"), 
			$attr, $content);
		$res .= $p;
		$res .= '$_ctx->archives = $core->blog->getDates($params); unset($params);'."\n";
		$res .= "?>\n";
		
		$res .=
		'<?php while ($_ctx->archives->fetch()) : ?>'.$content.'<?php endwhile; $_ctx->archives = null; ?>';
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:ArchiveURL - O -- Current archive result URL -->
	*/
	public function ArchiveURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->archives->url($core)').'; ?>';
	}
	
	
	/* Blog ----------------------------------------------- */
	/*dtd
	<!ELEMENT tpl:BlogArchiveURL - O -- Blog Archives URL -->
	*/
	public function BlogArchiveURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url.$core->url->getURLFor("archive")').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogCopyrightNotice - O -- Blog copyrght notices -->
	*/
	public function BlogCopyrightNotice($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->settings->system->copyright_notice').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogDescription - O -- Blog Description -->
	*/
	public function BlogDescription($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->desc').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogEditor - O -- Blog Editor -->
	*/
	public function BlogEditor($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->settings->system->editor').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogFeedID - O -- Blog Feed ID -->
	*/
	public function BlogFeedID($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'"urn:md5:".$core->blog->uid').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogFeedURL - O -- Blog Feed URL -->
	<!ATTLIST tpl:BlogFeedURL
	type	(rss2|atom)	#IMPLIED	-- feed type (default : rss2)
	>
	*/
	public function BlogFeedURL($attr)
	{
		$type = !empty($attr['type']) ? $attr['type'] : 'atom';
		
		if (!preg_match('#^(rss2|atom)$#',$type)) {
			$type = 'atom';
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url.$core->url->getURLFor("feed","'.$type.'")').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogName - O -- Blog Name -->
	*/
	public function BlogName($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->name').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogLanguage - O -- Blog Language -->
	*/
	public function BlogLanguage($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->settings->system->lang').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogThemeURL - O -- Blog's current Themei URL -->
	*/
	public function BlogThemeURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->settings->system->themes_url."/".$core->blog->settings->system->theme').'; ?>';
	}
    
	/*dtd
	<!ELEMENT tpl:BlogPublicURL - O -- Blog Public directory URL -->
	*/
	public function BlogPublicURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->settings->system->public_url').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogUpdateDate - O -- Blog last update date -->
	<!ATTLIST tpl:BlogUpdateDate
	format	CDATA	#IMPLIED	-- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
	iso8601	CDATA	#IMPLIED	-- if set, tells that date format is ISO 8601
	rfc822	CDATA	#IMPLIED	-- if set, tells that date format is RFC 822
	>
	*/
	public function BlogUpdateDate($attr)
	{
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		} else {
			$format = '%Y-%m-%d %H:%M:%S';
		}
		
		$iso8601 = !empty($attr['iso8601']);
		$rfc822 = !empty($attr['rfc822']);
		
		$f = $this->getFilters($attr);
		
		if ($rfc822) {
			return '<?php echo '.sprintf($f,"dt::rfc822(\$core->blog->upddt,\$core->blog->settings->system->blog_timezone)").'; ?>';
		} elseif ($iso8601) {
			return '<?php echo '.sprintf($f,"dt::iso8601(\$core->blog->upddt,\$core->blog->settings->system->blog_timezone)").'; ?>';
		} else {
			return '<?php echo '.sprintf($f,"dt::str('".$format."',\$core->blog->upddt)").'; ?>';
		}
	}
	
	/*dtd
	<!ELEMENT tpl:BlogID - 0 -- Blog ID -->
	*/
	public function BlogID($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->id').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogRSDURL - O -- Blog RSD URL -->
	*/
	public function BlogRSDURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url.$core->url->getURLFor(\'rsd\')').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogURL - O -- Blog URL -->
	*/
	public function BlogURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogQmarkURL - O -- Blog URL, ending with a question mark -->
	*/
	public function BlogQmarkURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->getQmarkURL()').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:BlogMetaRobots - O -- Blog meta robots tag definition, overrides robots_policy setting -->
	<!ATTLIST tpl:BlogMetaRobots
	robots	CDATA	#IMPLIED	-- can be INDEX,FOLLOW,NOINDEX,NOFOLLOW,ARCHIVE,NOARCHIVE
	>
	*/
	public function BlogMetaRobots($attr)
	{
		$robots = isset($attr['robots']) ? addslashes($attr['robots']) : '';
		return "<?php echo context::robotsPolicy(\$core->blog->settings->system->robots_policy,'".$robots."'); ?>";
	}
	
	/* Entries -------------------------------------------- */
	/*dtd
	<!ELEMENT tpl:Entries - - -- Blog Entries loop -->
	<!ATTLIST tpl:Entries
	lastn	CDATA	#IMPLIED	-- limit number of results to specified value
	author	CDATA	#IMPLIED	-- get entries for a given user id
	no_context (1|0)	#IMPLIED  -- Override context information
	sortby	(title|selected|author|date|id)	#IMPLIED	-- specify entries sort criteria (default : date) (multiple comma-separated sortby can be specified. Use "?asc" or "?desc" as suffix to provide an order for each sorby)
	order	(desc|asc)	#IMPLIED	-- specify entries order (default : desc)
	no_content	(0|1)	#IMPLIED	-- do not retrieve entries content
	selected	(0|1)	#IMPLIED	-- retrieve posts marked as selected only (value: 1) or not selected only (value: 0)
	url		CDATA	#IMPLIED	-- retrieve post by its url
	type		CDATA	#IMPLIED	-- retrieve post with given post_type (there can be many ones separated by comma)
	age		CDATA	#IMPLIED	-- retrieve posts by maximum age (ex: -2 days, last month, last week)
	ignore_pagination	(0|1)	#IMPLIED	-- ignore page number provided in URL (useful when using multiple tpl:Entries on the same page)
	>
	*/
	public function Entries($attr,$content)
	{
		$lastn = -1;
		if (isset($attr['lastn'])) {
			$lastn = abs((integer) $attr['lastn'])+0;
		}
		
		$p = 'if (!isset($_page_number)) { $_page_number = 1; }'."\n";
		
		if ($lastn != 0) {
			if ($lastn > 0) {
				$p .= "\$params['limit'] = ".$lastn.";\n";
			} else {
				$p .= "\$params['limit'] = \$_ctx->nb_entry_per_page;\n";
			}
			
			if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == "0") {
				$p .= "\$params['limit'] = array(((\$_page_number-1)*\$params['limit']),\$params['limit']);\n";
			} else {
				$p .= "\$params['limit'] = array(0, \$params['limit']);\n";
			}
		}
		
		if (isset($attr['author'])) {
			$p .= "\$params['user_id'] = '".addslashes($attr['author'])."';\n";
		}
		
		if (!empty($attr['type'])) {
			$p .= "\$params['post_type'] = preg_split('/\s*,\s*/','".addslashes($attr['type'])."',-1,PREG_SPLIT_NO_EMPTY);\n";
		}
		
		if (!empty($attr['url'])) {
			$p .= "\$params['post_url'] = '".addslashes($attr['url'])."';\n";
		}
		
		if (empty($attr['no_context']))
		{
			if (!isset($attr['author']))
			{
				$p .=
				'if ($_ctx->exists("users")) { '.
					"\$params['user_id'] = \$_ctx->users->user_id; ".
				"}\n";
			}
			
			$p .=
			'if ($_ctx->exists("archives")) { '.
				"\$params['post_year'] = \$_ctx->archives->year(); ".
				"\$params['post_month'] = \$_ctx->archives->month(); ";
			if (!isset($attr['lastn'])) {
				$p .= "unset(\$params['limit']); ";
			}
			$p .=
			"}\n";
			
			$p .=
			'if ($_ctx->exists("langs")) { '.
				"\$params['post_lang'] = \$_ctx->langs->post_lang; ".
			"}\n";
			
			$p .=
			'if (isset($_search)) { '.
				"\$params['search'] = \$_search; ".
			"}\n";
		}
		
		$p .= "\$params['order'] = '".$this->getSortByStr($attr,'post')."';\n";
		
		if (isset($attr['no_content']) && $attr['no_content']) {
			$p .= "\$params['no_content'] = true;\n";
		}
		
		if (isset($attr['selected'])) {
			$p .= "\$params['post_selected'] = ".(integer) (boolean) $attr['selected'].";";
		}
		
		if (isset($attr['age'])) {
			$age = $this->getAge($attr);
			$p .= !empty($age) ? "@\$params['sql'] .= ' AND P.post_dt > \'".$age."\'';\n" : '';
		}
		
		$res = "<?php\n";
		$res .= $p;
		$res .= $this->core->callBehavior("templatePrepareParams", 
			array("tag" => "Entries","method" => "blog::getPosts"), 
			$attr,$content);
		$res .= '$_ctx->post_params = $params;'."\n";
		$res .= '$_ctx->posts = $core->blog->getPosts($params); unset($params);'."\n";
		$res .= "?>\n";
		$res .=
		'<?php while ($_ctx->posts->fetch()) : ?>'.$content.'<?php endwhile; '.
		'$_ctx->posts = null; $_ctx->post_params = null; ?>';
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:DateHeader - O -- Displays date, if post is the first post of the given day -->
	*/
	public function DateHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->posts->firstPostOfDay()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:DateFooter - O -- Displays date,  if post is the last post of the given day -->
	*/
	public function DateFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->posts->lastPostOfDay()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryIf - - -- tests on current entry -->
	<!ATTLIST tpl:EntryIf
	type	CDATA	#IMPLIED	-- post has a given type (default: "post")
	first	(0|1)	#IMPLIED	-- post is the first post from list (value : 1) or not (value : 0)
	odd	(0|1)	#IMPLIED	-- post is in an odd position (value : 1) or not (value : 0)
	even	(0|1)	#IMPLIED	-- post is in an even position (value : 1) or not (value : 0)
	extended	(0|1)	#IMPLIED	-- post has an excerpt (value : 1) or not (value : 0)
	selected	(0|1)	#IMPLIED	-- post is selected (value : 1) or not (value : 0)
	has_attachment	(0|1)	#IMPLIED	-- post has attachments (value : 1) or not (value : 0) (see Attachment plugin for code)
	operator	(and|or)	#IMPLIED	-- combination of conditions, if more than 1 specifiec (default: and)
	url		CDATA	#IMPLIED	-- post has given url
	>
	*/
	public function EntryIf($attr,$content)
	{
		$if = new ArrayObject();
		$extended = null;
		
		$operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';
        
		if (isset($attr['type'])) {
			$type = trim($attr['type']);
			$type = !empty($type)?$type:'post';
			$if[] = '$_ctx->posts->post_type == "'.addslashes($type).'"';
		}
		
		if (isset($attr['url'])) {
			$url = trim($attr['url']);
			if (substr($url,0,1) == '!') {
				$url = substr($url,1);
				$if[] = '$_ctx->posts->post_url != "'.addslashes($url).'"';
			} else {
				$if[] = '$_ctx->posts->post_url == "'.addslashes($url).'"';
			}
		}
		
		if (isset($attr['first'])) {
			$sign = (boolean) $attr['first'] ? '=' : '!';
			$if[] = '$_ctx->posts->index() '.$sign.'= 0';
		}
		
		if (isset($attr['odd'])) {
			$sign = (boolean) $attr['odd'] ? '=' : '!';
			$if[] = '($_ctx->posts->index()+1)%2 '.$sign.'= 1';
		}
		
		if (isset($attr['extended'])) {
			$sign = (boolean) $attr['extended'] ? '' : '!';
			$if[] = $sign.'$_ctx->posts->isExtended()';
		}
		
		if (isset($attr['selected'])) {
			$sign = (boolean) $attr['selected'] ? '' : '!';
			$if[] = $sign.'(boolean)$_ctx->posts->post_selected';
		}
		
		$this->core->callBehavior('tplIfConditions','EntryIf',$attr,$content,$if);
		
		if (count($if) != 0) {
			return '<?php if('.implode(' '.$operator.' ', (array) $if).') : ?>'.$content.'<?php endif; ?>';
		} /*
		else {
			// Nothing in if statement => do not display content...
			return $content;
		}
		*/
	}
	
	/*dtd
	<!ELEMENT tpl:EntryIfFirst - O -- displays value if entry is the first one -->
	<!ATTLIST tpl:EntryIfFirst
	return	CDATA	#IMPLIED	-- value to display in case of success (default: first)
	>
	*/
	public function EntryIfFirst($attr)
	{
		$ret = isset($attr['return']) ? $attr['return'] : 'first';
		$ret = html::escapeHTML($ret);
		
		return
		'<?php if ($_ctx->posts->index() == 0) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryIfOdd - O -- displays value if entry is in an odd position -->
	<!ATTLIST tpl:EntryIfOdd
	return	CDATA	#IMPLIED	-- value to display in case of success (default: odd)
	>
	*/
	public function EntryIfOdd($attr)
	{
		$ret = isset($attr['return']) ? $attr['return'] : 'odd';
		$ret = html::escapeHTML($ret);
		
		return
		'<?php if (($_ctx->posts->index()+1)%2 == 1) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryIfSelected - O -- displays value if entry is selected -->
	<!ATTLIST tpl:EntryIfSelected
	return	CDATA	#IMPLIED	-- value to display in case of success (default: selected)
	>
	*/
	public function EntryIfSelected($attr)
	{
		$ret = isset($attr['return']) ? $attr['return'] : 'selected';
		$ret = html::escapeHTML($ret);
		
		return
		'<?php if ($_ctx->posts->post_selected) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryContent - O -- Entry content -->
	<!ATTLIST tpl:EntryContent
	absolute_urls	CDATA	#IMPLIED -- transforms local URLs to absolute one
	full			(1|0)	#IMPLIED -- returns full content with excerpt
	>
	*/
	public function EntryContent($attr)
	{
		$urls = '0';
		if (!empty($attr['absolute_urls'])) {
			$urls = '1';
		}
		
		$f = $this->getFilters($attr);
		
		if (!empty($attr['full'])) {
			return '<?php echo '.sprintf($f,
				'$_ctx->posts->getExcerpt('.$urls.')." ".$_ctx->posts->getContent('.$urls.')').'; ?>';
		} else {
			return '<?php echo '.sprintf($f,'$_ctx->posts->getContent('.$urls.')').'; ?>';
		}
	}
	
	/*dtd
	<!ELEMENT tpl:EntryExcerpt - O -- Entry excerpt -->
	<!ATTLIST tpl:EntryExcerpt
	absolute_urls	CDATA	#IMPLIED -- transforms local URLs to absolute one
	>
	*/
	public function EntryExcerpt($attr)
	{
		$urls = '0';
		if (!empty($attr['absolute_urls'])) {
			$urls = '1';
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->getExcerpt('.$urls.')').'; ?>';
	}
	
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorCommonName - O -- Entry author common name -->
	*/
	public function EntryAuthorCommonName($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->getAuthorCN()').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorDisplayName - O -- Entry author display name -->
	*/
	public function EntryAuthorDisplayName($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->user_displayname').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorID - O -- Entry author ID -->
	*/
	public function EntryAuthorID($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->user_id').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorEmail - O -- Entry author email -->
	<!ATTLIST tpl:EntryAuthorEmail
	spam_protected	(0|1)	#IMPLIED	-- protect email from spam (default: 1)
	>
	*/
	public function EntryAuthorEmail($attr)
	{
		$p = 'true';
		if (isset($attr['spam_protected']) && !$attr['spam_protected']) {
			$p = 'false';
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"\$_ctx->posts->getAuthorEmail(".$p.")").'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorLink - O -- Entry author link -->
	*/
	public function EntryAuthorLink($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->getAuthorLink()').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAuthorURL - O -- Entry author URL -->
	*/
	public function EntryAuthorURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->user_url').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryBasename - O -- Entry short URL (relative to /post) -->
	*/
	public function EntryBasename($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->post_url').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryFeedID - O -- Entry feed ID -->
	*/
	public function EntryFeedID($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->getFeedID()').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryFirstImage - O -- Extracts entry first image if exists -->
	<!ATTLIST tpl:EntryAuthorEmail
	size			(sq|t|s|m|o)	#IMPLIED	-- Image size to extract
	class		CDATA		#IMPLIED	-- Class to add on image tag
	>
	*/
	public function EntryFirstImage($attr)
	{
		$size = !empty($attr['size']) ? $attr['size'] : '';
		$class = !empty($attr['class']) ? $attr['class'] : '';
		$with_category = !empty($attr['with_category']) ? 'true' : 'false';
		
		return "<?php echo context::EntryFirstImageHelper('".addslashes($size)."','".addslashes($class)."'); ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryID - O -- Entry ID -->
	*/
	public function EntryID($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->post_id').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryLang - O --  Entry language or blog lang if not defined -->
	*/
	public function EntryLang($attr)
	{
		$f = $this->getFilters($attr);
		return
		'<?php if ($_ctx->posts->post_lang) { '.
			'echo '.sprintf($f,'$_ctx->posts->post_lang').'; '.
		'} else {'.
			'echo '.sprintf($f,'$core->blog->settings->system->lang').'; '.
		'} ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryNext - - -- Next entry block -->
	<!ATTLIST tpl:EntryNext
	restrict_to_lang		(0|1)	#IMPLIED	-- find next post in the same language (default: 0)
	>
	*/
	public function EntryNext($attr,$content)
	{
		$restrict_to_category = !empty($attr['restrict_to_category']) ? '1' : '0';
		$restrict_to_lang = !empty($attr['restrict_to_lang']) ? '1' : '0';
		
		return
		'<?php $next_post = $core->blog->getNextPost($_ctx->posts,1,'.$restrict_to_category.','.$restrict_to_lang.'); ?>'."\n".
		'<?php if ($next_post !== null) : ?>'.
			
			'<?php $_ctx->posts = $next_post; unset($next_post);'."\n".
			'while ($_ctx->posts->fetch()) : ?>'.
			$content.
			'<?php endwhile; $_ctx->posts = null; ?>'.
		"<?php endif; ?>\n";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryPrevious - - -- Previous entry block -->
	<!ATTLIST tpl:EntryPrevious
	restrict_to_category	(0|1)	#IMPLIED	-- find previous post in the same category (default: 0)
	restrict_to_lang		(0|1)	#IMPLIED	-- find next post in the same language (default: 0)
	>
	*/
	public function EntryPrevious($attr,$content)
	{
		$restrict_to_category = !empty($attr['restrict_to_category']) ? '1' : '0';
		$restrict_to_lang = !empty($attr['restrict_to_lang']) ? '1' : '0';
		
		return
		'<?php $prev_post = $core->blog->getNextPost($_ctx->posts,-1,'.$restrict_to_category.','.$restrict_to_lang.'); ?>'."\n".
		'<?php if ($prev_post !== null) : ?>'.
			
			'<?php $_ctx->posts = $prev_post; unset($prev_post);'."\n".
			'while ($_ctx->posts->fetch()) : ?>'.
			$content.
			'<?php endwhile; $_ctx->posts = null; ?>'.
		"<?php endif; ?>\n";
	}
	
	/*dtd
	<!ELEMENT tpl:EntryTitle - O -- Entry title -->
	*/
	public function EntryTitle($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->post_title').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryURL - O -- Entry URL -->
	*/
	public function EntryURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->posts->getURL()').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryDate - O -- Entry date -->
	<!ATTLIST tpl:EntryDate
	format	CDATA	#IMPLIED	-- date format (encoded in dc:str by default if iso8601 or rfc822 not specified)
	iso8601	CDATA	#IMPLIED	-- if set, tells that date format is ISO 8601
	rfc822	CDATA	#IMPLIED	-- if set, tells that date format is RFC 822
	upddt	CDATA	#IMPLIED	-- if set, uses the post update time
	creadt	CDATA	#IMPLIED	-- if set, uses the post creation time
	>
	*/
	public function EntryDate($attr)
	{
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}
		
		$iso8601 = !empty($attr['iso8601']);
		$rfc822 = !empty($attr['rfc822']);
		$type = (!empty($attr['creadt']) ? 'creadt' : '');
		$type = (!empty($attr['upddt']) ? 'upddt' : $type);
		
		$f = $this->getFilters($attr);
		
		if ($rfc822) {
			return '<?php echo '.sprintf($f,"\$_ctx->posts->getRFC822Date('".$type."')").'; ?>';
		} elseif ($iso8601) {
			return '<?php echo '.sprintf($f,"\$_ctx->posts->getISO8601Date('".$type."')").'; ?>';
		} else {
			return '<?php echo '.sprintf($f,"\$_ctx->posts->getDate('".$format."','".$type."')").'; ?>';
		}
	}
	
	/*dtd
	<!ELEMENT tpl:EntryTime - O -- Entry date -->
	<!ATTLIST tpl:EntryTime
	format	CDATA	#IMPLIED	-- time format 
	upddt	CDATA	#IMPLIED	-- if set, uses the post update time
	creadt	CDATA	#IMPLIED	-- if set, uses the post creation time
	>
	*/
	public function EntryTime($attr)
	{
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}
		
		$type = (!empty($attr['creadt']) ? 'creadt' : '');
		$type = (!empty($attr['upddt']) ? 'upddt' : $type);
        
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"\$_ctx->posts->getTime('".$format."','".$type."')").'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntriesHeader - - -- First entries result container -->
	*/
	public function EntriesHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->posts->isStart()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:EntriesFooter - - -- Last entries result container -->
	*/
	public function EntriesFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->posts->isEnd()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/* Languages -------------------------------------- */
	/*dtd
	<!ELEMENT tpl:Languages - - -- Languages loop -->
	<!ATTLIST tpl:Languages
	lang	CDATA	#IMPLIED	-- restrict loop on given lang
	order	(desc|asc)	#IMPLIED	-- languages ordering (default: desc)
	>
	*/
	public function Languages($attr,$content)
	{
		$p = "if (!isset(\$params)) \$params = array();\n";
		
		if (isset($attr['lang'])) {
			$p = "\$params['lang'] = '".addslashes($attr['lang'])."';\n";
		}
		
		$order = 'desc';
		if (isset($attr['order']) && preg_match('/^(desc|asc)$/i',$attr['order'])) {
			$p .= "\$params['order'] = '".$attr['order']."';\n ";
		}
		
		$res = "<?php\n";
		$res .= $p;
		$res .= $this->core->callBehavior("templatePrepareParams", 
			array("tag" => "Languages","method" => "blog::getLangs"), 
			$attr,$content);
		$res .= '$_ctx->langs = $core->blog->getLangs($params); unset($params);'."\n";
		$res .= "?>\n";
		
		$res .=
		'<?php if ($_ctx->langs->count() > 1) : '.
		'while ($_ctx->langs->fetch()) : ?>'.$content.
		'<?php endwhile; $_ctx->langs = null; endif; ?>';
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:LanguagesHeader - - -- First languages result container -->
	*/
	public function LanguagesHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->langs->isStart()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:LanguagesFooter - - -- Last languages result container -->
	*/
	public function LanguagesFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->langs->isEnd()) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:LanguageCode - O -- Language code -->
	*/
	public function LanguageCode($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->langs->post_lang').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:LanguageIfCurrent - - -- tests if post language is current language -->
	*/
	public function LanguageIfCurrent($attr,$content)
	{
		return
		"<?php if (\$_ctx->cur_lang == \$_ctx->langs->post_lang) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:LanguageURL - O -- Language URL -->
	*/
	public function LanguageURL($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'$core->blog->url.$core->url->getURLFor("lang",'.
			'$_ctx->langs->post_lang)').'; ?>';
	}
	
	/* Pagination ------------------------------------- */
	/*dtd
	<!ELEMENT tpl:Pagination - - -- Pagination container -->
	<!ATTLIST tpl:Pagination
	no_context	(0|1)	#IMPLIED	-- override test on posts count vs number of posts per page
	>
	*/
	public function Pagination($attr,$content)
	{
		$p = "<?php\n";
		$p .= '$params = $_ctx->post_params;'."\n";
		$p .= $this->core->callBehavior("templatePrepareParams", 
			array("tag" => "Pagination","method" => "blog::getPosts"), 
			$attr,$content);
		$p .= '$_ctx->pagination = $core->blog->getPosts($params,true); unset($params);'."\n";
		$p .= "?>\n";
		
		if (isset($attr['no_context']) && $attr['no_context']) {
			return $p.$content;
		}
        
		return
			$p.
			'<?php if ($_ctx->pagination->f(0) > $_ctx->posts->count()) : ?>'.
			$content.
			'<?php endif; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:PaginationCounter - O -- Number of pages -->
	*/
	public function PaginationCounter($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"context::PaginationNbPages()").'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:PaginationCurrent - O -- current page -->
	*/
	public function PaginationCurrent($attr)
	{
		$offset = 0;
		if (isset($attr['offset'])) {
			$offset = (integer) $attr['offset'];
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"context::PaginationPosition(".$offset.")").'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:PaginationIf - - -- pages tests -->
	<!ATTLIST tpl:PaginationIf
	start	(0|1)	#IMPLIED	-- test if we are at first page (value : 1) or not (value : 0)
	end	(0|1)	#IMPLIED	-- test if we are at last page (value : 1) or not (value : 0)
	>
	*/
	public function PaginationIf($attr,$content)
	{
		$if = array();
		
		if (isset($attr['start'])) {
			$sign = (boolean) $attr['start'] ? '' : '!';
			$if[] = $sign.'context::PaginationStart()';
		}
		
		if (isset($attr['end'])) {
			$sign = (boolean) $attr['end'] ? '' : '!';
			$if[] = $sign.'context::PaginationEnd()';
		}
		
		$this->core->callBehavior('tplIfConditions','PaginationIf',$attr,$content,$if);
		
		if (count($if) != 0) {
			return '<?php if('.implode(' && ', (array) $if).') : ?>'.$content.'<?php endif; ?>';
		} else {
			return $content;
		}
	}
	
	/*dtd
	<!ELEMENT tpl:PaginationURL - O -- link to previoux/next page -->
	<!ATTLIST tpl:PaginationURL
	offset	CDATA	#IMPLIED	-- page offset (negative for previous pages), default: 0
	>
	*/
	public function PaginationURL($attr)
	{
		$offset = 0;
		if (isset($attr['offset'])) {
			$offset = (integer) $attr['offset'];
		}
		
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,"context::PaginationURL(".$offset.")").'; ?>';
	}
	
	# System
	/*dtd
	<!ELEMENT tpl:SysBehavior - O -- Call a given behavior -->
	<!ATTLIST tpl:SysBehavior
	behavior	CDATA	#IMPLIED	-- behavior to call
	>
	*/
	public function SysBehavior($attr,$raw)
	{
		if (!isset($attr['behavior'])) {
			return;
		}
		
		$b = addslashes($attr['behavior']);
		return
		'<?php if ($core->hasBehavior(\''.$b.'\')) { '.
			'$core->callBehavior(\''.$b.'\',$core,$_ctx);'.
		'} ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:SysIf - - -- System settings tester container -->
	<!ATTLIST tpl:SysIf
	posts			(0|1)	#IMPLIED	-- test if posts are set in current context (value : 1) or not (value : 0)
	blog_lang			CDATA	#IMPLIED	-- tests if blog language is the one given in parameter
	current_tpl		CDATA	#IMPLIED	-- tests if current template is the one given in paramater
	current_mode		CDATA	#IMPLIED	-- tests if current URL mode is the one given in parameter
	has_tpl			CDATA     #IMPLIED  -- tests if a named template exists
	has_tag			CDATA     #IMPLIED  -- tests if a named template tag exists (see Tag plugin for code)
	blog_id			CDATA     #IMPLIED  -- tests if current blog ID is the one given in parameter
	operator			(and|or)	#IMPLIED	-- combination of conditions, if more than 1 specifiec (default: and)
	>
	*/
	public function SysIf($attr,$content)
	{
		$if = new ArrayObject();
		$is_ping = null;
		
		$operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';
		
		if (isset($attr['posts'])) {
			$sign = (boolean) $attr['posts'] ? '!' : '=';
			$if[] = '$_ctx->posts '.$sign.'== null';
		}
		
		if (isset($attr['blog_lang'])) {
			$if[] = "\$core->blog->settings->system->lang == '".addslashes($attr['blog_lang'])."'";
		}
		
		if (isset($attr['current_tpl'])) {
			$sign = '=';
			if (substr($attr['current_tpl'],0,1) == '!') {
				$sign = '!';
				$attr['current_tpl'] = substr($attr['current_tpl'],1);
			}
			$if[] = "\$_ctx->current_tpl ".$sign."= '".addslashes($attr['current_tpl'])."'";
		}
		
		if (isset($attr['current_mode'])) {
			$sign = '=';
			if (substr($attr['current_mode'],0,1) == '!') {
				$sign = '!';
				$attr['current_mode'] = substr($attr['current_mode'],1);
			}
			$if[] = "\$core->url->type ".$sign."= '".addslashes($attr['current_mode'])."'";
		}
		
		if (isset($attr['has_tpl'])) {
			$sign = '';
			if (substr($attr['has_tpl'],0,1) == '!') {
				$sign = '!';
				$attr['has_tpl'] = substr($attr['has_tpl'],1);
			}
			$if[] = $sign."\$core->tpl->getFilePath('".addslashes($attr['has_tpl'])."') !== false";
		}
		
		if (isset($attr['blog_id'])) {
			$sign = '';
			if (substr($attr['blog_id'],0,1) == '!') {
				$sign = '!';
				$attr['blog_id'] = substr($attr['blog_id'],1);
			}
			$if[] = $sign."(\$core->blog->id == '".addslashes($attr['blog_id'])."')";
		}
		
		if (isset($attr['search_count']) &&
			preg_match('/^((=|!|&gt;|&lt;)=|(&gt;|&lt;))\s*[0-9]+$/',trim($attr['search_count']))) {
			$if[] = '(isset($_search_count) && $_search_count '.html::decodeEntities($attr['search_count']).')';
		}
		
		$this->core->callBehavior('tplIfConditions','SysIf',$attr,$content,$if);
		
		if (count($if) != 0) {
			return '<?php if('.implode(' '.$operator.' ', (array) $if).') : ?>'.$content.'<?php endif; ?>';
		} else {
			return $content;
		}
	}
	
	/*dtd
	<!ELEMENT tpl:SysFeedSubtitle - O -- Feed subtitle -->
	*/
	public function SysFeedSubtitle($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php if ($_ctx->feed_subtitle !== null) { echo '.sprintf($f,'$_ctx->feed_subtitle').';} ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:SysIfFormError - O -- Container displayed if an error has been detected after form submission -->
	*/
	public function SysIfFormError($attr,$content)
	{
		return
		'<?php if ($_ctx->form_error !== null) : ?>'.
		$content.
		'<?php endif; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:SysIfFormError - O -- Form error -->
	*/
	public function SysFormError($attr)
	{
		return
		'<?php if ($_ctx->form_error !== null) { echo $_ctx->form_error; } ?>';
	}
	
	public function SysPoweredBy($attr)
	{
		return
		'<?php printf(__("Powered by %s"),"<a href=\"http://dotclear.org/\">Dotclear</a>"); ?>';
	}
	
	public function SysSearchString($attr)
	{
		$s = isset($attr['string']) ? $attr['string'] : '%1$s';
		
		$f = $this->getFilters($attr);
		return '<?php if (isset($_search)) { echo sprintf(__(\''.$s.'\'),'.sprintf($f,'$_search').',$_search_count);} ?>';
	}
	
	public function SysSelfURI($attr)
	{
		$f = $this->getFilters($attr);
		return '<?php echo '.sprintf($f,'http::getSelfURI()').'; ?>';
	}
}

# Template nodes, for parsing purposes

# Generic list node, this one may only be instanciated 
# once for root element
class tplNode 
{
	# Basic tree structure : links to parent, children forrest
	protected $parentNode;
	protected $children;
	
	public function __construct() {
		$this->children = array();
		$this->parentNode = null;
	}
	
	// Returns compiled block
	public function compile($tpl) {
		$res='';
		foreach ($this->children as $child) {
			$res .= $child->compile($tpl);
		}
		return $res;
	}
	
	# Add a children to current node
	public function addChild ($child) {
		$this->children[] = $child;
		$child->setParent($this);
	}
	
	# Defines parent for current node
	protected function setParent($parent) {
		$this->parentNode = $parent;
	}
	
	# Retrieves current node parent.
	# If parent is root node, null is returned
	public function getParent() {
		return $this->parentNode;
	}
	
	# Current node tag
	public function getTag() {
		return "ROOT";
	}
}

// Text node, for any non-tpl content
class tplNodeText extends tplNode 
{
	// Simple text node, only holds its content
	protected $content;
	
	public function __construct($text) {
		parent::__construct();
		$this->content=$text;
	}
	
	public function compile($tpl) {
		return $this->content;
	}
	
	public function getTag() {
		return "TEXT";
	}
}

// Block node, for all <tpl:Tag>...</tpl:Tag>
class tplNodeBlock extends tplNode 
{
	protected $attr;
	protected $tag;
	protected $closed;
    
	public function __construct($tag,$attr) {
		parent::__construct();
		$this->content='';
		$this->tag = $tag;
		$this->attr = $attr;
		$this->closed=false;
	}
	public function setClosing() {
		$this->closed = true;
	}
	public function isClosed() {
		return $this->closed;
	}
	public function compile($tpl) {
		if ($this->closed) {
			$content = parent::compile($tpl);
			return $tpl->compileBlockNode($this->tag,$this->attr,$content);
		} else {
			// if tag has not been closed, silently ignore its content...
			return '';
		}
	}
	public function getTag() {
		return $this->tag;
	}
}

// Value node, for all {{tpl:Tag}}
class tplNodeValue extends tplNode 
{
	protected $attr;
	protected $str_attr;
	protected $tag;
	
	public function __construct($tag,$attr,$str_attr) {
		parent::__construct();
		$this->content='';
		$this->tag = $tag;
		$this->attr = $attr;
		$this->str_attr = $str_attr;
	}
	
	public function compile($tpl) {
		return $tpl->compileValueNode($this->tag,$this->attr,$this->str_attr);
	}
	
	public function getTag() {
		return $this->tag;
	}
}

?>
