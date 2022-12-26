<?php
/**
 * @class tplNode
 * @brief Template nodes, for parsing purposes
 *
 * Generic list node, this one may only be instanciated once for root element
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class tplNode
{
    /**
     * Basic tree structure : links to parent, children forrest
     *
     * @var null|tplNode|tplNodeBlock|tplNodeBlockDefinition|tplNodeText|tplNodeValue|tplNodeValueParent
     */
    protected $parentNode;

    /**
     * Node children
     *
     * @var ArrayObject
     */
    protected $children;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->children   = new ArrayObject();
        $this->parentNode = null;
    }

    /**
     * Indicates that the node is closed.
     */
    public function setClosing(): void
    {
        // Nothing to do at this level
    }

    /**
     * Returns compiled block
     *
     * @param  template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(template $tpl)
    {
        $res = '';
        foreach ($this->children as $child) {
            $res .= $child->compile($tpl);
        }

        return $res;
    }

    /**
     * Add a children to current node.
     *
     * @param      tplNode|tplNodeBlock|tplNodeBlockDefinition|tplNodeText|tplNodeValue|tplNodeValueParent  $child  The child
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Set current node children.
     *
     * @param      ArrayObject  $children  The children
     */
    public function setChildren($children)
    {
        $this->children = $children;
        foreach ($this->children as $child) {
            $child->setParent($this);
        }
    }

    #

    /**
     * Defines parent for current node.
     *
     * @param      null|tplNode|tplNodeBlock|tplNodeBlockDefinition|tplNodeValue|tplNodeValueParent  $parent  The parent
     */
    protected function setParent($parent)
    {
        $this->parentNode = $parent;
    }

    /**
     * Retrieves current node parent.
     *
     * If parent is root node, null is returned
     *
     * @return     null|tplNode|tplNodeBlock|tplNodeBlockDefinition|tplNodeValue|tplNodeValueParent  The parent.
     */
    public function getParent()
    {
        return $this->parentNode;
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return 'ROOT';
    }
}
