<?php
/**
 * @class TplNodeValue
 *
 * Value node, for all {{tpl:Tag}}
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

class TplNodeValue extends TplNode
{
    /**
     * Node tag
     *
     * @var string
     */
    protected $tag;

    /**
     * Node attributes
     *
     * @var array
     */
    protected $attr;

    /**
     * Node string attributes
     *
     * @var string
     */
    protected $str_attr;

    /**
     * Node content
     *
     * @var string
     */
    protected $content;

    /**
     * Constructs a new instance.
     *
     * @param      string  $tag       The tag
     * @param      array   $attr      The attribute
     * @param      string  $str_attr  The string attribute
     */
    public function __construct(string $tag, array $attr, string $str_attr)
    {
        parent::__construct();
        $this->content  = '';
        $this->tag      = $tag;
        $this->attr     = $attr;
        $this->str_attr = $str_attr;
    }

    /**
     * Compile the value node
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(Template $tpl): string
    {
        return $tpl->compileValueNode($this->tag, $this->attr, $this->str_attr);
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return $this->tag;
    }
}
